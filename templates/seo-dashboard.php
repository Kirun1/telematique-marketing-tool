<div class="wrap">
	<div class="scraper-analytics-dashboard">
		<div class="sa-header">
			<div class="sa-brand">
				<h1><strong>SEO Assistant</strong></h1>
				<span class="sa-subtitle">Optimize your content for search engines</span>
			</div>
		</div>

		<div class="sa-container">
			<!-- Sidebar -->
			<?php ProductScraper::product_scraper_render_sidebar( 'seo-assistant' ); ?>

			<div class="sa-main-content">
				<div class="sa-section">
					<h2>SEO Overview</h2>

					<!-- SEO Stats Grid -->
					<div class="sa-stats-grid">
						<div class="sa-stat-card">
							<div class="stat-header">
								<h3>Optimized Posts</h3>
							</div>
							<div class="stat-main">
								<span class="stat-number"><?php echo $stats['optimized_posts']; ?></span>
								<span class="stat-percentage"><?php echo $stats['optimization_rate']; ?>%</span>
							</div>
							<div class="stat-target">
								Total Posts: <?php echo $stats['total_posts']; ?>
							</div>
						</div>

						<div class="sa-stat-card">
							<div class="stat-header">
								<h3>Readability Score</h3>
							</div>
							<div class="stat-main">
								<span class="stat-number"><?php echo $stats['avg_readability']; ?>%</span>
							</div>
							<div class="score-status">
								<?php
								$readability_score = $stats['avg_readability'];
								if ( $readability_score >= 70 ) {
									echo '<span class="status-text status-good">Good</span>';
								} elseif ( $readability_score >= 50 ) {
									echo '<span class="status-text status-warning">Fair</span>';
								} else {
									echo '<span class="status-text status-bad">Poor</span>';
								}
								?>
							</div>
						</div>

						<div class="sa-stat-card">
							<div class="stat-header">
								<h3>SEO Issues</h3>
							</div>
							<div class="stat-main">
								<span class="stat-number"><?php echo $stats['posts_without_meta'] + $stats['low_content_posts']; ?></span>
							</div>
							<div class="stat-target">
								<?php echo $stats['posts_without_meta']; ?> without meta, <?php echo $stats['low_content_posts']; ?> low content
							</div>
						</div>

						<!-- Additional stat card for keyword optimization -->
						<div class="sa-stat-card">
							<div class="stat-header">
								<h3>Keyword Usage</h3>
							</div>
							<div class="stat-main">
								<span class="stat-number"><?php echo $stats['posts_with_focus_keyword']; ?></span>
								<span class="stat-percentage"><?php echo $stats['total_posts'] > 0 ? round( ( $stats['posts_with_focus_keyword'] / $stats['total_posts'] ) * 100 ) : 0; ?>%</span>
							</div>
							<div class="stat-target">
								Posts with focus keywords
							</div>
						</div>
					</div>

					<!-- Content Analysis Tool -->
					<div class="sa-chart-section">
						<h3>Content Analysis</h3>
						<div class="content-analysis-tool">
							<textarea id="content-to-analyze" placeholder="Paste your content here to analyze..." rows="10" style="width: 100%; padding: 15px;"></textarea>
							<div class="analysis-controls">
								<input type="text" id="focus-keyword" placeholder="Focus keyword (optional)" style="padding: 10px; margin-right: 10px;">
								<button id="analyze-content" class="sa-btn sa-btn-primary">Analyze Content</button>
							</div>
							<div id="analysis-results" style="display: none; margin-top: 20px;"></div>
						</div>
					</div>

					<!-- Recent Analysis Section -->
					<?php if ( ! empty( $recent_analysis ) ) : ?>
					<div class="sa-table-section">
						<h3>Recent Content Analysis</h3>
						<div class="sa-table-container">
							<table class="sa-table">
								<thead>
									<tr>
										<th>Post Title</th>
										<th>SEO Score</th>
										<th>Readability</th>
										<th>Word Count</th>
										<th>Actions</th>
									</tr>
								</thead>
								<tbody>
									<?php foreach ( $recent_analysis as $analysis ) : ?>
									<tr>
										<td>
											<strong><?php echo esc_html( $analysis['title'] ); ?></strong>
										</td>
										<td>
											<div class="score-circle" data-score="<?php echo $analysis['analysis']['score'] ?? 0; ?>">
												<?php echo $analysis['analysis']['score'] ?? 0; ?>%
											</div>
										</td>
										<td>
											<?php
											$readability = $analysis['analysis']['readability']['flesch_score'] ?? 0;
											echo $readability > 0 ? $readability . '%' : 'N/A';
											?>
										</td>
										<td>
											<?php
											$word_count = $analysis['analysis']['word_count'] ?? 0;
											echo $word_count > 0 ? $word_count : 'N/A';
											?>
										</td>
										<td>
											<a href="<?php echo $analysis['edit_url']; ?>" class="sa-btn sa-btn-small">Edit</a>
										</td>
									</tr>
									<?php endforeach; ?>
								</tbody>
							</table>
						</div>
					</div>
					<?php endif; ?>

					<!-- Quick Actions -->
					<div class="sa-table-section">
						<h3>Quick SEO Actions</h3>
						<div class="quick-actions-grid">
							<div class="quick-action-card">
								<h4>Site Audit</h4>
								<p>Comprehensive SEO audit of your entire website</p>
								<button class="sa-btn sa-btn-secondary">Run Audit</button>
							</div>
							<div class="quick-action-card">
								<h4>Keyword Research</h4>
								<p>Find profitable keywords for your content</p>
								<button class="sa-btn sa-btn-secondary">Research Keywords</button>
							</div>
							<div class="quick-action-card">
								<h4>Competitor Analysis</h4>
								<p>Analyze competitor strategies</p>
								<button class="sa-btn sa-btn-secondary">Analyze Competitors</button>
							</div>
							<div class="quick-action-card">
								<h4>Fix SEO Issues</h4>
								<p>Address <?php echo $stats['posts_without_meta']; ?> posts without meta descriptions</p>
								<button class="sa-btn sa-btn-secondary">Fix Issues</button>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<style>
	.quick-actions-grid {
		display: grid;
		grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
		gap: 20px;
		margin-top: 20px;
	}

	.quick-action-card {
		background: white;
		padding: 20px;
		border-radius: 8px;
		border: 1px solid #e1e5e9;
	}

	.quick-action-card h4 {
		margin: 0 0 10px 0;
		color: #1a1a1a;
	}

	.quick-action-card p {
		color: #666;
		margin-bottom: 15px;
		font-size: 14px;
	}

	/* Score status styles */
	.status-good {
		color: #28a745;
		font-weight: bold;
	}

	.status-warning {
		color: #ffc107;
		font-weight: bold;
	}

	.status-bad {
		color: #dc3545;
		font-weight: bold;
	}

	/* Score circle styles */
	.score-circle {
		display: inline-block;
		width: 50px;
		height: 50px;
		border-radius: 50%;
		line-height: 50px;
		text-align: center;
		font-weight: bold;
		color: white;
	}

	.score-circle[data-score] {
		background-color: #28a745; /* Default green */
	}

	.score-circle[data-score="0"] {
		background-color: #6c757d;
	}

	.score-circle[data-score^="1"],
	.score-circle[data-score^="2"],
	.score-circle[data-score^="3"],
	.score-circle[data-score^="4"] {
		background-color: #dc3545; /* Red for 0-49 */
	}

	.score-circle[data-score^="5"],
	.score-circle[data-score^="6"] {
		background-color: #ffc107; /* Yellow for 50-69 */
		color: #000;
	}

	.score-circle[data-score^="7"],
	.score-circle[data-score^="8"],
	.score-circle[data-score^="9"] {
		background-color: #28a745; /* Green for 70-100 */
	}

	/* Table styles */
	.sa-table-container {
		background: white;
		border-radius: 8px;
		border: 1px solid #e1e5e9;
		overflow: hidden;
	}

	.sa-table {
		width: 100%;
		border-collapse: collapse;
	}

	.sa-table th {
		background: #f8f9fa;
		padding: 12px 15px;
		text-align: left;
		font-weight: 600;
		border-bottom: 1px solid #e1e5e9;
	}

	.sa-table td {
		padding: 12px 15px;
		border-bottom: 1px solid #f0f0f0;
	}

	.sa-table tr:last-child td {
		border-bottom: none;
	}

	.sa-btn-small {
		padding: 4px 12px;
		font-size: 12px;
	}
</style>

<script>
jQuery(document).ready(function($) {
	// Content analysis functionality.
	$('#analyze-content').on('click', function() {
		var content = $('#content-to-analyze').val();
		var keyword = $('#focus-keyword').val();
		
		if (!content.trim()) {
			alert('Please enter some content to analyze.');
			return;
		}

		var button = $(this);
		var originalText = button.text();
		button.text('Analyzing...').prop('disabled', true);

		$.ajax({
			url: ajaxurl,
			type: 'POST',
			data: {
				action: 'analyze_content',
				content: content,
				keyword: keyword,
				nonce: '<?php echo wp_create_nonce( 'product_scraper_nonce' ); ?>'
			},
			success: function(response) {
				button.text(originalText).prop('disabled', false);
				
				if (response.success) {
					displayAnalysisResults(response.data);
				} else {
					alert('Error analyzing content: ' + response.data);
				}
			},
			error: function() {
				button.text(originalText).prop('disabled', false);
				alert('Error analyzing content. Please try again.');
			}
		});
	});

	function displayAnalysisResults(analysis) {
		var results = $('#analysis-results');
		var html = '<div class="analysis-results">';
		
		html += '<div class="overall-score">';
		html += '<h4>Overall SEO Score: <span class="score-' + getScoreClass(analysis.score) + '">' + analysis.score + '%</span></h4>';
		html += '</div>';
		
		if (analysis.issues.length > 0) {
			html += '<div class="analysis-section issues">';
			html += '<h5>Issues to Fix:</h5>';
			html += '<ul>';
			analysis.issues.forEach(function(issue) {
				html += '<li class="severity-' + issue.severity + '">' + issue.message + '</li>';
			});
			html += '</ul>';
			html += '</div>';
		}
		
		if (analysis.improvements.length > 0) {
			html += '<div class="analysis-section improvements">';
			html += '<h5>Suggested Improvements:</h5>';
			html += '<ul>';
			analysis.improvements.forEach(function(improvement) {
				html += '<li class="severity-' + improvement.severity + '">' + improvement.message + '</li>';
			});
			html += '</ul>';
			html += '</div>';
		}
		
		if (analysis.good.length > 0) {
			html += '<div class="analysis-section good">';
			html += '<h5>Good Practices:</h5>';
			html += '<ul>';
			analysis.good.forEach(function(good) {
				html += '<li class="severity-' + good.severity + '">' + good.message + '</li>';
			});
			html += '</ul>';
			html += '</div>';
		}
		
		if (analysis.readability) {
			html += '<div class="analysis-section readability">';
			html += '<h5>Readability: ' + analysis.readability.description + '</h5>';
			html += '<p>Flesch Score: ' + analysis.readability.flesch_score + ' (' + analysis.readability.grade_level + ' level)</p>';
			html += '</div>';
		}
		
		html += '</div>';
		
		results.html(html).show();
	}

	function getScoreClass(score) {
		if (score >= 80) return 'excellent';
		if (score >= 60) return 'good';
		if (score >= 40) return 'fair';
		return 'poor';
	}
});
</script>
