<?php
/**
 * Bootstrap for the WP_Mock suite (tests/Mocked, phpunit-mocked.xml.dist).
 *
 * WHY A SECOND SUITE
 *
 * The testimonial and case-study classes came back from AJR Core 0.15.2 (2026-10-01) with ~40
 * tests written against WP_Mock. Rewriting them for tests/bootstrap.php's hand-written stubs
 * would mean re-deriving every assertion; running them as they are keeps the coverage that
 * proved the code in Core. The two bootstraps cannot share a process — that one defines WP
 * functions globally, WP_Mock must own them — so this suite runs from its own config.
 *
 * @package AJR\SiteCore
 */

define( 'ABSPATH', __DIR__ . '/' );
define( 'AJRWD_CORE_PATH', dirname( __DIR__ ) . '/' );
define( 'AJRWD_CORE_URL', 'https://example.test/wp-content/plugins/ajrwebdesign-core/' );
define( 'AJRWD_CORE_VERSION', '0.0.0-test' );

$ajrwd_autoload = dirname( __DIR__ ) . '/vendor/autoload.php';
if ( ! is_readable( $ajrwd_autoload ) ) {
	fwrite( STDERR, "Run `composer install` before the test suite.\n" ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- CLI bootstrap.
	exit( 1 );
}
require_once $ajrwd_autoload;

WP_Mock::bootstrap();

require_once __DIR__ . '/Mocked/wp-class-stubs.php';
require_once __DIR__ . '/Mocked/fake-ajr-core-modules.php';
