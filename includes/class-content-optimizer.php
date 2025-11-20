<?php

class ProductScraper_Content_Optimizer {


	public function __construct() {
		add_action( 'wp_ajax_analyze_content', array( $this, 'ajax_analyze_content' ) );
		add_action( 'wp_ajax_optimize_content', array( $this, 'ajax_optimize_content' ) );
	}

	public function ajax_analyze_content() {
		if ( ! wp_verify_nonce( $_POST['nonce'], 'content_analysis_nonce' ) ) {
			wp_die( 'Security check failed' );
		}

		$content       = wp_kses_post( $_POST['content'] );
		$focus_keyword = sanitize_text_field( $_POST['focus_keyword'] );

		$analysis = $this->analyze_content( $content, $focus_keyword );

		wp_send_json_success( $analysis );
	}

	private function analyze_content( $content, $focus_keyword = '' ) {
		$clean_content  = wp_strip_all_tags( $content );
		$word_count     = str_word_count( $clean_content );
		$content_length = strlen( $clean_content );

		$analysis = array(
			'word_count'        => $word_count,
			'content_length'    => $content_length,
			'reading_time'      => ceil( $word_count / 200 ), // 200 words per minute
			'keyword_density'   => 0,
			'readability_score' => $this->calculate_flesch_score( $clean_content ),
			'headings'          => $this->analyze_headings( $content ),
			'images'            => $this->count_images( $content ),
			'links'             => $this->count_links( $content ),
			'recommendations'   => array(),
		);

		if ( ! empty( $focus_keyword ) ) {
			$analysis['keyword_density']  = $this->calculate_keyword_density( $clean_content, $focus_keyword );
			$analysis['keyword_position'] = $this->check_keyword_position( $content, $focus_keyword );
		}

		$analysis['recommendations'] = $this->generate_recommendations( $analysis );

		return $analysis;
	}

	private function calculate_flesch_score( $content ) {
		$words     = str_word_count( $content );
		$sentences = preg_split( '/[.!?]+/', $content, -1, PREG_SPLIT_NO_EMPTY );
		$syllables = $this->count_syllables( $content );

		if ( $words > 0 && count( $sentences ) > 0 ) {
			$avg_sentence_length    = $words / count( $sentences );
			$avg_syllables_per_word = $syllables / $words;

			$score = 206.835 - ( 1.015 * $avg_sentence_length ) - ( 84.6 * $avg_syllables_per_word );
			return max( 0, min( 100, round( $score ) ) );
		}

		return 0;
	}

	private function count_syllables( $content ) {
		// Simplified syllable count
		$vowels = '/[aeiouy]+/i';
		preg_match_all( $vowels, $content, $matches );
		return count( $matches[0] );
	}

	private function analyze_headings( $content ) {
		$headings = array(
			'h1' => array(),
			'h2' => array(),
			'h3' => array(),
			'h4' => array(),
			'h5' => array(),
			'h6' => array(),
		);

		preg_match_all( '/<h[1-6][^>]*>(.*?)<\/h[1-6]>/i', $content, $matches );

		foreach ( $matches[0] as $match ) {
			preg_match( '/<h([1-6])/i', $match, $level );
			if ( isset( $level[1] ) ) {
				$heading_text                  = wp_strip_all_tags( $match );
				$headings[ 'h' . $level[1] ][] = $heading_text;
			}
		}

		return $headings;
	}

	private function count_images( $content ) {
		preg_match_all( '/<img[^>]+>/i', $content, $matches );
		return count( $matches[0] );
	}

	private function count_links( $content ) {
		preg_match_all( '/<a[^>]+>/i', $content, $matches );
		return count( $matches[0] );
	}

	private function calculate_keyword_density( $content, $keyword ) {
		$word_count    = str_word_count( $content );
		$keyword_count = substr_count( strtolower( $content ), strtolower( $keyword ) );

		if ( $word_count > 0 ) {
			return round( ( $keyword_count / $word_count ) * 100, 2 );
		}

		return 0;
	}

	private function check_keyword_position( $content, $keyword ) {
		$positions = array(
			'in_title'            => false,
			'in_first_paragraph'  => false,
			'in_meta_description' => false,
			'in_url'              => false,
		);

		$clean_content   = wp_strip_all_tags( $content );
		$first_paragraph = substr( $clean_content, 0, 200 );

		$positions['in_first_paragraph'] = stripos( $first_paragraph, $keyword ) !== false;

		return $positions;
	}

	private function generate_recommendations( $analysis ) {
		$recommendations = array();

		if ( $analysis['word_count'] < 300 ) {
			$recommendations[] = array(
				'type'     => 'content_length',
				'message'  => 'Content is too short. Aim for at least 300 words for better SEO.',
				'priority' => 'high',
			);
		}

		if ( $analysis['readability_score'] < 60 ) {
			$recommendations[] = array(
				'type'     => 'readability',
				'message'  => 'Content may be difficult to read. Try using shorter sentences and simpler words.',
				'priority' => 'medium',
			);
		}

		if ( count( $analysis['headings']['h2'] ) === 0 ) {
			$recommendations[] = array(
				'type'     => 'headings',
				'message'  => 'Add H2 headings to structure your content better.',
				'priority' => 'medium',
			);
		}

		if ( $analysis['images'] === 0 ) {
			$recommendations[] = array(
				'type'     => 'images',
				'message'  => 'Add relevant images to make your content more engaging.',
				'priority' => 'low',
			);
		}

		return $recommendations;
	}

	public function ajax_optimize_content() {
		if ( ! wp_verify_nonce( $_POST['nonce'], 'content_optimization_nonce' ) ) {
			wp_die( 'Security check failed' );
		}

		$content           = wp_kses_post( $_POST['content'] );
		$optimization_type = sanitize_text_field( $_POST['optimization_type'] );

		$optimized_content = $this->optimize_content( $content, $optimization_type );

		wp_send_json_success(
			array(
				'optimized_content' => $optimized_content,
				'changes_made'      => $this->explain_changes( $content, $optimized_content ),
			)
		);
	}

	private function optimize_content( $content, $type ) {
		// This would integrate with AI services
		// For now, return some basic optimizations

		switch ( $type ) {
			case 'readability':
				return $this->improve_readability( $content );
			case 'seo':
				return $this->optimize_for_seo( $content );
			case 'engagement':
				return $this->improve_engagement( $content );
			default:
				return $content;
		}
	}

	private function improve_readability( $content ) {
		// Basic readability improvements
		$content = preg_replace( '/\.\s*/', '. ', $content ); // Ensure proper spacing
		return $content;
	}

	private function optimize_for_seo( $content ) {
		// Basic SEO optimizations
		return $content;
	}

	private function improve_engagement( $content ) {
		// Basic engagement improvements
		return $content;
	}

	private function explain_changes( $original, $optimized ) {
		$changes = array();

		$original_word_count  = str_word_count( wp_strip_all_tags( $original ) );
		$optimized_word_count = str_word_count( wp_strip_all_tags( $optimized ) );

		if ( $optimized_word_count > $original_word_count ) {
			$changes[] = "Increased word count from {$original_word_count} to {$optimized_word_count}";
		}

		return $changes;
	}
}
