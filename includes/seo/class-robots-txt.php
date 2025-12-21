<?php

class ProductScraper_Robots_Txt
{

	private $default_rules = array(); // Intentionally empty
	private $custom_rules = array();

	public function __construct()
	{
		add_filter('robots_txt', array($this, 'enhance_robots_txt'), 10, 2);
		add_action('admin_init', array($this, 'register_robots_settings'));
		add_action('wp_ajax_save_robots_rules', array($this, 'ajax_save_robots_rules'));
		add_action('wp_ajax_reset_robots_rules', array($this, 'ajax_reset_robots_rules'));

		$this->load_custom_rules();
	}

	/**
	 * Load custom rules from database
	 */
	private function load_custom_rules()
	{
		$this->custom_rules = get_option('product_scraper_robots_rules', array());
	}

	/**
	 * Enhance robots.txt output
	 */
	public function enhance_robots_txt($output, $public)
	{
		if (!$public) {
			return "User-agent: *\nDisallow: /";
		}

		if (empty($this->custom_rules)) {
			// Zero defaults: do not inject anything
			return $output;
		}

		return $this->generate_robots_content($this->custom_rules);
	}

	/**
	 * Generate robots.txt content
	 */
	private function generate_robots_content($rules)
	{
		$content = "# Robots.txt managed by Product Scraper SEO\n\n";

		foreach ($rules as $section => $section_rules) {

			if ($section === 'user-agent') {
				foreach ($section_rules as $user_agent => $directives) {
					$content .= "User-agent: {$user_agent}\n";

					foreach ($directives as $directive => $paths) {
						if (is_array($paths)) {
							foreach ($paths as $path) {
								$content .= ucfirst($directive) . ": {$path}\n";
							}
						} else {
							$content .= ucfirst($directive) . ": {$paths}\n";
						}
					}

					$content .= "\n";
				}
			}

			if ($section === 'sitemap') {
				foreach ((array) $section_rules as $sitemap_url) {
					$content .= "Sitemap: {$sitemap_url}\n";
				}
				$content .= "\n";
			}
		}

		return trim($content);
	}

	/**
	 * Register settings
	 */
	public function register_robots_settings()
	{
		register_setting('product_scraper_seo_settings', 'product_scraper_robots_rules');
	}

	/**
	 * Get current robots.txt content
	 */
	public function get_current_robots_content()
	{
		$public = get_option('blog_public');
		if (!$public) {
			return "User-agent: *\nDisallow: /";
		}

		return $this->enhance_robots_txt('', $public);
	}

	/**
	 * Validate rules
	 */
	public function validate_rules($rules)
	{
		$errors = array();

		if (!is_array($rules)) {
			return array('Rules must be an array.');
		}

		if (isset($rules['user-agent'])) {
			foreach ($rules['user-agent'] as $ua => $directives) {
				if (!is_array($directives)) {
					$errors[] = "Invalid directives for user-agent {$ua}";
				}
			}
		}

		return $errors;
	}

	/**
	 * Persist custom rules
	 */
	private function save_custom_rules()
	{
		return update_option('product_scraper_robots_rules', $this->custom_rules);
	}

	/**
	 * Reset rules (hard reset)
	 */
	public function reset_to_defaults()
	{
		delete_option('product_scraper_robots_rules');
		$this->custom_rules = array();
		return true;
	}

	/**
	 * AJAX: Save rules
	 */
	public function ajax_save_robots_rules()
	{
		check_ajax_referer('product_scraper_nonce', 'nonce');

		if (!current_user_can('manage_options')) {
			wp_send_json_error('Insufficient permissions');
		}

		$rules = isset($_POST['rules']) ? $_POST['rules'] : array();
		$errors = $this->validate_rules($rules);

		if (!empty($errors)) {
			wp_send_json_error($errors);
		}

		$this->custom_rules = $rules;
		$this->save_custom_rules();

		wp_send_json_success();
	}

	/**
	 * AJAX: Reset rules
	 */
	public function ajax_reset_robots_rules()
	{
		check_ajax_referer('product_scraper_nonce', 'nonce');

		if (!current_user_can('manage_options')) {
			wp_send_json_error('Insufficient permissions');
		}

		$this->reset_to_defaults();
		wp_send_json_success();
	}

	/**
	 * Editor data (pure state)
	 */
	public function get_editor_data()
	{
		return array(
			'custom_rules' => $this->custom_rules,
			'analysis' => $this->analyze_robots_txt(),
			'current_content' => $this->get_current_robots_content(),
		);
	}

	/**
	 * Analyze robots.txt (read-only)
	 */
	public function analyze_robots_txt()
	{
		$analysis = array(
			'status' => 'unknown',
			'issues' => array(),
		);

		$response = wp_remote_get(home_url('/robots.txt'));

		if (is_wp_error($response)) {
			$analysis['status'] = 'error';
			$analysis['issues'][] = 'robots.txt not accessible';
			return $analysis;
		}

		$analysis['status'] = 'accessible';
		return $analysis;
	}
}
