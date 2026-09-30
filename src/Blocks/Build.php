<?php
/**
 * The "site build" kind of case study, as the case-study-card block renders it.
 *
 * @package AJR\SiteCore
 */

namespace AJR\SiteCore\Blocks;

use AJR\SiteCore\CaseStudies\Meta;
use AJR\SiteCore\CaseStudies\PostType;
use AJR\SiteCore\I18n\AttachmentAlt;

defined( 'ABSPATH' ) || exit;

/**
 * Render helpers for a case study whose kind is "site build".
 *
 * WHY THIS EXISTS
 *
 * The first four case studies are audits: a slow site, the work, and a before →
 * after pair of numbers. A site built from nothing has no "before", so the same
 * card would print empty score circles. A build is shown the other way round:
 * what the finished site looks like (screenshots in a browser and a phone frame)
 * and what it scores today (the four Google PageSpeed categories, plus a few
 * rows comparing it with a typical site).
 *
 * It is the SAME block (ajrwebdesign-core/case-study-card), not a new one: the
 * block asks is_build() and hands over to this class, so every place a case
 * study already appears — the single's hero and results band, the archive, a
 * Query Loop on a page — shows a build correctly with no template change.
 *
 * Three shapes, chosen by where the block sits:
 *   - hero()      the single's dark hero: devices and a score chip
 *   - results()   the single's results band: scorecard, facts, screenshot strip
 *   - card()      everywhere else: one whole-box-clickable card
 *
 * And three more since 1.11.0, each the inside of one band of the single, asked
 * for by the block's variant (band()). A band with nothing to show prints
 * nothing, and CaseStudies\OptionalBand then drops the band around it:
 *   - changes()   "What changed": up to three improvements, each a figure with an
 *                 optional before/after bar pair and a note
 *   - delivered() "What was delivered": a checklist
 *   - related()   "More case studies": the next site build, or two audits
 *
 * No JavaScript: the screenshot strip is CSS scroll-snap, the rings are a
 * conic-gradient driven by one custom property.
 */
class Build {

	/**
	 * The block variants that print the inside of one optional band of the single.
	 *
	 * @var string[]
	 */
	public const BAND_VARIANTS = array( 'changes', 'delivered', 'related' );

	/**
	 * Object-cache key (group `ajrwd_core`) for the newest case studies' IDs.
	 */
	protected const RECENT_CACHE_KEY = 'ajrwd_cs_recent';

	/**
	 * Ring labels that are one word too long for a ring's column on a phone,
	 * with a soft hyphen (the two bytes \xC2\xAD) where they may break. Only the
	 * printed label uses this; the spoken one stays a plain word.
	 *
	 * @var array<string,string>
	 */
	protected const SOFT_BREAKS = array( 'Barrierefreiheit' => "Barriere\xC2\xADfreiheit" );

	/**
	 * Whether a case study is a site build.
	 *
	 * @param int $post_id Case study ID.
	 */
	public static function is_build( int $post_id ): bool {
		return Meta::KIND_BUILD === get_post_meta( $post_id, Meta::KIND, true );
	}

	/**
	 * The site-build fields, always in full shape.
	 *
	 * @param int $post_id Case study ID.
	 * @return array<string,mixed>
	 */
	public static function data( int $post_id ): array {
		return Meta::sanitize_build( get_post_meta( $post_id, Meta::BUILD, true ) );
	}

	/**
	 * The band a score falls in, by Lighthouse's own thresholds: 90+ good,
	 * 50–89 needs improvement, below 50 poor. Empty for no score.
	 *
	 * @param string $score Score as stored ("98", or "").
	 */
	public static function score_level( string $score ): string {
		if ( '' === $score || ! ctype_digit( $score ) ) {
			return '';
		}
		$value = (int) $score;
		if ( $value >= 90 ) {
			return 'good';
		}
		return $value >= 50 ? 'fair' : 'poor';
	}

	/**
	 * Whether a device has at least one score to show.
	 *
	 * @param array<string,string> $scores One device's scores.
	 */
	public static function has_scores( array $scores ): bool {
		return '' !== implode( '', array_map( 'strval', $scores ) );
	}

	/**
	 * The visible name of a PageSpeed category.
	 *
	 * @param string $category One of Meta::SCORE_KEYS.
	 */
	public static function category_label( string $category ): string {
		$labels = array(
			'performance'    => 'Performance',
			'accessibility'  => 'Accessibility',
			'best_practices' => 'Best practices',
			'seo'            => 'SEO',
		);
		return Cards::ui_label( $labels[ $category ] ?? $category );
	}

	/**
	 * The four score rings for one device.
	 *
	 * Each ring is an image to a screen reader ("Performance: 98 out of 100");
	 * the label printed under it is hidden from it, so nothing is read twice.
	 *
	 * @param array<string,string> $scores One device's scores.
	 * @param string               $size   '' (large), 'md' or 'sm'.
	 * @param bool                 $labels Print the category under each ring.
	 */
	public static function rings( array $scores, string $size = '', bool $labels = true ): string {
		if ( ! self::has_scores( $scores ) ) {
			return '';
		}

		$class = 'ajr-cs-scores' . ( '' !== $size ? ' ajr-cs-scores--' . sanitize_html_class( $size ) : '' );
		$html  = '<div class="' . esc_attr( $class ) . '">';

		foreach ( Meta::SCORE_KEYS as $category ) {
			$score = (string) ( $scores[ $category ] ?? '' );
			$level = self::score_level( $score );
			if ( '' === $level ) {
				continue;
			}
			$label = self::category_label( $category );
			/* translators: 1: PageSpeed category, e.g. Performance. 2: the score, 0-100. */
			$spoken = sprintf( Cards::ui_label( '%1$s: %2$s out of 100' ), $label, $score );

			$html .= '<div class="ajr-cs-score">'
				. '<div class="ajr-cs-ring ajr-cs-ring--' . esc_attr( $level ) . '" style="--score:' . esc_attr( $score ) . '" role="img" aria-label="' . esc_attr( $spoken ) . '">' . esc_html( $score ) . '</div>'
				. ( $labels ? '<div class="ajr-cs-score__label" aria-hidden="true">' . esc_html( self::SOFT_BREAKS[ $label ] ?? $label ) . '</div>' : '' )
				. '</div>';
		}

		return $html . '</div>';
	}

	/**
	 * Google's Agentic Browsing result, in one of three shapes.
	 *
	 * Agentic Browsing is the fifth category in PageSpeed Insights: whether an AI
	 * assistant can read and use the page (a well-formed accessibility tree, a
	 * page that does not shift, an llms.txt file, and so on). It reports checks
	 * passed out of checks scored, not a 0 to 100 score, so it is not a ring.
	 *
	 *   row   the scorecard's third line, under Mobile and Desktop, with a sentence
	 *   pill  one line on the card: the figure and "ready for AI assistants"
	 *   chip  the figure alone, in the hero's score chip
	 *
	 * The pill and the chip are a badge, so they show only a full pass. The row
	 * shows any result with at least one check passed, in plain words.
	 *
	 * @param array<string,mixed> $build From data().
	 * @param string              $shape row, pill or chip.
	 */
	public static function agentic( array $build, string $shape ): string {
		$passed = (int) ( $build['agentic']['passed'] ?? 0 );
		$total  = (int) ( $build['agentic']['total'] ?? 0 );
		if ( $total < 1 || $passed < 1 ) {
			return '';
		}
		$full = $passed === $total;
		/* translators: 1: checks passed. 2: checks scored. */
		$figure = sprintf( Cards::ui_label( 'Agentic Browsing %1$d/%2$d' ), $passed, $total );

		if ( 'row' !== $shape ) {
			if ( ! $full ) {
				return '';
			}
			if ( 'chip' === $shape ) {
				return '<div class="ajr-cs-agentic-chip">' . esc_html( $figure ) . '</div>';
			}
			return '<p class="ajr-cs-agentic-pill"><strong>' . esc_html( $figure ) . '</strong> <span>' . esc_html( Cards::ui_label( 'Ready for AI assistants' ) ) . '</span></p>';
		}

		$text = $full
			/* translators: %d: the number of checks, all of them passed. */
			? sprintf( Cards::ui_label( 'All %d checks passed. AI assistants can read and use this site.' ), $total )
			/* translators: 1: checks passed. 2: checks scored. */
			: sprintf( Cards::ui_label( '%1$d of %2$d checks passed in Google’s test of how well AI assistants can read and use a site.' ), $passed, $total );

		// The big figure is for the eye; the sentence beside it says the same in words.
		return '<div class="ajr-cs-device"><div class="ajr-cs-device__head"><div class="ajr-cs-device__name">' . esc_html( Cards::ui_label( 'AI agents' ) ) . '</div><div class="ajr-cs-device__note">' . esc_html( Cards::ui_label( 'Google’s Agentic Browsing test' ) ) . '</div></div>'
			. '<div class="ajr-cs-agentic"><div class="ajr-cs-agentic__figure' . ( $full ? ' ajr-cs-agentic__figure--full' : '' ) . '" aria-hidden="true">' . esc_html( $passed . '/' . $total ) . '</div><p class="ajr-cs-agentic__text">' . esc_html( $text ) . '</p></div></div>';
	}

	/**
	 * The screenshots of a case study that are real image attachments, split by
	 * shape: landscape for the browser frame, portrait for the phone frame.
	 *
	 * @param int $post_id Case study ID.
	 * @return array{all:int[],desktop:int[],phone:int[]}
	 */
	public static function gallery( int $post_id ): array {
		$ids   = Meta::sanitize_gallery( get_post_meta( $post_id, Meta::GALLERY, true ) );
		$found = array(
			'all'     => array(),
			'desktop' => array(),
			'phone'   => array(),
		);
		if ( array() === $ids ) {
			return $found;
		}

		// Two queries for every attachment and its meta together, instead of two each.
		_prime_post_caches( $ids, false, true );

		foreach ( $ids as $id ) {
			if ( ! wp_attachment_is_image( $id ) ) {
				continue;
			}
			$meta                                       = wp_get_attachment_metadata( $id );
			$portrait                                   = is_array( $meta ) && (int) ( $meta['height'] ?? 0 ) > (int) ( $meta['width'] ?? 0 );
			$found['all'][]                             = $id;
			$found[ $portrait ? 'phone' : 'desktop' ][] = $id;
		}

		return $found;
	}

	/**
	 * The attributes every screenshot carries: async decoding, and alt text in the
	 * page's language that is never empty (German alt on German pages, else the
	 * image's own alt, else its title; see I18n\AttachmentAlt). A screenshot is
	 * the subject of the page, so it must never be silent.
	 *
	 * @param int $id Attachment ID.
	 * @return array<string,string>
	 */
	protected static function image_attrs( int $id ): array {
		return array(
			'decoding' => 'async',
			'alt'      => AttachmentAlt::for_page( $id ),
		);
	}

	/**
	 * A screenshot in a browser frame.
	 *
	 * @param int                  $id    Attachment ID.
	 * @param string               $size  Registered image size.
	 * @param array<string,string> $attrs Extra image attributes (sizes, loading, fetchpriority).
	 */
	public static function frame( int $id, string $size, array $attrs = array() ): string {
		$image = wp_get_attachment_image( $id, $size, false, array_merge( self::image_attrs( $id ), $attrs ) );
		if ( '' === $image ) {
			return '';
		}
		return '<div class="ajr-cs-frame"><div class="ajr-cs-frame__bar" aria-hidden="true"><span></span><span></span><span></span></div>' . $image . '</div>';
	}

	/**
	 * A screenshot in a phone frame.
	 *
	 * @param int                  $id    Attachment ID.
	 * @param string               $size  Registered image size.
	 * @param array<string,string> $attrs Extra image attributes.
	 */
	public static function phone( int $id, string $size, array $attrs = array() ): string {
		$image = wp_get_attachment_image( $id, $size, false, array_merge( self::image_attrs( $id ), $attrs ) );
		if ( '' === $image ) {
			return '';
		}
		return '<div class="ajr-cs-phone">' . $image . '</div>';
	}

	/**
	 * The device pair: the first landscape screenshot in a browser frame with the
	 * first portrait one overlapping it as a phone.
	 *
	 * @param array{all:int[],desktop:int[],phone:int[]} $gallery  From gallery().
	 * @param string                                     $sizes    The browser image's sizes attribute for this layout.
	 * @param bool                                       $priority True when this is the page's largest image (the single's hero).
	 * @param bool                                       $lazy     True when the pair is known to sit far down the page (the "more case studies" band).
	 */
	public static function devices( array $gallery, string $sizes, bool $priority = false, bool $lazy = false ): string {
		$desktop = $gallery['desktop'][0] ?? 0;
		if ( ! $desktop ) {
			return '';
		}

		$attrs = array( 'sizes' => $sizes );
		if ( $priority ) {
			// The one image on the page that may skip lazy-loading: it is the
			// single's largest paint. Everywhere else core decides.
			$attrs['loading']       = 'eager';
			$attrs['fetchpriority'] = 'high';
		} elseif ( $lazy ) {
			// Core gives the first large image it meets fetchpriority="high". On an
			// audit's page that was this one, in the second-to-last band: the browser
			// fetched a below-the-fold screenshot ahead of everything else.
			$attrs['loading'] = 'lazy';
		}

		$html = '<div class="ajr-cs-devices">' . self::frame( $desktop, 'ajr-hero-md', $attrs );

		$phone = $gallery['phone'][0] ?? 0;
		if ( $phone ) {
			// The phone is 24% of the pair's width less its 12px border. Claiming more
			// (25vw) made a phone at 2x fetch the 400px file where the 139px one fits:
			// 24 KB extra on an image that loads with the hero (perf review, 2026-09-30).
			$phone_attrs = array( 'sizes' => '(min-width: 881px) 130px, calc(24vw - 22px)' );
			if ( $lazy ) {
				$phone_attrs['loading'] = 'lazy';
			}
			$html .= self::phone( $phone, 'medium', $phone_attrs );
		}

		return $html . '</div>';
	}

	/**
	 * The hostname of the live site, for the "Visit …" link text.
	 *
	 * @param string $url The live URL.
	 */
	public static function host( string $url ): string {
		$host = (string) wp_parse_url( $url, PHP_URL_HOST );

		// No regex: a leading "www." is a fixed prefix, and a preg_* failure would hand
		// back null, which prints as an empty link.
		return str_starts_with( $host, 'www.' ) ? substr( $host, 4 ) : $host;
	}

	/**
	 * Where the list of all case studies lives: the post type's archive, or
	 * /case-studies/ when a page has taken the archive's place.
	 */
	public static function archive_url(): string {
		$archive = get_post_type_archive_link( PostType::POST_TYPE );

		return $archive ? (string) $archive : home_url( '/case-studies/' );
	}

	/**
	 * The single's hero: tags, title, summary and CTAs left; the device pair and
	 * a chip of the mobile scores right.
	 *
	 * The template hands every case study the same CTA pair, worded for an audit
	 * ("Get results like these" / "View all case studies"). A build keeps the first
	 * button's address but words it "Start a project like this", and its second
	 * button goes to the finished site when one is set. The link back to the other
	 * case studies is then printed by related(), the "More case studies" band.
	 *
	 * @param int                 $post_id    Case study ID.
	 * @param array<string,mixed> $attributes Block attributes (the template's CTA pair; see above).
	 */
	public static function hero( int $post_id, array $attributes ): string {
		// The CTAs are plain markup, not core/button blocks, so their on-demand
		// CSS never loads by itself (same as the audit hero in render.php).
		wp_enqueue_style( 'wp-block-buttons' );
		wp_enqueue_style( 'wp-block-button' );
		if ( wp_style_is( 'ajrwebdesign-theme-core-button', 'registered' ) ) {
			wp_enqueue_style( 'ajrwebdesign-theme-core-button' );
		}

		$case_meta = Cards::get_case_meta( $post_id );
		$build     = self::data( $post_id );
		$gallery   = self::gallery( $post_id );

		$cta_url = isset( $attributes['ctaUrl'] ) ? esc_url_raw( (string) $attributes['ctaUrl'], array( 'http', 'https' ) ) : '';
		// The second button's text names the site ("Visit example.com"): the same
		// words as the link beside the screenshot strip, so one address never has
		// two names. Without a live URL it stays the template's own (the archive link).
		$cta2_url = $build['url'];
		/* translators: %s: the live site's hostname, e.g. example.com. */
		$cta2_label = '' !== $cta2_url ? sprintf( Cards::ui_label( 'Visit %s' ), self::host( $cta2_url ) ) : '';
		if ( '' === $cta2_url ) {
			$cta2_url   = isset( $attributes['ctaSecondaryUrl'] ) ? esc_url_raw( (string) $attributes['ctaSecondaryUrl'], array( 'http', 'https' ) ) : '';
			$cta2_label = isset( $attributes['ctaSecondaryLabel'] ) ? (string) $attributes['ctaSecondaryLabel'] : '';
		}

		ob_start();
		?>
		<div <?php echo get_block_wrapper_attributes( array( 'class' => 'ajr-cs-hero ajr-cs-hero--build' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
			<div class="ajr-cs-hero__intro">
				<?php echo Cards::render_tags( $post_id, 'ajr-cs-hero' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

				<h1 class="ajr-cs-hero__title"><?php echo esc_html( '' !== $case_meta['title'] ? $case_meta['title'] : get_the_title( $post_id ) ); ?></h1>

				<?php if ( '' !== $case_meta['summary'] ) : ?>
					<p class="ajr-cs-hero__summary"><?php echo esc_html( $case_meta['summary'] ); ?></p>
				<?php endif; ?>

				<?php if ( $cta_url || ( $cta2_url && $cta2_label ) ) : ?>
					<div class="wp-block-buttons ajr-cs-hero__ctas">
						<?php if ( $cta_url ) : ?>
							<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" style="border:2px solid transparent" href="<?php echo esc_url( $cta_url ); ?>"><?php echo esc_html( Cards::ui_label( 'Start a project like this' ) ); ?></a></div>
						<?php endif; ?>
						<?php if ( $cta2_url && $cta2_label ) : ?>
							<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" style="border:2px solid transparent" href="<?php echo esc_url( $cta2_url ); ?>"><?php echo esc_html( $cta2_label ); ?></a></div>
						<?php endif; ?>
					</div>
				<?php endif; ?>
			</div>

			<div class="ajr-cs-hero__floats ajr-cs-build-art">
				<div class="ajr-cs-hero__ringbg" aria-hidden="true"></div>
				<?php echo self::devices( $gallery, '(min-width: 1280px) 500px, (min-width: 881px) 40vw, calc(100vw - 3rem)', true ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

				<?php if ( self::has_scores( $build['scores']['mobile'] ) ) : ?>
					<div class="ajr-cs-build-chip">
						<div class="ajr-cs-hero__label"><?php echo esc_html( Cards::ui_label( 'Google PageSpeed · mobile' ) ); ?></div>
						<?php echo self::rings( $build['scores']['mobile'], 'sm', false ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<?php echo self::agentic( $build, 'chip' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</div>
				<?php endif; ?>
			</div>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * The single's results band: the scorecard (both devices' rings beside the
	 * comparison rows), the headline facts, and the screenshot strip.
	 *
	 * @param int $post_id Case study ID.
	 */
	public static function results( int $post_id ): string {
		$build   = self::data( $post_id );
		$gallery = self::gallery( $post_id );

		$devices = array(
			'mobile'  => array( Cards::ui_label( 'Mobile' ), Cards::ui_label( 'Mid-range phone, slow 4G' ) ),
			'desktop' => array( Cards::ui_label( 'Desktop' ), Cards::ui_label( 'Broadband' ) ),
		);

		$scores = '';
		foreach ( $devices as $device => $text ) {
			$rings = self::rings( $build['scores'][ $device ] );
			if ( '' === $rings ) {
				continue;
			}
			$scores .= '<div class="ajr-cs-device"><div class="ajr-cs-device__head"><div class="ajr-cs-device__name">' . esc_html( $text[0] ) . '</div><div class="ajr-cs-device__note">' . esc_html( $text[1] ) . '</div></div>' . $rings . '</div>';
		}
		$scores .= self::agentic( $build, 'row' );

		ob_start();
		?>
		<div <?php echo get_block_wrapper_attributes( array( 'class' => 'ajr-cs-build' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
			<?php if ( '' !== $scores || array() !== $build['compare'] ) : ?>
				<div class="ajr-cs-scorecard<?php echo ( '' === $scores || array() === $build['compare'] ) ? ' ajr-cs-scorecard--single' : ''; ?>">
					<?php if ( '' !== $scores ) : ?>
						<div class="ajr-cs-scorecard__scores"><?php echo $scores; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- built above from escaped parts. ?></div>
					<?php endif; ?>

					<?php if ( array() !== $build['compare'] ) : ?>
						<div class="ajr-cs-compare">
							<h3 class="ajr-cs-compare__title"><?php echo esc_html( '' !== $build['compare_title'] ? $build['compare_title'] : Cards::ui_label( 'Against a typical WordPress site' ) ); ?></h3>
							<?php foreach ( $build['compare'] as $row ) : ?>
								<div class="ajr-cs-cmp">
									<div class="ajr-cs-cmp__head"><span><?php echo esc_html( $row['label'] ); ?></span><strong><?php echo esc_html( $row['value'] ); ?></strong></div>
									<?php if ( $row['percent'] > 0 ) : ?>
										<?php // No bar length entered, no bars: a bar is a claim about a ratio. ?>
										<div class="ajr-cs-cmp__bar ajr-cs-cmp__bar--this" style="width:<?php echo esc_attr( (string) $row['percent'] ); ?>%" aria-hidden="true"></div>
										<div class="ajr-cs-cmp__bar ajr-cs-cmp__bar--typical" aria-hidden="true"></div>
									<?php endif; ?>
									<?php if ( '' !== $row['note'] ) : ?>
										<p class="ajr-cs-cmp__note"><?php echo esc_html( $row['note'] ); ?></p>
									<?php endif; ?>
								</div>
							<?php endforeach; ?>
						</div>
					<?php endif; ?>
				</div>
			<?php endif; ?>

			<?php if ( array() !== $build['facts'] ) : ?>
				<dl class="ajr-cs-facts">
					<?php foreach ( $build['facts'] as $fact ) : ?>
						<div class="ajr-cs-fact">
							<dt class="ajr-cs-fact__label"><?php echo esc_html( $fact['label'] ); ?></dt>
							<dd class="ajr-cs-fact__value"><?php echo esc_html( $fact['value'] ); ?></dd>
							<?php if ( '' !== $fact['note'] ) : ?>
								<dd class="ajr-cs-fact__note"><?php echo esc_html( $fact['note'] ); ?></dd>
							<?php endif; ?>
						</div>
					<?php endforeach; ?>
				</dl>
			<?php endif; ?>

			<?php if ( '' !== $build['source'] ) : ?>
				<p class="ajr-cs-source"><?php echo esc_html( $build['source'] ); ?></p>
			<?php endif; ?>

			<?php echo self::strip( $gallery, $build['url'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * The screenshot strip: every screenshot in its frame, side by side, scrolled
	 * sideways with CSS scroll-snap.
	 *
	 * The scroller is a focusable, labelled region so it can be scrolled from the
	 * keyboard. Each caption is the attachment's title and caption, which is what
	 * the media library already asks for.
	 *
	 * @param array{all:int[],desktop:int[],phone:int[]} $gallery From gallery().
	 * @param string                                     $url     The live URL, for the link beside the heading.
	 */
	public static function strip( array $gallery, string $url = '' ): string {
		if ( array() === $gallery['all'] ) {
			return '';
		}

		$phones = array_flip( $gallery['phone'] );
		$items  = '';

		foreach ( $gallery['all'] as $id ) {
			// The strip's height is clamp(170px, 24vw, 330px) (style.css), so a 16:10
			// frame is 38.4vw wide between 709px and 1376px and a fixed width outside it.
			$is_phone = isset( $phones[ $id ] );
			$shot     = $is_phone
				? self::phone( $id, 'medium', array( 'sizes' => '(min-width: 1376px) 162px, (min-width: 709px) calc(11.1vw + 9px), 88px' ) )
				: self::frame( $id, 'medium_large', array( 'sizes' => '(min-width: 1376px) 528px, (min-width: 709px) 38.4vw, 272px' ) );
			if ( '' === $shot ) {
				continue;
			}

			$name    = (string) get_the_title( $id );
			$caption = (string) wp_get_attachment_caption( $id );
			$text    = ( '' !== $name ? '<strong>' . esc_html( $name ) . '</strong>' : '' )
				. ( '' !== $caption ? ' ' . esc_html( $caption ) : '' );

			$items .= '<li class="ajr-cs-strip__item"><figure class="ajr-cs-strip__figure">' . $shot
				. ( '' !== $text ? '<figcaption class="ajr-cs-strip__caption">' . $text . '</figcaption>' : '' )
				. '</figure></li>';
		}

		if ( '' === $items ) {
			return '';
		}

		$html = '<div class="ajr-cs-strip-head"><h3 class="ajr-cs-strip-head__title">' . esc_html( Cards::ui_label( 'A look around the site' ) ) . '</h3>';
		if ( '' !== $url ) {
			/* translators: %s: the live site's hostname, e.g. example.com. */
			$html .= '<a class="ajr-cs-strip-head__link" href="' . esc_url( $url ) . '">' . esc_html( sprintf( Cards::ui_label( 'Visit %s' ), self::host( $url ) ) ) . '</a>';
		}
		$html .= '</div>';

		// role="list": list-style:none drops the list semantics in Safari/VoiceOver otherwise.
		return $html . '<div class="ajr-cs-strip" role="region" tabindex="0" aria-label="' . esc_attr( Cards::ui_label( 'Screenshots of the finished site' ) ) . '"><ul class="ajr-cs-strip__list" role="list">' . $items . '</ul></div>';
	}

	/**
	 * The card shown everywhere except the case study's own page: the device
	 * pair, then type, title, summary, the mobile scores and the read button.
	 *
	 * ⛔ WHOLE-BOX CLICKABLE: the title link is the card's only link and its
	 * ::after covers the whole card (the block's existing --linked rules). No
	 * wrapper between the article and that link may be positioned, or the hit
	 * area shrinks to that wrapper.
	 *
	 * @param int  $post_id  Case study ID.
	 * @param bool $as_block True when the card IS the block's output (it takes the block's
	 *                       own wrapper attributes). False when it is printed inside another
	 *                       shape, such as related(): the block's wrapper belongs to that shape,
	 *                       and taking it twice would apply the block's margin twice.
	 */
	public static function card( int $post_id, bool $as_block = true ): string {
		$case_meta = Cards::get_case_meta( $post_id );
		$build     = self::data( $post_id );
		$gallery   = self::gallery( $post_id );
		$title     = '' !== $case_meta['title'] ? $case_meta['title'] : (string) get_the_title( $post_id );
		// Printed inside another shape, the card is the "more case studies" band: far down the page.
		$devices = self::devices( $gallery, '(min-width: 1280px) 620px, (min-width: 800px) calc(55vw - 64px), calc(94vw - 66px)', false, ! $as_block );

		ob_start();
		?>
		<article <?php echo $as_block ? get_block_wrapper_attributes( array( 'class' => 'ajr-cs-build-card ajr-case-study-card--linked' ) ) : 'class="ajr-cs-build-card ajr-case-study-card--linked"'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core escapes the wrapper attributes; the other branch is a literal. ?>>
			<div class="ajr-cs-build-card__inner<?php echo '' === $devices ? ' ajr-cs-build-card__inner--text' : ''; ?>">
				<?php if ( '' !== $devices ) : ?>
					<div class="ajr-cs-build-card__media"><?php echo $devices; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></div>
				<?php endif; ?>

				<div class="ajr-cs-build-card__body">
					<?php if ( '' !== $case_meta['eyebrow'] ) : ?>
						<p class="ajr-case-study-card__eyebrow"><?php echo esc_html( $case_meta['eyebrow'] ); ?></p>
					<?php endif; ?>

					<h3 class="ajr-case-study-card__title"><a class="ajr-case-study-card__title-link" href="<?php echo esc_url( (string) get_permalink( $post_id ) ); ?>"><?php echo esc_html( $title ); ?></a></h3>

					<?php if ( '' !== $case_meta['summary'] ) : ?>
						<p class="ajr-case-study-card__summary"><?php echo esc_html( $case_meta['summary'] ); ?></p>
					<?php endif; ?>

					<?php if ( self::has_scores( $build['scores']['mobile'] ) ) : ?>
						<?php echo self::rings( $build['scores']['mobile'], 'md' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
						<p class="ajr-cs-build-card__note"><?php echo esc_html( Cards::ui_label( 'Google PageSpeed · mobile' ) ); ?></p>
					<?php endif; ?>

					<?php echo self::agentic( $build, 'pill' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

					<span class="ajr-case-study-card__read"><?php echo esc_html( Cards::ui_label( 'Read the case study' ) ); ?> <?php echo Cards::icon( 'arrow-right' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
				</div>
			</div>
		</article>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * "What changed": the improvements the work produced, each shown as a change.
	 *
	 * A row is a label and a figure ("2×", "EN + ES"). With a "before" length it
	 * also draws two bars, before and after, the after bar always full width; the
	 * bars are decoration for the figure beside them and are hidden from screen
	 * readers. Counts are the client's to publish, so the fields ask for a change,
	 * not a number (Andrew, 2026-09-30).
	 *
	 * @param int $post_id Case study ID.
	 */
	public static function changes( int $post_id ): string {
		$build = self::data( $post_id );
		if ( array() === $build['changes'] ) {
			return '';
		}

		ob_start();
		?>
		<div <?php echo get_block_wrapper_attributes( array( 'class' => 'ajr-cs-changes' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
			<div class="ajr-cs-changes__intro">
				<h2 class="wp-block-heading"><?php echo esc_html( '' !== $build['changes_title'] ? $build['changes_title'] : Cards::ui_label( 'What changed' ) ); ?></h2>
				<?php if ( '' !== $build['changes_intro'] ) : ?>
					<p class="ajr-cs-changes__text"><?php echo esc_html( $build['changes_intro'] ); ?></p>
				<?php endif; ?>
			</div>

			<div class="ajr-cs-changes__rows">
				<?php foreach ( $build['changes'] as $row ) : ?>
					<div class="ajr-cs-change">
						<div class="ajr-cs-change__head">
							<h3 class="ajr-cs-change__label"><?php echo esc_html( $row['label'] ); ?></h3>
							<span class="ajr-cs-change__figure"><?php echo esc_html( $row['figure'] ); ?></span>
						</div>
						<?php if ( $row['before'] > 0 ) : ?>
							<div class="ajr-cs-change__bar" aria-hidden="true"><span class="ajr-cs-change__name"><?php echo esc_html( Cards::ui_label( 'Before' ) ); ?></span><span class="ajr-cs-change__track ajr-cs-change__track--before" style="width:<?php echo esc_attr( (string) $row['before'] ); ?>%"></span></div>
							<div class="ajr-cs-change__bar" aria-hidden="true"><span class="ajr-cs-change__name"><?php echo esc_html( Cards::ui_label( 'After' ) ); ?></span><span class="ajr-cs-change__track ajr-cs-change__track--after"></span></div>
						<?php endif; ?>
						<?php if ( '' !== $row['note'] ) : ?>
							<p class="ajr-cs-change__note"><?php echo esc_html( $row['note'] ); ?></p>
						<?php endif; ?>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * "What was delivered": a checklist, in the theme's checkmark list style.
	 *
	 * @param int $post_id Case study ID.
	 */
	public static function delivered( int $post_id ): string {
		$build = self::data( $post_id );
		if ( array() === $build['delivered'] ) {
			return '';
		}

		$list = '<ul class="wp-block-list is-style-checkmark-list ajr-cs-delivered__list">';
		foreach ( $build['delivered'] as $item ) {
			$list .= '<li>' . esc_html( $item ) . '</li>';
		}
		$list .= '</ul>';

		// Sent through core's List block, which returns the markup unchanged: a theme
		// loads its list styles (the checkmarks) only where a List block renders, and
		// this way the plugin needs to know nothing about the theme's stylesheets.
		$wrapper = get_block_wrapper_attributes( array( 'class' => 'ajr-cs-delivered' ) );
		$list    = render_block(
			array(
				'blockName'    => 'core/list',
				'attrs'        => array( 'className' => 'is-style-checkmark-list' ),
				'innerBlocks'  => array(),
				'innerHTML'    => $list,
				'innerContent' => array( $list ),
			)
		);

		$html = '<div ' . $wrapper . '>'
			. '<h2 class="wp-block-heading has-text-align-center">' . esc_html( Cards::ui_label( 'What was delivered' ) ) . '</h2>';
		if ( '' !== $build['delivered_intro'] ) {
			$html .= '<p class="ajr-cs-delivered__intro has-text-align-center">' . esc_html( $build['delivered_intro'] ) . '</p>';
		}

		return $html . $list . '</div>';
	}

	/**
	 * The newest published case studies, newest first, as IDs.
	 *
	 * One small query, cached until a case study is saved or deleted
	 * (forget_recent(), hooked in CaseStudies\Meta): the "more case studies" band
	 * is on every case study's page. Not keyed on core's `posts` stamp, which
	 * moves on every post-meta write anywhere on the site, the editor's
	 * heartbeat lock included, so the cache would hardly ever be warm.
	 *
	 * @return int[]
	 */
	protected static function recent_ids(): array {
		$key = self::RECENT_CACHE_KEY;
		$ids = wp_cache_get( $key, 'ajrwd_core' );
		if ( is_array( $ids ) ) {
			return $ids;
		}

		$query = new \WP_Query(
			array(
				'post_type'              => PostType::POST_TYPE,
				'post_status'            => 'publish',
				'has_password'           => false,
				'posts_per_page'         => 8,
				'orderby'                => 'date',
				'order'                  => 'DESC',
				'fields'                 => 'ids',
				'ignore_sticky_posts'    => true,
				'no_found_rows'          => true,
				'update_post_term_cache' => false,
			)
		);
		$ids   = array_map( 'intval', $query->posts );
		wp_cache_set( $key, $ids, 'ajrwd_core', HOUR_IN_SECONDS );

		return $ids;
	}

	/**
	 * Forgets the cached list of recent case studies. Runs when a case study is
	 * saved (published, edited, trashed, given a password) or deleted.
	 */
	public static function forget_recent(): void {
		wp_cache_delete( self::RECENT_CACHE_KEY, 'ajrwd_core' );

		// The cached pages that print the "more case studies" band (tagged in related()).
		do_action( 'litespeed_purge', self::RECENT_CACHE_KEY ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- LiteSpeed Cache's own hook.
	}

	/**
	 * Which case studies the "more case studies" band shows on one case study's
	 * page: the newest other site build alone, or, when there is none, the two
	 * newest other audits. Empty when there is nothing else to show.
	 *
	 * The cached list it is given is a shortcut, not the gate. A case study
	 * unpublished or given a password a moment ago can still be in it (a request
	 * that was mid-query when the cache was cleared writes the old list back), so
	 * every candidate is checked here, at the moment it would be printed.
	 *
	 * @param int[] $recent  Recent case-study IDs, newest first.
	 * @param int   $post_id The case study being shown.
	 * @return int[]
	 */
	public static function pick_related( array $recent, int $post_id ): array {
		$others = array_values(
			array_filter(
				array_map( 'intval', $recent ),
				static fn( int $id ): bool => $id !== $post_id && Cards::can_show( $id )
			)
		);

		foreach ( $others as $id ) {
			if ( self::is_build( $id ) ) {
				return array( $id );
			}
		}

		return array_slice( $others, 0, 2 );
	}

	/**
	 * "More case studies": the newest OTHER site build as its card; when there is
	 * none, the two newest other audits as compact cards. Then the link to all of
	 * them, which is the page's way back to the rest of the work.
	 *
	 * Works on an audit's page too, where it shows the newest build.
	 *
	 * @param int $post_id The case study being shown.
	 */
	public static function related( int $post_id ): string {
		$recent = self::recent_ids();
		if ( array() === $recent ) {
			return '';
		}

		// One query for the rows and one for the fields of every candidate, instead of one each.
		_prime_post_caches( $recent, false, true );
		$pick = self::pick_related( $recent, $post_id );
		if ( array() === $pick ) {
			return '';
		}

		// Page caches: this page now shows another case study, so it must be cleared when
		// any case study changes (forget_recent()). Does nothing without LiteSpeed Cache.
		do_action( 'litespeed_tag_add', self::RECENT_CACHE_KEY ); // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- LiteSpeed Cache's own hook.

		if ( self::is_build( $pick[0] ) ) {
			$cards = self::card( $pick[0], false );
			$pair  = false;
		} else {
			$cards = '';
			foreach ( $pick as $id ) {
				$cards .= render_block(
					array(
						'blockName'    => 'ajrwebdesign-core/case-study-mini-card',
						'attrs'        => array( 'caseStudyId' => $id ),
						'innerBlocks'  => array(),
						'innerHTML'    => '',
						'innerContent' => array(),
					)
				);
			}
			$pair = true;
		}
		if ( '' === trim( $cards ) ) {
			return '';
		}

		return '<div ' . get_block_wrapper_attributes( array( 'class' => 'ajr-cs-related' ) ) . '>'
			. '<h2 class="wp-block-heading has-text-align-center">' . esc_html( Cards::ui_label( 'More case studies' ) ) . '</h2>'
			. '<div class="ajr-cs-related__list' . ( $pair ? ' ajr-cs-related__list--pair' : '' ) . '">' . $cards . '</div>'
			. '<p class="ajr-cs-more"><a href="' . esc_url( self::archive_url() ) . '">' . esc_html( Cards::ui_label( 'View all case studies' ) ) . '</a></p>'
			. '</div>';
	}

	/**
	 * The inside of one of the single's optional bands, by the block's variant.
	 *
	 * "changes" and "delivered" belong to a site build and print nothing for an
	 * audit; "related" works for both kinds.
	 *
	 * @param int    $post_id Case study ID.
	 * @param string $variant changes, delivered or related.
	 */
	public static function band( int $post_id, string $variant ): string {
		if ( 'related' === $variant ) {
			return self::related( $post_id );
		}
		if ( ! self::is_build( $post_id ) ) {
			return '';
		}

		return 'changes' === $variant ? self::changes( $post_id ) : self::delivered( $post_id );
	}

	/**
	 * Render a site-build case study for the block, picking the shape from the
	 * block's variant and from whether this is the case study's own page.
	 *
	 * @param int                 $post_id    Case study ID.
	 * @param array<string,mixed> $attributes Block attributes.
	 */
	public static function render( int $post_id, array $attributes ): string {
		if ( 'hero' === ( $attributes['variant'] ?? 'default' ) ) {
			return self::hero( $post_id, $attributes );
		}

		$own_page = is_singular( PostType::POST_TYPE ) && get_queried_object_id() === $post_id;

		return $own_page ? self::results( $post_id ) : self::card( $post_id );
	}
}
