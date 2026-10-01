<?php
/**
 * Plugin singleton — wires every module together.
 *
 * @package AJR\SiteCore
 */

namespace AJR\SiteCore\Core;

use AJR\SiteCore\Blocks\BlockVersion;
use AJR\SiteCore\Blocks\Registrar;
use AJR\SiteCore\CaseStudies\BuildCopy;
use AJR\SiteCore\CaseStudies\CaseStudies;
use AJR\SiteCore\CaseStudies\Meta;
use AJR\SiteCore\CaseStudies\Metabox;
use AJR\SiteCore\CaseStudies\OptionalBand;
use AJR\SiteCore\CaseStudies\PrivateTagQuery;
use AJR\SiteCore\CaseStudies\StoryIntro;
use AJR\SiteCore\Compat\ThemeSupport;
use AJR\SiteCore\I18n\AttachmentAlt;
use AJR\SiteCore\I18n\Hreflang;
use AJR\SiteCore\I18n\Strings;
use AJR\SiteCore\Posts\Meta as PostsMeta;
use AJR\SiteCore\Seo\CaseStudies as CaseStudiesSeo;
use AJR\SiteCore\Testimonials\German as TestimonialsGerman;
use AJR\SiteCore\Testimonials\Testimonials;

defined( 'ABSPATH' ) || exit;

/**
 * Central bootstrap. Instantiates each feature module and calls its
 * register() method. Hooks are never added in constructors so modules
 * stay testable in isolation.
 *
 * The plugin has no settings page. Since 1.15.0 it registers this site's two content types
 * again (testimonials, case studies), which lived in AJR Core from 1.8.0/1.9.0 until Andrew
 * decided content types belong in each site's own plugin (2026-10-01); while an older AJR Core
 * still has those modules on, they stand down (Core\CoreModules). The activation hook in the
 * main plugin file registers the case-study type before flushing rewrite rules.
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
			new BlockVersion( AJRWD_CORE_VERSION ),
			new Registrar(),
			new \AJR\SiteCore\Blocks\ImageSizes(),
			new ThemeSupport(),
			new Strings(),
			new Hreflang(),
			// "Alt text (German)" on images, for posts shown in both languages.
			new AttachmentAlt(),
			new PostsMeta(),
			// The two content types and their blocks (stand down while AJR Core serves them).
			new Testimonials(),
			new CaseStudies(),
			// Query Loops filtered by case-study tag (Featured Work, the Case Studies lists).
			new PrivateTagQuery(),
			new TestimonialsGerman(),
			// This site's layer on the case-study type: Core Web Vitals fields, their
			// metabox, the case-study SEO tweaks and the story intro.
			new Meta(),
			new Metabox(),
			new CaseStudiesSeo(),
			new StoryIntro(),
			// The site-build kind of case study: its results-band wording. Its fields are
			// in Meta and Metabox; Blocks\Build renders it inside the case-study-card block.
			new BuildCopy(),
			// Drops a band of the single that has nothing to show for this case study.
			new OptionalBand(),
		);

		foreach ( $modules as $module ) {
			$module->register();
		}
	}
}
