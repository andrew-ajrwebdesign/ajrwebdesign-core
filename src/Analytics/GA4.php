<?php
/**
 * Consent-gated GA4 output.
 *
 * @package AJR\SiteCore
 */

namespace AJR\SiteCore\Analytics;

use AJR\SiteCore\Admin\Settings;

defined( 'ABSPATH' ) || exit;

/**
 * Replaces the old theme's hardcoded, ungated wp_head gtag snippet.
 *
 * Google Consent Mode v2: every consent signal defaults to "denied" before
 * gtag loads; Complianz (active on the site) updates the consent state when
 * the visitor accepts, and gtag upgrades itself. Frontend only, never for
 * logged-in users, and only when a measurement ID is configured.
 */
class GA4 {

	/**
	 * Plugin settings.
	 *
	 * @var Settings
	 */
	private Settings $settings;

	/**
	 * Receives settings by injection.
	 *
	 * @param Settings $settings Plugin settings.
	 */
	public function __construct( Settings $settings ) {
		$this->settings = $settings;
	}

	/**
	 * Hooks the head output.
	 */
	public function register(): void {
		add_action( 'wp_head', array( $this, 'output' ), 5 );
	}

	/**
	 * Prints the consent-defaulted gtag snippet.
	 */
	public function output(): void {
		if ( ! $this->settings->is_enabled( 'analytics' ) || is_user_logged_in() ) {
			return;
		}

		$id = (string) $this->settings->get( 'ga4_id' );
		if ( '' === $id ) {
			return;
		}
		// The consent-default snippet must execute inline before gtag loads —
		// it cannot go through wp_enqueue_script without losing the ordering
		// guarantee Consent Mode v2 requires.
		?>
		<script async src="<?php echo esc_url( 'https://www.googletagmanager.com/gtag/js?id=' . rawurlencode( $id ) ); ?>"></script><?php // phpcs:ignore WordPress.WP.EnqueuedResources.NonEnqueuedScript ?>
		<script>
		window.dataLayer = window.dataLayer || [];
		function gtag(){dataLayer.push(arguments);}
		gtag('consent', 'default', {
			ad_storage: 'denied',
			ad_user_data: 'denied',
			ad_personalization: 'denied',
			analytics_storage: 'denied',
			wait_for_update: 500
		});
		gtag('js', new Date());
		gtag('config', <?php echo wp_json_encode( $id ); ?>);
		</script>
		<?php
	}
}
