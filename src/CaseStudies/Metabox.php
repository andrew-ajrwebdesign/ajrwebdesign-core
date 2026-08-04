<?php
/**
 * Case Study Details metabox.
 *
 * @package AJR\SiteCore
 */

namespace AJR\SiteCore\CaseStudies;

defined( 'ABSPATH' ) || exit;

/**
 * Restores the legacy full-width "Case Study Details" editing experience —
 * Overview, Mobile/Desktop before-after grids, Impact tiles — with the
 * original admin styling, but reading and writing the STRUCTURED meta
 * (ajrwd_cs_*) instead of the 22 legacy flat keys.
 */
class Metabox {

	private const NONCE_ACTION = 'ajrwd_cs_save_details';
	private const NONCE_FIELD  = 'ajrwd_cs_details_nonce';

	/**
	 * Hooks the metabox, its assets, and the save handler.
	 */
	public function register(): void {
		add_action( 'add_meta_boxes', array( $this, 'add_metabox' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
		add_action( 'save_post_' . PostType::POST_TYPE, array( $this, 'save' ) );
	}

	/**
	 * Registers the metabox.
	 */
	public function add_metabox(): void {
		add_meta_box(
			'ajrwd_case_study_details',
			__( 'Case Study Details', 'ajrwebdesign-core' ),
			array( $this, 'render' ),
			PostType::POST_TYPE,
			'normal',
			'high'
		);
	}

	/**
	 * Enqueues the admin stylesheet on the case-study screens.
	 *
	 * @param string $hook Current admin page hook.
	 */
	public function enqueue_assets( string $hook ): void {
		if ( ! in_array( $hook, array( 'post.php', 'post-new.php' ), true ) ) {
			return;
		}
		$screen = get_current_screen();
		if ( ! $screen || PostType::POST_TYPE !== $screen->post_type ) {
			return;
		}
		wp_enqueue_style(
			'ajrwd-core-case-studies-admin',
			AJRWD_CORE_URL . 'assets/admin/case-studies.css',
			array(),
			AJRWD_CORE_VERSION
		);
	}

	/**
	 * Renders one text field.
	 *
	 * @param string $name        Input name.
	 * @param string $label       Field label.
	 * @param string $value       Current value.
	 * @param string $placeholder Placeholder text.
	 * @param string $context     Optional field modifier (before/after).
	 */
	private function field( string $name, string $label, string $value, string $placeholder = '', string $context = '' ): void {
		$classes = 'ajr-case-study-field';
		if ( '' !== $context ) {
			$classes .= ' ajr-case-study-field--' . sanitize_html_class( $context );
		}
		?>
		<p class="<?php echo esc_attr( $classes ); ?>">
			<label for="<?php echo esc_attr( $name ); ?>"><strong><?php echo esc_html( $label ); ?></strong></label>
			<input
				type="text"
				id="<?php echo esc_attr( $name ); ?>"
				name="<?php echo esc_attr( $name ); ?>"
				value="<?php echo esc_attr( $value ); ?>"
				placeholder="<?php echo esc_attr( $placeholder ); ?>"
				class="widefat"
			>
		</p>
		<?php
	}

	/**
	 * Renders a device panel (Before/After metric grid).
	 *
	 * @param string $device  'mobile' or 'desktop'.
	 * @param string $title   Section heading.
	 * @param array  $metrics Metrics structure.
	 */
	private function device_section( string $device, string $title, array $metrics ): void {
		$examples = array(
			'mobile'  => array(
				'before' => array( '42', '5.2s', '0.32', '412ms' ),
				'after'  => array( '96', '1.4s', '0.02', '87ms' ),
			),
			'desktop' => array(
				'before' => array( '78', '2.1s', '0.10', '180ms' ),
				'after'  => array( '99', '0.8s', '0.01', '65ms' ),
			),
		);
		?>
		<div class="ajr-case-study-meta-section">
			<h3><?php echo esc_html( $title ); ?></h3>
			<div class="ajr-case-study-before-after-grid">
				<?php foreach ( array( 'before', 'after' ) as $phase ) : ?>
					<div class="ajr-case-study-<?php echo esc_attr( $phase ); ?>-column">
						<h4><?php echo 'before' === $phase ? esc_html__( 'Before', 'ajrwebdesign-core' ) : esc_html__( 'After', 'ajrwebdesign-core' ); ?></h4>
						<?php
						$labels = array( 'score', 'lcp', 'cls', 'inp' );
						foreach ( $labels as $i => $metric ) {
							$this->field(
								"ajrwd_cs[{$device}][{$phase}][{$metric}]",
								strtoupper( 'score' === $metric ? 'Score' : $metric ),
								(string) ( $metrics[ $device ][ $phase ][ $metric ] ?? '' ),
								$examples[ $device ][ $phase ][ $i ],
								$phase
							);
						}
						?>
					</div>
				<?php endforeach; ?>
			</div>
		</div>
		<?php
	}

	/**
	 * Renders the metabox.
	 *
	 * @param \WP_Post $post Current post.
	 */
	public function render( \WP_Post $post ): void {
		wp_nonce_field( self::NONCE_ACTION, self::NONCE_FIELD );

		$eyebrow    = (string) get_post_meta( $post->ID, Meta::EYEBROW, true );
		$summary    = (string) get_post_meta( $post->ID, Meta::SUMMARY, true );
		$title_de   = (string) get_post_meta( $post->ID, Meta::TITLE_DE, true );
		$eyebrow_de = (string) get_post_meta( $post->ID, Meta::EYEBROW_DE, true );
		$summary_de = (string) get_post_meta( $post->ID, Meta::SUMMARY_DE, true );
		$metrics    = get_post_meta( $post->ID, Meta::METRICS, true );
		$metrics    = is_array( $metrics ) ? array_replace_recursive( Meta::empty_metrics(), $metrics ) : Meta::empty_metrics();
		$impact     = get_post_meta( $post->ID, Meta::IMPACT, true );
		$impact     = is_array( $impact ) ? array_merge( Meta::empty_impact(), $impact ) : Meta::empty_impact();
		?>
		<div class="ajr-case-study-meta-section">
			<h3><?php esc_html_e( 'Overview', 'ajrwebdesign-core' ); ?></h3>
			<?php
			$this->field( 'ajrwd_cs_eyebrow', __( 'Category / Eyebrow', 'ajrwebdesign-core' ), $eyebrow, 'ECOMMERCE' );
			$this->field( 'ajrwd_cs_summary', __( 'Short Summary', 'ajrwebdesign-core' ), $summary, __( 'The client’s store was slow, with poor Core Web Vitals…', 'ajrwebdesign-core' ) );
			?>
		</div>

		<div class="ajr-case-study-meta-section">
			<h3><?php esc_html_e( 'German Translation (Deutsch)', 'ajrwebdesign-core' ); ?></h3>
			<p class="description"><?php esc_html_e( 'Used on German pages instead of the English title, eyebrow, and summary. An empty field falls back to English. Metrics and impact tiles are shared between languages.', 'ajrwebdesign-core' ); ?></p>
			<?php
			$this->field( 'ajrwd_cs_title_de', __( 'Title (DE)', 'ajrwebdesign-core' ), $title_de );
			$this->field( 'ajrwd_cs_eyebrow_de', __( 'Category / Eyebrow (DE)', 'ajrwebdesign-core' ), $eyebrow_de );
			$this->field( 'ajrwd_cs_summary_de', __( 'Short Summary (DE)', 'ajrwebdesign-core' ), $summary_de );
			?>
		</div>

		<div class="ajr-case-study-meta-grid">
			<?php
			$this->device_section( 'mobile', __( 'Mobile Performance', 'ajrwebdesign-core' ), $metrics );
			$this->device_section( 'desktop', __( 'Desktop Performance', 'ajrwebdesign-core' ), $metrics );
			?>
		</div>

		<div class="ajr-case-study-meta-section">
			<h3><?php esc_html_e( 'Impact Tiles', 'ajrwebdesign-core' ); ?></h3>
			<div class="ajr-case-study-result-row">
				<?php
				$this->field( 'ajrwd_cs_impact[cwv_before]', __( 'Core Web Vitals Before Status', 'ajrwebdesign-core' ), $impact['cwv_before'], 'Failed' );
				$this->field( 'ajrwd_cs_impact[cwv_after]', __( 'Core Web Vitals After Status', 'ajrwebdesign-core' ), $impact['cwv_after'], 'Passed' );
				?>
			</div>
			<div class="ajr-case-study-result-row">
				<?php
				$this->field( 'ajrwd_cs_impact[requests_removed]', __( 'Requests Removed', 'ajrwebdesign-core' ), $impact['requests_removed'], '18' );
				$this->field( 'ajrwd_cs_impact[page_size_reduced]', __( 'Page Size Reduced', 'ajrwebdesign-core' ), $impact['page_size_reduced'], '-62%' );
				?>
			</div>
		</div>
		<?php
	}

	/**
	 * Saves the structured meta.
	 *
	 * @param int $post_id Post being saved.
	 */
	public function save( int $post_id ): void {
		if ( ! isset( $_POST[ self::NONCE_FIELD ] )
			|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST[ self::NONCE_FIELD ] ) ), self::NONCE_ACTION )
		) {
			return;
		}
		if ( defined( 'DOING_AUTOSAVE' ) && DOING_AUTOSAVE ) {
			return;
		}
		if ( ! current_user_can( 'edit_post', $post_id ) ) {
			return;
		}

		if ( isset( $_POST['ajrwd_cs_eyebrow'] ) ) {
			update_post_meta( $post_id, Meta::EYEBROW, sanitize_text_field( wp_unslash( $_POST['ajrwd_cs_eyebrow'] ) ) );
		}
		if ( isset( $_POST['ajrwd_cs_summary'] ) ) {
			update_post_meta( $post_id, Meta::SUMMARY, sanitize_text_field( wp_unslash( $_POST['ajrwd_cs_summary'] ) ) );
		}

		$german_fields = array(
			'ajrwd_cs_title_de'   => Meta::TITLE_DE,
			'ajrwd_cs_eyebrow_de' => Meta::EYEBROW_DE,
			'ajrwd_cs_summary_de' => Meta::SUMMARY_DE,
		);
		foreach ( $german_fields as $input_name => $meta_key ) {
			if ( isset( $_POST[ $input_name ] ) ) {
				update_post_meta( $post_id, $meta_key, sanitize_text_field( wp_unslash( $_POST[ $input_name ] ) ) );
			}
		}

		if ( isset( $_POST['ajrwd_cs'] ) && is_array( $_POST['ajrwd_cs'] ) ) {
			$meta_handler = new Meta();
			update_post_meta( $post_id, Meta::METRICS, $meta_handler->sanitize_metrics( wp_unslash( $_POST['ajrwd_cs'] ) ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		}
		if ( isset( $_POST['ajrwd_cs_impact'] ) && is_array( $_POST['ajrwd_cs_impact'] ) ) {
			$meta_handler = isset( $meta_handler ) ? $meta_handler : new Meta();
			update_post_meta( $post_id, Meta::IMPACT, $meta_handler->sanitize_impact( wp_unslash( $_POST['ajrwd_cs_impact'] ) ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		}
	}
}
