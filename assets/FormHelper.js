if (typeof wsYii2 == 'undefined') {
	var wsYii2 = {};
}

wsYii2.FormHelper = {
	HighlightTabbedFormErrors: {

		init: function(formSelector) {
			var myself = this;

			$(formSelector).on('afterValidate', function(ev) {
				myself.checkForErrors(formSelector);
			});

			// Also check on initial page load in case it has been validated server-side and came back with errors
			myself.checkForErrors(formSelector);
		},

		checkForErrors: function(formSelector) {
			if (typeof formSelector == 'undefined') {
				formSelector = 'body';
			}

			// If form has client-side errors make sure the first tab with an error is active and user is aware there are problems
			var $form = $(formSelector);
			var $errors = $form.find('.has-error');
			if ($errors.length > 0) {
				var $tabPane = $errors.first().closest('.tab-pane');
				if ($tabPane.length > 0) {
					var paneId = $tabPane.attr('id');
					$form.find('.nav-tabs a[href="#'+ paneId +'"]').tab('show');
				}
				$form.find('input[type=submit], button[type=submit]').tooltip({trigger: 'manual', title: 'Please check the form.', container: 'body'});  //use container=body to make it not wrap inside element with little space
				$form.find('input[type=submit], button[type=submit]').tooltip('show');

				var origBgColor = $('.tooltip-inner, .tooltip-arrow').css('background-color');
				$('.tooltip-inner').css('background-color', '#ec0000');  //show it in red
				$('.tooltip-arrow').css('border-top-color', '#ec0000');

				setTimeout(function() {
					$('input[type=submit], button[type=submit]').tooltip('destroy');
					$('.tooltip-inner').css('background-color', origBgColor);
					$('.tooltip-arrow').css('border-top-color', origBgColor);
				}, 2000);
			}
		}
	},

	WarnLeavingUnsaved: {

		savedState: null,
		currState: null,

		init: function(formSelector) {
			this.savedState = this.getFormData(formSelector);

			var myself = this;

			$(window).on('beforeunload', function(event) {
				myself.currState = myself.getFormData(formSelector);

				if (myself.currState !== myself.savedState) {
					return 'You have unsaved changes. They will be lost if you leave the page.';
				}
			});
		},

		markSaved: function(formSelector) {
			this.savedState = this.getFormData(formSelector);
		},

		getFormData: function(formSelector) {
			// Use this instead in case we need to do something more fancy
			// var formData = $(formSelector).serializeArray();
			// return $.param(formData);

			return $(formSelector).serialize();
		}
	}
};
