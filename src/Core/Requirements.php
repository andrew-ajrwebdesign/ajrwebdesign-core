<?php
/**
 * Requirements — what this plugin needs from AJR Core, and a warning when it is missing.
 *
 * One thing: the Featured Work list's filter (below). Until 1.15.0 this also warned when Core's
 * Case studies or Testimonials module was off, because Core registered those types; this plugin
 * registers them now, so a module being off is the normal state.
 *
 * @package AJR\SiteCore
 */

namespace AJR\SiteCore\Core;

use AJR\SiteCore\CaseStudies\PostType;

defined( 'ABSPATH' ) || exit;

/**
 * Warns when AJR Core is not set up for this site's Featured Work list.
 */
class Requirements {

	/**
	 * Hook in.
	 */
	public function register(): void {
		add_action( 'admin_notices', array( $this, 'notice' ) );
	}

	/**
	 * Whether the Featured Work list's filter is being ignored.
	 *
	 * The list is a Query Loop filtered to case studies tagged "featured". That
	 * taxonomy has no public pages, and WordPress silently drops a Query Loop
	 * filter on such a taxonomy: the list then shows the newest case studies
	 * whatever their tags, which looks plausible and reports nothing. AJR Core's
	 * private-taxonomies module restores the filter, but only when it is switched
	 * on AND lists `case_study_tag`. This is true when a case study is tagged
	 * "featured" and either half of that is missing.
	 */
	public function featured_filter_off(): bool {
		if ( ! taxonomy_exists( PostType::TAXONOMY ) || ! class_exists( '\AJR\Core\Site\Private_Taxonomy_Query' ) || ! class_exists( '\AJR\Core\Framework\Config' ) ) {
			return false;
		}

		$term = get_term_by( 'slug', 'featured', PostType::TAXONOMY );
		if ( ! $term instanceof \WP_Term || $term->count < 1 ) {
			return false;
		}

		return ! \AJR\Core\Framework\Config::get( 'modules.private_taxonomies', false )
			|| ! in_array( PostType::TAXONOMY, \AJR\Core\Site\Private_Taxonomy_Query::taxonomies(), true );
	}

	/**
	 * The admin notices, for people who can fix them.
	 */
	public function notice(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		if ( $this->featured_filter_off() ) {
			printf(
				'<div class="notice notice-warning"><p>%s</p></div>',
				esc_html__( 'The Featured Work list is showing every case study, not only the ones tagged “featured”. WordPress ignores a Query Loop filter on a taxonomy with no public pages. To fix it: switch on “Query Loop: filter by private taxonomies” under AJR Core → Modules, then add case_study_tag to the private taxonomies on AJR Core → Blocks.', 'ajrwebdesign-core' )
			);
		}
	}
}
