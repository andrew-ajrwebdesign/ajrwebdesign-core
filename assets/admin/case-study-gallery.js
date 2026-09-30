/**
 * Case study edit screen: the screenshot picker in the "Case Study Details" metabox.
 *
 * Opens WordPress's own media library, keeps the chosen attachment IDs (in the
 * order picked) in the hidden field the metabox saves, and redraws the row of
 * thumbnails. Plain JavaScript, no build step. Thumbnails are built with
 * createElement, never innerHTML: an attachment's URL and title are data.
 */
( () => {
	'use strict';

	const box = document.querySelector( '[data-ajrwd-gallery]' );

	if ( ! box || ! window.wp || ! window.wp.media ) {
		return;
	}

	const input = box.querySelector( 'input[type="hidden"]' );
	const list = box.querySelector( '[data-ajrwd-gallery-list]' );
	const choose = box.querySelector( '[data-ajrwd-gallery-choose]' );
	const clear = box.querySelector( '[data-ajrwd-gallery-clear]' );
	// The cap is the server's (Meta::MAX_GALLERY); it trims again on save.
	const max = parseInt( box.getAttribute( 'data-max' ), 10 ) || 8;
	let frame;

	/**
	 * The IDs currently stored.
	 *
	 * @return {number[]} Attachment IDs.
	 */
	const ids = () =>
		input.value
			.split( ',' )
			.map( ( id ) => parseInt( id, 10 ) )
			.filter( ( id ) => id > 0 );

	/**
	 * Store the chosen attachments and redraw the thumbnails.
	 *
	 * @param {Object[]} items Attachment objects from the media frame.
	 */
	const set = ( items ) => {
		const kept = items.slice( 0, max );

		input.value = kept.map( ( item ) => item.id ).join( ',' );

		while ( list.firstChild ) {
			list.removeChild( list.firstChild );
		}

		kept.forEach( ( item ) => {
			const size =
				( item.sizes &&
					( item.sizes.thumbnail ||
						item.sizes.medium ||
						item.sizes.full ) ) ||
				item;

			// An attachment whose details have not arrived yet has no address:
			// it is still saved by its ID, it just has no thumbnail to draw.
			if ( ! size.url ) {
				return;
			}

			const li = document.createElement( 'li' );
			const img = document.createElement( 'img' );

			img.src = size.url;
			img.alt = item.alt || item.title || '';
			img.width = 150;
			img.height = 150;
			li.appendChild( img );
			list.appendChild( li );
		} );
	};

	choose.addEventListener( 'click', ( event ) => {
		event.preventDefault();

		if ( ! frame ) {
			frame = window.wp.media( {
				title: box.getAttribute( 'data-title' ),
				button: { text: box.getAttribute( 'data-button' ) },
				library: { type: 'image' },
				multiple: 'add',
			} );

			// Open with exactly the saved screenshots selected, in their order.
			// Reset first: the frame is kept between openings, and without it
			// "Remove all" followed by "Choose" would bring the old ones back.
			frame.on( 'open', () => {
				const selection = frame.state().get( 'selection' );

				selection.reset();
				ids().forEach( ( id ) => {
					const attachment = window.wp.media.attachment( id );

					attachment.fetch();
					selection.add( attachment );
				} );
			} );

			frame.on( 'select', () => {
				set( frame.state().get( 'selection' ).toJSON() );
			} );
		}

		frame.open();
	} );

	clear.addEventListener( 'click', ( event ) => {
		event.preventDefault();
		set( [] );
	} );
} )();
