<?php
/**
 * Requirements — what this plugin needs from AJR Core, and a warning when it is missing.
 *
 * Since 1.8.0 (testimonials) and 1.9.0 (case studies) the post types this plugin layers on are
 * registered by AJR Core, and only while Core's matching module is switched on. `Requires
 * Plugins: ajr-core` guarantees Core is active, not that the modules are on. With a module off,
 * the posts vanish from the admin and every /case-studies/ URL returns 404, silently. This
 * class makes that loud: an admin notice naming the module to switch on.
 *
 * @package AJR\SiteCore
 */

namespace AJR\SiteCore\Core;

use AJR\SiteCore\CaseStudies\PostType;
use AJR\SiteCore\Testimonials\German;

defined( 'ABSPATH' ) || exit;

/**
 * Warns when AJR Core's modules are off.
 */
class Requirements {

	/**
	 * Hook in.
	 */
	public function register(): void {
		add_action( 'admin_notices', array( $this, 'notice' ) );
	}

	/**
	 * The AJR Core modules whose post type is not registered, by their name in Core's Modules page.
	 *
	 * @return array<int,string>
	 */
	public function missing(): array {
		$modules = array(
			PostType::POST_TYPE => __( 'Case studies', 'ajrwebdesign-core' ),
			German::POST_TYPE   => __( 'Testimonials', 'ajrwebdesign-core' ),
		);

		$missing = array();
		foreach ( $modules as $post_type => $label ) {
			if ( ! post_type_exists( $post_type ) ) {
				$missing[] = $label;
			}
		}

		return $missing;
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

		$missing = $this->missing();
		if ( array() === $missing ) {
			return;
		}

		printf(
			'<div class="notice notice-error"><p>%s</p></div>',
			esc_html(
				sprintf(
					/* translators: %s: comma-separated module names, e.g. "Case studies, Testimonials". */
					__( 'AJR Web Design Core needs these AJR Core modules switched on: %s. Turn them on under AJR Core → Modules. Until then those posts are missing from the admin and their pages return 404.', 'ajrwebdesign-core' ),
					implode( ', ', $missing )
				)
			)
		);
	}
}
