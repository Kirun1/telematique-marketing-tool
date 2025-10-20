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
        }

        // Meta description
        if (!empty($meta_description)) {
            echo '<meta name="description" content="' . esc_attr($meta_description) . '" />' . "\n";
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
        }

        if (is_front_page()) {
            $this->output_website_structured_data();
        }
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
