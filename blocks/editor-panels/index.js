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
import {
	TextareaControl,
	TextControl,
	Button,
	Flex,
	FlexItem,
} from '@wordpress/components';

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

const EMPTY_PHASE = { score: '', lcp: '', cls: '', inp: '' };
const EMPTY_METRICS = {
	mobile: { before: { ...EMPTY_PHASE }, after: { ...EMPTY_PHASE } },
	desktop: { before: { ...EMPTY_PHASE }, after: { ...EMPTY_PHASE } },
};
const EMPTY_IMPACT = {
	cwv_before: '',
	cwv_after: '',
	requests_removed: '',
	page_size_reduced: '',
};

function MetricPhase( { label, phase, onChange } ) {
	const metricLabels = { score: 'Score', lcp: 'LCP', cls: 'CLS', inp: 'INP' };
	return (
		<>
			<p style={ { fontWeight: 600, margin: '8px 0 4px' } }>{ label }</p>
			<Flex wrap gap={ 2 }>
				{ Object.keys( metricLabels ).map( ( key ) => (
					<FlexItem key={ key } style={ { flexBasis: '45%' } }>
						<TextControl
							label={ metricLabels[ key ] }
							value={ phase[ key ] || '' }
							onChange={ ( value ) =>
								onChange( { ...phase, [ key ]: value } )
							}
						/>
					</FlexItem>
				) ) }
			</Flex>
		</>
	);
}

function CaseStudyPanel() {
	const postType = useSelect(
		( select ) => select( 'core/editor' ).getCurrentPostType(),
		[]
	);
	const [ meta, setMeta ] = useEntityProp( 'postType', postType, 'meta' );

	if ( postType !== 'ajr_case_study' ) {
		return null;
	}

	const metrics = {
		...EMPTY_METRICS,
		...( meta?.ajrwd_cs_metrics || {} ),
		mobile: {
			...EMPTY_METRICS.mobile,
			...( meta?.ajrwd_cs_metrics?.mobile || {} ),
		},
		desktop: {
			...EMPTY_METRICS.desktop,
			...( meta?.ajrwd_cs_metrics?.desktop || {} ),
		},
	};
	const impact = { ...EMPTY_IMPACT, ...( meta?.ajrwd_cs_impact || {} ) };

	const setMetrics = ( device, phaseKey, phase ) =>
		setMeta( {
			...meta,
			ajrwd_cs_metrics: {
				...metrics,
				[ device ]: { ...metrics[ device ], [ phaseKey ]: phase },
			},
		} );
	const setImpact = ( key ) => ( value ) =>
		setMeta( { ...meta, ajrwd_cs_impact: { ...impact, [ key ]: value } } );

	return (
		<>
			<PluginDocumentSettingPanel
				name="ajrwd-cs-overview"
				title={ __( 'Case study — overview', 'ajrwebdesign-core' ) }
				initialOpen
			>
				<TextControl
					label={ __( 'Eyebrow / category', 'ajrwebdesign-core' ) }
					value={ meta?.ajrwd_cs_eyebrow || '' }
					onChange={ ( value ) =>
						setMeta( { ...meta, ajrwd_cs_eyebrow: value } )
					}
					placeholder="ECOMMERCE"
				/>
				<TextareaControl
					label={ __( 'Short summary', 'ajrwebdesign-core' ) }
					value={ meta?.ajrwd_cs_summary || '' }
					rows={ 4 }
					onChange={ ( value ) =>
						setMeta( { ...meta, ajrwd_cs_summary: value } )
					}
				/>
			</PluginDocumentSettingPanel>

			<PluginDocumentSettingPanel
				name="ajrwd-cs-mobile"
				title={ __( 'Mobile performance', 'ajrwebdesign-core' ) }
			>
				<MetricPhase
					label={ __( 'Before', 'ajrwebdesign-core' ) }
					phase={ metrics.mobile.before }
					onChange={ ( phase ) =>
						setMetrics( 'mobile', 'before', phase )
					}
				/>
				<MetricPhase
					label={ __( 'After', 'ajrwebdesign-core' ) }
					phase={ metrics.mobile.after }
					onChange={ ( phase ) =>
						setMetrics( 'mobile', 'after', phase )
					}
				/>
			</PluginDocumentSettingPanel>

			<PluginDocumentSettingPanel
				name="ajrwd-cs-desktop"
				title={ __( 'Desktop performance', 'ajrwebdesign-core' ) }
			>
				<MetricPhase
					label={ __( 'Before', 'ajrwebdesign-core' ) }
					phase={ metrics.desktop.before }
					onChange={ ( phase ) =>
						setMetrics( 'desktop', 'before', phase )
					}
				/>
				<MetricPhase
					label={ __( 'After', 'ajrwebdesign-core' ) }
					phase={ metrics.desktop.after }
					onChange={ ( phase ) =>
						setMetrics( 'desktop', 'after', phase )
					}
				/>
			</PluginDocumentSettingPanel>

			<PluginDocumentSettingPanel
				name="ajrwd-cs-impact"
				title={ __( 'Impact tiles', 'ajrwebdesign-core' ) }
			>
				<TextControl
					label={ __( 'Core Web Vitals — before', 'ajrwebdesign-core' ) }
					value={ impact.cwv_before }
					onChange={ setImpact( 'cwv_before' ) }
					placeholder="Failed"
				/>
				<TextControl
					label={ __( 'Core Web Vitals — after', 'ajrwebdesign-core' ) }
					value={ impact.cwv_after }
					onChange={ setImpact( 'cwv_after' ) }
					placeholder="Passed"
				/>
				<TextControl
					label={ __( 'Requests removed', 'ajrwebdesign-core' ) }
					value={ impact.requests_removed }
					onChange={ setImpact( 'requests_removed' ) }
					placeholder="18"
				/>
				<TextControl
					label={ __( 'Page size reduced', 'ajrwebdesign-core' ) }
					value={ impact.page_size_reduced }
					onChange={ setImpact( 'page_size_reduced' ) }
					placeholder="-62%"
				/>
			</PluginDocumentSettingPanel>
		</>
	);
}

registerPlugin( 'ajrwd-editor-panels', {
	render: () => (
		<>
			<GermanTranslationPanel />
			<LogoPanel />
			<CaseStudyPanel />
		</>
	),
} );
