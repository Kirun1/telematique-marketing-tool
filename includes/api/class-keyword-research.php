<?php

class ProductScraper_Keyword_Research
{

	private $api;

	public function __construct()
	{
		$this->api = new ProductScraper_API_Integrations();

		add_action('wp_ajax_research_keywords', [$this, 'ajax_research_keywords']);
		add_action('wp_ajax_get_keyword_suggestions', [$this, 'ajax_get_keyword_suggestions']);
	}

	public function ajax_research_keywords()
	{
		if (!wp_verify_nonce($_POST['nonce'], 'keyword_research_nonce')) {
			wp_die('Security check failed');
		}

		$keyword = sanitize_text_field($_POST['keyword']);
		$competitor_url = esc_url_raw($_POST['competitor_url'] ?? '');

		$research_data = $this->research_keyword($keyword, $competitor_url);

		wp_send_json_success($research_data);
	}

	private function research_keyword($keyword, $competitor_url = '')
	{
		$data = [
			'keyword' => $keyword,
			'volume_estimate' => $this->estimate_search_volume($keyword),
			'competition' => $this->estimate_competition($keyword),
			'trend' => $this->get_trend_data($keyword),
			'related_keywords' => $this->get_related_keywords($keyword),
			'content_gaps' => [],
			'competitor_analysis' => [],
		];

		if (!empty($competitor_url)) {
			$data['competitor_analysis'] = $this->analyze_competitor_keywords($competitor_url, $keyword);
		}

		$data['content_gaps'] = $this->identify_content_gaps($keyword, $data);

		return $data;
	}

	private function estimate_search_volume($keyword)
	{
		$post_id = url_to_postid(get_permalink());

		if ($post_id) {
			$metrics = $this->api->get_gsc_page_metrics($post_id);
			return [
				'monthly' => $metrics['impressions'] ?? null,
				'trend' => $metrics['ctr'] !== null ? 'stable' : null,
				'seasonality' => null,
			];
		}

		return [
			'monthly' => null,
			'trend' => null,
			'seasonality' => null,
		];
	}

	private function estimate_competition($keyword)
	{
		$referring_domains = $this->api->get_referring_domains();
		$top_keywords = $this->api->get_top_keywords();

		$competition_score = 0;

		foreach ($top_keywords as $kw) {
			if (strcasecmp($kw['keyword'], $keyword) === 0) {
				$competition_score += 50;
			}
		}

		foreach ($referring_domains as $domain) {
			if (isset($domain['anchor_text']) && stripos($domain['anchor_text'], $keyword) !== false) {
				$competition_score += 10;
			}
		}

		$competition_score = min($competition_score, 100);

		$difficulty = $competition_score > 70 ? 'high' : ($competition_score > 40 ? 'medium' : 'low');

		return [
			'score' => $competition_score ?: null,
			'difficulty' => $difficulty ?: null,
			'advertisers' => null,
		];
	}

	private function get_trend_data($keyword)
	{
		$engagement = $this->api->get_engagement_metrics();

		if (empty($engagement['page_views'])) {
			return [];
		}

		return $engagement['page_views'];
	}

	private function get_related_keywords($keyword)
	{
		$top_keywords = $this->api->get_top_keywords();
		$related = [];

		foreach ($top_keywords as $kw) {
			if (stripos($kw['keyword'], $keyword) !== false && strcasecmp($kw['keyword'], $keyword) !== 0) {
				$related[] = [
					'keyword' => $kw['keyword'],
					'volume' => $kw['volume'] ?? null,
					'competition' => $kw['competition'] ?? null,
				];
			}
		}

		return array_slice($related, 0, 10);
	}

	private function analyze_competitor_keywords($url, $focus_keyword)
	{
		$competitor_data = $this->api->get_competitor_analysis($url);

		if (empty($competitor_data)) {
			return [];
		}

		$overlap = 0;
		foreach (($competitor_data['top_keywords'] ?? []) as $kw) {
			if (stripos($kw['keyword'], $focus_keyword) !== false) {
				$overlap++;
			}
		}

		$competitor_data['keyword_overlap'] = $overlap;

		return $competitor_data;
	}

	private function identify_content_gaps($keyword, $research_data)
	{
		$gaps = [];
		$top_keywords = $this->api->get_top_keywords();
		$competitor_keywords = [];

		foreach (($research_data['competitor_analysis']['top_keywords'] ?? []) as $kw) {
			$competitor_keywords[] = $kw['keyword'];
		}

		foreach ($top_keywords as $kw) {
			if (!in_array($kw['keyword'], $competitor_keywords, true) && stripos($kw['keyword'], $keyword) !== false) {
				$gaps[] = [
					'keyword' => $kw['keyword'],
					'opportunity_score' => $kw['competition'] !== null ? 100 - $kw['competition'] : null,
					'content_type' => 'blog_post',
				];
			}
		}

		return array_slice($gaps, 0, 5);
	}

	public function ajax_get_keyword_suggestions()
	{
		if (!wp_verify_nonce($_POST['nonce'], 'keyword_suggestions_nonce')) {
			wp_die('Security check failed');
		}

		$content = wp_kses_post($_POST['content']);
		$suggestions = $this->generate_keyword_suggestions($content);

		wp_send_json_success($suggestions);
	}

	private function generate_keyword_suggestions($content)
	{
		$clean_content = wp_strip_all_tags($content);
		$words = str_word_count($clean_content, 1);

		$stop_words = $this->get_stop_words();
		$word_freq = array_count_values($words);

		foreach ($stop_words as $stop_word) {
			unset($word_freq[$stop_word]);
		}

		arsort($word_freq);
		$top_words = array_slice($word_freq, 0, 10);

		$suggestions = [];
		foreach ($top_words as $word => $freq) {
			if (strlen($word) > 3) {
				$suggestions[] = [
					'keyword' => $word,
					'frequency' => $freq,
					'relevance' => 'high',
				];
			}
		}

		return $suggestions;
	}

	private function get_stop_words()
	{
		return ['the', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by', 'a', 'an', 'is', 'are', 'was', 'were'];
	}

	// TODO: Currently these REST API routes are not called anywhere in the plugin.
	// Revisit to either connect them to admin pages, frontend AJAX, or other triggers
	// so that features like keyword research, content analysis, and optimization are active.
}
