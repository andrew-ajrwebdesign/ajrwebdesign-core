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
 * order. Visitors who never grant get a page with zero third-party requests
 * (the one exception: a mid-session revoke keeps the already-loaded gtag.js,
 * which then sends Google's documented cookieless denied-state pings).
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
		gtag('config', <?php echo wp_json_encode( $id, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT ); ?>);

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
			s.src = <?php echo wp_json_encode( $src, JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT ); ?>;
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

		/* Lead measurement: every lead path fires generate_lead (a key
			event in the GA4 property) with a lead_type parameter so the
			sources stay distinguishable — form_submit (WPForms), booking_click
			(outbound to the booking domain, the site's primary CTA),
			email_click (mailto:) and phone_click (tel:). The parameter has a
			registered event-scoped custom dimension; GA4 discards parameters
			collected before their dimension exists, so the dimension must be
			created BEFORE this ships. Events queue pre-consent like everything
			else: a later grant replays the queue and sends them with granted
			state — if consent never arrives, gtag.js never loads and nothing
			leaves the browser. */
		function ajrwdLead(type) {
			gtag('event', 'generate_lead', { lead_type: type });
		}
		function ajrwdBindLeads() {
			/* Click paths bind FIRST so nothing later in this function can
				throw before they exist. One delegated listener, capture phase
				so a stopPropagation in some future widget cannot eat it.
				gtag's beacon transport survives the navigation the click
				starts. href is lowercased for the prefix checks only — an
				editor-typed MAILTO: navigates fine and must still count. */
			var bookingDomains = <?php echo wp_json_encode( $this->booking_domains(), JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT ); ?>;
			document.addEventListener('click', function (e) {
				var a = e.target && e.target.closest ? e.target.closest('a[href]') : null;
				if (!a) { return; }
				var href = (a.getAttribute('href') || '').toLowerCase();
				if (href.indexOf('mailto:') === 0) { ajrwdLead('email_click'); return; }
				if (href.indexOf('tel:') === 0) { ajrwdLead('phone_click'); return; }
				var host;
				try { host = new URL(a.href, location.href).hostname; } catch (err) { return; }
				for (var i = 0; i < bookingDomains.length; i++) {
					var d = bookingDomains[i];
					if (host === d || host.slice(-(d.length + 1)) === '.' + d) { ajrwdLead('booking_click'); return; }
				}
			}, true);

			/* WPForms bundles jQuery, so its AJAX success event is a jQuery
				event — and jQuery may not exist yet when this stub executes
				inside a delayed/combined bundle. Retry the binding after
				LiteSpeed drains its queue (DOMContentLiteSpeedLoaded) and on
				window load, flag-guarded so the handler can never bind twice
				(an unguarded rebind would double-count every form lead). */
			var formBound = false;
			function ajrwdBindForm() {
				if (formBound || !window.jQuery) { return; }
				formBound = true;
				window.jQuery(document).on('wpformsAjaxSubmitSuccess', function () {
					ajrwdLead('form_submit');
				});
			}
			ajrwdBindForm();
			document.addEventListener('DOMContentLiteSpeedLoaded', ajrwdBindForm);
			window.addEventListener('load', ajrwdBindForm);

			/* Non-AJAX submits land on a reloaded page carrying the
				confirmation container (an AJAX success injects it after this
				runs, so the paths cannot double-count); sessionStorage keeps a
				refresh of that page from re-counting, and may THROW under
				blocked-storage browser modes — hence the try/catch, after the
				click paths are already bound. NOTE: a form set to redirect or
				"show page" confirmation fires NOTHING on either path — both
				live forms use message confirmation; keep it that way or add a
				path for it. */
			try {
				if (document.querySelector('.wpforms-confirmation-container-full')) {
					var k = 'ajrwd_lead_' + location.pathname;
					if (!sessionStorage.getItem(k)) {
						sessionStorage.setItem(k, '1');
						ajrwdLead('form_submit');
					}
				}
			} catch (err) {}
		}
		if (document.readyState === 'loading') {
			document.addEventListener('DOMContentLoaded', ajrwdBindLeads);
		} else {
			/* An optimizer that runs this bundle after DOMContentLoaded would
				otherwise mean the listeners never bind — readyState makes the
				binding independent of when the script executes. */
			ajrwdBindLeads();
		}
		</script>
		<?php
	}

	/**
	 * Hostnames whose outbound clicks count as a booking lead.
	 *
	 * A subdomain of a listed domain matches too (calendly.com covers
	 * andrew.calendly.com). Filterable so a booking-tool change is a
	 * one-line site adjustment, not a code edit. Values are lowercased here
	 * because URL.hostname is always lowercase — a filter returning
	 * 'Cal.com' would otherwise silently never match a click. Bare
	 * hostnames only: a value carrying a scheme or path never matches.
	 *
	 * @return string[] Lowercase bare domains.
	 */
	protected function booking_domains(): array {
		$domains = apply_filters( 'ajrwd_booking_domains', array( 'calendly.com' ) );
		return array_values( array_filter( array_map( 'strtolower', array_map( 'strval', (array) $domains ) ) ) );
	}
}
