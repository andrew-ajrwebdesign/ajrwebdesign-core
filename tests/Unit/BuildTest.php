<?php
/**
 * Site-build case study tests.
 *
 * @package AJR\SiteCore
 */

namespace AJR\SiteCore\Tests\Unit;

use AJR\SiteCore\Blocks\Build;
use AJR\SiteCore\Blocks\Cards;
use AJR\SiteCore\CaseStudies\BuildCopy;
use AJR\SiteCore\CaseStudies\Meta;
use AJR\SiteCore\I18n\AttachmentAlt;
use PHPUnit\Framework\TestCase;

/**
 * The site-build fields' sanitizers and the pure parts of their rendering.
 */
class BuildTest extends TestCase {

	protected function setUp(): void {
		$GLOBALS['ajrwd_test_meta'] = array();
		$GLOBALS['ajrwd_test_lang'] = '';
	}

	public function test_kind_is_audit_unless_it_is_exactly_build(): void {
		$this->assertSame( Meta::KIND_BUILD, Meta::sanitize_kind( 'build' ) );
		$this->assertSame( Meta::KIND_AUDIT, Meta::sanitize_kind( 'audit' ) );
		$this->assertSame( Meta::KIND_AUDIT, Meta::sanitize_kind( '' ) );
		$this->assertSame( Meta::KIND_AUDIT, Meta::sanitize_kind( 'anything-else' ) );
		$this->assertSame( Meta::KIND_AUDIT, Meta::sanitize_kind( array( 'build' ) ) );
	}

	public function test_is_build_reads_the_kind(): void {
		$this->assertFalse( Build::is_build( 9 ) );

		update_post_meta( 9, Meta::KIND, Meta::KIND_BUILD );
		$this->assertTrue( Build::is_build( 9 ) );
	}

	public function test_build_always_comes_back_in_full_shape(): void {
		$this->assertSame( Meta::empty_build(), Meta::sanitize_build( 'not an array' ) );
		$this->assertSame( Meta::empty_build(), Meta::sanitize_build( array( 'unknown' => 'dropped' ) ) );
	}

	public function test_scores_are_whole_numbers_from_0_to_100_or_empty(): void {
		$clean = Meta::sanitize_build(
			array(
				'scores' => array(
					'mobile'  => array(
						'performance'    => '98',
						'accessibility'  => ' 100 ',
						'best_practices' => '250',
						'seo'            => 'ninety',
					),
					'desktop' => array(
						'performance' => '-5',
						'seo'         => '99.5',
						'made_up'     => '100',
					),
				),
			)
		);

		$this->assertSame( '98', $clean['scores']['mobile']['performance'] );
		$this->assertSame( '100', $clean['scores']['mobile']['accessibility'] );
		// Over 100 is capped; words, negatives and decimals are not scores.
		$this->assertSame( '100', $clean['scores']['mobile']['best_practices'] );
		$this->assertSame( '', $clean['scores']['mobile']['seo'] );
		$this->assertSame( '', $clean['scores']['desktop']['performance'] );
		$this->assertSame( '', $clean['scores']['desktop']['seo'] );
		$this->assertArrayNotHasKey( 'made_up', $clean['scores']['desktop'] );
	}

	public function test_live_url_must_be_http_or_https(): void {
		$this->assertSame( 'https://example.com/', Meta::sanitize_build( array( 'url' => ' https://example.com/ ' ) )['url'] );
		$this->assertSame( '', Meta::sanitize_build( array( 'url' => 'javascript:alert(1)' ) )['url'] );
		$this->assertSame( '', Meta::sanitize_build( array( 'url' => array( 'https://example.com/' ) ) )['url'] );
	}

	public function test_comparison_rows_need_a_label_and_a_value_and_are_capped_at_three(): void {
		$clean = Meta::sanitize_build(
			array(
				'compare' => array(
					array(
						'label'   => 'Home page weight',
						'value'   => '594 KB',
						'percent' => '20.5',
						'note'    => '<b>Typical</b> page: 2,894 KB.',
					),
					array(
						'label' => 'No value, so dropped',
						'value' => '',
					),
					array(
						'label'   => 'Files',
						'value'   => '27',
						'percent' => '900',
					),
					array(
						'label'   => 'Load',
						'value'   => '1.7s',
						'percent' => '0',
					),
					array(
						'label' => 'A fourth kept row would be one too many',
						'value' => 'x',
					),
				),
			)
		);

		$this->assertCount( Meta::MAX_COMPARE, $clean['compare'] );
		$this->assertSame( 'Home page weight', $clean['compare'][0]['label'] );
		// The bar length is a whole percentage, capped at 100.
		$this->assertSame( 21, $clean['compare'][0]['percent'] );
		$this->assertSame( 100, $clean['compare'][1]['percent'] );
		// 0 (or nothing entered) stays 0, which means "no bar": it must never become a
		// 1% sliver that claims a ratio nobody entered.
		$this->assertSame( 0, $clean['compare'][2]['percent'] );
		// Markup is stripped: a row is plain text.
		$this->assertSame( 'Typical page: 2,894 KB.', $clean['compare'][0]['note'] );
	}

	public function test_facts_need_a_value_and_a_label_and_are_capped_at_four(): void {
		$rows = array();
		for ( $i = 1; $i <= 6; $i++ ) {
			$rows[] = array(
				'value' => (string) $i,
				'label' => 'Fact ' . $i,
			);
		}
		$rows[] = array(
			'value' => '7 days',
			'label' => '',
		);

		$clean = Meta::sanitize_build( array( 'facts' => $rows ) );

		$this->assertCount( Meta::MAX_FACTS, $clean['facts'] );
		$this->assertSame( array( 'value', 'label', 'note' ), array_keys( $clean['facts'][0] ) );
		$this->assertSame( '', $clean['facts'][0]['note'] );
	}

	public function test_row_text_is_capped(): void {
		$clean = Meta::sanitize_build( array( 'intro' => str_repeat( 'a', 400 ) ) );

		$this->assertSame( Meta::MAX_ROW_TEXT, strlen( $clean['intro'] ) );
	}

	public function test_gallery_accepts_a_list_or_the_metabox_string(): void {
		$this->assertSame( array( 12, 7, 30 ), Meta::sanitize_gallery( '12, 7,7 , abc, -4, 0, 30' ) );
		$this->assertSame( array( 5, 6 ), Meta::sanitize_gallery( array( '5', 6, '5', array( 9 ) ) ) );
		$this->assertSame( array(), Meta::sanitize_gallery( '' ) );
		$this->assertSame( array(), Meta::sanitize_gallery( null ) );
	}

	public function test_gallery_is_capped(): void {
		$this->assertCount( Meta::MAX_GALLERY, Meta::sanitize_gallery( range( 1, 40 ) ) );
	}

	public function test_score_level_follows_lighthouse_thresholds(): void {
		$this->assertSame( 'good', Build::score_level( '100' ) );
		$this->assertSame( 'good', Build::score_level( '90' ) );
		$this->assertSame( 'fair', Build::score_level( '89' ) );
		$this->assertSame( 'fair', Build::score_level( '50' ) );
		$this->assertSame( 'poor', Build::score_level( '49' ) );
		$this->assertSame( 'poor', Build::score_level( '0' ) );
		$this->assertSame( '', Build::score_level( '' ) );
		$this->assertSame( '', Build::score_level( 'n/a' ) );
	}

	public function test_rings_print_nothing_without_a_score(): void {
		$this->assertSame( '', Build::rings( Meta::empty_build()['scores']['mobile'] ) );
	}

	public function test_rings_carry_the_score_a_spoken_label_and_skip_empty_categories(): void {
		$html = Build::rings(
			array(
				'performance'    => '98',
				'accessibility'  => '100',
				'best_practices' => '',
				'seo'            => '45',
			),
			'md'
		);

		$this->assertStringContainsString( 'class="ajr-cs-scores ajr-cs-scores--md"', $html );
		$this->assertStringContainsString( 'style="--score:98"', $html );
		$this->assertStringContainsString( 'aria-label="Performance: 98 out of 100"', $html );
		$this->assertStringContainsString( 'ajr-cs-ring--poor', $html );
		// Three scores, three rings: the empty category is left out, not drawn as 0.
		$this->assertSame( 3, substr_count( $html, 'role="img"' ) );
		$this->assertStringNotContainsString( 'Best practices', $html );
	}

	public function test_rings_without_labels_still_name_each_score(): void {
		$html = Build::rings( array( 'performance' => '98' ), 'sm', false );

		$this->assertStringNotContainsString( 'ajr-cs-score__label', $html );
		$this->assertStringContainsString( 'aria-label="Performance: 98 out of 100"', $html );
	}

	public function test_rings_are_spoken_in_german_on_german_pages(): void {
		$GLOBALS['ajrwd_test_lang'] = 'de';

		$html = Build::rings( array( 'accessibility' => '100' ) );

		$this->assertStringContainsString( 'aria-label="Barrierefreiheit: 100 von 100"', $html );
		// The printed label may break at a soft hyphen (a phone column is narrower than
		// the word); the spoken one above stays a plain word.
		$this->assertStringContainsString( ">Barriere\xC2\xADfreiheit<", $html );
	}

	public function test_replace_text_keeps_the_tag_and_escapes_the_text(): void {
		$this->assertSame(
			'<h2 class="wp-block-heading cs-results-title">The build at a glance</h2>',
			BuildCopy::replace_text( '<h2 class="wp-block-heading cs-results-title">The results at a glance</h2>', BuildCopy::TAG_HEADING, 'The build at a glance' )
		);

		// A $ or backslash in the text is text, never a backreference; markup is escaped.
		$this->assertSame(
			'<p class="cs-results-intro">Costs $1 \\2 &lt;b&gt;</p>',
			BuildCopy::replace_text( '<p class="cs-results-intro">Old</p>', BuildCopy::TAG_PARAGRAPH, 'Costs $1 \\2 <b>' )
		);
	}

	public function test_replace_text_refuses_any_other_tag_pattern(): void {
		// The tag goes into a regular expression, so it is one of two constants or nothing.
		$html = '<div class="x">Old</div>';

		$this->assertSame( $html, BuildCopy::replace_text( $html, 'div', 'New' ) );
		$this->assertSame( $html, BuildCopy::replace_text( $html, '.*', 'New' ) );
	}

	public function test_can_show_needs_a_public_post_or_a_reader_and_never_a_locked_one(): void {
		$GLOBALS['ajrwd_test_locked'] = array();
		$GLOBALS['ajrwd_test_public'] = array( 11 );
		$GLOBALS['ajrwd_test_can']    = false;

		// Published for everyone.
		$this->assertTrue( Cards::can_show( 11 ) );
		// A draft: hidden from a visitor, shown to someone who may read it.
		$this->assertFalse( Cards::can_show( 12 ) );
		$GLOBALS['ajrwd_test_can'] = true;
		$this->assertTrue( Cards::can_show( 12 ) );

		// Waiting for its password: hidden even from someone who could read it,
		// and even though the post itself is published.
		$GLOBALS['ajrwd_test_locked'] = array( 11 );
		$this->assertFalse( Cards::can_show( 11 ) );

		$GLOBALS['ajrwd_test_locked'] = array();
		$GLOBALS['ajrwd_test_public'] = array();
		$GLOBALS['ajrwd_test_can']    = false;
	}

	public function test_alt_text_is_german_on_german_pages_and_never_empty(): void {
		$GLOBALS['ajrwd_test_titles'][20] = 'Home page';
		$GLOBALS['ajrwd_test_titles'][21] = 'Equipment';
		update_post_meta( 20, '_wp_attachment_image_alt', 'The home page' );
		update_post_meta( 20, AttachmentAlt::META, 'Die Startseite' );
		update_post_meta( 21, '_wp_attachment_image_alt', 'The equipment page' );

		// English page: the image's own alt, whatever German exists.
		$this->assertSame( 'The home page', AttachmentAlt::for_page( 20 ) );
		// No alt at all: the title, never an empty string.
		$GLOBALS['ajrwd_test_titles'][22] = 'Coffee guide';
		$this->assertSame( 'Coffee guide', AttachmentAlt::for_page( 22 ) );

		$GLOBALS['ajrwd_test_lang'] = 'de';
		$this->assertSame( 'Die Startseite', AttachmentAlt::for_page( 20 ) );
		// No German alt: the English one.
		$this->assertSame( 'The equipment page', AttachmentAlt::for_page( 21 ) );

		$GLOBALS['ajrwd_test_titles'] = array();
	}
}
