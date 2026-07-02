<?php

namespace frontend\modules\announcement\assets;

use yii\web\AssetBundle;

/**
 * In-page AI chat widget for refining the announcement listing search.
 */
class ChatAsset extends AssetBundle
{
	public $css = [
		'css/chat.css',
	];

	public $js = [
		'js/chat.js',
	];

	public $depends = [
		'yii\web\JqueryAsset',
	];

	public function init()
	{
		parent::init();
		$this->sourcePath = dirname(__DIR__) . '/views/default';
	}
}
