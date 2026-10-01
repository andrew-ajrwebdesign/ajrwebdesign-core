/**
 * Testimonial edit screen — the sidebar panel for the star rating and the source logo.
 *
 * ⛔ Plain JavaScript against wp.element, with NO JSX and NO build step, like the blocks.
 * Loaded only on the testimonial edit screen (Testimonials::enqueue_panel()).
 *
 * The rating had no control at all on the site this came from: every quote showed five stars
 * because that was the stored default, and changing one meant editing the database.
 */
( function ( plugins, editor, element, data, coreData, blockEditor, components, i18n ) {
	'use strict';

	var el = element.createElement;
	var __ = i18n.__;

	var POST_TYPE = 'ajr_testimonial';
	var RATING = 'ajr_testimonial_rating';
	var LOGO_ID = 'ajr_testimonial_logo_id';

	// wp.editor since WordPress 6.6; wp.editPost before that.
	var PluginDocumentSettingPanel =
		editor.PluginDocumentSettingPanel || ( window.wp.editPost && window.wp.editPost.PluginDocumentSettingPanel );

	var MediaUpload = blockEditor.MediaUpload;
	var MediaUploadCheck = blockEditor.MediaUploadCheck;
	var Button = components.Button;
	var RangeControl = components.RangeControl;

	/**
	 * The panel.
	 *
	 * @return {?Object} The element, or null on any other post type.
	 */
	function TestimonialPanel() {
		var postType = data.useSelect( function ( select ) {
			return select( 'core/editor' ).getCurrentPostType();
		}, [] );

		var entity = coreData.useEntityProp( 'postType', postType, 'meta' );
		var meta = entity[ 0 ] || {};
		var setMeta = entity[ 1 ];

		var logoId = meta[ LOGO_ID ] || 0;
		var media = data.useSelect(
			function ( select ) {
				return logoId ? select( coreData.store ).getMedia( logoId ) : null;
			},
			[ logoId ]
		);

		if ( POST_TYPE !== postType || ! PluginDocumentSettingPanel ) {
			return null;
		}

		/**
		 * Write one meta key, keeping the rest.
		 *
		 * @param {string} key   Meta key.
		 * @param {*}      value New value.
		 */
		function update( key, value ) {
			var next = Object.assign( {}, meta );
			next[ key ] = value;
			setMeta( next );
		}

		return el(
			element.Fragment,
			{},
			el(
				PluginDocumentSettingPanel,
				{ name: 'ajr-testimonial-rating', title: __( 'Rating', 'ajrwebdesign-core' ), initialOpen: true },
				el( RangeControl, {
					label: __( 'Stars', 'ajrwebdesign-core' ),
					value: meta[ RATING ] || 5,
					min: 1,
					max: 5,
					step: 1,
					withInputField: true,
					help: __( 'Shown as stars above the quote, when the block has star ratings switched on.', 'ajrwebdesign-core' ),
					onChange: function ( value ) {
						update( RATING, value || 5 );
					},
					__next40pxDefaultSize: true,
					__nextHasNoMarginBottom: true,
				} )
			),
			el(
				PluginDocumentSettingPanel,
				{ name: 'ajr-testimonial-logo', title: __( 'Source logo', 'ajrwebdesign-core' ), initialOpen: true },
				el(
					MediaUploadCheck,
					{},
					el( MediaUpload, {
						allowedTypes: [ 'image' ],
						value: logoId,
						onSelect: function ( selected ) {
							update( LOGO_ID, selected && selected.id ? selected.id : 0 );
						},
						render: function ( args ) {
							return el(
								element.Fragment,
								{},
								media && media.source_url
									? el( 'img', {
											src: media.source_url,
											alt: '',
											style: { maxWidth: '140px', display: 'block', marginBottom: '8px' },
									  } )
									: null,
								el(
									Button,
									{ variant: 'secondary', onClick: args.open },
									logoId ? __( 'Replace logo', 'ajrwebdesign-core' ) : __( 'Select logo', 'ajrwebdesign-core' )
								),
								logoId
									? el(
											Button,
											{
												variant: 'tertiary',
												isDestructive: true,
												onClick: function () {
													update( LOGO_ID, 0 );
												},
											},
											__( 'Remove', 'ajrwebdesign-core' )
									  )
									: null
							);
						},
					} )
				),
				el(
					'p',
					{ className: 'description' },
					__( 'The company, or the site the review came from. Shown small in the corner of the card.', 'ajrwebdesign-core' )
				)
			)
		);
	}

	plugins.registerPlugin( 'ajrwd-testimonial', { render: TestimonialPanel } );
} )(
	window.wp.plugins,
	window.wp.editor,
	window.wp.element,
	window.wp.data,
	window.wp.coreData,
	window.wp.blockEditor,
	window.wp.components,
	window.wp.i18n
);
