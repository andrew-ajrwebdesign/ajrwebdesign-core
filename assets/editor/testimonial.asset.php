<?php
/**
 * Dependencies and version for the testimonial editor panel (assets/editor/testimonial.js).
 *
 * Hand-written: this panel has no build step. Read by Testimonials\Testimonials::enqueue_panel().
 * wp-i18n must stay listed or the panel's strings can never be translated.
 *
 * @package AJR\SiteCore
 */

return [
	'dependencies' => [
		'wp-plugins',
		'wp-editor',
		'wp-element',
		'wp-data',
		'wp-core-data',
		'wp-block-editor',
		'wp-components',
		'wp-i18n',
	],
	'version'      => defined( 'AJRWD_CORE_VERSION' ) ? AJRWD_CORE_VERSION : false,
];
