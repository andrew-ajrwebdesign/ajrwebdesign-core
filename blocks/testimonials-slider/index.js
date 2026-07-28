/**
 * Testimonials Slider block registration (dynamic — no save).
 */
import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import {
	PanelBody,
	RangeControl,
	ToggleControl,
	FormTokenField,
} from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { store as coreStore } from '@wordpress/core-data';
import ServerSideRender from '@wordpress/server-side-render';
import metadata from './block.json';
import './style.css';

registerBlockType( metadata.name, {
	edit: ( { attributes, setAttributes } ) => {
		const { perView, count, showRating, tags } = attributes;
		const blockProps = useBlockProps();
		const terms = useSelect( ( select ) => {
			const records = select( coreStore ).getEntityRecords(
				'taxonomy',
				'testimonial_tag',
				{ per_page: 50, hide_empty: false }
			);
			return records || [];
		}, [] );
		const slugByName = Object.fromEntries(
			terms.map( ( t ) => [ t.name, t.slug ] )
		);
		const nameBySlug = Object.fromEntries(
			terms.map( ( t ) => [ t.slug, t.name ] )
		);
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
						<FormTokenField
							label={ __(
								'Filter by service tags',
								'ajrwebdesign-core'
							) }
							value={ tags.map(
								( slug ) => nameBySlug[ slug ] || slug
							) }
							suggestions={ terms.map( ( t ) => t.name ) }
							onChange={ ( names ) =>
								setAttributes( {
									tags: names.map(
										( name ) => slugByName[ name ] || name
									),
								} )
							}
							__experimentalExpandOnFocus
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
