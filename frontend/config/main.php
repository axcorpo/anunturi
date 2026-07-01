<?php
$params = array_merge(
	require __DIR__ . '/../../common/config/params.php',
	require __DIR__ . '/../../common/config/params-local.php',
	require __DIR__ . '/params.php',
	require __DIR__ . '/params-local.php'
);

return [
	'id' => 'app-frontend',
	'name' => 'eConstrucți Website',
	'basePath' => dirname(__DIR__),
	'controllerNamespace' => 'frontend\controllers',
	'bootstrap' => [
		'log',
		'frontend\components\ApplicationBootstrap',
		'account',
		'announcement',
		'firm',
		'individual',
	],
	'modules' => [
		'account' => [
			'class' => 'frontend\modules\account\Module',
		],
		'announcement' => [
			'class' => 'frontend\modules\announcement\Module',
		],
        'firm' => [
            'class' => 'frontend\modules\firm\Module',
        ],
        'individual' => [
            'class' => 'frontend\modules\individual\Module',
        ],
	],
	'components' => [
		'request' => [
			'baseUrl' => '',
			'csrfParam' => '_csrf',
			'enableCsrfValidation' => false,
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
			'loginUrl' => ['/site/login'],
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
			'baseUrl' => '',
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
					'pattern' => '',
					'route' => '/site/index',
				],
				[
					'pattern' => '/rss',
					'route' => '/site/rss',
				],
				[
					'pattern' => '/sitemap',
					'route' => '/site/sitemap',
				],
				[
					'pattern' => '<action:(logout|search|auth|accept-cookies|ipn|assume-identity)>',
					'route' => '/site/<action>',
				],
			],
		],
        'authClientCollection' => [
            'class' => 'yii\authclient\Collection',
            'clients' => [
                'google' => [
                    'class' => 'yii\authclient\clients\Google',
                    'clientId' => '335495482763-arnqdrvu8mp3is909i5jadpq1og609ps.apps.googleusercontent.com',
                    'clientSecret' => 'YhWsKQzgUuASuOE80n0D4ViJ',
                ],
//                'facebook' => [
//                    'class' => 'yii\authclient\clients\Facebook',
//                    'clientId' => '4369769799790789',
//                    'clientSecret' => '25c41a1741ea23d6f0a24125030cdd22',
//                    'attributeNames' => ['name', 'first_name', 'last_name', 'email', 'gender'],
//                ],
            ],
        ],
	],
	'params' => $params,
];
