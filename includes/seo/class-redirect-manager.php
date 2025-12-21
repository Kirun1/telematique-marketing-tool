<?php
class ProductScraper_Redirect_Manager
{

	private $redirect_table;
	private $table_exists = false;

	public function __construct()
	{
		global $wpdb;

		$this->redirect_table = $wpdb->prefix . 'seo_redirects';
		$this->table_exists = $this->check_table_exists();

		if ($this->table_exists) {
			add_action('template_redirect', array($this, 'maybe_handle_redirect'), 1);
		}
	}

	/**
	 * Plugin activation callback
	 * Creates or updates the redirects table
	 */
	public static function activate()
	{
		global $wpdb;

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';

		$table_name = $wpdb->prefix . 'seo_redirects';
		$charset = $wpdb->get_charset_collate();

		$sql = "
			CREATE TABLE {$table_name} (
				id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
				from_url VARCHAR(255) NOT NULL,
				to_url VARCHAR(255) NOT NULL,
				type SMALLINT NOT NULL DEFAULT 301,
				PRIMARY KEY (id),
				UNIQUE KEY from_url (from_url)
			) {$charset};
		";

		dbDelta($sql);
	}

	/**
	 * Check if redirect table exists
	 */
	private function check_table_exists()
	{
		global $wpdb;

		$found = $wpdb->get_var(
			$wpdb->prepare(
				"SHOW TABLES LIKE %s",
				$this->redirect_table
			)
		);

		return $found === $this->redirect_table;
	}

	/**
	 * Create or update a redirect
	 */
	public function create_redirect($from_url, $to_url, $type = 301)
	{
		if (!$this->table_exists) {
			return false;
		}

		global $wpdb;

		$type = (int) $type;
		if (!in_array($type, array(301, 302, 307), true)) {
			return false;
		}

		$from_url = esc_url_raw(untrailingslashit($from_url));
		$to_url = esc_url_raw($to_url);

		if (empty($from_url) || empty($to_url)) {
			return false;
		}

		return (bool) $wpdb->replace(
			$this->redirect_table,
			array(
				'from_url' => $from_url,
				'to_url' => $to_url,
				'type' => $type,
			),
			array('%s', '%s', '%d')
		);
	}

	/**
	 * Execute redirect if current request matches
	 */
	public function maybe_handle_redirect()
	{
		if (!$this->table_exists || is_admin() || wp_doing_ajax()) {
			return;
		}

		global $wpdb;

		$current_url = untrailingslashit(
			esc_url_raw(home_url(add_query_arg(null, null)))
		);

		$redirect = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT to_url, type FROM {$this->redirect_table} WHERE from_url = %s LIMIT 1",
				$current_url
			)
		);

		if ($redirect) {
			wp_redirect($redirect->to_url, (int) $redirect->type);
			exit;
		}
	}

	/**
	 * TODO:
	 * - Track 404 hits for redirect suggestions
	 * - Add bulk import/export (CSV)
	 * - Add regex / wildcard support
	 * - Add redirect loop detection
	 * - Add multisite compatibility handling
	 */
}
