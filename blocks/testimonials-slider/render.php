<?php
/**
 * Testimonials Slider render.
 *
 * CSS scroll-snap does the sliding; view.js only adds arrows/dots. One
 * cached query, capped; Polylang filters it to the current language.
 *
 * @package AJR\SiteCore
 *
 * @var array $attributes Block attributes.
 */

use AJR\SiteCore\Testimonials\PostType;

defined( 'ABSPATH' ) || exit;

$ajrwd_per_view = max( 1, min( 3, (int) ( $attributes['perView'] ?? 2 ) ) );
$ajrwd_count    = (int) ( $attributes['count'] ?? 0 );
$ajrwd_stars_on = ! isset( $attributes['showRating'] ) || (bool) $attributes['showRating'];

$ajrwd_query = new \WP_Query(
	array(
		'post_type'              => PostType::POST_TYPE,
		'post_status'            => 'publish',
		'posts_per_page'         => $ajrwd_count > 0 ? min( $ajrwd_count, 12 ) : 12,
		'orderby'                => array(
			'menu_order' => 'ASC',
			'date'       => 'DESC',
		),
		'no_found_rows'          => true,
		'update_post_term_cache' => false,
	)
);

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
			$ajrwd_rating = max( 1, min( 5, (int) get_post_meta( get_the_ID(), PostType::RATING, true ) ) );
			$ajrwd_name   = get_the_title();
			$ajrwd_role   = get_the_excerpt();
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
						<?php the_content(); ?>
					</blockquote>

					<footer class="ajr-tslider__byline">
						<?php if ( has_post_thumbnail() ) : ?>
							<?php
							the_post_thumbnail(
								'thumbnail',
								array(
									'class'   => 'ajr-tslider__avatar',
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
					</footer>
				</article>
			</li>
		<?php } ?>
	</ul>

	<?php if ( $ajrwd_has_nav ) : ?>
		<div class="ajr-tslider__nav">
			<button type="button" class="ajr-tslider__arrow ajr-tslider__arrow--prev" aria-label="<?php esc_attr_e( 'Previous testimonials', 'ajrwebdesign-core' ); ?>">
				<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="M14.5 5.5 8 12l6.5 6.5"/></svg>
			</button>
			<div class="ajr-tslider__dots" role="tablist" aria-label="<?php esc_attr_e( 'Testimonial pages', 'ajrwebdesign-core' ); ?>"></div>
			<button type="button" class="ajr-tslider__arrow ajr-tslider__arrow--next" aria-label="<?php esc_attr_e( 'Next testimonials', 'ajrwebdesign-core' ); ?>">
				<svg viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><path fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" d="m9.5 5.5 6.5 6.5-6.5 6.5"/></svg>
			</button>
		</div>
	<?php endif; ?>
</div>
<?php
wp_reset_postdata();
