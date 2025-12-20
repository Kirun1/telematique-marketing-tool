<?php
class ProductScraper_International_SEO
{

	/**
	 * Generate hreflang tags for a WooCommerce product
	 *
	 * @param int $product_id WooCommerce product ID
	 * @return array Array of hreflang => URL
	 */
	public function add_hreflang_tags($product_id)
	{
		$hreflangs = array();

		// WPML integration
		if (function_exists('icl_object_id')) {
			$languages = apply_filters('wpml_active_languages', NULL, array('skip_missing' => 0));
			if (!empty($languages)) {
				foreach ($languages as $lang_code => $lang_info) {
					$translated_id = apply_filters('wpml_object_id', $product_id, 'product', true, $lang_code);
					if ($translated_id) {
						$hreflangs[$lang_code] = get_permalink($translated_id);
					}
				}
			}
		}
		// Polylang integration
		elseif (function_exists('pll_get_post')) {
			$languages = pll_get_post_types(); // All translatable post types
			$all_langs = pll_get_the_languages();
			if (!empty($all_langs)) {
				foreach ($all_langs as $lang_code => $lang_info) {
					$translated_id = pll_get_post($product_id, $lang_code);
					if ($translated_id) {
						$hreflangs[$lang_code] = get_permalink($translated_id);
					}
				}
			}
		}
		// Fallback: site default
		else {
			$hreflangs[get_locale()] = get_permalink($product_id);
		}

		return $hreflangs;
	}

	/**
	 * Output HTML <link rel="alternate" hreflang=""> tags
	 *
	 * @param int $product_id
	 */
	public function render_hreflang_tags($product_id)
	{
		$tags = $this->add_hreflang_tags($product_id);
		foreach ($tags as $lang => $url) {
			echo '<link rel="alternate" hreflang="' . esc_attr($lang) . '" href="' . esc_url($url) . '" />' . PHP_EOL;
		}
	}
}
