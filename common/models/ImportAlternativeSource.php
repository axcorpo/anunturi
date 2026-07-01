<?php

namespace common\models;

use Yii;

/**
 * This is the model class for table "{{%import_alternative_source}}".
 *
 * @property int $id
 * @property int $column_id
 * @property string $value
 * @property string $source
 * @property int $source_index
 * @property int $deleted
 *
 * @property ImportColumn $column
 */
class ImportAlternativeSource extends CommonActiveRecord
{
    /**
     * {@inheritdoc}
     */
    public static function tableName()
    {
        return '{{%import_alternative_source}}';
    }

    /**
     * {@inheritdoc}
     */
    public function rules()
    {
        return [
            [['column_id'], 'required'],
            [['column_id', 'source_index', 'deleted'], 'integer'],
            [['value', 'source'], 'string', 'max' => 255],
            [['column_id'], 'exist', 'skipOnError' => true, 'targetClass' => ImportColumn::class, 'targetAttribute' => ['column_id' => 'id']],
        ];
    }

    /**
     * {@inheritdoc}
     */
    public function attributeLabels()
    {
        return [
            'id' => Yii::t('label', 'ID'),
            'column_id' => Yii::t('label', 'Column ID'),
            'value' => Yii::t('label', 'Value'),
            'source' => Yii::t('label', 'Source'),
            'source_index' => Yii::t('label', 'Source Index'),
            'deleted' => Yii::t('label', 'Deleted'),
        ];
    }

    /**
     * @return \yii\db\ActiveQuery
     */
    public function getColumn()
    {
        return $this->hasOne(ImportColumn::class, ['id' => 'column_id']);
    }
}
