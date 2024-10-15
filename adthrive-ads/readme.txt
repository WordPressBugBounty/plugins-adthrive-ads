=== Raptive Ads ===
Contributors: raptive
Tags: ads raptive
Requires at least: 4.6.0
Tested up to: 6.6.2
Requires PHP: 5.6
Stable tag: trunk
License: GPLv2 or later
License URI: http://www.gnu.org/licenses/gpl-2.0.html

Simplifies adding Raptive Ads to your site!

== Description ==

The Raptive Ads WordPress Plugin is the easiest and most popular way to install Raptive’s innovative ad code and get ads running on your site. The plugin adds a JavaScript tag to your site and lets you set up a few preferences within WordPress.

* Disable ads by category, tag, or individual page
* Disable video metadata
* Enable CLS optimization
* Enable AMP ads
* Enable Web Stories ads
* Enable Ad Block Recovery
* Override ads.txt
* Create a video sitemap redirect

Raptive is a strategic partner equipping independent creators and enterprise publishers with unmatched ad management, world-class tech, and inspired opportunities to make you more money.


== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/adthrive-ads` directory, or install the plugin through the WordPress plugins screen directly.
1. Activate the plugin through the 'Plugins' screen in WordPress
1. Use the Raptive->Ads screen to set your Site ID

== Changelog ==
= 3.6.3 =
* Removed custom update checker

= 3.6.2 =
* Improved support for hashed email detection

= 3.6.1 =
* Bug fixes

= 3.6.0 =
* Support additional keys for hashed email detection
* Adds options to add AI crawlers as disallowed user agents in robots.txt

= 3.5.7 =
* Support hashed email detection in the URL

= 3.5.6 =
* Improve email detection in the URL

= 3.5.5 =
* Tested up to latest wp version

= 3.5.3 =
* Add option for disabling ads within WordPress admin

= 3.5.2 =
* Update tested up to version

= 3.5.1 =
* Improve data tracking

= 3.5.0 =
* Revert permission headers
* Fix message typo

= 3.4.1 =
* Fixes bug setting headers

= 3.4.0 =
* Add NoAI meta tag checkbox
* Enable CLS Optimization by default on new installations
* Fix minor bugs

= 3.3.1 =
* Updated branding to use Raptive assets

= 3.2.1 =
* Fixes issue with Web Story ads

= 3.1.0 =
* Adds option for inserting category to body class on posts
* Adds additional logging for PHP and WordPress versioning
* Fixes bug with multiple header CLS files insertion
* Fixes issue with incorrect property sent from an endpoint

= 3.0.1 =
* Update WordPress version compatibilty

= 3.0.0 =
* Update asset storage to utilize wp_options
* Updates to encode captured emails
* Improve stability during plugin upgrade process

= 2.4.0 =
* Addressed warning messages that occurred during debug mode
* Improved upgrade stability
* Improved error handling

= 2.3.1 =
* Minor bug fixes

= 2.2.4 =
* Bug fixes for update hook

= 2.2.3 =
* Bug fixes for ad recovery

= 2.2.2 =
* Bug fixes for update hook and header error in email detection class

= 2.2.1 =
* Minor bug fix for PHP v7.2

= 2.2.0 =
* Minor bug fixes

= 2.1.1 =
* Significant infrastructure changes and backend updates to allow for continued improvements to our plugin

= 1.1.5 =
* Cron job bug fix

= 1.1.4 =
* Added MCM support for Web Stories
* Minor fixes

= 1.1.3 =
* Minor bug fixes and optimizations

= 1.1.2 =
* Bug fixes from v1.1.1

= 1.1.1 =
* Added support for ad block recovery
* Improvements to ad & page speed

= 1.1.0 =
* Additional CLS optimization

= 1.0.50 =
* Bug fixes

= 1.0.49 =
* Improved functionality for GDPR compliance

= 1.0.48 =
* Minor bug fixes and optimizations

= 1.0.47 =
* Behind-the-scenes enhancements for CLS Optimization setting

= 1.0.46 =
* Added support for Web Stories ads
* Bug fixes for CLS Optimization

= 1.0.45 =
* Removed Content Security Policy option

= 1.0.44 =
* Added option to enable solution for ad-related CLS

= 1.0.43 =
* Update to help with ads.txt installation on new sites

= 1.0.41 =
* Removed adblock recovery

= 1.0.40 =
* Updated minimum supported PHP version
* Confirm the adblock recovery script is available before loading

= 1.0.39 =
* Enable redirect of video-sitemap url to adthrive-hosted video sitemmap
* Updated adblock recovery.

= 1.0.38 =
* Remove the client side experiment threshold from the script tag.

= 1.0.37 =
* AdBlock recovery option added to plugin. This option allows ads to be shown to users with ad blockers enabled

= 1.0.36 =
* Added post and site option to disable adding video metadata
* Updated video files to handle override-embed and player type in the shortcode
* Prevent ads from loading when a post is being edited in Thrive Architect

= 1.0.35 =
* Added a post option to re-enable ads on the specified date

= 1.0.34 =
* Always Use HTTPS Resources

= 1.0.33 =
* Update WordPress tested up to 5.2.2
* Always use HTTPS for the script tag

= 1.0.32 =
* Added an option to disable auto-insert video players on individual posts or pages

= 1.0.31 =
* Added an option to override ads.txt by copying it to the site root
* Redirect to the hosted ads.txt file by default

= 1.0.30 =
* Updated AMP ad refresh targeting

= 1.0.29 =
* V2.7 of ads.txt
* Added warning when deactivating AdThrive Ads Plugin
* Fixed the sending of PII on AMP pages

= 1.0.28 =
* V2.5 of ads.txt
* Fixed AMP support for PHP < 5.4

= 1.0.27 =
* V2.3 of ads.txt

= 1.0.26 =
* Added AMP support

= 1.0.25 =
* Add support for viewing the GDPR consent by adding ?threshold=gdpr to the site url

= 1.0.24 =
* Load the ad code at the top of the head tag

= 1.0.23 =
* V2.2 of ads.txt

= 1.0.22 =
* V2 of ads.txt

= 1.0.21 =
* Added a new adthrive-in-post-video-player shortcode

= 1.0.20 =
* Update to CMB2 v2.3.0 to improve compatibilty with PHP 7.2

= 1.0.19 =
* Adjusted the ad code script block
* Removed Iframe busters with XSS vulnerabilities

= 1.0.18 =
* Added Iframe busters

= 1.0.17 =
* Block ads on 404 pages

= 1.0.16 =
* Updated ads.txt

= 1.0.15 =
* Updated ads.txt
* Added a new Content Security Policy option that will upgrade insecure requests and block all mixed content

= 1.0.13 =
* Delay setup until after plugins loaded

= 1.0.12 =
* Added support for ads.txt

= 1.0.11 =
* Removed support for Cloudflare Rocket Loader

= 1.0.10 =
* Added support for Cloudflare Rocket Loader

= 1.0.9 =
* Added plugin version output

= 1.0.8 =
* Changed the HTTPS endpoint

= 1.0.7 =
* Added HTTPS support

= 1.0.6 =
* Improved compatibilty with PHP 7 and WordPress 4.7

= 1.0.4 =
* Improved multisite support

= 1.0.3 =
* Improved settings initialization and style
* Improved the tag and category input performance for large datasets

= 1.0.2 =
* Added a PHP 5.3+ version check

= 1.0.1 =
* Updated to support PHP 5.3

= 1.0.0 =
* Initial public release
