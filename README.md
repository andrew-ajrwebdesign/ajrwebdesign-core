# AJR Web Design Core

The site plugin for [ajrwebdesign.com](https://ajrwebdesign.com) — what is unique to this site, running beside the shared **AJR Core** plugin and the `ajrwebdesign-theme` FSE theme. Part of the signature stack: clean FSE theme + AJR Core + a thin site plugin.

**Status: actively maintained** (powers the live site).

### Moved to AJR Core in 1.7.0

GA4 with consent gating and lead tracking, FAQ structured data, the business profile (now one
`#business` entity inside The SEO Framework's graph), Disable Comments, the tooling (REST)
application password, and the post breadcrumbs (now WordPress's own `core/breadcrumbs` block,
styled by the theme). Configure those in **AJR Core** in wp-admin, not here.

### Moved to AJR Core in 1.8.0

Testimonials: the post type (same `ajr_testimonial` key), its rating and source-logo fields
(now `ajr_testimonial_rating` / `ajr_testimonial_logo_id`) and the slider block (now
`ajr/testimonials`). This plugin keeps only the German quote and role, swapped in on German
pages through AJR Core's `ajr_core_testimonial_text` filter.

## What's inside

### Blocks (all dynamic, `block.json` + `render.php` + editor controls)

| Block | Purpose |
|---|---|
| `responsive-image` | Art-directed image: separate desktop/mobile assets, lazy-loading + fetch-priority controls |
| `case-study-card` | Full case-study showcase: score circles, Core Web Vitals metrics, impact tiles |
| `case-study-mini-card` | Compact result card with count-up animation |
| `post-intro` | Styled lead paragraph sourced from post meta |
| `post-callout` | Highlight box sourced from post meta |
| `language-aware-nav` | Renders the navigation matching the visitor's Polylang language, resolved by slug convention (`{menuSlug}-{lang}`) — one header/footer part serves every language |
| `language-switcher` | Links to the current page's translations |

### Modules

- **CaseStudies** — `ajr_case_study` CPT + tag taxonomy, structured REST-exposed meta (metrics, impact), and a legacy-meta migration (`wp ajr-core migrate-case-meta` or one-click from the settings screen)
- **Testimonials (German)** — the German quote and role fields and their editor panel; the testimonials themselves are AJR Core's
- **I18n** — Polylang string registration via the `ajrwebdesign-core-i18n` theme-support contract, and hreflang handling
- **Compat** — `add_theme_support` contracts so the plugin degrades gracefully on any theme

## Development

```bash
composer install   # PHP dependencies + autoloader
npm install        # block build tooling
npm run build      # compile blocks/ -> build/
composer lint      # PHPCS (WordPress Coding Standards)
composer test      # PHPUnit
```

Requires WordPress 6.9+ and PHP 8.0+.

## Installation (target sites)

Download the install-ready zip from the [Releases page](../../releases) — `vendor/` and compiled blocks are bundled; no build step needed.

## Architecture

PSR-4 under the `AJR\SiteCore\` namespace (`src/`), singleton `Core\Plugin` bootstrap, hooks registered in `register()` methods (never constructors), one text domain (`ajrwebdesign-core`). See `.github/workflows/` for CI and the release pipeline.
