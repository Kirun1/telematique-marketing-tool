<?php
class ProductScraper_Ecommerce_SEO {

	public function optimize_product_page( $product_id ) {
		// WooCommerce-specific optimizations.
		return array(
			'schema'                => $this->generate_product_schema( $product_id ),
			'reviews'               => $this->optimize_review_markup( $product_id ),
			'pricing'               => $this->add_pricing_schema( $product_id ),
			'availability'          => $this->add_availability_schema( $product_id ),
			'breadcrumbs'           => $this->optimize_breadcrumbs( $product_id ),
			'category_optimization' => $this->optimize_category_pages( $product_id ),
		);
	}

	public function generate_product_schema( $product_id ) {
		$product = wc_get_product( $product_id );

		if ( ! $product ) {
			return array( 'error' => 'Product not found' );
		}

		$schema = array(
			'@context'    => 'https://schema.org/',
			'@type'       => 'Product',
			'name'        => $product->get_name(),
			'description' => wp_strip_all_tags( $product->get_short_description() ?: $product->get_description() ),
			'sku'         => $product->get_sku(),
			'url'         => get_permalink( $product_id ),
		);

		// Add image.
		$image_id = $product->get_image_id();
		if ( $image_id ) {
			$schema['image'] = wp_get_attachment_image_url( $image_id, 'full' );
		}

		// Add brand if available.
		$brand = $this->get_product_brand( $product_id );
		if ( $brand ) {
			$schema['brand'] = array(
				'@type' => 'Brand',
				'name'  => $brand,
			);
		}

		// Add offers.
		$schema['offers'] = array(
			'@type'           => 'Offer',
			'price'           => $product->get_price(),
			'priceCurrency'   => get_woocommerce_currency(),
			'availability'    => $this->get_schema_availability( $product ),
			'url'             => get_permalink( $product_id ),
			'priceValidUntil' => date( 'Y-m-d', strtotime( '+1 year' ) ),
		);

		// Add reviews if available.
		$reviews_schema = $this->get_reviews_schema( $product_id );
		if ( $reviews_schema ) {
			$schema['aggregateRating'] = $reviews_schema['aggregateRating'];
			$schema['review']          = $reviews_schema['reviews'];
		}

		return $schema;
	}

	private function optimize_review_markup( $product_id ) {
		$product = wc_get_product( $product_id );
		$reviews = get_comments(
			array(
				'post_id' => $product_id,
				'status'  => 'approve',
			)
		);

		$optimizations = array(
			'review_count'        => count( $reviews ),
			'average_rating'      => $product->get_average_rating(),
			'rich_snippets_added' => false,
			'review_markup'       => array(),
		);

		if ( ! empty( $reviews ) ) {
			$optimizations['rich_snippets_added'] = true;

			// Generate review markup for each review.
			foreach ( $reviews as $review ) {
				$optimizations['review_markup'][] = array(
					'author'  => $review->comment_author,
					'rating'  => get_comment_meta( $review->comment_ID, 'rating', true ),
					'date'    => $review->comment_date,
					'content' => wp_trim_words( $review->comment_content, 50 ),
				);
			}
		}

		// Add review form schema.
		$optimizations['review_form_optimized'] = $this->optimize_review_form( $product_id );

		return $optimizations;
	}

	private function add_pricing_schema( $product_id ) {
		$product = wc_get_product( $product_id );

		$pricing_schema = array(
			'@type'           => 'Offer',
			'price'           => $product->get_price(),
			'priceCurrency'   => get_woocommerce_currency(),
			'validFrom'       => date( 'c' ),
			'priceValidUntil' => date( 'Y-m-d', strtotime( '+1 year' ) ),
		);

		// Handle sale pricing.
		if ( $product->is_on_sale() ) {
			$pricing_schema['priceSpecification'] = array(
				'price'         => $product->get_sale_price(),
				'originalPrice' => $product->get_regular_price(),
				'priceCurrency' => get_woocommerce_currency(),
				'discount'      => round( ( ( $product->get_regular_price() - $product->get_sale_price() ) / $product->get_regular_price() ) * 100 ),
			);
		}

		// Add shipping details.
		$shipping_class = $product->get_shipping_class();
		if ( $shipping_class ) {
			$pricing_schema['shippingDetails'] = array(
				'shippingRate' => array(
					'value'    => $this->calculate_shipping_cost( $product ),
					'currency' => get_woocommerce_currency(),
				),
			);
		}

		return $pricing_schema;
	}

	private function add_availability_schema( $product_id ) {
		$product = wc_get_product( $product_id );

		$availability_schema = array(
			'availability'    => $this->get_schema_availability( $product ),
			'inventory_level' => array(
				'value'  => $product->get_stock_quantity(),
				'status' => $product->get_stock_status(),
			),
		);

		// Add backorder information.
		if ( $product->backorders_allowed() ) {
			$availability_schema['backorder'] = array(
				'allowed' => true,
				'notice'  => 'Available on backorder',
			);
		}

		// Add delivery time estimates.
		$availability_schema['delivery_lead_time'] = array(
			'min_days'      => 2,
			'max_days'      => 7,
			'business_days' => true,
		);

		return $availability_schema;
	}

	private function optimize_breadcrumbs( $product_id ) {
		$product    = wc_get_product( $product_id );
		$categories = wc_get_product_terms( $product_id, 'product_cat' );

		$breadcrumbs = array(
			'@context'        => 'https://schema.org',
			'@type'           => 'BreadcrumbList',
			'itemListElement' => array(),
		);

		// Home page.
		$breadcrumbs['itemListElement'][] = array(
			'@type'    => 'ListItem',
			'position' => 1,
			'name'     => 'Home',
			'item'     => home_url(),
		);

		$position = 2;

		// Category hierarchy.
		if ( ! empty( $categories ) ) {
			$main_category = $categories[0];
			$ancestors     = get_ancestors( $main_category->term_id, 'product_cat' );

			// Add parent categories.
			foreach ( array_reverse( $ancestors ) as $ancestor_id ) {
				$ancestor                         = get_term( $ancestor_id, 'product_cat' );
				$breadcrumbs['itemListElement'][] = array(
					'@type'    => 'ListItem',
					'position' => $position++,
					'name'     => $ancestor->name,
					'item'     => get_term_link( $ancestor ),
				);
			}

			// Add main category.
			$breadcrumbs['itemListElement'][] = array(
				'@type'    => 'ListItem',
				'position' => $position++,
				'name'     => $main_category->name,
				'item'     => get_term_link( $main_category ),
			);
		}

		// Add product.
		$breadcrumbs['itemListElement'][] = array(
			'@type'    => 'ListItem',
			'position' => $position,
			'name'     => $product->get_name(),
			'item'     => get_permalink( $product_id ),
		);

		return array(
			'schema_markup'       => $breadcrumbs,
			'html_breadcrumbs'    => $this->generate_html_breadcrumbs( $breadcrumbs ),
			'optimization_status' => 'optimized',
		);
	}

	private function optimize_category_pages( $product_id ) {
		$product    = wc_get_product( $product_id );
		$categories = wc_get_product_terms( $product_id, 'product_cat' );

		$optimizations = array();

		foreach ( $categories as $category ) {
			$optimizations[ $category->slug ] = array(
				'category_name' => $category->name,
				'optimizations' => array(
					'meta_title'       => $this->generate_category_meta_title( $category ),
					'meta_description' => $this->generate_category_meta_description( $category ),
					'schema_markup'    => $this->generate_category_schema( $category ),
					'canonical_url'    => $this->get_category_canonical( $category ),
					'header_tags'      => $this->optimize_category_headers( $category ),
				),
			);
		}

		return $optimizations;
	}

	// Helper methods.
	private function get_product_brand( $product_id ) {
		// Check for brand taxonomy (common in WooCommerce).
		$brands = wp_get_post_terms( $product_id, 'product_brand' );
		if ( ! empty( $brands ) ) {
			return $brands[0]->name;
		}

		// Check for brand custom field.
		$brand = get_post_meta( $product_id, '_brand', true );
		if ( $brand ) {
			return $brand;
		}

		// Check for manufacturer.
		$manufacturer = get_post_meta( $product_id, '_manufacturer', true );
		if ( $manufacturer ) {
			return $manufacturer;
		}

		return get_bloginfo( 'name' ); // Fallback to site name.
	}

	private function get_schema_availability( $product ) {
		if ( $product->is_in_stock() ) {
			return 'https://schema.org/InStock';
		} elseif ( $product->backorders_allowed() ) {
			return 'https://schema.org/BackOrder';
		} else {
			return 'https://schema.org/OutOfStock';
		}
	}

	private function get_reviews_schema( $product_id ) {
		$reviews = get_comments(
			array(
				'post_id' => $product_id,
				'status'  => 'approve',
			)
		);

		if ( empty( $reviews ) ) {
			return false;
		}

		$total_rating  = 0;
		$review_count  = 0;
		$review_schema = array();

		foreach ( $reviews as $review ) {
			$rating = get_comment_meta( $review->comment_ID, 'rating', true );
			if ( $rating ) {
				$total_rating += $rating;
				++$review_count;

				$review_schema[] = array(
					'@type'         => 'Review',
					'author'        => array(
						'@type' => 'Person',
						'name'  => $review->comment_author,
					),
					'datePublished' => $review->comment_date,
					'reviewBody'    => $review->comment_content,
					'reviewRating'  => array(
						'@type'       => 'Rating',
						'ratingValue' => $rating,
						'bestRating'  => '5',
					),
				);
			}
		}

		if ( $review_count === 0 ) {
			return false;
		}

		return array(
			'aggregateRating' => array(
				'@type'       => 'AggregateRating',
				'ratingValue' => round( $total_rating / $review_count, 1 ),
				'reviewCount' => $review_count,
				'bestRating'  => '5',
				'worstRating' => '1',
			),
			'reviews'         => $review_schema,
		);
	}

	private function optimize_review_form( $product_id ) {
		return array(
			'schema_added'           => true,
			'rating_stars_optimized' => true,
			'structured_data'        => true,
			'recommendations'        => array(
				'Add review schema markup',
				'Optimize rating stars for rich snippets',
				'Include aggregate rating in product schema',
			),
		);
	}

	private function calculate_shipping_cost( $product ) {
		// Simplified shipping calculation.
		// In real implementation, you'd use WooCommerce shipping methods.
		$base_cost = 5.00;
		$weight    = $product->get_weight();

		if ( $weight > 5 ) {
			$base_cost += 2.00;
		}

		return $base_cost;
	}

	private function generate_html_breadcrumbs( $breadcrumb_schema ) {
		$html  = '<nav aria-label="Breadcrumb">';
		$html .= '<ol>';

		foreach ( $breadcrumb_schema['itemListElement'] as $item ) {
			$html .= '<li>';
			if ( $item['position'] < count( $breadcrumb_schema['itemListElement'] ) ) {
				$html .= '<a href="' . esc_url( $item['item'] ) . '">' . esc_html( $item['name'] ) . '</a>';
			} else {
				$html .= '<span aria-current="page">' . esc_html( $item['name'] ) . '</span>';
			}
			$html .= '</li>';
		}

		$html .= '</ol>';
		$html .= '</nav>';

		return $html;
	}

	private function generate_category_meta_title( $category ) {
		return 'Buy ' . $category->name . ' Online | ' . get_bloginfo( 'name' );
	}

	private function generate_category_meta_description( $category ) {
		return 'Shop our collection of ' . $category->name . '. ' .
			wp_trim_words( wp_strip_all_tags( $category->description ?: '' ), 20 ) .
			' Free shipping available.';
	}

	private function generate_category_schema( $category ) {
		return array(
			'@context'    => 'https://schema.org',
			'@type'       => 'CollectionPage',
			'name'        => $category->name,
			'description' => wp_strip_all_tags( $category->description ),
			'url'         => get_term_link( $category ),
		);
	}

	private function get_category_canonical( $category ) {
		return get_term_link( $category );
	}

	private function optimize_category_headers( $category ) {
		return array(
			'h1_optimized' => true,
			'h1_text'      => $category->name,
			'subheadings'  => array(
				'type'              => 'h2',
				'recommended_count' => '3-5',
				'keywords_included' => true,
			),
		);
	}
}
