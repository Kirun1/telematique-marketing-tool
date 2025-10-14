<?php

class ProductScraper_SEO_Assistant
{

    public function __construct()
    {
        add_action('admin_menu', array($this, 'add_seo_menu'));
        add_action('add_meta_boxes', array($this, 'add_seo_meta_boxes'));
        add_action('save_post', array($this, 'save_seo_metadata'));
        add_filter('wp_insert_post_data', array($this, 'optimize_content_on_save'));
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
    }

    public function add_seo_meta_boxes()
    {
        $post_types = get_post_types(array('public' => true));
        foreach ($post_types as $post_type) {
            add_meta_box(
                'seo_assistant_meta_box',
                'SEO Assistant',
                array($this, 'render_seo_meta_box'),
                $post_type,
                'normal',
                'high'
            );
        }
    }

    public function render_seo_meta_box($post)
    {
        $seo_title = get_post_meta($post->ID, '_seo_title', true);
        $meta_description = get_post_meta($post->ID, '_meta_description', true);
        $focus_keyword = get_post_meta($post->ID, '_focus_keyword', true);
        $readability_score = get_post_meta($post->ID, '_readability_score', true);

        include PRODUCT_SCRAPER_PLUGIN_PATH . 'templates/seo-meta-box.php';
    }

    public function save_seo_metadata($post_id)
    {
        if (defined('DOING_AUTOSAVE') && DOING_AUTOSAVE) return;
        if (!current_user_can('edit_post', $post_id)) return;

        $fields = array('_seo_title', '_meta_description', '_focus_keyword');
        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                update_post_meta($post_id, $field, sanitize_text_field($_POST[$field]));
            }
        }

        // Calculate readability score
        $content = get_post_field('post_content', $post_id);
        $this->calculate_readability_score($post_id, $content);
    }

    public function optimize_content_on_save($data)
    {
        if (!isset($_POST['seo_auto_optimize']) || !$_POST['seo_auto_optimize']) {
            return $data;
        }

        // AI-powered content optimization
        $optimized_content = $this->ai_optimize_content($data['post_content']);
        $data['post_content'] = $optimized_content;

        return $data;
    }

    private function calculate_readability_score($post_id, $content)
    {
        $content = wp_strip_all_tags($content);
        $word_count = str_word_count($content);
        $sentence_count = preg_split('/[.!?]+/', $content, -1, PREG_SPLIT_NO_EMPTY);
        $sentence_count = count($sentence_count);

        if ($word_count > 0 && $sentence_count > 0) {
            $average_sentence_length = $word_count / $sentence_count;
            $score = max(0, min(100, 100 - ($average_sentence_length * 2)));
            update_post_meta($post_id, '_readability_score', intval($score));
        }
    }

    private function ai_optimize_content($content)
    {
        // This would integrate with an AI service like OpenAI
        // For now, return the original content
        return $content;
    }

    public function display_seo_dashboard()
    {
        $stats = $this->get_seo_stats();
        include PRODUCT_SCRAPER_PLUGIN_PATH . 'templates/seo-dashboard.php';
    }

    private function get_seo_stats()
    {
        global $wpdb;

        $total_posts = wp_count_posts()->publish;
        $posts_with_seo = $wpdb->get_var("
            SELECT COUNT(DISTINCT post_id) 
            FROM {$wpdb->postmeta} 
            WHERE meta_key = '_seo_title' 
            AND meta_value != ''
        ");

        $avg_readability = $wpdb->get_var("
            SELECT AVG(meta_value) 
            FROM {$wpdb->postmeta} 
            WHERE meta_key = '_readability_score'
        ");

        return array(
            'total_posts' => $total_posts,
            'optimized_posts' => $posts_with_seo,
            'optimization_rate' => $total_posts > 0 ? round(($posts_with_seo / $total_posts) * 100) : 0,
            'avg_readability' => round($avg_readability ?: 0),
            'issues_found' => $this->scan_for_seo_issues()
        );
    }

    private function scan_for_seo_issues()
    {
        $issues = array();

        // Check for posts without meta descriptions
        $posts_without_meta = get_posts(array(
            'meta_query' => array(
                array(
                    'key' => '_meta_description',
                    'compare' => 'NOT EXISTS'
                )
            ),
            'numberposts' => 10
        ));

        foreach ($posts_without_meta as $post) {
            $issues[] = array(
                'type' => 'missing_meta_description',
                'title' => $post->post_title,
                'url' => get_edit_post_link($post->ID),
                'severity' => 'medium'
            );
        }

        return $issues;
    }
}
