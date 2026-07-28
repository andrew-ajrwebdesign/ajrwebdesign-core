# AJR Web Design Core

Core functionality plugin for [ajrwebdesign.com](https://ajrwebdesign.com) — the companion plugin to the `ajrwebdesign-theme` FSE theme. Part of the signature stack: clean FSE theme + PSR-4 core plugin + fully customisable custom blocks.

**Status: actively maintained** (powers the live site).

## What's inside

### Blocks (all dynamic, `block.json` + `render.php` + editor controls)

| Block | Purpose |
|---|---|
| `responsive-image` | Art-directed image: separate desktop/mobile assets, lazy-loading + fetch-priority controls |
| `case-study-card` | Full case-study showcase: score circles, Core Web Vitals metrics, impact tiles |
| `case-study-mini-card` | Compact result card with count-up animation |
| `breadcrumbs` | Language-aware Home › Blog › Category trail for posts |
| `post-intro` | Styled lead paragraph sourced from post meta |
| `post-callout` | Highlight box sourced from post meta |
| `language-aware-nav` | Renders the navigation matching the visitor's Polylang language, resolved by slug convention (`{menuSlug}-{lang}`) — one header/footer part serves every language |

### Modules

- **CaseStudies** — `ajr_case_study` CPT + tag taxonomy, structured REST-exposed meta (metrics, impact), and a legacy-meta migration (`wp ajr-core migrate-case-meta` or one-click from the settings screen)
- **Analytics** — consent-gated GA4 (Google Consent Mode v2 defaults to denied; Complianz drives consent updates)
- **I18n** — Polylang string registration via the `ajrwebdesign-core-i18n` theme-support contract
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
