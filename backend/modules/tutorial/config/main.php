<?php
return [
	'aliases' => [
		'@tutorial' => dirname(__DIR__),
	],
	'components' => [
		'urlManager' => [
			'class' => 'yii\web\UrlManager',
			'rules' => [
				// Specific routes
				[
					'class' => 'yii\web\GroupUrlRule',
					'prefix' => '<module>/tutorial',
					'routePrefix' => '<module>',
					'rules' => [
						[
							'pattern' => '<tutorial_id:(\d+|__\w+__)>/<controller>/<id:(\d+|__\w+__)>/<action>',
							'route' => '<controller>/<action>',
						],
						[
							'pattern' => '<tutorial_id:(\d+|__\w+__)>/<controller>/<action>',
							'route' => '<controller>/<action>',
						],
					],
				],
				// Default routes
				[
					'pattern' => '/',
					'route' => 'default/index',
				],
				[
					'pattern' => '<controller>/<id:(\d+|__\w+__|\w+)>/<action>',
					'route' => '<controller>/<action>',
				],
				[
					'pattern' => '<controller>/<action>',
					'route' => '<controller>/<action>',
				],
			],
		],
	],
];