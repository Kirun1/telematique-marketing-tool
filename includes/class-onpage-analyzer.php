<?php

class ProductScraper_OnPage_Analyzer
{
    private $focus_keyword;

    public function __construct($focus_keyword = '')
    {
        $this->focus_keyword = $focus_keyword;
    }

    public function calculate_seo_score($post_id)
    {
        $checks = array(
            'title_length' => $this->check_title_length($post_id),
            'meta_description' => $this->check_meta_description($post_id),
            'content_length' => $this->check_content_length($post_id),
            'keyword_usage' => $this->check_keyword_usage($post_id),
            'images_alt' => $this->check_images_alt($post_id),
            'internal_links' => $this->check_internal_links($post_id),
            'external_links' => $this->check_external_links($post_id),
            'headings' => $this->check_headings($post_id),
            'readability' => $this->check_readability($post_id),
            'url_structure' => $this->check_url_structure($post_id)
        );

        return $this->calculate_total_score($checks);
    }

    /**
     * Check if title length is optimal (50-60 characters)
     */
    public function check_title_length($post_id)
    {
        $title = get_the_title($post_id);
        $title_length = mb_strlen($title);

        $score = 0;
        $feedback = '';

        if ($title_length >= 50 && $title_length <= 60) {
            $score = 10;
            $feedback = 'Perfect! Title length is optimal for SEO.';
        } elseif ($title_length >= 40 && $title_length <= 70) {
            $score = 7;
            $feedback = 'Good. Title length is acceptable.';
        } elseif ($title_length > 70) {
            $score = 3;
            $feedback = 'Title is too long. Consider shortening to under 60 characters.';
        } else {
            $score = 2;
            $feedback = 'Title is too short. Aim for 50-60 characters.';
        }

        return array(
            'score' => $score,
            'max_score' => 10,
            'feedback' => $feedback,
            'data' => array(
                'length' => $title_length,
                'title' => $title
            )
        );
    }

    /**
     * Check if meta description exists and has proper length
     */
    public function check_meta_description($post_id)
    {
        $meta_description = get_post_meta($post_id, '_meta_description', true);

        if (empty($meta_description)) {
            // Try to get from Yoast or other SEO plugins
            $meta_description = get_post_meta($post_id, '_yoast_wpseo_metadesc', true);
        }

        if (empty($meta_description)) {
            // Generate from content excerpt
            $post = get_post($post_id);
            $meta_description = wp_trim_words(wp_strip_all_tags($post->post_content), 25);
        }

        $description_length = mb_strlen($meta_description);
        $score = 0;
        $feedback = '';

        if ($description_length >= 120 && $description_length <= 160) {
            $score = 10;
            $feedback = 'Excellent! Meta description length is perfect.';
        } elseif ($description_length >= 100 && $description_length <= 180) {
            $score = 7;
            $feedback = 'Good. Meta description length is acceptable.';
        } elseif ($description_length > 180) {
            $score = 4;
            $feedback = 'Meta description is too long. Keep it under 160 characters.';
        } elseif ($description_length < 100) {
            $score = 3;
            $feedback = 'Meta description is too short. Aim for 120-160 characters.';
        } else {
            $score = 0;
            $feedback = 'No meta description found.';
        }

        // Check if focus keyword is in meta description
        $keyword_in_meta = false;
        if (!empty($this->focus_keyword) && !empty($meta_description)) {
            $keyword_in_meta = stripos($meta_description, $this->focus_keyword) !== false;
            if ($keyword_in_meta) {
                $score = min(10, $score + 2);
                $feedback .= ' Focus keyword included.';
            }
        }

        return array(
            'score' => $score,
            'max_score' => 10,
            'feedback' => $feedback,
            'data' => array(
                'length' => $description_length,
                'content' => $meta_description,
                'keyword_included' => $keyword_in_meta
            )
        );
    }

    /**
     * Check if content has sufficient length
     */
    public function check_content_length($post_id)
    {
        $post = get_post($post_id);
        $content = wp_strip_all_tags($post->post_content);
        $word_count = str_word_count($content);

        $score = 0;
        $feedback = '';

        if ($word_count >= 1500) {
            $score = 10;
            $feedback = 'Excellent! Content length is substantial.';
        } elseif ($word_count >= 1000) {
            $score = 8;
            $feedback = 'Very good. Content has sufficient depth.';
        } elseif ($word_count >= 600) {
            $score = 6;
            $feedback = 'Good. Content length is adequate.';
        } elseif ($word_count >= 300) {
            $score = 4;
            $feedback = 'Fair. Consider adding more content.';
        } else {
            $score = 2;
            $feedback = 'Content is too short. Aim for at least 300 words.';
        }

        return array(
            'score' => $score,
            'max_score' => 10,
            'feedback' => $feedback,
            'data' => array(
                'word_count' => $word_count,
                'character_count' => strlen($content)
            )
        );
    }

    /**
     * Check keyword usage throughout content
     */
    public function check_keyword_usage($post_id)
    {
        if (empty($this->focus_keyword)) {
            return array(
                'score' => 5,
                'max_score' => 10,
                'feedback' => 'No focus keyword set. Set a focus keyword for better analysis.',
                'data' => array()
            );
        }

        $post = get_post($post_id);
        $content = wp_strip_all_tags($post->post_content);
        $title = get_the_title($post_id);

        $keyword = strtolower($this->focus_keyword);
        $content_lower = strtolower($content);
        $title_lower = strtolower($title);

        // Count keyword occurrences
        $keyword_count = substr_count($content_lower, $keyword);
        $word_count = str_word_count($content);

        // Calculate keyword density
        $density = $word_count > 0 ? ($keyword_count / $word_count) * 100 : 0;

        // Check keyword in title
        $keyword_in_title = stripos($title, $this->focus_keyword) !== false;

        // Check keyword in first paragraph
        $first_paragraph = $this->get_first_paragraph($post->post_content);
        $keyword_in_first_paragraph = stripos(wp_strip_all_tags($first_paragraph), $this->focus_keyword) !== false;

        // Check keyword in headings
        $keyword_in_headings = $this->check_keyword_in_headings($post->post_content, $keyword);

        $score = 0;
        $feedback = '';

        // Score based on density
        if ($density >= 0.5 && $density <= 2.5) {
            $score += 4;
            $feedback .= 'Good keyword density. ';
        } elseif ($density > 2.5) {
            $score += 1;
            $feedback .= 'Keyword density too high - avoid keyword stuffing. ';
        } else {
            $feedback .= 'Low keyword density - consider using focus keyword more. ';
        }

        // Score based on placement
        if ($keyword_in_title) {
            $score += 2;
            $feedback .= 'Keyword in title. ';
        } else {
            $feedback .= 'Consider adding keyword to title. ';
        }

        if ($keyword_in_first_paragraph) {
            $score += 2;
            $feedback .= 'Keyword in first paragraph. ';
        } else {
            $feedback .= 'Consider adding keyword to first paragraph. ';
        }

        if ($keyword_in_headings) {
            $score += 2;
            $feedback .= 'Keyword in headings. ';
        } else {
            $feedback .= 'Consider adding keyword to headings. ';
        }

        return array(
            'score' => $score,
            'max_score' => 10,
            'feedback' => trim($feedback),
            'data' => array(
                'keyword_count' => $keyword_count,
                'keyword_density' => round($density, 2),
                'keyword_in_title' => $keyword_in_title,
                'keyword_in_first_paragraph' => $keyword_in_first_paragraph,
                'keyword_in_headings' => $keyword_in_headings
            )
        );
    }

    /**
     * Check if images have alt text
     */
    public function check_images_alt($post_id)
    {
        $post = get_post($post_id);
        $content = $post->post_content;

        // Extract images from content
        preg_match_all('/<img[^>]+>/i', $content, $images);
        $total_images = count($images[0]);

        $images_with_alt = 0;
        $images_with_keyword_alt = 0;

        foreach ($images[0] as $img_tag) {
            preg_match('/alt=["\']([^"\']*)["\']/i', $img_tag, $alt_match);
            $alt_text = isset($alt_match[1]) ? $alt_match[1] : '';

            if (!empty($alt_text)) {
                $images_with_alt++;

                // Check if focus keyword is in alt text
                if (!empty($this->focus_keyword) && stripos($alt_text, $this->focus_keyword) !== false) {
                    $images_with_keyword_alt++;
                }
            }
        }

        $score = 0;
        $feedback = '';

        if ($total_images === 0) {
            $score = 5;
            $feedback = 'No images found in content. Consider adding relevant images.';
        } elseif ($images_with_alt === $total_images) {
            $score = 10;
            $feedback = 'Perfect! All images have alt text.';
        } elseif ($images_with_alt > 0) {
            $percentage = ($images_with_alt / $total_images) * 100;
            $score = round(($percentage / 100) * 8);
            $feedback = "{$images_with_alt} of {$total_images} images have alt text.";
        } else {
            $score = 0;
            $feedback = 'No images have alt text. This hurts accessibility and SEO.';
        }

        if ($images_with_keyword_alt > 0 && !empty($this->focus_keyword)) {
            $feedback .= " {$images_with_keyword_alt} images include focus keyword in alt text.";
        }

        return array(
            'score' => $score,
            'max_score' => 10,
            'feedback' => $feedback,
            'data' => array(
                'total_images' => $total_images,
                'images_with_alt' => $images_with_alt,
                'images_with_keyword_alt' => $images_with_keyword_alt
            )
        );
    }

    /**
     * Check for internal links
     */
    public function check_internal_links($post_id)
    {
        $post = get_post($post_id);
        $content = $post->post_content;

        // Extract all links
        preg_match_all('/<a[^>]+href=([\'"])(?<url>.*?)\1[^>]*>/i', $content, $links);
        $all_links = $links['url'] ?? array();

        $internal_links = 0;
        $external_links = 0;
        $site_url = site_url();

        foreach ($all_links as $link) {
            if (strpos($link, $site_url) !== false || strpos($link, '/') === 0) {
                $internal_links++;
            } else {
                $external_links++;
            }
        }

        $score = 0;
        $feedback = '';

        if ($internal_links >= 3) {
            $score = 10;
            $feedback = 'Excellent internal linking!';
        } elseif ($internal_links >= 1) {
            $score = 6;
            $feedback = 'Good internal linking.';
        } else {
            $score = 2;
            $feedback = 'No internal links found. Link to other relevant content on your site.';
        }

        return array(
            'score' => $score,
            'max_score' => 10,
            'feedback' => $feedback,
            'data' => array(
                'internal_links' => $internal_links,
                'external_links' => $external_links,
                'total_links' => count($all_links)
            )
        );
    }

    /**
     * Check for external links
     */
    public function check_external_links($post_id)
    {
        $post = get_post($post_id);
        $content = $post->post_content;

        preg_match_all('/<a[^>]+href=([\'"])(?<url>.*?)\1[^>]*>/i', $content, $links);
        $all_links = $links['url'] ?? array();

        $external_links = 0;
        $site_url = site_url();

        foreach ($all_links as $link) {
            if (strpos($link, $site_url) === false && strpos($link, '/') !== 0) {
                $external_links++;
            }
        }

        $score = 0;
        $feedback = '';

        if ($external_links >= 2) {
            $score = 8;
            $feedback = 'Good external linking to authoritative sources.';
        } elseif ($external_links >= 1) {
            $score = 5;
            $feedback = 'Some external links present.';
        } else {
            $score = 3;
            $feedback = 'Consider adding external links to authoritative sources.';
        }

        return array(
            'score' => $score,
            'max_score' => 10,
            'feedback' => $feedback,
            'data' => array(
                'external_links' => $external_links
            )
        );
    }

    /**
     * Check heading structure
     */
    public function check_headings($post_id)
    {
        $post = get_post($post_id);
        $content = $post->post_content;

        // Extract headings
        preg_match_all('/<h([1-6])[^>]*>(.*?)<\/h\1>/i', $content, $headings);
        $heading_levels = $headings[1] ?? array();
        $heading_texts = $headings[2] ?? array();

        $heading_structure = array();
        foreach ($heading_levels as $index => $level) {
            $heading_structure[] = array(
                'level' => $level,
                'text' => wp_strip_all_tags($heading_texts[$index])
            );
        }

        $has_h1 = false;
        $h1_count = 0;
        $proper_structure = true;
        $current_level = 1;

        foreach ($heading_structure as $heading) {
            if ($heading['level'] == 1) {
                $has_h1 = true;
                $h1_count++;
            }

            // Check for proper hierarchy (no skipping levels)
            if ($heading['level'] > $current_level + 1) {
                $proper_structure = false;
            }
            $current_level = $heading['level'];
        }

        $score = 0;
        $feedback = '';

        // Check for H1
        if (!$has_h1) {
            $feedback .= 'No H1 heading found. ';
            $score += 0;
        } elseif ($h1_count > 1) {
            $feedback .= 'Multiple H1 headings found. Use only one H1 per page. ';
            $score += 3;
        } else {
            $feedback .= 'Good H1 structure. ';
            $score += 4;
        }

        // Check heading hierarchy
        if ($proper_structure && count($heading_structure) > 0) {
            $feedback .= 'Proper heading hierarchy. ';
            $score += 4;
        } elseif (count($heading_structure) > 0) {
            $feedback .= 'Heading hierarchy could be improved. ';
            $score += 2;
        } else {
            $feedback .= 'No headings found. Add headings to structure your content. ';
            $score += 0;
        }

        // Check if focus keyword is in headings
        $keyword_in_headings = false;
        if (!empty($this->focus_keyword)) {
            foreach ($heading_structure as $heading) {
                if (stripos($heading['text'], $this->focus_keyword) !== false) {
                    $keyword_in_headings = true;
                    break;
                }
            }
            if ($keyword_in_headings) {
                $score += 2;
                $feedback .= 'Focus keyword found in headings. ';
            }
        }

        return array(
            'score' => min(10, $score),
            'max_score' => 10,
            'feedback' => trim($feedback),
            'data' => array(
                'total_headings' => count($heading_structure),
                'has_h1' => $has_h1,
                'h1_count' => $h1_count,
                'proper_hierarchy' => $proper_structure,
                'keyword_in_headings' => $keyword_in_headings,
                'structure' => $heading_structure
            )
        );
    }

    /**
     * Check content readability
     */
    public function check_readability($post_id)
    {
        $post = get_post($post_id);
        $content = wp_strip_all_tags($post->post_content);

        // Calculate Flesch Reading Ease score
        $words = str_word_count($content);
        $sentences = preg_split('/[.!?]+/', $content, -1, PREG_SPLIT_NO_EMPTY);
        $syllables = $this->count_syllables($content);

        if ($words > 0 && count($sentences) > 0) {
            $avg_sentence_length = $words / count($sentences);
            $avg_syllables_per_word = $syllables / $words;

            $flesch_score = 206.835 - (1.015 * $avg_sentence_length) - (84.6 * $avg_syllables_per_word);
            $flesch_score = max(0, min(100, round($flesch_score)));
        } else {
            $flesch_score = 0;
        }

        $score = 0;
        $feedback = '';

        if ($flesch_score >= 80) {
            $score = 10;
            $feedback = 'Excellent readability! Very easy to understand.';
        } elseif ($flesch_score >= 70) {
            $score = 8;
            $feedback = 'Good readability. Easy to understand.';
        } elseif ($flesch_score >= 60) {
            $score = 6;
            $feedback = 'Fair readability. Consider simplifying some sentences.';
        } elseif ($flesch_score >= 50) {
            $score = 4;
            $feedback = 'Moderately difficult to read.';
        } else {
            $score = 2;
            $feedback = 'Difficult to read. Consider rewriting for better clarity.';
        }

        return array(
            'score' => $score,
            'max_score' => 10,
            'feedback' => $feedback,
            'data' => array(
                'flesch_score' => $flesch_score,
                'avg_sentence_length' => round($avg_sentence_length ?? 0, 1),
                'avg_syllables_per_word' => round($avg_syllables_per_word ?? 0, 1)
            )
        );
    }

    /**
     * Check URL structure
     */
    public function check_url_structure($post_id)
    {
        $url = get_permalink($post_id);
        $post = get_post($post_id);

        $url_length = strlen($url);
        $has_keyword_in_url = false;
        $has_stop_words = false;
        $is_lowercase = true;

        // Check if focus keyword is in URL
        if (!empty($this->focus_keyword)) {
            $clean_url = preg_replace('/[^a-zA-Z0-9]/', ' ', $url);
            $has_keyword_in_url = stripos($clean_url, $this->focus_keyword) !== false;
        }

        // Check for stop words in URL
        $stop_words = array('a', 'an', 'the', 'and', 'or', 'but', 'in', 'on', 'at', 'to', 'for', 'of', 'with', 'by');
        $url_parts = explode('/', $url);
        $last_part = end($url_parts);
        $url_words = explode('-', $last_part);

        foreach ($url_words as $word) {
            if (in_array($word, $stop_words)) {
                $has_stop_words = true;
                break;
            }
        }

        // Check if URL is lowercase
        if ($url !== strtolower($url)) {
            $is_lowercase = false;
        }

        $score = 0;
        $feedback = '';

        // URL length check
        if ($url_length <= 60) {
            $score += 4;
            $feedback .= 'Good URL length. ';
        } else {
            $feedback .= 'URL is quite long. Consider shortening. ';
        }

        // Keyword in URL
        if ($has_keyword_in_url) {
            $score += 3;
            $feedback .= 'Focus keyword in URL. ';
        } else {
            $feedback .= 'Consider adding focus keyword to URL. ';
        }

        // Stop words
        if (!$has_stop_words) {
            $score += 2;
            $feedback .= 'No stop words in URL. ';
        } else {
            $feedback .= 'Remove stop words from URL. ';
        }

        // Lowercase
        if ($is_lowercase) {
            $score += 1;
            $feedback .= 'URL is lowercase. ';
        } else {
            $feedback .= 'URL should be lowercase. ';
        }

        return array(
            'score' => $score,
            'max_score' => 10,
            'feedback' => trim($feedback),
            'data' => array(
                'url_length' => $url_length,
                'has_keyword' => $has_keyword_in_url,
                'has_stop_words' => $has_stop_words,
                'is_lowercase' => $is_lowercase,
                'url' => $url
            )
        );
    }

    /**
     * Calculate total SEO score from all checks
     */
    private function calculate_total_score($checks)
    {
        $total_score = 0;
        $max_possible_score = 0;
        $detailed_feedback = array();

        foreach ($checks as $check_name => $check_data) {
            $total_score += $check_data['score'];
            $max_possible_score += $check_data['max_score'];

            $detailed_feedback[$check_name] = array(
                'score' => $check_data['score'],
                'max_score' => $check_data['max_score'],
                'feedback' => $check_data['feedback'],
                'data' => $check_data['data']
            );
        }

        $overall_score = $max_possible_score > 0 ? round(($total_score / $max_possible_score) * 100) : 0;

        // Determine SEO rating
        if ($overall_score >= 90) {
            $rating = 'Excellent';
            $color = '#4CAF50';
        } elseif ($overall_score >= 80) {
            $rating = 'Good';
            $color = '#8BC34A';
        } elseif ($overall_score >= 70) {
            $rating = 'Fair';
            $color = '#FFC107';
        } elseif ($overall_score >= 50) {
            $rating = 'Needs Improvement';
            $color = '#FF9800';
        } else {
            $rating = 'Poor';
            $color = '#F44336';
        }

        return array(
            'overall_score' => $overall_score,
            'rating' => $rating,
            'color' => $color,
            'total_checks' => count($checks),
            'checks' => $detailed_feedback,
            'priority_issues' => $this->identify_priority_issues($detailed_feedback)
        );
    }

    /**
     * Identify priority issues that need immediate attention
     */
    private function identify_priority_issues($checks)
    {
        $priority_issues = array();

        foreach ($checks as $check_name => $check_data) {
            $percentage = ($check_data['score'] / $check_data['max_score']) * 100;

            if ($percentage < 50) {
                $priority_issues[] = array(
                    'check' => $check_name,
                    'score' => $check_data['score'],
                    'max_score' => $check_data['max_score'],
                    'feedback' => $check_data['feedback'],
                    'priority' => $percentage < 30 ? 'high' : 'medium'
                );
            }
        }

        return $priority_issues;
    }

    /**
     * Helper method to get first paragraph from content
     */
    private function get_first_paragraph($content)
    {
        preg_match('/<p[^>]*>(.*?)<\/p>/s', $content, $matches);
        return $matches[1] ?? '';
    }

    /**
     * Helper method to check if keyword appears in headings
     */
    private function check_keyword_in_headings($content, $keyword)
    {
        preg_match_all('/<h[1-6][^>]*>(.*?)<\/h[1-6]>/i', $content, $headings);

        foreach ($headings[1] as $heading) {
            if (stripos(wp_strip_all_tags($heading), $keyword) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Helper method to count syllables in text
     */
    private function count_syllables($text)
    {
        $text = strtolower($text);
        $text = preg_replace('/[^a-z]/', ' ', $text);
        $words = preg_split('/\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);

        $syllable_count = 0;
        foreach ($words as $word) {
            $syllable_count += $this->count_word_syllables($word);
        }

        return $syllable_count;
    }

    /**
     * Helper method to count syllables in a single word
     */
    private function count_word_syllables($word)
    {
        $word = trim($word);
        if (empty($word)) return 0;

        // Basic syllable counting rules
        $vowels = '/[aeiouy]+/';
        preg_match_all($vowels, $word, $matches);
        $count = count($matches[0]);

        // Adjust for common exceptions
        if (substr($word, -1) == 'e') $count--;
        if (substr($word, -2) == 'le') $count++;
        if ($count == 0) $count = 1;

        return $count;
    }

    /**
     * Set focus keyword for analysis
     */
    public function set_focus_keyword($keyword)
    {
        $this->focus_keyword = $keyword;
        return $this;
    }

    /**
     * Get improvement suggestions based on analysis
     */
    public function get_improvement_suggestions($post_id)
    {
        $analysis = $this->calculate_seo_score($post_id);
        $suggestions = array();

        foreach ($analysis['checks'] as $check_name => $check) {
            $percentage = ($check['score'] / $check['max_score']) * 100;

            if ($percentage < 70) {
                $suggestions[] = array(
                    'area' => $this->format_check_name($check_name),
                    'current_score' => $check['score'] . '/' . $check['max_score'],
                    'suggestion' => $check['feedback'],
                    'priority' => $percentage < 50 ? 'high' : 'medium'
                );
            }
        }

        // Sort by priority
        usort($suggestions, function ($a, $b) {
            $priority_order = array('high' => 0, 'medium' => 1, 'low' => 2);
            return $priority_order[$a['priority']] - $priority_order[$b['priority']];
        });

        return $suggestions;
    }

    /**
     * Format check name for display
     */
    private function format_check_name($check_name)
    {
        return ucwords(str_replace('_', ' ', $check_name));
    }
}
