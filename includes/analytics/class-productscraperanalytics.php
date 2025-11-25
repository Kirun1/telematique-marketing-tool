<?php
/**
 * Analytics Dashboard class for Product Scraper
 *
 * @package    Product_Scraper
 * @subpackage Analytics
 * @author     Your Name
 * @since      1.0.0
 */

/**
 * Handles analytics dashboard functionality for Product Scraper
 *
 * This class manages the analytics dashboard, including menu creation,
 * data visualization, and API integrations for SEO data.
 */
class ProductScraperAnalytics {

	/**
	 * API integrations instance
	 *
	 * @var ProductScraper_API_Integrations
	 */
	private $api;

	/**
	 * Admin functionality instance
	 *
	 * @var ProductScraperAdmin
	 */
	private $admin;

	/**
	 * Constructor - initializes the analytics dashboard
	 *
	 * Sets up API connections and registers admin hooks for the analytics interface.
	 */
	public function __construct() {
		$this->api = new ProductScraper_API_Integrations();

		add_action( 'admin_menu', array( $this, 'add_analytics_menu' ) );
		add_action( 'wp_ajax_get_scraper_analytics', array( $this, 'ajax_get_analytics' ) );
		add_action( 'wp_ajax_get_keyword_data', array( $this, 'ajax_get_keyword_data' ) );
		add_action( 'wp_ajax_sync_seo_data', array( $this, 'ajax_sync_seo_data' ) );

		add_action( 'wp_ajax_test_api_connections', array( $this, 'ajax_test_api_connections' ) );
		add_action( 'wp_ajax_clear_seo_cache', array( $this, 'ajax_clear_seo_cache' ) );

		// Initialize historical data if needed
		add_action( 'init', array( $this, 'initialize_historical_data' ) );

		// Initialize the admin class for the scraper functionality.
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

		// Add submenus for different sections.
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

		// Add Product Scraper as a subpage.
		add_submenu_page(
			'scraper-analytics',
			'Product Scraper',
			'Product Scraper',
			'manage_options',
			'product-scraper',
			array( $this, 'display_product_scraper' )
		);

		// Add Reports page.
		add_submenu_page(
			'scraper-analytics',
			'SEO Reports',
			'Reports',
			'manage_options',
			'scraper-reports',
			array( $this, 'display_reports_page' )
		);

		// Add Settings page.
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
		// Call the existing admin page from ProductScraperAdmin class.
		$this->admin->admin_page();
	}

	/**
	 * Initialize historical data for existing installations
	 */
	public function initialize_historical_data() {
		$historical_key  = 'product_scraper_historical_data';
		$historical_data = get_option( $historical_key, array() );

		// If no historical data exists, create some sample data for the past 7 days
		if ( empty( $historical_data ) ) {
			$seo_data     = $this->api->get_seo_dashboard_data();
			$current_time = current_time( 'timestamp' );

			for ( $i = 6; $i >= 0; $i-- ) {
				$date          = date( 'Y-m-d', strtotime( "-$i days", $current_time ) );
				$random_factor = 0.8 + ( mt_rand( 0, 40 ) / 100 ); // Random factor between 0.8 and 1.2

				$historical_data[ $date ] = array(
					'timestamp'         => strtotime( $date ),
					'organic_traffic'   => round( $seo_data['organic_traffic']['current'] * $random_factor ),
					'referring_domains' => round( $seo_data['referring_domains']['count'] * $random_factor ),
					'digital_score'     => max( 0, min( 100, round( $seo_data['digital_score'] * ( 0.95 + ( mt_rand( 0, 10 ) / 100 ) ) ) ) ),
					'engagement'        => array(
						'visit_duration' => round( $seo_data['engagement_metrics']['visit_duration'] * $random_factor ),
						'page_views'     => round( $seo_data['engagement_metrics']['page_views'] * $random_factor ),
						'bounce_rate'    => max( 0, min( 100, round( $seo_data['engagement_metrics']['bounce_rate'] * ( 0.95 + ( mt_rand( 0, 10 ) / 100 ) ) ) ) ),
					),
				);
			}

			update_option( $historical_key, $historical_data, false );
		}
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
									<?php echo $this->format_percentage_change( $stats['organic_traffic_change'] ); ?>
								</div>
								<div class="stat-main">
									<span class="stat-number"><?php echo number_format( $stats['organic_traffic'] ); ?></span>
								</div>
								<div class="stat-target">
									Target: <?php echo number_format( $stats['traffic_target'] ); ?>
									<div class="target-progress">
										<?php
										$progress = $stats['traffic_target'] > 0 ?
											min( 100, ( $stats['organic_traffic'] / $stats['traffic_target'] ) * 100 ) : 0;
										?>
										<div class="progress-bar">
											<div class="progress-fill" style="width: <?php echo esc_attr( $progress ); ?>%"></div>
										</div>
										<span class="progress-text"><?php echo round( $progress ); ?>% to target</span>
									</div>
								</div>
							</div>

							<div class="sa-stat-card">
								<div class="stat-header">
									<h3><span class="dashicons dashicons-external"></span> &nbsp; Referring Domains</h3>
									<?php echo $this->format_percentage_change( $stats['referring_domains_change'] ); ?>
								</div>
								<div class="stat-main">
									<span class="stat-number"><?php echo number_format( $stats['referring_domains'] ); ?></span>
								</div>
								<?php echo $this->generate_weekly_trend_html( $stats['weekly_trend'] ); ?>
							</div>

							<div class="sa-stat-card">
								<div class="stat-header">
									<h3><span class="dashicons dashicons-cart"></span> &nbsp; Digital Score</h3>
									<?php echo $this->format_percentage_change( $stats['digital_score_change'] ); ?>
								</div>
								<div class="stat-main">
									<div class="score-circle" style="--score: <?php echo esc_attr( $stats['digital_score'] ); ?>%">
										<span class="score"><?php echo esc_html( $stats['digital_score'] ); ?>%</span>
									</div>
								</div>
								<div class="score-status">
									<span class="status-text"><?php echo $this->get_score_status( $stats['digital_score'] ); ?></span>
									<button class="sa-btn-link">See Details →</button>
								</div>
							</div>
						</div>

						<!-- Additional engagement metrics -->
						<div class="sa-stats-grid" style="margin-top: 20px;">
							<div class="sa-stat-card">
								<div class="stat-header">
									<h3><span class="dashicons dashicons-clock"></span> &nbsp; Avg. Visit Duration</h3>
									<?php
									$duration_change = $stats['engagement']['visit_duration_change'] ?? 0;
									echo $this->format_percentage_change( $duration_change );
									?>
								</div>
								<div class="stat-main">
									<span class="stat-number">
										<?php
										$duration = $stats['engagement']['visit_duration'] ?? 0;
										echo $this->format_duration( $duration );
										?>
									</span>
								</div>
								<div class="stat-subtitle">per session</div>
							</div>

							<div class="sa-stat-card">
								<div class="stat-header">
									<h3><span class="dashicons dashicons-visibility"></span> &nbsp; Page Views</h3>
									<?php
									$pageviews_change = $stats['engagement']['page_views_change'] ?? 0;
									echo $this->format_percentage_change( $pageviews_change );
									?>
								</div>
								<div class="stat-main">
									<span class="stat-number">
										<?php
										$pageviews = $stats['engagement']['page_views'] ?? 0;
										echo $this->format_large_number( $pageviews );
										?>
									</span>
								</div>
								<div class="stat-subtitle">total views</div>
							</div>

							<div class="sa-stat-card">
								<div class="stat-header">
									<h3><span class="dashicons dashicons-chart-bar"></span> &nbsp; Bounce Rate</h3>
									<?php
									$bounce_rate   = $stats['engagement']['bounce_rate'] ?? 0;
									$bounce_change = $stats['engagement']['bounce_rate_change'] ?? 0;
									// For bounce rate, negative change is good
									$bounce_display_change = -$bounce_change;
									echo $this->format_percentage_change( $bounce_display_change );
									?>
								</div>
								<div class="stat-main">
									<span class="stat-number <?php echo $bounce_rate < 40 ? 'positive' : ( $bounce_rate < 70 ? 'neutral' : 'negative' ); ?>">
										<?php echo number_format( $bounce_rate, 1 ); ?>%
									</span>
								</div>
								<div class="stat-subtitle">lower is better</div>
							</div>
						</div>

						<!-- CHARTS SECTION -->
						<div class="sa-charts-grid">
							<!-- Traffic Chart -->
							<div class="sa-chart-card">
								<div class="chart-header">
									<h3>Organic Traffic Trend</h3>
									<div class="chart-actions">
										<select id="traffic-period" class="chart-period-selector">
											<option value="7d">7 Days</option>
											<option value="30d" selected>30 Days</option>
											<option value="90d">90 Days</option>
										</select>
									</div>
								</div>
								<div class="chart-container">
									<canvas id="trafficTrendChart" height="250"></canvas>
								</div>
							</div>

							<!-- Keyword Performance -->
							<div class="sa-chart-card">
								<div class="chart-header">
									<h3>Top Performing Keywords</h3>
								</div>
								<div class="chart-container">
									<canvas id="keywordPerformanceChart" height="250"></canvas>
								</div>
							</div>

							<!-- Competitor Comparison -->
							<div class="sa-chart-card">
								<div class="chart-header">
									<h3>Competitor Analysis</h3>
								</div>
								<div class="chart-container">
									<canvas id="competitorRadarChart" height="250"></canvas>
								</div>
							</div>

							<!-- SEO Health Score -->
							<div class="sa-chart-card">
								<div class="chart-header">
									<h3>SEO Health Score</h3>
								</div>
								<div class="chart-container">
									<canvas id="seoHealthGauge" height="250"></canvas>
								</div>
							</div>
						</div>
					</div>

						<script>
							jQuery(document).ready(function($) {
								// Initialize all charts
								ProductScraperCharts.init({
									ajaxurl: '<?php echo esc_js( admin_url( 'admin-ajax.php' ) ); ?>',
									nonce: '<?php echo esc_js( wp_create_nonce( 'product_scraper_charts' ) ); ?>'
								});
							});
						</script>

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
							nonce: '<?php echo esc_js( wp_create_nonce( 'analytics_nonce' ) ); ?>'
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
					// Chart.js implementation for search volume.
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
					// Show loading state.
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
	 * Calculate digital score trend from real historical data
	 */
	private function calculate_score_trend( $current_score ) {
		$historical_trends = $this->calculate_historical_trends();
		return $historical_trends['digital_score_change'];
	}

	private function get_score_status( $score ) {
		if ( $score >= 80 ) {
			return 'Excellent';
		}
		if ( $score >= 60 ) {
			return 'Good';
		}
		if ( $score >= 40 ) {
			return 'Fair';
		}
		return 'Needs Improvement';
	}

	private function format_duration( $seconds ) {
		if ( $seconds < 60 ) {
			return round( $seconds ) . 's';
		}
		$minutes           = floor( $seconds / 60 );
		$remaining_seconds = $seconds % 60;
		return $minutes . 'm ' . round( $remaining_seconds ) . 's';
	}

	private function format_large_number( $number ) {
		if ( $number >= 1000000 ) {
			return round( $number / 1000000, 1 ) . 'M';
		}
		if ( $number >= 1000 ) {
			return round( $number / 1000, 1 ) . 'K';
		}
		return number_format( $number );
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
	 * Get dashboard statistics with real historical trends
	 */
	private function get_dashboard_stats() {
		$seo_data      = $this->api->get_seo_dashboard_data();
		$plugin        = new ProductScraper();
		$scraper_stats = $plugin->storage->get_stats();

		// Store current data for historical tracking
		$this->store_historical_data( $seo_data );

		// Calculate real trends from historical data
		$historical_trends = $this->calculate_historical_trends();

		return array(
			'organic_traffic'          => $seo_data['organic_traffic']['current'],
			'traffic_target'           => $seo_data['organic_traffic']['current'] * 1.4,
			'organic_traffic_change'   => $historical_trends['organic_traffic_change'],
			'organic_traffic_trend'    => $historical_trends['organic_traffic_trend'],
			'referring_domains'        => $seo_data['referring_domains']['count'],
			'referring_domains_change' => $historical_trends['referring_domains_change'],
			'referring_domains_trend'  => $historical_trends['referring_domains_trend'],
			'digital_score'            => $seo_data['digital_score'],
			'digital_score_change'     => $historical_trends['digital_score_change'],
			'digital_score_trend'      => $historical_trends['digital_score_trend'],
			'weekly_trend'             => $this->generate_weekly_trend_data( $seo_data['referring_domains']['count'] ),
			'total_products'           => $scraper_stats['total_products'] ?? 0,
			'imported_products'        => $scraper_stats['imported_products'] ?? 0,
			'engagement'               => $this->get_engagement_with_trends( $seo_data['engagement_metrics'] ),
		);
	}

	/**
	 * Store current data for historical trend analysis
	 */
	private function store_historical_data( $current_data ) {
		$historical_key  = 'product_scraper_historical_data';
		$historical_data = get_option( $historical_key, array() );

		$current_timestamp = current_time( 'timestamp' );
		$today             = date( 'Y-m-d', $current_timestamp );

		// Only store one record per day to avoid bloating the database
		if ( isset( $historical_data[ $today ] ) ) {
			return; // Already stored today's data
		}

		$daily_data = array(
			'timestamp'         => $current_timestamp,
			'organic_traffic'   => $current_data['organic_traffic']['current'],
			'referring_domains' => $current_data['referring_domains']['count'],
			'digital_score'     => $current_data['digital_score'],
			'engagement'        => $current_data['engagement_metrics'],
		);

		// Store today's data
		$historical_data[ $today ] = $daily_data;

		// Keep only last 90 days of data to prevent database bloat
		$historical_data = array_slice( $historical_data, -90, 90, true );

		update_option( $historical_key, $historical_data, false );
	}

	/**
	 * Calculate real trends from historical data
	 */
	private function calculate_historical_trends() {
		$historical_key  = 'product_scraper_historical_data';
		$historical_data = get_option( $historical_key, array() );

		if ( count( $historical_data ) < 2 ) {
			// Not enough data for trend calculation
			return array(
				'organic_traffic_change'   => 0,
				'organic_traffic_trend'    => 'neutral',
				'referring_domains_change' => 0,
				'referring_domains_trend'  => 'neutral',
				'digital_score_change'     => 0,
				'digital_score_trend'      => 'neutral',
			);
		}

		// Sort by date (newest first)
		krsort( $historical_data );
		$historical_array = array_values( $historical_data );

		// Current period (last 7 days)
		$current_period = array_slice( $historical_array, 0, min( 7, count( $historical_array ) ) );

		// Previous period (7 days before current period)
		$previous_period = array_slice( $historical_array, 7, min( 7, count( $historical_array ) - 7 ) );

		if ( empty( $previous_period ) ) {
			// Not enough data for comparison
			return array(
				'organic_traffic_change'   => 0,
				'organic_traffic_trend'    => 'neutral',
				'referring_domains_change' => 0,
				'referring_domains_trend'  => 'neutral',
				'digital_score_change'     => 0,
				'digital_score_trend'      => 'neutral',
			);
		}

		// Calculate averages for both periods
		$current_traffic_avg  = $this->calculate_average( $current_period, 'organic_traffic' );
		$previous_traffic_avg = $this->calculate_average( $previous_period, 'organic_traffic' );

		$current_domains_avg  = $this->calculate_average( $current_period, 'referring_domains' );
		$previous_domains_avg = $this->calculate_average( $previous_period, 'referring_domains' );

		$current_score_avg  = $this->calculate_average( $current_period, 'digital_score' );
		$previous_score_avg = $this->calculate_average( $previous_period, 'digital_score' );

		// Calculate percentage changes
		$traffic_change = $this->calculate_percentage_change( $previous_traffic_avg, $current_traffic_avg );
		$domains_change = $this->calculate_percentage_change( $previous_domains_avg, $current_domains_avg );
		$score_change   = $this->calculate_percentage_change( $previous_score_avg, $current_score_avg );

		return array(
			'organic_traffic_change'   => $traffic_change,
			'organic_traffic_trend'    => $this->determine_trend_direction( $traffic_change ),
			'referring_domains_change' => $domains_change,
			'referring_domains_trend'  => $this->determine_trend_direction( $domains_change ),
			'digital_score_change'     => $score_change,
			'digital_score_trend'      => $this->determine_trend_direction( $score_change ),
		);
	}

	/**
	 * Calculate average from historical data
	 */
	private function calculate_average( $data, $metric ) {
		if ( empty( $data ) ) {
			return 0;
		}

		$sum   = 0;
		$count = 0;

		foreach ( $data as $record ) {
			if ( isset( $record[ $metric ] ) ) {
				$sum += floatval( $record[ $metric ] );
				++$count;
			}
		}

		return $count > 0 ? $sum / $count : 0;
	}

	/**
	 * Calculate percentage change between two values
	 */
	private function calculate_percentage_change( $old_value, $new_value ) {
		if ( $old_value == 0 ) {
			return $new_value > 0 ? 100 : 0; // Handle division by zero
		}

		$change = ( ( $new_value - $old_value ) / abs( $old_value ) ) * 100;
		return round( $change, 1 );
	}

	/**
	 * Determine trend direction based on percentage change
	 */
	private function determine_trend_direction( $change ) {
		if ( $change > 2.0 ) {
			return 'positive';
		} elseif ( $change < -2.0 ) {
			return 'negative';
		} else {
			return 'neutral';
		}
	}

	/**
	 * Get engagement metrics with trends
	 */
	private function get_engagement_with_trends( $current_engagement ) {
		$historical_key  = 'product_scraper_historical_data';
		$historical_data = get_option( $historical_key, array() );

		if ( count( $historical_data ) < 2 ) {
			// Return current data without trends
			return array_merge(
				$current_engagement,
				array(
					'visit_duration_change' => 0,
					'page_views_change'     => 0,
					'bounce_rate_change'    => 0,
				)
			);
		}

		// Sort by date (newest first)
		krsort( $historical_data );
		$historical_array = array_values( $historical_data );

		// Current period (last 7 days)
		$current_period  = array_slice( $historical_array, 0, min( 7, count( $historical_array ) ) );
		$previous_period = array_slice( $historical_array, 7, min( 7, count( $historical_array ) - 7 ) );

		if ( empty( $previous_period ) ) {
			return array_merge(
				$current_engagement,
				array(
					'visit_duration_change' => 0,
					'page_views_change'     => 0,
					'bounce_rate_change'    => 0,
				)
			);
		}

		// Calculate engagement trends
		$current_duration_avg  = $this->calculate_engagement_average( $current_period, 'visit_duration' );
		$previous_duration_avg = $this->calculate_engagement_average( $previous_period, 'visit_duration' );

		$current_views_avg  = $this->calculate_engagement_average( $current_period, 'page_views' );
		$previous_views_avg = $this->calculate_engagement_average( $previous_period, 'page_views' );

		$current_bounce_avg  = $this->calculate_engagement_average( $current_period, 'bounce_rate' );
		$previous_bounce_avg = $this->calculate_engagement_average( $previous_period, 'bounce_rate' );

		return array_merge(
			$current_engagement,
			array(
				'visit_duration_change' => $this->calculate_percentage_change( $previous_duration_avg, $current_duration_avg ),
				'page_views_change'     => $this->calculate_percentage_change( $previous_views_avg, $current_views_avg ),
				'bounce_rate_change'    => $this->calculate_percentage_change( $previous_bounce_avg, $current_bounce_avg ),
			)
		);
	}

	/**
	 * Calculate average for engagement metrics
	 */
	private function calculate_engagement_average( $data, $metric ) {
		if ( empty( $data ) ) {
			return 0;
		}

		$sum   = 0;
		$count = 0;

		foreach ( $data as $record ) {
			if ( isset( $record['engagement'][ $metric ] ) ) {
				$sum += floatval( $record['engagement'][ $metric ] );
				++$count;
			}
		}

		return $count > 0 ? $sum / $count : 0;
	}

	/**
	 * Generate weekly trend data from actual historical data
	 */
	private function generate_weekly_trend_data( $current_count ) {
		$historical_key  = 'product_scraper_historical_data';
		$historical_data = get_option( $historical_key, array() );

		// If we have enough historical data, use real weekly patterns
		if ( count( $historical_data ) >= 7 ) {
			$last_7_days = array_slice( $historical_data, -7, 7, true );

			$weekly_data = array(
				'mon' => 0,
				'tue' => 0,
				'wed' => 0,
				'thu' => 0,
				'fri' => 0,
				'sat' => 0,
				'sun' => 0,
			);

			foreach ( $last_7_days as $date => $data ) {
				$day_of_week = strtolower( date( 'D', strtotime( $date ) ) );
				if ( isset( $weekly_data[ $day_of_week ] ) ) {
					$weekly_data[ $day_of_week ] = $data['referring_domains'] ?? 0;
				}
			}

			return $weekly_data;
		}

		// Fallback to estimated pattern if not enough data
		$base_count = $current_count > 0 ? $current_count : 50;

		return array(
			'mon' => round( $base_count * 0.9 ),
			'tue' => round( $base_count * 1.0 ),
			'wed' => round( $base_count * 1.1 ),
			'thu' => round( $base_count * 1.05 ),
			'fri' => round( $base_count * 0.95 ),
			'sat' => round( $base_count * 0.8 ),
			'sun' => round( $base_count * 0.85 ),
		);
	}

	/**
	 * Format percentage change with proper styling
	 */
	private function format_percentage_change( $change ) {
		if ( $change > 0 ) {
			return '<span class="stat-change positive">+' . number_format( $change, 1 ) . '%</span>';
		} elseif ( $change < 0 ) {
			return '<span class="stat-change negative">' . number_format( $change, 1 ) . '%</span>';
		} else {
			return '<span class="stat-change neutral">' . number_format( $change, 1 ) . '%</span>';
		}
	}

	/**
	 * Generate weekly trend HTML with dynamic data
	 */
	private function generate_weekly_trend_html( $weekly_trend ) {
		$days       = array( 'mon', 'tue', 'wed', 'thu', 'fri', 'sat', 'sun' );
		$day_labels = array( 'M', 'T', 'W', 'T', 'F', 'S', 'S' );

		$html = '<div class="stat-trend">';

		foreach ( $days as $index => $day ) {
			$value    = $weekly_trend[ $day ] ?? 0;
			$height   = $value > 0 ? min( 100, ( $value / max( $weekly_trend ) ) * 100 ) : 5;
			$is_today = $index === (int) date( 'N' ) - 1; // Monday = 0, Sunday = 6

			$html .= '<span class="trend-day ' . ( $is_today ? 'today' : '' ) . '" title="' . ucfirst( $day ) . ': ' . $value . '" style="height: ' . $height . '%">';
			$html .= $day_labels[ $index ];
			$html .= '</span>';
		}

		$html .= '</div>';
		return $html;
	}

	/**
	 * AJAX handler for analytics data.
	 */
	public function ajax_get_analytics() {

		// Ensure nonce exists.
		if ( ! isset( $_POST['nonce'] ) ) {
			wp_die( 'Missing nonce.' );
		}

		// Unslash and sanitize the nonce.
		$nonce = sanitize_text_field( wp_unslash( $_POST['nonce'] ) );

		// Verify the nonce.
		if ( ! wp_verify_nonce( $nonce, 'analytics_nonce' ) ) {
			wp_die( 'Security check failed.' );
		}

		// Get dashboard stats.
		$stats = $this->get_dashboard_stats();

		wp_send_json_success( $stats );
	}

	/**
	 * AJAX handler for keyword data.
	 */
	public function ajax_get_keyword_data() {

		// Ensure nonce exists.
		if ( ! isset( $_POST['nonce'] ) ) {
			wp_die( 'Missing nonce.' );
		}

		// Unslash and sanitize the nonce.
		$nonce = sanitize_text_field( wp_unslash( $_POST['nonce'] ) );

		// Verify the nonce.
		if ( ! wp_verify_nonce( $nonce, 'analytics_nonce' ) ) {
			wp_die( 'Security check failed.' );
		}

		// Get SEO dashboard data.
		$seo_data = $this->api->get_seo_dashboard_data();

		wp_send_json_success( array( 'keywords' => $seo_data['top_keywords'] ) );
	}

	/**
	 * AJAX handler for data sync.
	 */
	public function ajax_sync_seo_data() {

		// Ensure nonce exists.
		if ( ! isset( $_POST['nonce'] ) ) {
			wp_die( 'Missing nonce.' );
		}

		// Unslash and sanitize the nonce.
		$nonce = sanitize_text_field( wp_unslash( $_POST['nonce'] ) );

		// Verify the nonce.
		if ( ! wp_verify_nonce( $nonce, 'analytics_nonce' ) ) {
			wp_die( 'Security check failed.' );
		}

		// Get new SEO dashboard data.
		$new_data = $this->api->get_seo_dashboard_data();

		wp_send_json_success( $new_data );
	}

	/**
	 * Display settings page for API configurations
	 */
	public function display_settings_page() {
		// Handle form submissions.
		if ( isset( $_POST['submit_settings'] ) && check_admin_referer( 'product_scraper_settings_nonce' ) ) {
			$this->save_settings();
		}

		// Get current settings.
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
				// Test API connections.
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
							nonce: '<?php echo esc_js( wp_create_nonce( 'test_apis_nonce' ) ); ?>'
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

				// Clear cache.
				$('#clear_cache').on('click', function() {
					var $button = $(this);
					var originalText = $button.html();

					$button.prop('disabled', true).html('<span class="dashicons dashicons-update spin"></span> Clearing...');

					$.ajax({
						url: ajaxurl,
						type: 'POST',
						data: {
							action: 'clear_seo_cache',
							nonce: '<?php echo esc_js( wp_create_nonce( 'clear_cache_nonce' ) ); ?>'
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
	 * Save settings with proper security validation.
	 */
	private function save_settings() {
		// Verify nonce first - this should already be done by check_admin_referer() but we'll double-check.
		if (
			! isset( $_POST['product_scraper_settings_nonce'] ) ||
			! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['product_scraper_settings_nonce'] ) ), 'product_scraper_settings_nonce' )
		) {
			wp_die( 'Security check failed.' );
		}

		// Check user capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( 'Insufficient permissions.' );
		}

		// Google Services - with proper sanitization.
		if ( isset( $_POST['ga4_property_id'] ) ) {
			$ga4_property_id = sanitize_text_field( wp_unslash( $_POST['ga4_property_id'] ) );
			update_option( 'product_scraper_ga4_property_id', $ga4_property_id );
		}

		if ( isset( $_POST['pagespeed_api'] ) ) {
			$pagespeed_api = sanitize_text_field( wp_unslash( $_POST['pagespeed_api'] ) );
			update_option( 'product_scraper_pagespeed_api', $pagespeed_api );
		}

		if ( isset( $_POST['google_service_account'] ) ) {
			$json = sanitize_textarea_field( wp_unslash( $_POST['google_service_account'] ) );
			update_option( 'product_scraper_google_service_account', $json );
		}

		// SEO Platforms - with proper sanitization.
		if ( isset( $_POST['ahrefs_api'] ) ) {
			$ahrefs_api = sanitize_text_field( wp_unslash( $_POST['ahrefs_api'] ) );
			update_option( 'product_scraper_ahrefs_api', $ahrefs_api );
		}

		if ( isset( $_POST['semrush_api'] ) ) {
			$semrush_api = sanitize_text_field( wp_unslash( $_POST['semrush_api'] ) );
			update_option( 'product_scraper_semrush_api', $semrush_api );
		}

		// Advanced Settings - with proper sanitization.
		if ( isset( $_POST['cache_duration'] ) ) {
			$cache_duration = absint( $_POST['cache_duration'] );
			update_option( 'product_scraper_cache_duration', $cache_duration );
		}

		update_option( 'product_scraper_enable_debug', isset( $_POST['enable_debug'] ) ? 1 : 0 );
		update_option( 'product_scraper_auto_sync', isset( $_POST['auto_sync'] ) ? 1 : 0 );

		echo '<div class="notice notice-success is-dismissible"><p>Settings saved successfully!</p></div>';
	}

	/**
	 * Display reports page.
	 */
	public function display_reports_page() {
		// Get report data.
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
									<div class="metric-value"><?php echo esc_html( number_format( $reports['organic_traffic'] ) ); ?></div>
									<div class="metric-change <?php echo esc_attr( $reports['traffic_change'] >= 0 ? 'positive' : 'negative' ); ?>">
										<?php echo esc_html( $reports['traffic_change'] >= 0 ? '+' : '' ); ?><?php echo esc_html( $reports['traffic_change'] ); ?>%
									</div>
								</div>

								<div class="sa-metric-card">
									<h3>Keyword Rankings</h3>
									<div class="metric-value"><?php echo esc_html( number_format( $reports['keyword_rankings'] ) ); ?></div>
									<div class="metric-change positive">+12%</div>
								</div>

								<div class="sa-metric-card">
									<h3>Backlinks</h3>
									<div class="metric-value"><?php echo esc_html( number_format( $reports['backlinks'] ) ); ?></div>
									<div class="metric-change positive">+8%</div>
								</div>
							</div>

							<!-- Technical SEO Health -->
							<div class="sa-report-card">
								<h3>Technical SEO Health</h3>
								<div class="health-metrics">
									<?php foreach ( $reports['technical_health'] as $metric ) : ?>
										<?php
										// Sanitize metric data.
										$label  = isset( $metric['label'] ) ? esc_html( $metric['label'] ) : '';
										$score  = isset( $metric['score'] ) ? intval( $metric['score'] ) : 0;
										$status = isset( $metric['status'] ) ? esc_attr( $metric['status'] ) : 'neutral';
										?>
										<div class="health-metric">
											<span class="metric-label"><?php echo esc_html( $label ); ?></span>
											<div class="metric-score">
												<span class="score"><?php echo esc_html( $score ); ?>%</span>
												<div class="score-bar">
													<div class="score-fill <?php echo esc_attr( $status ); ?>" style="width: <?php echo esc_attr( $score ); ?>%;"></div>
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
										<?php
										// Sanitize content data.
										$title     = isset( $content['title'] ) ? esc_html( $content['title'] ) : '';
										$url       = isset( $content['url'] ) ? esc_url( $content['url'] ) : '';
										$traffic   = isset( $content['traffic'] ) ? intval( $content['traffic'] ) : 0;
										$keywords  = isset( $content['keywords'] ) ? intval( $content['keywords'] ) : 0;
										$backlinks = isset( $content['backlinks'] ) ? intval( $content['backlinks'] ) : 0;
										?>
										<div class="content-item">
											<div class="content-title">
												<a href="<?php echo esc_url( $url ); ?>" target="_blank" rel="noopener noreferrer">
													<?php echo esc_html( $title ); ?>
												</a>
											</div>
											<div class="content-metrics">
												<span class="metric">Traffic: <?php echo esc_html( number_format( $traffic ) ); ?></span>
												<span class="metric">Keywords: <?php echo esc_html( number_format( $keywords ) ); ?></span>
												<span class="metric">Backlinks: <?php echo esc_html( number_format( $backlinks ) ); ?></span>
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
												<?php
												// Sanitize competitor data.
												$domain      = isset( $competitor['domain'] ) ? esc_html( $competitor['domain'] ) : '';
												$authority   = isset( $competitor['authority'] ) ? esc_html( $competitor['authority'] ) : 0;
												$ref_domains = isset( $competitor['ref_domains'] ) ? intval( $competitor['ref_domains'] ) : 0;
												$traffic     = isset( $competitor['traffic'] ) ? intval( $competitor['traffic'] ) : 0;
												$keywords    = isset( $competitor['keywords'] ) ? intval( $competitor['keywords'] ) : 0;
												?>
												<tr>
													<td><?php echo esc_html( $domain ); ?></td>
													<td><?php echo esc_html( $authority ); ?></td>
													<td><?php echo esc_html( number_format( $ref_domains ) ); ?></td>
													<td><?php echo esc_html( number_format( $traffic ) ); ?></td>
													<td><?php echo esc_html( number_format( $keywords ) ); ?></td>
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
										<?php
										// Sanitize recommendation data.
										$title       = isset( $rec['title'] ) ? esc_html( $rec['title'] ) : '';
										$description = isset( $rec['description'] ) ? esc_html( $rec['description'] ) : '';
										$priority    = isset( $rec['priority'] ) ? esc_attr( $rec['priority'] ) : 'medium';
										$impact      = isset( $rec['impact'] ) ? esc_html( $rec['impact'] ) : 'Medium';
										?>
										<div class="recommendation-item priority-<?php echo esc_attr( $priority ); ?>">
											<div class="rec-content">
												<h4><?php echo esc_html( $title ); ?></h4>
												<p><?php echo esc_html( $description ); ?></p>
												<span class="rec-impact">Impact: <?php echo esc_html( $impact ); ?></span>
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

				// Show loading state.
				jQuery('.sa-main-content').addClass('loading');

				// In a real implementation, you would make an AJAX call here.
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
				// Implement CSV generation and download.
			}
		</script>
		<?php
	}

	/**
	 * Get SEO reports data
	 */
	private function get_seo_reports() {
		// This would typically fetch real data from your API integrations.
		// For now, returning sample data structure.

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
	 * AJAX handler for testing API connections.
	 *
	 * @return void
	 */
	public function ajax_test_api_connections() {
		// Check if nonce exists first.
		if ( ! isset( $_POST['nonce'] ) ) {
			wp_send_json_error( 'Missing security token.' );
		}

		// Sanitize and verify nonce.
		$nonce = sanitize_text_field( wp_unslash( $_POST['nonce'] ) );

		if ( ! wp_verify_nonce( $nonce, 'test_apis_nonce' ) ) {
			wp_send_json_error( 'Security check failed.' );
		}

		// Check user capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Insufficient permissions.' );
		}

		$results          = array();
		$api_integrations = new ProductScraper_API_Integrations();

		// Test Google Analytics.
		try {
			$ga_data                     = $api_integrations->get_organic_traffic();
			$results['google_analytics'] = array(
				'connected' => 'google_analytics' === $ga_data['source'],
				'message'   => 'google_analytics' === $ga_data['source'] ? 'Connected successfully' : 'No data received',
			);
		} catch ( Exception $e ) {
			$results['google_analytics'] = array(
				'connected' => false,
				'message'   => $e->getMessage(),
			);
		}

		// Test PageSpeed Insights.
		try {
			$health_data          = $api_integrations->get_site_health_metrics();
			$results['pagespeed'] = array(
				'connected' => 'pagespeed_api' === $health_data['source'],
				'message'   => 'pagespeed_api' === $health_data['source'] ? 'Connected successfully' : 'No data received',
			);
		} catch ( Exception $e ) {
			$results['pagespeed'] = array(
				'connected' => false,
				'message'   => $e->getMessage(),
			);
		}

		// Test Ahrefs.
		try {
			$ahrefs_data       = $api_integrations->get_referring_domains();
			$results['ahrefs'] = array(
				'connected' => 'ahrefs_api' === $ahrefs_data['source'],
				'message'   => 'ahrefs_api' === $ahrefs_data['source'] ? 'Connected successfully' : 'No data received',
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
	 * AJAX handler for clearing cache.
	 */
	public function ajax_clear_seo_cache() {
		// Verify nonce existence first.
		if ( ! isset( $_POST['nonce'] ) ) {
			wp_sendjson_error( 'Misng nonce.' );
		}

		// Sanitize nonce properly.
		$nonce = sanitize_text_field( wp_unslash( $_POST['nonce'] ) );

		if ( ! wp_verify_nonce( $nonce, 'clear_cache_nonce' ) ) {
			wp_send_json_erro( 'Security check failed.' );
		}

		$cleared_count = 0;

		// Use WordPress cache functions to manage known transient keys.
		$cached_keys = wp_cache_get( 'product_scraper_transient_keys', 'product_scraper' );

		if ( $cached_keys && is_array( $cached_keys ) ) {
			foreach ( $cached_keys as $key ) {
				if ( delete_transient( $key ) ) {
					$cleaned_key = str_replace( 'product_scraper_', '', $key );
					wp_cche_delete( $cleaned_key, 'prduct_scraper' );
					++$clered_count;
				}
			}
		}

		// Clea the main keys cache.
		wp_cache_delete( 'product_scraper_transient_keys', 'product_scraper' );

		wp_send_json_success(
			sprintf(
				'Cache cleared successfully. %d items removed.',
				$clearedcount
			)
		);
	}
}
?>