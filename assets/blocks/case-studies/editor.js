/**
 * Case studies grid — the editor side.
 *
 * ⛔ Plain JavaScript against wp.element, with NO JSX and NO build step. Ported unchanged from
 * AJR Core 0.15.2 (2026-10-01), where it was written that way so the plugin needs no build;
 * kept so here, outside this plugin's webpack blocks, so its markup stays byte-for-byte the same.
 *
 * The preview is the real server render. The tag filter lists the site's own tags by name
 * and stores the chosen slug.
 */
( function ( blocks, element, blockEditor, components, data, coreData, i18n, ServerSideRender ) {
	'use strict';

	var el = element.createElement;
	var __ = i18n.__;

	var InspectorControls = blockEditor.InspectorControls;
	var useBlockProps = blockEditor.useBlockProps;

	var PanelBody = components.PanelBody;
	var RangeControl = components.RangeControl;
	var SelectControl = components.SelectControl;
	var ToggleControl = components.ToggleControl;
	var Placeholder = components.Placeholder;

	var BLOCK = 'ajr/case-studies';

	blocks.registerBlockType( BLOCK, {
		edit: function ( props ) {
			var attributes = props.attributes;
			var setAttributes = props.setAttributes;

			var terms = data.useSelect( function ( select ) {
				return (
					select( coreData.store ).getEntityRecords( 'taxonomy', 'case_study_tag', {
						per_page: 100,
						hide_empty: false,
					} ) || []
				);
			}, [] );

			var controls = el(
				InspectorControls,
				{},
				el(
					PanelBody,
					{ title: __( 'Grid', 'ajrwebdesign-core' ) },
					el( RangeControl, {
						label: __( 'Number of case studies', 'ajrwebdesign-core' ),
						value: attributes.count,
						min: 1,
						max: 12,
						onChange: function ( value ) {
							setAttributes( { count: value || 3 } );
						},
						__next40pxDefaultSize: true,
						__nextHasNoMarginBottom: true,
					} ),
					el( RangeControl, {
						label: __( 'Columns (desktop)', 'ajrwebdesign-core' ),
						value: attributes.columns,
						min: 1,
						max: 4,
						help: __( 'On a phone it is always one column.', 'ajrwebdesign-core' ),
						onChange: function ( value ) {
							setAttributes( { columns: value || 3 } );
						},
						__next40pxDefaultSize: true,
						__nextHasNoMarginBottom: true,
					} ),
					el( SelectControl, {
						label: __( 'Only case studies with this tag', 'ajrwebdesign-core' ),
						value: attributes.tag,
						options: [ { value: '', label: __( 'All case studies', 'ajrwebdesign-core' ) } ].concat(
							terms.map( function ( term ) {
								return { value: term.slug, label: term.name };
							} )
						),
						onChange: function ( value ) {
							setAttributes( { tag: value } );
						},
						__next40pxDefaultSize: true,
						__nextHasNoMarginBottom: true,
					} ),
					el( ToggleControl, {
						label: __( 'Show the pictures', 'ajrwebdesign-core' ),
						checked: !! attributes.showImage,
						onChange: function ( value ) {
							setAttributes( { showImage: value } );
						},
						__nextHasNoMarginBottom: true,
					} ),
					el( ToggleControl, {
						label: __( 'Show the results', 'ajrwebdesign-core' ),
						checked: !! attributes.showResults,
						onChange: function ( value ) {
							setAttributes( { showResults: value } );
						},
						__nextHasNoMarginBottom: true,
					} ),
					el( SelectControl, {
						label: __( 'Title heading level', 'ajrwebdesign-core' ),
						value: String( attributes.headingLevel || 3 ),
						options: [
							{ value: '2', label: 'H2' },
							{ value: '3', label: 'H3' },
							{ value: '4', label: 'H4' },
						],
						help: __( 'One level below the heading above this block: H2 straight under the page title, H3 under an H2 section heading.', 'ajrwebdesign-core' ),
						onChange: function ( value ) {
							setAttributes( { headingLevel: parseInt( value, 10 ) || 3 } );
						},
						__next40pxDefaultSize: true,
						__nextHasNoMarginBottom: true,
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
					// The front end prints nothing when no case study matches; the editor says why.
					EmptyResponsePlaceholder: function () {
						return el( Placeholder, {
							icon: 'analytics',
							label: __( 'Case studies', 'ajrwebdesign-core' ),
							instructions: __(
								'No published case study matches this block yet. Add them under Case Studies in the admin menu, or remove the tag filter. Visitors see nothing here until there is something to show.',
								'ajrwebdesign-core'
							),
						} );
					},
				} )
			);
		},

		// Rendered in PHP from the stored case studies, so editing one updates every page.
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
