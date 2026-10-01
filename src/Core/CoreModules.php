<?php
/**
 * CoreModules — whether an AJR Core release on this site still registers a content type.
 *
 * Testimonials and case studies lived in AJR Core's Testimonials and Case studies modules from
 * Core 0.11/0.12 until 0.16.0, when Andrew decided (2026-10-01) that every content type belongs
 * in the site's own plugin: "any cpts need to be build into a per client pluign". This plugin
 * registers them again, under the same keys.
 *
 * While an older AJR Core still has the module switched ON, Core registers the type and its
 * blocks, so this plugin must stand down: two registrations of one block name print a notice
 * and the second loses, and two post type registrations race on their arguments. That makes the
 * release safe in either order: this plugin can go live first and do nothing, and it takes over
 * the moment the module is switched off or Core is updated to a release without it.
 *
 * Read once per request when the modules register (plugins_loaded), from Core's settings row,
 * which is autoloaded: no query.
 *
 * @package AJR\SiteCore
 */

namespace AJR\SiteCore\Core;

defined( 'ABSPATH' ) || exit;

/**
 * Asks AJR Core whether one of its modules is on.
 */
class CoreModules {

	/**
	 * Whether AJR Core is active and serving this module itself.
	 *
	 * False when Core is missing, and when Core no longer knows the module: from 0.16.0
	 * `Modules::enabled()` returns false for an id it does not declare.
	 *
	 * @param string $module Core module id, e.g. "testimonials" or "case_studies".
	 */
	public static function serves( string $module ): bool {
		if ( ! class_exists( '\AJR\Core\Framework\Modules' ) || ! method_exists( '\AJR\Core\Framework\Modules', 'enabled' ) ) {
			return false;
		}

		return (bool) \AJR\Core\Framework\Modules::enabled( $module );
	}
}
