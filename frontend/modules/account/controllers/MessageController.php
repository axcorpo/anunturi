<?php

namespace frontend\modules\account\controllers;

use common\models\Conversation;
use common\models\IgnoredUser;
use common\models\Message;
use frontend\controllers\MainController;
use frontend\modules\account\models\CustomerMessageForm;
use frontend\modules\account\models\MessageForm;
use frontend\modules\account\models\ProfileForm;
use Yii;
use yii\data\ActiveDataProvider;
use yii\db\Exception;
use yii\filters\AccessControl;
use yii\helpers\FileHelper;
use yii\web\NotFoundHttpException;
use yii\web\UploadedFile;

class MessageController extends MainController
{
	/**
	 * @inheritdoc
	 */
	public function behaviors()
	{
		return [
			'access' => [
				'class' => AccessControl::class,
				'rules' => [
					[
						'allow' => true,
						'actions' => ['index', 'view', 'upload-file', 'create', 'delete-conversation', 'block-user', 'unblock-user', 'customer-message'],
						'roles' => ['@'],
					],
				],
			],
		];
	}

    /**
     * Displays index view.
     *
     * @return mixed
     * @throws NotFoundHttpException
     */
    public function actionIndex()
    {
        $query = Message::find()
            ->alias('m')
            ->select([
                'm.*',
                ])
            ->where([
                'm.status' => Message::STATUS_ACTIVE,
                'm.deleted' => Message::NO,
            ]);


        if (empty(Yii::$app->request->get('type'))) {
            $query->joinWith([
                'conversation c',
            ])
            ->andWhere([
                'OR',
                ['m.created_by' => Yii::$app->user->identity->id],
                ['m.recipient_id' => Yii::$app->user->identity->id],
            ])
            ->groupBy(['m.conversation_id'])
            ->orderBy([
                'c.updated_at' => SORT_DESC,
            ]);
        }

        if (Yii::$app->request->get('type') === 'received') {
            $query->andWhere([
                'm.recipient_id' => Yii::$app->user->identity->id,
            ])
            ->orderBy([
                'm.created_at' => SORT_DESC,
            ]);
        }

        if (Yii::$app->request->get('type') === 'sent') {
            $query->andWhere([
                'm.created_by' => Yii::$app->user->identity->id,
            ])
            ->orderBy([
                'm.created_at' => SORT_DESC,
            ]);
        }

        $dataProvider = new ActiveDataProvider([
            'query' => $query,
            'pagination' => [
                'defaultPageSize' => 8,
            ],
        ]);

        return $this->render('index', [
            'dataProvider' => $dataProvider,
        ]);
    }

    /**
     * Displays a single Announcement model.
     *
     * @param string $slug
     * @return mixed
     * @throws NotFoundHttpException
     * @throws \Exception
     */
    public function actionView($id)
    {
        $model = $this->findModel($id);
        $formModel = new MessageForm([
            'announcement_id' => $model->announcement_id,
            'recipient_id' => $model->recipient_id == Yii::$app->user->id ? $model->created_by : $model->recipient_id,
        ]);
        $messageModels = Message::find()
            ->alias('m')
            ->select([
                'm.*'
            ])
            ->joinWith([
                'conversation c'
            ])
            ->where([
                'm.status' => Message::STATUS_ACTIVE,
                'm.deleted' => Message::NO,
            ])
            ->andWhere([
                'm.conversation_id' => $model->conversation_id,
                'm.recipient_id' => Yii::$app->user->id,
            ])
            ->andWhere([
                'IS', 'm.seen_at', null,
            ])
            ->all();

        foreach ($messageModels as $messageModel) {
            $messageModel->seen_at = (new \DateTime)->format('Y-m-d H:i:s');
            $messageModel->save(false, ['seen_at']);
        }

        if ($formModel->load(Yii::$app->request->post()) && ($result = $formModel->saveModel())) {
            Yii::$app->trigger('invalidate.cache', new \tws\caching\CacheEvent(['key' => 'findAllMessages']));

            $message = Yii::t('common', 'Your message was sent.');

            if (Yii::$app->request->isAjax) {
                return $this->asJson([
                    'id' => $model->id,
                    'success' => true,
                    'message' => $message,
                ]);
            }
            Yii::$app->session->setFlash('success', $message);

            return $this->redirect(['view', 'id' => $model->id]);
        }

		if (Yii::$app->request->isAjax) {
			return $this->asJson([
				'success' => (bool) $result,
				'data' => $this->renderAjax('view', [
					'model' => $model,
					'formModel' => $formModel,
				]),
			]);
		}

        return $this->render('view', [
            'model' => $model,
            'formModel' => $formModel,
        ]);
    }

    /**
     * Creates a new Company model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     *
     * @return mixed
     * @throws \yii\db\Exception
     */
    public function actionCreate()
    {
        $model = new MessageForm([
			'parent_id' => Yii::$app->request->get('parent_id'),
			'announcement_id' => Yii::$app->request->get('announcement_id'),
		]);

        $message = Yii::t('common', 'Your message was sent.');
        $result = true;

        if ($model->load(Yii::$app->request->post()) && ($result = $model->saveModel())) {
            Yii::$app->trigger('invalidate.cache', new \tws\caching\CacheEvent(['key' => 'findAllMessages']));

            if (Yii::$app->request->isAjax) {
                return $this->asJson([
                    'id' => $model->id,
                    'success' => true,
                    'message' => $message,
                ]);
            }
            Yii::$app->session->setFlash('success', $message);

            return $this->redirect(['view', 'id' => $model->id]);
        }

        if (Yii::$app->request->isAjax) {
                return $this->asJson([
                    'success' => (bool)$result,
                    'data' => $this->renderAjax('create', [
                        'model' => $model,
                    ]),

                ]);
            }

        return $this->render('create', [
            'model' => $model,
        ]);
    }

    /**
     * Deletes existing Company models.
     * If deletion is successful, JSON is returned or the browser will be redirected to the 'index' page.
     *
     * @param null|int $id
     * @return mixed
     * @throws \Throwable
     * @throws NotFoundHttpException if no model was found
     */
    public function actionDeleteConversation($id = null)
    {
        $model = Conversation::findOne(['id' => $id]);
        $model->delete(true);

        $message = Yii::t('common', 'Conversation has been deleted.');
        if (Yii::$app->request->isAjax) {
            return $this->asJson([
                'id' => $model->id,
                'success' => true,
                'message' => $message,
            ]);
        }
        Yii::$app->session->setFlash('success', $message);

        return $this->redirect(['index']);
    }


    /**
     * Insert user into ignore list.
     * If deletion is successful, JSON is returned or the browser will be redirected to the 'index' page.
     *
     * @param null|int $id
     * @return mixed
     * @throws \Throwable
     * @throws NotFoundHttpException if no model was found
     */
    public function actionBlockUser()
    {
        try {
            $ignoredUsers = IgnoredUser::find()
                ->alias('iu')
                ->where([
                    'iu.user_id' => Yii::$app->request->get('user_id'),
                    'iu.created_by' => Yii::$app->user->id,
                ])
                ->andWhere([
                    'iu.status' => IgnoredUser::STATUS_ACTIVE,
                    'iu.deleted' => IgnoredUser::NO,
                ])
                ->all();

            if (!$ignoredUsers) {
                $model = new IgnoredUser();
                $model->user_id = Yii::$app->request->get('user_id');
                $model->status = IgnoredUser::STATUS_ACTIVE;
                if (!$model->save()) {
                    throw new \Exception();
                }

                $message = Yii::t('common', 'User has been blocked.');
                if (Yii::$app->request->isAjax) {
                    return $this->asJson([
                        'id' => $model->id,
                        'success' => true,
                        'message' => $message,
                    ]);
                }
                Yii::$app->session->setFlash('success', $message);
            }
        } catch (\Exception $e) {
            return null;
        }

        return $this->redirect(['index']);
    }

    /**
    /**
     * Insert user into ignore list.
     * If deletion is successful, JSON is returned or the browser will be redirected to the 'index' page.
     *
     * @param null|int $id
     * @return mixed
     * @throws \Throwable
     * @throws NotFoundHttpException if no model was found
     */
    public function actionUnblockUser()
    {
        try {
            $ignoredUser = IgnoredUser::find()
                ->alias('iu')
                ->where([
                    'iu.user_id' => Yii::$app->request->get('user_id'),
                    'iu.created_by' => Yii::$app->user->id,
                ])
                ->andWhere([
                    'iu.status' => IgnoredUser::STATUS_ACTIVE,
                    'iu.deleted' => IgnoredUser::NO,
                ])
                ->one();

            if ($ignoredUser) {
                $ignoredUser->delete();

                $message = Yii::t('common', 'User has been unblocked.');
                if (Yii::$app->request->isAjax) {
                    return $this->asJson([
                        'id' => $ignoredUser->id,
                        'success' => true,
                        'message' => $message,
                    ]);
                }
                Yii::$app->session->setFlash('success', $message);
            }
        } catch (\Exception $e) {
            return null;
        }

        return $this->redirect(['index']);
    }

    /**
     * Creates a new Company model.
     * If creation is successful, the browser will be redirected to the 'view' page.
     *
     * @return mixed
     * @throws \yii\db\Exception
     */
    public function actionCustomerMessage()
    {
        $model = new CustomerMessageForm([
            'announcement_id' => Yii::$app->request->get('announcement_id'),
        ]);

        $message = Yii::t('common', 'Your message was sent.');
        $result = true;

        if ($model->load(Yii::$app->request->post()) && ($result = $model->saveModel())) {
            Yii::$app->trigger('invalidate.cache', new \tws\caching\CacheEvent(['key' => 'findAllMessages']));

            if (Yii::$app->request->isAjax) {
                return $this->asJson([
                    'id' => $model->id,
                    'success' => true,
                    'message' => $message,
                ]);
            }
            Yii::$app->session->setFlash('success', $message);

            return $this->redirect(['view', 'id' => $model->id]);
        }

        if (Yii::$app->request->isAjax) {
            return $this->asJson([
                'success' => (bool)$result,
                'data' => $this->renderAjax('create', [
                    'model' => $model,
                ]),

            ]);
        }

        return $this->render('create', [
            'model' => $model,
        ]);
    }



	/**
	 * Finds the Subscriber model based on its primary key value.
	 * If the model is not found, a 404 HTTP exception will be thrown.
	 *
	 * @param integer $id
	 * @param \yii\db\ActiveRecord|null $modelName
	 * @return \yii\db\ActiveRecord|ProfileForm the loaded model
	 * @throws NotFoundHttpException if the model cannot be found
	 */
	protected function findModel($id, $modelName = null)
	{
		$modelName = class_exists($modelName) ? $modelName : Message::class;
		$model = $modelName::find()
			->where([
				'id' => $id,
				'deleted' => Message::NO,
			]);

		if (($model = $model->one()) !== null) {
			return $model;
		}

		throw new NotFoundHttpException(Yii::t('common', 'The requested page does not exist.'));
	}
}
