<?php

class ProductScraperAdmin
{

    public function __construct()
    {
        add_action('admin_menu', array($this, 'add_admin_menu'));
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
            <h1>Product Scraper - Nahrin.ch</h1>

            <div id="scraper-app">
                <form method="post" action="options.php">
                    <?php
                    settings_fields('product_scraper_settings');
                    $options = get_option('product_scraper_options', array());
                    ?>

                    <table class="form-table">
                        <tr>
                            <th scope="row">Nahrin.ch Shop URL</th>
                            <td>
                                <input type="url" name="product_scraper_options[target_url]"
                                    value="<?php echo esc_attr($options['target_url'] ?? 'https://www.nahrin.ch/de/lebensmittel/saucen'); ?>"
                                    class="regular-text">
                                <p class="description">e.g., https://www.nahrin.ch/de/lebensmittel/saucen</p>
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
                <div class="storage-stats card">
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

                <h2>Scrape Products</h2>
                <div class="scraper-controls">
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

        <style>
            #progress-bar {
                width: 100%;
                height: 20px;
                background: #f0f0f0;
                border-radius: 10px;
                overflow: hidden;
                margin: 10px 0;
            }

            #progress-bar-inner {
                height: 100%;
                background: #0073aa;
                width: 0%;
                transition: width 0.3s ease;
            }

            .product-item {
                border: 1px solid #ddd;
                padding: 15px;
                margin: 10px 0;
                border-radius: 5px;
                background: #f9f9f9;
            }

            .product-badges {
                display: flex;
                gap: 5px;
                margin: 5px 0;
            }

            .badge {
                background: #ffd700;
                padding: 2px 8px;
                border-radius: 3px;
                font-size: 12px;
                font-weight: bold;
            }

            .product-stats {
                display: flex;
                gap: 15px;
                font-size: 14px;
                color: #666;
            }

            .storage-stats {
                padding: 15px;
                background: #f9f9f9;
                border-radius: 5px;
                margin: 15px 0;
            }

            .stats-grid {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
                gap: 15px;
                margin-top: 10px;
            }

            .stat-item {
                text-align: center;
                padding: 10px;
                background: white;
                border-radius: 5px;
            }

            .stat-number {
                display: block;
                font-size: 24px;
                font-weight: bold;
                color: #0073aa;
            }

            .stat-date {
                display: block;
                font-size: 14px;
                font-weight: bold;
                color: #0073aa;
            }

            .stat-label {
                font-size: 12px;
                color: #666;
            }

            .export-controls {
                margin: 15px 0;
                padding: 10px;
                background: #f0f0f0;
                border-radius: 5px;
            }

            .button-danger {
                background: #dc3232;
                border-color: #dc3232;
                color: white;
            }

            .button-danger:hover {
                background: #a00;
                border-color: #a00;
            }
        </style>

        <script>
            jQuery(document).ready(function($) {
                let scrapedProducts = [];
                let storedProducts = [];
                let currentStats = {
                    products: 0,
                    pages: 0,
                    errors: 0
                };

                // Load stored products on page load
                loadStoredProducts();

                $('#start-scraping').on('click', function() {
                    const targetUrl = $('input[name="product_scraper_options[target_url]"]').val();
                    const maxPages = $('input[name="product_scraper_options[max_pages]"]').val();
                    const scrapeDetails = $('input[name="product_scraper_options[scrape_details]"]').is(':checked');
                    const delay = $('input[name="product_scraper_options[request_delay]"]').val();

                    if (!targetUrl) {
                        alert('Please enter a target URL');
                        return;
                    }

                    $('#scraping-progress').show();
                    $('#start-scraping').prop('disabled', true);
                    scrapedProducts = [];
                    currentStats = {
                        products: 0,
                        pages: 0,
                        errors: 0
                    };

                    scrapePage(1, parseInt(maxPages), targetUrl, scrapeDetails, parseFloat(delay));
                });

                $('#test-selectors').on('click', function() {
                    const targetUrl = $('input[name="product_scraper_options[target_url]"]').val();

                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'test_selectors',
                            target_url: targetUrl,
                            nonce: '<?php echo wp_create_nonce("test_selectors_nonce"); ?>'
                        },
                        success: function(response) {
                            if (response.success) {
                                $('#scraping-results').html('<div class="notice notice-success"><pre>' +
                                    JSON.stringify(response.data, null, 2) + '</pre></div>');
                            } else {
                                alert('Error: ' + response.data);
                            }
                        }
                    });
                });

                $('#refresh-stored').on('click', function() {
                    loadStoredProducts();
                });

                $('#save-to-db').on('click', function() {
                    if (scrapedProducts.length === 0) {
                        alert('No products to save');
                        return;
                    }

                    const targetUrl = $('input[name="product_scraper_options[target_url]"]').val();

                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'scrape_products',
                            page: 1,
                            max_pages: 1,
                            target_url: targetUrl,
                            save_only: true,
                            products_data: JSON.stringify(scrapedProducts),
                            nonce: '<?php echo wp_create_nonce("scrape_products_nonce"); ?>'
                        },
                        success: function(response) {
                            if (response.success) {
                                alert('Products saved to database successfully!');
                                loadStoredProducts();
                            } else {
                                alert('Error saving products: ' + response.data);
                            }
                        }
                    });
                });

                $('#export-csv').on('click', function() {
                    exportProducts('csv', scrapedProducts);
                });

                $('#export-excel').on('click', function() {
                    exportProducts('excel', scrapedProducts);
                });

                $('#export-all-csv').on('click', function() {
                    exportProducts('csv', storedProducts, true);
                });

                $('#export-all-excel').on('click', function() {
                    exportProducts('excel', storedProducts, true);
                });

                $('#select-all').on('change', function() {
                    $('.product-checkbox').prop('checked', this.checked);
                });

                $('#do-bulk-action').on('click', function() {
                    const action = $('#bulk-action-selector').val();
                    const selectedProducts = $('.product-checkbox:checked').map(function() {
                        return $(this).val();
                    }).get();

                    if (selectedProducts.length === 0) {
                        alert('Please select products to perform bulk action');
                        return;
                    }

                    switch (action) {
                        case 'export_csv':
                            exportProducts('csv', storedProducts.filter(p => selectedProducts.includes(p.id.toString())));
                            break;
                        case 'export_excel':
                            exportProducts('excel', storedProducts.filter(p => selectedProducts.includes(p.id.toString())));
                            break;
                        case 'delete':
                            if (confirm(`Are you sure you want to delete ${selectedProducts.length} products?`)) {
                                deleteProducts(selectedProducts);
                            }
                            break;
                        default:
                            alert('Please select a bulk action');
                    }
                });

                $('#delete-all-products').on('click', function() {
                    if (confirm('Are you sure you want to delete ALL stored products? This action cannot be undone.')) {
                        $.ajax({
                            url: ajaxurl,
                            type: 'POST',
                            data: {
                                action: 'delete_stored_products',
                                delete_all: true,
                                nonce: '<?php echo wp_create_nonce("delete_products_nonce"); ?>'
                            },
                            success: function(response) {
                                if (response.success) {
                                    alert('All products deleted successfully!');
                                    loadStoredProducts();
                                } else {
                                    alert('Error deleting products: ' + response.data);
                                }
                            }
                        });
                    }
                });

                function loadStoredProducts() {
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'get_stored_products',
                            nonce: '<?php echo wp_create_nonce("get_products_nonce"); ?>'
                        },
                        success: function(response) {
                            if (response.success) {
                                storedProducts = response.data.products;
                                displayStoredProducts(storedProducts, response.data.stats);
                            }
                        }
                    });
                }

                function displayStoredProducts(products, stats) {
                    let html = '';

                    if (products.length === 0) {
                        html = '<tr><td colspan="9">No products stored yet.</td></tr>';
                    } else {
                        products.forEach(product => {
                            html += `
                            <tr>
                                <td><input type="checkbox" class="product-checkbox" value="${product.id}"></td>
                                <td>${product.id}</td>
                                <td>${product.product_name}</td>
                                <td>${product.price_display} (${product.price} CHF)</td>
                                <td>${product.rating_stars} ★</td>
                                <td>${product.review_count}</td>
                                <td>${new Date(product.scraped_at).toLocaleDateString()}</td>
                                <td>${product.imported ? 'Imported' : 'Not Imported'}</td>
                                <td>
                                    <button class="button button-small export-single" data-id="${product.id}" data-format="csv">CSV</button>
                                    <button class="button button-small export-single" data-id="${product.id}" data-format="excel">Excel</button>
                                    <button class="button button-small button-danger delete-single" data-id="${product.id}">Delete</button>
                                </td>
                            </tr>
                        `;
                        });
                    }

                    $('#stored-products-list').html(html);
                    $('#storage-info').text(`Showing ${products.length} products`);

                    // Add event listeners for single actions
                    $('.export-single').on('click', function() {
                        const productId = $(this).data('id');
                        const format = $(this).data('format');
                        const product = storedProducts.find(p => p.id == productId);
                        if (product) {
                            exportProducts(format, [product]);
                        }
                    });

                    $('.delete-single').on('click', function() {
                        const productId = $(this).data('id');
                        if (confirm('Are you sure you want to delete this product?')) {
                            deleteProducts([productId]);
                        }
                    });
                }

                function deleteProducts(productIds) {
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'delete_stored_products',
                            product_ids: productIds,
                            nonce: '<?php echo wp_create_nonce("delete_products_nonce"); ?>'
                        },
                        success: function(response) {
                            if (response.success) {
                                alert('Products deleted successfully!');
                                loadStoredProducts();
                            } else {
                                alert('Error deleting products: ' + response.data);
                            }
                        }
                    });
                }

                function exportProducts(format, products, isAll = false) {
                    if (products.length === 0) {
                        alert('No products to export');
                        return;
                    }

                    const form = $('<form>', {
                        method: 'post',
                        action: ajaxurl,
                        style: 'display: none;'
                    });

                    form.append($('<input>', {
                        type: 'hidden',
                        name: 'action',
                        value: format === 'csv' ? 'export_products_csv' : 'export_products_excel'
                    }));

                    form.append($('<input>', {
                        type: 'hidden',
                        name: 'products_data',
                        value: JSON.stringify(products)
                    }));

                    form.append($('<input>', {
                        type: 'hidden',
                        name: 'nonce',
                        value: '<?php echo wp_create_nonce("export_products_nonce"); ?>'
                    }));

                    if (isAll) {
                        form.append($('<input>', {
                            type: 'hidden',
                            name: 'export_all',
                            value: '1'
                        }));
                    }

                    $('body').append(form);
                    form.submit();
                    form.remove();
                }

                // ... rest of your existing functions (scrapePage, updateStats, scrapingComplete, displayProducts) ...
                function scrapePage(page, maxPages, targetUrl, scrapeDetails, delay) {
                    currentStats.pages = page;
                    updateStats();

                    $('#progress-text').text(`Scraping page ${page} of ${maxPages}...`);
                    $('#progress-bar-inner').css('width', ((page - 1) / maxPages) * 100 + '%');

                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'scrape_products',
                            page: page,
                            max_pages: maxPages,
                            target_url: targetUrl,
                            scrape_details: scrapeDetails,
                            nonce: '<?php echo wp_create_nonce("scrape_products_nonce"); ?>'
                        },
                        success: function(response) {
                            if (response.success) {
                                scrapedProducts = scrapedProducts.concat(response.data.products);
                                currentStats.products = scrapedProducts.length;

                                if (response.data.errors) {
                                    currentStats.errors += response.data.errors;
                                }

                                // Show debug info
                                if (response.data.debug) {
                                    console.log('Debug Info:', response.data.debug);
                                    $('#scraping-results').append(
                                        '<div class="notice notice-info"><strong>Debug:</strong><pre>' +
                                        JSON.stringify(response.data.debug, null, 2) + '</pre></div>'
                                    );
                                }

                                updateStats();

                                if (page < maxPages && response.data.has_more) {
                                    setTimeout(function() {
                                        scrapePage(page + 1, maxPages, targetUrl, scrapeDetails, delay);
                                    }, delay * 1000);
                                } else {
                                    scrapingComplete();
                                }
                            } else {
                                currentStats.errors++;
                                updateStats();
                                $('#scraping-results').append('<div class="notice notice-error">Error scraping page ' + page + ': ' + response.data + '</div>');

                                if (page < maxPages) {
                                    setTimeout(function() {
                                        scrapePage(page + 1, maxPages, targetUrl, scrapeDetails, delay);
                                    }, delay * 1000);
                                } else {
                                    scrapingComplete();
                                }
                            }
                        },
                        error: function(xhr, status, error) {
                            currentStats.errors++;
                            updateStats();
                            $('#scraping-results').append('<div class="notice notice-error">AJAX error on page ' + page + ': ' + error + '</div>');

                            if (page < maxPages) {
                                setTimeout(function() {
                                    scrapePage(page + 1, maxPages, targetUrl, scrapeDetails, delay);
                                }, delay * 1000);
                            } else {
                                scrapingComplete();
                            }
                        }
                    });
                }

                function updateStats() {
                    $('#scraping-stats').html(`
                    <div class="product-stats">
                        <span>Pages: ${currentStats.pages}</span>
                        <span>Products: ${currentStats.products}</span>
                        <span>Errors: ${currentStats.errors}</span>
                    </div>
                `);
                }

                function scrapingComplete() {
                    $('#progress-text').text(`Scraping completed! Found ${scrapedProducts.length} products.`);
                    $('#progress-bar-inner').css('width', '100%');
                    $('#start-scraping').prop('disabled', false);
                    $('#import-woocommerce').prop('disabled', false);

                    displayProducts(scrapedProducts);
                }

                function displayProducts(products) {
                    $('#scraped-products').show();
                    $('#products-count').text(`(${products.length} products)`);

                    let html = '';

                    products.forEach((product, index) => {
                        html += `
                        <div class="product-item">
                            <h4>${product.name || 'No Name'}</h4>
                            ${product.badges ? `<div class="product-badges">${
                                product.badges.map(badge => `<span class="badge">${badge}</span>`).join('')
                            }</div>` : ''}
                            <p><strong>Price:</strong> ${product.price || 'N/A'} ${product.price_amount ? `(${product.price_amount} CHF)` : ''}</p>
                            <p><strong>Rating:</strong> ${product.rating_stars || 0} stars (${product.review_count || 0} reviews)</p>
                            ${product.url ? `<p><strong>URL:</strong> <a href="${product.url}" target="_blank">${product.url}</a></p>` : ''}
                            ${product.image ? `<img src="${product.image}" style="max-width: 100px; height: auto;" loading="lazy">` : ''}
                        </div>
                    `;
                    });

                    $('#products-list').html(html);

                    // Store products in global variable for export
                    window.scrapedProductsData = products;
                }
            });
        </script>

        <!-- temprarily add debug info  -->
        <button id="debug-db" class="button button-secondary">Debug Database</button>

        <script>
        $('#debug-db').on('click', function() {
        $.ajax({
        url: ajaxurl,
        type: 'POST',
        data: {
        action: 'debug_database',
        nonce: '<?php echo wp_create_nonce("debug_nonce"); ?>'
        },
        success: function(response) {
        console.log('Database Debug:', response);
        alert('Check browser console for database debug information');
        }
        });
        });
        </script>
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
            error_log('ProductScraper: Storage object not initialized');
            return 0;
        }

        try {
            $saved_count = $plugin->storage->save_products($products, $source_url);
            error_log("ProductScraper: Attempted to save " . count($products) . " products, saved: " . $saved_count);
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