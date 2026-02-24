=== Steadfast Fraud Check for WooCommerce ===
Contributors: tsdev
Tags: woocommerce, fraud, steadfast, courier, bangladesh, cod, cash on delivery
Requires at least: 5.8
Tested up to: 6.7
Stable tag: 1.0.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Protect your WooCommerce store from COD fraud using Steadfast Courier's fraud check API.

== Description ==

**Steadfast Fraud Check** is a powerful WooCommerce plugin that automatically detects potentially fraudulent orders by checking customer phone numbers against the Steadfast Courier fraud database.

### Key Features

* ✅ **Automatic Fraud Check** — Checks every new order's billing phone number via the Steadfast API
* 🚫 **Block High-Risk Orders** — Optionally prevent checkout completion for high-risk customers
* ⚠️ **Risk Badges** — Colour-coded risk badges (Safe / Medium / High) in the WooCommerce orders list
* 📊 **Order Meta Box** — Full delivery history stats visible on each single order page
* 🔍 **Manual Check** — Check any phone number manually from the admin panel
* 💾 **Smart Caching** — Results cached in a custom database table to minimize API calls
* 📈 **Dashboard** — Overview stats showing total checks, risk distribution, and recent high-risk orders
* 🔐 **Secure** — All AJAX actions protected with nonces and capability checks

### How It Works

1. Customer places a WooCommerce order with their phone number
2. Plugin calls `GET https://portal.steadfast.com.bd/api/v1/fraud_check/{phone}` using your API credentials
3. The API returns the customer's courier delivery history (total orders, delivered, returned, cancelled)
4. Plugin calculates a **risk score (0–100)** based on the return + cancellation rate
5. Order is tagged with **Safe**, **Medium**, or **High** risk level
6. High-risk orders can optionally be blocked at checkout

### Risk Score Calculation

| Return+Cancel Rate | Risk Level |
|--------------------|------------|
| < 20%              | ✅ Safe     |
| 20% – 39%          | ⚠️ Medium   |
| ≥ 40%              | 🚫 High     |

*(Thresholds are configurable in Settings)*

== Installation ==

1. Upload the `fraud-check` folder to `/wp-content/plugins/`
2. Activate via **Plugins → Installed Plugins**
3. Go to **Fraud Check → Settings**
4. Enter your **Steadfast API Key** and **Secret Key** (from [portal.steadfast.com.bd](https://portal.steadfast.com.bd))
5. Click **Test Connection** to verify
6. Configure thresholds and checkout behaviour as needed

== Frequently Asked Questions ==

= Where do I get my API Key and Secret Key? =
Log in to your Steadfast Merchant account at [portal.steadfast.com.bd](https://portal.steadfast.com.bd), then navigate to API Settings.

= Does this plugin block customers permanently? =
No. The block only applies at the moment of checkout. If you disable "Block High Risk Orders", high-risk customers can still place orders — they'll just be flagged.

= How often is the cache refreshed? =
Every check result is cached for the duration set in Settings (default 6 hours). You can force a fresh check from the order meta box or manual check page.

= Does this work with WooCommerce HPOS? =
Yes! The plugin supports both the legacy `shop_order` post type and the new High-Performance Order Storage (HPOS).

= What if the phone number has fewer or more than 11 digits? =
The plugin will skip the API call and mark the check as "unknown" for invalid numbers.

== Screenshots ==

1. Dashboard overview with stats and high-risk order list
2. Settings page with API credentials and risk thresholds
3. Manual phone number check with real-time result
4. Risk badge column in WooCommerce orders list
5. Single order meta box with full delivery statistics

== Changelog ==

= 1.0.0 =
* Initial release
* Steadfast API fraud check integration
* Auto-check on new WooCommerce orders
* Block high-risk customers at checkout
* Risk badge column in orders list
* Single order meta box with stats
* Manual check admin page
* Smart caching with custom DB table
* Dashboard with risk distribution stats

== Upgrade Notice ==

= 1.0.0 =
Initial release.
