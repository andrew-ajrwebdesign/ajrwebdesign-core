<?php
/**
 * Plugin settings screen and typed access to stored options.
 *
 * @package AJR\SiteCore
 */

namespace AJR\SiteCore\Admin;

defined( 'ABSPATH' ) || exit;

/**
 * One settings screen for the whole plugin: feature toggles plus the GA4
 * measurement ID. Stored as a single option row (not autoloaded data-heavy).
 */
class Settings {

	public const OPTION = 'ajrwd_core_settings';

	/**
	 * Cached option value for this request.
	 *
	 * @var array<string,mixed>|null
	 */
	private ?array $values = null;

	/**
	 * Default settings — every feature on except analytics (needs an ID first).
	 *
	 * @return array<string,mixed>
	 */
	public static function defaults(): array {
		return array(
			'case_studies_cpt' => 1,
			'analytics'        => 0,
			'ga4_id'           => '',
		);
	}

	/**
	 * Hooks the admin page and option registration.
	 */
	public function register(): void {
		add_action( 'admin_menu', array( $this, 'add_page' ) );
		add_action( 'admin_init', array( $this, 'register_settings' ) );
	}

	/**
	 * Whether a boolean feature flag is on.
	 *
	 * @param string $key Setting key.
	 */
	public function is_enabled( string $key ): bool {
		return ! empty( $this->get( $key ) );
	}

	/**
	 * Returns a single setting value.
	 *
	 * @param string $key Setting key.
	 * @return mixed
	 */
	public function get( string $key ) {
		if ( null === $this->values ) {
			$stored       = get_option( self::OPTION, array() );
			$this->values = wp_parse_args( is_array( $stored ) ? $stored : array(), self::defaults() );
		}
		return $this->values[ $key ] ?? null;
	}

	/**
	 * Registers the option with its sanitizer.
	 */
	public function register_settings(): void {
		register_setting(
			'ajrwd_core',
			self::OPTION,
			array(
				'type'              => 'array',
				'sanitize_callback' => array( $this, 'sanitize' ),
				'default'           => self::defaults(),
			)
		);
	}

	/**
	 * Sanitizes the submitted settings array.
	 *
	 * @param mixed $input Raw submitted value.
	 * @return array<string,mixed>
	 */
	public function sanitize( $input ): array {
		$input = is_array( $input ) ? $input : array();
		$clean = array();

		$clean['case_studies_cpt'] = empty( $input['case_studies_cpt'] ) ? 0 : 1;
		$clean['analytics']        = empty( $input['analytics'] ) ? 0 : 1;

		$ga4_id = isset( $input['ga4_id'] ) ? sanitize_text_field( $input['ga4_id'] ) : '';
		// GA4 measurement IDs look like G-XXXXXXXXXX.
		$clean['ga4_id'] = preg_match( '/^G-[A-Z0-9]{4,}$/', $ga4_id ) ? $ga4_id : '';

		return $clean;
	}

	/**
	 * Adds the settings page under Settings.
	 */
	public function add_page(): void {
		add_options_page(
			__( 'AJR Core', 'ajrwebdesign-core' ),
			__( 'AJR Core', 'ajrwebdesign-core' ),
			'manage_options',
			'ajrwd-core',
			array( $this, 'render_page' )
		);
	}

	/**
	 * Renders the settings form.
	 */
	public function render_page(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}
		$values = wp_parse_args( (array) get_option( self::OPTION, array() ), self::defaults() );
		?>
		<div class="wrap">
			<h1><?php esc_html_e( 'AJR Web Design Core', 'ajrwebdesign-core' ); ?></h1>
			<form method="post" action="options.php">
				<?php settings_fields( 'ajrwd_core' ); ?>
				<table class="form-table" role="presentation">
					<tr>
						<th scope="row"><?php esc_html_e( 'Case studies', 'ajrwebdesign-core' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="<?php echo esc_attr( self::OPTION ); ?>[case_studies_cpt]" value="1" <?php checked( ! empty( $values['case_studies_cpt'] ) ); ?> />
								<?php esc_html_e( 'Enable the Case Studies post type', 'ajrwebdesign-core' ); ?>
							</label>
						</td>
					</tr>
					<tr>
						<th scope="row"><?php esc_html_e( 'Analytics', 'ajrwebdesign-core' ); ?></th>
						<td>
							<label>
								<input type="checkbox" name="<?php echo esc_attr( self::OPTION ); ?>[analytics]" value="1" <?php checked( ! empty( $values['analytics'] ) ); ?> />
								<?php esc_html_e( 'Output the GA4 tag (consent-gated)', 'ajrwebdesign-core' ); ?>
							</label>
							<p>
								<label for="ajrwd-ga4-id"><?php esc_html_e( 'GA4 measurement ID', 'ajrwebdesign-core' ); ?></label>
								<input type="text" id="ajrwd-ga4-id" name="<?php echo esc_attr( self::OPTION ); ?>[ga4_id]" value="<?php echo esc_attr( $values['ga4_id'] ); ?>" placeholder="G-XXXXXXXXXX" class="regular-text" />
							</p>
						</td>
					</tr>
				</table>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}
}
