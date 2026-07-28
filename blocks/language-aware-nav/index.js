/**
 * Language-Aware Navigation block registration (dynamic — no save).
 */
import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, TextControl, Notice } from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';
import metadata from './block.json';

registerBlockType( metadata.name, {
	edit: ( { attributes, setAttributes } ) => {
		const { menuSlug } = attributes;
		const blockProps = useBlockProps();
		return (
			<>
				<InspectorControls>
					<PanelBody
						title={ __( 'Navigation menu', 'ajrwebdesign-core' ) }
					>
						<TextControl
							label={ __( 'Menu slug', 'ajrwebdesign-core' ) }
							value={ menuSlug }
							onChange={ ( value ) =>
								setAttributes( { menuSlug: value } )
							}
							help={ __(
								'Resolves the navigation named “{slug}-{language}” (e.g. primary-en, primary-de) for the visitor’s language, falling back to the default language.',
								'ajrwebdesign-core'
							) }
						/>
						<Notice status="info" isDismissible={ false }>
							{ __(
								'The preview shows the default-language menu.',
								'ajrwebdesign-core'
							) }
						</Notice>
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
