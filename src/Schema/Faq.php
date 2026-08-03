<?php
/**
 * FAQPage structured data.
 *
 * @package AJR\SiteCore
 */

namespace AJR\SiteCore\Schema;

defined( 'ABSPATH' ) || exit;

/**
 * Emits schema.org FAQPage JSON-LD for singular content that contains a
 * `faq-section`-classed group with accordion question/answer items. The
 * marker class is written by the theme's FAQ layout, so only genuine FAQ
 * accordions reach search engines — process/step accordions elsewhere on
 * the same page are deliberately ignored.
 */
class Faq {

	/**
	 * Marker class that opts a section's accordions into the schema.
	 */
	public const SECTION_CLASS = 'faq-section';

	/**
	 * Object-cache group for the rendered JSON.
	 */
	public const CACHE_GROUP = 'ajrwd_core';

	/**
	 * Hooks the head output.
	 */
	public function register(): void {
		add_action( 'wp_head', array( $this, 'output_schema' ) );
	}

	/**
	 * Prints the FAQPage JSON-LD when the current singular content has FAQs.
	 *
	 * The JSON is cached in the object cache keyed on the post's modified
	 * time, so the block parse runs at most once per edit per cache lifetime.
	 */
	public function output_schema(): void {
		if ( ! is_singular() ) {
			return;
		}
		$post = get_post();
		if ( ! $post || false === strpos( $post->post_content, self::SECTION_CLASS ) ) {
			return;
		}
		$cache_key = 'faq_schema_' . $post->ID . '_' . md5( $post->post_modified_gmt );
		$json      = wp_cache_get( $cache_key, self::CACHE_GROUP );
		if ( false === $json ) {
			$pairs = self::extract_faq_pairs( parse_blocks( $post->post_content ) );
			$json  = $pairs ? self::build_json( $pairs ) : '';
			wp_cache_set( $cache_key, $json, self::CACHE_GROUP, HOUR_IN_SECONDS );
		}
		if ( '' !== $json ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JSON built by wp_json_encode from stripped strings.
			echo '<script type="application/ld+json">' . $json . '</script>' . "\n";
		}
	}

	/**
	 * Walks a parsed block tree and collects question/answer pairs from
	 * accordion items that sit inside a faq-section group.
	 *
	 * @param array $blocks Parsed blocks (parse_blocks output shape).
	 * @param bool  $in_faq Whether the walker is already inside a faq-section.
	 * @return array[] List of arrays with `question` and `answer` strings.
	 */
	public static function extract_faq_pairs( array $blocks, bool $in_faq = false ): array {
		$pairs = array();
		foreach ( $blocks as $block ) {
			if ( empty( $block['blockName'] ) ) {
				continue;
			}
			$class  = (string) ( $block['attrs']['className'] ?? '' );
			$is_faq = $in_faq || false !== strpos( $class, self::SECTION_CLASS );
			if ( $is_faq && 'core/accordion-item' === $block['blockName'] ) {
				$pair = self::pair_from_item( $block );
				if ( null !== $pair ) {
					$pairs[] = $pair;
				}
				continue;
			}
			if ( ! empty( $block['innerBlocks'] ) ) {
				$pairs = array_merge( $pairs, self::extract_faq_pairs( $block['innerBlocks'], $is_faq ) );
			}
		}
		return $pairs;
	}

	/**
	 * Builds one question/answer pair from an accordion-item block.
	 *
	 * @param array $item Parsed accordion-item block.
	 * @return array|null Pair, or null when either half is empty.
	 */
	protected static function pair_from_item( array $item ): ?array {
		$question = '';
		$answer   = '';
		foreach ( $item['innerBlocks'] as $child ) {
			if ( 'core/accordion-heading' === $child['blockName']
				&& preg_match( '#toggle-title[^>]*>(.*?)</span>#s', (string) $child['innerHTML'], $m ) ) {
				$question = trim( wp_strip_all_tags( $m[1] ) );
			}
			if ( 'core/accordion-panel' === $child['blockName'] ) {
				$answer = trim( self::text_from_blocks( $child['innerBlocks'] ) );
			}
		}
		if ( '' === $question || '' === $answer ) {
			return null;
		}
		return array(
			'question' => $question,
			'answer'   => $answer,
		);
	}

	/**
	 * Flattens a block subtree to plain text.
	 *
	 * @param array $blocks Parsed blocks.
	 * @return string Space-joined text content.
	 */
	protected static function text_from_blocks( array $blocks ): string {
		$text = '';
		foreach ( $blocks as $block ) {
			if ( ! empty( $block['innerHTML'] ) ) {
				$text .= ' ' . trim( wp_strip_all_tags( (string) $block['innerHTML'] ) );
			}
			if ( ! empty( $block['innerBlocks'] ) ) {
				$text .= ' ' . self::text_from_blocks( $block['innerBlocks'] );
			}
		}
		return trim( (string) preg_replace( '/\s+/', ' ', $text ) );
	}

	/**
	 * Encodes the pairs as a schema.org FAQPage JSON-LD string.
	 *
	 * @param array $pairs Question/answer pairs.
	 * @return string JSON.
	 */
	protected static function build_json( array $pairs ): string {
		$entities = array();
		foreach ( $pairs as $pair ) {
			$entities[] = array(
				'@type'          => 'Question',
				'name'           => $pair['question'],
				'acceptedAnswer' => array(
					'@type' => 'Answer',
					'text'  => $pair['answer'],
				),
			);
		}
		return (string) wp_json_encode(
			array(
				'@context'   => 'https://schema.org',
				'@type'      => 'FAQPage',
				'mainEntity' => $entities,
			),
			JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
		);
	}
}
