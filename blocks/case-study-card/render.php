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

$case_title = get_the_title( $case_study_id );
$case_meta  = Cards::get_case_meta( $case_study_id );
$metrics    = $case_meta['metrics'];
$impact     = $case_meta['impact'];

$lcp_value = Cards::render_change_value( $metrics['mobile']['before']['lcp'], $metrics['mobile']['after']['lcp'] );
$cwv_value = Cards::render_change_value( $impact['cwv_before'], $impact['cwv_after'], 'status' );

$wrapper_attributes = get_block_wrapper_attributes(
	array( 'class' => 'ajr-case-study-card ajr-case-study-card--' . $layout_direction )
);
?>
<section <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<div class="ajr-case-study-card__left">
		<?php
		echo Cards::device_panel( 'Mobile', 'device-mobile', $metrics['mobile'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo Cards::device_panel( 'Desktop', 'device-desktop', $metrics['desktop'] ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		?>
	</div>

	<div class="ajr-case-study-card__right">
		<?php if ( '' !== $case_meta['eyebrow'] ) : ?>
			<p class="ajr-case-study-card__eyebrow"><?php echo esc_html( $case_meta['eyebrow'] ); ?></p>
		<?php endif; ?>

		<?php if ( $case_title ) : ?>
			<h3 class="ajr-case-study-card__title"><?php echo esc_html( $case_title ); ?></h3>
		<?php endif; ?>

		<?php if ( '' !== $case_meta['summary'] ) : ?>
			<p class="ajr-case-study-card__summary"><?php echo esc_html( $case_meta['summary'] ); ?></p>
		<?php endif; ?>

		<?php echo Cards::render_tags( $case_study_id, 'ajr-case-study-card' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>

		<div class="ajr-case-study-card__divider"></div>

		<div class="ajr-case-study-card__tiles">
			<?php
			echo Cards::tile( 'gauge', 'Largest Contentful Paint', $lcp_value, 'purple' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo Cards::tile( 'core-web-vitals', 'Core Web Vitals', $cwv_value, 'green' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo Cards::tile( 'cube', 'Requests Removed', Cards::render_count_value( $impact['requests_removed'], '', 'ajr-case-study-card__count' ), 'blue' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			echo Cards::tile( 'page-size-reduced', 'Page Size Reduced', Cards::render_count_value( $impact['page_size_reduced'], '', 'ajr-case-study-card__count' ), 'purple' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			?>
		</div>
	</div>
</section>
