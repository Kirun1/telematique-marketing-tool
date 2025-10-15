<?php
class ProductScraper_Advanced_Sitemap
{
    private $sitemap_path;
    private $base_url;
    private $max_urls_per_sitemap;
    private $sitemap_indexes;

    public function __construct()
    {
        $this->sitemap_path = ABSPATH;
        $this->base_url = home_url();
        $this->max_urls_per_sitemap = 500; // Google's recommended limit
        $this->sitemap_indexes = array();
    }

    public function generate_sitemaps()
    {
        $sitemap_types = array(
            'posts' => $this->generate_post_sitemap(),
            'pages' => $this->generate_page_sitemap(),
            'products' => $this->generate_product_sitemap(),
            'categories' => $this->generate_category_sitemap(),
            'tags' => $this->generate_tag_sitemap(),
            'authors' => $this->generate_author_sitemap(),
            'images' => $this->generate_image_sitemap(),
            'videos' => $this->generate_video_sitemap()
        );

        // News sitemap for eligible sites
        if ($this->is_news_site()) {
            $sitemap_types['news'] = $this->generate_news_sitemap();
        }

        // Generate sitemap index
        $sitemap_types['index'] = $this->generate_sitemap_index();

        return $sitemap_types;
    }

    /**
     * Generate post sitemap
     */
    private function generate_post_sitemap()
    {
        $posts = get_posts(array(
            'post_type' => 'post',
            'post_status' => 'publish',
            'numberposts' => -1,
            'orderby' => 'modified',
            'order' => 'DESC'
        ));

        $urls = array();
        foreach ($posts as $post) {
            $url_data = array(
                'loc' => get_permalink($post),
                'lastmod' => $this->format_date($post->post_modified),
                'changefreq' => $this->determine_change_frequency($post),
                'priority' => $this->calculate_priority($post, 'post')
            );

            // Add images if available
            $images = $this->get_post_images($post);
            if (!empty($images)) {
                $url_data['images'] = $images;
            }

            $urls[] = $url_data;
        }

        return $this->create_sitemap_file($urls, 'post-sitemap');
    }

    /**
     * Generate page sitemap
     */
    private function generate_page_sitemap()
    {
        $pages = get_pages(array(
            'post_status' => 'publish',
            'sort_column' => 'post_modified',
            'sort_order' => 'DESC'
        ));

        $urls = array();
        foreach ($pages as $page) {
            // Skip noindex pages
            if ($this->is_noindex_page($page->ID)) {
                continue;
            }

            $urls[] = array(
                'loc' => get_permalink($page),
                'lastmod' => $this->format_date($page->post_modified),
                'changefreq' => $this->determine_change_frequency($page),
                'priority' => $this->calculate_priority($page, 'page')
            );
        }

        return $this->create_sitemap_file($urls, 'page-sitemap');
    }

    /**
     * Generate product sitemap
     */
    private function generate_product_sitemap()
    {
        if (!class_exists('WooCommerce')) {
            return array('error' => 'WooCommerce not active');
        }

        $products = wc_get_products(array(
            'status' => 'publish',
            'limit' => -1,
            'orderby' => 'modified',
            'order' => 'DESC'
        ));

        $urls = array();
        foreach ($products as $product) {
            $product_data = array(
                'loc' => get_permalink($product->get_id()),
                'lastmod' => $this->format_date($product->get_date_modified()),
                'changefreq' => 'weekly',
                'priority' => 0.8
            );

            // Add product-specific data
            $product_data['product'] = array(
                'name' => $product->get_name(),
                'price' => $product->get_price(),
                'currency' => get_woocommerce_currency(),
                'availability' => $product->is_in_stock() ? 'in stock' : 'out of stock'
            );

            // Add product images
            $image_id = $product->get_image_id();
            if ($image_id) {
                $product_data['images'] = array(
                    array(
                        'loc' => wp_get_attachment_url($image_id),
                        'title' => $product->get_name()
                    )
                );
            }

            // Add gallery images
            $gallery_ids = $product->get_gallery_image_ids();
            foreach ($gallery_ids as $gallery_id) {
                $product_data['images'][] = array(
                    'loc' => wp_get_attachment_url($gallery_id),
                    'title' => $product->get_name() . ' Gallery'
                );
            }

            $urls[] = $product_data;
        }

        return $this->create_sitemap_file($urls, 'product-sitemap');
    }

    /**
     * Generate category sitemap
     */
    private function generate_category_sitemap()
    {
        $categories = get_categories(array(
            'taxonomy' => 'category',
            'hide_empty' => true,
            'orderby' => 'count',
            'order' => 'DESC'
        ));

        $urls = array();
        foreach ($categories as $category) {
            $urls[] = array(
                'loc' => get_category_link($category),
                'lastmod' => $this->get_category_lastmod($category),
                'changefreq' => 'weekly',
                'priority' => 0.6
            );
        }

        return $this->create_sitemap_file($urls, 'category-sitemap');
    }

    /**
     * Generate tag sitemap
     */
    private function generate_tag_sitemap()
    {
        $tags = get_tags(array(
            'hide_empty' => true,
            'orderby' => 'count',
            'order' => 'DESC'
        ));

        $urls = array();
        foreach ($tags as $tag) {
            $urls[] = array(
                'loc' => get_tag_link($tag),
                'lastmod' => $this->get_tag_lastmod($tag),
                'changefreq' => 'monthly',
                'priority' => 0.4
            );
        }

        return $this->create_sitemap_file($urls, 'tag-sitemap');
    }

    /**
     * Generate author sitemap
     */
    private function generate_author_sitemap()
    {
        $authors = get_users(array(
            'who' => 'authors',
            'has_published_posts' => true,
            'fields' => array('ID', 'user_nicename', 'user_registered')
        ));

        $urls = array();
        foreach ($authors as $author) {
            $urls[] = array(
                'loc' => get_author_posts_url($author->ID),
                'lastmod' => $this->get_author_lastmod($author->ID),
                'changefreq' => 'weekly',
                'priority' => 0.5
            );
        }

        return $this->create_sitemap_file($urls, 'author-sitemap');
    }

    /**
     * Generate image sitemap
     */
    private function generate_image_sitemap()
    {
        $images = get_posts(array(
            'post_type' => 'attachment',
            'post_mime_type' => 'image',
            'post_status' => 'inherit',
            'numberposts' => 1000, // Limit for performance
            'orderby' => 'post_modified',
            'order' => 'DESC'
        ));

        $urls = array();
        foreach ($images as $image) {
            $parent_post = get_post($image->post_parent);

            $image_data = array(
                'loc' => wp_get_attachment_url($image->ID),
                'title' => get_the_title($image->ID),
                'caption' => $this->get_image_caption($image),
                'license' => $this->get_image_license($image)
            );

            // If image is attached to a post, use that post's URL
            if ($parent_post && $parent_post->post_status === 'publish') {
                $urls[] = array(
                    'loc' => get_permalink($parent_post),
                    'lastmod' => $this->format_date($image->post_modified),
                    'images' => array($image_data)
                );
            } else {
                // Standalone images
                $urls[] = array(
                    'loc' => $this->base_url . '/image/' . $image->ID,
                    'lastmod' => $this->format_date($image->post_modified),
                    'images' => array($image_data)
                );
            }
        }

        return $this->create_sitemap_file($urls, 'image-sitemap', true);
    }

    /**
     * Generate video sitemap
     */
    private function generate_video_sitemap()
    {
        $videos = get_posts(array(
            'post_type' => 'any',
            'post_status' => 'publish',
            'numberposts' => 500,
            'meta_query' => array(
                array(
                    'key' => '_video_url',
                    'compare' => 'EXISTS'
                )
            )
        ));

        $urls = array();
        foreach ($videos as $video_post) {
            $video_url = get_post_meta($video_post->ID, '_video_url', true);
            $video_thumbnail = get_post_meta($video_post->ID, '_video_thumbnail', true);

            if (!$video_url) continue;

            $video_data = array(
                'thumbnail_loc' => $video_thumbnail ?: $this->get_video_thumbnail($video_post),
                'title' => get_the_title($video_post->ID),
                'description' => wp_trim_words(get_the_excerpt($video_post->ID), 50),
                'content_loc' => $video_url,
                'duration' => get_post_meta($video_post->ID, '_video_duration', true) ?: 0,
                'publication_date' => $this->format_date($video_post->post_date),
                'family_friendly' => 'yes',
                'requires_subscription' => 'no',
                'live' => 'no'
            );

            $urls[] = array(
                'loc' => get_permalink($video_post->ID),
                'lastmod' => $this->format_date($video_post->post_modified),
                'video' => $video_data
            );
        }

        return $this->create_sitemap_file($urls, 'video-sitemap', false, 'video');
    }

    /**
     * Generate news sitemap
     */
    private function generate_news_sitemap()
    {
        $news_posts = get_posts(array(
            'post_type' => 'post',
            'post_status' => 'publish',
            'date_query' => array(
                array(
                    'after' => '2 days ago'
                )
            ),
            'numberposts' => 1000, // Google News limit
            'orderby' => 'date',
            'order' => 'DESC'
        ));

        $urls = array();
        foreach ($news_posts as $post) {
            $categories = get_the_category($post->ID);
            $news_keywords = array();

            foreach ($categories as $category) {
                $news_keywords[] = $category->name;
            }

            $tags = get_the_tags($post->ID);
            if ($tags) {
                foreach ($tags as $tag) {
                    $news_keywords[] = $tag->name;
                }
            }

            $urls[] = array(
                'loc' => get_permalink($post->ID),
                'lastmod' => $this->format_date($post->post_modified),
                'news' => array(
                    'publication' => array(
                        'name' => get_bloginfo('name'),
                        'language' => get_bloginfo('language')
                    ),
                    'publication_date' => $this->format_date($post->post_date),
                    'title' => get_the_title($post->ID),
                    'keywords' => implode(', ', array_slice($news_keywords, 0, 10)) // Max 10 keywords
                )
            );
        }

        return $this->create_sitemap_file($urls, 'news-sitemap', false, 'news');
    }

    /**
     * Generate sitemap index
     */
    private function generate_sitemap_index()
    {
        $sitemap_index = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
        $sitemap_index .= '<sitemapindex xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";

        foreach ($this->sitemap_indexes as $sitemap_data) {
            $sitemap_index .= '<sitemap>' . "\n";
            $sitemap_index .= '<loc>' . esc_url($sitemap_data['loc']) . '</loc>' . "\n";
            $sitemap_index .= '<lastmod>' . $sitemap_data['lastmod'] . '</lastmod>' . "\n";
            $sitemap_index .= '</sitemap>' . "\n";
        }

        $sitemap_index .= '</sitemapindex>';

        $filename = 'sitemap-index.xml';
        $filepath = $this->sitemap_path . $filename;

        if (file_put_contents($filepath, $sitemap_index)) {
            return array(
                'file' => $filename,
                'url' => $this->base_url . '/' . $filename,
                'url_count' => count($this->sitemap_indexes),
                'size' => filesize($filepath)
            );
        }

        return array('error' => 'Failed to create sitemap index');
    }

    /**
     * Create sitemap file
     */
    private function create_sitemap_file($urls, $base_name, $is_image_sitemap = false, $sitemap_type = 'url')
    {
        if (empty($urls)) {
            return array('error' => 'No URLs to include in sitemap');
        }

        $chunks = array_chunk($urls, $this->max_urls_per_sitemap);
        $sitemap_files = array();

        foreach ($chunks as $index => $chunk) {
            $suffix = count($chunks) > 1 ? '-' . ($index + 1) : '';
            $filename = $base_name . $suffix . '.xml';

            $sitemap_content = $this->generate_sitemap_xml($chunk, $is_image_sitemap, $sitemap_type);
            $filepath = $this->sitemap_path . $filename;

            if (file_put_contents($filepath, $sitemap_content)) {
                $sitemap_files[] = array(
                    'file' => $filename,
                    'url' => $this->base_url . '/' . $filename,
                    'url_count' => count($chunk),
                    'size' => filesize($filepath)
                );

                // Add to sitemap index
                $this->sitemap_indexes[] = array(
                    'loc' => $this->base_url . '/' . $filename,
                    'lastmod' => $this->format_date(current_time('mysql'))
                );
            }
        }

        return count($sitemap_files) === 1 ? $sitemap_files[0] : $sitemap_files;
    }

    /**
     * Generate XML content for sitemap
     */
    private function generate_sitemap_xml($urls, $is_image_sitemap = false, $sitemap_type = 'url')
    {
        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . "\n";

        switch ($sitemap_type) {
            case 'video':
                $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:video="http://www.google.com/schemas/sitemap-video/1.1">' . "\n";
                break;
            case 'news':
                $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:news="http://www.google.com/schemas/sitemap-news/0.9">' . "\n";
                break;
            case 'image':
                $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9" xmlns:image="http://www.google.com/schemas/sitemap-image/1.1">' . "\n";
                break;
            default:
                $xml .= '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
        }

        foreach ($urls as $url_data) {
            $xml .= '<url>' . "\n";
            $xml .= '<loc>' . esc_url($url_data['loc']) . '</loc>' . "\n";

            if (isset($url_data['lastmod'])) {
                $xml .= '<lastmod>' . $url_data['lastmod'] . '</lastmod>' . "\n";
            }

            if (isset($url_data['changefreq'])) {
                $xml .= '<changefreq>' . $url_data['changefreq'] . '</changefreq>' . "\n";
            }

            if (isset($url_data['priority'])) {
                $xml .= '<priority>' . $url_data['priority'] . '</priority>' . "\n";
            }

            // Add images
            if (!empty($url_data['images'])) {
                foreach ($url_data['images'] as $image) {
                    $xml .= '<image:image>' . "\n";
                    $xml .= '<image:loc>' . esc_url($image['loc']) . '</image:loc>' . "\n";
                    if (isset($image['title'])) {
                        $xml .= '<image:title>' . $this->escape_xml($image['title']) . '</image:title>' . "\n";
                    }
                    if (isset($image['caption'])) {
                        $xml .= '<image:caption>' . $this->escape_xml($image['caption']) . '</image:caption>' . "\n";
                    }
                    if (isset($image['license'])) {
                        $xml .= '<image:license>' . esc_url($image['license']) . '</image:license>' . "\n";
                    }
                    $xml .= '</image:image>' . "\n";
                }
            }

            // Add videos
            if (!empty($url_data['video'])) {
                $video = $url_data['video'];
                $xml .= '<video:video>' . "\n";

                if (isset($video['thumbnail_loc'])) {
                    $xml .= '<video:thumbnail_loc>' . esc_url($video['thumbnail_loc']) . '</video:thumbnail_loc>' . "\n";
                }
                if (isset($video['title'])) {
                    $xml .= '<video:title>' . $this->escape_xml($video['title']) . '</video:title>' . "\n";
                }
                if (isset($video['description'])) {
                    $xml .= '<video:description>' . $this->escape_xml($video['description']) . '</video:description>' . "\n";
                }
                if (isset($video['content_loc'])) {
                    $xml .= '<video:content_loc>' . esc_url($video['content_loc']) . '</video:content_loc>' . "\n";
                }
                if (isset($video['duration'])) {
                    $xml .= '<video:duration>' . intval($video['duration']) . '</video:duration>' . "\n";
                }
                if (isset($video['publication_date'])) {
                    $xml .= '<video:publication_date>' . $video['publication_date'] . '</video:publication_date>' . "\n";
                }
                if (isset($video['family_friendly'])) {
                    $xml .= '<video:family_friendly>' . $video['family_friendly'] . '</video:family_friendly>' . "\n";
                }

                $xml .= '</video:video>' . "\n";
            }

            // Add news
            if (!empty($url_data['news'])) {
                $news = $url_data['news'];
                $xml .= '<news:news>' . "\n";
                $xml .= '<news:publication>' . "\n";
                $xml .= '<news:name>' . $this->escape_xml($news['publication']['name']) . '</news:name>' . "\n";
                $xml .= '<news:language>' . $news['publication']['language'] . '</news:language>' . "\n";
                $xml .= '</news:publication>' . "\n";
                $xml .= '<news:publication_date>' . $news['publication_date'] . '</news:publication_date>' . "\n";
                $xml .= '<news:title>' . $this->escape_xml($news['title']) . '</news:title>' . "\n";
                if (isset($news['keywords'])) {
                    $xml .= '<news:keywords>' . $this->escape_xml($news['keywords']) . '</news:keywords>' . "\n";
                }
                $xml .= '</news:news>' . "\n";
            }

            $xml .= '</url>' . "\n";
        }

        $xml .= '</urlset>';
        return $xml;
    }

    /**
     * Helper methods
     */

    /**
     * Format date for sitemap
     */
    private function format_date($date_string)
    {
        return date('c', strtotime($date_string));
    }

    /**
     * Determine change frequency based on post type and modification date
     */
    private function determine_change_frequency($post)
    {
        $modified = strtotime($post->post_modified);
        $now = current_time('timestamp');
        $diff_days = ($now - $modified) / DAY_IN_SECONDS;

        if ($diff_days < 7) {
            return 'daily';
        } elseif ($diff_days < 30) {
            return 'weekly';
        } elseif ($diff_days < 90) {
            return 'monthly';
        } else {
            return 'yearly';
        }
    }

    /**
     * Calculate priority based on post type and hierarchy
     */
    private function calculate_priority($post, $post_type)
    {
        switch ($post_type) {
            case 'page':
                // Homepage gets highest priority
                if (get_option('page_on_front') == $post->ID) {
                    return 1.0;
                }
                // Important pages get higher priority
                $important_pages = array(
                    get_option('page_for_posts'),
                    get_option('woocommerce_shop_page_id'),
                    get_option('woocommerce_cart_page_id'),
                    get_option('woocommerce_checkout_page_id'),
                    get_option('woocommerce_myaccount_page_id')
                );
                if (in_array($post->ID, $important_pages)) {
                    return 0.9;
                }
                return 0.7;

            case 'post':
                // Recent posts get higher priority
                $post_age = (current_time('timestamp') - strtotime($post->post_date)) / DAY_IN_SECONDS;
                if ($post_age < 30) {
                    return 0.8;
                } elseif ($post_age < 90) {
                    return 0.6;
                }
                return 0.4;

            default:
                return 0.5;
        }
    }

    /**
     * Get post images for image sitemap
     */
    private function get_post_images($post)
    {
        $images = array();

        // Featured image
        $featured_image_id = get_post_thumbnail_id($post->ID);
        if ($featured_image_id) {
            $images[] = array(
                'loc' => wp_get_attachment_url($featured_image_id),
                'title' => get_the_title($post->ID),
                'caption' => wp_get_attachment_caption($featured_image_id)
            );
        }

        // Images from content
        $content_images = $this->extract_images_from_content($post->post_content);
        foreach ($content_images as $image_url) {
            $images[] = array(
                'loc' => $image_url,
                'title' => get_the_title($post->ID)
            );
        }

        return $images;
    }

    /**
     * Extract images from post content
     */
    private function extract_images_from_content($content)
    {
        $images = array();
        preg_match_all('/<img[^>]+src="([^">]+)"/', $content, $matches);

        if (!empty($matches[1])) {
            foreach ($matches[1] as $image_url) {
                // Convert relative URLs to absolute
                if (strpos($image_url, 'http') !== 0) {
                    $image_url = $this->base_url . $image_url;
                }
                $images[] = $image_url;
            }
        }

        return $images;
    }

    /**
     * Check if page is noindex
     */
    private function is_noindex_page($post_id)
    {
        $robots = get_post_meta($post_id, '_yoast_wpseo_meta-robots-noindex', true);
        if ($robots === '1') {
            return true;
        }

        // Check for other SEO plugins
        $aioseop_robots = get_post_meta($post_id, '_aioseop_noindex', true);
        if ($aioseop_robots === 'on') {
            return true;
        }

        return false;
    }

    /**
     * Get category last modified date
     */
    private function get_category_lastmod($category)
    {
        $recent_post = get_posts(array(
            'category' => $category->term_id,
            'numberposts' => 1,
            'orderby' => 'modified',
            'order' => 'DESC'
        ));

        if (!empty($recent_post)) {
            return $this->format_date($recent_post[0]->post_modified);
        }

        return $this->format_date(current_time('mysql'));
    }

    /**
     * Get tag last modified date
     */
    private function get_tag_lastmod($tag)
    {
        $recent_post = get_posts(array(
            'tag_id' => $tag->term_id,
            'numberposts' => 1,
            'orderby' => 'modified',
            'order' => 'DESC'
        ));

        if (!empty($recent_post)) {
            return $this->format_date($recent_post[0]->post_modified);
        }

        return $this->format_date(current_time('mysql'));
    }

    /**
     * Get author last modified date
     */
    private function get_author_lastmod($author_id)
    {
        $recent_post = get_posts(array(
            'author' => $author_id,
            'numberposts' => 1,
            'orderby' => 'modified',
            'order' => 'DESC'
        ));

        if (!empty($recent_post)) {
            return $this->format_date($recent_post[0]->post_modified);
        }

        return $this->format_date(current_time('mysql'));
    }

    /**
     * Get image caption
     */
    private function get_image_caption($image)
    {
        $caption = wp_get_attachment_caption($image->ID);
        if ($caption) {
            return $caption;
        }

        return get_post_meta($image->ID, '_wp_attachment_image_alt', true) ?: '';
    }

    /**
     * Get image license
     */
    private function get_image_license($image)
    {
        // This would typically come from a custom field or plugin
        return get_post_meta($image->ID, '_image_license_url', true) ?: '';
    }

    /**
     * Get video thumbnail
     */
    private function get_video_thumbnail($post)
    {
        $thumbnail_id = get_post_thumbnail_id($post->ID);
        if ($thumbnail_id) {
            return wp_get_attachment_url($thumbnail_id);
        }

        // Try to extract from video URL (for YouTube, Vimeo, etc.)
        $video_url = get_post_meta($post->ID, '_video_url', true);
        return $this->extract_video_thumbnail($video_url);
    }

    /**
     * Extract video thumbnail from URL
     */
    private function extract_video_thumbnail($video_url)
    {
        if (strpos($video_url, 'youtube.com') !== false || strpos($video_url, 'youtu.be') !== false) {
            preg_match('/(?:youtube\.com\/(?:[^\/]+\/.+\/|(?:v|e(?:mbed)?)\/|.*[?&]v=)|youtu\.be\/)([^"&?\/\s]{11})/', $video_url, $matches);
            if (isset($matches[1])) {
                return 'https://img.youtube.com/vi/' . $matches[1] . '/hqdefault.jpg';
            }
        } elseif (strpos($video_url, 'vimeo.com') !== false) {
            preg_match('/vimeo\.com\/(?:channels\/(?:\w+\/)?|groups\/(?:[^\/]*)\/videos\/|)(\d+)(?:|\/\?)/', $video_url, $matches);
            if (isset($matches[1])) {
                $vimeo_data = wp_remote_get('https://vimeo.com/api/v2/video/' . $matches[1] . '.json');
                if (!is_wp_error($vimeo_data)) {
                    $data = json_decode(wp_remote_retrieve_body($vimeo_data), true);
                    if (!empty($data[0]['thumbnail_large'])) {
                        return $data[0]['thumbnail_large'];
                    }
                }
            }
        }

        return '';
    }

    /**
     * Check if site is eligible for news sitemap
     */
    private function is_news_site()
    {
        // Check if site is registered with Google News
        if (get_option('google_news_verification')) {
            return true;
        }

        // Check if site frequently publishes news content
        $recent_posts = get_posts(array(
            'post_type' => 'post',
            'post_status' => 'publish',
            'date_query' => array(
                array(
                    'after' => '1 month ago'
                )
            ),
            'numberposts' => 1
        ));

        return !empty($recent_posts);
    }

    /**
     * Escape XML content
     */
    private function escape_xml($string)
    {
        return htmlspecialchars($string, ENT_XML1, 'UTF-8');
    }

    /**
     * Submit sitemap to search engines
     */
    public function submit_to_search_engines()
    {
        $sitemap_url = $this->base_url . '/sitemap-index.xml';
        $results = array();

        // Submit to Google
        $google_url = 'https://www.google.com/ping?sitemap=' . urlencode($sitemap_url);
        $google_response = wp_remote_get($google_url);
        $results['google'] = !is_wp_error($google_response) && wp_remote_retrieve_response_code($google_response) === 200;

        // Submit to Bing
        $bing_url = 'https://www.bing.com/ping?sitemap=' . urlencode($sitemap_url);
        $bing_response = wp_remote_get($bing_url);
        $results['bing'] = !is_wp_error($bing_response) && wp_remote_retrieve_response_code($bing_response) === 200;

        return $results;
    }

    /**
     * Get sitemap statistics
     */
    public function get_sitemap_stats()
    {
        $sitemaps = $this->generate_sitemaps();
        $stats = array(
            'total_sitemaps' => 0,
            'total_urls' => 0,
            'total_size' => 0,
            'sitemap_types' => array()
        );

        foreach ($sitemaps as $type => $sitemap_data) {
            if (isset($sitemap_data['error'])) {
                continue;
            }

            if (isset($sitemap_data['file'])) {
                // Single sitemap file
                $stats['total_sitemaps']++;
                $stats['total_urls'] += $sitemap_data['url_count'];
                $stats['total_size'] += $sitemap_data['size'];
                $stats['sitemap_types'][$type] = $sitemap_data['url_count'];
            } elseif (is_array($sitemap_data) && isset($sitemap_data[0]['file'])) {
                // Multiple sitemap files
                foreach ($sitemap_data as $sitemap_file) {
                    $stats['total_sitemaps']++;
                    $stats['total_urls'] += $sitemap_file['url_count'];
                    $stats['total_size'] += $sitemap_file['size'];
                }
                $stats['sitemap_types'][$type] = array_sum(array_column($sitemap_data, 'url_count'));
            }
        }

        return $stats;
    }
}
