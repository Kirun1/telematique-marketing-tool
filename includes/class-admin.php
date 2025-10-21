<?php

class ProductScraperAdmin
{

    public function __construct()
    {
        // add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
        add_action('wp_ajax_scrape_products', array($this, 'ajax_scrape_products'));
        add_action('wp_ajax_test_selectors', array($this, 'ajax_test_selectors'));
        add_action('wp_ajax_get_stored_products', array($this, 'ajax_get_stored_products'));
        add_action('wp_ajax_export_products_csv', array($this, 'ajax_export_products_csv'));
        add_action('wp_ajax_export_products_excel', array($this, 'ajax_export_products_excel'));
        add_action('wp_ajax_delete_stored_products', array($this, 'ajax_delete_stored_products'));

        // Handle direct export requests
        if (isset($_GET['page']) && $_GET['page'] === 'product-scraper' && isset($_GET['export'])) {
            add_action('admin_init', array($this, 'handle_export'));
        }
    }

    public function add_admin_menu()
    {
        add_options_page(
            'Product Scraper',
            'Product Scraper',
            'manage_options',
            'product-scraper',
            array($this, 'admin_page')
        );
    }

    public function admin_init()
    {
        register_setting('product_scraper_settings', 'product_scraper_options');
    }

    public function admin_page()
    {
        // Get stored products stats
        $plugin = new ProductScraper();
        $stats = $plugin->storage->get_stats();
?>
        <div class="wrap">
            <div class="scraper-analytics-dashboard">
                <div class="sa-header">
                    <div class="sa-brand">
                        <h1><strong>Product Scraper</strong></h1>
                        <span class="sa-subtitle">Dashboard</span>
                    </div>
                    <div class="sa-actions">
                        <button class="sa-btn sa-btn-primary" onclick="refreshAnalytics()">
                            <span class="dashicons dashicons-update"></span>
                            Refresh Data
                        </button>
                    </div>
                </div>
                <div class="sa-container">
                    <!-- Sidebar -->
                    <?php ProductScraper::product_scraper_render_sidebar('product-scraper'); ?>

                    <div class="sa-main-content">
                        <div class="sa-section">
                            <h2>Product Scraper Settings</h2>

                            <div class="sa-info-box">
                                <div class="info-icon">
                                    <span class="dashicons dashicons-info"></span>
                                </div>
                                <div class="info-content">
                                    <p>Configure the settings below to start scraping products from the any shop. Make sure to respect the website's terms of service and robots.txt file when scraping data.</p>
                                </div>
                            </div>
                        </div>
                        <div id="scraper-app">
                            <form method="post" action="options.php">
                                <?php
                                settings_fields('product_scraper_settings');
                                $options = get_option('product_scraper_options', array());
                                ?>

                                <table class="form-table">
                                    <tr>
                                        <th scope="row">Shop URL</th>
                                        <td>
                                            <input type="url" name="product_scraper_options[target_url]"
                                                value="<?php echo esc_attr($options['target_url'] ?? 'https://www.example.com/de/lebensmittel/saucen'); ?>"
                                                class="regular-text">
                                            <p class="description">e.g., https://www.example.com/de/lebensmittel/saucen</p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">Maximum Pages to Scrape</th>
                                        <td>
                                            <input type="number" name="product_scraper_options[max_pages]"
                                                value="<?php echo esc_attr($options['max_pages'] ?? 10); ?>"
                                                min="1" max="50">
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">Scrape Product Details</th>
                                        <td>
                                            <input type="checkbox" name="product_scraper_options[scrape_details]"
                                                value="1" <?php checked($options['scrape_details'] ?? 1); ?>>
                                            <span class="description">Visit each product page for detailed information (slower)</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">Delay Between Requests</th>
                                        <td>
                                            <input type="number" name="product_scraper_options[request_delay]"
                                                value="<?php echo esc_attr($options['request_delay'] ?? 2); ?>"
                                                min="1" max="10" step="0.5">
                                            <span class="description">seconds (be respectful to the server)</span>
                                        </td>
                                    </tr>
                                </table>

                                <?php submit_button('Save Settings'); ?>
                            </form>

                            <hr>

                            <!-- Storage Statistics -->
                            <div class="storage-stats">
                                <h3>Storage Statistics</h3>
                                <div class="stats-grid">
                                    <div class="stat-item">
                                        <span class="stat-number"><?php echo $stats['total_products'] ?? 0; ?></span>
                                        <span class="stat-label">Total Products</span>
                                    </div>
                                    <div class="stat-item">
                                        <span class="stat-number"><?php echo $stats['imported_products'] ?? 0; ?></span>
                                        <span class="stat-label">Imported</span>
                                    </div>
                                    <div class="stat-item">
                                        <span class="stat-number"><?php echo $stats['total_sources'] ?? 0; ?></span>
                                        <span class="stat-label">Sources</span>
                                    </div>
                                    <div class="stat-item">
                                        <span class="stat-date"><?php
                                                                if (!empty($stats['last_scraped']) && $stats['last_scraped'] !== '0000-00-00 00:00:00') {
                                                                    echo date('M j, Y g:i A', strtotime($stats['last_scraped']));
                                                                } else {
                                                                    echo 'Never';
                                                                }
                                                                ?></span>
                                        <span class="stat-label">Last Scraped</span>
                                    </div>
                                </div>
                            </div>

                            <hr>

                            <div class="scraper-controls">
                                <h2>Scrape Products</h2>
                                <button id="start-scraping" class="button button-primary">Start Scraping Nahrin Products</button>
                                <button id="test-selectors" class="button button-secondary">Test Selectors</button>
                                <button id="refresh-stored" class="button">Refresh Stored Products</button>
                                <button id="import-woocommerce" class="button button-secondary" disabled>Import to WooCommerce</button>
                            </div>

                            <div id="scraping-progress" style="display: none;">
                                <h3>Scraping Progress</h3>
                                <div id="progress-bar">
                                    <div id="progress-bar-inner"></div>
                                </div>
                                <div id="progress-text">Starting...</div>
                                <div id="scraping-stats"></div>
                                <div id="scraping-results"></div>
                            </div>

                            <hr>

                            <div id="scraped-products" style="display: none;">
                                <h3>Scraped Products <span id="products-count"></span></h3>
                                <div class="export-controls">
                                    <button id="export-csv" class="button button-secondary">Export as CSV</button>
                                    <button id="export-excel" class="button button-secondary">Export as Excel</button>
                                    <button id="save-to-db" class="button button-primary">Save to Database</button>
                                </div>
                                <div id="products-list"></div>
                            </div>

                            <!-- Stored Products Section -->
                            <div id="stored-products-section">
                                <h3>Stored Products</h3>
                                <div class="tablenav">
                                    <div class="alignleft actions">
                                        <select id="bulk-action-selector">
                                            <option value="">Bulk Actions</option>
                                            <option value="export_csv">Export as CSV</option>
                                            <option value="export_excel">Export as Excel</option>
                                            <option value="delete">Delete</option>
                                        </select>
                                        <button id="do-bulk-action" class="button">Apply</button>
                                        <button id="export-all-csv" class="button">Export All as CSV</button>
                                        <button id="export-all-excel" class="button">Export All as Excel</button>
                                        <button id="delete-all-products" class="button button-danger">Delete All Products</button>
                                    </div>
                                    <div class="alignright">
                                        <span id="storage-info"></span>
                                    </div>
                                </div>
                                <table class="wp-list-table widefat fixed striped" id="stored-products-table">
                                    <thead>
                                        <tr>
                                            <th class="check-column"><input type="checkbox" id="select-all"></th>
                                            <th>ID</th>
                                            <th>Product Name</th>
                                            <th>Price</th>
                                            <th>Rating</th>
                                            <th>Reviews</th>
                                            <th>Scraped Date</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody id="stored-products-list">
                                        <tr>
                                            <td colspan="9">Loading stored products...</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
<?php
    }

    public function ajax_scrape_products()
    {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'scrape_products_nonce')) {
            wp_die('Security check failed');
        }

        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }

        $page = intval($_POST['page']);
        $max_pages = intval($_POST['max_pages']);
        $target_url = esc_url_raw($_POST['target_url']);
        $scrape_details = isset($_POST['scrape_details']) ? boolval($_POST['scrape_details']) : false;
        $save_only = isset($_POST['save_only']) ? boolval($_POST['save_only']) : false;

        // Add debug info
        $debug_info = array();

        // If we're just saving existing data
        if ($save_only && isset($_POST['products_data'])) {
            $products = json_decode(stripslashes($_POST['products_data']), true);
            $saved_count = $this->save_scraped_products($products, $target_url);

            // Add debug info
            $debug_info['save_only'] = true;
            $debug_info['products_received'] = count($products);
            $debug_info['database_debug'] = $this->debug_database();

            wp_send_json_success(array(
                'products' => $products,
                'saved_count' => $saved_count,
                'has_more' => false,
                'errors' => 0,
                'debug' => $debug_info
            ));
        }

        $scraper = new ProductScraperEngine();
        $scraper->set_base_url($target_url);

        $products = $scraper->scrape_products($page, 1);

        // If detailed scraping is enabled, visit each product page
        $errors = 0;
        if ($scrape_details && !empty($products)) {
            foreach ($products as &$product) {
                if (!empty($product['url'])) {
                    $details = $scraper->scrape_product_details($product['url']);
                    if ($details) {
                        $product = array_merge($product, $details);
                    } else {
                        $errors++;
                    }
                    sleep(1);
                }
            }
        }

        // SAVE TO DATABASE
        $saved_count = 0;
        if (!empty($products)) {
            $saved_count = $this->save_scraped_products($products, $target_url);
        }

        // Add debug info
        $debug_info['products_scraped'] = count($products);
        $debug_info['database_debug'] = $this->debug_database();

        // Check if there are more pages
        $has_more = $page < $max_pages && !empty($products);

        wp_send_json_success(array(
            'products' => $products,
            'has_more' => $has_more,
            'errors' => $errors,
            'saved_count' => $saved_count,
            'debug' => $debug_info
        ));
    }

    /**
     * Save products to database
     */
    /**
     * Save products to database with debug information
     */
    private function save_scraped_products($products, $source_url)
    {
        // Get the storage instance from the main plugin class
        $plugin = new ProductScraper();

        // Debug: Check if storage object is created properly
        if (!$plugin->storage) {
            // error_log('ProductScraper: Storage object not initialized');
            return 0;
        }

        try {
            $saved_count = $plugin->storage->save_products($products, $source_url);
            // error_log("ProductScraper: Attempted to save " . count($products) . " products, saved: " . $saved_count);
            return $saved_count;
        } catch (Exception $e) {
            error_log('ProductScraper: Error saving products: ' . $e->getMessage());
            return 0;
        }
    }

    /**
     * Get stored products
     */
    public function ajax_get_stored_products()
    {
        if (!wp_verify_nonce($_POST['nonce'], 'get_products_nonce')) {
            wp_die('Security check failed');
        }

        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }

        $source_url = isset($_POST['source_url']) ? esc_url_raw($_POST['source_url']) : '';
        $imported = isset($_POST['imported']) ? intval($_POST['imported']) : null;

        $plugin = new ProductScraper();
        $products = $plugin->storage->get_products($source_url, $imported);
        $stats = $plugin->storage->get_stats();

        wp_send_json_success(array(
            'products' => $products,
            'stats' => $stats
        ));
    }

    /**
     * Export products as CSV
     */
    public function ajax_export_products_csv()
    {
        $this->export_products('csv');
    }

    /**
     * Export products as Excel
     */
    public function ajax_export_products_excel()
    {
        $this->export_products('excel');
    }

    /**
     * Handle export functionality
     */
    private function export_products($format)
    {
        if (!wp_verify_nonce($_POST['nonce'], 'export_products_nonce')) {
            wp_die('Security check failed');
        }

        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }

        // Get products data
        $products = array();
        if (isset($_POST['export_all']) && $_POST['export_all']) {
            $plugin = new ProductScraper();
            $products = $plugin->storage->get_products();
        } elseif (isset($_POST['products_data'])) {
            $products = json_decode(stripslashes($_POST['products_data']), true);
        }

        if (empty($products)) {
            wp_die('No products to export');
        }

        // Prepare CSV data
        $filename = 'nahrin-products-' . date('Y-m-d-H-i-s') . '.' . ($format === 'csv' ? 'csv' : 'xlsx');

        // Set headers
        if ($format === 'csv') {
            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
        } else {
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="' . $filename . '"');
        }

        // Create output stream
        $output = fopen('php://output', 'w');

        // Add BOM for UTF-8
        fwrite($output, "\xEF\xBB\xBF");

        // Headers
        $headers = array(
            'ID',
            'Name',
            'URL',
            'Price (CHF)',
            'Display Price',
            'Rating',
            'Review Count',
            'Badges',
            'Image URL',
            'Categories',
            'SKU',
            'Description',
            'Scraped Date'
        );

        fputcsv($output, $headers);

        // Data rows
        foreach ($products as $product) {
            $product_data = isset($product['product_data']) ? $product['product_data'] : $product;

            $row = array(
                $product['id'] ?? '',
                $product['product_name'] ?? $product_data['name'] ?? '',
                $product['product_url'] ?? $product_data['url'] ?? '',
                $product['price'] ?? $product_data['price_amount'] ?? '',
                $product['price_display'] ?? $product_data['price'] ?? '',
                $product['rating_stars'] ?? $product_data['rating_stars'] ?? '',
                $product['review_count'] ?? $product_data['review_count'] ?? '',
                is_array($product['badges'] ?? $product_data['badges'] ?? '') ?
                    implode(', ', $product['badges'] ?? $product_data['badges'] ?? array()) : '',
                $product['image_url'] ?? $product_data['image'] ?? '',
                is_array($product_data['categories'] ?? '') ?
                    implode(', ', $product_data['categories'] ?? array()) : '',
                $product_data['sku'] ?? '',
                $product_data['full_description'] ?? $product_data['description'] ?? '',
                $product['scraped_at'] ?? ''
            );

            fputcsv($output, $row);
        }

        fclose($output);
        exit;
    }

    /**
     * Delete stored products
     */
    public function ajax_delete_stored_products()
    {
        if (!wp_verify_nonce($_POST['nonce'], 'delete_products_nonce')) {
            wp_die('Security check failed');
        }

        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }

        $plugin = new ProductScraper();

        if (isset($_POST['delete_all']) && $_POST['delete_all']) {
            // Delete all products
            global $wpdb;
            $table_name = $wpdb->prefix . 'scraped_products';
            $result = $wpdb->query("TRUNCATE TABLE $table_name");

            if ($result !== false) {
                wp_send_json_success('All products deleted successfully');
            } else {
                wp_send_json_error('Error deleting products');
            }
        } elseif (isset($_POST['product_ids'])) {
            // Delete specific products
            $product_ids = array_map('intval', $_POST['product_ids']);
            $result = $plugin->storage->delete_products($product_ids);

            if ($result !== false) {
                wp_send_json_success('Products deleted successfully');
            } else {
                wp_send_json_error('Error deleting products');
            }
        } else {
            wp_send_json_error('No products specified for deletion');
        }
    }

    public function ajax_test_selectors()
    {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'test_selectors_nonce')) {
            wp_die('Security check failed');
        }

        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }

        $target_url = esc_url_raw($_POST['target_url']);

        $scraper = new ProductScraperEngine();
        $scraper->set_base_url($target_url);

        $html = $scraper->fetch_page($target_url);

        if (!$html) {
            wp_send_json_error('Failed to fetch page');
        }

        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);

        $test_results = array(
            'product_count' => $xpath->query('//ol[contains(@class, "grid")]//li[contains(@class, "product-item")]')->length,
            'product_names' => array(),
            'product_prices' => array(),
            'product_images' => array()
        );

        // Test name selection
        $name_nodes = $xpath->query('//ol[contains(@class, "grid")]//li[contains(@class, "product-item")]//p[contains(@class, "product-item-link")]');
        foreach ($name_nodes as $node) {
            $test_results['product_names'][] = trim($node->textContent);
        }

        // Test price selection
        $price_nodes = $xpath->query('//ol[contains(@class, "grid")]//li[contains(@class, "product-item")]//span[@class="price"]');
        foreach ($price_nodes as $node) {
            $test_results['product_prices'][] = trim($node->textContent);
        }

        // Test image selection
        $img_nodes = $xpath->query('//ol[contains(@class, "grid")]//li[contains(@class, "product-item")]//img');
        foreach ($img_nodes as $node) {
            $test_results['product_images'][] = $node->getAttribute('src');
        }

        wp_send_json_success($test_results);
    }

    /**
     * Handle direct export requests
     */
    public function handle_export()
    {
        if (!current_user_can('manage_options')) {
            return;
        }

        $format = $_GET['export'] ?? '';
        if (in_array($format, ['csv', 'excel'])) {
            $this->export_products($format);
        }
    }

    /**
     * Debug database connection and table
     */
    public function debug_database()
    {
        global $wpdb;
        $table_name = $wpdb->prefix . 'scraped_products';

        // Check if table exists
        $table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;

        // Check table structure
        $table_structure = $wpdb->get_results("DESCRIBE $table_name");

        // Count existing records
        $record_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");

        return array(
            'table_exists' => $table_exists,
            'table_structure' => $table_structure,
            'record_count' => $record_count,
            'table_name' => $table_name
        );
    }

    public function ajax_debug_database()
    {
        if (!wp_verify_nonce($_POST['nonce'], 'debug_nonce')) {
            wp_die('Security check failed');
        }

        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }

        $debug_info = $this->debug_database();
        wp_send_json_success($debug_info);
    }
}
?>