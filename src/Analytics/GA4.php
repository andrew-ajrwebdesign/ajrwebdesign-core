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

		/* Consent Mode updates. Complianz fires cmplz_fire_categories on
		   accept/save and cmplz_revoke on withdrawal; without these the
		   defaults above stay denied forever and nothing is ever measured.
		   Wired here rather than relying on Complianz's own statistics
		   integration, so the tag stays correct whatever that is set to. */
		document.addEventListener('cmplz_fire_categories', function (e) {
			var cats = (e.detail && e.detail.categories) ? e.detail.categories : [];
			var has = function (c) { return cats.indexOf(c) !== -1; };
			var marketing = has('marketing') ? 'granted' : 'denied';
			gtag('consent', 'update', {
				security_storage: 'granted',
				functionality_storage: 'granted',
				personalization_storage: has('preferences') ? 'granted' : 'denied',
				analytics_storage: has('statistics') ? 'granted' : 'denied',
				ad_storage: marketing,
				ad_user_data: marketing,
				ad_personalization: marketing
			});
		});

		document.addEventListener('cmplz_revoke', function () {
			gtag('consent', 'update', {
				personalization_storage: 'denied',
				analytics_storage: 'denied',
				ad_storage: 'denied',
				ad_user_data: 'denied',
				ad_personalization: 'denied'
			});
		});
		</script>
		<?php
	}
}
