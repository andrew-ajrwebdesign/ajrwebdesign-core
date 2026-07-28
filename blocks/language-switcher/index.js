/**
 * Language Switcher block registration (dynamic — no save).
 */
import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, ToggleControl } from '@wordpress/components';
import ServerSideRender from '@wordpress/server-side-render';
import metadata from './block.json';
import './style.css';

registerBlockType( metadata.name, {
	edit: ( { attributes, setAttributes } ) => {
		const { showFlags, showNames } = attributes;
		const blockProps = useBlockProps();
		return (
			<>
				<InspectorControls>
					<PanelBody
						title={ __( 'Language switcher', 'ajrwebdesign-core' ) }
					>
						<ToggleControl
							label={ __( 'Show flags', 'ajrwebdesign-core' ) }
							checked={ showFlags }
							onChange={ ( value ) =>
								setAttributes( { showFlags: value } )
							}
						/>
						<ToggleControl
							label={ __(
								'Show language name',
								'ajrwebdesign-core'
							) }
							checked={ showNames }
							onChange={ ( value ) =>
								setAttributes( { showNames: value } )
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
