jQuery(document).ready(function($) {
    // Content Analysis
    $('#analyze-content').on('click', function() {
        const content = $('#content-to-analyze').val();
        const focusKeyword = $('#focus-keyword').val();
        
        if (!content) {
            alert('Please enter some content to analyze');
            return;
        }
        
        $(this).prop('disabled', true).text('Analyzing...');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'analyze_content',
                content: content,
                focus_keyword: focusKeyword,
                nonce: seo_admin_nonce
            },
            success: function(response) {
                if (response.success) {
                    displayAnalysisResults(response.data);
                } else {
                    alert('Error analyzing content');
                }
            },
            complete: function() {
                $('#analyze-content').prop('disabled', false).text('Analyze Content');
            }
        });
    });
    
    function displayAnalysisResults(data) {
        let html = `
            <div class="analysis-result">
                <h4>Content Analysis Results</h4>
                <div class="analysis-metric">
                    <span>Word Count:</span>
                    <span class="${data.word_count >= 300 ? 'metric-good' : 'metric-bad'}">${data.word_count} words</span>
                </div>
                <div class="analysis-metric">
                    <span>Reading Time:</span>
                    <span>${data.reading_time} minutes</span>
                </div>
                <div class="analysis-metric">
                    <span>Readability Score:</span>
                    <span class="${data.readability_score >= 60 ? 'metric-good' : 'metric-warning'}">${data.readability_score}/100</span>
                </div>
        `;
        
        if (data.keyword_density > 0) {
            html += `
                <div class="analysis-metric">
                    <span>Keyword Density:</span>
                    <span class="${data.keyword_density >= 1 && data.keyword_density <= 3 ? 'metric-good' : 'metric-warning'}">${data.keyword_density}%</span>
                </div>
            `;
        }
        
        html += `</div>`;
        
        // Recommendations
        if (data.recommendations.length > 0) {
            html += `<h4>Recommendations</h4>`;
            data.recommendations.forEach(rec => {
                html += `<div class="recommendation ${rec.priority}">${rec.message}</div>`;
            });
        }
        
        $('#analysis-results').html(html).show();
    }
    
    // Keyword Research
    $('#research-keywords').on('click', function() {
        const keyword = $('#research-keyword').val();
        
        if (!keyword) {
            alert('Please enter a keyword to research');
            return;
        }
        
        $(this).prop('disabled', true).text('Researching...');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'research_keywords',
                keyword: keyword,
                nonce: seo_admin_nonce
            },
            success: function(response) {
                if (response.success) {
                    displayKeywordResearch(response.data);
                } else {
                    alert('Error researching keywords');
                }
            },
            complete: function() {
                $('#research-keywords').prop('disabled', false).text('Research Keywords');
            }
        });
    });
    
    // Content Optimization
    $('.optimize-content').on('click', function() {
        const content = $('#content-to-analyze').val();
        const optimizationType = $(this).data('type');
        
        if (!content) {
            alert('Please enter some content to optimize');
            return;
        }
        
        $(this).prop('disabled', true).text('Optimizing...');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'optimize_content',
                content: content,
                optimization_type: optimizationType,
                nonce: seo_admin_nonce
            },
            success: function(response) {
                if (response.success) {
                    $('#content-to-analyze').val(response.data.optimized_content);
                    alert('Content optimized! Changes: ' + response.data.changes_made.join(', '));
                } else {
                    alert('Error optimizing content');
                }
            },
            complete: function() {
                $('.optimize-content').prop('disabled', false).text('Optimize');
            }
        });
    });
});