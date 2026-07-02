<?php

namespace frontend\modules\announcement\controllers;

use common\helpers\AnnouncementListSearch;
use common\helpers\AnnouncementSearchAuditLog;
use common\models\Action;
use common\models\AiConversation;
use common\models\AiMessage;
use common\models\Announcement;
use common\models\Assistant;
use common\models\Category;
use common\models\County;
use common\models\RecordVectorIndex;
use common\models\Subscriber;
use common\services\OpenAIResponsesService;
use common\services\OpenAiRecordVectorStoreService;
use dosamigos\google\maps\LatLng;
use dosamigos\google\maps\Map;
use dosamigos\google\maps\overlays\Marker;
use frontend\controllers\MainController;
use frontend\modules\announcement\models\AnnouncementEmbedForm;
use frontend\modules\announcement\models\ReviewForm;
use frontend\modules\announcement\models\FilterForm;
use frontend\modules\announcement\models\ListFilterForm;
use kartik\depdrop\DepDropAction;
use Yii;
use yii\data\ActiveDataProvider;
use yii\db\ActiveQuery;
use common\helpers\Inflector;
use yii\filters\AccessControl;
use yii\filters\VerbFilter;
use yii\helpers\Url;
use yii\web\NotFoundHttpException;

class DefaultController extends MainController
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
						'actions' => [
							'index', 'create', 'view', 'embed', 'category-actions', 'chat',
						],
						'roles' => ['?', '@'],
					],
				],
			],
			'verbs' => [
				'class' => VerbFilter::class,
				'actions' => [
					'chat' => ['POST'],
				],
			],
		];
	}


	/**
	 * @inheritdoc
	 */
	public function actions()
	{
		return [
			'category-actions' => [
				'class' => DepDropAction::class,
				'outputCallback' => function ($selectedId, $params) {
					return Action::queryActionByCategoryId($selectedId);
				},
			],
		];
	}

	/**
	 * Displays index view.
	 *
	 * @param null|string $category
 	 * @param null|string $county
	 * @param null|string $tag
	 * @param null|int $year
	 * @return mixed
	 */
	public function actionIndex($category = null, $county = null, $tag = null, $year = null)
	{
		$view = $this->getView();
		$canonicalUrl = Url::canonical();
		$model = null;
		$modelTranslation = null;
		$countyModel = null;

		if (!empty($category) && ($model = Category::findCategoryBySlug($category))) {
			$modelTranslation = $model->getTranslation();
			$view->title = Yii::t('frontend', 'Announcements from category: {0}', $modelTranslation->name);
			if (Yii::$app->request->post('FilterForm')) {
				Yii::$app->session->set('filter', array_filter(Yii::$app->request->post('FilterForm')));
				$this->refresh();
			}
		} else {
			Yii::$app->session->remove('filter');
			if ($page = \common\models\Page::findPageByRoute(["/{$this->module->id}/{$this->id}/index"])) {
				$modelTranslation = $page->getTranslation();
				$view->title = $modelTranslation->title;
			}
			if (!empty($county)) {
				$countyModel = County::findCountyBySlug($county);
				$view->title = Yii::t('frontend', 'Announcements from county: {0}', $countyModel->name ?: $county);
			}
			if (!empty($tag)) {
				$view->title = Yii::t('frontend', 'Announcements tagged with: {0}', Inflector::humanize($tag));
			} elseif (!empty($year)) {
				$view->title = Yii::t('frontend', 'Announcements from year {0}', $year);
			}
		}

		// Standard meta tags
		$view->registerMetaTag(['name' => 'description', 'content' => $modelTranslation->description], 'description');
		$view->registerMetaTag(['name' => 'keywords', 'content' => $modelTranslation->keywords], 'keywords');
		$view->registerLinkTag(['rel' => 'canonical', 'href' => $canonicalUrl], 'canonical');

		// Basic metadata for open graph
		$view->registerMetaTag(['property' => 'og:type', 'content' => 'announcement'], 'og:type');
		$view->registerMetaTag(['property' => 'og:url', 'content' => $canonicalUrl], 'og:url');
		$view->registerMetaTag(['property' => 'og:title', 'content' => !empty($category) && Category::findCategoryBySlug($category) ? $modelTranslation->name : $modelTranslation->title], 'og:title');
		$view->registerMetaTag(['property' => 'og:description', 'content' => $modelTranslation->description], 'og:description');

		// Free-text search: stable key (`search` / `cautare`) with fallback to the legacy
		// slugged-label param so old links keep working.
		$searchSlug = AnnouncementListSearch::getQueryParam();
		$legacySearchSlug = Inflector::slug(Yii::t('label', 'Search'));
		$searchRaw = trim((string) Yii::$app->request->get($searchSlug, ''));
		if ($searchRaw === '' && $legacySearchSlug !== $searchSlug) {
			$searchRaw = trim((string) Yii::$app->request->get($legacySearchSlug, ''));
		}
		$resolvedSearch = $searchRaw;
		$locationSlug = Inflector::slug(Yii::t('label', 'Location'));
		$locationValue = Yii::$app->request->get($locationSlug) ?: Yii::$app->request->get('location');

		// Filter bar (GET, flat params). Invalid values are dropped rather than shown as errors —
		// the filter UI is best-effort.
		$modelListFilterForm = new ListFilterForm();
		$modelListFilterForm->load(Yii::$app->request->get(), '');
		if (!$modelListFilterForm->validate()) {
			foreach (array_keys($modelListFilterForm->getErrors()) as $attr) {
				$modelListFilterForm->$attr = null;
			}
		}
		$activeFilters = $modelListFilterForm->activeFilters();

		// Route-level county slug wins over a free-form filter value (they target different scopes).
		$resolvedCountyName = $countyModel->name ?: $county;
		$filterCounty = $activeFilters['county'] ?? null;

		$semanticOrderedIds = null;
		$openAiAudit = null;
		$semanticIdsForQuery = [];
		$semanticSkippedReason = null;
		$aiTokenSource = null;

		// 1) Chat-driven AI search: a one-shot `ai=<token>` looks up pre-computed semantic IDs from cache.
		//    This is the only place where AI ranking is applied — the plain input search stays on SQL LIKE.
		$aiToken = (string) Yii::$app->request->get('ai', '');
		$aiToken = preg_match('/^[a-f0-9]{8,32}$/', $aiToken) ? $aiToken : '';
		if ($aiToken !== '' && Yii::$app->has('cache')) {
			$cached = Yii::$app->cache->get('ai_search:' . $aiToken);
			if (is_array($cached) && isset($cached['ids']) && is_array($cached['ids'])) {
				$currentGen = OpenAiRecordVectorStoreService::currentSearchCacheGeneration();
				if (!isset($cached['gen']) || $cached['gen'] === $currentGen) {
					$semanticOrderedIds = array_values(array_map('intval', $cached['ids']));
					// AI searched and found nothing — show an empty listing (consistent with the chat reply),
					// not the unfiltered grid. The `-1` sentinel survives provideAnnouncements' array_filter
					// (unlike 0) and matches no row.
					if ($semanticOrderedIds === []) {
						$semanticOrderedIds = [-1];
					}
					$semanticIdsForQuery = $semanticOrderedIds;
					$aiTokenSource = 'chat';
				}
			}
		}

		// 2) Legacy input-search vector path (only when the global flag is on).
		if ($semanticOrderedIds === null && $resolvedSearch !== '' && OpenAiRecordVectorStoreService::semanticSearchEnabled()) {
			// Skip vector search for short / single-word queries — SQL LIKE alone handles them well and vector
			// adds latency + cost for no relevance gain (configurable via `announcementVectorSemanticSearchMinWords`).
			$minWords = max(1, (int) (Yii::$app->params['announcementVectorSemanticSearchMinWords'] ?? 2));
			$wordCount = count(preg_split('/\s+/u', $resolvedSearch, -1, PREG_SPLIT_NO_EMPTY));
			if ($wordCount < $minWords) {
				$semanticSkippedReason = 'query_too_short';
			} else {
				$openAiAudit = [];
				$semanticOrderedIds = OpenAiRecordVectorStoreService::searchAnnouncementIds($resolvedSearch, null, $openAiAudit);
				$semanticIdsForQuery = $semanticOrderedIds;
				if ($semanticOrderedIds === []) {
					$semanticOrderedIds = null;
				}
			}
		}

		$baseParams = [
			'category' => $model !== null ? $model->id : null,
			'county' => $resolvedCountyName ?: $filterCounty,
			'tag' => $tag,
			'year' => $year,
			'location' => $locationValue,
			'search' => $resolvedSearch !== '' ? $resolvedSearch : null,
			'filter' => Yii::$app->session->get('filter'),
			'min_price' => $activeFilters['min_price'] ?? null,
			'max_price' => $activeFilters['max_price'] ?? null,
			'currency' => $activeFilters['currency'] ?? null,
			'locality' => $activeFilters['locality'] ?? null,
			'sort_by' => $activeFilters['sort_by'] ?? null,
		];

		// Promotional slots are suppressed in AI mode — the grid must show exactly the announcements
		// the chat reply talked about, in AI relevance order.
		if ($semanticOrderedIds === null) {
			$promotionalData = Announcement::provideAnnouncements(array_merge($baseParams, [
				'promotional' => true,
				'limit' => 3,
			]))->all();
		} else {
			$promotionalData = [];
		}

		$auctions = [];
		$auctionListTop = \common\models\Auction::findAuctionByPosition(\common\models\Auction::POSITION_ANNOUNCEMENTS_PAGE_300X440_LIST_TOP , $countyModel->id, $model->id);
		if ($auctionListTop->id) {
			$auctions[] = $auctionListTop->id;
		}
		$auctionListMiddle = \common\models\Auction::findAuctionByPosition(\common\models\Auction::POSITION_ANNOUNCEMENTS_PAGE_1030X120_LIST_MIDDLE, $countyModel->id, $model->id);
		if ($auctionListMiddle->id) {
			$auctions[] = $auctionListMiddle->id;
		}
		$auctionListBottom = \common\models\Auction::findAuctionByPosition(\common\models\Auction::POSITION_ANNOUNCEMENTS_PAGE_300X440_LIST_BOTTOM, $countyModel->id, $model->id);
		if ($auctionListBottom->id) {
			$auctions[] = $auctionListBottom->id;
		}

		$query = Announcement::provideAnnouncements(array_merge($baseParams, [
			'promotional' => null,
			'limit' => null,
		]), $semanticOrderedIds);

		// Pager URLs must repeat path params (category/county/tag/year) — they are not in $_GET on pretty
		// routes — and only the ACTIVE filters, so pagination preserves state without empty-param noise.
		$listPagerParams = Yii::$app->request->getQueryParams();
		foreach (['category' => $category, 'county' => $county, 'tag' => $tag, 'year' => $year] as $routeKey => $routeVal) {
			if ($routeVal !== null && $routeVal !== '') {
				$listPagerParams[$routeKey] = $routeVal;
			}
		}
		if ($resolvedSearch !== '') {
			$listPagerParams[$searchSlug] = $resolvedSearch;
		} else {
			unset($listPagerParams[$searchSlug]);
		}
		foreach (array_keys($modelListFilterForm->attributes) as $filterKey) {
			unset($listPagerParams[$filterKey]);
		}
		foreach ($activeFilters as $filterKey => $filterValue) {
			$listPagerParams[$filterKey] = $filterValue;
		}

		$dataProviderConfig = [
			'query' => $query,
			'pagination' => [
				'defaultPageSize' => 16 - count($promotionalData) + 3 - count($auctions),
				'params' => $listPagerParams,
			],
		];
		if ($semanticOrderedIds !== null && $semanticOrderedIds !== []) {
			$dataProviderConfig['sort'] = false;
		}
		$dataProvider = new ActiveDataProvider($dataProviderConfig);

		foreach ($promotionalData as $announcement) {
			$model = Announcement::findOne(['id' => $announcement->id]);
            if ($model->created_by != Yii::$app->user->id) {
                $model->views++;
                $model->displayed_at = (new \DateTime)->format('Y-m-d H:i:s');
                $model->save(false, ['views', 'displayed_at']);
            }
		}

		foreach ($dataProvider->getModels() as $announcement) {
			$model = Announcement::findOne(['id' => $announcement->id]);
            if ($model->created_by != Yii::$app->user->id) {
                $model->views++;
                $model->save(false, ['views']);
            }
		}

		$modelFilterForm = new FilterForm();
		if (!empty(Yii::$app->session->get('filter'))) {
			foreach(Yii::$app->session->get('filter') as $key => $value) {
				$modelFilterForm->$key = $value;
			}
		}

		if ($aiTokenSource !== null) {
			$sqlMode = 'AI chat search — semantic IDs from cached chat token; SQL LIKE still applied on search_text/category/locality/county';
		} elseif ($resolvedSearch !== '') {
			$sqlMode = ($semanticOrderedIds !== null && $semanticOrderedIds !== [])
				? 'hybrid — SQL LIKE on search_text, category, locality, county for every row; vector order among IDs returned by OpenAI'
				: 'LIKE only — SQL on search_text, category name, locality, county (vector off, empty, or zero IDs after DB map)';
		} else {
			$sqlMode = 'no text query — filters only (no search phrase; vector / LIKE on phrase not used)';
		}
		AnnouncementSearchAuditLog::writeOverwrite([
			'query' => $resolvedSearch,
			'semantic_feature_enabled' => OpenAiRecordVectorStoreService::semanticSearchEnabled(),
			'ai_token_source' => $aiTokenSource,
			'openai_invoked' => $openAiAudit !== null,
			'openai_audit' => $openAiAudit,
			'semantic_ids_for_query' => $semanticIdsForQuery,
			'semantic_skipped_reason' => $semanticSkippedReason,
			'sql_mode' => $sqlMode,
			'listing_total_count' => (int) $dataProvider->getTotalCount(),
			'listing_first_page_ids' => array_map(static function ($m) {
				return (int) $m->id;
			}, $dataProvider->getModels()),
		]);

		return $this->render('index', [
			'promotionalData' => $promotionalData,
			'dataProvider' => $dataProvider,
			'auctionListTop' => $auctionListTop,
			'auctionListMiddle' => $auctionListMiddle,
			'auctionListBottom' => $auctionListBottom,
			'modelFilterForm' => $modelFilterForm,
			'modelListFilterForm' => $modelListFilterForm,
		]);
	}

	/**
	 * Displays a single Announcement model.
	 *
	 * @param string $slug
	 * @return mixed
	 * @throws NotFoundHttpException
	 */
	public function actionView($slug)
	{
		$model = $this->findModel($slug);
        if ($model->created_by != Yii::$app->user->id) {
            $model->visits++;
            $model->save(false, ['visits']);
        }

		$modelTranslation = $model->getTranslation();
		$view = $this->getView();
		$canonicalUrl = Url::canonical();

		// Standard meta tags
		$actions = [];
		foreach ($model->actions as $action) {
			$actions[] = \common\models\Action::getMyTypes()[$action->type];
		}
		$view->title = ($actions ? implode(', ', $actions) . ' - ' : '') . $modelTranslation->title;
		$view->registerMetaTag(['name' => 'description', 'content' => $modelTranslation->description], 'description');
		$view->registerMetaTag(['name' => 'keywords', 'content' => $modelTranslation->keywords], 'keywords');
		$view->registerLinkTag(['rel' => 'canonical', 'href' => $canonicalUrl], 'canonical');

		// Basic metadata for open graph
		$view->registerMetaTag(['property' => 'og:type', 'content' => 'announcement'], 'og:type');
		$view->registerMetaTag(['property' => 'og:url', 'content' => $canonicalUrl], 'og:url');
		$view->registerMetaTag(['property' => 'og:title', 'content' => ($actions ? implode(', ', $actions) . ' - ' : '') . $modelTranslation->title], 'og:title');
		$view->registerMetaTag(['property' => 'og:description', 'content' => $modelTranslation->description], 'og:description');
		if ($ogLogo = $model->getImageUrl()) {
			$view->registerMetaTag(['property' => 'og:image', 'content' => $ogLogo], 'og:image');
		}

		$location = implode(', ', array_filter([
			$model->locality,
			$model->county,
			$model->country ? \common\models\Country::findAllCountries()[$model->country]->name : null,
		]));
		$latLng = new LatLng([
			'lat' => $model->latitude,
			'lng' => $model->longitude,
		]);
		$map = new Map([
			'center' => $latLng,
			'zoom' => 12,
			'width' => '100%',
			'height' => 400,
			'mapTypeControl' => false,
		]);
		$marker = new Marker([
			'position' => $latLng,
			'title' => Yii::$app->name,
			'icon' => Yii::getAlias('@web/img/map/marker.png'),
		]);
		$map->addOverlay($marker);


		$reviewModel = new ReviewForm(['announcement_id' => $model->id]);

		if ($reviewModel->load(Yii::$app->request->post()) && $reviewModel->validate()) {
			if ($reviewModel->save()) {
				Yii::$app->session->setFlash('success', Yii::t('frontend', 'Thank you for your feedback.'));
			} else {
				Yii::$app->session->setFlash('error', Yii::t('common', 'There was an error sending your message.'));
			}
			return $this->refresh();
		}

		return $this->render('view', [
			'model' => $model,
            'reviewModel' => $reviewModel,
			'modelTranslation' => $model->getTranslation(),
			'tags' => array_filter(explode(',', $modelTranslation->keywords)),
			'map' => $map,
			'location' => $location,
		]);
	}


	public function actionCreate()
	{
		$this->layout = 'embed';
		try {
			$code = Yii::$app->security->unmaskToken((string) Yii::$app->request->get('code'));
			$subscriber = Subscriber::findOne(['code' => $code]);
			$user = $subscriber->user;
			$model = new AnnouncementEmbedForm();
			return $this->render('_create-form', [
				'model' => $model,
			]);
		}  catch (\Exception $e) {
			return $this->goHome();
		}
	}

	/**
	 * AJAX endpoint for the listing-page AI chat that helps refine the announcement search.
	 *
	 * Request body: { message: string, history: [{role:'user'|'assistant', content:string}] }
	 * Response: { reply: string, suggested_query: string|null, search_url: string|null }
	 *
	 * The model is grounded in the announcement vector store (file_search). It must always
	 * return a single JSON object — the prose reply plus an optional refined search phrase
	 * that the front-end turns into a one-click "apply filter" action.
	 */
	public function actionChat()
	{
		Yii::$app->response->format = \yii\web\Response::FORMAT_JSON;

		$raw = Yii::$app->request->getRawBody();
		$payload = $raw ? json_decode($raw, true) : null;
		if (!is_array($payload)) {
			$payload = Yii::$app->request->post();
		}
		$message = trim((string) ($payload['message'] ?? ''));
		if ($message === '') {
			return ['error' => Yii::t('frontend', 'Please type a message.')];
		}

		// Resolve / create the AiConversation. Persistence is best-effort: if a DB write fails we still serve
		// the chat reply, the conversation just won't be retrievable later for analytics / fine-tuning.
		$conversationIdRaw = $payload['conversation_id'] ?? null;
		$conversationId = is_numeric($conversationIdRaw) ? (int) $conversationIdRaw : 0;
		$aiConversation = null;
		if ($conversationId > 0) {
			$aiConversation = AiConversation::find()
				->where([
					'id' => $conversationId,
					'status' => AiConversation::STATUS_ACTIVE,
					'deleted' => AiConversation::NO,
				])
				->one();
		}
		if ($aiConversation === null) {
			try {
				$aiConversation = new AiConversation();
				$aiConversation->status = AiConversation::STATUS_ACTIVE;
				$aiConversation->deleted = AiConversation::NO;
				$aiConversation->created_by = Yii::$app->user->isGuest ? null : (int) Yii::$app->user->id;
				// First user message doubles as a short summary for admin lists.
				$aiConversation->summary = mb_substr($message, 0, 250);
				if ($aiConversation->save(false)) {
					$conversationId = (int) $aiConversation->id;
				} else {
					$aiConversation = null;
				}
			} catch (\Throwable $e) {
				Yii::warning('AiConversation create failed: ' . $e->getMessage(), __METHOD__);
				$aiConversation = null;
			}
		}

		// Save the user turn immediately so we have it even if the OpenAI call fails downstream.
		if ($aiConversation !== null) {
			try {
				$userMsg = new AiMessage();
				$userMsg->conversation_id = (int) $aiConversation->id;
				$userMsg->role = AiMessage::ROLE_USER;
				$userMsg->content = $message;
				$userMsg->status = AiMessage::STATUS_COMPLETED;
				$userMsg->created_by = Yii::$app->user->isGuest ? null : (int) Yii::$app->user->id;
				$userMsg->save(false);
			} catch (\Throwable $e) {
				Yii::warning('AiMessage (user) save failed: ' . $e->getMessage(), __METHOD__);
			}
		}

		$history = [];
		if (!empty($payload['history']) && is_array($payload['history'])) {
			foreach ($payload['history'] as $turn) {
				if (!is_array($turn)) {
					continue;
				}
				$role = (string) ($turn['role'] ?? '');
				$content = trim((string) ($turn['content'] ?? ''));
				if (($role !== 'user' && $role !== 'assistant') || $content === '') {
					continue;
				}
				$history[] = [
					'role' => $role,
					'content' => mb_substr($content, 0, 4000),
				];
				if (count($history) >= 20) {
					break;
				}
			}
		}

		// KB resolved from the DB (the params id acts only as an optional override).
		$kb = OpenAiRecordVectorStoreService::resolveKnowledgeBase();

		$vectorStoreIds = [];
		if ($kb !== null && !empty($kb->vector_store_id)) {
			$vectorStoreIds[] = (string) $kb->vector_store_id;
		}

		// Optional Assistant configured in the backend: when one exists (param announcementChatAssistantId
		// or the default chat assistant), it drives the system prompt, model, temperature and the set of
		// vector stores searched. Falls back to the params-based config below when none is available.
		$assistant = Assistant::findChatAssistant();
		if ($assistant !== null) {
			$assistantStores = $assistant->collectVectorStoreIds();
			if ($assistantStores !== []) {
				$vectorStoreIds = $assistantStores;
			}
		}

		$contextLines = [];
		if (!empty($payload['context']) && is_array($payload['context'])) {
			$ctx = $payload['context'];
			$known = [
				'category' => 'categoria',
				'county' => 'judetul',
				'locality' => 'localitatea',
				'location' => 'locatia',
				'tag' => 'eticheta',
				'year' => 'anul',
				'limit' => 'limita',
			];
			foreach ($known as $key => $label) {
				$val = isset($ctx[$key]) ? trim((string) $ctx[$key]) : '';
				if ($val !== '') {
					$contextLines[] = $label . ': ' . mb_substr($val, 0, 100);
				}
			}
			$searchInUrl = '';
			foreach (['cautare', 'search'] as $sk) {
				$candidate = isset($ctx[$sk]) ? trim((string) $ctx[$sk]) : '';
				if ($candidate !== '') {
					$searchInUrl = $candidate;
					break;
				}
			}
			if ($searchInUrl !== '') {
				$contextLines[] = 'fraza de cautare curenta: ' . mb_substr($searchInUrl, 0, 200);
			}
			$pageTitle = isset($ctx['pageTitle']) ? trim((string) $ctx['pageTitle']) : '';
			if ($pageTitle !== '') {
				$contextLines[] = 'pagina: ' . mb_substr($pageTitle, 0, 200);
			}
		}
		$contextBlock = $contextLines !== []
			? "\n\nFiltrele active pe pagina utilizatorului (foloseste-le ca punct de start fara sa le repeti utilizatorului decat daca le confirmi sau le schimbi):\n- " . implode("\n- ", $contextLines)
			: '';

		// Default role used when no Assistant is configured in the backend (or it has no instructions).
		$defaultRole = "Esti un asistent care ajuta vizitatorii sa rafineze cautarea pe pagina de anunturi. "
			. "Vorbesti in limba utilizatorului (de obicei romana). "
			. "OBLIGATORIU: pentru ORICE intrebare in care utilizatorul descrie ce vrea sa cumpere / caute (chiar daca formularea e generala — ex. \"vreau materiale de constructii\", \"caut un utilaj\"), "
			. "apeleaza tool-ul `file_search` pe vector store inainte sa raspunzi. NU raspunde din cunostinte generale despre anunturile site-ului. "
			. "Singurele momente cand poti sari peste file_search sunt salutul / multumirea / o intrebare clara de clarificare ('ce judet?'). "
			. "Cere clarificari scurte cand intentia este complet vaga, dar nu intreba lucruri pe care utilizatorul le-a setat deja prin filtre. "
			. "FOARTE IMPORTANT — context cumulativ: intr-o conversatie cu mai multe mesaje, fiecare mesaj nou il interpretezi IMPREUNA cu cele anterioare, nu izolat. "
			. "Exemplu: utilizatorul cere 'caramida', apoi scrie 'de vanzare' — intentia este 'caramida de vanzare', NU orice anunt de vanzare. "
			. "Cand apelezi file_search, interogarea TREBUIE sa contina termenii specifici acumulati din toata conversatia (marca, tip obiect, locatie etc.), nu doar ultimul mesaj. "
			. "Cand intelegi suficient ce cauta utilizatorul, propune o fraza concisa de cautare (3-8 cuvinte cheie) pe care o vom aplica in listare.";

		// The base role comes from the backend-configured Assistant when available; the JSON output
		// contract below is ALWAYS appended because the response parsing depends on it.
		$baseInstructions = ($assistant !== null && trim((string) $assistant->instructions) !== '')
			? trim((string) $assistant->instructions)
			: $defaultRole;

		$jsonDirective = " Raspunde STRICT cu un obiect JSON valid, fara markdown, fara cod fenced, dupa schema: "
			. '{"reply": string, "suggested_query": string|null, "found": boolean, "relevant_ids": number[]}. '
			. 'Fiecare fragment returnat de file_search incepe cu o linie "record_id: N" — acela este ID-ul anuntului. '
			. 'In "relevant_ids" pui DOAR record_id-urile anunturilor care corespund EXACT cererii cumulate din conversatie, in ordinea relevantei. '
			. 'Fii strict la atribute precise: daca utilizatorul cere "3 usi" si un anunt are "4 usi", acel anunt NU se include, chiar daca e din aceeasi categorie. '
			. 'La fel pentru marca, culoare, locatie, dimensiuni etc. — atributul cerut trebuie sa se potriveasca explicit in textul anuntului. '
			. '"found" este true DOAR daca relevant_ids nu e gol; '
			. 'daca rezultatele sunt din alta categorie sau nu se potrivesc (ex. cauta "caramida de inchiriat" si gasesti doar apartamente), setezi found=false, relevant_ids=[] si suggested_query=null. '
			. 'Setezi "suggested_query" la o fraza concisa (3-8 cuvinte) ori de cate ori found=true — nu lasa null in acel caz. '
			. '"suggested_query" trebuie sa reflecte intentia COMPLETA acumulata din conversatie (ex. "caramida de vanzare"), niciodata doar ultimul mesaj eliptic. '
			. "Nu inventa anunturi care nu apar in vector store. Daca nu gasesti nimic relevant, spune-i utilizatorului si cere mai multe detalii.";

		$instructions = $baseInstructions . $jsonDirective . $contextBlock;

		$input = [];
		foreach ($history as $turn) {
			$input[] = $turn;
		}
		$input[] = ['role' => 'user', 'content' => $message];

		$model = $assistant !== null && !empty($assistant->model)
			? (string) $assistant->model
			: (string) (Yii::$app->params['announcementChatModel'] ?? 'gpt-4o-mini');

		// Reply cache stores the deterministic part of the answer (reply + suggested_query + relevant_ids).
		// The AI token (single-use lookup key) is always re-issued — it has its own short TTL and would otherwise
		// outlive its `ai_search:<token>` payload silently.
		$replyCacheTtl = (int) (Yii::$app->params['announcementChatCacheTtl'] ?? 3600);
		$replyCache = null;
		$replyCacheKey = null;
		if ($replyCacheTtl > 0 && Yii::$app->has('cache')) {
			$replyCache = Yii::$app->cache;
			$replyCacheKey = 'acr:' . sha1(implode('|', [
				OpenAiRecordVectorStoreService::currentSearchCacheGeneration(),
				$model,
				implode(',', $vectorStoreIds),
				$instructions,
				json_encode($input, JSON_UNESCAPED_UNICODE),
			]));
		}

		$reply = null;
		$suggestedQuery = null;
		$relevantIds = [];
		$found = true;

		$cached = $replyCache !== null && $replyCacheKey !== null ? $replyCache->get($replyCacheKey) : false;
		if (is_array($cached) && isset($cached['reply'])) {
			$reply = (string) $cached['reply'];
			$suggestedQuery = isset($cached['suggested_query']) && is_string($cached['suggested_query']) && $cached['suggested_query'] !== ''
				? (string) $cached['suggested_query']
				: null;
			$relevantIds = isset($cached['relevant_ids']) && is_array($cached['relevant_ids'])
				? array_values(array_map('intval', $cached['relevant_ids']))
				: [];
			$found = !isset($cached['found']) || $cached['found'] !== false;
		} else {
			try {
				// Use the raw payload so we can extract both the reply text AND the exact file_search chunks
				// the model retrieved. Running a separate searchAnnouncementIds() call afterwards is wrong:
				// it would hit the vector store with our stricter threshold/ranker config and return a different
				// set of IDs than the AI used to write the reply, producing a visible mismatch. Same single
				// call ⇒ same set ⇒ no drift.
				// file_search cap at the OpenAI hard limit (50). Cross-category noise is filtered downstream by the
				// relative-score floor, not by lowering the cap. Hitting the cap is the trigger for a supplementary
				// searchAnnouncementIds pass — see below.
				$fileSearchMaxResults = (int) (Yii::$app->params['announcementChatFileSearchMaxResults'] ?? 50);
				// GPT-5.x reasoning models reject the `temperature` parameter — send it only to legacy models.
				$temperature = str_starts_with($model, 'gpt-5')
					? null
					: ($assistant !== null && $assistant->temperature !== null && $assistant->temperature !== ''
						? (float) $assistant->temperature
						: 0.3);
				$rawResponse = OpenAIResponsesService::createResponseRaw(
					$model,
					$input,
					$instructions,
					$vectorStoreIds,
					$temperature,
					null,
					$fileSearchMaxResults,
					null
				);
			} catch (\Throwable $e) {
				Yii::error('Announcement chat failed: ' . $e->getMessage(), __METHOD__);
				return ['error' => Yii::t('frontend', 'AI service is temporarily unavailable.')];
			}

			$reply = trim(OpenAIResponsesService::extractOutputText($rawResponse));
			$json = $this->parseChatJson($reply);
			$modelIds = [];
			if ($json !== null) {
				$reply = trim((string) ($json['reply'] ?? $reply));
				$q = $json['suggested_query'] ?? null;
				if (is_string($q)) {
					$q = trim($q);
					if ($q !== '') {
						$suggestedQuery = mb_substr($q, 0, 200);
					}
				}
				// Explicit false only — missing/absent field keeps the legacy behavior (trust the chunks).
				if (array_key_exists('found', $json) && $json['found'] === false) {
					$found = false;
				}
				// The model picks the matching announcements itself (it sees `record_id: N` in every chunk and
				// can apply exact attribute constraints that embeddings cannot separate).
				if (isset($json['relevant_ids']) && is_array($json['relevant_ids'])) {
					foreach ($json['relevant_ids'] as $mid) {
						$mid = (int) $mid;
						if ($mid > 0 && !in_array($mid, $modelIds, true)) {
							$modelIds[] = $mid;
						}
					}
				}
			}
			if ($reply === '') {
				$reply = Yii::t('frontend', 'Could not generate a response. Try rephrasing your request.');
			}

			// The model judged the retrieved chunks irrelevant ("found": false) — do NOT surface IDs from them
			// and do NOT run fallback/supplement searches. Otherwise the listing contradicts the reply
			// ("nu am gasit nimic" while the grid fills with cross-category noise).
			if (!$found) {
				$suggestedQuery = null;
				$relevantIds = [];
				Yii::info('model reported found=false — skipping ID extraction and fallbacks', 'chat-debug');
			} else {
				// Map the file_search file_ids → announcement record_ids via the local index table.
				$fileSearch = OpenAIResponsesService::extractFileSearchResults($rawResponse);
				$fileSearchResults = $fileSearch['results'] ?? [];
				$fileSearchQueries = $fileSearch['queries'] ?? [];

				if ($suggestedQuery === null && !empty($fileSearchQueries)) {
					// Longest variant carries the most accumulated conversation context.
					$bestQ = '';
					foreach ($fileSearchQueries as $fsq) {
						$fsq = trim((string) $fsq);
						if (mb_strlen($fsq) > mb_strlen($bestQ)) {
							$bestQ = $fsq;
						}
					}
					if ($bestQ !== '') {
						$suggestedQuery = mb_substr($bestQ, 0, 200);
					}
				}

				// Preferred path: the model returned the matching record_ids explicitly. Validate them against
				// the IDs actually retrievable from the file_search chunks (anti-hallucination) and keep the
				// model's relevance order. When the model has spoken precisely, skip fallback/supplement —
				// they operate on embedding similarity and would re-add the near-duplicates the model excluded.
				$modelIdsValidated = [];
				if ($modelIds !== []) {
					$chunkIds = $this->resolveAnnouncementIdsFromFileSearch($fileSearchResults, $rawResponse);
					$chunkIdSet = array_flip($chunkIds);
					foreach ($modelIds as $mid) {
						if (isset($chunkIdSet[$mid])) {
							$modelIdsValidated[] = $mid;
						}
					}
					Yii::info('model relevant_ids=[' . implode(',', $modelIds) . '], validated=['
						. implode(',', $modelIdsValidated) . '] (chunk_ids=' . count($chunkIds) . ')', 'chat-debug');
				}

				if ($modelIdsValidated !== []) {
					$relevantIds = $modelIdsValidated;
				} else {
					$filteredFileSearchResults = $this->filterFileSearchResultsByRelativeScore($fileSearchResults);
					$relevantIds = $this->resolveAnnouncementIdsFromFileSearch($filteredFileSearchResults, $rawResponse);
				}

				// Fallback: direct vector search when file_search didn't yield mappable IDs (model skipped tool,
				// relative filter emptied the set, or RecordVectorIndex is out of sync). Use suggested_query,
				// file_search queries, or the user's message — not only suggested_query.
				if ($relevantIds === [] && OpenAiRecordVectorStoreService::isConfigured()) {
					$fallbackQuery = $this->resolveChatSearchQuery($message, $suggestedQuery, $fileSearchQueries, $history !== []);
					if ($fallbackQuery !== '') {
						try {
							$fbAudit = null;
							$relevantIds = OpenAiRecordVectorStoreService::searchAnnouncementIds($fallbackQuery, null, $fbAudit, 0.1);
							Yii::info('fallback searchAnnouncementIds returned ' . count($relevantIds)
								. ' ids for "' . $fallbackQuery . '"', 'chat-debug');
						} catch (\Throwable $e) {
							Yii::warning('Chat fallback search failed: ' . $e->getMessage(), __METHOD__);
						}
					}
				}

				// Supplementary "extra cautari": file_search hit the cap → there were probably more matches
				// past position 50 that we're missing. Run a direct vector search with a relaxed threshold and merge
				// any unique IDs at the tail. file_search rank order stays in front, supplementary order at the back.
				// Skipped when the model hand-picked the IDs — merging similarity-based extras would dilute its precision.
				$rawFileSearchCount = count($fileSearchResults);
				$supplementQuery = $this->resolveChatSearchQuery($message, $suggestedQuery, $fileSearchQueries, $history !== []);
				$hitCap = $modelIdsValidated === []
					&& $supplementQuery !== ''
					&& $rawFileSearchCount >= $fileSearchMaxResults
					&& OpenAiRecordVectorStoreService::isConfigured();
				if ($hitCap) {
					try {
						$supplementThreshold = (float) (Yii::$app->params['announcementChatSupplementScoreThreshold'] ?? 0.1);
						$extraAudit = null;
						$extraIds = OpenAiRecordVectorStoreService::searchAnnouncementIds(
							$supplementQuery,
							null,
							$extraAudit,
							$supplementThreshold
						);
						$existing = array_flip($relevantIds);
						$added = 0;
						foreach ($extraIds as $eid) {
							if (!isset($existing[$eid])) {
								$relevantIds[] = (int) $eid;
								$existing[$eid] = true;
								$added++;
							}
						}
						Yii::info('supplement: cap=' . $fileSearchMaxResults . ', file_search_raw=' . $rawFileSearchCount
							. ', supplement_total=' . count($extraIds) . ', added=' . $added
							. ' (threshold=' . $supplementThreshold . ')', 'chat-debug');
					} catch (\Throwable $e) {
						Yii::warning('Chat supplement search failed: ' . $e->getMessage(), __METHOD__);
					}
				} else {
					Yii::info('supplement: skipped (cap=' . $fileSearchMaxResults . ', file_search_raw=' . $rawFileSearchCount . ')', 'chat-debug');
				}

				// Keep only IDs the listing would actually render right now (active announcement + active subscription).
				if ($relevantIds !== []) {
					$relevantIds = $this->filterListingDisplayableIds($relevantIds);
				}
			}

			if ($replyCache !== null && $replyCacheKey !== null) {
				$replyCache->set($replyCacheKey, [
					'reply' => $reply,
					'suggested_query' => $suggestedQuery,
					'relevant_ids' => $relevantIds,
					'found' => $found,
				], $replyCacheTtl);
			}
		}

		// The user-facing reply is always just the result count (fixed wording, with diacritics).
		// The model's prose stays in the reply cache / logs but is never shown.
		// Romanian numeral rule: 20+ takes "de" before the noun ("25 de anunțuri").
		$idCount = count($relevantIds);
		if ($idCount === 0) {
			$reply = 'Niciun anunț găsit.';
		} elseif ($idCount === 1) {
			$reply = '1 anunț găsit.';
		} elseif ($idCount < 20) {
			$reply = $idCount . ' anunțuri găsite.';
		} else {
			$reply = $idCount . ' de anunțuri găsite.';
		}

		// Issue a fresh ai token on every turn — also when nothing was found (empty `ids`). The listing
		// interprets an empty-ids token as "show zero results", keeping the grid consistent with the
		// chat reply instead of silently falling back to the unfiltered listing.
		$searchUrl = null;
		$aiToken = null;
		if (Yii::$app->has('cache')) {
			$aiToken = bin2hex(random_bytes(6));
			$tokenTtl = (int) (Yii::$app->params['announcementChatAiTokenTtl'] ?? 1800);
			Yii::$app->cache->set('ai_search:' . $aiToken, [
				'query' => $suggestedQuery,
				'ids' => $relevantIds,
				'gen' => OpenAiRecordVectorStoreService::currentSearchCacheGeneration(),
			], max(60, $tokenTtl));
			$searchUrl = Url::to(['/announcement/default/index', 'ai' => $aiToken]);
		}
		Yii::info('result: relevant_ids=' . count($relevantIds)
			. ', suggested_query=' . ($suggestedQuery !== null ? '"' . $suggestedQuery . '"' : 'null')
			. ', search_url=' . ($searchUrl !== null ? 'set' : 'null')
			. ', conversation_id=' . ($conversationId > 0 ? $conversationId : 'none'), 'chat-debug');

		// Save the assistant turn together with the metadata that matters for later fine-tuning:
		// suggested_query (what the model thought we should search for) and the relevant_ids (what we
		// actually surfaced in the listing). Encoded into the content column as JSON so we don't need a
		// schema change; the chat reply prose stays accessible via the `reply` field.
		if ($aiConversation !== null) {
			try {
				$assistantContent = json_encode([
					'reply' => $reply,
					'suggested_query' => $suggestedQuery,
					'relevant_ids' => $relevantIds,
				], JSON_UNESCAPED_UNICODE);
				$assistantMsg = new AiMessage();
				$assistantMsg->conversation_id = (int) $aiConversation->id;
				$assistantMsg->role = AiMessage::ROLE_ASSISTANT;
				$assistantMsg->content = $assistantContent !== false ? $assistantContent : $reply;
				$assistantMsg->status = AiMessage::STATUS_COMPLETED;
				$assistantMsg->completed_at = (new \DateTime())->format('Y-m-d H:i:s');
				$assistantMsg->save(false);
			} catch (\Throwable $e) {
				Yii::warning('AiMessage (assistant) save failed: ' . $e->getMessage(), __METHOD__);
			}
		}

		return [
			'reply' => $reply,
			'suggested_query' => $suggestedQuery,
			'search_url' => $searchUrl,
			'ai_token' => $aiToken,
			'relevant_ids' => $relevantIds,
			'found' => $found,
			'conversation_id' => $conversationId > 0 ? $conversationId : null,
		];
	}

	/**
	 * Intersect a list of announcement IDs with the rows the public listing would actually show right now
	 * (active announcement + active subscription). Preserves the input order — AI ranking stays intact.
	 *
	 * @param int[] $ids
	 * @return int[]
	 */
	protected function filterListingDisplayableIds(array $ids): array
	{
		$ids = array_values(array_unique(array_map('intval', array_filter($ids))));
		if ($ids === []) {
			return [];
		}
		$rows = Announcement::find()
			->alias('a')
			->joinWith(['subscriptions s'], false)
			->where([
				'a.id' => $ids,
				'a.status' => Announcement::STATUS_ACTIVE,
				'a.deleted' => Announcement::NO,
				's.status' => Announcement::STATUS_ACTIVE,
				's.deleted' => Announcement::NO,
			])
			->select(['a.id'])
			->groupBy(['a.id'])
			->column();
		if ($rows === []) {
			return [];
		}
		$valid = array_flip(array_map('intval', $rows));
		return array_values(array_filter($ids, static function ($id) use ($valid) {
			return isset($valid[$id]);
		}));
	}

	/**
	 * Relative-score filter on file_search chunks. If the filter would drop everything but raw results
	 * exist, keep the unfiltered set so we still get listing IDs.
	 *
	 * @param array[] $fileSearchResults
	 * @return array[]
	 */
	protected function filterFileSearchResultsByRelativeScore(array $fileSearchResults): array
	{
		if ($fileSearchResults === []) {
			return [];
		}
		$relFactor = (float) (Yii::$app->params['announcementChatFileSearchRelativeFloor'] ?? 0.6);
		if ($relFactor <= 0.0) {
			return $fileSearchResults;
		}

		$topScore = null;
		foreach ($fileSearchResults as $r) {
			if (is_array($r) && isset($r['score'])) {
				$s = (float) $r['score'];
				if ($topScore === null || $s > $topScore) {
					$topScore = $s;
				}
			}
		}
		$cutoff = $topScore !== null ? $topScore * max(0.0, min(1.0, $relFactor)) : null;
		if ($cutoff === null) {
			return $fileSearchResults;
		}

		$kept = [];
		$keptScores = [];
		$droppedScores = [];
		foreach ($fileSearchResults as $r) {
			if (!is_array($r)) {
				continue;
			}
			$score = isset($r['score']) ? (float) $r['score'] : null;
			if ($score !== null && $score < $cutoff) {
				$droppedScores[] = round($score, 3);
				continue;
			}
			$kept[] = $r;
			if ($score !== null) {
				$keptScores[] = round($score, 3);
			}
		}
		if ($kept === [] && $fileSearchResults !== []) {
			Yii::info('file_search: relative filter dropped all ' . count($fileSearchResults)
				. ' results (top=' . round($topScore, 3) . ', cutoff=' . round($cutoff, 3)
				. ') — keeping unfiltered set', 'chat-debug');
			return $fileSearchResults;
		}
		Yii::info('file_search: kept ' . count($kept) . ' / ' . count($fileSearchResults)
			. ' (top=' . round($topScore, 3)
			. ', cutoff=' . round($cutoff, 3)
			. ', kept_scores=[' . implode(',', $keptScores) . ']'
			. ', dropped_scores=[' . implode(',', $droppedScores) . '])', 'chat-debug');
		return $kept;
	}

	/**
	 * Resolve announcement IDs from file_search chunks: vector-store attributes, indexed plain-text, DB map, citations.
	 *
	 * @param array[] $fileSearchResults
	 * @return int[]
	 */
	protected function resolveAnnouncementIdsFromFileSearch(array $fileSearchResults, array $rawResponse): array
	{
		$ids = [];
		$seen = [];
		$append = static function (int $id) use (&$ids, &$seen): void {
			if ($id <= 0 || isset($seen[$id])) {
				return;
			}
			$seen[$id] = true;
			$ids[] = $id;
		};

		foreach ($fileSearchResults as $r) {
			if (!is_array($r)) {
				continue;
			}
			$fromChunk = $this->extractRecordIdFromFileSearchChunk($r);
			if ($fromChunk !== null) {
				$append($fromChunk);
			}
		}

		$fileIds = [];
		$seenFile = [];
		foreach ($fileSearchResults as $r) {
			if (!is_array($r)) {
				continue;
			}
			$fid = trim((string) ($r['file_id'] ?? ''));
			if ($fid === '' || isset($seenFile[$fid])) {
				continue;
			}
			$seenFile[$fid] = true;
			$fileIds[] = $fid;
		}
		foreach ($this->mapOpenAiFileIdsToAnnouncementIds($fileIds) as $id) {
			$append($id);
		}

		if ($ids === []) {
			foreach (OpenAIResponsesService::extractCitedFileIds($rawResponse) as $fid) {
				foreach ($this->mapOpenAiFileIdsToAnnouncementIds([$fid]) as $id) {
					$append($id);
				}
			}
		}

		return $ids;
	}

	/**
	 * @param array<string, mixed> $chunk
	 */
	protected function extractRecordIdFromFileSearchChunk(array $chunk): ?int
	{
		$attrs = $chunk['attributes'] ?? null;
		if (is_array($attrs) && isset($attrs['record_id'])) {
			$id = (int) $attrs['record_id'];
			return $id > 0 ? $id : null;
		}
		$text = (string) ($chunk['text'] ?? '');
		if ($text !== '' && preg_match('/(?:^|\n)record_id:\s*(\d+)/i', $text, $m)) {
			$id = (int) $m[1];
			return $id > 0 ? $id : null;
		}
		return null;
	}

	/**
	 * @param string[] $fileIds
	 * @return int[]
	 */
	protected function mapOpenAiFileIdsToAnnouncementIds(array $fileIds): array
	{
		$fileIds = array_values(array_unique(array_filter(array_map('strval', $fileIds))));
		if ($fileIds === []) {
			return [];
		}
		$rows = RecordVectorIndex::find()
			->select(['openai_file_id', 'record_id'])
			->where([
				'openai_file_id' => $fileIds,
				'deleted' => RecordVectorIndex::NO,
				'status' => RecordVectorIndex::STATUS_ACTIVE,
			])
			->asArray()
			->all();
		$byFile = [];
		foreach ($rows as $row) {
			$byFile[(string) $row['openai_file_id']] = (int) $row['record_id'];
		}
		$ids = [];
		foreach ($fileIds as $fid) {
			if (isset($byFile[$fid])) {
				$ids[] = $byFile[$fid];
			}
		}
		return array_values(array_unique($ids));
	}

	/**
	 * Best query string for fallback vector search: model phrase → file_search queries → user message.
	 *
	 * The raw user message is used only on the FIRST turn ($hasHistory = false). Follow-up messages are
	 * usually elliptical ("de vanzare", "mai ieftin") — searching with them alone drops the accumulated
	 * context and floods the listing with unrelated categories.
	 *
	 * @param string[] $fileSearchQueries
	 */
	protected function resolveChatSearchQuery(string $message, ?string $suggestedQuery, array $fileSearchQueries = [], bool $hasHistory = false): string
	{
		if ($suggestedQuery !== null) {
			$suggestedQuery = trim($suggestedQuery);
			if ($suggestedQuery !== '') {
				return mb_substr($suggestedQuery, 0, 200);
			}
		}
		// Prefer the longest file_search query — the model often issues several variants and the
		// longest one carries the most accumulated context.
		$best = '';
		foreach ($fileSearchQueries as $q) {
			$q = trim((string) $q);
			if (mb_strlen($q) > mb_strlen($best)) {
				$best = $q;
			}
		}
		if ($best !== '') {
			return mb_substr($best, 0, 200);
		}
		if (!$hasHistory) {
			$message = trim($message);
			if ($message !== '') {
				return mb_substr($message, 0, 200);
			}
		}
		return '';
	}

	/**
	 * Best-effort decode of a JSON object returned by the model — tolerates ```json fenced blocks
	 * and stray prose around it.
	 */
	protected function parseChatJson(string $text): ?array
	{
		$text = trim($text);
		if ($text === '') {
			return null;
		}
		if (preg_match('/```(?:json)?\s*(\{.*?\})\s*```/s', $text, $m)) {
			$text = $m[1];
		}
		$decoded = json_decode($text, true);
		if (is_array($decoded)) {
			return $decoded;
		}
		$start = strpos($text, '{');
		$end = strrpos($text, '}');
		if ($start !== false && $end !== false && $end > $start) {
			$candidate = substr($text, $start, $end - $start + 1);
			$decoded = json_decode($candidate, true);
			if (is_array($decoded)) {
				return $decoded;
			}
		}
		return null;
	}


	/**
	 * Finds the Announcement model based on its slug value.
	 * If the model is not found, a 404 HTTP exception will be thrown.
	 *
	 * @param string $slug
	 * @return array|\yii\db\ActiveRecord|Announcement
	 * @throws NotFoundHttpException if the model cannot be found
	 */
	protected function findModel($slug)
	{
		$model = Announcement::find()
			->alias('a')
			->joinWith([
				'announcementTranslations at' => function (ActiveQuery $query) {
					$query->andOnCondition(['at.language_id' => Yii::$app->language]);
				},
			])
			->where([
				'at.slug' => $slug,
				'a.deleted' => Announcement::NO,
			])
			->andWhere([
				'OR',
				['=', 'a.status', Announcement::STATUS_ACTIVE],
				['=', 'a.created_by', Yii::$app->user->id],
			])
			->limit(1);

		if (($model = $model->one()) !== null) {
			return $model;
		}

		throw new NotFoundHttpException(Yii::t('common', 'The requested page does not exist.'));
	}

	/**
	 * Returns the JavaScript API file.
	 *
	 * @return mixed
	 */
	public function actionEmbed()
	{
		return Yii::$app->response->sendFile(Yii::getAlias('@announcement/web/js/embed.js'));
	}
}
