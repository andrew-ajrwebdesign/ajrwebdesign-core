<?php
/**
 * Stand-ins for the AJR Core pieces Core\Requirements reads, for the Unit suite.
 *
 * Requirements warns when the Featured Work list's filter is ignored, which depends on AJR
 * Core's Config and Private_Taxonomy_Query. Without these the warning could never print in a
 * test, so no test could fail on it (code-standards review, 2026-10-01). Tests set the static
 * values and reset them in setUp().
 *
 * @package AJR\SiteCore
 */

// phpcs:disable
namespace AJR\Core\Framework {
	if ( ! class_exists( Config::class ) ) {
		class Config {
			public static array $values = array();

			public static function get( string $key, $fallback = null ) {
				return self::$values[ $key ] ?? $fallback;
			}
		}
	}
}

namespace AJR\Core\Site {
	if ( ! class_exists( Private_Taxonomy_Query::class ) ) {
		class Private_Taxonomy_Query {
			public static array $list = array();

			public static function taxonomies(): array {
				return self::$list;
			}
		}
	}
}

namespace {
	if ( ! class_exists( 'WP_Term' ) ) {
		class WP_Term {
			public $count = 0;

			public function __construct( int $count = 0 ) {
				$this->count = $count;
			}
		}
	}

	// Terms by taxonomy and slug, set per test.
	$GLOBALS['ajrwd_test_terms'] = array();

	if ( ! function_exists( 'get_term_by' ) ) {
		function get_term_by( $field, $value, $taxonomy ) {
			return $GLOBALS['ajrwd_test_terms'][ $taxonomy ][ $value ] ?? false;
		}
	}

	if ( ! function_exists( 'esc_html__' ) ) {
		function esc_html__( $text, $domain = 'default' ) {
			return esc_html( $text );
		}
	}
}
// phpcs:enable
