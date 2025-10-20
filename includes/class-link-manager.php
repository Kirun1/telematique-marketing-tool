<?php

class ProductScraper_Link_Manager {
    
    private $internal_links = array();
    private $external_links = array();
    private $broken_links = array();
    
    public function __construct() {
        add_action('wp_ajax_scan_links', array($this, 'ajax_scan_links'));
        add_action('wp_ajax_check_broken_links', array($this, 'ajax_check_broken_links'));
        add_action('save_post', array($this, 'scan_post_links'));
        add_action('delete_post', array($this, 'cleanup_post_links'));
    }
    
    /**
     * Scan all posts for links
     */
    public function scan_all_links() {
        $post_types = get_post_types(array('public' => true), 'names');
        $links_data = array(
            'internal' => array(),
            'external' => array(),
            'broken' => array(),
            'stats' => array()
        );
        
        foreach ($post_types as $post_type) {
            $posts = get_posts(array(
                'post_type' => $post_type,
                'numberposts' => -1,
                'post_status' => 'publish'
            ));
            
            foreach ($posts as $post) {
                $post_links = $this->extract_links_from_content($post->post_content, $post->ID);
                $links_data = $this->merge_links_data($links_data, $post_links);
            }
        }
        
        // Update stats
        $links_data['stats'] = $this->calculate_link_stats($links_data);
        
        // Store in database
        update_option('product_scraper_links_data', $links_data);
        update_option('product_scraper_links_scan_timestamp', current_time('timestamp'));
        
        return $links_data;
    }
    
    /**
     * Scan links from a specific post
     */
    public function scan_post_links($post_id) {
        $post = get_post($post_id);
        if (!$post || $post->post_status !== 'publish') {
            return;
        }
        
        $post_links = $this->extract_links_from_content($post->post_content, $post_id);
        $existing_data = get_option('product_scraper_links_data', array(
            'internal' => array(),
            'external' => array(),
            'broken' => array(),
            'stats' => array()
        ));
        
        // Remove old links from this post
        $existing_data = $this->remove_post_links($existing_data, $post_id);
        
        // Merge new links
        $updated_data = $this->merge_links_data($existing_data, $post_links);
        $updated_data['stats'] = $this->calculate_link_stats($updated_data);
        
        update_option('product_scraper_links_data', $updated_data);
        
        return $updated_data;
    }
    
    /**
     * Extract links from post content
     */
    private function extract_links_from_content($content, $post_id) {
        $links = array(
            'internal' => array(),
            'external' => array()
        );
        
        if (empty($content)) {
            return $links;
        }
        
        // Use DOMDocument to parse links
        $dom = new DOMDocument();
        @$dom->loadHTML($content);
        
        $anchor_tags = $dom->getElementsByTagName('a');
        
        foreach ($anchor_tags as $anchor) {
            $href = $anchor->getAttribute('href');
            $text = $anchor->textContent;
            $nofollow = $anchor->getAttribute('rel') === 'nofollow';
            
            if (empty($href) || $href === '#') {
                continue;
            }
            
            $link_data = array(
                'url' => $href,
                'anchor_text' => trim($text),
                'source_post_id' => $post_id,
                'source_url' => get_permalink($post_id),
                'nofollow' => $nofollow,
                'found_date' => current_time('mysql')
            );
            
            if ($this->is_internal_link($href)) {
                $link_data['internal_url'] = $this->normalize_internal_url($href);
                $link_data['target_post_id'] = url_to_postid($link_data['internal_url']);
                $links['internal'][] = $link_data;
            } else {
                $link_data['domain'] = parse_url($href, PHP_URL_HOST);
                $links['external'][] = $link_data;
            }
        }
        
        return $links;
    }
    
    /**
     * Check if link is internal
     */
    private function is_internal_link($url) {
        $home_url = home_url();
        $home_domain = parse_url($home_url, PHP_URL_HOST);
        $link_domain = parse_url($url, PHP_URL_HOST);
        
        // Handle relative URLs
        if (empty($link_domain)) {
            return true;
        }
        
        return $link_domain === $home_domain;
    }
    
    /**
     * Normalize internal URL
     */
    private function normalize_internal_url($url) {
        if (strpos($url, home_url()) === 0) {
            return $url;
        }
        
        // Handle relative URLs
        if (strpos($url, '/') === 0) {
            return home_url($url);
        }
        
        return $url;
    }
    
    /**
     * Merge links data
     */
    private function merge_links_data($existing_data, $new_links) {
        // Merge internal links
        foreach ($new_links['internal'] as $new_link) {
            $existing_data['internal'][] = $new_link;
        }
        
        // Merge external links
        foreach ($new_links['external'] as $new_link) {
            $existing_data['external'][] = $new_link;
        }
        
        return $existing_data;
    }
    
    /**
     * Remove links from a specific post
     */
    private function remove_post_links($links_data, $post_id) {
        // Remove internal links from this post
        $links_data['internal'] = array_filter($links_data['internal'], function($link) use ($post_id) {
            return $link['source_post_id'] != $post_id;
        });
        
        // Remove external links from this post
        $links_data['external'] = array_filter($links_data['external'], function($link) use ($post_id) {
            return $link['source_post_id'] != $post_id;
        });
        
        // Reset array keys
        $links_data['internal'] = array_values($links_data['internal']);
        $links_data['external'] = array_values($links_data['external']);
        
        return $links_data;
    }
    
    /**
     * Calculate link statistics
     */
    private function calculate_link_stats($links_data) {
        $stats = array(
            'total_internal_links' => count($links_data['internal']),
            'total_external_links' => count($links_data['external']),
            'internal_domains' => array(),
            'external_domains' => array(),
            'links_per_post' => array(),
            'nofollow_count' => 0
        );
        
        // Count domains and links per post
        foreach ($links_data['internal'] as $link) {
            $post_id = $link['source_post_id'];
            if (!isset($stats['links_per_post'][$post_id])) {
                $stats['links_per_post'][$post_id] = 0;
            }
            $stats['links_per_post'][$post_id]++;
            
            if ($link['nofollow']) {
                $stats['nofollow_count']++;
            }
        }
        
        foreach ($links_data['external'] as $link) {
            $domain = $link['domain'];
            if (!isset($stats['external_domains'][$domain])) {
                $stats['external_domains'][$domain] = 0;
            }
            $stats['external_domains'][$domain]++;
            
            $post_id = $link['source_post_id'];
            if (!isset($stats['links_per_post'][$post_id])) {
                $stats['links_per_post'][$post_id] = 0;
            }
            $stats['links_per_post'][$post_id]++;
            
            if ($link['nofollow']) {
                $stats['nofollow_count']++;
            }
        }
        
        // Sort domains by frequency
        arsort($stats['external_domains']);
        $stats['external_domains'] = array_slice($stats['external_domains'], 0, 20);
        
        return $stats;
    }
    
    /**
     * Check for broken links
     */
    public function check_broken_links($links_data = null) {
        if (!$links_data) {
            $links_data = get_option('product_scraper_links_data', array());
        }
        
        $broken_links = array();
        $checked_links = 0;
        
        // Check internal links
        foreach ($links_data['internal'] as $link) {
            if ($this->is_broken_internal_link($link)) {
                $broken_links[] = array_merge($link, array(
                    'error' => 'Internal link broken',
                    'status_code' => 404
                ));
            }
            $checked_links++;
            
            // Prevent timeout for large sites
            if ($checked_links >= 50) {
                break;
            }
        }
        
        // Check external links (sample)
        $external_sample = array_slice($links_data['external'], 0, 20);
        foreach ($external_sample as $link) {
            $status = $this->check_external_link_status($link['url']);
            if ($status >= 400) {
                $broken_links[] = array_merge($link, array(
                    'error' => 'External link broken',
                    'status_code' => $status
                ));
            }
            $checked_links++;
        }
        
        // Update broken links data
        $links_data['broken'] = $broken_links;
        update_option('product_scraper_links_data', $links_data);
        
        return $broken_links;
    }
    
    /**
     * Check if internal link is broken
     */
    private function is_broken_internal_link($link) {
        if (isset($link['target_post_id']) && $link['target_post_id'] > 0) {
            $post = get_post($link['target_post_id']);
            return !$post || $post->post_status !== 'publish';
        }
        
        // Fallback: check if URL exists
        $response = wp_remote_head($link['url'], array('timeout' => 10));
        if (is_wp_error($response)) {
            return true;
        }
        
        $status_code = wp_remote_retrieve_response_code($response);
        return $status_code >= 400;
    }
    
    /**
     * Check external link status
     */
    private function check_external_link_status($url) {
        $response = wp_remote_head($url, array(
            'timeout' => 10,
            'redirection' => 2
        ));
        
        if (is_wp_error($response)) {
            return 0; // Unknown status
        }
        
        return wp_remote_retrieve_response_code($response);
    }
    
    /**
     * Get internal linking suggestions
     */
    public function get_linking_suggestions() {
        $links_data = get_option('product_scraper_links_data', array());
        $suggestions = array();
        
        // Find posts with few or no internal links
        $posts_with_few_links = array();
        foreach ($links_data['stats']['links_per_post'] as $post_id => $link_count) {
            if ($link_count <= 2) {
                $post = get_post($post_id);
                if ($post) {
                    $posts_with_few_links[] = array(
                        'post_id' => $post_id,
                        'title' => $post->post_title,
                        'link_count' => $link_count,
                        'url' => get_permalink($post_id)
                    );
                }
            }
        }
        
        if (!empty($posts_with_few_links)) {
            $suggestions[] = array(
                'type' => 'low_internal_links',
                'priority' => 'medium',
                'message' => count($posts_with_few_links) . ' posts have few internal links',
                'data' => $posts_with_few_links
            );
        }
        
        // Find orphaned posts (no incoming links)
        $orphaned_posts = $this->find_orphaned_posts($links_data);
        if (!empty($orphaned_posts)) {
            $suggestions[] = array(
                'type' => 'orphaned_posts',
                'priority' => 'high',
                'message' => count($orphaned_posts) . ' posts have no incoming internal links',
                'data' => $orphaned_posts
            );
        }
        
        // Find opportunities for internal linking
        $linking_opportunities = $this->find_linking_opportunities($links_data);
        if (!empty($linking_opportunities)) {
            $suggestions[] = array(
                'type' => 'linking_opportunities',
                'priority' => 'low',
                'message' => count($linking_opportunities) . ' potential internal linking opportunities found',
                'data' => $linking_opportunities
            );
        }
        
        return $suggestions;
    }
    
    /**
     * Find orphaned posts (no incoming links)
     */
    private function find_orphaned_posts($links_data) {
        $all_posts = get_posts(array(
            'post_type' => 'post',
            'numberposts' => -1,
            'post_status' => 'publish'
        ));
        
        $posts_with_incoming_links = array();
        foreach ($links_data['internal'] as $link) {
            if (isset($link['target_post_id']) && $link['target_post_id'] > 0) {
                $posts_with_incoming_links[$link['target_post_id']] = true;
            }
        }
        
        $orphaned_posts = array();
        foreach ($all_posts as $post) {
            if (!isset($posts_with_incoming_links[$post->ID]) && $post->post_parent == 0) {
                $orphaned_posts[] = array(
                    'post_id' => $post->ID,
                    'title' => $post->post_title,
                    'url' => get_permalink($post->ID)
                );
            }
        }
        
        return $orphaned_posts;
    }
    
    /**
     * Find linking opportunities
     */
    private function find_linking_opportunities($links_data) {
        $opportunities = array();
        
        // This would implement more advanced keyword-based linking suggestions
        // For now, return empty array
        return $opportunities;
    }
    
    /**
     * Cleanup links when post is deleted
     */
    public function cleanup_post_links($post_id) {
        $links_data = get_option('product_scraper_links_data', array());
        $updated_data = $this->remove_post_links($links_data, $post_id);
        $updated_data['stats'] = $this->calculate_link_stats($updated_data);
        update_option('product_scraper_links_data', $updated_data);
    }
    
    /**
     * AJAX handler for scanning links
     */
    public function ajax_scan_links() {
        check_ajax_referer('product_scraper_nonce', 'nonce');
        
        $links_data = $this->scan_all_links();
        
        wp_send_json_success(array(
            'links_data' => $links_data,
            'timestamp' => get_option('product_scraper_links_scan_timestamp')
        ));
    }
    
    /**
     * AJAX handler for checking broken links
     */
    public function ajax_check_broken_links() {
        check_ajax_referer('product_scraper_nonce', 'nonce');
        
        $links_data = get_option('product_scraper_links_data', array());
        $broken_links = $this->check_broken_links($links_data);
        
        wp_send_json_success(array(
            'broken_links' => $broken_links,
            'total_checked' => count($broken_links)
        ));
    }
    
    /**
     * Get link report
     */
    public function get_link_report() {
        $links_data = get_option('product_scraper_links_data', array());
        $suggestions = $this->get_linking_suggestions();
        
        return array(
            'links_data' => $links_data,
            'suggestions' => $suggestions,
            'last_scan' => get_option('product_scraper_links_scan_timestamp')
        );
    }
}