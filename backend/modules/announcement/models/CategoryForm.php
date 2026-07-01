<?php

namespace backend\modules\announcement\models;

use common\helpers\ModelHelper;
use common\helpers\UploadHelper;
use common\models\Action;
use common\models\Category;
use common\models\CategoryHasAction;
use common\models\CategoryHasField;
use common\models\CategoryTranslation;
use Yii;
use yii\base\InvalidCallException;
use yii\base\Model;
use yii\helpers\ArrayHelper;
use yii\helpers\FileHelper;
use common\helpers\Inflector;
use yii\helpers\StringHelper;
use yii\web\UploadedFile;

class CategoryForm extends Category
{
	// i18n attributes
	public $name;
	public $keywords;
	public $description;
	public $content;

    /**
     * @var UploadedFile The image file.
     */
    public $imageFile;

	/**
	 * @var array The Action model IDs.
	 */
	public $action = [];
	/**
	 * @var array A list of (sub)Category models.
	 */
	public $subcategory = [];

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
	public function rules()
	{
		return ArrayHelper::merge(parent::rules(), [
			[['name'], 'required', 'when' => function ($model) {
				return empty($model->name[Yii::$app->language]);
			}, 'whenClient' => 'function (attribute, value) {
				return attribute.$form.find("[name*=\"[name][' . Yii::$app->language . ']\"]").val() == "";
			}'],
			[['keywords', 'anchor', 'content'], 'safe'],
			[['name', 'keywords', 'description', 'content'], 'each', 'rule' => ['trim']],
			[['keywords', 'description', 'content'], 'each', 'rule' => ['default', 'value' => null]],
            [['imageFile'], 'file', 'extensions' => Yii::$app->params['image.extensions'], 'mimeTypes' => Yii::$app->params['image.mimeTypes'], 'maxSize' => Yii::$app->settings->get('maxFileSize'), 'maxFiles' => 1, 'skipOnEmpty' => true],
			[['action'], 'each', 'rule' => ['exist', 'skipOnError' => true, 'targetClass' => Action::class, 'targetAttribute' => ['action' => 'id']]],
			[['subcategory'], 'each', 'rule' => ['exist', 'skipOnError' => true, 'targetClass' => Category::class, 'targetAttribute' => ['subcategory' => 'id']]],
		]);
	}

	/**
	 * @inheritdoc
	 */
	public function attributeLabels()
	{
		return ArrayHelper::merge(parent::attributeLabels(), [
            'imageFile' => Yii::t('label', 'Image'),
			'name' => Yii::t('common', 'Name'),
			'keywords' => Yii::t('common', 'Keywords'),
			'description' => Yii::t('common', 'Description'),
			'content' => Yii::t('common', 'Content'),
			'parent_id' => Yii::t('common', 'Parent'),
			'action' => Yii::t('label', 'Actions'),
			'subcategory' => Yii::t('label', 'Subcategories'),
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
		$this->name = ArrayHelper::map($this->categoryTranslations, 'language_id', 'name');
		$this->keywords = ArrayHelper::map($this->categoryTranslations, 'language_id', 'keywordsList');
		$this->description = ArrayHelper::map($this->categoryTranslations, 'language_id', 'description');
		$this->content = ArrayHelper::map($this->categoryTranslations, 'language_id', 'content');
		$this->action = ArrayHelper::getColumn($this->getCategoryHasActions()->select(['action_id'])->orderBy(['sort_order' => SORT_ASC])->all(), 'action_id');
		$this->subcategory = ArrayHelper::getColumn(CategoryHasAction::find()->select(['category_id'])->where(['action_id' => $this->action])->orderBy(['sort_order' => SORT_ASC])->all(), 'category_id');
	}

	/**
	 * Saves the translations.
	 *
	 * @return bool
	 */
	protected function saveCategoryTranslations()
	{
		try {
			foreach (\common\models\Language::findAllLanguages() as $language) {
				$categoryTranslation = CategoryTranslation::findOne([
					'category_id' => $this->id,
					'language_id' => $language->language_id,
				]);
				if (!$categoryTranslation) {
					$categoryTranslation = new CategoryTranslation();
					$categoryTranslation->language_id = $language->language_id;
				}
				$categoryTranslation->name = ModelHelper::getTranslation($this->name, $language->language_id);
				$categoryTranslation->slug = Inflector::slug($categoryTranslation->name);
				$categoryTranslation->keywords = $this->keywords[$language->language_id] ? implode(',', (array) $this->keywords[$language->language_id]) : null;
				$categoryTranslation->description = $this->description[$language->language_id];
				$categoryTranslation->content = $this->content[$language->language_id];

				$translation = CategoryTranslation::findOne([
					'slug' => $categoryTranslation->slug,
					'language_id' => $language->language_id,
				]);
				if ($translation) {
					$parentTranslation = CategoryTranslation::findOne([
						'category_id' => $this->parent_id,
						'language_id' => $language->language_id,
					]);
				}
				$categoryTranslation->slug = implode('-', array_filter([$parentTranslation->slug, $categoryTranslation->slug]));

				$this->link('categoryTranslations', $categoryTranslation);
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

			$categories = Category::findAll([
				'id' => array_merge([$this->id], (array) $this->subcategory),
				'status' => Category::STATUS_ACTIVE,
				'deleted' => Category::NO,
			]);

			foreach ($categories as $category) {
				$category->unlinkAll('actions', true);
				foreach ($actions as $action) {
					$categoryAction = new CategoryHasAction();
					$categoryAction->category_id = $category->id;
					$categoryAction->action_id = $action->id;
					if (!$categoryAction->save()) {
						throw new \Exception();
					}
				}
			}
			return true;
		} catch (\Exception $e) {
			$this->addError('', $e->getMessage());
			return false;
		}
	}

	/**
	 * Saves the category leaf flag.
	 *
	 * @return bool
	 * @throws \Exception
	 * @throws \Throwable
	 */
	protected function saveCategoryLeaf()
	{
		try {
			$categories  = Category::getTree($this->id);
			foreach ($categories as $category) {
				$category = Category::findOne(['id' => $category->id]);
				$children = Category::find()
					->where([
						'status' => self::STATUS_ACTIVE,
						'deleted' => self::NO,
						'parent_id' => $category->id,
					])
					->all();
				if(count($children) > 0) {
					$category->leaf = 0;
				} else {
					$category->leaf = 1;
				}
				if (!$category->save(['leaf'])) {
					throw new \Exception();
				}
			}

			return true;
		} catch (InvalidCallException $e) {
			$this->addError('', $e->getMessage());
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

            $dirPath = Yii::getAlias("@uploads/category/{$this->id}");
            $oldFilePath = "{$dirPath}/{$this->oldAttributes['image']}";
            $fileName = StringHelper::truncate(implode('_', array_filter([
                    Inflector::slug($this->translation->name),
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
            // Unset the file attributes since they are treated later
            unset($this->image);
			if (!$this->save()) {
				throw new \Exception();
			}
			if (!$this->saveCategoryTranslations()) {
				throw new \Exception();
			}
			if (!$this->saveCategoryLeaf()) {
				throw new \Exception();
			}
			if (!$this->linkActions()) {
				throw new \Exception();
			}
			// Save files
			if (!$this->saveFiles()) {
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
