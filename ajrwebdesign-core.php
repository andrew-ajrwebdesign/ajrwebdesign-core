<?php
/**
 * Plugin Name:       AJR Web Design Core
 * Plugin URI:        https://github.com/andrew-ajrwebdesign/ajrwebdesign-core
 * Description:       Core functionality for ajrwebdesign.com — custom blocks, case studies, analytics, and multilingual helpers. Companion to the ajrwebdesign-theme FSE theme.
 * Version:           1.1.3
 * Requires at least: 6.9
 * Requires PHP:      8.0
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

define( 'AJRWD_CORE_VERSION', '1.1.3' );
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

add_action(
	'plugins_loaded',
	static function () {
		\AJR\SiteCore\Core\Plugin::instance()->init();
	}
);

register_activation_hook(
	__FILE__,
	static function () {
		\AJR\SiteCore\Core\Plugin::activate();
	}
);

register_deactivation_hook(
	__FILE__,
	static function () {
		flush_rewrite_rules();
	}
);
