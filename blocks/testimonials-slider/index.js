/**
 * Testimonials Slider block registration (dynamic — no save).
 */
import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, RangeControl, ToggleControl } from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';
import metadata from './block.json';
import './style.css';

registerBlockType( metadata.name, {
	edit: ( { attributes, setAttributes } ) => {
		const { perView, count, showRating } = attributes;
		const blockProps = useBlockProps();
		return (
			<>
				<InspectorControls>
					<PanelBody title={ __( 'Slider', 'ajrwebdesign-core' ) }>
						<RangeControl
							label={ __(
								'Cards per view (desktop)',
								'ajrwebdesign-core'
							) }
							value={ perView }
							min={ 1 }
							max={ 3 }
							onChange={ ( value ) =>
								setAttributes( { perView: value } )
							}
						/>
						<RangeControl
							label={ __(
								'Number of testimonials (0 = all)',
								'ajrwebdesign-core'
							) }
							value={ count }
							min={ 0 }
							max={ 12 }
							onChange={ ( value ) =>
								setAttributes( { count: value } )
							}
						/>
						<ToggleControl
							label={ __( 'Show star ratings', 'ajrwebdesign-core' ) }
							checked={ showRating }
							onChange={ ( value ) =>
								setAttributes( { showRating: value } )
							}
						/>
					</PanelBody>
				</InspectorControls>
				<div { ...blockProps }>
					<ServerSideRender
						block={ metadata.name }
						attributes={ attributes }
					/>
				</div>
			</>
		);
	},
	save: () => null,
} );
