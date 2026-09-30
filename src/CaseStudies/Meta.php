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
	 * What kind of case study this is: an audit (before → after numbers, the
	 * original four) or a site build (screenshots and the finished site's scores).
	 */
	public const KIND = 'ajrwd_cs_kind';

	/**
	 * The site-build fields, one object: live URL, results intro, PageSpeed
	 * scores, comparison rows, headline facts and the source line.
	 */
	public const BUILD = 'ajrwd_cs_build';

	/**
	 * Screenshot attachment IDs, in display order. Landscape images are shown in
	 * a browser frame, portrait ones in a phone frame.
	 */
	public const GALLERY = 'ajrwd_cs_gallery';

	/**
	 * Kind: an audit. The default, and what every case study was before 1.10.0.
	 */
	public const KIND_AUDIT = 'audit';

	/**
	 * Kind: a site build.
	 */
	public const KIND_BUILD = 'build';

	/**
	 * The four PageSpeed categories, per device.
	 *
	 * @var string[]
	 */
	public const SCORE_KEYS = array( 'performance', 'accessibility', 'best_practices', 'seo' );

	/**
	 * The most comparison rows, facts and screenshots one case study keeps.
	 * More than this stops being a highlight and starts being a report.
	 */
	public const MAX_COMPARE = 3;
	public const MAX_FACTS   = 4;
	public const MAX_GALLERY = 8;

	/**
	 * The longest a row's text may be. It is scorecard text, not a paragraph.
	 */
	public const MAX_ROW_TEXT = 140;

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
		add_filter( 'rest_prepare_' . PostType::POST_TYPE, array( $this, 'hide_protected_meta' ), 10, 3 );
	}

	/**
	 * The fields this plugin puts in the REST API, which a password must also guard.
	 *
	 * @var string[]
	 */
	protected const REST_KEYS = array( self::EYEBROW, self::SUMMARY, self::METRICS, self::IMPACT, self::TITLE_DE, self::EYEBROW_DE, self::SUMMARY_DE );

	/**
	 * A password-protected case study's fields stay behind its password in the REST API too.
	 *
	 * WordPress blanks a protected post's content and excerpt in REST but returns registered
	 * meta without asking for the password, so the summary and the numbers were readable at
	 * /wp/v2/ajr_case_study/<id> (security review, 2026-09-30). AJR Core does this for its own
	 * three fields; this is the same guard for this plugin's. Someone who may edit the case
	 * study still gets them, so the editor keeps working.
	 *
	 * @param mixed $response The REST response.
	 * @param mixed $post     The case study.
	 * @param mixed $request  The REST request.
	 * @return mixed
	 */
	public function hide_protected_meta( $response, $post, $request ) {
		if ( ! $response instanceof \WP_REST_Response || ! $post instanceof \WP_Post ) {
			return $response;
		}
		$context = $request instanceof \WP_REST_Request ? (string) $request->get_param( 'context' ) : 'view';
		if ( ! post_password_required( $post ) || ( 'edit' === $context && current_user_can( 'edit_post', $post->ID ) ) ) {
			return $response;
		}

		$data = $response->get_data();
		if ( is_array( $data ) && isset( $data['meta'] ) && is_array( $data['meta'] ) ) {
			foreach ( self::REST_KEYS as $key ) {
				unset( $data['meta'][ $key ] );
			}
			$response->set_data( $data );
		}

		return $response;
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

		// The site-build fields are edited in the metabox and read only by the
		// server-rendered blocks, so they stay OUT of the REST API: registered
		// meta is returned for a password-protected post without its password.
		register_post_meta(
			PostType::POST_TYPE,
			self::KIND,
			array(
				'type'              => 'string',
				'single'            => true,
				'default'           => self::KIND_AUDIT,
				'sanitize_callback' => array( self::class, 'sanitize_kind' ),
				'show_in_rest'      => false,
				'auth_callback'     => $auth,
			)
		);

		register_post_meta(
			PostType::POST_TYPE,
			self::BUILD,
			array(
				'type'              => 'object',
				'single'            => true,
				'sanitize_callback' => array( self::class, 'sanitize_build' ),
				'show_in_rest'      => false,
				'auth_callback'     => $auth,
			)
		);

		register_post_meta(
			PostType::POST_TYPE,
			self::GALLERY,
			array(
				'type'              => 'array',
				'single'            => true,
				'sanitize_callback' => array( self::class, 'sanitize_gallery' ),
				'show_in_rest'      => false,
				'auth_callback'     => $auth,
			)
		);
	}

	/**
	 * The kind, limited to the two this plugin renders. Anything else is an audit.
	 *
	 * @param mixed $value Raw meta value.
	 */
	public static function sanitize_kind( $value ): string {
		return self::KIND_BUILD === $value ? self::KIND_BUILD : self::KIND_AUDIT;
	}

	/**
	 * Empty site-build structure.
	 *
	 * @return array<string,mixed>
	 */
	public static function empty_build(): array {
		$device = array_fill_keys( self::SCORE_KEYS, '' );
		return array(
			'url'           => '',
			'intro'         => '',
			'scores'        => array(
				'mobile'  => $device,
				'desktop' => $device,
			),
			'compare_title' => '',
			'compare'       => array(),
			'facts'         => array(),
			'source'        => '',
		);
	}

	/**
	 * One line of scorecard text: plain, single-line, capped.
	 *
	 * @param mixed $value Raw value.
	 */
	protected static function row_text( $value ): string {
		return is_scalar( $value ) ? mb_substr( sanitize_text_field( (string) $value ), 0, self::MAX_ROW_TEXT ) : '';
	}

	/**
	 * Sanitizes the site-build object, dropping unknown keys and half-filled rows.
	 *
	 * A score is a whole number from 0 to 100 or empty. A comparison row is kept
	 * only with a label and a value; its bar length is a whole percentage from 0
	 * (no bar) to 100. A fact is kept only with a value and a label.
	 *
	 * @param mixed $value Raw meta value.
	 * @return array<string,mixed>
	 */
	public static function sanitize_build( $value ): array {
		$clean = self::empty_build();
		if ( ! is_array( $value ) ) {
			return $clean;
		}

		if ( isset( $value['url'] ) && is_scalar( $value['url'] ) ) {
			$clean['url'] = esc_url_raw( trim( (string) $value['url'] ), array( 'http', 'https' ) );
		}
		foreach ( array( 'intro', 'compare_title', 'source' ) as $key ) {
			$clean[ $key ] = self::row_text( $value[ $key ] ?? '' );
		}

		foreach ( array( 'mobile', 'desktop' ) as $device ) {
			foreach ( self::SCORE_KEYS as $category ) {
				$raw = $value['scores'][ $device ][ $category ] ?? '';
				$raw = is_scalar( $raw ) ? trim( (string) $raw ) : '';
				if ( '' !== $raw && ctype_digit( $raw ) ) {
					$clean['scores'][ $device ][ $category ] = (string) min( 100, (int) $raw );
				}
			}
		}

		foreach ( is_array( $value['compare'] ?? null ) ? $value['compare'] : array() as $row ) {
			if ( ! is_array( $row ) || count( $clean['compare'] ) >= self::MAX_COMPARE ) {
				continue;
			}
			$label  = self::row_text( $row['label'] ?? '' );
			$figure = self::row_text( $row['value'] ?? '' );
			if ( '' === $label || '' === $figure ) {
				continue;
			}
			// 0 means "no bar": a row left without a bar length must not draw a sliver
			// that claims this site is 1% of typical.
			$percent            = isset( $row['percent'] ) && is_numeric( $row['percent'] ) ? (int) round( (float) $row['percent'] ) : 0;
			$clean['compare'][] = array(
				'label'   => $label,
				'value'   => $figure,
				'percent' => max( 0, min( 100, $percent ) ),
				'note'    => self::row_text( $row['note'] ?? '' ),
			);
		}

		foreach ( is_array( $value['facts'] ?? null ) ? $value['facts'] : array() as $row ) {
			if ( ! is_array( $row ) || count( $clean['facts'] ) >= self::MAX_FACTS ) {
				continue;
			}
			$figure = self::row_text( $row['value'] ?? '' );
			$label  = self::row_text( $row['label'] ?? '' );
			if ( '' === $figure || '' === $label ) {
				continue;
			}
			$clean['facts'][] = array(
				'value' => $figure,
				'label' => $label,
				'note'  => self::row_text( $row['note'] ?? '' ),
			);
		}

		return $clean;
	}

	/**
	 * Sanitizes the screenshot list: positive attachment IDs, no repeats, capped.
	 *
	 * Accepts the stored array or the metabox's comma-separated field.
	 *
	 * @param mixed $value Raw meta value.
	 * @return int[]
	 */
	public static function sanitize_gallery( $value ): array {
		if ( is_string( $value ) ) {
			$value = preg_split( '/[\s,]+/', $value, -1, PREG_SPLIT_NO_EMPTY );
		}
		if ( ! is_array( $value ) ) {
			return array();
		}

		$ids = array();
		foreach ( $value as $id ) {
			$id = is_scalar( $id ) ? (int) $id : 0;
			if ( $id > 0 && ! isset( $ids[ $id ] ) ) {
				$ids[ $id ] = $id;
			}
			if ( count( $ids ) >= self::MAX_GALLERY ) {
				break;
			}
		}

		return array_values( $ids );
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
