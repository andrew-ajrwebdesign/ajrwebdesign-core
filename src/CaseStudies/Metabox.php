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
	 * Enqueues the metabox's stylesheets and the screenshot picker (with WordPress's
	 * media library) on the case-study edit screens.
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

		wp_enqueue_style(
			'ajrwd-core-case-study-build-admin',
			AJRWD_CORE_URL . 'assets/admin/case-study-build.css',
			array( 'ajrwd-core-case-studies-admin' ),
			AJRWD_CORE_VERSION
		);

		// The screenshot picker opens WordPress's own media library.
		wp_enqueue_media();
		wp_enqueue_script(
			'ajrwd-core-case-study-gallery',
			AJRWD_CORE_URL . 'assets/admin/case-study-gallery.js',
			array( 'media-editor' ),
			AJRWD_CORE_VERSION,
			array( 'in_footer' => true )
		);
	}

	/**
	 * Renders the site-build fields: live URL, results intro, PageSpeed scores,
	 * comparison rows, headline facts, source line and the screenshot picker.
	 *
	 * @param \WP_Post $post Current post.
	 */
	protected function build_section( \WP_Post $post ): void {
		$build   = Meta::sanitize_build( get_post_meta( $post->ID, Meta::BUILD, true ) );
		$gallery = Meta::sanitize_gallery( get_post_meta( $post->ID, Meta::GALLERY, true ) );

		$categories = array(
			'performance'    => __( 'Performance', 'ajrwebdesign-core' ),
			'accessibility'  => __( 'Accessibility', 'ajrwebdesign-core' ),
			'best_practices' => __( 'Best practices', 'ajrwebdesign-core' ),
			'seo'            => __( 'SEO', 'ajrwebdesign-core' ),
		);
		?>
		<div class="ajr-case-study-meta-section">
			<h3><?php esc_html_e( 'Site build', 'ajrwebdesign-core' ); ?></h3>
			<p class="description"><?php esc_html_e( 'Shown when the kind above is “Site build”, in place of the before and after grids below. Empty fields and half-filled rows are left off the page.', 'ajrwebdesign-core' ); ?></p>
			<?php
			$this->field( 'ajrwd_cs_build[url]', __( 'Live site address', 'ajrwebdesign-core' ), $build['url'], 'https://example.com/' );
			$this->field( 'ajrwd_cs_build[intro]', __( 'Results intro (one line under “The build at a glance”)', 'ajrwebdesign-core' ), $build['intro'], __( 'Google’s own test, run on the live home page a week after launch.', 'ajrwebdesign-core' ) );
			?>

			<h4><?php esc_html_e( 'Google PageSpeed scores (0 to 100)', 'ajrwebdesign-core' ); ?></h4>
			<?php
			foreach ( array(
				'mobile'  => __( 'Mobile', 'ajrwebdesign-core' ),
				'desktop' => __( 'Desktop', 'ajrwebdesign-core' ),
			) as $device => $device_label ) :
				?>
				<div class="ajr-case-study-score-row">
					<?php
					foreach ( $categories as $category => $category_label ) {
						$this->field(
							"ajrwd_cs_build[scores][{$device}][{$category}]",
							$device_label . ': ' . $category_label,
							$build['scores'][ $device ][ $category ],
							'100'
						);
					}
					?>
				</div>
			<?php endforeach; ?>

			<h4><?php esc_html_e( 'Comparison rows (up to three)', 'ajrwebdesign-core' ); ?></h4>
			<?php
			$this->field( 'ajrwd_cs_build[compare_title]', __( 'Heading', 'ajrwebdesign-core' ), $build['compare_title'], __( 'Against a typical WordPress site', 'ajrwebdesign-core' ) );
			for ( $i = 0; $i < Meta::MAX_COMPARE; $i++ ) :
				$row = $build['compare'][ $i ] ?? array();
				?>
				<div class="ajr-case-study-score-row">
					<?php
					$this->field( "ajrwd_cs_build[compare][{$i}][label]", __( 'What is compared', 'ajrwebdesign-core' ), (string) ( $row['label'] ?? '' ), __( 'Home page weight', 'ajrwebdesign-core' ) );
					$this->field( "ajrwd_cs_build[compare][{$i}][value]", __( 'This site', 'ajrwebdesign-core' ), (string) ( $row['value'] ?? '' ), '594 KB' );
					$this->field( "ajrwd_cs_build[compare][{$i}][percent]", __( 'Bar length, % of typical (empty: no bar)', 'ajrwebdesign-core' ), ! empty( $row['percent'] ) ? (string) $row['percent'] : '', '21' );
					$this->field( "ajrwd_cs_build[compare][{$i}][note]", __( 'Note under the bars', 'ajrwebdesign-core' ), (string) ( $row['note'] ?? '' ), __( 'Typical WordPress page: 2,894 KB.', 'ajrwebdesign-core' ) );
					?>
				</div>
			<?php endfor; ?>

			<h4><?php esc_html_e( 'Headline facts (up to four)', 'ajrwebdesign-core' ); ?></h4>
			<?php
			for ( $i = 0; $i < Meta::MAX_FACTS; $i++ ) :
				$row = $build['facts'][ $i ] ?? array();
				?>
				<div class="ajr-case-study-score-row ajr-case-study-score-row--three">
					<?php
					$this->field( "ajrwd_cs_build[facts][{$i}][value]", __( 'Figure', 'ajrwebdesign-core' ), (string) ( $row['value'] ?? '' ), '7 days' );
					$this->field( "ajrwd_cs_build[facts][{$i}][label]", __( 'What it is', 'ajrwebdesign-core' ), (string) ( $row['label'] ?? '' ), __( 'Brief to live', 'ajrwebdesign-core' ) );
					$this->field( "ajrwd_cs_build[facts][{$i}][note]", __( 'Small print', 'ajrwebdesign-core' ), (string) ( $row['note'] ?? '' ), __( 'Scope approved to launch', 'ajrwebdesign-core' ) );
					?>
				</div>
			<?php endfor; ?>

			<?php $this->field( 'ajrwd_cs_build[source]', __( 'Source line (where the numbers come from, and when)', 'ajrwebdesign-core' ), $build['source'], __( 'Scores: Google PageSpeed Insights, 30 September 2026.', 'ajrwebdesign-core' ) ); ?>

			<h4><?php esc_html_e( 'Screenshots', 'ajrwebdesign-core' ); ?></h4>
			<p class="description">
				<?php
				printf(
					/* translators: %d: the most screenshots one case study keeps. */
					esc_html__( 'Up to %d, shown in this order. Wide screenshots get a browser frame, tall ones a phone frame; the first of each makes the hero and the card. Each image’s title and caption are printed under it, and its alt text (and German alt text) is read aloud, so fill those in the media library.', 'ajrwebdesign-core' ),
					(int) Meta::MAX_GALLERY
				);
				?>
			</p>
			<div class="ajr-case-study-gallery" data-ajrwd-gallery data-max="<?php echo esc_attr( (string) Meta::MAX_GALLERY ); ?>" data-title="<?php esc_attr_e( 'Choose screenshots', 'ajrwebdesign-core' ); ?>" data-button="<?php esc_attr_e( 'Use these screenshots', 'ajrwebdesign-core' ); ?>">
				<input type="hidden" name="ajrwd_cs_gallery" value="<?php echo esc_attr( implode( ',', $gallery ) ); ?>">
				<ul class="ajr-case-study-gallery__list" data-ajrwd-gallery-list>
					<?php foreach ( $gallery as $attachment_id ) : ?>
						<li><?php echo wp_get_attachment_image( $attachment_id, 'thumbnail' ); ?></li>
					<?php endforeach; ?>
				</ul>
				<p>
					<button type="button" class="button" data-ajrwd-gallery-choose><?php esc_html_e( 'Choose screenshots', 'ajrwebdesign-core' ); ?></button>
					<button type="button" class="button-link" data-ajrwd-gallery-clear><?php esc_html_e( 'Remove all', 'ajrwebdesign-core' ); ?></button>
				</p>
			</div>
		</div>
		<?php
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
		$kind       = Meta::sanitize_kind( get_post_meta( $post->ID, Meta::KIND, true ) );
		?>
		<div class="ajr-case-study-meta-section">
			<h3><?php esc_html_e( 'Overview', 'ajrwebdesign-core' ); ?></h3>
			<?php
			$this->field( 'ajrwd_cs_eyebrow', __( 'Category / Eyebrow', 'ajrwebdesign-core' ), $eyebrow, 'ECOMMERCE' );
			$this->field( 'ajrwd_cs_summary', __( 'Short Summary', 'ajrwebdesign-core' ), $summary, __( 'The client’s store was slow, with poor Core Web Vitals…', 'ajrwebdesign-core' ) );
			?>
			<p class="ajr-case-study-field">
				<label for="ajrwd_cs_kind"><strong><?php esc_html_e( 'Kind of case study', 'ajrwebdesign-core' ); ?></strong></label>
				<select id="ajrwd_cs_kind" name="ajrwd_cs_kind">
					<option value="<?php echo esc_attr( Meta::KIND_AUDIT ); ?>" <?php selected( $kind, Meta::KIND_AUDIT ); ?>><?php esc_html_e( 'Audit: before and after numbers', 'ajrwebdesign-core' ); ?></option>
					<option value="<?php echo esc_attr( Meta::KIND_BUILD ); ?>" <?php selected( $kind, Meta::KIND_BUILD ); ?>><?php esc_html_e( 'Site build: screenshots and the finished site’s scores', 'ajrwebdesign-core' ); ?></option>
				</select>
			</p>
		</div>

		<?php $this->build_section( $post ); ?>

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

		// Site-build fields. update_post_meta() expects slashed data and strips one
		// level itself, so the cleaned values are slashed again on the way in.
		if ( isset( $_POST['ajrwd_cs_kind'] ) ) {
			update_post_meta( $post_id, Meta::KIND, Meta::sanitize_kind( sanitize_key( wp_unslash( $_POST['ajrwd_cs_kind'] ) ) ) );
		}
		if ( isset( $_POST['ajrwd_cs_build'] ) && is_array( $_POST['ajrwd_cs_build'] ) ) {
			update_post_meta( $post_id, Meta::BUILD, wp_slash( Meta::sanitize_build( wp_unslash( $_POST['ajrwd_cs_build'] ) ) ) ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- sanitize_build() cleans every field.
		}
		if ( isset( $_POST['ajrwd_cs_gallery'] ) ) {
			// Only images the person saving may read themselves: an ID typed into the
			// field must not put someone else's private or draft-attached image on a
			// public page.
			$gallery = array_filter(
				Meta::sanitize_gallery( sanitize_text_field( wp_unslash( $_POST['ajrwd_cs_gallery'] ) ) ),
				static fn( int $id ): bool => wp_attachment_is_image( $id ) && current_user_can( 'read_post', $id )
			);
			update_post_meta( $post_id, Meta::GALLERY, array_values( $gallery ) );
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
