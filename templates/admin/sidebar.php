<?php
/**
 * Sidebar Template
 *
 * @package ProductScraper
 * @since 1.0.0
 */

// Security check.
if (!defined('ABSPATH')) {
	exit;
}

// Get current page to set active state.
// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Used only to determine the active admin menu item.
$current_page = isset($_GET['page']) ? sanitize_text_field(wp_unslash($_GET['page'])) : '';
?>

<div class="sa-sidebar">
	<div class="sa-nav-section">
		<h3>ANALYSIS</h3>
		<ul class="sa-nav">
			<li class="<?php echo 'scraper-analytics' === $current_page ? 'active' : ''; ?>">
				<a href="<?php echo esc_url(admin_url('admin.php?page=scraper-analytics')); ?>">
					<span class="dashicons dashicons-dashboard"></span> &nbsp; Dashboard
				</a>
			</li>
			<li class="<?php echo 'scraper-keywords' === $current_page ? 'active' : ''; ?>">
				<a href="<?php echo esc_url(admin_url('admin.php?page=scraper-keywords')); ?>">
					<span class="dashicons dashicons-tag"></span> &nbsp; Keywords
				</a>
			</li>
			<li class="<?php echo 'scraper-competitors' === $current_page ? 'active' : ''; ?>">
				<a href="<?php echo esc_url(admin_url('admin.php?page=scraper-competitors')); ?>">
					<span class="dashicons dashicons-chart-bar"></span> &nbsp; Competitors
				</a>
			</li>
		</ul>
	</div>

	<div class="sa-nav-section">
		<h3>SEO TOOLS</h3>
		<ul class="sa-nav">
			<li class="<?php echo 'seo-assistant' === $current_page ? 'active' : ''; ?>">
				<a href="<?php echo esc_url(admin_url('admin.php?page=seo-assistant')); ?>">
					<span class="dashicons dashicons-editor-spellcheck"></span> &nbsp; SEO Assistant
				</a>
			</li>
			<li class="<?php echo 'seo-analysis' === $current_page ? 'active' : ''; ?>">
				<a href="<?php echo esc_url(admin_url('admin.php?page=seo-analysis')); ?>">
					<span class="dashicons dashicons-chart-line"></span> &nbsp; SEO Analysis
				</a>
			</li>
			<li class="<?php echo 'link-manager' === $current_page ? 'active' : ''; ?>">
				<a href="<?php echo esc_url(admin_url('admin.php?page=link-manager')); ?>">
					<i data-lucide="link" class="lucide-icon"></i>&nbsp; Link Manager
				</a>
			</li>
		</ul>
	</div>

	<div class="sa-nav-section">
		<h3>DATA</h3>
		<ul class="sa-nav">
			<li class="<?php echo 'product-scraper' === $current_page ? 'active' : ''; ?>">
				<a href="<?php echo esc_url(admin_url('admin.php?page=product-scraper')); ?>">
					<span class="dashicons dashicons-download"></span> &nbsp; Product Scraper
				</a>
			</li>
			<li class="<?php echo 'scraper-reports' === $current_page ? 'active' : ''; ?>">
				<a href="<?php echo esc_url(admin_url('admin.php?page=scraper-reports')); ?>">
					<span class="dashicons dashicons-media-text"></span> &nbsp; Reports
				</a>
			</li>
			<li class="<?php echo 'scraper-settings' === $current_page ? 'active' : ''; ?>">
				<a href="<?php echo esc_url(admin_url('admin.php?page=scraper-settings')); ?>">
					<span class="dashicons dashicons-admin-generic"></span> &nbsp; Settings
				</a>
			</li>
		</ul>
	</div>

	<div class="sa-feature-notice">
		<div class="feature-badge">NEW</div>
		<p><strong>Link Intelligence</strong></p>
		<p class="feature-desc">Advanced link tracking and optimization.</p>
	</div>

	<div class="sa-footer">
		<!-- <button class="sa-btn sa-btn-premium">
			Get Premium
		</button> -->
	</div>
</div>