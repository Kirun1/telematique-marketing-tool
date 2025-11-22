<?php
class ProductScraper_Rank_Tracker {

	private $api_key;
	private $search_engine;
	private $user_agent;

	public function __construct( $api_key = '' ) {
		$this->api_key       = $api_key;
		$this->search_engine = 'google';
		$this->user_agent    = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36';
	}

	public function track_keyword_ranks( $keywords, $competitors = array() ) {
		$results = array();

		foreach ( $keywords as $keyword ) {
			$serp_data = $this->get_serp_data( $keyword );
			$rank_data = array(
				'keyword'              => $keyword,
				'position'             => $this->get_own_rank( $serp_data, home_url() ),
				'serp_features'        => $this->get_serp_features( $keyword ),
				'competitor_positions' => $this->get_competitor_positions( $serp_data, $competitors ),
				'serp_analysis'        => $this->analyze_serp( $serp_data ),
				'timestamp'            => current_time( 'mysql' ),
				'date'                 => current_time( 'Y-m-d' ),
			);

			$results[ $keyword ] = $rank_data;
			$this->store_rank_data( $rank_data );

			// Respectful delay between requests.
			sleep( 2 );
		}

		return $results;
	}

	public function get_serp_features( $keyword ) {
		$serp_data = $this->get_serp_data( $keyword );

		return array(
			'featured_snippet' => $this->check_featured_snippet( $serp_data ),
			'people_also_ask'  => $this->check_people_also_ask( $serp_data ),
			'related_searches' => $this->check_related_searches( $serp_data ),
			'image_pack'       => $this->check_image_pack( $serp_data ),
			'video_carousel'   => $this->check_video_carousel( $serp_data ),
			'shopping_results' => $this->check_shopping_results( $serp_data ),
			'news_carousel'    => $this->check_news_carousel( $serp_data ),
			'site_links'       => $this->check_site_links( $serp_data ),
			'knowledge_panel'  => $this->check_knowledge_panel( $serp_data ),
			'top_stories'      => $this->check_top_stories( $serp_data ),
		);
	}

	/**
	 * Check for featured snippet in SERP
	 */
	private function check_featured_snippet( $serp_data ) {
		if ( empty( $serp_data['html'] ) ) {
			return false;
		}

		$dom = new DOMDocument();
		@$dom->loadHTML( $serp_data['html'] );
		$xpath = new DOMXPath( $dom );

		// Multiple patterns for featured snippet detection.
		$featured_selectors = array(
			'//div[contains(@class, "g")]//div[contains(@class, "V3FYCf")]',
			'//div[contains(@class, "g")]//div[contains(@class, "xpdopen")]',
			'//div[contains(@class, "g")]//block-component',
			'//div[@data-tts="answers"]',
			'//div[contains(@class, "ifM9O")]',
			'//div[contains(@class, "LGOjhe")]',
			'//div[@data-ved]//div[contains(@class, "Z0LcW")]',
		);

		foreach ( $featured_selectors as $selector ) {
			$nodes = $xpath->query( $selector );
			if ( $nodes->length > 0 ) {
				return array(
					'present'         => true,
					'type'            => $this->determine_snippet_type( $nodes->item( 0 ), $xpath ),
					'position'        => 0, // Featured snippets typically appear at position 0.
					'content_preview' => $this->extract_snippet_content( $nodes->item( 0 ) ),
				);
			}
		}

		return array( 'present' => false );
	}

	/**
	 * Determine the type of featured snippet
	 */
	private function determine_snippet_type( $node, $xpath ) {
		$html = $node->ownerDocument->saveHTML( $node );

		// Check for paragraph snippet.
		if ( strpos( $html, '</p>' ) !== false || strpos( $html, '<br>' ) !== false ) {
			return 'paragraph';
		}

		// Check for list snippet.
		if ( strpos( $html, '<ul>' ) !== false || strpos( $html, '<ol>' ) !== false ) {
			return 'list';
		}

		// Check for table snippet.
		if ( strpos( $html, '<table>' ) !== false ) {
			return 'table';
		}

		// Check for video snippet.
		$video_nodes = $xpath->query( './/video', $node );
		if ( $video_nodes->length > 0 ) {
			return 'video';
		}

		return 'unknown';
	}

	/**
	 * Extract snippet content preview
	 */
	private function extract_snippet_content( $node ) {
		$text = $node->textContent;
		$text = preg_replace( '/\s+/', ' ', $text );
		$text = trim( $text );

		return substr( $text, 0, 200 ) . ( strlen( $text ) > 200 ? '...' : '' );
	}

	/**
	 * Check for "People Also Ask" section
	 */
	private function check_people_also_ask( $serp_data ) {
		if ( empty( $serp_data['html'] ) ) {
			return false;
		}

		$dom = new DOMDocument();
		@$dom->loadHTML( $serp_data['html'] );
		$xpath = new DOMXPath( $dom );

		$paa_selectors = array(
			'//div[@class="related-question-pair"]',
			'//g-accordion',
			'//div[contains(@class, "y6IFtc")]',
			'//div[contains(@class, "Wt5Tfe")]',
			'//div[@jsname="yEVEwb"]',
			'//div[contains(@class, "L3Ezfd")]',
		);

		$questions = array();
		foreach ( $paa_selectors as $selector ) {
			$nodes = $xpath->query( $selector );
			if ( $nodes->length > 0 ) {
				foreach ( $nodes as $node ) {
					$question_text = $this->extract_paa_question( $node, $xpath );
					if ( $question_text ) {
						$questions[] = $question_text;
					}
				}

				if ( ! empty( $questions ) ) {
					return array(
						'present'        => true,
						'question_count' => count( $questions ),
						'questions'      => array_slice( $questions, 0, 5 ), // Limit to first 5 questions.
						'position'       => $this->find_paa_position( $dom ),
					);
				}
			}
		}

		return array( 'present' => false );
	}

	/**
	 * Extract PAA question text
	 */
	private function extract_paa_question( $node, $xpath ) {
		// Try multiple selectors for question text.
		$question_selectors = array(
			'.//div[@role="button"]',
			'.//span',
			'.//div[contains(@class, "match-mod-horizontal-padding")]',
		);

		foreach ( $question_selectors as $selector ) {
			$question_nodes = $xpath->query( $selector, $node );
			if ( $question_nodes->length > 0 ) {
				$text = trim( $question_nodes->item( 0 )->textContent );
				if ( ! empty( $text ) && strlen( $text ) > 10 ) {
					return $text;
				}
			}
		}

		return '';
	}

	/**
	 * Find PAA position in SERP
	 */
	private function find_paa_position( $dom ) {
		$xpath           = new DOMXPath( $dom );
		$organic_results = $xpath->query( '//div[contains(@class, "g")]' );

		for ( $i = 0; $i < $organic_results->length; $i++ ) {
			$result = $organic_results->item( $i );
			$html   = $result->ownerDocument->saveHTML( $result );

			// Check if this result contains PAA.
			if (
				strpos( $html, 'related-question-pair' ) !== false ||
				strpos( $html, 'Wt5Tfe' ) !== false
			) {
				return $i + 1; // Convert to 1-based position.
			}
		}

		return 0;
	}

	/**
	 * Check for related searches
	 */
	private function check_related_searches( $serp_data ) {
		if ( empty( $serp_data['html'] ) ) {
			return false;
		}

		$dom = new DOMDocument();
		@$dom->loadHTML( $serp_data['html'] );
		$xpath = new DOMXPath( $dom );

		$related_selectors = array(
			'//div[@id="botstuff"]//a',
			'//div[contains(@class, "card-section")]//a',
			'//p[contains(@class, "nVcaUb")]//a',
			'//div[@role="heading"]/following-sibling::div//a',
		);

		$related_terms = array();
		foreach ( $related_selectors as $selector ) {
			$nodes = $xpath->query( $selector );
			if ( $nodes->length > 0 ) {
				foreach ( $nodes as $node ) {
					$text = trim( $node->textContent );
					$href = $node->getAttribute( 'href' );

					// Filter out non-search links and very short terms.
					if (
						strpos( $href, '/search?' ) !== false &&
						strlen( $text ) > 3 &&
						strlen( $text ) < 50 &&
						! in_array( $text, $related_terms )
					) {
						$related_terms[] = $text;
					}
				}

				if ( ! empty( $related_terms ) ) {
					return array(
						'present'    => true,
						'term_count' => count( $related_terms ),
						'terms'      => array_slice( $related_terms, 0, 8 ),
						'position'   => 'bottom', // Related searches typically appear at bottom.
					);
				}
			}
		}

		return array( 'present' => false );
	}

	/**
	 * Check for image pack
	 */
	private function check_image_pack( $serp_data ) {
		if ( empty( $serp_data['html'] ) ) {
			return false;
		}

		$dom = new DOMDocument();
		@$dom->loadHTML( $serp_data['html'] );
		$xpath = new DOMXPath( $dom );

		$image_selectors = array(
			'//div[contains(@class, "islr")]',
			'//div[contains(@class, "isv-r")]',
			'//div[@data-entitytype="images"]',
			'//g-scrolling-carousel',
			'//div[contains(@class, "VyxnJb")]',
		);

		$image_count = 0;
		$image_urls  = array();

		foreach ( $image_selectors as $selector ) {
			$nodes = $xpath->query( $selector );
			if ( $nodes->length > 0 ) {
				// Count images within the image pack.
				$images      = $xpath->query( './/img', $nodes->item( 0 ) );
				$image_count = $images->length;

				// Extract image URLs.
				foreach ( $images as $img ) {
					$src = $img->getAttribute( 'src' );
					if ( $src && ! in_array( $src, $image_urls ) && count( $image_urls ) < 5 ) {
						$image_urls[] = $src;
					}
				}

				if ( $image_count > 0 ) {
					return array(
						'present'       => true,
						'image_count'   => $image_count,
						'sample_images' => $image_urls,
						'position'      => $this->find_image_pack_position( $dom ),
					);
				}
			}
		}

		return array( 'present' => false );
	}

	/**
	 * Find image pack position in SERP
	 */
	private function find_image_pack_position( $dom ) {
		$xpath           = new DOMXPath( $dom );
		$organic_results = $xpath->query( '//div[contains(@class, "g")]' );

		for ( $i = 0; $i < $organic_results->length; $i++ ) {
			$result = $organic_results->item( $i );
			$html   = $result->ownerDocument->saveHTML( $result );

			// Check if this result contains image pack.
			if (
				strpos( $html, 'islr' ) !== false ||
				strpos( $html, 'isv-r' ) !== false ||
				strpos( $html, 'scrolling-carousel' ) !== false
			) {
				return $i + 1;
			}
		}

		return 0;
	}

	/**
	 * Check for video carousel
	 */
	private function check_video_carousel( $serp_data ) {
		if ( empty( $serp_data['html'] ) ) {
			return false;
		}

		$dom = new DOMDocument();
		@$dom->loadHTML( $serp_data['html'] );
		$xpath = new DOMXPath( $dom );

		$video_selectors = array(
			'//div[contains(@class, "g")]//g-scrolling-carousel[contains(@class, "F8yfEe")]',
			'//div[contains(@data-entitytype, "video")]',
			'//div[contains(@class, "MkXWrd")]',
			'//g-section-with-header[contains(@data-cid, "vid")]',
		);

		$video_count  = 0;
		$video_titles = array();

		foreach ( $video_selectors as $selector ) {
			$nodes = $xpath->query( $selector );
			if ( $nodes->length > 0 ) {
				// Count videos in carousel.
				$videos      = $xpath->query( './/g-inner-card', $nodes->item( 0 ) );
				$video_count = $videos->length;

				// Extract video titles.
				foreach ( $videos as $video ) {
					$title_node = $xpath->query( './/div[@role="heading"]', $video );
					if ( $title_node->length > 0 ) {
						$title = trim( $title_node->item( 0 )->textContent );
						if ( $title && ! in_array( $title, $video_titles ) && count( $video_titles ) < 3 ) {
							$video_titles[] = $title;
						}
					}
				}

				if ( $video_count > 0 ) {
					return array(
						'present'       => true,
						'video_count'   => $video_count,
						'sample_titles' => $video_titles,
						'position'      => $this->find_video_carousel_position( $dom ),
					);
				}
			}
		}

		return array( 'present' => false );
	}

	/**
	 * Additional SERP feature checks
	 */
	private function check_shopping_results( $serp_data ) {
		if ( empty( $serp_data['html'] ) ) {
			return false;
		}

		$dom = new DOMDocument();
		@$dom->loadHTML( $serp_data['html'] );

		return array(
			'present' => strpos( $serp_data['html'], 'shopping' ) !== false ||
				strpos( $serp_data['html'], 'commercial' ) !== false,
			'type'    => 'shopping_ads',
		);
	}

	private function check_news_carousel( $serp_data ) {
		if ( empty( $serp_data['html'] ) ) {
			return false;
		}

		$dom = new DOMDocument();
		@$dom->loadHTML( $serp_data['html'] );

		return array(
			'present' => strpos( $serp_data['html'], 'top-stories' ) !== false ||
				strpos( $serp_data['html'], 'news-carousel' ) !== false,
			'type'    => 'news',
		);
	}

	private function check_site_links( $serp_data ) {
		if ( empty( $serp_data['html'] ) ) {
			return false;
		}

		$dom = new DOMDocument();
		@$dom->loadHTML( $serp_data['html'] );
		$xpath = new DOMXPath( $dom );

		$site_links = $xpath->query( '//div[contains(@class, "g")]//div[contains(@class, "MUxGbd")]//a' );

		return array(
			'present'    => $site_links->length > 1,
			'link_count' => $site_links->length,
		);
	}

	private function check_knowledge_panel( $serp_data ) {
		if ( empty( $serp_data['html'] ) ) {
			return false;
		}

		return array(
			'present' => strpos( $serp_data['html'], 'knowledge-panel' ) !== false ||
				strpos( $serp_data['html'], 'kno-kp' ) !== false,
			'type'    => 'knowledge_graph',
		);
	}

	private function check_top_stories( $serp_data ) {
		if ( empty( $serp_data['html'] ) ) {
			return false;
		}

		$dom = new DOMDocument();
		@$dom->loadHTML( $serp_data['html'] );
		$xpath = new DOMXPath( $dom );

		$top_stories = $xpath->query( '//g-section-with-header[contains(@data-cid, "news")]' );

		return array(
			'present' => $top_stories->length > 0,
			'type'    => 'news_carousel',
		);
	}

	/**
	 * Get SERP data by keyword
	 */
	private function get_serp_data( $keyword ) {
		// Try API first if available.
		if ( $this->api_key ) {
			return $this->get_serp_via_api( $keyword );
		}

		// Fallback to direct HTTP request.
		return $this->get_serp_via_http( $keyword );
	}

	/**
	 * Get SERP via API (SerpAPI, Moz, Ahrefs, etc.)
	 */
	private function get_serp_via_api( $keyword ) {
		$url = 'https://serpapi.com/search.json?q=' . urlencode( $keyword ) . '&api_key=' . $this->api_key;

		$response = wp_remote_get(
			$url,
			array(
				'timeout'    => 30,
				'user-agent' => $this->user_agent,
			)
		);

		if ( is_wp_error( $response ) ) {
			return array(
				'html'  => '',
				'error' => $response->get_error_message(),
			);
		}

		$body = wp_remote_retrieve_body( $response );
		$data = json_decode( $body, true );

		return array(
			'html'            => $data['html'] ?? '',
			'organic_results' => $data['organic_results'] ?? array(),
			'serp_features'   => $data['serp_features'] ?? array(),
		);
	}

	/**
	 * Get SERP via direct HTTP request
	 */
	private function get_serp_via_http( $keyword ) {
		$url = 'https://www.google.com/search?q=' . urlencode( $keyword ) . '&num=10';

		$response = wp_remote_get(
			$url,
			array(
				'timeout'    => 30,
				'user-agent' => $this->user_agent,
				'headers'    => array(
					'Accept'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
					'Accept-Language' => 'en-US,en;q=0.5',
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			return array(
				'html'  => '',
				'error' => $response->get_error_message(),
			);
		}

		return array(
			'html'            => wp_remote_retrieve_body( $response ),
			'organic_results' => array(),
			'serp_features'   => array(),
		);
	}

	/**
	 * Get own website rank position
	 */
	private function get_own_rank( $serp_data, $website_url ) {
		if ( empty( $serp_data['organic_results'] ) ) {
			return $this->extract_rank_from_html( $serp_data['html'], $website_url );
		}

		foreach ( $serp_data['organic_results'] as $index => $result ) {
			if ( strpos( $result['link'], $website_url ) !== false ) {
				return $index + 1;
			}
		}

		return 0; // Not in top 10.
	}

	/**
	 * Extract rank from HTML when API not available
	 */
	private function extract_rank_from_html( $html, $website_url ) {
		$dom = new DOMDocument();
		@$dom->loadHTML( $html );
		$xpath = new DOMXPath( $dom );

		$results = $xpath->query( '//div[contains(@class, "g")]//a' );

		for ( $i = 0; $i < $results->length; $i++ ) {
			$link = $results->item( $i )->getAttribute( 'href' );
			if ( strpos( $link, $website_url ) !== false ) {
				return $i + 1;
			}
		}

		return 0;
	}

	/**
	 * Get competitor positions
	 */
	private function get_competitor_positions( $serp_data, $competitors ) {
		$positions = array();

		foreach ( $competitors as $competitor ) {
			$positions[ $competitor ] = $this->get_own_rank( $serp_data, $competitor );
		}

		return $positions;
	}

	/**
	 * Analyze SERP for opportunities
	 */
	private function analyze_serp( $serp_data ) {
		return array(
			'total_results'     => $this->extract_total_results( $serp_data['html'] ),
			'serp_difficulty'   => $this->calculate_serp_difficulty( $serp_data ),
			'opportunity_score' => $this->calculate_opportunity_score( $serp_data ),
			'content_gaps'      => $this->identify_content_gaps( $serp_data ),
		);
	}

	/**
	 * Store rank data in database
	 */
	private function store_rank_data( $rank_data ) {
		global $wpdb;
		$table_name = $wpdb->prefix . 'rank_tracking_data';

		$wpdb->insert(
			$table_name,
			array(
				'keyword'         => $rank_data['keyword'],
				'position'        => $rank_data['position'],
				'serp_features'   => json_encode( $rank_data['serp_features'] ),
				'competitor_data' => json_encode( $rank_data['competitor_positions'] ),
				'serp_analysis'   => json_encode( $rank_data['serp_analysis'] ),
				'tracked_date'    => $rank_data['date'],
			)
		);
	}

	/**
	 * Extract total search results from SERP
	 */
	private function extract_total_results( $html ) {
		if ( empty( $html ) ) {
			return 0;
		}

		$dom = new DOMDocument();
		@$dom->loadHTML( $html );
		$xpath = new DOMXPath( $dom );

		// Multiple patterns for total results count.
		$result_selectors = array(
			'//div[@id="result-stats"]',
			'//div[contains(@class, "appbar")]',
			'//div[contains(text(), "results")]',
			'//span[contains(text(), "results")]',
		);

		foreach ( $result_selectors as $selector ) {
			$nodes = $xpath->query( $selector );
			if ( $nodes->length > 0 ) {
				$text = $nodes->item( 0 )->textContent;
				return $this->parse_results_count( $text );
			}
		}

		return 0;
	}

	/**
	 * Parse results count from text
	 */
	private function parse_results_count( $text ) {
		// Remove commas and extract numbers.
		$clean_text = preg_replace( '/[^\d]/', '', $text );

		if ( preg_match( '/(\d+)/', $clean_text, $matches ) ) {
			$count = intval( $matches[1] );

			// Handle large numbers (like 1,230,000,000).
			if ( strpos( $text, 'billion' ) !== false || $count > 1000000000 ) {
				return $count * 1000000; // Convert to actual number.
			}

			return $count;
		}

		return 0;
	}

	/**
	 * Calculate SERP difficulty score
	 */
	private function calculate_serp_difficulty( $serp_data ) {
		$difficulty_score = 0;
		$factors          = array();

		// Factor 1: Total results count.
		$total_results = $this->extract_total_results( $serp_data['html'] );
		if ( $total_results > 0 ) {
			$results_factor            = min( 100, ( $total_results / 1000000 ) * 100 );
			$difficulty_score         += $results_factor * 0.3;
			$factors['results_volume'] = round( $results_factor );
		}

		// Factor 2: Domain authority of top results.
		$domain_authority            = $this->analyze_top_domains( $serp_data );
		$difficulty_score           += $domain_authority * 0.4;
		$factors['domain_authority'] = round( $domain_authority );

		// Factor 3: SERP features competition.
		$serp_features            = $this->analyze_serp_features( $serp_data );
		$difficulty_score        += $serp_features * 0.3;
		$factors['serp_features'] = round( $serp_features );

		// Normalize score to 0-100.
		$final_score = min( 100, max( 0, round( $difficulty_score ) ) );

		return array(
			'score'   => $final_score,
			'level'   => $this->get_difficulty_level( $final_score ),
			'factors' => $factors,
		);
	}

	/**
	 * Analyze domain authority of top results
	 */
	private function analyze_top_domains( $serp_data ) {
		$authority_score = 0;
		$top_domains     = array();

		if ( ! empty( $serp_data['organic_results'] ) ) {
			foreach ( array_slice( $serp_data['organic_results'], 0, 5 ) as $result ) {
				$domain                 = parse_url( $result['link'], PHP_URL_HOST );
				$domain_authority       = $this->estimate_domain_authority( $domain );
				$top_domains[ $domain ] = $domain_authority;
				$authority_score       += $domain_authority;
			}

			if ( count( $top_domains ) > 0 ) {
				return $authority_score / count( $top_domains );
			}
		}

		// Fallback: estimate based on common patterns.
		return 60; // Medium difficulty default.
	}

	/**
	 * Estimate domain authority (simplified)
	 */
	private function estimate_domain_authority( $domain ) {
		$authority = 50; // Base score.

		// Boost for well-known domains.
		$high_authority_domains = array(
			'wikipedia.org',
			'youtube.com',
			'facebook.com',
			'amazon.com',
			'twitter.com',
			'linkedin.com',
			'instagram.com',
			'reddit.com',
			'medium.com',
			'quora.com',
			'forbes.com',
			'nytimes.com',
		);

		foreach ( $high_authority_domains as $high_domain ) {
			if ( strpos( $domain, $high_domain ) !== false ) {
				$authority = 95;
				break;
			}
		}

		// Adjust based on domain age indicators
		if ( preg_match( '/(\d{4})/', $domain ) ) {
			$authority += 10; // Older domains often have numbers.
		}

		// Penalize new/suspicious domains.
		if (
			strpos( $domain, '.xyz' ) !== false ||
			strpos( $domain, '.top' ) !== false ||
			strpos( $domain, '.club' ) !== false
		) {
			$authority -= 20;
		}

		return max( 10, min( 100, $authority ) );
	}

	/**
	 * Analyze SERP features competition
	 */
	private function analyze_serp_features( $serp_data ) {
		$feature_score    = 0;
		$features_present = 0;

		$serp_features = array(
			'featured_snippet' => 25,
			'people_also_ask'  => 15,
			'image_pack'       => 10,
			'video_carousel'   => 15,
			'shopping_results' => 20,
			'news_carousel'    => 10,
			'knowledge_panel'  => 5,
		);

		foreach ( $serp_features as $feature => $weight ) {
			$check_method = 'check_' . $feature;
			if ( method_exists( $this, $check_method ) ) {
				$result = $this->$check_method( $serp_data );
				if ( is_array( $result ) && $result['present'] ) {
					$feature_score += $weight;
					++$features_present;
				}
			}
		}

		return min( 100, $feature_score );
	}

	/**
	 * Get difficulty level from score
	 */
	private function get_difficulty_level( $score ) {
		if ( $score >= 80 ) {
			return 'Very Hard';
		}
		if ( $score >= 60 ) {
			return 'Hard';
		}
		if ( $score >= 40 ) {
			return 'Medium';
		}
		if ( $score >= 20 ) {
			return 'Easy';
		}
		return 'Very Easy';
	}

	/**
	 * Calculate opportunity score
	 */
	private function calculate_opportunity_score( $serp_data ) {
		$opportunity_score = 0;
		$factors           = array();

		// Factor 1: Current ranking position.
		$current_rank                = $this->get_own_rank( $serp_data, home_url() );
		$rank_opportunity            = $this->calculate_rank_opportunity( $current_rank );
		$opportunity_score          += $rank_opportunity * 0.3;
		$factors['ranking_position'] = round( $rank_opportunity );

		// Factor 2: SERP feature opportunities.
		$feature_opportunity      = $this->calculate_feature_opportunity( $serp_data );
		$opportunity_score       += $feature_opportunity * 0.4;
		$factors['serp_features'] = round( $feature_opportunity );

		// Factor 3: Content gap opportunities.
		$content_opportunity     = $this->calculate_content_opportunity( $serp_data );
		$opportunity_score      += $content_opportunity * 0.3;
		$factors['content_gaps'] = round( $content_opportunity );

		// Normalize score to 0-100.
		$final_score = min( 100, max( 0, round( $opportunity_score ) ) );

		return array(
			'score'           => $final_score,
			'level'           => $this->get_opportunity_level( $final_score ),
			'factors'         => $factors,
			'recommendations' => $this->generate_opportunity_recommendations( $serp_data, $factors ),
		);
	}

	/**
	 * Calculate opportunity based on current rank
	 */
	private function calculate_rank_opportunity( $current_rank ) {
		if ( $current_rank === 0 ) {
			return 100; // Not ranking = high opportunity.
		}
		if ( $current_rank <= 3 ) {
			return 20;   // Already top = low opportunity.
		}
		if ( $current_rank <= 10 ) {
			return 50;  // On first page = medium opportunity.
		}

		return 80; // Second page or lower = high opportunity.
	}

	/**
	 * Calculate SERP feature opportunities
	 */
	private function calculate_feature_opportunity( $serp_data ) {
		$feature_score      = 0;
		$available_features = 0;

		$feature_weights = array(
			'featured_snippet' => array(
				'weight'  => 30,
				'present' => false,
			),
			'people_also_ask'  => array(
				'weight'  => 25,
				'present' => false,
			),
			'image_pack'       => array(
				'weight'  => 15,
				'present' => false,
			),
			'video_carousel'   => array(
				'weight'  => 20,
				'present' => false,
			),
			'related_searches' => array(
				'weight'  => 10,
				'present' => false,
			),
		);

		foreach ( $feature_weights as $feature => $data ) {
			$check_method = 'check_' . $feature;
			if ( method_exists( $this, $check_method ) ) {
				$result                                 = $this->$check_method( $serp_data );
				$feature_weights[ $feature ]['present'] = is_array( $result ) && $result['present'];
			}
		}

		// Score based on features that are present but we're not targeting.
		foreach ( $feature_weights as $feature => $data ) {
			if ( $data['present'] ) {
				++$available_features;
				$feature_score += $data['weight'];
			}
		}

		return min( 100, $feature_score );
	}

	/**
	 * Calculate content gap opportunities
	 */
	private function calculate_content_opportunity( $serp_data ) {
		$content_score = 0;

		// Analyze top ranking content.
		if ( ! empty( $serp_data['organic_results'] ) ) {
			$top_results = array_slice( $serp_data['organic_results'], 0, 5 );

			// Check for content quality indicators.
			foreach ( $top_results as $result ) {
				$title   = $result['title'] ?? '';
				$snippet = $result['snippet'] ?? '';

				// Opportunity if top results have weak content.
				if ( strlen( $snippet ) < 100 ) {
					$content_score += 20;
				}

				// Opportunity if titles are not optimized.
				if ( strlen( $title ) < 30 || strlen( $title ) > 70 ) {
					$content_score += 15;
				}
			}
		}

		return min( 100, $content_score );
	}

	/**
	 * Get opportunity level from score
	 */
	private function get_opportunity_level( $score ) {
		if ( $score >= 80 ) {
			return 'High';
		}
		if ( $score >= 60 ) {
			return 'Good';
		}
		if ( $score >= 40 ) {
			return 'Medium';
		}
		if ( $score >= 20 ) {
			return 'Low';
		}
		return 'Very Low';
	}

	/**
	 * Generate opportunity recommendations
	 */
	private function generate_opportunity_recommendations( $serp_data, $factors ) {
		$recommendations = array();

		// Ranking position recommendations.
		if ( $factors['ranking_position'] >= 70 ) {
			$recommendations[] = 'Focus on improving your current ranking position through on-page optimization and backlink building.';
		}

		// SERP feature recommendations.
		if ( $factors['serp_features'] >= 60 ) {
			$recommendations[] = 'Target available SERP features like featured snippets and "People Also Ask" sections.';
		}

		// Content gap recommendations.
		if ( $factors['content_gaps'] >= 50 ) {
			$recommendations[] = 'Create more comprehensive content that addresses user intent better than current top results.';
		}

		// Specific feature opportunities.
		$featured_snippet = $this->check_featured_snippet( $serp_data );
		if ( $featured_snippet['present'] ) {
			$recommendations[] = 'Optimize content to target the featured snippet by providing clear, concise answers.';
		}

		$people_also_ask = $this->check_people_also_ask( $serp_data );
		if ( $people_also_ask['present'] ) {
			$recommendations[] = 'Include FAQ sections that answer common questions from "People Also Ask".';
		}

		return array_slice( $recommendations, 0, 3 ); // Limit to top 3 recommendations.
	}

	/**
	 * Identify content gaps
	 */
	private function identify_content_gaps( $serp_data ) {
		$content_gaps = array();

		// Analyze "People Also Ask" for content ideas.
		$paa = $this->check_people_also_ask( $serp_data );
		if ( $paa['present'] && ! empty( $paa['questions'] ) ) {
			foreach ( $paa['questions'] as $question ) {
				$content_gaps[] = array(
					'type'        => 'question',
					'content'     => $question,
					'opportunity' => 'Answer this question in your content',
				);
			}
		}

		// Analyze related searches for topic gaps.
		$related = $this->check_related_searches( $serp_data );
		if ( $related['present'] && ! empty( $related['terms'] ) ) {
			foreach ( $related['terms'] as $term ) {
				$content_gaps[] = array(
					'type'        => 'related_topic',
					'content'     => $term,
					'opportunity' => 'Create content around this related topic',
				);
			}
		}

		// Analyze top results for missing content types.
		if ( ! empty( $serp_data['organic_results'] ) ) {
			$content_types = $this->analyze_content_types( $serp_data['organic_results'] );
			foreach ( $content_types as $type => $present ) {
				if ( ! $present ) {
					$content_gaps[] = array(
						'type'        => 'content_format',
						'content'     => $type,
						'opportunity' => "Create {$type} content to compete in this SERP",
					);
				}
			}
		}

		return array_slice( $content_gaps, 0, 5 ); // Limit to top 5 gaps.
	}

	/**
	 * Analyze content types in top results
	 */
	private function analyze_content_types( $organic_results ) {
		$content_types = array(
			'guide'      => false,
			'review'     => false,
			'tutorial'   => false,
			'comparison' => false,
			'news'       => false,
			'video'      => false,
		);

		foreach ( array_slice( $organic_results, 0, 5 ) as $result ) {
			$title   = strtolower( $result['title'] ?? '' );
			$snippet = strtolower( $result['snippet'] ?? '' );

			// Detect content types from titles and snippets.
			if ( strpos( $title, 'guide' ) !== false || strpos( $snippet, 'guide' ) !== false ) {
				$content_types['guide'] = true;
			}
			if ( strpos( $title, 'review' ) !== false || strpos( $snippet, 'review' ) !== false ) {
				$content_types['review'] = true;
			}
			if ( strpos( $title, 'tutorial' ) !== false || strpos( $title, 'how to' ) !== false ) {
				$content_types['tutorial'] = true;
			}
			if ( strpos( $title, 'vs' ) !== false || strpos( $title, 'comparison' ) !== false ) {
				$content_types['comparison'] = true;
			}
		}

		return $content_types;
	}

	/**
	 * Find video carousel position in SERP
	 */
	private function find_video_carousel_position( $dom ) {
		$xpath           = new DOMXPath( $dom );
		$organic_results = $xpath->query( '//div[contains(@class, "g")]' );

		for ( $i = 0; $i < $organic_results->length; $i++ ) {
			$result = $organic_results->item( $i );
			$html   = $result->ownerDocument->saveHTML( $result );

			// Check if this result contains video carousel.
			if (
				strpos( $html, 'video-carousel' ) !== false ||
				strpos( $html, 'F8yfEe' ) !== false ||
				strpos( $html, 'MkXWrd' ) !== false
			) {
				return $i + 1;
			}
		}

		return 0;
	}

	/**
	 * Get attribute value safely (helper method)
	 */
	private function getAttribute( $node, $attribute ) {
		if ( $node && $node->hasAttribute( $attribute ) ) {
			return $node->getAttribute( $attribute );
		}
		return '';
	}
}
