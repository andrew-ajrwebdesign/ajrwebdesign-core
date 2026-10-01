<?php
/**
 * Stand-in for AJR Core's module registry, so Core\CoreModules can be tested both ways.
 *
 * Only the one method this plugin calls. `$on` lists the module ids a test switches on; tests
 * reset it in tearDown(). Defined only when the real class is absent (it always is here: AJR
 * Core is not a dependency of this plugin's test suite).
 *
 * @package AJR\SiteCore
 */

namespace AJR\Core\Framework;

if ( ! class_exists( Modules::class ) ) {
	/**
	 * AJR Core's Modules, reduced to enabled().
	 */
	class Modules {

		/**
		 * Module ids switched on for the current test.
		 *
		 * @var array<int,string>
		 */
		public static array $on = array();

		/**
		 * Whether a module is on.
		 *
		 * @param string $id Module id.
		 */
		public static function enabled( string $id ): bool {
			return in_array( $id, self::$on, true );
		}
	}
}
