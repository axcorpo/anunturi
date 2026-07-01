<?php

namespace common\widgets\jsmaps;

use yii\web\AssetBundle;

class JsMapsAsset extends AssetBundle
{
	/**
	 * @inheritdoc
	 */
	public $css = [
		'css/jsmaps.css',
	];

	/**
	 * @inheritdoc
	 */
	public $js = [
		'js/jsmaps-libs.js',
		'js/jsmaps-panzoom.js',
		'js/jsmaps.min.js',
	];

	/**
	 * @inheritdoc
	 */
	public $depends = [
		'yii\web\JqueryAsset',
	];

	/**
	 * @inheritdoc
	 */
	public function init()
	{
		parent::init();

		$this->sourcePath = __DIR__ . '/assets';

		if (YII_ENV_DEV) {
			$this->publishOptions['forceCopy'] = true;
		}
	}
}
