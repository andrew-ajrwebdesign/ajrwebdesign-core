<?php
/**
 * Plugin Name:       AJR Web Design Core
 * Plugin URI:        https://github.com/andrew-ajrwebdesign/ajrwebdesign-core
 * Description:       Site plugin for ajrwebdesign.com — its own blocks, case studies, testimonials and multilingual helpers. Runs alongside AJR Core, which provides the shared features. Companion to the ajrwebdesign-theme FSE theme.
 * Version:           1.15.0
 * Requires at least: 6.9
 * Requires PHP:      8.0
 * Requires Plugins:  ajr-core
 * Author:            AJR Web Design
 * Author URI:        https://ajrwebdesign.com
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       ajrwebdesign-core
 * Domain Path:       /languages
 *
 * @package AJR\SiteCore
 */

defined( 'ABSPATH' ) || exit;

// Version is read from the plugin header above — never repeated as a literal,
// so a release bump is one edit and the two can never drift apart.
$ajrwd_core_header = get_file_data( __FILE__, array( 'Version' => 'Version' ) );
define( 'AJRWD_CORE_VERSION', $ajrwd_core_header['Version'] );
define( 'AJRWD_CORE_FILE', __FILE__ );
define( 'AJRWD_CORE_PATH', plugin_dir_path( __FILE__ ) );
define( 'AJRWD_CORE_URL', plugin_dir_url( __FILE__ ) );
define( 'AJRWD_CORE_BASENAME', plugin_basename( __FILE__ ) );

// Guarded autoloader — admin notice instead of a fatal when vendor/ is absent
// (a source checkout without `composer install`).
$ajrwd_core_autoload = AJRWD_CORE_PATH . 'vendor/autoload.php';
if ( ! is_readable( $ajrwd_core_autoload ) ) {
	add_action(
		'admin_notices',
		static function () {
			echo '<div class="notice notice-error"><p><strong>AJR Web Design Core</strong> is missing its dependencies. Install the release zip, or run <code>composer install</code> in the plugin folder.</p></div>';
		}
	);
	return;
}
require_once $ajrwd_core_autoload;

/*
 * Register the case-study type BEFORE flushing, so the flush includes its /case-studies/ rules.
 * The other order leaves every case study 404ing until something else flushes. Skipped while an
 * older AJR Core still registers the type (its own activation and settings watchers own the
 * rules then).
 */
register_activation_hook(
	__FILE__,
	static function () {
		if ( ! \AJR\SiteCore\Core\CoreModules::serves( \AJR\SiteCore\CaseStudies\CaseStudies::CORE_MODULE ) ) {
			( new \AJR\SiteCore\CaseStudies\CaseStudies() )->register_post_type();
		}
		flush_rewrite_rules();
	}
);

/*
 * Deactivated while AJR Core no longer registers case studies, the stored /case-studies/ rules
 * would outlive the type and send its addresses to the home page with a 200 instead of a 404.
 * Unregister first so the flush leaves them out. While Core still serves the type, Core's own
 * registration stands and nothing is unregistered.
 */
register_deactivation_hook(
	__FILE__,
	static function () {
		if ( ! \AJR\SiteCore\Core\CoreModules::serves( \AJR\SiteCore\CaseStudies\CaseStudies::CORE_MODULE ) && post_type_exists( \AJR\SiteCore\CaseStudies\CaseStudies::POST_TYPE ) ) {
			unregister_post_type( \AJR\SiteCore\CaseStudies\CaseStudies::POST_TYPE );
		}
		delete_option( \AJR\SiteCore\CaseStudies\CaseStudies::REWRITE_OPTION );
		flush_rewrite_rules();
	}
);

add_action(
	'plugins_loaded',
	static function () {
		\AJR\SiteCore\Core\Plugin::instance()->init();
	}
);
