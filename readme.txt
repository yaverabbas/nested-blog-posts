=== Nested Blog Posts ===
Contributors: wwhry
Donate link: https://github.com/yaverabbas
Tags: nested posts, hierarchical posts, parent child posts, hierarchical permalinks, nested permalinks, post hierarchy, seo-friendly urls
Requires at least: 6.3
Tested up to: 6.9.1
Stable tag: 1.0.0
Requires PHP: 7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Enable parent/child hierarchy for standard Posts and generate nested permalinks like /parent/child/ (unlimited depth).

== Description ==

Nested Blog Posts makes the built-in **Posts** post type behave more like **Pages**:

* Adds a **Parent** dropdown to Posts.
* Generates hierarchical permalinks like `/parent/child/` (supports unlimited depth).
* Routes nested URLs correctly so you don’t get 404s or forced redirects to `/child/`.

This plugin does not contact external servers and does not collect user data.

== Installation ==

1. Upload the plugin files to the `/wp-content/plugins/nested-blog-posts/` directory, or install the plugin through the WordPress plugins screen.
2. Activate **Nested Blog Posts** through the 'Plugins' screen in WordPress.
3. Go to **Settings → Nested Blog Posts** and ensure it’s **Enabled**.

== How to use ==

1. **Enable the plugin** (Settings → Nested Blog Posts → Enabled).
2. **Create a Parent blog post** (a normal Post).
3. **Create a Child blog post** and set its **Parent** in the editor sidebar (Post → Parent).
4. To create a deeper tree, set the new post’s **Parent** to the previous child.
   Example: `/parent/child/grandchild/`
5. If you disable the feature later, the plugin automatically refreshes rewrite rules and WordPress will fall back to normal post behavior.

== Frequently Asked Questions ==

= Will my old /child/ URLs still work? =
Yes. WordPress will typically redirect old single-level URLs to the new hierarchical permalink.

= Can this conflict with Pages or taxonomies? =
It can if you use the exact same path for a Page or taxonomy route. The plugin will not override a published Page at the same full path, but you should avoid slug collisions.

= How do I debug? =
As an admin, add `?nbp_debug=1` to a nested URL and check the response headers in DevTools → Network.

== Changelog ==

= 1.0.0 =
* Initial release.

== Upgrade Notice ==

= 1.0.0 =
First public release.
