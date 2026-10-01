/**
 * Testimonials slider dots: one per scroll stop, click to jump, synced to
 * scroll position. Loads only where the block renders; reduced-motion aware.
 *
 * Scroll sync runs at most once a frame, and a resize rebuilds the dots only
 * after it settles and only when the number of stops changed, so a focused
 * dot keeps focus through a window resize.
 */
( () => {
	const smooth = window.matchMedia( '(prefers-reduced-motion: reduce)' )
		.matches
		? 'auto'
		: 'smooth';

	document.querySelectorAll( '.ajr-tslider' ).forEach( ( root ) => {
		const track = root.querySelector( '.ajr-tslider__track' );
		const dotsWrap = root.querySelector( '.ajr-tslider__dots' );
		if ( ! track || ! dotsWrap ) {
			return;
		}

		const slideWidth = () => {
			const slide = track.querySelector( '.ajr-tslider__slide' );
			if ( ! slide ) {
				return 0;
			}
			const gap = parseFloat( getComputedStyle( track ).columnGap ) || 0;
			return slide.getBoundingClientRect().width + gap;
		};

		const pages = () => {
			const width = slideWidth();
			if ( ! width ) {
				return 1;
			}
			return Math.max(
				1,
				Math.round( ( track.scrollWidth - track.clientWidth ) / width ) +
					1
			);
		};

		// Translated by PHP; %d is the page number. Falls back to English so a
		// cached render without the attribute still gets a real label.
		const labelTemplate =
			dotsWrap.dataset.dotLabel || 'Go to testimonial page %d';

		const dots = [];
		const buildDots = () => {
			const count = pages();
			if ( count === dots.length ) {
				return;
			}
			dotsWrap.textContent = '';
			dots.length = 0;
			for ( let i = 0; i < count; i++ ) {
				const dot = document.createElement( 'button' );
				dot.type = 'button';
				dot.className = 'ajr-tslider__dot';
				dot.setAttribute(
					'aria-label',
					labelTemplate.replace( '%d', `${ i + 1 }` )
				);
				dot.addEventListener( 'click', () =>
					track.scrollTo( {
						left: i * slideWidth(),
						behavior: smooth,
					} )
				);
				dotsWrap.appendChild( dot );
				dots.push( dot );
			}
		};

		const sync = () => {
			const width = slideWidth();
			const page = width ? Math.round( track.scrollLeft / width ) : 0;
			dots.forEach( ( d, i ) => {
				const active = i === page;
				d.classList.toggle( 'is-active', active );
				// The active dot was signalled by colour alone; aria-current
				// makes the same state available to assistive tech.
				if ( active ) {
					d.setAttribute( 'aria-current', 'true' );
				} else {
					d.removeAttribute( 'aria-current' );
				}
			} );
		};

		let frame = 0;
		track.addEventListener(
			'scroll',
			() => {
				if ( ! frame ) {
					frame = window.requestAnimationFrame( () => {
						frame = 0;
						sync();
					} );
				}
			},
			{ passive: true }
		);

		let settle = 0;
		window.addEventListener( 'resize', () => {
			window.clearTimeout( settle );
			settle = window.setTimeout( () => {
				buildDots();
				sync();
			}, 150 );
		} );

		buildDots();
		sync();
	} );
} )();
