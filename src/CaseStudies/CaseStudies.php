<?php
/**
 * Case studies — this site's finished work: the public content type, its tags and generic
 * fields, the editor panel, a card block for one case study and a grid block for several.
 *
 * WHERE THIS CAME FROM
 *
 * Built here first, moved into AJR Core in 1.9.0 (Core 0.12.0, 2026-09-28), and back here in
 * 1.15.0 (2026-10-01) when Andrew decided content types belong in each site's own plugin. The
 * code is AJR Core 0.15.2's, so the fixes made while it lived there come with it. Every key is
 * unchanged — post type, taxonomy, every meta key, both block names and their attributes, the
 * markup and classes, the /case-studies/ addresses — so no case study, page or link migrates.
 *
 * While an older AJR Core still has its Case studies module switched on, this class registers
 * nothing (see Core\CoreModules). This site's own layer — the Core Web Vitals fields and their
 * metabox, the score-circle cards, the story intro, the SEO tweaks — is in the sibling classes.
 *
 * WHAT A CASE STUDY IS
 *
 * The title is the project, the content is the full story (its own page), the excerpt is a
 * short fallback summary and the featured image is the card picture. The sidebar adds the
 * client line, a one-paragraph result summary and up to six "before → after" results.
 *
 * No structured data is printed here: the SEO plugin owns the page's WebPage node and AJR
 * Core's graph owns #business. ⛔ Never add Review or Rating markup to a case study.
 *
 * @package AJR\SiteCore
 */

namespace AJR\SiteCore\CaseStudies;

use AJR\SiteCore\Core\CoreModules;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the case study post type, its tags and fields, the editor panel and both blocks.
 */
class CaseStudies {

	/**
	 * Post type key. ⛔ Never change: it is the database value tying existing case studies to
	 * the type.
	 */
	public const POST_TYPE = PostType::POST_TYPE;

	/**
	 * Taxonomy key. ⛔ Never change, for the same reason.
	 */
	public const TAXONOMY = PostType::TAXONOMY;

	/**
	 * Client or industry line ("Dental practice, Leeds").
	 */
	public const CLIENT = 'ajr_case_study_client';

	/**
	 * One-paragraph result summary shown on the card.
	 */
	public const SUMMARY = 'ajr_case_study_summary';

	/**
	 * Up to six results, each {label, before, after}.
	 */
	public const RESULTS = 'ajr_case_study_results';

	/**
	 * The most results one case study keeps. More than this stops being a highlight.
	 */
	public const MAX_RESULTS = 6;

	/**
	 * The longest a result label or value may be. They are card text, not paragraphs.
	 */
	public const MAX_RESULT_LENGTH = 60;

	/**
	 * The card block. ⛔ Never change: pages store it.
	 */
	public const BLOCK_CARD = 'ajr/case-study-card';

	/**
	 * The grid block. ⛔ Never change: pages store it.
	 */
	public const BLOCK_GRID = 'ajr/case-studies';

	/**
	 * The most case studies one grid shows, whatever it is set to.
	 */
	public const MAX = 12;

	/**
	 * The most columns a grid lays out.
	 */
	public const MAX_COLUMNS = 4;

	/**
	 * Front-end stylesheet handle, shared by both blocks.
	 */
	public const STYLE = 'ajrwd-case-studies';

	/**
	 * Editor panel script handle (client, summary and results, on the case study edit screen).
	 */
	public const PANEL_SCRIPT = 'ajrwd-case-study-panel';

	/**
	 * AJR Core's module id for the same feature, while a Core release still has one.
	 */
	public const CORE_MODULE = 'case_studies';

	/**
	 * Option holding the slug and archive switch the stored rewrite rules were last built for.
	 */
	public const REWRITE_OPTION = 'ajrwd_case_studies_rewrite';

	/**
	 * This site's names and address. Values the folio had in AJR Core's settings when the type
	 * moved back (2026-10-01): no archive, because /case-studies/ is an ordinary Page listing
	 * them with the Case studies block. Change them with the `ajrwd_case_studies_settings`
	 * filter — and ⛔ a new slug moves every case study's address, so add redirects with it. The
	 * rewrite rules follow by themselves (heal_rewrite_rules()).
	 */
	public const SETTINGS = array(
		'singular'    => 'Case Study',
		'plural'      => 'Case Studies',
		'slug'        => 'case-studies',
		'has_archive' => false,
		'tag_label'   => 'Case study tags',
	);

	/**
	 * Register hooks, unless AJR Core is still registering case studies itself.
	 *
	 * Never in the constructor, so the class can be tested on its own.
	 */
	public function register(): void {
		if ( CoreModules::serves( self::CORE_MODULE ) ) {
			return;
		}

		add_action( 'init', array( $this, 'register_post_type' ) );
		add_action( 'init', array( $this, 'register_taxonomy' ) );
		add_action( 'init', array( $this, 'register_meta' ) );
		add_action( 'init', array( $this, 'register_blocks' ) );
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_panel' ) );
		add_action( 'admin_init', array( $this, 'heal_rewrite_rules' ) );
		add_filter( 'rest_prepare_' . self::POST_TYPE, array( $this, 'hide_protected_meta' ), 10, 3 );
	}

	/**
	 * The settings, after the `ajrwd_case_studies_settings` filter, each falling back to its
	 * default when the filter blanks it or returns the wrong type.
	 *
	 * @return array{singular:string,plural:string,slug:string,has_archive:bool,tag_label:string}
	 */
	public function settings(): array {
		/**
		 * Filters case studies' names, address and archive switch.
		 *
		 * @param array<string,mixed> $settings singular, plural, slug, has_archive and tag_label.
		 */
		$filtered = apply_filters( 'ajrwd_case_studies_settings', self::SETTINGS );
		$filtered = is_array( $filtered ) ? $filtered : array();
		$settings = self::SETTINGS;

		foreach ( array( 'singular', 'plural', 'slug', 'tag_label' ) as $key ) {
			$value = isset( $filtered[ $key ] ) && is_string( $filtered[ $key ] ) ? trim( $filtered[ $key ] ) : '';
			if ( '' !== $value ) {
				$settings[ $key ] = $value;
			}
		}

		if ( isset( $filtered['has_archive'] ) && is_bool( $filtered['has_archive'] ) ) {
			$settings['has_archive'] = $filtered['has_archive'];
		}

		return $settings;
	}

	/**
	 * Rebuild the rewrite rules when the slug or archive switch they were built for changed.
	 *
	 * The slug is a constant behind a filter, so nothing saves when it changes, and a deploy
	 * runs no activation hook: without this a new slug would 404 every case study until someone
	 * re-saved Settings → Permalinks (SEO review, 2026-10-01). It also covers the handover from
	 * AJR Core when Core is updated by file rather than by switching its module off: the first
	 * administrator page view after this plugin starts serving rebuilds the rules once.
	 *
	 * wp-admin only and administrators only, so the one write it can make never happens on a
	 * visitor's request or for a user who could not have saved the permalinks themselves.
	 */
	public function heal_rewrite_rules(): void {
		if ( ! current_user_can( 'manage_options' ) ) {
			return;
		}

		$signature = $this->slug() . '|' . ( $this->settings()['has_archive'] ? 'archive' : 'no-archive' );
		if ( get_option( self::REWRITE_OPTION ) === $signature ) {
			return;
		}

		// Not autoloaded: only this admin_init check reads it.
		update_option( self::REWRITE_OPTION, $signature, false );
		flush_rewrite_rules( false );
	}

	/**
	 * A password-protected case study's fields stay behind its password in the REST API too.
	 *
	 * Core blanks a protected post's content and excerpt in REST but adds registered meta
	 * without checking the password, so client, summary and results were readable by anyone at
	 * /wp/v2/ajr_case_study/<id> (security review, 2026-09-28). The blocks already refuse to show
	 * a protected case study; this makes the API agree. The editor's own reads (context=edit,
	 * which core only allows to users who can edit the post) are left alone.
	 *
	 * @param mixed $response The REST response.
	 * @param mixed $post     The case study.
	 * @param mixed $request  The REST request.
	 * @return mixed
	 */
	public function hide_protected_meta( $response, $post, $request ) {
		if ( ! $response instanceof \WP_REST_Response || ! $post instanceof \WP_Post ) {
			return $response;
		}
		$context = $request instanceof \WP_REST_Request ? (string) $request->get_param( 'context' ) : 'view';
		// Edit context alone is not enough: the collection route only checks edit_posts, so
		// also require the right to edit THIS post (as core's can_access_password_content()).
		if ( ! post_password_required( $post ) || ( 'edit' === $context && current_user_can( 'edit_post', $post->ID ) ) ) {
			return $response;
		}

		$data = $response->get_data();
		if ( is_array( $data ) && isset( $data['meta'] ) && is_array( $data['meta'] ) ) {
			unset( $data['meta'][ self::CLIENT ], $data['meta'][ self::SUMMARY ], $data['meta'][ self::RESULTS ] );
			$response->set_data( $data );
		}

		return $response;
	}

	/**
	 * The public slug, for each case study's address (and the archive, when it is on).
	 */
	public function slug(): string {
		$slug = sanitize_title( $this->settings()['slug'] );

		return '' !== $slug ? $slug : self::SETTINGS['slug'];
	}

	/**
	 * Register the post type.
	 *
	 * ⛔ Every argument is AJR Core 0.15.2's with the folio's saved values: public, in REST,
	 * singles at /case-studies/<name>/ and no archive. A test pins them.
	 *
	 * Public so the activation hook can call it before flushing rewrite rules.
	 */
	public function register_post_type(): void {
		$settings = $this->settings();
		$singular = $settings['singular'];
		$plural   = $settings['plural'];
		$slug     = $this->slug();

		register_post_type(
			self::POST_TYPE,
			array(
				'labels'          => array(
					'name'               => $plural,
					'singular_name'      => $singular,
					'menu_name'          => $plural,
					/* translators: %s: the plural name, e.g. "Case Studies". */
					'all_items'          => sprintf( __( 'All %s', 'ajrwebdesign-core' ), $plural ),
					/* translators: %s: the singular name, e.g. "Case Study". */
					'add_new_item'       => sprintf( __( 'Add New %s', 'ajrwebdesign-core' ), $singular ),
					/* translators: %s: the singular name, e.g. "Case Study". */
					'edit_item'          => sprintf( __( 'Edit %s', 'ajrwebdesign-core' ), $singular ),
					/* translators: %s: the singular name, e.g. "Case Study". */
					'new_item'           => sprintf( __( 'New %s', 'ajrwebdesign-core' ), $singular ),
					/* translators: %s: the singular name, e.g. "Case Study". */
					'view_item'          => sprintf( __( 'View %s', 'ajrwebdesign-core' ), $singular ),
					/* translators: %s: the plural name, e.g. "Case Studies". */
					'search_items'       => sprintf( __( 'Search %s', 'ajrwebdesign-core' ), $plural ),
					/* translators: %s: the plural name, e.g. "Case Studies". */
					'not_found'          => sprintf( __( 'No %s found.', 'ajrwebdesign-core' ), $plural ),
					/* translators: %s: the plural name, e.g. "Case Studies". */
					'not_found_in_trash' => sprintf( __( 'No %s found in Trash.', 'ajrwebdesign-core' ), $plural ),
				),
				'public'          => true,
				'show_in_rest'    => true,
				'has_archive'     => $settings['has_archive'] ? $slug : false,
				'rewrite'         => array(
					'slug'       => $slug,
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
	 * Register the tags used to choose which case studies a grid shows. Editor-facing only:
	 * no archive, no URL.
	 */
	public function register_taxonomy(): void {
		$label = $this->settings()['tag_label'];

		register_taxonomy(
			self::TAXONOMY,
			array( self::POST_TYPE ),
			array(
				'labels'            => array(
					'name'          => $label,
					'singular_name' => $label,
					'menu_name'     => $label,
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

	/**
	 * Register the client, summary and results fields, editable in the editor panel.
	 */
	public function register_meta(): void {
		$auth = static fn(): bool => current_user_can( 'edit_posts' );

		register_post_meta(
			self::POST_TYPE,
			self::CLIENT,
			array(
				'type'              => 'string',
				'single'            => true,
				'default'           => '',
				'sanitize_callback' => 'sanitize_text_field',
				'show_in_rest'      => true,
				'auth_callback'     => $auth,
			)
		);

		register_post_meta(
			self::POST_TYPE,
			self::SUMMARY,
			array(
				'type'              => 'string',
				'single'            => true,
				'default'           => '',
				'sanitize_callback' => 'sanitize_textarea_field',
				'show_in_rest'      => true,
				'auth_callback'     => $auth,
			)
		);

		$text = array( 'type' => 'string' );

		register_post_meta(
			self::POST_TYPE,
			self::RESULTS,
			array(
				'type'              => 'array',
				'single'            => true,
				'default'           => array(),
				'sanitize_callback' => array( self::class, 'sanitize_results' ),
				'show_in_rest'      => array(
					'schema' => array(
						'type'  => 'array',
						// No maxItems: an extra row is trimmed by sanitize_results(), not refused.
						'items' => array(
							'type'                 => 'object',
							'properties'           => array(
								'label'  => $text,
								'before' => $text,
								'after'  => $text,
							),
							'additionalProperties' => false,
						),
					),
				),
				'auth_callback'     => $auth,
			)
		);
	}

	/**
	 * Results: a list of up to six {label, before, after} rows of short plain text.
	 *
	 * A row is kept only when it has a label AND an "after" value — the result itself. "Before"
	 * is optional ("Enquiries: +40%" has no before). Rows missing either are dropped rather than
	 * printed as a half-filled line, and anything past the sixth is trimmed.
	 *
	 * @param mixed $value Submitted value.
	 * @return array<int,array{label:string,before:string,after:string}>
	 */
	public static function sanitize_results( $value ): array {
		if ( ! is_array( $value ) ) {
			return array();
		}

		$rows = array();

		foreach ( $value as $row ) {
			if ( ! is_array( $row ) ) {
				continue;
			}

			$clean = array();
			foreach ( array( 'label', 'before', 'after' ) as $field ) {
				$raw             = isset( $row[ $field ] ) && is_scalar( $row[ $field ] ) ? (string) $row[ $field ] : '';
				$clean[ $field ] = mb_substr( sanitize_text_field( $raw ), 0, self::MAX_RESULT_LENGTH );
			}

			if ( '' === $clean['label'] || '' === $clean['after'] ) {
				continue;
			}

			$rows[] = $clean;

			if ( count( $rows ) >= self::MAX_RESULTS ) {
				break;
			}
		}

		return $rows;
	}

	/**
	 * The editor panel (client, summary, results), on the case study edit screen only.
	 *
	 * Plain JavaScript with a hand-written dependency list, like the blocks: no build step.
	 */
	public function enqueue_panel(): void {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		if ( ! $screen || self::POST_TYPE !== ( $screen->post_type ?? '' ) ) {
			return;
		}

		$asset = require AJRWD_CORE_PATH . 'assets/editor/case-study.asset.php';

		wp_enqueue_script(
			self::PANEL_SCRIPT,
			AJRWD_CORE_URL . 'assets/editor/case-study.js',
			$asset['dependencies'],
			$asset['version'],
			array( 'in_footer' => true )
		);
		wp_set_script_translations( self::PANEL_SCRIPT, 'ajrwebdesign-core', AJRWD_CORE_PATH . 'languages' );
	}

	/**
	 * Register both blocks, and their one shared stylesheet separately.
	 *
	 * ⛔ The style is not declared in block.json, for the reason given on
	 * Testimonials::register_block(). Registered here and enqueued inside render(), after the
	 * early return, it loads only on a page actually showing a case study. The blocks live in
	 * assets/blocks/, and need no version filter, for the reasons given there too.
	 *
	 * The stylesheet is also both blocks' EDITOR style, so the server-rendered preview in the
	 * editor looks like the page.
	 */
	public function register_blocks(): void {
		$base = AJRWD_CORE_PATH . 'assets/blocks';

		if ( ! file_exists( $base . '/case-studies/block.json' ) || ! file_exists( $base . '/case-study-card/block.json' ) ) {
			return;
		}

		wp_register_style(
			self::STYLE,
			AJRWD_CORE_URL . 'assets/blocks/case-studies/style.css',
			array(),
			defined( 'AJRWD_CORE_VERSION' ) ? AJRWD_CORE_VERSION : false
		);
		// `path` lets core inline this small file instead of making a request for it.
		wp_style_add_data( self::STYLE, 'path', $base . '/case-studies/style.css' );

		register_block_type(
			$base . '/case-study-card',
			array(
				'render_callback'      => array( $this, 'render_card' ),
				'editor_style_handles' => array( self::STYLE ),
			)
		);

		register_block_type(
			$base . '/case-studies',
			array(
				'render_callback'      => array( $this, 'render_grid' ),
				'editor_style_handles' => array( self::STYLE ),
			)
		);
	}

	/**
	 * The case study a card block shows: its own setting, or the post in block context.
	 *
	 * With caseStudyId 0 the card uses the loop's post, so it works inside a Query Loop set to
	 * case studies. A context post of any other type shows nothing.
	 *
	 * @param array<string,mixed> $attributes Block attributes.
	 * @param mixed               $block      The WP_Block, when core supplies one.
	 */
	protected function card_post_id( array $attributes, $block ): int {
		$id = absint( $attributes['caseStudyId'] ?? 0 );

		if ( $id > 0 ) {
			return $id;
		}

		$context = is_object( $block ) && isset( $block->context ) && is_array( $block->context ) ? $block->context : array();

		if ( self::POST_TYPE === ( $context['postType'] ?? '' ) ) {
			return absint( $context['postId'] ?? 0 );
		}

		return 0;
	}

	/**
	 * A case study that may be shown: exists, is this type, is published, has no password.
	 *
	 * A password-protected case study is published but private in intent; its summary and
	 * results are not printed on another page.
	 *
	 * @param int $id Post ID.
	 */
	protected function showable( int $id ): ?\WP_Post {
		if ( $id <= 0 ) {
			return null;
		}

		$post = get_post( $id );

		if ( ! $post instanceof \WP_Post
			|| self::POST_TYPE !== $post->post_type
			|| 'publish' !== $post->post_status
			|| '' !== (string) ( $post->post_password ?? '' ) ) {
			return null;
		}

		return $post;
	}

	/**
	 * Render the card block: one case study.
	 *
	 * @param array<string,mixed> $attributes Block attributes.
	 * @param string              $content    Inner content (none; dynamic block).
	 * @param mixed               $block      The WP_Block.
	 */
	public function render_card( array $attributes = array(), string $content = '', $block = null ): string {
		$post = $this->showable( $this->card_post_id( $attributes, $block ) );

		if ( null === $post ) {
			// Missing, unpublished or protected: nothing, and no stylesheet for it.
			return '';
		}

		wp_enqueue_style( self::STYLE );
		$this->prime_images( array( $post ) );

		$wrapper = get_block_wrapper_attributes( array( 'class' => 'ajr-cs-single' ) );

		return '<div ' . $wrapper . '>' // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes() escapes every value it returns.
			. $this->card(
				$post,
				! isset( $attributes['showImage'] ) || (bool) $attributes['showImage'],
				! isset( $attributes['showResults'] ) || (bool) $attributes['showResults'],
				'(min-width: 1280px) 640px, (min-width: 782px) 50vw, 100vw',
				self::heading_level( $attributes )
			)
			. '</div>';
	}

	/**
	 * The number of case studies a grid shows: 1–12, default 3.
	 *
	 * @param array<string,mixed> $attributes Block attributes.
	 */
	public static function count( array $attributes ): int {
		$count = isset( $attributes['count'] ) ? (int) $attributes['count'] : 3;

		return max( 1, min( self::MAX, $count ) );
	}

	/**
	 * The card title's heading level: 2–4, default 3.
	 *
	 * A setting, not a fixed h3, because where the block sits decides it: straight under the
	 * page's h1 (an archive, a Query Loop) the titles are h2, under an h2 section they are h3.
	 * A skipped level is an outline defect.
	 *
	 * @param array<string,mixed> $attributes Block attributes.
	 */
	public static function heading_level( array $attributes ): int {
		$level = isset( $attributes['headingLevel'] ) ? (int) $attributes['headingLevel'] : 3;

		return max( 2, min( 4, $level ) );
	}

	/**
	 * The number of columns a grid lays out from 782px: 1–4, default 3.
	 *
	 * @param array<string,mixed> $attributes Block attributes.
	 */
	public static function columns( array $attributes ): int {
		$columns = isset( $attributes['columns'] ) ? (int) $attributes['columns'] : 3;

		return max( 1, min( self::MAX_COLUMNS, $columns ) );
	}

	/**
	 * The WP_Query arguments for a grid's attributes.
	 *
	 * Ordered by the "Order" field first, then newest, so the client can pin the best work to
	 * the front. One query, no pagination count, no term cache (the card prints no terms), and
	 * password-protected case studies left out.
	 *
	 * @param array<string,mixed> $attributes Block attributes.
	 * @return array<string,mixed>
	 */
	public function query_args( array $attributes ): array {
		$tag = isset( $attributes['tag'] ) && is_scalar( $attributes['tag'] ) ? sanitize_title( (string) $attributes['tag'] ) : '';

		$args = array(
			'post_type'              => self::POST_TYPE,
			'post_status'            => 'publish',
			'has_password'           => false,
			'posts_per_page'         => self::count( $attributes ),
			// ID last: several case studies can share an Order and a publish time, and without a final
			// tiebreak MySQL may return them in a different order from one query plan to the next
			// (it did when has_password was added, 2026-10-01).
			'orderby'                => array(
				'menu_order' => 'ASC',
				'date'       => 'DESC',
				'ID'         => 'DESC',
			),
			'ignore_sticky_posts'    => true,
			'no_found_rows'          => true,
			'update_post_term_cache' => false,
		);

		if ( '' !== $tag ) {
			$args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- One indexed taxonomy, at most 12 rows, and only when the block is filtered.
				array(
					'taxonomy' => self::TAXONOMY,
					'field'    => 'slug',
					'terms'    => array( $tag ),
				),
			);
		}

		return $args;
	}

	/**
	 * Run the query. Separate so tests can supply posts without a database.
	 *
	 * @param array<string,mixed> $args WP_Query arguments.
	 * @return array<int,\WP_Post>
	 */
	protected function find( array $args ): array {
		$query = new \WP_Query( $args );

		return array_values( array_filter( $query->posts, static fn( $post ): bool => $post instanceof \WP_Post ) );
	}

	/**
	 * Render the grid block: several case studies, the same card for each.
	 *
	 * @param array<string,mixed> $attributes Block attributes.
	 */
	public function render_grid( array $attributes = array() ): string {
		$posts = $this->find( $this->query_args( $attributes ) );

		if ( array() === $posts ) {
			// Nothing to show is a section not filled in yet: no output, no stylesheet.
			return '';
		}

		wp_enqueue_style( self::STYLE );
		$this->prime_images( $posts );

		$columns = self::columns( $attributes );
		$wrapper = get_block_wrapper_attributes(
			array(
				'class' => 'ajr-cs-grid',
				'style' => '--ajr-cs-columns:' . $columns,
			)
		);
		// The card's share of the viewport once the columns apply, capped at its share of a
		// 1200px container on wide screens (else 34vw picks a far bigger file than the card
		// shows); one column below 782px.
		$sizes = 1 === $columns
			? '(min-width: 1280px) 1200px, 100vw'
			: '(min-width: 1280px) ' . (int) ceil( 1200 / $columns ) . 'px, (min-width: 782px) ' . (int) ceil( 100 / $columns ) . 'vw, 100vw';

		$items = '';
		foreach ( $posts as $post ) {
			$items .= '<li class="ajr-cs-grid__item">'
				. $this->card(
					$post,
					! isset( $attributes['showImage'] ) || (bool) $attributes['showImage'],
					! isset( $attributes['showResults'] ) || (bool) $attributes['showResults'],
					$sizes,
					self::heading_level( $attributes )
				)
				. '</li>';
		}

		// role="list": list-style:none drops the list semantics in Safari/VoiceOver otherwise.
		return '<div ' . $wrapper . '><ul class="ajr-cs-grid__list" role="list">' . $items . '</ul></div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- wrapper escaped by core; items built by card(), which escapes every value.
	}

	/**
	 * One card's markup, shared by both blocks.
	 *
	 * ⛔ WHOLE-BOX CLICKABLE (Andrew's hard rule): the title link is the card's ONLY link, and
	 * its ::after stretches over the whole card, so anywhere on the card opens the case study
	 * while a screen reader hears one link with the title as its name. The image is not
	 * linked for the same reason — a second link to the same place is noise in a link list.
	 *
	 * The results are a description list: the label is the term, the change is the
	 * description. The visible "before → after" is hidden from screen readers and replaced by
	 * a spoken "from … to …", because an arrow is not read aloud.
	 *
	 * @param \WP_Post $post         The case study.
	 * @param bool     $show_image   Print the featured image.
	 * @param bool     $show_results Print the results list.
	 * @param string   $sizes        The image's sizes attribute for this layout.
	 * @param int      $level        The title's heading level, 2–4.
	 */
	public function card( \WP_Post $post, bool $show_image, bool $show_results, string $sizes, int $level = 3 ): string {
		$tag     = 'h' . max( 2, min( 4, $level ) );
		$post_id = (int) $post->ID;
		$title   = get_the_title( $post );
		$client  = (string) get_post_meta( $post_id, self::CLIENT, true );
		$summary = (string) get_post_meta( $post_id, self::SUMMARY, true );
		// The excerpt only when one was WRITTEN: core fills an empty one from the content.
		if ( '' === $summary && has_excerpt( $post ) ) {
			$summary = (string) get_the_excerpt( $post );
		}
		$results = $show_results ? self::sanitize_results( get_post_meta( $post_id, self::RESULTS, true ) ) : array();

		$html = '<article class="ajr-cs-card">';

		if ( $show_image && has_post_thumbnail( $post ) ) {
			$html .= '<div class="ajr-cs-card__media">'
				. get_the_post_thumbnail(
					$post,
					'medium_large',
					array(
						// No 'loading': core lazy-loads a card below the fold and leaves one at
						// the top of the page eager, which a forced value would override.
						'class'    => 'ajr-cs-card__image',
						'decoding' => 'async',
						'sizes'    => $sizes,
					)
				)
				. '</div>';
		}

		$html .= '<div class="ajr-cs-card__body">';
		$html .= '<' . $tag . ' class="ajr-cs-card__title"><a class="ajr-cs-card__link" href="' . esc_url( (string) get_permalink( $post ) ) . '">' . esc_html( $title ) . '</a></' . $tag . '>';

		if ( '' !== $client ) {
			$html .= '<p class="ajr-cs-card__client">' . esc_html( $client ) . '</p>';
		}

		if ( '' !== $summary ) {
			$html .= '<p class="ajr-cs-card__summary">' . esc_html( $summary ) . '</p>';
		}

		if ( array() !== $results ) {
			$html .= '<dl class="ajr-cs-card__results">';
			foreach ( $results as $row ) {
				$html .= '<div class="ajr-cs-card__result"><dt class="ajr-cs-card__label">' . esc_html( $row['label'] ) . '</dt><dd class="ajr-cs-card__change">';

				if ( '' !== $row['before'] ) {
					$html .= '<span class="ajr-cs-card__before" aria-hidden="true">' . esc_html( $row['before'] ) . '</span>'
						. '<span class="ajr-cs-card__arrow" aria-hidden="true"></span>'
						. '<span class="ajr-cs-card__after" aria-hidden="true">' . esc_html( $row['after'] ) . '</span>'
						/* translators: 1: the value before the work, 2: the value after it. */
						. '<span class="ajr-cs-card__sr">' . esc_html( sprintf( __( 'from %1$s to %2$s', 'ajrwebdesign-core' ), $row['before'], $row['after'] ) ) . '</span>';
				} else {
					$html .= '<span class="ajr-cs-card__after">' . esc_html( $row['after'] ) . '</span>';
				}

				$html .= '</dd></div>';
			}
			$html .= '</dl>';
		}

		$html .= '</div></article>';

		return $html;
	}

	/**
	 * Load every card image's attachment in one query instead of one each.
	 *
	 * Rendering by post object skips the thumbnail priming core does inside The Loop, so
	 * without this a grid of twelve would cost twelve attachment lookups.
	 *
	 * @param array<int,\WP_Post> $posts Case studies being shown.
	 */
	protected function prime_images( array $posts ): void {
		$ids = array();

		foreach ( $posts as $post ) {
			$ids[] = (int) get_post_meta( (int) $post->ID, '_thumbnail_id', true );
		}

		$ids = array_values( array_unique( array_filter( $ids ) ) );

		if ( array() !== $ids ) {
			_prime_post_caches( $ids, false, true );
		}
	}
}
