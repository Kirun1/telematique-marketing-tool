<?php

class ProductScraper_Keyword_Research {


	public function __construct() {
		add_action( 'wp_ajax_research_keywords', array( $this, 'ajax_research_keywords' ) );
		add_action( 'wp_ajax_get_keyword_suggestions', array( $this, 'ajax_get_keyword_suggestions' ) );
	}

	public function ajax_research_keywords() {
		if ( ! wp_verify_nonce( $_POST['nonce'], 'keyword_research_nonce' ) ) {
			wp_die( 'Security check failed' );
		}

		$keyword        = sanitize_text_field( $_POST['keyword'] );
		$competitor_url = esc_url_raw( $_POST['competitor_url'] ?? '' );

		$research_data = $this->research_keyword( $keyword, $competitor_url );

		wp_send_json_success( $research_data );
	}

	private function research_keyword( $keyword, $competitor_url = '' ) {
		$data = array(
			'keyword'             => $keyword,
			'volume_estimate'     => $this->estimate_search_volume( $keyword ),
			'competition'         => $this->estimate_competition( $keyword ),
			'trend'               => $this->get_trend_data( $keyword ),
			'related_keywords'    => $this->get_related_keywords( $keyword ),
			'content_gaps'        => array(),
			'competitor_analysis' => array(),
		);

		if ( ! empty( $competitor_url ) ) {
			$data['competitor_analysis'] = $this->analyze_competitor_keywords( $competitor_url, $keyword );
		}

		$data['content_gaps'] = $this->identify_content_gaps( $keyword, $data );

		return $data;
	}

	private function estimate_search_volume( $keyword ) {
		// This would integrate with keyword research APIs
		// For now, return mock data
		$base_volume = rand( 100, 10000 );
		return array(
			'monthly'     => $base_volume,
			'trend'       => 'stable',
			'seasonality' => 'low',
		);
	}

	private function estimate_competition( $keyword ) {
		// Mock competition analysis
		$competition_score = rand( 1, 100 );
		$difficulty        = $competition_score > 70 ? 'high' : ( $competition_score > 40 ? 'medium' : 'low' );

		return array(
			'score'       => $competition_score,
			'difficulty'  => $difficulty,
			'advertisers' => rand( 1, 20 ),
		);
	}

	private function get_trend_data( $keyword ) {
		// Mock trend data
		$months = 12;
		$trend  = array();

		for ( $i = $months; $i >= 0; $i-- ) {
			$date           = date( 'M Y', strtotime( "-$i months" ) );
			$trend[ $date ] = rand( 80, 120 );
		}

		return $trend;
	}

	private function get_related_keywords( $keyword ) {
		// Mock related keywords
		$related   = array();
		$modifiers = array( 'best', 'buy', 'review', 'price', 'how to', 'vs', 'alternative' );

		foreach ( $modifiers as $modifier ) {
			$related[] = array(
				'keyword'     => "$modifier $keyword",
				'volume'      => rand( 50, 5000 ),
				'competition' => rand( 1, 100 ),
			);
		}

		// Sort by volume
		usort(
			$related,
			function ( $a, $b ) {
				return $b['volume'] - $a['volume'];
			}
		);

		return array_slice( $related, 0, 10 );
	}

	private function analyze_competitor_keywords( $url, $focus_keyword ) {
		// Basic competitor analysis
		return array(
			'ranking_keywords' => $this->get_competitor_ranking_keywords( $url ),
			'top_pages'        => $this->get_competitor_top_pages( $url ),
			'keyword_overlap'  => $this->calculate_keyword_overlap( $url, $focus_keyword ),
		);
	}

	private function identify_content_gaps( $keyword, $research_data ) {
		$gaps = array();

		// Analyze related keywords that have low competition
		foreach ( $research_data['related_keywords'] as $related ) {
			if ( $related['competition'] < 30 && $related['volume'] > 100 ) {
				$gaps[] = array(
					'keyword'           => $related['keyword'],
					'opportunity_score' => $this->calculate_opportunity_score( $related ),
					'content_type'      => $this->suggest_content_type( $related['keyword'] ),
				);
			}
		}

		return array_slice( $gaps, 0, 5 );
	}

	private function calculate_opportunity_score( $keyword_data ) {
		$volume_weight      = 0.6;
		$competition_weight = 0.4;

		$volume_score      = min( $keyword_data['volume'] / 1000, 1 );
		$competition_score = 1 - ( $keyword_data['competition'] / 100 );

		return round( ( $volume_score * $volume_weight + $competition_score * $competition_weight ) * 100 );
	}

	private function suggest_content_type( $keyword ) {
		$types         = array( 'blog_post', 'product_page', 'guide', 'review', 'comparison' );
		$keyword_lower = strtolower( $keyword );

		if ( strpos( $keyword_lower, 'how to' ) !== false ) {
			return 'guide';
		}
		if ( strpos( $keyword_lower, 'vs' ) !== false ) {
			return 'comparison';
		}
		if ( strpos( $keyword_lower, 'review' ) !== false ) {
			return 'review';
		}
		if ( strpos( $keyword_lower, 'buy' ) !== false ) {
			return 'product_page';
		}

		return $types[ array_rand( $types ) ];
	}

	public function ajax_get_keyword_suggestions() {
		if ( ! wp_verify_nonce( $_POST['nonce'], 'keyword_suggestions_nonce' ) ) {
			wp_die( 'Security check failed' );
		}

		$content     = wp_kses_post( $_POST['content'] );
		$suggestions = $this->generate_keyword_suggestions( $content );

		wp_send_json_success( $suggestions );
	}

	private function generate_keyword_suggestions( $content ) {
		$clean_content = wp_strip_all_tags( $content );
		$words         = str_word_count( $clean_content, 1 );

		// Remove stop words and count frequency
		$stop_words = $this->get_stop_words();
		$word_freq  = array_count_values( $words );

		foreach ( $stop_words as $stop_word ) {
			unset( $word_freq[ $stop_word ] );
		}

		arsort( $word_freq );
		$top_words = array_slice( $word_freq, 0, 10 );

		$suggestions = array();
		foreach ( $top_words as $word => $frequency ) {
			if ( strlen( $word ) > 3 ) { // Only consider words longer than 3 characters
				$suggestions[] = array(
					'keyword'   => $word,
					'frequency' => $frequency,
					'relevance' => 'high',
				);
			}
		}

		return $suggestions;
	}

	private function get_stop_words() {
		return array( 'the', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by', 'a', 'an', 'is', 'are', 'was', 'were' );
	}

	// Mock methods for competitor analysis
	private function get_competitor_ranking_keywords( $url ) {
		return array(
			array(
				'keyword'  => 'main product',
				'position' => 1,
			),
			array(
				'keyword'  => 'related service',
				'position' => 3,
			),
			array(
				'keyword'  => 'industry term',
				'position' => 5,
			),
		);
	}

	private function get_competitor_top_pages( $url ) {
		return array(
			array(
				'page'          => '/products/main-product',
				'traffic_share' => 35,
			),
			array(
				'page'          => '/blog/guide',
				'traffic_share' => 20,
			),
			array(
				'page'          => '/about',
				'traffic_share' => 15,
			),
		);
	}

	private function calculate_keyword_overlap( $url, $focus_keyword ) {
		return rand( 10, 80 );
	}
}
