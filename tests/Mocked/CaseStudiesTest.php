<?php
/**
 * Unit tests for CaseStudies\CaseStudies (ported from AJR Core 0.15.2, 2026-10-01).
 *
 * WHY THIS FILE EXISTS
 *
 * The post type and taxonomy registration is the one thing here that can never drift: the
 * case studies already on ajrwebdesign.com, and their addresses (/case-studies/<name>/), carried
 * into AJR Core and back with no migration ONLY because the keys and arguments match exactly. A difference would not error —
 * a URL would quietly 404 or an archive would vanish. So the arguments are pinned against a
 * copy of the folio's, along with the rest of what fails quietly: the results sanitiser, the
 * whole-card link, the grid's query and clamps, and "nothing to show = no output, no assets".
 *
 * @package AJR\SiteCore
 */

declare( strict_types=1 );

namespace AJR\SiteCore\Tests\Mocked;

use AJR\Core\Framework\Modules;
use AJR\SiteCore\CaseStudies\CaseStudies;
use WP_Mock;
use WP_Mock\Tools\TestCase;

/**
 * Tests for the case studies module.
 */
class CaseStudiesTest extends TestCase {

	/**
	 * The post type arguments AJR Core 0.15.2 registered on the folio (its defaults, with the
	 * folio's saved "no archive"), minus the labels. ⛔ Every case study's address depends on them.
	 */
	protected const FOLIO_POST_TYPE_ARGS = [
		'public'          => true,
		'show_in_rest'    => true,
		'has_archive'     => false,
		'rewrite'         => [
			'slug'       => 'case-studies',
			'with_front' => false,
		],
		'menu_icon'       => 'dashicons-analytics',
		'menu_position'   => 21,
		'supports'        => [ 'title', 'editor', 'excerpt', 'thumbnail', 'page-attributes', 'custom-fields' ],
		'capability_type' => 'post',
	];

	/**
	 * The folio's taxonomy arguments, minus the labels.
	 */
	protected const FOLIO_TAXONOMY_ARGS = [
		'hierarchical'      => false,
		'public'            => false,
		'show_ui'           => true,
		'show_admin_column' => true,
		'show_in_rest'      => true,
		'rewrite'           => false,
		'query_var'         => false,
	];

	/**
	 * Attachment ID batches passed to _prime_post_caches().
	 *
	 * @var array<int,array<int,int>>
	 */
	protected array $primed = [];

	/**
	 * Translation functions pass through.
	 */
	public function setUp(): void {
		parent::setUp();
		WP_Mock::userFunction( '__' )->andReturnArg( 0 );
	}

	/**
	 * {@inheritDoc}
	 */
	public function tearDown(): void {
		Modules::$on = array();
		parent::tearDown();
	}

	/**
	 * A simple sanitize_title() stand-in: lower-case, spaces to dashes, nothing else.
	 */
	protected function sanitize_title_mock(): void {
		WP_Mock::userFunction( 'sanitize_title' )->andReturnUsing(
			static fn( $s ) => trim( (string) preg_replace( '/[^a-z0-9-]+/', '-', strtolower( trim( (string) $s ) ) ), '-' )
		);
	}

	/**
	 * A sanitize_text_field() stand-in: strip tags, collapse whitespace, trim.
	 */
	protected function sanitize_text_mock(): void {
		WP_Mock::userFunction( 'sanitize_text_field' )->andReturnUsing(
			static fn( $s ) => trim( (string) preg_replace( '/\s+/', ' ', wp_strip_all_tags_stub( (string) $s ) ) )
		);
	}

	/**
	 * A module whose query returns the given posts, recording the arguments it got.
	 *
	 * @param array<int,\WP_Post> $posts Posts to return.
	 */
	protected function module( array $posts = [] ): CaseStudies {
		return new class( $posts ) extends CaseStudies {

			/**
			 * Arguments find() was called with.
			 *
			 * @var array<string,mixed>|null
			 */
			public ?array $args = null;

			/**
			 * Build.
			 *
			 * @param array<int,\WP_Post> $posts Posts to return.
			 */
			public function __construct( protected array $posts ) {}

			/**
			 * No database: return the fixture.
			 *
			 * @param array<string,mixed> $args WP_Query arguments.
			 * @return array<int,\WP_Post>
			 */
			protected function find( array $args ): array {
				$this->args = $args;
				return $this->posts;
			}
		};
	}

	/**
	 * A case study fixture.
	 *
	 * @param int    $id     Post ID.
	 * @param string $status Post status.
	 * @param string $type   Post type.
	 */
	protected function post( int $id, string $status = 'publish', string $type = CaseStudies::POST_TYPE ): \WP_Post {
		return new \WP_Post(
			[
				'ID'            => $id,
				'post_type'     => $type,
				'post_status'   => $status,
				'post_password' => '',
			]
		);
	}

	/**
	 * The functions card() calls, answered from a small in-memory fixture.
	 *
	 * @param array<int,array<string,mixed>> $meta     Post ID => meta key => value.
	 * @param array<int,\WP_Post>            $by_id    Posts get_post() can find.
	 * @param array<int,string>              $excerpts Post ID => written excerpt.
	 */
	protected function render_mocks( array $meta = [], array $by_id = [], array $excerpts = [] ): void {
		$this->sanitize_text_mock();
		WP_Mock::userFunction( 'absint' )->andReturnUsing( static fn( $v ) => abs( (int) $v ) );
		WP_Mock::userFunction( 'get_post' )->andReturnUsing( static fn( $id ) => $by_id[ $id ] ?? null );
		WP_Mock::userFunction( 'get_block_wrapper_attributes' )->andReturnUsing(
			static fn( array $extra ): string => 'class="wp-block-ajr ' . $extra['class'] . '"' . ( isset( $extra['style'] ) ? ' style="' . $extra['style'] . '"' : '' )
		);
		WP_Mock::userFunction( 'esc_html' )->andReturnUsing( static fn( $s ) => htmlspecialchars( (string) $s, ENT_QUOTES ) );
		WP_Mock::userFunction( 'esc_url' )->andReturnUsing( static fn( $s ) => (string) $s );
		WP_Mock::userFunction( 'get_permalink' )->andReturnUsing( static fn( $p ) => 'https://example.test/case-studies/cs-' . $p->ID . '/' );
		WP_Mock::userFunction( 'get_the_title' )->andReturnUsing( static fn( $p ) => 'Project ' . $p->ID );
		WP_Mock::userFunction( 'has_excerpt' )->andReturnUsing( static fn( $p ) => isset( $excerpts[ $p->ID ] ) );
		WP_Mock::userFunction( 'get_the_excerpt' )->andReturnUsing( static fn( $p ) => $excerpts[ $p->ID ] ?? 'AUTO EXCERPT' );
		WP_Mock::userFunction( 'has_post_thumbnail' )->andReturnUsing( static fn( $p ) => ! empty( $meta[ $p->ID ]['_thumbnail_id'] ) );
		WP_Mock::userFunction( 'get_the_post_thumbnail' )->andReturnUsing(
			static fn( $p, $size, $attr ) => '<img class="' . $attr['class'] . '" data-size="' . $size . '" loading="' . ( $attr['loading'] ?? 'CORE' ) . '" sizes="' . $attr['sizes'] . '">'
		);
		WP_Mock::userFunction( 'get_post_meta' )->andReturnUsing(
			static fn( $id, $key ) => $meta[ $id ][ $key ] ?? ''
		);
		$primed = &$this->primed;
		WP_Mock::userFunction( '_prime_post_caches' )->andReturnUsing(
			static function ( $ids ) use ( &$primed ): void {
				$primed[] = $ids;
			}
		);
	}

	/**
	 * ⛔ The keys existing content depends on.
	 */
	public function test_keys_match_the_content_already_on_the_site(): void {
		$this->assertSame( 'ajr_case_study', CaseStudies::POST_TYPE );
		$this->assertSame( 'case_study_tag', CaseStudies::TAXONOMY );
		$this->assertSame( 'ajr_case_study_client', CaseStudies::CLIENT );
		$this->assertSame( 'ajr_case_study_summary', CaseStudies::SUMMARY );
		$this->assertSame( 'ajr_case_study_results', CaseStudies::RESULTS );
		$this->assertSame( 'ajr/case-study-card', CaseStudies::BLOCK_CARD );
		$this->assertSame( 'ajr/case-studies', CaseStudies::BLOCK_GRID );
	}

	/**
	 * Hooks go on in register(), and constructing the module adds none.
	 */
	public function test_register_adds_the_hooks(): void {
		$module = new CaseStudies();

		WP_Mock::expectActionAdded( 'init', [ $module, 'register_post_type' ] );
		WP_Mock::expectActionAdded( 'init', [ $module, 'register_taxonomy' ] );
		WP_Mock::expectActionAdded( 'init', [ $module, 'register_meta' ] );
		WP_Mock::expectActionAdded( 'init', [ $module, 'register_blocks' ] );
		WP_Mock::expectActionAdded( 'enqueue_block_editor_assets', [ $module, 'enqueue_panel' ] );
		WP_Mock::expectActionAdded( 'admin_init', [ $module, 'heal_rewrite_rules' ] );
		WP_Mock::expectFilterAdded( 'rest_prepare_ajr_case_study', [ $module, 'hide_protected_meta' ], 10, 3 );

		$module->register();

		$this->assertConditionsMet();
	}

	/**
	 * ⛔ With default settings, every argument except the labels is the folio's, key for key.
	 */
	public function test_post_type_arguments_match_the_folio_exactly(): void {
		$this->sanitize_title_mock();
		$captured = [];
		WP_Mock::userFunction( 'register_post_type' )->once()->andReturnUsing(
			static function ( $key, $args ) use ( &$captured ) {
				$captured = [ $key, $args ];
			}
		);

		( new CaseStudies() )->register_post_type();

		[ $key, $args ] = $captured;
		$this->assertSame( 'ajr_case_study', $key );
		$labels = $args['labels'];
		unset( $args['labels'] );
		$this->assertSame( self::FOLIO_POST_TYPE_ARGS, $args );
		$this->assertSame( 'Case Studies', $labels['name'] );
		$this->assertSame( 'Case Study', $labels['singular_name'] );
		$this->assertSame( 'Add New Case Study', $labels['add_new_item'] );
		$this->assertSame( 'View Case Study', $labels['view_item'] );
	}

	/**
	 * ⛔ The taxonomy: the folio's arguments exactly, attached to the case study type only.
	 */
	public function test_taxonomy_arguments_match_the_folio_exactly(): void {
		$captured = [];
		WP_Mock::userFunction( 'register_taxonomy' )->once()->andReturnUsing(
			static function ( $key, $types, $args ) use ( &$captured ) {
				$captured = [ $key, $types, $args ];
			}
		);

		( new CaseStudies() )->register_taxonomy();

		[ $key, $types, $args ] = $captured;
		$this->assertSame( 'case_study_tag', $key );
		$this->assertSame( [ 'ajr_case_study' ], $types );
		$labels = $args['labels'];
		unset( $args['labels'] );
		$this->assertSame( self::FOLIO_TAXONOMY_ARGS, $args );
		$this->assertSame( 'Case study tags', $labels['name'] );
	}

	/**
	 * Names, slug and archive come from the filter; the slug is sanitised and feeds BOTH the
	 * archive and the single rewrite; a blank name falls back rather than going nameless.
	 */
	public function test_filter_drives_labels_slug_and_archive(): void {
		WP_Mock::onFilter( 'ajrwd_case_studies_settings' )
			->with( CaseStudies::SETTINGS )
			->reply(
				[
					'plural'      => 'Projects',
					'singular'    => '',
					'slug'        => 'Our Work',
					'has_archive' => true,
					'tag_label'   => 'Services',
				]
			);
		$this->sanitize_title_mock();
		$post_type = [];
		$taxonomy  = [];
		WP_Mock::userFunction( 'register_post_type' )->andReturnUsing(
			static function ( $key, $args ) use ( &$post_type ) {
				$post_type = $args;
			}
		);
		WP_Mock::userFunction( 'register_taxonomy' )->andReturnUsing(
			static function ( $key, $types, $args ) use ( &$taxonomy ) {
				$taxonomy = $args;
			}
		);

		$module = new CaseStudies();
		$module->register_post_type();
		$module->register_taxonomy();

		$this->assertSame( 'Projects', $post_type['labels']['name'] );
		$this->assertSame( 'Case Study', $post_type['labels']['singular_name'] );
		$this->assertSame( 'our-work', $post_type['has_archive'] );
		$this->assertSame( 'our-work', $post_type['rewrite']['slug'] );
		$this->assertSame( 'Services', $taxonomy['labels']['name'] );
	}

	/**
	 * A filter returning the wrong types keeps the defaults: an archive switch that is not a
	 * boolean stays off, and a slug that sanitises to nothing falls back to the folio's rather
	 * than registering singles at the site root.
	 */
	public function test_bad_filter_values_keep_the_defaults(): void {
		WP_Mock::onFilter( 'ajrwd_case_studies_settings' )
			->with( CaseStudies::SETTINGS )
			->reply(
				[
					'has_archive' => 'yes',
					'slug'        => '%%%',
					'plural'      => [ 'x' ],
				]
			);
		$this->sanitize_title_mock();
		$captured = [];
		WP_Mock::userFunction( 'register_post_type' )->andReturnUsing(
			static function ( $key, $args ) use ( &$captured ) {
				$captured = $args;
			}
		);

		( new CaseStudies() )->register_post_type();

		$this->assertFalse( $captured['has_archive'] );
		$this->assertSame( 'case-studies', $captured['rewrite']['slug'] );
		$this->assertSame( 'Case Studies', $captured['labels']['name'] );
	}

	/**
	 * The fields: strings and a results array, in REST with a closed schema, edit_posts only.
	 */
	public function test_meta_registration(): void {
		$captured = [];
		WP_Mock::userFunction( 'register_post_meta' )->times( 3 )->andReturnUsing(
			static function ( $type, $key, $args ) use ( &$captured ) {
				$captured[ $key ] = [ $type, $args ];
			}
		);
		WP_Mock::userFunction( 'current_user_can' )->with( 'edit_posts' )->andReturn( false );

		( new CaseStudies() )->register_meta();

		$this->assertSame( [ CaseStudies::CLIENT, CaseStudies::SUMMARY, CaseStudies::RESULTS ], array_keys( $captured ) );

		foreach ( $captured as [ $type, $args ] ) {
			$this->assertSame( 'ajr_case_study', $type );
			$this->assertTrue( $args['single'] );
			$this->assertFalse( call_user_func( $args['auth_callback'] ) );
			$this->assertNotEmpty( $args['show_in_rest'] );
		}

		$this->assertSame( 'string', $captured[ CaseStudies::CLIENT ][1]['type'] );
		$this->assertSame( 'sanitize_text_field', $captured[ CaseStudies::CLIENT ][1]['sanitize_callback'] );
		$this->assertSame( 'string', $captured[ CaseStudies::SUMMARY ][1]['type'] );
		$this->assertSame( 'sanitize_textarea_field', $captured[ CaseStudies::SUMMARY ][1]['sanitize_callback'] );

		$results = $captured[ CaseStudies::RESULTS ][1];
		$this->assertSame( 'array', $results['type'] );
		$this->assertSame( [], $results['default'] );
		$this->assertSame( [ CaseStudies::class, 'sanitize_results' ], $results['sanitize_callback'] );
		$items = $results['show_in_rest']['schema']['items'];
		$this->assertSame( 'object', $items['type'] );
		$this->assertFalse( $items['additionalProperties'] );
		$this->assertSame( [ 'label', 'before', 'after' ], array_keys( $items['properties'] ) );
		foreach ( $items['properties'] as $property ) {
			$this->assertSame( 'string', $property['type'] );
		}
	}

	/**
	 * Results: cleaned, empty and half-filled rows dropped, extra keys ignored, capped at six.
	 */
	public function test_results_are_sanitised_and_capped(): void {
		$this->sanitize_text_mock();

		$this->assertSame( [], CaseStudies::sanitize_results( 'not a list' ) );
		$this->assertSame( [], CaseStudies::sanitize_results( null ) );
		$this->assertSame( [], CaseStudies::sanitize_results( [ [ 'label' => '', 'before' => '', 'after' => '' ] ] ) );

		$clean = CaseStudies::sanitize_results(
			[
				[
					'label'  => ' <b>Load time</b> ',
					'before' => '4.1s',
					'after'  => '1.2s',
					'evil'   => 'x',
				],
				'not a row',
				[
					'label' => 'No after',
					'before' => '1',
				],
				[
					'label' => '',
					'after' => 'no label',
				],
				[
					'label' => 'Enquiries',
					'after' => 40,
				],
				[
					'label'  => 'Nested',
					'before' => [ 'x' ],
					'after'  => 'ok',
				],
			]
		);

		$this->assertSame(
			[
				[
					'label'  => 'Load time',
					'before' => '4.1s',
					'after'  => '1.2s',
				],
				[
					'label'  => 'Enquiries',
					'before' => '',
					'after'  => '40',
				],
				[
					'label'  => 'Nested',
					'before' => '',
					'after'  => 'ok',
				],
			],
			$clean
		);

		$many = array_fill(
			0,
			9,
			[
				'label' => 'L',
				'after' => 'A',
			]
		);
		$this->assertCount( 6, CaseStudies::sanitize_results( $many ) );

		$long = CaseStudies::sanitize_results(
			[
				[
					'label' => str_repeat( 'x', 200 ),
					'after' => 'A',
				],
			]
		);
		$this->assertSame( CaseStudies::MAX_RESULT_LENGTH, mb_strlen( $long[0]['label'] ) );
	}

	/**
	 * The card: one link, the title as an h3 whose link is stretched; client, summary and a
	 * results list with a spoken "from … to …" and an aria-hidden arrow.
	 */
	public function test_card_render_markup(): void {
		$post = $this->post( 5 );
		$this->render_mocks(
			[
				5 => [
					'_thumbnail_id'        => 50,
					CaseStudies::CLIENT  => 'Dental practice, Leeds',
					CaseStudies::SUMMARY => 'Faster pages & more calls.',
					CaseStudies::RESULTS => [
						[
							'label'  => 'Load time',
							'before' => '4.1s',
							'after'  => '1.2s',
						],
						[
							'label'  => 'Enquiries',
							'before' => '',
							'after'  => '+40%',
						],
					],
				],
			],
			[ 5 => $post ]
		);
		WP_Mock::userFunction( 'wp_enqueue_style' )->once()->with( CaseStudies::STYLE );

		$html = ( new CaseStudies() )->render_card( [ 'caseStudyId' => 5 ] );

		$this->assertStringStartsWith( '<div class="wp-block-ajr ajr-cs-single"><article class="ajr-cs-card">', $html );
		$this->assertSame( 1, substr_count( $html, '<a ' ), 'Exactly one link in the card.' );
		$this->assertStringContainsString( '<h3 class="ajr-cs-card__title"><a class="ajr-cs-card__link" href="https://example.test/case-studies/cs-5/">Project 5</a></h3>', $html );
		$this->assertStringContainsString( 'class="ajr-cs-card__image" data-size="medium_large" loading="CORE"', $html );
		$this->assertStringContainsString( '<p class="ajr-cs-card__client">Dental practice, Leeds</p>', $html );
		$this->assertStringContainsString( '<p class="ajr-cs-card__summary">Faster pages &amp; more calls.</p>', $html );
		$this->assertStringContainsString( '<dl class="ajr-cs-card__results">', $html );
		$this->assertStringContainsString( '<dt class="ajr-cs-card__label">Load time</dt>', $html );
		$this->assertStringContainsString( '<span class="ajr-cs-card__arrow" aria-hidden="true"></span>', $html );
		$this->assertStringContainsString( '<span class="ajr-cs-card__before" aria-hidden="true">4.1s</span>', $html );
		$this->assertStringContainsString( '<span class="ajr-cs-card__sr">from 4.1s to 1.2s</span>', $html );
		// No "before": no arrow and no spoken from/to, just the result, readable.
		$this->assertStringContainsString( '<dd class="ajr-cs-card__change"><span class="ajr-cs-card__after">+40%</span></dd>', $html );
		$this->assertSame( 1, substr_count( $html, 'ajr-cs-card__arrow' ) );
		// ⛔ No structured data, ever.
		$this->assertStringNotContainsString( 'application/ld+json', $html );
		$this->assertStringNotContainsString( 'itemprop', $html );
		$this->assertConditionsMet();
	}

	/**
	 * Image and results can be switched off; the summary falls back to a WRITTEN excerpt only.
	 */
	public function test_card_toggles_and_summary_fallback(): void {
		$this->render_mocks(
			[
				6 => [
					'_thumbnail_id'        => 60,
					CaseStudies::RESULTS => [
						[
							'label' => 'L',
							'after' => 'A',
						],
					],
				],
				7 => [],
			],
			[
				6 => $this->post( 6 ),
				7 => $this->post( 7 ),
			],
			[ 6 => 'Written excerpt.' ]
		);
		WP_Mock::userFunction( 'wp_enqueue_style' )->twice();

		$module = new CaseStudies();
		$html   = $module->render_card(
			[
				'caseStudyId' => 6,
				'showImage'   => false,
				'showResults' => false,
			]
		);
		$this->assertStringNotContainsString( '<img', $html );
		$this->assertStringNotContainsString( 'ajr-cs-card__results', $html );
		$this->assertStringContainsString( '<p class="ajr-cs-card__summary">Written excerpt.</p>', $html );

		// No summary, no written excerpt: no summary line (never core's auto-excerpt).
		$html = $module->render_card( [ 'caseStudyId' => 7 ] );
		$this->assertStringNotContainsString( 'ajr-cs-card__summary', $html );
		$this->assertStringNotContainsString( 'AUTO EXCERPT', $html );
		$this->assertStringNotContainsString( 'ajr-cs-card__client', $html );
		$this->assertConditionsMet();
	}

	/**
	 * Missing, unpublished, protected, wrong-type: '' and no stylesheet.
	 */
	public function test_card_renders_nothing_when_not_showable(): void {
		$protected                = $this->post( 4 );
		$protected->post_password = 'secret';
		$this->render_mocks(
			[],
			[
				2 => $this->post( 2, 'draft' ),
				3 => $this->post( 3, 'publish', 'page' ),
				4 => $protected,
			]
		);
		WP_Mock::userFunction( 'wp_enqueue_style' )->never();

		$module = new CaseStudies();
		$this->assertSame( '', $module->render_card( [ 'caseStudyId' => 1 ] ), 'missing' );
		$this->assertSame( '', $module->render_card( [ 'caseStudyId' => 2 ] ), 'draft' );
		$this->assertSame( '', $module->render_card( [ 'caseStudyId' => 3 ] ), 'not a case study' );
		$this->assertSame( '', $module->render_card( [ 'caseStudyId' => 4 ] ), 'password-protected' );
		$this->assertSame( '', $module->render_card( [] ), 'no ID and no context' );
		$this->assertConditionsMet();
	}

	/**
	 * caseStudyId 0 inside a Query Loop uses the loop's post — but only a case study.
	 */
	public function test_card_uses_block_context(): void {
		$this->render_mocks( [], [ 9 => $this->post( 9 ) ] );
		WP_Mock::userFunction( 'wp_enqueue_style' )->once();

		$module = new CaseStudies();
		$loop   = (object) [
			'context' => [
				'postId'   => 9,
				'postType' => 'ajr_case_study',
			],
		];
		$page   = (object) [
			'context' => [
				'postId'   => 9,
				'postType' => 'page',
			],
		];

		$this->assertStringContainsString( 'Project 9', $module->render_card( [ 'caseStudyId' => 0 ], '', $loop ) );
		$this->assertSame( '', $module->render_card( [ 'caseStudyId' => 0 ], '', $page ) );
		$this->assertConditionsMet();
	}

	/**
	 * The grid query: one bounded query, ordered, no pagination count, no term cache, no
	 * password-protected posts; count clamped 1–12 with a default of 3.
	 */
	public function test_grid_query_arguments_and_count_clamp(): void {
		$module = new CaseStudies();

		$args = $module->query_args( [] );
		$this->assertSame( 'ajr_case_study', $args['post_type'] );
		$this->assertSame( 'publish', $args['post_status'] );
		$this->assertFalse( $args['has_password'] );
		$this->assertSame( 3, $args['posts_per_page'] );
		$this->assertSame( [ 'menu_order' => 'ASC', 'date' => 'DESC', 'ID' => 'DESC' ], $args['orderby'] );
		$this->assertTrue( $args['no_found_rows'] );
		$this->assertFalse( $args['update_post_term_cache'] );
		$this->assertArrayNotHasKey( 'tax_query', $args );

		$this->assertSame( 1, $module->query_args( [ 'count' => 0 ] )['posts_per_page'] );
		$this->assertSame( 1, $module->query_args( [ 'count' => -5 ] )['posts_per_page'] );
		$this->assertSame( 7, $module->query_args( [ 'count' => 7 ] )['posts_per_page'] );
		$this->assertSame( 12, $module->query_args( [ 'count' => 99 ] )['posts_per_page'] );
	}

	/**
	 * Columns clamp 1–4 with a default of 3.
	 */
	public function test_columns_clamp(): void {
		$this->assertSame( 3, CaseStudies::columns( [] ) );
		$this->assertSame( 1, CaseStudies::columns( [ 'columns' => 0 ] ) );
		$this->assertSame( 2, CaseStudies::columns( [ 'columns' => '2' ] ) );
		$this->assertSame( 4, CaseStudies::columns( [ 'columns' => 9 ] ) );
	}

	/**
	 * The title's heading level clamps 2–4 with a default of 3, and the card prints it.
	 */
	public function test_heading_level(): void {
		$this->assertSame( 3, CaseStudies::heading_level( [] ) );
		$this->assertSame( 2, CaseStudies::heading_level( [ 'headingLevel' => 1 ] ) );
		$this->assertSame( 4, CaseStudies::heading_level( [ 'headingLevel' => '4' ] ) );
		$this->assertSame( 4, CaseStudies::heading_level( [ 'headingLevel' => 6 ] ) );

		$this->render_mocks( [], [ 8 => $this->post( 8 ) ] );
		WP_Mock::userFunction( 'wp_enqueue_style' )->once();
		$html = ( new CaseStudies() )->render_card(
			[
				'caseStudyId'  => 8,
				'headingLevel' => 2,
			]
		);
		$this->assertStringContainsString( '<h2 class="ajr-cs-card__title">', $html );
		$this->assertStringContainsString( '</a></h2>', $html );
		$this->assertStringNotContainsString( '<h3', $html );
		$this->assertConditionsMet();
	}

	/**
	 * A tag becomes a sanitised slug tax_query; an empty one adds none.
	 */
	public function test_tag_produces_a_tax_query(): void {
		$this->sanitize_title_mock();
		$module = new CaseStudies();

		$this->assertSame(
			[
				[
					'taxonomy' => 'case_study_tag',
					'field'    => 'slug',
					'terms'    => [ 'performance' ],
				],
			],
			$module->query_args( [ 'tag' => ' Performance ' ] )['tax_query']
		);
		$this->assertArrayNotHasKey( 'tax_query', $module->query_args( [ 'tag' => '' ] ) );
		$this->assertArrayNotHasKey( 'tax_query', $module->query_args( [ 'tag' => [ 'x' ] ] ) );
	}

	/**
	 * No case studies: empty output, and no stylesheet queued for it.
	 */
	public function test_empty_grid_renders_nothing_and_loads_nothing(): void {
		WP_Mock::userFunction( 'wp_enqueue_style' )->never();

		$this->assertSame( '', $this->module( [] )->render_grid( [] ) );
		$this->assertConditionsMet();
	}

	/**
	 * The grid: a list of the same cards, the columns as a custom property, the attributes in
	 * the query, the stylesheet queued once rendering is certain, images primed in one batch.
	 */
	public function test_grid_render(): void {
		$this->render_mocks(
			[
				1 => [ '_thumbnail_id' => 11 ],
				2 => [ '_thumbnail_id' => 12 ],
				3 => [],
			]
		);
		$this->sanitize_title_mock();
		WP_Mock::userFunction( 'wp_enqueue_style' )->once()->with( CaseStudies::STYLE );

		$module = $this->module( [ $this->post( 1 ), $this->post( 2 ), $this->post( 3 ) ] );
		$html   = $module->render_grid(
			[
				'count'   => 40,
				'columns' => 2,
				'tag'     => 'seo',
			]
		);

		$this->assertStringStartsWith( '<div class="wp-block-ajr ajr-cs-grid" style="--ajr-cs-columns:2"><ul class="ajr-cs-grid__list" role="list">', $html );
		$this->assertSame( 3, substr_count( $html, '<li class="ajr-cs-grid__item"><article class="ajr-cs-card">' ) );
		$this->assertSame( 3, substr_count( $html, '<h3 class="ajr-cs-card__title">' ) );
		$this->assertSame( 3, substr_count( $html, '<a ' ) );
		$this->assertStringContainsString( 'sizes="(min-width: 1280px) 600px, (min-width: 782px) 50vw, 100vw"', $html );
		$this->assertSame( 12, $module->args['posts_per_page'] );
		$this->assertSame( [ 'seo' ], $module->args['tax_query'][0]['terms'] );
		$this->assertSame( [ [ 11, 12 ] ], $this->primed, 'One batch, only the images that exist.' );
		$this->assertConditionsMet();
	}

	/**
	 * The editor panel loads on the case study screen and nowhere else.
	 */
	public function test_panel_only_on_the_case_study_screen(): void {
		WP_Mock::userFunction( 'get_current_screen' )->andReturn( (object) [ 'post_type' => 'page' ] );
		WP_Mock::userFunction( 'wp_enqueue_script' )->never();

		( new CaseStudies() )->enqueue_panel();

		$this->assertConditionsMet();
	}

	/**
	 * Cache-busting comes from the asset files: both editor scripts carry this plugin's version.
	 */
	public function test_editor_assets_carry_the_plugin_version(): void {
		foreach ( [ 'case-studies', 'case-study-card' ] as $block ) {
			$asset = require AJRWD_CORE_PATH . "assets/blocks/$block/editor.asset.php";
			$this->assertSame( AJRWD_CORE_VERSION, $asset['version'], $block );
		}
		$this->assertFalse( method_exists( CaseStudies::class, 'version_block_assets' ), 'The filter could never take effect.' );
	}

	/**
	 * The rules are rebuilt once when the slug or archive switch they were built for changes —
	 * including the first time this plugin serves — and not again until it changes.
	 */
	public function test_rewrite_rules_heal_when_the_signature_changes(): void {
		$this->sanitize_title_mock();
		WP_Mock::userFunction( 'current_user_can' )->with( 'manage_options' )->andReturn( true );
		WP_Mock::userFunction( 'get_option' )->with( CaseStudies::REWRITE_OPTION )->andReturn( false, 'case-studies|no-archive' );
		WP_Mock::userFunction( 'update_option' )->once()->with( CaseStudies::REWRITE_OPTION, 'case-studies|no-archive', false );
		WP_Mock::userFunction( 'flush_rewrite_rules' )->once()->with( false );

		$module = new CaseStudies();
		$module->heal_rewrite_rules(); // First serve: nothing stored yet, so rebuild.
		$module->heal_rewrite_rules(); // Same signature: nothing.

		$this->assertConditionsMet();
	}

	/**
	 * A changed slug from the filter rebuilds the rules.
	 */
	public function test_a_new_slug_rebuilds_the_rules(): void {
		$this->sanitize_title_mock();
		WP_Mock::onFilter( 'ajrwd_case_studies_settings' )->with( CaseStudies::SETTINGS )->reply( [ 'slug' => 'work' ] );
		WP_Mock::userFunction( 'current_user_can' )->with( 'manage_options' )->andReturn( true );
		WP_Mock::userFunction( 'get_option' )->with( CaseStudies::REWRITE_OPTION )->andReturn( 'case-studies|no-archive' );
		WP_Mock::userFunction( 'update_option' )->once()->with( CaseStudies::REWRITE_OPTION, 'work|no-archive', false );
		WP_Mock::userFunction( 'flush_rewrite_rules' )->once();

		( new CaseStudies() )->heal_rewrite_rules();

		$this->assertConditionsMet();
	}

	/**
	 * Nobody but an administrator triggers the write.
	 */
	public function test_rewrite_heal_is_for_administrators_only(): void {
		WP_Mock::userFunction( 'current_user_can' )->with( 'manage_options' )->andReturn( false );
		WP_Mock::userFunction( 'update_option' )->never();
		WP_Mock::userFunction( 'flush_rewrite_rules' )->never();

		( new CaseStudies() )->heal_rewrite_rules();

		$this->assertConditionsMet();
	}

	/**
	 * The defaults are the folio's saved values from when the type left AJR Core.
	 */
	public function test_settings_are_the_folios(): void {
		$this->assertSame(
			[
				'singular'    => 'Case Study',
				'plural'      => 'Case Studies',
				'slug'        => 'case-studies',
				'has_archive' => false,
				'tag_label'   => 'Case study tags',
			],
			CaseStudies::SETTINGS
		);
	}

	/**
	 * ⛔ While an older AJR Core still has its Case studies module on, this plugin adds nothing.
	 */
	public function test_stands_down_while_core_serves_the_module(): void {
		Modules::$on = [ 'case_studies' ];
		$module      = new CaseStudies();

		WP_Mock::expectActionNotAdded( 'init', [ $module, 'register_post_type' ] );
		WP_Mock::expectActionNotAdded( 'init', [ $module, 'register_blocks' ] );
		WP_Mock::expectFilterNotAdded( 'rest_prepare_ajr_case_study', [ $module, 'hide_protected_meta' ], 10, 3 );
		WP_Mock::expectActionNotAdded( 'admin_init', [ $module, 'heal_rewrite_rules' ] );

		$module->register();

		$this->assertConditionsMet();
	}

	/**
	 * Only Core's case-studies switch stands this class down; the testimonials one does not.
	 */
	public function test_other_core_modules_do_not_stand_it_down(): void {
		Modules::$on = [ 'testimonials' ];
		$module      = new CaseStudies();

		WP_Mock::expectActionAdded( 'init', [ $module, 'register_post_type' ] );

		$module->register();

		$this->assertConditionsMet();
	}

	/**
	 * A password-protected case study's fields are removed from public REST reads, kept for the
	 * editor (context=edit) and for unprotected case studies.
	 */
	public function test_protected_case_study_meta_hidden_in_rest(): void {
		$meta = [
			CaseStudies::CLIENT  => 'Acme',
			CaseStudies::SUMMARY => 'Faster.',
			CaseStudies::RESULTS => [ [ 'label' => 'Load', 'before' => '4s', 'after' => '1s' ] ],
			'other_meta'          => 'kept',
		];
		$make = static fn() => new \WP_REST_Response( [ 'id' => 5, 'meta' => $meta ] );
		$view = new \WP_REST_Request( 'GET', '/wp/v2/ajr_case_study/5' );
		$edit = new \WP_REST_Request( 'GET', '/wp/v2/ajr_case_study/5' );
		$edit->set_param( 'context', 'edit' );
		$post = $this->post( 5 );

		WP_Mock::userFunction( 'post_password_required' )->andReturn( true );
		$hidden = ( new CaseStudies() )->hide_protected_meta( $make(), $post, $view )->get_data()['meta'];
		$this->assertSame( [ 'other_meta' => 'kept' ], $hidden );

		WP_Mock::userFunction( 'current_user_can' )->with( 'edit_post', 5 )->andReturn( true, false );
		$editor = ( new CaseStudies() )->hide_protected_meta( $make(), $post, $edit )->get_data()['meta'];
		$this->assertSame( $meta, $editor, 'The editor still reads the fields.' );

		// context=edit from someone who cannot edit THIS post (the collection route only checks
		// edit_posts): still hidden.
		$other = ( new CaseStudies() )->hide_protected_meta( $make(), $post, $edit )->get_data()['meta'];
		$this->assertSame( [ 'other_meta' => 'kept' ], $other );
	}

	/**
	 * An unprotected case study's fields stay in REST.
	 */
	public function test_unprotected_case_study_meta_kept_in_rest(): void {
		WP_Mock::userFunction( 'post_password_required' )->andReturn( false );
		$data = [ 'id' => 5, 'meta' => [ CaseStudies::CLIENT => 'Acme' ] ];

		$out = ( new CaseStudies() )->hide_protected_meta( new \WP_REST_Response( $data ), $this->post( 5 ), new \WP_REST_Request( 'GET', '/wp/v2/ajr_case_study/5' ) );
		$this->assertSame( $data, $out->get_data() );
	}
}

/**
 * strip_tags stand-in for the sanitize_text_field() test double.
 *
 * @param string $s Text.
 */
function wp_strip_all_tags_stub( string $s ): string {
	return strip_tags( $s ); // phpcs:ignore WordPress.WP.AlternativeFunctions.strip_tags_strip_tags -- test double.
}
