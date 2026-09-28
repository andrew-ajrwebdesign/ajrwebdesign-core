<?php
/**
 * Plugin singleton — wires every module together.
 *
 * @package AJR\SiteCore
 */

namespace AJR\SiteCore\Core;

use AJR\SiteCore\Blocks\BlockVersion;
use AJR\SiteCore\Blocks\Registrar;
use AJR\SiteCore\CaseStudies\Meta;
use AJR\SiteCore\CaseStudies\Metabox;
use AJR\SiteCore\CaseStudies\StoryIntro;
use AJR\SiteCore\Compat\ThemeSupport;
use AJR\SiteCore\I18n\Hreflang;
use AJR\SiteCore\I18n\Strings;
use AJR\SiteCore\Posts\Meta as PostsMeta;
use AJR\SiteCore\Seo\CaseStudies as CaseStudiesSeo;
use AJR\SiteCore\Testimonials\German as TestimonialsGerman;

defined( 'ABSPATH' ) || exit;

/**
 * Central bootstrap. Instantiates each feature module and calls its
 * register() method. Hooks are never added in constructors so modules
 * stay testable in isolation.
 *
 * Since 1.9.0 the plugin has no settings page and no activation routine: its last setting
 * ("enable case studies") became AJR Core's Case studies module, and AJR Core registers the
 * post types and owns their rewrite rules.
 */
class Plugin {

	/**
	 * Singleton instance.
	 *
	 * @var Plugin|null
	 */
	private static ?Plugin $instance = null;

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

		$modules = array(
			// Warns in wp-admin when AJR Core's Case studies or Testimonials module is off.
			new Requirements(),
			new BlockVersion( AJRWD_CORE_VERSION ),
			new Registrar(),
			new \AJR\SiteCore\Blocks\ImageSizes(),
			new ThemeSupport(),
			new Strings(),
			new Hreflang(),
			new PostsMeta(),
			new TestimonialsGerman(),
			// This site's layer on AJR Core's case-study type: Core Web Vitals fields, their
			// metabox, the case-study SEO tweaks and the story intro.
			new Meta(),
			new Metabox(),
			new CaseStudiesSeo(),
			new StoryIntro(),
		);

		foreach ( $modules as $module ) {
			$module->register();
		}
	}
}
