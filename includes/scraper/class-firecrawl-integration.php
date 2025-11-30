<?php

class ProductScraper_Firecrawl_Integration
{

    private $api_key;
    private $base_url;
    private $timeout;

    public function __construct()
    {
        $this->api_key = get_option('product_scraper_firecrawl_api_key', '');
        $this->base_url = get_option('product_scraper_firecrawl_base_url', 'https://api.firecrawl.dev');
        $this->timeout = get_option('product_scraper_firecrawl_timeout', 30);
    }

    /**
     * Scrape a URL using Firecrawl API
     */
    public function scrape_url($url, $options = array())
    {
        if (empty($this->api_key)) {
            throw new Exception('Firecrawl API key is not configured');
        }

        $default_options = array(
            'formats' => ['markdown', 'html'],
            'onlyMainContent' => true,
            'includeTags' => ['h1', 'h2', 'h3', 'p', 'img', 'price', 'product', 'description']
        );

        $options = array_merge($default_options, $options);

        $response = wp_remote_post(
            $this->base_url . '/v1/scrape',
            array(
                'timeout' => $this->timeout,
                'headers' => array(
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $this->api_key,
                ),
                'body' => json_encode(array(
                    'url' => $url,
                    'formats' => $options['formats'],
                    'onlyMainContent' => $options['onlyMainContent'],
                    'includeTags' => $options['includeTags'],
                ))
            )
        );

        if (is_wp_error($response)) {
            throw new Exception('Firecrawl API request failed: ' . $response->get_error_message());
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if ($status_code !== 200) {
            throw new Exception('Firecrawl API error: ' . ($data['error'] ?? 'Unknown error'));
        }

        return $data;
    }

    /**
     * Scrape multiple URLs in batch
     */
    public function scrape_urls($urls, $options = array())
    {
        if (empty($this->api_key)) {
            throw new Exception('Firecrawl API key is not configured');
        }

        $response = wp_remote_post(
            $this->base_url . '/v1/scrape',
            array(
                'timeout' => $this->timeout * count($urls),
                'headers' => array(
                    'Content-Type' => 'application/json',
                    'Authorization' => 'Bearer ' . $this->api_key,
                ),
                'body' => json_encode(array(
                    'urls' => $urls,
                    'formats' => $options['formats'] ?? ['markdown', 'html'],
                    'onlyMainContent' => $options['onlyMainContent'] ?? true,
                ))
            )
        );

        if (is_wp_error($response)) {
            throw new Exception('Firecrawl API batch request failed: ' . $response->get_error_message());
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);
        $data = json_decode($body, true);

        if ($status_code !== 200) {
            throw new Exception('Firecrawl API error: ' . ($data['error'] ?? 'Unknown error'));
        }

        return $data;
    }

    /**
     * Extract product information from scraped content
     */
    public function extract_products($scraped_data, $list_selector = null)
    {
        $products = array();

        if (isset($scraped_data['data'])) {
            $data = $scraped_data['data'];

            // Extract from markdown content
            if (isset($data['markdown'])) {
                $products = array_merge($products, $this->parse_markdown_products($data['markdown']));
            }

            // Extract from HTML content
            if (isset($data['html'])) {
                $products = array_merge($products, $this->parse_html_products($data['html'], $list_selector));
            }

            // Extract from LLM extraction if available
            if (isset($data['llm_extraction'])) {
                $products = array_merge($products, $this->parse_llm_extraction($data['llm_extraction']));
            }
        }

        return $products;
    }

    /**
     * Parse products from markdown content
     */
    private function parse_markdown_products($markdown)
    {
        $products = array();

        // Simple markdown parsing for product information
        // This can be enhanced based on your specific needs
        $lines = explode("\n", $markdown);
        $current_product = array();

        foreach ($lines as $line) {
            $line = trim($line);

            // Detect product names (usually headers)
            if (preg_match('/^#+\s+(.+)/', $line, $matches)) {
                if (!empty($current_product)) {
                    $products[] = $current_product;
                    $current_product = array();
                }
                $current_product['name'] = $matches[1];
            }
            // Detect prices
            elseif (preg_match('/(?:\$|€|£|CHF)\s*([0-9]+[.,][0-9]+|[0-9]+)/', $line, $matches)) {
                $current_product['price'] = $matches[0];
            }
            // Detect images
            elseif (preg_match('/!\[.*?\]\((.*?)\)/', $line, $matches)) {
                $current_product['image'] = $matches[1];
            }
            // Collect description
            elseif (!empty($line) && !preg_match('/^[#\-*]/', $line)) {
                if (isset($current_product['description'])) {
                    $current_product['description'] .= ' ' . $line;
                } else {
                    $current_product['description'] = $line;
                }
            }
        }

        // Add the last product
        if (!empty($current_product)) {
            $products[] = $current_product;
        }

        return $products;
    }

    /**
     * Parse products from HTML content
     */
    private function parse_html_products($html, $selector = null)
    {
        $products = array();

        if (!class_exists('DOMDocument')) {
            return $products;
        }

        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        libxml_clear_errors();

        $xpath = new DOMXPath($dom);

        // If no specific selector provided, try common product list patterns
        if (empty($selector)) {
            $selectors = array(
                '//div[contains(@class, "product")]',
                '//li[contains(@class, "product")]',
                '//article[contains(@class, "product")]',
                '//div[contains(@class, "item")]',
            );
        } else {
            $selectors = array($selector);
        }

        foreach ($selectors as $selector) {
            $product_nodes = $xpath->query($selector);

            if ($product_nodes->length > 0) {
                foreach ($product_nodes as $node) {
                    $product = $this->extract_product_from_node($node, $xpath);
                    if (!empty($product)) {
                        $products[] = $product;
                    }
                }
                break; // Use the first selector that finds products
            }
        }

        return $products;
    }

    /**
     * Extract product information from a DOM node
     */
    private function extract_product_from_node($node, $xpath)
    {
        $product = array();

        // Extract name
        $name_nodes = $xpath->query('.//h1|.//h2|.//h3|.//*[contains(@class, "name")]|.//*[contains(@class, "title")]', $node);
        if ($name_nodes->length > 0) {
            $product['name'] = trim($name_nodes->item(0)->textContent);
        }

        // Extract price
        $price_nodes = $xpath->query('.//*[contains(@class, "price")]|.//*[contains(@class, "cost")]|.//*[contains(text(), "$") or contains(text(), "€") or contains(text(), "£") or contains(text(), "CHF")]', $node);
        if ($price_nodes->length > 0) {
            $product['price'] = trim($price_nodes->item(0)->textContent);
        }

        // Extract image
        $image_nodes = $xpath->query('.//img', $node);
        if ($image_nodes->length > 0) {
            $product['image'] = $image_nodes->item(0)->getAttribute('src');
        }

        // Extract description
        $desc_nodes = $xpath->query('.//p|.//*[contains(@class, "description")]|.//*[contains(@class, "desc")]', $node);
        if ($desc_nodes->length > 0) {
            $product['description'] = trim($desc_nodes->item(0)->textContent);
        }

        // Extract URL
        $link_nodes = $xpath->query('.//a', $node);
        if ($link_nodes->length > 0) {
            $product['url'] = $link_nodes->item(0)->getAttribute('href');
        }

        return !empty($product) ? $product : null;
    }

    /**
     * Parse LLM extraction data
     */
    private function parse_llm_extraction($llm_data)
    {
        if (is_string($llm_data)) {
            $llm_data = json_decode($llm_data, true);
        }

        if (isset($llm_data['products']) && is_array($llm_data['products'])) {
            return $llm_data['products'];
        }

        return array();
    }

    /**
     * Test API connection
     */
    public function test_connection()
    {
        try {
            $test_url = 'https://example.com';
            $result = $this->scrape_url($test_url, array('onlyMainContent' => true));
            return array('success' => true, 'message' => 'Firecrawl API connection successful');
        } catch (Exception $e) {
            return array('success' => false, 'message' => $e->getMessage());
        }
    }
}
