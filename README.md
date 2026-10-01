# AJR Web Design Core

The site plugin for [ajrwebdesign.com](https://ajrwebdesign.com) — what is unique to this site, running beside the shared **AJR Core** plugin and the `ajrwebdesign-theme` FSE theme. Part of the signature stack: clean FSE theme + AJR Core + a thin site plugin.

**Status: actively maintained** (powers the live site).

### 1.16.0: the Query Loop tag filter lives here too

`CaseStudies\PrivateTagQuery` puts back a Query Loop's filter on `case_study_tag`, which core drops because the taxonomy is not public (Featured Work and the Case Studies lists would otherwise list every case study). It was AJR Core's `private_taxonomies` module; Andrew, 2026-10-01: *"query loop only on client so remove from core"*. It stands down while an older AJR Core still has that module on. The `Core\Requirements` warning about that module is gone with it. Testimonial and case-study lists now end their ordering on ID, so quotes that share an Order and a publish time always appear in the same sequence.

### Back from AJR Core in 1.15.0

Testimonials and case studies are registered by this plugin again (Andrew, 2026-10-01: content
types belong in each site's own plugin, not in AJR Core). The code is AJR Core 0.15.2's, and every
key is unchanged — `ajr_testimonial` / `testimonial_tag`, `ajr_case_study` / `case_study_tag`,
every meta key, the `ajr/testimonials`, `ajr/case-studies` and `ajr/case-study-card` blocks,
their markup and classes, the `/case-studies/<name>/` addresses and the
`ajr_core_testimonial_text` filter — so nothing on the site migrates.

- **Safe in either order.** While an older AJR Core still has its *Testimonials* or *Case studies*
  module switched on, the matching class here registers nothing (`Core\CoreModules`). Deploy this
  plugin first, then switch those two modules off (AJR Core → Modules) — Core deletes the rewrite
  rules as it does and they rebuild with the same addresses — then update AJR Core to 0.16.0,
  which no longer has them.
- **Settings are constants** (`SETTINGS` on each class): the names, the `case-studies` slug and
  "no archive" are the values the folio had saved in AJR Core. Change them with the
  `ajrwd_testimonials_settings` / `ajrwd_case_studies_settings` filters. ⛔ A new slug moves every
  case study's address: add redirects, and re-save Settings → Permalinks.
- **Blocks** live in `assets/blocks/` (plain JavaScript, no build step), not `blocks/` + `build/`:
  `Blocks\Registrar` registers every `build/` block without a render callback.
- **Tests:** a second PHPUnit suite, `phpunit-mocked.xml.dist` (WP_Mock), carries Core's tests
  for these classes; `composer test` runs both.
- The "AJR Core modules are off" admin error from 1.9.0 is gone: off is now the normal state.

The two sections below are history: what 1.8.0 and 1.9.0 moved out, which 1.15.0 brought back.

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

**Deploy order (1.9.0 only; superseded by 1.15.0 above):** AJR Core 0.12.0 first, then switch its
**Case studies** and **Testimonials** modules on, then this plugin.

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
  *(Superseded in 1.16.0: the filter is this plugin's own, `CaseStudies\PrivateTagQuery`, and the warning is gone.)*
- **Drafts and passwords.** Both card blocks now print nothing for a case study that is a draft,
  private or waiting for its password (`Cards::can_show()`), and the plugin's REST fields are
  withheld from a password-protected case study. Its own page still prints the title.
- No JavaScript was added to the front end: the rings are CSS, the screenshot strip is scroll-snap.

### New in 1.11.0: a fuller case-study page

Three optional bands on the single, each a variant of the same `case-study-card` block sitting
inside a section the theme owns (`cs-optional-band`). A band with nothing to show prints nothing
and `CaseStudies\OptionalBand` removes the section around it.

- **What changed** (`variant: changes`): up to three improvements, each a figure ("2×") with an
  optional before and after bar pair and a note. For a rebuild. ⛔ A client's traffic is shown as
  a change, never as their numbers.
- **What was delivered** (`variant: delivered`): a checklist, one item per line in the metabox.
- **More case studies** (`variant: related`): the newest other site build as its card, or two
  audits when there is none, and the link to all case studies. Shown on audits too. Every card is
  checked with `Cards::can_show()` as it is printed: the cached list of recent case studies
  (cleared when one is saved or deleted) is a shortcut, never the gate. Pages showing the band
  carry the LiteSpeed tag `ajrwd_cs_recent`, purged on the same events.

Also in 1.11.0: a case study's breadcrumb trail keeps its "Case Studies" level when the list is
an ordinary page at `/case-studies/` and AJR Core's archive is off (`Seo\CaseStudies`).

### New in 1.12.0: Agentic Browsing

Google's PageSpeed Insights has a fifth result, Agentic Browsing: whether an AI assistant can
read and use the page. It reports checks passed out of checks scored (2 to 4 today), not a score
out of 100. A site build can carry it: two fields in the metabox, "Checks passed" and "Checks
scored" (`ajrwd_cs_build[agentic]`).

- **Scorecard** (single): a third line under Mobile and Desktop, the figure and one sentence.
- **Card**: a dark panel under the rings ("4/4 · Agentic Browsing · Ready for AI assistants"),
  and a line in the **hero chip**, both shown only for a full pass. A partial result is stated in
  the scorecard, plainly, and is never a badge.
- **1.13.0:** the card's panel and the scorecard row of a full pass are dark on the white card,
  so the result stands out among the scores (it was a small tinted line).

⛔ Enter what PageSpeed Insights reports for the live site on the day (request the
`AGENTIC_BROWSING` category). Leave both fields empty when it was not measured.

### A speed or care job, shown as a build (1.14.0)

A job on a site somebody else built (speed work, ongoing care) can use the "Site build" kind, so
it gets the same page: screenshots, a scorecard, "What changed", "What was delivered". Three
things keep it honest:

- **Results heading** (`ajrwd_cs_build[results_title]`): its own heading in place of "The build
  at a glance".
- **Line under the scores** (`scores_note`, `scores_note_de`): what the scores are, in place of
  "Google PageSpeed · mobile" on the card and in the hero chip (for example an average of several
  pages, and what it was before).
- **Comparison heading**: "Against where it started", with each bar the figure now as a
  percentage of the figure before.

Enter only the scores the work changed: a ring is printed only for a score that was entered.
With the type line `SITE CARE` the story's opening line is the care one, not the new-site one
(`CaseStudies\StoryIntro`). The screenshot strip is labelled "Screenshots of the site" for every
case study (it said "the finished site").

Also in 1.14.0: the Post Intro and Post Callout blocks print nothing for a post the visitor may
not see (password, draft, private), and a password-protected post's intro and callout fields are
left out of its REST response, as a case study's already were.

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

- **CaseStudies** — the case-study type, its generic fields and the `ajr/case-studies` / `ajr/case-study-card` blocks (`CaseStudies`), plus this site's layer on it: structured REST-exposed Core Web Vitals meta (metrics, impact) and its metabox, the site-build fields (kind, scores, comparison rows, facts, screenshots), the story intro, the results-band wording for a build, and the case-study SEO tweaks
- **Testimonials** — the testimonial type, its rating and logo fields and the `ajr/testimonials` slider (`Testimonials`), plus the German quote and role fields and their editor panel (`German`)
- **I18n** — Polylang string registration via the `ajrwebdesign-core-i18n` theme-support contract, and hreflang handling
- **Compat** — `add_theme_support` contracts so the plugin degrades gracefully on any theme

## Development

```bash
composer install   # PHP dependencies + autoloader
npm install        # block build tooling
npm run build      # compile blocks/ -> build/
composer lint      # PHPCS (WordPress Coding Standards)
composer test      # PHPUnit: both suites (phpunit.xml.dist + phpunit-mocked.xml.dist)
```

Requires WordPress 6.9+ and PHP 8.0+.

## Installation (target sites)

Download the install-ready zip from the [Releases page](../../releases) — `vendor/` and compiled blocks are bundled; no build step needed.

## Architecture

PSR-4 under the `AJR\SiteCore\` namespace (`src/`), singleton `Core\Plugin` bootstrap, hooks registered in `register()` methods (never constructors), one text domain (`ajrwebdesign-core`). See `.github/workflows/` for CI and the release pipeline.
