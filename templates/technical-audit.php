<?php

/**
 * Technical Audit Template
 *
 * This template file is used for displaying technical audit results
 * in the Product Scraper Nahrin plugin.
 *
 * @package Product_Scraper_Nahrin
 * @subpackage Templates
 * @since 1.0.0
 */

// Security check to prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Technical Audit Results Display
 *
 * This template renders the technical audit interface which shows:
 * - Scraping performance metrics
 * - Data quality assessments
 * - System compatibility checks
 * - Error logs and diagnostics
 *
 * Usage:
 * - Included by main plugin files to display audit dashboard
 * - Uses WordPress admin styles and components
 * - Handles both summary and detailed audit views
 *
 * @var array $audit_data Technical audit results array
 * @var bool $detailed_view Whether to show detailed or summary view
 */
