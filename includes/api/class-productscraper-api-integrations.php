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
	 * Get referring domains count - REAL DATA ONLY
	 *
	 * @return array
	 */
	public function get_referring_domains() {
		$api_key = get_option( 'product_scraper_ahrefs_api' );
		$target  = wp_parse_url( get_site_url(), PHP_URL_HOST );

		if ( ! $api_key ) {
			return $this->get_empty_referring_domains();
		}

		$url = "https://apiv2.ahrefs.com?token={$api_key}&target={$target}&from=domain_rating&mode=domain";

		$response = wp_remote_get( $url );
		if ( is_wp_error( $response ) ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'Ahrefs API HTTP error: ' . $response->get_error_message() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			}
			return $this->get_empty_referring_domains();
		}

		$data = json_decode( wp_remote_retrieve_body( $response ), true );

		// Handle API errors or empty responses.
		if ( isset( $data['error'] ) ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				error_log( 'Ahrefs API error: ' . $data['error'] ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			}
			return $this->get_empty_referring_domains();
		}

		$refdomains    = isset( $data['refdomains'] ) ? intval( $data['refdomains'] ) : 0;
		$domain_rating = isset( $data['domain_rating'] ) ? floatval( $data['domain_rating'] ) : 0;

		return array(
			'count'         => $refdomains,
			'domain_rating' => $domain_rating,
			'trend'         => 'neutral',
			'change'        => 0,
			'source'        => 'ahrefs_api',
		);
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
		$api_key = get_option( 'product_scraper_ahrefs_api' );

		if ( ! $api_key ) {
			return $this->get_empty_competitor_analysis();
		}

		$competitors = array( 'competitor1.com', 'competitor2.com', 'competitor3.com' );
		$analysis    = array();

		foreach ( $competitors as $domain ) {
			$url      = "https://apiv2.ahrefs.com?token={$api_key}&target={$domain}&from=domain_rating&mode=domain";
			$response = wp_remote_get( $url );

			if ( is_wp_error( $response ) ) {
				$analysis[] = $this->get_empty_competitor_profile( $domain );
				continue;
			}

			$data = json_decode( wp_remote_retrieve_body( $response ), true );

			if ( isset( $data['error'] ) ) {
				$analysis[] = $this->get_empty_competitor_profile( $domain );
				continue;
			}

			$analysis[] = array(
				'domain'      => $domain,
				'authority'   => isset( $data['domain_rating'] ) ? floatval( $data['domain_rating'] ) : 0,
				'ref_domains' => isset( $data['refdomains'] ) ? intval( $data['refdomains'] ) : 0,
				'traffic'     => isset( $data['traffic'] ) ? intval( $data['traffic'] ) : 0,
				'source'      => 'ahrefs_api',
			);
		}

		return $analysis;
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
	 * Get empty referring domains data structure
	 *
	 * @return array
	 */
	private function get_empty_referring_domains() {
		return array(
			'count'         => 0,
			'domain_rating' => 0,
			'trend'         => 'neutral',
			'change'        => 0,
			'source'        => 'none',
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
	 * Get empty competitor analysis data structure
	 *
	 * @return array
	 */
	private function get_empty_competitor_analysis() {
		return array(
			array(
				'domain'      => 'competitor1.com',
				'authority'   => 0,
				'ref_domains' => 0,
				'traffic'     => 0,
				'source'      => 'none',
			),
			array(
				'domain'      => 'competitor2.com',
				'authority'   => 0,
				'ref_domains' => 0,
				'traffic'     => 0,
				'source'      => 'none',
			),
			array(
				'domain'      => 'competitor3.com',
				'authority'   => 0,
				'ref_domains' => 0,
				'traffic'     => 0,
				'source'      => 'none',
			),
		);
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
			'source'      => 'none',
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
}
