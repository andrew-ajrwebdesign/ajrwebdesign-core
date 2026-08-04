<?php
/**
 * Shared helpers for the case-study card blocks.
 *
 * @package AJR\SiteCore
 */

namespace AJR\SiteCore\Blocks;

use AJR\SiteCore\CaseStudies\Meta;
use AJR\SiteCore\Core\Utils;

defined( 'ABSPATH' ) || exit;

/**
 * Render helpers used by case-study-card and case-study-mini-card.
 * Ported from the legacy plugin's procedural render files so frontend
 * markup stays identical; data now comes from the structured meta with a
 * transparent fallback to the legacy flat keys (pre-migration content).
 */
class Cards {

	/**
	 * Icons that may be inlined.
	 *
	 * @var string[]
	 */
	private const ALLOWED_ICONS = array(
		'device-mobile',
		'device-desktop',
		'trend-up',
		'cube',
		'gauge',
		'arrow-right',
		'metric-before',
		'metric-after',
		'core-web-vitals',
		'page-size-reduced',
	);

	/**
	 * Icon cache for the request.
	 *
	 * @var array<string,string>
	 */
	private static array $icon_cache = array();

	/**
	 * German versions of the cards' fixed interface strings. Terms that are
	 * used untranslated in German (LCP, CLS, INP, Core Web Vitals, Desktop)
	 * have no entry and pass through unchanged.
	 *
	 * @var array<string,string>
	 */
	private const LABELS_DE = array(
		'Mobile'            => 'Mobil',
		'Before'            => 'Vorher',
		'After'             => 'Nachher',
		'Requests Removed'  => 'Requests entfernt',
		'Page Size Reduced' => 'Seitengröße reduziert',
		'Performance Score' => 'Performance-Score',
		'LCP Improvement'   => 'LCP-Verbesserung',
		'Case study tags'   => 'Fallstudien-Schlagwörter',
		'Passed'            => 'Bestanden',
		'Failed'            => 'Nicht bestanden',
	);

	/**
	 * Whether the current request renders a German page.
	 */
	public static function is_de(): bool {
		return 'de' === Utils::current_language();
	}

	/**
	 * Returns a card interface string in the visitor's language.
	 *
	 * The cards' fixed strings can't go through gettext (the plugin ships no
	 * catalogs); like the testimonials slider, they switch on the Polylang
	 * language directly. English (and any unmapped string) passes through.
	 *
	 * @param string $text English source string.
	 */
	public static function ui_label( string $text ): string {
		if ( self::is_de() && isset( self::LABELS_DE[ $text ] ) ) {
			return self::LABELS_DE[ $text ];
		}
		return $text;
	}

	/**
	 * Returns a flattened, legacy-compatible meta view of a case study:
	 * title, eyebrow, summary, metrics[device][phase][metric], impact[...].
	 *
	 * On German pages the title/eyebrow/summary come from the *_de meta
	 * fields when filled (testimonials single-entry model); empty German
	 * fields fall back to the English values. Metrics are shared.
	 *
	 * @param int $post_id Case study ID.
	 * @return array<string,mixed>
	 */
	public static function get_case_meta( int $post_id ): array {
		$metrics = get_post_meta( $post_id, Meta::METRICS, true );
		$metrics = is_array( $metrics ) ? array_replace_recursive( Meta::empty_metrics(), $metrics ) : Meta::empty_metrics();
		$impact  = get_post_meta( $post_id, Meta::IMPACT, true );
		$impact  = is_array( $impact ) ? array_merge( Meta::empty_impact(), $impact ) : Meta::empty_impact();

		$data = array(
			'title'   => (string) get_the_title( $post_id ),
			'eyebrow' => (string) get_post_meta( $post_id, Meta::EYEBROW, true ),
			'summary' => (string) get_post_meta( $post_id, Meta::SUMMARY, true ),
			'metrics' => $metrics,
			'impact'  => $impact,
		);

		// Legacy fallback: any field still empty is read from the old flat keys.
		if ( '' === $data['eyebrow'] ) {
			$data['eyebrow'] = (string) get_post_meta( $post_id, '_ajr_case_study_eyebrow', true );
		}
		if ( '' === $data['summary'] ) {
			$data['summary'] = (string) get_post_meta( $post_id, '_ajr_case_study_summary', true );
		}
		foreach ( array( 'mobile', 'desktop' ) as $device ) {
			foreach ( array( 'before', 'after' ) as $phase ) {
				foreach ( Meta::METRIC_KEYS as $metric ) {
					if ( '' === $data['metrics'][ $device ][ $phase ][ $metric ] ) {
						$legacy = (string) get_post_meta( $post_id, "_ajr_case_study_{$device}_{$phase}_{$metric}", true );
						$data['metrics'][ $device ][ $phase ][ $metric ] = $legacy;
					}
				}
			}
		}
		$legacy_impact = array(
			'cwv_before'        => '_ajr_case_study_cwv_before_status',
			'cwv_after'         => '_ajr_case_study_cwv_after_status',
			'requests_removed'  => '_ajr_case_study_requests_removed',
			'page_size_reduced' => '_ajr_case_study_page_size_reduced',
		);
		foreach ( $legacy_impact as $new_key => $legacy_key ) {
			if ( '' === $data['impact'][ $new_key ] ) {
				$data['impact'][ $new_key ] = (string) get_post_meta( $post_id, $legacy_key, true );
			}
		}

		// German pages: overlay the translated fields, keeping the English
		// value wherever a translation hasn't been filled in yet.
		if ( self::is_de() ) {
			$german = array(
				'title'   => Meta::TITLE_DE,
				'eyebrow' => Meta::EYEBROW_DE,
				'summary' => Meta::SUMMARY_DE,
			);
			foreach ( $german as $field => $meta_key ) {
				$value = trim( (string) get_post_meta( $post_id, $meta_key, true ) );
				if ( '' !== $value ) {
					$data[ $field ] = $value;
				}
			}
		}

		return $data;
	}

	/**
	 * Splits a display value like "-62%" / "1.4s" into number parts for the
	 * count-up animation. Returns false when not numeric-leading.
	 *
	 * @param string $value Raw display value.
	 * @return array{number:string,decimals:int,prefix:string,suffix:string,original:string}|false
	 */
	public static function parse_count_value( string $value ) {
		$value = trim( $value );
		if ( '' === $value || ! preg_match( '/^(-?)(\d+(?:\.\d+)?)(.*)$/', $value, $matches ) ) {
			return false;
		}
		$decimals = 0;
		if ( false !== strpos( $matches[2], '.' ) ) {
			$parts    = explode( '.', $matches[2] );
			$decimals = isset( $parts[1] ) ? strlen( $parts[1] ) : 0;
		}
		return array(
			'number'   => $matches[2],
			'decimals' => $decimals,
			'prefix'   => $matches[1],
			'suffix'   => trim( $matches[3] ),
			'original' => $value,
		);
	}

	/**
	 * Renders a count-up span (falls back to plain text for non-numeric values).
	 *
	 * @param string $value      Display value.
	 * @param string $class_name Extra class.
	 * @param string $base_class Base BEM class for the span.
	 */
	public static function render_count_value( string $value, string $class_name = '', string $base_class = 'ajr-case-study-mini-card__count' ): string {
		$parsed = self::parse_count_value( $value );
		if ( ! $parsed ) {
			return esc_html( $value );
		}
		return sprintf(
			'<span class="%1$s" data-count-to="%2$s" data-count-decimals="%3$s" data-count-prefix="%4$s" data-count-suffix="%5$s">%6$s</span>',
			esc_attr( trim( $base_class . ' ' . $class_name ) ),
			esc_attr( $parsed['number'] ),
			esc_attr( (string) $parsed['decimals'] ),
			esc_attr( $parsed['prefix'] ),
			esc_attr( $parsed['suffix'] ),
			esc_html( $parsed['original'] )
		);
	}

	/**
	 * Inlines a bundled SVG icon.
	 *
	 * @param string $icon_name Icon file name (allow-listed).
	 */
	public static function icon( string $icon_name ): string {
		if ( ! in_array( $icon_name, self::ALLOWED_ICONS, true ) ) {
			return '';
		}
		if ( isset( self::$icon_cache[ $icon_name ] ) ) {
			return self::$icon_cache[ $icon_name ];
		}
		$path                           = AJRWD_CORE_PATH . 'assets/icons/' . $icon_name . '.svg';
		$svg                            = file_exists( $path ) ? (string) file_get_contents( $path ) : ''; // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		self::$icon_cache[ $icon_name ] = $svg;
		return $svg;
	}

	/**
	 * Maps a case-study tag slug to an icon name.
	 *
	 * @param string $term_slug Tag slug.
	 */
	public static function tag_icon( string $term_slug ): string {
		$map = array(
			'woocommerce'        => 'cube',
			'wordpress'          => 'device-desktop',
			'seo'                => 'trend-up',
			'performance'        => 'gauge',
			'speed-optimization' => 'gauge',
			'core-web-vitals'    => 'core-web-vitals',
			'caching'            => 'page-size-reduced',
		);
		return $map[ $term_slug ] ?? 'core-web-vitals';
	}

	/**
	 * Converts "5.2s" / "412ms" / bare numbers to milliseconds.
	 *
	 * @param string $value Timing display value.
	 */
	public static function value_to_ms( string $value ): ?float {
		$value = strtolower( trim( $value ) );
		if ( '' === $value ) {
			return null;
		}
		if ( preg_match( '/^(-?\d+(?:\.\d+)?)\s*ms$/', $value, $matches ) ) {
			return (float) $matches[1];
		}
		if ( preg_match( '/^(-?\d+(?:\.\d+)?)\s*s$/', $value, $matches ) ) {
			return (float) $matches[1] * 1000;
		}
		if ( is_numeric( $value ) ) {
			return (float) $value;
		}
		return null;
	}

	/**
	 * Percentage change between two timing values ("-73%" style, rounded).
	 *
	 * @param string $before Before value.
	 * @param string $after  After value.
	 */
	public static function calculate_improvement( string $before, string $after ): string {
		$before_ms = self::value_to_ms( $before );
		$after_ms  = self::value_to_ms( $after );
		if ( null === $before_ms || null === $after_ms || $before_ms <= 0 ) {
			return '';
		}
		return round( ( ( $after_ms - $before_ms ) / $before_ms ) * 100 ) . '%';
	}

	/**
	 * Renders a before → after value pair for the large card.
	 *
	 * @param string $before   Before value.
	 * @param string $after    After value.
	 * @param string $modifier Optional class modifier ('status' skips count-up).
	 */
	public static function render_change_value( string $before, string $after, string $modifier = '' ): string {
		if ( '' === $before && '' === $after ) {
			return '';
		}

		$before_class = 'ajr-case-study-card__score-before';
		$after_class  = 'ajr-case-study-card__score-after';
		if ( '' !== $modifier ) {
			$before_class .= ' ajr-case-study-card__score-before--' . sanitize_html_class( $modifier );
			$after_class  .= ' ajr-case-study-card__score-after--' . sanitize_html_class( $modifier );
		}

		if ( 'status' === $modifier ) {
			$after_value = sprintf( '<span class="%1$s">%2$s</span>', esc_attr( $after_class ), esc_html( $after ) );
		} else {
			$after_value = self::render_count_value( $after, $after_class, 'ajr-case-study-card__count' );
		}

		return sprintf(
			'<span class="%1$s">%2$s</span> <span class="ajr-case-study-card__arrow-inline" aria-hidden="true">%3$s</span> %4$s',
			esc_attr( $before_class ),
			esc_html( $before ),
			self::icon( 'arrow-right' ),
			$after_value
		);
	}

	/**
	 * Renders one compact metric (LCP/CLS/INP) for a device panel.
	 *
	 * @param string $label Metric label.
	 * @param string $value Metric value.
	 * @param string $type  'before' or 'after'.
	 */
	public static function metric_inline( string $label, string $value, string $type = 'before' ): string {
		if ( '' === $value ) {
			return '';
		}
		$modifier = 'after' === $type ? 'after' : 'before';
		ob_start();
		?>
		<div class="ajr-case-study-card__metric ajr-case-study-card__metric--<?php echo esc_attr( $modifier ); ?>">
			<span class="ajr-case-study-card__metric-label"><?php echo esc_html( $label ); ?></span>
			<span class="ajr-case-study-card__metric-value-wrap">
				<span class="ajr-case-study-card__metric-icon" aria-hidden="true">
					<?php echo self::icon( 'after' === $modifier ? 'metric-after' : 'metric-before' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</span>
				<span class="ajr-case-study-card__metric-value"><?php echo esc_html( $value ); ?></span>
			</span>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Renders a device panel (Mobile/Desktop score circles + metric rows).
	 *
	 * @param string                             $device  Display name.
	 * @param string                             $icon    Icon name.
	 * @param array<string,array<string,string>> $data    ['before' => [score,lcp,cls,inp], 'after' => [...]].
	 */
	public static function device_panel( string $device, string $icon, array $data ): string {
		$before = $data['before'] ?? array();
		$after  = $data['after'] ?? array();

		$before_score = (string) ( $before['score'] ?? '' );
		$after_score  = (string) ( $after['score'] ?? '' );
		if ( '' === $before_score && '' === $after_score ) {
			return '';
		}

		$before_score_value = max( 0, min( 100, (int) $before_score ) );
		$after_score_value  = max( 0, min( 100, (int) $after_score ) );

		ob_start();
		?>
		<div class="ajr-case-study-card__device-panel">
			<div class="ajr-case-study-card__device-head">
				<div class="ajr-case-study-card__device-icon-box" aria-hidden="true">
					<?php echo self::icon( $icon ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>
				<div class="ajr-case-study-card__device-name"><?php echo esc_html( $device ); ?></div>
			</div>

			<div class="ajr-case-study-card__score-flow">
				<div class="ajr-case-study-card__score-block">
					<div class="ajr-case-study-card__score-kicker ajr-case-study-card__score-kicker--before"><?php echo esc_html( self::ui_label( 'Before' ) ); ?></div>
					<div
						class="ajr-case-study-card__score-circle ajr-case-study-card__score-circle--before"
						style="--score: <?php echo esc_attr( (string) $before_score_value ); ?>;"
						data-score-target="<?php echo esc_attr( (string) $before_score_value ); ?>"
					>
						<?php echo self::render_count_value( $before_score, 'ajr-case-study-card__score-number', 'ajr-case-study-card__count' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</div>
				</div>

				<div class="ajr-case-study-card__flow-arrow" aria-hidden="true">
					<?php echo self::icon( 'arrow-right' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>

				<div class="ajr-case-study-card__score-block">
					<div class="ajr-case-study-card__score-kicker ajr-case-study-card__score-kicker--after"><?php echo esc_html( self::ui_label( 'After' ) ); ?></div>
					<div
						class="ajr-case-study-card__score-circle ajr-case-study-card__score-circle--after"
						style="--score: <?php echo esc_attr( (string) $after_score_value ); ?>;"
						data-score-target="<?php echo esc_attr( (string) $after_score_value ); ?>"
					>
						<?php echo self::render_count_value( $after_score, 'ajr-case-study-card__score-number', 'ajr-case-study-card__count' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</div>
				</div>
			</div>

			<div class="ajr-case-study-card__metrics-row">
				<div class="ajr-case-study-card__metrics-group">
					<?php
					echo self::metric_inline( 'LCP', (string) ( $before['lcp'] ?? '' ), 'before' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					echo self::metric_inline( 'CLS', (string) ( $before['cls'] ?? '' ), 'before' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					echo self::metric_inline( 'INP', (string) ( $before['inp'] ?? '' ), 'before' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					?>
				</div>
				<div class="ajr-case-study-card__metrics-group ajr-case-study-card__metrics-group--after">
					<?php
					echo self::metric_inline( 'LCP', (string) ( $after['lcp'] ?? '' ), 'after' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					echo self::metric_inline( 'CLS', (string) ( $after['cls'] ?? '' ), 'after' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					echo self::metric_inline( 'INP', (string) ( $after['inp'] ?? '' ), 'after' ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					?>
				</div>
			</div>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Allowed HTML for tile values (count spans + inline SVG).
	 *
	 * @return array<string,mixed>
	 */
	public static function allowed_tile_html(): array {
		return array(
			'span'   => array(
				'class'               => true,
				'aria-hidden'         => true,
				'data-count-to'       => true,
				'data-count-decimals' => true,
				'data-count-prefix'   => true,
				'data-count-suffix'   => true,
				'data-count-duration' => true,
			),
			'svg'    => array(
				'width'       => true,
				'height'      => true,
				'viewBox'     => true,
				'viewbox'     => true,
				'fill'        => true,
				'xmlns'       => true,
				'aria-hidden' => true,
				'focusable'   => true,
				'class'       => true,
			),
			'path'   => array(
				'd'               => true,
				'stroke'          => true,
				'stroke-width'    => true,
				'stroke-linecap'  => true,
				'stroke-linejoin' => true,
				'fill'            => true,
			),
			'circle' => array(
				'cx'           => true,
				'cy'           => true,
				'r'            => true,
				'fill'         => true,
				'fill-opacity' => true,
				'stroke'       => true,
				'stroke-width' => true,
			),
			'rect'   => array(
				'x'            => true,
				'y'            => true,
				'width'        => true,
				'height'       => true,
				'rx'           => true,
				'fill'         => true,
				'stroke'       => true,
				'stroke-width' => true,
			),
		);
	}

	/**
	 * Renders a summary tile for the large card.
	 *
	 * @param string $icon       Icon name.
	 * @param string $tile_title Tile heading.
	 * @param string $value      Tile value HTML (kses-filtered).
	 * @param string $modifier   Colour modifier.
	 */
	public static function tile( string $icon, string $tile_title, string $value, string $modifier = 'default' ): string {
		if ( '' === $value ) {
			return '';
		}
		ob_start();
		?>
		<div class="ajr-case-study-card__tile ajr-case-study-card__tile--<?php echo esc_attr( $modifier ); ?>">
			<div class="ajr-case-study-card__tile-icon" aria-hidden="true">
				<?php echo self::icon( $icon ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
			</div>
			<div class="ajr-case-study-card__tile-body">
				<?php if ( '' !== $tile_title ) : ?>
					<div class="ajr-case-study-card__tile-title"><?php echo esc_html( $tile_title ); ?></div>
				<?php endif; ?>
				<div class="ajr-case-study-card__tile-value">
					<?php echo wp_kses( $value, self::allowed_tile_html() ); ?>
				</div>
			</div>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Renders the non-linked tag row for a card.
	 *
	 * @param int    $post_id    Case study ID.
	 * @param string $base_class BEM base class ("ajr-case-study-mini-card").
	 */
	public static function render_tags( int $post_id, string $base_class = 'ajr-case-study-mini-card' ): string {
		$terms = get_the_terms( $post_id, \AJR\SiteCore\CaseStudies\PostType::TAXONOMY );
		if ( empty( $terms ) || is_wp_error( $terms ) ) {
			return '';
		}
		ob_start();
		?>
		<div class="<?php echo esc_attr( $base_class ); ?>__tags" aria-label="<?php echo esc_attr( self::ui_label( 'Case study tags' ) ); ?>">
			<?php foreach ( $terms as $term ) : ?>
				<span class="<?php echo esc_attr( $base_class ); ?>__tag">
					<span class="<?php echo esc_attr( $base_class ); ?>__tag-icon" aria-hidden="true">
						<?php echo self::icon( self::tag_icon( $term->slug ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
					</span>
					<span class="<?php echo esc_attr( $base_class ); ?>__tag-label"><?php echo esc_html( $term->name ); ?></span>
				</span>
			<?php endforeach; ?>
		</div>
		<?php
		return (string) ob_get_clean();
	}
}
