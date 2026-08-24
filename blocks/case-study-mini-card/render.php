<?php
/**
 * Case Study Mini Card render.
 *
 * Markup is identical to the legacy ajr-site-blocks/case-study-mini-card so
 * the ported stylesheet applies unchanged; data reads through Cards::get_case_meta()
 * (structured meta with legacy fallback).
 *
 * @package AJR\SiteCore
 *
 * @var array $attributes Block attributes.
 */

use AJR\SiteCore\Blocks\Cards;
use AJR\SiteCore\CaseStudies\PostType;

defined( 'ABSPATH' ) || exit;

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

$mobile_before_score = $case_meta['metrics']['mobile']['before']['score'];
$mobile_after_score  = $case_meta['metrics']['mobile']['after']['score'];
$lcp_improvement     = Cards::calculate_improvement(
	$case_meta['metrics']['mobile']['before']['lcp'],
	$case_meta['metrics']['mobile']['after']['lcp']
);

// Whole-box-clickable everywhere EXCEPT on the post's own single — the
// title becomes a stretched link (style.css draws the hit area and hover).
$card_link = get_queried_object_id() !== $case_study_id || ! is_singular( PostType::POST_TYPE )
	? get_permalink( $case_study_id )
	: '';

$wrapper_attributes = get_block_wrapper_attributes(
	array( 'class' => 'ajr-case-study-mini-card' . ( $card_link ? ' ajr-case-study-mini-card--linked' : '' ) )
);
?>
<article <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<div class="ajr-case-study-mini-card__content">
		<?php if ( '' !== $case_meta['eyebrow'] ) : ?>
			<p class="ajr-case-study-mini-card__eyebrow"><?php echo esc_html( $case_meta['eyebrow'] ); ?></p>
		<?php endif; ?>

		<?php if ( $case_title ) : ?>
			<h3 class="ajr-case-study-mini-card__title">
			<?php
			if ( $card_link ) :
				?>
				<a class="ajr-case-study-mini-card__title-link" href="<?php echo esc_url( $card_link ); ?>"><?php echo esc_html( $case_title ); ?></a>
				<?php
else :
	?>
				<?php echo esc_html( $case_title ); ?><?php endif; ?></h3>
		<?php endif; ?>

		<?php if ( '' !== $case_meta['summary'] ) : ?>
			<p class="ajr-case-study-mini-card__summary"><?php echo esc_html( $case_meta['summary'] ); ?></p>
		<?php endif; ?>
	</div>

	<div class="ajr-case-study-mini-card__result">
		<div class="ajr-case-study-mini-card__result-inner">
			<div class="ajr-case-study-mini-card__result-label"><?php echo esc_html( Cards::ui_label( 'Performance Score' ) ); ?></div>

			<div class="ajr-case-study-mini-card__score">
				<span class="ajr-case-study-mini-card__score-before"><?php echo esc_html( $mobile_before_score ); ?></span>
				<span class="ajr-case-study-mini-card__score-arrow" aria-hidden="true">
					<?php echo Cards::icon( 'arrow-right' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</span>
				<?php echo Cards::render_count_value( $mobile_after_score, 'ajr-case-study-mini-card__score-after' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>

			<?php if ( '' !== $lcp_improvement ) : ?>
				<div class="ajr-case-study-mini-card__improvement">
					<div class="ajr-case-study-mini-card__result-label"><?php echo esc_html( Cards::ui_label( 'LCP Improvement' ) ); ?></div>
					<div class="ajr-case-study-mini-card__improvement-value">
						<?php echo Cards::render_count_value( $lcp_improvement, 'ajr-case-study-mini-card__improvement-number' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</div>
				</div>
			<?php endif; ?>
		</div>
	</div>
	<?php echo Cards::render_tags( $case_study_id ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
</article>
