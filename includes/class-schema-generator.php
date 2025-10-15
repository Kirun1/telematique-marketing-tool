<?php
class ProductScraper_Schema_Generator
{
    private $post;
    private $schema_data;

    public function __construct()
    {
        $this->schema_data = array();
    }

    public function generate_schema($post_id)
    {
        $this->post = get_post($post_id);
        $schema_type = $this->determine_schema_type($post_id);

        $base_schema = array(
            '@context' => 'https://schema.org',
            '@type' => $schema_type,
            'headline' => get_the_title($post_id),
            'description' => $this->get_meta_description($post_id),
            'datePublished' => get_the_date('c', $post_id),
            'dateModified' => get_the_modified_date('c', $post_id),
            'author' => array(
                '@type' => 'Person',
                'name' => get_the_author_meta('display_name', get_post_field('post_author', $post_id))
            ),
            'publisher' => $this->get_publisher_info(),
            'mainEntityOfPage' => array(
                '@type' => 'WebPage',
                '@id' => get_permalink($post_id)
            )
        );

        // Add schema-specific data based on type
        $type_specific_schema = $this->generate_type_specific_schema($schema_type, $post_id);

        // Merge base schema with type-specific schema
        $complete_schema = array_merge($base_schema, $type_specific_schema);

        // Add organization schema
        $complete_schema['publisher'] = $this->get_organization_schema();

        // Add breadcrumb schema
        $complete_schema['breadcrumb'] = $this->generate_breadcrumb_schema($post_id);

        // Add website schema
        $complete_schema['@graph'] = array(
            $this->generate_website_schema(),
            $this->generate_organization_schema(),
            $this->generate_breadcrumb_schema($post_id)
        );

        return $complete_schema;
    }

    /**
     * Determine the most appropriate schema type for the post
     */
    public function determine_schema_type($post_id)
    {
        $post_type = get_post_type($post_id);
        $categories = get_the_category($post_id);
        $tags = get_the_tags($post_id);
        $content = get_post_field('post_content', $post_id);

        // Check for specific content patterns first
        if ($this->is_product_page($post_id)) {
            return 'Product';
        } elseif ($this->is_recipe_page($content)) {
            return 'Recipe';
        } elseif ($this->is_event_page($content)) {
            return 'Event';
        } elseif ($this->is_faq_page($content)) {
            return 'FAQPage';
        } elseif ($this->is_howto_page($content)) {
            return 'HowTo';
        } elseif ($this->is_review_page($content)) {
            return 'Review';
        } elseif ($this->is_local_business_page($post_id)) {
            return 'LocalBusiness';
        }

        // Default based on post type and categories
        switch ($post_type) {
            case 'post':
                // Check if it's a news article
                if ($this->is_news_article($post_id)) {
                    return 'NewsArticle';
                }
                return 'Article';

            case 'page':
                if (is_front_page()) {
                    return 'WebSite';
                } elseif ($this->is_about_page($post_id)) {
                    return 'AboutPage';
                } elseif ($this->is_contact_page($post_id)) {
                    return 'ContactPage';
                }
                return 'WebPage';

            case 'product':
                return 'Product';

            default:
                return 'Article';
        }
    }

    /**
     * Get meta description for schema
     */
    public function get_meta_description($post_id)
    {
        // First, try to get from custom field
        $meta_description = get_post_meta($post_id, '_meta_description', true);

        if (empty($meta_description)) {
            // Try SEO plugins
            $meta_description = get_post_meta($post_id, '_yoast_wpseo_metadesc', true);
        }

        if (empty($meta_description)) {
            // Try All in One SEO
            $meta_description = get_post_meta($post_id, '_aioseop_description', true);
        }

        if (empty($meta_description)) {
            // Generate from content
            $post = get_post($post_id);
            $meta_description = wp_trim_words(wp_strip_all_tags($post->post_content), 25);
        }

        return sanitize_text_field($meta_description);
    }

    /**
     * Get publisher information
     */
    private function get_publisher_info()
    {
        return array(
            '@type' => 'Organization',
            'name' => get_bloginfo('name'),
            'url' => home_url(),
            'logo' => $this->get_site_logo()
        );
    }

    /**
     * Generate type-specific schema data
     */
    private function generate_type_specific_schema($schema_type, $post_id)
    {
        switch ($schema_type) {
            case 'Article':
            case 'NewsArticle':
                return $this->generate_article_schema($post_id);

            case 'Product':
                return $this->generate_product_schema($post_id);

            case 'Recipe':
                return $this->generate_recipe_schema($post_id);

            case 'Event':
                return $this->generate_event_schema($post_id);

            case 'LocalBusiness':
                return $this->generate_local_business_schema($post_id);

            case 'FAQPage':
                return $this->generate_faq_schema($post_id);

            case 'HowTo':
                return $this->generate_howto_schema($post_id);

            case 'Review':
                return $this->generate_review_schema($post_id);

            default:
                return array();
        }
    }

    /**
     * Generate Article schema
     */
    private function generate_article_schema($post_id)
    {
        $post = get_post($post_id);
        $categories = get_the_category($post_id);
        $tags = get_the_tags($post_id);

        $article_schema = array(
            'articleSection' => !empty($categories) ? $categories[0]->name : 'General',
            'keywords' => $this->get_keywords_string($post_id),
            'wordCount' => str_word_count(wp_strip_all_tags($post->post_content)),
            'timeRequired' => $this->get_reading_time($post_id),
            'articleBody' => wp_strip_all_tags($post->post_content)
        );

        // Add featured image
        $thumbnail_url = get_the_post_thumbnail_url($post_id, 'full');
        if ($thumbnail_url) {
            $article_schema['image'] = array(
                '@type' => 'ImageObject',
                'url' => $thumbnail_url,
                'width' => 1200,
                'height' => 630
            );
        }

        // Add comments count if applicable
        $comment_count = get_comments_number($post_id);
        if ($comment_count > 0) {
            $article_schema['commentCount'] = $comment_count;
        }

        return $article_schema;
    }

    /**
     * Generate Product schema
     */
    private function generate_product_schema($post_id)
    {
        $product_schema = array(
            '@type' => 'Product',
            'name' => get_the_title($post_id),
            'description' => $this->get_meta_description($post_id),
            'sku' => $this->get_product_sku($post_id),
            'brand' => array(
                '@type' => 'Brand',
                'name' => $this->get_product_brand($post_id)
            ),
            'offers' => array(
                '@type' => 'Offer',
                'priceCurrency' => $this->get_currency(),
                'price' => $this->get_product_price($post_id),
                'availability' => $this->get_product_availability($post_id),
                'url' => get_permalink($post_id)
            )
        );

        // Add product images
        $images = $this->get_product_images($post_id);
        if (!empty($images)) {
            $product_schema['image'] = $images;
        }

        // Add reviews if available
        $reviews = $this->get_product_reviews($post_id);
        if (!empty($reviews)) {
            $product_schema['review'] = $reviews;
            $product_schema['aggregateRating'] = $this->get_aggregate_rating($post_id);
        }

        return $product_schema;
    }

    /**
     * Generate Recipe schema
     */
    private function generate_recipe_schema($post_id)
    {
        $content = get_post_field('post_content', $post_id);

        return array(
            '@type' => 'Recipe',
            'name' => get_the_title($post_id),
            'description' => $this->get_meta_description($post_id),
            'prepTime' => $this->extract_recipe_time($content, 'prep'),
            'cookTime' => $this->extract_recipe_time($content, 'cook'),
            'totalTime' => $this->extract_recipe_time($content, 'total'),
            'recipeYield' => $this->extract_recipe_yield($content),
            'recipeIngredient' => $this->extract_recipe_ingredients($content),
            'recipeInstructions' => $this->extract_recipe_instructions($content),
            'nutrition' => $this->extract_recipe_nutrition($content),
            'aggregateRating' => $this->get_aggregate_rating($post_id)
        );
    }

    /**
     * Generate Event schema
     */
    private function generate_event_schema($post_id)
    {
        $event_date = get_post_meta($post_id, '_event_date', true);
        $event_location = get_post_meta($post_id, '_event_location', true);

        return array(
            '@type' => 'Event',
            'name' => get_the_title($post_id),
            'description' => $this->get_meta_description($post_id),
            'startDate' => $event_date ?: get_the_date('c', $post_id),
            'endDate' => get_post_meta($post_id, '_event_end_date', true),
            'eventStatus' => 'https://schema.org/EventScheduled',
            'eventAttendanceMode' => 'https://schema.org/OfflineEventAttendanceMode',
            'location' => array(
                '@type' => 'Place',
                'name' => $event_location ?: get_bloginfo('name'),
                'address' => $this->get_location_address($post_id)
            ),
            'organizer' => array(
                '@type' => 'Organization',
                'name' => get_bloginfo('name'),
                'url' => home_url()
            )
        );
    }

    /**
     * Generate LocalBusiness schema
     */
    private function generate_local_business_schema($post_id)
    {
        return array(
            '@type' => 'LocalBusiness',
            'name' => get_bloginfo('name'),
            'description' => $this->get_meta_description($post_id),
            'url' => home_url(),
            'telephone' => get_option('business_phone'),
            'address' => array(
                '@type' => 'PostalAddress',
                'streetAddress' => get_option('business_address_street'),
                'addressLocality' => get_option('business_address_city'),
                'addressRegion' => get_option('business_address_region'),
                'postalCode' => get_option('business_address_zip'),
                'addressCountry' => get_option('business_address_country')
            ),
            'geo' => array(
                '@type' => 'GeoCoordinates',
                'latitude' => get_option('business_latitude'),
                'longitude' => get_option('business_longitude')
            ),
            'openingHoursSpecification' => $this->get_business_hours(),
            'priceRange' => get_option('business_price_range'),
            'sameAs' => $this->get_social_profiles()
        );
    }

    /**
     * Generate FAQ schema
     */
    private function generate_faq_schema($post_id)
    {
        $content = get_post_field('post_content', $post_id);
        $faq_questions = $this->extract_faq_questions($content);

        if (empty($faq_questions)) {
            return array();
        }

        return array(
            'mainEntity' => $faq_questions
        );
    }

    /**
     * Generate HowTo schema
     */
    private function generate_howto_schema($post_id)
    {
        $content = get_post_field('post_content', $post_id);

        return array(
            'estimatedCost' => array(
                '@type' => 'MonetaryAmount',
                'currency' => $this->get_currency(),
                'value' => $this->extract_howto_cost($content)
            ),
            'supply' => $this->extract_howto_supplies($content),
            'tool' => $this->extract_howto_tools($content),
            'step' => $this->extract_howto_steps($content),
            'totalTime' => $this->extract_recipe_time($content, 'total')
        );
    }

    /**
     * Generate Review schema
     */
    private function generate_review_schema($post_id)
    {
        $rating = get_post_meta($post_id, '_review_rating', true);

        return array(
            'itemReviewed' => array(
                '@type' => 'Product',
                'name' => get_post_meta($post_id, '_reviewed_product', true) ?: get_the_title($post_id)
            ),
            'reviewRating' => array(
                '@type' => 'Rating',
                'ratingValue' => $rating ?: 5,
                'bestRating' => 5,
                'worstRating' => 1
            ),
            'author' => array(
                '@type' => 'Person',
                'name' => get_the_author_meta('display_name', get_post_field('post_author', $post_id))
            )
        );
    }

    /**
     * Generate Website schema
     */
    private function generate_website_schema()
    {
        return array(
            '@type' => 'WebSite',
            '@id' => home_url('/#website'),
            'url' => home_url(),
            'name' => get_bloginfo('name'),
            'description' => get_bloginfo('description'),
            'publisher' => array(
                '@id' => home_url('/#organization')
            )
        );
    }

    /**
     * Generate Organization schema
     */
    private function generate_organization_schema()
    {
        return array(
            '@type' => 'Organization',
            '@id' => home_url('/#organization'),
            'name' => get_bloginfo('name'),
            'url' => home_url(),
            'logo' => array(
                '@type' => 'ImageObject',
                'url' => $this->get_site_logo(),
                'width' => 600,
                'height' => 60
            ),
            'sameAs' => $this->get_social_profiles()
        );
    }

    /**
     * Generate Breadcrumb schema
     */
    private function generate_breadcrumb_schema($post_id)
    {
        $breadcrumbs = array(
            '@type' => 'BreadcrumbList',
            'itemListElement' => array()
        );

        // Home page
        $breadcrumbs['itemListElement'][] = array(
            '@type' => 'ListItem',
            'position' => 1,
            'name' => 'Home',
            'item' => home_url()
        );

        // Categories
        $categories = get_the_category($post_id);
        $position = 2;

        if (!empty($categories)) {
            foreach ($categories as $category) {
                $breadcrumbs['itemListElement'][] = array(
                    '@type' => 'ListItem',
                    'position' => $position++,
                    'name' => $category->name,
                    'item' => get_category_link($category->term_id)
                );
            }
        }

        // Current page
        $breadcrumbs['itemListElement'][] = array(
            '@type' => 'ListItem',
            'position' => $position,
            'name' => get_the_title($post_id),
            'item' => get_permalink($post_id)
        );

        return $breadcrumbs;
    }

    // Helper methods for schema detection

    private function is_product_page($post_id)
    {
        return get_post_type($post_id) === 'product' ||
            stripos(get_the_title($post_id), 'buy') !== false ||
            stripos(get_the_title($post_id), 'price') !== false ||
            $this->has_product_metadata($post_id);
    }

    private function is_recipe_page($content)
    {
        $recipe_indicators = array('ingredients', 'instructions', 'cook', 'bake', 'recipe', 'prep time');
        return $this->contains_indicators($content, $recipe_indicators);
    }

    private function is_event_page($content)
    {
        $event_indicators = array('event', 'date:', 'time:', 'location', 'venue', 'tickets');
        return $this->contains_indicators($content, $event_indicators);
    }

    private function is_faq_page($content)
    {
        return preg_match_all('/<h[1-6][^>]*>.*?\?.*?<\/h[1-6]>/i', $content) >= 3 ||
            substr_count($content, '?') >= 5;
    }

    private function is_howto_page($content)
    {
        $howto_indicators = array('step', 'instructions', 'how to', 'tutorial', 'guide');
        return $this->contains_indicators($content, $howto_indicators);
    }

    private function is_review_page($content)
    {
        $review_indicators = array('review', 'rating', 'stars', 'pros', 'cons', 'verdict');
        return $this->contains_indicators($content, $review_indicators);
    }

    private function is_local_business_page($post_id)
    {
        return is_page_template('contact.php') ||
            stripos(get_the_title($post_id), 'contact') !== false ||
            stripos(get_the_title($post_id), 'location') !== false;
    }

    private function is_news_article($post_id)
    {
        $post_date = get_the_date('Y-m-d', $post_id);
        $days_ago = (time() - strtotime($post_date)) / (60 * 60 * 24);
        return $days_ago <= 2; // Consider as news if published within 2 days
    }

    private function is_about_page($post_id)
    {
        return stripos(get_the_title($post_id), 'about') !== false ||
            is_page_template('about.php');
    }

    private function is_contact_page($post_id)
    {
        return stripos(get_the_title($post_id), 'contact') !== false ||
            is_page_template('contact.php');
    }

    // Extraction helper methods

    private function extract_faq_questions($content)
    {
        preg_match_all('/<h[1-6][^>]*>(.*?\?)[^<]*<\/h[1-6]>/i', $content, $questions);
        $faq_items = array();

        foreach ($questions[1] as $index => $question) {
            // Find the answer (content until next heading)
            $answer = $this->extract_faq_answer($content, $index);

            $faq_items[] = array(
                '@type' => 'Question',
                'name' => wp_strip_all_tags($question),
                'acceptedAnswer' => array(
                    '@type' => 'Answer',
                    'text' => wp_strip_all_tags($answer)
                )
            );
        }

        return $faq_items;
    }

    private function extract_faq_answer($content, $question_index)
    {
        // Simplified answer extraction - in real implementation, you'd want more robust parsing
        $parts = preg_split('/<h[1-6][^>]*>/i', $content);
        return isset($parts[$question_index + 1]) ? wp_strip_all_tags($parts[$question_index + 1]) : '';
    }

    private function extract_recipe_ingredients($content)
    {
        preg_match_all('/(?:\d+\/\d+|\d+\.?\d*)\s*(?:cup|tbsp|tsp|oz|lb|g|kg|ml|l|teaspoon|tablespoon|ounce|pound|gram|kilogram|milliliter|liter)s?\s+[^\n]+/i', $content, $matches);
        return $matches[0] ?: array();
    }

    private function extract_recipe_instructions($content)
    {
        $instructions = array();
        // Look for numbered steps
        preg_match_all('/\d+\.\s*([^\n\.!?]+[\.!?])/i', $content, $steps);

        foreach ($steps[1] as $index => $step) {
            $instructions[] = array(
                '@type' => 'HowToStep',
                'position' => $index + 1,
                'text' => trim($step)
            );
        }

        return $instructions;
    }

    // Additional helper methods

    private function get_site_logo()
    {
        $custom_logo_id = get_theme_mod('custom_logo');
        if ($custom_logo_id) {
            return wp_get_attachment_url($custom_logo_id);
        }
        return '';
    }

    private function get_keywords_string($post_id)
    {
        $tags = get_the_tags($post_id);
        $keywords = array();

        if ($tags) {
            foreach ($tags as $tag) {
                $keywords[] = $tag->name;
            }
        }

        return implode(', ', $keywords);
    }

    private function get_reading_time($post_id)
    {
        $content = get_post_field('post_content', $post_id);
        $word_count = str_word_count(wp_strip_all_tags($content));
        $reading_time = ceil($word_count / 200); // 200 words per minute
        return 'PT' . $reading_time . 'M';
    }

    private function contains_indicators($content, $indicators)
    {
        foreach ($indicators as $indicator) {
            if (stripos($content, $indicator) !== false) {
                return true;
            }
        }
        return false;
    }

    // Product-specific methods
    private function get_product_sku($post_id)
    {
        return get_post_meta($post_id, '_sku', true) ?: 'SKU-' . $post_id;
    }

    private function get_product_brand($post_id)
    {
        return get_post_meta($post_id, '_brand', true) ?: get_bloginfo('name');
    }

    private function get_product_price($post_id)
    {
        $price = get_post_meta($post_id, '_price', true);
        return $price ? floatval($price) : 0;
    }

    private function get_product_availability($post_id)
    {
        $stock = get_post_meta($post_id, '_stock_status', true);
        switch ($stock) {
            case 'instock':
                return 'https://schema.org/InStock';
            case 'outofstock':
                return 'https://schema.org/OutOfStock';
            default:
                return 'https://schema.org/InStock';
        }
    }

    private function get_product_images($post_id)
    {
        $images = array();
        $thumbnail_id = get_post_thumbnail_id($post_id);

        if ($thumbnail_id) {
            $images[] = wp_get_attachment_url($thumbnail_id);
        }

        // Get gallery images
        $gallery_ids = get_post_meta($post_id, '_product_image_gallery', true);
        if ($gallery_ids) {
            $gallery_ids = explode(',', $gallery_ids);
            foreach ($gallery_ids as $gallery_id) {
                $images[] = wp_get_attachment_url($gallery_id);
            }
        }

        return $images;
    }

    private function get_currency()
    {
        return get_option('woocommerce_currency') ?: 'USD';
    }

    // Output the schema as JSON-LD
    public function output_json_ld($post_id)
    {
        $schema = $this->generate_schema($post_id);
        if (!empty($schema)) {
            echo '<script type="application/ld+json">' . json_encode($schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . '</script>';
        }
    }

    // Validate schema
    public function validate_schema($schema)
    {
        // Basic validation - in production, you might want to use Schema.org's validator
        $required_fields = array('@context', '@type', 'name');

        foreach ($required_fields as $field) {
            if (!isset($schema[$field]) || empty($schema[$field])) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get Organization schema
     */
    private function get_organization_schema()
    {
        return array(
            '@type' => 'Organization',
            '@id' => home_url('/#organization'),
            'name' => get_bloginfo('name'),
            'url' => home_url(),
            'logo' => array(
                '@type' => 'ImageObject',
                'url' => $this->get_site_logo(),
                'width' => 600,
                'height' => 60
            ),
            'sameAs' => $this->get_social_profiles(),
            'contactPoint' => array(
                '@type' => 'ContactPoint',
                'telephone' => get_option('business_phone') ?: '',
                'contactType' => 'customer service',
                'email' => get_option('admin_email')
            )
        );
    }

    /**
     * Get product reviews for schema
     */
    private function get_product_reviews($post_id)
    {
        $reviews = array();

        // Check if WooCommerce is active and product has reviews
        if (function_exists('wc_get_product') && get_post_type($post_id) === 'product') {
            $product = wc_get_product($post_id);
            $args = array(
                'post_id' => $post_id,
                'status' => 'approve',
                'type' => 'review'
            );

            $comments = get_comments($args);

            foreach ($comments as $comment) {
                $rating = get_comment_meta($comment->comment_ID, 'rating', true);

                if ($rating) {
                    $reviews[] = array(
                        '@type' => 'Review',
                        'author' => array(
                            '@type' => 'Person',
                            'name' => $comment->comment_author
                        ),
                        'datePublished' => $comment->comment_date,
                        'description' => $comment->comment_content,
                        'reviewRating' => array(
                            '@type' => 'Rating',
                            'ratingValue' => $rating,
                            'bestRating' => 5,
                            'worstRating' => 1
                        )
                    );
                }
            }
        }

        // If no WooCommerce reviews, check for custom review meta
        if (empty($reviews)) {
            $custom_reviews = get_post_meta($post_id, '_custom_reviews', true);
            if ($custom_reviews && is_array($custom_reviews)) {
                foreach ($custom_reviews as $review) {
                    $reviews[] = array(
                        '@type' => 'Review',
                        'author' => array(
                            '@type' => 'Person',
                            'name' => $review['author'] ?? 'Anonymous'
                        ),
                        'datePublished' => $review['date'] ?? current_time('c'),
                        'description' => $review['content'] ?? '',
                        'reviewRating' => array(
                            '@type' => 'Rating',
                            'ratingValue' => $review['rating'] ?? 5,
                            'bestRating' => 5,
                            'worstRating' => 1
                        )
                    );
                }
            }
        }

        return $reviews;
    }

    /**
     * Get aggregate rating for products
     */
    private function get_aggregate_rating($post_id)
    {
        $rating_value = 0;
        $review_count = 0;

        // Check if WooCommerce is active and the function exists
        if (class_exists('WooCommerce') && function_exists('wc_get_product') && get_post_type($post_id) === 'product') {
            $product = wc_get_product($post_id);
            if ($product) {
                $rating_value = $product->get_average_rating();
                $review_count = $product->get_review_count();
            }
        }

        // Custom rating fallback
        if (!$rating_value) {
            $rating_value = get_post_meta($post_id, '_average_rating', true);
            $review_count = get_post_meta($post_id, '_review_count', true);
        }

        if ($rating_value && $review_count > 0) {
            return array(
                '@type' => 'AggregateRating',
                'ratingValue' => floatval($rating_value),
                'bestRating' => 5,
                'worstRating' => 1,
                'ratingCount' => intval($review_count),
                'reviewCount' => intval($review_count)
            );
        }

        return null;
    }

    /**
     * Extract recipe time from content
     */
    private function extract_recipe_time($content, $type = 'total')
    {
        $patterns = array(
            'prep' => '/prep.*?time.*?(\d+)/i',
            'cook' => '/cook.*?time.*?(\d+)/i',
            'total' => '/total.*?time.*?(\d+)/i'
        );

        $pattern = $patterns[$type] ?? $patterns['total'];
        preg_match($pattern, $content, $matches);

        if (isset($matches[1])) {
            $minutes = intval($matches[1]);
            return 'PT' . $minutes . 'M';
        }

        // Default times based on type
        $default_times = array(
            'prep' => 'PT15M',
            'cook' => 'PT30M',
            'total' => 'PT45M'
        );

        return $default_times[$type] ?? 'PT45M';
    }

    /**
     * Extract recipe yield (servings) from content
     */
    private function extract_recipe_yield($content)
    {
        // Look for serving information
        preg_match('/(\d+)\s*(?:serving|portion|person)/i', $content, $matches);
        if (isset($matches[1])) {
            return $matches[1] . ' servings';
        }

        preg_match('/serves\s*(\d+)/i', $content, $matches);
        if (isset($matches[1])) {
            return $matches[1] . ' servings';
        }

        // Default yield
        return '4 servings';
    }

    /**
     * Extract recipe nutrition information
     */
    private function extract_recipe_nutrition($content)
    {
        $nutrition = array(
            '@type' => 'NutritionInformation'
        );

        // Extract calories
        preg_match('/(\d+)\s*calories/i', $content, $matches);
        if (isset($matches[1])) {
            $nutrition['calories'] = $matches[1] . ' calories';
        }

        // Extract other nutrition facts
        $nutrition_patterns = array(
            'fatContent' => '/(\d+)\s*g\s*fat/i',
            'carbohydrateContent' => '/(\d+)\s*g\s*carbs/i',
            'proteinContent' => '/(\d+)\s*g\s*protein/i',
            'sugarContent' => '/(\d+)\s*g\s*sugar/i',
            'fiberContent' => '/(\d+)\s*g\s*fiber/i'
        );

        foreach ($nutrition_patterns as $key => $pattern) {
            preg_match($pattern, $content, $matches);
            if (isset($matches[1])) {
                $nutrition[$key] = $matches[1] . ' g';
            }
        }

        return count($nutrition) > 1 ? $nutrition : null;
    }

    /**
     * Get location address for events
     */
    private function get_location_address($post_id)
    {
        $address = array(
            '@type' => 'PostalAddress'
        );

        // Try to get from event meta
        $street = get_post_meta($post_id, '_event_address', true);
        $city = get_post_meta($post_id, '_event_city', true);
        $state = get_post_meta($post_id, '_event_state', true);
        $zip = get_post_meta($post_id, '_event_zip', true);
        $country = get_post_meta($post_id, '_event_country', true);

        if ($street) $address['streetAddress'] = $street;
        if ($city) $address['addressLocality'] = $city;
        if ($state) $address['addressRegion'] = $state;
        if ($zip) $address['postalCode'] = $zip;
        if ($country) $address['addressCountry'] = $country;

        // Fallback to business address
        if (empty($address['streetAddress'])) {
            $business_fields = array(
                'streetAddress' => 'business_address_street',
                'addressLocality' => 'business_address_city',
                'addressRegion' => 'business_address_region',
                'postalCode' => 'business_address_zip',
                'addressCountry' => 'business_address_country'
            );

            foreach ($business_fields as $schema_field => $option_name) {
                $value = get_option($option_name);
                if ($value) {
                    $address[$schema_field] = $value;
                }
            }
        }

        return count($address) > 1 ? $address : array(
            '@type' => 'PostalAddress',
            'addressLocality' => get_bloginfo('name')
        );
    }

    /**
     * Get business hours for LocalBusiness schema
     */
    private function get_business_hours()
    {
        $hours = array();
        $days = array('Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday', 'Sunday');

        foreach ($days as $day) {
            $open_time = get_option('business_hours_' . strtolower($day) . '_open', '09:00');
            $close_time = get_option('business_hours_' . strtolower($day) . '_close', '17:00');

            $hours[] = array(
                '@type' => 'OpeningHoursSpecification',
                'dayOfWeek' => $day,
                'opens' => $open_time,
                'closes' => $close_time
            );
        }

        return $hours;
    }

    /**
     * Get social media profiles
     */
    private function get_social_profiles()
    {
        $social_profiles = array();
        $social_platforms = array(
            'facebook' => 'business_facebook_url',
            'twitter' => 'business_twitter_url',
            'instagram' => 'business_instagram_url',
            'linkedin' => 'business_linkedin_url',
            'youtube' => 'business_youtube_url',
            'pinterest' => 'business_pinterest_url'
        );

        foreach ($social_platforms as $platform => $option_name) {
            $url = get_option($option_name);
            if ($url) {
                $social_profiles[] = $url;
            }
        }

        return $social_profiles;
    }

    /**
     * Extract HowTo cost from content
     */
    private function extract_howto_cost($content)
    {
        // Look for cost patterns
        preg_match('/(?:cost|price|budget).*?\$?(\d+(?:\.\d{2})?)/i', $content, $matches);
        if (isset($matches[1])) {
            return floatval($matches[1]);
        }

        preg_match('/\$(\d+(?:\.\d{2})?)/', $content, $matches);
        if (isset($matches[1])) {
            return floatval($matches[1]);
        }

        return 0; // Default cost
    }

    /**
     * Extract HowTo supplies from content
     */
    private function extract_howto_supplies($content)
    {
        $supplies = array();

        // Look for supplies/materials section
        if (preg_match('/(?:supplies|materials|what you need):?(.*?)(?=steps|instructions|$)/is', $content, $section)) {
            // Extract list items
            preg_match_all('/[-*]?\s*([^\n\.!?]+[\.!?])/i', $section[1], $items);

            foreach ($items[1] as $item) {
                $supplies[] = array(
                    '@type' => 'HowToSupply',
                    'name' => trim($item)
                );
            }
        }

        // If no specific section found, look for bullet points
        if (empty($supplies)) {
            preg_match_all('/[-*]\s*([^\n]+)/i', $content, $items);
            foreach (array_slice($items[1], 0, 5) as $item) { // Limit to 5 items
                $supplies[] = array(
                    '@type' => 'HowToSupply',
                    'name' => trim($item)
                );
            }
        }

        return $supplies;
    }

    /**
     * Extract HowTo tools from content
     */
    private function extract_howto_tools($content)
    {
        $tools = array();
        $tool_keywords = array('tool', 'equipment', 'machine', 'device', 'software', 'app');

        // Look for tools section
        if (preg_match('/(?:tools|equipment):?(.*?)(?=steps|instructions|$)/is', $content, $section)) {
            preg_match_all('/[-*]?\s*([^\n\.!?]+[\.!?])/i', $section[1], $items);

            foreach ($items[1] as $item) {
                $tools[] = array(
                    '@type' => 'HowToTool',
                    'name' => trim($item)
                );
            }
        }

        // Search for tool keywords in content
        if (empty($tools)) {
            $sentences = preg_split('/[.!?]+/', $content);
            foreach ($sentences as $sentence) {
                foreach ($tool_keywords as $keyword) {
                    if (stripos($sentence, $keyword) !== false) {
                        $tools[] = array(
                            '@type' => 'HowToTool',
                            'name' => trim($sentence)
                        );
                        break;
                    }
                }
                if (count($tools) >= 3) break; // Limit to 3 tools
            }
        }

        return $tools;
    }

    /**
     * Extract HowTo steps from content
     */
    private function extract_howto_steps($content)
    {
        $steps = array();

        // Method 1: Look for numbered steps
        preg_match_all('/(\d+)\.\s*([^\n\.!?]+[\.!?])/i', $content, $numbered_steps);

        if (!empty($numbered_steps[1])) {
            foreach ($numbered_steps[1] as $index => $step_number) {
                $steps[] = array(
                    '@type' => 'HowToStep',
                    'position' => intval($step_number),
                    'text' => trim($numbered_steps[2][$index])
                );
            }
        }

        // Method 2: Look for bullet points with step indicators
        if (empty($steps)) {
            preg_match_all('/[-*]\s*([Ss]tep\s*\d+[^\n]*)/i', $content, $bullet_steps);
            if (!empty($bullet_steps[1])) {
                foreach ($bullet_steps[1] as $index => $step_text) {
                    $steps[] = array(
                        '@type' => 'HowToStep',
                        'position' => $index + 1,
                        'text' => trim($step_text)
                    );
                }
            }
        }

        // Method 3: Split by paragraphs and use as steps
        if (empty($steps)) {
            $paragraphs = preg_split('/\n\s*\n/', $content);
            foreach (array_slice($paragraphs, 0, 10) as $index => $paragraph) { // Limit to 10 steps
                $clean_paragraph = wp_strip_all_tags(trim($paragraph));
                if (strlen($clean_paragraph) > 10) { // Only use substantial paragraphs
                    $steps[] = array(
                        '@type' => 'HowToStep',
                        'position' => $index + 1,
                        'text' => $clean_paragraph
                    );
                }
            }
        }

        return $steps;
    }

    /**
     * Check if post has product metadata
     */
    private function has_product_metadata($post_id)
    {
        // Check for common product meta fields
        $product_meta_fields = array('_price', '_sku', '_stock_status', '_regular_price', '_sale_price');

        foreach ($product_meta_fields as $field) {
            if (get_post_meta($post_id, $field, true)) {
                return true;
            }
        }

        // Check for WooCommerce product type
        if (get_post_meta($post_id, '_product_type', true)) {
            return true;
        }

        return false;
    }

    /**
     * Get additional social profiles with fallbacks
     */
    private function get_social_profiles_extended()
    {
        $profiles = $this->get_social_profiles();

        // Add additional fallback profiles
        $additional_profiles = array(
            get_option('business_github_url'),
            get_option('business_dribbble_url'),
            get_option('business_behance_url')
        );

        foreach ($additional_profiles as $profile) {
            if ($profile) {
                $profiles[] = $profile;
            }
        }

        return array_filter($profiles); // Remove empty values
    }

    /**
     * Enhanced recipe time extraction with multiple patterns
     */
    private function extract_recipe_time_enhanced($content, $type = 'total')
    {
        $time_patterns = array(
            'prep' => array(
                '/prep.*?time.*?(\d+)/i',
                '/preparation.*?(\d+)/i',
                '/prep.*?(\d+)\s*min/i'
            ),
            'cook' => array(
                '/cook.*?time.*?(\d+)/i',
                '/cooking.*?(\d+)/i',
                '/cook.*?(\d+)\s*min/i'
            ),
            'total' => array(
                '/total.*?time.*?(\d+)/i',
                '/ready in.*?(\d+)/i',
                '/takes.*?(\d+)\s*min/i'
            )
        );

        $patterns = $time_patterns[$type] ?? $time_patterns['total'];

        foreach ($patterns as $pattern) {
            preg_match($pattern, $content, $matches);
            if (isset($matches[1])) {
                $minutes = intval($matches[1]);
                return 'PT' . $minutes . 'M';
            }
        }

        // Fallback to default times
        $default_times = array(
            'prep' => 'PT15M',
            'cook' => 'PT30M',
            'total' => 'PT45M'
        );

        return $default_times[$type] ?? 'PT45M';
    }
}
