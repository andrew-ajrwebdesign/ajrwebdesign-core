<?php
/**
 * Post Intro render — replaces the legacy [ajr_post_intro] shortcode.
 *
 * @package AJR\SiteCore
 *
 * @var array     $attributes Block attributes.
 * @var \WP_Block $block      Block instance.
 */

use AJR\SiteCore\Posts\Meta;

defined( 'ABSPATH' ) || exit;

$intro_post_id = isset( $block->context['postId'] ) ? (int) $block->context['postId'] : get_the_ID();
if ( ! $intro_post_id || 'post' !== get_post_type( $intro_post_id ) ) {
	return;
}

$intro = (string) get_post_meta( $intro_post_id, Meta::INTRO_TEXT, true );
if ( '' === trim( $intro ) ) {
	return;
}

$wrapper_attributes = get_block_wrapper_attributes( array( 'class' => 'ajr-post-intro' ) );
?>
<div <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<?php echo wpautop( esc_html( $intro ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
</div>
