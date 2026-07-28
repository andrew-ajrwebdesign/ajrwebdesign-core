/**
 * Testimonials slider controls: arrows scroll the snap track, dots track
 * pages via scroll position. ~1KB, loads only where the block renders.
 */
( () => {
	const smooth = window.matchMedia( '(prefers-reduced-motion: reduce)' )
		.matches
		? 'auto'
		: 'smooth';

	document.querySelectorAll( '.ajr-tslider' ).forEach( ( root ) => {
		const track = root.querySelector( '.ajr-tslider__track' );
		const prev = root.querySelector( '.ajr-tslider__arrow--prev' );
		const next = root.querySelector( '.ajr-tslider__arrow--next' );
		const dotsWrap = root.querySelector( '.ajr-tslider__dots' );
		if ( ! track || ! prev ) {
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

		const pages = () =>
			Math.max(
				1,
				Math.ceil(
					( track.scrollWidth - track.clientWidth ) / slideWidth()
				) + 1
			);

		const currentPage = () =>
			Math.round( track.scrollLeft / slideWidth() );

		const dots = [];
		const buildDots = () => {
			if ( ! dotsWrap ) {
				return;
			}
			dotsWrap.textContent = '';
			dots.length = 0;
			for ( let i = 0; i < pages(); i++ ) {
				const dot = document.createElement( 'button' );
				dot.type = 'button';
				dot.className = 'ajr-tslider__dot';
				dot.setAttribute( 'aria-label', `${ i + 1 }` );
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
			const page = currentPage();
			dots.forEach( ( d, i ) =>
				d.classList.toggle( 'is-active', i === page )
			);
			prev.disabled = track.scrollLeft <= 4;
			next.disabled =
				track.scrollLeft >=
				track.scrollWidth - track.clientWidth - 4;
		};

		prev.addEventListener( 'click', () =>
			track.scrollBy( { left: -slideWidth(), behavior: smooth } )
		);
		next.addEventListener( 'click', () =>
			track.scrollBy( { left: slideWidth(), behavior: smooth } )
		);
		track.addEventListener( 'scroll', sync, { passive: true } );
		window.addEventListener( 'resize', () => {
			buildDots();
			sync();
		} );

		buildDots();
		sync();
	} );
} )();
