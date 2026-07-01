<?php

namespace common\widgets\jsmaps;

use Yii;
use yii\base\InvalidConfigException;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\web\JsExpression;
use yii\web\View;

/**
 * Class JsMaps
 *
 * @package common\widgets\jsmaps
 * @author Tree Web Solutions Team <treewebsolutions.com@gmail.com>
 */
class JsMaps extends \yii\base\Widget
{
	/**
	 * @var array The maps configuration array.
	 */
	public $maps = [];

	/**
	 * @var array The jsmaps options.
	 */
	public $options = [];

	/**
	 * @var array The client (JS) options.
	 */
	public $clientOptions = [];

	/**
	 * @var array The client (JS) events.
	 */
	public $clientEvents = [];

	/**
	 * @var string The client (JS) selector.
	 */
	private $_clientSelector;

	/**
	 * @var string The global widget JS hash variable.
	 */
	private $_hashVar;

	/**
	 * @inheritdoc
	 * @throws InvalidConfigException
	 */
	public function init()
	{
		parent::init();

		if (isset($this->maps) && (!is_array($this->maps) || empty(reset($this->maps)))) {
			throw new InvalidConfigException('The "maps" property must contain at least one key => value non-empty item.');
		}

		$this->setupProperties();
		$this->registerAssets();
	}

	/**
	 * @inheritdoc
	 */
	public function run()
	{
		return Html::tag('div', null, $this->options);
	}

	/**
	 * Gets the client selector.
	 *
	 * @return string
	 */
	public function getClientSelector()
	{
		if (!$this->_clientSelector) {
			$this->_clientSelector = '#' . $this->getId();
		}
		return $this->_clientSelector;
	}

	/**
	 * Gets the hash variable.
	 *
	 * @return string
	 */
	public function getHashVar()
	{
		if (!$this->_hashVar) {
			$this->_hashVar = 'jsmaps_' . hash('crc32', $this->buildClientOptions());
		}
		return $this->_hashVar;
	}

	/**
	 * Sets the widget properties.
	 */
	protected function setupProperties()
	{
		$this->options = array_merge([
			'id' => $this->getId(),
			'class' => 'jsmaps-wrapper',
			'data' => [
				'jsmaps-options' => $this->getHashVar(),
			],
		], $this->options);
		Html::addCssClass($this->options, 'jsmaps-wrapper');
		$this->setId($this->options['id']);
		$this->options['data']['jsmaps-options'] = $this->getHashVar();
	}

	/**
	 * Builds the client options.
	 *
	 * @return string
	 */
	protected function buildClientOptions()
	{
		$this->clientOptions = array_merge([
			'map' => key($this->maps),
		], $this->clientOptions);

		return Json::encode($this->clientOptions);
	}

	/**
	 * Registers the widget assets.
	 */
	protected function registerAssets()
	{
		$view = $this->getView();

		// Register assets
		JsMapsAsset::register($view);

		// Register JavaScript plugin config
		foreach ($this->maps as $mapKey => $mapValue) {
			if (!empty($mapKey) && !empty($mapValue)) {
				$mapValue = Json::encode($mapValue);
				$view->registerJs("window.JSMaps.maps['{$mapKey}'] = {$mapValue};");
			}
		}
		// Register widget hash JavaScript variable
		$view->registerJs("var {$this->getHashVar()} = {$this->buildClientOptions()};", View::POS_HEAD);

		// Build client script
		$js = "jQuery('{$this->getClientSelector()}').JSMaps({$this->getHashVar()})";

		// Build client events
		if (!empty($this->clientEvents)) {
			foreach ($this->clientEvents as $clientEvent => $eventHandler) {
				if (!($eventHandler instanceof JsExpression)) {
					$eventHandler = new JsExpression($eventHandler);
				}
				$js .= ".on('{$clientEvent}', {$eventHandler})";
			}
		}

		// Register widget JavaScript
		$view->registerJs("{$js};");
	}
}