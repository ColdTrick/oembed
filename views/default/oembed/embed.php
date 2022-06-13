<?php
/**
 * Generate the embed code based on a provided adapter
 *
 * @uses $vars['url']    the original URL which will be embedded
 * @uses $vars['oembed'] the oembed information
 */

$oembed = elgg_extract('oembed', $vars);
if (empty($oembed)) {
	return;
}

$type = elgg_extract('type', $oembed, 'default');
if (elgg_view_exists("oembed/type/{$type}")) {
	echo elgg_view("oembed/type/{$type}", $vars);
	return;
}

echo elgg_view('oembed/type/default', $vars);
