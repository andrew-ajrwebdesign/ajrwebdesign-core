<?php
/**
 * ProfessionalService structured data.
 *
 * @package AJR\SiteCore
 */

namespace AJR\SiteCore\Schema;

defined( 'ABSPATH' ) || exit;

/**
 * Emits the schema.org ProfessionalService entity for the business on the
 * front page (every language's home, via Polylang's per-language front
 * page handling). This is the machine-readable NAP anchor for the local
 * search play: the same identity Google reads from the Impressum and the
 * Business Profile. Business facts live here as constants — this plugin is
 * site-specific by design; a reusable schema injector belongs to the
 * AI SEO Assistant roadmap, not here.
 */
class Business {

	/**
	 * Stable entity id fragment, referenced from other nodes if needed.
	 */
	public const ENTITY_ID = '#business';

	/**
	 * Hooks the head output.
	 */
	public function register(): void {
		add_action( 'wp_head', array( $this, 'output_schema' ) );
	}

	/**
	 * Prints the ProfessionalService JSON-LD on front pages only.
	 */
	public function output_schema(): void {
		if ( ! is_front_page() ) {
			return;
		}

		$node = array(
			'@context'      => 'https://schema.org',
			'@type'         => 'ProfessionalService',
			'@id'           => home_url( '/' ) . self::ENTITY_ID,
			'name'          => 'AJR Web Design',
			'alternateName' => 'ajrwebdesign',
			'url'           => home_url( '/' ),
			'email'         => 'andrew@ajrwebdesign.com',
			'telephone'     => '+49-176-80305750',
			'founder'       => array(
				'@type' => 'Person',
				'name'  => 'Andrew Ryan',
			),
			'address'       => array(
				'@type'           => 'PostalAddress',
				'streetAddress'   => 'Tennenbacher Str. 50a',
				'postalCode'      => '79106',
				'addressLocality' => 'Freiburg im Breisgau',
				'addressCountry'  => 'DE',
			),
			'areaServed'    => array(
				array(
					'@type' => 'City',
					'name'  => 'Freiburg im Breisgau',
				),
				// Remote WordPress work has no geographic boundary.
				'Worldwide',
			),
			'knowsLanguage' => array( 'en', 'de' ),
			'sameAs'        => array(
				'https://www.linkedin.com/in/ajrwebdesign/',
			),
		);

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wp_json_encode output.
		echo '<script type="application/ld+json">' . wp_json_encode( $node, JSON_UNESCAPED_SLASHES ) . '</script>' . "\n";
	}
}
