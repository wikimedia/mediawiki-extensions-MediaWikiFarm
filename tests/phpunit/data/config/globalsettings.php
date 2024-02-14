<?php

return [
	'wgActionPaths' => [
		'testfarm' => [
			'edit' => '/edit/$1',
		],
	],
	'wgSkipSkins' => [
		'+testfarm' => [
			'Chick',
		],
	],
];
