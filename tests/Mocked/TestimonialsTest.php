<?php
/**
 * Unit tests for Testimonials\Testimonials (ported from AJR Core 0.15.2, 2026-10-01).
 *
 * WHY THIS FILE EXISTS
 *
 * The post type and taxonomy keys are the one thing here that can never change: the
 * testimonials already on ajrwebdesign.com carried into AJR Core and back with no migration
 * only because the keys match. A rename would not error — the quotes would simply vanish from the
 * admin and every block would render nothing. So the keys are pinned, along with the rest of
 * what fails quietly: the rating clamp, the query cap, the tag filter, the text filter's
 * fallback, the markup the site's look depends on, and "no testimonials = no output, no assets".
 *
 * @package AJR\SiteCore
 */

declare( strict_types=1 );

namespace AJR\SiteCore\Tests\Mocked;

use AJR\Core\Framework\Modules;
use AJR\SiteCore\Testimonials\Testimonials;
use WP_Mock;
use WP_Mock\Tools\TestCase;

/**
 * Tests for the testimonials module.
 */
class TestimonialsTest extends TestCase {

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
	 * A Testimonials whose query returns the given posts, recording the arguments it got.
	 *
	 * @param array<int,\WP_Post> $posts Posts to return.
	 */
	protected function module( array $posts ): Testimonials {
		return new class( $posts ) extends Testimonials {

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
	 * The functions render() calls, answered from a small in-memory fixture.
	 *
	 * @param array<int,array<string,mixed>> $meta Post ID => meta key => value.
	 */
	protected function render_mocks( array $meta = [] ): void {
		WP_Mock::userFunction( 'get_block_wrapper_attributes' )->andReturnUsing(
			static fn( array $extra ): string => 'class="wp-block-ajr-testimonials ' . $extra['class'] . '" style="' . $extra['style'] . '"'
		);
		WP_Mock::userFunction( 'esc_attr' )->andReturnUsing( static fn( $s ) => (string) $s );
		WP_Mock::userFunction( 'esc_html' )->andReturnUsing( static fn( $s ) => (string) $s );
		WP_Mock::userFunction( 'esc_attr_e' )->andReturnUsing(
			static function ( $s ): void {
				echo $s; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- test double.
			}
		);
		WP_Mock::userFunction( 'wp_kses_post' )->andReturnArg( 0 );
		WP_Mock::userFunction( 'wpautop' )->andReturnUsing( static fn( $s ) => '<p>' . $s . '</p>' );
		WP_Mock::userFunction( 'get_the_title' )->andReturnUsing( static fn( $p ) => 'Name ' . $p->ID );
		WP_Mock::userFunction( 'get_the_content' )->andReturnUsing( static fn( $more, $strip, $p ) => 'Quote ' . $p->ID );
		WP_Mock::userFunction( 'get_the_excerpt' )->andReturnUsing( static fn( $p ) => 'Role ' . $p->ID );
		// Raster logos in the fixture: core's own size attributes stand.
		WP_Mock::userFunction( 'get_post_mime_type' )->andReturn( 'image/png' );
		// Post 3 has no written excerpt (core would auto-fill one from the quote).
		WP_Mock::userFunction( 'has_excerpt' )->andReturnUsing( static fn( $p ) => 3 !== $p->ID );
		WP_Mock::userFunction( 'has_post_thumbnail' )->andReturnUsing( static fn( $p ) => ! empty( $meta[ $p->ID ]['_thumbnail_id'] ) );
		WP_Mock::userFunction( 'get_the_post_thumbnail' )->andReturnUsing(
			static fn( $p, $size, $attr ) => '<img class="' . $attr['class'] . '" alt="' . $attr['alt'] . '">'
		);
		WP_Mock::userFunction( 'wp_get_attachment_image' )->andReturnUsing(
			static fn( $id, $size, $icon, $attr ) => '<img class="' . $attr['class'] . '" data-id="' . $id . '">'
		);
		WP_Mock::userFunction( 'get_post_meta' )->andReturnUsing(
			static fn( $id, $key ) => $meta[ $id ][ $key ] ?? ''
		);
		WP_Mock::userFunction( '_prime_post_caches' )->zeroOrMoreTimes();
		WP_Mock::userFunction( 'wp_enqueue_script' )->zeroOrMoreTimes();
	}

	/**
	 * A post fixture.
	 *
	 * @param int $id Post ID.
	 */
	protected function post( int $id ): \WP_Post {
		return new \WP_Post(
			[
				'ID'        => $id,
				'post_type' => Testimonials::POST_TYPE,
			]
		);
	}

	/**
	 * ⛔ The keys existing content depends on.
	 */
	public function test_keys_match_the_content_already_on_the_site(): void {
		$this->assertSame( 'ajr_testimonial', Testimonials::POST_TYPE );
		$this->assertSame( 'testimonial_tag', Testimonials::TAXONOMY );
		$this->assertSame( 'ajr_testimonial_rating', Testimonials::RATING );
		$this->assertSame( 'ajr_testimonial_logo_id', Testimonials::LOGO_ID );
	}

	/**
	 * Hooks go on in register(), and constructing the module adds none.
	 */
	public function test_register_adds_the_hooks(): void {
		$module = new Testimonials();

		WP_Mock::expectActionAdded( 'init', [ $module, 'register_post_type' ] );
		WP_Mock::expectActionAdded( 'init', [ $module, 'register_taxonomy' ] );
		WP_Mock::expectActionAdded( 'init', [ $module, 'register_meta' ] );
		WP_Mock::expectActionAdded( 'init', [ $module, 'register_block' ] );
		WP_Mock::expectActionAdded( 'enqueue_block_editor_assets', [ $module, 'enqueue_panel' ] );
		WP_Mock::expectFilterAdded( 'wpmai_flush_copies_for_post', [ $module, 'flush_markdown_copies' ], 10, 2 );

		$module->register();

		$this->assertConditionsMet();
	}

	/**
	 * The post type: not public, in the admin and REST, same supports as the original.
	 */
	public function test_post_type_arguments(): void {
		$captured = [];
		WP_Mock::userFunction( 'register_post_type' )->once()->andReturnUsing(
			static function ( $key, $args ) use ( &$captured ) {
				$captured = [ $key, $args ];
			}
		);

		( new Testimonials() )->register_post_type();

		[ $key, $args ] = $captured;
		$this->assertSame( 'ajr_testimonial', $key );
		$this->assertFalse( $args['public'] );
		$this->assertTrue( $args['show_ui'] );
		$this->assertTrue( $args['show_in_rest'] );
		$this->assertSame( 'dashicons-format-quote', $args['menu_icon'] );
		$this->assertSame( [ 'title', 'editor', 'excerpt', 'thumbnail', 'page-attributes', 'custom-fields' ], $args['supports'] );
		$this->assertSame( 'Testimonials', $args['labels']['name'] );
		$this->assertSame( 'Testimonial', $args['labels']['singular_name'] );
	}

	/**
	 * The names come from the filter, and a blank one falls back rather than going nameless.
	 */
	public function test_labels_come_from_the_filter(): void {
		WP_Mock::onFilter( 'ajrwd_testimonials_settings' )
			->with( Testimonials::SETTINGS )
			->reply(
				[
					'plural'    => 'Client stories',
					'singular'  => '',
					'tag_label' => 'Products',
				]
			);
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

		$module = new Testimonials();
		$module->register_post_type();
		$module->register_taxonomy();

		$this->assertSame( 'Client stories', $post_type['labels']['name'] );
		$this->assertSame( 'Testimonial', $post_type['labels']['singular_name'] );
		$this->assertSame( 'Edit Testimonial', $post_type['labels']['edit_item'] );
		$this->assertSame( 'Products', $taxonomy['labels']['name'] );
	}

	/**
	 * The taxonomy: flat, private, in the admin column and REST, no URLs.
	 */
	public function test_taxonomy_arguments(): void {
		$captured = [];
		WP_Mock::userFunction( 'register_taxonomy' )->once()->andReturnUsing(
			static function ( $key, $types, $args ) use ( &$captured ) {
				$captured = [ $key, $types, $args ];
			}
		);

		( new Testimonials() )->register_taxonomy();

		[ $key, $types, $args ] = $captured;
		$this->assertSame( 'testimonial_tag', $key );
		$this->assertSame( [ 'ajr_testimonial' ], $types );
		$this->assertFalse( $args['hierarchical'] );
		$this->assertFalse( $args['public'] );
		$this->assertTrue( $args['show_admin_column'] );
		$this->assertTrue( $args['show_in_rest'] );
		$this->assertFalse( $args['rewrite'] );
		$this->assertSame( 'Service tags', $args['labels']['name'] );
	}

	/**
	 * Rating and logo meta: integers, in REST, sanitised, edit_posts only.
	 */
	public function test_meta_registration(): void {
		$captured = [];
		WP_Mock::userFunction( 'register_post_meta' )->twice()->andReturnUsing(
			static function ( $type, $key, $args ) use ( &$captured ) {
				$captured[ $key ] = [ $type, $args ];
			}
		);
		WP_Mock::userFunction( 'current_user_can' )->with( 'edit_posts' )->andReturn( false );

		( new Testimonials() )->register_meta();

		$this->assertSame( [ Testimonials::RATING, Testimonials::LOGO_ID ], array_keys( $captured ) );

		[ $type, $rating ] = $captured[ Testimonials::RATING ];
		$this->assertSame( 'ajr_testimonial', $type );
		$this->assertSame( 'integer', $rating['type'] );
		$this->assertSame( 5, $rating['default'] );
		$this->assertTrue( $rating['show_in_rest'] );
		$this->assertSame( 4, call_user_func( $rating['sanitize_callback'], '4' ) );
		$this->assertFalse( call_user_func( $rating['auth_callback'] ) );

		$logo = $captured[ Testimonials::LOGO_ID ][1];
		$this->assertSame( 'integer', $logo['type'] );
		$this->assertSame( 0, $logo['default'] );
		$this->assertSame( [ Testimonials::class, 'sanitize_logo' ], $logo['sanitize_callback'] );
	}

	/**
	 * A logo must be an image; a real user must be able to read it; a user-less write (WP-CLI,
	 * the migration) keeps an existing image.
	 */
	public function test_logo_must_be_a_readable_image(): void {
		WP_Mock::userFunction( 'absint' )->andReturnUsing( static fn( $v ) => abs( (int) $v ) );
		WP_Mock::userFunction( 'wp_attachment_is_image' )->andReturnUsing( static fn( $id ) => in_array( $id, [ 10, 11 ], true ) );
		WP_Mock::userFunction( 'current_user_can' )->andReturnUsing( static fn( $cap, $id = 0 ) => 'read_post' === $cap && 10 === $id );

		WP_Mock::userFunction( 'get_current_user_id' )->andReturn( 3 );
		$this->assertSame( 10, Testimonials::sanitize_logo( '10' ), 'readable image kept' );
		$this->assertSame( 0, Testimonials::sanitize_logo( 11 ), 'image the user cannot read refused' );
		$this->assertSame( 0, Testimonials::sanitize_logo( 12 ), 'not an image refused' );
		$this->assertSame( 0, Testimonials::sanitize_logo( 'x' ), 'garbage refused' );
	}

	/**
	 * An SVG logo with no stored size gets real proportions at the 26px display height, so the
	 * browser reserves the right box; a raster logo, or an SVG with a stored size, is left to core.
	 */
	public function test_svg_logo_gets_its_real_proportions(): void {
		$file = tempnam( sys_get_temp_dir(), 'svg' );
		file_put_contents( $file, '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 177 46"><rect/></svg>' ); // phpcs:ignore WordPress.WP.AlternativeFunctions -- test fixture.
		WP_Mock::userFunction( 'get_post_mime_type' )->andReturnUsing( static fn( $id ) => 7 === $id ? 'image/png' : 'image/svg+xml' );
		WP_Mock::userFunction( 'wp_get_attachment_metadata' )->andReturnUsing( static fn( $id ) => 9 === $id ? [ 'width' => 200, 'height' => 50 ] : [] );
		WP_Mock::userFunction( 'get_attached_file' )->andReturn( $file );

		$size = new \ReflectionMethod( Testimonials::class, 'svg_logo_size' );
		$size->setAccessible( true );

		$this->assertSame( [ 'width' => 100, 'height' => 26 ], $size->invoke( null, 8 ), '177×46 at 26px high' );
		$this->assertSame( [], $size->invoke( null, 7 ), 'raster: core decides' );
		$this->assertSame( [], $size->invoke( null, 9 ), 'SVG with a stored size: core decides' );
		unlink( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions -- test fixture.
	}

	/**
	 * No user (WP-CLI, cron, the one-off migration): an existing image is kept.
	 */
	public function test_logo_without_a_user_keeps_an_image(): void {
		WP_Mock::userFunction( 'absint' )->andReturnUsing( static fn( $v ) => abs( (int) $v ) );
		WP_Mock::userFunction( 'wp_attachment_is_image' )->andReturn( true );
		WP_Mock::userFunction( 'get_current_user_id' )->andReturn( 0 );

		$this->assertSame( 11, Testimonials::sanitize_logo( 11 ) );
	}

	/**
	 * A rating is clamped to 1–5 whatever arrives.
	 */
	public function test_rating_is_clamped(): void {
		$this->assertSame( 1, Testimonials::sanitize_rating( 0 ) );
		$this->assertSame( 1, Testimonials::sanitize_rating( -4 ) );
		$this->assertSame( 1, Testimonials::sanitize_rating( 'not a number' ) );
		$this->assertSame( 3, Testimonials::sanitize_rating( '3' ) );
		$this->assertSame( 5, Testimonials::sanitize_rating( 9 ) );
	}

	/**
	 * The query matches the original: ordered, no pagination count, language-neutral, capped.
	 */
	public function test_query_arguments_and_count_cap(): void {
		$module = new Testimonials();

		$args = $module->query_args( [] );
		$this->assertSame( 'ajr_testimonial', $args['post_type'] );
		$this->assertSame( 'publish', $args['post_status'] );
		$this->assertFalse( $args['has_password'], 'A testimonial with a password is not shown.' );
		$this->assertSame( 12, $args['posts_per_page'] );
		$this->assertSame( [ 'menu_order' => 'ASC', 'date' => 'DESC' ], $args['orderby'] );
		$this->assertTrue( $args['no_found_rows'] );
		$this->assertFalse( $args['update_post_term_cache'] );
		$this->assertSame( '', $args['lang'] );
		$this->assertArrayNotHasKey( 'tax_query', $args );

		$this->assertSame( 5, $module->query_args( [ 'count' => 5 ] )['posts_per_page'] );
		$this->assertSame( 12, $module->query_args( [ 'count' => 40 ] )['posts_per_page'] );
		$this->assertSame( 12, $module->query_args( [ 'count' => 0 ] )['posts_per_page'] );
		$this->assertSame( 12, $module->query_args( [ 'count' => -3 ] )['posts_per_page'] );
	}

	/**
	 * Tags become a slug tax_query, sanitised, with empties dropped.
	 */
	public function test_tags_produce_a_tax_query(): void {
		WP_Mock::userFunction( 'sanitize_title' )->andReturnUsing( static fn( $s ) => strtolower( trim( (string) $s ) ) );

		$args = ( new Testimonials() )->query_args( [ 'tags' => [ 'Performance', '', 'seo' ] ] );

		$this->assertSame(
			[
				[
					'taxonomy' => 'testimonial_tag',
					'field'    => 'slug',
					'terms'    => [ 'performance', 'seo' ],
				],
			],
			$args['tax_query']
		);
	}

	/**
	 * No testimonials: empty output, and no stylesheet or script queued for it.
	 */
	public function test_nothing_to_show_renders_nothing_and_loads_nothing(): void {
		WP_Mock::userFunction( 'wp_enqueue_style' )->never();
		WP_Mock::userFunction( 'wp_enqueue_script' )->never();

		$this->assertSame( '', $this->module( [] )->render( [] ) );
		$this->assertConditionsMet();
	}

	/**
	 * The markup keeps every class the original slider's CSS and script depend on.
	 */
	public function test_render_markup_keeps_the_slider_classes(): void {
		$this->render_mocks(
			[
				1 => [
					Testimonials::RATING => '4',
					'_thumbnail_id'      => 50,
					Testimonials::LOGO_ID => 60,
				],
				2 => [ Testimonials::RATING => '' ],
				3 => [],
			]
		);
		WP_Mock::userFunction( 'wp_enqueue_style' )->once()->with( Testimonials::STYLE );

		$module = $this->module( [ $this->post( 1 ), $this->post( 2 ), $this->post( 3 ) ] );
		$html   = $module->render( [ 'perView' => 2 ] );

		foreach ( [
			'ajr-tslider"',
			'--ajr-tslider-per-view:2',
			'ajr-tslider__track',
			'ajr-tslider__slide',
			'ajr-tslider__card',
			'ajr-tslider__stars',
			'--ajr-tslider-stars:4',
			'4 out of 5 stars',
			'ajr-tslider__quote',
			'<p>Quote 1</p>',
			'ajr-tslider__byline',
			'class="ajr-tslider__avatar" alt="Name 1"',
			'ajr-tslider__avatar ajr-tslider__avatar--initial',
			'ajr-tslider__name',
			'ajr-tslider__role',
			'class="ajr-tslider__logo" data-id="60"',
			'ajr-tslider__nav',
			'ajr-tslider__dots',
			'data-dot-label="Go to testimonial page %d"',
		] as $needle ) {
			$this->assertStringContainsString( $needle, $html, "Missing: $needle" );
		}

		// A written role prints; an unwritten one prints no role line (never the auto-excerpt).
		$this->assertStringContainsString( 'Role 1', $html );
		$this->assertStringNotContainsString( 'Role 3', $html );
		$this->assertSame( 2, substr_count( $html, 'ajr-tslider__role' ) );

		$this->assertSame( 3, substr_count( $html, 'class="ajr-tslider__slide"' ) );
		// A blank stored value clamps to 1, as the original did. (A post with no rating row at
		// all reads the registered default, 5, from get_post_meta() before it gets here.)
		$this->assertStringContainsString( '--ajr-tslider-stars:1', $html );
		$this->assertConditionsMet();
	}

	/**
	 * Stars can be switched off, and with no more cards than fit there are no dots.
	 */
	public function test_render_without_stars_or_dots(): void {
		$this->render_mocks();
		WP_Mock::userFunction( 'wp_enqueue_style' )->once();
		WP_Mock::userFunction( 'wp_enqueue_script' )->never();

		$html = $this->module( [ $this->post( 1 ), $this->post( 2 ) ] )->render(
			[
				'perView'    => 2,
				'showRating' => false,
			]
		);

		$this->assertStringNotContainsString( 'ajr-tslider__stars', $html );
		$this->assertStringNotContainsString( 'ajr-tslider__dots', $html );
		$this->assertConditionsMet();
	}

	/**
	 * The block's attributes reach the query.
	 */
	public function test_render_passes_the_attributes_to_the_query(): void {
		$this->render_mocks();
		WP_Mock::userFunction( 'wp_enqueue_style' )->zeroOrMoreTimes();
		WP_Mock::userFunction( 'sanitize_title' )->andReturnArg( 0 );

		$module = $this->module( [ $this->post( 1 ) ] );
		$module->render(
			[
				'count' => 99,
				'tags'  => [ 'audit' ],
			]
		);

		$this->assertSame( 12, $module->args['posts_per_page'] );
		$this->assertSame( [ 'audit' ], $module->args['tax_query'][0]['terms'] );
	}

	/**
	 * The text filter is applied, and what it returns is printed.
	 */
	public function test_text_filter_is_applied(): void {
		WP_Mock::onFilter( 'ajr_core_testimonial_text' )
			->with(
				[
					'quote' => 'Hello',
					'role'  => 'Owner',
				],
				7
			)
			->reply(
				[
					'quote' => 'Hallo',
					'role'  => 'Inhaber',
				]
			);

		$this->assertSame(
			[
				'quote' => 'Hallo',
				'role'  => 'Inhaber',
			],
			( new Testimonials() )->text( 'Hello', 'Owner', 7 )
		);
	}

	/**
	 * A filter returning the wrong shape falls back to the original text, key by key.
	 */
	public function test_bad_filter_output_falls_back(): void {
		$original = [
			'quote' => 'Hello',
			'role'  => 'Owner',
		];

		WP_Mock::onFilter( 'ajr_core_testimonial_text' )->with( $original, 7 )->reply( 'not an array' );
		$this->assertSame( $original, ( new Testimonials() )->text( 'Hello', 'Owner', 7 ) );

		WP_Mock::onFilter( 'ajr_core_testimonial_text' )->with( $original, 8 )->reply(
			[
				'quote' => [ 'nested' ],
				'role'  => 'Inhaber',
			]
		);
		$this->assertSame(
			[
				'quote' => 'Hello',
				'role'  => 'Inhaber',
			],
			( new Testimonials() )->text( 'Hello', 'Owner', 8 )
		);

		WP_Mock::onFilter( 'ajr_core_testimonial_text' )->with( $original, 9 )->reply( [] );
		$this->assertSame( $original, ( new Testimonials() )->text( 'Hello', 'Owner', 9 ) );
	}

	/**
	 * The filtered text is what render() prints.
	 */
	public function test_render_prints_the_filtered_text(): void {
		$this->render_mocks();
		WP_Mock::userFunction( 'wp_enqueue_style' )->zeroOrMoreTimes();
		WP_Mock::onFilter( 'ajr_core_testimonial_text' )
			->with(
				[
					'quote' => 'Quote 1',
					'role'  => 'Role 1',
				],
				1
			)
			->reply(
				[
					'quote' => 'Zitat',
					'role'  => 'Rolle',
				]
			);

		$html = $this->module( [ $this->post( 1 ) ] )->render( [] );

		$this->assertStringContainsString( '<p>Zitat</p>', $html );
		$this->assertStringContainsString( '>Rolle<', $html );
		$this->assertStringNotContainsString( 'Quote 1', $html );
	}

	/**
	 * The defaults are the folio's saved values from when the type left AJR Core.
	 */
	public function test_settings_are_the_folios(): void {
		$this->assertSame(
			[
				'singular'  => 'Testimonial',
				'plural'    => 'Testimonials',
				'tag_label' => 'Service tags',
			],
			Testimonials::SETTINGS
		);
	}

	/**
	 * ⛔ While an older AJR Core still has its Testimonials module on, this plugin adds nothing:
	 * a second registration of the block name would lose, with a notice.
	 */
	public function test_stands_down_while_core_serves_the_module(): void {
		Modules::$on = [ 'testimonials' ];
		$module      = new Testimonials();

		WP_Mock::expectActionNotAdded( 'init', [ $module, 'register_post_type' ] );
		WP_Mock::expectActionNotAdded( 'init', [ $module, 'register_block' ] );
		WP_Mock::expectActionNotAdded( 'init', [ $module, 'register_meta' ] );

		$module->register();

		$this->assertConditionsMet();
	}

	/**
	 * A changed testimonial drops AJR Core's Markdown copies; anything else keeps Core's answer.
	 */
	public function test_testimonial_changes_flush_markdown_copies(): void {
		$module = new Testimonials();

		$this->assertTrue( $module->flush_markdown_copies( false, $this->post( 1 ) ) );
		$page = new \WP_Post(
			[
				'ID'        => 2,
				'post_type' => 'page',
			]
		);
		$this->assertFalse( $module->flush_markdown_copies( false, $page ) );
		$this->assertTrue( $module->flush_markdown_copies( true, $page ) );
		$this->assertFalse( $module->flush_markdown_copies( false, null ) );
	}

	/**
	 * Cache-busting comes from the asset files, not a metadata filter: the editor script's
	 * version is read from editor.asset.php, which must carry this plugin's.
	 */
	public function test_editor_asset_carries_the_plugin_version(): void {
		$asset = require AJRWD_CORE_PATH . 'assets/blocks/testimonials/editor.asset.php';

		$this->assertSame( AJRWD_CORE_VERSION, $asset['version'] );
		$this->assertFalse( method_exists( Testimonials::class, 'version_block_assets' ), 'The filter could never take effect.' );
	}
}
