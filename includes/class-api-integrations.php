<?php

class ProductScraper_API_Integrations
{
    private $cache_duration = 3600; // 1 hour cache

    public function __construct()
    {
        add_action('wp_ajax_sync_seo_data', array($this, 'ajax_sync_seo_data'));
    }

    /**
     * Check if Google API classes are available
     */
    private function google_api_available()
    {
        return class_exists('Google_Client') && class_exists('Google_Service_AnalyticsData');
    }

    /**
     * Get comprehensive SEO data for the current site
     */
    public function get_seo_dashboard_data()
    {
        $cache_key = 'product_scraper_seo_data_' . md5(get_site_url());
        $cached_data = get_transient($cache_key);

        if ($cached_data !== false) {
            return $cached_data;
        }

        $data = array(
            'organic_traffic' => $this->get_organic_traffic(),
            'referring_domains' => $this->get_referring_domains(),
            'top_keywords' => $this->get_top_keywords(),
            'digital_score' => $this->calculate_digital_score(),
            'engagement_metrics' => $this->get_engagement_metrics(),
            'competitor_analysis' => $this->get_competitor_analysis(),
            'site_health' => $this->get_site_health_metrics(),
            'last_updated' => current_time('mysql')
        );

        error_log('SEO Dashboard Data: ' . print_r($data, true));

        set_transient($cache_key, $data, $this->cache_duration);
        return $data;
    }

    /**
     * Get organic traffic data from Google Analytics - REAL DATA ONLY
     */
    public function get_organic_traffic()
    {
        $propertyId = get_option('product_scraper_ga4_property_id');

        if ($this->google_api_available()) {
            error_log('Google Client set');
        } else {
            error_log('Google Client class not available');
        }

        // Only try Google Analytics API if vendor is loaded AND property ID exists
        if ($propertyId && $this->google_api_available()) {
            try {
                return $this->get_google_analytics_data($propertyId);
            } catch (Exception $e) {
                // Log error and return empty data structure
                error_log('Google Analytics API error: ' . $e->getMessage());
                return $this->get_empty_traffic_data();
            }
        }

        // Return empty data if no API configured
        return $this->get_empty_traffic_data();
    }

    /**
     * Get Google Analytics data (only called if vendor is loaded)
     */
    private function get_google_analytics_data($propertyId)
    {
        $client = new Google_Client();
        $client->setAuthConfig(WP_CONTENT_DIR . '/uploads/product-scraper-service-account.json');
        $client->addScope('https://www.googleapis.com/auth/analytics.readonly');

        $analytics = new Google_Service_AnalyticsData($client);
        $request = new Google_Service_AnalyticsData_RunReportRequest([
            'dimensions' => [new Google_Service_AnalyticsData_Dimension(['name' => 'sessionSource'])],
            'metrics' => [
                new Google_Service_AnalyticsData_Metric(['name' => 'sessions']),
                new Google_Service_AnalyticsData_Metric(['name' => 'bounceRate']),
                new Google_Service_AnalyticsData_Metric(['name' => 'averageSessionDuration'])
            ],
            'dateRanges' => [
                new Google_Service_AnalyticsData_DateRange(['startDate' => '30daysAgo', 'endDate' => 'today'])
            ]
        ]);

        $response = $analytics->properties->runReport("properties/$propertyId", $request);

        // Parse response - handle empty data
        $sessions = 0;
        $bounce_rate = 0;
        $avg_duration = 0;

        if ($response->getRows()) {
            foreach ($response->getRows() as $row) {
                $sessions += (int)$row->getMetricValues()[0]->getValue();
                $bounce_rate = (float)$row->getMetricValues()[1]->getValue();
                $avg_duration = (float)$row->getMetricValues()[2]->getValue();
            }
        }

        return [
            'current' => $sessions,
            'previous' => 0, // You'd need historical data for this
            'change' => 0,
            'trend' => 'neutral',
            'bounce_rate' => $bounce_rate,
            'avg_duration' => $avg_duration,
            'source' => 'google_analytics'
        ];
    }

    /**
     * Get referring domains count - REAL DATA ONLY
     */
    public function get_referring_domains()
    {
        $api_key = get_option('product_scraper_ahrefs_api');
        $target = parse_url(get_site_url(), PHP_URL_HOST);

        if (!$api_key) return $this->get_empty_referring_domains();

        $url = "https://apiv2.ahrefs.com?token={$api_key}&target={$target}&from=domain_rating&mode=domain";

        $response = wp_remote_get($url);
        if (is_wp_error($response)) {
            error_log('Ahrefs API HTTP error: ' . $response->get_error_message());
            return $this->get_empty_referring_domains();
        }

        $data = json_decode(wp_remote_retrieve_body($response), true);

        // Handle API errors or empty responses
        if (isset($data['error'])) {
            error_log('Ahrefs API error: ' . $data['error']);
            return $this->get_empty_referring_domains();
        }

        $refdomains = isset($data['refdomains']) ? intval($data['refdomains']) : 0;
        $domain_rating = isset($data['domain_rating']) ? floatval($data['domain_rating']) : 0;

        return [
            'count' => $refdomains,
            'domain_rating' => $domain_rating,
            'trend' => 'neutral',
            'change' => 0,
            'source' => 'ahrefs_api'
        ];
    }

    /**
     * Get top performing keywords - REAL DATA ONLY
     */
    private function get_top_keywords()
    {
        $gsc_connected = get_option('product_scraper_google_search_console');

        if ($gsc_connected && $this->google_api_available()) {
            try {
                return $this->get_gsc_keywords();
            } catch (Exception $e) {
                error_log('Google Search Console API error: ' . $e->getMessage());
                return $this->get_empty_keywords();
            }
        }

        // Return empty if no GSC configured
        return $this->get_empty_keywords();
    }

    /**
     * Get keywords from Google Search Console
     */
    private function get_gsc_keywords()
    {
        // This is a placeholder - we'll need to implement actual GSC API integration
        // For now, return empty array
        return [];
    }

    /**
     * Calculate comprehensive digital score based on real data
     */
    private function calculate_digital_score()
    {
        $traffic_data = $this->get_organic_traffic();
        $referring_data = $this->get_referring_domains();
        $health_data = $this->get_site_health_metrics();

        $metrics = array(
            'traffic_score' => $this->calculate_traffic_score($traffic_data),
            'authority_score' => $this->calculate_authority_score($referring_data),
            'technical_score' => $health_data['overall'] ?? 0
        );

        $total_score = array_sum($metrics) / count($metrics);
        return round($total_score);
    }

    /**
     * Calculate traffic score based on real traffic data
     */
    private function calculate_traffic_score($traffic_data)
    {
        if ($traffic_data['current'] > 10000) return 100;
        if ($traffic_data['current'] > 5000) return 80;
        if ($traffic_data['current'] > 1000) return 60;
        if ($traffic_data['current'] > 100) return 40;
        return 20;
    }

    /**
     * Calculate authority score based on real referring domains
     */
    private function calculate_authority_score($referring_data)
    {
        $ref_domains = $referring_data['count'];
        $domain_rating = $referring_data['domain_rating'] * 10; // Convert to 0-100 scale

        if ($ref_domains > 1000) return min(100, $domain_rating + 20);
        if ($ref_domains > 100) return min(100, $domain_rating + 10);
        if ($ref_domains > 10) return min(100, $domain_rating + 5);
        return $domain_rating;
    }

    /**
     * Get engagement metrics from real data
     */
    private function get_engagement_metrics()
    {
        $propertyId = get_option('product_scraper_ga4_property_id');

        if ($propertyId && $this->google_api_available()) {
            try {
                return $this->get_google_analytics_engagement($propertyId);
            } catch (Exception $e) {
                error_log('Google Analytics Engagement API error: ' . $e->getMessage());
            }
        }

        return $this->get_empty_engagement_metrics();
    }

    /**
     * Get engagement metrics from Google Analytics
     */
    private function get_google_analytics_engagement($propertyId)
    {
        $client = new Google_Client();
        $client->setAuthConfig(WP_CONTENT_DIR . '/uploads/product-scraper-service-account.json');
        $client->addScope('https://www.googleapis.com/auth/analytics.readonly');

        $analytics = new Google_Service_AnalyticsData($client);
        error_log('$analytics: ' . print_r($analytics, true));
        $request = new Google_Service_AnalyticsData_RunReportRequest([
            'metrics' => [
                new Google_Service_AnalyticsData_Metric(['name' => 'sessions']),
                new Google_Service_AnalyticsData_Metric(['name' => 'averageSessionDuration']),
                new Google_Service_AnalyticsData_Metric(['name' => 'bounceRate']),
                new Google_Service_AnalyticsData_Metric(['name' => 'screenPageViewsPerSession'])
            ],
            'dateRanges' => [
                new Google_Service_AnalyticsData_DateRange(['startDate' => '30daysAgo', 'endDate' => 'today'])
            ]
        ]);

        $response = $analytics->properties->runReport("properties/$propertyId", $request);

        $metrics = [
            'visit_duration' => 0,
            'page_views' => 0,
            'bounce_rate' => 0,
            'pages_per_session' => 0
        ];

        if ($response->getRows()) {
            $row = $response->getRows()[0];
            $metrics = [
                'visit_duration' => (float)$row->getMetricValues()[1]->getValue(),
                'page_views' => (int)$row->getMetricValues()[0]->getValue(),
                'bounce_rate' => (float)$row->getMetricValues()[2]->getValue(),
                'pages_per_session' => (float)$row->getMetricValues()[3]->getValue()
            ];
        }

        return $metrics;
    }

    /**
     * Get competitor analysis - REAL DATA ONLY
     */
    private function get_competitor_analysis()
    {
        $api_key = get_option('product_scraper_ahrefs_api');

        if (!$api_key) {
            return $this->get_empty_competitor_analysis();
        }

        $competitors = ['competitor1.com', 'competitor2.com', 'competitor3.com'];
        $analysis = [];

        foreach ($competitors as $domain) {
            $url = "https://apiv2.ahrefs.com?token={$api_key}&target={$domain}&from=domain_rating&mode=domain";
            $response = wp_remote_get($url);

            if (is_wp_error($response)) {
                $analysis[] = $this->get_empty_competitor_profile($domain);
                continue;
            }

            $data = json_decode(wp_remote_retrieve_body($response), true);

            if (isset($data['error'])) {
                $analysis[] = $this->get_empty_competitor_profile($domain);
                continue;
            }

            $analysis[] = [
                'domain' => $domain,
                'authority' => isset($data['domain_rating']) ? floatval($data['domain_rating']) : 0,
                'ref_domains' => isset($data['refdomains']) ? intval($data['refdomains']) : 0,
                'traffic' => isset($data['traffic']) ? intval($data['traffic']) : 0,
                'source' => 'ahrefs_api'
            ];
        }

        return $analysis;
    }

    /**
     * Get site health metrics - REAL DATA ONLY
     */
    public function get_site_health_metrics()
    {
        $api_key = get_option('product_scraper_pagespeed_api');

        // If no API key, return empty data
        if (!$api_key) {
            return $this->get_empty_site_health();
        }

        $url = urlencode(get_site_url());
        $api_url = "https://www.googleapis.com/pagespeedonline/v5/runPagespeed?url={$url}&key={$api_key}";

        $response = wp_remote_get($api_url, array(
            'timeout' => 30,
            'headers' => array(
                'User-Agent' => 'WordPress/ProductScraper'
            )
        ));

        // Check for WP HTTP errors
        if (is_wp_error($response)) {
            error_log('PageSpeed API WP Error: ' . $response->get_error_message());
            return $this->get_empty_site_health();
        }

        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code !== 200) {
            error_log('PageSpeed API HTTP Error: ' . $response_code);
            return $this->get_empty_site_health();
        }

        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        // Check if JSON decoding failed
        if (json_last_error() !== JSON_ERROR_NONE) {
            error_log('PageSpeed API JSON Error: ' . json_last_error_msg());
            return $this->get_empty_site_health();
        }

        // Check if the expected structure exists
        if (!isset($data['lighthouseResult']['categories'])) {
            error_log('PageSpeed API: Invalid response structure');
            return $this->get_empty_site_health();
        }

        $categories = $data['lighthouseResult']['categories'];

        // Extract scores safely
        $scores = [
            'performance' => $this->get_safe_category_score($categories, 'performance'),
            'accessibility' => $this->get_safe_category_score($categories, 'accessibility'),
            'best_practices' => $this->get_safe_category_score($categories, 'best-practices'),
            'seo' => $this->get_safe_category_score($categories, 'seo'),
        ];

        // Calculate overall score from real data
        $overall = round(array_sum($scores) / count($scores));

        return [
            'scores' => $scores,
            'overall' => $overall,
            'source' => 'pagespeed_api'
        ];
    }

    /**
     * Safely get category score without fallbacks
     */
    private function get_safe_category_score($categories, $category_name)
    {
        if (isset($categories[$category_name]['score'])) {
            return round($categories[$category_name]['score'] * 100);
        }
        return 0; // Return 0 instead of random data
    }

    // EMPTY DATA STRUCTURES - NO FALLBACKS

    private function get_empty_traffic_data()
    {
        return [
            'current' => 0,
            'previous' => 0,
            'change' => 0,
            'trend' => 'neutral',
            'bounce_rate' => 0,
            'avg_duration' => 0,
            'source' => 'none'
        ];
    }

    private function get_empty_referring_domains()
    {
        return [
            'count' => 0,
            'domain_rating' => 0,
            'trend' => 'neutral',
            'change' => 0,
            'source' => 'none'
        ];
    }

    private function get_empty_keywords()
    {
        return [];
    }

    private function get_empty_engagement_metrics()
    {
        return [
            'visit_duration' => 0,
            'page_views' => 0,
            'bounce_rate' => 0,
            'pages_per_session' => 0
        ];
    }

    private function get_empty_competitor_analysis()
    {
        return [
            [
                'domain' => 'competitor1.com',
                'authority' => 0,
                'ref_domains' => 0,
                'traffic' => 0,
                'source' => 'none'
            ],
            [
                'domain' => 'competitor2.com',
                'authority' => 0,
                'ref_domains' => 0,
                'traffic' => 0,
                'source' => 'none'
            ],
            [
                'domain' => 'competitor3.com',
                'authority' => 0,
                'ref_domains' => 0,
                'traffic' => 0,
                'source' => 'none'
            ]
        ];
    }

    private function get_empty_competitor_profile($domain)
    {
        return [
            'domain' => $domain,
            'authority' => 0,
            'ref_domains' => 0,
            'traffic' => 0,
            'source' => 'none'
        ];
    }

    private function get_empty_site_health()
    {
        return [
            'scores' => [
                'performance' => 0,
                'accessibility' => 0,
                'best_practices' => 0,
                'seo' => 0,
            ],
            'overall' => 0,
            'source' => 'none'
        ];
    }

    /**
     * AJAX handler for data synchronization
     */
    public function ajax_sync_seo_data()
    {
        if (!wp_verify_nonce($_POST['nonce'], 'product_scraper_nonce')) {
            wp_send_json_error('Security check failed');
        }

        // Clear cache to force refresh
        $cache_key = 'product_scraper_seo_data_' . md5(get_site_url());
        delete_transient($cache_key);

        $new_data = $this->get_seo_dashboard_data();
        wp_send_json_success($new_data);
    }
}
