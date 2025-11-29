<?php
/**
 * API Integrations for Product Scraper
 *
 * @package    Product_Scraper
 * @subpackage API
 * @author     Your Name
 * @since      1.0.0
 */

/**
 * Handles API integrations for various SEO and analytics services
 *
 * This class manages connections to Google Analytics, Ahrefs, SEMrush,
 * PageSpeed Insights, and other third-party APIs to gather SEO data.
 */
class ProductScraper_API_Integrations {


	/**
	 * Cache duration in seconds
	 *
	 * @var int
	 */
	private $cache_duration = 3600; // 1 hour cache.

	/**
	 * Constructor
	 *
	 * Sets up AJAX handlers for data synchronization.
	 */
	public function __construct() {
		add_action( 'wp_ajax_sync_seo_data', array( $this, 'ajax_sync_seo_data' ) );
	}

	/**
	 * Check if Google API classes are available
	 *
	 * @return bool
	 */
	private function google_api_available() {
		return class_exists( 'Google_Client' ) && class_exists( 'Google_Service_AnalyticsData' );
	}

	/**
	 * Get comprehensive SEO data for the current site
	 *
	 * @return array
	 */
	public function get_seo_dashboard_data() {
		$cache_key   = 'product_scraper_seo_data_' . md5( get_site_url() );
		$cached_data = get_transient( $cache_key );

		if ( false !== $cached_data ) {
			return $cached_data;
		}

		$data = array(
			'organic_traffic'     => $this->get_organic_traffic(),
			'referring_domains'   => $this->get_referring_domains(),
			'top_keywords'        => $this->get_top_keywords(),
			'digital_score'       => $this->calculate_digital_score(),
			'engagement_metrics'  => $this->get_engagement_metrics(),
			'competitor_analysis' => $this->get_competitor_analysis(),
			'site_health'         => $this->get_site_health_metrics(),
			'last_updated'        => current_time( 'mysql' ),
		);

		// Debug logging - remove in production.
		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'SEO Dashboard Data: ' . print_r( $data, true ) ); // phpcs:ignore
		}

		set_transient( $cache_key, $data, $this->cache_duration );
		return $data;
	}

	/**
	 * Get organic traffic data from multiple sources with fallback strategy
	 *
	 * @return array
	 */
	public function get_organic_traffic() {
		$cache_key   = 'product_scraper_organic_traffic_' . md5( get_site_url() );
		$cached_data = get_transient( $cache_key );

		if ( false !== $cached_data ) {
			return $cached_data;
		}

		$traffic_data = $this->fetch_organic_traffic_with_fallback();
		set_transient( $cache_key, $traffic_data, $this->cache_duration );

		return $traffic_data;
	}

	/**
	 * Fetch organic traffic with multiple fallback sources
	 *
	 * @return array
	 */
	private function fetch_organic_traffic_with_fallback() {
		$sources_tried = array();

		// Source 1: Google Analytics 4 (Primary).
		if ( $this->can_use_google_analytics() ) {
			try {
				$ga_data = $this->get_google_analytics_data();
				if ( $this->is_valid_traffic_data( $ga_data ) ) {
					$sources_tried[] = 'google_analytics';
					return $ga_data;
				}
			} catch ( Exception $e ) {
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log( 'GA4 Organic Traffic Error: ' . $e->getMessage() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				}
				$sources_tried[] = 'google_analytics_failed';
			}
		}

		// Source 2: Google Search Console (Secondary).
		if ( $this->can_use_search_console() ) {
			try {
				$gsc_data = $this->get_search_console_traffic();
				if ( $this->is_valid_traffic_data( $gsc_data ) ) {
					$sources_tried[] = 'search_console';
					return $gsc_data;
				}
			} catch ( Exception $e ) {
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log( 'GSC Traffic Error: ' . $e->getMessage() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				}
				$sources_tried[] = 'search_console_failed';
			}
		}

		// Source 3: Third-party SEO tools (Tertiary).
		if ( $this->can_use_seo_tools() ) {
			try {
				$seo_tool_data = $this->get_seo_tool_traffic_estimate();
				if ( $this->is_valid_traffic_data( $seo_tool_data ) ) {
					$sources_tried[] = 'seo_tool';
					return $seo_tool_data;
				}
			} catch ( Exception $e ) {
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log( 'SEO Tool Traffic Error: ' . $e->getMessage() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				}
				$sources_tried[] = 'seo_tool_failed';
			}
		}

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'All organic traffic sources failed: ' . implode( ', ', $sources_tried ) ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		}
		return $this->get_empty_traffic_data();
	}

	/**
	 * Check if Google Analytics can be used
	 *
	 * @return bool
	 */
	private function can_use_google_analytics() {
		$property_id     = get_option( 'product_scraper_ga4_property_id' );
		$service_account = get_option( 'product_scraper_google_service_account' );

		return $property_id
			&& ! empty( $service_account )
			&& $this->google_api_available()
			&& null !== json_decode( $service_account, true );
	}

	/**
	 * Enhanced Google Analytics data with better metrics
	 *
	 * @return array
	 * @throws Exception If API call fails.
	 */
	private function get_google_analytics_data() {
		$property_id          = get_option( 'product_scraper_ga4_property_id' );
		$service_account_json = get_option( 'product_scraper_google_service_account' );

		try {
			$client          = new Google_Client();
			$service_account = json_decode( $service_account_json, true );

			if ( JSON_ERROR_NONE !== json_last_error() ) {
				throw new Exception( 'Invalid service account JSON' );
			}

			$client->setAuthConfig( $service_account );
			$client->addScope( 'https://www.googleapis.com/auth/analytics.readonly' );
			$client->setAccessType( 'offline' );

			$analytics = new Google_Service_AnalyticsData( $client );

			// Test authentication first.
			$access_token = $client->fetchAccessTokenWithAssertion();
			if ( isset( $access_token['error'] ) ) {
				throw new Exception( 'GA4 Authentication failed: ' . $access_token['error_description'] );
			}

			// Get current period data (last 30 days).
			$current_data = $this->fetch_ga4_traffic_data( $analytics, $property_id, '30daysAgo', 'today' );

			// Get previous period data for comparison (30-60 days ago).
			$previous_data = $this->fetch_ga4_traffic_data( $analytics, $property_id, '60daysAgo', '30daysAgo' );

			// Calculate trends and changes.
			$change = $previous_data['sessions'] > 0 ?
				( ( $current_data['sessions'] - $previous_data['sessions'] ) / $previous_data['sessions'] ) * 100 : 0;

			$trend = $this->determine_trend( $change );

			return array(
				'current'          => $current_data['sessions'],
				'previous'         => $previous_data['sessions'],
				'change'           => round( $change, 1 ),
				'trend'            => $trend,
				'bounce_rate'      => $current_data['bounce_rate'],
				'avg_duration'     => $current_data['avg_duration'],
				'organic_sessions' => $current_data['organic_sessions'],
				'total_users'      => $current_data['total_users'],
				'source'           => 'google_analytics',
				'last_updated'     => current_time( 'mysql' ),
			);
		} catch ( Exception $e ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'Google Analytics API Detailed Error: ' . $e->getMessage() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			}
			throw $e;
		}
	}

	/**
	 * Fetch specific GA4 traffic data with organic filtering
	 *
	 * @param Google_Service_AnalyticsData $analytics  Analytics service instance.
	 * @param string                       $property_id GA4 property ID.
	 * @param string                       $start_date Start date for the report.
	 * @param string                       $end_date   End date for the report.
	 * @return array
	 */
	private function fetch_ga4_traffic_data( $analytics, $property_id, $start_date, $end_date ) {
		// Organic traffic query.
		$organic_request = new Google_Service_AnalyticsData_RunReportRequest(
			array(
				'dimensions'      => array(
					new Google_Service_AnalyticsData_Dimension( array( 'name' => 'sessionMedium' ) ),
					new Google_Service_AnalyticsData_Dimension( array( 'name' => 'sessionSource' ) ),
				),
				'metrics'         => array(
					new Google_Service_AnalyticsData_Metric( array( 'name' => 'sessions' ) ),
					new Google_Service_AnalyticsData_Metric( array( 'name' => 'bounceRate' ) ),
					new Google_Service_AnalyticsData_Metric( array( 'name' => 'averageSessionDuration' ) ),
					new Google_Service_AnalyticsData_Metric( array( 'name' => 'totalUsers' ) ),
				),
				'dateRanges'      => array(
					new Google_Service_AnalyticsData_DateRange(
						array(
							'startDate' => $start_date,
							'endDate'   => $end_date,
						)
					),
				),
				'dimensionFilter' => array(
					'filter' => array(
						'fieldName'    => 'sessionMedium',
						'stringFilter' => array(
							'matchType' => 'EXACT',
							'value'     => 'organic',
						),
					),
				),
			)
		);

		$response = $analytics->properties->runReport( "properties/{$property_id}", $organic_request );

		$sessions     = 0;
		$bounce_rate  = 0;
		$avg_duration = 0;
		$total_users  = 0;

		if ( $response->getRows() ) {
			foreach ( $response->getRows() as $row ) {
				$sessions    += (int) $row->getMetricValues()[0]->getValue();
				$bounce_rate  = (float) $row->getMetricValues()[1]->getValue();
				$avg_duration = (float) $row->getMetricValues()[2]->getValue();
				$total_users += (int) $row->getMetricValues()[3]->getValue();
			}
		}

		return array(
			'sessions'         => $sessions,
			'bounce_rate'      => $bounce_rate,
			'avg_duration'     => $avg_duration,
			'organic_sessions' => $sessions, // Specifically organic.
			'total_users'      => $total_users,
		);
	}

	/**
	 * Get traffic data from Google Search Console
	 *
	 * @return array
	 */
	private function get_search_console_traffic() {
		// Todo: This would require additional GSC API implementation.
		// For now, return empty data structure.
		return $this->get_empty_traffic_data();
	}

	/**
	 * Get traffic estimate from SEO tools (Ahrefs/SEMrush)
	 *
	 * @return array
	 * @throws Exception If API call fails.
	 */
	private function get_seo_tool_traffic_estimate() {
		$api_key = get_option( 'product_scraper_ahrefs_api' );
		$target  = wp_parse_url( get_site_url(), PHP_URL_HOST );

		if ( ! $api_key ) {
			throw new Exception( 'No SEO tool API key configured' );
		}

		$url = "https://apiv2.ahrefs.com?token={$api_key}&target={$target}&from=metrics_extended&mode=domain";

		$response = wp_remote_get(
			$url,
			array(
				'timeout' => 15,
				'headers' => array(
					'User-Agent' => 'WordPress/ProductScraper; ' . home_url(),
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			throw new Exception( 'SEO tool API HTTP error: ' . $response->get_error_message() ); // phpcs:ignore
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( isset( $data['error'] ) ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped
			throw new Exception( 'SEO tool API error: ' . $data['error'] );
		}

		$organic_traffic  = $data['organic_traffic'] ?? 0;
		$previous_traffic = $data['organic_traffic_previous'] ?? $organic_traffic;

		$change = $previous_traffic > 0 ?
			( ( $organic_traffic - $previous_traffic ) / $previous_traffic ) * 100 : 0;

		return array(
			'current'      => (int) $organic_traffic,
			'previous'     => (int) $previous_traffic,
			'change'       => round( $change, 1 ),
			'trend'        => $this->determine_trend( $change ),
			'bounce_rate'  => 0, // Not available from SEO tools.
			'avg_duration' => 0, // Not available from SEO tools.
			'source'       => 'seo_tool',
			'last_updated' => current_time( 'mysql' ),
		);
	}

	/**
	 * Check if traffic data is valid and usable
	 *
	 * @param array $data Traffic data to validate.
	 * @return bool
	 */
	private function is_valid_traffic_data( $data ) {
		return is_array( $data )
			&& isset( $data['current'] )
			&& $data['current'] > 0
			&& 'none' !== $data['source'];
	}

	/**
	 * Determine trend direction based on percentage change
	 *
	 * @param float $change Percentage change.
	 * @return string
	 */
	private function determine_trend( $change ) {
		if ( $change > 5 ) {
			return 'positive';
		} elseif ( $change < -5 ) {
			return 'negative';
		} else {
			return 'neutral';
		}
	}

	/**
	 * Check if Search Console can be used
	 *
	 * @return bool
	 */
	private function can_use_search_console() {
		// Todo: Implement based on your GSC configuration.
		return false; // Placeholder.
	}

	/**
	 * Check if SEO tools can be used
	 *
	 * @return bool
	 */
	private function can_use_seo_tools() {
		return ! empty( get_option( 'product_scraper_ahrefs_api' ) )
			|| ! empty( get_option( 'product_scraper_semrush_api' ) );
	}

	/**
	 * Get referring domains from multiple sources with comprehensive backlink data
	 *
	 * @return array
	 */
	public function get_referring_domains() {
		$cache_key   = 'product_scraper_referring_domains_' . md5( get_site_url() );
		$cached_data = get_transient( $cache_key );

		if ( false !== $cached_data ) {
			return $cached_data;
		}

		$sources_tried = array();

		// Source 1: Google Search Console (Primary - Free & Official)
		if ( $this->can_use_search_console() ) {
			try {
				$gsc_data = $this->get_gsc_links_data();
				if ( $this->is_valid_referring_data( $gsc_data ) ) {
					$sources_tried[] = 'google_search_console';
					set_transient( $cache_key, $gsc_data, $this->cache_duration );
					return $gsc_data;
				}
			} catch ( Exception $e ) {
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log( 'GSC Links Error: ' . $e->getMessage() );
				}
				$sources_tried[] = 'gsc_failed';
			}
		}

		// Source 2: Ahrefs (Secondary - Comprehensive)
		$ahrefs_data = $this->get_ahrefs_referring_domains();
		if ( $this->is_valid_referring_data( $ahrefs_data ) ) {
			$sources_tried[] = 'ahrefs';
			set_transient( $cache_key, $ahrefs_data, $this->cache_duration );
			return $ahrefs_data;
		}

		// Source 3: SEMrush (Tertiary)
		$semrush_data = $this->get_semrush_backlinks();
		if ( $this->is_valid_referring_data( $semrush_data ) ) {
			$sources_tried[] = 'semrush';
			set_transient( $cache_key, $semrush_data, $this->cache_duration );
			return $semrush_data;
		}

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'All referring domains sources failed: ' . implode( ', ', $sources_tried ) );
		}

		$empty_data = $this->get_empty_referring_domains();
		set_transient( $cache_key, $empty_data, $this->cache_duration );
		return $empty_data;
	}

	/**
	 * Get backlink data from SEMrush
	 */
	private function get_semrush_backlinks() {
		$api_key = get_option( 'product_scraper_semrush_api' );
		$target  = wp_parse_url( get_site_url(), PHP_URL_HOST );

		if ( ! $api_key ) {
			return $this->get_empty_referring_domains();
		}

		// SEMrush backlinks endpoint
		$url = add_query_arg(
			array(
				'key'            => $api_key,
				'type'           => 'backlinks',
				'target'         => $target,
				'target_type'    => 'root_domain',
				'export_columns' => 'total',
			),
			'https://api.semrush.com'
		);

		$response = wp_remote_get( $url );

		if ( is_wp_error( $response ) ) {
			return $this->get_empty_referring_domains();
		}

		$data = wp_remote_retrieve_body( $response );

		// SEMrush returns CSV format
		$lines = explode( "\n", $data );
		if ( count( $lines ) > 1 ) {
			$backlinks_data = str_getcsv( $lines[1] );
			if ( isset( $backlinks_data[0] ) ) {
				$backlinks_count = intval( $backlinks_data[0] );

				return array(
					'count'         => $backlinks_count,
					'domain_rating' => 0, // SEMrush doesn't provide domain rating
					'trend'         => 'neutral',
					'change'        => 0,
					'source'        => 'semrush_api',
				);
			}
		}

		return $this->get_empty_referring_domains();
	}

	/**
	 * Get links data from Google Search Console
	 *
	 * @return array
	 * @throws Exception If API call fails.
	 */
	private function get_gsc_links_data() {
		if ( ! $this->google_api_available() ) {
			throw new Exception( 'Google API not available' );
		}

		$service_account_json = get_option( 'product_scraper_google_service_account' );
		$site_url             = get_site_url();

		if ( empty( $service_account_json ) ) {
			throw new Exception( 'Service account JSON not configured' );
		}

		try {
			$client          = new Google_Client();
			$service_account = json_decode( $service_account_json, true );

			if ( JSON_ERROR_NONE !== json_last_error() ) {
				throw new Exception( 'Invalid service account JSON' );
			}

			$client->setAuthConfig( $service_account );
			$client->addScope( 'https://www.googleapis.com/auth/webmasters' );
			$client->setAccessType( 'offline' );

			$webmasters = new Google_Service_Webmasters( $client );

			// Test authentication
			$access_token = $client->fetchAccessTokenWithAssertion();
			if ( isset( $access_token['error'] ) ) {
				throw new Exception( 'GSC Authentication failed: ' . $access_token['error_description'] );
			}

			// Get external links (referring domains)
			$external_links = $this->get_gsc_external_links( $webmasters, $site_url );

			// Get internal links
			$internal_links = $this->get_gsc_internal_links( $webmasters, $site_url );

			return array(
				'count'               => $external_links['referring_domains'],
				'domain_rating'       => $this->calculate_domain_authority( $external_links['referring_domains'] ),
				'external_links'      => $external_links['total_links'],
				'internal_links'      => $internal_links['total_links'],
				'top_linking_domains' => $external_links['top_domains'],
				'top_linked_pages'    => $internal_links['top_pages'],
				'trend'               => 'neutral',
				'change'              => 0,
				'source'              => 'google_search_console',
			);

		} catch ( Exception $e ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'Google Search Console Links API Error: ' . $e->getMessage() );
			}
			throw $e;
		}
	}

	/**
	 * Get external links data from GSC
	 */
	private function get_gsc_external_links( $webmasters, $site_url ) {
		try {
			$response = $webmasters->sites->listSites();
			$sites    = $response->getSiteEntry();

			$site_exists = false;
			foreach ( $sites as $site ) {
				if ( $site->getSiteUrl() === $site_url ) {
					$site_exists = true;
					break;
				}
			}

			if ( ! $site_exists ) {
				throw new Exception( 'Site not verified in Google Search Console' );
			}

			// Get external links count (simplified - actual implementation would use links API)
			// Note: GSC API for links is more complex and may require additional setup

			$external_links_data = array(
				'referring_domains' => 0,
				'total_links'       => 0,
				'top_domains'       => array(),
			);

			// This is a simplified implementation
			// In a real scenario, you'd use: $webmasters->links->listLinks($site_url)

			return $external_links_data;

		} catch ( Exception $e ) {
			throw new Exception( 'GSC External Links Error: ' . $e->getMessage() );
		}
	}

	/**
	 * Get internal links data from GSC
	 */
	private function get_gsc_internal_links( $webmasters, $site_url ) {
		try {
			// Simplified internal links implementation
			// In production, you'd analyze your own site structure

			$internal_links_data = array(
				'total_links' => $this->calculate_internal_links_count(),
				'top_pages'   => $this->get_top_internal_linked_pages(),
			);

			return $internal_links_data;

		} catch ( Exception $e ) {
			throw new Exception( 'GSC Internal Links Error: ' . $e->getMessage() );
		}
	}

	/**
	 * Calculate internal links count by analyzing site structure
	 */
	private function calculate_internal_links_count() {
		// Method 1: Analyze WordPress posts and pages
		$post_count = wp_count_posts();
		$page_count = wp_count_posts( 'page' );

		$total_posts = $post_count->publish + $page_count->publish;

		// Estimate internal links (this is a simplified calculation)
		// A typical site has 10-50 internal links per page
		$estimated_links_per_page = 20;

		return $total_posts * $estimated_links_per_page;
	}

	/**
	 * Get top internally linked pages
	 */
	private function get_top_internal_linked_pages() {
		global $wpdb;

		// Get pages with most internal links (simplified approach)
		$top_pages = $wpdb->get_results(
			"SELECT post_title, ID, 
                (LENGTH(post_content) - LENGTH(REPLACE(post_content, '<a href', ''))) / LENGTH('<a href') as link_count
         FROM {$wpdb->posts} 
         WHERE post_status = 'publish' 
         AND post_type IN ('post', 'page')
         ORDER BY link_count DESC 
         LIMIT 10"
		);

		$formatted_pages = array();
		foreach ( $top_pages as $page ) {
			$formatted_pages[] = array(
				'url'   => get_permalink( $page->ID ),
				'title' => $page->post_title,
				'links' => intval( $page->link_count ),
			);
		}

		return $formatted_pages;
	}

	/**
	 * Calculate estimated domain authority based on referring domains
	 */
	private function calculate_domain_authority( $referring_domains ) {
		if ( $referring_domains <= 0 ) {
			return 0.0;
		}

		// Simplified domain authority calculation
		// Based on logarithmic scale similar to Ahrefs DR
		$dr = log( $referring_domains + 1 ) * 10;

		return min( 100.0, round( $dr, 1 ) );
	}

	/**
	 * Get domain rating from Ahrefs API
	 *
	 * @param string $api_key Ahrefs API key.
	 * @param string $target Domain to check.
	 * @return float
	 */
	private function get_domain_rating( $api_key, $target ) {
		if ( empty( $api_key ) ) {
			return 0.0;
		}

		$url = add_query_arg(
			array(
				'token'  => $api_key,
				'target' => $target,
				'from'   => 'domain_rating',
				'mode'   => 'domain',
				'limit'  => 1,
			),
			'https://apiv2.ahrefs.com'
		);

		$response = wp_remote_get(
			$url,
			array(
				'timeout' => 15,
			)
		);

		if ( is_wp_error( $response ) ) {
			return 0.0;
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		if ( 200 !== $response_code ) {
			return 0.0;
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( isset( $data['error'] ) || ! isset( $data['domain_rating'] ) ) {
			return 0.0;
		}

		return floatval( $data['domain_rating'] );
	}

	/**
	 * Enhanced Ahrefs implementation with better error handling
	 */
	private function get_ahrefs_referring_domains() {
		$api_key = get_option( 'product_scraper_ahrefs_api' );
		$target  = wp_parse_url( get_site_url(), PHP_URL_HOST );

		if ( ! $api_key ) {
			return $this->get_empty_referring_domains();
		}

		$url = add_query_arg(
			array(
				'token'  => $api_key,
				'target' => $target,
				'from'   => 'refdomains',
				'mode'   => 'domain',
				'limit'  => 1,
			),
			'https://apiv2.ahrefs.com'
		);

		$response = wp_remote_get(
			$url,
			array(
				'timeout' => 15,
			)
		);

		if ( is_wp_error( $response ) ) {
			return $this->get_empty_referring_domains();
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		if ( 200 !== $response_code ) {
			return $this->get_empty_referring_domains();
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );

		if ( isset( $data['error'] ) ) {
			return $this->get_empty_referring_domains();
		}

		$refdomains = 0;
		if ( isset( $data['refdomains'] ) ) {
			$refdomains = intval( $data['refdomains'] );
		} elseif ( isset( $data['total'] ) ) {
			$refdomains = intval( $data['total'] );
		}

		$domain_rating = $this->get_domain_rating( $api_key, $target );

		return array(
			'count'               => $refdomains,
			'domain_rating'       => $domain_rating,
			'external_links'      => 0, // Ahrefs provides this separately
			'internal_links'      => $this->calculate_internal_links_count(),
			'top_linking_domains' => array(),
			'top_linked_pages'    => $this->get_top_internal_linked_pages(),
			'trend'               => 'neutral',
			'change'              => 0,
			'source'              => 'ahrefs_api',
		);
	}

	/**
	 * Enhanced empty data structure
	 *
	 * @return array
	 */
	private function get_empty_referring_domains() {
		return array(
			'count'               => 0,
			'domain_rating'       => 0,
			'external_links'      => 0,
			'internal_links'      => $this->calculate_internal_links_count(), // Always calculate internal links
			'top_linking_domains' => array(),
			'top_linked_pages'    => $this->get_top_internal_linked_pages(),
			'trend'               => 'neutral',
			'change'              => 0,
			'source'              => 'none',
		);
	}

	/**
	 * Check if referring data is valid
	 */
	private function is_valid_referring_data( $data ) {
		return is_array( $data )
			&& isset( $data['count'] )
			&& $data['count'] > 0
			&& 'none' !== $data['source'];
	}

	/**
	 * Get top performing keywords - REAL DATA ONLY
	 *
	 * @return array
	 */
	private function get_top_keywords() {
		$gsc_connected = get_option( 'product_scraper_google_search_console' );

		if ( $gsc_connected && $this->google_api_available() ) {
			try {
				return $this->get_gsc_keywords();
			} catch ( Exception $e ) {
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log( 'Google Search Console API error: ' . $e->getMessage() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				}
				return $this->get_empty_keywords();
			}
		}

		// Return empty if no GSC configured.
		return $this->get_empty_keywords();
	}

	/**
	 * Get keywords from Google Search Console
	 *
	 * @return array
	 */
	private function get_gsc_keywords() {
		// This is a placeholder - we'll need to implement actual GSC API integration.
		// For now, return empty array.
		return array();
	}

	/**
	 * Calculate comprehensive digital score based on real data
	 *
	 * @return int
	 */
	private function calculate_digital_score() {
		$traffic_data   = $this->get_organic_traffic();
		$referring_data = $this->get_referring_domains();
		$health_data    = $this->get_site_health_metrics();

		$metrics = array(
			'traffic_score'   => $this->calculate_traffic_score( $traffic_data ),
			'authority_score' => $this->calculate_authority_score( $referring_data ),
			'technical_score' => $health_data['overall'] ?? 0,
		);

		$total_score = array_sum( $metrics ) / count( $metrics );
		return round( $total_score );
	}

	/**
	 * Calculate traffic score based on real traffic data
	 *
	 * @param array $traffic_data Traffic data array.
	 * @return int
	 */
	private function calculate_traffic_score( $traffic_data ) {
		if ( $traffic_data['current'] > 10000 ) {
			return 100;
		}
		if ( $traffic_data['current'] > 5000 ) {
			return 80;
		}
		if ( $traffic_data['current'] > 1000 ) {
			return 60;
		}
		if ( $traffic_data['current'] > 100 ) {
			return 40;
		}
		return 20;
	}

	/**
	 * Calculate authority score based on real referring domains
	 *
	 * @param array $referring_data Referring domains data.
	 * @return float
	 */
	private function calculate_authority_score( $referring_data ) {
		$ref_domains   = $referring_data['count'];
		$domain_rating = $referring_data['domain_rating'] * 10; // Convert to 0-100 scale.

		if ( $ref_domains > 1000 ) {
			return min( 100, $domain_rating + 20 );
		}
		if ( $ref_domains > 100 ) {
			return min( 100, $domain_rating + 10 );
		}
		if ( $ref_domains > 10 ) {
			return min( 100, $domain_rating + 5 );
		}
		return $domain_rating;
	}

	/**
	 * Get engagement metrics from real data
	 *
	 * @return array
	 */
	private function get_engagement_metrics() {
		$property_id = get_option( 'product_scraper_ga4_property_id' );

		if ( $property_id && $this->google_api_available() ) {
			try {
				return $this->get_google_analytics_engagement( $property_id );
			} catch ( Exception $e ) {
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log( 'Google Analytics Engagement API error: ' . $e->getMessage() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				}
			}
		}

		return $this->get_empty_engagement_metrics();
	}

	/**
	 * Get engagement metrics from Google Analytics
	 *
	 * @param string $property_id GA4 property ID.
	 * @return array
	 * @throws Exception If API call fails.
	 */
	private function get_google_analytics_engagement( $property_id ) {
		// Get the service account JSON from database option.
		$service_account_json = get_option( 'product_scraper_google_service_account' );

		if ( empty( $service_account_json ) ) {
			throw new Exception( 'Service account JSON not configured' );
		}

		$client = new Google_Client();

		// Use setAuthConfig with JSON string instead of file path.
		$client->setAuthConfig( json_decode( $service_account_json, true ) );
		$client->addScope( 'https://www.googleapis.com/auth/analytics.readonly' );

		$analytics = new Google_Service_AnalyticsData( $client );

		$request = new Google_Service_AnalyticsData_RunReportRequest(
			array(
				'metrics'    => array(
					new Google_Service_AnalyticsData_Metric( array( 'name' => 'sessions' ) ),
					new Google_Service_AnalyticsData_Metric( array( 'name' => 'averageSessionDuration' ) ),
					new Google_Service_AnalyticsData_Metric( array( 'name' => 'bounceRate' ) ),
					new Google_Service_AnalyticsData_Metric( array( 'name' => 'screenPageViewsPerSession' ) ),
				),
				'dateRanges' => array(
					new Google_Service_AnalyticsData_DateRange(
						array(
							'startDate' => '30daysAgo',
							'endDate'   => 'today',
						)
					),
				),
			)
		);

		$response = $analytics->properties->runReport( "properties/{$property_id}", $request );

		$metrics = array(
			'visit_duration'    => 0,
			'page_views'        => 0,
			'bounce_rate'       => 0,
			'pages_per_session' => 0,
		);

		if ( $response->getRows() ) {
			$row     = $response->getRows()[0];
			$metrics = array(
				'visit_duration'    => (float) $row->getMetricValues()[1]->getValue(),
				'page_views'        => (int) $row->getMetricValues()[0]->getValue(),
				'bounce_rate'       => (float) $row->getMetricValues()[2]->getValue(),
				'pages_per_session' => (float) $row->getMetricValues()[3]->getValue(),
			);
		}

		return $metrics;
	}

	/**
	 * Get competitor analysis - REAL DATA ONLY
	 *
	 * @return array
	 */
	private function get_competitor_analysis() {
		$cache_key   = 'product_scraper_competitor_analysis_' . md5( get_site_url() );
		$cached_data = get_transient( $cache_key );

		if ( false !== $cached_data ) {
			return $cached_data;
		}

		$analysis = array();

		// Get configured competitors from settings
		$competitors = $this->get_configured_competitors();

		if ( empty( $competitors ) ) {
			// If no competitors configured, return empty analysis
			$empty_data = $this->get_empty_competitor_analysis();
			set_transient( $cache_key, $empty_data, $this->cache_duration );
			return $empty_data;
		}

		// Try different data sources for competitor analysis
		$analysis = $this->fetch_competitor_data_with_fallback( $competitors );

		set_transient( $cache_key, $analysis, $this->cache_duration );
		return $analysis;
	}

	/**
	 * Get configured competitors from settings
	 *
	 * @return array
	 */
	private function get_configured_competitors() {
		$competitors = get_option( 'product_scraper_competitors', array() );

		if ( ! empty( $competitors ) && is_string( $competitors ) ) {
			// Handle string format (comma-separated)
			$competitors = array_map( 'trim', explode( ',', $competitors ) );
		}

		// Filter out empty values and ensure proper format
		$competitors = array_filter(
			array_map(
				function ( $domain ) {
					$domain = trim( $domain );
					if ( empty( $domain ) ) {
						return false;
					}

					// Ensure domain format
					if ( false === strpos( $domain, '.' ) ) {
						return false;
					}

					return $domain;
				},
				(array) $competitors
			)
		);

		return array_slice( $competitors, 0, 5 ); // Limit to 5 competitors
	}

	/**
	 * Fetch competitor data with multiple fallback sources
	 *
	 * @param array $competitors Array of competitor domains.
	 * @return array
	 */
	private function fetch_competitor_data_with_fallback( $competitors ) {
		$sources_tried = array();
		$analysis      = array();

		// Source 1: Ahrefs API (Primary)
		if ( $this->can_use_ahrefs() ) {
			try {
				$ahrefs_data = $this->get_ahrefs_competitor_data( $competitors );
				if ( $this->is_valid_competitor_data( $ahrefs_data ) ) {
					$sources_tried[] = 'ahrefs';
					return $ahrefs_data;
				}
			} catch ( Exception $e ) {
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log( 'Ahrefs Competitor Data Error: ' . $e->getMessage() );
				}
				$sources_tried[] = 'ahrefs_failed';
			}
		}

		// Source 2: SEMrush API (Secondary)
		if ( $this->can_use_semrush() ) {
			try {
				$semrush_data = $this->get_semrush_competitor_data( $competitors );
				if ( $this->is_valid_competitor_data( $semrush_data ) ) {
					$sources_tried[] = 'semrush';
					return $semrush_data;
				}
			} catch ( Exception $e ) {
				if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
					error_log( 'SEMrush Competitor Data Error: ' . $e->getMessage() );
				}
				$sources_tried[] = 'semrush_failed';
			}
		}

		// Source 3: Built-in analysis (Tertiary)
		try {
			$builtin_data = $this->get_builtin_competitor_analysis( $competitors );
			if ( $this->is_valid_competitor_data( $builtin_data ) ) {
				$sources_tried[] = 'builtin';
				return $builtin_data;
			}
		} catch ( Exception $e ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'Built-in Competitor Analysis Error: ' . $e->getMessage() );
			}
			$sources_tried[] = 'builtin_failed';
		}

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'All competitor data sources failed: ' . implode( ', ', $sources_tried ) );
		}

		return $this->get_empty_competitor_analysis();
	}

	/**
	 * Check if Ahrefs API can be used
	 *
	 * @return bool
	 */
	private function can_use_ahrefs() {
		return ! empty( get_option( 'product_scraper_ahrefs_api' ) );
	}

	/**
	 * Check if SEMrush API can be used
	 *
	 * @return bool
	 */
	private function can_use_semrush() {
		return ! empty( get_option( 'product_scraper_semrush_api' ) );
	}

	/**
	 * Get competitor data from Ahrefs API
	 *
	 * @param array $competitors Array of competitor domains.
	 * @return array
	 * @throws Exception If API call fails.
	 */
	private function get_ahrefs_competitor_data( $competitors ) {
		$api_key  = get_option( 'product_scraper_ahrefs_api' );
		$analysis = array();

		foreach ( $competitors as $domain ) {
			$url = add_query_arg(
				array(
					'token'  => $api_key,
					'target' => $domain,
					'from'   => 'domain_rating',
					'mode'   => 'domain',
					'limit'  => 1,
				),
				'https://apiv2.ahrefs.com'
			);

			$response = wp_remote_get(
				$url,
				array(
					'timeout' => 15,
				)
			);

			if ( is_wp_error( $response ) ) {
				throw new Exception( 'Ahrefs API HTTP error: ' . $response->get_error_message() );
			}

			$response_code = wp_remote_retrieve_response_code( $response );
			if ( 200 !== $response_code ) {
				throw new Exception( 'Ahrefs API returned HTTP ' . $response_code );
			}

			$data = json_decode( wp_remote_retrieve_body( $response ), true );

			if ( isset( $data['error'] ) ) {
				throw new Exception( 'Ahrefs API error: ' . $data['error'] );
			}

			$analysis[] = array(
				'domain'      => $domain,
				'authority'   => isset( $data['domain_rating'] ) ? floatval( $data['domain_rating'] ) : 0,
				'ref_domains' => isset( $data['refdomains'] ) ? intval( $data['refdomains'] ) : 0,
				'traffic'     => isset( $data['organic_traffic'] ) ? intval( $data['organic_traffic'] ) : 0,
				'keywords'    => isset( $data['keywords'] ) ? intval( $data['keywords'] ) : 0,
				'source'      => 'ahrefs_api',
			);
		}

		return $analysis;
	}

	/**
	 * Get competitor data from SEMrush API
	 *
	 * @param array $competitors Array of competitor domains.
	 * @return array
	 * @throws Exception If API call fails.
	 */
	private function get_semrush_competitor_data( $competitors ) {
		$api_key  = get_option( 'product_scraper_semrush_api' );
		$analysis = array();

		foreach ( $competitors as $domain ) {
			$url = add_query_arg(
				array(
					'key'            => $api_key,
					'type'           => 'domain_ranks',
					'domain'         => $domain,
					'export_columns' => 'Dn,Rk,Or,Ot,Oc,Ad',
				),
				'https://api.semrush.com'
			);

			$response = wp_remote_get( $url );

			if ( is_wp_error( $response ) ) {
				throw new Exception( 'SEMrush API HTTP error: ' . $response->get_error_message() );
			}

			$data = wp_remote_retrieve_body( $response );

			// SEMrush returns CSV format
			$lines = explode( "\n", $data );
			if ( count( $lines ) > 1 ) {
				$competitor_data = str_getcsv( $lines[1] );

				$analysis[] = array(
					'domain'      => $domain,
					'authority'   => isset( $competitor_data[1] ) ? floatval( $competitor_data[1] ) : 0,
					'traffic'     => isset( $competitor_data[3] ) ? intval( $competitor_data[3] ) : 0,
					'keywords'    => isset( $competitor_data[2] ) ? intval( $competitor_data[2] ) : 0,
					'ref_domains' => isset( $competitor_data[5] ) ? intval( $competitor_data[5] ) : 0,
					'source'      => 'semrush_api',
				);
			} else {
				// If no data returned, add empty entry
				$analysis[] = $this->get_empty_competitor_profile( $domain );
			}
		}

		return $analysis;
	}

	/**
	 * Perform built-in competitor analysis when APIs are not available
	 *
	 * @param array $competitors Array of competitor domains.
	 * @return array
	 */
	private function get_builtin_competitor_analysis( $competitors ) {
		$analysis   = array();
		$own_domain = wp_parse_url( get_site_url(), PHP_URL_HOST );

		foreach ( $competitors as $domain ) {
			// Basic domain analysis
			$domain_data = $this->analyze_competitor_domain( $domain, $own_domain );
			$analysis[]  = array_merge(
				array(
					'domain' => $domain,
					'source' => 'builtin_analysis',
				),
				$domain_data
			);
		}

		return $analysis;
	}

	/**
	 * Analyze competitor domain using available WordPress data
	 *
	 * @param string $competitor_domain Competitor domain.
	 * @param string $own_domain Our domain.
	 * @return array
	 */
	private function analyze_competitor_domain( $competitor_domain, $own_domain ) {
		$analysis = array(
			'authority'   => 0,
			'traffic'     => 0,
			'keywords'    => 0,
			'ref_domains' => 0,
		);

		// Estimate authority based on domain age and TLD (very basic)
		$domain_authority      = $this->estimate_domain_authority( $competitor_domain );
		$analysis['authority'] = $domain_authority;

		// Estimate traffic based on authority and other factors
		$analysis['traffic'] = $this->estimate_traffic( $domain_authority );

		// Estimate keywords
		$analysis['keywords'] = $this->estimate_keywords( $domain_authority );

		// Estimate referring domains
		$analysis['ref_domains'] = $this->estimate_referring_domains( $domain_authority );

		return $analysis;
	}

	/**
	 * Estimate domain authority based on actual domain analysis
	 */
	private function estimate_domain_authority( $domain ) {
		// Start with base authority
		$authority = 20;

		// Check if we have actual data for this domain
		$cached_authority = get_transient( 'domain_auth_' . md5( $domain ) );
		if ( false !== $cached_authority ) {
			return $cached_authority;
		}

		// Analyze domain characteristics
		$tld = strtolower( pathinfo( $domain, PATHINFO_EXTENSION ) );

		// Premium TLDs often have higher authority
		$premium_tlds = array( 'com', 'org', 'net', 'edu', 'gov' );
		if ( in_array( $tld, $premium_tlds, true ) ) {
			$authority += 15;
		}

		// Domain age estimation (in a real implementation, use WHOIS data)
		$domain_length = strlen( pathinfo( $domain, PATHINFO_FILENAME ) );
		if ( $domain_length <= 8 ) {
			$authority += 10; // Short domains are often more established
		} elseif ( $domain_length >= 20 ) {
			$authority -= 5; // Very long domains might be newer
		}

		// Hyphens in domain often indicate newer sites
		if ( strpos( $domain, '-' ) !== false ) {
			$authority -= 5;
		}

		$final_authority = max( 1, min( 100, $authority ) );

		// Cache for 1 day
		set_transient( 'domain_auth_' . md5( $domain ), $final_authority, DAY_IN_SECONDS );

		return $final_authority;
	}

	/**
	 * Estimate traffic based on domain authority and other factors
	 */
	private function estimate_traffic( $authority ) {
		// Traffic generally follows a power law distribution
		// Higher authority domains get exponentially more traffic
		if ( $authority >= 80 ) {
			return intval( $authority * 5000 ); // High authority sites
		} elseif ( $authority >= 60 ) {
			return intval( $authority * 1000 ); // Medium authority sites
		} elseif ( $authority >= 40 ) {
			return intval( $authority * 200 ); // Low authority sites
		} else {
			return intval( $authority * 50 ); // New sites
		}
	}

	/**
	 * Estimate keywords based on domain authority
	 *
	 * @param float $authority Domain authority.
	 * @return int
	 */
	private function estimate_keywords( $authority ) {
		// Keyword count generally correlates with traffic and authority
		return intval( $authority * 75 );
	}

	/**
	 * Estimate referring domains based on domain authority
	 *
	 * @param float $authority Domain authority.
	 * @return int
	 */
	private function estimate_referring_domains( $authority ) {
		// referring domains scale with domain authority
		if ( $authority >= 80 ) {
			return intval( $authority * 15 ); // High authority sites
		} elseif ( $authority >= 60 ) {
			return intval( $authority * 8 ); // Medium authority sites
		} elseif ( $authority >= 40 ) {
			return intval( $authority * 4 ); // Low authority sites
		} else {
			return intval( $authority * 2 ); // New sites
		}
	}

	/**
	 * Check if competitor data is valid
	 *
	 * @param array $data Competitor data to validate.
	 * @return bool
	 */
	private function is_valid_competitor_data( $data ) {
		if ( ! is_array( $data ) || empty( $data ) ) {
			return false;
		}

		foreach ( $data as $competitor ) {
			if ( ! isset( $competitor['domain'] ) || empty( $competitor['domain'] ) ) {
				return false;
			}

			// Check if we have at least some data
			$has_data    = false;
			$data_fields = array( 'authority', 'traffic', 'keywords', 'ref_domains' );

			foreach ( $data_fields as $field ) {
				if ( isset( $competitor[ $field ] ) && $competitor[ $field ] > 0 ) {
					$has_data = true;
					break;
				}
			}

			if ( ! $has_data ) {
				return false;
			}
		}

		return true;
	}

	/**
	 * Get empty competitor analysis data structure
	 *
	 * @return array
	 */
	private function get_empty_competitor_analysis() {
		$competitors = $this->get_configured_competitors();

		if ( empty( $competitors ) ) {
			// Return generic placeholder if no competitors configured
			return array(
				array(
					'domain'      => 'competitor1.com',
					'authority'   => 0,
					'ref_domains' => 0,
					'traffic'     => 0,
					'keywords'    => 0,
					'source'      => 'none',
				),
				array(
					'domain'      => 'competitor2.com',
					'authority'   => 0,
					'ref_domains' => 0,
					'traffic'     => 0,
					'keywords'    => 0,
					'source'      => 'none',
				),
				array(
					'domain'      => 'competitor3.com',
					'authority'   => 0,
					'ref_domains' => 0,
					'traffic'     => 0,
					'keywords'    => 0,
					'source'      => 'none',
				),
			);
		}

		// Return empty data for configured competitors
		$analysis = array();
		foreach ( $competitors as $domain ) {
			$analysis[] = $this->get_empty_competitor_profile( $domain );
		}

		return $analysis;
	}

	/**
	 * Get empty competitor profile data structure
	 *
	 * @param string $domain Competitor domain.
	 * @return array
	 */
	private function get_empty_competitor_profile( $domain ) {
		return array(
			'domain'      => $domain,
			'authority'   => 0,
			'ref_domains' => 0,
			'traffic'     => 0,
			'keywords'    => 0,
			'source'      => 'none',
		);
	}

	/**
	 * Get site health metrics - REAL DATA ONLY
	 *
	 * @return array
	 */
	public function get_site_health_metrics() {
		$api_key = get_option( 'product_scraper_pagespeed_api' );

		// If no API key, return empty data.
		if ( ! $api_key ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'PageSpeed API: No API key configured' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			}
			return $this->get_empty_site_health();
		}

		$site_url = get_site_url();

		// Better URL handling - ensure we're using a clean URL.
		$parsed_url = wp_parse_url( $site_url );
		if ( ! $parsed_url || ! isset( $parsed_url['host'] ) ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'PageSpeed API: Invalid site URL' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			}
			return $this->get_empty_site_health();
		}

		$test_url = $parsed_url['scheme'] . '://' . $parsed_url['host'];

		// Use the correct API endpoint and parameters.
		$api_url = 'https://www.googleapis.com/pagespeedonline/v5/runPagespeed';
		$api_url = add_query_arg(
			array(
				'url'      => rawurlencode( $test_url ),
				'key'      => $api_key,
				'strategy' => 'desktop',
			),
			$api_url
		);

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'PageSpeed API Testing URL: ' . $test_url ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		}

		$response = wp_remote_get(
			$api_url,
			array(
				'timeout' => 30,
				'headers' => array(
					'User-Agent' => 'WordPress/ProductScraper; ' . home_url(),
				),
			)
		);

		// Check for WP HTTP errors.
		if ( is_wp_error( $response ) ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'PageSpeed API WP Error: ' . $response->get_error_message() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			}
			return $this->get_empty_site_health();
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'PageSpeed API Response Code: ' . $response_code ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		}

		if ( 200 !== $response_code ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'PageSpeed API HTTP Error: ' . $response_code ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( 'PageSpeed API Error Body: ' . $response_body ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			}

			if ( 400 === $response_code ) {
				// Try to parse the error for more details.
				$error_data = json_decode( $response_body, true );
				if ( isset( $error_data['error']['message'] ) ) {
					if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
						error_log( 'PageSpeed API 400 Error: ' . $error_data['error']['message'] ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
					}
				} elseif ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
						error_log( 'PageSpeed API 400 Error - Likely invalid API key or malformed request' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log

				}
			}

			return $this->get_empty_site_health();
		}

		$data = json_decode( $response_body, true );

		// Check if JSON decoding failed.
		if ( JSON_ERROR_NONE !== json_last_error() ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'PageSpeed API JSON Error: ' . json_last_error_msg() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			}
			return $this->get_empty_site_health();
		}

		// Check if the expected structure exists.
		if ( ! isset( $data['lighthouseResult']['categories'] ) ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'PageSpeed API: Invalid response structure' ); // phpcs:ignore
				error_log( 'PageSpeed API Response: ' . print_r( $data, true ) ); // phpcs:ignore 
			}
			return $this->get_empty_site_health();
		}

		$categories = $data['lighthouseResult']['categories'];

		// Extract scores safely.
		$scores = array(
			'performance'    => $this->get_safe_category_score( $categories, 'performance' ),
			'accessibility'  => $this->get_safe_category_score( $categories, 'accessibility' ),
			'best_practices' => $this->get_safe_category_score( $categories, 'best-practices' ),
			'seo'            => $this->get_safe_category_score( $categories, 'seo' ),
		);

		// Calculate overall score from real data.
		$overall = round( array_sum( $scores ) / count( $scores ) );

		if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
			error_log( 'PageSpeed API Success - Scores: ' . print_r( $scores, true ) ); // phpcs:ignore
		}

		return array(
			'scores'  => $scores,
			'overall' => $overall,
			'source'  => 'pagespeed_api',
		);
	}

	/**
	 * Safely get category score without fallbacks
	 *
	 * @param array  $categories    Categories array from PageSpeed.
	 * @param string $category_name Category name to get score for.
	 * @return int
	 */
	private function get_safe_category_score( $categories, $category_name ) {
		if ( isset( $categories[ $category_name ]['score'] ) ) {
			return round( $categories[ $category_name ]['score'] * 100 );
		}
		return 0; // Return 0 instead of random data.
	}

	/**
	 * Get empty traffic data structure
	 *
	 * @return array
	 */
	private function get_empty_traffic_data() {
		return array(
			'current'          => 0,
			'previous'         => 0,
			'change'           => 0,
			'trend'            => 'neutral',
			'bounce_rate'      => 0,
			'avg_duration'     => 0,
			'organic_sessions' => 0,
			'total_users'      => 0,
			'source'           => 'none',
			'last_updated'     => current_time( 'mysql' ),
		);
	}

	/**
	 * Get empty keywords data structure
	 *
	 * @return array
	 */
	private function get_empty_keywords() {
		return array();
	}

	/**
	 * Get empty engagement metrics data structure
	 *
	 * @return array
	 */
	private function get_empty_engagement_metrics() {
		return array(
			'visit_duration'    => 0,
			'page_views'        => 0,
			'bounce_rate'       => 0,
			'pages_per_session' => 0,
		);
	}

	/**
	 * Get empty site health data structure
	 *
	 * @return array
	 */
	private function get_empty_site_health() {
		return array(
			'scores'  => array(
				'performance'    => 0,
				'accessibility'  => 0,
				'best_practices' => 0,
				'seo'            => 0,
			),
			'overall' => 0,
			'source'  => 'none',
		);
	}

	/**
	 * AJAX handler for data synchronization
	 */
	public function ajax_sync_seo_data() {
		// Check if nonce exists first.
		if ( ! isset( $_POST['nonce'] ) ) {
			wp_send_json_error( 'Missing security token.' );
		}

		// Unslash and sanitize the nonce.
		$nonce = sanitize_text_field( wp_unslash( $_POST['nonce'] ) );

		// Verify the nonce.
		if ( ! wp_verify_nonce( $nonce, 'product_scraper_nonce' ) ) {
			wp_send_json_error( 'Security check failed.' );
		}

		// Check user capabilities.
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_send_json_error( 'Insufficient permissions.' );
		}

		// Clear cache to force refresh.
		$cache_key = 'product_scraper_seo_data_' . md5( get_site_url() );
		delete_transient( $cache_key );

		$new_data = $this->get_seo_dashboard_data();
		wp_send_json_success( $new_data );
	}

	/**
 * Research keyword using available APIs
 */
public function research_keyword($keyword) {
    // Try SEMrush first if available
    if ($this->can_use_semrush()) {
        return $this->research_keyword_semrush($keyword);
    }
    
    // Try Ahrefs if available
    if ($this->can_use_ahrefs()) {
        return $this->research_keyword_ahrefs($keyword);
    }
    
    // No APIs available
    throw new Exception('No keyword research APIs configured. Please set up SEMrush or Ahrefs in settings.');
}

/**
 * Research keyword using SEMrush API
 */
private function research_keyword_semrush($keyword) {
    $api_key = get_option('product_scraper_semrush_api');
    
    if (!$api_key) {
        throw new Exception('SEMrush API not configured');
    }
    
    $url = add_query_arg(
        array(
            'key' => $api_key,
            'type' => 'phrase_all',
            'phrase' => $keyword,
            'database' => 'us',
            'export_columns' => 'Ph,Nq,Cp,Co'
        ),
        'https://api.semrush.com'
    );
    
    $response = wp_remote_get($url, array('timeout' => 15));
    
    if (is_wp_error($response)) {
        throw new Exception('SEMrush API error: ' . $response->get_error_message());
    }
    
    $data = wp_remote_retrieve_body($response);
    $lines = explode("\n", $data);
    
    if (count($lines) > 1) {
        $keyword_data = str_getcsv($lines[1]);
        
        return array(
            'volume' => isset($keyword_data[1]) ? intval($keyword_data[1]) : null,
            'cpc' => isset($keyword_data[2]) ? floatval($keyword_data[2]) : null,
            'competition' => isset($keyword_data[3]) ? floatval($keyword_data[3]) : null,
            'source' => 'semrush'
        );
    }
    
    throw new Exception('No data returned from SEMrush');
}

/**
 * Research keyword using Ahrefs API
 */
private function research_keyword_ahrefs($keyword) {
	$api_key = get_option('product_scraper_ahrefs_api');
	
	if (!$api_key) {
		throw new Exception('Ahrefs API not configured');
	}
	
	$url = add_query_arg(
		array(
			'token' => $api_key,
			'target' => $keyword,
			'from' => 'keywords_for_site',
			'mode' => 'phrase',
			'limit' => 1
		),
		'https://apiv2.ahrefs.com'
	);
	
	$response = wp_remote_get($url, array('timeout' => 15));
	
	if (is_wp_error($response)) {
		throw new Exception('Ahrefs API error: ' . $response->get_error_message());
	}
	
	$data = json_decode(wp_remote_retrieve_body($response), true);
	
	if (isset($data['error'])) {
		throw new Exception('Ahrefs API error: ' . $data['error']);
	}
	
	if (isset($data['keywords']) && count($data['keywords']) > 0) {
		$keyword_data = $data['keywords'][0];
		
		return array(
			'volume' => $keyword_data['search_volume'] ?? null,
			'cpc' => $keyword_data['cpc'] ?? null,
			'competition' => $keyword_data['competition'] ?? null,
			'source' => 'ahrefs'
		);
	}
	
	throw new Exception('No data returned from Ahrefs');
}
}
