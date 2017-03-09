<?php

@include_once(dirname(__FILE__) . '/vendor/autoload.php');

// register default elgg events
elgg_register_event_handler('init', 'system', 'oembed_init');

/**
 * Init function for this plugin
 *
 * @return void
 */
function oembed_init() {
	
	// plugin hooks
	elgg_register_plugin_hook_handler('view_vars', 'output/longtext', '\ColdTrick\OEmbed\Longtext::process', 9999);
}
