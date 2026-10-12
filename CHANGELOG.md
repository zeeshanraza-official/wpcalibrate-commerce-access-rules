# Changelog: WPCalibrate Content Restriction

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.0.2] - 2026-10-12

### Fixed
- **Rule Publishing Validation Block**:
  - Eliminated browser form submission cancellation caused by hidden `#wpcal-duration-val` having `min="1"` with default `value="0"`.
  - Added `novalidate` to rule edit form and defaulted duration value to `30` with `min="0"`.
  - Added hidden routing inputs (`page`, `tab`) and updated `Menu::handle_form_actions()` to read `$_REQUEST['page']`.
  - Added `manage_options` fallback across all permission checks in `Capabilities`.
  - Broadened `RuleRepository::find()` and `count()` queries to accept all non-trash post statuses (`publish`, `pending`, `draft`, `private`).

### Added
- **Demo Restriction Rules Generator**:
  - Implemented `DemoDataSeeder` to automatically generate 4 realistic demo restriction rules (VIP Club Access, Digital Course / Ebook Buyer, Registered Members, Seasonal Promotion).
  - Added manual "Load Demo Rules" buttons to admin toolbar and empty state card.

## [1.0.1] - 2026-10-12

### Fixed
- **Admin Sidebar Icon Sizing & Standard Color**:
  - Restrained sidebar parent menu icon dimensions to 20x20px (`width: 20px !important; height: 20px !important; padding: 7px 0 0 0 !important; object-fit: contain !important;`) on all WordPress admin screens.
  - Aligned icon color to native WordPress dashboard icon gray (`#a7aaad` via CSS filter brightness/invert) and active/hover white (`#ffffff`).
- **Overview Tab Critical Error (HPOS Method Check)**:
  - Resolved fatal error caused by calling non-existent `\Automattic\WooCommerce\Utilities\OrderUtil::is_hpos_enabled()`.
  - Replaced with canonical `OrderUtil::custom_orders_table_usage_is_enabled()` guarded by `method_exists()` across Overview tab, Integrations tab, and REST controller.

## [1.0.0] - 2026-10-12

### Added
- **Core Architecture:**
  - Plugin bootstrap with PHP 8.2 & 8.3 compatibility, WordPress 6.5–7.1.3 support, and WooCommerce 8.0–11.2.1 support.
  - Declared full compatibility with WooCommerce HPOS (`custom_order_tables`) and Cart/Checkout Blocks (`cart_checkout_blocks`).
  - PSR-4 autoloader for `WPCalibrate\CommerceAccessRules\` namespace.
  - Private Custom Post Type `wpcal_access_rule` with structured post meta storage.
- **Rule Engine & Condition Groups:**
  - Support for multiple conditions grouped with AND/OR logical relations.
  - Conditions: Logged-in user, logged-out user, specific WordPress roles, specific individual user IDs, WooCommerce products, WooCommerce product categories, active WooCommerce Subscriptions, and fixed calendar date windows.
  - Duration models: Permanent access, configurable days, configurable weeks, calendar months, and fixed start/end windows honoring WordPress site timezone.
  - Multi-rule priority sorting and configurable conflict resolution (ANY rule vs ALL rules).
- **Access Evaluation Service:**
  - Central `AccessEvaluator` returning structured `AccessResult` objects with machine-readable reason codes (`allowed`, `guest`, `role_mismatch`, `user_mismatch`, `no_qualifying_purchase`, `order_status_invalid`, `before_start`, `expired`, `subscription_inactive`).
  - Request-time fail-closed expiry validation (independent of background cron jobs).
  - Preview bypass for administrators with `wpcalibrate_manage_access_rules`.
- **WooCommerce Purchase Evaluation:**
  - HPOS-compatible order queries using official `wc_get_orders()` CRUD APIs.
  - Configurable qualifying order statuses (defaulting to completed and processing).
  - Line-item aware partial refund handling (access preserved if qualifying item quantity remains valid).
  - Idempotent order hook listeners (`woocommerce_order_status_changed`, `woocommerce_order_partially_refunded`, `woocommerce_order_fully_refunded`).
- **Content Restriction & Leakage Protection:**
  - Server-side full page restriction on `template_redirect` with loop-preventing safe redirects.
  - `the_content` inline restriction with custom messages and purchase buttons.
  - Namespaced shortcode `[wpcalibrate_restrict rule="..."]` with recursion guards.
  - Native Gutenberg restriction block `wpcalibrate/content-restriction` for server-side nested block protection.
  - Content protection on excerpts (`get_the_excerpt`, `the_excerpt`), RSS feeds (`the_content_feed`), WordPress search and archive queries (`pre_get_posts`), and WP REST API responses (`rest_prepare_post`, etc.).
- **User Interface & Administration:**
  - Shared top-level WPCalibrate admin menu with duplicate detection and isolated plugin submenu.
  - Central rule management dashboard with search, status filters, duplicate action, toggle action, and pagination.
  - Interactive customer Access Inspector for diagnosing real-time access and linking to qualifying WooCommerce orders.
  - Content edit-screen restriction metabox on posts, pages, WooCommerce products, and public CPTs.
  - General settings, diagnostic logging toggle, and data retention preferences on uninstall.
  - Official WPCalibrate brand contact channels (+447474795976 Phone/WhatsApp, support@wpcalibrate.com).
- **Optional Integrations:**
  - Elementor server-side restriction controls for sections, containers, and widgets.
  - WooCommerce Subscriptions condition evaluation via official public functions.
- **Licensing Contract:**
  - Abstract license service interface with masked key fields and developer notice pending provider specifications.
- **Testing & Quality:**
  - Automated PHP test suite for rule evaluation, purchase conditions, refunds, durations, and security checks.
