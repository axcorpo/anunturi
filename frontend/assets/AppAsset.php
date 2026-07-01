<?php

namespace frontend\assets;

use yii\web\AssetBundle;

class AppAsset extends AssetBundle
{
	/**
	 * @inheritdoc
	 */
	public $basePath = '@webroot';

	/**
	 * @inheritdoc
	 */
	public $baseUrl = '@web';

	/**
	 * Theme
	 */
	public $theme;

	/**
	 * @inheritdoc
	 */
	public $css = [
        'fonts/muli/style.css',
        'fonts/poppins/style.css',
        'fonts/montserrat/style.css',
        'fonts/herr-von-muellerhoff/style.css',
		'plugins/font-awesome/css/font-awesome.min.css',
		'plugins/listtyicons/style.css',
		'plugins/bootstrapthumbnail/bootstrap-thumbnail.css',
		'plugins/select2/css/select2.min.css',
		'plugins/selectbox/select_option1.css',
		'plugins/owl-carousel/owl.carousel.min.css',
		'plugins/fancybox/jquery.fancybox.min.css',
		'plugins/isotope/isotope.min.css',
		'plugins/rateyo/jquery.rateyo.min.css',
		'plugins/animate/animate.css',
		'plugins/bootstrap-multiselect/bootstrap-multiselect.css',
	];

	/**
	 * @inheritdoc
	 */
	public $js = [
		'plugins/counter-up/jquery.counterup.min.js',
		'plugins/selectbox/jquery.selectbox-0.1.3.min.js',
		'plugins/owl-carousel/owl.carousel.min.js',
		'plugins/slick/slick.min.js',
		'plugins/isotope/isotope.min.js',
		'plugins/isotope/isotope-triger.min.js',
		'plugins/rateyo/jquery.rateyo.min.js',
		'plugins/bootstrap-multiselect/bootstrap-multiselect.js',
		'js/main-app.js',
		'js/custom.js',
		'js/socialshare.js',
	];

	/**
	 * @inheritdoc
	 */
	public $depends = [
		'yii\web\YiiAsset',
		'yii\bootstrap\BootstrapPluginAsset',
		'frontend\assets\NpmAsset',
		'frontend\assets\BowerAsset',
	];

	/**
	 * Initialize the asset bundle
	 */
	public function init()
	{
		parent::init();

		$theme = \Yii::$app->settings->get('theme') ?: 'default';
		
		// Load theme-specific CSS files with numbered prefixes
		$themeCss = $this->getThemeCssFiles($theme);
		
		// Find position to insert theme CSS (after bootstrap-multiselect.css)
		$index = array_search('plugins/bootstrap-multiselect/bootstrap-multiselect.css', $this->css);
		
		if ($index !== false) {
			// Insert theme CSS right after bootstrap-multiselect.css
			array_splice($this->css, $index + 1, 0, $themeCss);
		} else {
			// If not found, append to the end
			$this->css = array_merge($this->css, $themeCss);
		}
	}
	
	/**
	 * Get theme CSS files in order based on numeric prefix
	 * @param string $theme Theme name
	 * @return array Array of CSS file paths
	 */
	protected function getThemeCssFiles($theme)
	{
		$themePath = \Yii::getAlias('@webroot') . "/tpl/{$theme}/css";
		$cssFiles = [];
		
		if (!is_dir($themePath)) {
			return $cssFiles;
		}
		
		// Scan directory for CSS files with _##_ prefix pattern
		$files = scandir($themePath);
		$orderedFiles = [];
		
		foreach ($files as $file) {
			// Match pattern: _##_name.css where ## is a number
			if (preg_match('/^_(\d+)_(.+)\.css$/', $file, $matches)) {
				$order = (int)$matches[1];
				$orderedFiles[$order] = "/tpl/{$theme}/css/{$file}";
			}
		}
		
		// Sort by numeric prefix
		ksort($orderedFiles);
		
		return array_values($orderedFiles);
	}
}
