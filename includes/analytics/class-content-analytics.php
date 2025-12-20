<?php
class ProductScraper_Content_Analytics
{
	protected ProductScraper_API_Integrations $api;

	public function __construct()
	{
		$this->api = new ProductScraper_API_Integrations();
	}

	public function get_content_performance($post_id)
	{
		return array(
			'organic_traffic' => $this->api->get_organic_traffic($post_id),
			'average_position' => $this->get_average_position($post_id),
			'click_through_rate' => $this->get_ctr($post_id),
			'impressions' => $this->get_impressions($post_id),
			'engagement_metrics' => $this->get_engagement_metrics($post_id),
			'top_keywords' => $this->get_top_keywords($post_id),
		);
	}

	private function get_average_position($post_id)
	{
		$metrics = $this->api->get_gsc_page_metrics($post_id);

		return array(
			'average_position' => $metrics['position'] ?? null,
		);
	}

	private function get_ctr($post_id)
	{
		$metrics = $this->api->get_gsc_page_metrics($post_id);

		return array(
			'ctr' => $metrics['ctr'] ?? null,
			'clicks' => $metrics['clicks'] ?? null,
		);
	}

	private function get_impressions($post_id)
	{
		$metrics = $this->api->get_gsc_page_metrics($post_id);

		return array(
			'impressions' => $metrics['impressions'] ?? null,
		);
	}

	private function get_engagement_metrics($post_id)
	{
		$ga4 = $this->api->get_ga4_engagement_metrics($post_id);

		return array(
			'avg_time_on_page' => $ga4['avg_time_on_page'] ?? null,
			'pages_per_session' => $ga4['pages_per_session'] ?? null,
			'conversion_rate' => $ga4['conversion_rate'] ?? null,
			'comments' => get_comments_number($post_id),
		);
	}

	private function get_top_keywords($post_id)
	{
		return $this->api->get_gsc_top_queries($post_id, 10);
	}

	/* =====================
	 * Content decay analysis
	 * ===================== */

	public function analyze_content_decay($post_id)
	{
		$performance = $this->get_content_performance($post_id);

		$score = 0;
		$reasons = [];

		if (($performance['organic_traffic']['change'] ?? 0) < 0) {
			$score += 25;
			$reasons[] = 'Declining organic traffic';
		}

		if (($performance['average_position']['average_position'] ?? 0) > 20) {
			$score += 25;
			$reasons[] = 'Poor average search position';
		}

		if (($performance['engagement_metrics']['avg_time_on_page'] ?? 0) < 60) {
			$score += 25;
			$reasons[] = 'Low engagement time';
		}

		if (($performance['click_through_rate']['ctr'] ?? 0) < 0.03) {
			$score += 25;
			$reasons[] = 'Low click-through rate';
		}

		return array(
			'decay_score' => $score,
			'health_status' => $this->get_health_status($score),
			'reasons' => $reasons,
			'recommendations' => $this->get_decay_recommendations($score),
		);
	}

	private function get_health_status($score)
	{
		if ($score < 25)
			return 'healthy';
		if ($score < 50)
			return 'warning';
		if ($score < 75)
			return 'concerning';
		return 'critical';
	}

	private function get_decay_recommendations($score)
	{
		if ($score < 25)
			return [];

		$recs = ['Update content with current information'];

		if ($score >= 50) {
			$recs[] = 'Expand or refresh content sections';
		}

		if ($score >= 75) {
			$recs[] = 'Consider full content rewrite';
		}

		return $recs;
	}
}
