<?php

namespace common\validators;

use Yii;
use yii\validators\Validator;

class StripTagsFilter extends Validator
{
	/**
	 * @var array A list of HTML tags that are allowed.
	 */
	public $allowedHtmlTags = [];

	/**
	 * @var bool Flag that indicates if the HTML attributes should be removed also.
	 */
	public $stripHtmlAttributes = true;

	/**
	 * @var array A list of HTML attributes that are allowed.
	 */
	public $allowedHtmlAttributes = [];


	/**
	 * {@inheritdoc}
	 */
	public function __construct($config = [])
	{
		parent::__construct($config);

		if (empty($this->allowedHtmlTags)) {
			$this->allowedHtmlTags = Yii::$app->params['sanitize.allowedHtmlTags'];
		}
		if (empty($this->allowedHtmlAttributes)) {
			$this->allowedHtmlAttributes = Yii::$app->params['sanitize.allowedHtmlAttributes'];
		}
	}

	/**
	 * {@inheritdoc}
	 */
	public function validateAttribute($model, $attribute)
	{
		$value = $model->$attribute;

		if (is_array($value)) {
			foreach ($value as $key => $val) {
				$value[$key] = $this->stripTagsAndAttributes($val);
			}
		} else {
			$value = $this->stripTagsAndAttributes($value);
		}

		$model->$attribute = $value;
	}

	/**
	 * Strip the HTML tags and attributes.
	 *
	 * @param string $value
	 * @return string|false
	 */
	public function stripTagsAndAttributes($value)
	{
		$value = strip_tags($value, $this->allowedHtmlTags);

		if (!$this->stripHtmlAttributes) {
			return $value;
		}

		$document = new \DOMDocument('1.0', 'UTF-8');
		$document->loadHTML("<div>{$value}</div>", LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD);

		// Remove the attributes
		foreach($document->getElementsByTagName('*') as $node) {
			for ($i = $node->attributes->length -1; $i >= 0; $i--) {
				$attribute = $node->attributes->item($i);

				// Handle wildcard attributes
				$wildcardAttribute = substr($attribute->name, 0, 5);

				if (
					!in_array($attribute->name, $this->allowedHtmlAttributes) &&
					!in_array($wildcardAttribute, $this->allowedHtmlAttributes)
				) {
					$node->removeAttributeNode($attribute);
				}
			}
		}

		// Get the inner HTML of the container
		$container = $document->getElementsByTagName('div')->item(0);
		if ($container instanceof \DOMNode) {
			$container = $container->parentNode->removeChild($container);
			while ($document->firstChild) {
				$document->removeChild($document->firstChild);
			}
			while ($container->firstChild) {
				$document->appendChild($container->firstChild);
			}
		}

		return trim($document->saveHTML());
	}
}
