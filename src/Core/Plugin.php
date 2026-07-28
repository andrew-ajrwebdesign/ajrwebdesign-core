<?php
/**
 * Plugin singleton — wires every module together.
 *
 * @package AJR\SiteCore
 */

namespace AJR\SiteCore\Core;

use AJR\SiteCore\Admin\Settings;
use AJR\SiteCore\Analytics\GA4;
use AJR\SiteCore\Blocks\Registrar;
use AJR\SiteCore\CaseStudies\Meta;
use AJR\SiteCore\CaseStudies\Migration;
use AJR\SiteCore\CaseStudies\PostType;
use AJR\SiteCore\Compat\ThemeSupport;
use AJR\SiteCore\I18n\Strings;
use AJR\SiteCore\Posts\Meta as PostsMeta;
use AJR\SiteCore\Testimonials\PostType as Testimonials;

defined( 'ABSPATH' ) || exit;

/**
 * Central bootstrap. Instantiates each feature module and calls its
 * register() method. Hooks are never added in constructors so modules
 * stay testable in isolation.
 */
class Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var Plugin|null
	 */
	private static ?Plugin $instance = null;

	/**
	 * Plugin settings, loaded once and injected into modules.
	 *
	 * @var Settings
	 */
	private Settings $settings;

	/**
	 * Returns the shared instance.
	 */
	public static function instance(): self {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Intentionally empty — all wiring happens in init().
	 */
	private function __construct() {}

	/**
	 * Instantiates and registers every module.
	 */
	public function init(): void {
		add_action(
			'init',
			static function () {
				load_plugin_textdomain( 'ajrwebdesign-core', false, dirname( AJRWD_CORE_BASENAME ) . '/languages' );
			}
		);

		$this->settings = new Settings();
		$this->settings->register();

		$modules = array(
			new Registrar(),
			new ThemeSupport(),
			new Strings(),
			new PostsMeta(),
			new Testimonials(),
			new GA4( $this->settings ),
		);

		if ( $this->settings->is_enabled( 'case_studies_cpt' ) ) {
			$modules[] = new PostType();
			$modules[] = new Meta();
			$modules[] = new Migration();
		}

		foreach ( $modules as $module ) {
			$module->register();
		}
	}

	/**
	 * Activation routine: seed defaults, register the CPT once, flush rewrites.
	 */
	public static function activate(): void {
		if ( false === get_option( Settings::OPTION ) ) {
			add_option( Settings::OPTION, Settings::defaults() );
		}

		// Register the CPT so its rewrite rules exist before flushing.
		$settings = new Settings();
		if ( $settings->is_enabled( 'case_studies_cpt' ) ) {
			( new PostType() )->register_post_type();
		}
		flush_rewrite_rules();
	}
}
