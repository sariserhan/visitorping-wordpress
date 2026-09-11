=== VisitorPing — Real-Time Website Visitor Alerts ===
Contributors: visitorping
Tags: analytics, visitor notifications, realtime visitors, visitor radar, live analytics
Requires at least: 5.6
Tested up to: 6.7
Requires PHP: 7.4
Stable tag: 1.3.1
License: MIT
License URI: https://opensource.org/licenses/MIT

Connect WordPress to VisitorPing for live website visits and iPhone arrival alerts. Requires a VisitorPing account and hosted service subscription.

== Description ==

**VisitorPing** connects your WordPress website to a live visitor dashboard and iPhone arrival alerts. No chat widget is required.

The connector is free to download. Tracking, storage, and notifications are provided by the separate VisitorPing hosted service. An account is required: Starter offers a 14-day trial with no credit card, then US$19/month if you choose to continue. See [plans](https://visitorping.com/#pricing), [terms](https://visitorping.com/terms), and [privacy](https://visitorping.com/privacy).

The core experience is simple:
**Someone visits your website → VisitorPing detects the visit → you receive an instant push notification on your phone and see the visitor live on your radar.**

VisitorPing is designed for businesses where an individual website visit can become a valuable customer action. It helps teams across services, retail, hospitality, professional work, and online businesses understand live interest without living inside a traditional analytics dashboard.

= Key Features =
* **Timely Mobile Alerts**: Get notified on iPhone when a visitor or configured high-intent action is received.
* **Non-Sensitive Intelligence**: See approximate location (City, State, Country), referrer source (Google, LinkedIn, Facebook, Direct), entry page, and device type.
* **Performance-Conscious Installation**: The tracking script loads asynchronously so it does not block the initial page render.
* **Exclude Administrators**: By default, skips tracking for logged-in users who can edit posts. Test with a private window and your caching configuration.
* **First-Party Privacy**: Pseudonymous visitor identification uses first-party browser storage; VisitorPing does not attempt to reveal the person behind an anonymous visit.
* **Content Delivery**: Securely send reviewed VisitorPing articles into WordPress as drafts. Scheduled daily publishing is a separate opt-in setting that is off by default. The integration can never update or delete existing posts.

== Installation ==

1. Download the plugin ZIP from https://visitorping.com/downloads/visitorping-wordpress.zip. In WordPress, open Plugins > Add New > Upload Plugin and select the ZIP.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Go to **Settings → VisitorPing** in your WordPress admin.
4. Paste your **Site Key** (found in your [VisitorPing Dashboard](https://visitorping.com)).
5. Click **Save Settings**.
6. Open your website in a private window and confirm a visit in the VisitorPing dashboard. For phone alerts, install the iPhone app from https://visitorping.com/download and enable notifications.

== Frequently Asked Questions ==

= Where do I find my Site Key? =
Log into your [VisitorPing Dashboard](https://visitorping.com/dashboard/sites), click on your website, and copy the Site Key (for example `vp_ABC23456`).

= Will this slow down my website? =
The VisitorPing script is loaded asynchronously and sends compact activity events. Website owners should still test it with their own theme, plugins, consent configuration, and performance tooling before production rollout.

= Will I get notified when I visit my own website? =
By default, it skips logged-in users who can edit posts. Logged-out visits, private browsing, or cached pages may still be tracked. A saved key or reachable script alone does not prove a visit or notification was delivered.

== External services and data ==

After you enter a valid Site Key, public pages load the VisitorPing service script from https://cdn.visitorping.com/site/{SITE_KEY}.js. The script sends activity to https://ingest.visitorping.com/e for hosted processing, storage, dashboard display, and configured alerts. Data may include page URLs, referrers, timestamps, browser and device information, and a pseudonymous browser identifier. Requests include an IP address, which can be used to derive approximate location. VisitorPing does not identify anonymous people by name.

The administrator's connection test requests the configured script URL. A custom CDN/proxy URL, if configured, is used instead of the default. Optional content delivery accepts signed requests to create posts only after the administrator enables it; publishing is a separate opt-in.

Configure any consent controls your website needs before enabling tracking. Developers can use the `visitorping_should_track` filter to suppress the script. Service terms: https://visitorping.com/terms. Privacy policy: https://visitorping.com/privacy.

== Removal ==

Deactivate the plugin to stop it injecting the tracker, then clear page/CDN caches. Deleting it removes its local settings. Previously created posts and data stored in the VisitorPing service are not deleted by uninstalling the plugin; manage them separately in WordPress and VisitorPing.

== Changelog ==

= 1.3.1 =
* Clarify hosted-service pricing and data handling.
* Add direct ZIP installation instructions and accurate setup status.
* Link to verified mobile download availability.

= 1.3.0 =
* Add an opt-in setting that lets VisitorPing publish one scheduled daily article.
* Report a publish_post capability only while that setting is enabled.
* Keep every other delivery at draft status.

= 1.2.0 =
* Add signed, replay-limited endpoints for VisitorPing draft delivery.
* Add an administrator-controlled publishing secret with rotation.
* Enforce idempotent delivery and default every post to draft status.

= 1.1.0 =
* Use the subscription-aware keyed site bootstrap.
* Validate the current Site Key format before loading tracking.
* Verify the configured site-specific script from WordPress Admin.

= 1.0.0 =
* Initial release.
* Automatic script injection into `wp_head`.
* Administrator and editor exclusion settings.
* Connection test and verification assistant.
