/**
 * Post Intro block registration (dynamic — no save).
 *
 * Edits the post's intro meta directly in place, so the editor shows and
 * saves the real value rather than a static preview.
 */
import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import { useBlockProps } from '@wordpress/block-editor';
import { useEntityProp } from '@wordpress/core-data';
import { TextareaControl } from '@wordpress/components';
import metadata from './block.json';
import './style.css';

registerBlockType( metadata.name, {
	edit: ( { context } ) => {
		const blockProps = useBlockProps( { className: 'ajr-post-intro' } );
		const postType = context.postType || 'post';
		const [ meta, setMeta ] = useEntityProp(
			'postType',
			postType,
			'meta',
			context.postId
		);

		if ( postType !== 'post' ) {
			return (
				<div { ...blockProps }>
					<em>
						{ __(
							'Post Intro renders on single blog posts only.',
							'ajrwebdesign-core'
						) }
					</em>
				</div>
			);
		}

		return (
			<div { ...blockProps }>
				<TextareaControl
					label={ __( 'Intro text', 'ajrwebdesign-core' ) }
					value={ meta?.ajr_intro_text || '' }
					onChange={ ( value ) =>
						setMeta( { ...meta, ajr_intro_text: value } )
					}
					placeholder={ __(
						'Intro paragraph shown under the post title…',
						'ajrwebdesign-core'
					) }
				/>
			</div>
		);
	},
	save: () => null,
} );
