<?php
/**
 * Responsive Image render.
 *
 * Frontend markup is intentionally identical to the legacy
 * ajr-site-blocks/responsive-image block (minus the wrapper block class,
 * which follows the new block name) so existing styling carries over.
 *
 * @package AJR\SiteCore
 *
 * @var array $attributes Block attributes.
 */

defined( 'ABSPATH' ) || exit;

$desktop_image_id = isset( $attributes['desktopImageId'] ) ? (int) $attributes['desktopImageId'] : 0;
$mobile_image_id  = isset( $attributes['mobileImageId'] ) ? (int) $attributes['mobileImageId'] : 0;
$breakpoint       = isset( $attributes['breakpoint'] ) ? (int) $attributes['breakpoint'] : 760;
$img_loading      = isset( $attributes['loading'] ) && 'eager' === $attributes['loading'] ? 'eager' : 'lazy';
$fetch_priority   = isset( $attributes['fetchPriority'] ) ? sanitize_key( $attributes['fetchPriority'] ) : 'auto';
$custom_alt       = isset( $attributes['alt'] ) ? trim( wp_strip_all_tags( $attributes['alt'] ) ) : '';
$border_radius    = isset( $attributes['borderRadius'] ) ? (int) $attributes['borderRadius'] : 0;

if ( ! $desktop_image_id ) {
	return;
}

$desktop_alt = $custom_alt ? $custom_alt : (string) get_post_meta( $desktop_image_id, '_wp_attachment_image_alt', true );

$figure_style = $border_radius > 0 ? 'border-radius:' . $border_radius . 'px;overflow:hidden;' : '';

$img_attr = array(
	'class'    => 'ajr-responsive-image__img',
	'alt'      => $desktop_alt,
	'loading'  => $img_loading,
	'decoding' => 'async',
	'sizes'    => '720px',
);
if ( in_array( $fetch_priority, array( 'high', 'low' ), true ) ) {
	$img_attr['fetchpriority'] = $fetch_priority;
}

$desktop_img_html = wp_get_attachment_image( $desktop_image_id, 'full', false, $img_attr );
if ( ! $desktop_img_html ) {
	return;
}

$wrapper_attributes = get_block_wrapper_attributes(
	array(
		'class' => 'ajr-responsive-image',
		'style' => $figure_style,
	)
);
?>
<figure <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<?php if ( $mobile_image_id ) : ?>
		<?php
		$mobile_src    = wp_get_attachment_image_url( $mobile_image_id, 'full' );
		$mobile_srcset = wp_get_attachment_image_srcset( $mobile_image_id, 'full' );
		?>
		<picture>
			<?php if ( $mobile_srcset ) : ?>
				<source
					media="(max-width: <?php echo esc_attr( $breakpoint ); ?>px)"
					srcset="<?php echo esc_attr( $mobile_srcset ); ?>"
					sizes="600px"
				>
			<?php elseif ( $mobile_src ) : ?>
				<source
					media="(max-width: <?php echo esc_attr( $breakpoint ); ?>px)"
					srcset="<?php echo esc_url( $mobile_src ); ?>"
				>
			<?php endif; ?>
			<?php echo $desktop_img_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</picture>
	<?php else : ?>
		<?php echo $desktop_img_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	<?php endif; ?>
</figure>
