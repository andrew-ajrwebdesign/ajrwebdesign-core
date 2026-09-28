<?php
/**
 * German testimonial text tests.
 *
 * @package AJR\SiteCore
 */

namespace AJR\SiteCore\Tests\Unit;

use AJR\SiteCore\Testimonials\German;
use PHPUnit\Framework\TestCase;

/**
 * On German pages AJR Core's quote and role become the German fields, where filled in.
 */
class TestimonialsGermanTest extends TestCase {

	/**
	 * Fresh meta and language for every test.
	 */
	protected function setUp(): void {
		$GLOBALS['ajrwd_test_meta'] = array();
		$GLOBALS['ajrwd_test_lang'] = 'de';
	}

	/**
	 * The fields AJR Core passes in.
	 *
	 * @return array<string,string>
	 */
	private function english(): array {
		return array(
			'quote' => 'Great work.',
			'role'  => 'Owner, Acme',
		);
	}

	public function test_german_page_uses_german_fields(): void {
		update_post_meta( 7, German::QUOTE_DE, 'Tolle Arbeit.' );
		update_post_meta( 7, German::ROLE_DE, 'Inhaber, Acme' );

		$this->assertSame(
			array(
				'quote' => 'Tolle Arbeit.',
				'role'  => 'Inhaber, Acme',
			),
			( new German() )->german( $this->english(), 7 )
		);
	}

	public function test_empty_german_field_falls_back_to_english(): void {
		update_post_meta( 7, German::QUOTE_DE, 'Tolle Arbeit.' );
		update_post_meta( 7, German::ROLE_DE, '   ' );

		$out = ( new German() )->german( $this->english(), 7 );
		$this->assertSame( 'Tolle Arbeit.', $out['quote'] );
		$this->assertSame( 'Owner, Acme', $out['role'] );
	}

	public function test_english_page_is_untouched(): void {
		$GLOBALS['ajrwd_test_lang'] = 'en';
		update_post_meta( 7, German::QUOTE_DE, 'Tolle Arbeit.' );

		$this->assertSame( $this->english(), ( new German() )->german( $this->english(), 7 ) );
	}
}
