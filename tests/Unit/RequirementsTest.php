<?php
/**
 * Requirements tests.
 *
 * @package AJR\SiteCore
 */

namespace AJR\SiteCore\Tests\Unit;

use AJR\SiteCore\Core\Requirements;
use PHPUnit\Framework\TestCase;

/**
 * The missing-module warning.
 */
class RequirementsTest extends TestCase {

	/**
	 * Both post types registered, an administrator.
	 */
	protected function setUp(): void {
		$GLOBALS['ajrwd_test_post_types'] = array( 'ajr_case_study', 'ajr_testimonial' );
		$GLOBALS['ajrwd_test_can']        = true;
	}

	public function test_nothing_missing_prints_nothing(): void {
		$this->assertSame( array(), ( new Requirements() )->missing() );

		ob_start();
		( new Requirements() )->notice();
		$this->assertSame( '', ob_get_clean() );
	}

	public function test_missing_modules_are_named(): void {
		$GLOBALS['ajrwd_test_post_types'] = array( 'ajr_testimonial' );
		$this->assertSame( array( 'Case studies' ), ( new Requirements() )->missing() );

		$GLOBALS['ajrwd_test_post_types'] = array();
		ob_start();
		( new Requirements() )->notice();
		$html = ob_get_clean();
		$this->assertStringStartsWith( '<div class="notice notice-error"><p>', $html );
		$this->assertStringContainsString( 'Case studies, Testimonials', $html );
	}

	public function test_notice_only_for_administrators(): void {
		$GLOBALS['ajrwd_test_post_types'] = array();
		$GLOBALS['ajrwd_test_can']        = false;

		ob_start();
		( new Requirements() )->notice();
		$this->assertSame( '', ob_get_clean() );
	}
}
