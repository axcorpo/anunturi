generateUrl = function(url, opt) {
	var prop, arg, arg_ne;
	for (prop in opt) {
		arg = '{' + prop + '}';
		if (url.indexOf(arg) !== -1) {
			url = url.replace(new RegExp(arg, 'g'), encodeURIComponent(opt[prop]));
		}
		arg_ne = '{' + prop + '-ne}';
		if (url.indexOf(arg_ne) !== -1) {
			url = url.replace(new RegExp(arg_ne, 'g'), opt[prop]);
		}
	}
	return this.cleanUrl(url);
};
cleanUrl = function(fullUrl) {
	fullUrl = fullUrl.replace(/\{([^{}]*)}/g, '');
	var params = fullUrl.match(/[^\=\&\?]+=[^\=\&\?]+/g),
		url = fullUrl.split("?")[0];
	if (params && params.length > 0) {
		url += "?" + params.join("&");
	}
	return url;
};

doPopup = function(e, url) {
	e = (e ? e : window.event);
	var t = (e.target ? e.target : e.srcElement),
		width = t.data - width || 800,
		height = t.data - height || 500;
	var
		px = Math.floor(((screen.availWidth || 1024) - width) / 2),
		py = Math.floor(((screen.availHeight || 700) - height) / 2);
	var params = {
		url: $('#input_url').val(),
		img: $('#input_img').val(),
		title: $('#input_title').val(),
		desc: $('#input_desc').val(),
		redirect_url: $('#input_redirect_url').val(),
		app_id: $('#input_app_id').val(),
		via: $('#input_via').val(),
		hashtags: $('#input_hashtags').val()
	};
	var popup = window.open(generateUrl(url, params), "social", "width=" + width + ",height=" + height + ",left=" + px + ",top=" + py + ",location=0,menubar=0,toolbar=0,status=0,scrollbars=1,resizable=1");
	if (popup) {
		popup.focus();
		if (e.preventDefault) e.preventDefault();
		e.returnValue = false;
	}
	return !!popup;
};
$(document).on('click', '.social-share', function (e) {
	doPopup(e, $(this).attr('href'));
});