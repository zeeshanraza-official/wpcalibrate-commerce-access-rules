=== WPCalibrate Content Restriction ===
Contributors: wpcalibrate
Tags: woocommerce, content restriction, access control, restrict content, paywall
Requires at least: 6.5
Tested up to: 7.1.3
Requires PHP: 8.2
Stable tag: 1.0.2
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Control and restrict access to WordPress posts, pages, products, shortcodes, and blocks using WooCommerce customer purchases, roles, and dates.

== Description ==

**WPCalibrate Content Restriction** is a production-ready WordPress access control plugin built specifically for WooCommerce stores. It allows store owners and site administrators to restrict complete content items or specific content sections based on qualifying WooCommerce purchases, user roles, specific customers, and date ranges.

Unlike heavy membership plugins, WPCalibrate Content Restriction is lightweight, focused, and modular. It integrates cleanly with WooCommerce HPOS (High-Performance Order Storage) and public CRUD APIs without creating bloated membership tiers or altering standard WooCommerce checkout flows.

### Key Features

* **Central Reusable Restriction Rules:** Create access rules once and assign them across posts, pages, WooCommerce products, custom post types, shortcodes, or Gutenberg blocks.
* **WooCommerce Purchase Rules:** Grant content access when a customer purchases specific WooCommerce products or any product within designated product categories.
* **Intelligent Refund Handling:** Automatically revokes access if a qualifying purchase is refunded or cancelled, while preserving access if unrelated line items are refunded.
* **Flexible Access Durations:** Support permanent (lifetime) access, configurable durations (e.g. 30 days, 12 weeks, 6 calendar months), or fixed calendar date windows.
* **Group Conditions with AND/OR Logic:** Combine multiple requirements (such as "Purchased Product X OR has VIP Role") using flexible condition groups.
* **Custom Denied Behaviors:** Show custom restriction notices, display purchase buttons, redirect to login, redirect to custom internal pages, or redirect directly to the required product.
* **Content Leakage Prevention:** Automatically protects excerpts, RSS feeds, search listings, archive loops, and WP REST API responses from exposing restricted content.
* **Gutenberg Restriction Block:** Native `wpcalibrate/content-restriction` container block to protect nested editor blocks server-side.
* **Namespaced Shortcode:** Protect inline content with `[wpcalibrate_restrict rule="123"]...[/wpcalibrate_restrict]`.
* **Optional Elementor Integration:** Server-side restriction controls for Elementor sections, containers, and widgets (gracefully dormant when Elementor is not installed).
* **Optional WooCommerce Subscriptions Integration:** Support subscription-based access conditions when WooCommerce Subscriptions is active.
* **Customer Access Inspector:** Built-in administrative diagnostic tool to search any customer and inspect their real-time access grants and qualifying orders.

== Installation ==

1. Upload the `wpcalibrate-commerce-access-rules` folder to your `/wp-content/plugins/` directory, or install through the WordPress Plugins menu.
2. Activate the plugin through the 'Plugins' screen in WordPress.
3. Ensure WooCommerce is installed and active.
4. Navigate to **WPCalibrate -> Content Restriction** to configure your restriction rules and default settings.

== Frequently Asked Questions ==

= Does this plugin require WooCommerce? =
Yes, WooCommerce is required for commercial purchase evaluation and order status tracking.

= Does it support WooCommerce HPOS (High-Performance Order Storage)? =
Yes, WPCalibrate Content Restriction fully supports WooCommerce HPOS and standard order storage via official WooCommerce CRUD APIs.

= How does partial refund handling work? =
If a customer purchases multiple items or multiple quantities and one item is refunded, access is only revoked if the specific line item granting access was fully refunded. Unrelated line items being refunded will not revoke valid access.

= What happens when access expires? =
Access checks fail closed immediately at request time based on verified purchase timestamps and server site timezone. The plugin does not depend solely on WP-Cron to deny expired users.

= Can administrators preview restricted content? =
Yes, administrators with the `wpcalibrate_manage_access_rules` capability automatically bypass frontend restrictions to preview protected content.

== Screenshots ==

1. Central Restriction Rules listing and status controls.
2. Condition builder with AND/OR logic and WooCommerce product selectors.
3. Customer Access Inspector showing real-time access decisions.
4. Content edit-screen restriction metabox.

== Changelog ==

= 1.0.2 =
* Fix: Prevented silent browser form submission cancellation when creating new rules by setting safe min values, adding form novalidate, and adding hidden routing fields.
* Fix: Added manage_options capability fallback for complete administrator compatibility.
* Fix: Broadened rule repository post status handling for draft/pending rules.
* Feature: Implemented demo restriction data generator seeding 4 production-ready restriction rules with one-click reload.
* Feature: Added native GitHub release updater support for seamless updates directly from the WordPress dashboard.

= 1.0.1 =
* Fix: Restrained admin sidebar menu logo icon to standard WordPress 20x20px dimension across all admin screens.
* Fix: Standardized sidebar menu icon color to match WordPress admin gray (#a7aaad) with hover/active states.
* Fix: Resolved fatal error on Overview and Integrations tabs by replacing invalid is_hpos_enabled() with canonical custom_orders_table_usage_is_enabled().

= 1.0.0 =
* Initial production release.
* Central rule management with AND/OR condition groups.
* WooCommerce product and category purchase evaluation.
* HPOS and Cart/Checkout Blocks compatibility.
* Gutenberg restriction block and `[wpcalibrate_restrict]` shortcode.
* Excerpt, search, feed, and REST API leakage prevention.
* Customer Access Inspector.
* Optional Elementor and WooCommerce Subscriptions integration modules.
