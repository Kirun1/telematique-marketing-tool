<?php
class ProductScraper_Voice_Search {

	public function optimize_for_voice( $content ) {
		// Focus on conversational keywords and question-based queries.
		return array(
			'question_keywords'             => $this->extract_question_keywords( $content ),
			'featured_snippet_optimization' => $this->optimize_for_featured_snippets( $content ),
			'conversational_content'        => $this->make_content_conversational( $content ),
			'local_voice_queries'           => $this->optimize_for_local_voice( $content ),
		);
	}

	private function extract_question_keywords( $content ) {
		$questions = array(
			'what'  => array(),
			'how'   => array(),
			'where' => array(),
			'when'  => array(),
			'why'   => array(),
			'which' => array(),
			'who'   => array(),
		);

		// Common voice search question patterns.
		$question_patterns = array(
			'what'  => '/what (is|are|does|do|can|should|would)\s+([^.?]+)[.?]/i',
			'how'   => '/how (to|does|do|can|much|many|long)\s+([^.?]+)[.?]/i',
			'where' => '/where (is|are|can)\s+([^.?]+)[.?]/i',
			'when'  => '/when (is|are|does|do|should)\s+([^.?]+)[.?]/i',
			'why'   => '/why (is|are|does|do|should)\s+([^.?]+)[.?]/i',
			'which' => '/which (is|are|should|would)\s+([^.?]+)[.?]/i',
			'who'   => '/who (is|are|makes|manufactures)\s+([^.?]+)[.?]/i',
		);

		// Extract questions from content.
		foreach ( $question_patterns as $type => $pattern ) {
			preg_match_all( $pattern, $content, $matches );
			if ( ! empty( $matches[0] ) ) {
				$questions[ $type ] = array_map( 'trim', $matches[0] );
			}
		}

		// Generate additional question keywords based on content analysis.
		$content_questions = $this->generate_questions_from_content( $content );

		return array(
			'detected_questions'  => $questions,
			'suggested_questions' => $content_questions,
			'question_density'    => $this->calculate_question_density( $content ),
			'optimization_score'  => $this->calculate_question_optimization_score( $questions, $content_questions ),
		);
	}

	private function optimize_for_featured_snippets( $content ) {
		$optimizations = array(
			'structured_data'       => array(),
			'content_structure'     => array(),
			'answer_blocks'         => array(),
			'snippet_opportunities' => array(),
		);

		// Identify potential featured snippet opportunities.
		$paragraphs         = preg_split( '/\n\s*\n/', $content );
		$snippet_candidates = array();

		foreach ( $paragraphs as $index => $paragraph ) {
			$paragraph = trim( $paragraph );
			if ( empty( $paragraph ) || strlen( $paragraph ) < 50 ) {
				continue;
			}

			$snippet_score = $this->calculate_snippet_potential( $paragraph );

			if ( $snippet_score > 0.5 ) {
				$snippet_candidates[] = array(
					'content'  => $paragraph,
					'score'    => $snippet_score,
					'type'     => $this->determine_snippet_type( $paragraph ),
					'position' => $index,
				);
			}
		}

		// Sort by snippet potential.
		usort(
			$snippet_candidates,
			function ( $a, $b ) {
				return $b['score'] - $a['score'];
			}
		);

		$optimizations['snippet_opportunities'] = array_slice( $snippet_candidates, 0, 5 );

		// Add structured data recommendations.
		$optimizations['structured_data'] = array(
			'faq_schema'        => $this->suggest_faq_schema( $content ),
			'howto_schema'      => $this->suggest_howto_schema( $content ),
			'definition_schema' => $this->suggest_definition_schema( $content ),
		);

		// Content structure optimizations.
		$optimizations['content_structure'] = array(
			'heading_hierarchy' => $this->analyze_heading_structure( $content ),
			'list_usage'        => $this->analyze_list_usage( $content ),
			'table_usage'       => $this->analyze_table_usage( $content ),
			'paragraph_length'  => $this->analyze_paragraph_length( $content ),
		);

		return $optimizations;
	}

	private function make_content_conversational( $content ) {
		$conversational_analysis = array(
			'readability_score'   => $this->calculate_readability( $content ),
			'conversational_tone' => $this->analyze_conversational_tone( $content ),
			'sentence_structure'  => $this->analyze_sentence_structure( $content ),
			'improvements'        => array(),
			'rewritten_examples'  => array(),
		);

		// Analyze and suggest improvements.
		$sentences         = preg_split( '/(?<=[.?!])\s+/', $content );
		$complex_sentences = array();

		foreach ( $sentences as $sentence ) {
			$sentence_complexity = $this->calculate_sentence_complexity( $sentence );

			if ( $sentence_complexity > 0.7 ) {
				$complex_sentences[] = array(
					'original'         => $sentence,
					'complexity_score' => $sentence_complexity,
					'simplified'       => $this->simplify_sentence( $sentence ),
				);
			}
		}

		$conversational_analysis['complex_sentences'] = array_slice( $complex_sentences, 0, 5 );

		// Suggest conversational improvements.
		$conversational_analysis['improvements'] = array(
			'use_contractions'         => $this->suggest_contractions( $content ),
			'add_rhetorical_questions' => $this->suggest_rhetorical_questions( $content ),
			'include_transition_words' => $this->suggest_transition_words( $content ),
			'personal_pronouns'        => $this->analyze_personal_pronouns( $content ),
		);

		// Generate rewritten examples.
		$conversational_analysis['rewritten_examples'] = $this->generate_conversational_examples( $content );

		return $conversational_analysis;
	}

	private function optimize_for_local_voice( $content ) {
		$local_optimization = array(
			'local_keywords'    => array(),
			'location_mentions' => array(),
			'local_schema'      => array(),
			'voice_commands'    => array(),
		);

		// Extract local keywords and entities.
		$local_optimization['local_keywords'] = $this->extract_local_keywords( $content );

		// Analyze location mentions.
		$local_optimization['location_mentions'] = $this->extract_location_mentions( $content );

		// Suggest local schema markup.
		$local_optimization['local_schema'] = $this->suggest_local_schema( $content );

		// Generate voice command patterns.
		$local_optimization['voice_commands'] = $this->generate_voice_commands( $content );

		return $local_optimization;
	}

	// Helper methods for question extraction.
	private function generate_questions_from_content( $content ) {
		$sentences           = preg_split( '/(?<=[.?!])\s+/', $content );
		$generated_questions = array();

		$question_templates = array(
			'What is {keyword}?',
			'How does {keyword} work?',
			'Where can I find {keyword}?',
			'When should I use {keyword}?',
			'Why is {keyword} important?',
			'How to use {keyword}?',
			'What are the benefits of {keyword}?',
			'How much does {keyword} cost?',
		);

		// Extract key nouns and phrases.
		$keywords = $this->extract_key_phrases( $content );

		foreach ( $keywords as $keyword ) {
			foreach ( $question_templates as $template ) {
				$question              = str_replace( '{keyword}', $keyword, $template );
				$generated_questions[] = $question;
			}
		}

		return array_slice( array_unique( $generated_questions ), 0, 10 );
	}

	private function calculate_question_density( $content ) {
		$sentences      = preg_split( '/(?<=[.?!])\s+/', $content );
		$question_count = 0;

		foreach ( $sentences as $sentence ) {
			if ( preg_match( '/^(what|how|where|when|why|which|who).*\?$/i', $sentence ) ) {
				++$question_count;
			}
		}

		return count( $sentences ) > 0 ? ( $question_count / count( $sentences ) ) * 100 : 0;
	}

	private function calculate_question_optimization_score( $detected_questions, $suggested_questions ) {
		$total_detected = 0;
		foreach ( $detected_questions as $type_questions ) {
			$total_detected += count( $type_questions );
		}

		$score = min( ( $total_detected / 10 ) * 100, 100 ); // Max 10 questions for perfect score.
		return round( $score, 1 );
	}

	// Helper methods for featured snippets.
	private function calculate_snippet_potential( $paragraph ) {
		$score = 0;

		// Length check (ideal: 40-60 words).
		$word_count = str_word_count( $paragraph );
		if ( $word_count >= 40 && $word_count <= 60 ) {
			$score += 0.3;
		}

		// Starts with answer.
		if ( preg_match( '/^(yes|no|the|it|this|these|those)/i', $paragraph ) ) {
			$score += 0.2;
		}

		// Contains numbers or statistics.
		if ( preg_match( '/\d+/', $paragraph ) ) {
			$score += 0.2;
		}

		// Clear structure.
		if ( preg_match( '/^(first|second|third|finally|in conclusion)/i', $paragraph ) ) {
			$score += 0.3;
		}

		return min( $score, 1.0 );
	}

	private function determine_snippet_type( $paragraph ) {
		if ( preg_match( '/\b(step|guide|instructions?|tutorial)\b/i', $paragraph ) ) {
			return 'how-to';
		} elseif ( preg_match( '/\b(definition|means|refers to|is)\b/i', $paragraph ) ) {
			return 'definition';
		} elseif ( preg_match( '/\d+\s*(steps|items|points)/i', $paragraph ) ) {
			return 'list';
		} elseif ( preg_match( '/\b(table|chart|comparison)\b/i', $paragraph ) ) {
			return 'table';
		} else {
			return 'paragraph';
		}
	}

	private function suggest_faq_schema( $content ) {
		$questions = $this->extract_question_keywords( $content );
		$faq_items = array();

		foreach ( $questions['detected_questions'] as $type => $type_questions ) {
			foreach ( array_slice( $type_questions, 0, 3 ) as $question ) {
				$faq_items[] = array(
					'question' => $question,
					'answer'   => $this->generate_answer_suggestion( $question, $content ),
				);
			}
		}

		return array_slice( $faq_items, 0, 10 );
	}

	private function suggest_howto_schema( $content ) {
		// Implementation for how-to schema suggestions.
		return array(
			'has_howto_potential' => preg_match( '/\b(how to|step by step|tutorial)\b/i', $content ) > 0,
			'steps_identified'    => preg_match_all( '/\b(step \d+|first|next|then|finally)\b/i', $content, $matches ),
			'recommendations'     => array( 'Add step-by-step instructions', 'Include estimated time', 'List required tools/materials' ),
		);
	}

	private function suggest_definition_schema( $content ) {
		// Implementation for definition schema suggestions.
		return array(
			'has_definition_potential' => preg_match( '/\b(is defined as|means|refers to)\b/i', $content ) > 0,
			'key_terms'                => $this->extract_key_phrases( $content ),
			'recommendations'          => array( 'Define key terms clearly', 'Use definition schema markup', 'Provide examples' ),
		);
	}

	// Helper methods for conversational content.
	private function calculate_readability( $content ) {
		// Simplified Flesch Reading Ease calculation.
		$words     = str_word_count( $content );
		$sentences = preg_split( '/[.?!]+/', $content );
		$syllables = $this->estimate_syllables( $content );

		if ( $words === 0 || count( $sentences ) === 0 ) {
			return 0;
		}

		$average_sentence_length    = $words / count( $sentences );
		$average_syllables_per_word = $syllables / $words;

		$score = 206.835 - ( 1.015 * $average_sentence_length ) - ( 84.6 * $average_syllables_per_word );

		return max( 0, min( 100, $score ) );
	}

	private function analyze_conversational_tone( $content ) {
		$conversational_indicators = array(
			'personal_pronouns' => preg_match_all( '/\b(I|you|we|us|our|your)\b/i', $content ),
			'contractions'      => preg_match_all( '/\b(\w+\'\w+)\b/', $content ),
			'questions'         => preg_match_all( '/\?/', $content ),
			'imperatives'       => preg_match_all( '/^\s*(start|try|use|add|make|create)\b/im', $content ),
		);

		$total_words = str_word_count( $content );
		$score       = 0;

		if ( $total_words > 0 ) {
			$score += ( $conversational_indicators['personal_pronouns'] / $total_words ) * 100;
			$score += ( $conversational_indicators['contractions'] / $total_words ) * 100;
			$score += ( $conversational_indicators['questions'] / $total_words ) * 100;
		}

		return min( $score, 100 );
	}

	private function calculate_sentence_complexity( $sentence ) {
		$words         = str_word_count( $sentence );
		$complex_words = preg_match_all( '/\b\w{7,}\b/', $sentence );
		$clauses       = preg_match_all( '/\b(and|but|or|however|although|because|since)\b/i', $sentence );

		if ( $words === 0 ) {
			return 0;
		}

		$complexity = ( $complex_words / $words ) * 0.6 + ( $clauses / $words ) * 0.4;
		return min( $complexity, 1.0 );
	}

	private function simplify_sentence( $sentence ) {
		// Basic sentence simplification rules.
		$simplifications = array(
			'/\bhowever\b/i'       => 'but',
			'/\balthough\b/i'      => 'though',
			'/\bconsequently\b/i'  => 'so',
			'/\bnevertheless\b/i'  => 'still',
			'/\bfurthermore\b/i'   => 'also',
			'/\butilize\b/i'       => 'use',
			'/\bapproximately\b/i' => 'about',
			'/\bterminate\b/i'     => 'end',
		);

		$simplified = preg_replace( array_keys( $simplifications ), array_values( $simplifications ), $sentence );

		// Split long sentences.
		if ( str_word_count( $simplified ) > 25 ) {
			$simplified = preg_replace( '/([,;])\s*/', '$1 ', $simplified );
		}

		return $simplified;
	}

	// Additional helper methods would be implemented here.
	private function estimate_syllables( $content ) {
		// Simplified syllable estimation.
		$words           = str_word_count( $content, 1 );
		$total_syllables = 0;

		foreach ( $words as $word ) {
			$total_syllables += max( 1, ceil( strlen( $word ) / 3 ) );
		}

		return $total_syllables;
	}

	private function extract_key_phrases( $content ) {
		// Extract potential keywords (simplified).
		preg_match_all( '/\b(\w+\s+\w+\s+\w+|\w+\s+\w+)\b/i', $content, $matches );
		return array_slice( array_unique( $matches[0] ), 0, 15 );
	}

	private function generate_answer_suggestion( $question, $content ) {
		// Generate a suggested answer based on content.
		return 'Based on the content, this appears to be related to ' .
			substr( $content, 0, 100 ) . '...';
	}

	// Methods for local voice optimization.
	private function extract_local_keywords( $content ) {
		$local_patterns = array(
			'near_me'    => '/\b(near me|close by|local|nearby)\b/i',
			'directions' => '/\b(directions|how to get to|location)\b/i',
			'hours'      => '/\b(hours|open|close|today|tomorrow)\b/i',
			'services'   => '/\b(best|top|rated|recommended)\b/i',
		);

		$keywords = array();
		foreach ( $local_patterns as $type => $pattern ) {
			if ( preg_match_all( $pattern, $content, $matches ) ) {
				$keywords[ $type ] = $matches[0];
			}
		}

		return $keywords;
	}

	private function extract_location_mentions( $content ) {
		// Simple location extraction (in real implementation, use geocoding API).
		preg_match_all( '/\b(\w+ \w+ \w+|\w+ \w+)\b(?=,?\s*(?:area|city|state|country))/i', $content, $matches );
		return array_unique( $matches[0] );
	}

	private function suggest_local_schema( $content ) {
		return array(
			'local_business'  => preg_match( '/\b(store|shop|restaurant|business)\b/i', $content ) > 0,
			'opening_hours'   => preg_match( '/\b(\d+[ap]m|\d+:\d+)\b/i', $content ) > 0,
			'address'         => preg_match( '/\b(street|avenue|road|boulevard)\b/i', $content ) > 0,
			'recommendations' => array( 'Add LocalBusiness schema', 'Include opening hours', 'Add geo coordinates' ),
		);
	}

	private function generate_voice_commands( $content ) {
		$commands    = array();
		$key_phrases = $this->extract_key_phrases( $content );

		foreach ( $key_phrases as $phrase ) {
			$commands[] = 'Hey Google, ' . $phrase;
			$commands[] = 'Alexa, ' . $phrase;
			$commands[] = 'Siri, ' . $phrase;
		}

		return array_slice( $commands, 0, 10 );
	}

	// Additional analysis methods.
	private function analyze_heading_structure( $content ) {
		return array(
			'h1_count'        => preg_match_all( '/<h1[^>]*>/i', $content ),
			'h2_count'        => preg_match_all( '/<h2[^>]*>/i', $content ),
			'h3_count'        => preg_match_all( '/<h3[^>]*>/i', $content ),
			'recommendations' => array( 'Use one H1 per page', 'Create logical heading hierarchy' ),
		);
	}

	private function analyze_list_usage( $content ) {
		return array(
			'ul_count'        => preg_match_all( '/<ul[^>]*>/i', $content ),
			'ol_count'        => preg_match_all( '/<ol[^>]*>/i', $content ),
			'recommendations' => array( 'Use numbered lists for steps', 'Use bullet points for features' ),
		);
	}

	private function analyze_table_usage( $content ) {
		return array(
			'table_count'     => preg_match_all( '/<table[^>]*>/i', $content ),
			'has_captions'    => preg_match_all( '/<caption>/i', $content ),
			'recommendations' => array( 'Add table captions', 'Use simple table structures' ),
		);
	}

	private function analyze_paragraph_length( $content ) {
		$paragraphs = preg_split( '/\n\s*\n/', $content );
		$lengths    = array_map( 'str_word_count', $paragraphs );

		return array(
			'average_length'  => array_sum( $lengths ) / count( $lengths ),
			'max_length'      => max( $lengths ),
			'min_length'      => min( $lengths ),
			'recommendations' => array( 'Keep paragraphs under 100 words', 'Use short paragraphs for mobile' ),
		);
	}

	private function suggest_contractions( $content ) {
		$suggestions = array();
		$patterns    = array(
			'/\bdo not\b/i'   => "don't",
			'/\bdoes not\b/i' => "doesn't",
			'/\bis not\b/i'   => "isn't",
			'/\bare not\b/i'  => "aren't",
			'/\bwill not\b/i' => "won't",
			'/\bcannot\b/i'   => "can't",
		);

		foreach ( $patterns as $formal => $contraction ) {
			if ( preg_match( $formal, $content ) ) {
				$suggestions[] = "Replace '" . preg_replace( '/\b(do not)\b/i', 'do not', $formal ) . "' with '" . $contraction . "'";
			}
		}

		return $suggestions;
	}

	private function suggest_rhetorical_questions( $content ) {
		$key_phrases = $this->extract_key_phrases( $content );
		$questions   = array();

		foreach ( array_slice( $key_phrases, 0, 5 ) as $phrase ) {
			$questions[] = 'Did you know that ' . $phrase . '?';
			$questions[] = 'Want to learn more about ' . $phrase . '?';
		}

		return $questions;
	}

	private function suggest_transition_words( $content ) {
		$transition_words    = array( 'also', 'however', 'therefore', 'meanwhile', 'consequently', 'furthermore' );
		$missing_transitions = array();

		foreach ( $transition_words as $word ) {
			if ( stripos( $content, $word ) === false ) {
				$missing_transitions[] = "Consider adding '" . $word . "' for better flow";
			}
		}

		return $missing_transitions;
	}

	private function analyze_personal_pronouns( $content ) {
		$pronouns = array( 'I', 'you', 'we', 'us', 'our', 'your', 'my' );
		$counts   = array();

		foreach ( $pronouns as $pronoun ) {
			$counts[ $pronoun ] = substr_count( strtolower( $content ), strtolower( $pronoun ) );
		}

		return array(
			'counts'  => $counts,
			'total'   => array_sum( $counts ),
			'density' => ( str_word_count( $content ) > 0 ) ? ( array_sum( $counts ) / str_word_count( $content ) ) * 100 : 0,
		);
	}

	private function generate_conversational_examples( $content ) {
		$sentences = preg_split( '/(?<=[.?!])\s+/', $content );
		$examples  = array();

		foreach ( array_slice( $sentences, 0, 3 ) as $sentence ) {
			$examples[] = array(
				'original'       => $sentence,
				'conversational' => $this->make_sentence_conversational( $sentence ),
			);
		}

		return $examples;
	}

	private function make_sentence_conversational( $sentence ) {
		$conversational_replacements = array(
			'/\bcommence\b/i'      => 'start',
			'/\bterminate\b/i'     => 'end',
			'/\butilize\b/i'       => 'use',
			'/\bapproximately\b/i' => 'about',
			'/\bhowever\b/i'       => 'but',
			'/\bfurthermore\b/i'   => 'also',
			'/\bconsequently\b/i'  => 'so',
		);

		$conversational = preg_replace( array_keys( $conversational_replacements ), array_values( $conversational_replacements ), $sentence );

		// Add personal touch.
		if ( ! preg_match( '/\b(you|your|we|our)\b/i', $conversational ) ) {
			$conversational = "You'll find that " . lcfirst( $conversational );
		}

		return $conversational;
	}

	private function analyze_sentence_structure( $content ) {
		$sentences = preg_split( '/(?<=[.?!])\s+/', $content );
		$analysis  = array(
			'total_sentences'     => count( $sentences ),
			'sentence_lengths'    => array(),
			'sentence_types'      => array(
				'declarative'   => 0,
				'interrogative' => 0,
				'imperative'    => 0,
				'exclamatory'   => 0,
			),
			'complexity_analysis' => array(),
			'readability_metrics' => array(),
			'recommendations'     => array(),
		);

		if ( count( $sentences ) === 0 ) {
			return $analysis;
		}

		$total_words       = 0;
		$long_sentences    = 0;
		$short_sentences   = 0;
		$complex_sentences = 0;

		foreach ( $sentences as $sentence ) {
			$sentence = trim( $sentence );
			if ( empty( $sentence ) ) {
				continue;
			}

			$word_count   = str_word_count( $sentence );
			$total_words += $word_count;

			// Store sentence length.
			$analysis['sentence_lengths'][] = $word_count;

			// Analyze sentence type.
			$analysis['sentence_types'] = $this->categorize_sentence_type( $sentence, $analysis['sentence_types'] );

			// Complexity analysis.
			$complexity_score                  = $this->calculate_sentence_complexity_score( $sentence );
			$analysis['complexity_analysis'][] = array(
				'sentence'         => $sentence,
				'word_count'       => $word_count,
				'complexity_score' => $complexity_score,
				'complexity_level' => $this->get_complexity_level( $complexity_score ),
			);

			// Count sentence types by length.
			if ( $word_count > 25 ) {
				++$long_sentences;
				if ( $complexity_score > 0.7 ) {
					++$complex_sentences;
				}
			} elseif ( $word_count < 10 ) {
				++$short_sentences;
			}
		}

		// Calculate readability metrics.
		$analysis['readability_metrics'] = array(
			'average_sentence_length' => round( $total_words / count( $sentences ), 2 ),
			'words_per_sentence'      => round( $total_words / count( $sentences ), 2 ),
			'long_sentence_ratio'     => round( ( $long_sentences / count( $sentences ) ) * 100, 2 ),
			'short_sentence_ratio'    => round( ( $short_sentences / count( $sentences ) ) * 100, 2 ),
			'complex_sentence_ratio'  => round( ( $complex_sentences / count( $sentences ) ) * 100, 2 ),
			'sentence_variety_score'  => $this->calculate_sentence_variety( $analysis['sentence_lengths'] ),
		);

		// Generate recommendations.
		$analysis['recommendations'] = $this->generate_sentence_structure_recommendations( $analysis );

		return $analysis;
	}

	private function categorize_sentence_type( $sentence, $sentence_types ) {
		$sentence = trim( $sentence );

		if ( preg_match( '/\?$/', $sentence ) ) {
			++$sentence_types['interrogative'];
		} elseif ( preg_match( '/!$/', $sentence ) ) {
			++$sentence_types['exclamatory'];
		} elseif ( preg_match( '/^\s*(Start|Try|Use|Add|Make|Create|Click|Visit|Download)/i', $sentence ) ) {
			++$sentence_types['imperative'];
		} else {
			++$sentence_types['declarative'];
		}

		return $sentence_types;
	}

	private function calculate_sentence_complexity_score( $sentence ) {
		$score = 0;
		$words = str_word_count( $sentence );

		if ( $words === 0 ) {
			return 0;
		}

		// Factor 1: Long words (7+ characters).
		$long_words = preg_match_all( '/\b\w{7,}\b/', $sentence );
		$score     += ( $long_words / $words ) * 0.4;

		// Factor 2: Conjunctions and complex structures.
		$conjunctions = preg_match_all( '/\b(and|but|or|however|although|because|since|while|though|unless|until|when|where|if)\b/i', $sentence );
		$score       += ( $conjunctions / $words ) * 0.3;

		// Factor 3: Clauses and phrases.
		$clause_indicators = preg_match_all( '/(,|;|:|\()/', $sentence );
		$score            += ( $clause_indicators / $words ) * 0.2;

		// Factor 4: Passive voice.
		$passive_voice = preg_match_all( '/\b(am|is|are|was|were|be|being|been)\s+\w+ed\b/i', $sentence );
		$score        += ( $passive_voice / $words ) * 0.1;

		return min( $score, 1.0 );
	}

	private function get_complexity_level( $score ) {
		if ( $score < 0.3 ) {
			return 'simple';
		}
		if ( $score < 0.6 ) {
			return 'moderate';
		}
		return 'complex';
	}

	private function calculate_sentence_variety( $sentence_lengths ) {
		if ( count( $sentence_lengths ) < 2 ) {
			return 0;
		}

		$average  = array_sum( $sentence_lengths ) / count( $sentence_lengths );
		$variance = 0;

		foreach ( $sentence_lengths as $length ) {
			$variance += pow( $length - $average, 2 );
		}

		$variance = $variance / count( $sentence_lengths );
		$std_dev  = sqrt( $variance );

		// Normalize to 0-100 scale (higher = more variety).
		$variety_score = min( ( $std_dev / $average ) * 100, 100 );

		return round( $variety_score, 2 );
	}

	private function generate_sentence_structure_recommendations( $analysis ) {
		$recommendations = array();
		$metrics         = $analysis['readability_metrics'];
		$sentence_types  = $analysis['sentence_types'];

		// Sentence length recommendations.
		if ( $metrics['average_sentence_length'] > 20 ) {
			$recommendations[] = 'Consider shortening sentences. Aim for 15-20 words average for better readability.';
		}

		if ( $metrics['long_sentence_ratio'] > 30 ) {
			$recommendations[] = 'Reduce long sentences (25+ words). Break them into shorter, more digestible sentences.';
		}

		if ( $metrics['short_sentence_ratio'] < 10 ) {
			$recommendations[] = 'Add more short sentences (under 10 words) to improve rhythm and emphasis.';
		}

		// Sentence type variety.
		$total_sentences = $analysis['total_sentences'];
		$question_ratio  = ( $sentence_types['interrogative'] / $total_sentences ) * 100;

		if ( $question_ratio < 5 ) {
			$recommendations[] = 'Add more questions to engage readers and optimize for voice search queries.';
		}

		if ( $sentence_types['imperative'] < 2 ) {
			$recommendations[] = 'Include imperative sentences (commands) to create more actionable content.';
		}

		// Complexity recommendations.
		if ( $metrics['complex_sentence_ratio'] > 25 ) {
			$recommendations[] = 'Simplify complex sentences. Use active voice and break up nested clauses.';
		}

		// Variety recommendations.
		if ( $metrics['sentence_variety_score'] < 30 ) {
			$recommendations[] = 'Vary sentence lengths more. Mix short, medium, and long sentences for better flow.';
		}

		// Voice search specific recommendations.
		$recommendations[] = 'Use conversational sentence structures that mimic how people speak naturally.';
		$recommendations[] = 'Place key information at the beginning of sentences for better voice search results.';

		return array_slice( $recommendations, 0, 8 ); // Return top 8 recommendations.
	}
}
