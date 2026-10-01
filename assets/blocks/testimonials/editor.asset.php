<?php
/**
 * Asset metadata for the testimonials block's editor script.
 *
 * Hand-written because the block has no build step (ported from AJR Core 0.15.2, 2026-10-01). It carries the plugin version, so a release busts the editor's
 * cache, and declares wp-i18n, without which core never loads the script's translations.
 *
 * @package AJR\SiteCore
 */

return [
	'dependencies' => [
		'wp-blocks',
		'wp-element',
		'wp-block-editor',
		'wp-components',
		'wp-data',
		'wp-core-data',
		'wp-i18n',
		'wp-server-side-render',
	],
	'version'      => defined( 'AJRWD_CORE_VERSION' ) ? AJRWD_CORE_VERSION : false,
];
