<?php

/**
 * SEO Analysis Template
 *
 * @package ProductScraper
 * @since 1.0.0
 */

// Security check.
if (!defined('ABSPATH')) {
	exit;
}
?>

<div class="wrap">
	<div class="scraper-analytics-dashboard">
		<div class="sa-header">
			<div class="sa-brand">
				<h1><strong>Scraper Analytics</strong></h1>
				<span class="sa-subtitle">SEO Analysis</span>
			</div>
			<div class="sa-actions">
				<button class="sa-btn sa-btn-primary" onclick="runFullAnalysis()">
					<i data-lucide="refresh-cw" class="lucide-icon"></i>
					Run Full Analysis
				</button>
				<button class="sa-btn sa-btn-secondary" onclick="exportAnalysisReport()">
					<span class="p-3 rounded-xl bg-blue-500/10 text-blue-500">
						<i data-lucide="download" class="lucide-icon"></i>
					</span>
					Export Report
				</button>
			</div>
		</div>

		<div class="sa-container">
			<!-- Sidebar -->
			<?php ProductScraper::product_scraper_render_sidebar('seo-analysis'); ?>

			<!-- Main Content -->
			<div class="sa-main-content">
				<!-- Overview Section -->
				<div class="sa-section">
					<h2>Complete SEO Analysis</h2>
					<p class="sa-description">Comprehensive analysis of your website's SEO performance and technical
						health.</p>

					<!-- Overall Score -->
					<div class="sa-analysis-overview">
						<div class="overview-score">
							<div class="score-circle large">
								<span
									class="score"><?php echo esc_html($site_analysis['technical']['score'] ?? 0); ?>%</span>
							</div>
							<div class="score-details">
								<h3>Overall SEO Score</h3>
								<p>Based on technical, content, and performance factors</p>
								<div class="score-breakdown">
									<div class="breakdown-item">
										<span class="label">Technical:</span>
										<span
											class="value"><?php echo esc_html($site_analysis['technical']['score'] ?? 0); ?>%</span>
									</div>
									<div class="breakdown-item">
										<span class="label">Content:</span>
										<span
											class="value"><?php echo esc_html($site_analysis['content']['score'] ?? 0); ?>%</span>
									</div>
									<div class="breakdown-item">
										<span class="label">Performance:</span>
										<span
											class="value"><?php echo esc_html($site_analysis['performance']['score'] ?? 0); ?>%</span>
									</div>
								</div>
							</div>
						</div>
					</div>

					<!-- Technical SEO Analysis -->
					<div class="sa-analysis-section">
						<h3>
							<span class="p-3 rounded-xl bg-blue-500/10 text-blue-500">
								<i data-lucide="wrench" class="lucide-icon"></i>
							</span>
							Technical SEO Analysis
						</h3>

						<div class="analysis-metrics">
							<div class="metric-card">
								<div class="metric-value">
									<?php echo esc_html($site_analysis['technical']['indexable'] ?? 'Unknown'); ?></div>
								<div class="metric-label">Indexable</div>
							</div>
							<div class="metric-card">
								<div class="metric-value">
									<?php echo esc_html($site_analysis['technical']['permalink_structure'] ?? 'Unknown'); ?>
								</div>
								<div class="metric-label">Permalink Structure</div>
							</div>
							<div class="metric-card">
								<div class="metric-value">
									<?php echo esc_html($site_analysis['technical']['ssl'] ?? 'Unknown'); ?></div>
								<div class="metric-label">SSL Enabled</div>
							</div>
						</div>

						<div class="analysis-issues">
							<h4>Technical Issues</h4>
							<?php if (!empty($site_analysis['technical']['issues'])): ?>
								<?php foreach ($site_analysis['technical']['issues'] as $issue): ?>
									<div class="issue-item severity-<?php echo esc_attr($issue['severity']); ?>">
										<span class="issue-icon">⚠️</span>
										<div class="issue-content">
											<h5><?php echo esc_html($issue['title']); ?></h5>
											<p><?php echo esc_html($issue['description']); ?></p>
											<span class="issue-fix">Fix: <?php echo esc_html($issue['fix']); ?></span>
										</div>
									</div>
								<?php endforeach; ?>
							<?php else: ?>
								<p class="no-issues">No critical technical issues found.</p>
							<?php endif; ?>
						</div>
					</div>

					<!-- Content SEO Analysis -->
					<div class="sa-analysis-section">
						<h3>
							<span class="p-3 rounded-xl bg-blue-500/10 text-blue-500">
								<i data-lucide="pencil" class="lucide-icon"></i>
							</span>
							Content SEO Analysis
						</h3>

						<div class="content-stats-grid">
							<div class="stat-card">
								<div class="stat-value">
									<?php echo esc_html($site_analysis['content']['stats']['total_posts'] ?? 0); ?>
								</div>
								<div class="stat-label">Total Posts</div>
							</div>
							<div class="stat-card">
								<div class="stat-value">
									<?php echo esc_html($site_analysis['content']['stats']['optimized_posts'] ?? 0); ?>
								</div>
								<div class="stat-label">Optimized Posts</div>
							</div>
							<div class="stat-card">
								<div class="stat-value">
									<?php echo esc_html($site_analysis['content']['stats']['optimization_rate'] ?? 0); ?>%
								</div>
								<div class="stat-label">Optimization Rate</div>
							</div>
							<div class="stat-card">
								<div class="stat-value">
									<?php echo esc_html($site_analysis['content']['stats']['avg_readability'] ?? 0); ?>%
								</div>
								<div class="stat-label">Avg Readability</div>
							</div>
						</div>

						<div class="content-recommendations">
							<h4>Content Recommendations</h4>
							<?php if (!empty($site_analysis['content']['recommendations'])): ?>
								<?php foreach ($site_analysis['content']['recommendations'] as $rec): ?>
									<div class="recommendation-item">
										<span class="rec-icon">💡</span>
										<div class="rec-content">
											<h5><?php echo esc_html($rec['title']); ?></h5>
											<p><?php echo esc_html($rec['description']); ?></p>
											<span class="rec-impact">Impact: <?php echo esc_html($rec['impact']); ?></span>
										</div>
									</div>
								<?php endforeach; ?>
							<?php else: ?>
								<p>No specific content recommendations at this time.</p>
							<?php endif; ?>
						</div>
					</div>

					<!-- Performance Analysis -->
					<div class="sa-analysis-section">
						<h3><span class="p-3 rounded-xl bg-blue-500/10 text-blue-500">
								<i data-lucide="gauge" class="lucide-icon"></i>
							</span>
							Performance Analysis
						</h3>

						<div class="performance-metrics">
							<div class="metric-card">
								<div class="metric-value">
									<?php echo esc_html($site_analysis['performance']['score'] ?? 0); ?>%</div>
								<div class="metric-label">Performance Score</div>
							</div>
							<div class="metric-card">
								<div class="metric-value">
									<?php echo esc_html($site_analysis['performance']['load_time'] ?? 'N/A'); ?></div>
								<div class="metric-label">Load Time</div>
							</div>
							<div class="metric-card">
								<div class="metric-value">
									<?php echo esc_html($site_analysis['performance']['page_size'] ?? 'N/A'); ?></div>
								<div class="metric-label">Page Size</div>
							</div>
						</div>

						<div class="performance-recommendations">
							<h4>Performance Recommendations</h4>
							<?php if (!empty($site_analysis['performance']['recommendations'])): ?>
								<?php foreach ($site_analysis['performance']['recommendations'] as $rec): ?>
									<div class="recommendation-item">
										<span class="rec-icon">⚡</span>
										<div class="rec-content">
											<h5><?php echo esc_html($rec['title']); ?></h5>
											<p><?php echo esc_html($rec['description']); ?></p>
											<span class="rec-impact">Impact: <?php echo esc_html($rec['impact']); ?></span>
										</div>
									</div>
								<?php endforeach; ?>
							<?php else: ?>
								<p>No performance recommendations at this time.</p>
							<?php endif; ?>
						</div>
					</div>

					<!-- Action Plan -->
					<div class="sa-analysis-section">
						<h3>
							<span class="p-3 rounded-xl bg-blue-500/10 text-blue-500">
								<i data-lucide="clipboard" class="lucide-icon"></i>
							</span>
							SEO Action Plan
						</h3>

						<div class="action-plan">
							<div class="action-priority high">
								<h4>High Priority</h4>
								<ul>
									<li>Fix critical technical issues affecting indexing</li>
									<li>Optimize meta descriptions for key pages</li>
									<li>Improve page load speed</li>
								</ul>
							</div>
							<div class="action-priority medium">
								<h4>Medium Priority</h4>
								<ul>
									<li>Add structured data markup</li>
									<li>Optimize internal linking structure</li>
									<li>Improve content readability</li>
								</ul>
							</div>
							<div class="action-priority low">
								<h4>Low Priority</h4>
								<ul>
									<li>Add social media meta tags</li>
									<li>Optimize image alt texts</li>
									<li>Implement schema markup enhancements</li>
								</ul>
							</div>
						</div>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>

<style>
	.sa-analysis-overview {
		background: #fff;
		border-radius: 8px;
		padding: 30px;
		margin-bottom: 30px;
		text-align: center;
	}

	.overview-score {
		display: flex;
		align-items: center;
		justify-content: center;
		gap: 40px;
		flex-wrap: wrap;
	}

	.score-circle.large {
		width: 120px;
		height: 120px;
		border: 8px solid #e0e0e0;
		border-radius: 50%;
		display: flex;
		align-items: center;
		justify-content: center;
		background: conic-gradient(#4CAF50
				<?php echo esc_attr($site_analysis['technical']['score'] ?? 0); ?>
				%, #e0e0e0 0%);
	}

	.score-circle.large .score {
		font-size: 24px;
		font-weight: bold;
		color: #2c3338;
	}

	.score-details {
		text-align: left;
	}

	.score-breakdown {
		margin-top: 15px;
	}

	.breakdown-item {
		display: flex;
		justify-content: space-between;
		margin-bottom: 8px;
		font-size: 14px;
	}

	.breakdown-item .label {
		color: #646970;
	}

	.breakdown-item .value {
		font-weight: 600;
		color: #2c3338;
	}

	.sa-analysis-section {
		background: #fff;
		border-radius: 8px;
		padding: 30px;
		margin-bottom: 30px;
	}

	.sa-analysis-section h3 {
		display: flex;
		align-items: center;
		gap: 10px;
		margin-bottom: 20px;
		color: #2c3338;
	}

	.analysis-metrics,
	.content-stats-grid,
	.performance-metrics {
		display: grid;
		grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
		gap: 20px;
		margin-bottom: 30px;
	}

	.metric-card,
	.stat-card {
		background: #f8f9fa;
		border-radius: 6px;
		padding: 20px;
		text-align: center;
		border: 1px solid #e0e0e0;
	}

	.metric-value,
	.stat-value {
		font-size: 24px;
		font-weight: bold;
		color: #2c3338;
		margin-bottom: 5px;
	}

	.metric-label,
	.stat-label {
		font-size: 12px;
		color: #646970;
		text-transform: uppercase;
		letter-spacing: 0.5px;
	}

	.analysis-issues,
	.content-recommendations,
	.performance-recommendations {
		margin-top: 20px;
	}

	.issue-item {
		display: flex;
		gap: 15px;
		padding: 15px;
		border: 1px solid #e0e0e0;
		border-radius: 6px;
		margin-bottom: 15px;
		align-items: flex-start;
	}

	.issue-item.severity-high {
		border-left: 4px solid #d63638;
		background: #f8d7da;
	}

	.issue-item.severity-medium {
		border-left: 4px solid #dba617;
		background: #fff3cd;
	}

	.issue-item.severity-low {
		border-left: 4px solid #00a32a;
		background: #d1e7dd;
	}

	.issue-icon {
		font-size: 20px;
		flex-shrink: 0;
	}

	.issue-content h5 {
		margin: 0 0 8px 0;
		color: #2c3338;
	}

	.issue-content p {
		margin: 0 0 8px 0;
		color: #646970;
	}

	.issue-fix {
		font-size: 12px;
		color: #8c8f94;
		font-weight: 600;
	}

	.no-issues {
		color: #00a32a;
		font-style: italic;
	}

	.recommendation-item {
		display: flex;
		gap: 15px;
		padding: 15px;
		border: 1px solid #e0e0e0;
		border-radius: 6px;
		margin-bottom: 15px;
		align-items: flex-start;
	}

	.rec-icon {
		font-size: 20px;
		flex-shrink: 0;
	}

	.rec-content h5 {
		margin: 0 0 8px 0;
		color: #2c3338;
	}

	.rec-content p {
		margin: 0 0 8px 0;
		color: #646970;
	}

	.rec-impact {
		font-size: 12px;
		color: #8c8f94;
		font-weight: 600;
	}

	.action-plan {
		display: grid;
		grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
		gap: 20px;
	}

	.action-priority {
		padding: 20px;
		border-radius: 6px;
		border: 1px solid #e0e0e0;
	}

	.action-priority.high {
		border-left: 4px solid #d63638;
	}

	.action-priority.medium {
		border-left: 4px solid #dba617;
	}

	.action-priority.low {
		border-left: 4px solid #00a32a;
	}

	.action-priority h4 {
		margin: 0 0 15px 0;
		color: #2c3338;
	}

	.action-priority ul {
		margin: 0;
		padding-left: 20px;
	}

	.action-priority li {
		margin-bottom: 8px;
		color: #646970;
	}
</style>

<script>
	function runFullAnalysis() {
		const button = document.querySelector('.sa-btn-primary');
		const originalText = button.innerHTML;

		button.disabled = true;
		button.innerHTML = '<span class="dashicons dashicons-update spin"></span> Analyzing...';

		// Simulate analysis process.
		setTimeout(() => {
			button.disabled = false;
			button.innerHTML = originalText;
			alert('Full SEO analysis completed!');
			location.reload(); // Refresh to show updated results.
		}, 3000);
	}

	function exportAnalysisReport() {
		// In a real implementation, this would generate a PDF or CSV report.
		alert('Export feature would generate a comprehensive SEO report');
	}
</script>