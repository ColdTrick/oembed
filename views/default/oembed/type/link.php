<?php
/**
 * Handle 'link' type embed code
 *
 * @uses $vars['url']    the original URL which will be embedded
 * @uses $vars['oembed'] the oembed information
 */

$oembed = elgg_extract('oembed', $vars);
if (empty($oembed)) {
	return;
}

// don't replace anything
echo '';
