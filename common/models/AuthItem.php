<?php

namespace common\models;

use Yii;
use yii\rbac\Item;

/**
 * This is the model class for table "{{%auth_item}}".
 *
 * @property string $name
 * @property int $type
 * @property string $description
 * @property string $rule_name
 * @property resource $data
 * @property int $created_at
 * @property int $updated_at
 *
 * @property AuthAssignment[] $authAssignments
 * @property AuthRule $ruleName
 * @property AuthItemChild[] $authItemChildren
 * @property AuthItemChild[] $authItemChildren0
 * @property AuthItem[] $children
 * @property AuthItem[] $parents
 */
class AuthItem extends CommonActiveRecord
{
	/**
	 * @inheritdoc
	 */
	public static function tableName()
	{
		return '{{%auth_item}}';
	}

	/**
	 * @inheritdoc
	 */
	public function rules()
	{
		return [
			[['name', 'type'], 'required'],
			[['type', 'created_at', 'updated_at'], 'integer'],
			[['description', 'data'], 'string'],
			[['name', 'rule_name'], 'string', 'max' => 64],
			[['name'], 'unique'],
			[['rule_name'], 'exist', 'skipOnError' => true, 'targetClass' => AuthRule::class, 'targetAttribute' => ['rule_name' => 'name']],
		];
	}

	/**
	 * @inheritdoc
	 */
	public function attributeLabels()
	{
		return [
			'name' => Yii::t('label', 'Name'),
			'type' => Yii::t('label', 'Type'),
			'description' => Yii::t('label', 'Description'),
			'rule_name' => Yii::t('label', 'Rule Name'),
			'data' => Yii::t('label', 'Data'),
			'created_at' => Yii::t('label', 'Created At'),
			'updated_at' => Yii::t('label', 'Updated At'),
		];
	}

	/**
	 * @return \yii\db\ActiveQuery|CommonActiveQuery
	 */
	public function getAuthAssignments()
	{
		return $this->hasMany(AuthAssignment::class, ['item_name' => 'name']);
	}

	/**
	 * @return \yii\db\ActiveQuery|CommonActiveQuery
	 */
	public function getRuleName()
	{
		return $this->hasOne(AuthRule::class, ['name' => 'rule_name']);
	}

	/**
	 * @return \yii\db\ActiveQuery|CommonActiveQuery
	 */
	public function getAuthItemChildren()
	{
		return $this->hasMany(AuthItemChild::class, ['parent' => 'name']);
	}

	/**
	 * @return \yii\db\ActiveQuery|CommonActiveQuery
	 */
	public function getAuthItemChildren0()
	{
		return $this->hasMany(AuthItemChild::class, ['child' => 'name']);
	}

	/**
	 * @return \yii\db\ActiveQuery|CommonActiveQuery
	 * @throws \yii\base\InvalidConfigException
	 */
	public function getChildren()
	{
		return $this->hasMany(AuthItem::class, ['name' => 'child'])->viaTable('{{%auth_item_child}}', ['parent' => 'name']);
	}

	/**
	 * @return \yii\db\ActiveQuery|CommonActiveQuery
	 * @throws \yii\base\InvalidConfigException
	 */
	public function getParents()
	{
		return $this->hasMany(AuthItem::class, ['name' => 'parent'])->viaTable('{{%auth_item_child}}', ['child' => 'name']);
	}

	/**
	 * Finds all roles.
	 *
	 * @return mixed
	 */
	public static function findAllRoles()
	{
		return static::find()
			->select([
				'name',
				'description',
			])
			->where([
				'AND',
				['=', 'type', Item::TYPE_ROLE],
				['!=', 'name', 'superAdmin'],
			])
			->all();
	}

	/**
	 * Gets the permissions items array.
	 *
	 * @return array
	 */
	public static function getAllPermissions()
	{
		return [
			'Website' => [
				'heading' => Yii::t('common', 'Website'),
				'groups' => [
					'Page' => [
						'heading' => Yii::t('common', 'Pages'),
						'items' => [
							'viewPage' => Yii::t('common', 'View'),
							'createPage' => Yii::t('common', 'Create'),
							'updatePage' => Yii::t('common', 'Update'),
							'deletePage' => Yii::t('common', 'Delete'),
							'restorePage' => Yii::t('common', 'Restore'),
						],
					],
					'Menu' => [
						'heading' => Yii::t('common', 'Menus'),
						'items' => [
							'viewMenu' => Yii::t('common', 'View'),
							'createMenu' => Yii::t('common', 'Create'),
							'updateMenu' => Yii::t('common', 'Update'),
							'deleteMenu' => Yii::t('common', 'Delete'),
							'restoreMenu' => Yii::t('common', 'Restore'),
						],
					],
					'Carousel' => [
						'heading' => Yii::t('common', 'Carousels'),
						'items' => [
							'viewCarousel' => Yii::t('common', 'View'),
							'createCarousel' => Yii::t('common', 'Create'),
							'updateCarousel' => Yii::t('common', 'Update'),
							'deleteCarousel' => Yii::t('common', 'Delete'),
							'restoreCarousel' => Yii::t('common', 'Restore'),
						],
					],
				],
			],
            'Marketing' => [
                'heading' => Yii::t('common', 'Marketing'),
                'groups' => [
                    'Direct' => [
                        'heading' => Yii::t('common', 'Direct Marketing Campaigns'),
                        'items' => [
                            'viewDirectMarketingCampaign' => Yii::t('common', 'View'),
                            'createDirectMarketingCampaign' => Yii::t('common', 'Create'),
                            'updateDirectMarketingCampaign' => Yii::t('common', 'Update'),
                            'deleteDirectMarketingCampaign' => Yii::t('common', 'Delete'),
                            'restoreDirectMarketingCampaign' => Yii::t('common', 'Restore'),
                        ],
                    ],
                    'Recipient' => [
                        'heading' => Yii::t('common', 'Marketing Recipients'),
                        'items' => [
                            'viewMarketingRecipient' => Yii::t('common', 'View'),
                            'createMarketingRecipient' => Yii::t('common', 'Create'),
                            'updateMarketingRecipient' => Yii::t('common', 'Update'),
                            'deleteMarketingRecipient' => Yii::t('common', 'Delete'),
                            'restoreMarketingRecipient' => Yii::t('common', 'Restore'),
                            'importMarketingRecipient' => Yii::t('common', 'Import {item}', ['item' => Yii::t('label', 'Marketing Recipients')]),
                        ],
                    ],
                    'Group' => [
                        'heading' => Yii::t('common', 'Marketing Groups'),
                        'items' => [
                            'viewMarketingGroup' => Yii::t('common', 'View'),
                            'createMarketingGroup' => Yii::t('common', 'Create'),
                            'updateMarketingGroup' => Yii::t('common', 'Update'),
                            'deleteMarketingGroup' => Yii::t('common', 'Delete'),
                            'restoreMarketingGroup' => Yii::t('common', 'Restore'),
                        ],
                    ],
                ],
            ],
			'Commercial' => [
				'heading' => Yii::t('common', 'Commercials'),
				'groups' => [
					'Auction' => [
						'heading' => Yii::t('common', 'Auctions'),
						'items' => [
							'viewAuction' => Yii::t('common', 'View'),
							'createAuction' => Yii::t('common', 'Create'),
							'updateAuction' => Yii::t('common', 'Update'),
							'deleteAuction' => Yii::t('common', 'Delete'),
							'restoreAuction' => Yii::t('common', 'Restore'),
						],
					],
					'Broker' => [
						'heading' => Yii::t('common', 'Brokers'),
						'items' => [
							'viewBroker' => Yii::t('common', 'View'),
							'createBroker' => Yii::t('common', 'Create'),
							'updateBroker' => Yii::t('common', 'Update'),
							'deleteBroker' => Yii::t('common', 'Delete'),
							'restoreBroker' => Yii::t('common', 'Restore'),
						],
					],
					'Bid' => [
						'heading' => Yii::t('common', 'Bids'),
						'items' => [
							'viewBid' => Yii::t('common', 'View'),
							'createBid' => Yii::t('common', 'Create'),
							'updateBid' => Yii::t('common', 'Update'),
							'deleteBid' => Yii::t('common', 'Delete'),
							'restoreBid' => Yii::t('common', 'Restore'),
						],
					],
					'Commercial' => [
						'heading' => Yii::t('common', 'Commercials'),
						'items' => [
							'viewCommercial' => Yii::t('common', 'View'),
							'createCommercial' => Yii::t('common', 'Create'),
							'updateCommercial' => Yii::t('common', 'Update'),
							'deleteCommercial' => Yii::t('common', 'Delete'),
							'restoreCommercial' => Yii::t('common', 'Restore'),
						],
					],
				],
			],
			'Announcement' => [
				'heading' => Yii::t('common', 'Announcements'),
				'groups' => [
					'Category' => [
						'heading' => Yii::t('common', 'Categories'),
						'items' => [
							'viewCategory' => Yii::t('common', 'View'),
							'createCategory' => Yii::t('common', 'Create'),
							'updateCategory' => Yii::t('common', 'Update'),
							'deleteCategory' => Yii::t('common', 'Delete'),
							'restoreCategory' => Yii::t('common', 'Restore'),
						],
					],
					'Field' => [
						'heading' => Yii::t('common', 'Fields'),
						'items' => [
							'viewField' => Yii::t('common', 'View'),
							'createField' => Yii::t('common', 'Create'),
							'updateField' => Yii::t('common', 'Update'),
							'deleteField' => Yii::t('common', 'Delete'),
							'restoreField' => Yii::t('common', 'Restore'),
						],
					],
					'Announcement' => [
						'heading' => Yii::t('common', 'Announcements'),
						'items' => [
							'viewAnnouncement' => Yii::t('common', 'View'),
							'createAnnouncement' => Yii::t('common', 'Create'),
							'updateAnnouncement' => Yii::t('common', 'Update'),
							'deleteAnnouncement' => Yii::t('common', 'Delete'),
							'restoreAnnouncement' => Yii::t('common', 'Restore'),
						],
					],
					'Promotional' => [
						'heading' => Yii::t('common', 'Promotionals'),
						'items' => [
							'viewPromotional' => Yii::t('common', 'View'),
							'createPromotional' => Yii::t('common', 'Create'),
							'updatePromotional' => Yii::t('common', 'Update'),
							'deletePromotional' => Yii::t('common', 'Delete'),
							'restorePromotional' => Yii::t('common', 'Restore'),
						],
					],
					'Renewal' => [
						'heading' => Yii::t('common', 'Renewals'),
						'items' => [
							'viewRenewal' => Yii::t('common', 'View'),
							'createRenewal' => Yii::t('common', 'Create'),
							'updateRenewal' => Yii::t('common', 'Update'),
							'deleteRenewal' => Yii::t('common', 'Delete'),
							'restoreRenewal' => Yii::t('common', 'Restore'),
						],
					],
                    'Reservation' => [
                        'heading' => Yii::t('common', 'Reservations'),
                        'items' => [
                            'viewReservation' => Yii::t('common', 'View'),
                            'createReservation' => Yii::t('common', 'Create'),
                            'updateReservation' => Yii::t('common', 'Update'),
                            'deleteReservation' => Yii::t('common', 'Delete'),
                            'restoreReservation' => Yii::t('common', 'Restore'),
                        ],
                    ],
                    'Reviews' => [
                        'heading' => Yii::t('common', 'Reviews'),
                        'items' => [
                            'viewReview' => Yii::t('common', 'View'),
                            'createReview' => Yii::t('common', 'Create'),
                            'updateReview' => Yii::t('common', 'Update'),
                            'deleteReview' => Yii::t('common', 'Delete'),
                            'restoreReview' => Yii::t('common', 'Restore'),
                        ],
                    ],
				],
			],
			'Payment' => [
				'heading' => Yii::t('common', 'Payments'),
				'items' => [
					'viewPayment' => Yii::t('common', 'View'),
					'createPayment' => Yii::t('common', 'Create'),
					'updatePayment' => Yii::t('common', 'Update'),
					'deletePayment' => Yii::t('common', 'Delete'),
					'restorePayment' => Yii::t('common', 'Restore'),
				],
			],
			'Subscriber' => [
				'heading' => Yii::t('common', 'Subscribers'),
				'groups' => [
					'Subscriber' => [
						'heading' => Yii::t('common', 'Subscribers'),
						'items' => [
							'viewSubscriber' => Yii::t('common', 'View'),
							'createSubscriber' => Yii::t('common', 'Create'),
							'updateSubscriber' => Yii::t('common', 'Update'),
							'deleteSubscriber' => Yii::t('common', 'Delete'),
							'restoreSubscriber' => Yii::t('common', 'Restore'),
						],
					],
					'Invoice' => [
						'heading' => Yii::t('common', 'Invoices'),
						'items' => [
							'viewInvoice' => Yii::t('common', 'View'),
							'updateInvoice' => Yii::t('common', 'Update'),
						],
					],
				],
			],
			'Nomenclature' => [
				'heading' => Yii::t('common', 'Nomenclature'),
				'groups' => [
					'Package' => [
						'heading' => Yii::t('common', 'Packages'),
						'items' => [
							'viewPackage' => Yii::t('common', 'View'),
							'createPackage' => Yii::t('common', 'Create'),
							'updatePackage' => Yii::t('common', 'Update'),
							'deletePackage' => Yii::t('common', 'Delete'),
							'restorePackage' => Yii::t('common', 'Restore'),
						],
					],
					'Feature' => [
						'heading' => Yii::t('common', 'Features'),
						'items' => [
							'viewFeature' => Yii::t('common', 'View'),
							'createFeature' => Yii::t('common', 'Create'),
							'updateFeature' => Yii::t('common', 'Update'),
						],
					],
					'DocumentSeries' => [
						'heading' => Yii::t('common', 'Document Series'),
						'items' => [
							'viewDocumentSeries' => Yii::t('common', 'View'),
							'createDocumentSeries' => Yii::t('common', 'Create'),
							'updateDocumentSeries' => Yii::t('common', 'Update'),
							'deleteDocumentSeries' => Yii::t('common', 'Delete'),
							'restoreDocumentSeries' => Yii::t('common', 'Restore'),
						],
					],
					'UnitOfMeasure' => [
						'heading' => Yii::t('common', 'Unit Of Measures'),
						'items' => [
							'viewUnitOfMeasure' => Yii::t('common', 'View'),
							'createUnitOfMeasure' => Yii::t('common', 'Create'),
							'updateUnitOfMeasure' => Yii::t('common', 'Update'),
							'deleteUnitOfMeasure' => Yii::t('common', 'Delete'),
							'restoreUnitOfMeasure' => Yii::t('common', 'Restore'),
						],
					],
					'KnowledgeBase' => [
						'heading' => Yii::t('label', 'Knowledge Bases'),
						'items' => [
							'viewKnowledgeBase' => Yii::t('label', 'View'),
							'createKnowledgeBase' => Yii::t('label', 'Create'),
							'updateKnowledgeBase' => Yii::t('label', 'Update'),
							'deleteKnowledgeBase' => Yii::t('label', 'Delete'),
							'restoreKnowledgeBase' => Yii::t('label', 'Restore'),
						],
					],
					'Assistant' => [
						'heading' => Yii::t('label', 'Assistants'),
						'items' => [
							'viewAssistant' => Yii::t('label', 'View'),
							'createAssistant' => Yii::t('label', 'Create'),
							'updateAssistant' => Yii::t('label', 'Update'),
							'deleteAssistant' => Yii::t('label', 'Delete'),
							'restoreAssistant' => Yii::t('label', 'Restore'),
						],
					],
					'EmailTemplate' => [
						'heading' => Yii::t('common', 'Email Templates'),
						'items' => [
							'viewEmailTemplate' => Yii::t('common', 'View'),
							'createEmailTemplate' => Yii::t('common', 'Create'),
							'updateEmailTemplate' => Yii::t('common', 'Update'),
							'deleteEmailTemplate' => Yii::t('common', 'Delete'),
							'restoreEmailTemplate' => Yii::t('common', 'Restore'),
						],
					],
					'InvoiceTemplate' => [
						'heading' => Yii::t('common', 'Invoice Templates'),
						'items' => [
							'viewInvoiceTemplate' => Yii::t('common', 'View'),
							'createInvoiceTemplate' => Yii::t('common', 'Create'),
							'updateInvoiceTemplate' => Yii::t('common', 'Update'),
							'deleteInvoiceTemplate' => Yii::t('common', 'Delete'),
							'restoreInvoiceTemplate' => Yii::t('common', 'Restore'),
						],
					],
				],
			],
			'User' => [
				'heading' => Yii::t('common', 'Users'),
				'groups' => [
					'User' => [
						'heading' => Yii::t('common', 'Users'),
						'items' => [
							'viewUser' => Yii::t('common', 'View'),
							'createUser' => Yii::t('common', 'Create'),
							'updateUser' => Yii::t('common', 'Update'),
							'deleteUser' => Yii::t('common', 'Delete'),
							'restoreUser' => Yii::t('common', 'Restore'),
						],
					],
					'Role' => [
						'heading' => Yii::t('common', 'Roles'),
						'items' => [
							'viewUserRole' => Yii::t('common', 'View'),
							'createUserRole' => Yii::t('common', 'Create'),
							'updateUserRole' => Yii::t('common', 'Update'),
							'deleteUserRole' => Yii::t('common', 'Delete'),
						],
					],
				],
			],
			'Setting' => [
				'heading' => Yii::t('common', 'Settings'),
				'groups' => [
					'Setting' => [
						'heading' => Yii::t('common', 'Settings'),
						'items' => [
							'updateGeneralSetting' => Yii::t('common', 'General'),
							'updateEmailSetting' => Yii::t('common', 'Email'),
							'updatePaymentSetting' => Yii::t('common', 'Payment'),
							'updateCommercialSetting' => Yii::t('common', 'Commercial'),
							'updateSeoSetting' => Yii::t('common', 'SEO'),
							'updateSocialNetworkSetting' => Yii::t('common', 'Social Networks'),
							'updateContactSetting' => Yii::t('common', 'Contact'),
							'updateScriptSetting' => Yii::t('common', 'Script'),
							'clearCacheSetting' => Yii::t('common', 'Clear Cache'),
						],
					],
					'Language' => [
						'heading' => Yii::t('common', 'Languages'),
						'items' => [
							'viewLanguageSetting' => Yii::t('common', 'View'),
							'updateLanguageSetting' => Yii::t('common', 'Update'),
							'translateIntoLanguageSetting' => Yii::t('common', 'Translate'),
						],
					],
					'Currency' => [
						'heading' => Yii::t('common', 'Currencies'),
						'items' => [
							'viewCurrencySetting' => Yii::t('common', 'View'),
							'updateCurrencySetting' => Yii::t('common', 'Update'),
						],
					],
					'Integration' => [
						'heading' => Yii::t('common', 'Integrations'),
						'items' => [
							'viewIntegration' => Yii::t('common', 'View'),
							'createIntegration' => Yii::t('common', 'Create'),
							'updateIntegration' => Yii::t('common', 'Update'),
							'deleteIntegration' => Yii::t('common', 'Delete'),
						],
					],
				],
			],
			'Backup' => [
				'heading' => Yii::t('common', 'Backups'),
				'items' => [
					'viewBackup' => Yii::t('common', 'View'),
					'createBackup' => Yii::t('common', 'Create'),
					'downloadBackup' => Yii::t('common', 'Download'),
					'recoverBackup' => Yii::t('common', 'Recover'),
					'deleteBackup' => Yii::t('common', 'Delete'),
					'restoreBackup' => Yii::t('common', 'Restore'),
				],
			],
			'EventLog' => [
				'heading' => Yii::t('common', 'Event Logs'),
				'items' => [
					'viewEventLog' => Yii::t('common', 'View'),
				],
			],
		];
	}

	/**
	 * Filters the permissions list by the current authenticated user permissions.
	 *
	 * @param array $data
	 * @return array
	 */
	public static function filterPermissions($data)
	{
		$permissions = [];

		foreach ($data as $key => $val) {
			if (isset($val['visible']) && $val['visible'] === false) {
				continue;
			}
			if (isset($val['groups'])) {
				// Filter the permissions for the group items
				$groups = self::filterPermissions($val['groups']);
				// Push to the stack only if the group items array is not empty
				if (!empty($groups)) {
					$permissions[$key] = [
						'heading' => $val['heading'],
						'groups' => $groups,
					];
				}
			} elseif (isset($val['items'])) {
				// Filter the permissions for the child items
				$items = self::filterPermissions($val['items']);
				// Push to the stack only if the child items array is not empty
				if (!empty($items)) {
					$permissions[$key] = [
						'heading' => $val['heading'],
						'items' => $items,
					];
				}
			} else {
				// Check the user permission
				if (Yii::$app->user->can($key)) {
					$permissions[$key] = $val;
				}
			}
		}

		return $permissions;
	}

	/**
	 * Gets the filtered permissions for the current authenticated user.
	 *
	 * @return array
	 */
	public static function getFilteredPermissions()
	{
		return self::filterPermissions(self::getAllPermissions());
	}
}
