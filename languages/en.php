<?php

return [
	
	// plugin settings
	'oembed:settings:whitelist' => "Whitelisted domains for oEmbed support",
	'oembed:settings:whitelist:help' => "Enter a comma separated list of domains for which oEmbed is supported. Only for these domains will oEmbed be processed. If left empty processing will take place on all URLs. For example the domain for '%s' is '%s'.",
	'oembed:settings:blacklist' => "Blacklisted domains for oEmbed support",
	'oembed:settings:blacklist:help' => "Enter a comma separated list of domains for which oEmbed will not be supported. For the domains in this list oEmbed will not be processed. For example the domain for '%s' is '%s'.",
	'oembed:settings:default_height' => "Height for embedded content",
	'oembed:settings:default_height:help' => "Configure the height for embedded content.",
];
