<?php

return [
	'plugin' => [
		'name' => 'oEmbed',
		'version' => '5.0.3',
	],
	'settings' => [
		'default_height' => 300,
	],
	'events' => [
		'view_vars' => [
			'output/longtext' => [
				'\ColdTrick\OEmbed\Longtext::process' => ['priority' => 9999],
			],
		],
	],
];
