/**
 * Case Study Mini Card block registration (dynamic — no save).
 */
import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, ComboboxControl } from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { store as coreStore } from '@wordpress/core-data';
import ServerSideRender from '@wordpress/server-side-render';
import metadata from './block.json';
import './style.css';

export function useCaseStudyOptions() {
	return useSelect( ( select ) => {
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
}

registerBlockType( metadata.name, {
	edit: ( { attributes, setAttributes } ) => {
		const { caseStudyId } = attributes;
		const blockProps = useBlockProps();
		const options = useCaseStudyOptions();
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
								'Case Study Mini Card — choose a case study in the sidebar.',
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
