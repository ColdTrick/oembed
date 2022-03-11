<?php

return [
	'plugin' => [
		'version' => '3.0.1',
	],
	'hooks' => [
		'view_vars' => [
			'output/longtext' => [
				'\ColdTrick\OEmbed\Longtext::process' => ['priority' => 9999],
			],
		],
	],
];
