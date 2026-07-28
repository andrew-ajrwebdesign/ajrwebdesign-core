/**
 * Post Breadcrumbs block registration (dynamic — no save).
 */
import { registerBlockType } from '@wordpress/blocks';
import { __ } from '@wordpress/i18n';
import { useBlockProps, InspectorControls } from '@wordpress/block-editor';
import { PanelBody, TextControl, ToggleControl } from '@wordpress/components';
import metadata from './block.json';
import './style.css';

registerBlockType( metadata.name, {
	edit: ( { attributes, setAttributes } ) => {
		const { separator, showHome } = attributes;
		const blockProps = useBlockProps( { className: 'ajr-breadcrumbs' } );
		return (
			<>
				<InspectorControls>
					<PanelBody title={ __( 'Breadcrumbs', 'ajrwebdesign-core' ) }>
						<ToggleControl
							label={ __( 'Show Home link', 'ajrwebdesign-core' ) }
							checked={ showHome }
							onChange={ ( value ) =>
								setAttributes( { showHome: value } )
							}
						/>
						<TextControl
							label={ __( 'Separator', 'ajrwebdesign-core' ) }
							value={ separator }
							onChange={ ( value ) =>
								setAttributes( { separator: value } )
							}
						/>
					</PanelBody>
				</InspectorControls>
				<nav { ...blockProps }>
					{ showHome && (
						<>
							<a href="#home" onClick={ ( e ) => e.preventDefault() }>
								{ __( 'Home', 'ajrwebdesign-core' ) }
							</a>
							<span
								className="ajr-breadcrumb-separator"
								aria-hidden="true"
							>
								{ separator }
							</span>
						</>
					) }
					<a href="#blog" onClick={ ( e ) => e.preventDefault() }>
						{ __( 'Resources', 'ajrwebdesign-core' ) }
					</a>
					<span className="ajr-breadcrumb-separator" aria-hidden="true">
						{ separator }
					</span>
					<a href="#category" onClick={ ( e ) => e.preventDefault() }>
						{ __( 'Category', 'ajrwebdesign-core' ) }
					</a>
				</nav>
			</>
		);
	},
	save: () => null,
} );
