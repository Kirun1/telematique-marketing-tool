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

class ProductScraper
{

    public $storage;
    public $analytics;

    public function __construct()
    {
        $this->storage = new ProductScraperDataStorage();
        $this->analytics = new ProductScraperAnalytics();
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
