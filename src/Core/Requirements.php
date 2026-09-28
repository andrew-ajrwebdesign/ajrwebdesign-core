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
	 * The admin notice, for people who can fix it.
	 */
	public function notice(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
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
