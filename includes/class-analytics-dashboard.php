<?php

class ProductScraperAnalytics
{

    private $api;

    public function __construct()
    {
        $this->api = new ProductScraper_API_Integrations();

        add_action('admin_menu', array($this, 'add_analytics_menu'));
        add_action('wp_ajax_get_scraper_analytics', array($this, 'ajax_get_analytics'));
        add_action('wp_ajax_get_keyword_data', array($this, 'ajax_get_keyword_data'));
        add_action('wp_ajax_sync_seo_data', array($this, 'ajax_sync_seo_data'));

        // Initialize the admin class for the scraper functionality
        $this->admin = new ProductScraperAdmin();
    }

    /**
     * Add standalone analytics menu with scraper as subpage
     */
    public function add_analytics_menu()
    {
        add_menu_page(
            'Scraper Analytics',
            'Scraper Analytics',
            'manage_options',
            'scraper-analytics',
            array($this, 'display_analytics_dashboard'),
            'dashicons-chart-line',
            30
        );

        // Add submenus for different sections
        add_submenu_page(
            'scraper-analytics',
            'Dashboard',
            'Dashboard',
            'manage_options',
            'scraper-analytics',
            array($this, 'display_analytics_dashboard')
        );

        add_submenu_page(
            'scraper-analytics',
            'Keyword Analysis',
            'Keyword Analysis',
            'manage_options',
            'scraper-keywords',
            array($this, 'display_keyword_analysis')
        );

        add_submenu_page(
            'scraper-analytics',
            'Competitor Analysis',
            'Competitor Analysis',
            'manage_options',
            'scraper-competitors',
            array($this, 'display_competitor_analysis')
        );

        // Add Product Scraper as a subpage
        add_submenu_page(
            'scraper-analytics',
            'Product Scraper',
            'Product Scraper',
            'manage_options',
            'product-scraper',
            array($this, 'display_product_scraper')
        );
    }

    /**
     * Display the product scraper page
     */
    public function display_product_scraper()
    {
        // Call the existing admin page from ProductScraperAdmin class
        $this->admin->admin_page();
    }

    /**
     * Main analytics dashboard
     */
    public function display_analytics_dashboard()
    {
        $stats = $this->get_dashboard_stats();
?>
        <div class="wrap">
            <div class="scraper-analytics-dashboard">
                <!-- sa-style Header -->
                <div class="sa-header">
                    <div class="sa-brand">
                        <h1><strong>Scraper Analytics</strong></h1>
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
                    <!-- Left Sidebar -->
                    <div class="sa-sidebar">
                        <div class="sa-nav-section">
                            <h3>ANALYSIS</h3>
                            <ul class="sa-nav">
                                <li class="active"><a href="<?php echo admin_url('admin.php?page=scraper-analytics'); ?>"><span class="dashicons dashicons-dashboard"></span> &nbsp; Dashboard</a></li>
                                <li><a href="<?php echo admin_url('admin.php?page=scraper-keywords'); ?>"><span class="dashicons dashicons-tag"></span> &nbsp; Keywords</a></li>
                                <li><a href="<?php echo admin_url('admin.php?page=scraper-competitors'); ?>"><span class="dashicons dashicons-chart-bar"></span> &nbsp; Competitors</a></li>
                            </ul>
                        </div>

                        <div class="sa-nav-section">
                            <h3>DATA</h3>
                            <ul class="sa-nav">
                                <li><a href="<?php echo admin_url('admin.php?page=product-scraper'); ?>"><span class="dashicons dashicons-download"></span> &nbsp; Product Scraper</a></li>
                                <li><a href="#"><span class="dashicons dashicons-media-text"></span> &nbsp; Reports</a></li>
                                <li><a href="#"><span class="dashicons dashicons-admin-generic"></span> &nbsp; Settings</a></li>
                            </ul>
                        </div>

                        <div class="sa-feature-notice">
                            <div class="feature-badge">NEW</div>
                            <p><strong>New features available</strong></p>
                            <p class="feature-desc">Check out the new dashboard view, pages now load faster.</p>
                        </div>

                        <div class="sa-footer">
                            <button class="sa-btn sa-btn-premium">
                                Get Premium
                            </button>
                        </div>
                    </div>

                    <!-- Main Content -->
                    <div class="sa-main-content">
                        <div class="sa-section">
                            <h2>Dashboard</h2>

                            <!-- Stats Grid -->
                            <div class="sa-stats-grid">
                                <div class="sa-stat-card">
                                    <div class="stat-header">
                                        <h3><span class="dashicons dashicons-groups"></span> &nbsp; Organic Traffic</h3>
                                        <span class="stat-change positive">+0.9%</span>
                                    </div>
                                    <div class="stat-main">
                                        <span class="stat-number"><?php echo number_format($stats['organic_traffic']); ?></span>
                                    </div>
                                    <div class="stat-target">
                                        Target: <?php echo number_format($stats['traffic_target']); ?>
                                    </div>
                                </div>

                                <div class="sa-stat-card">
                                    <div class="stat-header">
                                        <h3><span class="dashicons dashicons-external"></span> &nbsp; Referring Domains</h3>
                                        <span class="stat-change positive">+0.9%</span>
                                    </div>
                                    <div class="stat-main">
                                        <span class="stat-number"><?php echo number_format($stats['referring_domains']); ?></span>
                                    </div>
                                    <div class="stat-trend">
                                        S M T W T F S
                                    </div>
                                </div>

                                <div class="sa-stat-card">
                                    <div class="stat-header">
                                        <h3><span class="dashicons dashicons-cart"></span> &nbsp;  Digital Score</h3>
                                    </div>
                                    <div class="stat-main">
                                        <div class="score-circle">
                                            <span class="score"><?php echo $stats['digital_score']; ?>%</span>
                                        </div>
                                    </div>
                                    <div class="score-status">
                                        <span class="status-text">Enough Easy</span>
                                        <button class="sa-btn-link">See Details →</button>
                                    </div>
                                </div>
                            </div>

                            <!-- Search Volume Chart -->
                            <div class="sa-chart-section">
                                <div class="chart-header">
                                    <h3>Search Volume</h3>
                                    <div class="chart-legend">
                                        <span class="legend-item">Current</span>
                                        <span class="legend-item">Previous</span>
                                    </div>
                                </div>
                                <div class="chart-container">
                                    <canvas id="searchVolumeChart" width="400" height="200"></canvas>
                                </div>
                            </div>

                            <!-- Keywords Table -->
                            <div class="sa-table-section">
                                <div class="table-header">
                                    <h3>Top Performing Keywords</h3>
                                    <button class="sa-btn sa-btn-secondary">Export CSV</button>
                                </div>
                                <table class="sa-table">
                                    <thead>
                                        <tr>
                                            <th>No</th>
                                            <th>Keywords</th>
                                            <th>Volume</th>
                                            <th>Traffic</th>
                                            <th>Date</th>
                                        </tr>
                                    </thead>
                                    <tbody id="keywords-table-body">
                                        <!-- Dynamic content will be loaded via AJAX -->
                                    </tbody>
                                </table>
                            </div>

                            <!-- Additional Metrics -->
                            <div class="sa-metrics-grid">
                                <div class="metric-card">
                                    <h4>Visit Duration</h4>
                                    <div class="metric-value">90K <span class="positive">+12%</span></div>
                                </div>
                                <div class="metric-card">
                                    <h4>Page View</h4>
                                    <div class="metric-value">170K <span class="positive">+6%</span></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>        

        <script>
            jQuery(document).ready(function($) {
                loadKeywordsData();
                loadSearchVolumeChart();

                function loadKeywordsData() {
                    $.ajax({
                        url: ajaxurl,
                        type: 'POST',
                        data: {
                            action: 'get_keyword_data',
                            nonce: '<?php echo wp_create_nonce("analytics_nonce"); ?>'
                        },
                        success: function(response) {
                            if (response.success) {
                                displayKeywordsTable(response.data.keywords);
                            }
                        }
                    });
                }

                function displayKeywordsTable(keywords) {
                    let html = '';
                    keywords.forEach((keyword, index) => {
                        html += `
                            <tr>
                                <td><strong>${index + 1}</strong></td>
                                <td>${keyword.phrase}</td>
                                <td>${keyword.volume}</td>
                                <td>${keyword.traffic_share}%</td>
                                <td>${keyword.last_updated}</td>
                            </tr>
                        `;
                    });
                    $('#keywords-table-body').html(html);
                }

                function loadSearchVolumeChart() {
                    // Chart.js implementation for search volume
                    const ctx = document.getElementById('searchVolumeChart').getContext('2d');
                    const chart = new Chart(ctx, {
                        type: 'line',
                        data: {
                            labels: ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'],
                            datasets: [{
                                label: 'Search Volume',
                                data: [12000, 19000, 15000, 25000, 22000, 30000, 28000, 26000, 24000, 22000, 20000, 18000],
                                borderColor: '#4CAF50',
                                tension: 0.4,
                                fill: true,
                                backgroundColor: 'rgba(76, 175, 80, 0.1)'
                            }]
                        },
                        options: {
                            responsive: true,
                            plugins: {
                                legend: {
                                    display: false
                                }
                            },
                            scales: {
                                y: {
                                    beginAtZero: true,
                                    max: 35000
                                }
                            }
                        }
                    });
                }

                window.refreshAnalytics = function() {
                    loadKeywordsData();
                    // Show loading state
                    $('.sa-btn-primary').addClass('loading');
                    setTimeout(() => {
                        $('.sa-btn-primary').removeClass('loading');
                    }, 1000);
                };
            });
        </script>
    <?php
    }

    /**
     * Keyword analysis page
     */
    public function display_keyword_analysis()
    {
    ?>
        <div class="wrap">
            <div class="scraper-analytics-dashboard">
                <div class="sa-header">
                    <div class="sa-brand">
                        <h1><strong>Scraper Analytics</strong></h1>
                        <span class="sa-subtitle">Keyword Analysis</span>
                    </div>
                </div>

                <div class="sa-container">
                    <!-- Sidebar -->
                    <div class="sa-sidebar">
                        <div class="sa-nav-section">
                            <h3>ANALYSIS</h3>
                            <ul class="sa-nav">
                                <li><a href="<?php echo admin_url('admin.php?page=scraper-analytics'); ?>"><span class="dashicons dashicons-dashboard"></span> &nbsp; Dashboard</a></li>
                                <li class="active"><a href="<?php echo admin_url('admin.php?page=scraper-keywords'); ?>"><span class="dashicons dashicons-tag"></span> &nbsp; Keywords</a></li>
                                <li><a href="<?php echo admin_url('admin.php?page=scraper-competitors'); ?>"><span class="dashicons dashicons-chart-bar"></span> &nbsp; Competitors</a></li>
                            </ul>
                        </div>

                        <div class="sa-nav-section">
                            <h3>DATA</h3>
                            <ul class="sa-nav">
                                <li><a href="<?php echo admin_url('admin.php?page=product-scraper'); ?>"><span class="dashicons dashicons-download"></span> &nbsp; Product Scraper</a></li>
                                <li><a href="#"><span class="dashicons dashicons-media-text"></span> &nbsp; Reports</a></li>
                                <li><a href="#"><span class="dashicons dashicons-admin-generic"></span> &nbsp; Settings</a></li>
                            </ul>
                        </div>

                        <div class="sa-feature-notice">
                            <div class="feature-badge">NEW</div>
                            <p><strong>New features available</strong></p>
                            <p class="feature-desc">Check out the new dashboard view, pages now load faster.</p>
                        </div>

                        <div class="sa-footer">
                            <button class="sa-btn sa-btn-premium">
                                Get Premium
                            </button>
                        </div>
                    </div>

                    <div class="sa-main-content">
                        <div class="sa-section">
                            <h2>Keyword Analysis</h2>
                            <div class="sa-stat-card">
                                <h3>Keyword Performance Overview</h3>
                                <p>Detailed keyword analysis and performance metrics will be displayed here.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    <?php
    }

    /**
     * Competitor analysis page
     */
    public function display_competitor_analysis()
    {
    ?>
        <div class="wrap">
            <div class="scraper-analytics-dashboard">
                <div class="sa-header">
                    <div class="sa-brand">
                        <h1><strong>Scraper Analytics</strong></h1>
                        <span class="sa-subtitle">Competitor Analysis</span>
                    </div>
                </div>

                <div class="sa-container">
                    <!-- Sidebar -->
                    <div class="sa-sidebar">
                        <div class="sa-nav-section">
                            <h3>ANALYSIS</h3>
                            <ul class="sa-nav">
                                <li><a href="<?php echo admin_url('admin.php?page=scraper-analytics'); ?>"><span class="dashicons dashicons-dashboard"></span> &nbsp; Dashboard</a></li>
                                <li><a href="<?php echo admin_url('admin.php?page=scraper-keywords'); ?>"><span class="dashicons dashicons-tag"></span> &nbsp; Keywords</a></li>
                                <li class="active"><a href="<?php echo admin_url('admin.php?page=scraper-competitors'); ?>"><span class="dashicons dashicons-chart-bar"></span> &nbsp; Competitors</a></li>
                            </ul>
                        </div>

                        <div class="sa-nav-section">
                            <h3>DATA</h3>
                            <ul class="sa-nav">
                                <li><a href="<?php echo admin_url('admin.php?page=product-scraper'); ?>"><span class="dashicons dashicons-download"></span> &nbsp; Product Scraper</a></li>
                                <li><a href="#"><span class="dashicons dashicons-media-text"></span> &nbsp; Reports</a></li>
                                <li><a href="#"><span class="dashicons dashicons-admin-generic"></span> &nbsp; Settings</a></li>
                            </ul>
                        </div>

                        <div class="sa-feature-notice">
                            <div class="feature-badge">NEW</div>
                            <p><strong>New features available</strong></p>
                            <p class="feature-desc">Check out the new dashboard view, pages now load faster.</p>
                        </div>

                        <div class="sa-footer">
                            <button class="sa-btn sa-btn-premium">
                                Get Premium
                            </button>
                        </div>
                    </div>

                    <div class="sa-main-content">
                        <div class="sa-section">
                            <h2>Competitor Analysis</h2>
                            <div class="sa-stat-card">
                                <h3>Competitor Performance</h3>
                                <p>Competitor analysis and comparison metrics will be displayed here.</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
<?php
    }

    /**
     * Get dashboard statistics
     */
    private function get_dashboard_stats()
    {
        $seo_data = $this->api->get_seo_dashboard_data();
        $plugin = new ProductScraper();
        $scraper_stats = $plugin->storage->get_stats();

        return array(
            'organic_traffic' => $seo_data['organic_traffic']['current'],
            'traffic_target' => $seo_data['organic_traffic']['current'] * 1.4, // 40% growth target
            'referring_domains' => $seo_data['referring_domains']['count'],
            'digital_score' => $seo_data['digital_score'],
            'total_products' => $scraper_stats['total_products'] ?? 0,
            'imported_products' => $scraper_stats['imported_products'] ?? 0,
            'engagement' => $seo_data['engagement_metrics']
        );
    }

    /**
     * AJAX handler for analytics data
     */
    public function ajax_get_analytics()
    {
        if (!wp_verify_nonce($_POST['nonce'], 'analytics_nonce')) {
            wp_die('Security check failed');
        }

        $stats = $this->get_dashboard_stats();
        wp_send_json_success($stats);
    }

    /**
     * AJAX handler for keyword data
     */
    public function ajax_get_keyword_data()
    {
        if (!wp_verify_nonce($_POST['nonce'], 'analytics_nonce')) {
            wp_die('Security check failed');
        }

        $seo_data = $this->api->get_seo_dashboard_data();
        wp_send_json_success(array('keywords' => $seo_data['top_keywords']));
    }

    /**
     * AJAX handler for data sync
     */
    public function ajax_sync_seo_data()
    {
        if (!wp_verify_nonce($_POST['nonce'], 'analytics_nonce')) {
            wp_die('Security check failed');
        }

        $new_data = $this->api->get_seo_dashboard_data();
        wp_send_json_success($new_data);
    }
}
?>