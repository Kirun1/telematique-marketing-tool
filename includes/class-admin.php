<?php

class ProductScraperAdmin {
    
    public function __construct() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'admin_init'));
        add_action('wp_ajax_scrape_products', array($this, 'ajax_scrape_products'));
    }
    
    public function add_admin_menu() {
        add_options_page(
            'Product Scraper',
            'Product Scraper',
            'manage_options',
            'product-scraper',
            array($this, 'admin_page')
        );
    }
    
    public function admin_init() {
        register_setting('product_scraper_settings', 'product_scraper_options');
    }
    
    public function admin_page() {
        ?>
        <div class="wrap">
            <h1>Product Scraper</h1>
            
            <div id="scraper-app">
                <form method="post" action="options.php">
                    <?php
                    settings_fields('product_scraper_settings');
                    $options = get_option('product_scraper_options', array());
                    ?>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">Target Website URL</th>
                            <td>
                                <input type="url" name="product_scraper_options[target_url]" 
                                       value="<?php echo esc_attr($options['target_url'] ?? ''); ?>" 
                                       class="regular-text" placeholder="https://example.com/shop/">
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
                    </table>
                    
                    <?php submit_button('Save Settings'); ?>
                </form>
                
                <hr>
                
                <h2>Scrape Products</h2>
                <div class="scraper-controls">
                    <button id="start-scraping" class="button button-primary">Start Scraping</button>
                    <button id="import-woocommerce" class="button button-secondary" disabled>Import to WooCommerce</button>
                </div>
                
                <div id="scraping-progress" style="display: none;">
                    <h3>Scraping Progress</h3>
                    <div id="progress-bar">
                        <div id="progress-bar-inner"></div>
                    </div>
                    <div id="progress-text">Starting...</div>
                    <div id="scraping-results"></div>
                </div>
                
                <div id="scraped-products" style="display: none;">
                    <h3>Scraped Products</h3>
                    <div id="products-list"></div>
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
            }
        </style>
        
        <script>
        jQuery(document).ready(function($) {
            let scrapedProducts = [];
            
            $('#start-scraping').on('click', function() {
                const targetUrl = $('input[name="product_scraper_options[target_url]"]').val();
                const maxPages = $('input[name="product_scraper_options[max_pages]"]').val();
                
                if (!targetUrl) {
                    alert('Please enter a target URL');
                    return;
                }
                
                $('#scraping-progress').show();
                $('#start-scraping').prop('disabled', true);
                
                scrapePage(1, parseInt(maxPages), targetUrl);
            });
            
            function scrapePage(page, maxPages, targetUrl) {
                $('#progress-text').text(`Scraping page ${page} of ${maxPages}...`);
                
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'scrape_products',
                        page: page,
                        max_pages: maxPages,
                        target_url: targetUrl,
                        nonce: '<?php echo wp_create_nonce("scrape_products_nonce"); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            scrapedProducts = scrapedProducts.concat(response.data.products);
                            
                            const progress = (page / maxPages) * 100;
                            $('#progress-bar-inner').css('width', progress + '%');
                            
                            if (page < maxPages && response.data.has_more) {
                                scrapePage(page + 1, maxPages, targetUrl);
                            } else {
                                scrapingComplete();
                            }
                        } else {
                            alert('Error: ' + response.data);
                        }
                    },
                    error: function() {
                        alert('AJAX error occurred');
                    }
                });
            }
            
            function scrapingComplete() {
                $('#progress-text').text('Scraping completed! Found ' + scrapedProducts.length + ' products.');
                $('#start-scraping').prop('disabled', false);
                $('#import-woocommerce').prop('disabled', false);
                
                displayProducts(scrapedProducts);
            }
            
            function displayProducts(products) {
                $('#scraped-products').show();
                let html = '';
                
                products.forEach((product, index) => {
                    html += `
                        <div class="product-item">
                            <h4>${product.name}</h4>
                            <p>Price: ${product.price || 'N/A'}</p>
                            <p>${product.description || ''}</p>
                        </div>
                    `;
                });
                
                $('#products-list').html(html);
            }
            
            $('#import-woocommerce').on('click', function() {
                if (scrapedProducts.length === 0) {
                    alert('No products to import');
                    return;
                }
                
                if (!confirm(`Import ${scrapedProducts.length} products to WooCommerce?`)) {
                    return;
                }
                
                // This would be implemented with another AJAX endpoint
                alert('Import functionality would be implemented here');
            });
        });
        </script>
        <?php
    }
    
    public function ajax_scrape_products() {
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
        
        $scraper = new ProductScraperEngine();
        $scraper->set_base_url($target_url);
        
        $products = $scraper->scrape_products($page, 1); // Scrape only one page per AJAX call
        
        wp_send_json_success(array(
            'products' => $products,
            'has_more' => count($products) > 0 && $page < $max_pages
        ));
    }
}
?>