/**
 * Case Study Card block registration (dynamic — no save).
 */
import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import {
	PanelBody,
	ComboboxControl,
	SelectControl,
} from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { store as coreStore } from '@wordpress/core-data';
import ServerSideRender from '@wordpress/server-side-render';
import metadata from './block.json';
import './style.css';

registerBlockType( metadata.name, {
	edit: ( { attributes, setAttributes } ) => {
		const { caseStudyId, layoutDirection } = attributes;
		const blockProps = useBlockProps();
		const options = useSelect( ( select ) => {
			const posts = select( coreStore ).getEntityRecords(
				'postType',
				'ajr_case_study',
				{ per_page: 100, status: 'publish' }
			);
			return ( posts || [] ).map( ( post ) => ( {
				value: String( post.id ),
				label: post.title?.rendered || `#${ post.id }`,
			} ) );
		}, [] );

		return (
			<>
				<InspectorControls>
					<PanelBody title={ __( 'Case study', 'ajrwebdesign-core' ) }>
						<ComboboxControl
							label={ __(
								'Select case study',
								'ajrwebdesign-core'
							) }
							value={ caseStudyId ? String( caseStudyId ) : '' }
							options={ options }
							onChange={ ( value ) =>
								setAttributes( {
									caseStudyId: value
										? parseInt( value, 10 )
										: 0,
								} )
							}
						/>
						<SelectControl
							label={ __( 'Layout', 'ajrwebdesign-core' ) }
							value={ layoutDirection }
							options={ [
								{
									label: __(
										'Stats left',
										'ajrwebdesign-core'
									),
									value: 'stats-left',
								},
								{
									label: __(
										'Text left',
										'ajrwebdesign-core'
									),
									value: 'text-left',
								},
							] }
							onChange={ ( value ) =>
								setAttributes( { layoutDirection: value } )
							}
						/>
					</PanelBody>
				</InspectorControls>
				<div { ...blockProps }>
					{ caseStudyId ? (
						<ServerSideRender
							block={ metadata.name }
							attributes={ attributes }
						/>
					) : (
						<p>
							{ __(
								'Case Study Card — choose a case study in the sidebar.',
								'ajrwebdesign-core'
							) }
						</p>
					) }
				</div>
			</>
		);
	},
	save: () => null,
} );
