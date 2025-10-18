<?php

/**
 * Plugin Name: Product Scraper & Analytics
 * Description: Scrapes product data and provides analytics dashboard
 * Version: 2.0.0
 * Author: Telematique LTD
 * Text Domain: product-scraper
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('PRODUCT_SCRAPER_VERSION', '2.0.0');
define('PRODUCT_SCRAPER_PLUGIN_URL', plugin_dir_url(__FILE__));
define('PRODUCT_SCRAPER_PLUGIN_PATH', plugin_dir_path(__FILE__));

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

class ProductScraper
{

    public $storage;
    public $analytics;
    public $seo_assistant;
    public $content_optimizer;
    public $keyword_research;

    public function __construct()
    {
        $this->storage = new ProductScraperDataStorage();
        $this->analytics = new ProductScraperAnalytics();
        $this->seo_assistant = new ProductScraper_SEO_Assistant();
        $this->content_optimizer = new ProductScraper_Content_Optimizer();
        $this->keyword_research = new ProductScraper_Keyword_Research();
        add_action('init', array($this, 'init'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_admin_scripts'));
    }

    public function init()
    {
        if (is_admin()) {
            new ProductScraperAdmin();
        }
    }

    public function enqueue_admin_scripts($hook)
    {
        // Define your plugin page slugs
        $plugin_pages = array(
            'toplevel_page_scraper-analytics',
            'scraper-analytics_page_scraper-keywords',
            'scraper-analytics_page_scraper-competitors',
            'toplevel_page_product-scraper',
            'scraper-analytics_page_product-scraper',
            'scraper-analytics_page_seo-assistant'
        );

        // Check if we're on one of your plugin pages or post edit screens
        $is_plugin_page = in_array($hook, $plugin_pages);
        $is_post_edit = in_array($hook, array('post.php', 'post-new.php'));

        if ($is_plugin_page || $is_post_edit) {
            // Enqueue CSS files
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

            // Enqueue JS file
            wp_enqueue_script(
                'product-scraper-seo-admin-js',
                PRODUCT_SCRAPER_PLUGIN_URL . 'assets/seo-admin.js',
                array('jquery', 'wp-api'),
                PRODUCT_SCRAPER_VERSION,
                true
            );

            // Localize script for AJAX and translations
            wp_localize_script('product-scraper-seo-admin-js', 'productScraper', array(
                'ajaxurl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('product_scraper_nonce'),
                'api_nonce' => wp_create_nonce('wp_rest'),
                'api_url' => rest_url('product-scraper/v1/'),
                'strings' => array(
                    'saving' => __('Saving...', 'product-scraper'),
                    'saved' => __('Saved!', 'product-scraper'),
                    'error' => __('Error saving data.', 'product-scraper')
                )
            ));
        }
    }
}

new ProductScraper();
