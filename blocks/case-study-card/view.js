/**
 * Count-up animation for case-study card numbers.
 *
 * Elements carry data-count-to / data-count-decimals / data-count-prefix /
 * data-count-suffix. Animates once when scrolled into view; skipped for
 * prefers-reduced-motion and browsers without IntersectionObserver.
 */
( () => {
	const reduceMotion = window.matchMedia(
		'(prefers-reduced-motion: reduce)'
	).matches;

	const format = ( value, decimals, prefix, suffix ) =>
		`${ prefix || '' }${
			decimals > 0 ? value.toFixed( decimals ) : Math.round( value )
		}${ suffix || '' }`;

	const targets = document.querySelectorAll( '[data-count-to]' );
	if ( ! targets.length ) {
		return;
	}
	if ( reduceMotion || ! ( 'IntersectionObserver' in window ) ) {
		return; // Server-rendered text already shows the final value.
	}

	const DURATION = 900;

	const animate = ( el ) => {
		if ( el.dataset.ajrAnimated === 'true' ) {
			return;
		}
		el.dataset.ajrAnimated = 'true';

		const to = parseFloat( el.dataset.countTo );
		if ( Number.isNaN( to ) ) {
			return;
		}
		const decimals = parseInt( el.dataset.countDecimals || '0', 10 );
		const prefix = el.dataset.countPrefix || '';
		const suffix = el.dataset.countSuffix || '';
		const start = performance.now();

		const tick = ( now ) => {
			const progress = Math.min( ( now - start ) / DURATION, 1 );
			const eased = 1 - Math.pow( 1 - progress, 3 );
			el.textContent = format( to * eased, decimals, prefix, suffix );
			if ( progress < 1 ) {
				requestAnimationFrame( tick );
			}
		};
		requestAnimationFrame( tick );
	};

	const observer = new IntersectionObserver(
		( entries ) => {
			entries.forEach( ( entry ) => {
				if ( entry.isIntersecting ) {
					animate( entry.target );
					observer.unobserve( entry.target );
				}
			} );
		},
		{ threshold: 0.4 }
	);

	targets.forEach( ( el ) => observer.observe( el ) );
} )();
