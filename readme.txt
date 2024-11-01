=== WooCommerce Rejoiner ===
Contributors: madjax, saschabratton
Tags: woocommerce, rejoiner, abandoned cart, email marketing, remarketing, ecommerce, cart abandonment email
Requires at least: 4.6
Tested up to: 6.4.3
Stable tag: 2.4.1
License: GPLv3
License URI: http://www.gnu.org/licenses/gpl-3.0.html

== Description ==

# Create a seamless customer journey across email, SMS & direct mail.

Rejoiner makes it easy to manage all of your retention marketing channels from a single platform. Organize your customer data in one place. Run all of your retention channels with one platform. Centralize all of your creative production with one team.

* Orchestrate email, SMS & postcards with Rejoiner's journey builder.
* Leverage the entire spectrum of customer data to segment the right audiences.
* Build beautiful email + SMS creative with intuitive drag and drop editors.
* Measure every dollar of revenue with custom attribution modeling.
* Trigger marketing at the opportune moments throughout the customer lifecycle.

[Click Here To See Pricing](https://www.rejoiner.com/pricing?utm_source=wordpress-plugin-directory&utm_medium=app-store&utm_campaign=woocommerce-listing)


== Installation ==

1. [Look at the pricing plans](https://www.rejoiner.com/pricing?utm_campaign=woocommerce-listing&utm_medium=app-store&utm_source=wordpress-plugin-directory) [take an interactive tour](https://www.rejoiner.com/interactive-tour?utm_campaign=woocommerce-listing&utm_medium=app-store&utm_source=wordpress-plugin-directory).

2. Upload & activate the plug-in according to [these instructions](https://docs.rejoiner.com/docs/woocommerce?utm_campaign=woocommerce-listing&utm_medium=app-store&utm_source=wordpress-plugin-directory).

== Frequently Asked Questions ==

= Installation Instructions =
1. [Look at the pricing plans](https://www.rejoiner.com/pricing?utm_campaign=woocommerce-listing&utm_medium=app-store&utm_source=wordpress-plugin-directory) [take an interactive tour](https://www.rejoiner.com/interactive-tour?utm_campaign=woocommerce-listing&utm_medium=app-store&utm_source=wordpress-plugin-directory).

2. Upload & activate the plug-in according to [these instructions](https://docs.rejoiner.com/docs/woocommerce?utm_campaign=woocommerce-listing&utm_medium=app-store&utm_source=wordpress-plugin-directory).

= How do I get started? =
Head over to [Rejoiner.com](https://rejoiner.com/?utm_campaign=woocommerce-listing&utm_medium=app-store&utm_source=wordpress-plugin-directory), look at the [pricing plans](http://rejoiner.com/pricing?utm_campaign=woocommerce-listing&utm_medium=app-store&utm_source=wordpress-plugin-directory) and [take an interactive tour](https://www.rejoiner.com/interactive-tour?utm_campaign=woocommerce-listing&utm_medium=app-store&utm_source=wordpress-plugin-directory).


== Screenshots ==
1. Increase Revenue with Customer Journeys
2. Deliver the Right Message
3. Complex Marketing Automation
4. Customer Segments
5. Stay Out of the SPAM Folder
6. Build Revenue Reports
7. Drag & Drop Email Builder


== Changelog ==
= 2.4.0 =
Adds address/phone collection

= 2.3.1 =
Fixes bug in add to list on conversion

= 2.3 =
Includes the WC_Product object in the wc_rejoiner_cart_item_name filter parameters

= 2.2 =
* Remove support for RJ1
* Bug fixes for opt-in marketing
* Add wc_rejoiner_optin_list_id filter

= 2.1 =
* Minor bug fixes

= 2.0 =
* Support for Rejoiner 2.0
* Add session meta data filter.

= 1.6 =
* Add promo code features.

= 1.5.1 =
* Accept marketing feature

= 1.5 =
* Add option to send converted customers to a Rejoiner list via REST API.

= 1.4.6 =
* Bugfix for AJAX cart sync

= 1.4.5 =
* Use native WC session for unique ID. Props @adamchal

= 1.4.4 =
* Fix escaping attribute_value for JSON

= 1.4.3 =
* Add support for variant images

= 1.4.2 =
* Add filters for passing attribute data to setCartItem

= 1.4.1 =
* Prevent encoding of double quotes in product title for trackProductView

= 1.4 =
* Integrate new Rejoiner API

= 1.3.5 =
* Add product url and category to setCartItem

= 1.3.4 =
* Preserve custom GA utm parameters

= 1.3.3 =
* Add screenshots
* Update readme.txt

= 1.3.2 =
* Move REST API call to woocommerce_payment_complete action

= 1.3.1 =
* Bugfix: prevent empty email parameter for non-logged in users

= 1.3 =
* Integrate Rejoiner REST API for conversion tracking redundancy - visit Settings > Integration and add your API key and secret to take advantage of this new feature.

= 1.2.6 =
* Move refill cart function hook to wp_loaded

= 1.2.5 =
* Add new filters: wc_rejoiner_cart_item_name, wc_rejoiner_cart_item_variant, wc_rejoiner_thumb_size - see included sample-functions.php file
* When user is logged in, set 'email' parameter as part of the setCartData call on cart and checkout, with the customer's email address

= 1.2.4 =
* Undeclared variable bug fix

= 1.2.3 =
* Product name escaping bug fix

= 1.2.2 =
* Remove description from Rejoiner JS
* Better number formatting
* Prevent display of tracking code on thank you page

= 1.2.1 =
* Display tracking only on cart and checkout

= 1.2 =
* Validate image URLs
* Use excerpt for description
* Better description sanitization

= 1.1 =
* Initial public release