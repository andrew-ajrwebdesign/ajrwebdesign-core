<?php
/**
 * Unit-test bootstrap: autoloader + minimal WP function stubs.
 *
 * These are pure unit tests (no WordPress install); only the small set of
 * WP functions the tested classes touch are stubbed here.
 *
 * @package AJR\SiteCore
 */

define( 'ABSPATH', __DIR__ . '/' );
define( 'AJRWD_CORE_PATH', dirname( __DIR__ ) . '/' );

require dirname( __DIR__ ) . '/vendor/autoload.php';

// phpcs:disable
function sanitize_text_field( $str ) {
	return trim( preg_replace( '/[\r\n\t ]+/', ' ', strip_tags( (string) $str ) ) );
}

function sanitize_html_class( $classname ) {
	return preg_replace( '/[^A-Za-z0-9_-]/', '', (string) $classname );
}

function sanitize_key( $key ) {
	return preg_replace( '/[^a-z0-9_\-]/', '', strtolower( (string) $key ) );
}

function sanitize_title( $title ) {
	$title = strtolower( trim( (string) $title ) );
	$title = preg_replace( '/[^a-z0-9\-_]+/', '-', $title );
	return trim( $title, '-' );
}

function esc_attr( $text ) {
	return htmlspecialchars( (string) $text, ENT_QUOTES );
}

function esc_html( $text ) {
	return htmlspecialchars( (string) $text, ENT_QUOTES );
}

function esc_attr_e( $text, $domain = 'default' ) {
	echo esc_attr( $text );
}

// Per-test meta store.
$GLOBALS['ajrwd_test_meta'] = array();

function get_post_meta( $post_id, $key = '', $single = false ) {
	return $GLOBALS['ajrwd_test_meta'][ $post_id ][ $key ] ?? '';
}

function update_post_meta( $post_id, $key, $value ) {
	$GLOBALS['ajrwd_test_meta'][ $post_id ][ $key ] = $value;
	return true;
}

function get_the_terms( $post_id, $taxonomy ) {
	return array();
}

// Per-test titles + Polylang language (empty string = Polylang inactive).
$GLOBALS['ajrwd_test_titles'] = array();
$GLOBALS['ajrwd_test_lang']   = '';

function get_the_title( $post = 0 ) {
	return $GLOBALS['ajrwd_test_titles'][ $post ] ?? '';
}

function pll_current_language( $field = 'slug' ) {
	return $GLOBALS['ajrwd_test_lang'];
}

function is_wp_error( $thing ) {
	return false;
}

function wp_strip_all_tags( $text, $remove_breaks = false ) {
	$text = preg_replace( '@<(script|style)[^>]*?>.*?</\1>@si', '', (string) $text );
	$text = strip_tags( $text );
	if ( $remove_breaks ) {
		$text = preg_replace( '/[\r\n\t ]+/', ' ', $text );
	}
	return trim( $text );
}
// phpcs:enable
