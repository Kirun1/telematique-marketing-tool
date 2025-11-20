<?php

class WooCommerceProductImporter {

	/**
	 * Import scraped products into WooCommerce
	 */
	public function import_products( $products ) {
		$results = array(
			'success' => 0,
			'errors'  => 0,
			'skipped' => 0,
		);

		foreach ( $products as $product_data ) {
			$result = $this->import_single_product( $product_data );

			if ( $result === true ) {
				++$results['success'];
			} elseif ( $result === 'skipped' ) {
				++$results['skipped'];
			} else {
				++$results['errors'];
			}
		}

		return $results;
	}

	/**
	 * Import single product
	 */
	private function import_single_product( $product_data ) {
		// Check if product already exists
		if ( $this->product_exists( $product_data['name'] ) ) {
			return 'skipped';
		}

		// Create new product
		$product = new WC_Product();

		// Set basic product data
		$product->set_name( $product_data['name'] );
		$product->set_status( 'publish' );
		$product->set_catalog_visibility( 'visible' );
		$product->set_description( $product_data['full_description'] ?? $product_data['description'] ?? '' );
		$product->set_short_description( $product_data['description'] ?? '' );

		// Set price
		if ( isset( $product_data['price'] ) ) {
			$price = $this->parse_price( $product_data['price'] );
			if ( $price ) {
				$product->set_regular_price( $price );
				$product->set_price( $price );
			}
		}

		// Set SKU
		if ( isset( $product_data['sku'] ) ) {
			$product->set_sku( $product_data['sku'] );
		}

		// Download and set featured image
		if ( isset( $product_data['image'] ) ) {
			$image_id = $this->download_image( $product_data['image'], $product_data['name'] );
			if ( $image_id ) {
				$product->set_image_id( $image_id );
			}
		}

		// Download gallery images
		if ( ! empty( $product_data['gallery_images'] ) ) {
			$gallery_ids = array();
			foreach ( $product_data['gallery_images'] as $image_url ) {
				$image_id = $this->download_image( $image_url, $product_data['name'] . ' gallery' );
				if ( $image_id ) {
					$gallery_ids[] = $image_id;
				}
			}
			$product->set_gallery_image_ids( $gallery_ids );
		}

		// Set categories
		if ( ! empty( $product_data['categories'] ) ) {
			$category_ids = $this->get_or_create_categories( $product_data['categories'] );
			$product->set_category_ids( $category_ids );
		}

		try {
			$product_id = $product->save();
			return $product_id > 0 ? true : false;
		} catch ( Exception $e ) {
			error_log( 'Product import error: ' . $e->getMessage() );
			return false;
		}
	}

	/**
	 * Check if product already exists
	 */
	private function product_exists( $product_name ) {
		$existing = get_posts(
			array(
				'post_type'   => 'product',
				'title'       => $product_name,
				'post_status' => 'any',
				'numberposts' => 1,
			)
		);

		return ! empty( $existing );
	}

	/**
	 * Parse price from string
	 */
	private function parse_price( $price_string ) {
		// Remove currency symbols and non-numeric characters except decimal point
		$price = preg_replace( '/[^\d.,]/', '', $price_string );
		$price = str_replace( ',', '.', $price );

		// Handle prices like "1.000,00" vs "1,000.00"
		if ( preg_match( '/^\d+\.\d{3},\d{2}$/', $price ) ) {
			$price = str_replace( '.', '', $price );
			$price = str_replace( ',', '.', $price );
		}

		return floatval( $price );
	}

	/**
	 * Download image from URL and attach to media library
	 */
	private function download_image( $image_url, $image_name ) {
		require_once ABSPATH . 'wp-admin/includes/image.php';
		require_once ABSPATH . 'wp-admin/includes/file.php';
		require_once ABSPATH . 'wp-admin/includes/media.php';

		$tmp = download_url( $image_url );

		if ( is_wp_error( $tmp ) ) {
			return false;
		}

		$file_array = array(
			'name'     => sanitize_file_name( $image_name ) . '.jpg',
			'tmp_name' => $tmp,
		);

		$id = media_handle_sideload( $file_array, 0 );

		if ( is_wp_error( $id ) ) {
			@unlink( $file_array['tmp_name'] );
			return false;
		}

		return $id;
	}

	/**
	 * Get or create product categories
	 */
	private function get_or_create_categories( $category_names ) {
		$category_ids = array();

		foreach ( $category_names as $category_name ) {
			$term = term_exists( $category_name, 'product_cat' );

			if ( ! $term ) {
				$term = wp_insert_term( $category_name, 'product_cat' );
			}

			if ( ! is_wp_error( $term ) && isset( $term['term_id'] ) ) {
				$category_ids[] = $term['term_id'];
			}
		}

		return $category_ids;
	}
}
