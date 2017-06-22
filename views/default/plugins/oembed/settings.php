<?php

/* @var $plugin \Elggplugin */
$plugin = elgg_extract('entity', $vars);

$site_url = elgg_get_site_url();
$url = Embed\Http\Url::create($site_url);
$domain = $url->getDomain();

echo elgg_view_field([
	'#type' => 'plaintext',
	'#label' => elgg_echo('oembed:settings:whitelist'),
	'#help' => elgg_echo('oembed:settings:whitelist:help', [$site_url, $domain]),
	'name' => 'params[whitelist]',
	'value' => $plugin->whitelist,
	'rows' => 4,
]);

echo elgg_view_field([
	'#type' => 'plaintext',
	'#label' => elgg_echo('oembed:settings:blacklist'),
	'#help' => elgg_echo('oembed:settings:blacklist:help', [$site_url, $domain]),
	'name' => 'params[blacklist]',
	'value' => $plugin->blacklist,
	'rows' => 4,
]);

// proxy support
echo elgg_view_field([
	'#type' => 'text',
	'#label' => elgg_echo('oembed:settings:proxy_host'),
	'#help' => elgg_echo('oembed:settings:proxy_host:help'),
	'name' => 'params[proxy_host]',
	'value' => $plugin->proxy_host,
]);

echo elgg_view_field([
	'#type' => 'number',
	'#label' => elgg_echo('oembed:settings:proxy_port'),
	'#help' => elgg_echo('oembed:settings:proxy_port:help'),
	'name' => 'params[proxy_port]',
	'value' => $plugin->proxy_port,
	'min' => 0,
	'max' => 65536,
]);
