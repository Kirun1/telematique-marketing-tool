<?php

class ProductScraper_Link_Manager
{

	private $batch_size = 50; // Posts per batch for scanning

	public function __construct()
	{
		add_action('wp_ajax_scan_links', [$this, 'ajax_scan_links']);
		add_action('wp_ajax_check_broken_links', [$this, 'ajax_check_broken_links']);
		add_action('save_post', [$this, 'scan_post_links']);
		add_action('delete_post', [$this, 'cleanup_post_links']);
	}

	/**
	 * Scan all posts in batches for links
	 */
	public function scan_all_links()
	{
		$post_types = get_post_types(['public' => true], 'names');
		$links_data = [
			'internal' => [],
			'external' => [],
			'broken' => [],
			'stats' => [],
		];

		foreach ($post_types as $post_type) {
			$offset = 0;

			while (true) {
				$posts = get_posts([
					'post_type' => $post_type,
					'numberposts' => $this->batch_size,
					'offset' => $offset,
					'post_status' => 'publish',
				]);

				if (empty($posts)) {
					break;
				}

				foreach ($posts as $post) {
					$post_links = $this->extract_links_from_content($post->post_content, $post->ID);
					$links_data = $this->merge_links_data($links_data, $post_links);
				}

				$offset += $this->batch_size;
			}
		}

		$links_data['stats'] = $this->calculate_link_stats($links_data);
		update_option('product_scraper_links_data', $links_data);
		update_option('product_scraper_links_scan_timestamp', current_time('timestamp'));

		return $links_data;
	}

	/**
	 * Scan a single post for links
	 */
	public function scan_post_links($post_id)
	{
		$post = get_post($post_id);
		if (!$post || $post->post_status !== 'publish')
			return;

		$post_links = $this->extract_links_from_content($post->post_content, $post_id);
		$existing_data = get_option('product_scraper_links_data', [
			'internal' => [],
			'external' => [],
			'broken' => [],
			'stats' => [],
		]);

		$existing_data = $this->remove_post_links($existing_data, $post_id);
		$updated_data = $this->merge_links_data($existing_data, $post_links);
		$updated_data['stats'] = $this->calculate_link_stats($updated_data);

		update_option('product_scraper_links_data', $updated_data);

		return $updated_data;
	}

	/**
	 * Extract links from post content
	 */
	private function extract_links_from_content($content, $post_id)
	{
		$links = ['internal' => [], 'external' => []];
		if (empty($content))
			return $links;

		libxml_use_internal_errors(true);
		$dom = new DOMDocument();
		$dom->loadHTML('<?xml encoding="utf-8" ?>' . $content);

		foreach ($dom->getElementsByTagName('a') as $anchor) {
			$href = $anchor->getAttribute('href');
			$text = trim($anchor->textContent);
			$nofollow = strtolower($anchor->getAttribute('rel')) === 'nofollow';

			if (empty($href) || $href === '#')
				continue;

			$link_data = [
				'url' => $href,
				'anchor_text' => $text,
				'source_post_id' => $post_id,
				'source_url' => get_permalink($post_id),
				'nofollow' => $nofollow,
				'found_date' => current_time('mysql'),
			];

			if ($this->is_internal_link($href)) {
				$link_data['internal_url'] = $this->normalize_internal_url($href);
				$link_data['target_post_id'] = url_to_postid($link_data['internal_url']);
				$links['internal'][] = $link_data;
			} else {
				$link_data['domain'] = parse_url($href, PHP_URL_HOST);
				$links['external'][] = $link_data;
			}
		}

		return $links;
	}

	/**
	 * Determine if a URL is internal
	 */
	private function is_internal_link($url)
	{
		$home_domain = parse_url(home_url(), PHP_URL_HOST);
		$link_domain = parse_url($url, PHP_URL_HOST);
		return empty($link_domain) || $link_domain === $home_domain;
	}

	/**
	 * Normalize internal URLs
	 */
	private function normalize_internal_url($url)
	{
		if (strpos($url, home_url()) === 0)
			return $url;
		if (strpos($url, '/') === 0)
			return home_url($url);
		return $url;
	}

	/**
	 * Merge links data arrays
	 */
	private function merge_links_data($existing, $new)
	{
		$existing['internal'] = array_merge($existing['internal'], $new['internal']);
		$existing['external'] = array_merge($existing['external'], $new['external']);
		return $existing;
	}

	/**
	 * Remove all links from a post
	 */
	private function remove_post_links($links_data, $post_id)
	{
		$links_data['internal'] = array_values(array_filter(
			$links_data['internal'],
			fn($link) => $link['source_post_id'] != $post_id
		));

		$links_data['external'] = array_values(array_filter(
			$links_data['external'],
			fn($link) => $link['source_post_id'] != $post_id
		));

		return $links_data;
	}

	/**
	 * Calculate link stats
	 */
	private function calculate_link_stats($links_data)
	{
		$stats = [
			'total_internal_links' => count($links_data['internal']),
			'total_external_links' => count($links_data['external']),
			'external_domains' => [],
			'links_per_post' => [],
			'nofollow_count' => 0,
		];

		foreach ($links_data['internal'] as $link) {
			$post_id = $link['source_post_id'];
			$stats['links_per_post'][$post_id] = ($stats['links_per_post'][$post_id] ?? 0) + 1;
			if ($link['nofollow'])
				$stats['nofollow_count']++;
		}

		foreach ($links_data['external'] as $link) {
			$post_id = $link['source_post_id'];
			$domain = $link['domain'];
			$stats['external_domains'][$domain] = ($stats['external_domains'][$domain] ?? 0) + 1;
			$stats['links_per_post'][$post_id] = ($stats['links_per_post'][$post_id] ?? 0) + 1;
			if ($link['nofollow'])
				$stats['nofollow_count']++;
		}

		arsort($stats['external_domains']);
		$stats['external_domains'] = array_slice($stats['external_domains'], 0, 20);

		return $stats;
	}

	/**
	 * Broken link checking
	 */
	public function check_broken_links($links_data = null)
	{
		if (!$links_data)
			$links_data = get_option('product_scraper_links_data', []);

		$broken_links = [];

		// Check internal links
		foreach ($links_data['internal'] as $link) {
			if ($this->is_broken_internal_link($link)) {
				$broken_links[] = array_merge($link, ['error' => 'Internal link broken', 'status_code' => 404]);
			}
		}

		// Check external links (batch or sample)
		$external_links = array_slice($links_data['external'], 0, 50);
		foreach ($external_links as $link) {
			$status = $this->check_external_link_status($link['url']);
			if ($status >= 400) {
				$broken_links[] = array_merge($link, ['error' => 'External link broken', 'status_code' => $status]);
			}
		}

		$links_data['broken'] = $broken_links;
		update_option('product_scraper_links_data', $links_data);

		return $broken_links;
	}

	private function is_broken_internal_link($link)
	{
		if (!empty($link['target_post_id'])) {
			$post = get_post($link['target_post_id']);
			return !$post || $post->post_status !== 'publish';
		}
		$response = wp_remote_head($link['url'], ['timeout' => 10]);
		return is_wp_error($response) || wp_remote_retrieve_response_code($response) >= 400;
	}

	private function check_external_link_status($url)
	{
		$response = wp_remote_head($url, ['timeout' => 10, 'redirection' => 3]);
		if (is_wp_error($response))
			return 0;
		return wp_remote_retrieve_response_code($response);
	}

	/**
	 * Internal linking suggestions (keyword-based)
	 */
	public function get_linking_suggestions()
	{
		$links_data = get_option('product_scraper_links_data', []);
		$suggestions = [];

		$all_posts = get_posts(['numberposts' => -1, 'post_status' => 'publish']);
		$incoming_links = array_column($links_data['internal'], 'target_post_id');

		foreach ($all_posts as $post) {
			$post_id = $post->ID;
			$link_count = $links_data['stats']['links_per_post'][$post_id] ?? 0;

			if ($link_count <= 2) {
				$suggestions[] = [
					'type' => 'low_internal_links',
					'priority' => 'medium',
					'message' => "Post '{$post->post_title}' has only {$link_count} internal links",
					'data' => [
						'post_id' => $post_id,
						'title' => $post->post_title,
						'url' => get_permalink($post_id),
						'link_count' => $link_count,
					]
				];
			}

			if (!in_array($post_id, $incoming_links)) {
				$suggestions[] = [
					'type' => 'orphaned_post',
					'priority' => 'high',
					'message' => "Post '{$post->post_title}' has no incoming internal links",
					'data' => [
						'post_id' => $post_id,
						'title' => $post->post_title,
						'url' => get_permalink($post_id),
					]
				];
			}
		}

		return $suggestions;
	}

	/**
	 * Cleanup links when post is deleted
	 */
	public function cleanup_post_links($post_id)
	{
		$links_data = get_option('product_scraper_links_data', []);
		$updated_data = $this->remove_post_links($links_data, $post_id);
		$updated_data['stats'] = $this->calculate_link_stats($updated_data);
		update_option('product_scraper_links_data', $updated_data);
	}

	/**
	 * Secure AJAX handlers
	 */
	public function ajax_scan_links()
	{
		check_ajax_referer('product_scraper_nonce', 'nonce');
		if (!current_user_can('manage_options'))
			wp_send_json_error('Unauthorized');

		$links_data = $this->scan_all_links();
		wp_send_json_success([
			'links_data' => $links_data,
			'timestamp' => get_option('product_scraper_links_scan_timestamp'),
		]);
	}

	public function ajax_check_broken_links()
	{
		check_ajax_referer('product_scraper_nonce', 'nonce');
		if (!current_user_can('manage_options'))
			wp_send_json_error('Unauthorized');

		$links_data = get_option('product_scraper_links_data', []);
		$broken_links = $this->check_broken_links($links_data);
		wp_send_json_success([
			'broken_links' => $broken_links,
			'total_checked' => count($broken_links),
		]);
	}

	/**
	 * Get full link report
	 */
	public function get_link_report()
	{
		return [
			'links_data' => get_option('product_scraper_links_data', []),
			'suggestions' => $this->get_linking_suggestions(),
			'last_scan' => get_option('product_scraper_links_scan_timestamp'),
		];
	}
}
