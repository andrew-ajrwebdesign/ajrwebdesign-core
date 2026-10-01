/**
 * Testimonials — the editor side.
 *
 * ⛔ Plain JavaScript against wp.element, with NO JSX and NO build step. Ported unchanged from
 * AJR Core 0.15.2 (2026-10-01), where it was written that way so the plugin needs no build;
 * kept so here, outside this plugin's webpack blocks, so its markup stays byte-for-byte the same.
 *
 * The preview is the real server render, so what the client arranges is what the page shows.
 * The tag filter lists the site's own tags by name and stores their slugs.
 */
( function ( blocks, element, blockEditor, components, data, coreData, i18n, ServerSideRender ) {
	'use strict';

	var el = element.createElement;
	var __ = i18n.__;

	var InspectorControls = blockEditor.InspectorControls;
	var useBlockProps = blockEditor.useBlockProps;

	var PanelBody = components.PanelBody;
	var RangeControl = components.RangeControl;
	var ToggleControl = components.ToggleControl;
	var FormTokenField = components.FormTokenField;
	var Placeholder = components.Placeholder;

	var BLOCK = 'ajr/testimonials';

	blocks.registerBlockType( BLOCK, {
		edit: function ( props ) {
			var attributes = props.attributes;
			var setAttributes = props.setAttributes;
			var tags = attributes.tags || [];

			var terms = data.useSelect( function ( select ) {
				return (
					select( coreData.store ).getEntityRecords( 'taxonomy', 'testimonial_tag', {
						per_page: 100,
						hide_empty: false,
					} ) || []
				);
			}, [] );

			var slugByName = {};
			var nameBySlug = {};
			terms.forEach( function ( term ) {
				slugByName[ term.name ] = term.slug;
				nameBySlug[ term.slug ] = term.name;
			} );

			var controls = el(
				InspectorControls,
				{},
				el(
					PanelBody,
					{ title: __( 'Slider', 'ajrwebdesign-core' ) },
					el( RangeControl, {
						label: __( 'Cards per view (desktop)', 'ajrwebdesign-core' ),
						value: attributes.perView,
						min: 1,
						max: 3,
						help: __( 'On a phone it is always one card at a time.', 'ajrwebdesign-core' ),
						onChange: function ( value ) {
							setAttributes( { perView: value } );
						},
					} ),
					el( RangeControl, {
						label: __( 'Number of testimonials (0 = all)', 'ajrwebdesign-core' ),
						value: attributes.count,
						min: 0,
						max: 12,
						help: __( 'At most 12 are ever shown.', 'ajrwebdesign-core' ),
						onChange: function ( value ) {
							setAttributes( { count: value } );
						},
					} ),
					el( ToggleControl, {
						label: __( 'Show star ratings', 'ajrwebdesign-core' ),
						checked: !! attributes.showRating,
						onChange: function ( value ) {
							setAttributes( { showRating: value } );
						},
					} ),
					el( FormTokenField, {
						label: __( 'Only show quotes with these tags', 'ajrwebdesign-core' ),
						value: tags.map( function ( slug ) {
							return nameBySlug[ slug ] || slug;
						} ),
						suggestions: terms.map( function ( term ) {
							return term.name;
						} ),
						onChange: function ( names ) {
							setAttributes( {
								tags: names.map( function ( name ) {
									return slugByName[ name ] || name;
								} ),
							} );
						},
						__experimentalExpandOnFocus: true,
						__next40pxDefaultSize: true,
					} )
				)
			);

			return el(
				'div',
				useBlockProps(),
				controls,
				el( ServerSideRender, {
					block: BLOCK,
					attributes: attributes,
					// The front end prints nothing when no quote matches; the editor says why.
					EmptyResponsePlaceholder: function () {
						return el( Placeholder, {
							icon: 'format-quote',
							label: __( 'Testimonials', 'ajrwebdesign-core' ),
							instructions: __(
								'No published testimonial matches this block yet. Add quotes under Testimonials in the admin menu, or remove the tag filter. Visitors see nothing here until there is something to show.',
								'ajrwebdesign-core'
							),
						} );
					},
				} )
			);
		},

		// Rendered in PHP from the stored testimonials, so editing a quote updates every page.
		save: function () {
			return null;
		},
	} );
} )(
	window.wp.blocks,
	window.wp.element,
	window.wp.blockEditor,
	window.wp.components,
	window.wp.data,
	window.wp.coreData,
	window.wp.i18n,
	window.wp.serverSideRender
);
