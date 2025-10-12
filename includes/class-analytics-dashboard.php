<?php

class ProductScraperAnalytics
{

    public function __construct()
    {
        add_action('admin_menu', array($this, 'add_analytics_menu'));
        add_action('wp_ajax_get_scraper_analytics', array($this, 'ajax_get_analytics'));
        add_action('wp_ajax_get_keyword_data', array($this, 'ajax_get_keyword_data'));
    }

    /**
     * Add standalone analytics menu
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
                <!-- LEGO-style Header -->
                <div class="lego-header">
                    <div class="lego-brand">
                        <h1>🚀 <strong>Scraper Analytics</strong></h1>
                        <span class="lego-subtitle">Dashboard</span>
                    </div>
                    <div class="lego-actions">
                        <button class="lego-btn lego-btn-primary" onclick="refreshAnalytics()">
                            <span class="dashicons dashicons-update"></span>
                            Refresh Data
                        </button>
                        <div class="lego-user">
                            <span class="user-name">Steven Smith</span>
                            <span class="user-email">smith@gmail.com</span>
                        </div>
                    </div>
                </div>

                <div class="lego-container">
                    <!-- Left Sidebar -->
                    <div class="lego-sidebar">
                        <div class="lego-nav-section">
                            <h3>ANALYSIS</h3>
                            <ul class="lego-nav">
                                <li class="active"><a href="<?php echo admin_url('admin.php?page=scraper-analytics'); ?>">📊 Dashboard</a></li>
                                <li><a href="<?php echo admin_url('admin.php?page=scraper-keywords'); ?>">🔑 Key Words</a></li>
                                <li><a href="<?php echo admin_url('admin.php?page=scraper-competitors'); ?>">👥 Competitors</a></li>
                            </ul>
                        </div>

                        <div class="lego-nav-section">
                            <h3>DATA</h3>
                            <ul class="lego-nav">
                                <li><a href="<?php echo admin_url('options-general.php?page=product-scraper'); ?>">🛠️ Scraper</a></li>
                                <li><a href="#">📊 Reports</a></li>
                                <li><a href="#">⚙️ Settings</a></li>
                            </ul>
                        </div>

                        <div class="lego-feature-notice">
                            <div class="feature-badge">NEW</div>
                            <p><strong>New features available</strong></p>
                            <p class="feature-desc">Check out the new dashboard view, pages now load faster.</p>
                        </div>

                        <div class="lego-footer">
                            <button class="lego-btn lego-btn-premium">
                                ⭐ Get Premium
                            </button>
                            <div class="user-details">
                                <strong>Steven Smith</strong>
                                <span>smith@gmail.com</span>
                            </div>
                        </div>
                    </div>

                    <!-- Main Content -->
                    <div class="lego-main-content">
                        <div class="lego-section">
                            <h2>Dashboard</h2>

                            <!-- Stats Grid -->
                            <div class="lego-stats-grid">
                                <div class="lego-stat-card">
                                    <div class="stat-header">
                                        <h3>Organic Traffic</h3>
                                        <span class="stat-change positive">+0.9%</span>
                                    </div>
                                    <div class="stat-main">
                                        <span class="stat-number"><?php echo number_format($stats['organic_traffic']); ?></span>
                                    </div>
                                    <div class="stat-target">
                                        Target: <?php echo number_format($stats['traffic_target']); ?>
                                    </div>
                                </div>

                                <div class="lego-stat-card">
                                    <div class="stat-header">
                                        <h3>Referring Domains</h3>
                                        <span class="stat-change positive">+0.9%</span>
                                    </div>
                                    <div class="stat-main">
                                        <span class="stat-number"><?php echo number_format($stats['referring_domains']); ?></span>
                                    </div>
                                    <div class="stat-trend">
                                        S M T W T F S
                                    </div>
                                </div>

                                <div class="lego-stat-card">
                                    <div class="stat-header">
                                        <h3>Digital Score</h3>
                                    </div>
                                    <div class="stat-main">
                                        <div class="score-circle">
                                            <span class="score"><?php echo $stats['digital_score']; ?>%</span>
                                        </div>
                                    </div>
                                    <div class="score-status">
                                        <span class="status-text">Enough Easy</span>
                                        <button class="lego-btn-link">See Details →</button>
                                    </div>
                                </div>
                            </div>

                            <!-- Search Volume Chart -->
                            <div class="lego-chart-section">
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
                            <div class="lego-table-section">
                                <div class="table-header">
                                    <h3>Top Performing Keywords</h3>
                                    <button class="lego-btn lego-btn-secondary">Export CSV</button>
                                </div>
                                <table class="lego-table">
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
                            <div class="lego-metrics-grid">
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

        <style>
            <?php include_once PRODUCT_SCRAPER_PLUGIN_PATH . 'assets/lego-analytics.css'; ?>
        </style>

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
                    $('.lego-btn-primary').addClass('loading');
                    setTimeout(() => {
                        $('.lego-btn-primary').removeClass('loading');
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
                <!-- Similar LEGO-style structure for keyword analysis -->
                <div class="lego-header">
                    <div class="lego-brand">
                        <h1>🚀 <strong>Scraper Analytics</strong></h1>
                        <span class="lego-subtitle">Keyword Analysis</span>
                    </div>
                </div>

                <div class="lego-container">
                    <!-- Sidebar (same as dashboard) -->
                    <div class="lego-sidebar">
                        <!-- Same sidebar navigation -->
                    </div>

                    <div class="lego-main-content">
                        <div class="lego-section">
                            <h2>Keyword Analysis</h2>
                            <!-- Keyword-specific content here -->
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
                <!-- Similar LEGO-style structure for competitor analysis -->
                <div class="lego-header">
                    <div class="lego-brand">
                        <h1>🚀 <strong>Scraper Analytics</strong></h1>
                        <span class="lego-subtitle">Competitor Analysis</span>
                    </div>
                </div>

                <div class="lego-container">
                    <!-- Sidebar (same as dashboard) -->
                    <div class="lego-sidebar">
                        <!-- Same sidebar navigation -->
                    </div>

                    <div class="lego-main-content">
                        <div class="lego-section">
                            <h2>Competitor Analysis</h2>
                            <!-- Competitor-specific content here -->
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
        $plugin = new ProductScraper();
        $scraper_stats = $plugin->storage->get_stats();

        return array(
            'organic_traffic' => 32000,
            'traffic_target' => 140000,
            'referring_domains' => 95000,
            'digital_score' => 76,
            'total_products' => $scraper_stats['total_products'] ?? 0,
            'imported_products' => $scraper_stats['imported_products'] ?? 0
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

        $keywords = array(
            array(
                'phrase' => 'UI & Graphic Design Tips',
                'volume' => '190k',
                'traffic_share' => 25,
                'last_updated' => '30 July'
            ),
            array(
                'phrase' => 'UK Design & Research Tips',
                'volume' => '200k',
                'traffic_share' => 23,
                'last_updated' => '28 July'
            ),
            array(
                'phrase' => 'Figma Tutorial - Components',
                'volume' => '195k',
                'traffic_share' => 12,
                'last_updated' => '25 July'
            ),
            array(
                'phrase' => 'Dashboard Design Tips',
                'volume' => '222k',
                'traffic_share' => 12,
                'last_updated' => '25 July'
            )
        );

        wp_send_json_success(array('keywords' => $keywords));
    }
}
?>