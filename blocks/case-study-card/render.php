<?php
/**
 * Case Study Card render.
 *
 * Markup is identical to the legacy ajr-site-blocks/case-study-card so the
 * ported stylesheet applies unchanged; data reads through
 * Cards::get_case_meta() (structured meta with legacy fallback).
 *
 * @package AJR\SiteCore
 *
 * @var array $attributes Block attributes.
 */

use AJR\SiteCore\Blocks\Cards;
use AJR\SiteCore\CaseStudies\PostType;

defined( 'ABSPATH' ) || exit;

$layout_direction = isset( $attributes['layoutDirection'] ) ? sanitize_key( $attributes['layoutDirection'] ) : 'stats-left';
if ( ! in_array( $layout_direction, array( 'stats-left', 'text-left' ), true ) ) {
	$layout_direction = 'stats-left';
}

$case_study_id = isset( $attributes['caseStudyId'] ) ? absint( $attributes['caseStudyId'] ) : 0;
if ( ! $case_study_id && isset( $block->context['postId'] ) ) {
	// Template context: with no explicit selection, render the current post.
	$case_study_id = (int) $block->context['postId'];
}
if ( ! $case_study_id || PostType::POST_TYPE !== get_post_type( $case_study_id ) ) {
	return;
}

$case_meta  = Cards::get_case_meta( $case_study_id );
$case_title = $case_meta['title'];
$metrics    = $case_meta['metrics'];
$impact     = $case_meta['impact'];

$variant = isset( $attributes['variant'] ) ? sanitize_key( $attributes['variant'] ) : 'default';

if ( 'hero' === $variant ) {
	// The single-template hero: chips, h1, summary and CTAs left, a
	// float cluster of the headline numbers right. Rendered by this block
	// because every value is per-post meta a static template cannot reach.
	//
	// The CTAs are plain markup, not core/button blocks, so on-demand block
	// CSS never loads for them by itself — enqueue the button styles (core's
	// and the theme's) explicitly or the CTAs render as bare text links.
	wp_enqueue_style( 'wp-block-buttons' );
	wp_enqueue_style( 'wp-block-button' );
	if ( wp_style_is( 'ajrwebdesign-theme-core-button', 'registered' ) ) {
		wp_enqueue_style( 'ajrwebdesign-theme-core-button' );
	}

	$hero_attributes = get_block_wrapper_attributes( array( 'class' => 'ajr-cs-hero' ) );
	// http/https only: a CTA is a navigation, and the attribute is editable by
	// anyone who can edit a post carrying the block — no mailto/tel/ftp here.
	$cta_url         = isset( $attributes['ctaUrl'] ) ? esc_url_raw( $attributes['ctaUrl'], array( 'http', 'https' ) ) : '';
	$cta_label       = isset( $attributes['ctaLabel'] ) ? (string) $attributes['ctaLabel'] : '';
	$cta2_url        = isset( $attributes['ctaSecondaryUrl'] ) ? esc_url_raw( $attributes['ctaSecondaryUrl'], array( 'http', 'https' ) ) : '';
	$cta2_label      = isset( $attributes['ctaSecondaryLabel'] ) ? (string) $attributes['ctaSecondaryLabel'] : '';

	$before_ms = Cards::value_to_ms( $metrics['mobile']['before']['lcp'] );
	$after_ms  = Cards::value_to_ms( $metrics['mobile']['after']['lcp'] );
	// The bar pair reads "how much shorter the wait is" — after as a share
	// of before, floored at 8% so a huge win still draws a visible bar.
	$after_pct = ( $before_ms && $after_ms && $before_ms > 0 )
		? max( 8, min( 100, (int) round( ( $after_ms / $before_ms ) * 100 ) ) )
		: 0;
	?>
	<div <?php echo $hero_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
		<div class="ajr-cs-hero__intro">
			<?php echo Cards::render_tags( $case_study_id, 'ajr-cs-hero' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

			<h1 class="ajr-cs-hero__title"><?php echo esc_html( $case_title ? $case_title : get_the_title( $case_study_id ) ); ?></h1>

			<?php if ( '' !== $case_meta['summary'] ) : ?>
				<p class="ajr-cs-hero__summary"><?php echo esc_html( $case_meta['summary'] ); ?></p>
			<?php endif; ?>

			<?php if ( ( $cta_url && $cta_label ) || ( $cta2_url && $cta2_label ) ) : ?>
				<?php /* Buttons inside a dark hero always render as the theme's white-outline pair (global.css hero hook) — a gradient class here would be inert. */ ?>
				<div class="wp-block-buttons ajr-cs-hero__ctas">
					<?php // Real hero buttons carry their 2px border as block-level style, not CSS — mirror that here so heights match the site's 48px buttons; the hero hook supplies the color. ?>
					<?php if ( $cta_url && $cta_label ) : ?>
						<div class="wp-block-button"><a class="wp-block-button__link wp-element-button" style="border:2px solid transparent" href="<?php echo esc_url( $cta_url ); ?>"><?php echo esc_html( $cta_label ); ?></a></div>
					<?php endif; ?>
					<?php if ( $cta2_url && $cta2_label ) : ?>
						<div class="wp-block-button is-style-outline"><a class="wp-block-button__link wp-element-button" style="border:2px solid transparent" href="<?php echo esc_url( $cta2_url ); ?>"><?php echo esc_html( $cta2_label ); ?></a></div>
					<?php endif; ?>
				</div>
			<?php endif; ?>
		</div>

		<div class="ajr-cs-hero__floats">
			<div class="ajr-cs-hero__ringbg" aria-hidden="true"></div>

			<div class="ajr-cs-hero__float ajr-cs-hero__float--scores">
				<div>
					<div class="ajr-cs-hero__label"><?php echo esc_html( Cards::ui_label( 'Mobile' ) . ' · ' . Cards::ui_label( 'Before' ) ); ?></div>
					<div class="ajr-cs-hero__ring ajr-cs-hero__ring--before"><?php echo esc_html( $metrics['mobile']['before']['score'] ); ?></div>
				</div>
				<span class="ajr-cs-hero__arrow" aria-hidden="true"><?php echo Cards::icon( 'arrow-right' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
				<div>
					<div class="ajr-cs-hero__label"><?php echo esc_html( Cards::ui_label( 'Mobile' ) . ' · ' . Cards::ui_label( 'After' ) ); ?></div>
					<div class="ajr-cs-hero__ring ajr-cs-hero__ring--after"><?php echo esc_html( $metrics['mobile']['after']['score'] ); ?></div>
				</div>
			</div>

			<?php if ( $after_pct ) : ?>
				<div class="ajr-cs-hero__float ajr-cs-hero__float--lcp">
					<div class="ajr-cs-hero__label"><?php echo esc_html( Cards::ui_label( 'Largest Contentful Paint' ) ); ?></div>
					<div class="ajr-cs-hero__bars">
						<div class="ajr-cs-hero__bar ajr-cs-hero__bar--before"></div>
						<div class="ajr-cs-hero__bar ajr-cs-hero__bar--after" style="width:<?php echo esc_attr( (string) $after_pct ); ?>%"></div>
						<div class="ajr-cs-hero__bar-nums"><span><?php echo esc_html( $metrics['mobile']['before']['lcp'] ); ?></span><span><?php echo esc_html( $metrics['mobile']['after']['lcp'] ); ?></span></div>
					</div>
				</div>
			<?php endif; ?>

			<?php if ( '' !== $impact['cwv_after'] ) : ?>
				<div class="ajr-cs-hero__float ajr-cs-hero__float--cwv">
					<div class="ajr-cs-hero__label"><?php echo esc_html( Cards::ui_label( 'Core Web Vitals' ) ); ?></div>
					<div class="ajr-cs-hero__cwv"><span class="ajr-cs-hero__cwv-dot" aria-hidden="true"></span> <?php echo esc_html( Cards::ui_label( $impact['cwv_after'] ) ); ?></div>
				</div>
			<?php endif; ?>
		</div>
	</div>
	<?php
	return;
}

$lcp_value = Cards::render_change_value( $metrics['mobile']['before']['lcp'], $metrics['mobile']['after']['lcp'] );
$cwv_value = Cards::render_change_value( Cards::ui_label( $impact['cwv_before'] ), Cards::ui_label( $impact['cwv_after'] ), 'status' );

// Whole-box-clickable everywhere EXCEPT on the post's own single, where a
// self-link would be noise: the title becomes a stretched link to the
// case-study page (style.css draws the full-card hit area and hover).
$card_link = get_queried_object_id() !== $case_study_id || ! is_singular( PostType::POST_TYPE )
	? get_permalink( $case_study_id )
	: '';

$wrapper_attributes = get_block_wrapper_attributes(
	array( 'class' => 'ajr-case-study-card ajr-case-study-card--' . $layout_direction . ( $card_link ? ' ajr-case-study-card--linked' : '' ) )
);
?>
<section <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<div class="ajr-case-study-card__left">
		<?php
		echo Cards::device_panel( Cards::ui_label( 'Mobile' ), 'device-mobile', $metrics['mobile'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo Cards::device_panel( Cards::ui_label( 'Desktop' ), 'device-desktop', $metrics['desktop'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		?>
	</div>

	<div class="ajr-case-study-card__right">
		<?php if ( '' !== $case_meta['eyebrow'] ) : ?>
			<p class="ajr-case-study-card__eyebrow"><?php echo esc_html( $case_meta['eyebrow'] ); ?></p>
		<?php endif; ?>

		<?php if ( $case_title ) : ?>
			<h3 class="ajr-case-study-card__title">
			<?php
			if ( $card_link ) :
				?>
				<a class="ajr-case-study-card__title-link" href="<?php echo esc_url( $card_link ); ?>"><?php echo esc_html( $case_title ); ?></a>
				<?php
else :
	?>
				<?php echo esc_html( $case_title ); ?><?php endif; ?></h3>
		<?php endif; ?>

		<?php if ( '' !== $case_meta['summary'] ) : ?>
			<p class="ajr-case-study-card__summary"><?php echo esc_html( $case_meta['summary'] ); ?></p>
		<?php endif; ?>

		<?php echo Cards::render_tags( $case_study_id, 'ajr-case-study-card' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

		<div class="ajr-case-study-card__divider"></div>

		<div class="ajr-case-study-card__tiles">
			<?php
			echo Cards::tile( 'gauge', Cards::ui_label( 'Largest Contentful Paint' ), $lcp_value, 'purple' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo Cards::tile( 'core-web-vitals', Cards::ui_label( 'Core Web Vitals' ), $cwv_value, 'green' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo Cards::tile( 'cube', Cards::ui_label( 'Requests Removed' ), Cards::render_count_value( $impact['requests_removed'], '', 'ajr-case-study-card__count' ), 'blue' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo Cards::tile( 'page-size-reduced', Cards::ui_label( 'Page Size Reduced' ), Cards::render_count_value( $impact['page_size_reduced'], '', 'ajr-case-study-card__count' ), 'purple' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			?>
		</div>

		<?php if ( $card_link ) : ?>
			<?php // The visible affordance for the stretched link: the bento-card read-more language (mask arrow, 4px hover slide). A span, not a second anchor — the whole card is already the link. ?>
			<span class="ajr-case-study-card__read"><?php echo esc_html( Cards::ui_label( 'Read the case study' ) ); ?> <?php echo Cards::icon( 'arrow-right' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
		<?php endif; ?>
	</div>
</section>
