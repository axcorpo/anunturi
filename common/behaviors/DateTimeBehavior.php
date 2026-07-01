<?php

namespace common\behaviors;

use DateTime;
use yii\base\Behavior;
use yii\db\BaseActiveRecord;

/**
 * DateTimeBehavior automatically formats date/datetime attributes to MySQL DATETIME format.
 *
 * @author Alin Hort <alinhort@gmail.com>
 */
class DateTimeBehavior extends Behavior
{
	/**
	 * @var string The datetime format.
	 */
	public $format = 'Y-m-d H:i:s';

	/**
	 * @var null|mixed The value used when the date format is invalid.
	 */
	public $invalidValue = null;

	/**
	 * @var array The attributes that reference a datetime string.
	 */
	public $attributes = [];

	/**
	 * @inheritdoc
	 */
	public function events()
	{
		return [
			BaseActiveRecord::EVENT_BEFORE_INSERT => 'evaluateAttributes',
			BaseActiveRecord::EVENT_BEFORE_UPDATE => 'evaluateAttributes',
		];
	}

	/**
	 * Evaluates the attribute value and assigns it to the current attributes.
	 *
	 * @param \yii\base\Event $event
	 */
	public function evaluateAttributes($event)
	{
		// Loop through the provided attributes
		foreach ($this->attributes as $attribute) {
			// Continue with the next iteration if the owner does not contain the current attribute
			if (!isset($this->owner->$attribute)) {
				continue;
			}
			// Check if the attribute name is a string and does contain a value
			if (!is_string($attribute) || empty($this->owner->$attribute)) {
				// Set the attribute invalid  value
				$this->owner->$attribute = $this->invalidValue;
				// Continue with the next iteration
				continue;
			}
			// Format attribute as datetime
			$this->owner->$attribute = (new DateTime($this->owner->$attribute))->format($this->format);
		}
	}
}
