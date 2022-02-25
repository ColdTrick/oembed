<?php
/**
 * Generate the embed code based on a provided adapter
 *
 * @uses $vars['url']     the original URL which will be embedded
 * @uses $vars['adapter'] the Embed\Adapters\Adapter to get information from
 */

use Embed\Adapters\Adapter;

$adapter = elgg_extract('adapter', $vars);
if (!$adapter instanceof Adapter) {
	return;
}

$adapter_type = $adapter->getType();
if (elgg_view_exists("oembed/type/{$adapter_type}")) {
	echo elgg_view("oembed/type/{$adapter_type}", $vars);
	return;
}

echo elgg_view('oembed/type/default', $vars);
