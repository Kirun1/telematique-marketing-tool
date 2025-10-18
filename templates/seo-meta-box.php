<?php

/**
 * SEO Meta Box Template
 * 
 * @package ProductScraper
 * @since 1.0.0
 */

// Security check - prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get current post ID
$post_id = get_the_ID();
$seo_data = get_post_meta($post_id, '_productscraper_seo_data', true);

// Set default values
$defaults = array(
    'focus_keyword' => '',
    'meta_title' => '',
    'meta_description' => '',
    'canonical_url' => '',
    'meta_robots' => 'index,follow',
    'og_title' => '',
    'og_description' => '',
    'og_image' => '',
    'twitter_title' => '',
    'twitter_description' => '',
    'twitter_image' => '',
    'schema_type' => 'Product',
    'custom_schema' => ''
);

$seo_data = wp_parse_args($seo_data, $defaults);

// Nonce field for security
wp_nonce_field('productscraper_seo_meta_box', 'productscraper_seo_nonce');
?>

<div class="productscraper-seo-meta-box">
    <div class="ps-seo-tabs">
        <nav class="ps-tab-nav">
            <button type="button" class="ps-tab-button active" data-tab="basic">Basic SEO</button>
            <button type="button" class="ps-tab-button" data-tab="social">Social Media</button>
            <button type="button" class="ps-tab-button" data-tab="advanced">Advanced</button>
            <button type="button" class="ps-tab-button" data-tab="schema">Schema</button>
        </nav>

        <!-- Basic SEO Tab -->
        <div class="ps-tab-content active" id="ps-tab-basic">
            <div class="ps-form-group">
                <label for="ps_focus_keyword">
                    <strong>Focus Keyword</strong>
                    <span class="ps-help-text">Primary keyword you want to rank for</span>
                </label>
                <input type="text" id="ps_focus_keyword" name="productscraper_seo[focus_keyword]"
                    value="<?php echo esc_attr($seo_data['focus_keyword']); ?>"
                    class="ps-form-control" placeholder="e.g., best wireless headphones">
                <div class="ps-keyword-suggestions" id="ps_keyword_suggestions"></div>
            </div>

            <div class="ps-form-group">
                <label for="ps_meta_title">
                    <strong>Meta Title</strong>
                    <span class="ps-character-count">
                        <span id="ps_title_count">0</span>/60 characters
                    </span>
                </label>
                <input type="text" id="ps_meta_title" name="productscraper_seo[meta_title]"
                    value="<?php echo esc_attr($seo_data['meta_title']); ?>"
                    class="ps-form-control"
                    placeholder="<?php echo esc_attr(wp_get_document_title()); ?>">
                <div class="ps-preview">
                    <strong>Preview:</strong>
                    <div class="ps-preview-title" id="ps_title_preview">
                        <?php echo esc_html($seo_data['meta_title'] ?: wp_get_document_title()); ?>
                    </div>
                </div>
            </div>

            <div class="ps-form-group">
                <label for="ps_meta_description">
                    <strong>Meta Description</strong>
                    <span class="ps-character-count">
                        <span id="ps_description_count">0</span>/160 characters
                    </span>
                </label>
                <textarea id="ps_meta_description" name="productscraper_seo[meta_description]"
                    class="ps-form-control" rows="3"
                    placeholder="Write a compelling meta description that encourages clicks"><?php echo esc_textarea($seo_data['meta_description']); ?></textarea>
                <div class="ps-preview">
                    <strong>Preview:</strong>
                    <div class="ps-preview-description" id="ps_description_preview">
                        <?php echo esc_html($seo_data['meta_description'] ?: 'No meta description set.'); ?>
                    </div>
                </div>
            </div>

            <div class="ps-seo-score">
                <div class="ps-score-header">
                    <strong>SEO Score</strong>
                    <span class="ps-score-badge" id="ps_seo_score">0%</span>
                </div>
                <div class="ps-score-breakdown">
                    <div class="ps-score-item" data-check="title">
                        <span class="ps-check-icon"></span>
                        Meta Title Optimized
                    </div>
                    <div class="ps-score-item" data-check="description">
                        <span class="ps-check-icon"></span>
                        Meta Description Optimized
                    </div>
                    <div class="ps-score-item" data-check="keyword">
                        <span class="ps-check-icon"></span>
                        Focus Keyword Used
                    </div>
                    <div class="ps-score-item" data-check="content">
                        <span class="ps-check-icon"></span>
                        Content Length
                    </div>
                </div>
            </div>
        </div>

        <!-- Social Media Tab -->
        <div class="ps-tab-content" id="ps-tab-social">
            <div class="ps-social-section">
                <h4>Facebook Open Graph</h4>
                <div class="ps-form-group">
                    <label for="ps_og_title">OG Title</label>
                    <input type="text" id="ps_og_title" name="productscraper_seo[og_title]"
                        value="<?php echo esc_attr($seo_data['og_title']); ?>"
                        class="ps-form-control" placeholder="Leave empty to use meta title">
                </div>

                <div class="ps-form-group">
                    <label for="ps_og_description">OG Description</label>
                    <textarea id="ps_og_description" name="productscraper_seo[og_description]"
                        class="ps-form-control" rows="2"
                        placeholder="Leave empty to use meta description"><?php echo esc_textarea($seo_data['og_description']); ?></textarea>
                </div>

                <div class="ps-form-group">
                    <label for="ps_og_image">OG Image</label>
                    <div class="ps-image-upload">
                        <input type="hidden" id="ps_og_image" name="productscraper_seo[og_image]"
                            value="<?php echo esc_attr($seo_data['og_image']); ?>">
                        <div class="ps-image-preview" id="ps_og_preview">
                            <?php if ($seo_data['og_image']): ?>
                                <img src="<?php echo esc_url(wp_get_attachment_image_url($seo_data['og_image'], 'thumbnail')); ?>" alt="OG Image Preview">
                            <?php endif; ?>
                        </div>
                        <button type="button" class="button ps-upload-button" data-target="ps_og_image">
                            Choose Image
                        </button>
                        <button type="button" class="button ps-remove-button" data-target="ps_og_image">
                            Remove
                        </button>
                    </div>
                </div>
            </div>

            <div class="ps-social-section">
                <h4>Twitter Card</h4>
                <div class="ps-form-group">
                    <label for="ps_twitter_title">Twitter Title</label>
                    <input type="text" id="ps_twitter_title" name="productscraper_seo[twitter_title]"
                        value="<?php echo esc_attr($seo_data['twitter_title']); ?>"
                        class="ps-form-control" placeholder="Leave empty to use OG title">
                </div>

                <div class="ps-form-group">
                    <label for="ps_twitter_description">Twitter Description</label>
                    <textarea id="ps_twitter_description" name="productscraper_seo[twitter_description]"
                        class="ps-form-control" rows="2"
                        placeholder="Leave empty to use OG description"><?php echo esc_textarea($seo_data['twitter_description']); ?></textarea>
                </div>

                <div class="ps-form-group">
                    <label for="ps_twitter_image">Twitter Image</label>
                    <div class="ps-image-upload">
                        <input type="hidden" id="ps_twitter_image" name="productscraper_seo[twitter_image]"
                            value="<?php echo esc_attr($seo_data['twitter_image']); ?>">
                        <div class="ps-image-preview" id="ps_twitter_preview">
                            <?php if ($seo_data['twitter_image']): ?>
                                <img src="<?php echo esc_url(wp_get_attachment_image_url($seo_data['twitter_image'], 'thumbnail')); ?>" alt="Twitter Image Preview">
                            <?php endif; ?>
                        </div>
                        <button type="button" class="button ps-upload-button" data-target="ps_twitter_image">
                            Choose Image
                        </button>
                        <button type="button" class="button ps-remove-button" data-target="ps_twitter_image">
                            Remove
                        </button>
                    </div>
                </div>
            </div>

            <div class="ps-social-preview">
                <h4>Social Preview</h4>
                <div class="ps-preview-tabs">
                    <button type="button" class="ps-preview-tab active" data-preview="facebook">Facebook</button>
                    <button type="button" class="ps-preview-tab" data-preview="twitter">Twitter</button>
                </div>

                <div class="ps-facebook-preview ps-preview-content active">
                    <div class="ps-facebook-card">
                        <div class="ps-facebook-image" id="ps_facebook_image_preview">
                            <?php if ($seo_data['og_image']): ?>
                                <img src="<?php echo esc_url(wp_get_attachment_image_url($seo_data['og_image'], 'medium')); ?>" alt="Facebook Preview">
                            <?php else: ?>
                                <div class="ps-no-image">No image set</div>
                            <?php endif; ?>
                        </div>
                        <div class="ps-facebook-content">
                            <div class="ps-facebook-title" id="ps_facebook_title_preview">
                                <?php echo esc_html($seo_data['og_title'] ?: $seo_data['meta_title'] ?: get_the_title()); ?>
                            </div>
                            <div class="ps-facebook-description" id="ps_facebook_description_preview">
                                <?php echo esc_html($seo_data['og_description'] ?: $seo_data['meta_description'] ?: get_the_excerpt()); ?>
                            </div>
                            <div class="ps-facebook-url"><?php echo esc_url(home_url()); ?></div>
                        </div>
                    </div>
                </div>

                <div class="ps-twitter-preview ps-preview-content">
                    <div class="ps-twitter-card">
                        <div class="ps-twitter-image" id="ps_twitter_image_preview">
                            <?php if ($seo_data['twitter_image']): ?>
                                <img src="<?php echo esc_url(wp_get_attachment_image_url($seo_data['twitter_image'], 'medium')); ?>" alt="Twitter Preview">
                            <?php else: ?>
                                <div class="ps-no-image">No image set</div>
                            <?php endif; ?>
                        </div>
                        <div class="ps-twitter-content">
                            <div class="ps-twitter-title" id="ps_twitter_title_preview">
                                <?php echo esc_html($seo_data['twitter_title'] ?: $seo_data['og_title'] ?: $seo_data['meta_title'] ?: get_the_title()); ?>
                            </div>
                            <div class="ps-twitter-description" id="ps_twitter_description_preview">
                                <?php echo esc_html($seo_data['twitter_description'] ?: $seo_data['og_description'] ?: $seo_data['meta_description'] ?: get_the_excerpt()); ?>
                            </div>
                            <div class="ps-twitter-url"><?php echo esc_url(home_url()); ?></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Advanced Tab -->
        <div class="ps-tab-content" id="ps-tab-advanced">
            <div class="ps-form-group">
                <label for="ps_canonical_url">
                    <strong>Canonical URL</strong>
                    <span class="ps-help-text">Use this to specify the preferred version of this page</span>
                </label>
                <input type="url" id="ps_canonical_url" name="productscraper_seo[canonical_url]"
                    value="<?php echo esc_url($seo_data['canonical_url']); ?>"
                    class="ps-form-control" placeholder="<?php echo esc_url(get_permalink()); ?>">
            </div>

            <div class="ps-form-group">
                <label for="ps_meta_robots">
                    <strong>Meta Robots</strong>
                    <span class="ps-help-text">How search engines should handle this page</span>
                </label>
                <select id="ps_meta_robots" name="productscraper_seo[meta_robots]" class="ps-form-control">
                    <option value="index,follow" <?php selected($seo_data['meta_robots'], 'index,follow'); ?>>
                        Index, Follow
                    </option>
                    <option value="noindex,follow" <?php selected($seo_data['meta_robots'], 'noindex,follow'); ?>>
                        Noindex, Follow
                    </option>
                    <option value="index,nofollow" <?php selected($seo_data['meta_robots'], 'index,nofollow'); ?>>
                        Index, Nofollow
                    </option>
                    <option value="noindex,nofollow" <?php selected($seo_data['meta_robots'], 'noindex,nofollow'); ?>>
                        Noindex, Nofollow
                    </option>
                </select>
            </div>

            <div class="ps-advanced-options">
                <h4>Additional Meta Tags</h4>
                <div class="ps-form-group">
                    <label for="ps_structured_data">Structured Data</label>
                    <textarea id="ps_structured_data" name="productscraper_seo[structured_data]"
                        class="ps-form-control" rows="4"
                        placeholder="Add custom JSON-LD structured data"><?php echo esc_textarea($seo_data['structured_data'] ?? ''); ?></textarea>
                </div>
            </div>
        </div>

        <!-- Schema Tab -->
        <div class="ps-tab-content" id="ps-tab-schema">
            <div class="ps-form-group">
                <label for="ps_schema_type">
                    <strong>Schema Type</strong>
                    <span class="ps-help-text">Select the appropriate schema type for this content</span>
                </label>
                <select id="ps_schema_type" name="productscraper_seo[schema_type]" class="ps-form-control">
                    <option value="Article" <?php selected($seo_data['schema_type'], 'Article'); ?>>Article</option>
                    <option value="BlogPosting" <?php selected($seo_data['schema_type'], 'BlogPosting'); ?>>Blog Posting</option>
                    <option value="NewsArticle" <?php selected($seo_data['schema_type'], 'NewsArticle'); ?>>News Article</option>
                    <option value="Product" <?php selected($seo_data['schema_type'], 'Product'); ?>>Product</option>
                    <option value="Service" <?php selected($seo_data['schema_type'], 'Service'); ?>>Service</option>
                    <option value="Recipe" <?php selected($seo_data['schema_type'], 'Recipe'); ?>>Recipe</option>
                    <option value="Event" <?php selected($seo_data['schema_type'], 'Event'); ?>>Event</option>
                    <option value="Organization" <?php selected($seo_data['schema_type'], 'Organization'); ?>>Organization</option>
                    <option value="Person" <?php selected($seo_data['schema_type'], 'Person'); ?>>Person</option>
                    <option value="Custom" <?php selected($seo_data['schema_type'], 'Custom'); ?>>Custom</option>
                </select>
            </div>

            <div class="ps-schema-preview">
                <h4>Schema Markup Preview</h4>
                <div class="ps-schema-code" id="ps_schema_preview">
                    <pre><code>{
    "@context": "https://schema.org",
    "@type": "<?php echo esc_html($seo_data['schema_type']); ?>",
    "headline": "<?php echo esc_html(get_the_title()); ?>",
    "description": "<?php echo esc_html(get_the_excerpt()); ?>",
    "datePublished": "<?php echo esc_html(get_the_date('c')); ?>",
    "dateModified": "<?php echo esc_html(get_the_modified_date('c')); ?>"
}</code></pre>
                </div>
            </div>

            <div class="ps-form-group" id="ps_custom_schema_group"
                style="<?php echo ($seo_data['schema_type'] === 'Custom') ? '' : 'display: none;'; ?>">
                <label for="ps_custom_schema">
                    <strong>Custom Schema Markup</strong>
                    <span class="ps-help-text">Enter custom JSON-LD schema markup</span>
                </label>
                <textarea id="ps_custom_schema" name="productscraper_seo[custom_schema]"
                    class="ps-form-control" rows="8"
                    placeholder='{
    "@context": "https://schema.org",
    "@type": "YourCustomType",
    "name": "Example"
}'><?php echo esc_textarea($seo_data['custom_schema']); ?></textarea>
            </div>

            <div class="ps-schema-validation">
                <button type="button" class="button" id="ps_validate_schema">
                    Validate Schema
                </button>
                <span class="ps-validation-result" id="ps_validation_result"></span>
            </div>
        </div>
    </div>
</div>

<style>
    .productscraper-seo-meta-box {
        font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
    }

    .ps-seo-tabs {
        border: 1px solid #ccd0d4;
        background: #fff;
    }

    .ps-tab-nav {
        display: flex;
        border-bottom: 1px solid #ccd0d4;
        background: #f8f9fa;
    }

    .ps-tab-button {
        padding: 12px 16px;
        background: none;
        border: none;
        border-bottom: 3px solid transparent;
        cursor: pointer;
        font-size: 13px;
        color: #646970;
    }

    .ps-tab-button.active {
        border-bottom-color: #2271b1;
        color: #2271b1;
        background: #fff;
    }

    .ps-tab-content {
        display: none;
        padding: 16px;
    }

    .ps-tab-content.active {
        display: block;
    }

    .ps-form-group {
        margin-bottom: 16px;
    }

    .ps-form-group label {
        display: block;
        margin-bottom: 4px;
        font-weight: 600;
    }

    .ps-form-control {
        width: 100%;
        padding: 6px 8px;
        border: 1px solid #8c8f94;
        border-radius: 4px;
        font-size: 14px;
    }

    .ps-help-text {
        display: block;
        font-weight: normal;
        color: #646970;
        font-size: 12px;
        margin-top: 2px;
    }

    .ps-character-count {
        float: right;
        font-size: 12px;
        color: #646970;
    }

    .ps-preview {
        margin-top: 8px;
        padding: 8px;
        background: #f6f7f7;
        border-radius: 4px;
        font-size: 12px;
    }

    .ps-preview-title {
        color: #1a0dab;
        font-size: 18px;
        line-height: 1.2;
        margin-bottom: 4px;
    }

    .ps-preview-description {
        color: #4d5156;
        line-height: 1.4;
    }

    .ps-seo-score {
        border: 1px solid #ccd0d4;
        border-radius: 4px;
        padding: 12px;
        background: #f8f9fa;
    }

    .ps-score-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 8px;
    }

    .ps-score-badge {
        padding: 4px 8px;
        background: #d63638;
        color: #fff;
        border-radius: 12px;
        font-size: 12px;
        font-weight: bold;
    }

    .ps-score-badge.good {
        background: #00a32a;
    }

    .ps-score-badge.ok {
        background: #dba617;
    }

    .ps-score-badge.poor {
        background: #d63638;
    }

    .ps-score-item {
        display: flex;
        align-items: center;
        margin-bottom: 4px;
        font-size: 12px;
    }

    .ps-check-icon {
        width: 16px;
        height: 16px;
        margin-right: 8px;
        border-radius: 50%;
        background: #dcdcde;
    }

    .ps-check-icon.checked {
        background: #00a32a url('data:image/svg+xml;utf8,<svg viewBox="0 0 20 20" xmlns="http://www.w3.org/2000/svg"><path fill="white" d="M14.4 6.4L9 11.8 5.6 8.4 4 10l5 5 7-7z"/></svg>') center no-repeat;
    }

    .ps-image-upload {
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .ps-image-preview {
        width: 100px;
        height: 100px;
        border: 1px solid #ccd0d4;
        border-radius: 4px;
        display: flex;
        align-items: center;
        justify-content: center;
        background: #f8f9fa;
    }

    .ps-image-preview img {
        max-width: 100%;
        max-height: 100%;
    }

    .ps-no-image {
        color: #646970;
        font-size: 12px;
    }

    .ps-social-section {
        margin-bottom: 20px;
        padding-bottom: 20px;
        border-bottom: 1px solid #ccd0d4;
    }

    .ps-social-preview {
        margin-top: 20px;
    }

    .ps-preview-tabs {
        display: flex;
        margin-bottom: 12px;
    }

    .ps-preview-tab {
        padding: 8px 16px;
        background: #f8f9fa;
        border: 1px solid #ccd0d4;
        border-right: none;
        cursor: pointer;
    }

    .ps-preview-tab:first-child {
        border-radius: 4px 0 0 4px;
    }

    .ps-preview-tab:last-child {
        border-right: 1px solid #ccd0d4;
        border-radius: 0 4px 4px 0;
    }

    .ps-preview-tab.active {
        background: #2271b1;
        color: #fff;
    }

    .ps-preview-content {
        display: none;
    }

    .ps-preview-content.active {
        display: block;
    }

    .ps-facebook-card,
    .ps-twitter-card {
        border: 1px solid #dadde1;
        border-radius: 8px;
        overflow: hidden;
        max-width: 500px;
    }

    .ps-facebook-image,
    .ps-twitter-image {
        height: 262px;
        background: #f0f2f5;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .ps-twitter-image {
        height: 280px;
    }

    .ps-facebook-content,
    .ps-twitter-content {
        padding: 10px 12px;
        background: #fff;
    }

    .ps-facebook-title,
    .ps-twitter-title {
        font-weight: 600;
        color: #1d2129;
        margin-bottom: 4px;
        font-size: 16px;
        line-height: 1.25;
    }

    .ps-twitter-title {
        font-size: 15px;
    }

    .ps-facebook-description,
    .ps-twitter-description {
        color: #606770;
        font-size: 14px;
        line-height: 1.33;
    }

    .ps-twitter-description {
        color: #536471;
    }

    .ps-facebook-url,
    .ps-twitter-url {
        color: #606770;
        font-size: 12px;
        text-transform: uppercase;
        margin-top: 4px;
    }

    .ps-schema-code {
        background: #f6f7f7;
        border: 1px solid #ccd0d4;
        border-radius: 4px;
        padding: 12px;
        font-family: 'Courier New', monospace;
        font-size: 12px;
        overflow-x: auto;
    }

    .ps-schema-validation {
        margin-top: 12px;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .ps-validation-result {
        font-size: 12px;
        padding: 4px 8px;
        border-radius: 4px;
    }

    .ps-validation-result.success {
        background: #d1e7dd;
        color: #0f5132;
    }

    .ps-validation-result.error {
        background: #f8d7da;
        color: #721c24;
    }

    .ps-keyword-suggestions {
        margin-top: 4px;
        border: 1px solid #ccd0d4;
        border-radius: 4px;
        background: #fff;
        max-height: 150px;
        overflow-y: auto;
        display: none;
    }

    .ps-keyword-suggestion {
        padding: 8px 12px;
        cursor: pointer;
        border-bottom: 1px solid #f0f0f0;
    }

    .ps-keyword-suggestion:hover {
        background: #f8f9fa;
    }

    .ps-keyword-suggestion:last-child {
        border-bottom: none;
    }
</style>

<script>
    jQuery(document).ready(function($) {
        // Tab functionality
        $('.ps-tab-button').on('click', function() {
            var tab = $(this).data('tab');

            $('.ps-tab-button').removeClass('active');
            $('.ps-tab-content').removeClass('active');

            $(this).addClass('active');
            $('#ps-tab-' + tab).addClass('active');
        });

        // Preview tab functionality
        $('.ps-preview-tab').on('click', function() {
            var preview = $(this).data('preview');

            $('.ps-preview-tab').removeClass('active');
            $('.ps-preview-content').removeClass('active');

            $(this).addClass('active');
            $('.ps-' + preview + '-preview').addClass('active');
        });

        // Character counters
        function updateCharacterCount() {
            var title = $('#ps_meta_title').val();
            var description = $('#ps_meta_description').val();

            $('#ps_title_count').text(title.length);
            $('#ps_description_count').text(description.length);

            // Update previews
            $('#ps_title_preview').text(title || '<?php echo esc_js(wp_get_document_title()); ?>');
            $('#ps_description_preview').text(description || 'No meta description set.');
        }

        $('#ps_meta_title, #ps_meta_description').on('input', updateCharacterCount);
        updateCharacterCount();

        // Image upload functionality
        $('.ps-upload-button').on('click', function() {
            var target = $(this).data('target');
            var frame = wp.media({
                title: 'Select Image',
                multiple: false,
                library: {
                    type: 'image'
                }
            });

            frame.on('select', function() {
                var attachment = frame.state().get('selection').first().toJSON();
                $('#' + target).val(attachment.id);
                $('#' + target + '_preview').html('<img src="' + attachment.url + '" alt="Preview">');
            });

            frame.open();
        });

        $('.ps-remove-button').on('click', function() {
            var target = $(this).data('target');
            $('#' + target).val('');
            $('#' + target + '_preview').html('<div class="ps-no-image">No image set</div>');
        });

        // Schema type change
        $('#ps_schema_type').on('change', function() {
            if ($(this).val() === 'Custom') {
                $('#ps_custom_schema_group').show();
            } else {
                $('#ps_custom_schema_group').hide();
            }
        });

        // Keyword suggestions
        $('#ps_focus_keyword').on('input', function() {
            var keyword = $(this).val();
            if (keyword.length > 2) {
                // Simulate API call for keyword suggestions
                setTimeout(function() {
                    var suggestions = [
                        keyword + ' 2024',
                        'best ' + keyword,
                        keyword + ' review',
                        'buy ' + keyword,
                        keyword + ' price'
                    ];

                    var $suggestions = $('#ps_keyword_suggestions');
                    $suggestions.empty();

                    suggestions.forEach(function(suggestion) {
                        $suggestions.append('<div class="ps-keyword-suggestion">' + suggestion + '</div>');
                    });

                    $suggestions.show();
                }, 500);
            } else {
                $('#ps_keyword_suggestions').hide();
            }
        });

        $(document).on('click', '.ps-keyword-suggestion', function() {
            $('#ps_focus_keyword').val($(this).text());
            $('#ps_keyword_suggestions').hide();
        });

        // SEO score calculation
        function calculateSeoScore() {
            var score = 0;
            var checks = {
                title: $('#ps_meta_title').val().length > 0 && $('#ps_meta_title').val().length <= 60,
                description: $('#ps_meta_description').val().length > 0 && $('#ps_meta_description').val().length <= 160,
                keyword: $('#ps_focus_keyword').val().length > 0,
                content: $('body').text().length > 300
            };

            $('.ps-score-item').each(function() {
                var check = $(this).data('check');
                var $icon = $(this).find('.ps-check-icon');

                if (checks[check]) {
                    score += 25;
                    $icon.addClass('checked');
                } else {
                    $icon.removeClass('checked');
                }
            });

            var $badge = $('#ps_seo_score');
            $badge.text(score + '%');
            $badge.removeClass('good ok poor');

            if (score >= 75) {
                $badge.addClass('good');
            } else if (score >= 50) {
                $badge.addClass('ok');
            } else {
                $badge.addClass('poor');
            }
        }

        // Calculate initial score
        calculateSeoScore();

        // Update score on changes
        $('#ps_meta_title, #ps_meta_description, #ps_focus_keyword').on('input', calculateSeoScore);
    });
</script>