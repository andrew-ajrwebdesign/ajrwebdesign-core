/**
 * Responsive Image — editor UI.
 *
 * Desktop image is required; mobile image is optional art direction served
 * below the breakpoint. The editor preview mirrors the frontend <picture>.
 */
import { __ } from '@wordpress/i18n';
import {
	useBlockProps,
	InspectorControls,
	MediaUpload,
	MediaUploadCheck,
} from '@wordpress/block-editor';
import {
	PanelBody,
	Button,
	TextControl,
	RangeControl,
	SelectControl,
} from '@wordpress/components';
import { useSelect } from '@wordpress/data';
import { store as coreStore } from '@wordpress/core-data';

const ALLOWED_MEDIA = [ 'image' ];

function ImagePicker( { label, imageId, onSelect, onRemove } ) {
	const media = useSelect(
		( select ) =>
			imageId ? select( coreStore ).getMedia( imageId ) : null,
		[ imageId ]
	);
	return (
		<div style={ { marginBottom: '16px' } }>
			<p style={ { fontWeight: 600, marginBottom: '4px' } }>{ label }</p>
			<MediaUploadCheck>
				<MediaUpload
					allowedTypes={ ALLOWED_MEDIA }
					value={ imageId }
					onSelect={ onSelect }
					render={ ( { open } ) => (
						<>
							{ media?.source_url && (
								<img
									src={
										media.media_details?.sizes?.medium
											?.source_url || media.source_url
									}
									alt=""
									style={ {
										maxWidth: '100%',
										display: 'block',
										marginBottom: '8px',
										borderRadius: '4px',
									} }
								/>
							) }
							<Button variant="secondary" onClick={ open }>
								{ imageId
									? __( 'Replace', 'ajrwebdesign-core' )
									: __( 'Select image', 'ajrwebdesign-core' ) }
							</Button>
							{ !! imageId && (
								<Button
									variant="tertiary"
									isDestructive
									onClick={ onRemove }
								>
									{ __( 'Remove', 'ajrwebdesign-core' ) }
								</Button>
							) }
						</>
					) }
				/>
			</MediaUploadCheck>
		</div>
	);
}

export default function Edit( { attributes, setAttributes } ) {
	const {
		desktopImageId,
		mobileImageId,
		alt,
		breakpoint,
		loading,
		fetchPriority,
		borderRadius,
	} = attributes;

	const desktopMedia = useSelect(
		( select ) =>
			desktopImageId
				? select( coreStore ).getMedia( desktopImageId )
				: null,
		[ desktopImageId ]
	);

	const blockProps = useBlockProps( {
		className: 'ajr-responsive-image',
		style:
			borderRadius > 0
				? { borderRadius: `${ borderRadius }px`, overflow: 'hidden' }
				: undefined,
	} );

	return (
		<>
			<InspectorControls>
				<PanelBody
					title={ __( 'Images', 'ajrwebdesign-core' ) }
					initialOpen
				>
					<ImagePicker
						label={ __( 'Desktop image', 'ajrwebdesign-core' ) }
						imageId={ desktopImageId }
						onSelect={ ( media ) =>
							setAttributes( { desktopImageId: media.id } )
						}
						onRemove={ () =>
							setAttributes( { desktopImageId: 0 } )
						}
					/>
					<ImagePicker
						label={ __(
							'Mobile image (optional)',
							'ajrwebdesign-core'
						) }
						imageId={ mobileImageId }
						onSelect={ ( media ) =>
							setAttributes( { mobileImageId: media.id } )
						}
						onRemove={ () => setAttributes( { mobileImageId: 0 } ) }
					/>
					<TextControl
						label={ __( 'Alt text override', 'ajrwebdesign-core' ) }
						value={ alt }
						onChange={ ( value ) => setAttributes( { alt: value } ) }
						help={ __(
							'Leave empty to use the desktop image’s alt text.',
							'ajrwebdesign-core'
						) }
					/>
				</PanelBody>
				<PanelBody
					title={ __( 'Display', 'ajrwebdesign-core' ) }
					initialOpen={ false }
				>
					<RangeControl
						label={ __(
							'Mobile breakpoint (px)',
							'ajrwebdesign-core'
						) }
						value={ breakpoint }
						min={ 320 }
						max={ 1200 }
						onChange={ ( value ) =>
							setAttributes( { breakpoint: value } )
						}
					/>
					<RangeControl
						label={ __( 'Border radius (px)', 'ajrwebdesign-core' ) }
						value={ borderRadius }
						min={ 0 }
						max={ 40 }
						onChange={ ( value ) =>
							setAttributes( { borderRadius: value } )
						}
					/>
					<SelectControl
						label={ __( 'Loading', 'ajrwebdesign-core' ) }
						value={ loading }
						options={ [
							{ label: 'lazy', value: 'lazy' },
							{ label: 'eager', value: 'eager' },
						] }
						onChange={ ( value ) =>
							setAttributes( { loading: value } )
						}
					/>
					<SelectControl
						label={ __( 'Fetch priority', 'ajrwebdesign-core' ) }
						value={ fetchPriority }
						options={ [
							{ label: 'auto', value: 'auto' },
							{ label: 'high', value: 'high' },
							{ label: 'low', value: 'low' },
						] }
						onChange={ ( value ) =>
							setAttributes( { fetchPriority: value } )
						}
					/>
				</PanelBody>
			</InspectorControls>

			<figure { ...blockProps }>
				{ desktopMedia?.source_url ? (
					<img
						className="ajr-responsive-image__img"
						src={ desktopMedia.source_url }
						alt={ alt }
						style={ { display: 'block', maxWidth: '100%' } }
					/>
				) : (
					<div
						style={ {
							padding: '48px 16px',
							background: '#f0f0f0',
							textAlign: 'center',
							color: '#757575',
						} }
					>
						{ __(
							'Responsive Image — choose a desktop image in the sidebar.',
							'ajrwebdesign-core'
						) }
					</div>
				) }
			</figure>
		</>
	);
}
