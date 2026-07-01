<?php
return [
	// This parameter is used to remove a specific URL segment in order to get the right uploads URL
	'upload.ignoredBasePaths' => ['admin', 'dashboard'],
    'image.extensions' => ['jpeg', 'jpg', 'png', 'gif'],
    'image.mimeTypes' => ['image/jpeg', 'image/jpg', 'image/png', 'image/gif'],
    // Not begin with dot and allow only specific characters
    'sanitize.pattern' => '/^(?!^\.)[a-zA-Z 0-9\/\.\,\:\+\-\_\@\[\]\(\)\{\}\?\!\&\'(\r\n|\r|\n)]*$/i',
    'sanitize.allowedHtmlTags' => ['strong', 'em', 'ul', 'ol', 'li', 'br', 'hr', 'p', 'div', 'span'],
    'sanitize.allowedHtmlAttributes' => ['class', 'id', 'style', 'data-', 'aria-'],
	// SPV
	'spv.clientId' => '94f289f2be860d57fb8deec7746a7e8a7e3ee71d9f941767',
	'spv.clientSecret' => 'e0f4a350f6eb2c110464189cdba55cc2b17b2a2db35c7e8a7e3ee71d9f941767',
	'spv.redirect' => 'https://www.econstructii.ro/spv',
];
