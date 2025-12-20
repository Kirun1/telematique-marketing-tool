<?php

class ProductScraper_Firecrawl_Integration
{
    private $api_key;
    private $base_url;
    private $timeout;
    private $api_version = 'v2';

    public function __construct()
    {
        $this->api_key = get_option('product_scraper_firecrawl_api_key', '');
        $this->base_url = get_option('product_scraper_firecrawl_base_url', 'https://api.firecrawl.dev');
        $this->timeout = get_option('product_scraper_firecrawl_timeout', 30);
    }

    /**
     * Scrape a URL using Firecrawl API v2
     */
    public function scrape_url($url, $options = array())
    {
        if (empty($this->api_key)) {
            throw new Exception('Firecrawl API key is not configured. Please set it in the settings.');
        }

        if (!filter_var($url, FILTER_VALIDATE_URL)) {
            throw new Exception('Invalid URL format: ' . $url);
        }

        $default_options = array(
            'formats' => ['markdown', 'html'],
            'onlyMainContent' => false, // Changed to false to get all content
            'timeout' => 60000,
        );

        $options = array_merge($default_options, $options);

        $endpoint = trim($this->base_url, '/') . '/' . $this->api_version . '/scrape';
        
        $args = array(
            'timeout' => $this->timeout,
            'headers' => array(
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $this->api_key,
            ),
            'body' => json_encode(array(
                'url' => $url,
                'formats' => $options['formats'],
                'onlyMainContent' => $options['onlyMainContent'],
                'timeout' => $options['timeout'],
            )),
            'sslverify' => false,
        );

        $response = wp_remote_post($endpoint, $args);

        if (is_wp_error($response)) {
            throw new Exception('Firecrawl API request failed: ' . $response->get_error_message());
        }

        $status_code = wp_remote_retrieve_response_code($response);
        $body = wp_remote_retrieve_body($response);

        $data = json_decode($body, true);

        if ($status_code !== 200) {
            $error_msg = 'Firecrawl API error (' . $status_code . '): ';
            
            if (isset($data['error'])) {
                $error_msg .= $data['error'];
            } elseif (isset($data['message'])) {
                $error_msg .= $data['message'];
            } else {
                $error_msg .= 'Unknown error';
            }
            
            throw new Exception($error_msg);
        }

        if (!isset($data['success']) || $data['success'] !== true) {
            throw new Exception('Firecrawl API returned unsuccessful response');
        }

        return $data;
    }

    /**
     * Extract products from Nahrin.ch specifically
     */
    public function extract_products($scraped_data, $url = '')
    {
        $products = array();

        if (!isset($scraped_data['data'])) {
            return $products;
        }

        $data = $scraped_data['data'];

        // Try different extraction methods
        if (isset($data['markdown'])) {
            $products = $this->parse_nahrin_markdown($data['markdown'], $url);
        }

        // If no products found, try HTML parsing
        if (empty($products) && isset($data['html'])) {
            $products = $this->parse_nahrin_html($data['html'], $url);
        }

        // Add metadata to each product
        foreach ($products as &$product) {
            $product['scraped_at'] = current_time('mysql');
            $product['source'] = 'firecrawl_v2';
            if ($url) {
                $product['source_url'] = $url;
            }
        }

        return $products;
    }

    /**
     * Parse Nahrin.ch specific markdown format
     */
    private function parse_nahrin_markdown($markdown, $base_url)
    {
        $products = array();
        
        // Nahrin.ch specific patterns
        $lines = explode("\n", $markdown);
        
        $current_product = null;
        $collecting = false;
        $in_product_list = false;
        
        foreach ($lines as $line) {
            $trimmed_line = trim($line);
            
            // Look for product list indicators
            if (preg_match('/produkte?|artikel|items?|waren/i', $trimmed_line) && 
                strlen($trimmed_line) < 100) {
                $in_product_list = true;
                continue;
            }
            
            // Look for product names (usually bold text or headers)
            if ($in_product_list && preg_match('/^(?:\*\*|\#+)\s*(.+?)(?:\*\*|$)/', $trimmed_line, $matches)) {
                // Save previous product
                if ($current_product && !empty($current_product['name'])) {
                    $products[] = $current_product;
                }
                
                $current_product = array(
                    'name' => $matches[1],
                    'description' => '',
                    'price' => '',
                    'url' => '',
                    'image' => ''
                );
                $collecting = true;
            }
            // Look for prices (Nahrin uses CHF)
            elseif ($collecting && preg_match('/(?:CHF|Preis|Preisvorschlag)[:\s]*([\d,.]+)/i', $trimmed_line, $matches)) {
                $current_product['price'] = 'CHF ' . $matches[1];
            }
            // Look for URLs
            elseif ($collecting && preg_match('/\[([^\]]*)\]\(([^)]+)\)/', $trimmed_line, $matches)) {
                $url = $matches[2];
                // Convert relative URLs to absolute
                if (!preg_match('/^https?:\/\//', $url)) {
                    $url = rtrim($base_url, '/') . '/' . ltrim($url, '/');
                }
                $current_product['url'] = $url;
            }
            // Look for images
            elseif ($collecting && preg_match('/!\[([^\]]*)\]\(([^)]+)\)/', $trimmed_line, $matches)) {
                $image_url = $matches[2];
                // Convert relative image URLs to absolute
                if (!preg_match('/^https?:\/\//', $image_url)) {
                    $image_url = rtrim($base_url, '/') . '/' . ltrim($image_url, '/');
                }
                $current_product['image'] = $image_url;
            }
            // Collect description (non-header, non-empty lines)
            elseif ($collecting && !empty($trimmed_line) && 
                   !preg_match('/^(#|\*|\-|\d+\.|!?\[)/', $trimmed_line) &&
                   !preg_match('/(?:CHF|Preis|Preisvorschlag)[:\s]*[\d,.]+/i', $trimmed_line)) {
                
                if (strlen($current_product['description']) < 1000) {
                    if (!empty($current_product['description'])) {
                        $current_product['description'] .= ' ';
                    }
                    $current_product['description'] .= $trimmed_line;
                }
            }
            
            // If we hit a section break, save the current product
            if ($collecting && preg_match('/^#{1,3}\s+/', $trimmed_line) && 
                !preg_match('/produkt|artikel/i', $trimmed_line)) {
                if ($current_product && !empty($current_product['name'])) {
                    $products[] = $current_product;
                    $current_product = null;
                    $collecting = false;
                }
            }
        }
        
        // Save the last product
        if ($current_product && !empty($current_product['name'])) {
            $products[] = $current_product;
        }
        
        // If no products found with specific parsing, try generic extraction
        if (empty($products)) {
            $products = $this->generic_markdown_extraction($markdown, $base_url);
        }
        
        return $products;
    }

    /**
     * Generic markdown extraction as fallback
     */
    private function generic_markdown_extraction($markdown, $base_url)
    {
        $products = array();
        
        // Look for any text that might be product names
        $lines = explode("\n", $markdown);
        $potential_products = array();
        
        foreach ($lines as $line) {
            $trimmed = trim($line);
            
            // Look for lines that might be product names
            // Typically 3-10 words, not too long, not headers
            if (strlen($trimmed) > 10 && strlen($trimmed) < 150 &&
                !preg_match('/^[#*\-]/', $trimmed) &&
                !preg_match('/cookie|datenschutz|impressum|agb|kontakt/i', $trimmed) &&
                preg_match('/\p{L}/u', $trimmed)) { // Contains letters
                
                $potential_products[] = array(
                    'name' => $trimmed,
                    'description' => '',
                    'price' => '',
                    'url' => '',
                    'image' => ''
                );
                
                // Limit to avoid too many false positives
                if (count($potential_products) >= 20) {
                    break;
                }
            }
        }
        
        return $potential_products;
    }

    /**
     * Parse HTML for Nahrin products
     */
    private function parse_nahrin_html($html, $base_url)
    {
        $products = array();
        
        if (!class_exists('DOMDocument')) {
            return $products;
        }

        $dom = new DOMDocument();
        @$dom->loadHTML(mb_convert_encoding($html, 'HTML-ENTITIES', 'UTF-8'));
        
        $xpath = new DOMXPath($dom);
        
        // Try different selectors for Nahrin
        $selectors = array(
            '//div[contains(@class, "product")]',
            '//li[contains(@class, "product")]',
            '//article[contains(@class, "product")]',
            '//div[contains(@class, "item") and contains(@class, "product")]',
            '//div[@data-product-id]',
            '//div[contains(@class, "product-item")]',
            '//div[contains(@class, "product-list")]//div[contains(@class, "item")]',
        );

        foreach ($selectors as $selector) {
            $nodes = $xpath->query($selector);
            
            if ($nodes && $nodes->length > 0) {
                foreach ($nodes as $node) {
                    $product = $this->extract_product_from_node($node, $xpath, $base_url);
                    if ($product && !empty($product['name'])) {
                        $products[] = $product;
                    }
                }
                
                if (!empty($products)) {
                    break;
                }
            }
        }

        return $products;
    }

    /**
     * Extract product from DOM node
     */
    private function extract_product_from_node($node, $xpath, $base_url)
    {
        $product = array();
        
        // Name - try various selectors
        $name_selectors = array(
            './/h1', './/h2', './/h3', './/h4',
            './/*[contains(@class, "product-name")]',
            './/*[contains(@class, "product-title")]',
            './/*[contains(@class, "item-title")]',
            './/*[@itemprop="name"]',
            './/a[contains(@class, "product-item-link")]',
        );
        
        foreach ($name_selectors as $selector) {
            $elements = $xpath->query($selector, $node);
            if ($elements && $elements->length > 0) {
                $product['name'] = trim($elements->item(0)->textContent);
                break;
            }
        }
        
        // Price
        $price_selectors = array(
            './/*[contains(@class, "price")]',
            './/*[contains(@class, "product-price")]',
            './/*[@itemprop="price"]',
            './/*[contains(text(), "CHF")]',
            './/span[contains(@class, "price")]',
        );
        
        foreach ($price_selectors as $selector) {
            $elements = $xpath->query($selector, $node);
            if ($elements && $elements->length > 0) {
                $product['price'] = trim($elements->item(0)->textContent);
                break;
            }
        }
        
        // URL
        $link_selectors = array(
            './/a[contains(@href, "product")]',
            './/a[contains(@class, "product")]',
            './/a[@href]',
        );
        
        foreach ($link_selectors as $selector) {
            $elements = $xpath->query($selector, $node);
            if ($elements && $elements->length > 0) {
                $href = $elements->item(0)->getAttribute('href');
                if ($href) {
                    // Make URL absolute
                    if (!preg_match('/^https?:\/\//', $href)) {
                        $href = rtrim($base_url, '/') . '/' . ltrim($href, '/');
                    }
                    $product['url'] = $href;
                }
                break;
            }
        }
        
        // Image
        $image_selectors = array(
            './/img[contains(@class, "product")]',
            './/img[@src]',
            './/*[contains(@class, "product-image")]//img',
        );
        
        foreach ($image_selectors as $selector) {
            $elements = $xpath->query($selector, $node);
            if ($elements && $elements->length > 0) {
                $src = $elements->item(0)->getAttribute('src');
                if ($src) {
                    // Make image URL absolute
                    if (!preg_match('/^https?:\/\//', $src)) {
                        $src = rtrim($base_url, '/') . '/' . ltrim($src, '/');
                    }
                    $product['image'] = $src;
                }
                break;
            }
        }
        
        return !empty($product) ? $product : null;
    }

    /**
     * Test API connection
     */
    public function test_connection()
    {
        try {
            if (empty($this->api_key)) {
                return array(
                    'success' => false,
                    'message' => 'API key is empty. Please set your Firecrawl API key in settings.'
                );
            }

            $test_url = 'https://example.com';
            $result = $this->scrape_url($test_url, [
                'formats' => ['markdown'],
                'onlyMainContent' => true,
            ]);
            
            return array(
                'success' => true,
                'message' => 'Firecrawl API v2 connection successful!'
            );
            
        } catch (Exception $e) {
            $error_message = $e->getMessage();
            
            if (strpos($error_message, '401') !== false || 
                strpos($error_message, '403') !== false ||
                strpos($error_message, 'Invalid') !== false) {
                return array(
                    'success' => false,
                    'message' => 'Invalid API key. Please check your Firecrawl API key.'
                );
            } elseif (strpos($error_message, 'connection') !== false) {
                return array(
                    'success' => false,
                    'message' => 'Network connection error. Check your internet and the API base URL.'
                );
            } else {
                return array(
                    'success' => false,
                    'message' => 'Connection failed: ' . $error_message
                );
            }
        }
    }
}