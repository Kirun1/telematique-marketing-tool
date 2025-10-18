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
            <div class="sa-sidebar">
                <div class="sa-nav-section">
                    <h3>SEO TOOLS</h3>
                    <ul class="sa-nav">
                        <li class="active"><a href="#">SEO Dashboard</a></li>
                        <li><a href="#">Content Analysis</a></li>
                        <li><a href="#">Keyword Research</a></li>
                        <li><a href="#">Rank Tracking</a></li>
                    </ul>
                </div>
            </div>

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
                                <span class="status-text">Good</span>
                            </div>
                        </div>

                        <div class="sa-stat-card">
                            <div class="stat-header">
                                <h3>SEO Issues</h3>
                            </div>
                            <div class="stat-main">
                                <span class="stat-number"><?php echo count($stats['issues_found']); ?></span>
                            </div>
                            <div class="stat-target">
                                Needs attention
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
</style>