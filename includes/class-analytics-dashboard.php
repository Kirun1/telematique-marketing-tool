<?php

class ProductScraperAnalytics {


	private $api;

	public function __construct() {
		$this->api = new ProductScraper_API_Integrations();

		add_action( 'admin_menu', array( $this, 'add_analytics_menu' ) );
		add_action( 'wp_ajax_get_scraper_analytics', array( $this, 'ajax_get_analytics' ) );
		add_action( 'wp_ajax_get_keyword_data', array( $this, 'ajax_get_keyword_data' ) );
		add_action( 'wp_ajax_sync_seo_data', array( $this, 'ajax_sync_seo_data' ) );

		add_action( 'wp_ajax_test_api_connections', array( $this, 'ajax_test_api_connections' ) );
		add_action( 'wp_ajax_clear_seo_cache', array( $this, 'ajax_clear_seo_cache' ) );

		// Initialize the admin class for the scraper functionality
		$this->admin = new ProductScraperAdmin();
	}

	/**
	 * Add standalone analytics menu with scraper as subpage
	 */
	public function add_analytics_menu() {
		add_menu_page(
			'Scraper Analytics',
			'Scraper Analytics',
			'manage_options',
			'scraper-analytics',
			array( $this, 'display_analytics_dashboard' ),
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
			array( $this, 'display_analytics_dashboard' )
		);

		add_submenu_page(
			'scraper-analytics',
			'Keyword Analysis',
			'Keyword Analysis',
			'manage_options',
			'scraper-keywords',
			array( $this, 'display_keyword_analysis' )
		);

		add_submenu_page(
			'scraper-analytics',
			'Competitor Analysis',
			'Competitor Analysis',
			'manage_options',
			'scraper-competitors',
			array( $this, 'display_competitor_analysis' )
		);

		// Add Product Scraper as a subpage
		add_submenu_page(
			'scraper-analytics',
			'Product Scraper',
			'Product Scraper',
			'manage_options',
			'product-scraper',
			array( $this, 'display_product_scraper' )
		);

		// Add Reports page
		add_submenu_page(
			'scraper-analytics',
			'SEO Reports',
			'Reports',
			'manage_options',
			'scraper-reports',
			array( $this, 'display_reports_page' )
		);

		// Add Settings page
		add_submenu_page(
			'scraper-analytics',
			'SEO Settings',
			'Settings',
			'manage_options',
			'scraper-settings',
			array( $this, 'display_settings_page' )
		);
	}

	/**
	 * Display the product scraper page
	 */
	public function display_product_scraper() {
		// Call the existing admin page from ProductScraperAdmin class
		$this->admin->admin_page();
	}

	/**
	 * Main analytics dashboard
	 */
	public function display_analytics_dashboard() {
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
					<!-- Sidebar -->
					<?php ProductScraper::product_scraper_render_sidebar( 'scraper-analytics' ); ?>

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
										<span class="stat-number"><?php echo number_format( $stats['organic_traffic'] ); ?></span>
									</div>
									<div class="stat-target">
										Target: <?php echo number_format( $stats['traffic_target'] ); ?>
									</div>
								</div>

								<div class="sa-stat-card">
									<div class="stat-header">
										<h3><span class="dashicons dashicons-external"></span> &nbsp; Referring Domains</h3>
										<span class="stat-change positive">+0.9%</span>
									</div>
									<div class="stat-main">
										<span class="stat-number"><?php echo number_format( $stats['referring_domains'] ); ?></span>
									</div>
									<div class="stat-trend">
										S M T W T F S
									</div>
								</div>

								<div class="sa-stat-card">
									<div class="stat-header">
										<h3><span class="dashicons dashicons-cart"></span> &nbsp; Digital Score</h3>
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
							nonce: '<?php echo wp_create_nonce( 'analytics_nonce' ); ?>'
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
	public function display_keyword_analysis() {
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
					<!-- Sidebar -->
					<?php ProductScraper::product_scraper_render_sidebar( 'scraper-keywords' ); ?>

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
	public function display_competitor_analysis() {
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
					<?php ProductScraper::product_scraper_render_sidebar( 'scraper-competitors' ); ?>

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
	private function get_dashboard_stats() {
		$seo_data      = $this->api->get_seo_dashboard_data();
		$plugin        = new ProductScraper();
		$scraper_stats = $plugin->storage->get_stats();

		return array(
			'organic_traffic'   => $seo_data['organic_traffic']['current'],
			'traffic_target'    => $seo_data['organic_traffic']['current'] * 1.4, // 40% growth target
			'referring_domains' => $seo_data['referring_domains']['count'],
			'digital_score'     => $seo_data['digital_score'],
			'total_products'    => $scraper_stats['total_products'] ?? 0,
			'imported_products' => $scraper_stats['imported_products'] ?? 0,
			'engagement'        => $seo_data['engagement_metrics'],
		);
	}

	/**
	 * AJAX handler for analytics data
	 */
	public function ajax_get_analytics() {
		if ( ! wp_verify_nonce( $_POST['nonce'], 'analytics_nonce' ) ) {
			wp_die( 'Security check failed' );
		}

		$stats = $this->get_dashboard_stats();
		wp_send_json_success( $stats );
	}

	/**
	 * AJAX handler for keyword data
	 */
	public function ajax_get_keyword_data() {
		if ( ! wp_verify_nonce( $_POST['nonce'], 'analytics_nonce' ) ) {
			wp_die( 'Security check failed' );
		}

		$seo_data = $this->api->get_seo_dashboard_data();
		wp_send_json_success( array( 'keywords' => $seo_data['top_keywords'] ) );
	}

	/**
	 * AJAX handler for data sync
	 */
	public function ajax_sync_seo_data() {
		if ( ! wp_verify_nonce( $_POST['nonce'], 'analytics_nonce' ) ) {
			wp_die( 'Security check failed' );
		}

		$new_data = $this->api->get_seo_dashboard_data();
		wp_send_json_success( $new_data );
	}

	/**
	 * Display settings page for API configurations
	 */
	public function display_settings_page() {
		// Handle form submissions
		if ( isset( $_POST['submit_settings'] ) && check_admin_referer( 'product_scraper_settings_nonce' ) ) {
			$this->save_settings();
		}

		// Get current settings
		$settings = $this->get_current_settings();
		?>
		<div class="wrap">
			<div class="scraper-analytics-dashboard">
				<div class="sa-header">
					<div class="sa-brand">
						<h1><strong>Scraper Analytics</strong></h1>
						<span class="sa-subtitle">SEO Settings</span>
					</div>
				</div>

				<div class="sa-container">
					<!-- Sidebar -->
					<?php ProductScraper::product_scraper_render_sidebar( 'scraper-settings' ); ?>

					<!-- Main Content -->
					<div class="sa-main-content">
						<div class="sa-section">
							<h2>API Configuration</h2>
							<p class="sa-description">Configure your API keys to enable real data collection from various SEO platforms.</p>

							<form method="post" class="sa-settings-form">
								<?php wp_nonce_field( 'product_scraper_settings_nonce' ); ?>

								<!-- Google Services -->
								<div class="sa-settings-group">
									<h3><span class="dashicons dashicons-google"></span> Google Services</h3>

									<div class="sa-setting-row">
										<label for="ga4_property_id">Google Analytics 4 Property ID</label>
										<input type="text" id="ga4_property_id" name="ga4_property_id"
											value="<?php echo esc_attr( $settings['ga4_property_id'] ); ?>"
											class="sa-form-control"
											placeholder="123456789">
										<p class="description">Enter your <strong>numeric GA4 Property ID</strong> (e.g., 123456789), NOT the Measurement ID that starts with "G-". Find it in Google Analytics under Admin → Property Settings.</p>
									</div>

									<div class="sa-setting-row">
										<label for="google_service_account">Google Service Account JSON</label>
										<textarea id="google_service_account" name="google_service_account"
											class="sa-form-control" rows="6"
											placeholder='Paste your service account JSON credentials'><?php echo esc_textarea( $settings['google_service_account'] ); ?></textarea>
										<p class="description">Service account credentials for Google Analytics API access</p>
									</div>

									<div class="sa-setting-row">
										<label for="pagespeed_api">Google PageSpeed Insights API Key</label>
										<input type="password" id="pagespeed_api" name="pagespeed_api"
											value="<?php echo esc_attr( $settings['pagespeed_api'] ); ?>"
											class="sa-form-control"
											placeholder="AIza...">
										<p class="description">API key for PageSpeed Insights performance data</p>
									</div>
								</div>

								<!-- SEO Platforms -->
								<div class="sa-settings-group">
									<h3><span class="dashicons dashicons-chart-area"></span> SEO Platforms</h3>

									<div class="sa-setting-row">
										<label for="ahrefs_api">Ahrefs API Key</label>
										<input type="password" id="ahrefs_api" name="ahrefs_api"
											value="<?php echo esc_attr( $settings['ahrefs_api'] ); ?>"
											class="sa-form-control"
											placeholder="Ahrefs API key">
										<p class="description">For backlink data and competitor analysis</p>
									</div>

									<div class="sa-setting-row">
										<label for="semrush_api">SEMrush API Key</label>
										<input type="password" id="semrush_api" name="semrush_api"
											value="<?php echo esc_attr( $settings['semrush_api'] ); ?>"
											class="sa-form-control"
											placeholder="SEMrush API key">
										<p class="description">For keyword research and ranking data</p>
									</div>
								</div>

								<!-- Advanced Settings -->
								<div class="sa-settings-group">
									<h3><span class="dashicons dashicons-admin-tools"></span> Advanced Settings</h3>

									<div class="sa-setting-row">
										<label for="cache_duration">Data Cache Duration</label>
										<select id="cache_duration" name="cache_duration" class="sa-form-control">
											<option value="900" <?php selected( $settings['cache_duration'], '900' ); ?>>15 minutes</option>
											<option value="1800" <?php selected( $settings['cache_duration'], '1800' ); ?>>30 minutes</option>
											<option value="3600" <?php selected( $settings['cache_duration'], '3600' ); ?>>1 hour</option>
											<option value="7200" <?php selected( $settings['cache_duration'], '7200' ); ?>>2 hours</option>
											<option value="14400" <?php selected( $settings['cache_duration'], '14400' ); ?>>4 hours</option>
										</select>
										<p class="description">How long to cache API data before refreshing</p>
									</div>

									<div class="sa-setting-row">
										<label>
											<input type="checkbox" name="enable_debug" value="1" <?php checked( $settings['enable_debug'], 1 ); ?>>
											Enable Debug Mode
										</label>
										<p class="description">Log API requests and errors for troubleshooting</p>
									</div>

									<div class="sa-setting-row">
										<label>
											<input type="checkbox" name="auto_sync" value="1" <?php checked( $settings['auto_sync'], 1 ); ?>>
											Auto-sync Data
										</label>
										<p class="description">Automatically refresh data when visiting dashboard</p>
									</div>
								</div>

								<div class="sa-settings-actions">
									<button type="submit" name="submit_settings" class="sa-btn sa-btn-primary">
										<span class="dashicons dashicons-yes-alt"></span>
										Save Settings
									</button>

									<button type="button" id="test_apis" class="sa-btn sa-btn-secondary">
										<span class="dashicons dashicons-admin-tools"></span>
										Test API Connections
									</button>

									<button type="button" id="clear_cache" class="sa-btn sa-btn-warning">
										<span class="dashicons dashicons-trash"></span>
										Clear Cache
									</button>
								</div>
							</form>

							<!-- API Status -->
							<div class="sa-api-status">
								<h3>API Connection Status</h3>
								<div class="sa-status-grid">
									<div class="sa-status-item" data-api="google_analytics">
										<span class="status-icon"></span>
										<span class="status-label">Google Analytics</span>
										<span class="status-message">Not tested</span>
									</div>
									<div class="sa-status-item" data-api="pagespeed">
										<span class="status-icon"></span>
										<span class="status-label">PageSpeed Insights</span>
										<span class="status-message">Not tested</span>
									</div>
									<div class="sa-status-item" data-api="ahrefs">
										<span class="status-icon"></span>
										<span class="status-label">Ahrefs</span>
										<span class="status-message">Not tested</span>
									</div>
									<div class="sa-status-item" data-api="semrush">
										<span class="status-icon"></span>
										<span class="status-label">SEMrush</span>
										<span class="status-message">Not tested</span>
									</div>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>

		<script>
			jQuery(document).ready(function($) {
				// Test API connections
				$('#test_apis').on('click', function() {
					var $button = $(this);
					var originalText = $button.html();

					$button.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> Testing APIs...');

					$('.sa-status-item').each(function() {
						var $item = $(this);
						$item.find('.status-icon').removeClass('success error').addClass('loading');
						$item.find('.status-message').text('Testing...');
					});

					$.ajax({
						url: ajaxurl,
						type: 'POST',
						data: {
							action: 'test_api_connections',
							nonce: '<?php echo wp_create_nonce( 'test_apis_nonce' ); ?>'
						},
						success: function(response) {
							if (response.success) {
								$.each(response.data, function(api, result) {
									var $item = $('.sa-status-item[data-api="' + api + '"]');
									var $icon = $item.find('.status-icon');
									var $message = $item.find('.status-message');

									$icon.removeClass('loading');
									if (result.connected) {
										$icon.addClass('success').html('✓');
										$message.text('Connected').css('color', '#00a32a');
									} else {
										$icon.addClass('error').html('✗');
										$message.text(result.message).css('color', '#d63638');
									}
								});
							}
						},
						complete: function() {
							$button.prop('disabled', false).html(originalText);
						}
					});
				});

				// Clear cache
				$('#clear_cache').on('click', function() {
					var $button = $(this);
					var originalText = $button.html();

					$button.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> Clearing...');

					$.ajax({
						url: ajaxurl,
						type: 'POST',
						data: {
							action: 'clear_seo_cache',
							nonce: '<?php echo wp_create_nonce( 'clear_cache_nonce' ); ?>'
						},
						success: function(response) {
							if (response.success) {
								alert('Cache cleared successfully!');
							} else {
								alert('Error clearing cache.');
							}
						},
						complete: function() {
							$button.prop('disabled', false).html(originalText);
						}
					});
				});
			});
		</script>
		<?php
	}

	/**
	 * Get current settings
	 */
	private function get_current_settings() {
		return array(
			'ga4_property_id'        => get_option( 'product_scraper_ga4_property_id', '' ),
			'google_service_account' => get_option( 'product_scraper_google_service_account', '' ),
			'pagespeed_api'          => get_option( 'product_scraper_pagespeed_api', '' ),
			'ahrefs_api'             => get_option( 'product_scraper_ahrefs_api', '' ),
			'semrush_api'            => get_option( 'product_scraper_semrush_api', '' ),
			'cache_duration'         => get_option( 'product_scraper_cache_duration', '3600' ),
			'enable_debug'           => get_option( 'product_scraper_enable_debug', 0 ),
			'auto_sync'              => get_option( 'product_scraper_auto_sync', 1 ),
		);
	}

	/**
	 * Save settings
	 */
	private function save_settings() {
		// Google Services
		update_option( 'product_scraper_ga4_property_id', sanitize_text_field( $_POST['ga4_property_id'] ) );
		update_option( 'product_scraper_pagespeed_api', sanitize_text_field( $_POST['pagespeed_api'] ) );
		// update_option('product_scraper_google_service_account', sanitize_textarea_field($_POST['google_service_account']));
		if ( isset( $_POST['google_service_account'] ) ) {
			$json = wp_unslash( trim( $_POST['google_service_account'] ) );
			update_option( 'product_scraper_google_service_account', $json );
		}

		// SEO Platforms
		update_option( 'product_scraper_ahrefs_api', sanitize_text_field( $_POST['ahrefs_api'] ) );
		update_option( 'product_scraper_semrush_api', sanitize_text_field( $_POST['semrush_api'] ) );

		// Advanced Settings
		update_option( 'product_scraper_cache_duration', intval( $_POST['cache_duration'] ) );
		update_option( 'product_scraper_enable_debug', isset( $_POST['enable_debug'] ) ? 1 : 0 );
		update_option( 'product_scraper_auto_sync', isset( $_POST['auto_sync'] ) ? 1 : 0 );

		echo '<div class="notice notice-success is-dismissible"><p>Settings saved successfully!</p></div>';
	}

	/**
	 * Display reports page
	 */
	public function display_reports_page() {
		// Get report data
		$reports = $this->get_seo_reports();
		?>
		<div class="wrap">
			<div class="scraper-analytics-dashboard">
				<div class="sa-header">
					<div class="sa-brand">
						<h1><strong>Scraper Analytics</strong></h1>
						<span class="sa-subtitle">SEO Reports</span>
					</div>
					<div class="sa-actions">
						<button class="sa-btn sa-btn-primary" onclick="generatePDFReport()">
							<span class="dashicons dashicons-pdf"></span>
							Export PDF
						</button>
						<button class="sa-btn sa-btn-secondary" onclick="exportCSV()">
							<span class="dashicons dashicons-media-spreadsheet"></span>
							Export CSV
						</button>
					</div>
				</div>

				<div class="sa-container">
					<!-- Sidebar -->
					<?php ProductScraper::product_scraper_render_sidebar( 'scraper-reports' ); ?>

					<!-- Main Content -->
					<div class="sa-main-content">
						<!-- Report Filters -->
						<div class="sa-report-filters">
							<div class="filter-group">
								<label for="report_period">Period:</label>
								<select id="report_period" class="sa-form-control">
									<option value="7">Last 7 days</option>
									<option value="30" selected>Last 30 days</option>
									<option value="90">Last 90 days</option>
									<option value="365">Last 12 months</option>
								</select>
							</div>
							<div class="filter-group">
								<label for="report_type">Report Type:</label>
								<select id="report_type" class="sa-form-control">
									<option value="overview">Overview</option>
									<option value="technical">Technical SEO</option>
									<option value="content">Content Analysis</option>
									<option value="backlinks">Backlink Profile</option>
									<option value="competitors">Competitor Analysis</option>
								</select>
							</div>
							<button class="sa-btn sa-btn-primary" onclick="generateReport()">
								Generate Report
							</button>
						</div>

						<!-- Overview Report -->
						<div class="sa-report-section">
							<h2>SEO Performance Overview</h2>

							<!-- Key Metrics -->
							<div class="sa-metrics-grid">
								<div class="sa-metric-card large">
									<div class="metric-header">
										<h3>Overall SEO Score</h3>
										<span class="metric-trend positive">+5%</span>
									</div>
									<div class="metric-value"><?php echo esc_html( $reports['overall_score'] ); ?>%</div>
									<div class="metric-progress">
										<div class="progress-bar">
											<div class="progress-fill" style="width: <?php echo esc_attr( $reports['overall_score'] ); ?>%"></div>
										</div>
									</div>
								</div>

								<div class="sa-metric-card">
									<h3>Organic Traffic</h3>
									<div class="metric-value"><?php echo number_format( $reports['organic_traffic'] ); ?></div>
									<div class="metric-change <?php echo $reports['traffic_change'] >= 0 ? 'positive' : 'negative'; ?>">
										<?php echo $reports['traffic_change'] >= 0 ? '+' : ''; ?><?php echo esc_html( $reports['traffic_change'] ); ?>%
									</div>
								</div>

								<div class="sa-metric-card">
									<h3>Keyword Rankings</h3>
									<div class="metric-value"><?php echo number_format( $reports['keyword_rankings'] ); ?></div>
									<div class="metric-change positive">+12%</div>
								</div>

								<div class="sa-metric-card">
									<h3>Backlinks</h3>
									<div class="metric-value"><?php echo number_format( $reports['backlinks'] ); ?></div>
									<div class="metric-change positive">+8%</div>
								</div>
							</div>

							<!-- Technical SEO Health -->
							<div class="sa-report-card">
								<h3>Technical SEO Health</h3>
								<div class="health-metrics">
									<?php foreach ( $reports['technical_health'] as $metric ) : ?>
										<div class="health-metric">
											<span class="metric-label"><?php echo esc_html( $metric['label'] ); ?></span>
											<div class="metric-score">
												<span class="score"><?php echo esc_html( $metric['score'] ); ?>%</span>
												<div class="score-bar">
													<div class="score-fill <?php echo $metric['status']; ?>" style="width: <?php echo esc_attr( $metric['score'] ); ?>%"></div>
												</div>
											</div>
										</div>
									<?php endforeach; ?>
								</div>
							</div>

							<!-- Top Performing Content -->
							<div class="sa-report-card">
								<h3>Top Performing Content</h3>
								<div class="content-list">
									<?php foreach ( $reports['top_content'] as $content ) : ?>
										<div class="content-item">
											<div class="content-title">
												<a href="<?php echo esc_url( $content['url'] ); ?>" target="_blank">
													<?php echo esc_html( $content['title'] ); ?>
												</a>
											</div>
											<div class="content-metrics">
												<span class="metric">Traffic: <?php echo number_format( $content['traffic'] ); ?></span>
												<span class="metric">Keywords: <?php echo number_format( $content['keywords'] ); ?></span>
												<span class="metric">Backlinks: <?php echo number_format( $content['backlinks'] ); ?></span>
											</div>
										</div>
									<?php endforeach; ?>
								</div>
							</div>

							<!-- Competitor Comparison -->
							<div class="sa-report-card">
								<h3>Competitor Comparison</h3>
								<div class="competitor-table">
									<table class="sa-table">
										<thead>
											<tr>
												<th>Domain</th>
												<th>Domain Authority</th>
												<th>Referring Domains</th>
												<th>Organic Traffic</th>
												<th>Top Keywords</th>
											</tr>
										</thead>
										<tbody>
											<?php foreach ( $reports['competitors'] as $competitor ) : ?>
												<tr>
													<td><?php echo esc_html( $competitor['domain'] ); ?></td>
													<td><?php echo esc_html( $competitor['authority'] ); ?></td>
													<td><?php echo number_format( $competitor['ref_domains'] ); ?></td>
													<td><?php echo number_format( $competitor['traffic'] ); ?></td>
													<td><?php echo number_format( $competitor['keywords'] ); ?></td>
												</tr>
											<?php endforeach; ?>
										</tbody>
									</table>
								</div>
							</div>

							<!-- Recommendations -->
							<div class="sa-report-card">
								<h3>SEO Recommendations</h3>
								<div class="recommendations-list">
									<?php foreach ( $reports['recommendations'] as $rec ) : ?>
										<div class="recommendation-item priority-<?php echo esc_attr( $rec['priority'] ); ?>">
											<span class="rec-icon">⚡</span>
											<div class="rec-content">
												<h4><?php echo esc_html( $rec['title'] ); ?></h4>
												<p><?php echo esc_html( $rec['description'] ); ?></p>
												<span class="rec-impact">Impact: <?php echo esc_html( $rec['impact'] ); ?></span>
											</div>
										</div>
									<?php endforeach; ?>
								</div>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>

		<script>
			function generateReport() {
				var period = jQuery('#report_period').val();
				var type = jQuery('#report_type').val();

				// Show loading state
				jQuery('.sa-main-content').addClass('loading');

				// In a real implementation, you would make an AJAX call here
				setTimeout(function() {
					jQuery('.sa-main-content').removeClass('loading');
					alert('Report generated for ' + period + ' days, type: ' + type);
				}, 1000);
			}

			function generatePDFReport() {
				alert('PDF export feature would be implemented here');
				// Implement PDF.js or server-side PDF generation
			}

			function exportCSV() {
				alert('CSV export feature would be implemented here');
				// Implement CSV generation and download
			}
		</script>
		<?php
	}

	/**
	 * Get SEO reports data
	 */
	private function get_seo_reports() {
		// This would typically fetch real data from your API integrations
		// For now, returning sample data structure

		return array(
			'overall_score'    => 76,
			'organic_traffic'  => 32450,
			'traffic_change'   => 8.7,
			'keyword_rankings' => 1245,
			'backlinks'        => 956,
			'technical_health' => array(
				array(
					'label'  => 'Page Speed',
					'score'  => 82,
					'status' => 'good',
				),
				array(
					'label'  => 'Mobile Friendly',
					'score'  => 95,
					'status' => 'excellent',
				),
				array(
					'label'  => 'SSL Security',
					'score'  => 100,
					'status' => 'excellent',
				),
				array(
					'label'  => 'Structured Data',
					'score'  => 65,
					'status' => 'average',
				),
				array(
					'label'  => 'Internal Linking',
					'score'  => 58,
					'status' => 'needs-improvement',
				),
			),
			'top_content'      => array(
				array(
					'title'     => 'Best Product Review 2024',
					'url'       => get_site_url() . '/best-product-review-2024',
					'traffic'   => 8450,
					'keywords'  => 45,
					'backlinks' => 23,
				),
				array(
					'title'     => 'How to Use Advanced Features',
					'url'       => get_site_url() . '/how-to-use-advanced-features',
					'traffic'   => 6230,
					'keywords'  => 32,
					'backlinks' => 18,
				),
			),
			'competitors'      => array(
				array(
					'domain'      => 'competitor1.com',
					'authority'   => 72,
					'ref_domains' => 1250,
					'traffic'     => 45000,
					'keywords'    => 2100,
				),
				array(
					'domain'      => 'competitor2.com',
					'authority'   => 65,
					'ref_domains' => 890,
					'traffic'     => 32000,
					'keywords'    => 1650,
				),
			),
			'recommendations'  => array(
				array(
					'title'       => 'Improve Page Load Speed',
					'description' => 'Optimize images and leverage browser caching to improve page load times.',
					'priority'    => 'high',
					'impact'      => 'High',
				),
				array(
					'title'       => 'Add Schema Markup',
					'description' => 'Implement structured data to enhance search result appearances.',
					'priority'    => 'medium',
					'impact'      => 'Medium',
				),
				array(
					'title'       => 'Build Quality Backlinks',
					'description' => 'Focus on acquiring backlinks from authoritative websites in your niche.',
					'priority'    => 'high',
					'impact'      => 'High',
				),
			),
		);
	}

	/**
	 * AJAX handler for testing API connections
	 */
	public function ajax_test_api_connections() {
		if ( ! wp_verify_nonce( $_POST['nonce'], 'test_apis_nonce' ) ) {
			wp_send_json_error( 'Security check failed' );
		}

		$results          = array();
		$api_integrations = new ProductScraper_API_Integrations();

		// Test Google Analytics
		try {
			$ga_data = $api_integrations->get_organic_traffic();
			error_log( '$ga_data: ' . print_r( $ga_data, true ) );
			$results['google_analytics'] = array(
				'connected' => $ga_data['source'] === 'google_analytics',
				'message'   => $ga_data['source'] === 'google_analytics' ? 'Connected successfully' : 'No data received',
			);
		} catch ( Exception $e ) {
			$results['google_analytics'] = array(
				'connected' => false,
				'message'   => $e->getMessage(),
			);
		}

		// Test PageSpeed Insights
		try {
			$health_data          = $api_integrations->get_site_health_metrics();
			$results['pagespeed'] = array(
				'connected' => $health_data['source'] === 'pagespeed_api',
				'message'   => $health_data['source'] === 'pagespeed_api' ? 'Connected successfully' : 'No data received',
			);
		} catch ( Exception $e ) {
			$results['pagespeed'] = array(
				'connected' => false,
				'message'   => $e->getMessage(),
			);
		}

		// Test Ahrefs
		try {
			$ahrefs_data       = $api_integrations->get_referring_domains();
			$results['ahrefs'] = array(
				'connected' => $ahrefs_data['source'] === 'ahrefs_api',
				'message'   => $ahrefs_data['source'] === 'ahrefs_api' ? 'Connected successfully' : 'No data received',
			);
		} catch ( Exception $e ) {
			$results['ahrefs'] = array(
				'connected' => false,
				'message'   => $e->getMessage(),
			);
		}

		wp_send_json_success( $results );
	}

	/**
	 * AJAX handler for clearing cache
	 */
	public function ajax_clear_seo_cache() {
		if ( ! wp_verify_nonce( $_POST['nonce'], 'clear_cache_nonce' ) ) {
			wp_send_json_error( 'Security check failed' );
		}

		global $wpdb;

		// Clear all plugin transients
		$wpdb->query(
			"DELETE FROM {$wpdb->options} 
            WHERE option_name LIKE '_transient_product_scraper_%' 
            OR option_name LIKE '_transient_timeout_product_scraper_%'"
		);

		wp_send_json_success( 'Cache cleared successfully' );
	}
}
?>
