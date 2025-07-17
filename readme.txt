=== Broken Shortcode Finder ===
Contributors: CognitoWP
Tags: shortcode, broken shortcode, finder, repair, cleanup
Requires at least: 5.0
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.0.9
License: GPL-2.0+
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Scans your WordPress site for orphaned shortcodes and helps you repair or remove them.

== Description ==

Broken Shortcode Finder scans your WordPress content (posts, pages, and custom post types) to identify orphaned shortcodes - shortcodes that exist in your content but don't have corresponding registered handlers.

Key Features:
- Comprehensive scanning of all content types
- Detects shortcodes in regular content and Gutenberg blocks
- Shows where each shortcode is being used

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/cognitowp-broken-shortcode-finder` directory, or install the plugin through the WordPress plugins screen directly.
2. Activate the plugin through the 'Plugins' screen in WordPress
3. Navigate to Tools > Broken Shortcode Finder to start scanning

== Frequently Asked Questions ==

= What types of content does it scan? =
By default it scans posts and pages, but you can extend this to other post types using the 'bsfr_content_types' filter.

= Does it work with Gutenberg blocks? =
Yes, it specifically scans common block types where shortcodes might appear (code blocks, HTML blocks, etc.)

== Screenshots ==
1. The main scanning interface
2. Results showing found orphaned shortcodes
3. The replacement options dialog

== Changelog ==

= 1.0 =
* Initial release

== Upgrade Notice ==

= 1.0 =
Initial release of Broken Shortcode Finder