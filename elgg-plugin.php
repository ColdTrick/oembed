<?php

return [
	'plugin' => [
		'name' => 'oEmbed',
		'version' => '4.0',
	],
	'hooks' => [
		'view_vars' => [
			'output/longtext' => [
				'\ColdTrick\OEmbed\Longtext::process' => ['priority' => 9999],
			],
		],
	],
];
