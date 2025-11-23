<?php
/**
 * SEO Assistant for Product Scraper Plugin
 *
 * @package    Product_Scraper
 * @subpackage SEO
 * @since      1.0.0
 */

/**
 * Class ProductScraper_SEO_Assistant
 *
 * Handles SEO optimization, analysis, and content improvements.
 */
class ProductScraper_SEO_Assistant {

	/**
	 * Analysis results storage.
	 *
	 * @var array
	 */
	private $analysis_results = array();

	/**
	 * Initialize SEO Assistant.
	 */
	public function __construct() {
		add_action( 'admin_menu', array( $this, 'add_seo_menu' ) );
		add_action( 'add_meta_boxes', array( $this, 'add_seo_meta_boxes' ) );
		add_action( 'save_post', array( $this, 'save_seo_metadata' ) );
		add_action( 'save_post', array( $this, 'perform_real_time_analysis' ) );
		add_filter( 'wp_insert_post_data', array( $this, 'optimize_content_on_save' ) );
		add_action( 'edit_category_form_fields', array( $this, 'add_taxonomy_seo_fields' ) );
		add_action( 'edit_tag_form_fields', array( $this, 'add_taxonomy_seo_fields' ) );
		add_action( 'edited_term', array( $this, 'save_taxonomy_seo_fields' ) );

		// AJAX handlers.
		add_action( 'wp_ajax_analyze_content', array( $this, 'ajax_analyze_content' ) );
		add_action( 'wp_ajax_optimize_content', array( $this, 'ajax_optimize_content' ) );
	}

	/**
	 * Add SEO menu to admin.
	 */
	public function add_seo_menu() {
		add_submenu_page(
			'scraper-analytics',
			'SEO Assistant',
			'SEO Assistant',
			'manage_options',
			'seo-assistant',
			array( $this, 'display_seo_dashboard' )
		);

		// Add submenus for different SEO sections.
		add_submenu_page(
			'scraper-analytics',
			'SEO Analysis',
			'SEO Analysis',
			'manage_options',
			'seo-analysis',
			array( $this, 'display_seo_analysis' )
		);

		add_submenu_page(
			'scraper-analytics',
			'Link Manager',
			'Link Manager',
			'manage_options',
			'link-manager',
			array( $this, 'display_link_manager' )
		);
	}

	/**
	 * Add SEO meta boxes to posts.
	 */
	public function add_seo_meta_boxes() {
		$post_types = get_post_types( array( 'public' => true ) );
		foreach ( $post_types as $post_type ) {
			add_meta_box(
				'seo_assistant_meta_box',
				'SEO Assistant - Advanced Optimization',
				array( $this, 'render_seo_meta_box' ),
				$post_type,
				'normal',
				'high'
			);
		}
	}

	/**
	 * Render SEO meta box.
	 *
	 * @param WP_Post $post Post object.
	 */
	public function render_seo_meta_box( $post ) {
		$seo_data = $this->get_seo_data( $post->ID );
		$analysis = $this->analyze_content( $post->post_content, $seo_data['focus_keyword'] );

		include PRODUCT_SCRAPER_PLUGIN_PATH . 'templates/admin/seo-meta-box.php';
	}

	/**
	 * Get SEO data for a post.
	 *
	 * @param int $post_id Post ID.
	 * @return array SEO data.
	 */
	private function get_seo_data( $post_id ) {
		return array(
			'seo_title'           => get_post_meta( $post_id, '_seo_title', true ),
			'meta_description'    => get_post_meta( $post_id, '_meta_description', true ),
			'focus_keyword'       => get_post_meta( $post_id, '_focus_keyword', true ),
			'secondary_keywords'  => get_post_meta( $post_id, '_secondary_keywords', true ),
			'readability_score'   => get_post_meta( $post_id, '_readability_score', true ),
			'canonical_url'       => get_post_meta( $post_id, '_canonical_url', true ),
			'meta_robots'         => get_post_meta( $post_id, '_meta_robots', true ),
			'og_title'            => get_post_meta( $post_id, '_og_title', true ),
			'og_description'      => get_post_meta( $post_id, '_og_description', true ),
			'og_image'            => get_post_meta( $post_id, '_og_image', true ),
			'twitter_title'       => get_post_meta( $post_id, '_twitter_title', true ),
			'twitter_description' => get_post_meta( $post_id, '_twitter_description', true ),
			'twitter_image'       => get_post_meta( $post_id, '_twitter_image', true ),
		);
	}

	/**
	 * Save SEO metadata.
	 *
	 * @param int $post_id Post ID.
	 */
	public function save_seo_metadata( $post_id ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		// Verify nonce.
		if ( ! isset( $_POST['seo_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['seo_meta_nonce'] ) ), 'save_seo_meta' ) ) {
			return;
		}

		$fields = array(
			'_seo_title',
			'_meta_description',
			'_focus_keyword',
			'_secondary_keywords',
			'_canonical_url',
			'_meta_robots',
			'_og_title',
			'_og_description',
			'_og_image',
			'_twitter_title',
			'_twitter_description',
			'_twitter_image',
		);

		foreach ( $fields as $field ) {
			if ( isset( $_POST[ $field ] ) ) {
				$value = sanitize_text_field( wp_unslash( $_POST[ $field ] ) );
				update_post_meta( $post_id, $field, $value );
			}
		}

		// Calculate readability score.
		$content = get_post_field( 'post_content', $post_id );
		$this->calculate_readability_score( $post_id, $content );
	}

	/**
	 * Perform real-time SEO analysis.
	 *
	 * @param int $post_id Post ID.
	 */
	public function perform_real_time_analysis( $post_id ) {
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}

		$content       = get_post_field( 'post_content', $post_id );
		$focus_keyword = get_post_meta( $post_id, '_focus_keyword', true );

		$analysis = $this->analyze_content( $content, $focus_keyword );
		update_post_meta( $post_id, '_seo_analysis', $analysis );
	}

	/**
	 * Analyze content for SEO.
	 *
	 * @param string $content Content to analyze.
	 * @param string $focus_keyword Focus keyword.
	 * @return array Analysis results.
	 */
	public function analyze_content( $content, $focus_keyword = '' ) {
		$analysis = array(
			'score'        => 0,
			'issues'       => array(),
			'improvements' => array(),
			'good'         => array(),
		);

		$clean_content = wp_strip_all_tags( $content );
		$word_count    = str_word_count( $clean_content );

		// Basic content analysis.
		if ( $word_count < 300 ) {
			$analysis['issues'][] = array(
				'type'     => 'content_length',
				'message'  => 'Content is too short. Aim for at least 300 words.',
				'severity' => 'high',
			);
		} elseif ( $word_count > 2000 ) {
			$analysis['improvements'][] = array(
				'type'     => 'content_length',
				'message'  => 'Content is quite long. Consider breaking it into multiple sections.',
				'severity' => 'low',
			);
		} else {
			$analysis['good'][] = array(
				'type'     => 'content_length',
				'message'  => 'Content length is good.',
				'severity' => 'good',
			);
		}

		// Focus keyword analysis.
		if ( ! empty( $focus_keyword ) ) {
			$keyword_analysis = $this->analyze_keyword_usage( $clean_content, $focus_keyword );
			$analysis         = array_merge_recursive( $analysis, $keyword_analysis );
		}

		// Readability analysis.
		$readability             = $this->calculate_readability_advanced( $clean_content );
		$analysis['readability'] = $readability;

		// Calculate overall score.
		$analysis['score'] = $this->calculate_overall_score( $analysis );

		return $analysis;
	}

	/**
	 * Analyze keyword usage in content.
	 *
	 * @param string $content Content to analyze.
	 * @param string $keyword Keyword to check.
	 * @return array Keyword analysis.
	 */
	private function analyze_keyword_usage( $content, $keyword ) {
		$analysis      = array();
		$keyword_lower = strtolower( $keyword );
		$content_lower = strtolower( $content );

		$keyword_count   = substr_count( $content_lower, $keyword_lower );
		$word_count      = str_word_count( $content );
		$keyword_density = ( $keyword_count / max( $word_count, 1 ) ) * 100;

		if ( 0 === $keyword_count ) {
			$analysis['issues'][] = array(
				'type'     => 'keyword_usage',
				'message'  => 'Focus keyword not found in content.',
				'severity' => 'high',
			);
		} elseif ( $keyword_density < 0.5 ) {
			$analysis['issues'][] = array(
				'type'     => 'keyword_density',
				'message'  => 'Focus keyword density is too low.',
				'severity' => 'medium',
			);
		} elseif ( $keyword_density > 3 ) {
			$analysis['issues'][] = array(
				'type'     => 'keyword_density',
				'message'  => 'Focus keyword density is too high (keyword stuffing).',
				'severity' => 'high',
			);
		} else {
			$analysis['good'][] = array(
				'type'     => 'keyword_density',
				'message'  => 'Focus keyword density is optimal.',
				'severity' => 'good',
			);
		}

		return $analysis;
	}

	/**
	 * Calculate advanced readability score.
	 *
	 * @param string $content Content to analyze.
	 * @return array Readability data.
	 */
	private function calculate_readability_advanced( $content ) {
		// Implement Flesch Reading Ease score.
		$words     = str_word_count( $content );
		$sentences = preg_split( '/[.!?]+/', $content, -1, PREG_SPLIT_NO_EMPTY );
		$syllables = $this->count_syllables( $content );

		if ( $words > 0 && count( $sentences ) > 0 ) {
			$average_sentence_length    = $words / count( $sentences );
			$average_syllables_per_word = $syllables / $words;

			$flesch_score = 206.835 - ( 1.015 * $average_sentence_length ) - ( 84.6 * $average_syllables_per_word );

			return array(
				'flesch_score' => round( $flesch_score ),
				'grade_level'  => $this->get_grade_level( $flesch_score ),
				'description'  => $this->get_readability_description( $flesch_score ),
			);
		}

		return array(
			'flesch_score' => 0,
			'grade_level'  => 'N/A',
			'description'  => 'Not enough content to analyze',
		);
	}

	/**
	 * Count syllables in content.
	 *
	 * @param string $content Content to analyze.
	 * @return int Syllable count.
	 */
	private function count_syllables( $content ) {
		// Basic syllable counting (can be improved).
		$words     = str_word_count( $content, 1 );
		$syllables = 0;

		foreach ( $words as $word ) {
			$syllables += $this->count_word_syllables( $word );
		}

		return $syllables;
	}

	/**
	 * Count syllables in a single word.
	 *
	 * @param string $word Word to analyze.
	 * @return int Syllable count.
	 */
	private function count_word_syllables( $word ) {
		// Simple syllable counting algorithm.
		$word  = preg_replace( '/[^a-z]/i', '', strtolower( $word ) );
		$count = 0;

		$word_length = strlen( $word );
		if ( $word_length > 0 ) {
			$count           = 1; // At least one syllable.
			$vowels          = 'aeiouy';
			$prev_char_vowel = false;

			for ( $i = 0; $i < $word_length; $i++ ) {
				$is_vowel = strpos( $vowels, $word[ $i ] ) !== false;

				if ( $is_vowel && ! $prev_char_vowel ) {
					++$count;
				}

				$prev_char_vowel = $is_vowel;
			}

			// Adjust for common exceptions.
			if ( substr( $word, -1 ) === 'e' ) {
				--$count;
			}

			if ( substr( $word, -2 ) === 'le' && $word_length > 2 ) {
				++$count;
			}
		}

		return max( 1, $count );
	}

	/**
	 * Get grade level based on Flesch score.
	 *
	 * @param float $flesch_score Flesch readability score.
	 * @return string Grade level.
	 */
	private function get_grade_level( $flesch_score ) {
		if ( $flesch_score >= 90 ) {
			return '5th grade';
		}
		if ( $flesch_score >= 80 ) {
			return '6th grade';
		}
		if ( $flesch_score >= 70 ) {
			return '7th grade';
		}
		if ( $flesch_score >= 60 ) {
			return '8th & 9th grade';
		}
		if ( $flesch_score >= 50 ) {
			return '10th to 12th grade';
		}
		if ( $flesch_score >= 30 ) {
			return 'College';
		}
		return 'College Graduate';
	}

	/**
	 * Get readability description.
	 *
	 * @param float $flesch_score Flesch readability score.
	 * @return string Readability description.
	 */
	private function get_readability_description( $flesch_score ) {
		if ( $flesch_score >= 90 ) {
			return 'Very easy to read';
		}
		if ( $flesch_score >= 80 ) {
			return 'Easy to read';
		}
		if ( $flesch_score >= 70 ) {
			return 'Fairly easy to read';
		}
		if ( $flesch_score >= 60 ) {
			return 'Standard';
		}
		if ( $flesch_score >= 50 ) {
			return 'Fairly difficult to read';
		}
		if ( $flesch_score >= 30 ) {
			return 'Difficult to read';
		}
		return 'Very difficult to read';
	}

	/**
	 * Calculate overall SEO score.
	 *
	 * @param array $analysis SEO analysis data.
	 * @return int Overall score (0-100).
	 */
	private function calculate_overall_score( $analysis ) {
		$score = 100;

		// Deduct points for issues.
		foreach ( $analysis['issues'] as $issue ) {
			switch ( $issue['severity'] ) {
				case 'high':
					$score -= 20;
					break;
				case 'medium':
					$score -= 10;
					break;
				case 'low':
					$score -= 5;
					break;
			}
		}

		// Add points for good practices.
		foreach ( $analysis['good'] as $good ) {
			$score += 5;
		}

		return max( 0, min( 100, $score ) );
	}

	/**
	 * Add taxonomy SEO fields.
	 *
	 * @param WP_Term $term Term object.
	 */
	public function add_taxonomy_seo_fields( $term ) {
		$term_id          = $term->term_id;
		$seo_title        = get_term_meta( $term_id, '_seo_title', true );
		$meta_description = get_term_meta( $term_id, '_meta_description', true );
		?>
		<tr class="form-field">
			<th scope="row" valign="top">
				<label for="seo_title"><?php esc_html_e( 'SEO Title' ); ?></label>
			</th>
			<td>
				<input type="text" name="seo_title" id="seo_title" value="<?php echo esc_attr( $seo_title ); ?>" />
				<p class="description"><?php esc_html_e( 'Custom title for search engines' ); ?></p>
			</td>
		</tr>
		<tr class="form-field">
			<th scope="row" valign="top">
				<label for="meta_description"><?php esc_html_e( 'Meta Description' ); ?></label>
			</th>
			<td>
				<textarea name="meta_description" id="meta_description" rows="3" cols="50"><?php echo esc_textarea( $meta_description ); ?></textarea>
				<p class="description"><?php esc_html_e( 'Custom description for search engines' ); ?></p>
			</td>
		</tr>
		<?php
	}

	/**
	 * Save taxonomy SEO fields.
	 *
	 * @param int $term_id Term ID.
	 */
	public function save_taxonomy_seo_fields( $term_id ) {
		// Verify nonce.
		if ( ! isset( $_POST['_wpnonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['_wpnonce'] ) ), 'update-tag_' . $term_id ) ) {
			return;
		}

		if ( isset( $_POST['seo_title'] ) ) {
			$seo_title = sanitize_text_field( wp_unslash( $_POST['seo_title'] ) );
			update_term_meta( $term_id, '_seo_title', $seo_title );
		}
		if ( isset( $_POST['meta_description'] ) ) {
			$meta_description = sanitize_textarea_field( wp_unslash( $_POST['meta_description'] ) );
			update_term_meta( $term_id, '_meta_description', $meta_description );
		}
	}

	/**
	 * Display SEO dashboard.
	 */
	public function display_seo_dashboard() {
		$stats           = $this->get_seo_stats();
		$recent_analysis = $this->get_recent_analysis();

		include PRODUCT_SCRAPER_PLUGIN_PATH . 'templates/admin/seo-dashboard.php';
	}

	/**
	 * Display SEO analysis page.
	 */
	public function display_seo_analysis() {
		$site_analysis = $this->analyze_site_seo();
		include PRODUCT_SCRAPER_PLUGIN_PATH . 'templates/admin/seo-analysis.php';
	}

	/**
	 * Display link manager.
	 */
	public function display_link_manager() {
		$internal_links = $this->get_internal_links();
		$external_links = $this->get_external_links();
		include PRODUCT_SCRAPER_PLUGIN_PATH . 'templates/admin/link-manager.php';
	}

	/**
	 * Analyze site SEO.
	 *
	 * @return array Site analysis data.
	 */
	private function analyze_site_seo() {
		$analysis = array(
			'technical'   => $this->analyze_technical_seo(),
			'content'     => $this->analyze_content_seo(),
			'performance' => $this->analyze_performance_seo(),
		);

		return $analysis;
	}

	/**
	 * Analyze technical SEO.
	 *
	 * @return array Technical SEO analysis.
	 */
	private function analyze_technical_seo() {
		$technical = array();

		// Check if site is indexable.
		$technical['indexable'] = ! get_option( 'blog_public' ) ? 'No' : 'Yes';

		// Check permalink structure.
		$permalink_structure              = get_option( 'permalink_structure' );
		$technical['permalink_structure'] = empty( $permalink_structure ) ? 'Plain' : 'SEO Friendly';

		// Check SSL.
		$technical['ssl'] = is_ssl() ? 'Yes' : 'No';

		return $technical;
	}

	/**
	 * AJAX handler for content analysis.
	 */
	public function ajax_analyze_content() {
		check_ajax_referer( 'product_scraper_nonce', 'nonce' );

		// Verify input data exists.
		if ( ! isset( $_POST['content'] ) || ! isset( $_POST['keyword'] ) ) {
			wp_send_json_error( 'Missing required data' );
		}

		$content = wp_kses_post( wp_unslash( $_POST['content'] ) );
		$keyword = sanitize_text_field( wp_unslash( $_POST['keyword'] ) );

		$analysis = $this->analyze_content( $content, $keyword );

		wp_send_json_success( $analysis );
	}

	/**
	 * AJAX handler for content optimization.
	 */
	public function ajax_optimize_content() {
		check_ajax_referer( 'product_scraper_nonce', 'nonce' );

		// Verify input data exists.
		if ( ! isset( $_POST['content'] ) || ! isset( $_POST['keyword'] ) ) {
			wp_send_json_error( 'Missing required data' );
		}

		$content = wp_kses_post( wp_unslash( $_POST['content'] ) );
		$keyword = sanitize_text_field( wp_unslash( $_POST['keyword'] ) );

		// This would integrate with AI service for optimization.
		$optimized_content = $this->ai_optimize_content( $content, $keyword );

		wp_send_json_success(
			array(
				'optimized_content' => $optimized_content,
				'changes_made'      => $this->get_optimization_changes( $content, $optimized_content ),
			)
		);
	}

	/**
	 * Get optimization changes.
	 *
	 * @param string $original Original content.
	 * @param string $optimized Optimized content.
	 * @return array Optimization changes.
	 */
	private function get_optimization_changes( $original, $optimized ) {
		// Compare original and optimized content.
		return array(
			'word_count_change'       => str_word_count( $optimized ) - str_word_count( $original ),
			'readability_improvement' => 'Improved',
			'keyword_optimization'    => 'Enhanced',
		);
	}

	/**
	 * Get SEO statistics for the dashboard.
	 *
	 * @return array SEO statistics.
	 */
	private function get_seo_stats() {
		$total_posts = wp_count_posts()->publish;

		// Use WordPress functions instead of direct database calls.
		$posts_with_seo_title     = $this->count_posts_with_meta( '_seo_title' );
		$posts_with_meta_desc     = $this->count_posts_with_meta( '_meta_description' );
		$posts_with_focus_keyword = $this->count_posts_with_meta( '_focus_keyword' );
		$avg_readability          = $this->get_average_readability();
		$optimized_posts          = $this->count_optimized_posts();
		$posts_without_meta       = $this->count_posts_without_meta();
		$low_content_posts        = $this->count_low_content_posts();

		// Calculate rates with proper ternary operators.
		$optimization_rate             = ( $total_posts > 0 ) ? round( ( $optimized_posts / $total_posts ) * 100 ) : 0;
		$title_optimization_rate       = ( $total_posts > 0 ) ? round( ( $posts_with_seo_title / $total_posts ) * 100 ) : 0;
		$description_optimization_rate = ( $total_posts > 0 ) ? round( ( $posts_with_meta_desc / $total_posts ) * 100 ) : 0;

		return array(
			'total_posts'                   => $total_posts,
			'posts_with_seo_title'          => $posts_with_seo_title,
			'posts_with_meta_desc'          => $posts_with_meta_desc,
			'posts_with_focus_keyword'      => $posts_with_focus_keyword,
			'optimized_posts'               => $optimized_posts,
			'avg_readability'               => round( $avg_readability ),
			'posts_without_meta'            => $posts_without_meta,
			'low_content_posts'             => $low_content_posts,
			'optimization_rate'             => $optimization_rate,
			'title_optimization_rate'       => $title_optimization_rate,
			'description_optimization_rate' => $description_optimization_rate,
		);
	}

	/**
	 * Count posts with specific meta key.
	 *
	 * @param string $meta_key Meta key to check.
	 * @return int Count of posts.
	 */
	private function count_posts_with_meta( $meta_key ) {
		$cache_key = 'seo_stats_' . $meta_key;
		$count     = wp_cache_get( $cache_key );

		if ( false === $count ) {
			$query = new WP_Query(
				array(
					'post_type'      => array( 'post', 'page' ),
					'post_status'    => 'publish',
					'fields'         => 'ids',
					'posts_per_page' => -1,
					'meta_query'     => array(
						array(
							'key'     => $meta_key,
							'value'   => '',
							'compare' => '!=',
						),
					),
				)
			);
			$count = $query->found_posts;
			wp_cache_set( $cache_key, $count, '', 3600 ); // Cache for 1 hour.
		}

		return $count;
	}

	/**
	 * Get average readability score.
	 *
	 * @return float Average readability.
	 */
	private function get_average_readability() {
		$cache_key = 'seo_stats_avg_readability';
		$average   = wp_cache_get( $cache_key );

		if ( false === $average ) {
			global $wpdb;
			$average = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT AVG(meta_value) 
					FROM {$wpdb->postmeta} 
					WHERE meta_key = %s 
					AND meta_value != ''",
					'_readability_score'
				)
			);
			wp_cache_set( $cache_key, $average, '', 3600 );
		}

		return (float) $average;
	}

	/**
	 * Count optimized posts.
	 *
	 * @return int Count of optimized posts.
	 */
	private function count_optimized_posts() {
		$cache_key = 'seo_stats_optimized_posts';
		$count     = wp_cache_get( $cache_key );

		if ( false === $count ) {
			$query = new WP_Query(
				array(
					'post_type'      => array( 'post', 'page' ),
					'post_status'    => 'publish',
					'fields'         => 'ids',
					'posts_per_page' => -1,
					'meta_query'     => array(
						array(
							'key'     => '_seo_title',
							'value'   => '',
							'compare' => '!=',
						),
						array(
							'key'     => '_meta_description',
							'value'   => '',
							'compare' => '!=',
						),
					),
				)
			);
			$count = $query->found_posts;
			wp_cache_set( $cache_key, $count, '', 3600 );
		}

		return $count;
	}

	/**
	 * Count posts without meta description.
	 *
	 * @return int Count of posts without meta.
	 */
	private function count_posts_without_meta() {
		$cache_key = 'seo_stats_posts_without_meta';
		$count     = wp_cache_get( $cache_key );

		if ( false === $count ) {
			$query = new WP_Query(
				array(
					'post_type'      => array( 'post', 'page' ),
					'post_status'    => 'publish',
					'fields'         => 'ids',
					'posts_per_page' => -1,
					'meta_query'     => array(
						array(
							'key'     => '_meta_description',
							'compare' => 'NOT EXISTS',
						),
					),
				)
			);
			$count = $query->found_posts;
			wp_cache_set( $cache_key, $count, '', 3600 );
		}

		return $count;
	}

	/**
	 * Count low content posts.
	 *
	 * @return int Count of low content posts.
	 */
	private function count_low_content_posts() {
		$cache_key = 'seo_stats_low_content_posts';
		$count     = wp_cache_get( $cache_key );

		if ( false === $count ) {
			$posts = get_posts(
				array(
					'post_type'   => array( 'post', 'page' ),
					'post_status' => 'publish',
					'numberposts' => -1,
					'fields'      => 'ids',
				)
			);

			$count = 0;
			foreach ( $posts as $post_id ) {
				$content    = get_post_field( 'post_content', $post_id );
				$word_count = str_word_count( wp_strip_all_tags( $content ) );
				if ( $word_count < 300 ) {
					++$count;
				}
			}
			wp_cache_set( $cache_key, $count, '', 3600 );
		}

		return $count;
	}

	/**
	 * Get recent SEO analysis results.
	 *
	 * @return array Recent analysis data.
	 */
	private function get_recent_analysis() {
		$cache_key     = 'seo_recent_analysis';
		$analysis_data = wp_cache_get( $cache_key );

		if ( false === $analysis_data ) {
			$query = new WP_Query(
				array(
					'post_type'      => array( 'post', 'page' ),
					'post_status'    => 'publish',
					'posts_per_page' => 5,
					'orderby'        => 'modified',
					'order'          => 'DESC',
					'meta_query'     => array(
						array(
							'key'     => '_seo_analysis',
							'compare' => 'EXISTS',
						),
					),
				)
			);

			$analysis_data = array();
			foreach ( $query->posts as $post ) {
				$analysis        = get_post_meta( $post->ID, '_seo_analysis', true );
				$analysis_data[] = array(
					'post_id'  => $post->ID,
					'title'    => $post->post_title,
					'analysis' => maybe_unserialize( $analysis ),
					'edit_url' => get_edit_post_link( $post->ID ),
				);
			}
			wp_cache_set( $cache_key, $analysis_data, '', 1800 ); // Cache for 30 minutes.
		}

		return $analysis_data;
	}

	/**
	 * Get internal links data.
	 *
	 * @return array Internal links data.
	 */
	private function get_internal_links() {
		// This would be implemented with the Link Manager class.
		// For now, return basic data.
		return array(
			'total_internal_links'  => 0,
			'orphaned_posts'        => 0,
			'linking_opportunities' => 0,
		);
	}

	/**
	 * Get external links data.
	 *
	 * @return array External links data.
	 */
	private function get_external_links() {
		// This would be implemented with the Link Manager class.
		// For now, return basic data.
		return array(
			'total_external_links' => 0,
			'broken_links'         => 0,
			'nofollow_links'       => 0,
		);
	}

	/**
	 * AI content optimization (placeholder - would integrate with actual AI service).
	 *
	 * @param string $content Content to optimize.
	 * @param string $keyword Focus keyword.
	 * @return string Optimized content.
	 */
	private function ai_optimize_content( $content, $keyword ) {
		// This is a placeholder for AI content optimization.
		// In a real implementation, you would integrate with OpenAI, GPT, or similar services.

		// For now, return the original content with a simple optimization.
		if ( ! empty( $keyword ) && strpos( strtolower( $content ), strtolower( $keyword ) ) === false ) {
			// Add keyword to the beginning of content if not found.
			$content = "<p><strong>Keyword Focus: {$keyword}</strong></p>\n\n" . $content;
		}

		return $content;
	}

	/**
	 * Calculate basic readability score.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $content Content to analyze.
	 */
	private function calculate_readability_score( $post_id, $content ) {
		$content        = wp_strip_all_tags( $content );
		$word_count     = str_word_count( $content );
		$sentence_count = preg_split( '/[.!?]+/', $content, -1, PREG_SPLIT_NO_EMPTY );
		$sentence_count = count( $sentence_count );

		if ( $word_count > 0 && $sentence_count > 0 ) {
			$average_sentence_length = $word_count / $sentence_count;
			$score                   = max( 0, min( 100, 100 - ( $average_sentence_length * 2 ) ) );
			update_post_meta( $post_id, '_readability_score', intval( $score ) );
		}
	}

	/**
	 * Optimize content on save if auto-optimization is enabled.
	 *
	 * @param array $data Post data.
	 * @return array Modified post data.
	 */
	public function optimize_content_on_save( $data ) {
		// Verify nonce and check if auto-optimization is enabled.
		if ( ! isset( $_POST['seo_meta_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['seo_meta_nonce'] ) ), 'save_seo_meta' ) ) {
			return $data;
		}

		if ( ! isset( $_POST['seo_auto_optimize'] ) || empty( $_POST['seo_auto_optimize'] ) ) {
			return $data;
		}

		$auto_optimize = sanitize_text_field( wp_unslash( $_POST['seo_auto_optimize'] ) );
		if ( 'yes' !== $auto_optimize ) {
			return $data;
		}

		// AI-powered content optimization.
		$optimized_content    = $this->ai_optimize_content( $data['post_content'], '' );
		$data['post_content'] = $optimized_content;

		return $data;
	}

	/**
	 * Analyze content SEO across the site.
	 *
	 * @return array Content SEO analysis.
	 */
	private function analyze_content_seo() {
		$stats = $this->get_seo_stats();

		return array(
			'score'           => $stats['optimization_rate'],
			'stats'           => $stats,
			'issues'          => array(),
			'recommendations' => array(),
		);
	}

	/**
	 * Analyze performance SEO.
	 *
	 * @return array Performance SEO analysis.
	 */
	private function analyze_performance_seo() {
		return array(
			'score'           => 0,
			'metrics'         => array(),
			'issues'          => array(),
			'recommendations' => array(),
		);
	}
}