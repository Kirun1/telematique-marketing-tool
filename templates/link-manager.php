<?php
/**
 * Link Manager Template
 * 
 * @package ProductScraper
 * @since 1.0.0
 */

// Security check
if (!defined('ABSPATH')) {
    exit;
}

// Get link data
$internal_links = $this->get_internal_links();
$external_links = $this->get_external_links();
?>

<div class="wrap">
    <div class="scraper-analytics-dashboard">
        <div class="sa-header">
            <div class="sa-brand">
                <h1><strong>Scraper Analytics</strong></h1>
                <span class="sa-subtitle">Link Manager</span>
            </div>
            <div class="sa-actions">
                <button class="sa-btn sa-btn-primary" onclick="scanLinks()">
                    <span class="dashicons dashicons-update"></span>
                    Scan Links
                </button>
                <button class="sa-btn sa-btn-secondary" onclick="exportLinkReport()">
                    <span class="dashicons dashicons-download"></span>
                    Export Report
                </button>
                <button class="sa-btn sa-btn-success" onclick="showAddLinkModal()">
                    <span class="dashicons dashicons-plus"></span>
                    Add Link
                </button>
            </div>
        </div>

        <div class="sa-container">
            <!-- Sidebar -->
            <div class="sa-sidebar">
                <div class="sa-nav-section">
                    <h3>ANALYSIS</h3>
                    <ul class="sa-nav">
                        <li><a href="<?php echo admin_url('admin.php?page=scraper-analytics'); ?>"><span class="dashicons dashicons-dashboard"></span> &nbsp; Dashboard</a></li>
                        <li><a href="<?php echo admin_url('admin.php?page=scraper-keywords'); ?>"><span class="dashicons dashicons-tag"></span> &nbsp; Keywords</a></li>
                        <li><a href="<?php echo admin_url('admin.php?page=scraper-competitors'); ?>"><span class="dashicons dashicons-chart-bar"></span> &nbsp; Competitors</a></li>
                    </ul>
                </div>

                <div class="sa-nav-section">
                    <h3>SEO TOOLS</h3>
                    <ul class="sa-nav">
                        <li><a href="<?php echo admin_url('admin.php?page=seo-assistant'); ?>"><span class="dashicons dashicons-editor-spellcheck"></span> &nbsp; SEO Assistant</a></li>
                        <li><a href="<?php echo admin_url('admin.php?page=seo-analysis'); ?>"><span class="dashicons dashicons-chart-line"></span> &nbsp; SEO Analysis</a></li>
                        <li class="active"><a href="<?php echo admin_url('admin.php?page=link-manager'); ?>"><span class="dashicons dashicons-admin-links"></span> &nbsp; Link Manager</a></li>
                    </ul>
                </div>

                <div class="sa-nav-section">
                    <h3>DATA</h3>
                    <ul class="sa-nav">
                        <li><a href="<?php echo admin_url('admin.php?page=product-scraper'); ?>"><span class="dashicons dashicons-download"></span> &nbsp; Product Scraper</a></li>
                        <li><a href="<?php echo admin_url('admin.php?page=scraper-reports'); ?>"><span class="dashicons dashicons-media-text"></span> &nbsp; Reports</a></li>
                        <li><a href="<?php echo admin_url('admin.php?page=scraper-settings'); ?>"><span class="dashicons dashicons-admin-generic"></span> &nbsp; Settings</a></li>
                    </ul>
                </div>

                <div class="sa-feature-notice">
                    <div class="feature-badge">NEW</div>
                    <p><strong>Link Intelligence</strong></p>
                    <p class="feature-desc">Advanced link tracking and optimization.</p>
                </div>

                <div class="sa-footer">
                    <button class="sa-btn sa-btn-premium">
                        Get Premium
                    </button>
                </div>
            </div>

            <!-- Main Content -->
            <div class="sa-main-content">
                <!-- Overview Section -->
                <div class="sa-section">
                    <h2>Link Management & Analysis</h2>
                    <p class="sa-description">Monitor and optimize your internal and external linking structure for better SEO performance.</p>

                    <!-- Link Overview Stats -->
                    <div class="sa-link-overview">
                        <div class="link-stats-grid">
                            <div class="stat-card internal">
                                <div class="stat-icon">🔗</div>
                                <div class="stat-content">
                                    <div class="stat-value"><?php echo esc_html($internal_links['total_internal_links'] ?? 0); ?></div>
                                    <div class="stat-label">Internal Links</div>
                                </div>
                            </div>
                            <div class="stat-card external">
                                <div class="stat-icon">🌐</div>
                                <div class="stat-content">
                                    <div class="stat-value"><?php echo esc_html($external_links['total_external_links'] ?? 0); ?></div>
                                    <div class="stat-label">External Links</div>
                                </div>
                            </div>
                            <div class="stat-card broken">
                                <div class="stat-icon">⚠️</div>
                                <div class="stat-content">
                                    <div class="stat-value"><?php echo esc_html($external_links['broken_links'] ?? 0); ?></div>
                                    <div class="stat-label">Broken Links</div>
                                </div>
                            </div>
                            <div class="stat-card orphaned">
                                <div class="stat-icon">📄</div>
                                <div class="stat-content">
                                    <div class="stat-value"><?php echo esc_html($internal_links['orphaned_posts'] ?? 0); ?></div>
                                    <div class="stat-label">Orphaned Pages</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Internal Links Section -->
                    <div class="sa-analysis-section">
                        <h3><span class="dashicons dashicons-admin-links"></span> Internal Links Analysis</h3>
                        
                        <div class="internal-links-stats">
                            <div class="metric-row">
                                <div class="metric-item">
                                    <span class="metric-label">Total Internal Links:</span>
                                    <span class="metric-value"><?php echo esc_html($internal_links['total_internal_links'] ?? 0); ?></span>
                                </div>
                                <div class="metric-item">
                                    <span class="metric-label">Orphaned Pages:</span>
                                    <span class="metric-value"><?php echo esc_html($internal_links['orphaned_posts'] ?? 0); ?></span>
                                </div>
                                <div class="metric-item">
                                    <span class="metric-label">Linking Opportunities:</span>
                                    <span class="metric-value"><?php echo esc_html($internal_links['linking_opportunities'] ?? 0); ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="internal-links-actions">
                            <button class="sa-btn sa-btn-info" onclick="findOrphanedPages()">
                                <span class="dashicons dashicons-search"></span>
                                Find Orphaned Pages
                            </button>
                            <button class="sa-btn sa-btn-warning" onclick="suggestInternalLinks()">
                                <span class="dashicons dashicons-lightbulb"></span>
                                Suggest Internal Links
                            </button>
                        </div>

                        <div class="links-table-container">
                            <h4>Recent Internal Links</h4>
                            <table class="wp-list-table widefat fixed striped">
                                <thead>
                                    <tr>
                                        <th>Source Page</th>
                                        <th>Target Page</th>
                                        <th>Anchor Text</th>
                                        <th>Link Type</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="5" class="no-links-message">
                                            <p>No internal links found. Run a link scan to discover internal links.</p>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- External Links Section -->
                    <div class="sa-analysis-section">
                        <h3><span class="dashicons dashicons-external"></span> External Links Analysis</h3>
                        
                        <div class="external-links-stats">
                            <div class="metric-row">
                                <div class="metric-item">
                                    <span class="metric-label">Total External Links:</span>
                                    <span class="metric-value"><?php echo esc_html($external_links['total_external_links'] ?? 0); ?></span>
                                </div>
                                <div class="metric-item">
                                    <span class="metric-label">Broken Links:</span>
                                    <span class="metric-value"><?php echo esc_html($external_links['broken_links'] ?? 0); ?></span>
                                </div>
                                <div class="metric-item">
                                    <span class="metric-label">NoFollow Links:</span>
                                    <span class="metric-value"><?php echo esc_html($external_links['nofollow_links'] ?? 0); ?></span>
                                </div>
                            </div>
                        </div>

                        <div class="external-links-actions">
                            <button class="sa-btn sa-btn-danger" onclick="checkBrokenLinks()">
                                <span class="dashicons dashicons-warning"></span>
                                Check Broken Links
                            </button>
                            <button class="sa-btn sa-btn-info" onclick="analyzeLinkQuality()">
                                <span class="dashicons dashicons-chart-bar"></span>
                                Analyze Link Quality
                            </button>
                        </div>

                        <div class="links-table-container">
                            <h4>Recent External Links</h4>
                            <table class="wp-list-table widefat fixed striped">
                                <thead>
                                    <tr>
                                        <th>Source Page</th>
                                        <th>Target URL</th>
                                        <th>Anchor Text</th>
                                        <th>Link Attributes</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td colspan="6" class="no-links-message">
                                            <p>No external links found. Run a link scan to discover external links.</p>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <!-- Link Building Opportunities -->
                    <div class="sa-analysis-section">
                        <h3><span class="dashicons dashicons-chart-line"></span> Link Building Opportunities</h3>
                        
                        <div class="opportunities-grid">
                            <div class="opportunity-card">
                                <div class="opportunity-header">
                                    <span class="opportunity-icon">💡</span>
                                    <h4>Internal Linking</h4>
                                </div>
                                <div class="opportunity-content">
                                    <p>Optimize your internal link structure to improve site architecture and SEO.</p>
                                    <ul>
                                        <li>Add links to related content</li>
                                        <li>Fix orphaned pages</li>
                                        <li>Improve anchor text diversity</li>
                                    </ul>
                                </div>
                                <div class="opportunity-actions">
                                    <button class="sa-btn sa-btn-small" onclick="optimizeInternalLinks()">Optimize Now</button>
                                </div>
                            </div>

                            <div class="opportunity-card">
                                <div class="opportunity-header">
                                    <span class="opportunity-icon">🚀</span>
                                    <h4>External Link Audit</h4>
                                </div>
                                <div class="opportunity-content">
                                    <p>Audit and improve your outbound linking strategy.</p>
                                    <ul>
                                        <li>Find broken external links</li>
                                        <li>Add relevant outbound links</li>
                                        <li>Monitor link quality</li>
                                    </ul>
                                </div>
                                <div class="opportunity-actions">
                                    <button class="sa-btn sa-btn-small" onclick="auditExternalLinks()">Start Audit</button>
                                </div>
                            </div>

                            <div class="opportunity-card">
                                <div class="opportunity-header">
                                    <span class="opportunity-icon">🎯</span>
                                    <h4>Competitor Analysis</h4>
                                </div>
                                <div class="opportunity-content">
                                    <p>Analyze competitor backlink profiles for opportunities.</p>
                                    <ul>
                                        <li>Identify linking domains</li>
                                        <li>Find guest posting opportunities</li>
                                        <li>Discover resource link targets</li>
                                    </ul>
                                </div>
                                <div class="opportunity-actions">
                                    <button class="sa-btn sa-btn-small" onclick="analyzeCompetitors()">Analyze</button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Link Modal -->
<div id="addLinkModal" class="sa-modal" style="display: none;">
    <div class="sa-modal-content">
        <div class="sa-modal-header">
            <h3>Add New Link</h3>
            <span class="sa-modal-close" onclick="hideAddLinkModal()">&times;</span>
        </div>
        <div class="sa-modal-body">
            <form id="addLinkForm">
                <div class="form-group">
                    <label for="link_source">Source Page</label>
                    <select id="link_source" name="link_source" class="sa-form-control">
                        <option value="">Select Source Page</option>
                        <?php
                        $pages = get_posts(array(
                            'post_type' => array('post', 'page'),
                            'post_status' => 'publish',
                            'numberposts' => 50
                        ));
                        foreach ($pages as $page) {
                            echo '<option value="' . esc_attr($page->ID) . '">' . esc_html($page->post_title) . '</option>';
                        }
                        ?>
                    </select>
                </div>
                <div class="form-group">
                    <label for="link_target">Target URL</label>
                    <input type="url" id="link_target" name="link_target" class="sa-form-control" placeholder="https://example.com">
                </div>
                <div class="form-group">
                    <label for="link_anchor">Anchor Text</label>
                    <input type="text" id="link_anchor" name="link_anchor" class="sa-form-control" placeholder="Descriptive anchor text">
                </div>
                <div class="form-group">
                    <label for="link_type">Link Type</label>
                    <select id="link_type" name="link_type" class="sa-form-control">
                        <option value="internal">Internal</option>
                        <option value="external">External</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="link_attributes">Link Attributes</label>
                    <div class="checkbox-group">
                        <label>
                            <input type="checkbox" name="link_nofollow" value="1"> NoFollow
                        </label>
                        <label>
                            <input type="checkbox" name="link_sponsored" value="1"> Sponsored
                        </label>
                        <label>
                            <input type="checkbox" name="link_ugc" value="1"> UGC
                        </label>
                    </div>
                </div>
            </form>
        </div>
        <div class="sa-modal-footer">
            <button type="button" class="sa-btn sa-btn-secondary" onclick="hideAddLinkModal()">Cancel</button>
            <button type="button" class="sa-btn sa-btn-primary" onclick="addNewLink()">Add Link</button>
        </div>
    </div>
</div>

<style>
.sa-link-overview {
    background: #fff;
    border-radius: 8px;
    padding: 30px;
    margin-bottom: 30px;
}

.link-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
}

.stat-card {
    display: flex;
    align-items: center;
    gap: 15px;
    padding: 20px;
    border-radius: 8px;
    border: 1px solid #e0e0e0;
}

.stat-card.internal {
    border-left: 4px solid #4CAF50;
}

.stat-card.external {
    border-left: 4px solid #2196F3;
}

.stat-card.broken {
    border-left: 4px solid #f44336;
}

.stat-card.orphaned {
    border-left: 4px solid #FF9800;
}

.stat-icon {
    font-size: 24px;
    flex-shrink: 0;
}

.stat-content {
    flex: 1;
}

.stat-value {
    font-size: 24px;
    font-weight: bold;
    color: #2c3338;
    margin-bottom: 5px;
}

.stat-label {
    font-size: 12px;
    color: #646970;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.metric-row {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 20px;
}

.metric-item {
    display: flex;
    justify-content: space-between;
    padding: 15px;
    background: #f8f9fa;
    border-radius: 6px;
    border: 1px solid #e0e0e0;
}

.metric-label {
    font-weight: 600;
    color: #2c3338;
}

.metric-value {
    font-weight: bold;
    color: #2196F3;
}

.internal-links-actions,
.external-links-actions {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
    flex-wrap: wrap;
}

.links-table-container {
    margin-top: 20px;
}

.no-links-message {
    text-align: center;
    padding: 40px;
    color: #646970;
    font-style: italic;
}

.opportunities-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
}

.opportunity-card {
    background: #f8f9fa;
    border: 1px solid #e0e0e0;
    border-radius: 8px;
    padding: 20px;
}

.opportunity-header {
    display: flex;
    align-items: center;
    gap: 10px;
    margin-bottom: 15px;
}

.opportunity-icon {
    font-size: 24px;
}

.opportunity-header h4 {
    margin: 0;
    color: #2c3338;
}

.opportunity-content {
    margin-bottom: 15px;
}

.opportunity-content p {
    margin: 0 0 15px 0;
    color: #646970;
}

.opportunity-content ul {
    margin: 0;
    padding-left: 20px;
    color: #646970;
}

.opportunity-content li {
    margin-bottom: 5px;
}

.opportunity-actions {
    text-align: right;
}

.sa-btn-small {
    padding: 8px 16px;
    font-size: 12px;
}

/* Modal Styles */
.sa-modal {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0,0,0,0.5);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 10000;
}

.sa-modal-content {
    background: #fff;
    border-radius: 8px;
    width: 90%;
    max-width: 500px;
    max-height: 90vh;
    overflow-y: auto;
}

.sa-modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    padding: 20px;
    border-bottom: 1px solid #e0e0e0;
}

.sa-modal-header h3 {
    margin: 0;
    color: #2c3338;
}

.sa-modal-close {
    font-size: 24px;
    cursor: pointer;
    color: #646970;
}

.sa-modal-body {
    padding: 20px;
}

.sa-modal-footer {
    padding: 20px;
    border-top: 1px solid #e0e0e0;
    text-align: right;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    margin-bottom: 5px;
    font-weight: 600;
    color: #2c3338;
}

.sa-form-control {
    width: 100%;
    padding: 8px 12px;
    border: 1px solid #dcdcde;
    border-radius: 4px;
    font-size: 14px;
}

.checkbox-group {
    display: flex;
    gap: 20px;
    flex-wrap: wrap;
}

.checkbox-group label {
    display: flex;
    align-items: center;
    gap: 5px;
    font-weight: normal;
    cursor: pointer;
}

.sa-btn-info {
    background: #17a2b8;
    color: white;
}

.sa-btn-warning {
    background: #ffc107;
    color: #2c3338;
}

.sa-btn-danger {
    background: #dc3545;
    color: white;
}

.sa-btn-info:hover,
.sa-btn-warning:hover,
.sa-btn-danger:hover {
    opacity: 0.9;
}
</style>

<script>
function scanLinks() {
    const button = document.querySelector('.sa-btn-primary');
    const originalText = button.innerHTML;
    
    button.disabled = true;
    button.innerHTML = '<span class="dashicons dashicons-update spin"></span> Scanning...';
    
    // Simulate scanning process
    setTimeout(() => {
        button.disabled = false;
        button.innerHTML = originalText;
        alert('Link scan completed!');
        location.reload();
    }, 3000);
}

function exportLinkReport() {
    alert('Link report export feature would generate a comprehensive link analysis report');
}

function showAddLinkModal() {
    document.getElementById('addLinkModal').style.display = 'flex';
}

function hideAddLinkModal() {
    document.getElementById('addLinkModal').style.display = 'none';
}

function addNewLink() {
    const form = document.getElementById('addLinkForm');
    const formData = new FormData(form);
    
    // Validate form
    if (!formData.get('link_source') || !formData.get('link_target') || !formData.get('link_anchor')) {
        alert('Please fill in all required fields');
        return;
    }
    
    // Simulate adding link
    alert('New link added successfully!');
    hideAddLinkModal();
    form.reset();
}

function findOrphanedPages() {
    alert('Finding orphaned pages... This feature would identify pages with no internal links.');
}

function suggestInternalLinks() {
    alert('Suggesting internal links... This feature would recommend relevant internal linking opportunities.');
}

function checkBrokenLinks() {
    alert('Checking broken links... This feature would scan all external links for 404 errors.');
}

function analyzeLinkQuality() {
    alert('Analyzing link quality... This feature would evaluate the quality of external links.');
}

function optimizeInternalLinks() {
    alert('Optimizing internal links... This feature would automatically improve internal linking structure.');
}

function auditExternalLinks() {
    alert('Starting external link audit... This feature would analyze all outbound links.');
}

function analyzeCompetitors() {
    alert('Analyzing competitors... This feature would examine competitor backlink profiles.');
}

// Close modal when clicking outside
document.getElementById('addLinkModal').addEventListener('click', function(e) {
    if (e.target === this) {
        hideAddLinkModal();
    }
});
</script>