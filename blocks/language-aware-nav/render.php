<?php
/**
 * Language-Aware Navigation render.
 *
 * Resolves a wp_navigation post by slug convention — `{menuSlug}-{lang}`
 * (e.g. `primary-en`, `primary-de`) — for the current Polylang language,
 * falling back to the default language, then to the bare slug. Navigation
 * posts are looked up by slug, never by ID, so the same template-part
 * markup works on every install.
 *
 * @package AJR\SiteCore
 *
 * @var array $attributes Block attributes.
 */

use AJR\SiteCore\Core\Utils;

defined( 'ABSPATH' ) || exit;

$menu_slug = isset( $attributes['menuSlug'] ) ? sanitize_title( $attributes['menuSlug'] ) : 'primary';
$nav_attrs = isset( $attributes['navAttrs'] ) && is_array( $attributes['navAttrs'] ) ? $attributes['navAttrs'] : array();

if ( '' === $menu_slug ) {
	return;
}

$candidates = array();
$current    = Utils::current_language();
$default    = Utils::default_language();

if ( '' !== $current ) {
	$candidates[] = "{$menu_slug}-{$current}";
}
if ( '' !== $default && $default !== $current ) {
	$candidates[] = "{$menu_slug}-{$default}";
}
$candidates[] = $menu_slug;

$nav_post = null;
foreach ( $candidates as $candidate ) {
	$nav_post = get_page_by_path( $candidate, OBJECT, 'wp_navigation' );
	if ( $nav_post instanceof \WP_Post && 'publish' === $nav_post->post_status ) {
		break;
	}
	$nav_post = null;
}

if ( ! $nav_post ) {
	return;
}

$nav_attrs['ref'] = $nav_post->ID;

echo do_blocks( '<!-- wp:navigation ' . wp_json_encode( $nav_attrs ) . ' /-->' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
