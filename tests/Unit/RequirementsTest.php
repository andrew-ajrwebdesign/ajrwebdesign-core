<?php
/**
 * Requirements tests.
 *
 * @package AJR\SiteCore
 */

namespace AJR\SiteCore\Tests\Unit;

use AJR\Core\Framework\Config;
use AJR\Core\Site\Private_Taxonomy_Query;
use AJR\SiteCore\Core\Requirements;
use PHPUnit\Framework\TestCase;

/**
 * The Featured Work warning. (Until 1.15.0 this also warned when AJR Core's case-study or
 * testimonial module was off; this plugin registers those types now, so off is normal.)
 */
class RequirementsTest extends TestCase {

	/**
	 * An administrator; the case-study tag taxonomy registered, one case study tagged "featured",
	 * and AJR Core's private-taxonomies module OFF — the state the warning exists for.
	 */
	protected function setUp(): void {
		$GLOBALS['ajrwd_test_can']        = true;
		$GLOBALS['ajrwd_test_taxonomies'] = array( 'case_study_tag' );
		$GLOBALS['ajrwd_test_terms']      = array( 'case_study_tag' => array( 'featured' => new \WP_Term( 1 ) ) );
		Config::$values                   = array( 'modules.private_taxonomies' => false );
		Private_Taxonomy_Query::$list     = array();
	}

	/**
	 * The notice HTML for the current state.
	 */
	protected function notice(): string {
		ob_start();
		( new Requirements() )->notice();
		return (string) ob_get_clean();
	}

	public function test_featured_filter_ignored_warns_an_administrator(): void {
		$this->assertTrue( ( new Requirements() )->featured_filter_off() );
		$html = $this->notice();
		$this->assertStringStartsWith( '<div class="notice notice-warning"><p>', $html );
		$this->assertStringContainsString( 'Featured Work', $html );
	}

	public function test_module_on_but_taxonomy_not_listed_still_warns(): void {
		Config::$values = array( 'modules.private_taxonomies' => true );
		$this->assertTrue( ( new Requirements() )->featured_filter_off() );
	}

	public function test_set_up_correctly_says_nothing(): void {
		Config::$values               = array( 'modules.private_taxonomies' => true );
		Private_Taxonomy_Query::$list = array( 'case_study_tag' );

		$this->assertFalse( ( new Requirements() )->featured_filter_off() );
		$this->assertSame( '', $this->notice() );
	}

	public function test_nothing_tagged_featured_says_nothing(): void {
		$GLOBALS['ajrwd_test_terms'] = array();
		$this->assertFalse( ( new Requirements() )->featured_filter_off() );

		$GLOBALS['ajrwd_test_terms'] = array( 'case_study_tag' => array( 'featured' => new \WP_Term( 0 ) ) );
		$this->assertFalse( ( new Requirements() )->featured_filter_off(), 'A featured tag with no case studies.' );
	}

	public function test_notice_only_for_administrators(): void {
		$GLOBALS['ajrwd_test_can'] = false;
		$this->assertSame( '', $this->notice() );
	}
}
