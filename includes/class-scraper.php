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
    public function set_base_url($url) {
        $this->base_url = trailingslashit($url);
    }
    
    /**
     * Scrape products from multiple pages
     */
    public function scrape_products($start_page = 1, $max_pages = 10) {
        $all_products = array();
        $current_page = $start_page;
        
        while ($current_page <= $max_pages) {
            $page_url = $this->get_page_url($current_page);
            $html = $this->fetch_page($page_url);
            
            if (!$html) {
                error_log("Failed to fetch page: " . $page_url);
                break;
            }
            
            $products = $this->parse_products($html);
            
            if (empty($products)) {
                break; // No more products found
            }
            
            $all_products = array_merge($all_products, $products);
            $current_page++;
            
            // Add delay to be respectful to the server
            sleep(2);
        }
        
        return $all_products;
    }
    
    /**
     * Generate URL for specific page
     */
    private function get_page_url($page_number) {
        return $this->base_url . 'page/' . $page_number . '/';
    }
    
    /**
     * Fetch page content using WordPress HTTP API
     */
    private function fetch_page($url) {
        $response = wp_remote_get($url, array(
            'timeout' => 30,
            'user-agent' => $this->user_agent,
            'headers' => array(
                'Accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8',
                'Accept-Language' => 'en-US,en;q=0.5',
            )
        ));
        
        if (is_wp_error($response)) {
            error_log('HTTP Error: ' . $response->get_error_message());
            return false;
        }
        
        $response_code = wp_remote_retrieve_response_code($response);
        if ($response_code !== 200) {
            error_log('HTTP Response Code: ' . $response_code);
            return false;
        }
        
        return wp_remote_retrieve_body($response);
    }
    
    /**
     * Parse products from HTML content
     */
    private function parse_products($html) {
        $products = array();
        $dom = new DOMDocument();
        
        // Suppress HTML5 errors
        libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        libxml_clear_errors();
        
        $xpath = new DOMXPath($dom);
        
        // Common WooCommerce product selectors
        $product_selectors = array(
            '//li[contains(@class, "product")]',
            '//div[contains(@class, "product")]',
            '//article[contains(@class, "product")]'
        );
        
        foreach ($product_selectors as $selector) {
            $product_nodes = $xpath->query($selector);
            
            if ($product_nodes->length > 0) {
                break;
            }
        }
        
        if ($product_nodes->length === 0) {
            return $products;
        }
        
        foreach ($product_nodes as $product_node) {
            $product_data = $this->parse_single_product($product_node, $xpath);
            if ($product_data) {
                $products[] = $product_data;
            }
        }
        
        return $products;
    }
    
    /**
     * Parse individual product data
     */
    private function parse_single_product($node, $xpath) {
        $product = array();
        
        // Product name
        $name_nodes = $xpath->query('.//h2[contains(@class, "woocommerce-loop-product__title")] | .//h3 | .//a[contains(@class, "product-title")]', $node);
        if ($name_nodes->length > 0) {
            $product['name'] = trim($name_nodes->item(0)->textContent);
        }
        
        // Product URL
        $link_nodes = $xpath->query('.//a[contains(@class, "woocommerce-LoopProduct-link")] | .//a[@href]', $node);
        if ($link_nodes->length > 0) {
            $product['url'] = $link_nodes->item(0)->getAttribute('href');
        }
        
        // Price
        $price_nodes = $xpath->query('.//span[contains(@class, "price")] | .//div[contains(@class, "price")]', $node);
        if ($price_nodes->length > 0) {
            $product['price'] = trim($price_nodes->item(0)->textContent);
        }
        
        // Image
        $img_nodes = $xpath->query('.//img', $node);
        if ($img_nodes->length > 0) {
            $product['image'] = $img_nodes->item(0)->getAttribute('src');
        }
        
        // Description (short)
        $desc_nodes = $xpath->query('.//div[contains(@class, "product-description")] | .//p', $node);
        if ($desc_nodes->length > 0) {
            $product['description'] = trim($desc_nodes->item(0)->textContent);
        }
        
        return !empty($product['name']) ? $product : false;
    }
    
    /**
     * Scrape detailed product information from product page
     */
    public function scrape_product_details($product_url) {
        $html = $this->fetch_page($product_url);
        
        if (!$html) {
            return false;
        }
        
        $dom = new DOMDocument();
        libxml_use_internal_errors(true);
        $dom->loadHTML($html);
        libxml_clear_errors();
        
        $xpath = new DOMXPath($dom);
        $details = array();
        
        // Full description
        $desc_nodes = $xpath->query('//div[contains(@class, "product_description")] | //div[contains(@class, "woocommerce-product-details__short-description")]');
        if ($desc_nodes->length > 0) {
            $details['full_description'] = trim($desc_nodes->item(0)->textContent);
        }
        
        // SKU
        $sku_nodes = $xpath->query('//span[contains(@class, "sku")]');
        if ($sku_nodes->length > 0) {
            $details['sku'] = trim($sku_nodes->item(0)->textContent);
        }
        
        // Categories
        $cat_nodes = $xpath->query('//span[contains(@class, "posted_in")]//a');
        $categories = array();
        foreach ($cat_nodes as $cat_node) {
            $categories[] = trim($cat_node->textContent);
        }
        $details['categories'] = $categories;
        
        // Additional images
        $gallery_nodes = $xpath->query('//div[contains(@class, "product-gallery")]//img | //div[contains(@class, "woocommerce-product-gallery")]//img');
        $gallery_images = array();
        foreach ($gallery_nodes as $img_node) {
            $src = $img_node->getAttribute('src');
            if ($src && $src !== $details['image']) {
                $gallery_images[] = $src;
            }
        }
        $details['gallery_images'] = $gallery_images;
        
        return $details;
    }
}
?>