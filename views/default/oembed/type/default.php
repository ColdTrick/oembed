<?php
/**
 * This is a fallback view and will try to generate embed code
 *
 * @uses $vars['url']     the original URL which will be embedded
 * @uses $vars['adapter'] the Embed\Adapters\Adapter to get information from
 */

use Embed\Adapters\Adapter;

$adapter = elgg_extract('adapter', $vars);
if (!($adapter instanceof Adapter)) {
	return;
}

echo $adapter->getCode();
