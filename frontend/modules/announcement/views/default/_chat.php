<?php

/* @var $this \yii\web\View */

use frontend\modules\announcement\assets\ChatAsset;
use yii\helpers\Json;
use yii\helpers\Url;

ChatAsset::register($this);

$chatUrl = Url::to(['/announcement/default/chat']);

// Pick the accent color from the theme stored in settings — each frontend theme uses a different hue
// for `.sidebarInner .panel-heading.categories-accordion` and `.btn-primary`. Falls back to default orange.
$themeAccents = [
	'default' => ['accent' => '#ffa019', 'hover' => '#e58c0e'],
	'red'     => ['accent' => '#ec2227', 'hover' => '#c81b1f'],
	'blue'    => ['accent' => '#0278c2', 'hover' => '#02639f'],
];
$themeName = (string) (Yii::$app->settings->get('theme') ?: 'default');
$accent = $themeAccents[$themeName] ?? $themeAccents['default'];
?>
<style>
#announcement-chat,
#announcement-chat-toggle,
#announcement-chat-toggle-bubble {
	--chat-accent: <?= $accent['accent'] ?>;
	--chat-accent-hover: <?= $accent['hover'] ?>;
}
</style>

<span id="announcement-chat-toggle-bubble" class="announcement-chat-toggle-bubble"><?= Yii::t('frontend', 'Search with AI') ?></span>
<button type="button" id="announcement-chat-toggle" class="announcement-chat-toggle" aria-label="<?= htmlspecialchars(Yii::t('frontend', 'Search with AI'), ENT_QUOTES, 'UTF-8') ?>">
	<i class="fa fa-reddit-alien"></i>
</button>

<div class="announcement-chat" id="announcement-chat">
	<div class="announcement-chat-header">
		<span class="announcement-chat-title">
			<?= Yii::t('frontend', 'Search with AI') ?>
		</span>
		<span class="announcement-chat-actions">
			<button type="button" class="announcement-chat-reset" title="<?= htmlspecialchars(Yii::t('frontend', 'Reset conversation'), ENT_QUOTES, 'UTF-8') ?>">
				<i class="fa fa-refresh"></i>
			</button>
			<button type="button" class="announcement-chat-close" title="<?= htmlspecialchars(Yii::t('frontend', 'Close'), ENT_QUOTES, 'UTF-8') ?>">
				<i class="fa fa-chevron-down"></i>
			</button>
		</span>
	</div>
	<div class="announcement-chat-body"></div>
	<div class="announcement-chat-input-row">
		<input type="text" class="announcement-chat-input" placeholder="..." maxlength="500">
		<button type="button" class="announcement-chat-mic" title="<?= htmlspecialchars(Yii::t('frontend', 'Voice input'), ENT_QUOTES, 'UTF-8') ?>"><i class="fa fa-microphone"></i></button>
		<button type="button" class="announcement-chat-send"><i class="fa fa-paper-plane"></i></button>
	</div>
</div>
<?php
$config = Json::htmlEncode([
	'root' => '#announcement-chat',
	'toggle' => '#announcement-chat-toggle',
	'bubble' => '#announcement-chat-toggle-bubble',
	'url' => $chatUrl,
	'language' => str_starts_with(strtolower((string) Yii::$app->language), 'ro') ? 'ro-RO' : (string) Yii::$app->language,
]);
$this->registerJs("(function(){ if (window.AnnouncementChat) { new AnnouncementChat({$config}).init(); } })();");
?>
