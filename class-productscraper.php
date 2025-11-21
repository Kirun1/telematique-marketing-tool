<?php
/**
 * Plugin Name: Product Scraper & Analytics
 * Description: Advanced SEO optimization with product scraping and analytics
 * Version: 2.1.0
 * Author: Telematique LTD
 * Text Domain: product-scraper
 *
 * @package ProductScraper
 */

// Prevent direct access.
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Define plugin constants.
define( 'PRODUCT_SCRAPER_VERSION', '2.1.0' );
define( 'PRODUCT_SCRAPER_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'PRODUCT_SCRAPER_PLUGIN_PATH', plugin_dir_path( __FILE__ ) );

// Load Composer dependencies.
$vendor_autoload = __DIR__ . '/vendor/autoload.php';
if ( file_exists( $vendor_autoload ) ) {
	require_once $vendor_autoload;
}

// Include required files.
require_once PRODUCT_SCRAPER_PLUGIN_PATH . 'includes/class-scraper.php';
require_once PRODUCT_SCRAPER_PLUGIN_PATH . 'includes/class-admin.php';
require_once PRODUCT_SCRAPER_PLUGIN_PATH . 'includes/class-woocommerceproductimporter.php';
require_once PRODUCT_SCRAPER_PLUGIN_PATH . 'includes/class-data-storage.php';
require_once PRODUCT_SCRAPER_PLUGIN_PATH . 'includes/class-analytics-dashboard.php';
require_once PRODUCT_SCRAPER_PLUGIN_PATH . 'includes/class-seo-assistant.php';
require_once PRODUCT_SCRAPER_PLUGIN_PATH . 'includes/class-content-optimizer.php';
require_once PRODUCT_SCRAPER_PLUGIN_PATH . 'includes/class-keyword-research.php';
require_once PRODUCT_SCRAPER_PLUGIN_PATH . 'includes/class-productscraper-voice-search.php';
require_once PRODUCT_SCRAPER_PLUGIN_PATH . 'includes/class-productscraper-social-optimizer.php';
require_once PRODUCT_SCRAPER_PLUGIN_PATH . 'includes/class-schema-generator.php';
require_once PRODUCT_SCRAPER_PLUGIN_PATH . 'includes/class-roi-tracker.php';
require_once PRODUCT_SCRAPER_PLUGIN_PATH . 'includes/class-redirect-manager.php';
require_once PRODUCT_SCRAPER_PLUGIN_PATH . 'includes/class-rank-tracker.php';
require_once PRODUCT_SCRAPER_PLUGIN_PATH . 'includes/class-onpage-analyzer.php';
require_once PRODUCT_SCRAPER_PLUGIN_PATH . 'includes/class-local-seo.php';
require_once PRODUCT_SCRAPER_PLUGIN_PATH . 'includes/class-international-seo.php';
require_once PRODUCT_SCRAPER_PLUGIN_PATH . 'includes/class-ecommerce-seo.php';
require_once PRODUCT_SCRAPER_PLUGIN_PATH . 'includes/class-content-analytics.php';
require_once PRODUCT_SCRAPER_PLUGIN_PATH . 'includes/class-competitor-analysis.php';
require_once PRODUCT_SCRAPER_PLUGIN_PATH . 'includes/class-ai-title-optimizer.php';
require_once PRODUCT_SCRAPER_PLUGIN_PATH . 'includes/class-ai-content-writer.php';
require_once PRODUCT_SCRAPER_PLUGIN_PATH . 'includes/class-advanced-sitemap.php';
require_once PRODUCT_SCRAPER_PLUGIN_PATH . 'includes/class-api-integrations.php';
require_once PRODUCT_SCRAPER_PLUGIN_PATH . 'includes/class-seo-analysis.php';
require_once PRODUCT_SCRAPER_PLUGIN_PATH . 'includes/class-link-manager.php';
require_once PRODUCT_SCRAPER_PLUGIN_PATH . 'includes/class-robots-txt.php';

/**
 * Main plugin controller for Product Scraper.
 *
 * @phpcs:disable WordPress.Files.FileName.InvalidClassFileName -- Main plugin file must retain its slugged filename.
 */
class ProductScraper {

	/**
	 * Data storage handler.
	 *
	 * @var ProductScraperDataStorage
	 */
	public $storage;

	/**
	 * Analytics dashboard handler.
	 *
	 * @var ProductScraperAnalytics
	 */
	public $analytics;

	/**
	 * SEO assistant service.
	 *
	 * @var ProductScraper_SEO_Assistant
	 */
	public $seo_assistant;

	/**
	 * Content optimizer service.
	 *
	 * @var ProductScraper_Content_Optimizer
	 */
	public $content_optimizer;

	/**
	 * Keyword research service.
	 *
	 * @var ProductScraper_Keyword_Research
	 */
	public $keyword_research;

	/**
	 * Integration service wrapper.
	 *
	 * @var ProductScraper_API_Integrations
	 */
	public $api_integrations;

	/**
	 * SEO analysis service.
	 *
	 * @var ProductScraper_SEO_Analysis
	 */
	public $seo_analysis;

	/**
	 * Link manager service.
	 *
	 * @var ProductScraper_Link_Manager
	 */
	public $link_manager;

	/**
	 * Robots.txt manager service.
	 *
	 * @var ProductScraper_Robots_Txt
	 */
	public $robots_manager;

	/**
	 * Bootstrap plugin services and hooks.
	 */
	public function __construct() {
		$this->storage           = new ProductScraperDataStorage();
		$this->analytics         = new ProductScraperAnalytics();
		$this->seo_assistant     = new ProductScraper_SEO_Assistant();
		$this->content_optimizer = new ProductScraper_Content_Optimizer();
		$this->keyword_research  = new ProductScraper_Keyword_Research();
		$this->api_integrations  = new ProductScraper_API_Integrations();
		$this->seo_analysis      = new ProductScraper_SEO_Analysis();
		$this->link_manager      = new ProductScraper_Link_Manager();
		$this->robots_manager    = new ProductScraper_Robots_Txt();

		// Core SEO hooks.
		add_action( 'init', array( $this, 'init' ) );
		add_action( 'wp_head', array( $this, 'output_seo_meta_tags' ) );
		add_action( 'template_redirect', array( $this, 'canonical_redirects' ) );

		// Admin hooks.
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );

		// XML Sitemaps.
		add_action( 'init', array( $this, 'setup_sitemaps' ) );

		// REST API.
		add_action( 'rest_api_init', array( $this, 'register_rest_routes' ) );
	}

	/**
	 * Initialize admin modules and shared SEO hooks.
	 *
	 * @return void
	 */
	public function init() {
		if ( is_admin() ) {
			new ProductScraperAdmin();
		}

		// Initialize SEO features.
		$this->setup_seo_features();
	}

	/**
	 * Register hooks for SEO meta, social tags, and structured data.
	 *
	 * @return void
	 */
	public function setup_seo_features() {
		// Remove other SEO plugins' meta boxes to avoid conflicts.
		if ( is_admin() ) {
			add_action( 'add_meta_boxes', array( $this, 'remove_other_seo_meta_boxes' ), 100 );
		}

		// Add Open Graph meta tags.
		add_action( 'wp_head', array( $this, 'add_opengraph_meta' ), 5 );

		// Add Twitter Card meta tags.
		add_action( 'wp_head', array( $this, 'add_twitter_card_meta' ), 5 );

		// Add JSON-LD structured data.
		add_action( 'wp_head', array( $this, 'add_structured_data' ), 10 );
	}

	/**
	 * Remove legacy SEO meta boxes for a cleaner UI.
	 *
	 * @return void
	 */
	public function remove_other_seo_meta_boxes() {
		// Remove Yoast SEO meta box.
		remove_meta_box( 'wpseo_meta', get_post_types(), 'normal' );

		// Remove Rank Math meta box.
		remove_meta_box( 'rank_math_metabox', get_post_types(), 'normal' );

		// Remove All in One SEO meta box.
		remove_meta_box( 'aioseo-settings', get_post_types(), 'normal' );
	}

	/**
	 * Route SEO meta output based on the current query context.
	 *
	 * @return void
	 */
	public function output_seo_meta_tags() {
		if ( is_singular() ) {
			$this->output_singular_meta_tags();
		} elseif ( is_tax() || is_category() || is_tag() ) {
			$this->output_taxonomy_meta_tags();
		} elseif ( is_author() ) {
			$this->output_author_meta_tags();
		} elseif ( is_home() || is_front_page() ) {
			$this->output_homepage_meta_tags();
		} elseif ( is_search() ) {
			$this->output_search_meta_tags();
		} elseif ( is_archive() ) {
			$this->output_archive_meta_tags();
		}
	}

	/**
	 * Output meta tags for singular posts and pages.
	 *
	 * @return void
	 */
	public function output_singular_meta_tags() {
		global $post;

		if ( ! $post ) {
			return;
		}

		$seo_title        = get_post_meta( $post->ID, '_seo_title', true );
		$meta_description = get_post_meta( $post->ID, '_meta_description', true );
		$canonical_url    = get_post_meta( $post->ID, '_canonical_url', true );
		$meta_robots      = get_post_meta( $post->ID, '_meta_robots', true );

		// Title tag.
		if ( ! empty( $seo_title ) ) {
			echo '<title>' . esc_html( $seo_title ) . '</title>' . "\n";
		} else {
			echo '<title>' . esc_html( get_the_title() ) . ' | ' . esc_html( get_bloginfo( 'name' ) ) . '</title>' . "\n";
		}

		// Meta description.
		if ( ! empty( $meta_description ) ) {
			echo '<meta name="description" content="' . esc_attr( $meta_description ) . '" />' . "\n";
		} elseif ( $post->post_excerpt ) {
			echo '<meta name="description" content="' . esc_attr( wp_trim_words( $post->post_excerpt, 25 ) ) . '" />' . "\n";
		} else {
			echo '<meta name="description" content="' . esc_attr( wp_trim_words( wp_strip_all_tags( $post->post_content ), 25 ) ) . '" />' . "\n";
		}

		// Canonical URL.
		if ( ! empty( $canonical_url ) ) {
			echo '<link rel="canonical" href="' . esc_url( $canonical_url ) . '" />' . "\n";
		} else {
			echo '<link rel="canonical" href="' . esc_url( get_permalink( $post->ID ) ) . '" />' . "\n";
		}

		// Meta robots.
		if ( ! empty( $meta_robots ) ) {
			echo '<meta name="robots" content="' . esc_attr( $meta_robots ) . '" />' . "\n";
		}
	}

	/**
	 * Output meta tags for taxonomy pages (categories, tags, custom taxonomies)
	 */
	public function output_taxonomy_meta_tags() {
		$term = get_queried_object();

		if ( ! $term || ! isset( $term->term_id ) ) {
			return;
		}

		$term_id  = $term->term_id;
		$taxonomy = $term->taxonomy;

		// Get SEO meta from term meta.
		$seo_title        = get_term_meta( $term_id, '_seo_title', true );
		$meta_description = get_term_meta( $term_id, '_meta_description', true );
		$canonical_url    = get_term_meta( $term_id, '_canonical_url', true );
		$meta_robots      = get_term_meta( $term_id, '_meta_robots', true );

		// Generate title.
		if ( ! empty( $seo_title ) ) {
			$title = $seo_title;
		} else {
			$title_parts = array( $term->name );

			if ( is_category() ) {
				$title_parts[] = __( 'Category', 'product-scraper' );
			} elseif ( is_tag() ) {
				$title_parts[] = __( 'Tag', 'product-scraper' );
			} else {
				$taxonomy_obj = get_taxonomy( $taxonomy );
				if ( $taxonomy_obj && ! empty( $taxonomy_obj->labels->singular_name ) ) {
					$title_parts[] = $taxonomy_obj->labels->singular_name;
				}
			}
			$title_parts[] = get_bloginfo( 'name' );

			$title = implode( ' | ', array_filter( $title_parts ) );
		}

		echo '<title>' . esc_html( $title ) . '</title>' . "\n";

		// Meta description.
		if ( ! empty( $meta_description ) ) {
			echo '<meta name="description" content="' . esc_attr( $meta_description ) . '" />' . "\n";
		} elseif ( ! empty( $term->description ) ) {
			echo '<meta name="description" content="' . esc_attr( wp_trim_words( $term->description, 25 ) ) . '" />' . "\n";
		} else {
			$default_description = sprintf(
				/* translators: 1: taxonomy term name, 2: taxonomy term name. */
				__( 'Browse our collection of %1$s. Find the best products and content related to %2$s.', 'product-scraper' ),
				$term->name,
				$term->name
			);
			echo '<meta name="description" content="' . esc_attr( $default_description ) . '" />' . "\n";
		}

		// Canonical URL.
		if ( ! empty( $canonical_url ) ) {
			echo '<link rel="canonical" href="' . esc_url( $canonical_url ) . '" />' . "\n";
		} else {
			echo '<link rel="canonical" href="' . esc_url( get_term_link( $term ) ) . '" />' . "\n";
		}

		// Meta robots.
		if ( ! empty( $meta_robots ) ) {
			echo '<meta name="robots" content="' . esc_attr( $meta_robots ) . '" />' . "\n";
		} elseif ( $this->is_paginated_archive() ) {
			// Default for taxonomy pages - index, follow unless paginated.
			echo '<meta name="robots" content="noindex, follow" />' . "\n";
		} else {
			echo '<meta name="robots" content="index, follow" />' . "\n";
		}

		// Prevent pagination duplication.
		if ( $this->is_paginated_archive() ) {
			$this->output_pagination_meta( $term );
		}
	}

	/**
	 * Output meta tags for author pages
	 */
	public function output_author_meta_tags() {
		$author_id = get_queried_object_id();
		$author    = get_queried_object();

		if ( ! $author ) {
			return;
		}

		// Get SEO meta from user meta.
		$seo_title        = get_user_meta( $author_id, '_seo_title', true );
		$meta_description = get_user_meta( $author_id, '_meta_description', true );
		$canonical_url    = get_user_meta( $author_id, '_canonical_url', true );
		$meta_robots      = get_user_meta( $author_id, '_meta_robots', true );

		// Generate title.
		if ( ! empty( $seo_title ) ) {
			$title = $seo_title;
		} else {
			$author_title_parts = array(
				$author->display_name,
				__( 'Author', 'product-scraper' ),
				get_bloginfo( 'name' ),
			);
			$title              = implode( ' | ', array_filter( $author_title_parts ) );
		}
		echo '<title>' . esc_html( $title ) . '</title>' . "\n";

		// Meta description.
		if ( ! empty( $meta_description ) ) {
			echo '<meta name="description" content="' . esc_attr( $meta_description ) . '" />' . "\n";
		} elseif ( ! empty( $author->description ) ) {
			echo '<meta name="description" content="' . esc_attr( wp_trim_words( $author->description, 25 ) ) . '" />' . "\n";
		} else {
			$post_count          = count_user_posts( $author_id );
			$default_description = sprintf(
				/* translators: 1: post count, 2: author display name, 3: author display name, 4: site name. */
				_n(
					'View all %1$d post by %2$s. %3$s is an author on %4$s.',
					'View all %1$d posts by %2$s. %3$s is an author on %4$s.',
					$post_count,
					'product-scraper'
				),
				$post_count,
				$author->display_name,
				$author->display_name,
				get_bloginfo( 'name' )
			);
			echo '<meta name="description" content="' . esc_attr( $default_description ) . '" />' . "\n";
		}

		// Canonical URL.
		if ( ! empty( $canonical_url ) ) {
			echo '<link rel="canonical" href="' . esc_url( $canonical_url ) . '" />' . "\n";
		} else {
			echo '<link rel="canonical" href="' . esc_url( get_author_posts_url( $author_id ) ) . '" />' . "\n";
		}

		// Meta robots.
		if ( ! empty( $meta_robots ) ) {
			echo '<meta name="robots" content="' . esc_attr( $meta_robots ) . '" />' . "\n";
		} elseif ( $this->is_paginated_archive() ) {
			// Default for author pages - index, follow unless paginated.
			echo '<meta name="robots" content="noindex, follow" />' . "\n";
		} else {
			echo '<meta name="robots" content="index, follow" />' . "\n";
		}

		// Prevent pagination duplication.
		if ( $this->is_paginated_archive() ) {
			$this->output_pagination_meta( $author );
		}
	}

	/**
	 * Output meta tags for homepage
	 */
	public function output_homepage_meta_tags() {
		$seo_title        = get_option( 'product_scraper_homepage_title' );
		$meta_description = get_option( 'product_scraper_homepage_description' );
		$canonical_url    = home_url( '/' );

		// Title tag.
		if ( ! empty( $seo_title ) ) {
			echo '<title>' . esc_html( $seo_title ) . '</title>' . "\n";
		} else {
			echo '<title>' . esc_html( get_bloginfo( 'name' ) ) . ' | ' . esc_html( get_bloginfo( 'description' ) ) . '</title>' . "\n";
		}

		// Meta description.
		if ( ! empty( $meta_description ) ) {
			echo '<meta name="description" content="' . esc_attr( $meta_description ) . '" />' . "\n";
		} elseif ( ! empty( get_bloginfo( 'description' ) ) ) {
			echo '<meta name="description" content="' . esc_attr( get_bloginfo( 'description' ) ) . '" />' . "\n";
		}

		// Canonical URL.
		echo '<link rel="canonical" href="' . esc_url( $canonical_url ) . '" />' . "\n";

		// Meta robots.
		echo '<meta name="robots" content="index, follow" />' . "\n";
	}

	/**
	 * Output meta tags for search results
	 */
	public function output_search_meta_tags() {
		$search_query = get_search_query();

		$title = sprintf(
			// translators: 1: search query, 2: site name.
			__( 'Search Results for: "%1$s" | %2$s', 'product-scraper' ),
			$search_query,
			get_bloginfo( 'name' )
		);

		$description = sprintf(
			// translators: 1: search query, 2: site name.
			__( 'Search results for "%1$s". Find relevant content and products on %2$s.', 'product-scraper' ),
			$search_query,
			get_bloginfo( 'name' )
		);

		echo '<title>' . esc_html( $title ) . '</title>' . "\n";

		echo '<meta name="description" content="' . esc_attr( $description ) . '" />' . "\n";

		echo '<link rel="canonical" href="' . esc_url( get_search_link() ) . '" />' . "\n";

		// Typically noindex search results to avoid duplicate content.
		echo '<meta name="robots" content="noindex, follow" />' . "\n";
	}

	/**
	 * Output meta tags for general archive pages
	 */
	public function output_archive_meta_tags() {
		if ( is_date() ) {
			$this->output_date_archive_meta_tags();
		} else {
			// Fallback for other archive types.
			echo '<title>' . esc_html( get_the_archive_title() ) . ' | ' . esc_html( get_bloginfo( 'name' ) ) . '</title>' . "\n";
			echo '<meta name="description" content="' . esc_attr( wp_trim_words( get_the_archive_description(), 25 ) ) . '" />' . "\n";
			echo '<link rel="canonical" href="' . esc_url( get_pagenum_link() ) . '" />' . "\n";

			if ( $this->is_paginated_archive() ) {
				echo '<meta name="robots" content="noindex, follow" />' . "\n";
			} else {
				echo '<meta name="robots" content="index, follow" />' . "\n";
			}
		}
	}

	/**
	 * Output meta tags for date-based archives
	 */
	public function output_date_archive_meta_tags() {
		if ( is_year() ) {
			$title = sprintf(
				// translators: 1: year, 2: site name.
				__( 'Posts from %1$s | %2$s', 'product-scraper' ),
				get_the_date( 'Y' ),
				get_bloginfo( 'name' )
			);
			$description = sprintf(
				// translators: 1: year, 2: site name.
				__( 'Browse all posts from %1$s on %2$s.', 'product-scraper' ),
				get_the_date( 'Y' ),
				get_bloginfo( 'name' )
			);
		} elseif ( is_month() ) {
			$title = sprintf(
				// translators: 1: month and year, 2: site name.
				__( 'Posts from %1$s | %2$s', 'product-scraper' ),
				get_the_date( 'F Y' ),
				get_bloginfo( 'name' )
			);
			$description = sprintf(
				// translators: 1: month and year, 2: site name.
				__( 'Browse all posts from %1$s on %2$s.', 'product-scraper' ),
				get_the_date( 'F Y' ),
				get_bloginfo( 'name' )
			);
		} elseif ( is_day() ) {
			$title = sprintf(
				// translators: 1: date, 2: site name.
				__( 'Posts from %1$s | %2$s', 'product-scraper' ),
				get_the_date(),
				get_bloginfo( 'name' )
			);
			$description = sprintf(
				// translators: 1: date, 2: site name.
				__( 'Browse all posts from %1$s on %2$s.', 'product-scraper' ),
				get_the_date(),
				get_bloginfo( 'name' )
			);
		}

		echo '<title>' . esc_html( $title ) . '</title>' . "\n";
		echo '<meta name="description" content="' . esc_attr( $description ) . '" />' . "\n";
		echo '<link rel="canonical" href="' . esc_url( get_pagenum_link() ) . '" />' . "\n";

		// Typically noindex date archives to avoid thin content.
		echo '<meta name="robots" content="noindex, follow" />' . "\n";
	}

	/**
	 * Output pagination meta tags to prevent duplicate content
	 *
	 * @param WP_Post|WP_Term|null $pagination_object Optional object context.
	 */
	private function output_pagination_meta( $pagination_object = null ) {
		global $wp_query;

		unset( $pagination_object );

		$paged = get_query_var( 'paged' );
		if ( ! $paged ) {
			$paged = 1;
		}

		if ( $paged > 1 ) {
			// Prev link.
			if ( $paged > 1 ) {
				echo '<link rel="prev" href="' . esc_url( get_pagenum_link( $paged - 1 ) ) . '" />' . "\n";
			}

			// Next link.
			if ( $paged < $wp_query->max_num_pages ) {
				echo '<link rel="next" href="' . esc_url( get_pagenum_link( $paged + 1 ) ) . '" />' . "\n";
			}
		}
	}

	/**
	 * Check if current page is a paginated archive
	 */
	private function is_paginated_archive() {
		global $wp_query;
		return $wp_query->is_paged && ( $wp_query->is_archive() || $wp_query->is_home() || $wp_query->is_search() );
	}

	/**
	 * Add Open Graph tags for singular content.
	 */
	public function add_opengraph_meta() {
		if ( ! is_singular() ) {
			return;
		}

		global $post;

		$og_title = get_post_meta( $post->ID, '_og_title', true );
		if ( '' === $og_title ) {
			$og_title = get_the_title();
		}

		$og_description = get_post_meta( $post->ID, '_og_description', true );
		if ( '' === $og_description ) {
			$og_description = wp_trim_words( get_the_excerpt(), 25 );
		}
		$og_image = get_post_meta( $post->ID, '_og_image', true );

		echo '<!-- Product Scraper Open Graph -->' . "\n";
		echo '<meta property="og:type" content="article" />' . "\n";
		echo '<meta property="og:title" content="' . esc_attr( $og_title ) . '" />' . "\n";
		echo '<meta property="og:description" content="' . esc_attr( $og_description ) . '" />' . "\n";
		echo '<meta property="og:url" content="' . esc_url( get_permalink() ) . '" />' . "\n";
		echo '<meta property="og:site_name" content="' . esc_attr( get_bloginfo( 'name' ) ) . '" />' . "\n";

		if ( $og_image ) {
			echo '<meta property="og:image" content="' . esc_url( $og_image ) . '" />' . "\n";
		} elseif ( has_post_thumbnail( $post->ID ) ) {
			$image_url = wp_get_attachment_image_url( get_post_thumbnail_id( $post->ID ), 'large' );
			echo '<meta property="og:image" content="' . esc_url( $image_url ) . '" />' . "\n";
		}
	}

	/**
	 * Add Open Graph meta tags for taxonomy pages
	 */
	public function add_taxonomy_opengraph_meta() {
		if ( ! is_tax() && ! is_category() && ! is_tag() ) {
			return;
		}

		$term = get_queried_object();
		if ( ! $term ) {
			return;
		}

		$og_title = get_term_meta( $term->term_id, '_og_title', true );
		if ( '' === $og_title ) {
			$og_title = $term->name;
		}

		$og_description = get_term_meta( $term->term_id, '_og_description', true );
		if ( '' === $og_description ) {
			$og_description = wp_trim_words( $term->description, 25 );
		}
		$og_image = get_term_meta( $term->term_id, '_og_image', true );

		echo '<!-- Product Scraper Taxonomy Open Graph -->' . "\n";
		echo '<meta property="og:type" content="website" />' . "\n";
		echo '<meta property="og:title" content="' . esc_attr( $og_title ) . '" />' . "\n";
		echo '<meta property="og:description" content="' . esc_attr( $og_description ) . '" />' . "\n";
		echo '<meta property="og:url" content="' . esc_url( get_term_link( $term ) ) . '" />' . "\n";
		echo '<meta property="og:site_name" content="' . esc_attr( get_bloginfo( 'name' ) ) . '" />' . "\n";

		if ( $og_image ) {
			echo '<meta property="og:image" content="' . esc_url( $og_image ) . '" />' . "\n";
		}
	}

	/**
	 * Add Open Graph meta tags for author pages
	 */
	public function add_author_opengraph_meta() {
		if ( ! is_author() ) {
			return;
		}

		$author_id = get_queried_object_id();
		$author    = get_queried_object();

		$og_title = get_user_meta( $author_id, '_og_title', true );
		if ( '' === $og_title ) {
			$og_title = $author->display_name;
		}

		$og_description = get_user_meta( $author_id, '_og_description', true );
		if ( '' === $og_description ) {
			$og_description = wp_trim_words( $author->description, 25 );
		}
		$og_image = get_user_meta( $author_id, '_og_image', true );

		echo '<!-- Product Scraper Author Open Graph -->' . "\n";
		echo '<meta property="og:type" content="profile" />' . "\n";
		echo '<meta property="og:title" content="' . esc_attr( $og_title ) . '" />' . "\n";
		echo '<meta property="og:description" content="' . esc_attr( $og_description ) . '" />' . "\n";
		echo '<meta property="og:url" content="' . esc_url( get_author_posts_url( $author_id ) ) . '" />' . "\n";
		echo '<meta property="og:site_name" content="' . esc_attr( get_bloginfo( 'name' ) ) . '" />' . "\n";
		echo '<meta property="profile:username" content="' . esc_attr( $author->user_login ) . '" />' . "\n";

		if ( $og_image ) {
			echo '<meta property="og:image" content="' . esc_url( $og_image ) . '" />' . "\n";
		} elseif ( function_exists( 'get_avatar_url' ) ) {
			$avatar_url = get_avatar_url( $author_id, array( 'size' => 300 ) );
			echo '<meta property="og:image" content="' . esc_url( $avatar_url ) . '" />' . "\n";
		}
	}

	/**
	 * Output Twitter Card meta tags for singular content.
	 */
	public function add_twitter_card_meta() {
		if ( ! is_singular() ) {
			return;
		}

		global $post;

		$twitter_title = get_post_meta( $post->ID, '_twitter_title', true );
		if ( '' === $twitter_title ) {
			$twitter_title = get_the_title();
		}

		$twitter_description = get_post_meta( $post->ID, '_twitter_description', true );
		if ( '' === $twitter_description ) {
			$twitter_description = wp_trim_words( get_the_excerpt(), 25 );
		}
		$twitter_image = get_post_meta( $post->ID, '_twitter_image', true );

		echo '<!-- Product Scraper Twitter Card -->' . "\n";
		echo '<meta name="twitter:card" content="summary_large_image" />' . "\n";
		echo '<meta name="twitter:title" content="' . esc_attr( $twitter_title ) . '" />' . "\n";
		echo '<meta name="twitter:description" content="' . esc_attr( $twitter_description ) . '" />' . "\n";

		if ( $twitter_image ) {
			echo '<meta name="twitter:image" content="' . esc_url( $twitter_image ) . '" />' . "\n";
		} elseif ( has_post_thumbnail( $post->ID ) ) {
			$image_url = wp_get_attachment_image_url( get_post_thumbnail_id( $post->ID ), 'large' );
			echo '<meta name="twitter:image" content="' . esc_url( $image_url ) . '" />' . "\n";
		}
	}

	/**
	 * Output appropriate structured data based on the current view.
	 */
	public function add_structured_data() {
		if ( is_singular( 'product' ) && function_exists( 'wc_get_product' ) ) {
			$this->output_product_structured_data();
		} elseif ( is_singular() ) {
			$this->output_article_structured_data();
		} elseif ( is_tax() || is_category() || is_tag() ) {
			$this->output_taxonomy_structured_data();
		} elseif ( is_author() ) {
			$this->output_author_structured_data();
		}

		if ( is_front_page() ) {
			$this->output_website_structured_data();
		}
	}

	/**
	 * Output structured data for taxonomy pages
	 */
	private function output_taxonomy_structured_data() {
		$term = get_queried_object();
		if ( ! $term ) {
			return;
		}

		$schema = array(
			'@context'    => 'https://schema.org',
			'@type'       => 'CollectionPage',
			'name'        => $term->name,
			'description' => wp_strip_all_tags( $term->description ),
			'url'         => get_term_link( $term ),
		);

		echo '<script type="application/ld+json">' .
			wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT ) .
			'</script>' . "\n";
	}

	/**
	 * Output structured data for author pages
	 */
	private function output_author_structured_data() {
		$author_id = get_queried_object_id();
		$author    = get_queried_object();

		$schema = array(
			'@context'    => 'https://schema.org',
			'@type'       => 'ProfilePage',
			'name'        => $author->display_name,
			'description' => wp_strip_all_tags( $author->description ),
			'url'         => get_author_posts_url( $author_id ),
			'mainEntity'  => array(
				'@type'       => 'Person',
				'name'        => $author->display_name,
				'description' => wp_strip_all_tags( $author->description ),
			),
		);

		echo '<script type="application/ld+json">' .
			wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT ) .
			'</script>' . "\n";
	}

	/**
	 * Enhance default WordPress sitemaps with SEO-related data.
	 */
	public function setup_sitemaps() {
		add_filter( 'wp_sitemaps_add_provider', array( $this, 'enhance_sitemaps' ), 10, 2 );
		add_filter( 'wp_sitemaps_posts_query_args', array( $this, 'enhance_posts_sitemap' ) );
		add_filter( 'wp_sitemaps_taxonomies_query_args', array( $this, 'enhance_taxonomies_sitemap' ) );
	}

	/**
	 * Register plugin settings for various SEO features.
	 */
	public function register_settings() {
		// Core SEO settings.
		register_setting( 'product_scraper_seo_settings', 'product_scraper_seo_title_template' );
		register_setting( 'product_scraper_seo_settings', 'product_scraper_seo_description_template' );
		register_setting( 'product_scraper_seo_settings', 'product_scraper_seo_robots_default' );

		// Social media settings.
		register_setting( 'product_scraper_seo_settings', 'product_scraper_facebook_app_id' );
		register_setting( 'product_scraper_seo_settings', 'product_scraper_twitter_site' );

		// Analytics settings.
		register_setting( 'product_scraper_settings', 'product_scraper_google_analytics_id' );
		register_setting( 'product_scraper_settings', 'product_scraper_google_search_console' );
		register_setting( 'product_scraper_settings', 'product_scraper_semrush_api' );
		register_setting( 'product_scraper_settings', 'product_scraper_ahrefs_api' );
		register_setting( 'product_scraper_settings', 'product_scraper_ga4_property_id' );
		register_setting( 'product_scraper_settings', 'product_scraper_pagespeed_api' );

		// Advanced SEO settings.
		register_setting( 'product_scraper_seo_settings', 'product_scraper_seo_breadcrumbs' );
		register_setting( 'product_scraper_seo_settings', 'product_scraper_seo_schema' );
		register_setting( 'product_scraper_seo_settings', 'product_scraper_seo_clean_permalinks' );
	}

	/**
	 * Enqueue admin assets on plugin and editing screens.
	 *
	 * @param string $hook Current admin page hook suffix.
	 */
	public function enqueue_admin_scripts( $hook ) {
		$plugin_pages = array(
			'toplevel_page_scraper-analytics',
			'scraper-analytics_page_scraper-keywords',
			'scraper-analytics_page_scraper-competitors',
			'toplevel_page_product-scraper',
			'scraper-analytics_page_product-scraper',
			'scraper-analytics_page_seo-assistant',
			'scraper-analytics_page_seo-settings',
			'scraper-analytics_page_seo-analysis',
			'scraper-analytics_page_link-manager',
			'scraper-analytics_page_scraper-reports',
			'scraper-analytics_page_scraper-settings',
		);

		$is_plugin_page = in_array( $hook, $plugin_pages, true );
		$is_post_edit   = in_array( $hook, array( 'post.php', 'post-new.php' ), true );
		$is_tax_edit    = in_array( $hook, array( 'edit-tags.php', 'term.php' ), true );

		if ( $is_plugin_page || $is_post_edit || $is_tax_edit ) {
			// Enqueue Chart.js for analytics.
			wp_enqueue_script(
				'chart-js',
				'https://cdn.jsdelivr.net/npm/chart.js',
				array(),
				'3.9.1',
				true
			);

			// Enqueue Select2 for better dropdowns.
			wp_enqueue_script(
				'select2-js',
				'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js',
				array( 'jquery' ),
				'4.1.0',
				true
			);

			wp_enqueue_style(
				'select2-css',
				'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css',
				array(),
				'4.1.0'
			);

			// Plugin styles.
			wp_enqueue_style(
				'product-scraper-analytics-css',
				PRODUCT_SCRAPER_PLUGIN_URL . 'assets/sa-analytics.css',
				array(),
				PRODUCT_SCRAPER_VERSION
			);

			wp_enqueue_style(
				'product-scraper-seo-admin-css',
				PRODUCT_SCRAPER_PLUGIN_URL . 'assets/seo-admin.css',
				array(),
				PRODUCT_SCRAPER_VERSION
			);

			// Plugin scripts.
			wp_enqueue_script(
				'product-scraper-seo-admin-js',
				PRODUCT_SCRAPER_PLUGIN_URL . 'assets/seo-admin.js',
				array( 'jquery', 'wp-api', 'chart-js', 'select2-js' ),
				PRODUCT_SCRAPER_VERSION,
				true
			);

			wp_localize_script(
				'product-scraper-seo-admin-js',
				'productScraper',
				array(
					'ajaxurl'       => admin_url( 'admin-ajax.php' ),
					'nonce'         => wp_create_nonce( 'product_scraper_nonce' ),
					'api_nonce'     => wp_create_nonce( 'wp_rest' ),
					'api_url'       => rest_url( 'product-scraper/v1/' ),
					'site_url'      => get_site_url(),
					'vendor_loaded' => file_exists( __DIR__ . '/vendor/autoload.php' ),
					'post_id'       => get_the_ID(),
					'strings'       => array(
						'saving'     => __( 'Saving...', 'product-scraper' ),
						'saved'      => __( 'Saved!', 'product-scraper' ),
						'error'      => __( 'Error saving data.', 'product-scraper' ),
						'analyzing'  => __( 'Analyzing content...', 'product-scraper' ),
						'optimizing' => __( 'Optimizing...', 'product-scraper' ),
					),
				)
			);
		}
	}

	/**
	 * Register REST API routes for content analysis endpoints.
	 */
	public function register_rest_routes() {
		register_rest_route(
			'product-scraper/v1',
			'/analyze-content',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'rest_analyze_content' ),
				'permission_callback' => array( $this, 'rest_permission_check' ),
			)
		);

		register_rest_route(
			'product-scraper/v1',
			'/optimize-content',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'rest_optimize_content' ),
				'permission_callback' => array( $this, 'rest_permission_check' ),
			)
		);

		register_rest_route(
			'product-scraper/v1',
			'/get-keyword-suggestions',
			array(
				'methods'             => 'POST',
				'callback'            => array( $this, 'rest_get_keyword_suggestions' ),
				'permission_callback' => array( $this, 'rest_permission_check' ),
			)
		);
	}

	/**
	 * REST API permission callback.
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return bool
	 */
	public function rest_permission_check( $request ) {
		unset( $request );

		return current_user_can( 'edit_posts' );
	}

	// Helper methods for structured data.
	/**
	 * Output structured data for WooCommerce products.
	 */
	private function output_product_structured_data() {
		global $post;
		$product = wc_get_product( $post->ID );

		if ( ! $product ) {
			return;
		}

		$ecommerce_seo = new ProductScraper_Ecommerce_SEO();
		$schema        = $ecommerce_seo->generate_product_schema( $post->ID );

		if ( ! empty( $schema ) && ! isset( $schema['error'] ) ) {
			echo '<script type="application/ld+json">' .
				wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT ) .
				'</script>' . "\n";
		}
	}

	/**
	 * Output structured data for articles.
	 */
	private function output_article_structured_data() {
		global $post;

		$schema = array(
			'@context'      => 'https://schema.org',
			'@type'         => 'Article',
			'headline'      => get_the_title(),
			'description'   => wp_trim_words( get_the_excerpt(), 25 ),
			'datePublished' => get_the_date( 'c' ),
			'dateModified'  => get_the_modified_date( 'c' ),
			'author'        => array(
				'@type' => 'Person',
				'name'  => get_the_author(),
			),
			'publisher'     => array(
				'@type' => 'Organization',
				'name'  => get_bloginfo( 'name' ),
				'logo'  => array(
					'@type' => 'ImageObject',
					'url'   => $this->get_site_logo(),
				),
			),
		);

		if ( has_post_thumbnail() ) {
			$schema['image'] = array(
				'@type'  => 'ImageObject',
				'url'    => get_the_post_thumbnail_url( $post->ID, 'full' ),
				'width'  => 1200,
				'height' => 630,
			);
		}

		echo '<script type="application/ld+json">' .
			wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT ) .
			'</script>' . "\n";
	}

	/**
	 * Output website-level structured data markup.
	 */
	private function output_website_structured_data() {
		$schema = array(
			'@context'        => 'https://schema.org',
			'@type'           => 'WebSite',
			'name'            => get_bloginfo( 'name' ),
			'description'     => get_bloginfo( 'description' ),
			'url'             => home_url(),
			'potentialAction' => array(
				'@type'       => 'SearchAction',
				'target'      => array(
					'@type'       => 'EntryPoint',
					'urlTemplate' => home_url( '/?s={search_term_string}' ),
				),
				'query-input' => 'required name=search_term_string',
			),
		);

		echo '<script type="application/ld+json">' .
			wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT ) .
			'</script>' . "\n";
	}

	/**
	 * Retrieve the site logo URL if set.
	 *
	 * @return string Site logo URL or empty string.
	 */
	private function get_site_logo() {
		$custom_logo_id = get_theme_mod( 'custom_logo' );
		if ( $custom_logo_id ) {
			return wp_get_attachment_url( $custom_logo_id );
		}
		return '';
	}

	/**
	 * Redirect attachment pages to their canonical URLs to prevent duplicates.
	 */
	public function canonical_redirects() {
		// Prevent duplicate content by redirecting non-canonical URLs.
		if ( is_attachment() ) {
			wp_safe_redirect( get_attachment_link(), 301 );
			exit;
		}
	}

	/**
	 * Render the admin sidebar
	 *
	 * @param string $current_page The current page slug.
	 */
	public static function product_scraper_render_sidebar( $current_page = '' ) {
		if ( empty( $current_page ) ) {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Value is only used to highlight the active admin menu item.
			$current_page = isset( $_GET['page'] ) ? sanitize_text_field( wp_unslash( $_GET['page'] ) ) : '';
		}

		$sidebar_path = PRODUCT_SCRAPER_PLUGIN_PATH . 'templates/sidebar.php';
		if ( file_exists( $sidebar_path ) ) {
			include $sidebar_path;
		}
	}
}

/* phpcs:enable WordPress.Files.FileName.InvalidClassFileName */

new ProductScraper();
