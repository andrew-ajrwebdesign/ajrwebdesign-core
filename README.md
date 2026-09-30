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

### Moved to AJR Core in 1.9.0

Case studies: the post type and tag taxonomy (same `ajr_case_study` / `case_study_tag` keys, same
`/case-studies/` URLs) are registered by AJR Core's Case studies module. This plugin keeps this
site's own layer on them — the Core Web Vitals metrics and impact fields, their metabox, the two
case-study card blocks, the story intro and the case-study SEO tweaks. The settings page (its only
setting enabled case studies) and the finished legacy-meta migration are gone; the 88 legacy
`_ajr_case_study_*` rows it read from are still in the database, untouched. So is the settings row it
stored (`ajrwd_core_settings`, which also still holds the pre-1.7.0 GA4 settings); nothing reads
it any more, and deleting it is a deliberate manual step.

**Deploy order (1.9.0 needs it):** AJR Core 0.12.0 first, then switch its **Case studies** and
**Testimonials** modules on (AJR Core → Modules), then this plugin. With either module off the
posts vanish from the admin and their pages 404; wp-admin shows an error notice naming the module
until it is on.

### New in 1.10.0: site-build case studies

A case study now has a **kind**. An *audit* (the original four) shows before → after numbers. A
*site build* has no "before", so it shows what the finished site looks like and what it scores:
screenshots in a browser and a phone frame, the four Google PageSpeed scores for mobile and
desktop, up to three rows comparing it with a typical site, and up to four headline facts.

- **Same block.** `case-study-card` asks `Blocks\Build::is_build()` and renders the build's own
  hero, results band or card. No new block, no template change: the single, the archive and any
  Query Loop show a build correctly.
- **Fields** are in the "Case Study Details" metabox (kind, live address, scores, comparison rows,
  facts, source line, screenshots). They are not in the REST API.
- **Screenshots** are ordinary media-library images. Wide ones get the browser frame, tall ones
  the phone frame; each image's title and caption print under it, so write those in the library.
- **German alt text.** Every image now has an "Alt text (German)" field in the media library
  (`I18n\AttachmentAlt`). German pages read it out in place of the English alt; left empty they
  use the English one, and an image with no alt at all falls back to its title.
- **Featured work.** A case study tagged `featured` (the `case_study_tag` taxonomy) is picked up
  by the theme's Featured Work list, which is a Query Loop. The tag is never printed as a pill.
  ⚠️ That taxonomy is not public, and WordPress silently ignores a Query Loop filter on a
  non-public taxonomy. Switch on **Query Loop: filter by private taxonomies** in AJR Core →
  Modules and list `case_study_tag` in AJR Core → Blocks, or the list shows every case study.
  This plugin shows a warning in wp-admin while that is missing (`Core\Requirements`).
- **Drafts and passwords.** Both card blocks now print nothing for a case study that is a draft,
  private or waiting for its password (`Cards::can_show()`), and the plugin's REST fields are
  withheld from a password-protected case study. Its own page still prints the title.
- No JavaScript was added to the front end: the rings are CSS, the screenshot strip is scroll-snap.

## What's inside

### Blocks (all dynamic, `block.json` + `render.php` + editor controls)

| Block | Purpose |
|---|---|
| `responsive-image` | Art-directed image: separate desktop/mobile assets, lazy-loading + fetch-priority controls |
| `case-study-card` | Full case-study showcase. Audit: score circles, Core Web Vitals metrics, impact tiles. Site build: screenshots, PageSpeed rings, comparison rows |
| `case-study-mini-card` | Compact result card with count-up animation |
| `post-intro` | Styled lead paragraph sourced from post meta |
| `post-callout` | Highlight box sourced from post meta |
| `language-aware-nav` | Renders the navigation matching the visitor's Polylang language, resolved by slug convention (`{menuSlug}-{lang}`) — one header/footer part serves every language |
| `language-switcher` | Links to the current page's translations |

### Modules

- **CaseStudies** — this site's layer on AJR Core's case-study type: structured REST-exposed Core Web Vitals meta (metrics, impact) and its metabox, the site-build fields (kind, scores, comparison rows, facts, screenshots), the story intro, the results-band wording for a build, and the case-study SEO tweaks
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
