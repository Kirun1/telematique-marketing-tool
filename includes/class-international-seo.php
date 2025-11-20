<?php
class ProductScraper_International_SEO {

	public function add_hreflang_tags() {
		// Multi-language and multi-region support
		$languages = array(
			'en-us' => 'https://example.com/us/',
			'en-gb' => 'https://example.com/uk/',
			'es-es' => 'https://example.com/es/',
			'fr-fr' => 'https://example.com/fr/',
		);

		return $languages;
	}
}
