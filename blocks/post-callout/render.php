<?php
/**
 * Post Callout render — replaces the legacy [ajr_post_callout] shortcode.
 *
 * @package AJR\SiteCore
 *
 * @var array     $attributes Block attributes.
 * @var \WP_Block $block      Block instance.
 */

use AJR\SiteCore\Posts\Meta;

defined( 'ABSPATH' ) || exit;

$callout_post_id = isset( $block->context['postId'] ) ? (int) $block->context['postId'] : get_the_ID();
if ( ! $callout_post_id || 'post' !== get_post_type( $callout_post_id ) ) {
	return;
}

$label = (string) get_post_meta( $callout_post_id, Meta::CALLOUT_LABEL, true );
$title = (string) get_post_meta( $callout_post_id, Meta::CALLOUT_TITLE, true );
$text  = (string) get_post_meta( $callout_post_id, Meta::CALLOUT_TEXT, true );

if ( '' === $label && '' === $title && '' === $text ) {
	return;
}

$wrapper_attributes = get_block_wrapper_attributes( array( 'class' => 'ajr-post-callout' ) );
?>
<aside <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> aria-label="<?php esc_attr_e( 'Article highlight', 'ajrwebdesign-core' ); ?>">
	<?php if ( '' !== $label ) : ?>
		<p class="ajr-post-callout__label"><?php echo esc_html( $label ); ?></p>
	<?php endif; ?>
	<?php if ( '' !== $title ) : ?>
		<p class="ajr-post-callout__title"><?php echo esc_html( $title ); ?></p>
	<?php endif; ?>
	<?php if ( '' !== $text ) : ?>
		<p class="ajr-post-callout__text"><?php echo esc_html( $text ); ?></p>
	<?php endif; ?>
</aside>
