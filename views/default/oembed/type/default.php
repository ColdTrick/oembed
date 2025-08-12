<?php
/**
 * This is a fallback view and will try to generate embed code
 *
 * @uses $vars['url']    the original URL which will be embedded
 * @uses $vars['oembed'] the oembed information
 */

$oembed = elgg_extract('oembed', $vars);
if (empty($oembed)) {
	return;
}

if (empty($oembed['html'])) {
	return;
}

$default_height = (int) elgg_get_plugin_setting('default_height', 'oembed');
$new_height = (int) elgg_extract('oembed_height', $vars, $default_height);

// change embed width to 100%
$adjust_width = function($match) use ($new_height) {
	if (!isset($match[1])) {
		return $match[0];
	}
	
	$width = 'width="100%"';
	
	if ($new_height) {
		// assume dimension of 16x9
		$width .= ' style="max-width: ' . round(($new_height / 9) * 16) . 'px"';
	}
	
	return $width;
};

// adjust embed height to plugin setting (if any)
$adjust_height = function($match) use ($new_height) {
	if (!isset($match[1])) {
		return $match[0];
	}

	return "height=\"{$new_height}\"";
};

$code = preg_replace_callback('/width=[\"\'](\d+\w*)[\"\']/', $adjust_width, $oembed['html']);
$code = preg_replace_callback('/height=[\"\'](\d+\w*)[\"\']/', $adjust_height, $code);

echo $code;
