<?php
/**
 * The case-study post type's keys, as this plugin's case-study extras refer to them.
 *
 * AJR Core registers `ajr_case_study` and `case_study_tag` (its Case studies module, 0.12.0;
 * until 1.9.0 this plugin registered them). The keys are unchanged, so every case study and its
 * /case-studies/ URL carried over. What stays here is this site's own layer on top: the Core Web
 * Vitals metrics and impact fields, their metabox, the score-circle cards, the story intro and
 * the case-study SEO tweaks.
 *
 * @package AJR\SiteCore
 */

namespace AJR\SiteCore\CaseStudies;

defined( 'ABSPATH' ) || exit;

/**
 * Case-study keys. Constants only: registration is AJR Core's.
 */
class PostType {

	/**
	 * AJR Core's case-study post type.
	 */
	public const POST_TYPE = 'ajr_case_study';

	/**
	 * AJR Core's case-study tag taxonomy.
	 */
	public const TAXONOMY = 'case_study_tag';
}
