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
use AJR\SiteCore\CaseStudies\OptionalBand;
use AJR\SiteCore\CaseStudies\StoryIntro;
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

	public function test_changes_need_a_label_and_a_figure_and_are_capped_at_three(): void {
		$clean = Meta::sanitize_build(
			array(
				'changes' => array(
					array(
						'label'  => 'How often Google shows the site',
						'figure' => '2×',
						'before' => '48.6',
						'note'   => '',
					),
					array(
						'label'  => 'No figure, so dropped',
						'figure' => '',
					),
					array(
						'label'  => 'Answers on page one',
						'figure' => 'EN + ES',
						// Nothing entered: no bars, never a sliver.
					),
					array(
						'label'  => 'Clicks',
						'figure' => '2×',
						'before' => '400',
					),
					array(
						'label'  => 'A fourth kept row would be one too many',
						'figure' => 'x',
					),
				),
			)
		);

		$this->assertCount( Meta::MAX_CHANGES, $clean['changes'] );
		$this->assertSame( array( 'label', 'figure', 'before', 'note' ), array_keys( $clean['changes'][0] ) );
		$this->assertSame( 49, $clean['changes'][0]['before'] );
		$this->assertSame( 0, $clean['changes'][1]['before'] );
		$this->assertSame( 100, $clean['changes'][2]['before'] );
	}

	public function test_delivered_takes_a_list_or_the_metabox_lines(): void {
		$lines = "A custom block theme\r\n\r\n  Every page in <b>English</b> and Spanish  \nEight practice areas";

		$this->assertSame(
			array( 'A custom block theme', 'Every page in English and Spanish', 'Eight practice areas' ),
			Meta::sanitize_build( array( 'delivered' => $lines ) )['delivered']
		);
		$this->assertSame(
			array( 'One', 'Two' ),
			Meta::sanitize_build( array( 'delivered' => array( 'One', '', array( 'nested' ), 'Two' ) ) )['delivered']
		);
		$this->assertCount( Meta::MAX_DELIVERED, Meta::sanitize_build( array( 'delivered' => array_fill( 0, 30, 'Item' ) ) )['delivered'] );
	}

	public function test_an_intro_may_be_longer_than_a_row(): void {
		$clean = Meta::sanitize_build(
			array(
				'changes_intro' => str_repeat( 'a', 400 ),
				'changes_title' => str_repeat( 'b', 400 ),
			)
		);

		$this->assertSame( Meta::MAX_NOTE_TEXT, strlen( $clean['changes_intro'] ) );
		$this->assertSame( Meta::MAX_ROW_TEXT, strlen( $clean['changes_title'] ) );
	}

	public function test_a_band_with_no_block_output_is_empty(): void {
		$empty  = '<section class="wp-block-group alignfull cs-optional-band has-surface-background-color has-background">' . "\n\n" . '</section>';
		$filled = '<section class="wp-block-group alignfull cs-optional-band"><div class="ajr-cs-delivered wp-block-ajrwebdesign-core-case-study-card"><h2>What was delivered</h2></div></section>';
		$card   = '<section class="wp-block-group cs-optional-band"><div class="wp-block-x ajr-cs-related"><div class="ajr-case-study-mini-card"></div></div></section>';

		$this->assertFalse( OptionalBand::has_content( $empty ) );
		$this->assertTrue( OptionalBand::has_content( $filled ) );
		$this->assertTrue( OptionalBand::has_content( $card ) );

		$band = new OptionalBand();
		$this->assertSame( '', $band->drop_if_empty( $empty, array( 'attrs' => array( 'className' => 'cs-optional-band' ) ) ) );
		$this->assertSame( $filled, $band->drop_if_empty( $filled, array( 'attrs' => array( 'className' => 'cs-optional-band' ) ) ) );
		// An ordinary group is never touched, empty or not.
		$this->assertSame( $empty, $band->drop_if_empty( $empty, array( 'attrs' => array( 'className' => 'hero-section' ) ) ) );
		$this->assertSame( $empty, $band->drop_if_empty( $empty, array( 'attrs' => array() ) ) );
	}

	public function test_changes_and_delivered_print_nothing_for_an_audit_or_with_no_rows(): void {
		// An audit: whatever is stored, these two bands are a build's.
		update_post_meta( 31, Meta::BUILD, array( 'delivered' => array( 'Something' ) ) );
		$this->assertSame( '', Build::band( 31, 'delivered' ) );
		$this->assertSame( '', Build::band( 31, 'changes' ) );

		// A build with neither filled in.
		update_post_meta( 32, Meta::KIND, Meta::KIND_BUILD );
		$this->assertSame( '', Build::changes( 32 ) );
		$this->assertSame( '', Build::delivered( 32 ) );
	}

	public function test_row_text_is_capped(): void {
		$clean = Meta::sanitize_build( array( 'source' => str_repeat( 'a', 400 ) ) );

		$this->assertSame( Meta::MAX_ROW_TEXT, strlen( $clean['source'] ) );
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

	public function test_a_care_job_words_its_own_heading_and_scores_line(): void {
		$clean = Meta::sanitize_build(
			array(
				'results_title'  => ' The results at a glance <b>',
				'scores_note'    => 'Mobile speed · was 32',
				'scores_note_de' => 'Mobiles Tempo · vorher 32',
			)
		);

		$this->assertSame( 'The results at a glance', $clean['results_title'] );
		$this->assertSame( 'Mobile speed · was 32', Build::scores_note( $clean ) );

		$GLOBALS['ajrwd_test_lang'] = 'de';
		$this->assertSame( 'Mobiles Tempo · vorher 32', Build::scores_note( $clean ) );
		// No German form entered: the line as written, not the PageSpeed default.
		$clean['scores_note_de'] = '';
		$this->assertSame( 'Mobile speed · was 32', Build::scores_note( $clean ) );
	}

	public function test_the_scores_line_is_pagespeed_mobile_unless_one_is_written(): void {
		$this->assertSame( 'Google PageSpeed · mobile', Build::scores_note( Meta::empty_build() ) );
		// A German line alone is not a line: the English one decides.
		$this->assertSame( 'Google PageSpeed · mobile', Build::scores_note( array( 'scores_note_de' => 'Nur Deutsch' ) ) );

		$GLOBALS['ajrwd_test_lang'] = 'de';
		$this->assertSame( 'Google PageSpeed · Mobil', Build::scores_note( Meta::empty_build() ) );
	}

	public function test_a_care_job_has_its_own_opening_line(): void {
		$care = StoryIntro::intro_for( 'SITE CARE' );

		$this->assertIsString( $care );
		$this->assertNotSame( StoryIntro::intro_for( 'SITE BUILD' ), $care );
		$this->assertNull( StoryIntro::intro_for( 'A CLIENT’S SECTOR' ) );
	}

	public function test_a_build_gets_the_new_site_line_unless_it_is_a_care_job(): void {
		$this->assertSame( 'SITE BUILD', StoryIntro::eyebrow_for( Meta::KIND_BUILD, 'SITE REBUILD' ) );
		$this->assertSame( 'SITE BUILD', StoryIntro::eyebrow_for( Meta::KIND_BUILD, '' ) );
		$this->assertSame( 'SITE CARE', StoryIntro::eyebrow_for( Meta::KIND_BUILD, 'SITE CARE' ) );
		// Typed by hand: any case, stray spaces. It must not fall through to the new-site line.
		$this->assertSame( 'SITE CARE', StoryIntro::eyebrow_for( Meta::KIND_BUILD, ' Site care ' ) );
		// An audit keeps its own eyebrow.
		$this->assertSame( 'ECOMMERCE', StoryIntro::eyebrow_for( Meta::KIND_AUDIT, 'Ecommerce' ) );
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

	public function test_more_case_studies_picks_the_newest_other_build_or_two_audits_and_never_a_hidden_one(): void {
		$GLOBALS['ajrwd_test_locked'] = array();
		$GLOBALS['ajrwd_test_public'] = array( 41, 42, 43, 44 );
		$GLOBALS['ajrwd_test_can']    = false;
		update_post_meta( 42, Meta::KIND, Meta::KIND_BUILD );
		update_post_meta( 44, Meta::KIND, Meta::KIND_BUILD );
		update_post_meta( 45, Meta::KIND, Meta::KIND_BUILD );

		// On an audit's page: the newest build, alone.
		$this->assertSame( array( 42 ), Build::pick_related( array( 41, 42, 43, 44 ), 41 ) );
		// On that build's own page: the next build, never itself.
		$this->assertSame( array( 44 ), Build::pick_related( array( 41, 42, 43, 44 ), 42 ) );
		// No other build: the two newest other audits.
		$this->assertSame( array( 41, 43 ), Build::pick_related( array( 41, 42, 43 ), 42 ) );
		// Alone: nothing.
		$this->assertSame( array(), Build::pick_related( array( 41 ), 41 ) );

		// A build that is no longer public (45 is a draft) but still in a stale cached list
		// is skipped: the list is not the gate.
		$this->assertSame( array( 42 ), Build::pick_related( array( 45, 41, 42 ), 41 ) );
		// One behind a password is skipped too.
		$GLOBALS['ajrwd_test_locked'] = array( 42 );
		$this->assertSame( array( 44 ), Build::pick_related( array( 42, 43, 44 ), 41 ) );

		$GLOBALS['ajrwd_test_locked'] = array();
		$GLOBALS['ajrwd_test_public'] = array();
	}

	public function test_agentic_browsing_is_two_small_numbers_and_never_more_passed_than_scored(): void {
		$this->assertSame( array( 'passed' => 0, 'total' => 0 ), Meta::sanitize_build( array() )['agentic'] );
		$this->assertSame( array( 'passed' => 4, 'total' => 4 ), Meta::sanitize_build( array( 'agentic' => array( 'passed' => '4', 'total' => '4' ) ) )['agentic'] );
		$this->assertSame( array( 'passed' => 2, 'total' => 3 ), Meta::sanitize_build( array( 'agentic' => array( 'passed' => 2, 'total' => 3 ) ) )['agentic'] );
		// A pair that cannot be true is a typing slip and shows nothing: capping "4 of 3"
		// to 3/3 would print a full pass nobody measured.
		$this->assertSame( array( 'passed' => 0, 'total' => 0 ), Meta::sanitize_build( array( 'agentic' => array( 'passed' => 4, 'total' => 3 ) ) )['agentic'] );
		$this->assertSame( array( 'passed' => 0, 'total' => 0 ), Meta::sanitize_build( array( 'agentic' => array( 'passed' => 3, 'total' => 400 ) ) )['agentic'] );
		$this->assertSame( array( 'passed' => 0, 'total' => 0 ), Meta::sanitize_build( array( 'agentic' => array( 'passed' => -2, 'total' => 3 ) ) )['agentic'] );
		$this->assertSame( array( 'passed' => 0, 'total' => 0 ), Meta::sanitize_build( array( 'agentic' => array( 'passed' => 1, 'total' => 1 ) ) )['agentic'] );
		$this->assertSame( array( 'passed' => 0, 'total' => 0 ), Meta::sanitize_build( array( 'agentic' => array( 'passed' => '<b>', 'total' => 'x' ) ) )['agentic'] );
	}

	public function test_agentic_browsing_shows_a_badge_only_for_a_full_pass(): void {
		$none    = Meta::sanitize_build( array() );
		$full    = Meta::sanitize_build( array( 'agentic' => array( 'passed' => 4, 'total' => 4 ) ) );
		$partial = Meta::sanitize_build( array( 'agentic' => array( 'passed' => 2, 'total' => 3 ) ) );

		foreach ( array( 'row', 'badge', 'chip' ) as $shape ) {
			$this->assertSame( '', Build::agentic( $none, $shape ) );
		}

		$badge = Build::agentic( $full, 'badge' );
		$this->assertStringContainsString( '>4/4<', $badge );
		// Without its layout the badge must still read as separate words.
		$this->assertSame( '4/4 Agentic Browsing Ready for AI assistants', wp_strip_all_tags( $badge ) );
		$this->assertStringContainsString( 'Agentic Browsing', $badge );
		$this->assertStringContainsString( 'Ready for AI assistants', $badge );
		$this->assertStringContainsString( 'Agentic Browsing 4/4', Build::agentic( $full, 'chip' ) );
		$this->assertStringContainsString( 'All 4 checks passed', Build::agentic( $full, 'row' ) );
		// A full pass is the highlighted row.
		$this->assertStringContainsString( 'ajr-cs-device--agents', Build::agentic( $full, 'row' ) );

		// A partial result is stated in the scorecard, plainly, and is never a badge.
		$this->assertSame( '', Build::agentic( $partial, 'badge' ) );
		$this->assertSame( '', Build::agentic( $partial, 'chip' ) );
		$this->assertStringContainsString( '2 of 3 checks passed', Build::agentic( $partial, 'row' ) );
		$this->assertStringNotContainsString( 'ajr-cs-device--agents', Build::agentic( $partial, 'row' ) );
	}

	/**
	 * A label typed with a straight apostrophe where the German map has a curly one, or
	 * reworded in one file and not the other, silently shows English on German pages.
	 */
	public function test_every_card_label_in_the_source_has_a_german_form(): void {
		// Used in German as they are, so they have no entry on purpose.
		$same_in_german = array( 'Desktop', 'Agentic Browsing', 'Agentic Browsing %1$d/%2$d', 'Largest Contentful Paint', 'Core Web Vitals' );

		$root  = dirname( __DIR__, 2 );
		$files = array_merge( glob( $root . '/src/*/*.php' ), glob( $root . '/blocks/*/render.php' ) );
		$found = array();
		foreach ( $files as $file ) {
			if ( preg_match_all( '/ui_label\(\s*\'([^\']+)\'\s*\)/', (string) file_get_contents( $file ), $matches ) ) {
				foreach ( $matches[1] as $literal ) {
					$found[ $literal ] = basename( $file );
				}
			}
		}
		$this->assertGreaterThan( 20, count( $found ), 'the scan found too few labels to be reading the source' );

		$GLOBALS['ajrwd_test_lang'] = 'de';
		foreach ( $found as $label => $file ) {
			if ( in_array( $label, $same_in_german, true ) ) {
				continue;
			}
			$this->assertNotSame( $label, Cards::ui_label( $label ), "“{$label}” ({$file}) has no German form" );
		}
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
