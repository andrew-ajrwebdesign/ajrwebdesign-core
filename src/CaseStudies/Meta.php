<?php
/**
 * Structured case-study meta.
 *
 * @package AJR\SiteCore
 */

namespace AJR\SiteCore\CaseStudies;

defined( 'ABSPATH' ) || exit;

/**
 * Replaces the legacy plugin's 22 loose string meta keys with four structured
 * fields, all REST-exposed so the card blocks and the editor sidebar panel
 * read the same source of truth.
 *
 * Values stay strings (they carry units: "1.4s", "-62%", "Passed").
 */
class Meta {

	public const EYEBROW = 'ajrwd_cs_eyebrow';
	public const SUMMARY = 'ajrwd_cs_summary';
	public const METRICS = 'ajrwd_cs_metrics';
	public const IMPACT  = 'ajrwd_cs_impact';

	// German translations — testimonials single-entry model: one post serves
	// both languages, the card blocks pick these on German pages and fall
	// back to the English fields when empty. Metrics/impact stay shared.
	public const TITLE_DE   = 'ajrwd_cs_title_de';
	public const EYEBROW_DE = 'ajrwd_cs_eyebrow_de';
	public const SUMMARY_DE = 'ajrwd_cs_summary_de';

	/**
	 * The metric keys tracked per device/phase.
	 *
	 * @var string[]
	 */
	public const METRIC_KEYS = array( 'score', 'lcp', 'cls', 'inp' );

	/**
	 * Hooks meta registration.
	 */
	public function register(): void {
		add_action( 'init', array( $this, 'register_meta' ) );
	}

	/**
	 * Registers the four structured fields.
	 */
	public function register_meta(): void {
		$auth = static function () {
			return current_user_can( 'edit_posts' );
		};

		foreach ( array( self::EYEBROW, self::SUMMARY, self::TITLE_DE, self::EYEBROW_DE, self::SUMMARY_DE ) as $key ) {
			register_post_meta(
				PostType::POST_TYPE,
				$key,
				array(
					'type'              => 'string',
					'single'            => true,
					'default'           => '',
					'sanitize_callback' => 'sanitize_text_field',
					'show_in_rest'      => true,
					'auth_callback'     => $auth,
				)
			);
		}

		register_post_meta(
			PostType::POST_TYPE,
			self::METRICS,
			array(
				'type'              => 'object',
				'single'            => true,
				'default'           => self::empty_metrics(),
				'sanitize_callback' => array( $this, 'sanitize_metrics' ),
				'show_in_rest'      => array(
					'schema' => array(
						'type'                 => 'object',
						'properties'           => self::metrics_schema(),
						'additionalProperties' => false,
					),
				),
				'auth_callback'     => $auth,
			)
		);

		register_post_meta(
			PostType::POST_TYPE,
			self::IMPACT,
			array(
				'type'              => 'object',
				'single'            => true,
				'default'           => self::empty_impact(),
				'sanitize_callback' => array( $this, 'sanitize_impact' ),
				'show_in_rest'      => array(
					'schema' => array(
						'type'                 => 'object',
						'properties'           => array(
							'cwv_before'        => array( 'type' => 'string' ),
							'cwv_after'         => array( 'type' => 'string' ),
							'requests_removed'  => array( 'type' => 'string' ),
							'page_size_reduced' => array( 'type' => 'string' ),
						),
						'additionalProperties' => false,
					),
				),
				'auth_callback'     => $auth,
			)
		);
	}

	/**
	 * REST schema for the metrics object: mobile/desktop × before/after × 4 metrics.
	 *
	 * @return array<string,mixed>
	 */
	private static function metrics_schema(): array {
		$phase  = array(
			'type'                 => 'object',
			'properties'           => array_fill_keys( self::METRIC_KEYS, array( 'type' => 'string' ) ),
			'additionalProperties' => false,
		);
		$device = array(
			'type'                 => 'object',
			'properties'           => array(
				'before' => $phase,
				'after'  => $phase,
			),
			'additionalProperties' => false,
		);
		return array(
			'mobile'  => $device,
			'desktop' => $device,
		);
	}

	/**
	 * Empty metrics structure.
	 *
	 * @return array<string,array<string,array<string,string>>>
	 */
	public static function empty_metrics(): array {
		$phase = array_fill_keys( self::METRIC_KEYS, '' );
		return array(
			'mobile'  => array(
				'before' => $phase,
				'after'  => $phase,
			),
			'desktop' => array(
				'before' => $phase,
				'after'  => $phase,
			),
		);
	}

	/**
	 * Empty impact structure.
	 *
	 * @return array<string,string>
	 */
	public static function empty_impact(): array {
		return array(
			'cwv_before'        => '',
			'cwv_after'         => '',
			'requests_removed'  => '',
			'page_size_reduced' => '',
		);
	}

	/**
	 * Sanitizes the metrics object, dropping unknown keys.
	 *
	 * @param mixed $value Raw meta value.
	 * @return array<string,array<string,array<string,string>>>
	 */
	public function sanitize_metrics( $value ): array {
		$clean = self::empty_metrics();
		if ( ! is_array( $value ) ) {
			return $clean;
		}
		foreach ( array( 'mobile', 'desktop' ) as $device ) {
			foreach ( array( 'before', 'after' ) as $phase ) {
				foreach ( self::METRIC_KEYS as $metric ) {
					if ( isset( $value[ $device ][ $phase ][ $metric ] ) ) {
						$clean[ $device ][ $phase ][ $metric ] = sanitize_text_field( (string) $value[ $device ][ $phase ][ $metric ] );
					}
				}
			}
		}
		return $clean;
	}

	/**
	 * Sanitizes the impact object, dropping unknown keys.
	 *
	 * @param mixed $value Raw meta value.
	 * @return array<string,string>
	 */
	public function sanitize_impact( $value ): array {
		$clean = self::empty_impact();
		if ( ! is_array( $value ) ) {
			return $clean;
		}
		foreach ( array_keys( $clean ) as $key ) {
			if ( isset( $value[ $key ] ) ) {
				$clean[ $key ] = sanitize_text_field( (string) $value[ $key ] );
			}
		}
		return $clean;
	}
}
