<?php
class ProductScraper_Local_SEO {

	public function add_local_business_markup() {
		return array(
			'@type'        => 'LocalBusiness',
			'name'         => get_option( 'local_business_name' ),
			'address'      => array(
				'@type'           => 'PostalAddress',
				'streetAddress'   => get_option( 'local_street_address' ),
				'addressLocality' => get_option( 'local_city' ),
				'postalCode'      => get_option( 'local_zip' ),
				'addressCountry'  => get_option( 'local_country' ),
			),
			'geo'          => array(
				'@type'     => 'GeoCoordinates',
				'latitude'  => get_option( 'local_latitude' ),
				'longitude' => get_option( 'local_longitude' ),
			),
			'openingHours' => $this->get_opening_hours(),
			'priceRange'   => get_option( 'local_price_range' ),
			'telephone'    => get_option( 'local_phone' ),
		);
	}

	/**
	 * Get formatted opening hours for schema markup
	 */
	public function get_opening_hours() {
		$opening_hours = array();
		$days          = $this->get_week_days();

		foreach ( $days as $day ) {
			$day_hours = $this->get_day_hours( $day );
			if ( $day_hours ) {
				$opening_hours[] = $day_hours;
			}
		}

		return $opening_hours;
	}

	/**
	 * Get opening hours for a specific day
	 */
	private function get_day_hours( $day ) {
		$open_time  = get_option( "local_hours_{$day}_open", '' );
		$close_time = get_option( "local_hours_{$day}_close", '' );

		// Skip if no hours set or closed.
		if ( empty( $open_time ) || empty( $close_time ) || $open_time === 'closed' ) {
			return '';
		}

		// Format: Mo 09:00-17:00.
		$day_code = $this->get_day_code( $day );
		return "{$day_code} {$open_time}-{$close_time}";
	}

	/**
	 * Get abbreviated day codes for schema
	 */
	private function get_day_code( $day ) {
		$day_codes = array(
			'monday'    => 'Mo',
			'tuesday'   => 'Tu',
			'wednesday' => 'We',
			'thursday'  => 'Th',
			'friday'    => 'Fr',
			'saturday'  => 'Sa',
			'sunday'    => 'Su',
		);

		return $day_codes[ strtolower( $day ) ] ?? substr( $day, 0, 2 );
	}

	/**
	 * Get list of week days
	 */
	private function get_week_days() {
		return array(
			'monday',
			'tuesday',
			'wednesday',
			'thursday',
			'friday',
			'saturday',
			'sunday',
		);
	}

	/**
	 * Enhanced opening hours with special days support
	 */
	public function get_opening_hours_enhanced() {
		$opening_hours = $this->get_opening_hours();

		// Add 24/7 hours if applicable.
		if ( $this->is_24_7() ) {
			$opening_hours = array( 'Mo-Su 00:00-23:59' );
		}

		// Handle special holiday hours.
		$special_hours = $this->get_special_hours();
		if ( ! empty( $special_hours ) ) {
			$opening_hours = array_merge( $opening_hours, $special_hours );
		}

		return array_filter( $opening_hours );
	}

	/**
	 * Check if business operates 24/7
	 */
	private function is_24_7() {
		return get_option( 'local_hours_24_7' ) === 'yes' ||
			get_option( 'local_business_hours_type' ) === '24_7';
	}

	/**
	 * Get special hours for holidays and exceptions
	 */
	private function get_special_hours() {
		$special_hours = array();
		$special_days  = get_option( 'local_special_hours', array() );

		if ( is_array( $special_days ) ) {
			foreach ( $special_days as $special_day ) {
				if ( ! empty( $special_day['date'] ) && ! empty( $special_day['hours'] ) ) {
					$formatted_date  = date( 'Y-m-d', strtotime( $special_day['date'] ) );
					$special_hours[] = "{$formatted_date} {$special_day['hours']}";
				}
			}
		}

		return $special_hours;
	}

	/**
	 * Get opening hours specification for more detailed schema
	 */
	public function get_opening_hours_specification() {
		$specifications = array();
		$days           = $this->get_week_days();

		foreach ( $days as $day ) {
			$open_time  = get_option( "local_hours_{$day}_open", '' );
			$close_time = get_option( "local_hours_{$day}_close", '' );

			if ( ! empty( $open_time ) && ! empty( $close_time ) && $open_time !== 'closed' ) {
				$specifications[] = array(
					'@type'     => 'OpeningHoursSpecification',
					'dayOfWeek' => ucfirst( $day ),
					'opens'     => $this->format_time_for_schema( $open_time ),
					'closes'    => $this->format_time_for_schema( $close_time ),
				);
			}
		}

		return $specifications;
	}

	/**
	 * Format time for schema (ensure proper format)
	 */
	private function format_time_for_schema( $time ) {
		// Handle various time formats.
		if ( preg_match( '/^\d{1,2}:\d{2}\s*(AM|PM)?$/i', $time ) ) {
			// Convert to 24-hour format if needed.
			return date( 'H:i', strtotime( $time ) );
		}

		// Assume it's already in proper format.
		return $time;
	}

	/**
	 * Get current business status (open/closed)
	 */
	public function get_business_status() {
		$current_time = current_time( 'H:i' );
		$current_day  = strtolower( current_time( 'l' ) );

		$open_time  = get_option( "local_hours_{$current_day}_open", '' );
		$close_time = get_option( "local_hours_{$current_day}_close", '' );

		if ( empty( $open_time ) || $open_time === 'closed' ) {
			return 'closed';
		}

		$open_time_24h  = date( 'H:i', strtotime( $open_time ) );
		$close_time_24h = date( 'H:i', strtotime( $close_time ) );

		if ( $current_time >= $open_time_24h && $current_time <= $close_time_24h ) {
			return 'open';
		}

		return 'closed';
	}

	/**
	 * Get next opening time
	 */
	public function get_next_opening_time() {
		$current_time = current_time( 'H:i' );
		$current_day  = strtolower( current_time( 'l' ) );
		$days         = $this->get_week_days();

		// Start from current day and check next 7 days.
		$current_index = array_search( $current_day, $days );

		for ( $i = 0; $i < 7; $i++ ) {
			$check_index = ( $current_index + $i ) % 7;
			$check_day   = $days[ $check_index ];

			$open_time  = get_option( "local_hours_{$check_day}_open", '' );
			$close_time = get_option( "local_hours_{$check_day}_close", '' );

			if ( ! empty( $open_time ) && $open_time !== 'closed' ) {
				$open_time_24h = date( 'H:i', strtotime( $open_time ) );

				// If it's today and we haven't passed opening time yet.
				if ( $i === 0 && $current_time < $open_time_24h ) {
					return array(
						'day'       => ucfirst( $check_day ),
						'time'      => $open_time,
						'timestamp' => strtotime( "today {$open_time}" ),
					);
				}
				// If it's a future day.
				elseif ( $i > 0 ) {
					return array(
						'day'       => ucfirst( $check_day ),
						'time'      => $open_time,
						'timestamp' => strtotime( "+{$i} days {$open_time}" ),
					);
				}
			}
		}

		return null;
	}

	/**
	 * Enhanced local business markup with all schema.org properties
	 */
	public function add_enhanced_local_business_markup() {
		$markup = array(
			'@context'                  => 'https://schema.org',
			'@type'                     => $this->get_business_type(),
			'name'                      => get_option( 'local_business_name' ) ?: get_bloginfo( 'name' ),
			'description'               => get_option( 'local_business_description' ) ?: get_bloginfo( 'description' ),
			'url'                       => home_url(),
			'telephone'                 => get_option( 'local_phone' ),
			'email'                     => get_option( 'local_email' ) ?: get_option( 'admin_email' ),
			'address'                   => $this->get_address_schema(),
			'geo'                       => $this->get_geo_schema(),
			'openingHours'              => $this->get_opening_hours_enhanced(),
			'openingHoursSpecification' => $this->get_opening_hours_specification(),
			'priceRange'                => get_option( 'local_price_range' ) ?: '$$',
			'image'                     => $this->get_business_images(),
			'logo'                      => $this->get_business_logo(),
			'sameAs'                    => $this->get_social_profiles(),
			'areaServed'                => $this->get_areas_served(),
			'hasOfferCatalog'           => $this->get_offer_catalog(),
			'paymentAccepted'           => $this->get_payment_methods(),
			'currenciesAccepted'        => $this->get_currencies_accepted(),
		);

		// Add business status if available.
		$business_status = $this->get_business_status();
		if ( $business_status ) {
			$markup['hoursAvailable'] = $this->get_opening_hours_specification();
		}

		return array_filter( $markup );
	}

	/**
	 * Get appropriate business type based on settings
	 */
	private function get_business_type() {
		$business_type = get_option( 'local_business_type', 'LocalBusiness' );

		// Map to specific schema.org types.
		$business_types = array(
			'restaurant'    => 'Restaurant',
			'store'         => 'Store',
			'medical'       => 'MedicalBusiness',
			'health'        => 'HealthAndBeautyBusiness',
			'automotive'    => 'AutomotiveBusiness',
			'food'          => 'FoodEstablishment',
			'professional'  => 'ProfessionalService',
			'home'          => 'HomeAndConstructionBusiness',
			'entertainment' => 'EntertainmentBusiness',
			'financial'     => 'FinancialService',
			'sports'        => 'SportsActivityLocation',
		);

		return $business_types[ $business_type ] ?? $business_type;
	}

	/**
	 * Get complete address schema
	 */
	private function get_address_schema() {
		return array(
			'@type'           => 'PostalAddress',
			'streetAddress'   => get_option( 'local_street_address' ) ?: '',
			'addressLocality' => get_option( 'local_city' ) ?: '',
			'addressRegion'   => get_option( 'local_region' ) ?: '',
			'postalCode'      => get_option( 'local_zip' ) ?: '',
			'addressCountry'  => get_option( 'local_country' ) ?: '',
		);
	}

	/**
	 * Get geo coordinates schema
	 */
	private function get_geo_schema() {
		$latitude  = get_option( 'local_latitude' );
		$longitude = get_option( 'local_longitude' );

		if ( $latitude && $longitude ) {
			return array(
				'@type'     => 'GeoCoordinates',
				'latitude'  => floatval( $latitude ),
				'longitude' => floatval( $longitude ),
			);
		}

		return null;
	}

	/**
	 * Get business images
	 */
	private function get_business_images() {
		$images         = array();
		$gallery_images = get_option( 'local_business_gallery', array() );

		if ( is_array( $gallery_images ) ) {
			foreach ( $gallery_images as $image_url ) {
				$images[] = $image_url;
			}
		}

		// Add featured image if available.
		$featured_image = get_option( 'local_business_featured_image' );
		if ( $featured_image ) {
			array_unshift( $images, $featured_image );
		}

		return ! empty( $images ) ? $images : null;
	}

	/**
	 * Get business logo
	 */
	private function get_business_logo() {
		$logo = get_option( 'local_business_logo' );
		if ( $logo ) {
			return array(
				'@type'  => 'ImageObject',
				'url'    => $logo,
				'width'  => 600,
				'height' => 60,
			);
		}

		// Fallback to site logo.
		$custom_logo_id = get_theme_mod( 'custom_logo' );
		if ( $custom_logo_id ) {
			return array(
				'@type'  => 'ImageObject',
				'url'    => wp_get_attachment_url( $custom_logo_id ),
				'width'  => 600,
				'height' => 60,
			);
		}

		return null;
	}

	/**
	 * Get social media profiles
	 */
	private function get_social_profiles() {
		$social_profiles  = array();
		$social_platforms = array(
			'facebook'  => 'local_facebook_url',
			'twitter'   => 'local_twitter_url',
			'instagram' => 'local_instagram_url',
			'linkedin'  => 'local_linkedin_url',
			'youtube'   => 'local_youtube_url',
		);

		foreach ( $social_platforms as $platform => $option_name ) {
			$url = get_option( $option_name );
			if ( $url ) {
				$social_profiles[] = $url;
			}
		}

		return ! empty( $social_profiles ) ? $social_profiles : null;
	}

	/**
	 * Get areas served by the business
	 */
	private function get_areas_served() {
		$areas_served = get_option( 'local_areas_served' );
		if ( $areas_served ) {
			return is_array( $areas_served ) ? $areas_served : explode( ',', $areas_served );
		}

		return null;
	}

	/**
	 * Get offer catalog
	 */
	private function get_offer_catalog() {
		$offers = get_option( 'local_business_offers', array() );
		if ( empty( $offers ) ) {
			return null;
		}

		$catalog = array(
			'@type'           => 'OfferCatalog',
			'name'            => 'Services',
			'itemListElement' => array(),
		);

		foreach ( $offers as $index => $offer ) {
			$catalog['itemListElement'][] = array(
				'@type'         => 'Offer',
				'position'      => $index + 1,
				'name'          => $offer['name'] ?? '',
				'description'   => $offer['description'] ?? '',
				'price'         => $offer['price'] ?? '',
				'priceCurrency' => $offer['currency'] ?? 'USD',
			);
		}

		return $catalog;
	}

	/**
	 * Get accepted payment methods
	 */
	private function get_payment_methods() {
		$payment_methods = get_option( 'local_payment_methods', array() );
		if ( empty( $payment_methods ) ) {
			return 'Cash, Credit Card';
		}

		return is_array( $payment_methods ) ? implode( ', ', $payment_methods ) : $payment_methods;
	}

	/**
	 * Get accepted currencies
	 */
	private function get_currencies_accepted() {
		$currencies = get_option( 'local_currencies_accepted', array( 'USD' ) );
		return is_array( $currencies ) ? implode( ', ', $currencies ) : $currencies;
	}

	/**
	 * Output JSON-LD markup for local business
	 */
	public function output_local_business_markup() {
		$markup = $this->add_enhanced_local_business_markup();
		if ( ! empty( $markup ) ) {
			echo '<script type="application/ld+json">' .
				json_encode( $markup, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT ) .
				'</script>';
		}
	}
}
