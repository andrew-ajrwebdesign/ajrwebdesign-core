<?php
/**
 * Language Switcher render.
 *
 * The proper-block version of the OC Legal details-dropdown switcher
 * (which lived in a wp:html pattern — the known standards debt). Current
 * language shows as flag + name + chevron; the other languages open in a
 * card styled like the nav dropdowns. No JS: <details> handles the toggle.
 *
 * @package AJR\SiteCore
 *
 * @var array $attributes Block attributes.
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'pll_the_languages' ) ) {
	return;
}

$ajrwd_langs = pll_the_languages(
	array(
		'raw'           => 1,
		'hide_if_empty' => 0,
	)
);

if ( ! is_array( $ajrwd_langs ) || count( $ajrwd_langs ) < 2 ) {
	return;
}

$ajrwd_show_flags = ! isset( $attributes['showFlags'] ) || (bool) $attributes['showFlags'];
$ajrwd_show_names = ! isset( $attributes['showNames'] ) || (bool) $attributes['showNames'];

$ajrwd_current = null;
$ajrwd_others  = array();
foreach ( $ajrwd_langs as $ajrwd_lang ) {
	if ( ! empty( $ajrwd_lang['current_lang'] ) ) {
		$ajrwd_current = $ajrwd_lang;
	} else {
		$ajrwd_others[] = $ajrwd_lang;
	}
}
if ( null === $ajrwd_current ) {
	$ajrwd_current = array_shift( $ajrwd_others );
}

$ajrwd_wrapper = get_block_wrapper_attributes( array( 'class' => 'ajr-lang-switch' ) );
?>
<details <?php echo $ajrwd_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<summary aria-label="<?php esc_attr_e( 'Choose a language', 'ajrwebdesign-core' ); ?>">
		<?php if ( $ajrwd_show_flags && ! empty( $ajrwd_current['flag'] ) ) : ?>
			<img src="<?php echo esc_url( $ajrwd_current['flag'] ); ?>" alt="" width="16" height="11">
		<?php endif; ?>
		<?php if ( $ajrwd_show_names ) : ?>
			<span><?php echo esc_html( $ajrwd_current['name'] ); ?></span>
		<?php endif; ?>
		<svg class="ajr-lang-switch__chev" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 12 12" width="10" height="10" aria-hidden="true"><path fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" d="M2.5 4.5 6 8l3.5-3.5"/></svg>
	</summary>
	<ul class="ajr-lang-switch__list">
		<?php foreach ( $ajrwd_others as $ajrwd_lang ) : ?>
			<li>
				<a href="<?php echo esc_url( $ajrwd_lang['url'] ); ?>" lang="<?php echo esc_attr( $ajrwd_lang['slug'] ); ?>" hreflang="<?php echo esc_attr( $ajrwd_lang['slug'] ); ?>">
					<?php if ( $ajrwd_show_flags && ! empty( $ajrwd_lang['flag'] ) ) : ?>
						<img src="<?php echo esc_url( $ajrwd_lang['flag'] ); ?>" alt="" width="16" height="11">
					<?php endif; ?>
					<?php echo esc_html( $ajrwd_lang['name'] ); ?>
				</a>
			</li>
		<?php endforeach; ?>
	</ul>
</details>
