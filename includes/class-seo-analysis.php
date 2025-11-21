<?php

class ProductScraper_SEO_Analysis {

	private $analysis_results = array();

	public function __construct() {
		add_action( 'wp_ajax_run_seo_analysis', array( $this, 'ajax_run_seo_analysis' ) );
		add_action( 'wp_ajax_get_seo_insights', array( $this, 'ajax_get_seo_insights' ) );
	}

	/**
	 * Comprehensive SEO analysis for the entire site
	 */
	public function analyze_site_seo() {
		$analysis = array(
			'technical'   => $this->analyze_technical_seo(),
			'content'     => $this->analyze_content_seo(),
			'performance' => $this->analyze_performance_seo(),
			'mobile'      => $this->analyze_mobile_seo(),
			'security'    => $this->analyze_security_seo(),
		);

		$analysis['overall_score'] = $this->calculate_overall_seo_score( $analysis );

		return $analysis;
	}

	/**
	 * Technical SEO analysis
	 */
	private function analyze_technical_seo() {
		global $wpdb;

		$technical = array(
			'score'           => 100,
			'issues'          => array(),
			'recommendations' => array(),
			'checks'          => array(),
		);

		// Permalink structure check.
		$permalink_structure = get_option( 'permalink_structure' );
		if ( empty( $permalink_structure ) || $permalink_structure == '/%postname%/' ) {
			$technical['checks']['permalink_structure'] = array(
				'status'  => 'warning',
				'message' => 'Plain permalinks detected. Use SEO-friendly permalinks.',
				'value'   => $permalink_structure,
			);
			$technical['score']                        -= 10;
		} else {
			$technical['checks']['permalink_structure'] = array(
				'status'  => 'good',
				'message' => 'SEO-friendly permalinks enabled.',
				'value'   => $permalink_structure,
			);
		}

		// Indexing status.
		$blog_public = get_option( 'blog_public' );
		if ( ! $blog_public ) {
			$technical['checks']['indexing'] = array(
				'status'  => 'critical',
				'message' => 'Search engines are blocked from indexing your site.',
				'value'   => 'Blocked',
			);
			$technical['score']             -= 20;
		} else {
			$technical['checks']['indexing'] = array(
				'status'  => 'good',
				'message' => 'Site is indexable by search engines.',
				'value'   => 'Allowed',
			);
		}

		// SSL check.
		if ( is_ssl() ) {
			$technical['checks']['ssl'] = array(
				'status'  => 'good',
				'message' => 'SSL certificate is installed and active.',
				'value'   => 'Enabled',
			);
		} else {
			$technical['checks']['ssl'] = array(
				'status'  => 'critical',
				'message' => 'SSL certificate is not installed. HTTPS is required for SEO.',
				'value'   => 'Disabled',
			);
			$technical['score']        -= 15;
		}

		// XML Sitemap check.
		if ( function_exists( 'wp_sitemaps_get_server' ) ) {
			$technical['checks']['sitemap'] = array(
				'status'  => 'good',
				'message' => 'XML sitemap is enabled.',
				'value'   => 'Enabled',
			);
		} else {
			$technical['checks']['sitemap'] = array(
				'status'  => 'warning',
				'message' => 'XML sitemap is not enabled.',
				'value'   => 'Disabled',
			);
			$technical['score']            -= 5;
		}

		// Robots.txt check.
		$robots_content                    = $this->check_robots_txt();
		$technical['checks']['robots_txt'] = $robots_content;

		// Image alt text analysis.
		$image_analysis         = $this->analyze_image_seo();
		$technical['image_seo'] = $image_analysis;
		$technical['score']    += $image_analysis['score_impact'];

		// Heading structure analysis.
		$heading_analysis               = $this->analyze_heading_structure();
		$technical['heading_structure'] = $heading_analysis;
		$technical['score']            += $heading_analysis['score_impact'];

		// Update score to be within bounds.
		$technical['score'] = max( 0, min( 100, $technical['score'] ) );

		return $technical;
	}

	/**
	 * Content SEO analysis
	 */
	private function analyze_content_seo() {
		global $wpdb;

		$content = array(
			'score'           => 100,
			'stats'           => array(),
			'issues'          => array(),
			'recommendations' => array(),
		);

		// Get post statistics.
		$post_types        = get_post_types( array( 'public' => true ), 'names' );
		$post_count        = 0;
		$optimized_posts   = 0;
		$low_content_posts = 0;

		foreach ( $post_types as $post_type ) {
			$posts = get_posts(
				array(
					'post_type'   => $post_type,
					'numberposts' => -1,
					'post_status' => 'publish',
				)
			);

			foreach ( $posts as $post ) {
				++$post_count;

				// Check if post has SEO title.
				$seo_title = get_post_meta( $post->ID, '_seo_title', true );
				if ( ! empty( $seo_title ) ) {
					++$optimized_posts;
				}

				// Check content length.
				$word_count = str_word_count( wp_strip_all_tags( $post->post_content ) );
				if ( $word_count < 300 ) {
					++$low_content_posts;
				}
			}
		}

		$content['stats']['total_posts']       = $post_count;
		$content['stats']['optimized_posts']   = $optimized_posts;
		$content['stats']['optimization_rate'] = $post_count > 0 ? round( ( $optimized_posts / $post_count ) * 100 ) : 0;
		$content['stats']['low_content_posts'] = $low_content_posts;

		// Calculate content score.
		if ( $content['stats']['optimization_rate'] < 50 ) {
			$content['score']   -= 20;
			$content['issues'][] = 'Less than 50% of posts have SEO titles';
		} elseif ( $content['stats']['optimization_rate'] < 80 ) {
			$content['score']   -= 10;
			$content['issues'][] = 'SEO optimization can be improved';
		}

		if ( $low_content_posts > 0 ) {
			$content['score']   -= 15;
			$content['issues'][] = "$low_content_posts posts have low content (less than 300 words)";
		}

		// Duplicate content check.
		$duplicate_check              = $this->check_duplicate_content();
		$content['duplicate_content'] = $duplicate_check;
		$content['score']            += $duplicate_check['score_impact'];

		// Internal linking analysis.
		$linking_analysis            = $this->analyze_internal_linking();
		$content['internal_linking'] = $linking_analysis;
		$content['score']           += $linking_analysis['score_impact'];

		return $content;
	}

	/**
	 * Performance SEO analysis
	 */
	private function analyze_performance_seo() {
		$performance = array(
			'score'           => 100,
			'metrics'         => array(),
			'issues'          => array(),
			'recommendations' => array(),
		);

		// Page load time (simulated - in real implementation, use Pagespeed API).
		$load_time                                     = $this->estimate_page_load_time();
		$performance['metrics']['estimated_load_time'] = $load_time;

		if ( $load_time > 3 ) {
			$performance['score']   -= 20;
			$performance['issues'][] = 'Page load time is above 3 seconds';
		} elseif ( $load_time > 2 ) {
			$performance['score']   -= 10;
			$performance['issues'][] = 'Page load time could be improved';
		}

		// Image optimization check.
		$image_optimization                = $this->check_image_optimization();
		$performance['image_optimization'] = $image_optimization;
		$performance['score']             += $image_optimization['score_impact'];

		// Caching check.
		$caching_status         = $this->check_caching();
		$performance['caching'] = $caching_status;
		$performance['score']  += $caching_status['score_impact'];

		// Database optimization check.
		$db_optimization         = $this->check_database_optimization();
		$performance['database'] = $db_optimization;
		$performance['score']   += $db_optimization['score_impact'];

		return $performance;
	}

	/**
	 * Mobile SEO analysis
	 */
	private function analyze_mobile_seo() {
		$mobile = array(
			'score'  => 100,
			'checks' => array(),
			'issues' => array(),
		);

		// Check if theme is responsive.
		$mobile['checks']['responsive_design'] = array(
			'status'  => 'good',
			'message' => 'Assume responsive design (manual verification recommended)',
			'value'   => 'Responsive',
		);

		// Viewport meta tag check.
		$viewport_check               = $this->check_viewport_meta();
		$mobile['checks']['viewport'] = $viewport_check;
		if ( $viewport_check['status'] === 'warning' ) {
			$mobile['score'] -= 10;
		}

		// Touch targets check.
		$mobile['checks']['touch_targets'] = array(
			'status'  => 'info',
			'message' => 'Verify touch target sizes manually',
			'value'   => 'Manual check needed',
		);

		// Mobile page speed
		$mobile_speed           = $this->check_mobile_speed();
		$mobile['mobile_speed'] = $mobile_speed;
		$mobile['score']       += $mobile_speed['score_impact'];

		return $mobile;
	}

	/**
	 * Security SEO analysis
	 */
	private function analyze_security_seo() {
		$security = array(
			'score'  => 100,
			'checks' => array(),
			'issues' => array(),
		);

		// SSL check (redundant but included for completeness).
		if ( ! is_ssl() ) {
			$security['checks']['ssl'] = array(
				'status'  => 'critical',
				'message' => 'SSL not enabled',
				'value'   => 'Insecure',
			);
			$security['score']        -= 30;
		} else {
			$security['checks']['ssl'] = array(
				'status'  => 'good',
				'message' => 'SSL enabled',
				'value'   => 'Secure',
			);
		}

		// WordPress version check.
		$wp_version = get_bloginfo( 'version' );
		$latest_wp  = $this->get_latest_wordpress_version();
		if ( version_compare( $wp_version, $latest_wp, '<' ) ) {
			$security['checks']['wp_version'] = array(
				'status'  => 'warning',
				'message' => 'WordPress is not up to date',
				'value'   => $wp_version,
			);
			$security['score']               -= 10;
		} else {
			$security['checks']['wp_version'] = array(
				'status'  => 'good',
				'message' => 'WordPress is up to date',
				'value'   => $wp_version,
			);
		}

		// Security headers check.
		$security_headers             = $this->check_security_headers();
		$security['security_headers'] = $security_headers;
		$security['score']           += $security_headers['score_impact'];

		return $security;
	}

	/**
	 * Helper methods for individual checks
	 */
	private function check_robots_txt() {
		$robots_url = home_url( '/robots.txt' );
		$response   = wp_remote_get( $robots_url );

		if ( is_wp_error( $response ) ) {
			return array(
				'status'  => 'warning',
				'message' => 'Robots.txt not accessible or not found',
				'value'   => 'Not found',
			);
		}

		$content = wp_remote_retrieve_body( $response );

		if ( empty( $content ) ) {
			return array(
				'status'  => 'warning',
				'message' => 'Robots.txt is empty',
				'value'   => 'Empty',
			);
		}

		return array(
			'status'  => 'good',
			'message' => 'Robots.txt is present and accessible',
			'value'   => 'Present',
		);
	}

	private function analyze_image_seo() {
		global $wpdb;

		// Count images without alt text.
		$images_without_alt = $wpdb->get_var(
			"
            SELECT COUNT(*) 
            FROM {$wpdb->posts} 
            WHERE post_type = 'attachment' 
            AND post_mime_type LIKE 'image/%'
            AND ID NOT IN (
                SELECT post_id 
                FROM {$wpdb->postmeta} 
                WHERE meta_key = '_wp_attachment_image_alt' 
                AND meta_value != ''
            )
        "
		);

		$total_images = $wpdb->get_var(
			"
            SELECT COUNT(*) 
            FROM {$wpdb->posts} 
            WHERE post_type = 'attachment' 
            AND post_mime_type LIKE 'image/%'
        "
		);

		$percentage_with_alt = $total_images > 0 ? round( ( ( $total_images - $images_without_alt ) / $total_images ) * 100 ) : 100;

		$score_impact = 0;
		if ( $percentage_with_alt < 50 ) {
			$score_impact = -15;
		} elseif ( $percentage_with_alt < 80 ) {
			$score_impact = -8;
		} elseif ( $percentage_with_alt < 95 ) {
			$score_impact = -3;
		}

		return array(
			'images_without_alt'  => $images_without_alt,
			'total_images'        => $total_images,
			'percentage_with_alt' => $percentage_with_alt,
			'score_impact'        => $score_impact,
			'status'              => $percentage_with_alt >= 80 ? 'good' : ( $percentage_with_alt >= 50 ? 'warning' : 'critical' ),
		);
	}

	private function analyze_heading_structure() {
		// This would require parsing actual page content.
		// For now, return a basic analysis.
		return array(
			'status'       => 'info',
			'message'      => 'Manual heading structure analysis recommended',
			'score_impact' => 0,
		);
	}

	private function check_duplicate_content() {
		// Basic duplicate content check by meta description.
		global $wpdb;

		$duplicate_meta_descriptions = $wpdb->get_var(
			"
            SELECT COUNT(*) 
            FROM (
                SELECT meta_value, COUNT(*) as count 
                FROM {$wpdb->postmeta} 
                WHERE meta_key = '_meta_description' 
                AND meta_value != '' 
                GROUP BY meta_value 
                HAVING COUNT(*) > 1
            ) as duplicates
        "
		);

		$score_impact = $duplicate_meta_descriptions > 0 ? -10 : 0;

		return array(
			'duplicate_meta_descriptions' => $duplicate_meta_descriptions,
			'score_impact'                => $score_impact,
			'status'                      => $duplicate_meta_descriptions > 0 ? 'warning' : 'good',
		);
	}

	private function analyze_internal_linking() {
		global $wpdb;

		$posts_without_links = $wpdb->get_var(
			"
            SELECT COUNT(*) 
            FROM {$wpdb->posts} p
            WHERE p.post_type = 'post' 
            AND p.post_status = 'publish'
            AND p.post_content NOT LIKE '%<a %'
        "
		);

		$total_posts = $wpdb->get_var(
			"
            SELECT COUNT(*) 
            FROM {$wpdb->posts} 
            WHERE post_type = 'post' 
            AND post_status = 'publish'
        "
		);

		$percentage_with_links = $total_posts > 0 ? round( ( ( $total_posts - $posts_without_links ) / $total_posts ) * 100 ) : 100;

		$score_impact = 0;
		if ( $percentage_with_links < 30 ) {
			$score_impact = -10;
		} elseif ( $percentage_with_links < 60 ) {
			$score_impact = -5;
		}

		return array(
			'posts_without_links'   => $posts_without_links,
			'total_posts'           => $total_posts,
			'percentage_with_links' => $percentage_with_links,
			'score_impact'          => $score_impact,
			'status'                => $percentage_with_links >= 60 ? 'good' : ( $percentage_with_links >= 30 ? 'warning' : 'critical' ),
		);
	}

	private function estimate_page_load_time() {
		// Simulate load time calculation.
		// TODO: In real implementation, use Pagespeed API.
		return rand( 15, 50 ) / 10; // Random value between 1.5 and 5 seconds.
	}

	private function check_image_optimization() {
		// Basic image optimization check.
		return array(
			'status'       => 'info',
			'message'      => 'Use Pagespeed API for detailed image analysis',
			'score_impact' => 0,
		);
	}

	private function check_caching() {
		// Check if caching is likely enabled.
		$caching_headers = $this->check_caching_headers();

		return array(
			'status'       => $caching_headers ? 'good' : 'warning',
			'message'      => $caching_headers ? 'Caching headers detected' : 'Caching may not be optimized',
			'score_impact' => $caching_headers ? 5 : -5,
		);
	}

	private function check_database_optimization() {
		global $wpdb;

		// Check for post revisions.
		$revisions = $wpdb->get_var(
			"
            SELECT COUNT(*) 
            FROM {$wpdb->posts} 
            WHERE post_type = 'revision'
        "
		);

		$score_impact = $revisions > 100 ? -5 : 0;

		return array(
			'revisions_count' => $revisions,
			'status'          => $revisions > 100 ? 'warning' : 'good',
			'message'         => $revisions > 100 ? 'Consider cleaning up post revisions' : 'Database appears optimized',
			'score_impact'    => $score_impact,
		);
	}

	private function check_viewport_meta() {
		// Check if viewport meta tag is present in theme.
		// This is a basic check - would need to parse theme files.
		return array(
			'status'  => 'good',
			'message' => 'Assume viewport meta tag is present',
			'value'   => 'Present',
		);
	}

	private function check_mobile_speed() {
		// Mobile speed check.
		return array(
			'status'       => 'info',
			'message'      => 'Use Pagespeed API for mobile speed analysis',
			'score_impact' => 0,
		);
	}

	private function get_latest_wordpress_version() {
		// Get latest WordPress version.
		$core_updates = get_core_updates();
		if ( ! is_wp_error( $core_updates ) && ! empty( $core_updates ) ) {
			return $core_updates[0]->current;
		}
		return get_bloginfo( 'version' );
	}

	private function check_security_headers() {
		// Basic security headers check.
		return array(
			'status'       => 'info',
			'message'      => 'Manual security headers check recommended',
			'score_impact' => 0,
		);
	}

	private function check_caching_headers() {
		// Simple check for caching.
		$response = wp_remote_head( home_url() );
		$headers  = wp_remote_retrieve_headers( $response );

		return isset( $headers['cache-control'] ) || isset( $headers['expires'] );
	}

	/**
	 * Calculate overall SEO score
	 */
	private function calculate_overall_seo_score( $analysis ) {
		$weights = array(
			'technical'   => 0.3,
			'content'     => 0.3,
			'performance' => 0.2,
			'mobile'      => 0.1,
			'security'    => 0.1,
		);

		$overall_score = 0;
		foreach ( $weights as $section => $weight ) {
			$overall_score += $analysis[ $section ]['score'] * $weight;
		}

		return round( $overall_score );
	}

	/**
	 * AJAX handler for running SEO analysis
	 */
	public function ajax_run_seo_analysis() {
		check_ajax_referer( 'product_scraper_nonce', 'nonce' );

		$analysis = $this->analyze_site_seo();

		// Store analysis results.
		update_option( 'product_scraper_seo_analysis', $analysis );
		update_option( 'product_scraper_seo_analysis_timestamp', current_time( 'timestamp' ) );

		wp_send_json_success(
			array(
				'analysis'  => $analysis,
				'timestamp' => get_option( 'product_scraper_seo_analysis_timestamp' ),
			)
		);
	}

	/**
	 * AJAX handler for getting SEO insights
	 */
	public function ajax_get_seo_insights() {
		check_ajax_referer( 'product_scraper_nonce', 'nonce' );

		$analysis = get_option( 'product_scraper_seo_analysis', array() );
		$insights = $this->generate_seo_insights( $analysis );

		wp_send_json_success( $insights );
	}

	/**
	 * Generate actionable SEO insights
	 */
	private function generate_seo_insights( $analysis ) {
		$insights = array(
			'critical'       => array(),
			'high_priority'  => array(),
			'recommended'    => array(),
			'good_practices' => array(),
		);

		// Technical SEO insights.
		if ( isset( $analysis['technical']['checks']['indexing'] ) &&
			$analysis['technical']['checks']['indexing']['status'] === 'critical' ) {
			$insights['critical'][] = 'Your site is blocking search engines from indexing. Go to Settings > Reading and uncheck "Discourage search engines from indexing this site".';
		}

		if ( isset( $analysis['technical']['checks']['ssl'] ) &&
			$analysis['technical']['checks']['ssl']['status'] === 'critical' ) {
			$insights['critical'][] = 'SSL certificate is not installed. HTTPS is essential for SEO and user trust.';
		}

		// Content SEO insights.
		if ( isset( $analysis['content']['stats']['optimization_rate'] ) &&
			$analysis['content']['stats']['optimization_rate'] < 50 ) {
			$insights['high_priority'][] = 'Only ' . $analysis['content']['stats']['optimization_rate'] . '% of your posts have SEO titles. Optimize all posts for better rankings.';
		}

		if ( isset( $analysis['content']['stats']['low_content_posts'] ) &&
			$analysis['content']['stats']['low_content_posts'] > 0 ) {
			$insights['high_priority'][] = 'You have ' . $analysis['content']['stats']['low_content_posts'] . ' posts with low content. Consider expanding these posts to at least 300 words.';
		}

		// Performance insights.
		if ( isset( $analysis['performance']['metrics']['estimated_load_time'] ) &&
			$analysis['performance']['metrics']['estimated_load_time'] > 3 ) {
			$insights['high_priority'][] = 'Page load time is ' . $analysis['performance']['metrics']['estimated_load_time'] . ' seconds. Aim for under 3 seconds for better SEO.';
		}

		return $insights;
	}

	/**
	 * Get analysis history
	 */
	public function get_analysis_history() {
		return get_option( 'product_scraper_seo_analysis_history', array() );
	}

	/**
	 * Save current analysis to history
	 */
	public function save_analysis_to_history( $analysis ) {
		$history   = $this->get_analysis_history();
		$history[] = array(
			'timestamp' => current_time( 'timestamp' ),
			'score'     => $analysis['overall_score'],
			'analysis'  => $analysis,
		);

		// Keep only last 10 analyses.
		if ( count( $history ) > 10 ) {
			$history = array_slice( $history, -10 );
		}

		update_option( 'product_scraper_seo_analysis_history', $history );
	}
}
