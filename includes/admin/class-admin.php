<?php

class ProductScraperAdmin
{

	private $firecrawl;

	public function __construct()
	{
		add_action('admin_init', array($this, 'admin_init'));
		add_action('wp_ajax_scrape_products', array($this, 'ajax_scrape_products'));
		add_action('wp_ajax_test_selectors', array($this, 'ajax_test_selectors'));
		add_action('wp_ajax_get_stored_products', array($this, 'ajax_get_stored_products'));
		add_action('wp_ajax_export_products_csv', array($this, 'ajax_export_products_csv'));
		add_action('wp_ajax_export_products_excel', array($this, 'ajax_export_products_excel'));
		add_action('wp_ajax_delete_stored_products', array($this, 'ajax_delete_stored_products'));
		add_action('wp_ajax_test_firecrawl_connection', array($this, 'ajax_test_firecrawl_connection'));

		// Initialize Firecrawl integration
		$this->firecrawl = new ProductScraper_Firecrawl_Integration();

		// Handle direct export requests.
		if (isset($_GET['page']) && $_GET['page'] === 'product-scraper' && isset($_GET['export'])) {
			add_action('admin_init', array($this, 'handle_export'));
		}
	}

	public function admin_init()
	{
		register_setting('product_scraper_settings', 'product_scraper_options');
		register_setting('product_scraper_firecrawl_settings', 'product_scraper_firecrawl_api_key');
		register_setting('product_scraper_firecrawl_settings', 'product_scraper_firecrawl_base_url');
		register_setting('product_scraper_firecrawl_settings', 'product_scraper_firecrawl_timeout');
	}

	public function admin_page()
	{
		// Get stored products stats.
		$plugin = new ProductScraper();
		$stats  = $plugin->storage->get_stats();

		// Get Firecrawl settings
        $firecrawl_api_key = get_option('product_scraper_firecrawl_api_key', '');
        $firecrawl_base_url = get_option('product_scraper_firecrawl_base_url', 'https://api.firecrawl.dev');
        $firecrawl_timeout = get_option('product_scraper_firecrawl_timeout', 30);
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
									<p>Configure the settings below to start scraping products from any shop using Firecrawl API.</p>
								</div>
							</div>
						</div>
						<div id="scraper-app">
							<form method="post" action="options.php">
								<?php settings_fields( 'product_scraper_firecrawl_settings' ); ?>

								<table class="form-table">
									<tr>
										<th scope="row">Firecrawl API Key</th>
										<td>
											<input type="password" name="product_scraper_firecrawl_api_key"
												value="<?php echo esc_attr($firecrawl_api_key); ?>"
												class="regular-text">
											<p class="description">Your Firecrawl API key. Get it from <a href="https://firecrawl.dev" target="_blank">firecrawl.dev</a></p>
										</td>
									</tr>
									<tr>
										<th scope="row">API Base URL</th>
										<td>
											<input type="url" name="product_scraper_firecrawl_base_url"
												value="<?php echo esc_attr($firecrawl_base_url); ?>"
												class="regular-text">
											<p class="description">Firecrawl API endpoint (default: https://api.firecrawl.dev)</p>
										</td>
									</tr>
									<tr>
										<th scope="row">Request Timeout</th>
										<td>
											<input type="number" name="product_scraper_firecrawl_timeout"
												value="<?php echo esc_attr($firecrawl_timeout); ?>"
												min="10" max="120" class="small-text">
											<span class="description">seconds</span>
										</td>
									</tr>									
								</table>

								<?php submit_button('Save Firecrawl Settings'); ?>
								<button type="button" id="test-firecrawl" class="button button-secondary">Test Firecrawl Connection</button>
							</form>

							<hr>

							<!-- Original Scraper Settings -->
                            <form method="post" action="options.php">
                                <?php
                                settings_fields( 'product_scraper_settings' );
                                $options = get_option( 'product_scraper_options', array() );
                                ?>

                                <table class="form-table">
                                    <tr>
                                        <th scope="row">Shop URL</th>
                                        <td>
                                            <input type="url" name="product_scraper_options[target_url]"
                                                value="<?php echo esc_attr( $options['target_url'] ?? 'https://www.example.com/de/lebensmittel/saucen' ); ?>"
                                                class="regular-text">
                                            <p class="description">e.g., https://www.example.com/de/lebensmittel/saucen</p>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">Maximum Pages to Scrape</th>
                                        <td>
                                            <input type="number" name="product_scraper_options[max_pages]"
                                                value="<?php echo esc_attr( $options['max_pages'] ?? 10 ); ?>"
                                                min="1" max="50">
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">Use Firecrawl API</th>
                                        <td>
                                            <input type="checkbox" name="product_scraper_options[use_firecrawl]"
                                                value="1" <?php checked( $options['use_firecrawl'] ?? 1 ); ?>>
                                            <span class="description">Use Firecrawl API for scraping (recommended)</span>
                                        </td>
                                    </tr>
                                    <tr>
                                        <th scope="row">Scrape Product Details</th>
                                        <td>
                                            <input type="checkbox" name="product_scraper_options[scrape_details]"
                                                value="1" <?php checked( $options['scrape_details'] ?? 1 ); ?>>
                                            <span class="description">Visit each product page for detailed information</span>
                                        </td>
                                    </tr>
                                </table>

                                <?php submit_button( 'Save Settings' ); ?>
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
                                        <span class="stat-date">
                                        <?php
                                        if ( ! empty( $stats['last_scraped'] ) && $stats['last_scraped'] !== '0000-00-00 00:00:00' ) {
                                            echo date( 'M j, Y g:i A', strtotime( $stats['last_scraped'] ) );
                                        } else {
                                            echo 'Never';
                                        }
                                        ?>
                                        </span>
                                        <span class="stat-label">Last Scraped</span>
                                    </div>
                                </div>
                            </div>

							<hr>

							<div class="scraper-controls">
                                <h2>Scrape Products</h2>
                                <button id="start-scraping" class="button button-primary">Start Scraping with Firecrawl</button>
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

		<script>
        jQuery(document).ready(function($) {
            // Test Firecrawl connection
            $('#test-firecrawl').on('click', function() {
                var $button = $(this);
                var originalText = $button.text();
                
                $button.text('Testing...').prop('disabled', true);
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'test_firecrawl_connection',
                        nonce: '<?php echo wp_create_nonce('test_firecrawl_nonce'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            alert('Firecrawl API connection successful!');
                        } else {
                            alert('Firecrawl API connection failed: ' + response.data);
                        }
                    },
                    error: function() {
                        alert('Error testing Firecrawl connection.');
                    },
                    complete: function() {
                        $button.text(originalText).prop('disabled', false);
                    }
                });
            });

            // Update scraping button text based on Firecrawl setting
            $('input[name="product_scraper_options[use_firecrawl]"]').on('change', function() {
                if ($(this).is(':checked')) {
                    $('#start-scraping').text('Start Scraping with Firecrawl');
                } else {
                    $('#start-scraping').text('Start Scraping (Legacy)');
                }
            });

			// Start scraping products
			$('#start-scraping').on('click', function() {
				startScraping();
			});

			// Refresh stored products
			$('#refresh-stored').on('click', function() {
				refreshStoredProducts();
			});

			// Test selectors
			$('#test-selectors').on('click', function() {
				testSelectors();
			});

			function startScraping() {
				var $button = $('#start-scraping');
				var originalText = $button.text();
				
				// Get settings from the form
				var target_url = $('input[name="product_scraper_options[target_url]"]').val();
				var max_pages = $('input[name="product_scraper_options[max_pages]"]').val();
				var use_firecrawl = $('input[name="product_scraper_options[use_firecrawl]"]').is(':checked') ? 1 : 0;
				var scrape_details = $('input[name="product_scraper_options[scrape_details]"]').is(':checked') ? 1 : 0;

				if (!target_url) {
					alert('Please enter a target URL');
					return;
				}

				$button.text('Scraping...').prop('disabled', true);
				$('#scraping-progress').show();
				$('#progress-text').text('Starting scraping process...');

				// Start the scraping process
				scrapePage(1, max_pages, target_url, use_firecrawl, scrape_details, []);
			}

			function scrapePage(page, max_pages, target_url, use_firecrawl, scrape_details, all_products) {
				$('#progress-text').text('Scraping page ' + page + ' of ' + max_pages + '...');
				$('#progress-bar-inner').css('width', ((page / max_pages) * 100) + '%');

				$.ajax({
					url: ajaxurl,
					type: 'POST',
					data: {
						action: 'scrape_products',
						page: page,
						max_pages: max_pages,
						target_url: target_url,
						use_firecrawl: use_firecrawl,
						scrape_details: scrape_details,
						nonce: '<?php echo wp_create_nonce('scrape_products_nonce'); ?>'
					},
					success: function(response) {
						if (response.success) {
							// Add scraped products to our collection
							if (response.data.products && response.data.products.length > 0) {
								all_products = all_products.concat(response.data.products);
								
								// Update progress
								$('#scraping-stats').html(
									'Scraped ' + response.data.products.length + ' products on page ' + page + 
									' (Total: ' + all_products.length + ' products)'
								);

								// Check if there are more pages to scrape
								if (response.data.has_more && page < max_pages) {
									setTimeout(function() {
										scrapePage(page + 1, max_pages, target_url, use_firecrawl, scrape_details, all_products);
									}, 1000); // Delay between pages
								} else {
									// Finished scraping
									finishScraping(all_products, target_url, response.data.saved_count);
								}
							} else {
								$('#progress-text').text('No products found on page ' + page);
								finishScraping(all_products, target_url, response.data.saved_count);
							}
						} else {
							console.log('Error: ' + response.data);
							$('#progress-text').text('Error: ' + response.data);
							$('#start-scraping').text(originalText).prop('disabled', false);
						}
					},
					error: function(xhr, status, error) {
						$('#progress-text').text('AJAX Error: ' + error);
						$('#start-scraping').text(originalText).prop('disabled', false);
					}
				});
			}

			function finishScraping(products, target_url, saved_count) {
				$('#progress-text').text('Scraping completed! Found ' + products.length + ' products. Saved ' + saved_count + ' to database.');
				$('#progress-bar-inner').css('width', '100%');
				$('#start-scraping').text('Start Scraping with Firecrawl').prop('disabled', false);

				// Show scraped products
				if (products.length > 0) {
					displayScrapedProducts(products, target_url);
				}
			}

			function displayScrapedProducts(products, target_url) {
				$('#products-count').text('(' + products.length + ')');
				var $productsList = $('#products-list');
				$productsList.empty();

				products.forEach(function(product, index) {
					var productHtml = `
						<div class="product-item">
							<h4>${product.name || 'Unnamed Product'}</h4>
							<p><strong>Price:</strong> ${product.price || 'N/A'}</p>
							<p><strong>URL:</strong> <a href="${product.url || '#'}" target="_blank">${product.url || 'N/A'}</a></p>
							${product.description ? '<p><strong>Description:</strong> ' + product.description + '</p>' : ''}
							${product.image ? '<img src="' + product.image + '" style="max-width: 100px; max-height: 100px;" />' : ''}
							<hr>
						</div>
					`;
					$productsList.append(productHtml);
				});

				$('#scraped-products').show();

				// Store products data for saving/exporting
				$('#scraped-products').data('products', products);
				$('#scraped-products').data('source_url', target_url);
			}

			function refreshStoredProducts() {
				var $button = $('#refresh-stored');
				var originalText = $button.text();
				
				$button.text('Loading...').prop('disabled', true);

				$.ajax({
					url: ajaxurl,
					type: 'POST',
					data: {
						action: 'get_stored_products',
						nonce: '<?php echo wp_create_nonce('get_products_nonce'); ?>'
					},
					success: function(response) {
						if (response.success) {
							updateStoredProductsTable(response.data.products);
							updateStorageStats(response.data.stats);
						} else {
							alert('Error loading stored products');
						}
					},
					error: function() {
						alert('Error loading stored products');
					},
					complete: function() {
						$button.text(originalText).prop('disabled', false);
					}
				});
			}

			function testSelectors() {
				var $button = $('#test-selectors');
				var originalText = $button.text();
				var target_url = $('input[name="product_scraper_options[target_url]"]').val();

				if (!target_url) {
					alert('Please enter a target URL');
					return;
				}

				$button.text('Testing...').prop('disabled', true);

				$.ajax({
					url: ajaxurl,
					type: 'POST',
					data: {
						action: 'test_selectors',
						target_url: target_url,
						nonce: '<?php echo wp_create_nonce('test_selectors_nonce'); ?>'
					},
					success: function(response) {
						if (response.success) {
							var results = response.data;
							alert(
								'Selector Test Results:\n\n' +
								'Products Found: ' + results.product_count + '\n' +
								'Product Names: ' + results.product_names.join(', ') + '\n' +
								'Product Prices: ' + results.product_prices.join(', ') + '\n' +
								'Product Images: ' + results.product_images.length + ' found'
							);
						} else {
							alert('Error testing selectors: ' + response.data);
						}
					},
					error: function() {
						alert('Error testing selectors');
					},
					complete: function() {
						$button.text(originalText).prop('disabled', false);
					}
				});
			}

			function updateStoredProductsTable(products) {
				var $tbody = $('#stored-products-list');
				$tbody.empty();

				if (products && products.length > 0) {
					products.forEach(function(product, index) {
						var rowHtml = `
							<tr>
								<th scope="row" class="check-column">
									<input type="checkbox" name="product_ids[]" value="${product.id}">
								</th>
								<td>${product.id}</td>
								<td>${product.product_name || 'N/A'}</td>
								<td>${product.price || 'N/A'}</td>
								<td>${product.rating_stars || 'N/A'}</td>
								<td>${product.review_count || '0'}</td>
								<td>${product.scraped_at || 'N/A'}</td>
								<td>${product.imported ? 'Imported' : 'Not Imported'}</td>
								<td>
									<button class="button button-small" onclick="viewProduct(${product.id})">View</button>
									<button class="button button-small button-danger" onclick="deleteProduct(${product.id})">Delete</button>
								</td>
							</tr>
						`;
						$tbody.append(rowHtml);
					});
				} else {
					$tbody.append('<tr><td colspan="9">No products stored yet.</td></tr>');
				}
			}

			function updateStorageStats(stats) {
				// Update the storage statistics display if needed
				console.log('Storage stats updated:', stats);
			}

			// Load stored products on page load
			refreshStoredProducts();
        });
        </script>
		<?php
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

	/**
     * AJAX handler for scraping products with Firecrawl
     */
    public function ajax_scrape_products() {
        // Verify nonce.
        if ( ! wp_verify_nonce( $_POST['nonce'], 'scrape_products_nonce' ) ) {
            wp_die( 'Security check failed' );
        }

        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( 'Insufficient permissions' );
        }

        $page = intval( $_POST['page'] );
        $max_pages = intval( $_POST['max_pages'] );
        $target_url = esc_url_raw( $_POST['target_url'] );
        $scrape_details = isset( $_POST['scrape_details'] ) ? boolval( $_POST['scrape_details'] ) : false;
        $use_firecrawl = isset( $_POST['use_firecrawl'] ) ? boolval( $_POST['use_firecrawl'] ) : true;
        $save_only = isset( $_POST['save_only'] ) ? boolval( $_POST['save_only'] ) : false;

        // Add debug info.
        $debug_info = array();

        // If we're just saving existing data.
        if ( $save_only && isset( $_POST['products_data'] ) ) {
            $products = json_decode( stripslashes( $_POST['products_data'] ), true );
            $saved_count = $this->save_scraped_products( $products, $target_url );

            wp_send_json_success(
                array(
                    'products' => $products,
                    'saved_count' => $saved_count,
                    'has_more' => false,
                    'errors' => 0,
                    'debug' => $debug_info,
                )
            );
        }

        $products = array();
        $errors = 0;

        try {
            if ($use_firecrawl) {
                // Use Firecrawl API for scraping
                $scraped_data = $this->firecrawl->scrape_url($target_url);
                $products = $this->firecrawl->extract_products($scraped_data);
                
                $debug_info['scraping_method'] = 'firecrawl';
                $debug_info['firecrawl_data'] = $scraped_data;
            } else {
                // Use legacy scraping method
                $scraper = new ProductScraperEngine();
                $scraper->set_base_url($target_url);
                $products = $scraper->scrape_products($page, 1);
                $debug_info['scraping_method'] = 'legacy';
            }

            // If detailed scraping is enabled, visit each product page
            if ($scrape_details && !empty($products)) {
                foreach ($products as &$product) {
                    if (!empty($product['url'])) {
                        try {
                            if ($use_firecrawl) {
                                $details_data = $this->firecrawl->scrape_url($product['url']);
                                $details = $this->extract_product_details($details_data);
                            } else {
                                $details = $scraper->scrape_product_details($product['url']);
                            }
                            
                            if ($details) {
                                $product = array_merge($product, $details);
                            } else {
                                $errors++;
                            }
                            
                            // Respectful delay
                            sleep(1);
                        } catch (Exception $e) {
                            $errors++;
                            error_log('Error scraping product details: ' . $e->getMessage());
                        }
                    }
                }
            }

            // Save to database
            $saved_count = 0;
            if (!empty($products)) {
                $saved_count = $this->save_scraped_products($products, $target_url);
            }

            $debug_info['products_scraped'] = count($products);
            $debug_info['database_debug'] = $this->debug_database();

            // Check if there are more pages (for legacy scraping)
            $has_more = !$use_firecrawl && $page < $max_pages && !empty($products);

            wp_send_json_success(
                array(
                    'products' => $products,
                    'has_more' => $has_more,
                    'errors' => $errors,
                    'saved_count' => $saved_count,
                    'debug' => $debug_info,
                )
            );

        } catch (Exception $e) {
            wp_send_json_error('Scraping failed: ' . $e->getMessage());
        }
    }

	/**
     * Extract product details from Firecrawl data
     */
    private function extract_product_details($scraped_data) {
        $details = array();
        
        if (isset($scraped_data['data'])) {
            $data = $scraped_data['data'];
            
            // Extract from markdown
            if (isset($data['markdown'])) {
                $markdown = $data['markdown'];
                
                // Extract price
                if (preg_match('/(?:\$|€|£|CHF)\s*([0-9]+[.,][0-9]+|[0-9]+)/', $markdown, $matches)) {
                    $details['price'] = $matches[0];
                }
                
                // Extract description (first paragraph)
                $lines = explode("\n", $markdown);
                foreach ($lines as $line) {
                    $line = trim($line);
                    if (!empty($line) && !preg_match('/^[#\-*!]/', $line)) {
                        $details['description'] = $line;
                        break;
                    }
                }
            }
            
            // Extract from LLM extraction if available
            if (isset($data['llm_extraction'])) {
                $llm_data = is_string($data['llm_extraction']) ? 
                           json_decode($data['llm_extraction'], true) : 
                           $data['llm_extraction'];
                
                if (isset($llm_data['price'])) {
                    $details['price'] = $llm_data['price'];
                }
                if (isset($llm_data['description'])) {
                    $details['description'] = $llm_data['description'];
                }
                if (isset($llm_data['sku'])) {
                    $details['sku'] = $llm_data['sku'];
                }
            }
        }
        
        return $details;
    }

	/**
     * AJAX handler for testing Firecrawl connection
     */
    public function ajax_test_firecrawl_connection() {
        if (!wp_verify_nonce($_POST['nonce'], 'test_firecrawl_nonce')) {
            wp_die('Security check failed');
        }

        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }

        try {
            $result = $this->firecrawl->test_connection();
            if ($result['success']) {
                wp_send_json_success('Firecrawl API connection successful');
            } else {
                wp_send_json_error($result['message']);
            }
        } catch (Exception $e) {
            wp_send_json_error($e->getMessage());
        }
    }

	/**
	 * Save products to database with debug information
	 */
	private function save_scraped_products($products, $source_url)
	{
		// Get the storage instance from the main plugin class.
		$plugin = new ProductScraper();

		// Debug: Check if storage object is created properly.
		if (! $plugin->storage) {
			return 0;
		}

		try {
			$saved_count = $plugin->storage->save_products($products, $source_url);
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
		if (! wp_verify_nonce($_POST['nonce'], 'get_products_nonce')) {
			wp_die('Security check failed');
		}

		if (! current_user_can('manage_options')) {
			wp_die('Insufficient permissions');
		}

		$source_url = isset($_POST['source_url']) ? esc_url_raw($_POST['source_url']) : '';
		$imported   = isset($_POST['imported']) ? intval($_POST['imported']) : null;

		$plugin   = new ProductScraper();
		$products = $plugin->storage->get_products($source_url, $imported);
		$stats    = $plugin->storage->get_stats();

		wp_send_json_success(
			array(
				'products' => $products,
				'stats'    => $stats,
			)
		);
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
		if (! wp_verify_nonce($_POST['nonce'], 'export_products_nonce')) {
			wp_die('Security check failed');
		}

		if (! current_user_can('manage_options')) {
			wp_die('Insufficient permissions');
		}

		// Get products data.
		$products = array();
		if (isset($_POST['export_all']) && $_POST['export_all']) {
			$plugin   = new ProductScraper();
			$products = $plugin->storage->get_products();
		} elseif (isset($_POST['products_data'])) {
			$products = json_decode(stripslashes($_POST['products_data']), true);
		}

		if (empty($products)) {
			wp_die('No products to export');
		}

		// Prepare CSV data.
		$filename = 'nahrin-products-' . date('Y-m-d-H-i-s') . '.' . ($format === 'csv' ? 'csv' : 'xlsx');

		// Set headers.
		if ($format === 'csv') {
			header('Content-Type: text/csv; charset=utf-8');
			header('Content-Disposition: attachment; filename="' . $filename . '"');
		} else {
			header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
			header('Content-Disposition: attachment; filename="' . $filename . '"');
		}

		// Create output stream.
		$output = fopen('php://output', 'w');

		// Add BOM for UTF-8.
		fwrite($output, "\xEF\xBB\xBF");

		// Headers.
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
			'Scraped Date',
		);

		fputcsv($output, $headers);

		// Data rows.
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
				$product['scraped_at'] ?? '',
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
		if (! wp_verify_nonce($_POST['nonce'], 'delete_products_nonce')) {
			wp_die('Security check failed');
		}

		if (! current_user_can('manage_options')) {
			wp_die('Insufficient permissions');
		}

		$plugin = new ProductScraper();

		if (isset($_POST['delete_all']) && $_POST['delete_all']) {
			// Delete all products.
			global $wpdb;
			$table_name = $wpdb->prefix . 'scraped_products';
			$result     = $wpdb->query("TRUNCATE TABLE $table_name");

			if ($result !== false) {
				wp_send_json_success('All products deleted successfully');
			} else {
				wp_send_json_error('Error deleting products');
			}
		} elseif (isset($_POST['product_ids'])) {
			// Delete specific products.
			$product_ids = array_map('intval', $_POST['product_ids']);
			$result      = $plugin->storage->delete_products($product_ids);

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
		// Verify nonce.
		if (! wp_verify_nonce($_POST['nonce'], 'test_selectors_nonce')) {
			wp_die('Security check failed');
		}

		if (! current_user_can('manage_options')) {
			wp_die('Insufficient permissions');
		}

		$target_url = esc_url_raw($_POST['target_url']);

		$scraper = new ProductScraperEngine();
		$scraper->set_base_url($target_url);

		$html = $scraper->fetch_page($target_url);

		if (! $html) {
			wp_send_json_error('Failed to fetch page');
		}

		$dom = new DOMDocument();
		libxml_use_internal_errors(true);
		$dom->loadHTML($html);
		libxml_clear_errors();

		$xpath = new DOMXPath($dom);

		$test_results = array(
			'product_count'  => $xpath->query('//ol[contains(@class, "grid")]//li[contains(@class, "product-item")]')->length,
			'product_names'  => array(),
			'product_prices' => array(),
			'product_images' => array(),
		);

		// Test name selection.
		$name_nodes = $xpath->query('//ol[contains(@class, "grid")]//li[contains(@class, "product-item")]//p[contains(@class, "product-item-link")]');
		foreach ($name_nodes as $node) {
			$test_results['product_names'][] = trim($node->textContent);
		}

		// Test price selection.
		$price_nodes = $xpath->query('//ol[contains(@class, "grid")]//li[contains(@class, "product-item")]//span[@class="price"]');
		foreach ($price_nodes as $node) {
			$test_results['product_prices'][] = trim($node->textContent);
		}

		// Test image selection.
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
		if (! current_user_can('manage_options')) {
			return;
		}

		$format = $_GET['export'] ?? '';
		if (in_array($format, array('csv', 'excel'))) {
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

		// Check if table exists.
		$table_exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;

		// Check table structure.
		$table_structure = $wpdb->get_results("DESCRIBE $table_name");

		// Count existing records.
		$record_count = $wpdb->get_var("SELECT COUNT(*) FROM $table_name");

		return array(
			'table_exists'    => $table_exists,
			'table_structure' => $table_structure,
			'record_count'    => $record_count,
			'table_name'      => $table_name,
		);
	}

	public function ajax_debug_database()
	{
		if (! wp_verify_nonce($_POST['nonce'], 'debug_nonce')) {
			wp_die('Security check failed');
		}

		if (! current_user_can('manage_options')) {
			wp_die('Insufficient permissions');
		}

		$debug_info = $this->debug_database();
		wp_send_json_success($debug_info);
	}
}
?>