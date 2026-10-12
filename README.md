<div align="center">

# WPCalibrate Content Restriction

[![WordPress Compatibility](https://img.shields.io/badge/WordPress-6.5%20to%207.1+-21759B?logo=wordpress&logoColor=white)](https://wordpress.org)
[![PHP Version](https://img.shields.io/badge/PHP-8.2%20%7C%208.3-777BB4?logo=php&logoColor=white)](https://php.net)
[![WooCommerce HPOS](https://img.shields.io/badge/WooCommerce%20HPOS-Compatible-96588A?logo=woocommerce&logoColor=white)](https://woocommerce.com)
[![Software License](https://img.shields.io/badge/License-GPL--2.0--or--later-blue.svg)](LICENSE)
[![Latest Release](https://img.shields.io/badge/Release-v1.0.2-success.svg)](https://github.com/zeeshanraza-official/wpcalibrate-commerce-access-rules/releases)

**A high-performance, enterprise-grade WordPress content restriction and paywall engine built specifically for WooCommerce stores.**

[About](#about) • [Key Features](#key-features) • [Documentation & Guide](#documentation--configuration-guide) • [Gutenberg & Shortcode](#gutenberg-block--shortcodes) • [Dashboard Updates](#seamless-wordpress-dashboard-updates) • [Installation](#installation--requirements) • [Changelog](#changelog) • [Support](#support--contact)

</div>

---

## About

**WPCalibrate Content Restriction** empowers WooCommerce store owners, publishers, and educators to monetize digital content without the bloat, complexity, or checkout conflicts of traditional membership suites.

Whether selling premium video courses, subscriber-only research reports, digital downloads, VIP community articles, or time-delimited promotions, WPCalibrate Content Restriction enforces strict, server-side access control across posts, pages, custom post types, Gutenberg blocks, and REST API endpoints.

### Why WPCalibrate Content Restriction?
- **Zero Checkout Conflicts**: Operates directly with native WooCommerce products and checkout flows—no alternate cart logic or synthetic gateway hacks.
- **HPOS Ready**: Built from the ground up for WooCommerce High-Performance Order Storage (HPOS) and public CRUD APIs.
- **High Performance & PSR-4 Architecture**: Pure PHP 8.2+ codebase with zero external composer bloat at runtime, persistent object caching, and request-time fail-closed validation.
- **Line-Item Aware Refund Handling**: Protects your revenue. When an order is partially refunded, access is only revoked if the specific line item granting access was returned.
- **Native Dashboard Updates**: Built-in GitHub updater allows store administrators to receive update notifications and perform one-click upgrades directly within the WordPress dashboard (`wp-admin/plugins.php` and `wp-admin/update-core.php`).

---

## Key Features

### 🎯 Central Reusable Restriction Rules
Create rules once under **WPCalibrate → Content Restriction** and assign them across your entire website:
- Apply to individual posts, pages, or WooCommerce products via the editor metabox.
- Protect inline sections using Gutenberg blocks or shortcodes.
- Multi-rule priority weighting and conflict resolution (Require **ANY** rule vs. Require **ALL** rules).

### 🛒 Comprehensive WooCommerce Purchase Rules
- **Product Purchases**: Restrict content to buyers of specific WooCommerce simple, variable, or digital products.
- **Category Purchases**: Grant access to customers who bought any product within designated product categories.
- **Order Status Filters**: Customize qualifying order statuses per rule (defaults to `Completed` and `Processing`).
- **Subscription Support**: Native integration hooks for WooCommerce Subscriptions to require active recurring subscriptions.

### ⏳ Flexible Access Durations & Expirations
- **Permanent Access**: Lifetime access after qualifying purchase.
- **Configurable Durations**: Granular access limits (e.g., `30 Days`, `12 Weeks`, `6 Months`) counted from the customer's completed order date.
- **Fixed Date Windows**: Ideal for seasonal sales, live cohort bootcamps, and early-access previews (honors site timezone).
- **Fail-Closed Security**: Evaluated at request time—does not depend on delayed WP-Cron background jobs to revoke expired access.

### 🛡️ Group Conditions with AND / OR Logic
Create sophisticated access criteria by organizing conditions into logical groups:
- **Condition Types**: Logged-in state, user roles, specific user IDs, purchased products, purchased product categories, active subscriptions, and date windows.
- **Multi-Group Logic**: Require **ALL** groups (AND) or allow access if **ANY** group is met (OR).

### 🚫 Flexible Denied Access Actions
Choose what visitors see when they do not meet the criteria:
- **Teaser + Notice**: Show the post excerpt followed by a customizable restriction notice and login button.
- **Purchase Teaser**: Display an automated purchase button linking directly to the required WooCommerce product.
- **Smart Redirection**: Redirect to WordPress login page (with safe return URL), WooCommerce My Account, or a custom landing page.
- **Content Obfuscation**: Blur protected content with a modern glassmorphism overlay and call-to-action banner.

### 🔒 Content Leakage Prevention
Ensures protected content is not exposed through secondary channels:
- Automatically sanitizes post excerpts in archive loops.
- Strips restricted content from RSS and Atom feeds (`the_content_feed`).
- Conceals restricted text in WordPress site search results.
- Intercepts and shields WordPress REST API responses (`wp/v2/posts`, `wp/v2/pages`).

### 🔍 Built-in Customer Access Inspector
An administrative simulation tool located under **WPCalibrate → Access Inspector**:
- Search any user or customer by username or email.
- Real-time simulation showing whether the user has access to any specific post, page, or rule.
- Detailed audit breakdown displaying evaluated condition groups, qualifying orders, order dates, and expiry timestamps.

---

## Documentation & Configuration Guide

### 1. Creating a Restriction Rule
1. In your WordPress admin, navigate to **WPCalibrate → Content Restriction**.
2. Click **+ Add New Rule** (or click **Load Demo Rules** to explore pre-built configurations).
3. Enter a descriptive **Rule Title** (e.g., *VIP Academy Course Access*).
4. In **Access Conditions**, add condition groups:
   - Select condition criteria: *Purchased Product*, *User Role*, *User is Logged In*, etc.
   - Configure condition parameters (e.g., select products or roles).
5. In **Access Duration & Expiry**, select *Permanent*, *Duration from Purchase Date*, or *Fixed Date Window*.
6. In **Restriction Action (When Denied)**, choose how to present the restriction (Teaser, Redirect, Obfuscation).
7. Under **Status & Priority**, set status to **Active (Enforcing)** and set numerical priority.
8. Click **Publish Restriction Rule**.

### 2. Protecting Entire Posts & Pages
1. Open the post, page, or product in the WordPress block or classic editor.
2. Look for the **WPCalibrate Content Restriction** metabox in the sidebar.
3. Toggle **Enable Content Restriction** on.
4. Select one or more rules to apply to this content item.
5. Save or update the post.

---

## Gutenberg Block & Shortcodes

### Native Gutenberg Container Block
WPCalibrate Content Restriction includes a native server-side container block for block theme and Gutenberg workflows:
1. In the block editor, click the **+** inserter and search for **Content Restriction** (`wpcalibrate/content-restriction`).
2. Add the block and select which restriction rule governs the nested container.
3. Place any inner blocks (videos, downloads, audio, paragraphs) inside the container.
4. Unqualified visitors will only see the configured fallback, while the inner blocks are completely omitted from the HTML server response.

### Namespaced Shortcode
For classic themes, Elementor text widgets, and template files:

```html
[wpcalibrate_restrict rule="48"]
    <h3>Exclusive Video Lesson</h3>
    <p>This premium tutorial is only visible to customers who purchased the Masterclass product.</p>
[/wpcalibrate_restrict]
```

#### Shortcode Attributes:
| Attribute | Type | Description |
| :--- | :--- | :--- |
| `rule` | `integer` | *(Required)* ID of the restriction rule to evaluate. |
| `message` | `string` | *(Optional)* Custom denial notice override. |
| `redirect` | `string` | *(Optional)* Target URL to redirect unauthorized visitors. |

---

## Seamless WordPress Dashboard Updates

This plugin is configured with native GitHub updater integration:
- When a new version is released on GitHub (`v1.0.3`, `v1.0.4`, etc.), WordPress automatically displays the standard **"There is a new version of WPCalibrate Content Restriction available"** notification.
- Administrators can review the changelog directly inside the WordPress plugin information modal and update with a single click in **Plugins → Installed Plugins** or **Dashboard → Updates**.
- Zero third-party updater dependencies or manual FTP uploads required for future versions.

---

## Installation & Requirements

### System Requirements
| Component | Minimum Version | Recommended |
| :--- | :--- | :--- |
| **WordPress** | 6.5 | 6.7+ |
| **PHP** | 8.2 | 8.2 or 8.3 |
| **WooCommerce** | 8.0 | 9.0+ (HPOS enabled) |
| **Web Server** | Nginx or Apache | HTTPS enabled |

### Standard Installation
1. Download the latest `wpcalibrate-commerce-access-rules-x.x.x.zip` from [Releases](https://github.com/zeeshanraza-official/wpcalibrate-commerce-access-rules/releases).
2. In your WordPress admin, navigate to **Plugins → Add New Plugin → Upload Plugin**.
3. Choose the ZIP file and click **Install Now**.
4. Click **Activate Plugin**.
5. Ensure WooCommerce is active on your site.
6. Access your settings under **WPCalibrate → Content Restriction**.

---

## Changelog

### [1.0.2] - 2026-10-12
- **Fixed**: Eliminated silent browser HTML5 constraint validation blockage when clicking "Publish Restriction Rule" by correcting duration input bounds, applying form `novalidate`, and adding hidden routing parameters.
- **Fixed**: Added `manage_options` capability fallback across all permission verifications for flawless multisite and custom administrator compatibility.
- **Fixed**: Broadened rule repository post status filtering to properly support draft, pending, and private restriction rules.
- **Added**: Implemented `DemoDataSeeder` with 4 realistic production demo rules (VIP Club, Digital Product Access, Registered Members Only, Seasonal Early Access) with auto-population and manual one-click reload.
- **Added**: Integrated native GitHub Release Updater for zero-friction one-click updates directly from the WordPress dashboard.

### [1.0.1] - 2026-10-12
- **Fixed**: Admin sidebar menu logo icon restrained to standard WordPress 20x20px dimension across all admin screens.
- **Fixed**: Sidebar menu icon color standardized to match native WordPress admin gray (`#a7aaad`) with active/hover highlights (`#ffffff`).
- **Fixed**: Resolved fatal error on Overview and Integrations tabs by replacing invalid `is_hpos_enabled()` with canonical `custom_orders_table_usage_is_enabled()`.

### [1.0.0] - 2026-10-12
- Initial production release.
- Central restriction rule management with multi-condition AND/OR groups.
- WooCommerce product, category, and order status evaluation.
- Full HPOS and Cart/Checkout Blocks support.
- Gutenberg container block and `[wpcalibrate_restrict]` shortcode.
- Complete content leakage prevention across excerpts, RSS feeds, search queries, and WP REST API.
- Customer Access Inspector real-time simulation tool.

---

## Support & Contact

- **Author**: [WPCalibrate](https://wpcalibrate.com)
- **Website**: [https://wpcalibrate.com](https://wpcalibrate.com)
- **Marketplace**: [https://marketplace.wpcalibrate.com](https://marketplace.wpcalibrate.com)
- **Email Support**: [support@wpcalibrate.com](mailto:support@wpcalibrate.com)
- **Phone / WhatsApp**: [+44 7474 795976](https://wa.me/447474795976)

---

## License

This software is released under the **GNU General Public License v2.0 or later** (GPL-2.0-or-later). See the [LICENSE](LICENSE) file for complete details.
