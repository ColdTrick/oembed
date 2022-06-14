<?php

return [
	'plugin' => [
		'name' => 'oEmbed',
		'version' => '3.0.2',
	],
	'hooks' => [
		'view_vars' => [
			'output/longtext' => [
				'\ColdTrick\OEmbed\Longtext::process' => ['priority' => 9999],
			],
		],
	],
];
