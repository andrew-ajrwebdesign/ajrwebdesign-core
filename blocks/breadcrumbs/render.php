<?php
/**
 * Post Breadcrumbs render.
 *
 * Replaces the legacy [ajr_post_breadcrumbs] shortcode. The legacy version
 * hardcoded home_url('/resources/') and the English "Resources" label, which
 * broke on German posts; here both the posts page and the home URL resolve
 * through Polylang for the current language.
 *
 * @package AJR\SiteCore
 *
 * @var array $attributes Block attributes.
 */

use AJR\SiteCore\Core\Utils;

defined( 'ABSPATH' ) || exit;

if ( ! is_singular( 'post' ) ) {
	return;
}

$separator = isset( $attributes['separator'] ) ? trim( (string) $attributes['separator'] ) : '›';
$separator = '' === $separator ? '›' : $separator;
$show_home = ! isset( $attributes['showHome'] ) || (bool) $attributes['showHome'];

$sep_html = '<span class="ajr-breadcrumb-separator" aria-hidden="true">' . esc_html( $separator ) . '</span>';
$crumbs   = array();

if ( $show_home ) {
	$crumbs[] = '<a href="' . esc_url( Utils::home_url() ) . '">' . esc_html__( 'Home', 'ajrwebdesign-core' ) . '</a>';
}

// The posts page, resolved for the current language when translated.
$posts_page_id = (int) get_option( 'page_for_posts' );
if ( $posts_page_id > 0 ) {
	if ( function_exists( 'pll_get_post' ) ) {
		$translated = pll_get_post( $posts_page_id );
		if ( $translated ) {
			$posts_page_id = (int) $translated;
		}
	}
	$crumbs[] = '<a href="' . esc_url( (string) get_permalink( $posts_page_id ) ) . '">' . esc_html( get_the_title( $posts_page_id ) ) . '</a>';
}

$categories = get_the_category();
if ( ! empty( $categories ) ) {
	$category = $categories[0];
	$crumbs[] = '<a href="' . esc_url( (string) get_category_link( $category->term_id ) ) . '">' . esc_html( $category->name ) . '</a>';
}

if ( empty( $crumbs ) ) {
	return;
}

$wrapper_attributes = get_block_wrapper_attributes( array( 'class' => 'ajr-breadcrumbs' ) );
?>
<nav <?php echo $wrapper_attributes; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?> aria-label="<?php esc_attr_e( 'Breadcrumbs', 'ajrwebdesign-core' ); ?>">
	<?php echo implode( $sep_html, $crumbs ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
</nav>
