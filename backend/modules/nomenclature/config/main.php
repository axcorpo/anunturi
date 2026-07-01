<?php
return [
	'aliases' => [
		'@nomenclature' => dirname(__DIR__),
	],
	'components' => [
		'urlManager' => [
			'class' => 'yii\web\UrlManager',
			'rules' => [
				[
					'class' => 'yii\web\GroupUrlRule',
					'prefix' => '<module>/product',
					'routePrefix' => '<module>',
					'rules' => [
						[
							'pattern' => '<product_id:(\d+|__\w+__)>/<controller>/<id:(\d+|__\w+__)>/<action>',
							'route' => '<controller>/<action>',
						],
						[
							'pattern' => '<product_id:(\d+|__\w+__)>/<controller>/<action>',
							'route' => '<controller>/<action>',
						],
					],
				],
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
