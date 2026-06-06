<?php
// PukiWiki2026 - oEmbed plugin
// oembed.inc.php
// License: GPL v2 or (at your option) any later version
//
// Embed external content via oEmbed (YouTube, Vimeo, Twitter/X, Flickr, …)

define('PLUGIN_OEMBED_USAGE', '#oembed(URL) / &oembed(URL);');

function plugin_oembed_init()
{
	require_once(LIB_DIR . 'oembed.php');
	return TRUE;
}

function plugin_oembed_inline()
{
	if (! func_num_args()) {
		return '&amp;oembed(): Usage: ' . PLUGIN_OEMBED_USAGE . ';';
	}

	$url = trim(func_get_arg(0));
	if (! is_url($url, TRUE)) {
		return '&amp;oembed(): Invalid URL: ' . htmlsc($url) . ';';
	}

	$html = pkwk_oembed_render($url);
	if ($html === FALSE) {
		return pkwk_oembed_fallback_link($url);
	}
	return $html;
}

function plugin_oembed_convert()
{
	if (! func_num_args()) {
		return '<p>#oembed(): Usage: ' . PLUGIN_OEMBED_USAGE . "</p>\n";
	}

	$url = trim(func_get_arg(0));
	if (! is_url($url, TRUE)) {
		return '<p>#oembed(): Invalid URL: ' . htmlsc($url) . "</p>\n";
	}

	$html = pkwk_oembed_render($url);
	if ($html === FALSE) {
		return '<p>' . pkwk_oembed_fallback_link($url) . "</p>\n";
	}
	return $html . "\n";
}
