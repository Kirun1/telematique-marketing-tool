Project: Product Scraper & Analytics (WordPress plugin)

Summary

- This repo is a WordPress plugin that provides scraping, storage, WooCommerce import, and SEO features.
- Main entry: `class-productscraper.php` (instantiates services and registers hooks).
- Key directories: `includes/` (service classes), `assets/` (CSS/JS), `templates/`, and `vendor/` (Composer libs).

What an AI coding agent should know (short & actionable)

- Architecture: `new ProductScraper()` in `class-productscraper.php` boots core services (storage, analytics, SEO helpers, API integrations). Treat `ProductScraper` as the application container.
- Data flow: scraping -> `ProductScraperEngine` (`includes/scraper/class-scraper.php`) -> `ProductScraperDataStorage` (`includes/scraper/class-data-storage.php`) -> optional `WooCommerceProductImporter` (`includes/class-woocommerceproductimporter.php`). Use `save_products()` and `get_products()` to interact with the plugin DB table.
- Integration points: WooCommerce (uses `WC_Product`, product CPT), WordPress REST (`product-scraper/v1` routes), Composer (`vendor/autoload.php`, composer.json includes `google/apiclient`), and external CDNs (Chart.js, Select2).

Project-specific conventions & pitfalls

- File/class naming: files use `class-*.php` but class names are inconsistent (e.g., `ProductScraperDataStorage` vs `ProductScraper_DataStorage` patterns elsewhere). Do not introduce PSR-4 namespaces; follow existing procedural/class style unless refactoring explicitly.
- Activation hook caveat: `includes/scraper/class-data-storage.php` calls `register_activation_hook( __FILE__, ...)` inside the included file. `__FILE__` there points to the include file, not the plugin main file — be careful when modifying activation behavior. Prefer registering activation hooks from the main plugin file (`class-productscraper.php`) or use explicit plugin main path.
- Database: custom table `{$wpdb->prefix}scraped_products` created with `dbDelta()`. The code currently drops the table before creating it — avoid changing this behavior silently as it will destroy stored data.
- Output & escaping: HTML output uses `esc_html`, `esc_attr`, `esc_url` consistently in `class-productscraper.php`. Follow those patterns for XSS-safe output.

Developer workflows to know

- Install/update PHP deps: run `composer install` in plugin root to populate `vendor/` (composer.json lists `google/apiclient`). The plugin checks `file_exists( __DIR__ . '/vendor/autoload.php' )` before using vendor libs.
- Local testing: run the plugin inside a WordPress + WooCommerce local environment (LocalWP, Docker, or similar). No test harness is included; manual testing via WP admin and REST endpoints is expected.
- Debugging: enable `WP_DEBUG` and `WP_DEBUG_LOG` in `wp-config.php`. Use `error_log()` calls already present in scraper code (e.g., fetch failures).

Patterns & examples (copyable guidance)

- To call the scraper programmatically:
  - Instantiate `ProductScraperEngine`, call `set_base_url()` then `scrape_products()`.
  - Save results with `ProductScraperDataStorage::save_products( $products, $source_url )`.
- REST endpoints: inspect `class-productscraper.php::register_rest_routes()` for `product-scraper/v1` routes and permission callback `current_user_can('edit_posts')`.
- WooCommerce import: `WooCommerceProductImporter::import_products()` expects the scraper product structure (keys: `name`, `url`, `image`, `price`, `full_description`, `sku`, `categories`, `gallery_images`). Use `parse_price()` in the importer for numeric conversion.

What not to change without discussion

- Don't convert the codebase to namespaced PSR-4 or change autoloading without a migration plan — many classes are referenced by name in procedural hooks.
- Avoid dropping or altering the custom DB schema behaviour (table drop/create) in `class-data-storage.php` without data migration notes.

If you need to make a change, helpful places to edit or inspect

- Boot / wiring: `class-productscraper.php`
- Scraper logic: `includes/scraper/class-scraper.php`
- Storage & DB schema: `includes/scraper/class-data-storage.php`
- WooCommerce import: `includes/class-woocommerceproductimporter.php`
- Admin UI + REST glue: `includes/admin/class-admin.php` and `class-productscraper.php` (enqueue, REST routes)
- Composer deps: `composer.json` and `vendor/`

Next steps I can take for you

- Run a targeted refactor (activation hook fix, safer db creation, or add unit-test scaffolding).
- Add a development README with LocalWP/Docker steps.

Questions for you

- Do you want me to register activation hooks from `class-productscraper.php` instead of the included file (`class-data-storage.php`)?
- Should I add a small `README-dev.md` describing how to run the plugin locally (LocalWP/Docker + composer)?
