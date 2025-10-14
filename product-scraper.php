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
    }

    public function init()
    {
        if (is_admin()) {
            new ProductScraperAdmin();
        }
    }
}

new ProductScraper();
