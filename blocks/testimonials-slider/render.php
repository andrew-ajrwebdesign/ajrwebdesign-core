<?php
/**
 * Testimonials Slider render.
 *
 * One CPT entry per testimonial holds both languages: content/excerpt are
 * the English quote/role, German lives in meta and is used on German pages
 * (English fallback). Optional service-tag filtering scopes the block per
 * page. CSS scroll-snap slides; view.js only powers the dots.
 *
 * @package AJR\SiteCore
 *
 * @var array $attributes Block attributes.
 */

use AJR\SiteCore\Core\Utils;
use AJR\SiteCore\Testimonials\PostType;

defined( 'ABSPATH' ) || exit;

$ajrwd_per_view = max( 1, min( 3, (int) ( $attributes['perView'] ?? 2 ) ) );
$ajrwd_count    = (int) ( $attributes['count'] ?? 0 );
$ajrwd_stars_on = ! isset( $attributes['showRating'] ) || (bool) $attributes['showRating'];
$ajrwd_tags     = isset( $attributes['tags'] ) && is_array( $attributes['tags'] ) ? array_filter( array_map( 'sanitize_title', $attributes['tags'] ) ) : array();
$ajrwd_is_de    = 'de' === Utils::current_language();

$ajrwd_args = array(
	'post_type'              => PostType::POST_TYPE,
	'post_status'            => 'publish',
	'posts_per_page'         => $ajrwd_count > 0 ? min( $ajrwd_count, 12 ) : 12,
	'orderby'                => array(
		'menu_order' => 'ASC',
		'date'       => 'DESC',
	),
	'no_found_rows'          => true,
	'update_post_term_cache' => false,
	'lang'                   => '', // Language-agnostic: one entry serves both.
);

if ( ! empty( $ajrwd_tags ) ) {
	$ajrwd_args['tax_query'] = array( // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_tax_query
		array(
			'taxonomy' => PostType::TAXONOMY,
			'field'    => 'slug',
			'terms'    => $ajrwd_tags,
		),
	);
}

$ajrwd_query = new \WP_Query( $ajrwd_args );

if ( ! $ajrwd_query->have_posts() ) {
	return;
}

$ajrwd_total   = $ajrwd_query->post_count;
$ajrwd_wrapper = get_block_wrapper_attributes(
	array(
		'class' => 'ajr-tslider',
		'style' => '--ajr-tslider-per-view:' . $ajrwd_per_view,
	)
);
$ajrwd_has_nav = $ajrwd_total > $ajrwd_per_view;
?>
<div <?php echo $ajrwd_wrapper; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>>
	<ul class="ajr-tslider__track" aria-label="<?php esc_attr_e( 'Client testimonials', 'ajrwebdesign-core' ); ?>">
		<?php
		while ( $ajrwd_query->have_posts() ) {
			$ajrwd_query->the_post();
			$ajrwd_id     = get_the_ID();
			$ajrwd_rating = max( 1, min( 5, (int) get_post_meta( $ajrwd_id, PostType::RATING, true ) ) );
			$ajrwd_name   = get_the_title();

			$ajrwd_quote = get_the_content();
			$ajrwd_role  = get_the_excerpt();
			if ( $ajrwd_is_de ) {
				$ajrwd_quote_de = (string) get_post_meta( $ajrwd_id, PostType::QUOTE_DE, true );
				$ajrwd_role_de  = (string) get_post_meta( $ajrwd_id, PostType::ROLE_DE, true );
				if ( '' !== trim( $ajrwd_quote_de ) ) {
					$ajrwd_quote = $ajrwd_quote_de;
				}
				if ( '' !== trim( $ajrwd_role_de ) ) {
					$ajrwd_role = $ajrwd_role_de;
				}
			}
			?>
			<li class="ajr-tslider__slide">
				<article class="ajr-tslider__card">
					<?php if ( $ajrwd_stars_on ) : ?>
						<div class="ajr-tslider__stars" style="--ajr-tslider-stars:<?php echo esc_attr( (string) $ajrwd_rating ); ?>" role="img" aria-label="
						<?php
						/* translators: %d: star rating out of five. */
						echo esc_attr( sprintf( __( '%d out of 5 stars', 'ajrwebdesign-core' ), $ajrwd_rating ) );
						?>
						"></div>
					<?php endif; ?>

					<blockquote class="ajr-tslider__quote">
						<?php echo wp_kses_post( wpautop( $ajrwd_quote ) ); ?>
					</blockquote>

					<footer class="ajr-tslider__byline">
						<?php if ( has_post_thumbnail() ) : ?>
							<?php
							the_post_thumbnail(
								'thumbnail',
								array(
									'class'   => 'ajr-tslider__avatar',
									'alt'     => $ajrwd_name,
									'loading' => 'lazy',
								)
							);
							?>
						<?php else : ?>
							<span class="ajr-tslider__avatar ajr-tslider__avatar--initial" aria-hidden="true"><?php echo esc_html( mb_substr( $ajrwd_name, 0, 1 ) ); ?></span>
						<?php endif; ?>
						<div>
							<p class="ajr-tslider__name"><?php echo esc_html( $ajrwd_name ); ?></p>
							<?php if ( '' !== $ajrwd_role ) : ?>
								<p class="ajr-tslider__role"><?php echo esc_html( $ajrwd_role ); ?></p>
							<?php endif; ?>
						</div>
						<?php
						$ajrwd_logo_id = (int) get_post_meta( $ajrwd_id, PostType::LOGO_ID, true );
						if ( $ajrwd_logo_id > 0 ) {
							echo wp_get_attachment_image(
								$ajrwd_logo_id,
								'medium',
								false,
								array(
									'class'   => 'ajr-tslider__logo',
									'loading' => 'lazy',
								)
							);
						}
						?>
					</footer>
				</article>
			</li>
		<?php } ?>
	</ul>

	<?php if ( $ajrwd_has_nav ) : ?>
		<div class="ajr-tslider__nav">
			<div class="ajr-tslider__dots" role="tablist" aria-label="<?php esc_attr_e( 'Testimonial pages', 'ajrwebdesign-core' ); ?>"></div>
		</div>
	<?php endif; ?>
</div>
<?php
wp_reset_postdata();
