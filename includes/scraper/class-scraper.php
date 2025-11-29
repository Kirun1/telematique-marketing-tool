<?php

class ProductScraperEngine {

	private $base_url;
	private $user_agent;

	public function __construct() {
		$this->user_agent = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36 (KHTML, like Gecko) Chrome/91.0.4472.124 Safari/537.36';
	}

	/**
	 * Set the base URL for scraping
	 */
	public function set_base_url( $url ) {
		$this->base_url = trailingslashit( $url );
	}

	/**
	 * Scrape products from multiple pages
	 */
	public function scrape_products( $start_page = 1, $max_pages = 10 ) {
		$all_products = array();
		$current_page = $start_page;

		while ( $current_page <= $max_pages ) {
			$page_url = $this->get_page_url( $current_page );
			$html     = $this->fetch_page( $page_url );

			if ( ! $html ) {
				error_log( 'Failed to fetch page: ' . $page_url );
				break;
			}

			$products = $this->parse_products( $html );

			if ( empty( $products ) ) {
				break; // No more products found.
			}

			$all_products = array_merge( $all_products, $products );
			++$current_page;

			// Add delay to be respectful to the server.
			sleep( 2 );
		}

		return $all_products;
	}

	// /**
	// * Generate URL for specific page
	// */
	// private function get_page_url($page_number) {
	// return $this->base_url . 'page/' . $page_number . '/';
	// }

	/**
	 * Fetch page content using WordPress HTTP API
	 */
	public function fetch_page( $url ) {
		$response = wp_remote_get(
			$url,
			array(
				'timeout'    => 30,
				'user-agent' => $this->user_agent,
				'headers'    => array(
					'Accept'          => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
					'Accept-Language' => 'en-US,en;q=0.5',
				),
			)
		);

		if ( is_wp_error( $response ) ) {
			error_log( 'HTTP Error: ' . $response->get_error_message() );
			return false;
		}

		$response_code = wp_remote_retrieve_response_code( $response );
		if ( $response_code !== 200 ) {
			error_log( 'HTTP Response Code: ' . $response_code );
			return false;
		}

		return wp_remote_retrieve_body( $response );
	}

	/**
	 * Parse products from HTML content
	 */
	private function parse_products( $html ) {
		$products = array();
		$dom      = new DOMDocument();

		// Suppress HTML5 errors.
		libxml_use_internal_errors( true );
		$dom->loadHTML( $html );
		libxml_clear_errors();

		$xpath = new DOMXPath( $dom );

		// Target the specific product list structure.
		$product_nodes = $xpath->query( '//ol[contains(@class, "grid")]//li[contains(@class, "product-item")]' );

		if ( $product_nodes->length === 0 ) {
			return $products;
		}

		foreach ( $product_nodes as $product_node ) {
			// Skip promotional items that aren't actual products.
			$is_promotion = $xpath->query( './/div[contains(@class, "type-promotions")]', $product_node )->length > 0;
			if ( $is_promotion ) {
				continue;
			}

			$product_data = $this->parse_single_product( $product_node, $xpath );
			if ( $product_data ) {
				$products[] = $product_data;
			}
		}

		return $products;
	}

	/**
	 * Parse individual product data
	 */
	private function parse_single_product( $node, $xpath ) {
		$product = array();

		// Product name - targeting the specific structure.
		$name_nodes = $xpath->query( './/p[contains(@class, "product-item-link")]', $node );
		if ( $name_nodes->length > 0 ) {
			$product['name'] = trim( $name_nodes->item( 0 )->textContent );
		}

		// Product URL.
		$link_nodes = $xpath->query( './/a[@href]', $node );
		if ( $link_nodes->length > 0 ) {
			$href = $link_nodes->item( 0 )->getAttribute( 'href' );
			// Only include product links, not promotional links.
			if ( strpos( $href, '/de/' ) !== false && strpos( $href, 'javascript:' ) === false ) {
				$product['url'] = $href;
			}
		}

		// Price - specific to shop/wocommerce structure.
		$price_nodes = $xpath->query( './/span[@class="price"]', $node );
		if ( $price_nodes->length > 0 ) {
			$product['price'] = trim( $price_nodes->item( 0 )->textContent );
			// Also get price amount from data attribute if available.
			$price_wrapper = $xpath->query( './/span[@data-price-amount]', $node );
			if ( $price_wrapper->length > 0 ) {
				$product['price_amount'] = $price_wrapper->item( 0 )->getAttribute( 'data-price-amount' );
			}
		}

		// Image.
		$img_nodes = $xpath->query( './/img', $node );
		if ( $img_nodes->length > 0 ) {
			$product['image'] = $img_nodes->item( 0 )->getAttribute( 'src' );
		}

		// Rating stars count.
		$filled_stars            = $xpath->query( './/svg[contains(@class, "fill-current") and contains(@style, "#FFC000")]', $node )->length;
		$partial_stars           = $xpath->query( './/svg[.//linearGradient]', $node )->length;
		$product['rating_stars'] = $filled_stars + ( $partial_stars * 0.5 );

		// Review count.
		$review_nodes = $xpath->query( './/span[@itemprop="reviewCount"]', $node );
		if ( $review_nodes->length > 0 ) {
			$product['review_count'] = intval( trim( $review_nodes->item( 0 )->textContent ) );
		}

		// Product badges (Bestseller, Aktion, etc.).
		$badge_nodes = $xpath->query( './/div[contains(@class, "product-sticker")]', $node );
		$badges      = array();
		foreach ( $badge_nodes as $badge_node ) {
			$badges[] = trim( $badge_node->textContent );
		}
		if ( ! empty( $badges ) ) {
			$product['badges'] = $badges;
		}

		// Product ID from data attribute.
		$product_id = $node->getAttribute( 'data-bx-item-id' );
		if ( $product_id && is_numeric( $product_id ) ) {
			$product['product_id'] = $product_id;
		}

		return ! empty( $product['name'] ) ? $product : false;
	}

	/**
	 * Scrape detailed product information from product page
	 */
	public function scrape_product_details( $product_url ) {
		$html = $this->fetch_page( $product_url );

		if ( ! $html ) {
			return false;
		}

		$dom = new DOMDocument();
		libxml_use_internal_errors( true );
		$dom->loadHTML( $html );
		libxml_clear_errors();

		$xpath   = new DOMXPath( $dom );
		$details = array();

		// Full description - look for product description sections.
		$desc_nodes = $xpath->query( '//div[contains(@class, "product-description")] | //div[contains(@class, "description")] | //div[@itemprop="description"]' );
		if ( $desc_nodes->length > 0 ) {
			$details['full_description'] = trim( $desc_nodes->item( 0 )->textContent );
		}

		// SKU.
		$sku_nodes = $xpath->query( '//span[contains(@class, "sku")] | //div[contains(text(), "SKU")]' );
		if ( $sku_nodes->length > 0 ) {
			$details['sku'] = trim( $sku_nodes->item( 0 )->textContent );
		}

		// Categories.
		$cat_nodes  = $xpath->query( '//a[contains(@href, "/category/")] | //span[contains(@class, "category")]//a' );
		$categories = array();
		foreach ( $cat_nodes as $cat_node ) {
			$category = trim( $cat_node->textContent );
			if ( ! empty( $category ) && ! in_array( $category, $categories ) ) {
				$categories[] = $category;
			}
		}
		$details['categories'] = $categories;

		// Additional images from gallery.
		$gallery_nodes  = $xpath->query( '//img[contains(@class, "gallery")] | //div[contains(@class, "gallery")]//img' );
		$gallery_images = array();
		foreach ( $gallery_nodes as $img_node ) {
			$src = $img_node->getAttribute( 'src' );
			if ( $src && $src !== $details['image'] ) {
				$gallery_images[] = $src;
			}
		}
		$details['gallery_images'] = $gallery_images;

		// Product specifications/attributes.
		$spec_nodes     = $xpath->query( '//table[contains(@class, "specifications")]//tr | //div[contains(@class, "attribute")]' );
		$specifications = array();
		foreach ( $spec_nodes as $spec_node ) {
			$label = $xpath->query( './/td[1] | .//strong', $spec_node );
			$value = $xpath->query( './/td[2] | .//span', $spec_node );

			if ( $label->length > 0 && $value->length > 0 ) {
				$spec_label = trim( $label->item( 0 )->textContent );
				$spec_value = trim( $value->item( 0 )->textContent );
				if ( ! empty( $spec_label ) ) {
					$specifications[ $spec_label ] = $spec_value;
				}
			}
		}
		$details['specifications'] = $specifications;

		return $details;
	}

	/**
	 * Enhanced method to get page URLs of the scraped site pagination
	 */
	private function get_page_url( $page_number ) {
		if ( $page_number === 1 ) {
			return $this->base_url;
		}
		return $this->base_url . 'page/' . $page_number . '/';
	}

	/**
	 * Check if there are more pages to scrape
	 */
	private function has_next_page( $html ) {
		$dom = new DOMDocument();
		libxml_use_internal_errors( true );
		$dom->loadHTML( $html );
		libxml_clear_errors();

		$xpath = new DOMXPath( $dom );

		// Look for next page link or pagination.
		$next_links = $xpath->query( '//a[contains(@class, "next")] | //a[contains(text(), "Next")]' );

		return $next_links->length > 0;
	}
}
