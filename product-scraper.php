<?php

/**
 * Plugin Name: Product Scraper & Analytics
 * Description: Advanced SEO optimization with product scraping and analytics
 * Version: 2.1.0
 * Author: Telematique LTD
 * Text Domain: product-scraper
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('PRODUCT_SCRAPER_VERSION', '2.1.0');
define('PRODUCT_SCRAPER_PLUGIN_URL', plugin_dir_url(__FILE__));
define('PRODUCT_SCRAPER_PLUGIN_PATH', plugin_dir_path(__FILE__));

// Load Composer dependencies
$vendor_autoload = __DIR__ . '/vendor/autoload.php';
if (file_exists($vendor_autoload)) {
    require_once $vendor_autoload;
}

// Include required files
require_once PRODUCT_SCRAPER_PLUGIN_PATH . 'includes/class-scraper.php';
require_once PRODUCT_SCRAPER_PLUGIN_PATH . 'includes/class-admin.php';
require_once PRODUCT_SCRAPER_PLUGIN_PATH . 'includes/class-woocommerce-importer.php';
require_once PRODUCT_SCRAPER_PLUGIN_PATH . 'includes/class-data-storage.php';
require_once PRODUCT_SCRAPER_PLUGIN_PATH . 'includes/class-analytics-dashboard.php';
require_once PRODUCT_SCRAPER_PLUGIN_PATH . 'includes/class-seo-assistant.php';
require_once PRODUCT_SCRAPER_PLUGIN_PATH . 'includes/class-content-optimizer.php';
require_once PRODUCT_SCRAPER_PLUGIN_PATH . 'includes/class-keyword-research.php';
require_once PRODUCT_SCRAPER_PLUGIN_PATH . 'includes/class-voice-search.php';
require_once PRODUCT_SCRAPER_PLUGIN_PATH . 'includes/class-social-optimizer.php';
require_once PRODUCT_SCRAPER_PLUGIN_PATH . 'includes/class-schema-generator.php';
require_once PRODUCT_SCRAPER_PLUGIN_PATH . 'includes/class-roi-tracker.php';
require_once PRODUCT_SCRAPER_PLUGIN_PATH . 'includes/class-redirect-manager.php';
require_once PRODUCT_SCRAPER_PLUGIN_PATH . 'includes/class-rank-tracker.php';
require_once PRODUCT_SCRAPER_PLUGIN_PATH . 'includes/class-onpage-analyzer.php';
require_once PRODUCT_SCRAPER_PLUGIN_PATH . 'includes/class-local-seo.php';
require_once PRODUCT_SCRAPER_PLUGIN_PATH . 'includes/class-international-seo.php';
require_once PRODUCT_SCRAPER_PLUGIN_PATH . 'includes/class-ecommerce-seo.php';
require_once PRODUCT_SCRAPER_PLUGIN_PATH . 'includes/class-content-analytics.php';
require_once PRODUCT_SCRAPER_PLUGIN_PATH . 'includes/class-competitor-analysis.php';
require_once PRODUCT_SCRAPER_PLUGIN_PATH . 'includes/class-ai-title-optimizer.php';
require_once PRODUCT_SCRAPER_PLUGIN_PATH . 'includes/class-ai-content-writer.php';
require_once PRODUCT_SCRAPER_PLUGIN_PATH . 'includes/class-advanced-sitemap.php';
require_once PRODUCT_SCRAPER_PLUGIN_PATH . 'includes/class-api-integrations.php';
require_once PRODUCT_SCRAPER_PLUGIN_PATH . 'includes/class-seo-analysis.php';
require_once PRODUCT_SCRAPER_PLUGIN_PATH . 'includes/class-link-manager.php';
require_once PRODUCT_SCRAPER_PLUGIN_PATH . 'includes/class-robots-txt.php';

class ProductScraper
{
    public $storage;
    public $analytics;
    public $seo_assistant;
    public $content_optimizer;
    public $keyword_research;
    public $api_integrations;
    public $seo_analysis;
    public $link_manager;
    public $robots_manager;

    public function __construct()
    {
        $this->storage = new ProductScraperDataStorage();
        $this->analytics = new ProductScraperAnalytics();
        $this->seo_assistant = new ProductScraper_SEO_Assistant();
        $this->content_optimizer = new ProductScraper_Content_Optimizer();
        $this->keyword_research = new ProductScraper_Keyword_Research();
        $this->api_integrations = new ProductScraper_API_Integrations();
        $this->seo_analysis = new ProductScraper_SEO_Analysis();
        $this->link_manager = new ProductScraper_Link_Manager();
        $this->robots_manager = new ProductScraper_Robots_Txt();

        // Core SEO hooks
        add_action('init', array($this, 'init'));
        add_action('wp_head', array($this, 'output_seo_meta_tags'));
        add_action('template_redirect', array($this, 'canonical_redirects'));

        // Admin hooks
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
        add_action('admin_init', array($this, 'register_settings'));

        // XML Sitemaps
        add_action('init', array($this, 'setup_sitemaps'));

        // REST API
        add_action('rest_api_init', array($this, 'register_rest_routes'));
    }

    public function init()
    {
        if (is_admin()) {
            new ProductScraperAdmin();
        }

        // Initialize SEO features
        $this->setup_seo_features();
    }

    public function setup_seo_features()
    {
        // Remove other SEO plugins' meta boxes to avoid conflicts
        if (is_admin()) {
            add_action('add_meta_boxes', array($this, 'remove_other_seo_meta_boxes'), 100);
        }

        // Add Open Graph meta tags
        add_action('wp_head', array($this, 'add_opengraph_meta'), 5);

        // Add Twitter Card meta tags
        add_action('wp_head', array($this, 'add_twitter_card_meta'), 5);

        // Add JSON-LD structured data
        add_action('wp_head', array($this, 'add_structured_data'), 10);
    }

    public function remove_other_seo_meta_boxes()
    {
        // Remove Yoast SEO meta box
        remove_meta_box('wpseo_meta', get_post_types(), 'normal');

        // Remove Rank Math meta box
        remove_meta_box('rank_math_metabox', get_post_types(), 'normal');

        // Remove All in One SEO meta box
        remove_meta_box('aioseo-settings', get_post_types(), 'normal');
    }

    public function output_seo_meta_tags()
    {
        if (is_singular()) {
            $this->output_singular_meta_tags();
        } elseif (is_tax() || is_category() || is_tag()) {
            $this->output_taxonomy_meta_tags();
        } elseif (is_author()) {
            $this->output_author_meta_tags();
        } elseif (is_home() || is_front_page()) {
            $this->output_homepage_meta_tags();
        } elseif (is_search()) {
            $this->output_search_meta_tags();
        } elseif (is_archive()) {
            $this->output_archive_meta_tags();
        }
    }

    public function output_singular_meta_tags()
    {
        global $post;

        if (!$post) return;

        $seo_title = get_post_meta($post->ID, '_seo_title', true);
        $meta_description = get_post_meta($post->ID, '_meta_description', true);
        $canonical_url = get_post_meta($post->ID, '_canonical_url', true);
        $meta_robots = get_post_meta($post->ID, '_meta_robots', true);

        // Title tag
        if (!empty($seo_title)) {
            echo '<title>' . esc_html($seo_title) . '</title>' . "\n";
        } else {
            echo '<title>' . esc_html(get_the_title()) . ' | ' . esc_html(get_bloginfo('name')) . '</title>' . "\n";
        }

        // Meta description
        if (!empty($meta_description)) {
            echo '<meta name="description" content="' . esc_attr($meta_description) . '" />' . "\n";
        } elseif ($post->post_excerpt) {
            echo '<meta name="description" content="' . esc_attr(wp_trim_words($post->post_excerpt, 25)) . '" />' . "\n";
        } else {
            echo '<meta name="description" content="' . esc_attr(wp_trim_words(wp_strip_all_tags($post->post_content), 25)) . '" />' . "\n";
        }

        // Canonical URL
        if (!empty($canonical_url)) {
            echo '<link rel="canonical" href="' . esc_url($canonical_url) . '" />' . "\n";
        } else {
            echo '<link rel="canonical" href="' . esc_url(get_permalink($post->ID)) . '" />' . "\n";
        }

        // Meta robots
        if (!empty($meta_robots)) {
            echo '<meta name="robots" content="' . esc_attr($meta_robots) . '" />' . "\n";
        }
    }

    /**
     * Output meta tags for taxonomy pages (categories, tags, custom taxonomies)
     */
    public function output_taxonomy_meta_tags()
    {
        $term = get_queried_object();
        
        if (!$term || !isset($term->term_id)) {
            return;
        }

        $term_id = $term->term_id;
        $taxonomy = $term->taxonomy;

        // Get SEO meta from term meta
        $seo_title = get_term_meta($term_id, '_seo_title', true);
        $meta_description = get_term_meta($term_id, '_meta_description', true);
        $canonical_url = get_term_meta($term_id, '_canonical_url', true);
        $meta_robots = get_term_meta($term_id, '_meta_robots', true);

        // Generate title
        if (!empty($seo_title)) {
            $title = esc_html($seo_title);
        } else {
            $title = esc_html($term->name);
            if (is_category()) {
                $title .= ' | ' . __('Category', 'product-scraper');
            } elseif (is_tag()) {
                $title .= ' | ' . __('Tag', 'product-scraper');
            } else {
                $taxonomy_obj = get_taxonomy($taxonomy);
                $title .= ' | ' . esc_html($taxonomy_obj->labels->singular_name);
            }
            $title .= ' | ' . esc_html(get_bloginfo('name'));
        }
        echo '<title>' . $title . '</title>' . "\n";

        // Meta description
        if (!empty($meta_description)) {
            echo '<meta name="description" content="' . esc_attr($meta_description) . '" />' . "\n";
        } elseif (!empty($term->description)) {
            echo '<meta name="description" content="' . esc_attr(wp_trim_words($term->description, 25)) . '" />' . "\n";
        } else {
            $default_description = sprintf(
                __('Browse our collection of %s. Find the best products and content related to %s.', 'product-scraper'),
                esc_attr($term->name),
                esc_attr($term->name)
            );
            echo '<meta name="description" content="' . $default_description . '" />' . "\n";
        }

        // Canonical URL
        if (!empty($canonical_url)) {
            echo '<link rel="canonical" href="' . esc_url($canonical_url) . '" />' . "\n";
        } else {
            echo '<link rel="canonical" href="' . esc_url(get_term_link($term)) . '" />' . "\n";
        }

        // Meta robots
        if (!empty($meta_robots)) {
            echo '<meta name="robots" content="' . esc_attr($meta_robots) . '" />' . "\n";
        } else {
            // Default for taxonomy pages - index, follow unless paginated
            if ($this->is_paginated_archive()) {
                echo '<meta name="robots" content="noindex, follow" />' . "\n";
            } else {
                echo '<meta name="robots" content="index, follow" />' . "\n";
            }
        }

        // Prevent pagination duplication
        if ($this->is_paginated_archive()) {
            $this->output_pagination_meta($term);
        }
    }

    /**
     * Output meta tags for author pages
     */
    public function output_author_meta_tags()
    {
        $author_id = get_queried_object_id();
        $author = get_queried_object();
        
        if (!$author) {
            return;
        }

        // Get SEO meta from user meta
        $seo_title = get_user_meta($author_id, '_seo_title', true);
        $meta_description = get_user_meta($author_id, '_meta_description', true);
        $canonical_url = get_user_meta($author_id, '_canonical_url', true);
        $meta_robots = get_user_meta($author_id, '_meta_robots', true);

        // Generate title
        if (!empty($seo_title)) {
            $title = esc_html($seo_title);
        } else {
            $title = esc_html($author->display_name) . ' | ' . __('Author', 'product-scraper') . ' | ' . esc_html(get_bloginfo('name'));
        }
        echo '<title>' . $title . '</title>' . "\n";

        // Meta description
        if (!empty($meta_description)) {
            echo '<meta name="description" content="' . esc_attr($meta_description) . '" />' . "\n";
        } elseif (!empty($author->description)) {
            echo '<meta name="description" content="' . esc_attr(wp_trim_words($author->description, 25)) . '" />' . "\n";
        } else {
            $post_count = count_user_posts($author_id);
            $default_description = sprintf(
                _n(
                    'View all %d post by %s. %s is an author on %s.',
                    'View all %d posts by %s. %s is an author on %s.',
                    $post_count,
                    'product-scraper'
                ),
                $post_count,
                esc_attr($author->display_name),
                esc_attr($author->display_name),
                esc_attr(get_bloginfo('name'))
            );
            echo '<meta name="description" content="' . $default_description . '" />' . "\n";
        }

        // Canonical URL
        if (!empty($canonical_url)) {
            echo '<link rel="canonical" href="' . esc_url($canonical_url) . '" />' . "\n";
        } else {
            echo '<link rel="canonical" href="' . esc_url(get_author_posts_url($author_id)) . '" />' . "\n";
        }

        // Meta robots
        if (!empty($meta_robots)) {
            echo '<meta name="robots" content="' . esc_attr($meta_robots) . '" />' . "\n";
        } else {
            // Default for author pages - index, follow unless paginated
            if ($this->is_paginated_archive()) {
                echo '<meta name="robots" content="noindex, follow" />' . "\n";
            } else {
                echo '<meta name="robots" content="index, follow" />' . "\n";
            }
        }

        // Prevent pagination duplication
        if ($this->is_paginated_archive()) {
            $this->output_pagination_meta($author);
        }
    }

    /**
     * Output meta tags for homepage
     */
    public function output_homepage_meta_tags()
    {
        $seo_title = get_option('product_scraper_homepage_title');
        $meta_description = get_option('product_scraper_homepage_description');
        $canonical_url = home_url('/');

        // Title tag
        if (!empty($seo_title)) {
            echo '<title>' . esc_html($seo_title) . '</title>' . "\n";
        } else {
            echo '<title>' . esc_html(get_bloginfo('name')) . ' | ' . esc_html(get_bloginfo('description')) . '</title>' . "\n";
        }

        // Meta description
        if (!empty($meta_description)) {
            echo '<meta name="description" content="' . esc_attr($meta_description) . '" />' . "\n";
        } elseif (!empty(get_bloginfo('description'))) {
            echo '<meta name="description" content="' . esc_attr(get_bloginfo('description')) . '" />' . "\n";
        }

        // Canonical URL
        echo '<link rel="canonical" href="' . esc_url($canonical_url) . '" />' . "\n";

        // Meta robots
        echo '<meta name="robots" content="index, follow" />' . "\n";
    }

    /**
     * Output meta tags for search results
     */
    public function output_search_meta_tags()
    {
        $search_query = get_search_query();
        
        echo '<title>' . sprintf(__('Search Results for: "%s" | %s', 'product-scraper'), esc_html($search_query), esc_html(get_bloginfo('name'))) . '</title>' . "\n";
        
        echo '<meta name="description" content="' . sprintf(__('Search results for "%s". Find relevant content and products on %s.', 'product-scraper'), esc_attr($search_query), esc_attr(get_bloginfo('name'))) . '" />' . "\n";
        
        echo '<link rel="canonical" href="' . esc_url(get_search_link()) . '" />' . "\n";
        
        // Typically noindex search results to avoid duplicate content
        echo '<meta name="robots" content="noindex, follow" />' . "\n";
    }

    /**
     * Output meta tags for general archive pages
     */
    public function output_archive_meta_tags()
    {
        if (is_date()) {
            $this->output_date_archive_meta_tags();
        } else {
            // Fallback for other archive types
            echo '<title>' . esc_html(get_the_archive_title()) . ' | ' . esc_html(get_bloginfo('name')) . '</title>' . "\n";
            echo '<meta name="description" content="' . esc_attr(wp_trim_words(get_the_archive_description(), 25)) . '" />' . "\n";
            echo '<link rel="canonical" href="' . esc_url(get_pagenum_link()) . '" />' . "\n";
            
            if ($this->is_paginated_archive()) {
                echo '<meta name="robots" content="noindex, follow" />' . "\n";
            } else {
                echo '<meta name="robots" content="index, follow" />' . "\n";
            }
        }
    }

    /**
     * Output meta tags for date-based archives
     */
    public function output_date_archive_meta_tags()
    {
        if (is_year()) {
            $title = sprintf(__('Posts from %s | %s', 'product-scraper'), get_the_date('Y'), get_bloginfo('name'));
            $description = sprintf(__('Browse all posts from %s on %s.', 'product-scraper'), get_the_date('Y'), get_bloginfo('name'));
        } elseif (is_month()) {
            $title = sprintf(__('Posts from %s | %s', 'product-scraper'), get_the_date('F Y'), get_bloginfo('name'));
            $description = sprintf(__('Browse all posts from %s on %s.', 'product-scraper'), get_the_date('F Y'), get_bloginfo('name'));
        } elseif (is_day()) {
            $title = sprintf(__('Posts from %s | %s', 'product-scraper'), get_the_date(), get_bloginfo('name'));
            $description = sprintf(__('Browse all posts from %s on %s.', 'product-scraper'), get_the_date(), get_bloginfo('name'));
        }

        echo '<title>' . esc_html($title) . '</title>' . "\n";
        echo '<meta name="description" content="' . esc_attr($description) . '" />' . "\n";
        echo '<link rel="canonical" href="' . esc_url(get_pagenum_link()) . '" />' . "\n";
        
        // Typically noindex date archives to avoid thin content
        echo '<meta name="robots" content="noindex, follow" />' . "\n";
    }

    /**
     * Output pagination meta tags to prevent duplicate content
     */
    private function output_pagination_meta($object = null)
    {
        global $wp_query;
        
        $paged = get_query_var('paged') ?: 1;
        
        if ($paged > 1) {
            // Prev link
            if ($paged > 1) {
                echo '<link rel="prev" href="' . esc_url(get_pagenum_link($paged - 1)) . '" />' . "\n";
            }
            
            // Next link
            if ($paged < $wp_query->max_num_pages) {
                echo '<link rel="next" href="' . esc_url(get_pagenum_link($paged + 1)) . '" />' . "\n";
            }
        }
    }

    /**
     * Check if current page is a paginated archive
     */
    private function is_paginated_archive()
    {
        global $wp_query;
        return $wp_query->is_paged && ($wp_query->is_archive() || $wp_query->is_home() || $wp_query->is_search());
    }

    public function add_opengraph_meta()
    {
        if (!is_singular()) return;

        global $post;

        $og_title = get_post_meta($post->ID, '_og_title', true) ?: get_the_title();
        $og_description = get_post_meta($post->ID, '_og_description', true) ?: wp_trim_words(get_the_excerpt(), 25);
        $og_image = get_post_meta($post->ID, '_og_image', true);

        echo '<!-- Product Scraper Open Graph -->' . "\n";
        echo '<meta property="og:type" content="article" />' . "\n";
        echo '<meta property="og:title" content="' . esc_attr($og_title) . '" />' . "\n";
        echo '<meta property="og:description" content="' . esc_attr($og_description) . '" />' . "\n";
        echo '<meta property="og:url" content="' . esc_url(get_permalink()) . '" />' . "\n";
        echo '<meta property="og:site_name" content="' . esc_attr(get_bloginfo('name')) . '" />' . "\n";

        if ($og_image) {
            echo '<meta property="og:image" content="' . esc_url($og_image) . '" />' . "\n";
        } elseif (has_post_thumbnail($post->ID)) {
            $image_url = wp_get_attachment_image_url(get_post_thumbnail_id($post->ID), 'large');
            echo '<meta property="og:image" content="' . esc_url($image_url) . '" />' . "\n";
        }
    }

    /**
     * Add Open Graph meta tags for taxonomy pages
     */
    public function add_taxonomy_opengraph_meta()
    {
        if (!is_tax() && !is_category() && !is_tag()) return;

        $term = get_queried_object();
        if (!$term) return;

        $og_title = get_term_meta($term->term_id, '_og_title', true) ?: $term->name;
        $og_description = get_term_meta($term->term_id, '_og_description', true) ?: wp_trim_words($term->description, 25);
        $og_image = get_term_meta($term->term_id, '_og_image', true);

        echo '<!-- Product Scraper Taxonomy Open Graph -->' . "\n";
        echo '<meta property="og:type" content="website" />' . "\n";
        echo '<meta property="og:title" content="' . esc_attr($og_title) . '" />' . "\n";
        echo '<meta property="og:description" content="' . esc_attr($og_description) . '" />' . "\n";
        echo '<meta property="og:url" content="' . esc_url(get_term_link($term)) . '" />' . "\n";
        echo '<meta property="og:site_name" content="' . esc_attr(get_bloginfo('name')) . '" />' . "\n";
        
        if ($og_image) {
            echo '<meta property="og:image" content="' . esc_url($og_image) . '" />' . "\n";
        }
    }

    /**
     * Add Open Graph meta tags for author pages
     */
    public function add_author_opengraph_meta()
    {
        if (!is_author()) return;

        $author_id = get_queried_object_id();
        $author = get_queried_object();

        $og_title = get_user_meta($author_id, '_og_title', true) ?: $author->display_name;
        $og_description = get_user_meta($author_id, '_og_description', true) ?: wp_trim_words($author->description, 25);
        $og_image = get_user_meta($author_id, '_og_image', true);

        echo '<!-- Product Scraper Author Open Graph -->' . "\n";
        echo '<meta property="og:type" content="profile" />' . "\n";
        echo '<meta property="og:title" content="' . esc_attr($og_title) . '" />' . "\n";
        echo '<meta property="og:description" content="' . esc_attr($og_description) . '" />' . "\n";
        echo '<meta property="og:url" content="' . esc_url(get_author_posts_url($author_id)) . '" />' . "\n";
        echo '<meta property="og:site_name" content="' . esc_attr(get_bloginfo('name')) . '" />' . "\n";
        echo '<meta property="profile:username" content="' . esc_attr($author->user_login) . '" />' . "\n";
        
        if ($og_image) {
            echo '<meta property="og:image" content="' . esc_url($og_image) . '" />' . "\n";
        } elseif (function_exists('get_avatar_url')) {
            $avatar_url = get_avatar_url($author_id, array('size' => 300));
            echo '<meta property="og:image" content="' . esc_url($avatar_url) . '" />' . "\n";
        }
    }

    public function add_twitter_card_meta()
    {
        if (!is_singular()) return;

        global $post;

        $twitter_title = get_post_meta($post->ID, '_twitter_title', true) ?: get_the_title();
        $twitter_description = get_post_meta($post->ID, '_twitter_description', true) ?: wp_trim_words(get_the_excerpt(), 25);
        $twitter_image = get_post_meta($post->ID, '_twitter_image', true);

        echo '<!-- Product Scraper Twitter Card -->' . "\n";
        echo '<meta name="twitter:card" content="summary_large_image" />' . "\n";
        echo '<meta name="twitter:title" content="' . esc_attr($twitter_title) . '" />' . "\n";
        echo '<meta name="twitter:description" content="' . esc_attr($twitter_description) . '" />' . "\n";

        if ($twitter_image) {
            echo '<meta name="twitter:image" content="' . esc_url($twitter_image) . '" />' . "\n";
        } elseif (has_post_thumbnail($post->ID)) {
            $image_url = wp_get_attachment_image_url(get_post_thumbnail_id($post->ID), 'large');
            echo '<meta name="twitter:image" content="' . esc_url($image_url) . '" />' . "\n";
        }
    }

    public function add_structured_data()
    {
        if (is_singular('product') && function_exists('wc_get_product')) {
            $this->output_product_structured_data();
        } elseif (is_singular()) {
            $this->output_article_structured_data();
        } elseif (is_tax() || is_category() || is_tag()) {
            $this->output_taxonomy_structured_data();
        } elseif (is_author()) {
            $this->output_author_structured_data();
        }

        if (is_front_page()) {
            $this->output_website_structured_data();
        }
    }

    /**
     * Output structured data for taxonomy pages
     */
    private function output_taxonomy_structured_data()
    {
        $term = get_queried_object();
        if (!$term) return;

        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'CollectionPage',
            'name' => $term->name,
            'description' => wp_strip_all_tags($term->description),
            'url' => get_term_link($term)
        );

        echo '<script type="application/ld+json">' . 
             json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . 
             '</script>' . "\n";
    }

    /**
     * Output structured data for author pages
     */
    private function output_author_structured_data()
    {
        $author_id = get_queried_object_id();
        $author = get_queried_object();

        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'ProfilePage',
            'name' => $author->display_name,
            'description' => wp_strip_all_tags($author->description),
            'url' => get_author_posts_url($author_id),
            'mainEntity' => array(
                '@type' => 'Person',
                'name' => $author->display_name,
                'description' => wp_strip_all_tags($author->description)
            )
        );

        echo '<script type="application/ld+json">' . 
             json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . 
             '</script>' . "\n";
    }

    public function setup_sitemaps()
    {
        add_filter('wp_sitemaps_add_provider', array($this, 'enhance_sitemaps'), 10, 2);
        add_filter('wp_sitemaps_posts_query_args', array($this, 'enhance_posts_sitemap'));
        add_filter('wp_sitemaps_taxonomies_query_args', array($this, 'enhance_taxonomies_sitemap'));
    }

    public function register_settings()
    {
        // Core SEO settings
        register_setting('product_scraper_seo_settings', 'product_scraper_seo_title_template');
        register_setting('product_scraper_seo_settings', 'product_scraper_seo_description_template');
        register_setting('product_scraper_seo_settings', 'product_scraper_seo_robots_default');

        // Social media settings
        register_setting('product_scraper_seo_settings', 'product_scraper_facebook_app_id');
        register_setting('product_scraper_seo_settings', 'product_scraper_twitter_site');

        // Analytics settings
        register_setting('product_scraper_settings', 'product_scraper_google_analytics_id');
        register_setting('product_scraper_settings', 'product_scraper_google_search_console');
        register_setting('product_scraper_settings', 'product_scraper_semrush_api');
        register_setting('product_scraper_settings', 'product_scraper_ahrefs_api');
        register_setting('product_scraper_settings', 'product_scraper_ga4_property_id');
        register_setting('product_scraper_settings', 'product_scraper_pagespeed_api');

        // Advanced SEO settings
        register_setting('product_scraper_seo_settings', 'product_scraper_seo_breadcrumbs');
        register_setting('product_scraper_seo_settings', 'product_scraper_seo_schema');
        register_setting('product_scraper_seo_settings', 'product_scraper_seo_clean_permalinks');
    }

    public function enqueue_admin_scripts($hook)
    {
        $plugin_pages = array(
            'toplevel_page_scraper-analytics',
            'scraper-analytics_page_scraper-keywords',
            'scraper-analytics_page_scraper-competitors',
            'toplevel_page_product-scraper',
            'scraper-analytics_page_product-scraper',
            'scraper-analytics_page_seo-assistant',
            'scraper-analytics_page_seo-settings',
            'scraper-analytics_page_seo-analysis',
            'scraper-analytics_page_link-manager'
        );

        $is_plugin_page = in_array($hook, $plugin_pages);
        $is_post_edit = in_array($hook, array('post.php', 'post-new.php'));
        $is_tax_edit = in_array($hook, array('edit-tags.php', 'term.php'));

        if ($is_plugin_page || $is_post_edit || $is_tax_edit) {
            // Enqueue Chart.js for analytics
            wp_enqueue_script(
                'chart-js',
                'https://cdn.jsdelivr.net/npm/chart.js',
                array(),
                '3.9.1',
                true
            );

            // Enqueue Select2 for better dropdowns
            wp_enqueue_script(
                'select2-js',
                'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js',
                array('jquery'),
                '4.1.0',
                true
            );

            wp_enqueue_style(
                'select2-css',
                'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css',
                array(),
                '4.1.0'
            );

            // Plugin styles
            wp_enqueue_style(
                'product-scraper-analytics-css',
                PRODUCT_SCRAPER_PLUGIN_URL . 'assets/sa-analytics.css',
                array(),
                PRODUCT_SCRAPER_VERSION
            );

            wp_enqueue_style(
                'product-scraper-seo-admin-css',
                PRODUCT_SCRAPER_PLUGIN_URL . 'assets/seo-admin.css',
                array(),
                PRODUCT_SCRAPER_VERSION
            );

            // Plugin scripts
            wp_enqueue_script(
                'product-scraper-seo-admin-js',
                PRODUCT_SCRAPER_PLUGIN_URL . 'assets/seo-admin.js',
                array('jquery', 'wp-api', 'chart-js', 'select2-js'),
                PRODUCT_SCRAPER_VERSION,
                true
            );

            wp_localize_script('product-scraper-seo-admin-js', 'productScraper', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('product_scraper_nonce'),
                'api_nonce' => wp_create_nonce('wp_rest'),
                'api_url' => rest_url('product-scraper/v1/'),
                'site_url' => get_site_url(),
                'vendor_loaded' => file_exists(__DIR__ . '/vendor/autoload.php'),
                'post_id' => get_the_ID(),
                'strings' => array(
                    'saving' => __('Saving...', 'product-scraper'),
                    'saved' => __('Saved!', 'product-scraper'),
                    'error' => __('Error saving data.', 'product-scraper'),
                    'analyzing' => __('Analyzing content...', 'product-scraper'),
                    'optimizing' => __('Optimizing...', 'product-scraper')
                )
            ));
        }
    }

    public function register_rest_routes()
    {
        register_rest_route('product-scraper/v1', '/analyze-content', array(
            'methods' => 'POST',
            'callback' => array($this, 'rest_analyze_content'),
            'permission_callback' => array($this, 'rest_permission_check')
        ));

        register_rest_route('product-scraper/v1', '/optimize-content', array(
            'methods' => 'POST',
            'callback' => array($this, 'rest_optimize_content'),
            'permission_callback' => array($this, 'rest_permission_check')
        ));

        register_rest_route('product-scraper/v1', '/get-keyword-suggestions', array(
            'methods' => 'POST',
            'callback' => array($this, 'rest_get_keyword_suggestions'),
            'permission_callback' => array($this, 'rest_permission_check')
        ));
    }

    public function rest_permission_check($request)
    {
        return current_user_can('edit_posts');
    }

    // Helper methods for structured data
    private function output_product_structured_data()
    {
        global $post;
        $product = wc_get_product($post->ID);

        if (!$product) return;

        $ecommerce_seo = new ProductScraper_Ecommerce_SEO();
        $schema = $ecommerce_seo->generate_product_schema($post->ID);

        if (!empty($schema) && !isset($schema['error'])) {
            echo '<script type="application/ld+json">' .
                json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) .
                '</script>' . "\n";
        }
    }

    private function output_article_structured_data()
    {
        global $post;

        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => get_the_title(),
            'description' => wp_trim_words(get_the_excerpt(), 25),
            'datePublished' => get_the_date('c'),
            'dateModified' => get_the_modified_date('c'),
            'author' => array(
                '@type' => 'Person',
                'name' => get_the_author()
            ),
            'publisher' => array(
                '@type' => 'Organization',
                'name' => get_bloginfo('name'),
                'logo' => array(
                    '@type' => 'ImageObject',
                    'url' => $this->get_site_logo()
                )
            )
        );

        if (has_post_thumbnail()) {
            $schema['image'] = array(
                '@type' => 'ImageObject',
                'url' => get_the_post_thumbnail_url($post->ID, 'full'),
                'width' => 1200,
                'height' => 630
            );
        }

        echo '<script type="application/ld+json">' .
            json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) .
            '</script>' . "\n";
    }

    private function output_website_structured_data()
    {
        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => get_bloginfo('name'),
            'description' => get_bloginfo('description'),
            'url' => home_url(),
            'potentialAction' => array(
                '@type' => 'SearchAction',
                'target' => array(
                    '@type' => 'EntryPoint',
                    'urlTemplate' => home_url('/?s={search_term_string}')
                ),
                'query-input' => 'required name=search_term_string'
            )
        );

        echo '<script type="application/ld+json">' .
            json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) .
            '</script>' . "\n";
    }

    private function get_site_logo()
    {
        $custom_logo_id = get_theme_mod('custom_logo');
        if ($custom_logo_id) {
            return wp_get_attachment_url($custom_logo_id);
        }
        return '';
    }

    public function canonical_redirects()
    {
        // Prevent duplicate content by redirecting non-canonical URLs
        if (is_attachment()) {
            wp_redirect(get_attachment_link(), 301);
            exit;
        }
    }
}

new ProductScraper();
