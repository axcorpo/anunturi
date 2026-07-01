<?php
return [
	'aliases' => [
		'@announcement' => dirname(__DIR__),
	],
	'components' => [
		'urlManager' => [
			'class' => 'yii\web\UrlManager',
			'rules' => [
				// Specific routes
				[
					'class' => 'yii\web\GroupUrlRule',
					'prefix' => '<module>/category',
					'routePrefix' => '<module>',
					'rules' => [
						[
							'pattern' => '<category_id:(\d+|__\w+__)>/<controller>/<id:(\d+|__\w+__)>/<action>',
							'route' => '<controller>/<action>',
						],
						[
							'pattern' => '<category_id:(\d+|__\w+__)>/<controller>/<action>',
							'route' => '<controller>/<action>',
						],
					],
				],
				[
					'class' => 'yii\web\GroupUrlRule',
					'prefix' => '<module>/field',
					'routePrefix' => '<module>',
					'rules' => [
						[
							'pattern' => '<field_id:(\d+|__\w+__)>/<controller>/<id:(\d+|__\w+__)>/<action>',
							'route' => '<controller>/<action>',
						],
						[
							'pattern' => '<field_id:(\d+|__\w+__)>/<controller>/<action>',
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
