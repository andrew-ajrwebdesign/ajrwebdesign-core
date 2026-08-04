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
 * Load-after-consent: no Google byte is downloaded until the visitor grants
 * the statistics category — before that, only this inline stub exists and
 * gtag calls queue harmlessly in the dataLayer. When Complianz grants
 * statistics (first accept, or replayed on every later page view for a
 * returning consenter), the consent state is updated FIRST and gtag.js is
 * injected after it, so Consent Mode v2 processes the queue in the correct
 * order. Declining visitors get a page with zero third-party requests.
 * Frontend only, never for logged-in users, and only when a measurement ID
 * is configured.
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
	 * Prints the consent-gated gtag stub.
	 */
	public function output(): void {
		if ( ! $this->settings->is_enabled( 'analytics' ) || is_user_logged_in() ) {
			return;
		}

		$id = (string) $this->settings->get( 'ga4_id' );
		if ( '' === $id ) {
			return;
		}
		$src = 'https://www.googletagmanager.com/gtag/js?id=' . rawurlencode( $id );
		// The consent-default snippet must execute inline before gtag can load —
		// it cannot go through wp_enqueue_script without losing the ordering
		// guarantee Consent Mode v2 requires.
		?>
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

		/* gtag.js is injected only after statistics consent — never eagerly.
			Until then every gtag() call above and below just queues in the
			dataLayer; if consent never arrives, nothing is ever downloaded
			or sent. */
		var ajrwdGtagLoaded = false;
		function ajrwdLoadGtag() {
			if (ajrwdGtagLoaded) { return; }
			ajrwdGtagLoaded = true;
			var s = document.createElement('script');
			s.async = true;
			s.src = <?php echo wp_json_encode( $src ); ?>;
			document.head.appendChild(s);
		}

		/* Consent Mode updates. Complianz fires cmplz_fire_categories on
			accept/save AND on every page view for a returning consenter;
			cmplz_revoke fires on withdrawal. The update is pushed before
			the script loads so the queue replays with the granted state. */
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
			if (has('statistics')) { ajrwdLoadGtag(); }
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

		/* Lead measurement: WPForms submissions fire generate_lead (marked
			as a key event in the GA4 property). WPForms bundles jQuery, so
			its AJAX success event is a jQuery event; binding happens on DOM
			ready when jQuery exists. The confirmation-container check covers
			non-AJAX submits that land on a reloaded page; sessionStorage
			keeps a refresh of that page from double-counting. Events queue
			pre-consent like everything else and are dropped unless granted. */
		document.addEventListener('DOMContentLoaded', function () {
			if (window.jQuery) {
				window.jQuery(document).on('wpformsAjaxSubmitSuccess', function () {
					gtag('event', 'generate_lead');
				});
			}
			if (document.querySelector('.wpforms-confirmation-container-full')) {
				var k = 'ajrwd_lead_' + location.pathname;
				if (!sessionStorage.getItem(k)) {
					sessionStorage.setItem(k, '1');
					gtag('event', 'generate_lead');
				}
			}
		});
		</script>
		<?php
	}
}
