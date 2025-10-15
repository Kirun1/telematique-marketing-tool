<?php
class ProductScraper_Social_Optimizer
{
    private $default_image;
    private $site_name;
    private $twitter_handle;
    private $facebook_app_id;

    public function __construct()
    {
        $this->site_name = get_bloginfo('name');
        $this->default_image = $this->get_default_social_image();
        $this->twitter_handle = get_option('social_twitter_handle', '');
        $this->facebook_app_id = get_option('social_facebook_app_id', '');
    }

    public function generate_social_meta($post_id)
    {
        return array(
            'facebook' => array(
                'title' => $this->get_facebook_title($post_id),
                'description' => $this->get_facebook_description($post_id),
                'image' => $this->get_facebook_image($post_id),
                'url' => get_permalink($post_id),
                'type' => $this->get_facebook_type($post_id),
                'site_name' => $this->site_name,
                'app_id' => $this->facebook_app_id
            ),
            'twitter' => array(
                'card' => $this->get_twitter_card_type($post_id),
                'title' => $this->get_twitter_title($post_id),
                'description' => $this->get_twitter_description($post_id),
                'image' => $this->get_twitter_image($post_id),
                'site' => $this->twitter_handle,
                'creator' => $this->get_twitter_creator($post_id)
            ),
            'linkedin' => array(
                'title' => $this->get_linkedin_title($post_id),
                'description' => $this->get_linkedin_description($post_id),
                'image' => $this->get_linkedin_image($post_id),
                'url' => get_permalink($post_id)
            ),
            'pinterest' => array(
                'title' => $this->get_pinterest_title($post_id),
                'description' => $this->get_pinterest_description($post_id),
                'image' => $this->get_pinterest_image($post_id)
            ),
            'whatsapp' => array(
                'title' => $this->get_whatsapp_title($post_id),
                'description' => $this->get_whatsapp_description($post_id)
            )
        );
    }

    /**
     * Get Facebook Open Graph title
     */
    private function get_facebook_title($post_id)
    {
        // Check for custom Facebook title
        $fb_title = get_post_meta($post_id, '_facebook_title', true);
        if (!empty($fb_title)) {
            return $this->trim_text($fb_title, 100);
        }

        // Check for Yoast SEO Facebook title
        $yoast_fb_title = get_post_meta($post_id, '_yoast_wpseo_opengraph-title', true);
        if (!empty($yoast_fb_title)) {
            return $this->trim_text($yoast_fb_title, 100);
        }

        // Check for All in One SEO title
        $aioseop_title = get_post_meta($post_id, '_aioseop_opengraph_settings', true);
        if ($aioseop_title && is_string($aioseop_title)) {
            $aioseop_data = maybe_unserialize($aioseop_title);
            if (is_array($aioseop_data) && !empty($aioseop_data['aioseop_opengraph_settings_title'])) {
                return $this->trim_text($aioseop_data['aioseop_opengraph_settings_title'], 100);
            }
        }

        // Use SEO title or post title
        $seo_title = get_post_meta($post_id, '_seo_title', true);
        if (empty($seo_title)) {
            $seo_title = get_the_title($post_id);
        }

        return $this->trim_text($seo_title, 100);
    }

    /**
     * Get Facebook Open Graph description
     */
    private function get_facebook_description($post_id)
    {
        // Check for custom Facebook description
        $fb_desc = get_post_meta($post_id, '_facebook_description', true);
        if (!empty($fb_desc)) {
            return $this->trim_text($fb_desc, 300);
        }

        // Check for Yoast SEO Facebook description
        $yoast_fb_desc = get_post_meta($post_id, '_yoast_wpseo_opengraph-description', true);
        if (!empty($yoast_fb_desc)) {
            return $this->trim_text($yoast_fb_desc, 300);
        }

        // Check for All in One SEO description
        $aioseop_desc = get_post_meta($post_id, '_aioseop_opengraph_settings', true);
        if ($aioseop_desc && is_string($aioseop_desc)) {
            $aioseop_data = maybe_unserialize($aioseop_desc);
            if (is_array($aioseop_data) && !empty($aioseop_data['aioseop_opengraph_settings_desc'])) {
                return $this->trim_text($aioseop_data['aioseop_opengraph_settings_desc'], 300);
            }
        }

        // Use SEO description or excerpt
        $seo_desc = get_post_meta($post_id, '_meta_description', true);
        if (empty($seo_desc)) {
            $seo_desc = get_the_excerpt($post_id);
        }

        if (empty($seo_desc)) {
            $post = get_post($post_id);
            $seo_desc = wp_trim_words(wp_strip_all_tags($post->post_content), 30);
        }

        return $this->trim_text($seo_desc, 300);
    }

    /**
     * Get Facebook Open Graph image
     */
    private function get_facebook_image($post_id)
    {
        // Check for custom Facebook image
        $fb_image = get_post_meta($post_id, '_facebook_image', true);
        if (!empty($fb_image)) {
            return $this->get_image_url($fb_image);
        }

        // Check for Yoast SEO Facebook image
        $yoast_fb_image = get_post_meta($post_id, '_yoast_wpseo_opengraph-image', true);
        if (!empty($yoast_fb_image)) {
            return $this->get_image_url($yoast_fb_image);
        }

        // Check for All in One SEO image
        $aioseop_image = get_post_meta($post_id, '_aioseop_opengraph_settings', true);
        if ($aioseop_image && is_string($aioseop_image)) {
            $aioseop_data = maybe_unserialize($aioseop_image);
            if (is_array($aioseop_data) && !empty($aioseop_data['aioseop_opengraph_settings_image'])) {
                return $this->get_image_url($aioseop_data['aioseop_opengraph_settings_image']);
            }
        }

        // Use featured image
        $featured_image = get_post_thumbnail_id($post_id);
        if ($featured_image) {
            $image_url = wp_get_attachment_image_url($featured_image, 'large');
            if ($image_url) {
                return $image_url;
            }
        }

        // Use first image from content
        $content_image = $this->get_first_content_image($post_id);
        if ($content_image) {
            return $content_image;
        }

        // Use default social image
        return $this->default_image;
    }

    /**
     * Get Facebook Open Graph type
     */
    private function get_facebook_type($post_id)
    {
        $post_type = get_post_type($post_id);

        switch ($post_type) {
            case 'post':
                return 'article';
            case 'product':
                return 'product';
            case 'page':
                if (is_front_page()) {
                    return 'website';
                }
                return 'article';
            default:
                return 'article';
        }
    }

    /**
     * Get Twitter Card title
     */
    private function get_twitter_title($post_id)
    {
        // Check for custom Twitter title
        $twitter_title = get_post_meta($post_id, '_twitter_title', true);
        if (!empty($twitter_title)) {
            return $this->trim_text($twitter_title, 70);
        }

        // Check for Yoast SEO Twitter title
        $yoast_twitter_title = get_post_meta($post_id, '_yoast_wpseo_twitter-title', true);
        if (!empty($yoast_twitter_title)) {
            return $this->trim_text($yoast_twitter_title, 70);
        }

        // Use Facebook title or fall back to regular title
        return $this->get_facebook_title($post_id);
    }

    /**
     * Get Twitter Card description
     */
    private function get_twitter_description($post_id)
    {
        // Check for custom Twitter description
        $twitter_desc = get_post_meta($post_id, '_twitter_description', true);
        if (!empty($twitter_desc)) {
            return $this->trim_text($twitter_desc, 200);
        }

        // Check for Yoast SEO Twitter description
        $yoast_twitter_desc = get_post_meta($post_id, '_yoast_wpseo_twitter-description', true);
        if (!empty($yoast_twitter_desc)) {
            return $this->trim_text($yoast_twitter_desc, 200);
        }

        // Use Facebook description
        return $this->get_facebook_description($post_id);
    }

    /**
     * Get Twitter Card image
     */
    private function get_twitter_image($post_id)
    {
        // Check for custom Twitter image
        $twitter_image = get_post_meta($post_id, '_twitter_image', true);
        if (!empty($twitter_image)) {
            return $this->get_image_url($twitter_image);
        }

        // Check for Yoast SEO Twitter image
        $yoast_twitter_image = get_post_meta($post_id, '_yoast_wpseo_twitter-image', true);
        if (!empty($yoast_twitter_image)) {
            return $this->get_image_url($yoast_twitter_image);
        }

        // Use Facebook image
        return $this->get_facebook_image($post_id);
    }

    /**
     * Get Twitter Card type
     */
    private function get_twitter_card_type($post_id)
    {
        $image = $this->get_twitter_image($post_id);

        // Check if we have a large enough image for summary_large_image
        if ($image && $this->is_image_large_enough($image)) {
            return 'summary_large_image';
        }

        return 'summary';
    }

    /**
     * Get Twitter creator handle
     */
    private function get_twitter_creator($post_id)
    {
        $author_id = get_post_field('post_author', $post_id);
        $author_twitter = get_the_author_meta('twitter', $author_id);

        if (!empty($author_twitter)) {
            return $this->clean_twitter_handle($author_twitter);
        }

        return $this->twitter_handle;
    }

    /**
     * Get LinkedIn title
     */
    private function get_linkedin_title($post_id)
    {
        // LinkedIn typically uses the same title as Facebook
        return $this->get_facebook_title($post_id);
    }

    /**
     * Get LinkedIn description
     */
    private function get_linkedin_description($post_id)
    {
        // LinkedIn prefers slightly longer descriptions
        $desc = $this->get_facebook_description($post_id);
        return $this->trim_text($desc, 256);
    }

    /**
     * Get LinkedIn image
     */
    private function get_linkedin_image($post_id)
    {
        // LinkedIn uses the same image requirements as Facebook
        return $this->get_facebook_image($post_id);
    }

    /**
     * Get Pinterest title
     */
    private function get_pinterest_title($post_id)
    {
        // Check for custom Pinterest title
        $pinterest_title = get_post_meta($post_id, '_pinterest_title', true);
        if (!empty($pinterest_title)) {
            return $this->trim_text($pinterest_title, 100);
        }

        // Pinterest prefers descriptive, engaging titles
        $title = $this->get_facebook_title($post_id);
        return $this->optimize_for_pinterest($title);
    }

    /**
     * Get Pinterest description
     */
    private function get_pinterest_description($post_id)
    {
        // Check for custom Pinterest description
        $pinterest_desc = get_post_meta($post_id, '_pinterest_description', true);
        if (!empty($pinterest_desc)) {
            return $this->trim_text($pinterest_desc, 500);
        }

        // Pinterest allows longer descriptions
        $desc = $this->get_facebook_description($post_id);
        $enhanced_desc = $this->enhance_pinterest_description($desc, $post_id);
        return $this->trim_text($enhanced_desc, 500);
    }

    /**
     * Get Pinterest image
     */
    private function get_pinterest_image($post_id)
    {
        // Check for custom Pinterest image
        $pinterest_image = get_post_meta($post_id, '_pinterest_image', true);
        if (!empty($pinterest_image)) {
            return $this->get_image_url($pinterest_image);
        }

        // Pinterest prefers vertical images
        $vertical_image = $this->get_vertical_image($post_id);
        if ($vertical_image) {
            return $vertical_image;
        }

        // Fall back to Facebook image
        return $this->get_facebook_image($post_id);
    }

    /**
     * Get WhatsApp title
     */
    private function get_whatsapp_title($post_id)
    {
        // WhatsApp shares typically show shorter titles
        $title = $this->get_facebook_title($post_id);
        return $this->trim_text($title, 60);
    }

    /**
     * Get WhatsApp description
     */
    private function get_whatsapp_description($post_id)
    {
        // WhatsApp shows limited description in preview
        $desc = $this->get_facebook_description($post_id);
        return $this->trim_text($desc, 120);
    }

    /**
     * Helper methods
     */

    /**
     * Trim text to specific length
     */
    private function trim_text($text, $length)
    {
        $text = wp_strip_all_tags($text);
        if (strlen($text) <= $length) {
            return $text;
        }

        $text = substr($text, 0, $length);
        $last_space = strrpos($text, ' ');

        if ($last_space !== false) {
            $text = substr($text, 0, $last_space);
        }

        return $text . '...';
    }

    /**
     * Get image URL from various input types
     */
    private function get_image_url($image)
    {
        if (is_numeric($image)) {
            // Image ID
            $image_url = wp_get_attachment_image_url($image, 'large');
            if ($image_url) {
                return $image_url;
            }
        } elseif (filter_var($image, FILTER_VALIDATE_URL)) {
            // Full URL
            return $image;
        } elseif (strpos($image, '/') === 0) {
            // Relative path
            return home_url($image);
        }

        return $image;
    }

    /**
     * Get default social image
     */
    private function get_default_social_image()
    {
        // Check for custom default social image
        $default_image = get_option('social_default_image');
        if (!empty($default_image)) {
            return $this->get_image_url($default_image);
        }

        // Check for site logo
        $custom_logo_id = get_theme_mod('custom_logo');
        if ($custom_logo_id) {
            $logo_url = wp_get_attachment_image_url($custom_logo_id, 'large');
            if ($logo_url) {
                return $logo_url;
            }
        }

        // Use a placeholder or site screenshot
        return get_template_directory_uri() . '/assets/images/social-default.jpg';
    }

    /**
     * Get first image from post content
     */
    private function get_first_content_image($post_id)
    {
        $post = get_post($post_id);
        $content = $post->post_content;

        // Look for images in content
        preg_match_all('/<img[^>]+src="([^">]+)"/', $content, $matches);

        if (!empty($matches[1])) {
            foreach ($matches[1] as $image_url) {
                // Convert relative URLs to absolute
                if (strpos($image_url, 'http') !== 0) {
                    $image_url = home_url($image_url);
                }

                // Check if image is large enough for social sharing
                if ($this->is_image_large_enough($image_url)) {
                    return $image_url;
                }
            }
        }

        return false;
    }

    /**
     * Check if image meets minimum size requirements
     */
    private function is_image_large_enough($image_url)
    {
        // For social media, we want images that are at least 200x200 pixels
        // In a production environment, you might want to check actual dimensions
        // For now, we'll assume most images are sufficient
        return true;
    }

    /**
     * Clean Twitter handle
     */
    private function clean_twitter_handle($handle)
    {
        $handle = trim($handle);
        $handle = ltrim($handle, '@');
        return $handle;
    }

    /**
     * Optimize title for Pinterest
     */
    private function optimize_for_pinterest($title)
    {
        // Add Pinterest-friendly elements
        $keywords = array('DIY', 'How to', 'Tutorial', 'Guide', 'Tips', 'Ideas', 'Inspiration');

        // Check if title already contains Pinterest-friendly words
        $has_pinterest_keyword = false;
        foreach ($keywords as $keyword) {
            if (stripos($title, $keyword) !== false) {
                $has_pinterest_keyword = true;
                break;
            }
        }

        if (!$has_pinterest_keyword) {
            $keyword = $keywords[array_rand($keywords)];
            $title = $keyword . ': ' . $title;
        }

        return $title;
    }

    /**
     * Enhance description for Pinterest
     */
    private function enhance_pinterest_description($description, $post_id)
    {
        $post = get_post($post_id);

        // Add call to action for Pinterest
        $cta_phrases = array(
            'Pin this for later!',
            'Save this idea!',
            'Perfect for Pinterest!',
            'Pin-worthy content!'
        );

        $cta = $cta_phrases[array_rand($cta_phrases)];

        // Add relevant hashtags for Pinterest
        $hashtags = $this->generate_pinterest_hashtags($post_id);

        $enhanced_description = $description . " " . $cta . " " . $hashtags;

        return $enhanced_description;
    }

    /**
     * Generate Pinterest hashtags
     */
    private function generate_pinterest_hashtags($post_id)
    {
        $hashtags = array();

        // Get categories as hashtags
        $categories = get_the_category($post_id);
        foreach ($categories as $category) {
            $hashtags[] = '#' . str_replace(' ', '', $category->name);
        }

        // Get tags as hashtags
        $tags = get_the_tags($post_id);
        if ($tags) {
            foreach ($tags as $tag) {
                $hashtags[] = '#' . str_replace(' ', '', $tag->name);
            }
        }

        // Add some general Pinterest hashtags
        $general_hashtags = array('#pinterest', '#ideas', '#inspiration', '#diy');
        $hashtags = array_merge($hashtags, $general_hashtags);

        // Limit to 10 hashtags
        $hashtags = array_slice($hashtags, 0, 10);

        return implode(' ', $hashtags);
    }

    /**
     * Get vertical image for Pinterest
     */
    private function get_vertical_image($post_id)
    {
        // Look for images with vertical orientation in gallery
        $gallery_images = get_post_meta($post_id, '_product_image_gallery', true);
        if ($gallery_images) {
            $image_ids = explode(',', $gallery_images);
            foreach ($image_ids as $image_id) {
                $image_url = wp_get_attachment_image_url($image_id, 'large');
                if ($image_url && $this->is_vertical_image($image_id)) {
                    return $image_url;
                }
            }
        }

        // Check featured image
        $featured_image_id = get_post_thumbnail_id($post_id);
        if ($featured_image_id && $this->is_vertical_image($featured_image_id)) {
            return wp_get_attachment_image_url($featured_image_id, 'large');
        }

        return false;
    }

    /**
     * Check if image is vertical orientation
     */
    private function is_vertical_image($image_id)
    {
        $metadata = wp_get_attachment_metadata($image_id);
        if ($metadata && isset($metadata['width']) && isset($metadata['height'])) {
            $ratio = $metadata['height'] / $metadata['width'];
            return $ratio > 1.2; // Height is at least 20% more than width
        }

        return false;
    }

    /**
     * Generate social meta HTML tags
     */
    public function generate_social_meta_tags($post_id)
    {
        $social_meta = $this->generate_social_meta($post_id);
        $html_tags = array();

        // Basic meta tags
        $html_tags[] = '<meta property="og:site_name" content="' . esc_attr($this->site_name) . '">';
        $html_tags[] = '<meta property="og:locale" content="' . get_locale() . '">';

        // Facebook Open Graph tags
        $html_tags[] = '<meta property="og:title" content="' . esc_attr($social_meta['facebook']['title']) . '">';
        $html_tags[] = '<meta property="og:description" content="' . esc_attr($social_meta['facebook']['description']) . '">';
        $html_tags[] = '<meta property="og:url" content="' . esc_url($social_meta['facebook']['url']) . '">';
        $html_tags[] = '<meta property="og:type" content="' . esc_attr($social_meta['facebook']['type']) . '">';
        $html_tags[] = '<meta property="og:image" content="' . esc_url($social_meta['facebook']['image']) . '">';

        if (!empty($social_meta['facebook']['app_id'])) {
            $html_tags[] = '<meta property="fb:app_id" content="' . esc_attr($social_meta['facebook']['app_id']) . '">';
        }

        // Twitter Card tags
        $html_tags[] = '<meta name="twitter:card" content="' . esc_attr($social_meta['twitter']['card']) . '">';
        $html_tags[] = '<meta name="twitter:title" content="' . esc_attr($social_meta['twitter']['title']) . '">';
        $html_tags[] = '<meta name="twitter:description" content="' . esc_attr($social_meta['twitter']['description']) . '">';
        $html_tags[] = '<meta name="twitter:image" content="' . esc_url($social_meta['twitter']['image']) . '">';

        if (!empty($social_meta['twitter']['site'])) {
            $html_tags[] = '<meta name="twitter:site" content="@' . esc_attr($social_meta['twitter']['site']) . '">';
        }

        if (!empty($social_meta['twitter']['creator'])) {
            $html_tags[] = '<meta name="twitter:creator" content="@' . esc_attr($social_meta['twitter']['creator']) . '">';
        }

        // Additional image meta
        $html_tags[] = '<meta property="og:image:width" content="1200">';
        $html_tags[] = '<meta property="og:image:height" content="630">';

        return implode("\n", $html_tags);
    }

    /**
     * Validate social meta data
     */
    public function validate_social_meta($post_id)
    {
        $social_meta = $this->generate_social_meta($post_id);
        $issues = array();

        // Check Facebook title
        if (empty($social_meta['facebook']['title'])) {
            $issues[] = 'Facebook title is empty';
        } elseif (strlen($social_meta['facebook']['title']) > 100) {
            $issues[] = 'Facebook title is too long';
        }

        // Check Facebook description
        if (empty($social_meta['facebook']['description'])) {
            $issues[] = 'Facebook description is empty';
        } elseif (strlen($social_meta['facebook']['description']) > 300) {
            $issues[] = 'Facebook description is too long';
        }

        // Check Facebook image
        if (empty($social_meta['facebook']['image'])) {
            $issues[] = 'Facebook image is missing';
        } elseif ($social_meta['facebook']['image'] === $this->default_image) {
            $issues[] = 'Using default social image - consider adding a custom image';
        }

        // Check Twitter title
        if (empty($social_meta['twitter']['title'])) {
            $issues[] = 'Twitter title is empty';
        } elseif (strlen($social_meta['twitter']['title']) > 70) {
            $issues[] = 'Twitter title is too long';
        }

        return array(
            'valid' => empty($issues),
            'issues' => $issues,
            'social_meta' => $social_meta
        );
    }

    /**
     * Generate social sharing URLs
     */
    public function generate_sharing_urls($post_id)
    {
        $post_url = urlencode(get_permalink($post_id));
        $post_title = urlencode(get_the_title($post_id));

        return array(
            'facebook' => 'https://www.facebook.com/sharer/sharer.php?u=' . $post_url,
            'twitter' => 'https://twitter.com/intent/tweet?text=' . $post_title . '&url=' . $post_url,
            'linkedin' => 'https://www.linkedin.com/sharing/share-offsite/?url=' . $post_url,
            'pinterest' => 'https://pinterest.com/pin/create/button/?url=' . $post_url . '&description=' . $post_title,
            'whatsapp' => 'https://api.whatsapp.com/send?text=' . $post_title . ' ' . $post_url
        );
    }
}
