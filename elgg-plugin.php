<?php

return [
	'plugin' => [
		'version' => '2.2',
	],
	'hooks' => [
		'view_vars' => [
			'output/longtext' => [
				'\ColdTrick\OEmbed\Longtext::process' => ['priority' => 9999],
			],
		],
	],
];
