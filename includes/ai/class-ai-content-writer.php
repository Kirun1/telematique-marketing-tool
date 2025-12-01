<?php

class ProductScraper_AI_Content_Writer {
    
    private $openai;
    private $api_key;
    private $max_tokens;
    private $temperature;
    private $content_tone;
    
    public function __construct() {
        $this->api_key = get_option('product_scraper_openai_api_key', '');
        $this->max_tokens = get_option('product_scraper_ai_max_tokens', 1000);
        $this->temperature = get_option('product_scraper_ai_temperature', 0.7);
        $this->content_tone = get_option('product_scraper_ai_content_tone', 'professional');
        
        $this->initialize_openai_client();
    }
    
    /**
     * Initialize OpenAI client with proper configuration
     */
    private function initialize_openai_client() {
        if (empty($this->api_key)) {
            $this->openai = null;
            return;
        }
        
        try {
            // For OpenAI PHP client v4.x
            $this->openai = OpenAI::client($this->api_key);
        } catch (Exception $e) {
            error_log('ProductScraper: Failed to initialize OpenAI client: ' . $e->getMessage());
            $this->openai = null;
        }
    }
    
    /**
     * Generate AI content based on topic and parameters
     */
    public function generate_content($topic, $keywords = array(), $tone = null, $content_type = 'blog_post') {
        if (!$this->openai) {
            throw new Exception('OpenAI client not initialized. Please check your API key.');
        }
        
        $tone = $tone ?: $this->content_tone;
        
        // Build the prompt based on content type
        $prompt = $this->build_prompt($topic, $keywords, $tone, $content_type);
        
        try {
            $response = $this->openai->chat()->create([
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    ['role' => 'system', 'content' => 'You are a professional content writer specializing in SEO-optimized content.'],
                    ['role' => 'user', 'content' => $prompt]
                ],
                'max_tokens' => min($this->max_tokens, 4000),
                'temperature' => $this->temperature,
            ]);
            
            return $response->choices[0]->message->content;
            
        } catch (Exception $e) {
            error_log('ProductScraper: OpenAI API error: ' . $e->getMessage());
            throw new Exception('Failed to generate content: ' . $e->getMessage());
        }
    }
    
    /**
     * Build prompt based on parameters
     */
    private function build_prompt($topic, $keywords, $tone, $content_type) {
        $prompt_templates = [
            'blog_post' => "Write a comprehensive blog post about '{$topic}' with a {$tone} tone. ",
            'product_description' => "Write a compelling product description for '{$topic}' with a {$tone} tone. ",
            'meta_description' => "Write an SEO-optimized meta description for '{$topic}' (max 160 characters). ",
            'social_media' => "Write engaging social media content about '{$topic}' with a {$tone} tone. ",
            'email' => "Write a professional email about '{$topic}' with a {$tone} tone. ",
        ];
        
        $prompt = $prompt_templates[$content_type] ?? $prompt_templates['blog_post'];
        
        // Add keywords if provided
        if (!empty($keywords)) {
            $keyword_list = implode(', ', $keywords);
            $prompt .= "Incorporate these keywords naturally: {$keyword_list}. ";
        }
        
        // Add specific instructions based on content type
        switch ($content_type) {
            case 'blog_post':
                $prompt .= "Include an engaging introduction, detailed body content with subheadings, and a compelling conclusion. Make it SEO-friendly and easy to read.";
                break;
            case 'product_description':
                $prompt .= "Highlight key features, benefits, and include a call-to-action. Focus on persuasive language that drives conversions.";
                break;
            case 'meta_description':
                $prompt .= "Make it compelling to click while accurately describing the content. Include primary keywords naturally.";
                break;
            case 'social_media':
                $prompt .= "Create multiple variations for different platforms (Twitter, Facebook, Instagram). Include relevant hashtags where appropriate.";
                break;
            case 'email':
                $prompt .= "Include a clear subject line, engaging body, and appropriate call-to-action. Format for optimal readability.";
                break;
        }
        
        return $prompt;
    }
    
    /**
     * Analyze content quality and provide suggestions
     */
    public function analyze_content_quality($content) {
        if (!$this->openai) {
            throw new Exception('OpenAI client not initialized. Please check your API key.');
        }
        
        try {
            $response = $this->openai->chat()->create([
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    ['role' => 'system', 'content' => 'You are an expert content analyst specializing in SEO and readability.'],
                    ['role' => 'user', 'content' => "Analyze this content and provide specific suggestions for improvement. Focus on:\n1. SEO optimization\n2. Readability and structure\n3. Engagement potential\n4. Grammar and style\n\nContent to analyze:\n{$content}"]
                ],
                'max_tokens' => 800,
                'temperature' => 0.3,
            ]);
            
            return $response->choices[0]->message->content;
            
        } catch (Exception $e) {
            error_log('ProductScraper: OpenAI analysis error: ' . $e->getMessage());
            throw new Exception('Failed to analyze content: ' . $e->getMessage());
        }
    }
    
    /**
     * Generate multiple title variations
     */
    public function generate_title_variations($topic, $keywords = array()) {
        if (!$this->openai) {
            throw new Exception('OpenAI client not initialized. Please check your API key.');
        }
        
        $keyword_text = !empty($keywords) ? "Include these keywords: " . implode(', ', $keywords) . ". " : "";
        
        try {
            $response = $this->openai->chat()->create([
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    ['role' => 'system', 'content' => 'You are an expert at creating compelling, SEO-optimized titles.'],
                    ['role' => 'user', 'content' => "Generate 5 engaging title variations for: '{$topic}'. {$keyword_text}Make them attention-grabbing and SEO-friendly. Return as a numbered list."]
                ],
                'max_tokens' => 500,
                'temperature' => 0.8,
            ]);
            
            return $this->parse_title_variations($response->choices[0]->message->content);
            
        } catch (Exception $e) {
            error_log('ProductScraper: OpenAI title generation error: ' . $e->getMessage());
            throw new Exception('Failed to generate title variations: ' . $e->getMessage());
        }
    }
    
    /**
     * Parse title variations from response
     */
    private function parse_title_variations($response_text) {
        $lines = explode("\n", $response_text);
        $titles = [];
        
        foreach ($lines as $line) {
            $line = trim($line);
            // Remove numbering and bullets
            $title = preg_replace('/^[0-9]+\.\s*/', '', $line);
            $title = preg_replace('/^[-*]\s*/', '', $title);
            
            if (!empty($title) && strlen($title) > 10) {
                $titles[] = $title;
            }
        }
        
        return array_slice($titles, 0, 5); // Return max 5 titles
    }
    
    /**
     * Check if OpenAI is configured and working
     */
    public function is_configured() {
        return !empty($this->api_key) && $this->openai !== null;
    }
    
    /**
     * Test API connection
     */
    public function test_connection() {
        if (!$this->is_configured()) {
            return ['success' => false, 'message' => 'OpenAI API key not configured'];
        }
        
        try {
            $response = $this->openai->chat()->create([
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    ['role' => 'user', 'content' => 'Respond with "OK" if you can read this.']
                ],
                'max_tokens' => 5,
            ]);
            
            return ['success' => true, 'message' => 'OpenAI API connection successful'];
            
        } catch (Exception $e) {
            return ['success' => false, 'message' => 'OpenAI API connection failed: ' . $e->getMessage()];
        }
    }
}