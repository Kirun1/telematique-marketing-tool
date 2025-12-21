<?php

class ProductScraper_Chart_Manager
{

	/**
	 * @var ProductScraper_API_Integrations
	 */
	private $api;

	public function __construct()
	{
		$this->api = new ProductScraper_API_Integrations();
		add_action('wp_ajax_get_chart_data', [$this, 'ajax_get_chart_data']);
	}

	/* -------------------------------------------------------------------------
	 * TRAFFIC TREND (GSC ONLY)
	 * ---------------------------------------------------------------------- */

	public function get_traffic_trend_data($period = '30d', $post_id = null)
	{

		$dates = $this->resolve_period_dates($period);

		$rows = $this->api->get_gsc_traffic_timeseries(
			$dates['start'],
			$dates['end'],
			$post_id
		);

		if (empty($rows)) {
			return $this->get_empty_timeseries_chart();
		}

		return [
			'labels' => array_column($rows, 'date'),
			'datasets' => [
				[
					'label' => 'Clicks',
					'data' => array_column($rows, 'clicks'),
					'borderColor' => '#4CAF50',
					'backgroundColor' => 'rgba(76,175,80,0.15)',
					'fill' => true,
					'tension' => 0.3,
				],
				[
					'label' => 'Impressions',
					'data' => array_column($rows, 'impressions'),
					'borderColor' => '#2196F3',
					'fill' => false,
					'tension' => 0.3,
				],
			],
		];
	}

	/* -------------------------------------------------------------------------
	 * KEYWORD PERFORMANCE (GSC QUERIES)
	 * ---------------------------------------------------------------------- */

	public function get_keyword_performance_data($period = '30d', $post_id = null)
	{

		$dates = $this->resolve_period_dates($period);

		$queries = $this->api->get_gsc_top_queries(
			$dates['start'],
			$dates['end'],
			$post_id
		);

		if (empty($queries)) {
			return $this->get_empty_keyword_data();
		}

		return [
			'labels' => array_column($queries, 'query'),
			'datasets' => [
				[
					'label' => 'Clicks',
					'data' => array_column($queries, 'clicks'),
					'backgroundColor' => 'rgba(54,162,235,0.7)',
				],
				[
					'label' => 'Impressions',
					'type' => 'line',
					'data' => array_column($queries, 'impressions'),
					'borderColor' => '#FF6384',
					'fill' => false,
				],
			],
		];
	}

	/* -------------------------------------------------------------------------
	 * COMPETITOR ANALYSIS (DISABLED — GSC CANNOT PROVIDE THIS)
	 * ---------------------------------------------------------------------- */

	public function get_competitor_analysis_data()
	{

		return [
			'labels' => [
				'Domain Authority',
				'Referring Domains',
				'Organic Traffic',
				'Content Score',
				'Social Score',
			],
			'datasets' => [],
		];
	}

	/* -------------------------------------------------------------------------
	 * SEO HEALTH (PAGESPEED ONLY)
	 * ---------------------------------------------------------------------- */

	public function get_seo_health_data()
	{

		$scores = $this->api->get_pagespeed_scores();

		if (empty($scores)) {
			return $this->get_empty_seo_health_data();
		}

		return [
			'labels' => ['Performance', 'Accessibility', 'Best Practices', 'SEO'],
			'datasets' => [
				[
					'label' => 'Score',
					'data' => [
						$scores['performance'] ?? 0,
						$scores['accessibility'] ?? 0,
						$scores['best_practices'] ?? 0,
						$scores['seo'] ?? 0,
					],
					'backgroundColor' => [
						'rgba(255,99,132,0.6)',
						'rgba(54,162,235,0.6)',
						'rgba(255,206,86,0.6)',
						'rgba(75,192,192,0.6)',
					],
				],
			],
		];
	}

	/* -------------------------------------------------------------------------
	 * AJAX
	 * ---------------------------------------------------------------------- */

	public function ajax_get_chart_data()
	{

		if (
			empty($_POST['nonce']) ||
			!wp_verify_nonce($_POST['nonce'], 'product_scraper_charts')
		) {
			wp_send_json_error('Invalid nonce');
		}

		if (!current_user_can('manage_options')) {
			wp_send_json_error('Permission denied');
		}

		$chart_type = sanitize_text_field($_POST['chart_type']);
		$period = sanitize_text_field($_POST['period'] ?? '30d');
		$post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : null;

		switch ($chart_type) {
			case 'traffic_trend':
				$data = $this->get_traffic_trend_data($period, $post_id);
				break;

			case 'keyword_performance':
				$data = $this->get_keyword_performance_data($period, $post_id);
				break;

			case 'competitor_analysis':
				$data = $this->get_competitor_analysis_data();
				break;

			case 'seo_health':
				$data = $this->get_seo_health_data();
				break;

			default:
				wp_send_json_error('Invalid chart type');
		}

		wp_send_json_success($data);
	}

	/* -------------------------------------------------------------------------
	 * HELPERS
	 * ---------------------------------------------------------------------- */

	private function resolve_period_dates($period)
	{

		$end = date('Y-m-d');

		switch ($period) {
			case '7d':
				$start = date('Y-m-d', strtotime('-7 days'));
				break;
			case '90d':
				$start = date('Y-m-d', strtotime('-90 days'));
				break;
			default:
				$start = date('Y-m-d', strtotime('-30 days'));
		}

		return compact('start', 'end');
	}

	private function get_empty_timeseries_chart()
	{
		return [
			'labels' => [],
			'datasets' => [],
		];
	}

	private function get_empty_keyword_data()
	{
		return [
			'labels' => [],
			'datasets' => [],
		];
	}

	private function get_empty_seo_health_data()
	{
		return [
			'labels' => ['Performance', 'Accessibility', 'Best Practices', 'SEO'],
			'datasets' => [],
		];
	}
}
