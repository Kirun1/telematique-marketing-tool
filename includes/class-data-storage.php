<?php

class ProductScraperDataStorage {

	private $table_name;

	public function __construct() {
		global $wpdb;
		$this->table_name = $wpdb->prefix . 'scraped_products';

		// Only create the table once, on plugin activation.
		register_activation_hook( __FILE__, array( $this, 'create_table' ) );
	}

	/**
	 * Create custom table for storing scraped products
	 */
	public function create_table() {
		global $wpdb;

		// Check if table already exists.
		$table_exists = $wpdb->get_var(
			$wpdb->prepare(
				'SHOW TABLES LIKE %s',
				$this->table_name
			)
		);

		if ( $table_exists === $this->table_name ) {
			// Table already exists — don’t recreate it.
			return;
		}

		$charset_collate = $wpdb->get_charset_collate();

		// Drop the table if it exists to ensure clean creation.
		$wpdb->query( "DROP TABLE IF EXISTS {$this->table_name}" );

		$sql = "CREATE TABLE {$this->table_name} (
        id mediumint(9) NOT NULL AUTO_INCREMENT,
        source_url varchar(255) NOT NULL,
        product_name text NOT NULL,
        product_url varchar(255),
        price decimal(10,2),
        price_display varchar(50),
        image_url text,
        rating_stars float,
        review_count int,
        badges text,
        product_data longtext,
        scraped_at datetime DEFAULT CURRENT_TIMESTAMP,
        imported tinyint(1) DEFAULT 0,
        imported_at datetime,
        PRIMARY KEY (id),
        KEY source_url (source_url(100)),
        KEY imported (imported),
        KEY product_url (product_url(100))
    ) $charset_collate;";

		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$result = dbDelta( $sql );

		// Log the result for debugging.

		// Verify table was created correctly.
		$table_exists = $wpdb->get_var( "SHOW TABLES LIKE '{$this->table_name}'" ) === $this->table_name;

		if ( $table_exists ) {
			// Check column structure.
			$columns = $wpdb->get_results( "DESCRIBE {$this->table_name}" );
		}
	}

	/**
	 * Save scraped products to database
	 */
	public function save_products( $products, $source_url ) {
		global $wpdb;

		$saved_count = 0;

		foreach ( $products as $product ) {
			// Check if product already exists.
			$existing = $wpdb->get_var(
				$wpdb->prepare(
					"SELECT id FROM {$this->table_name} WHERE product_url = %s",
					$product['url'] ?? ''
				)
			);

			if ( $existing ) {
				// Update existing product.
				$result = $wpdb->update(
					$this->table_name,
					array(
						'product_name'  => $product['name'] ?? '',
						'price'         => $product['price_amount'] ?? 0,
						'price_display' => $product['price'] ?? '',
						'image_url'     => $product['image'] ?? '',
						'rating_stars'  => $product['rating_stars'] ?? 0,
						'review_count'  => $product['review_count'] ?? 0,
						'badges'        => json_encode( $product['badges'] ?? array() ),
						'product_data'  => json_encode( $product ),
						'scraped_at'    => current_time( 'mysql' ),
					),
					array( 'id' => $existing )
				);
			} else {
				// Insert new product.
				$result = $wpdb->insert(
					$this->table_name,
					array(
						'source_url'    => $source_url,
						'product_name'  => $product['name'] ?? '',
						'product_url'   => $product['url'] ?? '',
						'price'         => $product['price_amount'] ?? 0,
						'price_display' => $product['price'] ?? '',
						'image_url'     => $product['image'] ?? '',
						'rating_stars'  => $product['rating_stars'] ?? 0,
						'review_count'  => $product['review_count'] ?? 0,
						'badges'        => json_encode( $product['badges'] ?? array() ),
						'product_data'  => json_encode( $product ),
						'scraped_at'    => current_time( 'mysql' ),
					)
				);
			}

			if ( $result !== false ) {
				++$saved_count;
			}
		}

		return $saved_count;
	}

	/**
	 * Get stored products
	 */
	public function get_products( $source_url = '', $imported = null ) {
		global $wpdb;

		$where  = array();
		$params = array();

		if ( ! empty( $source_url ) ) {
			$where[]  = 'source_url = %s';
			$params[] = $source_url;
		}

		if ( $imported !== null ) {
			$where[]  = 'imported = %d';
			$params[] = $imported;
		}

		$where_sql = '';
		if ( ! empty( $where ) ) {
			$where_sql = 'WHERE ' . implode( ' AND ', $where );
		}

		if ( ! empty( $params ) ) {
			$sql = $wpdb->prepare(
				"SELECT * FROM {$this->table_name} {$where_sql} ORDER BY scraped_at DESC",
				$params
			);
		} else {
			$sql = "SELECT * FROM {$this->table_name} {$where_sql} ORDER BY scraped_at DESC";
		}

		$results = $wpdb->get_results( $sql, ARRAY_A );

		// Decode JSON data.
		foreach ( $results as &$result ) {
			$result['badges']       = json_decode( $result['badges'], true );
			$result['product_data'] = json_decode( $result['product_data'], true );
		}

		return $results;
	}

	/**
	 * Mark products as imported
	 */
	public function mark_imported( $product_ids ) {
		global $wpdb;

		if ( ! is_array( $product_ids ) ) {
			$product_ids = array( $product_ids );
		}

		$placeholders = implode( ',', array_fill( 0, count( $product_ids ), '%d' ) );

		$result = $wpdb->query(
			$wpdb->prepare(
				"UPDATE {$this->table_name} SET imported = 1, imported_at = %s WHERE id IN ($placeholders)",
				array_merge( array( current_time( 'mysql' ) ), $product_ids )
			)
		);

		return $result;
	}

	/**
	 * Delete products
	 */
	public function delete_products( $product_ids ) {
		global $wpdb;

		if ( ! is_array( $product_ids ) ) {
			$product_ids = array( $product_ids );
		}

		$placeholders = implode( ',', array_fill( 0, count( $product_ids ), '%d' ) );

		$result = $wpdb->query(
			$wpdb->prepare(
				"DELETE FROM {$this->table_name} WHERE id IN ($placeholders)",
				$product_ids
			)
		);

		return $result;
	}

	/**
	 * Get statistics
	 */
	/**
	 * Get statistics
	 */
	public function get_stats() {
		global $wpdb;

		$stats = $wpdb->get_row(
			"
        SELECT 
            COUNT(*) as total_products,
            COUNT(DISTINCT source_url) as total_sources,
            SUM(imported) as imported_products,
            MAX(scraped_at) as last_scraped
        FROM {$this->table_name}
    ",
			ARRAY_A
		);

		// Return default values if no stats found (empty table).
		if ( ! $stats ) {
			$stats = array();
		}

		// Ensure all values are set and valid.
		return array(
			'total_products'    => $stats['total_products'] ?? 0,
			'total_sources'     => $stats['total_sources'] ?? 0,
			'imported_products' => $stats['imported_products'] ?? 0,
			'last_scraped'      => ( isset( $stats['last_scraped'] ) && $stats['last_scraped'] !== '0000-00-00 00:00:00' ) ? $stats['last_scraped'] : null,
		);
	}
}
