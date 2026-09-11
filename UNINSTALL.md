# Uninstall VisitorPing from a website

Removing a website in the VisitorPing dashboard stops VisitorPing from using that website record. It cannot edit the website or hosting account. Remove the integration at its installation point as described below.

Before removing anything, copy the Site Key from the script or integration settings. Remove only the VisitorPing entry containing `https://cdn.visitorping.com/site/` or the named VisitorPing package. Do not delete a shared header, tag container, theme, Worker, or unrelated custom code.

## wordpress

In **Plugins → Installed Plugins**, deactivate **VisitorPing**, then select **Delete**. WordPress runs the included `uninstall.php` and removes only the `visitorping_site_key` option. Clear page/CDN caches and verify the keyed script is absent from the published page source.

## shopify

In **Online Store → Themes → Customize → App embeds**, turn **VisitorPing Tracker** off and save. Then uninstall the VisitorPing app from **Settings → Apps and sales channels** if it is no longer needed. Shopify removes the theme app extension without editing `theme.liquid`. Verify on the published store.

## wix

In **Settings → Custom Code**, find **VisitorPing Doorbell Tracker**, disable or delete it, then publish. If the Velo option was installed, remove only the VisitorPing import and route-change block from `masterPage.js`. Do not delete the whole master page file.

## webflow

In **Site settings → Custom code**, remove only the VisitorPing script from **Head code**, save, and publish to every domain. If it was installed on individual pages, repeat under each page's custom-code settings.

## squarespace

In **Settings → Advanced/Developer Tools → Code Injection**, remove only the VisitorPing script from **Header** and save. Preserve every other header entry.

## framer

In **Project Settings → Custom Code**, delete the VisitorPing script entry, publish the site, and verify the published page rather than Preview.

## godaddy

Open every page where VisitorPing was added, remove the corresponding HTML section, and publish. GoDaddy's Website Builder integration is per-page, so removing one section does not remove it from other pages.

## yolabi

In the store's tracking settings, clear the VisitorPing tag from **custom head scripts** and republish the store. Leave any Google Analytics, Google Tag Manager, or Facebook Pixel values in place.

## ghost

In **Settings → Code Injection**, remove only the VisitorPing tag from **Site Header** and save. For a theme installation, remove only that tag from `default.hbs`, rebuild/upload the theme, and activate it.

## gtm

In Google Tag Manager, pause or delete the **VisitorPing** tag, then **Submit** and publish the container. Optionally delete the VisitorPing custom template after no tags use it. Never delete the container just to remove VisitorPing.

## react

Remove every `<VisitorPing />` usage and its import, then run `pnpm remove @visitorping/react` (or your package manager's equivalent). Rebuild and deploy the application.

## nuxt

Remove `@visitorping/nuxt` from `modules` and remove the `visitorping` configuration block. Run `pnpm remove @visitorping/nuxt`, rebuild, and deploy.

## astro

Remove `visitorping(...)` from `integrations` and its import. Run `pnpm remove @visitorping/astro`, rebuild, and deploy.

## cloudflare

First remove the route that maps traffic to the VisitorPing Worker. Confirm traffic reaches the origin normally. If the Worker is dedicated to VisitorPing, delete that Worker afterward. Do not delete a shared Worker; remove only the VisitorPing `HTMLRewriter` integration and binding, then redeploy.

## universal-cli

For HTML and supported Next.js installations made by the VisitorPing CLI, preview exact-key removal first:

```bash
npx visitorping install vp_YOUR_KEY --remove
npx visitorping install vp_YOUR_KEY --remove --write
```

`--delete` is an alias for `--remove`. The CLI removes only the matching Site Key. It does not sign into hosted builders or publish their sites.

## Verify removal

Open the published site in a private window, inspect page source or Network requests, and confirm there is no request for `cdn.visitorping.com/site/YOUR_SITE_KEY.js`. Cached HTML may require a platform or CDN cache purge.
