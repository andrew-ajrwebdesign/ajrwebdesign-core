/**
 * Case study edit screen — the sidebar panel for the client, the result summary and the
 * before-and-after results.
 *
 * ⛔ Plain JavaScript against wp.element, with NO JSX and NO build step, like the blocks.
 * Loaded only on the case study edit screen (Case_Studies::enqueue_panel()).
 *
 * A result row is shown on the site only when it has a label and an "after" value; the
 * server drops the rest on save (Case_Studies::sanitize_results()), and the help text says so.
 */
( function ( plugins, editor, element, data, coreData, components, i18n ) {
	'use strict';

	var el = element.createElement;
	var __ = i18n.__;

	var POST_TYPE = 'ajr_case_study';
	var CLIENT = 'ajr_case_study_client';
	var SUMMARY = 'ajr_case_study_summary';
	var RESULTS = 'ajr_case_study_results';
	var MAX_RESULTS = 6;
	var MAX_LENGTH = 60;

	// wp.editor since WordPress 6.6; wp.editPost before that.
	var PluginDocumentSettingPanel =
		editor.PluginDocumentSettingPanel || ( window.wp.editPost && window.wp.editPost.PluginDocumentSettingPanel );

	var Button = components.Button;
	var TextControl = components.TextControl;
	var TextareaControl = components.TextareaControl;

	/**
	 * The panel.
	 *
	 * @return {?Object} The element, or null on any other post type.
	 */
	function CaseStudyPanel() {
		var postType = data.useSelect( function ( select ) {
			return select( 'core/editor' ).getCurrentPostType();
		}, [] );

		var entity = coreData.useEntityProp( 'postType', postType, 'meta' );
		var meta = entity[ 0 ] || {};
		var setMeta = entity[ 1 ];

		if ( POST_TYPE !== postType || ! PluginDocumentSettingPanel ) {
			return null;
		}

		var results = Array.isArray( meta[ RESULTS ] ) ? meta[ RESULTS ] : [];

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

		/**
		 * Replace the results list.
		 *
		 * @param {Array} rows New rows.
		 */
		function setResults( rows ) {
			update( RESULTS, rows );
		}

		/**
		 * Change one field of one row.
		 *
		 * @param {number} index Row index.
		 * @param {string} field label, before or after.
		 * @param {string} value New value.
		 */
		function setField( index, field, value ) {
			setResults(
				results.map( function ( row, i ) {
					if ( i !== index ) {
						return row;
					}
					var next = { label: row.label || '', before: row.before || '', after: row.after || '' };
					next[ field ] = value;
					return next;
				} )
			);
		}

		/**
		 * Move a row up or down.
		 *
		 * @param {number} index Row index.
		 * @param {number} step  -1 up, 1 down.
		 */
		function move( index, step ) {
			var target = index + step;
			if ( target < 0 || target >= results.length ) {
				return;
			}
			var next = results.slice();
			var row = next[ index ];
			next[ index ] = next[ target ];
			next[ target ] = row;
			setResults( next );
		}

		var rows = results.map( function ( row, index ) {
			/* translators: %d: the result's position in the list, from 1. */
			var legend = i18n.sprintf( __( 'Result %d', 'ajrwebdesign-core' ), index + 1 );

			return el(
				'fieldset',
				{
					key: index,
					className: 'ajr-case-study-result',
					style: { border: 0, borderBlockEnd: '1px solid color-mix(in srgb, currentColor 15%, transparent)', padding: '0 0 12px', margin: '0 0 12px' },
				},
				el( 'legend', { style: { fontWeight: 600, marginBlockEnd: '8px' } }, legend ),
				el( TextControl, {
					label: __( 'What changed', 'ajrwebdesign-core' ),
					value: row.label || '',
					maxLength: MAX_LENGTH,
					placeholder: __( 'e.g. Page load time', 'ajrwebdesign-core' ),
					onChange: function ( value ) {
						setField( index, 'label', value );
					},
					__next40pxDefaultSize: true,
					__nextHasNoMarginBottom: true,
				} ),
				el(
					'div',
					{ style: { display: 'grid', gridTemplateColumns: '1fr 1fr', gap: '8px', marginBlockStart: '8px' } },
					el( TextControl, {
						label: __( 'Before (optional)', 'ajrwebdesign-core' ),
						value: row.before || '',
						maxLength: MAX_LENGTH,
						placeholder: __( 'e.g. 4.1s', 'ajrwebdesign-core' ),
						onChange: function ( value ) {
							setField( index, 'before', value );
						},
						__next40pxDefaultSize: true,
						__nextHasNoMarginBottom: true,
					} ),
					el( TextControl, {
						label: __( 'After', 'ajrwebdesign-core' ),
						value: row.after || '',
						maxLength: MAX_LENGTH,
						placeholder: __( 'e.g. 1.2s', 'ajrwebdesign-core' ),
						onChange: function ( value ) {
							setField( index, 'after', value );
						},
						__next40pxDefaultSize: true,
						__nextHasNoMarginBottom: true,
					} )
				),
				el(
					'div',
					{ style: { display: 'flex', gap: '4px', marginBlockStart: '8px' } },
					el(
						Button,
						{
							size: 'small',
							variant: 'tertiary',
							disabled: 0 === index,
							accessibleWhenDisabled: true,
							onClick: function () {
								move( index, -1 );
							},
						},
						__( 'Move up', 'ajrwebdesign-core' )
					),
					el(
						Button,
						{
							size: 'small',
							variant: 'tertiary',
							disabled: index === results.length - 1,
							accessibleWhenDisabled: true,
							onClick: function () {
								move( index, 1 );
							},
						},
						__( 'Move down', 'ajrwebdesign-core' )
					),
					el(
						Button,
						{
							size: 'small',
							variant: 'tertiary',
							isDestructive: true,
							onClick: function () {
								setResults(
									results.filter( function ( _row, i ) {
										return i !== index;
									} )
								);
							},
						},
						__( 'Remove', 'ajrwebdesign-core' )
					)
				)
			);
		} );

		return el(
			element.Fragment,
			{},
			el(
				PluginDocumentSettingPanel,
				{ name: 'ajr-case-study-details', title: __( 'Case study details', 'ajrwebdesign-core' ), initialOpen: true },
				el( TextControl, {
					label: __( 'Client', 'ajrwebdesign-core' ),
					value: meta[ CLIENT ] || '',
					help: __( 'The client, or their industry and town, e.g. "Dental practice, Leeds".', 'ajrwebdesign-core' ),
					onChange: function ( value ) {
						update( CLIENT, value );
					},
					__next40pxDefaultSize: true,
					__nextHasNoMarginBottom: true,
				} ),
				el( 'div', { style: { marginBlockStart: '16px' } } ),
				el( TextareaControl, {
					label: __( 'Result summary', 'ajrwebdesign-core' ),
					value: meta[ SUMMARY ] || '',
					rows: 4,
					help: __( 'One short paragraph on the card. Left empty, the card uses the excerpt if one is written.', 'ajrwebdesign-core' ),
					onChange: function ( value ) {
						update( SUMMARY, value );
					},
					__nextHasNoMarginBottom: true,
				} )
			),
			el(
				PluginDocumentSettingPanel,
				{ name: 'ajr-case-study-results', title: __( 'Results', 'ajrwebdesign-core' ), initialOpen: true },
				el(
					'p',
					{ className: 'description', style: { marginBlockStart: 0 } },
					__( 'Up to six before-and-after results, shown on the card in this order. A result needs "What changed" and "After" to be shown.', 'ajrwebdesign-core' )
				),
				rows,
				el(
					Button,
					{
						variant: 'secondary',
						disabled: results.length >= MAX_RESULTS,
						accessibleWhenDisabled: true,
						onClick: function () {
							setResults( results.concat( [ { label: '', before: '', after: '' } ] ) );
						},
					},
					results.length >= MAX_RESULTS ? __( 'Six results is the most', 'ajrwebdesign-core' ) : __( 'Add a result', 'ajrwebdesign-core' )
				)
			)
		);
	}

	plugins.registerPlugin( 'ajrwd-case-study', { render: CaseStudyPanel } );
} )(
	window.wp.plugins,
	window.wp.editor,
	window.wp.element,
	window.wp.data,
	window.wp.coreData,
	window.wp.components,
	window.wp.i18n
);
