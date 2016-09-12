<?php

return array(
	'wgActionPaths' => array(
		'testfarm' => array(
			'edit' => '/edit/$1',
		),
	),
	'wgSkipSkins' => array(
		'+testfarm' => array(
			'Chick',
		),
	),
);
