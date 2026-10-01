<?php
/**
 * The case-study post type's keys, as this plugin's case-study extras refer to them.
 *
 * Registered by CaseStudies\CaseStudies (AJR Core registered them from this plugin's 1.9.0 until
 * 1.15.0). The keys never changed, so every case study and its /case-studies/ URL carried over
 * both ways. The classes beside it are this site's own layer: the Core Web Vitals metrics and
 * impact fields, their metabox, the score-circle cards, the story intro and the SEO tweaks.
 *
 * @package AJR\SiteCore
 */

namespace AJR\SiteCore\CaseStudies;

defined( 'ABSPATH' ) || exit;

/**
 * Case-study keys, shared by every class in this folder. Registration is CaseStudies\CaseStudies.
 */
class PostType {

	/**
	 * The case-study post type.
	 */
	public const POST_TYPE = 'ajr_case_study';

	/**
	 * The case-study tag taxonomy.
	 */
	public const TAXONOMY = 'case_study_tag';
}
