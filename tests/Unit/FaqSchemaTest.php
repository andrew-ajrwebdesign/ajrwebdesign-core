<?php
/**
 * Tests for the FAQPage schema extraction.
 *
 * @package AJR\SiteCore
 */

use AJR\SiteCore\Schema\Faq;
use PHPUnit\Framework\TestCase;

/**
 * Covers Faq::extract_faq_pairs() — the faq-section scoping and the
 * question/answer extraction from accordion markup.
 */
final class FaqSchemaTest extends TestCase {

	/**
	 * Builds a minimal accordion-item block array.
	 *
	 * @param string $question Question text.
	 * @param string $answer   Answer paragraph text.
	 * @return array Parsed-block shaped array.
	 */
	private function item( string $question, string $answer ): array {
		return array(
			'blockName'   => 'core/accordion-item',
			'attrs'       => array(),
			'innerHTML'   => '',
			'innerContent'=> array(),
			'innerBlocks' => array(
				array(
					'blockName'   => 'core/accordion-heading',
					'attrs'       => array(),
					'innerBlocks' => array(),
					'innerContent'=> array(),
					'innerHTML'   => '<h3 class="wp-block-accordion-heading"><button type="button" class="wp-block-accordion-heading__toggle"><span class="wp-block-accordion-heading__toggle-title">' . $question . '</span><span class="wp-block-accordion-heading__toggle-icon" aria-hidden="true">+</span></button></h3>',
				),
				array(
					'blockName'   => 'core/accordion-panel',
					'attrs'       => array(),
					'innerHTML'   => '',
					'innerContent'=> array(),
					'innerBlocks' => array(
						array(
							'blockName'   => 'core/paragraph',
							'attrs'       => array(),
							'innerBlocks' => array(),
							'innerContent'=> array(),
							'innerHTML'   => '<p>' . $answer . '</p>',
						),
					),
				),
			),
		);
	}

	/**
	 * Wraps blocks in a group carrying the given className.
	 *
	 * @param array  $children Inner blocks.
	 * @param string $class    className attr.
	 * @return array Group block.
	 */
	private function group( array $children, string $class = '' ): array {
		return array(
			'blockName'   => 'core/group',
			'attrs'       => $class ? array( 'className' => $class ) : array(),
			'innerHTML'   => '',
			'innerContent'=> array(),
			'innerBlocks' => $children,
		);
	}

	/**
	 * Items inside a faq-section are extracted with clean text.
	 */
	public function test_extracts_pairs_inside_faq_section(): void {
		$tree = array(
			$this->group(
				array( $this->item( 'Only for WordPress?', 'Yes. <strong>WordPress</strong> only.' ) ),
				'faq-section'
			),
		);
		$pairs = Faq::extract_faq_pairs( $tree );
		$this->assertCount( 1, $pairs );
		$this->assertSame( 'Only for WordPress?', $pairs[0]['question'] );
		$this->assertSame( 'Yes. WordPress only.', $pairs[0]['answer'] );
	}

	/**
	 * Accordions outside a faq-section (e.g. process steps) are ignored.
	 */
	public function test_ignores_accordions_outside_faq_section(): void {
		$tree = array(
			$this->group( array( $this->item( '1. Discover', 'Understand goals.' ) ) ),
			$this->group(
				array( $this->item( 'How long does it take?', 'About a week.' ) ),
				'faq-section'
			),
		);
		$pairs = Faq::extract_faq_pairs( $tree );
		$this->assertCount( 1, $pairs );
		$this->assertSame( 'How long does it take?', $pairs[0]['question'] );
	}

	/**
	 * Items with an empty half are dropped rather than emitted half-formed.
	 */
	public function test_drops_incomplete_items(): void {
		$tree = array(
			$this->group( array( $this->item( 'Question without answer?', '' ) ), 'faq-section' ),
		);
		$this->assertSame( array(), Faq::extract_faq_pairs( $tree ) );
	}
}
