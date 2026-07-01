<?php
$params = array_merge(
	require __DIR__ . '/../../common/config/params.php',
	require __DIR__ . '/../../common/config/params-local.php',
	require __DIR__ . '/params.php',
	require __DIR__ . '/params-local.php'
);

return [
	'id' => 'app-backend',
	'name' => 'eConstrucți Administration',
	'basePath' => dirname(__DIR__),
	'controllerNamespace' => 'backend\controllers',
	'bootstrap' => [
		'log',
		'backend\components\ApplicationBootstrap',
		'setting-manager',
		'export-manager',
		'user-manager',
		'backup-manager',
		'eventlog-manager',
		'website-manager',
		'nomenclature-manager',
		'commercial-manager',
		'announcement-manager',
		'subscriber-manager',
		'backup-manager',
        'marketing-manager',
        'payment-manager',
        'import-manager',
        'export-manager',
	],
	'modules' => [
		'setting-manager' => [
			'class' => 'backend\modules\setting\Module',
		],
		'user-manager' => [
			'class' => 'backend\modules\user\Module',
		],
		'backup-manager' => [
			'class' => 'backend\modules\backup\Module',
		],
		'eventlog-manager' => [
			'class' => 'backend\modules\eventlog\Module',
		],
		'website-manager' => [
			'class' => 'backend\modules\website\Module',
		],
		'nomenclature-manager' => [
			'class' => 'backend\modules\nomenclature\Module',
		],
		'commercial-manager' => [
			'class' => 'backend\modules\commercial\Module',
		],
		'announcement-manager' => [
			'class' => 'backend\modules\announcement\Module',
		],
		'subscriber-manager' => [
			'class' => 'backend\modules\subscriber\Module',
		],
        'marketing-manager' => [
            'class' => 'backend\modules\marketing\Module',
        ],
		'payment-manager' => [
			'class' => 'backend\modules\payment\Module',
		],
        'import-manager' => [
            'class' => 'backend\modules\import\Module',
        ],
        'export-manager' => [
            'class' => 'backend\modules\export\Module',
        ],
	],
	'components' => [
		'request' => [
			'baseUrl' => '/admin',
			'csrfParam' => '_csrf',
			'enableCsrfValidation' => true,
			'enableCookieValidation' => true,
			'enableCsrfCookie' => false,
			'csrfCookie' => [
				'path' => '/',
				'httpOnly' => true,
			],
		],
		'user' => [
			'identityClass' => 'common\models\User',
			'enableAutoLogin' => true,
			'identityCookie' => [
				'path' => '/',
				'name' => '_identity',
				'httpOnly' => true,
			],
			'loginUrl' => '/',
		],
		'session' => [
			'name' => 'PRINTSESSID',
			'useCookies' => true,
			'cookieParams' => [
				'path' => '/',
				'httpOnly' => true,
			],
		],
		'log' => [
			'traceLevel' => YII_DEBUG ? 3 : 0,
			'targets' => [
				[
					'class' => 'yii\log\FileTarget',
					'levels' => ['error', 'warning'],
				],
			],
		],
		'errorHandler' => [
			'errorAction' => 'site/error',
		],
		'urlManager' => [
			'class' => 'codemix\localeurls\UrlManager',
			'baseUrl' => '/admin',
			'languages' => ['en' => 'en-US'],
			'enableLanguageDetection' => false,
			'enableDefaultLanguageUrlCode' => false,
			'enableLanguagePersistence' => false,
			'enableStrictParsing' => true,
			'enablePrettyUrl' => true,
			'showScriptName' => false,
			'normalizer' => [
				'class' => 'yii\web\UrlNormalizer',
				'action' => yii\web\UrlNormalizer::ACTION_REDIRECT_PERMANENT,
			],
			'rules' => [
				[
					'pattern' => '/',
					'route' => '/site/index',
				],
				[
					'pattern' => '/profile',
					'route' => '/profile/index',
				],
				[
					'pattern' => '/<action:(login|logout|reset-password|search)>',
					'route' => '/site/<action>',
				],
				[
					'pattern' => '/<controller>/<id:(\d+|__\w+__)>/<action>',
					'route' => '/<controller>/<action>',
				],
				[
					'pattern' => '/<controller>/<action>',
					'route' => '/<controller>/<action>',
				],
			],
		],
		'eventLog' => [
			'class' => 'backend\modules\eventlog\components\EventLog',
			'model' => 'common\models\EventLog',
		],
		'translate' => [
			'class' => 'richweber\google\translate\Translation',
			'key' => 'AIzaSyD3ved624zAP6SSUf_VYEQoS5-vT9n3kfU',
		],
	],
	'params' => $params,
];
