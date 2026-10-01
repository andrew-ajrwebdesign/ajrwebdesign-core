/**
 * Case study card — the editor side.
 *
 * ⛔ Plain JavaScript against wp.element, with NO JSX and NO build step. Ported unchanged from
 * AJR Core 0.15.2 (2026-10-01), where it was written that way so the plugin needs no build;
 * kept so here, outside this plugin's webpack blocks, so its markup stays byte-for-byte the same.
 *
 * The preview is the real server render. Inside a Query Loop the card has no case study of
 * its own and shows the loop's post; the server-side preview is not given block context, so
 * the loop's post ID is passed to it as the attribute instead — for the preview only, it is
 * never saved.
 */
( function ( blocks, element, blockEditor, components, data, coreData, i18n, ServerSideRender ) {
	'use strict';

	var el = element.createElement;
	var __ = i18n.__;

	var InspectorControls = blockEditor.InspectorControls;
	var useBlockProps = blockEditor.useBlockProps;

	var PanelBody = components.PanelBody;
	var SelectControl = components.SelectControl;
	var ToggleControl = components.ToggleControl;
	var Placeholder = components.Placeholder;

	var BLOCK = 'ajr/case-study-card';
	var POST_TYPE = 'ajr_case_study';

	blocks.registerBlockType( BLOCK, {
		edit: function ( props ) {
			var attributes = props.attributes;
			var setAttributes = props.setAttributes;
			var context = props.context || {};

			var posts = data.useSelect( function ( select ) {
				return (
					select( coreData.store ).getEntityRecords( 'postType', POST_TYPE, {
						per_page: 100,
						status: 'publish',
						orderby: 'title',
						order: 'asc',
						context: 'edit',
						_fields: 'id,title',
					} ) || []
				);
			}, [] );

			var inLoop = POST_TYPE === context.postType && context.postId;
			var previewId = attributes.caseStudyId || ( inLoop ? context.postId : 0 );

			var options = [
				{
					value: 0,
					label: inLoop
						? __( 'The case study in this Query Loop', 'ajrwebdesign-core' )
						: __( 'Choose a case study', 'ajrwebdesign-core' ),
				},
			].concat(
				posts.map( function ( post ) {
					return {
						value: post.id,
						// The raw title: the rendered one is HTML-encoded (an apostrophe shows as &#8217;).
						label: ( post.title && ( post.title.raw || post.title.rendered ) ) || '#' + post.id,
					};
				} )
			);

			var controls = el(
				InspectorControls,
				{},
				el(
					PanelBody,
					{ title: __( 'Case study', 'ajrwebdesign-core' ) },
					el( SelectControl, {
						label: __( 'Case study', 'ajrwebdesign-core' ),
						value: attributes.caseStudyId,
						options: options,
						help: __( 'Inside a Query Loop of case studies, leave this unset to show each one in turn.', 'ajrwebdesign-core' ),
						onChange: function ( value ) {
							setAttributes( { caseStudyId: parseInt( value, 10 ) || 0 } );
						},
						__next40pxDefaultSize: true,
						__nextHasNoMarginBottom: true,
					} ),
					el( ToggleControl, {
						label: __( 'Show the picture', 'ajrwebdesign-core' ),
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
					attributes: Object.assign( {}, attributes, { caseStudyId: previewId } ),
					EmptyResponsePlaceholder: function () {
						return el( Placeholder, {
							icon: 'analytics',
							label: __( 'Case study card', 'ajrwebdesign-core' ),
							instructions: __(
								'Choose a published case study in the block settings, or place this card inside a Query Loop of case studies. Visitors see nothing here until there is something to show.',
								'ajrwebdesign-core'
							),
						} );
					},
				} )
			);
		},

		// Rendered in PHP from the stored case study, so editing it updates every card.
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
