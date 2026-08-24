<?php
/**
 * Tests for the consent-gated GA4 output.
 *
 * @package AJR\SiteCore
 */

use AJR\SiteCore\Admin\Settings;
use AJR\SiteCore\Analytics\GA4;
use PHPUnit\Framework\TestCase;

/**
 * Covers GA4::output() — the gates that keep it silent, and the lead
 * measurement wiring that must survive refactors: every lead path fires
 * generate_lead with a lead_type, the booking-domain list reaches the
 * script as JSON, and the binder runs whether the script executes before
 * or after DOMContentLoaded.
 */
final class GA4Test extends TestCase {

	/**
	 * Reset shared test state.
	 */
	protected function setUp(): void {
		$GLOBALS['ajrwd_test_logged_in'] = false;
		$GLOBALS['ajrwd_test_filters']   = array();
	}

	/**
	 * Builds a Settings stub answering only the keys GA4 reads.
	 *
	 * @param bool   $enabled Analytics flag.
	 * @param string $ga4_id  Measurement ID.
	 * @return Settings
	 */
	private function settings( bool $enabled, string $ga4_id ): Settings {
		return new class( $enabled, $ga4_id ) extends Settings {
			/**
			 * Stubbed values.
			 *
			 * @var array<string,mixed>
			 */
			private array $stub;

			/**
			 * Captures the stub values.
			 *
			 * @param bool   $enabled Analytics flag.
			 * @param string $ga4_id  Measurement ID.
			 */
			public function __construct( bool $enabled, string $ga4_id ) {
				$this->stub = array(
					'analytics' => $enabled ? 1 : 0,
					'ga4_id'    => $ga4_id,
				);
			}

			/**
			 * Returns the stubbed value.
			 *
			 * @param string $key Setting key.
			 * @return mixed
			 */
			public function get( string $key ) {
				return $this->stub[ $key ] ?? null;
			}
		};
	}

	/**
	 * Runs output() and returns what it printed.
	 *
	 * @param Settings $settings Settings stub.
	 * @return string
	 */
	private function render( Settings $settings ): string {
		ob_start();
		( new GA4( $settings ) )->output();
		return (string) ob_get_clean();
	}

	/**
	 * Disabled analytics prints nothing.
	 */
	public function test_disabled_outputs_nothing(): void {
		$this->assertSame( '', $this->render( $this->settings( false, 'G-TEST123' ) ) );
	}

	/**
	 * A missing measurement ID prints nothing even when enabled.
	 */
	public function test_empty_id_outputs_nothing(): void {
		$this->assertSame( '', $this->render( $this->settings( true, '' ) ) );
	}

	/**
	 * Logged-in users never get the tag.
	 */
	public function test_logged_in_outputs_nothing(): void {
		$GLOBALS['ajrwd_test_logged_in'] = true;
		$this->assertSame( '', $this->render( $this->settings( true, 'G-TEST123' ) ) );
	}

	/**
	 * The consent default must precede the config call so Consent Mode v2
	 * processes the queue denied-first.
	 */
	public function test_consent_default_precedes_config(): void {
		$out     = $this->render( $this->settings( true, 'G-TEST123' ) );
		$default = strpos( $out, "gtag('consent', 'default'" );
		$config  = strpos( $out, "gtag('config'" );
		$this->assertNotFalse( $default );
		$this->assertNotFalse( $config );
		$this->assertLessThan( $config, $default );
	}

	/**
	 * Every lead path fires generate_lead with its lead_type.
	 */
	public function test_lead_paths_present(): void {
		$out = $this->render( $this->settings( true, 'G-TEST123' ) );
		$this->assertStringContainsString( "gtag('event', 'generate_lead', { lead_type: type })", $out );
		$this->assertStringContainsString( "ajrwdLead('form_submit')", $out );
		$this->assertStringContainsString( "ajrwdLead('booking_click')", $out );
		$this->assertStringContainsString( "ajrwdLead('email_click')", $out );
		$this->assertStringContainsString( "ajrwdLead('phone_click')", $out );
		$this->assertStringContainsString( 'wpformsAjaxSubmitSuccess', $out );
	}

	/**
	 * The binder must run regardless of when the script executes — a
	 * DOMContentLoaded-only registration goes dead under any optimizer
	 * that runs the bundle late.
	 */
	public function test_ready_state_binder(): void {
		$out = $this->render( $this->settings( true, 'G-TEST123' ) );
		$this->assertStringContainsString( "document.readyState === 'loading'", $out );
		$this->assertStringContainsString( "addEventListener('DOMContentLoaded', ajrwdBindLeads)", $out );
	}

	/**
	 * The default booking domain reaches the script as JSON.
	 */
	public function test_default_booking_domain_in_output(): void {
		$out = $this->render( $this->settings( true, 'G-TEST123' ) );
		$this->assertStringContainsString( 'var bookingDomains = ["calendly.com"]', $out );
	}

	/**
	 * The ajrwd_booking_domains filter replaces the list, junk entries are
	 * dropped rather than serialized, and mixed case is lowercased —
	 * URL.hostname is always lowercase, so a 'Cal.com' left as-is would
	 * silently never match a click.
	 */
	public function test_booking_domains_filterable(): void {
		$GLOBALS['ajrwd_test_filters']['ajrwd_booking_domains'] = static function () {
			return array( 'Cal.com', '', 'savvycal.com' );
		};
		$out = $this->render( $this->settings( true, 'G-TEST123' ) );
		$this->assertStringContainsString( 'var bookingDomains = ["cal.com","savvycal.com"]', $out );
		$this->assertStringNotContainsString( 'calendly.com', $out );
	}

	/**
	 * The WPForms binding retries after LiteSpeed drains its delayed queue,
	 * flag-guarded — an unguarded rebind would double-count every form lead.
	 */
	public function test_form_binding_retry_present(): void {
		$out = $this->render( $this->settings( true, 'G-TEST123' ) );
		$this->assertStringContainsString( "addEventListener('DOMContentLiteSpeedLoaded', ajrwdBindForm)", $out );
		$this->assertStringContainsString( 'if (formBound || !window.jQuery)', $out );
	}
}
