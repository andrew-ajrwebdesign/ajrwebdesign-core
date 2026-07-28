/**
 * Editor sidebar panels. Currently: the German translation panel on the
 * Testimonials post type (quote + role fields writing to post meta).
 */
import { registerPlugin } from '@wordpress/plugins';
import { PluginDocumentSettingPanel } from '@wordpress/editor';
import { __ } from '@wordpress/i18n';
import { useSelect } from '@wordpress/data';
import { useEntityProp } from '@wordpress/core-data';
import { TextareaControl, TextControl } from '@wordpress/components';

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

registerPlugin( 'ajrwd-editor-panels', {
	render: GermanTranslationPanel,
} );
