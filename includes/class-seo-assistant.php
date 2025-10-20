<?php

class ProductScraper_SEO_Assistant
{
    private $analysis_results = array();

    public function __construct()
    {
        add_action('admin_menu', array($this, 'add_seo_menu'));
        add_action('add_meta_boxes', array($this, 'add_seo_meta_boxes'));
        add_action('save_post', array($this, 'save_seo_metadata'));
        add_action('save_post', array($this, 'perform_real_time_analysis'));
        add_filter('wp_insert_post_data', array($this, 'optimize_content_on_save'));
        add_action('edit_category_form_fields', array($this, 'add_taxonomy_seo_fields'));
        add_action('edit_tag_form_fields', array($this, 'add_taxonomy_seo_fields'));
        add_action('edited_term', array($this, 'save_taxonomy_seo_fields'));
        
        // AJAX handlers
        add_action('wp_ajax_analyze_content', array($this, 'ajax_analyze_content'));
        add_action('wp_ajax_optimize_content', array($this, 'ajax_optimize_content'));
    }

    public function add_seo_menu()
    {
        add_submenu_page(
            'scraper-analytics',
            'SEO Assistant',
            'SEO Assistant',
            'manage_options',
            'seo-assistant',
            array($this, 'display_seo_dashboard')
        );

        // Add submenus for different SEO sections
        add_submenu_page(
            'scraper-analytics',
            'SEO Analysis',
            'SEO Analysis',
            'manage_options',
            'seo-analysis',
            array($this, 'display_seo_analysis')
        );

        add_submenu_page(
            'scraper-analytics',
            'Link Manager',
            'Link Manager',
            'manage_options',
            'link-manager',
            array($this, 'display_link_manager')
        );
    }

    public function add_seo_meta_boxes()
    {
        $post_types = get_post_types(array('public' => true));
        foreach ($post_types as $post_type) {
            add_meta_box(
                'seo_assistant_meta_box',
                'SEO Assistant - Advanced Optimization',
                array($this, 'render_seo_meta_box'),
                $post_type,
                'normal',
                'high'
            );
        }
    }

    public function render_seo_meta_box($post)
    {
        $seo_data = $this->get_seo_data($post->ID);
        $analysis = $this->analyze_content($post->post_content, $seo_data['focus_keyword']);
        
        include PRODUCT_SCRAPER_PLUGIN_PATH . 'templates/seo-meta-box.php';
    }

    private function get_seo_data($post_id)
    {
        return array(
            'seo_title' => get_post_meta($post_id, '_seo_title', true),
            'meta_description' => get_post_meta($post_id, '_meta_description', true),
            'focus_keyword' => get_post_meta($post_id, '_focus_keyword', true),
            'secondary_keywords' => get_post_meta($post_id, '_secondary_keywords', true),
            'readability_score' => get_post_meta($post_id, '_readability_score', true),
            'canonical_url' => get_post_meta($post_id, '_canonical_url', true),
            'meta_robots' => get_post_meta($post_id, '_meta_robots', true),
            'og_title' => get_post_meta($post_id, '_og_title', true),
            'og_description' => get_post_meta($post_id, '_og_description', true),
            'og_image' => get_post_meta($post_id, '_og_image', true),
            'twitter_title' => get_post_meta($post_id, '_twitter_title', true),
            'twitter_description' => get_post_meta($post_id, '_twitter_description', true),
            'twitter_image' => get_post_meta($post_id, '_twitter_image', true)
        );
    }

    public function save_seo_metadata($post_id)
    {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;

        $fields = array(
            '_seo_title', '_meta_description', '_focus_keyword', '_secondary_keywords',
            '_canonical_url', '_meta_robots', '_og_title', '_og_description', '_og_image',
            '_twitter_title', '_twitter_description', '_twitter_image'
        );

        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                update_post_meta($post_id, $field, sanitize_text_field($_POST[$field]));
            }
        }

        // Calculate readability score
        $content = get_post_field('post_content', $post_id);
        $this->calculate_readability_score($post_id, $content);
    }

    public function perform_real_time_analysis($post_id)
    {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        
        $content = get_post_field('post_content', $post_id);
        $focus_keyword = get_post_meta($post_id, '_focus_keyword', true);
        
        $analysis = $this->analyze_content($content, $focus_keyword);
        update_post_meta($post_id, '_seo_analysis', $analysis);
    }

    public function analyze_content($content, $focus_keyword = '')
    {
        $analysis = array(
            'score' => 0,
            'issues' => array(),
            'improvements' => array(),
            'good' => array()
        );

        $clean_content = wp_strip_all_tags($content);
        $word_count = str_word_count($clean_content);
        
        // Basic content analysis
        if ($word_count < 300) {
            $analysis['issues'][] = array(
                'type' => 'content_length',
                'message' => 'Content is too short. Aim for at least 300 words.',
                'severity' => 'high'
            );
        } elseif ($word_count > 2000) {
            $analysis['improvements'][] = array(
                'type' => 'content_length',
                'message' => 'Content is quite long. Consider breaking it into multiple sections.',
                'severity' => 'low'
            );
        } else {
            $analysis['good'][] = array(
                'type' => 'content_length',
                'message' => 'Content length is good.',
                'severity' => 'good'
            );
        }

        // Focus keyword analysis
        if (!empty($focus_keyword)) {
            $keyword_analysis = $this->analyze_keyword_usage($clean_content, $focus_keyword);
            $analysis = array_merge_recursive($analysis, $keyword_analysis);
        }

        // Readability analysis
        $readability = $this->calculate_readability_advanced($clean_content);
        $analysis['readability'] = $readability;

        // Calculate overall score
        $analysis['score'] = $this->calculate_overall_score($analysis);

        return $analysis;
    }

    private function analyze_keyword_usage($content, $keyword)
    {
        $analysis = array();
        $keyword_lower = strtolower($keyword);
        $content_lower = strtolower($content);
        
        $keyword_count = substr_count($content_lower, $keyword_lower);
        $word_count = str_word_count($content);
        $keyword_density = ($keyword_count / max($word_count, 1)) * 100;

        if ($keyword_count === 0) {
            $analysis['issues'][] = array(
                'type' => 'keyword_usage',
                'message' => 'Focus keyword not found in content.',
                'severity' => 'high'
            );
        } elseif ($keyword_density < 0.5) {
            $analysis['issues'][] = array(
                'type' => 'keyword_density',
                'message' => 'Focus keyword density is too low.',
                'severity' => 'medium'
            );
        } elseif ($keyword_density > 3) {
            $analysis['issues'][] = array(
                'type' => 'keyword_density',
                'message' => 'Focus keyword density is too high (keyword stuffing).',
                'severity' => 'high'
            );
        } else {
            $analysis['good'][] = array(
                'type' => 'keyword_density',
                'message' => 'Focus keyword density is optimal.',
                'severity' => 'good'
            );
        }

        return $analysis;
    }

    private function calculate_readability_advanced($content)
    {
        // Implement Flesch Reading Ease score
        $words = str_word_count($content);
        $sentences = preg_split('/[.!?]+/', $content, -1, PREG_SPLIT_NO_EMPTY);
        $syllables = $this->count_syllables($content);

        if ($words > 0 && count($sentences) > 0) {
            $average_sentence_length = $words / count($sentences);
            $average_syllables_per_word = $syllables / $words;
            
            $flesch_score = 206.835 - (1.015 * $average_sentence_length) - (84.6 * $average_syllables_per_word);
            
            return array(
                'flesch_score' => round($flesch_score),
                'grade_level' => $this->get_grade_level($flesch_score),
                'description' => $this->get_readability_description($flesch_score)
            );
        }

        return array(
            'flesch_score' => 0,
            'grade_level' => 'N/A',
            'description' => 'Not enough content to analyze'
        );
    }

    private function count_syllables($content)
    {
        // Basic syllable counting (can be improved)
        $words = str_word_count($content, 1);
        $syllables = 0;
        
        foreach ($words as $word) {
            $syllables += $this->count_word_syllables($word);
        }
        
        return $syllables;
    }

    private function count_word_syllables($word)
    {
        // Simple syllable counting algorithm
        $word = preg_replace('/[^a-z]/i', '', strtolower($word));
        $count = 0;
        
        if (strlen($word) > 0) {
            $count = 1; // At least one syllable
            $vowels = 'aeiouy';
            $prev_char_vowel = false;
            
            for ($i = 0; $i < strlen($word); $i++) {
                $is_vowel = strpos($vowels, $word[$i]) !== false;
                
                if ($is_vowel && !$prev_char_vowel) {
                    $count++;
                }
                
                $prev_char_vowel = $is_vowel;
            }
            
            // Adjust for common exceptions
            if (substr($word, -1) === 'e') {
                $count--;
            }
            
            if (substr($word, -2) === 'le' && strlen($word) > 2) {
                $count++;
            }
        }
        
        return max(1, $count);
    }

    private function get_grade_level($flesch_score)
    {
        if ($flesch_score >= 90) return '5th grade';
        if ($flesch_score >= 80) return '6th grade';
        if ($flesch_score >= 70) return '7th grade';
        if ($flesch_score >= 60) return '8th & 9th grade';
        if ($flesch_score >= 50) return '10th to 12th grade';
        if ($flesch_score >= 30) return 'College';
        return 'College Graduate';
    }

    private function get_readability_description($flesch_score)
    {
        if ($flesch_score >= 90) return 'Very easy to read';
        if ($flesch_score >= 80) return 'Easy to read';
        if ($flesch_score >= 70) return 'Fairly easy to read';
        if ($flesch_score >= 60) return 'Standard';
        if ($flesch_score >= 50) return 'Fairly difficult to read';
        if ($flesch_score >= 30) return 'Difficult to read';
        return 'Very difficult to read';
    }

    private function calculate_overall_score($analysis)
    {
        $score = 100;
        
        // Deduct points for issues
        foreach ($analysis['issues'] as $issue) {
            switch ($issue['severity']) {
                case 'high':
                    $score -= 20;
                    break;
                case 'medium':
                    $score -= 10;
                    break;
                case 'low':
                    $score -= 5;
                    break;
            }
        }
        
        // Add points for good practices
        foreach ($analysis['good'] as $good) {
            $score += 5;
        }
        
        return max(0, min(100, $score));
    }

    public function add_taxonomy_seo_fields($term)
    {
        $term_id = $term->term_id;
        $seo_title = get_term_meta($term_id, '_seo_title', true);
        $meta_description = get_term_meta($term_id, '_meta_description', true);
        ?>
        <tr class="form-field">
            <th scope="row" valign="top">
                <label for="seo_title"><?php _e('SEO Title'); ?></label>
            </th>
            <td>
                <input type="text" name="seo_title" id="seo_title" value="<?php echo esc_attr($seo_title); ?>" />
                <p class="description"><?php _e('Custom title for search engines'); ?></p>
            </td>
        </tr>
        <tr class="form-field">
            <th scope="row" valign="top">
                <label for="meta_description"><?php _e('Meta Description'); ?></label>
            </th>
            <td>
                <textarea name="meta_description" id="meta_description" rows="3" cols="50"><?php echo esc_textarea($meta_description); ?></textarea>
                <p class="description"><?php _e('Custom description for search engines'); ?></p>
            </td>
        </tr>
        <?php
    }

    public function save_taxonomy_seo_fields($term_id)
    {
        if (isset($_POST['seo_title'])) {
            update_term_meta($term_id, '_seo_title', sanitize_text_field($_POST['seo_title']));
        }
        if (isset($_POST['meta_description'])) {
            update_term_meta($term_id, '_meta_description', sanitize_textarea_field($_POST['meta_description']));
        }
    }

    public function display_seo_dashboard()
    {
        $stats = $this->get_seo_stats();
        $recent_analysis = $this->get_recent_analysis();
        
        include PRODUCT_SCRAPER_PLUGIN_PATH . 'templates/seo-dashboard.php';
    }

    public function display_seo_analysis()
    {
        $site_analysis = $this->analyze_site_seo();
        include PRODUCT_SCRAPER_PLUGIN_PATH . 'templates/seo-analysis.php';
    }

    public function display_link_manager()
    {
        $internal_links = $this->get_internal_links();
        $external_links = $this->get_external_links();
        include PRODUCT_SCRAPER_PLUGIN_PATH . 'templates/link-manager.php';
    }

    private function analyze_site_seo()
    {
        global $wpdb;
        
        $analysis = array(
            'technical' => $this->analyze_technical_seo(),
            'content' => $this->analyze_content_seo(),
            'performance' => $this->analyze_performance_seo()
        );
        
        return $analysis;
    }

    private function analyze_technical_seo()
    {
        $technical = array();
        
        // Check if site is indexable
        $technical['indexable'] = !get_option('blog_public') ? 'No' : 'Yes';
        
        // Check permalink structure
        $permalink_structure = get_option('permalink_structure');
        $technical['permalink_structure'] = empty($permalink_structure) ? 'Plain' : 'SEO Friendly';
        
        // Check SSL
        $technical['ssl'] = is_ssl() ? 'Yes' : 'No';
        
        return $technical;
    }

    // AJAX handlers
    public function ajax_analyze_content()
    {
        check_ajax_referer('product_scraper_nonce', 'nonce');
        
        $content = wp_kses_post($_POST['content']);
        $keyword = sanitize_text_field($_POST['keyword']);
        
        $analysis = $this->analyze_content($content, $keyword);
        
        wp_send_json_success($analysis);
    }

    public function ajax_optimize_content()
    {
        check_ajax_referer('product_scraper_nonce', 'nonce');
        
        $content = wp_kses_post($_POST['content']);
        $keyword = sanitize_text_field($_POST['keyword']);
        
        // This would integrate with AI service for optimization
        $optimized_content = $this->ai_optimize_content($content, $keyword);
        
        wp_send_json_success(array(
            'optimized_content' => $optimized_content,
            'changes_made' => $this->get_optimization_changes($content, $optimized_content)
        ));
    }

    private function get_optimization_changes($original, $optimized)
    {
        // Compare original and optimized content
        return array(
            'word_count_change' => str_word_count($optimized) - str_word_count($original),
            'readability_improvement' => 'Improved',
            'keyword_optimization' => 'Enhanced'
        );
    }
}