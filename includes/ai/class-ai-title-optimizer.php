<?php
// wp-content\plugins\product-scraper-nahrin\includes\ai\class-ai-title-optimizer.php

class ProductScraper_AI_Title_Optimizer {
    
    private $openai;
    private $api_key;
    
    public function __construct() {
        $this->api_key = get_option('product_scraper_openai_api_key', '');
        $this->initialize_openai_client();
    }
    
    /**
     * Initialize OpenAI client
     */
    private function initialize_openai_client() {
        if (empty($this->api_key)) {
            $this->openai = null;
            return;
        }
        
        try {
            $this->openai = OpenAI::client($this->api_key);
        } catch (Exception $e) {
            error_log('ProductScraper: Failed to initialize OpenAI client for title optimizer: ' . $e->getMessage());
            $this->openai = null;
        }
    }
    
    /**
     * Generate title variations
     */
    public function generate_title_variations($keyword, $current_title = '') {
        if (!$this->openai) {
            throw new Exception('OpenAI client not initialized');
        }
        
        $prompt = $current_title ? 
            "Optimize this title for better SEO and engagement: '{$current_title}'" :
            "Generate 5 SEO-optimized title variations for keyword: '{$keyword}'";
            
        try {
            $response = $this->openai->chat()->create([
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    ['role' => 'system', 'content' => 'You are an SEO expert specializing in title optimization.'],
                    ['role' => 'user', 'content' => $prompt]
                ],
                'max_tokens' => 500,
                'temperature' => 0.7,
            ]);
            
            return $this->parse_titles($response->choices[0]->message->content);
            
        } catch (Exception $e) {
            error_log('ProductScraper: Title optimization error: ' . $e->getMessage());
            throw new Exception('Failed to generate title variations: ' . $e->getMessage());
        }
    }
    
    /**
     * Analyze title emotional impact
     */
    public function analyze_title_emotional_impact($title) {
        if (!$this->openai) {
            throw new Exception('OpenAI client not initialized');
        }
        
        try {
            $response = $this->openai->chat()->create([
                'model' => 'gpt-3.5-turbo',
                'messages' => [
                    ['role' => 'system', 'content' => 'You are an expert in copywriting and emotional marketing.'],
                    ['role' => 'user', 'content' => "Analyze the emotional impact and engagement potential of this title: '{$title}'. Provide a brief analysis of its strengths and weaknesses."]
                ],
                'max_tokens' => 300,
                'temperature' => 0.3,
            ]);
            
            return $response->choices[0]->message->content;
            
        } catch (Exception $e) {
            error_log('ProductScraper: Title analysis error: ' . $e->getMessage());
            throw new Exception('Failed to analyze title: ' . $e->getMessage());
        }
    }
    
    /**
     * Parse titles from response
     */
    private function parse_titles($response_text) {
        $lines = explode("\n", $response_text);
        $titles = [];
        
        foreach ($lines as $line) {
            $line = trim($line);
            $title = preg_replace('/^[0-9]+\.\s*/', '', $line);
            $title = preg_replace('/^[-*]\s*/', '', $title);
            
            if (!empty($title) && strlen($title) > 5) {
                $titles[] = $title;
            }
        }
        
        return array_slice($titles, 0, 5);
    }
}