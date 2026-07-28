/**
 * Post Callout block registration (dynamic — no save).
 *
 * Edits the post's callout meta in place: label, title, and text fields
 * write straight to post meta, matching what the frontend renders.
 */
import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';
import { useEntityProp } from '@wordpress/core-data';
import { TextControl, TextareaControl } from '@wordpress/components';
import metadata from './block.json';
import './style.css';

registerBlockType( metadata.name, {
	edit: ( { context } ) => {
		const blockProps = useBlockProps( { className: 'ajr-post-callout' } );
		const postType = context.postType || 'post';
		const [ meta, setMeta ] = useEntityProp(
			'postType',
			postType,
			'meta',
			context.postId
		);

		if ( postType !== 'post' ) {
			return (
				<aside { ...blockProps }>
					<em>
						{ __(
							'Post Callout renders on single blog posts only.',
							'ajrwebdesign-core'
						) }
					</em>
				</aside>
			);
		}

		const update = ( key ) => ( value ) =>
			setMeta( { ...meta, [ key ]: value } );

		return (
			<aside { ...blockProps }>
				<TextControl
					label={ __( 'Label', 'ajrwebdesign-core' ) }
					value={ meta?.ajr_callout_label || '' }
					onChange={ update( 'ajr_callout_label' ) }
					placeholder={ __( 'KEY TAKEAWAY', 'ajrwebdesign-core' ) }
				/>
				<TextControl
					label={ __( 'Title', 'ajrwebdesign-core' ) }
					value={ meta?.ajr_callout_title || '' }
					onChange={ update( 'ajr_callout_title' ) }
				/>
				<TextareaControl
					label={ __( 'Text', 'ajrwebdesign-core' ) }
					value={ meta?.ajr_callout_text || '' }
					onChange={ update( 'ajr_callout_text' ) }
				/>
			</aside>
		);
	},
	save: () => null,
} );
