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
 * No JavaScript: the screenshot strip is CSS scroll-snap, the rings are a
 * conic-gradient driven by one custom property.
 */
class Build {

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
	 */
	public static function devices( array $gallery, string $sizes, bool $priority = false ): string {
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
		}

		$html = '<div class="ajr-cs-devices">' . self::frame( $desktop, 'ajr-hero-md', $attrs );

		$phone = $gallery['phone'][0] ?? 0;
		if ( $phone ) {
			// The phone is 24% of the pair's width less its 12px border. Claiming more
			// (25vw) made a phone at 2x fetch the 400px file where the 139px one fits:
			// 24 KB extra on an image that loads with the hero (perf review, 2026-09-30).
			$html .= self::phone( $phone, 'medium', array( 'sizes' => '(min-width: 881px) 130px, calc(24vw - 22px)' ) );
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
	 * case studies is then printed at the end of results().
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

			<?php // The hero's second button goes to the client's site, so this is the page's one link back to the other case studies (SEO review, 2026-09-30: without it a build was a dead end). ?>
			<p class="ajr-cs-more"><a href="<?php echo esc_url( self::archive_url() ); ?>"><?php echo esc_html( Cards::ui_label( 'View all case studies' ) ); ?></a></p>
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
	 * @param int $post_id Case study ID.
	 */
	public static function card( int $post_id ): string {
		$case_meta = Cards::get_case_meta( $post_id );
		$build     = self::data( $post_id );
		$gallery   = self::gallery( $post_id );
		$title     = '' !== $case_meta['title'] ? $case_meta['title'] : (string) get_the_title( $post_id );
		$devices   = self::devices( $gallery, '(min-width: 1280px) 620px, (min-width: 800px) calc(55vw - 64px), calc(94vw - 66px)' );

		ob_start();
		?>
		<article <?php echo get_block_wrapper_attributes( array( 'class' => 'ajr-cs-build-card ajr-case-study-card--linked' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
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

					<span class="ajr-case-study-card__read"><?php echo esc_html( Cards::ui_label( 'Read the case study' ) ); ?> <?php echo Cards::icon( 'arrow-right' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
				</div>
			</div>
		</article>
		<?php
		return (string) ob_get_clean();
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
