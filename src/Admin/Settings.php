<?php
/**
 * Plugin settings screen and typed access to stored options.
 *
 * @package AJR\SiteCore
 */

namespace AJR\SiteCore\Admin;

defined( 'ABSPATH' ) || exit;

/**
 * The site plugin's settings screen: its feature toggles, stored as one option row.
 * Tooling access (REST application password) moved to AJR Core → AI agents in 1.7.0.
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
	 * Default settings.
	 *
	 * GA4 moved to AJR Core → Google (1.7.0): its `analytics` and `ga4_id` keys are no longer
	 * declared, so the next save of this screen drops whatever was stored under them.
	 *
	 * @return array<string,mixed>
	 */
	public static function defaults(): array {
		return array(
			'case_studies_cpt' => 1,
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
				</table>
				<?php submit_button(); ?>
			</form>
		</div>
		<?php
	}
}
