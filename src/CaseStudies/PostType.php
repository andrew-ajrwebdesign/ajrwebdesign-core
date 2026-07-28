<?php
/**
 * Case Studies post type + taxonomy.
 *
 * @package AJR\SiteCore
 */

namespace AJR\SiteCore\CaseStudies;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the `ajr_case_study` post type (same name as the legacy plugin so
 * existing posts carry over) and its tag taxonomy. Unlike the legacy version,
 * case studies are now public with pretty permalinks and an archive.
 */
class PostType {

	public const POST_TYPE = 'ajr_case_study';
	public const TAXONOMY  = 'case_study_tag';

	/**
	 * Hooks registration on init.
	 */
	public function register(): void {
		add_action( 'init', array( $this, 'register_post_type' ) );
		add_action( 'init', array( $this, 'register_taxonomy' ) );
	}

	/**
	 * Registers the post type. Public method so activation can call it
	 * directly before flushing rewrite rules.
	 */
	public function register_post_type(): void {
		register_post_type(
			self::POST_TYPE,
			array(
				'labels'          => array(
					'name'               => __( 'Case Studies', 'ajrwebdesign-core' ),
					'singular_name'      => __( 'Case Study', 'ajrwebdesign-core' ),
					'menu_name'          => __( 'Case Studies', 'ajrwebdesign-core' ),
					'add_new_item'       => __( 'Add New Case Study', 'ajrwebdesign-core' ),
					'edit_item'          => __( 'Edit Case Study', 'ajrwebdesign-core' ),
					'view_item'          => __( 'View Case Study', 'ajrwebdesign-core' ),
					'all_items'          => __( 'All Case Studies', 'ajrwebdesign-core' ),
					'search_items'       => __( 'Search Case Studies', 'ajrwebdesign-core' ),
					'not_found'          => __( 'No case studies found.', 'ajrwebdesign-core' ),
					'not_found_in_trash' => __( 'No case studies found in Trash.', 'ajrwebdesign-core' ),
				),
				'public'          => true,
				'show_in_rest'    => true,
				'has_archive'     => 'case-studies',
				'rewrite'         => array(
					'slug'       => 'case-studies',
					'with_front' => false,
				),
				'menu_icon'       => 'dashicons-analytics',
				'menu_position'   => 21,
				'supports'        => array( 'title', 'editor', 'excerpt', 'thumbnail', 'page-attributes', 'custom-fields' ),
				'capability_type' => 'post',
			)
		);
	}

	/**
	 * Registers the tag taxonomy (editor-facing only, no public archives).
	 */
	public function register_taxonomy(): void {
		register_taxonomy(
			self::TAXONOMY,
			array( self::POST_TYPE ),
			array(
				'labels'            => array(
					'name'          => __( 'Case Study Tags', 'ajrwebdesign-core' ),
					'singular_name' => __( 'Case Study Tag', 'ajrwebdesign-core' ),
					'menu_name'     => __( 'Case Study Tags', 'ajrwebdesign-core' ),
				),
				'hierarchical'      => false,
				'public'            => false,
				'show_ui'           => true,
				'show_admin_column' => true,
				'show_in_rest'      => true,
				'rewrite'           => false,
				'query_var'         => false,
			)
		);
	}
}
