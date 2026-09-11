# VisitorPing for WordPress

See visits arrive in a live website dashboard, with optional iPhone notifications. This plugin connects your WordPress website to [VisitorPing](https://visitorping.com/?utm_source=github&utm_medium=plugin_repo&utm_campaign=first_installation).

**[Start a 14-day trial](https://visitorping.com/signup?utm_source=github&utm_medium=plugin_repo&utm_campaign=first_installation&utm_content=trial)** · No credit card required · Hosted service starts at $19/month after the trial.

The connector is free to download. A VisitorPing account and active service access are required for tracking and alerts. The iPhone app is an optional companion; you can use the dashboard in your browser.

## Install on your website

1. **[Download visitorping-wordpress.zip](https://github.com/sariserhan/visitorping-wordpress/releases/latest/download/visitorping-wordpress.zip)** from the release assets.
2. In WordPress, open **Plugins → Add New Plugin → Upload Plugin**. Choose the ZIP, install, and activate it.
3. Add your website in VisitorPing and copy its **Site Key**.
4. In WordPress, open **Settings → VisitorPing**, paste your Site Key, and save.
5. Open your website in a private window, complete any required consent, and check for the visit in your VisitorPing dashboard.

Use the release asset above for WordPress upload; GitHub’s automatic source archives have a different folder layout.

[Full WordPress setup guide](https://visitorping.com/guides/how-to-track-visitors-on-wordpress?utm_source=github&utm_medium=plugin_repo&utm_campaign=first_installation&utm_content=guide) · [Try the sample demo without signing up](https://visitorping.com/demo?utm_source=github&utm_medium=plugin_repo&utm_campaign=first_installation&utm_content=demo)

## What the plugin does

- Adds the VisitorPing tracking script to public pages after you configure a valid Site Key.
- Provides a connection check in WordPress settings.
- Skips logged-in administrators and editors by default. Logged-out visits and cached pages may still be tracked.
- Supports optional content delivery, enabled separately in settings.

A detected script or saved key alone does not prove that a visit or notification was delivered. Verify a test visit in the dashboard. VisitorPing does not identify anonymous visitors by name.

## Requirements and data handling

WordPress 5.6+ and PHP 7.4+. Test with your theme, caching plugins, and consent setup before production use.

The plugin loads a script from `cdn.visitorping.com` and sends website activity to the VisitorPing hosted service. See [external services and data](readme.txt), [privacy policy](https://visitorping.com/privacy), and [terms](https://visitorping.com/terms). The `visitorping_should_track` filter lets developers suppress tracking where needed.

## Support and removal

[Contact VisitorPing](https://visitorping.com/contact?utm_source=github&utm_medium=plugin_repo&utm_campaign=first_installation&utm_content=support) for installation help. Never post Site Keys, publishing secrets, account credentials, or visitor data in public issues.

Deactivate the plugin and clear page/CDN caches to stop script injection. See [removal instructions](UNINSTALL.md) for what deletion does and does not remove.

## License and releases

Plugin source: MIT, as declared in [visitorping.php](visitorping.php). Hosted service access is separate.

This repository distributes the WordPress connector only. A GitHub release is not a WordPress.org directory listing or approval.
