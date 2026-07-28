/**
 * Testimonials slider dots: one per scroll stop, click to jump, synced to
 * scroll position. Loads only where the block renders; reduced-motion aware.
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

		const pages = () =>
			Math.max(
				1,
				Math.round(
					( track.scrollWidth - track.clientWidth ) / slideWidth()
				) + 1
			);

		const dots = [];
		const buildDots = () => {
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
			const page = Math.round( track.scrollLeft / slideWidth() );
			dots.forEach( ( d, i ) =>
				d.classList.toggle( 'is-active', i === page )
			);
		};

		track.addEventListener( 'scroll', sync, { passive: true } );
		window.addEventListener( 'resize', () => {
			buildDots();
			sync();
		} );

		buildDots();
		sync();
	} );
} )();
