<?php
/* @var $this yii\web\View */
/* @var $model common\models\Announcement */

use common\models\Feature;
use common\models\Field;
use common\models\Option;
use common\models\PackageFeature;
use common\models\FeatureModule;
use common\models\ScheduledTask;
use yii\helpers\ArrayHelper;

/** @var \common\models\FieldValue[] $fieldValues */
$fieldValues = $model->fieldValues;
$values = [];
foreach ($fieldValues as $fieldValue) {
    if (in_array($fieldValue->field->type, [Field::TYPE_CHECKBOX, Field::TYPE_RADIO, Field::TYPE_SELECT, Field::TYPE_MULTIPLE_SELECT])) {
        $option = Option::findOne([
            'field_id' => $fieldValue->field_id,
            'value' => $fieldValue->value,
            'status' => Option::STATUS_ACTIVE,
            'deleted' => Option::NO,
        ]);
        $values[$fieldValue->field->translation->label][] = $option->translation->label;
    } elseif ($fieldValue->field->type == Field::TYPE_DATE) {
        $values[$fieldValue->field->translation->label][] = Yii::$app->formatter->asDate($fieldValue->value);
    } elseif ($fieldValue->field->type == Field::TYPE_DATETIME) {
        $values[$fieldValue->field->translation->label][] = Yii::$app->formatter->asDatetime($fieldValue->value);
    } else {
        $values[$fieldValue->field->translation->label][] = $fieldValue->value;
    }
}
?>

<table class="table table-bordered table-condensed">
	<tbody>
    <?php foreach ($values as $key => $value): ?>
	<tr>
		<td class="col-autowidth"><?= $key ?></td>
        <td><?= implode(', ', $values[$key]) ?></td>
    </tr>
    <?php endforeach; ?>
	</tbody>
</table>

