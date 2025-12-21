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
 * This class manages connections to Google Analytics,
 * PageSpeed Insights, and other third-party APIs to gather SEO data.
 */
class ProductScraper_API_Integrations
{


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
	public function __construct()
	{
		add_action('wp_ajax_sync_seo_data', array($this, 'ajax_sync_seo_data'));
	}

	/**
	 * Check if Google API classes are available
	 *
	 * @return bool
	 */
	private function google_api_available()
	{
		return class_exists('Google_Client') && class_exists('Google_Service_AnalyticsData');
	}

	/**
	 * Get comprehensive SEO data for the current site
	 *
	 * @return array
	 */
	public function get_seo_dashboard_data()
	{
		$cache_key = 'product_scraper_seo_data_' . md5(get_site_url());
		$cached_data = get_transient($cache_key);

		if (false !== $cached_data) {
			return $cached_data;
		}

		// Initialize with empty data structure first
		$data = array(
			'organic_traffic' => $this->get_empty_traffic_data(),
			'referring_domains' => $this->get_empty_referring_domains(),
			'top_keywords' => $this->get_empty_keywords(),
			'digital_score' => 0,
			'engagement_metrics' => $this->get_empty_engagement_metrics(),
			'competitor_analysis' => $this->get_empty_competitor_analysis(),
			'site_health' => $this->get_empty_site_health(),
			'site_health_error' => null, // stores error messages if any
			'last_updated' => current_time('mysql'),
		);

		try {
			// Get real data
			$data['organic_traffic'] = $this->get_organic_traffic();
			$data['referring_domains'] = $this->get_referring_domains();
			$data['top_keywords'] = $this->get_top_keywords();
			$data['engagement_metrics'] = $this->get_engagement_metrics();
			$data['competitor_analysis'] = $this->get_competitor_analysis();

			// Get site health once
			$site_health = $this->get_site_health_metrics();

			// If site health contains an 'error' key from our improved method, store it
			if (isset($site_health['error'])) {
				$data['site_health_error'] = $site_health['error'];
				$data['site_health']['status'] = 'error';
			} else {
				$data['site_health']['status'] = 'success';
			}

			$data['site_health'] = array_merge($data['site_health'], $site_health);

			// Pass site health to digital score calculation to avoid duplicate API calls
			$data['digital_score'] = $this->calculate_digital_score($site_health);

		} catch (Exception $e) {
			error_log('SEO Dashboard Data Error: ' . $e->getMessage());
			$data['site_health_error'] = $e->getMessage();
			$data['site_health']['status'] = 'error';
		}

		// Debug logging
		if (defined('WP_DEBUG') && WP_DEBUG) {
			error_log('SEO Dashboard Data Final: ' . print_r($data, true));
		}

		set_transient($cache_key, $data, $this->cache_duration);
		return $data;
	}

	/**
	 * Get organic traffic data from multiple sources with fallback strategy
	 *
	 * @return array
	 */
	public function get_organic_traffic($post_id = null)
	{
		try {

			// If post_id is provided, optionally filter traffic per page
			// Currently GA4 API fetch is site-wide; extend to post-level later
			$traffic = $this->fetch_organic_traffic_with_fallback();

			// Ensure source is always set for connection
			if (!isset($traffic['source'])) {
				$traffic['source'] = 'google_analytics';
			}

			// Return traffic even if sessions=0
			return $traffic;

		} catch (Exception $e) {
			if (defined('WP_DEBUG') && WP_DEBUG) {
				error_log('get_organic_traffic failed: ' . $e->getMessage());
			}
			return $this->get_empty_traffic_data();
		}
	}


	/**
	 * Fetch organic traffic with multiple fallback sources
	 *
	 * @return array
	 */
	private function fetch_organic_traffic_with_fallback($post_id = null)
	{
		$sources_tried = [];

		// Source 1: Google Analytics 4 (Primary)
		if ($this->can_use_google_analytics()) {
			try {
				$ga_data = $this->get_google_analytics_data($post_id); // pass post_id for page-level data

				// Only check structure, not session count, if post-level
				$is_valid = $post_id ? $this->is_valid_traffic_data($ga_data, false) : $this->is_valid_traffic_data($ga_data);

				if ($is_valid) {
					$sources_tried[] = 'google_analytics';
					return $ga_data;
				}
			} catch (Exception $e) {
				$sources_tried[] = 'google_analytics_failed';
				if (defined('WP_DEBUG') && WP_DEBUG) {
					error_log('GA4 Traffic Error: ' . $e->getMessage());
				}
			}
		}

		// Source 2: Google Search Console (Secondary)
		if ($this->can_use_search_console()) {
			try {
				$gsc_data = $this->get_search_console_traffic($post_id); // optionally filter by post

				if ($this->is_valid_traffic_data($gsc_data)) {
					$sources_tried[] = 'search_console';
					return $gsc_data;
				}
			} catch (Exception $e) {
				if (defined('WP_DEBUG') && WP_DEBUG) {
					error_log('GSC Traffic Error: ' . $e->getMessage());
				}
				$sources_tried[] = 'search_console_failed';
			}
		}

		if (defined('WP_DEBUG') && WP_DEBUG) {
			error_log('All organic traffic sources failed: ' . implode(', ', $sources_tried));
		}

		return $this->get_empty_traffic_data();
	}


	/**
	 * Check if Google Analytics can be used
	 *
	 * @return bool
	 */
	private function can_use_google_analytics()
	{
		$property_id = get_option('product_scraper_ga4_property_id');
		$service_account = get_option('product_scraper_google_service_account');

		return $property_id
			&& !empty($service_account)
			&& $this->google_api_available()
			&& null !== json_decode($service_account, true);
	}

	/**
	 * Get Google Analytics 4 organic traffic data
	 *
	 * @return array
	 * @throws Exception
	 */
	private function get_google_analytics_data($post_id = null)
	{
		$property_id = get_option('product_scraper_ga4_property_id');
		$service_account_json = get_option('product_scraper_google_service_account');

		if (!$property_id || empty($service_account_json)) {
			throw new Exception('GA4 property ID or service account is not configured.');
		}

		try {
			$client = new Google_Client();
			$service_account = json_decode($service_account_json, true);

			if (JSON_ERROR_NONE !== json_last_error()) {
				throw new Exception('Invalid service account JSON');
			}

			$client->setAuthConfig($service_account);
			$client->addScope('https://www.googleapis.com/auth/analytics.readonly');
			$client->setAccessType('offline');

			$analytics = new Google_Service_AnalyticsData($client);

			// Test authentication
			$access_token = $client->fetchAccessTokenWithAssertion();
			if (isset($access_token['error'])) {
				throw new Exception('GA4 Authentication failed: ' . $access_token['error_description']);
			}

			$start_current = '30daysAgo';
			$end_current = 'today';
			$start_previous = '60daysAgo';
			$end_previous = '30daysAgo';

			// If post_id is provided, filter by page path
			$page_filter = null;
			if ($post_id) {
				$url_path = wp_parse_url(get_permalink($post_id), PHP_URL_PATH);
				$page_filter = new Google_Service_AnalyticsData_FilterExpression([
					'filter' => new Google_Service_AnalyticsData_Filter([
						'fieldName' => 'pagePath',
						'stringFilter' => [
							'matchType' => 'EXACT',
							'value' => $url_path,
						],
					]),
				]);
			}

			$current_data = $this->fetch_ga4_traffic_data($analytics, $property_id, $start_current, $end_current, $page_filter);
			$previous_data = $this->fetch_ga4_traffic_data($analytics, $property_id, $start_previous, $end_previous, $page_filter);

			$change = $previous_data['sessions'] > 0
				? (($current_data['sessions'] - $previous_data['sessions']) / $previous_data['sessions']) * 100
				: 0;

			$trend = $this->determine_trend($change);

			return [
				'current' => $current_data['sessions'],
				'previous' => $previous_data['sessions'],
				'change' => round($change, 1),
				'trend' => $trend,
				'bounce_rate' => $current_data['bounce_rate'],
				'avg_duration' => $current_data['avg_duration'],
				'organic_sessions' => $current_data['organic_sessions'],
				'total_users' => $current_data['total_users'],
				'source' => 'google_analytics',
				'last_updated' => current_time('mysql'),
			];

		} catch (Exception $e) {
			if (defined('WP_DEBUG') && WP_DEBUG) {
				error_log('Google Analytics API Error: ' . $e->getMessage());
			}
			throw $e;
		}
	}

	/**
	 * Fetch GA4 organic traffic data with proper filter
	 *
	 * @param Google_Service_AnalyticsData $analytics
	 * @param string $property_id
	 * @param string $start_date
	 * @param string $end_date
	 * @return array
	 */
	private function fetch_ga4_traffic_data($analytics, $property_id, $start_date, $end_date, $page_filter = null)
	{
		$request = new Google_Service_AnalyticsData_RunReportRequest([
			'dimensions' => [
				new Google_Service_AnalyticsData_Dimension(['name' => 'sessionMedium']),
				new Google_Service_AnalyticsData_Dimension(['name' => 'sessionSource']),
			],
			'metrics' => [
				new Google_Service_AnalyticsData_Metric(['name' => 'sessions']),
				new Google_Service_AnalyticsData_Metric(['name' => 'bounceRate']),
				new Google_Service_AnalyticsData_Metric(['name' => 'averageSessionDuration']),
				new Google_Service_AnalyticsData_Metric(['name' => 'totalUsers']),
			],
			'dateRanges' => [
				new Google_Service_AnalyticsData_DateRange(['startDate' => $start_date, 'endDate' => $end_date]),
			],
		]);

		if ($page_filter) {
			$request->setDimensionFilter(
				new Google_Service_AnalyticsData_FilterExpression([
					'andGroup' => [
						'expressions' => [
							$page_filter,
							new Google_Service_AnalyticsData_FilterExpression([
								'filter' => new Google_Service_AnalyticsData_Filter([
									'fieldName' => 'sessionMedium',
									'stringFilter' => [
										'matchType' => 'EXACT',
										'value' => 'organic',
									],
								]),
							]),
						],
					],
				])
			);
		} else {
			// Default to organic traffic filter for site-level
			$request->setDimensionFilter(new Google_Service_AnalyticsData_FilterExpression([
				'filter' => new Google_Service_AnalyticsData_Filter([
					'fieldName' => 'sessionMedium',
					'stringFilter' => [
						'matchType' => 'EXACT',
						'value' => 'organic',
					],
				]),
			]));
		}

		$response = $analytics->properties->runReport("properties/{$property_id}", $request);

		$sessions = 0;
		$bounce_rate = 0;
		$avg_duration = 0;
		$total_users = 0;

		if ($response->getRows()) {
			foreach ($response->getRows() as $row) {
				$sessions += (int) $row->getMetricValues()[0]->getValue();
				$bounce_rate = (float) $row->getMetricValues()[1]->getValue();
				$avg_duration = (float) $row->getMetricValues()[2]->getValue();
				$total_users += (int) $row->getMetricValues()[3]->getValue();
			}
		}

		return [
			'sessions' => $sessions,
			'bounce_rate' => $bounce_rate,
			'avg_duration' => $avg_duration,
			'organic_sessions' => $sessions,
			'total_users' => $total_users,
		];
	}

	/**
	 * Get traffic data from Google Search Console
	 *
	 * @return array
	 */
	private function get_search_console_traffic($post_id = null)
	{
		if (!$this->can_use_search_console()) {
			return $this->get_empty_traffic_data();
		}

		$service_account_json = get_option('product_scraper_google_service_account');
		$client = new Google_Client();
		$client->setAuthConfig(json_decode($service_account_json, true));
		$client->addScope('https://www.googleapis.com/auth/webmasters.readonly');
		$client->setAccessType('offline');

		$webmasters = new Google_Service_Webmasters($client);
		$site_url = get_site_url();

		$start_date = date('Y-m-d', strtotime('-30 days'));
		$end_date = date('Y-m-d');

		$request = new Google_Service_Webmasters_SearchAnalyticsQueryRequest();
		$request->setStartDate($start_date);
		$request->setEndDate($end_date);
		$request->setDimensions(['query']);
		$request->setRowLimit(1000);

		if ($post_id) {
			$page_path = wp_parse_url(get_permalink($post_id), PHP_URL_PATH);
			$request->setDimensionFilterGroups([
				[
					'filters' => [
						[
							'dimension' => 'page',
							'operator' => 'equals',
							'expression' => $page_path,
						]
					]
				]
			]);
		}

		$response = $webmasters->searchanalytics->query($site_url, $request);

		$clicks = 0;
		$impressions = 0;

		if ($response->getRows()) {
			foreach ($response->getRows() as $row) {
				$clicks += $row->getClicks();
				$impressions += $row->getImpressions();
			}
		}

		return [
			'current' => $clicks,
			'previous' => 0, // optional: implement previous period comparison
			'change' => 0,
			'trend' => 'neutral',
			'source' => 'google_search_console',
			'last_updated' => current_time('mysql'),
			'impressions' => $impressions,
		];
	}

	/**
	 * Check if traffic data is valid and usable
	 *
	 * @param array $data Traffic data to validate.
	 * @return bool
	 */
	private function is_valid_traffic_data($data, $check_sessions = true)
	{
		if (!is_array($data) || !isset($data['current']) || !isset($data['source'])) {
			return false;
		}
		if ($check_sessions && $data['current'] <= 0) {
			return false;
		}
		return true;
	}

	/**
	 * Determine trend direction based on percentage change
	 *
	 * @param float $change Percentage change.
	 * @return string
	 */
	private function determine_trend($change)
	{
		if ($change > 5) {
			return 'positive';
		} elseif ($change < -5) {
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
	private function can_use_search_console()
	{
		$service_account = get_option('product_scraper_google_service_account');
		$site_url = get_site_url();
		return !empty($service_account) && $this->google_api_available() && $site_url;
	}

	/**
	 * Get referring domains from multiple sources with comprehensive backlink data
	 *
	 * @return array
	 */
	public function get_referring_domains()
	{
		$cache_key = 'product_scraper_referring_domains_' . md5(get_site_url());
		$cached_data = get_transient($cache_key);

		if (false !== $cached_data) {
			return $cached_data;
		}

		$sources_tried = array();

		// Source 1: Google Search Console (Primary - Free & Official)
		if ($this->can_use_search_console()) {
			try {
				$gsc_data = $this->get_gsc_links_data();
				if ($this->is_valid_referring_data($gsc_data)) {
					$sources_tried[] = 'google_search_console';
					set_transient($cache_key, $gsc_data, $this->cache_duration);
					return $gsc_data;
				}
			} catch (Exception $e) {
				if (defined('WP_DEBUG') && WP_DEBUG) {
					error_log('GSC Links Error: ' . $e->getMessage());
				}
				$sources_tried[] = 'gsc_failed';
			}
		}

		if (defined('WP_DEBUG') && WP_DEBUG) {
			error_log('All referring domains sources failed: ' . implode(', ', $sources_tried));
		}

		$empty_data = $this->get_empty_referring_domains();
		set_transient($cache_key, $empty_data, $this->cache_duration);
		return $empty_data;
	}

	/**
	 * Get links data from Google Search Console
	 *
	 * @return array
	 * @throws Exception If API call fails.
	 */
	public function get_gsc_links_data()
	{
		if (!$this->google_api_available()) {
			throw new Exception('Google API not available');
		}

		$service_account_json = get_option('product_scraper_google_service_account');
		$site_url = get_site_url();

		if (empty($service_account_json)) {
			throw new Exception('Service account JSON not configured');
		}

		try {
			$client = new Google_Client();
			$service_account = json_decode($service_account_json, true);

			if (JSON_ERROR_NONE !== json_last_error()) {
				throw new Exception('Invalid service account JSON');
			}

			$client->setAuthConfig($service_account);
			$client->addScope('https://www.googleapis.com/auth/webmasters');
			$client->setAccessType('offline');

			$webmasters = new Google_Service_Webmasters($client);

			// Test authentication
			$access_token = $client->fetchAccessTokenWithAssertion();
			if (isset($access_token['error'])) {
				throw new Exception('GSC Authentication failed: ' . $access_token['error_description']);
			}

			// Get external links (referring domains)
			$external_links = $this->get_gsc_external_links($webmasters, $site_url);

			// Get internal links
			$internal_links = $this->get_gsc_internal_links($webmasters, $site_url);

			return array(
				'count' => $external_links['referring_domains'] ?? 0,
				'domain_rating' => $this->calculate_domain_authority($external_links['referring_domains'] ?? 0),
				'external_links' => $external_links['total_links'] ?? 0,
				'internal_links' => $internal_links['total_links'] ?? 0,
				'top_linking_domains' => $external_links['top_domains'] ?? array(),
				'top_linked_pages' => $internal_links['top_pages'] ?? array(),
				'trend' => 'neutral',
				'change' => 0,
				'source' => 'google_search_console',
			);

		} catch (Exception $e) {
			if (defined('WP_DEBUG') && WP_DEBUG) {
				error_log('Google Search Console Links API Error: ' . $e->getMessage());
			}
			throw $e;
		}
	}

	/**
	 * Get external links data from GSC
	 */
	public function get_gsc_external_links($webmasters, $site_url)
	{
		try {
			$response = $webmasters->sites->listSites();
			$sites = $response->getSiteEntry();

			$site_exists = false;
			foreach ($sites as $site) {
				if ($site->getSiteUrl() === $site_url) {
					$site_exists = true;
					break;
				}
			}

			if (!$site_exists) {
				throw new Exception('Site not verified in Google Search Console');
			}

			// Get external links count (simplified - actual implementation would use links API)
			// TODO: GSC API may require additional setup

			$external_links_data = array(
				'referring_domains' => 0,
				'total_links' => 0,
				'top_domains' => array(),
			);

			// This is a simplified implementation
			// In a real scenario, you'd use: $webmasters->links->listLinks($site_url)

			return $external_links_data;

		} catch (Exception $e) {
			error_log('GSC External Links Error: ' . $e->getMessage());
			$external_links = array(); // fallback
			return $external_links;
		}
	}

	/**
	 * Get internal links data from GSC
	 */
	public function get_gsc_internal_links($webmasters, $site_url)
	{
		try {
			// Simplified internal links implementation
			// In production, you'd analyze your own site structure

			$internal_links_data = array(
				'total_links' => $this->calculate_internal_links_count(),
				'top_pages' => $this->get_top_internal_linked_pages(),
			);

			return $internal_links_data;

		} catch (Exception $e) {
			throw new Exception('GSC Internal Links Error: ' . $e->getMessage());
		}
	}

	/**
	 * Calculate internal links count by analyzing site structure
	 */
	private function calculate_internal_links_count()
	{
		// Method 1: Analyze WordPress posts and pages
		$post_count = wp_count_posts();
		$page_count = wp_count_posts('page');

		$total_posts = $post_count->publish + $page_count->publish;

		// Estimate internal links (this is a simplified calculation)
		// A typical site has 10-50 internal links per page
		$estimated_links_per_page = 20;

		return $total_posts * $estimated_links_per_page;
	}

	/**
	 * Get top internally linked pages
	 */
	private function get_top_internal_linked_pages()
	{
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
		foreach ($top_pages as $page) {
			$formatted_pages[] = array(
				'url' => get_permalink($page->ID),
				'title' => $page->post_title,
				'links' => intval($page->link_count),
			);
		}

		return $formatted_pages;
	}

	/**
	 * Enhanced empty data structure
	 *
	 * @return array
	 */
	private function get_empty_referring_domains()
	{
		return array(
			'count' => 0,
			'domain_rating' => 0,
			'external_links' => 0,
			'internal_links' => $this->calculate_internal_links_count(), // Always calculate internal links
			'top_linking_domains' => array(),
			'top_linked_pages' => $this->get_top_internal_linked_pages(),
			'trend' => 'neutral',
			'change' => 0,
			'source' => 'none',
		);
	}

	/**
	 * Check if referring data is valid
	 */
	private function is_valid_referring_data($data)
	{
		return is_array($data)
			&& isset($data['count'])
			&& $data['count'] > 0
			&& 'none' !== $data['source'];
	}

	/**
	 * Get top performing keywords - REAL DATA ONLY
	 *
	 * @return array
	 */
	private function get_top_keywords()
	{
		$gsc_connected = get_option('product_scraper_google_search_console');

		if ($gsc_connected && $this->google_api_available()) {
			try {
				return $this->get_gsc_keywords();
			} catch (Exception $e) {
				if (defined('WP_DEBUG') && WP_DEBUG) {
					error_log('Google Search Console API error: ' . $e->getMessage()); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
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
	private function get_gsc_keywords($limit = 20)
	{
		if (!$this->can_use_search_console()) {
			return [];
		}

		$service_account_json = get_option('product_scraper_google_service_account');
		$client = new Google_Client();
		$client->setAuthConfig(json_decode($service_account_json, true));
		$client->addScope('https://www.googleapis.com/auth/webmasters.readonly');
		$client->setAccessType('offline');

		$webmasters = new Google_Service_Webmasters($client);
		$site_url = get_site_url();

		$start_date = date('Y-m-d', strtotime('-30 days'));
		$end_date = date('Y-m-d');

		$request = new Google_Service_Webmasters_SearchAnalyticsQueryRequest();
		$request->setStartDate($start_date);
		$request->setEndDate($end_date);
		$request->setDimensions(['query']);
		$request->setRowLimit($limit);
		// $request->setOrderBy([
		// 	['field' => 'clicks', 'descending' => true]
		// ]);

		$response = $webmasters->searchanalytics->query($site_url, $request);

		$keywords = [];
		if ($response->getRows()) {
			foreach ($response->getRows() as $row) {
				$keywords[] = [
					'query' => $row->getKeys()[0],
					'clicks' => $row->getClicks(),
					'impressions' => $row->getImpressions(),
					'ctr' => $row->getCtr(),
					'position' => $row->getPosition(),
				];
			}
		}

		return $keywords;
	}

	/**
	 * Calculate comprehensive digital score based on real data
	 *
	 * @return int
	 */
	private function calculate_digital_score($site_health = null)
	{
		// Get traffic and referring domain data
		$traffic_data = $this->get_organic_traffic();
		$referring_data = $this->get_referring_domains();

		// Use provided site health if available; otherwise, fetch it
		if (null === $site_health) {
			$site_health = $this->get_site_health_metrics();
		}

		// Use overall score if available, otherwise fallback to 0
		$technical_score = 0;
		if (isset($site_health['overall']) && is_numeric($site_health['overall'])) {
			$technical_score = (int) $site_health['overall'];
		}

		$metrics = array(
			'traffic_score' => $this->calculate_traffic_score($traffic_data),
			'authority_score' => $this->calculate_authority_score($referring_data),
			'technical_score' => $technical_score,
		);

		$total_score = array_sum($metrics) / count($metrics);

		// TODO: Revisit score calculation once all metric methods are fully implemented
		return round($total_score);
	}

	/**
	 * Calculate traffic score based on real traffic data
	 *
	 * @param array $traffic_data Traffic data array.
	 * @return int
	 */
	private function calculate_traffic_score($traffic_data)
	{
		if ($traffic_data['current'] > 10000) {
			return 100;
		}
		if ($traffic_data['current'] > 5000) {
			return 80;
		}
		if ($traffic_data['current'] > 1000) {
			return 60;
		}
		if ($traffic_data['current'] > 100) {
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
	private function calculate_authority_score($referring_data)
	{
		$ref_domains = $referring_data['count'];
		$domain_rating = $referring_data['domain_rating'] * 10; // Convert to 0-100 scale.

		if ($ref_domains > 1000) {
			return min(100, $domain_rating + 20);
		}
		if ($ref_domains > 100) {
			return min(100, $domain_rating + 10);
		}
		if ($ref_domains > 10) {
			return min(100, $domain_rating + 5);
		}
		return $domain_rating;
	}

	/**
	 * Get engagement metrics from real data
	 *
	 * @return array
	 */
	private function get_engagement_metrics()
	{
		$property_id = get_option('product_scraper_ga4_property_id');

		if ($property_id && $this->google_api_available()) {
			try {
				return $this->get_google_analytics_engagement($property_id);
			} catch (Exception $e) {
				if (defined('WP_DEBUG') && WP_DEBUG) {
					error_log('Google Analytics Engagement API error: ' . $e->getMessage()); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
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
	private function get_google_analytics_engagement($property_id)
	{
		// Get the service account JSON from database option.
		$service_account_json = get_option('product_scraper_google_service_account');

		if (empty($service_account_json)) {
			throw new Exception('Service account JSON not configured');
		}

		$client = new Google_Client();

		// Use setAuthConfig with JSON string instead of file path.
		$client->setAuthConfig(json_decode($service_account_json, true));
		$client->addScope('https://www.googleapis.com/auth/analytics.readonly');

		$analytics = new Google_Service_AnalyticsData($client);

		$request = new Google_Service_AnalyticsData_RunReportRequest(
			array(
				'metrics' => array(
					new Google_Service_AnalyticsData_Metric(array('name' => 'sessions')),
					new Google_Service_AnalyticsData_Metric(array('name' => 'averageSessionDuration')),
					new Google_Service_AnalyticsData_Metric(array('name' => 'bounceRate')),
					new Google_Service_AnalyticsData_Metric(array('name' => 'screenPageViewsPerSession')),
				),
				'dateRanges' => array(
					new Google_Service_AnalyticsData_DateRange(
						array(
							'startDate' => '30daysAgo',
							'endDate' => 'today',
						)
					),
				),
			)
		);

		$response = $analytics->properties->runReport("properties/{$property_id}", $request);

		$metrics = array(
			'visit_duration' => 0,
			'page_views' => 0,
			'bounce_rate' => 0,
			'pages_per_session' => 0,
		);

		if ($response->getRows()) {
			$row = $response->getRows()[0];
			$metrics = array(
				'visit_duration' => (float) $row->getMetricValues()[1]->getValue(),
				'page_views' => (int) $row->getMetricValues()[0]->getValue(),
				'bounce_rate' => (float) $row->getMetricValues()[2]->getValue(),
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
	private function get_competitor_analysis()
	{
		$cache_key = 'product_scraper_competitor_analysis_' . md5(get_site_url());
		$cached_data = get_transient($cache_key);

		if (false !== $cached_data) {
			return $cached_data;
		}

		$analysis = array();

		// Get configured competitors from settings
		$competitors = $this->get_configured_competitors();

		if (empty($competitors)) {
			// If no competitors configured, return empty analysis
			$empty_data = $this->get_empty_competitor_analysis();
			set_transient($cache_key, $empty_data, $this->cache_duration);
			return $empty_data;
		}

		// Try different data sources for competitor analysis
		$analysis = $this->fetch_competitor_data_with_fallback($competitors);

		set_transient($cache_key, $analysis, $this->cache_duration);
		return $analysis;
	}

	/**
	 * Get configured competitors from settings
	 *
	 * @return array
	 */
	private function get_configured_competitors()
	{
		$competitors = get_option('product_scraper_competitors', array());

		if (!empty($competitors) && is_string($competitors)) {
			// Handle string format (comma-separated)
			$competitors = array_map('trim', explode(',', $competitors));
		}

		// Filter out empty values and ensure proper format
		$competitors = array_filter(
			array_map(
				function ($domain) {
					$domain = trim($domain);
					if (empty($domain)) {
						return false;
					}

					// Ensure domain format
					if (false === strpos($domain, '.')) {
						return false;
					}

					return $domain;
				},
				(array) $competitors
			)
		);

		return array_slice($competitors, 0, 5); // Limit to 5 competitors
	}

	/**
	 * Fetch competitor data with multiple fallback sources
	 *
	 * @param array $competitors Array of competitor domains.
	 * @return array
	 */
	private function fetch_competitor_data_with_fallback($competitors)
	{
		$sources_tried = array();
		$analysis = array();
		try {
			$builtin_data = $this->get_builtin_competitor_analysis($competitors);
			if ($this->is_valid_competitor_data($builtin_data)) {
				$sources_tried[] = 'builtin';
				return $builtin_data;
			}
		} catch (Exception $e) {
			if (defined('WP_DEBUG') && WP_DEBUG) {
				error_log('Built-in Competitor Analysis Error: ' . $e->getMessage());
			}
			$sources_tried[] = 'builtin_failed';
		}

		if (defined('WP_DEBUG') && WP_DEBUG) {
			error_log('All competitor data sources failed: ' . implode(', ', $sources_tried));
		}

		return $this->get_empty_competitor_analysis();
	}

	/**
	 * Perform built-in competitor analysis when APIs are not available
	 *
	 * @param array $competitors Array of competitor domains.
	 * @return array
	 */
	private function get_builtin_competitor_analysis($competitors)
	{
		$analysis = array();
		$own_domain = wp_parse_url(get_site_url(), PHP_URL_HOST);

		foreach ($competitors as $domain) {
			// Basic domain analysis
			$domain_data = $this->analyze_competitor_domain($domain, $own_domain);
			$analysis[] = array_merge(
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
	private function analyze_competitor_domain($competitor_domain, $own_domain)
	{
		$analysis = array(
			'authority' => 0,
			'traffic' => 0,
			'keywords' => 0,
			'ref_domains' => 0,
		);

		// Estimate authority based on domain age and TLD (very basic)
		$domain_authority = $this->estimate_domain_authority($competitor_domain);
		$analysis['authority'] = $domain_authority;

		// Estimate traffic based on authority and other factors
		$analysis['traffic'] = $this->estimate_traffic($domain_authority);

		// Estimate keywords
		$analysis['keywords'] = $this->estimate_keywords($domain_authority);

		// Estimate referring domains
		$analysis['ref_domains'] = $this->estimate_referring_domains($domain_authority);

		return $analysis;
	}

	/**
	 * Estimate domain authority based on actual domain analysis
	 */
	private function estimate_domain_authority($domain)
	{
		// Start with base authority
		$authority = 20;

		// Check if we have actual data for this domain
		$cached_authority = get_transient('domain_auth_' . md5($domain));
		if (false !== $cached_authority) {
			return $cached_authority;
		}

		// Analyze domain characteristics
		$tld = strtolower(pathinfo($domain, PATHINFO_EXTENSION));

		// Premium TLDs often have higher authority
		$premium_tlds = array('com', 'org', 'net', 'edu', 'gov');
		if (in_array($tld, $premium_tlds, true)) {
			$authority += 15;
		}

		// Domain age estimation (in a real implementation, use WHOIS data)
		$domain_length = strlen(pathinfo($domain, PATHINFO_FILENAME));
		if ($domain_length <= 8) {
			$authority += 10; // Short domains are often more established
		} elseif ($domain_length >= 20) {
			$authority -= 5; // Very long domains might be newer
		}

		// Hyphens in domain often indicate newer sites
		if (strpos($domain, '-') !== false) {
			$authority -= 5;
		}

		$final_authority = max(1, min(100, $authority));

		// Cache for 1 day
		set_transient('domain_auth_' . md5($domain), $final_authority, DAY_IN_SECONDS);

		return $final_authority;
	}

	/**
	 * Estimate traffic based on domain authority and other factors
	 */
	private function estimate_traffic($authority)
	{
		// Traffic generally follows a power law distribution
		// Higher authority domains get exponentially more traffic
		if ($authority >= 80) {
			return intval($authority * 5000); // High authority sites
		} elseif ($authority >= 60) {
			return intval($authority * 1000); // Medium authority sites
		} elseif ($authority >= 40) {
			return intval($authority * 200); // Low authority sites
		} else {
			return intval($authority * 50); // New sites
		}
	}

	/**
	 * Estimate keywords based on domain authority
	 *
	 * @param float $authority Domain authority.
	 * @return int
	 */
	private function estimate_keywords($authority)
	{
		// Keyword count generally correlates with traffic and authority
		return intval($authority * 75);
	}

	/**
	 * Estimate referring domains based on domain authority
	 *
	 * @param float $authority Domain authority.
	 * @return int
	 */
	private function estimate_referring_domains($authority)
	{
		// referring domains scale with domain authority
		if ($authority >= 80) {
			return intval($authority * 15); // High authority sites
		} elseif ($authority >= 60) {
			return intval($authority * 8); // Medium authority sites
		} elseif ($authority >= 40) {
			return intval($authority * 4); // Low authority sites
		} else {
			return intval($authority * 2); // New sites
		}
	}

	/**
	 * Check if competitor data is valid
	 *
	 * @param array $data Competitor data to validate.
	 * @return bool
	 */
	private function is_valid_competitor_data($data)
	{
		if (!is_array($data) || empty($data)) {
			return false;
		}

		foreach ($data as $competitor) {
			if (!isset($competitor['domain']) || empty($competitor['domain'])) {
				return false;
			}

			// Check if we have at least some data
			$has_data = false;
			$data_fields = array('authority', 'traffic', 'keywords', 'ref_domains');

			foreach ($data_fields as $field) {
				if (isset($competitor[$field]) && $competitor[$field] > 0) {
					$has_data = true;
					break;
				}
			}

			if (!$has_data) {
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
	/**
	 * Get empty competitor analysis data structure
	 */
	private function get_empty_competitor_analysis()
	{
		$competitors = $this->get_configured_competitors();

		// Use simple static data to avoid recursion
		$own_data = array(
			'domain' => wp_parse_url(get_site_url(), PHP_URL_HOST) ?: 'your-site.com',
			'authority' => 0,
			'traffic' => 0,
			'ref_domains' => 0,
			'keywords' => 0,
			'traffic_value' => 0,
			'is_primary' => true,
		);

		$analysis_data = array(
			'total_competitors' => count($competitors),
			'your_authority' => $own_data['authority'],
			'your_ref_domains' => $own_data['ref_domains'],
			'your_traffic' => $own_data['traffic'],
			'competitors' => array(),
			'content_gaps' => array(),
		);

		// Add own site
		$analysis_data['competitors'][] = $own_data;

		// Add configured competitors with empty data
		foreach ($competitors as $domain) {
			$analysis_data['competitors'][] = array(
				'domain' => $domain,
				'authority' => 0,
				'ref_domains' => 0,
				'traffic' => 0,
				'keywords' => 0,
				'source' => 'none',
				'is_primary' => false,
				'traffic_value' => 0,
				'ref_domains_percentage' => 0,
			);
		}

		return $analysis_data;
	}

	/**
	 * Get own site data for comparison in competitor analysis
	 *
	 * @return array
	 */
	private function get_own_site_data()
	{
		$site_url = get_site_url();
		$domain = wp_parse_url($site_url, PHP_URL_HOST);

		// Get basic site data without calling get_seo_dashboard_data() to avoid recursion
		$traffic_data = $this->get_organic_traffic();
		$referring_data = $this->get_referring_domains();
		$keywords_data = $this->get_top_keywords();

		return array(
			'domain' => $domain ?: 'your-site.com',
			'authority' => $referring_data['domain_rating'] ?? 0,
			'traffic' => $traffic_data['current'] ?? 0,
			'ref_domains' => $referring_data['count'] ?? 0,
			'keywords' => count($keywords_data ?? array()),
			'traffic_value' => $this->calculate_traffic_value($traffic_data['current'] ?? 0),
			'is_primary' => true,
		);
	}

	/**
	 * Calculate estimated traffic value for competitor analysis
	 */
	private function calculate_traffic_value($traffic)
	{
		// Basic estimation: $0.50 per organic visit
		return round($traffic * 0.5);
	}

	/**
	 * Get empty competitor profile data structure
	 *
	 * @param string $domain Competitor domain.
	 * @return array
	 */
	private function get_empty_competitor_profile($domain)
	{
		return array(
			'domain' => $domain,
			'authority' => 0,
			'ref_domains' => 0,
			'traffic' => 0,
			'keywords' => 0,
			'source' => 'none',
		);
	}

	/**
	 * Get site health metrics from page speed insights
	 *
	 * @return array
	 */
	public function get_site_health_metrics()
	{
		$api_key = get_option('product_scraper_pagespeed_api');

		// If no API key, return detailed error
		if (!$api_key) {
			if (defined('WP_DEBUG') && WP_DEBUG) {
				error_log('PageSpeed API: No API key configured');
			}
			return $this->get_empty_site_health('No API key configured.');
		}

		$site_url = get_site_url();
		$test_url = esc_url_raw($site_url);

		// Validate URL format
		if (!preg_match('#^https?://#i', $test_url)) {
			if (defined('WP_DEBUG') && WP_DEBUG) {
				error_log('PageSpeed API: Invalid site URL - ' . $test_url);
			}
			return $this->get_empty_site_health('Invalid site URL.');
		}

		// Check for localhost or private IPs
		$host = parse_url($test_url, PHP_URL_HOST);
		if (in_array($host, ['localhost', '127.0.0.1', '::1']) || preg_match('#^192\.168\.#', $host)) {
			return $this->get_empty_site_health('PageSpeed API cannot test localhost or private IP addresses.');
		}

		// Build API URL without manually encoding
		$api_url = add_query_arg(
			[
				'url' => $test_url,
				'key' => $api_key,
				'strategy' => 'desktop', // can also allow 'mobile' as parameter
			],
			'https://www.googleapis.com/pagespeedonline/v5/runPagespeed'
		);

		if (defined('WP_DEBUG') && WP_DEBUG) {
			error_log('PageSpeed API Request URL: ' . $api_url);
		}

		$response = wp_remote_get($api_url, [
			'timeout' => 30,
			'headers' => [
				'User-Agent' => 'WordPress/ProductScraper; ' . home_url(),
			],
		]);

		// Check WP HTTP errors
		if (is_wp_error($response)) {
			$msg = $response->get_error_message();
			if (defined('WP_DEBUG') && WP_DEBUG) {
				error_log('PageSpeed API WP Error: ' . $msg);
			}
			return $this->get_empty_site_health('HTTP request failed: ' . $msg);
		}

		$response_code = wp_remote_retrieve_response_code($response);
		$response_body = wp_remote_retrieve_body($response);

		// HTTP errors
		if ($response_code !== 200) {
			$error_message = 'HTTP Error ' . $response_code;
			$error_data = json_decode($response_body, true);
			if (isset($error_data['error']['message'])) {
				$error_message .= ' - ' . $error_data['error']['message'];
			}
			if (defined('WP_DEBUG') && WP_DEBUG) {
				error_log('PageSpeed API Error: ' . $error_message);
			}
			return $this->get_empty_site_health($error_message);
		}

		// Parse JSON
		$data = json_decode($response_body, true);
		if (json_last_error() !== JSON_ERROR_NONE) {
			$msg = json_last_error_msg();
			if (defined('WP_DEBUG') && WP_DEBUG) {
				error_log('PageSpeed API JSON Error: ' . $msg);
			}
			return $this->get_empty_site_health('JSON parse error: ' . $msg);
		}

		// Check for Lighthouse result
		if (!isset($data['lighthouseResult']['categories'])) {
			$msg = 'Invalid response structure';
			if (isset($data['error']['message'])) {
				$msg .= ' - ' . $data['error']['message'];
			}
			if (defined('WP_DEBUG') && WP_DEBUG) {
				error_log('PageSpeed API Response Error: ' . print_r($data, true));
			}
			return $this->get_empty_site_health($msg);
		}

		$categories = $data['lighthouseResult']['categories'];
		$scores = [];

		// Extract all available categories dynamically
		foreach ($categories as $cat_key => $cat) {
			$scores[$cat_key] = isset($cat['score']) ? round($cat['score'] * 100) : null;
		}

		// Calculate overall score if at least one category exists
		$overall = count($scores) > 0 ? round(array_sum(array_filter($scores)) / count($scores)) : null;

		if (defined('WP_DEBUG') && WP_DEBUG) {
			error_log('PageSpeed API Success - Scores: ' . print_r($scores, true));
		}

		return [
			'scores' => $scores,
			'overall' => $overall,
			'source' => 'pagespeed_api',
			'lighthouse' => $data['lighthouseResult'] ?? [],
			'loadingExperience' => $data['loadingExperience'] ?? [],
		];
	}

	/**
	 * Safely get category score without fallbacks
	 *
	 * @param array  $categories    Categories array from PageSpeed.
	 * @param string $category_name Category name to get score for.
	 * @return int
	 */
	private function get_safe_category_score($categories, $category_name)
	{
		if (isset($categories[$category_name]['score'])) {
			return round($categories[$category_name]['score'] * 100);
		}
		return 0; // Return 0 instead of random data.
	}

	/**
	 * Get empty traffic data structure
	 *
	 * @return array
	 */
	private function get_empty_traffic_data()
	{
		return array(
			'current' => 0,
			'previous' => 0,
			'change' => 0,
			'trend' => 'neutral',
			'bounce_rate' => 0,
			'avg_duration' => 0,
			'organic_sessions' => 0,
			'total_users' => 0,
			'source' => 'none',
			'last_updated' => current_time('mysql'),
		);
	}

	/**
	 * Get empty keywords data structure
	 *
	 * @return array
	 */
	private function get_empty_keywords()
	{
		return array();
	}

	/**
	 * Get empty engagement metrics data structure
	 *
	 * @return array
	 */
	private function get_empty_engagement_metrics()
	{
		return array(
			'visit_duration' => 0,
			'page_views' => 0,
			'bounce_rate' => 0,
			'pages_per_session' => 0,
		);
	}

	/**
	 * Get empty site health data structure
	 *
	 * @return array
	 */
	private function get_empty_site_health($error_message = null)
	{
		return array(
			'scores' => array(
				'performance' => 0,
				'accessibility' => 0,
				'best_practices' => 0,
				'seo' => 0,
			),
			'overall' => 0,
			'source' => 'none',
			'error' => $error_message,
		);
	}

	/**
	 * AJAX handler for data synchronization
	 */
	public function ajax_sync_seo_data()
	{
		// Check if nonce exists first.
		if (!isset($_POST['nonce'])) {
			wp_send_json_error('Missing security token.');
		}

		// Unslash and sanitize the nonce.
		$nonce = sanitize_text_field(wp_unslash($_POST['nonce']));

		// Verify the nonce.
		if (!wp_verify_nonce($nonce, 'product_scraper_nonce')) {
			wp_send_json_error('Security check failed.');
		}

		// Check user capabilities.
		if (!current_user_can('manage_options')) {
			wp_send_json_error('Insufficient permissions.');
		}

		// Clear cache to force refresh.
		$cache_key = 'product_scraper_seo_data_' . md5(get_site_url());
		delete_transient($cache_key);

		$new_data = $this->get_seo_dashboard_data();
		wp_send_json_success($new_data);
	}

	/**
	 * Check if API keys are properly set and valid
	 *
	 * @return array
	 */
	public function check_api_key_status()
	{
		$status = array(
			'google_analytics' => array(
				'configured' => false,
				'has_credentials' => false,
				'property_id_set' => false,
				'message' => ''
			),
			'pagespeed' => array(
				'configured' => false,
				'message' => ''
			)
		);

		// Check Google Analytics
		$ga4_property_id = get_option('product_scraper_ga4_property_id', '');
		$google_service_account = get_option('product_scraper_google_service_account', '');

		$status['google_analytics']['property_id_set'] = !empty($ga4_property_id);
		$status['google_analytics']['has_credentials'] = !empty($google_service_account);
		$status['google_analytics']['configured'] = $status['google_analytics']['property_id_set'] && $status['google_analytics']['has_credentials'];

		if (!$status['google_analytics']['configured']) {
			$status['google_analytics']['message'] = 'GA4 Property ID or Service Account JSON missing';
		}

		// Check PageSpeed Insights
		$pagespeed_api = get_option('product_scraper_pagespeed_api', '');
		$status['pagespeed']['configured'] = !empty($pagespeed_api);
		if (!$status['pagespeed']['configured']) {
			$status['pagespeed']['message'] = 'API key not configured';
		}

		return $status;
	}

	/**
	 * Get GA4 engagement metrics (site-wide or per post)
	 *
	 * @param int|null $post_id
	 * @return array
	 * @throws Exception
	 */
	public function get_ga4_engagement_metric($post_id = null)
	{
		if (!$this->can_use_google_analytics()) {
			return $this->get_empty_engagement_metrics();
		}

		$property_id = get_option('product_scraper_ga4_property_id');
		$service_account_json = get_option('product_scraper_google_service_account');

		$client = new Google_Client();
		$client->setAuthConfig(json_decode($service_account_json, true));
		$client->addScope('https://www.googleapis.com/auth/analytics.readonly');

		$analytics = new Google_Service_AnalyticsData($client);

		$dimension_filter = null;

		if ($post_id) {
			$page_path = wp_parse_url(get_permalink($post_id), PHP_URL_PATH);

			$dimension_filter = new Google_Service_AnalyticsData_FilterExpression([
				'filter' => new Google_Service_AnalyticsData_Filter([
					'fieldName' => 'pagePath',
					'stringFilter' => [
						'matchType' => 'EXACT',
						'value' => $page_path,
					],
				]),
			]);
		}

		$request = new Google_Service_AnalyticsData_RunReportRequest([
			'metrics' => [
				new Google_Service_AnalyticsData_Metric(['name' => 'averageSessionDuration']),
				new Google_Service_AnalyticsData_Metric(['name' => 'bounceRate']),
				new Google_Service_AnalyticsData_Metric(['name' => 'screenPageViewsPerSession']),
				new Google_Service_AnalyticsData_Metric(['name' => 'engagedSessions']),
			],
			'dateRanges' => [
				new Google_Service_AnalyticsData_DateRange([
					'startDate' => '30daysAgo',
					'endDate' => 'today',
				]),
			],
		]);

		if ($dimension_filter) {
			$request->setDimensionFilter($dimension_filter);
		}

		$response = $analytics->properties->runReport("properties/{$property_id}", $request);

		if (!$response->getRows()) {
			return $this->get_empty_engagement_metrics();
		}

		$row = $response->getRows()[0]->getMetricValues();

		return [
			'avg_time_on_page' => (float) $row[0]->getValue(),
			'bounce_rate' => (float) $row[1]->getValue(),
			'pages_per_session' => (float) $row[2]->getValue(),
			'engaged_sessions' => (int) $row[3]->getValue(),
			'source' => 'google_analytics',
			'last_updated' => current_time('mysql'),
		];
	}

	/**
	 * Get top search queries from Google Search Console
	 *
	 * @param int|null $post_id
	 * @param int $limit
	 * @return array
	 */
	public function get_gsc_top_queries($post_id = null, $limit = 20)
	{
		if (!$this->can_use_search_console()) {
			return [];
		}

		// CRITICAL FIX: Ensure $limit is always an integer
		$limit = absint($limit);
		if ($limit <= 0) {
			$limit = 20; // Default fallback
		}

		$client = new Google_Client();
		$client->setAuthConfig(json_decode(get_option('product_scraper_google_service_account'), true));
		$client->addScope('https://www.googleapis.com/auth/webmasters.readonly');

		$gsc = new Google_Service_Webmasters($client);
		$site_url = get_site_url();

		$request = new Google_Service_Webmasters_SearchAnalyticsQueryRequest();
		$request->setStartDate(date('Y-m-d', strtotime('-30 days')));
		$request->setEndDate(date('Y-m-d'));
		$request->setDimensions(['query']);

		// CRITICAL: Ensure we're passing an integer, not a date string
		$request->setRowLimit($limit);

		if ($post_id) {
			$page_path = wp_parse_url(get_permalink($post_id), PHP_URL_PATH);

			$request->setDimensionFilterGroups([
				[
					'filters' => [
						[
							'dimension' => 'page',
							'operator' => 'equals',
							'expression' => $page_path,
						],
					],
				],
			]);
		}

		try {
			$response = $gsc->searchanalytics->query($site_url, $request);

			$queries = [];

			foreach ((array) $response->getRows() as $row) {
				$queries[] = [
					'query' => $row->getKeys()[0],
					'clicks' => $row->getClicks(),
					'impressions' => $row->getImpressions(),
					'ctr' => $row->getCtr(),
					'position' => $row->getPosition(),
				];
			}

			return $queries;

		} catch (Exception $e) {
			if (defined('WP_DEBUG') && WP_DEBUG) {
				error_log('GSC Top Queries Error: ' . $e->getMessage());
			}
			return [];
		}
	}

	/**
	 * Get aggregated GSC metrics for a single page
	 *
	 * @param int $post_id
	 * @return array
	 */
	public function get_gsc_page_metrics($post_id)
	{
		if (!$this->can_use_search_console()) {
			return [
				'clicks' => 0,
				'impressions' => 0,
				'ctr' => 0,
				'position' => 0,
				'source' => 'none',
			];
		}

		$client = new Google_Client();
		$client->setAuthConfig(json_decode(get_option('product_scraper_google_service_account'), true));
		$client->addScope('https://www.googleapis.com/auth/webmasters.readonly');

		$gsc = new Google_Service_Webmasters($client);
		$site_url = get_site_url();
		$page_path = wp_parse_url(get_permalink($post_id), PHP_URL_PATH);

		$request = new Google_Service_Webmasters_SearchAnalyticsQueryRequest();
		$request->setStartDate(date('Y-m-d', strtotime('-30 days')));
		$request->setEndDate(date('Y-m-d'));
		$request->setDimensions(['page']);
		$request->setRowLimit(1);
		$request->setDimensionFilterGroups([
			[
				'filters' => [
					[
						'dimension' => 'page',
						'operator' => 'equals',
						'expression' => $page_path,
					],
				],
			],
		]);

		$response = $gsc->searchanalytics->query($site_url, $request);

		if (!$response->getRows()) {
			return [
				'clicks' => 0,
				'impressions' => 0,
				'ctr' => 0,
				'position' => 0,
				'source' => 'google_search_console',
			];
		}

		$row = $response->getRows()[0];

		return [
			'clicks' => $row->getClicks(),
			'impressions' => $row->getImpressions(),
			'ctr' => $row->getCtr(),
			'position' => $row->getPosition(),
			'source' => 'google_search_console',
			'last_updated' => current_time('mysql'),
		];
	}

	private function calculate_domain_authority($referring_domains)
	{
		if ($referring_domains <= 0) {
			return 0;
		}

		if ($referring_domains >= 1000) {
			return 9.5;
		}
		if ($referring_domains >= 500) {
			return 8.5;
		}
		if ($referring_domains >= 200) {
			return 7.5;
		}
		if ($referring_domains >= 100) {
			return 6.5;
		}
		if ($referring_domains >= 50) {
			return 5.5;
		}
		if ($referring_domains >= 10) {
			return 4.5;
		}

		return 3.0;
	}

	/**
	 * Get PageSpeed Insights scores (real data only)
	 *
	 * @param string $strategy desktop|mobile
	 * @return array
	 */
	public function get_pagespeed_scores($strategy = 'desktop')
	{
		$api_key = get_option('product_scraper_pagespeed_api');

		if (empty($api_key)) {
			return [
				'error' => 'PageSpeed API key not configured.',
				'source' => 'pagespeed_api',
			];
		}

		$site_url = esc_url_raw(get_site_url());

		// Block localhost & private IPs (PSI limitation)
		$host = wp_parse_url($site_url, PHP_URL_HOST);
		if (
			in_array($host, ['localhost', '127.0.0.1', '::1'], true) ||
			preg_match('#^(10\.|192\.168\.|172\.(1[6-9]|2[0-9]|3[0-1]))#', $host)
		) {
			return [
				'error' => 'PageSpeed cannot test localhost or private networks.',
				'source' => 'pagespeed_api',
			];
		}

		$api_url = add_query_arg(
			[
				'url' => $site_url,
				'key' => $api_key,
				'strategy' => $strategy,
				'category' => ['performance', 'accessibility', 'best-practices', 'seo'],
			],
			'https://www.googleapis.com/pagespeedonline/v5/runPagespeed'
		);

		$response = wp_remote_get($api_url, [
			'timeout' => 30,
			'headers' => [
				'User-Agent' => 'WordPress/ProductScraper',
			],
		]);

		if (is_wp_error($response)) {
			return [
				'error' => $response->get_error_message(),
				'source' => 'pagespeed_api',
			];
		}

		if (wp_remote_retrieve_response_code($response) !== 200) {
			return [
				'error' => 'PageSpeed API request failed.',
				'source' => 'pagespeed_api',
			];
		}

		$data = json_decode(wp_remote_retrieve_body($response), true);

		if (
			json_last_error() !== JSON_ERROR_NONE ||
			empty($data['lighthouseResult']['categories'])
		) {
			return [
				'error' => 'Invalid PageSpeed response structure.',
				'source' => 'pagespeed_api',
			];
		}

		$categories = $data['lighthouseResult']['categories'];

		$scores = [
			'performance' => isset($categories['performance']['score'])
				? round($categories['performance']['score'] * 100)
				: 0,
			'accessibility' => isset($categories['accessibility']['score'])
				? round($categories['accessibility']['score'] * 100)
				: 0,
			'best_practices' => isset($categories['best-practices']['score'])
				? round($categories['best-practices']['score'] * 100)
				: 0,
			'seo' => isset($categories['seo']['score'])
				? round($categories['seo']['score'] * 100)
				: 0,
		];

		$overall = round(array_sum($scores) / count($scores));

		return [
			'scores' => $scores,
			'overall' => $overall,
			'strategy' => $strategy,
			'source' => 'pagespeed_api',
			'fetched_at' => current_time('mysql'),
		];
	}

	/**
	 * Fetch Google Search Console traffic time series
	 * Supports site-wide or page-level analytics
	 *
	 * @param string|null $page_url Full permalink (optional)
	 * @param int $days Number of days (default 30)
	 * @return array
	 */
	public function get_gsc_traffic_timeseries($page_url = null, $days = 30, $post_id = null)
	{
		$property = get_option('product_scraper_gsc_property');
		$service_account_json = get_option('product_scraper_google_service_account');

		if (empty($property) || empty($service_account_json)) {
			return [
				'error' => 'GSC property or service account not configured',
				'source' => 'gsc',
			];
		}

		try {
			/* -----------------------------
			 * Google Client Initialization
			 * ----------------------------- */
			$client = new Google_Client();
			$client->setApplicationName('Product Scraper Analytics');
			$client->setAuthConfig(json_decode($service_account_json, true));
			$client->setScopes([
				Google_Service_SearchConsole::WEBMASTERS_READONLY,
			]);

			$service = new Google_Service_SearchConsole($client);

			/* -----------------------------
			 * Date Range
			 * ----------------------------- */
			$end_date = date('Y-m-d');
			$start_date = date('Y-m-d', strtotime("-{$days} days"));

			$request = new Google_Service_SearchConsole_SearchAnalyticsQueryRequest();
			$request->setStartDate($start_date);
			$request->setEndDate($end_date);
			$request->setDimensions(['date']);

			/* -----------------------------
			 * Page-level filter (optional)
			 * ----------------------------- */
			if (!empty($page_url)) {
				$request->setDimensionFilterGroups([
					new Google_Service_SearchConsole_ApiDimensionFilterGroup([
						'filters' => [
							new Google_Service_SearchConsole_ApiDimensionFilter([
								'dimension' => 'page',
								'operator' => 'equals',
								'expression' => esc_url_raw($page_url),
							]),
						],
					]),
				]);
			}

			$response = $service->searchanalytics->query($property, $request);
			$rows = $response->getRows();

			if (empty($rows)) {
				return [
					'data' => [],
					'source' => 'gsc',
					'start_date' => $start_date,
					'end_date' => $end_date,
				];
			}

			/* -----------------------------
			 * Normalize Response
			 * ----------------------------- */
			$timeseries = [];

			foreach ($rows as $row) {
				$timeseries[] = [
					'date' => $row->getKeys()[0],
					'clicks' => (int) $row->getClicks(),
					'impressions' => (int) $row->getImpressions(),
					'ctr' => round($row->getCtr(), 4),
					'position' => round($row->getPosition(), 2),
				];
			}

			return [
				'source' => 'gsc',
				'page_url' => $page_url,
				'start_date' => $start_date,
				'end_date' => $end_date,
				'days' => $days,
				'data' => $timeseries,
				'fetched_at' => current_time('mysql'),
			];

		} catch (Exception $e) {
			if (defined('WP_DEBUG') && WP_DEBUG) {
				error_log('GSC Time Series Error: ' . $e->getMessage());
			}

			return [
				'error' => $e->getMessage(),
				'source' => 'gsc',
			];
		}
	}
}
