<?php

/* @var $this yii\web\View */

use yii\web\View;

$settings = Yii::$app->settings->getAll();
?>

</div>

<?php if ($settings['seo']['googleAnalytics']) : ?>
	<script>
		(function(i,s,o,g,r,a,m){i['GoogleAnalyticsObject']=r;i[r]=i[r]||function(){
			(i[r].q=i[r].q||[]).push(arguments)},i[r].l=1*new Date();a=s.createElement(o),
			m=s.getElementsByTagName(o)[0];a.async=1;a.src=g;m.parentNode.insertBefore(a,m)
		})(window,document,'script','https://www.google-analytics.com/analytics.js','ga');

		ga('create', '<?= $settings['seo']['googleAnalytics'] ?>', 'auto');
		ga('send', 'pageview');
	</script>
<?php endif; ?>

<?php if ($settings['socialNetwork']['facebookPixelId']) : ?>
	<script>
		!function(f,b,e,v,n,t,s)
		{if(f.fbq)return;n=f.fbq=function(){n.callMethod?
			n.callMethod.apply(n,arguments):n.queue.push(arguments)};
			if(!f._fbq)f._fbq=n;n.push=n;n.loaded=!0;n.version='2.0';
			n.queue=[];t=b.createElement(e);t.async=!0;
			t.src=v;s=b.getElementsByTagName(e)[0];
			s.parentNode.insertBefore(t,s)}(window,document,'script',
			'https://connect.facebook.net/en_US/fbevents.js');
		fbq('init', '<?= $settings['socialNetwork']['facebookPixelId'] ?>');
		fbq('track', 'PageView');
	</script>
	<noscript>
		<img height="1" width="1" src="https://www.facebook.com/tr?id=<?= $settings['socialNetwork']['facebookPixelId'] ?>&ev=PageView&noscript=1"/>
	</noscript>
<?php endif; ?>

<?php if ($settings['general']['reCaptchaSiteKey']) : ?>
    <script src="https://www.google.com/recaptcha/api.js?onload=onloadCallback" async defer></script>
    <script>
        var onloadCallback = function() {
            grecaptcha.execute();
        };

        function setResponse(response) {
            document.getElementById('captcha-response').value = response;
        }
    </script>
<?php endif; ?>

<?= Yii::$app->settings->get('footerContent', 'script') ?>
