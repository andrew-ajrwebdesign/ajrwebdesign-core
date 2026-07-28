<?php
/**
 * Legacy meta migration (ajr-site-blocks → structured meta).
 *
 * @package AJR\SiteCore
 */

namespace AJR\SiteCore\CaseStudies;

defined( 'ABSPATH' ) || exit;

/**
 * Maps the legacy plugin's 22 flat `_ajr_case_study_*` keys onto the four
 * structured fields. Exposed two ways so it works on any host:
 * a WP-CLI command (`wp ajr-core migrate-case-meta`) and a one-shot admin
 * action on the plugin settings screen. Idempotent — safe to run twice.
 */
class Migration {

	private const ADMIN_ACTION = 'ajrwd_core_migrate_case_meta';

	/**
	 * Hooks the CLI command and the admin fallback.
	 */
	public function register(): void {
		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			\WP_CLI::add_command( 'ajr-core migrate-case-meta', array( $this, 'cli_command' ) );
		}
		add_action( 'admin_post_' . self::ADMIN_ACTION, array( $this, 'handle_admin_action' ) );
		add_action( 'admin_notices', array( $this, 'maybe_show_notice' ) );
	}

	/**
	 * The flat→structured key map.
	 *
	 * Returns [legacy key => [field, ...path]] where field is a Meta constant.
	 *
	 * @return array<string,array<int,string>>
	 */
	public static function key_map(): array {
		$map = array(
			'_ajr_case_study_eyebrow'           => array( Meta::EYEBROW ),
			'_ajr_case_study_summary'           => array( Meta::SUMMARY ),

			'_ajr_case_study_cwv_before_status' => array( Meta::IMPACT, 'cwv_before' ),
			'_ajr_case_study_cwv_after_status'  => array( Meta::IMPACT, 'cwv_after' ),
			'_ajr_case_study_requests_removed'  => array( Meta::IMPACT, 'requests_removed' ),
			'_ajr_case_study_page_size_reduced' => array( Meta::IMPACT, 'page_size_reduced' ),
		);

		foreach ( array( 'mobile', 'desktop' ) as $device ) {
			foreach ( array( 'before', 'after' ) as $phase ) {
				foreach ( Meta::METRIC_KEYS as $metric ) {
					$legacy         = "_ajr_case_study_{$device}_{$phase}_{$metric}";
					$map[ $legacy ] = array( Meta::METRICS, $device, $phase, $metric );
				}
			}
		}

		return $map;
	}

	/**
	 * Migrates one post. Legacy keys are read but never deleted (rollback
	 * safety); structured values are only written when a legacy value exists.
	 *
	 * @param int $post_id Case study post ID.
	 * @return int Number of legacy values migrated.
	 */
	public function migrate_post( int $post_id ): int {
		$metrics  = Meta::empty_metrics();
		$impact   = Meta::empty_impact();
		$migrated = 0;

		foreach ( self::key_map() as $legacy_key => $path ) {
			$value = get_post_meta( $post_id, $legacy_key, true );
			if ( '' === $value || false === $value ) {
				continue;
			}
			$value = sanitize_text_field( (string) $value );
			++$migrated;

			$field = $path[0];
			if ( Meta::EYEBROW === $field || Meta::SUMMARY === $field ) {
				update_post_meta( $post_id, $field, $value );
			} elseif ( Meta::IMPACT === $field ) {
				$impact[ $path[1] ] = $value;
			} elseif ( Meta::METRICS === $field ) {
				$metrics[ $path[1] ][ $path[2] ][ $path[3] ] = $value;
			}
		}

		if ( $migrated > 0 ) {
			update_post_meta( $post_id, Meta::METRICS, $metrics );
			update_post_meta( $post_id, Meta::IMPACT, $impact );
		}

		return $migrated;
	}

	/**
	 * Migrates every case study.
	 *
	 * @return array{posts:int,values:int}
	 */
	public function migrate_all(): array {
		$ids = get_posts(
			array(
				'post_type'      => PostType::POST_TYPE,
				'post_status'    => 'any',
				'posts_per_page' => 100,
				'fields'         => 'ids',
				'no_found_rows'  => true,
			)
		);

		$totals = array(
			'posts'  => 0,
			'values' => 0,
		);
		foreach ( $ids as $id ) {
			$count = $this->migrate_post( (int) $id );
			if ( $count > 0 ) {
				++$totals['posts'];
				$totals['values'] += $count;
			}
		}
		return $totals;
	}

	/**
	 * WP-CLI entry point.
	 */
	public function cli_command(): void {
		$totals = $this->migrate_all();
		\WP_CLI::success( sprintf( 'Migrated %d values across %d case studies.', $totals['values'], $totals['posts'] ) );
	}

	/**
	 * Admin fallback entry point (settings screen button).
	 */
	public function handle_admin_action(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			wp_die( esc_html__( 'Insufficient permissions.', 'ajrwebdesign-core' ) );
		}
		check_admin_referer( self::ADMIN_ACTION );

		$totals = $this->migrate_all();
		wp_safe_redirect(
			add_query_arg(
				array(
					'page'           => 'ajrwd-core',
					'ajrwd_migrated' => $totals['posts'],
				),
				admin_url( 'options-general.php' )
			)
		);
		exit;
	}

	/**
	 * Success notice after the admin migration redirect.
	 */
	public function maybe_show_notice(): void {
		if ( ! isset( $_GET['ajrwd_migrated'] ) || ! current_user_can( 'manage_options' ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			return;
		}
		$count = absint( wp_unslash( $_GET['ajrwd_migrated'] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
		printf(
			'<div class="notice notice-success is-dismissible"><p>%s</p></div>',
			esc_html( sprintf( /* translators: %d: number of posts */ __( 'Case-study meta migrated for %d posts.', 'ajrwebdesign-core' ), $count ) )
		);
	}

	/**
	 * URL for the settings-screen migration button.
	 */
	public static function action_url(): string {
		return wp_nonce_url( admin_url( 'admin-post.php?action=' . self::ADMIN_ACTION ), self::ADMIN_ACTION );
	}
}
