<?php
class ProductScraper_AI_Title_Optimizer {

	private $power_words;
	private $emotional_words;
	private $curiosity_indicators;

	public function __construct() {
		$this->initialize_word_lists();
	}

	/**
	 * Initialize word lists for analysis
	 */
	private function initialize_word_lists() {
		$this->power_words = array(
			// Action words
			'discover',
			'unlock',
			'master',
			'boost',
			'maximize',
			'transform',
			'achieve',
			'dominate',
			'crush',
			'skyrocket',
			'explode',
			'unleash',
			'supercharge',
			'revolutionize',
			'dominate',
			'guarantee',
			'proven',
			'instant',
			'easy',
			'simple',
			'quick',
			'fast',
			'effortless',
			'secret',
			'hidden',
			'little-known',
			'insider',
			'confidential',
			'exclusive',
			'ultimate',
			'complete',
			'comprehensive',
			'definitive',
			'essential',
			'must-have',
			'free',
			'bonus',
			'special',
			'limited',
			'exclusive',
			'new',
			'updated',
			'surprising',
			'shocking',
			'amazing',
			'incredible',
			'fantastic',
			'brilliant',
			'powerful',
			'effective',
			'efficient',
			'optimal',
			'premium',
			'elite',
		);

		$this->emotional_words = array(
			// Positive emotions
			'amazing',
			'awesome',
			'brilliant',
			'exciting',
			'fantastic',
			'incredible',
			'wonderful',
			'thrilling',
			'joyful',
			'happy',
			'delighted',
			'pleased',
			'satisfied',
			'content',
			'love',
			'adore',
			'cherish',
			'treasure',
			'value',
			'appreciate',
			'success',
			'achievement',
			'victory',
			'triumph',
			'accomplishment',
			'breakthrough',

			// Negative emotions (for problem-solving)
			'frustrating',
			'annoying',
			'difficult',
			'challenging',
			'complicated',
			'stressful',
			'problem',
			'issue',
			'struggle',
			'pain',
			'headache',
			'nightmare',
			'avoid',
			'prevent',
			'solve',
			'fix',
			'eliminate',
			'overcome',
		);

		$this->curiosity_indicators = array(
			'secret',
			'hidden',
			'little-known',
			'surprising',
			'shocking',
			'unexpected',
			'revealed',
			'exposed',
			'discovered',
			'uncovered',
			'the truth about',
			'what nobody tells you',
			'what they don\'t want you to know',
			'this one trick',
			'this simple method',
			'this weird tip',
			'why',
			'how',
			'what if',
			'the real reason',
			'the hidden truth',
		);
	}

	public function generate_title_variations( $keyword, $current_title = '' ) {
		// AI-powered title generation
		$variations = array(
			$this->generate_how_to_title( $keyword ),
			$this->generate_list_title( $keyword ),
			$this->generate_question_title( $keyword ),
			$this->generate_number_title( $keyword ),
			$this->generate_ultimate_guide_title( $keyword ),
			$this->generate_secret_title( $keyword ),
			$this->generate_problem_solution_title( $keyword ),
			$this->generate_comparison_title( $keyword ),
			$this->generate_mistake_title( $keyword ),
			$this->generate_myth_title( $keyword ),
		);

		// Add current title analysis if provided
		if ( ! empty( $current_title ) ) {
			$optimized_current = $this->optimize_existing_title( $current_title, $keyword );
			if ( $optimized_current ) {
				array_unshift( $variations, $optimized_current );
			}
		}

		return array_filter( $variations );
	}

	/**
	 * Generate "How To" style title
	 */
	private function generate_how_to_title( $keyword ) {
		$templates = array(
			'How to {keyword}: A Step-by-Step Guide',
			'How to {keyword} in [Number] Easy Steps',
			'The Ultimate Guide on How to {keyword}',
			'How to {keyword} Like a Pro',
			'Learn How to {keyword} the Right Way',
			'How to {keyword}: Tips from Experts',
			'How to {keyword} for Beginners',
			'Master the Art of {keyword}: Complete Guide',
		);

		return $this->apply_title_template( $templates, $keyword );
	}

	/**
	 * Generate list-style title
	 */
	private function generate_list_title( $keyword ) {
		$numbers = array( 5, 7, 10, 15, 21, 31, 50, 101 );
		$number  = $numbers[ array_rand( $numbers ) ];

		$templates = array(
			'[number] {keyword} Tips You Need to Know',
			'[number] Best Ways to {keyword}',
			'[number] {keyword} Strategies That Work',
			'[number] Essential {keyword} Techniques',
			'[number] Proven {keyword} Methods',
			'[number] {keyword} Hacks for Better Results',
			'[number] {keyword} Ideas to Try Today',
			'[number] {keyword} Secrets Experts Use',
		);

		$template = $templates[ array_rand( $templates ) ];
		return str_replace( array( '[number]', '{keyword}' ), array( $number, $keyword ), $template );
	}

	/**
	 * Generate question-based title
	 */
	private function generate_question_title( $keyword ) {
		$templates = array(
			'Are You Making These {keyword} Mistakes?',
			'What is the Best Way to {keyword}?',
			"Why Your {keyword} Isn't Working (And How to Fix It)",
			'How Can You {keyword} More Effectively?',
			'Is Your {keyword} Strategy Actually Working?',
			'What Nobody Tells You About {keyword}',
			'Why Most People Fail at {keyword} (And How to Succeed)',
			'The Real Truth About {keyword}: What You Need to Know',
		);

		return $this->apply_title_template( $templates, $keyword );
	}

	/**
	 * Generate number-focused title
	 */
	private function generate_number_title( $keyword ) {
		$templates = array(
			'[number] {keyword} Statistics That Will Surprise You',
			"[number] {keyword} Facts You Probably Didn't Know",
			'[number] {keyword} Examples That Actually Work',
			'[number] {keyword} Case Studies Revealed',
			'[number] {keyword} Trends to Watch',
			'[number] {keyword} Insights from Industry Leaders',
			'[number] {keyword} Lessons I Learned the Hard Way',
			'[number] {keyword} Predictions for [Year]',
		);

		$number   = rand( 7, 27 );
		$year     = date( 'Y' ) + 1;
		$template = $templates[ array_rand( $templates ) ];
		return str_replace( array( '[number]', '{keyword}', '[Year]' ), array( $number, $keyword, $year ), $template );
	}

	/**
	 * Generate ultimate guide title
	 */
	private function generate_ultimate_guide_title( $keyword ) {
		$templates = array(
			'The Ultimate Guide to {keyword}',
			'The Complete {keyword} Handbook',
			'{keyword}: The Definitive Guide',
			'Everything You Need to Know About {keyword}',
			'The A-Z Guide to Mastering {keyword}',
			'{keyword} Explained: The Ultimate Resource',
			"The Only {keyword} Guide You'll Ever Need",
			'Mastering {keyword}: The Complete Blueprint',
		);

		return $this->apply_title_template( $templates, $keyword );
	}

	/**
	 * Generate secret/revealing title
	 */
	private function generate_secret_title( $keyword ) {
		$templates = array(
			'The Secret to {keyword} That Nobody Talks About',
			'Little-Known {keyword} Secrets Revealed',
			'The Hidden Truth About {keyword}',
			"{keyword} Secrets the Pros Don't Want You to Know",
			'Confidential: The {keyword} Strategy They Tried to Hide',
			'The Forbidden Truth About {keyword}',
			'Insider {keyword} Techniques They Keep Secret',
			"The {keyword} Method They Don't Teach in Schools",
		);

		return $this->apply_title_template( $templates, $keyword );
	}

	/**
	 * Generate problem-solution title
	 */
	private function generate_problem_solution_title( $keyword ) {
		$templates = array(
			"Struggling with {keyword}? Here's the Solution",
			'The {keyword} Problem (And How to Solve It Forever)',
			'Why {keyword} is So Difficult and What to Do About It',
			'Tired of {keyword}? This Changes Everything',
			'The {keyword} Nightmare Ends Here',
			'Finally: A Real Solution to Your {keyword} Problems',
			'How I Overcame My {keyword} Challenges',
			'The End of {keyword} Frustration',
		);

		return $this->apply_title_template( $templates, $keyword );
	}

	/**
	 * Generate comparison title
	 */
	private function generate_comparison_title( $keyword ) {
		$templates = array(
			'{keyword} vs [Alternative]: Which is Better?',
			'The Great {keyword} Debate: Pros and Cons',
			'{keyword} Compared: What Really Works?',
			'Side-by-Side: {keyword} Analysis',
			'{keyword} Showdown: Which Method Wins?',
			'The Truth About {keyword} Alternatives',
			'{keyword} Face-Off: Breaking Down the Options',
			'{keyword} or [Alternative]? Making the Right Choice',
		);

		$alternatives = array( 'Traditional Methods', 'Old Techniques', 'The Competition', 'Other Solutions', 'Popular Alternatives' );
		$alternative  = $alternatives[ array_rand( $alternatives ) ];

		$template = $templates[ array_rand( $templates ) ];
		return str_replace( array( '{keyword}', '[Alternative]' ), array( $keyword, $alternative ), $template );
	}

	/**
	 * Generate mistake-focused title
	 */
	private function generate_mistake_title( $keyword ) {
		$templates = array(
			"[number] {keyword} Mistakes You're Probably Making",
			'The [number] Biggest {keyword} Mistakes (And How to Avoid Them)',
			'Are You Guilty of These {keyword} Errors?',
			'{keyword} Blunders That Could Cost You',
			'The Top {keyword} Pitfalls and How to Steer Clear',
			'Common {keyword} Mistakes That Sabotage Results',
			'{keyword} Fails: What Not to Do',
			"The Worst {keyword} Mistakes I've Ever Seen",
		);

		$number   = rand( 3, 11 );
		$template = $templates[ array_rand( $templates ) ];
		return str_replace( array( '[number]', '{keyword}' ), array( $number, $keyword ), $template );
	}

	/**
	 * Generate myth-busting title
	 */
	private function generate_myth_title( $keyword ) {
		$templates = array(
			'[number] {keyword} Myths Debunked',
			'The Truth About {keyword}: Separating Fact from Fiction',
			"{keyword} Lies You've Been Told",
			'Busting Common {keyword} Myths',
			'The {keyword} Reality Check: What Actually Works',
			'Why Everything You Know About {keyword} is Wrong',
			'{keyword} Fiction vs Reality',
			"The {keyword} Scam: Don't Fall for These Lies",
		);

		$number   = rand( 3, 7 );
		$template = $templates[ array_rand( $templates ) ];
		return str_replace( array( '[number]', '{keyword}' ), array( $number, $keyword ), $template );
	}

	/**
	 * Apply title template with keyword
	 */
	private function apply_title_template( $templates, $keyword ) {
		$template = $templates[ array_rand( $templates ) ];
		return str_replace( '{keyword}', $keyword, $template );
	}

	/**
	 * Optimize existing title
	 */
	private function optimize_existing_title( $title, $keyword ) {
		$analysis = $this->analyze_title_emotional_impact( $title );

		// If title is already strong, return as is
		if ( $analysis['emotional_score'] >= 70 && $analysis['click_through_rate'] >= 60 ) {
			return $title;
		}

		// Add power words if missing
		if ( $analysis['power_words'] < 2 ) {
			$title = $this->enhance_with_power_words( $title );
		}

		// Add curiosity gap if missing
		if ( ! $analysis['curiosity_gap'] ) {
			$title = $this->add_curiosity_gap( $title );
		}

		// Ensure keyword is included
		if ( stripos( $title, $keyword ) === false ) {
			$title = $this->incorporate_keyword( $title, $keyword );
		}

		return $this->trim_to_optimal_length( $title );
	}

	/**
	 * Analyze title emotional impact
	 */
	public function analyze_title_emotional_impact( $title ) {
		return array(
			'emotional_score'         => $this->calculate_emotional_score( $title ),
			'curiosity_gap'           => $this->check_curiosity_gap( $title ),
			'power_words'             => $this->count_power_words( $title ),
			'click_through_rate'      => $this->predict_ctr( $title ),
			'length_score'            => $this->analyze_title_length( $title ),
			'keyword_placement'       => $this->analyze_keyword_placement( $title ),
			'readability_score'       => $this->analyze_title_readability( $title ),
			'improvement_suggestions' => $this->generate_improvement_suggestions( $title ),
		);
	}

	/**
	 * Calculate emotional score
	 */
	private function calculate_emotional_score( $title ) {
		$score = 50; // Base score
		$words = str_word_count( strtolower( $title ), 1 );

		// Check for emotional words
		foreach ( $words as $word ) {
			if ( in_array( $word, $this->emotional_words ) ) {
				$score += 5;
			}
			if ( in_array( $word, $this->power_words ) ) {
				$score += 3;
			}
		}

		// Bonus for questions and curiosity indicators
		if ( $this->check_curiosity_gap( $title ) ) {
			$score += 15;
		}

		// Bonus for numbers (specificity)
		if ( preg_match( '/\d+/', $title ) ) {
			$score += 10;
		}

		return min( 100, max( 0, $score ) );
	}

	/**
	 * Check for curiosity gap
	 */
	private function check_curiosity_gap( $title ) {
		$title_lower = strtolower( $title );

		foreach ( $this->curiosity_indicators as $indicator ) {
			if ( strpos( $title_lower, $indicator ) !== false ) {
				return true;
			}
		}

		// Check for question marks
		if ( strpos( $title, '?' ) !== false ) {
			return true;
		}

		// Check for implied questions
		$implied_questions = array( 'why', 'how', 'what if', 'the truth about', 'secret' );
		foreach ( $implied_questions as $question ) {
			if ( strpos( $title_lower, $question ) !== false ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * Count power words
	 */
	private function count_power_words( $title ) {
		$count = 0;
		$words = str_word_count( strtolower( $title ), 1 );

		foreach ( $words as $word ) {
			if ( in_array( $word, $this->power_words ) ) {
				++$count;
			}
		}

		return $count;
	}

	/**
	 * Predict click-through rate
	 */
	private function predict_ctr( $title ) {
		$ctr_score = 50; // Base CTR

		// Length optimization (50-60 characters ideal)
		$length = strlen( $title );
		if ( $length >= 50 && $length <= 60 ) {
			$ctr_score += 20;
		} elseif ( $length >= 40 && $length <= 70 ) {
			$ctr_score += 10;
		} else {
			$ctr_score -= 15;
		}

		// Power words boost
		$power_words_count = $this->count_power_words( $title );
		$ctr_score        += ( $power_words_count * 5 );

		// Curiosity gap boost
		if ( $this->check_curiosity_gap( $title ) ) {
			$ctr_score += 15;
		}

		// Numbers boost (specificity)
		if ( preg_match( '/\d+/', $title ) ) {
			$ctr_score += 10;
		}

		// Emotional score influence
		$emotional_score = $this->calculate_emotional_score( $title );
		$ctr_score      += ( $emotional_score - 50 ) * 0.3;

		return min( 100, max( 0, round( $ctr_score ) ) );
	}

	/**
	 * Analyze title length
	 */
	private function analyze_title_length( $title ) {
		$length = strlen( $title );

		if ( $length >= 50 && $length <= 60 ) {
			return array(
				'score'  => 100,
				'status' => 'Perfect',
			);
		} elseif ( $length >= 40 && $length <= 70 ) {
			return array(
				'score'  => 80,
				'status' => 'Good',
			);
		} elseif ( $length < 40 ) {
			return array(
				'score'  => 40,
				'status' => 'Too Short',
			);
		} else {
			return array(
				'score'  => 30,
				'status' => 'Too Long',
			);
		}
	}

	/**
	 * Analyze keyword placement
	 */
	private function analyze_keyword_placement( $title ) {
		// This would typically use the focus keyword from context
		// For now, we'll analyze general keyword placement patterns
		$words      = explode( ' ', $title );
		$first_word = strtolower( $words[0] );
		$last_word  = strtolower( end( $words ) );

		$score = 50;

		// Bonus for keyword at beginning
		if ( in_array( $first_word, array( 'the', 'a', 'an', 'how', 'why', 'what' ) ) ) {
			$score += 10;
		}

		// Penalty for weak endings
		if ( in_array( $last_word, array( 'a', 'the', 'and', 'or', 'but' ) ) ) {
			$score -= 15;
		}

		return min( 100, max( 0, $score ) );
	}

	/**
	 * Analyze title readability
	 */
	private function analyze_title_readability( $title ) {
		$word_count          = str_word_count( $title );
		$sentence_count      = preg_match_all( '/[.!?]+/', $title ) + 1;
		$avg_sentence_length = $word_count / $sentence_count;

		if ( $avg_sentence_length <= 8 ) {
			return 90;
		} elseif ( $avg_sentence_length <= 12 ) {
			return 75;
		} elseif ( $avg_sentence_length <= 15 ) {
			return 60;
		} else {
			return 40;
		}
	}

	/**
	 * Generate improvement suggestions
	 */
	private function generate_improvement_suggestions( $title ) {
		$suggestions = array();
		$analysis    = $this->analyze_title_emotional_impact( $title );

		if ( $analysis['power_words'] < 2 ) {
			$suggestions[] = 'Add more power words to increase emotional impact';
		}

		if ( ! $analysis['curiosity_gap'] ) {
			$suggestions[] = 'Create a curiosity gap to encourage clicks';
		}

		if ( $analysis['click_through_rate'] < 60 ) {
			$suggestions[] = 'Optimize for higher predicted click-through rate';
		}

		$length_analysis = $this->analyze_title_length( $title );
		if ( $length_analysis['score'] < 80 ) {
			$suggestions[] = $length_analysis['status'] . ' - aim for 50-60 characters';
		}

		if ( $analysis['emotional_score'] < 60 ) {
			$suggestions[] = 'Increase emotional appeal with stronger language';
		}

		return array_slice( $suggestions, 0, 3 );
	}

	/**
	 * Enhance title with power words
	 */
	private function enhance_with_power_words( $title ) {
		$power_words = array_filter(
			$this->power_words,
			function ( $word ) use ( $title ) {
				return stripos( $title, $word ) === false;
			}
		);

		if ( empty( $power_words ) ) {
			return $title;
		}

		$selected_word = $power_words[ array_rand( $power_words ) ];

		// Add power word at beginning or end
		if ( rand( 0, 1 ) ) {
			return ucfirst( $selected_word ) . ' ' . lcfirst( $title );
		} else {
			return $title . ': ' . ucfirst( $selected_word ) . ' Tips';
		}
	}

	/**
	 * Add curiosity gap to title
	 */
	private function add_curiosity_gap( $title ) {
		$curiosity_phrases = array(
			'The Secret Nobody Tells You',
			'What They Don\'t Want You to Know',
			'The Surprising Truth',
			'This One Trick',
			'Why This Works',
			'The Real Reason',
		);

		$phrase = $curiosity_phrases[ array_rand( $curiosity_phrases ) ];

		if ( strlen( $title ) + strlen( $phrase ) < 70 ) {
			return $title . ': ' . $phrase;
		}

		return $title;
	}

	/**
	 * Incorporate keyword into title
	 */
	private function incorporate_keyword( $title, $keyword ) {
		$words = explode( ' ', $title );
		if ( count( $words ) > 3 ) {
			// Replace a middle word with the keyword
			$middle_index           = floor( count( $words ) / 2 );
			$words[ $middle_index ] = $keyword;
			return implode( ' ', $words );
		} else {
			// Add keyword at beginning
			return $keyword . ': ' . $title;
		}
	}

	/**
	 * Trim title to optimal length
	 */
	private function trim_to_optimal_length( $title ) {
		if ( strlen( $title ) <= 70 ) {
			return $title;
		}

		// Try to trim from natural break points
		if ( preg_match( '/^(.{50,65}[.!?:])\s/', $title, $matches ) ) {
			return $matches[1];
		}

		// Fallback: trim to 65 characters and add ellipsis
		return substr( $title, 0, 65 ) . '...';
	}

	/**
	 * Generate A/B test variations
	 */
	public function generate_ab_test_variations( $base_title, $keyword, $count = 3 ) {
		$variations = array();

		for ( $i = 0; $i < $count; $i++ ) {
			$method = array(
				'enhance_with_power_words',
				'add_curiosity_gap',
				'incorporate_keyword',
				'trim_to_optimal_length',
			)[ array_rand( array( 'enhance_with_power_words', 'add_curiosity_gap', 'incorporate_keyword', 'trim_to_optimal_length' ) ) ];

			$variation = $this->$method( $base_title, $keyword );
			if ( $variation !== $base_title ) {
				$variations[] = $variation;
			}
		}

		return array_slice( array_unique( $variations ), 0, $count );
	}

	/**
	 * Compare multiple titles and recommend best
	 */
	public function compare_titles( $titles ) {
		$scored_titles = array();

		foreach ( $titles as $title ) {
			$analysis        = $this->analyze_title_emotional_impact( $title );
			$scored_titles[] = array(
				'title'    => $title,
				'score'    => $analysis['click_through_rate'],
				'analysis' => $analysis,
			);
		}

		// Sort by score descending
		usort(
			$scored_titles,
			function ( $a, $b ) {
				return $b['score'] - $a['score'];
			}
		);

		return $scored_titles;
	}
}
