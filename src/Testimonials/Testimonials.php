<?php
/**
 * Testimonials — this site's client quotes: the content type, its tags and fields, the editor
 * panel and the slider block that shows them.
 *
 * WHERE THIS CAME FROM
 *
 * Built here first, moved into AJR Core in 1.8.0 (Core 0.11.0, 2026-09-28), and back here in
 * 1.15.0 (2026-10-01) when Andrew decided content types belong in each site's own plugin. The
 * code is AJR Core 0.15.2's, so the fixes made while it lived there come with it. Every key is
 * unchanged — post type, taxonomy, meta keys, block name, block attributes, markup and classes,
 * and the `ajr_core_testimonial_text` filter — so no testimonial, page or setting migrates.
 *
 * While an older AJR Core still has its Testimonials module switched on, this class registers
 * nothing (see Core\CoreModules).
 *
 * ONE ENTRY PER QUOTE
 *
 * The title is the person's name, the content is the quote, the excerpt is the role line
 * ("Owner, Example Ltd") and the featured image is their photo. The post type is not public:
 * a testimonial has no page of its own, it only ever appears inside a block, so there is no
 * thin URL for a search engine to find. German text comes from Testimonials\German.
 *
 * @package AJR\SiteCore
 */

namespace AJR\SiteCore\Testimonials;

use AJR\SiteCore\Core\CoreModules;

defined( 'ABSPATH' ) || exit;

/**
 * Registers the testimonial post type, its tags and fields, the editor panel and the block.
 */
class Testimonials {

	/**
	 * Post type key. ⛔ Never change: it is the database value tying existing quotes to the type.
	 */
	public const POST_TYPE = 'ajr_testimonial';

	/**
	 * Taxonomy key. ⛔ Never change, for the same reason.
	 */
	public const TAXONOMY = 'testimonial_tag';

	/**
	 * Star rating, 1–5.
	 */
	public const RATING = 'ajr_testimonial_rating';

	/**
	 * Attachment ID of the source logo (the company, or the platform the review came from).
	 */
	public const LOGO_ID = 'ajr_testimonial_logo_id';

	/**
	 * Block name. ⛔ Never change: it is stored in every page that shows the slider.
	 */
	public const BLOCK = 'ajr/testimonials';

	/**
	 * The most testimonials one block shows. A slider of more than this is a wall nobody reads,
	 * and the cap keeps the query bounded whatever the block is set to.
	 */
	public const MAX = 12;

	/**
	 * Front-end stylesheet handle.
	 */
	public const STYLE = 'ajrwd-testimonials';

	/**
	 * Front-end script handle (the slider's dots).
	 */
	public const VIEW_SCRIPT = 'ajrwd-testimonials-view';

	/**
	 * Editor panel script handle (rating and logo, on the testimonial edit screen).
	 */
	public const PANEL_SCRIPT = 'ajrwd-testimonial-panel';

	/**
	 * AJR Core's module id for the same feature, while a Core release still has one.
	 */
	public const CORE_MODULE = 'testimonials';

	/**
	 * This site's names for the admin. Values the folio had saved in AJR Core's settings when
	 * the type moved back (2026-10-01). Change them with the `ajrwd_testimonials_settings` filter.
	 */
	public const SETTINGS = array(
		'singular'  => 'Testimonial',
		'plural'    => 'Testimonials',
		'tag_label' => 'Service tags',
	);

	/**
	 * Register hooks, unless AJR Core is still registering testimonials itself.
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
		add_action( 'init', array( $this, 'register_block' ) );
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_panel' ) );
		add_filter( 'wpmai_flush_copies_for_post', array( $this, 'flush_markdown_copies' ), 10, 2 );
	}

	/**
	 * One of the names in SETTINGS, after the filter; the default when the filter blanks it.
	 *
	 * A blank label would give the admin menu an item with no name, which reads as broken.
	 *
	 * @param string $key Key in SETTINGS.
	 */
	protected function label( string $key ): string {
		/**
		 * Filters the names the admin uses for testimonials.
		 *
		 * @param array<string,string> $settings singular, plural and tag_label.
		 */
		$settings = apply_filters( 'ajrwd_testimonials_settings', self::SETTINGS );
		$value    = is_array( $settings ) && isset( $settings[ $key ] ) && is_string( $settings[ $key ] ) ? trim( $settings[ $key ] ) : '';

		return '' !== $value ? $value : self::SETTINGS[ $key ];
	}

	/**
	 * Register the post type.
	 *
	 * Not public and no archive: a testimonial is only ever shown inside a block, so it gets
	 * no URL of its own. show_in_rest is required for the block editor and for the panel.
	 */
	public function register_post_type(): void {
		$singular = $this->label( 'singular' );
		$plural   = $this->label( 'plural' );

		register_post_type(
			self::POST_TYPE,
			array(
				'labels'          => array(
					'name'          => $plural,
					'singular_name' => $singular,
					'menu_name'     => $plural,
					'all_items'     => $plural,
					/* translators: %s: the singular name, e.g. "Testimonial". */
					'add_new_item'  => sprintf( __( 'Add New %s', 'ajrwebdesign-core' ), $singular ),
					/* translators: %s: the singular name, e.g. "Testimonial". */
					'edit_item'     => sprintf( __( 'Edit %s', 'ajrwebdesign-core' ), $singular ),
					/* translators: %s: the singular name, e.g. "Testimonial". */
					'new_item'      => sprintf( __( 'New %s', 'ajrwebdesign-core' ), $singular ),
					/* translators: %s: the plural name, e.g. "Testimonials". */
					'search_items'  => sprintf( __( 'Search %s', 'ajrwebdesign-core' ), $plural ),
				),
				'public'          => false,
				// No permastruct or query var: a testimonial has no page of its own, and with
				// them registered the REST API advertised a `link` that only redirected home.
				'rewrite'         => false,
				'query_var'       => false,
				'show_ui'         => true,
				'show_in_rest'    => true,
				'menu_icon'       => 'dashicons-format-quote',
				'menu_position'   => 22,
				'supports'        => array( 'title', 'editor', 'excerpt', 'thumbnail', 'page-attributes', 'custom-fields' ),
				'capability_type' => 'post',
			)
		);
	}

	/**
	 * Register the tags used to choose which testimonials a block shows (e.g. only the
	 * performance-audit quotes on the audit page). Editor-facing only: no archive, no URL.
	 */
	public function register_taxonomy(): void {
		$label = $this->label( 'tag_label' );

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
	 * Register the rating and logo fields, editable in the editor panel.
	 */
	public function register_meta(): void {
		$auth = static fn(): bool => current_user_can( 'edit_posts' );

		register_post_meta(
			self::POST_TYPE,
			self::RATING,
			array(
				'type'              => 'integer',
				'single'            => true,
				'default'           => 5,
				'sanitize_callback' => array( self::class, 'sanitize_rating' ),
				'show_in_rest'      => true,
				'auth_callback'     => $auth,
			)
		);

		register_post_meta(
			self::POST_TYPE,
			self::LOGO_ID,
			array(
				'type'              => 'integer',
				'single'            => true,
				'default'           => 0,
				'sanitize_callback' => array( self::class, 'sanitize_logo' ),
				'show_in_rest'      => true,
				'auth_callback'     => $auth,
			)
		);
	}

	/**
	 * Keep AJR Core's Markdown copies honest about testimonials.
	 *
	 * A testimonial has no page of its own but is printed inside every page with the slider, so
	 * a quote a client asks to have taken down must leave those pages' Markdown copies with it.
	 * AJR Core 0.15.2 named this type itself; from 0.16.0 it no longer knows it, so this plugin
	 * says so through Core's filter (SEO review of Core's cache, 2026-09-30).
	 *
	 * @param mixed $flush Whether Core will drop every copy.
	 * @param mixed $post  The post that changed.
	 * @return mixed
	 */
	public function flush_markdown_copies( $flush, $post = null ) {
		if ( $post instanceof \WP_Post && self::POST_TYPE === $post->post_type ) {
			return true;
		}

		return $flush;
	}

	/**
	 * A logo is an image attachment the person saving it may see — otherwise 0.
	 *
	 * An author could otherwise point the public slider at an image attached to someone else's
	 * private post (security review, 2026-09-28). The read check applies whenever a real user is
	 * saving; a WP-CLI or cron write has no user, and a migration copying existing logos must not
	 * zero them all.
	 *
	 * @param mixed $value Submitted attachment ID.
	 */
	public static function sanitize_logo( $value ): int {
		$id = absint( $value );
		if ( 0 === $id || ! wp_attachment_is_image( $id ) ) {
			return 0;
		}
		if ( get_current_user_id() > 0 && ! current_user_can( 'read_post', $id ) ) {
			return 0;
		}

		return $id;
	}

	/**
	 * Width and height for an SVG logo, read from its viewBox at the 26px display height.
	 *
	 * Core stores no size for an SVG, so its <img> printed width="1" height="1": the browser
	 * reserved a 26×26 box that grew to the logo's real width once it loaded — a layout shift
	 * inside the byline, re-wrapping the name on narrow cards (performance review, 2026-09-28).
	 * Real proportions reserve the exact box. Raster logos keep core's own size attributes.
	 *
	 * @param int $id Attachment ID.
	 * @return array<string,int> Empty when there is nothing better than core's answer.
	 */
	protected static function svg_logo_size( int $id ): array {
		if ( 'image/svg+xml' !== get_post_mime_type( $id ) ) {
			return array();
		}
		$meta = wp_get_attachment_metadata( $id );
		if ( is_array( $meta ) && ! empty( $meta['width'] ) && ! empty( $meta['height'] ) ) {
			return array();
		}
		$file = get_attached_file( $id );
		if ( ! is_string( $file ) || ! is_readable( $file ) ) {
			return array();
		}
		$head = (string) file_get_contents( $file, false, null, 0, 2048 ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- a local attachment's opening bytes, not a remote request.
		if ( ! preg_match( '/viewBox\s*=\s*["\']\s*[-\d.]+[\s,]+[-\d.]+[\s,]+([\d.]+)[\s,]+([\d.]+)/i', $head, $m ) || (float) $m[2] <= 0 ) {
			return array();
		}
		$height = 26;

		return array(
			'width'  => (int) round( $height * (float) $m[1] / (float) $m[2] ),
			'height' => $height,
		);
	}

	/**
	 * A rating is a whole number of stars from 1 to 5, whatever was sent.
	 *
	 * @param mixed $value Submitted value.
	 */
	public static function sanitize_rating( $value ): int {
		return max( 1, min( 5, (int) $value ) );
	}

	/**
	 * The editor panel (rating and source logo), on the testimonial edit screen only.
	 *
	 * Plain JavaScript with a hand-written dependency list, like the block: no build step.
	 */
	public function enqueue_panel(): void {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;

		if ( ! $screen || self::POST_TYPE !== ( $screen->post_type ?? '' ) ) {
			return;
		}

		$asset = require AJRWD_CORE_PATH . 'assets/editor/testimonial.asset.php';

		wp_enqueue_script(
			self::PANEL_SCRIPT,
			AJRWD_CORE_URL . 'assets/editor/testimonial.js',
			$asset['dependencies'],
			$asset['version'],
			array( 'in_footer' => true )
		);
		wp_set_script_translations( self::PANEL_SCRIPT, 'ajrwebdesign-core', AJRWD_CORE_PATH . 'languages' );
	}

	/**
	 * Register the block, and its stylesheet and slider script separately.
	 *
	 * ⛔ Neither the style nor the view script is declared in block.json. Core enqueues a
	 * `viewScript` whenever the block is rendered — including when render() returned nothing
	 * because no testimonial matched. Registering both here and enqueuing them inside render(),
	 * after the early return, means they load only on a page actually showing testimonials.
	 *
	 * The block lives in assets/blocks/, not blocks/ or build/: Blocks\Registrar registers every
	 * build/ block without a render callback, and this one is build-free plain JavaScript.
	 *
	 * The stylesheet is also the block's EDITOR style, so the server-rendered preview in the
	 * editor looks like the page.
	 *
	 * Cache-busting needs no block_type_metadata filter: block.json declares only an
	 * editorScript, whose version WordPress takes from editor.asset.php (AJRWD_CORE_VERSION),
	 * and the stylesheet and view script are registered here with that version (performance
	 * review, 2026-10-01: the filter AJR Core carried could never take effect).
	 */
	public function register_block(): void {
		$dir = AJRWD_CORE_PATH . 'assets/blocks/testimonials';

		if ( ! file_exists( $dir . '/block.json' ) ) {
			return;
		}

		$version = defined( 'AJRWD_CORE_VERSION' ) ? AJRWD_CORE_VERSION : false;

		wp_register_style( self::STYLE, AJRWD_CORE_URL . 'assets/blocks/testimonials/style.css', array(), $version );
		// `path` lets core inline this small file instead of making a request for it.
		wp_style_add_data( self::STYLE, 'path', $dir . '/style.css' );

		wp_register_script(
			self::VIEW_SCRIPT,
			AJRWD_CORE_URL . 'assets/blocks/testimonials/view.js',
			array(),
			$version,
			array(
				'in_footer' => true,
				'strategy'  => 'defer',
			)
		);

		register_block_type(
			$dir,
			array(
				'render_callback'      => array( $this, 'render' ),
				'editor_style_handles' => array( self::STYLE ),
			)
		);
	}

	/**
	 * The WP_Query arguments for a block's attributes.
	 *
	 * Ordered by the "Order" field first, then newest, so the client can pin a quote to the
	 * front. `lang => ''` stops Polylang splitting testimonials by language: one entry serves
	 * every language, with translated text supplied through the text filter. A testimonial with
	 * a password is left out: an editor who sets one means to hide the quote, and this type has
	 * no page of its own for the password to guard (security review, 2026-10-01; AJR Core
	 * printed it).
	 *
	 * @param array<string,mixed> $attributes Block attributes.
	 * @return array<string,mixed>
	 */
	public function query_args( array $attributes ): array {
		$count = (int) ( $attributes['count'] ?? 0 );
		$tags  = isset( $attributes['tags'] ) && is_array( $attributes['tags'] )
			? array_values( array_filter( array_map( 'sanitize_title', array_map( 'strval', $attributes['tags'] ) ) ) )
			: array();

		$args = array(
			'post_type'              => self::POST_TYPE,
			'post_status'            => 'publish',
			'has_password'           => false,
			'posts_per_page'         => $count > 0 ? min( $count, self::MAX ) : self::MAX,
			'orderby'                => array(
				'menu_order' => 'ASC',
				'date'       => 'DESC',
			),
			'no_found_rows'          => true,
			'update_post_term_cache' => false,
			'lang'                   => '',
		);

		if ( array() !== $tags ) {
			$args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query -- One indexed taxonomy, at most 12 rows, and only when the block is filtered.
				array(
					'taxonomy' => self::TAXONOMY,
					'field'    => 'slug',
					'terms'    => $tags,
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
	 * The quote and role line to print, after the `ajr_core_testimonial_text` filter.
	 *
	 * The filter keeps AJR Core's name so Testimonials\German, and anything else already hooked
	 * to it, works unchanged. Whatever comes back is checked: a filter returning something other
	 * than an array, or a non-string for either key, falls back to the original for that key
	 * rather than printing nothing or fatalling.
	 *
	 * @param string $quote   The post content.
	 * @param string $role    The excerpt.
	 * @param int    $post_id Testimonial post ID.
	 * @return array{quote:string,role:string}
	 */
	public function text( string $quote, string $role, int $post_id ): array {
		$original = array(
			'quote' => $quote,
			'role'  => $role,
		);

		/**
		 * Filters a testimonial's quote and role line before they are printed.
		 *
		 * @param array{quote:string,role:string} $original Quote (post content) and role (excerpt).
		 * @param int                             $post_id  Testimonial post ID.
		 */
		$filtered = apply_filters( 'ajr_core_testimonial_text', $original, $post_id );

		if ( ! is_array( $filtered ) ) {
			return $original;
		}

		return array(
			'quote' => isset( $filtered['quote'] ) && is_string( $filtered['quote'] ) ? $filtered['quote'] : $quote,
			'role'  => isset( $filtered['role'] ) && is_string( $filtered['role'] ) ? $filtered['role'] : $role,
		);
	}

	/**
	 * Render the block.
	 *
	 * Markup and classes are unchanged from AJR Core 0.15.2 (and from this plugin's original
	 * slider before it), so the pages look exactly the same. CSS scroll-snap does the sliding;
	 * view.js only draws the dots.
	 *
	 * @param array<string,mixed> $attributes Block attributes.
	 */
	public function render( array $attributes = array() ): string {
		$posts = $this->find( $this->query_args( $attributes ) );

		if ( array() === $posts ) {
			// Nothing to show is a section not filled in yet, not an error: a visitor sees
			// nothing rather than an empty box, and no asset is loaded for it.
			return '';
		}

		wp_enqueue_style( self::STYLE );

		$per_view = max( 1, min( 3, (int) ( $attributes['perView'] ?? 2 ) ) );
		$stars_on = ! isset( $attributes['showRating'] ) || (bool) $attributes['showRating'];
		$has_nav  = count( $posts ) > $per_view;

		// The dots only exist when there is more than one page of cards.
		if ( $has_nav ) {
			wp_enqueue_script( self::VIEW_SCRIPT );
		}

		$this->prime_images( $posts );

		$wrapper = get_block_wrapper_attributes(
			array(
				'class' => 'ajr-tslider',
				'style' => '--ajr-tslider-per-view:' . $per_view,
			)
		);

		ob_start();
		?>
		<div <?php echo $wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- get_block_wrapper_attributes() escapes every value it returns. ?>>
			<ul class="ajr-tslider__track" aria-label="<?php esc_attr_e( 'Client testimonials', 'ajrwebdesign-core' ); ?>">
				<?php foreach ( $posts as $post ) : ?>
					<?php
					$post_id = (int) $post->ID;
					$rating  = self::sanitize_rating( get_post_meta( $post_id, self::RATING, true ) );
					$name    = get_the_title( $post );
					// The role is the excerpt only when one was WRITTEN: core fills an empty
					// excerpt from the content, which printed the quote a second time as the role.
					$role    = has_excerpt( $post ) ? (string) get_the_excerpt( $post ) : '';
					$text    = $this->text( (string) get_the_content( null, false, $post ), $role, $post_id );
					$logo_id = (int) get_post_meta( $post_id, self::LOGO_ID, true );
					?>
					<li class="ajr-tslider__slide">
						<article class="ajr-tslider__card">
							<?php if ( $stars_on ) : ?>
								<?php /* translators: %d: star rating out of five. */ ?>
								<div class="ajr-tslider__stars" style="--ajr-tslider-stars:<?php echo esc_attr( (string) $rating ); ?>" role="img" aria-label="<?php echo esc_attr( sprintf( __( '%d out of 5 stars', 'ajrwebdesign-core' ), $rating ) ); ?>"></div>
							<?php endif; ?>

							<blockquote class="ajr-tslider__quote">
								<?php echo wp_kses_post( wpautop( $text['quote'] ) ); ?>
							</blockquote>

							<footer class="ajr-tslider__byline">
								<?php if ( has_post_thumbnail( $post ) ) : ?>
									<?php
									echo get_the_post_thumbnail( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core builds and escapes the <img>.
										$post,
										'thumbnail',
										array(
											'class'   => 'ajr-tslider__avatar',
											'alt'     => $name,
											'loading' => 'lazy',
										)
									);
									?>
								<?php else : ?>
									<span class="ajr-tslider__avatar ajr-tslider__avatar--initial" aria-hidden="true"><?php echo esc_html( mb_substr( $name, 0, 1 ) ); ?></span>
								<?php endif; ?>
								<div>
									<p class="ajr-tslider__name"><?php echo esc_html( $name ); ?></p>
									<?php if ( '' !== $text['role'] ) : ?>
										<p class="ajr-tslider__role"><?php echo esc_html( $text['role'] ); ?></p>
									<?php endif; ?>
								</div>
								<?php
								if ( $logo_id > 0 ) {
									echo wp_get_attachment_image( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- core builds and escapes the <img>.
										$logo_id,
										'medium',
										false,
										array(
											'class'   => 'ajr-tslider__logo',
											'loading' => 'lazy',
										) + self::svg_logo_size( $logo_id )
									);
								}
								?>
							</footer>
						</article>
					</li>
				<?php endforeach; ?>
			</ul>

			<?php
			if ( $has_nav ) :
				/* translators: %d: page number within the testimonial slider. Substituted in view.js. */
				$dot_label = __( 'Go to testimonial page %d', 'ajrwebdesign-core' );
				?>
				<div class="ajr-tslider__nav">
					<div class="ajr-tslider__dots" role="group" aria-label="<?php esc_attr_e( 'Testimonial pages', 'ajrwebdesign-core' ); ?>" data-dot-label="<?php echo esc_attr( $dot_label ); ?>"></div>
				</div>
			<?php endif; ?>
		</div>
		<?php
		return (string) ob_get_clean();
	}

	/**
	 * Load every avatar and logo attachment in one query instead of one each.
	 *
	 * Rendering by post object skips the thumbnail priming core does inside The Loop, so
	 * without this each card would cost its own attachment lookup (up to 24 on a full slider).
	 *
	 * @param array<int,\WP_Post> $posts Testimonials being shown.
	 */
	protected function prime_images( array $posts ): void {
		$ids = array();

		foreach ( $posts as $post ) {
			$ids[] = (int) get_post_meta( (int) $post->ID, '_thumbnail_id', true );
			$ids[] = (int) get_post_meta( (int) $post->ID, self::LOGO_ID, true );
		}

		$ids = array_values( array_unique( array_filter( $ids ) ) );

		if ( array() !== $ids ) {
			_prime_post_caches( $ids, false, true );
		}
	}
}
