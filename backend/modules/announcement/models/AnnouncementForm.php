<?php

namespace backend\modules\announcement\models;

use common\helpers\ModelHelper;
use common\helpers\UploadHelper;
use common\models\Action;
use common\models\Announcement;
use common\models\AnnouncementHasAction;
use common\models\AnnouncementTranslation;
use common\models\Category;
use common\models\Field;
use common\models\FieldValue;
use common\models\Picture;
use common\models\User;
use tws\helpers\StringHelper;
use Yii;
use yii\base\InvalidCallException;
use yii\base\Model;
use yii\helpers\ArrayHelper;
use yii\helpers\FileHelper;
use common\helpers\Inflector;
use yii\web\UploadedFile;

class AnnouncementForm extends Announcement
{
	// i18n attributes
	public $title;
	public $keywords;
	public $description;
	public $content;

	/**
	 * @var array The Category model IDs.
	 */
	public $category;

    /**
     * @var UploadedFile The imageFile.
     */
    public $imageFile;

    public $imageSourceFile;

	public $picture;

    /**
     * @var array The Action model IDs.
     */
	public $action = [];
    /**

	/**
	 * @inheritdoc
	 */
	public function init()
	{
		parent::init();

		$this->status = static::STATUS_ACTIVE;
		$this->type = 1;
	}

    /**
     * @inheritdoc
     */
    public function attributes()
    {
        $attributes = parent::attributes();
        foreach (Field::findAllFields() as $field) {
            $attributes[] = Inflector::slug($field->name . '_' . $field->id, '_');
        }
        return $attributes;
    }

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return ArrayHelper::merge(parent::rules(), [
            [['start_at', 'end_at'], 'safe'],
            [['title'], 'required', 'when' => function ($model) {
                return empty($model->name[Yii::$app->language]);
            }, 'whenClient' => 'function (attribute, value) {
			return attribute.$form.find("[name*=\"[title][' . Yii::$app->language . ']\"]").val() == "";
			}'],
            [['content'], 'required', 'when' => function ($model) {
                return empty($model->content[Yii::$app->language]);
            }, 'whenClient' => 'function (attribute, value) {
			return attribute.$form.find("[name*=\"[content][' . Yii::$app->language . ']\"]").val() == "";
			}'],
            [['content'], 'string', 'length' => [80, 9000], 'strict' => false, 'when' => function ($model) {
                return empty($model->content[Yii::$app->language]);
            }, 'whenClient' => 'function (attribute, value) {
			return attribute.$form.find("[name*=\"[content][' . Yii::$app->language . ']\"]").val() != "";
			}'],
            [['title', 'content'], 'each', 'rule' => ['trim']],
            [['category', 'action', 'locality', 'county'], 'required'],
            [['category'], 'exist', 'skipOnError' => true, 'targetClass' => Category::class, 'targetAttribute' => ['category' => 'id']],
//            [['action'], 'exist', 'skipOnError' => true, 'targetClass' => Action::class, 'targetAttribute' => ['action' => 'id']],
            [['imageFile', 'imageSourceFile'], 'file', 'extensions' => Yii::$app->params['image.extensions'], 'mimeTypes' => Yii::$app->params['image.mimeTypes'], 'maxSize' => Yii::$app->settings->get('maxFileSize'), 'maxFiles' => 1, 'skipOnEmpty' => true],
            [['picture'], 'file', 'extensions' => Yii::$app->params['image.extensions'], 'mimeTypes' => Yii::$app->params['image.mimeTypes'], 'maxSize' => 20 * 1024 * 1024, 'maxFiles' => 3, 'skipOnEmpty' => true],
		]);
	}

	/**
	 * @inheritdoc
	 */
	public function attributeLabels()
	{
		return ArrayHelper::merge(parent::attributeLabels(), [
            'imageFile' => Yii::t('label', 'Image'),
            'imageSourceFile' => Yii::t('label', 'Source Image'),
            'picture' => Yii::t('label', 'Pictures'),
			'title' => Yii::t('common', 'Title'),
			'keywords' => Yii::t('common', 'Keywords'),
			'description' => Yii::t('common', 'Description'),
			'content' => Yii::t('common', 'Content'),
			'parent_id' => Yii::t('common', 'Parent'),
			'category' => Yii::t('label', 'Category'),
            'action' => Yii::t('label', 'Actions'),
			'company_id' => Yii::t('label', 'Company'),
		]);
	}

	/**
	 * @inheritdoc
	 */
	public function scenarios()
	{
		return Model::scenarios();
	}

	/**
	 * @inheritdoc
	 */
	public function afterFind()
	{
		parent::afterFind();
		$this->title = ArrayHelper::map($this->announcementTranslations, 'language_id', 'title');
		$this->keywords = ArrayHelper::map($this->announcementTranslations, 'language_id', function ($item) {
			return explode(',', $item->keywords);
		});
		$this->description = ArrayHelper::map($this->announcementTranslations, 'language_id', 'description');
		$this->content = ArrayHelper::map($this->announcementTranslations, 'language_id', 'content');
		$this->category = ArrayHelper::getColumn($this->categories, 'id');
		$this->action = ArrayHelper::getColumn($this->getAnnouncementHasActions()->select(['action_id'])->orderBy(['sort_order' => SORT_ASC])->all(), 'action_id');
        $fieldValues = FieldValue::find()
            ->where([
                'status' => FieldValue::STATUS_ACTIVE,
                'deleted' => FieldValue::NO,
            ])
            ->andWhere([
                'announcement_id' => $this->id,
            ])
            ->all();
        foreach ($fieldValues as $fieldValue) {
            if (in_array($fieldValue->field->type, [Field::TYPE_CHECKBOX, Field::TYPE_RADIO, Field::TYPE_SELECT, Field::TYPE_MULTIPLE_SELECT])) {
                $values[Inflector::slug($fieldValue->field->name . '_' . $fieldValue->field_id, '_')][] = $fieldValue->value;
            } elseif ($fieldValue->field->type == Field::TYPE_DATE) {
                $values[Inflector::slug($fieldValue->field->name . '_' . $fieldValue->field_id, '_')] = Yii::$app->formatter->asDate($fieldValue->value);
            } elseif ($fieldValue->field->type == Field::TYPE_DATETIME) {
                $values[Inflector::slug($fieldValue->field->name . '_' . $fieldValue->field_id, '_')] = Yii::$app->formatter->asDatetime($fieldValue->value);
            } else {
                $values[Inflector::slug($fieldValue->field->name . '_' . $fieldValue->field_id, '_')] = $fieldValue->value;
            }
        }
        if ($values) {
            foreach ($values as $key => $value) {
                $this->setAttribute($key, $value);
            }
        }
    }

	/**
	 * Saves the translations.
	 *
	 * @return bool
	 */
	protected function saveAnnouncementTranslations()
	{
		try {
			foreach (\common\models\Language::findAllLanguages() as $language) {
				$announcementTranslation = AnnouncementTranslation::findOne([
					'announcement_id' => $this->id,
					'language_id' => $language->language_id,
				]);
				if (!$announcementTranslation) {
					$announcementTranslation = new AnnouncementTranslation();
					$announcementTranslation->language_id = $language->language_id;
				}
				$announcementTranslation->title = ModelHelper::getTranslation($this->title, $language->language_id);
                $announcementTranslation->slug = Inflector::slug($announcementTranslation->title . '-' . $this->code);
				$announcementTranslation->keywords = implode(',', (array) $this->keywords[$language->language_id]);
				$announcementTranslation->description = $this->description[$language->language_id];
				$announcementTranslation->content = $this->content[$language->language_id];

				$this->link('announcementTranslations', $announcementTranslation);
			}

			return true;
		} catch (InvalidCallException $e) {
			$this->addError('', $e->getMessage());

			return false;
		}
	}

    /**
     * Links the Category models.
     *
     * @return bool
     * @throws \Throwable
     */
	protected function linkCategories()
	{
		try {
			$this->unlinkAll('categories', true);
			if (empty($this->category)) {
				return true;
			}
			$ids = Category::getTree($this->category);
			if (empty($ids)) {
				return true;
			}
			$categories = Category::findAll([
				'id' => $ids,
				'status' => Category::STATUS_ACTIVE,
				'deleted' => Category::NO,
			]);
			foreach ($categories as $category) {
				$this->link('categories', $category);
			}
			return true;
		} catch (InvalidCallException $e) {
			$this->addError('', $e->getMessage());
			return false;
		}
	}

    /**
     * Links the Action models.
     *
     * @return bool
     */
    protected function linkActions()
    {
        try {
            if (empty($this->action)) {
                return true;
            }
            $actions = Action::find()->where([
                'id' => $this->action,
                'status' => Action::STATUS_ACTIVE,
                'deleted' => Action::NO,
            ])->orderBy([new \yii\db\Expression('FIELD (id, ' . implode(',', $this->action) . ')')])->all();
            $announcements = Announcement::findAll([
                'id' => $this->id,
                'deleted' => Announcement::NO,
            ]);

            foreach ($announcements as $announcement) {
                $announcement->unlinkAll('actions', true);
                foreach ($actions as $action) {
                    $announcementAction = new AnnouncementHasAction();
                    $announcementAction->announcement_id = $announcement->id;
                    $announcementAction->action_id = $action->id;
                    if (!$announcementAction->save()) {
                        throw new \Exception();
                    }
                }
            }
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * {@inheritdoc}
     */
    public function load($data, $formName = null)
    {
        $result = parent::load($data, $formName);
        if (!empty($data)) {
            $this->imageFile = UploadedFile::getInstance($this, 'imageFile');
            $this->imageSourceFile = UploadedFile::getInstance($this, 'imageSourceFile');
            $this->picture = UploadedFile::getInstances($this, 'picture');
        }
        return $result;
    }

    /**
     * Saves the files.
     *
     * @return bool
     */
    protected function saveFiles()
    {
        try {
            if (!($file = $this->imageFile)) {
                return true;
            }
            $dirPath = Yii::getAlias("@uploads/announcement/{$this->id}");
            $oldFilePath = "{$dirPath}/{$this->oldAttributes['image']}";
            $fileName = StringHelper::truncate(implode('_', array_filter([
                    Inflector::slug($this->translation->title),
                    Yii::$app->security->generateRandomString(8),
                ])), 255 - (mb_strlen($file->extension) + 1), '') . ".{$file->extension}";
            $filePath = "{$dirPath}/{$fileName}";

            FileHelper::createDirectory($dirPath);
            if (!$file->saveAs($filePath)) {
                throw new \Exception();
            }
            if (!$this->updateAttributes(['image' => $fileName])) {
                throw new \Exception();
            }
            if (is_file($oldFilePath) && $oldFilePath != $filePath) {
                FileHelper::unlink($oldFilePath);
            }
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

    /**
     * Saves the files.
     *
     * @return bool
     */
    protected function saveSourceFiles()
    {
        try {
            if (!($file = $this->imageSourceFile)) {
                return true;
            }
            $dirPath = Yii::getAlias("@uploads/announcement/{$this->id}");
            $oldFilePath = "{$dirPath}/{$this->oldAttributes['source_image']}";
            $fileName = StringHelper::truncate(implode('_', array_filter([
                    Inflector::slug($this->source),
                    Yii::$app->security->generateRandomString(8),
                ])), 255 - (mb_strlen($file->extension) + 1), '') . ".{$file->extension}";
            $filePath = "{$dirPath}/{$fileName}";

            FileHelper::createDirectory($dirPath);
            if (!$file->saveAs($filePath)) {
                throw new \Exception();
            }
            if (!$this->updateAttributes(['source_image' => $fileName])) {
                throw new \Exception();
            }
            if (is_file($oldFilePath) && $oldFilePath != $filePath) {
                FileHelper::unlink($oldFilePath);
            }
            return true;
        } catch (\Exception $e) {
            return false;
        }
    }

	/**
	 * Saves the Picture files.
	 *
	 * @return bool
	 * @throws \yii\base\Exception
	 */
	protected function savePictures()
	{
        if (!($files = $this->picture)) {
            return true;
        }
		try {
			// Save the new files
			foreach ($files as $file) {
                $fileName = UploadHelper::saveFile($file, $this->translation->title, "@uploads/announcement/{$this->id}", Yii::$app->security->generateRandomString(8));
				if ($fileName) {
					$picture = new Picture();
					$picture->image = $fileName;
					$picture->type = 2;
					$picture->status = static::STATUS_ACTIVE;
					$picture->deleted = static::NO;
					if (!$picture->save()) {
						throw new \Exception();
					} else {
						$this->link('pictures', $picture);
					}
				}
			}
			return true;
		} catch (\Exception $e) {
			return false;
		}
	}

	public function saveFieldValues()
	{
		try {
            FieldValue::deleteAll([
                '=', 'announcement_id', $this->id,
            ]);
			foreach (Field::findAllByCategoryId($this->category) as $field) {
				$attribute = Inflector::slug($field->name . '_' . $field->id, '_');
				if (in_array($attribute, array_keys(array_filter(Yii::$app->request->post()['AnnouncementForm'])))) {
					$fieldValue = Yii::$app->request->post()['AnnouncementForm'][$attribute];
					if (is_array($fieldValue)) {
						foreach ($fieldValue as $value) {
							$model = FieldValue::find()
								->where([
									'announcement_id' => $this->id,
									'field_id' => $field->id,
									'value' => $value,
								])
								->one();
							if (!$model) {
								$model = new FieldValue();
								$model->announcement_id = $this->id;
								$model->field_id = $field->id;
								$model->value = $value;
							}
							$model->status = static::STATUS_ACTIVE;
							$model->deleted = static::NO;
							if (!$model->save()) {
								throw new \Exception();
							}
						}
					} else {
						$model = FieldValue::find()
							->where([
								'announcement_id' => $this->id,
								'field_id' => $field->id,
								'value' => $fieldValue,
							])
							->one();
						if (!$model) {
							$model = new FieldValue();
							$model->announcement_id = $this->id;
							$model->field_id = $field->id;
							$model->value = $fieldValue;
						}
						$model->status = static::STATUS_ACTIVE;
						$model->deleted = static::NO;
						if (!$model->save()) {
							throw new \Exception();
						}
					}
				}
			}
			return true;
		} catch (\Exception $e) {
			return false;
		}
	}

    /**
     * Saves the model.
     *
     * @return bool
     * @throws \yii\db\Exception
     * @throws \Throwable
     */
	public function saveModel()
	{
		$transaction = static::getDb()->beginTransaction();
		try {
			$this->county = str_replace('Județul ', '', $this->county);
			unset($this->image);
            foreach (Field::findAllFields() as $field) {
                $attribute = Inflector::slug($field->name . '_' . $field->id, '_');
                unset($this->$attribute);
            }
			if (!$this->code) {
				$this->code = static::generateUniqueCode();
			}
			if (!$this->save()) {
				throw new \Exception();
			}
			if (!$this->saveAnnouncementTranslations()) {
				throw new \Exception();
			}
			if (!$this->linkCategories()) {
				throw new \Exception();
			}
            if (!$this->linkActions()) {
                throw new \Exception();
            }
			if (!$this->saveFiles()) {
				throw new \Exception();
			}
            if (!$this->saveSourceFiles()) {
                throw new \Exception();
            }
			if (!$this->savePictures()) {
				throw new \Exception();
			}
            if (!$this->saveFieldValues()) {
                throw new \Exception();
            }
			$transaction->commit();
			return true;
		} catch(\Exception $e) {
			$transaction->rollBack();

			return false;
		}
	}
}
