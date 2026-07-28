/**
 * Editor sidebar panels. Currently: the German translation panel on the
 * Testimonials post type (quote + role fields writing to post meta).
 */
import { registerPlugin } from '@wordpress/plugins';
import { PluginDocumentSettingPanel } from '@wordpress/editor';
import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';
import { useEntityProp, store as coreStore } from '@wordpress/core-data';
import { MediaUpload, MediaUploadCheck } from '@wordpress/block-editor';
import { TextareaControl, TextControl, Button } from '@wordpress/components';

function GermanTranslationPanel() {
	const postType = useSelect(
		( select ) => select( 'core/editor' ).getCurrentPostType(),
		[]
	);
	const [ meta, setMeta ] = useEntityProp( 'postType', postType, 'meta' );

	if ( postType !== 'ajr_testimonial' ) {
		return null;
	}

	return (
		<PluginDocumentSettingPanel
			name="ajrwd-testimonial-de"
			title={ __( 'German translation', 'ajrwebdesign-core' ) }
			initialOpen
		>
			<TextareaControl
				label={ __( 'Quote (German)', 'ajrwebdesign-core' ) }
				value={ meta?.ajrwd_t_quote_de || '' }
				rows={ 6 }
				onChange={ ( value ) =>
					setMeta( { ...meta, ajrwd_t_quote_de: value } )
				}
				help={ __(
					'Shown on German pages; English is used when empty.',
					'ajrwebdesign-core'
				) }
			/>
			<TextControl
				label={ __( 'Role line (German)', 'ajrwebdesign-core' ) }
				value={ meta?.ajrwd_t_role_de || '' }
				onChange={ ( value ) =>
					setMeta( { ...meta, ajrwd_t_role_de: value } )
				}
			/>
		</PluginDocumentSettingPanel>
	);
}

function LogoPanel() {
	const postType = useSelect(
		( select ) => select( 'core/editor' ).getCurrentPostType(),
		[]
	);
	const [ meta, setMeta ] = useEntityProp( 'postType', postType, 'meta' );
	const logoId = meta?.ajrwd_t_logo_id || 0;
	const media = useSelect(
		( select ) =>
			logoId ? select( coreStore ).getMedia( logoId ) : null,
		[ logoId ]
	);

	if ( postType !== 'ajr_testimonial' ) {
		return null;
	}

	return (
		<PluginDocumentSettingPanel
			name="ajrwd-testimonial-logo"
			title={ __( 'Source logo', 'ajrwebdesign-core' ) }
			initialOpen
		>
			<MediaUploadCheck>
				<MediaUpload
					allowedTypes={ [ 'image' ] }
					value={ logoId }
					onSelect={ ( m ) =>
						setMeta( { ...meta, ajrwd_t_logo_id: m.id } )
					}
					render={ ( { open } ) => (
						<>
							{ media?.source_url && (
								<img
									src={ media.source_url }
									alt=""
									style={ {
										maxWidth: '140px',
										display: 'block',
										marginBottom: '8px',
									} }
								/>
							) }
							<Button variant="secondary" onClick={ open }>
								{ logoId
									? __( 'Replace logo', 'ajrwebdesign-core' )
									: __( 'Select logo', 'ajrwebdesign-core' ) }
							</Button>
							{ !! logoId && (
								<Button
									variant="tertiary"
									isDestructive
									onClick={ () =>
										setMeta( {
											...meta,
											ajrwd_t_logo_id: 0,
										} )
									}
								>
									{ __( 'Remove', 'ajrwebdesign-core' ) }
								</Button>
							) }
						</>
					) }
				/>
			</MediaUploadCheck>
		</PluginDocumentSettingPanel>
	);
}

registerPlugin( 'ajrwd-editor-panels', {
	render: () => (
		<>
			<GermanTranslationPanel />
			<LogoPanel />
		</>
	),
} );
