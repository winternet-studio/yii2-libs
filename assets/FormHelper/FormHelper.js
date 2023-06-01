if (typeof wsYii2 == 'undefined') {
	var wsYii2 = {};
}

wsYii2.FormHelper = {

	/**
	 * Get form input value(s) of any type of input
	 *
	 * @param {string|object} inputName - Name of form field or jQuery object of the element
	 * @param {object} options : (opt.) Possible options:
	 *   - `forceArray` : set true to force value(s) to be returned in an array (good for checkboxes)
	 *   - `forceNull` : set true to return null instead of empty string or empty array when nothing has been selected (forceArray has priority)
	 *   - `emptyString` : set true to return empty string instead of null or empty array when nothing has been selected (has priority over forceNull)
	 *
	 * @return string|array|null - The form field value (if form field was not found return value will be undefined)
	 */
	getValue: function(inputName, options) {
		var $inputElement, value;
		if (typeof options == 'undefined') options = {};

		if (typeof inputName === 'string') {
			$inputElement = $(':input[name="' + inputName + '"]');
		} else {
			$inputElement = $(inputName);
		}

		if ($inputElement.length >= 1) {
			// console.log('Element '+ inputName +' type: '+ $inputElement.attr('type'));
			switch ($inputElement.attr('type')) {
			case 'checkbox':
				if ($inputElement.length > 1) {
					value = [];
					$inputElement.each(function() {
						if ($(this).prop('checked') && !$(this).prop('disabled')) {
							value.push($(this).val());
						}
					});
				} else {
					if ($inputElement.is(':checked:not(:disabled)')) {
						value = $inputElement.val();
					} else {
						value = null;
					}
				}
				break;
			case 'radio':
				var fieldExists = ($inputElement.length > 0 ? true : false);
				value = $inputElement.filter(':checked:not(:disabled)').val();
				if (typeof value == 'undefined' && fieldExists) {
					value = null;
				}
				break;
			default:
				value = $inputElement.val();
				break;
			}

			if (options.forceArray) {
				if (value === null || value === '') {
					value = [];
				} else if (!Array.isArray(value)) {  //source: https://stackoverflow.com/questions/4775722/how-to-check-if-an-object-is-an-array
					value = [value];
				}
			} else if (options.emptyString) {
				if (value === null || (Array.isArray(value) && value.length === 0)) {
					value = '';
				}
			} else if (options.forceNull) {
				if (value === '' || (Array.isArray(value) && value.length === 0)) {
					value = null;
				}
			}
			return value;
		} else {
			console.log('Element '+ inputName +' not found');
		}
	},

	/**
	 * Set form input value(s) of any type of input
	 *
	 * @param {string|object} inputName - Name of form field or jQuery object of the element
	 * @param {object} options - Available options:
	 *   - `triggerChange` : set true to trigger a change event on the input if the new value is different (see also https://stackoverflow.com/questions/3179385/val-doesnt-trigger-change-in-jquery and https://stackoverflow.com/questions/24410581/changing-prop-using-jquery-does-not-trigger-change-event)
	 *
	 * @return {object|null} - The element as a jQuery object
	 */
	setValue: function(inputName, value, options) {
		var $inputElement;
		if (typeof options == 'undefined') options = {};

		if (typeof inputName === 'string') {
			$inputElement = $(':input[name="' + inputName + '"]');
		} else {
			$inputElement = $(inputName);
		}

		if ($inputElement.length >= 1) {
			switch ($inputElement.attr('type')) {
			case 'checkbox':
				$inputElement.each(function (i) {
					var $this = $(this);
					if (Array.isArray(value)) {
						if (value.length == 0) {
							if (options.triggerChange === true && $this.prop('checked') !== false) {
								$this.prop('checked', false).trigger('change');
							} else {
								$this.prop('checked', false);
							}
						} else {
							var setValue = false;
							$.each(value, function(indx, currValue) {
								if ($this.val() == currValue) {
									setValue = true;
									return false;
								}
							});
							if (options.triggerChange === true && $this.prop('checked') !== setValue) {
								$this.prop('checked', setValue).trigger('change');
							} else {
								$this.prop('checked', setValue);
							}
						}
					} else {
						if ($this.val() == value) {
							if (options.triggerChange === true && $this.prop('checked') !== true) {
								$this.prop('checked', true).trigger('change');
							} else {
								$this.prop('checked', true);
							}
						} else {
							if (options.triggerChange === true && $this.prop('checked') !== false) {
								$this.prop('checked', false).trigger('change');
							} else {
								$this.prop('checked', false);
							}
						}
					}
				});
				break;
			case 'radio':
				$inputElement.each(function (i) {
					var $this = $(this);
					if ($this.val() == value) {
						if (options.triggerChange === true && $this.prop('checked') !== true) {
							$this.prop('checked', true).trigger('change');
						} else {
							$this.prop('checked', true);
						}
					} else {
						if (options.triggerChange === true && $this.prop('checked') !== false) {
							$this.prop('checked', false).trigger('change');
						} else {
							$this.prop('checked', false);
						}
					}
				});
				break;
			default:
				if (options.triggerChange === true && $inputElement.val() != value) {
					$inputElement.val(value).trigger('change');
				} else {
					$inputElement.val(value);
				}
				break;
			}
			return $inputElement;
		} else {
			return null;
		}
	},

	/**
	 * Load ActiveForm with new model attributes via Javascript
	 *
	 * Form fields must have been named like this: <input name="Contact[firstname]"> <input name="Contact[lastname]">
	 *
	 * @param {(string|jQuery object)} formSelector - String with selector or a jQuery object
	 * @param {object} models : Object where keys match the 1st level form field names and the values are the model attributes that match the 2nd level, eg.: {Contact: {firstname: 'John', lastname: 'Doe'}, }
	 */
	loadActiveForm: function(formSelector, models) {
		if (!(formSelector instanceof jQuery)) {
			formSelector = $(formSelector);
		}

		$.each(models, function(modelName, model) {
			// Skip properties that are not models (= iterable arrays/objects)
			if (typeof model === 'string') return true;

			$.each(model, function(attributeName, attributeValue) {
				$input = formSelector.find(':input[name="'+ modelName +'['+ attributeName +']"]');
				if ($input.length > 1) {
					if ($input.first().is(':radio')) {
						$input.each(function() {
							if ($(this).val() == attributeValue) {
								$(this).prop('checked', true).click();
								if ($(this).closest('.btn').length > 0) {
									$(this).closest('.btn').button('toggle');
								}
							}
						});
					} else {
						alert('In wsYii2.FormHelper.loadActiveForm an input had multiple tags but they are not radio buttons.');
					}
				} else {
					if (attributeValue && $input.is('select')) {
						if ($input.find('option[value="'+ (attributeValue.replace /*only for strings*/ ? attributeValue.replace(/"/g, '&quot;') : attributeValue) +'"]').length == 0) {
							// automatically add an option with the current value so that it is not lost when saving the form back into the model
							var attributeLabel = 'Current value: '+ attributeValue;
							if (model._meta && model._meta.labels && model._meta.labels[attributeName]) {
								attributeLabel = model._meta.labels[attributeName];
							}
							$input.prepend(  $('<option/>').attr('value', attributeValue).html(attributeLabel)  );
						}
					}
					$input.val(attributeValue);
				}
			})
		});
	},

	/**
	 * Convert an HTML form to an object, specified by a selector 
	 *
	 * @param {string|jQuery object} : formSelector
	 * @param {boolean} : set to true to only collected fields that have been changed
	 */
	formToObject: function(formSelector, onlyChanged) {
		var data = $(formSelector).serializeArray().reduce(function(m,o){ m[o.name] = o.value; return m;}, {});

		// Add Select2 widgets where nothing is selected - serializeArray() will not include those!
		$(formSelector).find('select.select2-hidden-accessible').each(function(s2indv, s2val) {
			if ($(this).select2('data').length == 0) {
				data[ $(this).attr('name') ] = '';
			}
		});

		if (onlyChanged) {
alert('This part of the method (only returning changed values) has not been implemented yet. Returning all values for now.');
// NOTE: should we maybe rather store the form state before changes are made and then compare that instead of using defaultValues and defaultChecked??
return data;
			$.each(data, function(indx, val) {
				var defaultVal;
				var $input = $(formSelector).find(':input[name="'+ indx +'"]');
/*
TODO:
- consider the different types below this point  (basis: https://stackoverflow.com/questions/4591889/get-default-value-of-an-input-using-jquery#4592082)
	input: https://www.w3schools.com/jsref/prop_text_defaultvalue.asp
	checkbox and radio: https://www.w3schools.com/jsref/prop_checkbox_defaultchecked.asp
	select: https://www.w3schools.com/jsref/prop_option_defaultselected.asp
- also make function that will reset the default values to the current values on the form, again considering all 3 types
*/
				if ($input.length > 1) {
					if ($input.first().is(':radio')) {
						$input.each(function() {
							if ($(this).prop('defaultChecked')) {
								defaultVal = $(this).val();
							}
						});
					} else {
						alert('In wsYii2.FormHelper.loadActiveForm an input had multiple tags but they are not radio buttons.');
					}
				} else {
					$input.val(attributeValue);
				}
			});
		} else {
			// Source: comment from juanpastas on https://stackoverflow.com/a/17784656/2404541
			return data;
		}
	},

	/**
	 * Apply the server-side generated errors to the form fields
	 *
	 * @param {HTMLElement} form
	 * @param {object} response - Response object from the AJAX request
	 * @param {object} options - Available options:
	 *   - `skipHighlightIssues` : set true to not automatically call HighlightIssues.checkNow() to highlight any errors
	 *   - `userFriendlyNameCallback` : callback that can modify the name of the error messages' key to make it nicely readable by humans, eg. change `bk_firstname` to `First Name`. Passed one argument being the name. Returned string can include the string `[noColon]` in order not to put a colon after the name.
	 *   - `errorsLabel` : title of the modal. Default is `Errors`
	 */
	applyServerSideErrors: function(form, response, options) {
		options = $.extend({}, {
			skipHighlightIssues: false,
			userFriendlyNameCallback: null,
			errorsLabel: 'Errors',
		}, options);

		var errorCount = 0, errors = [];
		if (typeof response.err_msg_ext != 'undefined') {
			errors = response.err_msg_ext;
		} else if (typeof response.errorsItemized != 'undefined') {
			errors = response.errorsItemized;
		}
		if (errors !== null) {
			// NOTE: the lonelyErrors are errors that have IDs that do not match anything in Yii's ActiveForm, so show those errors in a modal instead
			var lonelyErrors = [];
			Object.keys(errors).forEach(function(errorName) {  //example errorName: `customer-cust_address`
				errorCount++;
				if (typeof form.yiiActiveForm('find', errorName) === 'undefined') {
					lonelyErrors.push([errorName, errors[errorName][0]]);
				}
			});
			if (lonelyErrors.length > 0) {
				var html = [];
				for (var i = 0; i < lonelyErrors.length; i++) {
					if (lonelyErrors[i][0] === '_generic') {
						html.push('<li>'+ lonelyErrors[i][1] + '</li>');
					} else {
						var name = lonelyErrors[i][0].replace(/^[a-z]+\-/, '');
						var addColon = true;
						if (options.userFriendlyNameCallback) {
							name = options.userFriendlyNameCallback(name);
							if (name && name.indexOf('[noColon]') > -1) {
								addColon = false;
								name = name.replace('[noColon]', '');
							}
						}
						html.push('<li>'+ (name ? name + (addColon ? ': ' : '') : '') + lonelyErrors[i][1] + '</li>');
					}
				}
				if (typeof appJS.showModal != 'undefined') {
					appJS.showModal({title: options.errorsLabel, html: '<ul>'+ html.join('') +'</ul>' });
				} else {
					alert(options.errorsLabel +':\n\n'+ html.join('\n').replace(/<li>/g, '- ').replace(/<\/li>/, ''));
				}
			}
		}
		// NOTE: errorCount MUST be determined before form.yiiActiveForm() because it modifies the `errors` variable! NOTE: updateMessages should always be called so that in case there are no errors any previously set errors are cleared.
		form.yiiActiveForm('updateMessages', errors, true);

		if (!options.skipHighlightIssues) {
			wsYii2.FormHelper.HighlightIssues.checkNow(form);
		}

		return {
			errorCount: errorCount
		};
	},

	/**
	 * Disable submit button temporarily to avoid double submission
	 *
	 * @param {string} selector : jQuery selector (or instance) for the submit button being clicked
	 * @param {string} attachTo : Set `click` to bind to the submit button's click event, or `form` to bind to the form's submit event
	 * @param {object} options : Available options:
	 *   - `blockTime` : milliseconds that the button(s) should be blocked for subsequent clicks after having been clicked the first time. Default is 3000ms.
	 *   - `callbackOnContinue` : callback that will be executed when submission is allowed to continue. Is passed one argument that is the jQuery instance of the selector.
	 *   - `delayMs` : milliseconds to delay the event binding (eg. in case other code would delete the event binding)
	 */
	prohibitDoubleSubmit: function(selector, attachTo, options) {
		options = $.extend({}, {
			blockTime: 3000,
			callbackOnContinue: null,
			delayMs: null
		}, options);

		var $submitButton = $(selector);

		var eventFunction = function(ev) {
			// Disable submit button temporarily
			var allowContinue = true;
			if (wsYii2.FormHelper._blockSubmit) {  //somehow it's not enough to set button as disabled (two quick double clicks can submit the form twice)
				ev.preventDefault();
				allowContinue = false;
			}

			wsYii2.FormHelper._blockSubmit = true;
			setTimeout(function() {
				// Wait until after this callback has finished so that it is actually submitted the first time (when selector is of type=submit)
				$submitButton.prop('disabled', true);
			}, 1);
			setTimeout(function() {
				$submitButton.prop('disabled', false);
				wsYii2.FormHelper._blockSubmit = false;
			}, options.blockTime);

			if (allowContinue && options.callbackOnContinue) {
				options.callbackOnContinue($submitButton);
			}
		};

		var bindEvents = function() {
			if (attachTo === 'submit') {
				$submitButton.closest('form').on('submit.wsyii2-nodbl', eventFunction);
			} else {
				$submitButton.on('click.wsyii2-nodbl', eventFunction);
			}
		};

		if (options.delayMs) {
			setTimeout(function() {
				bindEvents();
			}, options.delayMs);
		} else {
			bindEvents();
		}
	},

	/**
	 * Object for highlighting errors on a form, or just highlight any element for any purpose
	 */
	HighlightIssues: {

		/**
		 * Use this on page load (if desired)
		 *
		 * @param {object} options - Same options as for checkNow()
		 */
		init: function(formSelector, options) {
			var myself = this;

			$(formSelector).on('afterValidate', function(ev) {
				myself.checkNow(formSelector, options);
			});

			// Also check on initial page load in case it has been validated server-side and came back with errors
			myself.checkNow(formSelector, options);
		},

		/**
		 * Bring user's attention to any errors on the form
		 *
		 * By default both scrolls to (or make visible) the first error and visually highlights it.
		 *
		 * This can also be called directly instead of using the init() method.
		 *
		 * @param {object} options - Available options:
		 *   - `errorSelector` : set custom error CSS selector. Default is `.has-error`
		 *   - `elementAttentionClass` : name of class that that should be applied to the first error element in order to bring attention to it - usually applying kind of CSS animation. Default is `highlight-form-element`. Example CSS:
		 *     ```
		 *     @keyframes element-blinking {
		 *     	0% {
		 *     		box-shadow: 0 0 0 10pt  rgba(195, 0, 0, 0.3);
		 *     		background-color: rgba(195, 0, 0, 0.3);
		 *     	}
		 *     	100% {
		 *     		box-shadow: inherit;
		 *     		background-color: inherit;
		 *     	}
		 *     }
		 *     .highlight-form-element input,
		 *     .highlight-form-element select {
		 *     	animation: element-blinking 1s 2;
		 *     }
		 *     ```
		 *   - `removeClassAfter` : Number of milliseconds after which the class should be removed again. Set null to not remove it. Default is 4000 ms
		 *   - `skipScroll` : don't scroll to the first error element
		 *   - `skipFocus` : don't put cursor focus on the first error element
		 *   - `skipSubmitButtonTooltip` : don't show tooltip on submit button
		 *   - `submitButtonSelector` : set custom selector for the submit button. Default is `input[type=submit], button[type=submit]`
		 *   - `submitButtonTooltipText` : set custom text to use as the submit button tooltip, shown for a few seconds when the form contains errors. Default is `Please check the form.`
		 *   - `tooltipBgColor` : set custom CSS background color for the submit button tooltip. Default is red: `#ec0000`
		 */
		checkNow: function(formSelector, options) {
			options = $.extend({}, {
				errorSelector: '.has-error',
				elementAttentionClass: 'highlight-form-element',
				removeClassAfter: 4000,
				skipScroll: false,
				skipFocus: false,
				skipSubmitButtonTooltip: false,
				submitButtonSelector: 'input[type=submit], button[type=submit]',
				submitButtonTooltipText: null,
				tooltipBgColor: '#ec0000'  //= red
			}, options);

			if (typeof formSelector == 'undefined') {
				formSelector = 'body';
			}

			// If form has client-side errors make sure the first tab with an error is active and user is aware there are problems
			var $form = $(formSelector);
			var $errors = $form.find(options.errorSelector);
			if ($errors.length > 0) {
				var $firstError = $errors.first();

				var $tabPane = $firstError.closest('.tab-pane');
				if ($tabPane.length > 0) {
					var paneId = $tabPane.attr('id');
					$form.find('.nav-tabs a[href="#'+ paneId +'"]').tab('show');
				}

				// Scroll to first error and highlight it
				$('html, body').animate({
					scrollTop: $firstError.offset().top - ($(window).height() / 2),
				}, {
					duration: 'slow',
					complete: function() {
						if (!options.skipFocus) {
							$firstError.focus();
						}
						if (options.elementAttentionClass) {
							$firstError.addClass(options.elementAttentionClass);
							if (options.removeClassAfter) {
								setTimeout(function() {
									$firstError.removeClass(options.elementAttentionClass);
								}, options.removeClassAfter);
							}
						}
					}
				});

				// Add tooltip on submit button
				if (!options.skipSubmitButtonTooltip) {
					$form.find(options.submitButtonSelector).tooltip({trigger: 'manual', title: (options.submitButtonTooltipText ? options.submitButtonTooltipText : 'Please check the form.'), container: 'body'});  //use container=body to make it not wrap inside element with little space
					$form.find(options.submitButtonSelector).tooltip('show');

					var origBgColor = $('.tooltip-inner, .tooltip-arrow').css('background-color');
					$('.tooltip-inner').css('background-color', options.tooltipBgColor);
					$('.tooltip-arrow').css('border-top-color', options.tooltipBgColor);

					setTimeout(function() {
						$(options.submitButtonSelector).tooltip('destroy');
						$('.tooltip-inner').css('background-color', origBgColor);
						$('.tooltip-arrow').css('border-top-color', origBgColor);
					}, 2000);
				}
			}
		}
	},

	/**
	 * Object for highlighting errors on a form using Bootstrap tabs
	 *
	 * @deprecated No need to use this anymore since HighlightIssues automatically handles Bootstrap tabs
	 */
	HighlightTabbedFormErrors: {
		init: function(formSelector, options) {
			console.info("wsYii2.FormHelper.HighlightTabbedFormErrors is deprecated. Please use wsYii2.FormHelper.HighlightIssues instead.");
			return wsYii2.FormHelper.HighlightIssues.init(formSelector, options);
		},
		checkForErrors: function(formSelector, options) {
			console.info("wsYii2.FormHelper.HighlightTabbedFormErrors is deprecated. Please use wsYii2.FormHelper.HighlightIssues instead.");
			return wsYii2.FormHelper.HighlightIssues.checkNow(formSelector, options);
		}
	},

	/**
	 * Object for warning about leaving a form without changes having been saved
	 */
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
