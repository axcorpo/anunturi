/**
 * Announcement Form App
 */
(function ($, window, document, undefined) {
	this.announcementFormApp = {
		/**
		 * Initialization.
		 */
		init: function () {
			this._cacheElements();
			this._bindEvents();

			this.checkForStickySidebar();
		},

		/**
		 * Caches DOM elements.
		 *
		 * @private
		 */
		_cacheElements: function () {
			this.$window = $(window);
			this.$document = $(document);
			this.$html = $('html');
			this.$body = this.$html.children('body');

			this.$pageHeader = this.$body.find('#page-header');
			this.$pageFooter = this.$body.find('#page-footer');
			this.$navbarTop = this.$pageHeader.find('.navbar-top');
			this.$form = this.$body.find('#announcement-form');
			this.$announcementFormControl = this.$form.find('#announcement-form-control');
			this.$stepsContainer = this.$form.find('#steps-container');
			this.$summaryList = this.$form.find('.summary-list');
		},

		/**
		 * Binds events.
		 *
		 * @private
		 */
		_bindEvents: function () {
			this.$window.on('scroll.announcementFormApp', this._onWindowScroll.bind(this));
			this.$window.on('resize.announcementFormApp', this._onWindowResize.bind(this));
			this.$document.on('click.announcementFormApp', '[data-scrollto-step]', this._onScrollToStepClick.bind(this));
			this.$document.on('change.announcementFormApp', '.step-form-control', this._onStepFormControlChange.bind(this));
			this.$form.on('submit.announcementFormApp', this._onFormSubmit.bind(this));
		},

		/**
		 * Handles window scroll event.
		 *
		 * @param e
		 * @private
		 */
		_onWindowScroll: function (e) {
			var me = this;

			if (this._windowScrollTimeout) {
				clearTimeout(this._windowScrollTimeout);
			}
			this._windowScrollTimeout = setTimeout(function () {
				me.checkForStickySidebar();
			}, 16);
		},

		/**
		 * Handles window resize event.
		 *
		 * @param e
		 * @private
		 */
		_onWindowResize: function (e) {
			var me = this;

			if (this._windowResizeTimeout) {
				clearTimeout(this._windowResizeTimeout);
			}
			this._windowResizeTimeout = setTimeout(function () {
				me.checkForStickySidebar();
			}, 16);
		},

		/**
		 * Handles data-scrollto-step element click event.
		 *
		 * @param e
		 * @private
		 */
		_onScrollToStepClick: function (e) {
			var $this = $(e.currentTarget),
				thisData = $this.data(),
				$target = this.$stepsContainer.find('[data-step="' + thisData.scrolltoStep + '"]');

			if ($target.length) {
				this.scrollTo($target);
			}

			e.preventDefault();
		},

		/**
		 * Handles step form control element change event.
		 *
		 * @param e
		 * @private
		 */
		_onStepFormControlChange: function (e) {
			var $formControl = $(e.currentTarget),
				$step = $formControl.closest('[data-step]'),
				$nextStep = $step.next('[data-step]');

			$step.removeClass('has-error');

			if ($nextStep.length) {
				this.scrollTo($nextStep);
				$nextStep.find('.collapse').collapse('show');
			}
			this.updatePackagesPrices();
			this.updateSummaryList();
		},

		/**
		 * Handles form submit event.
		 *
		 * @param e
		 * @private
		 */
		_onFormSubmit: function (e) {
			var $steps = this.$stepsContainer.find('[data-step]'),
				errors = [];

			$steps.each(function (index, step) {
				var $step = $(step),
					stepData = $step.data(),
					$formControls = $step.find('.step-form-control'),
					hasError = false;

				if ($formControls.filter(':radio').length || $formControls.filter(':checkbox').length) {
					hasError = !$formControls.filter(':checked').length;
				} else {
					hasError = !$formControls.val();
				}

				$step.toggleClass('has-error', hasError);
				if (hasError) {
					errors.push(stepData.step);
				}
			});

			if (errors.length) {
				this.scrollTo($steps.filter('.has-error').first());
				this.$form.find(':submit').prop('disabled', false);
				e.preventDefault();
			}
		},

		/**
		 * Updates and animates the scroll position.
		 *
		 * @param target
		 */
		scrollTo: function (target) {
			mainApp.scrollTo(target, this.$navbarTop.outerHeight() + 14, 500);
		},

		/**
		 * Checks for sticky sidebar.
		 */
		checkForStickySidebar: function () {
			var $stickySidebar = this.$body.find('.sticky-sidebar'),
				windowScrollTop = this.$window.scrollTop(),
				isSticky = (windowScrollTop + 90) >= this.$form.offset().top;

			if (this.$window.outerWidth() < 992 || !isSticky) {
				$stickySidebar.removeClass('on').css({
					width: 'auto',
					top: 'auto'
				});
				return;
			}

			// https://stackoverflow.com/a/8886696
			var navbarTopHeight = this.$navbarTop.outerHeight() + 16,
				stickySidebarHeight = $stickySidebar.outerHeight(),
				topOfFooter = this.$pageFooter.position().top,
				scrollDistanceFromTopOfDoc = windowScrollTop + navbarTopHeight + stickySidebarHeight,
				scrollDistanceFromTopOfFooter = (scrollDistanceFromTopOfDoc - topOfFooter) + 16,
				top = navbarTopHeight;

			if (scrollDistanceFromTopOfDoc > topOfFooter) {
				top -= scrollDistanceFromTopOfFooter;
			}

			$stickySidebar.toggleClass('on', isSticky).css({
				width: $stickySidebar.closest('.sticky-sidebar-container').width(),
				top: top
			});
		},

		/**
		 * Gets the current quantity form control.
		 *
		 * @return {*|jQuery|HTMLElement}
		 */
		getQuantityFormControl: function () {
			var $quantityStep = this.$stepsContainer.find('[data-step-type="quantity"]'),
				$formControls = $quantityStep.find('.step-form-control');

			if ($formControls.length === 1 && $formControls.hasClass('step-quantity-custom-input')) {
				return $formControls.eq(0);
			}
			return $formControls.filter(':checked');
		},

		/**
		 * Gets the total price of all selected options.
		 *
		 * @return {number}
		 */
		getAnnouncementPrice: function () {
			return (+this.$announcementFormControl.data('price') || 0);
		},

		/**
		 * Gets the total price of all selected options.
		 *
		 * @return {number}
		 */
		getOptionsPrice: function () {
			var amount = 0;
			var data = [];
			this.$stepsContainer.find('[data-step]').each(function(i, step) {
				$(step).find("input[name*='options']").each(function (j, input) {
					$items = $(input).attr('name').split('][');
					$items = $items.slice(1);
					if ($items) {
						if ($items.length > 1) {
							var item = $items[1].replace(/[^a-z0-9\s]/gi, '');
							data.push(item + ':' + $(input).val());
						} else {
							if ($(input).is(':checked')) {
								var item = $items[0].replace(/[^a-z0-9\s]/gi, '');
								data.push($(input).val());
							}
						}
					}
				});
			});

			var optionValues = data.filter(function(itm, i, data) {
				return i == data.indexOf(itm);
			});

			if (!optionValues.length) {
				amount = 0;
			} else {
				var xhrData = {optionValues};

				// Make the AJAX request
				amount = $.ajax({
					url: window.location.href,
					method: 'GET',
					data: xhrData,
					async: false,
					success: function (response) {},
				}).responseText;
			}
			return (amount || 0);
		},

		/**
		 * Gets the total price.
		 *
		 * @return {number}
		 */
		getTotalPrice: function () {
			var announcementPrice = this.getAnnouncementPrice(),
				optionsPrice = this.getOptionsPrice(),
				$formControl = this.getQuantityFormControl(),
				data = $formControl.data();

			if (typeof data === 'undefined') {
				data = {};
			}
			if ($formControl.hasClass('step-quantity-custom-input')) {
				data.quantity = $formControl.val();
			}
			data.quantity = +data.quantity || 1;
			data.discount = +data.discount || 0;
			data.shipping_price = +data.shipping_price || 0;

			return +(((announcementPrice + optionsPrice) * data.quantity * (1 - data.discount / 100)) + data.shipping_price);
		},

		/**
		 * Updates the prices for the packages quantity step.
		 */
		updatePackagesPrices: function () {
			var me = this,
				$quantityStep = this.$stepsContainer.find('[data-step-type="quantity"]'),
				announcementPrice = this.getAnnouncementPrice(),
				optionsPrice = this.getOptionsPrice();

			$quantityStep.find('.step-quantity-form-control').each(function (index, formControl) {
				var $formControl = $(formControl),
					data = $formControl.data(),
					$stepPackage = $formControl.closest('.step-package'),
					$priceLabel = $stepPackage.find('.step-package-price'),
					price;

				data.quantity = +data.quantity || 1;
				data.discount = +data.discount || 0;
				data.shipping_price = +data.shipping_price || 0;

				price = ((announcementPrice + optionsPrice) * data.quantity * (1 - data.discount / 100)) + data.shipping_price;

				// $priceLabel.html(mainApp.formatter.asCurrency(price, me.$announcementFormControl.data('priceFormat')));
				$formControl.data('price', price);
			});
		},

		/**
		 * Updates the summary list.
		 */
		updateSummaryList: function () {
			var me = this,
				$steps = this.$stepsContainer.find('[data-step]'),
				$summaryItems = me.$summaryList.find('[data-step]'),
				$quantityFormControl = this.getQuantityFormControl(),
				totalPrice = this.getTotalPrice();

			$steps.each(function (index, step) {
				var $step = $(step),
					stepData = $step.data(),
					$summaryItem = $summaryItems.filter('[data-step="' + stepData.step + '"]'),
					$summaryItemContent = $summaryItem.find('.summary-item-content'),
					$formControls = $step.find('.step-form-control'),
					$formControl,
					stepName;


				if ($formControls.length === 1 && $formControls.hasClass('step-quantity-custom-input')) {
					$formControl = $formControls.eq(0);
					stepName = $formControl.val();
				} else {
					if ($formControls.hasClass('step-custom-input')) {
						var $items = [];
						$formControls.each(function (index, item) {
							var $item = $(item);
							$formControl = $item.eq(0);
							$items.push($formControl.closest('.form-group').find('.step-name').html() + ': ' + $item.val());
							stepName = $items.join(', <br>');
						});
					} else {
						$formControl = $formControls.filter(':checked');
						stepName = $formControl.parent().find('.step-name').html();
					}
				}

				if ($formControl.prop('checked') || $formControl.val()) {
					$summaryItemContent.removeClass('blank').children('.summary-item-value').html(stepName);
				} else {
					$summaryItemContent.addClass('blank');
				}
			});

			if ($quantityFormControl.is(':checked') || ($quantityFormControl.hasClass('step-quantity-custom-input') && $quantityFormControl.val())) {
				totalPrice = mainApp.formatter.asCurrency(totalPrice, me.$announcementFormControl.data('priceFormat'));
			} else {
				totalPrice = '&mdash;';
			}
			$summaryItems.filter('.summary-item-amount').find('.summary-item-content').html(totalPrice);
		},
	};
})(jQuery, window, document);

/**
 * Document Ready
 */
$(document).ready(function () {
	announcementFormApp.init();
});
