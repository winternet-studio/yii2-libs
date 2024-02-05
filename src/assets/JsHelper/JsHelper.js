if (typeof appJS != 'undefined') {
	alert('CONFLICT ERROR! The variable appJS already exists in the global namespace of Javascript. winternet/yii2/JsHelper library will overwrite the variable.');
}

appJS = {
	translateText: null
};


/* ------------- Common core functions ------------- */

/**
 * Check deep property on object
 *
 * This saves you for checking if each property level already exists.
 *
 * @param {object} object - Object variable
 * @param {string} path - String with properties in dot notation, eg. `child.clothing.shirtBrand` (which could for example have the value `H&M` which would then be returned)
 *
 * @return mixed - Returns value if found, undefined if not found
 */
appJS.getProp = function(object, path) {
	// Source: https://stackoverflow.com/a/20240290/2404541
    var o = object;
    path = path.replace(/\[(\w+)\]/g, '.$1');
    path = path.replace(/^\./, '');
    var a = path.split('.');
    while (a.length) {
        var n = a.shift();
        if (n in o) {
            o = o[n];
        } else {
            return;
        }
    }
    return o;
/*
	MY OWN WORKING VERSION:
	var parts, value;
	parts = path.split('.');
	value = object;
	for (var i = 0; i < parts.length; i++) {
		value = value[parts[i]];
		if (typeof value == 'undefined') {
			break;
		}
	}
	if (typeof value != 'undefined') {
		return value;
	}
*/
};

/**
 * Set deep property on object
 *
 * This saves you for checking if each property level already exists.
 *
 * @param {object} object - Object variable
 * @param {string} path - String with properties in dot notation, eg. `child.clothing.shirtBrand`
 * @param {mixed} value - Value to set set, eg. `H&M`
 *
 * @return void
 */
appJS.setProp = function(object, path, value) {
	// Source: https://stackoverflow.com/a/20240290/2404541
	var a = path.split('.');
	var o = object;
	for (var i = 0; i < a.length - 1; i++) {
		var n = a[i];
		if (n in o) {
			o = o[n];
		} else {
			o[n] = {};
			o = o[n];
		}
	}
	o[a[a.length - 1]] = value;
};

/* ------------- AJAX section ------------- */

/**
 * @return {Promise} - Returns the jQuery AJAX Promise
 */
appJS.ajax = function(parms) {
	if (typeof parms.error == 'undefined') {
		var titleText = 'Sorry, we ran into a problem...';
		if (appJS.translateText !== null) {
			titleText = appJS.translateText('Sorry, we ran into a problem', null, {skipNotif: true});
		} else if (typeof getphp != 'undefined') {
			var vars = getphp();
			if (typeof vars['Sorry, we ran into a problem'] !== 'undefined') {
				titleText = vars['Sorry, we ran into a problem'];
			}
		}

		parms.error = function(rsp, status, err) {
			var html = '';
			if (typeof rsp.responseJSON == 'undefined') rsp.responseJSON = {};
			if (rsp.responseJSON.name) {
				html += '<div style="color: #BDBDBD"><b><i>'+ rsp.responseJSON.name.toUpperCase() +'</i></b></div><br>';
			}
			if (rsp.responseJSON.message) {
				html += rsp.responseJSON.message.replace(/(?:\r\n|\r|\n)/g, '<br />');
			} else {
				html += 'Response from server: '+ (rsp.responseText === '' ? '<span style="color: #B0B0B0">EMPTY STRING</span>' : $('<div>').text(rsp.responseText.substr(0, 300)).html() + '... [see the rest in debugger]');
				console.error('Response from server:');
				console.error(rsp.responseText);
				html += '<br /><br />Text status: '+ status +'<br />Error thrown: '+ err;
			}
			if (rsp.responseJSON.file) {
				html += '<br><br>File: '+ rsp.responseJSON.file;
			}
			if (rsp.responseJSON.line) {
				html += '<br>Line: '+ rsp.responseJSON.line;
			}
			if (rsp.responseJSON['stack-trace']) {  //this (+file,line) will only be available in debug mode
				html += '<div style="color: #7B7B7B; font-family: monospace; font-size: 11px">'+ rsp.responseJSON['stack-trace'].join('<br>') +'</div>';
			}
			appJS.showModal({html: html, title: titleText});
			appJS.enableSubmit();
			if (typeof parms.errorAdditional != 'undefined') {
				parms.errorAdditional(rsp);
			}
		};
	}
	return $.ajax(parms);
};

// IMPORTANT NOTE!!! Please note that this does not cancel other events that have been set with on('click'). In those events check for the presence of the class click-disabled before executing them.
appJS.cancelClickHandler = function(e) {
	console.info('Click has been disabled to protect again double execution');
	e.preventDefault();
	e.stopImmediatePropagation();
	return false;
};
appJS.disableSubmit = function(selector) {
	appJS.disableSubmitSelector = selector;

	if (typeof appJS.disableSubmitElements === 'undefined') {
		appJS.disableSubmitElements = [];
	}

	$(appJS.disableSubmitSelector).each(function() {
		var $this = $(this);
		// $(this).html($(this).html() +' X');  //debug code
		if (typeof $this.attr('onclick') != 'undefined') {
			// Cancel out onclick attribute if set
			$this.data('onclickValue', $this.attr('onclick')).attr('onclick', '');
		}
		// Cancel out normal clicks
		$this.on('click', appJS.cancelClickHandler).addClass('click-disabled');
		appJS.disableSubmitElements.push($this);
	});
	console.info('Click disabled for '+ appJS.disableSubmitElements.length +' elements ('+ selector +')');
};
appJS.enableSubmit = function() {
	if (typeof appJS.disableSubmitElements == 'undefined') return;

	for (var i = 0; i < appJS.disableSubmitElements.length; i++) {
		var $elem = appJS.disableSubmitElements[i];
		// $elem.html($elem.html() +'-');  //debug code
		// Remove cancellation event
		$elem.off('click', appJS.cancelClickHandler).removeClass('click-disabled');
		// Reinstate onclick attribute if set
		if (typeof $elem.data('onclickValue') != 'undefined') {
			$elem.attr('onclick', $elem.data('onclickValue'));
		}
	}
	console.info('Click has been re-enabled for '+ appJS.disableSubmitElements.length +' elements');
	appJS.disableSubmitElements = [];
};




/* ------------- Standard AJAX call ------------- */

/**
 * Make a standard webservice call
 *
 * @param {object} args - Object where the following options are available:
 *   - {string} url - URL of the webservice
 *   - {string} method - Enforce this method, eg. `GET`, `POST`, etc. Otherwise if `params` has values it will be `POST`, otherwise `GET`
 *   - {object} params - Object with parameters to send to the webservice
 *   - {string|object} responseFormat - How to interpret the data being returned from the webservice. Available options:
 *   	- `nothing` : don't do any postprocessing as nothing is being returned, or the returned should only be processed by post-processing function
 *   	- `resultError`       : the following fields are returned in an array: `status` (with `ok` or `error`), `err_msg` or `errors` (array), `result_msg` or `notices` (array)
 *   	- `resultErrorQuiet` : same as above but status will only be given if there are specific messages to go along with it
 *   	- `resultOnly` : like `resultError` but use the `errorCallback:` in postActions to handle ALL aspects of dealing with errors (= error msgs not automatically shown)
 *   	- `resultOnlyQuiet` : like 'resultOnly' but with the difference described in `resultErrorQuiet`
 *   	- `boolean`       : webservice returns true or false which respectively means succeeded or failed, and the user will be told so
 *   	- `booleanQuiet` : same as above but user will only be notified if operation failed
 *   	- object with these keys for removing all existing entries in a dropdown box and fill with the items in the returned multi-dimensional associative array:
 *   		{
 *   			fillDropDown: true,
 *   			selectSelector: 'selector for the select input field',
 *   			valueColumn: 'name of key in the returned associative array that should be the value for the dropdown option',
 *   			labelColumn: 'name of key in the returned associative array that should be the label for the dropdown option'
 *   		}
 *   		- note that if an item is currently selected it will retain that selection if one of the new items have the same value
 *   - {array|string|callable} postActions - Array of actions to be done after the webservice has completed. An action is an object with one following keys:
 *   	- `successMessage` : set value with a message to the user if operation succeeds, eg. "Thank you for contacting us."
 *   	- `errorMessage` : set value with a message to the user if operation fails, eg. "Sorry, you message could not be sent."
 *   	- `reloadPage` : set to true to reload the page if operation succeeds (must be the last action to perform)
 *   	- `previousPage` : set to true to go back to the previous page in the browser history (must be the last action to perform)
 *   	- `redirectUrl` : set value to a URL to redirect to if operation succeeds (must be the last action to perform)
 *   	- `setHtml` : set to true to set the HTML (innerHtml property) for an element on the page if operation succeeds. Additional required keys:
 *   		- `selector` : selector for the element to set HTML for
 *   		- `html` : HTML to be set
 *   	- `successCallback` : set value to a string with function name or an anonymous function to call if operation succeeds. One argument is passed which will be an object with the following properties:
 *   		- `data` : response from server
 *   		- `doAjaxArgs` : arguments passed to the doAjax function
 *   	- `errorCallback` : set value to a string with function name or an anonymous function to call if operation fails. One argument is passed which will be an object with the following properties:
 *   		- `data` : response from server
 *   		- `doAjaxArgs` : arguments passed to the doAjax function
 *   	- as a shortcut for specifying just a single action you can pass the strings `reloadPage` or `previousPage`
 *   	- instead of one of the above you can also pass a callback function to be executed. One argument is passed which will be an object with the following properties:
 *   		- `data` : response from server
 *   		- `success` : boolean true or false based on response from server
 *   		- `doAjaxArgs` : arguments passed to the doAjax function
 *   - {object} options - Object with any of these options:
 *   	- `confirmMessage` : set a string with message to have the user confirm before executing the AJAX call
 *   	- `skipShowProcess` : set to true to not dim the page and show process status
 *   	- `requireSsl` : set to true to require SSL for transmitting this request to the server
 *   	- `ajaxOptions` : extra options for the jQuery ajax() call
 *   	- `textSuccess` : set custom message
 *   	- `textErrorBecause` : set custom message
 *   	- `textError` : set custom message
 *   	- `textPleaseNote` : set custom message
 *   	- `textSelectionCleared` : set custom message
 *
 * @return {void} - Everything is handled within the call itself
 */
appJS.doAjax = function(args) {
	if (!$.isPlainObject(args.params)) args.params = {};
	if (!$.isPlainObject(args.options)) args.options = {};
	if (!$.isPlainObject(args.options.ajaxOptions)) args.options.ajaxOptions = {};
	if (typeof args.postActions == 'string') {
		if (args.postActions == 'reloadPage') {
			args.postActions = [ {reloadPage: true} ];
		} else if (args.postActions == 'previousPage') {
			args.postActions = [ {previousPage: true} ];
		} else {
			alert('CONFIGURATION ERROR! postActions is an invalid string: '+ args.postActions);
		}
	}

	args.options = $.extend({  //defaults
		confirmMessage: null,
		textSuccess: 'Operation completed successfully.',
		textErrorBecause: 'Sorry, operation could not be completed because:',
		textError: 'Sorry, the operation failed.',
		textPleaseNote: 'Please note',
		textSelectionCleared: 'Please note that current selection ({value}) in dropdown box was not re-selected after changing its options.',
	}, args.options);

	if (args.options.confirmMessage !== null) {
		this.showModal({
			customModalSelector: 'confirm',
			title: (args.options.confirmTitle ? args.options.confirmTitle : 'Confirm'),
			html: (args.options.confirmMessage === true ? 'Are you sure you want to do this?' : args.options.confirmMessage),
			openedCallback: function(modalRef) {
				$(modalRef).find('.btn-yes').on('click', function() {
					args.options.confirmMessage = null;
					appJS.doAjax(args);
				});
			}
		});
		return;
	}

	if (!args.options.skipShowProcess) {
		appJS.showProgressBar();
	}

	if (args.options.requireSsl) {
		if (args.url.substr(0, 4) == 'http') {
			if (args.url.substr(0, 5) != 'https') {
				appJS.showModal('This data may only be sent over a secure connection. Please contact website developer.');
			}
		} else if (document.location.protocol != 'https:') {
			appJS.showModal('This data may only be sent over a secure connection. Please contact website developer.');
		}
	}

	var parms = {
		url: args.url,
		type: (args.method ? args.method.toUpperCase() : ($.isEmptyObject(args.params) ? 'GET' : 'POST')),
		data: args.params,
		success: function(rsp, jqXHR, textStatus) {
			var i, functionName;
			var f = args.responseFormat;
			var success = true;
			var postModalActionsActivated = false;

			if (!f) f = '';

			// Postpone some post actions until after modal has been closed
			var postModalActions = function() {
				var postModalActions = [];

				if (typeof args.postActions == 'function') {  //in case postActions is only a callable don't postpone anything
					postModalActions = args.postActions;
					args.postActions = [];
				} else {
					for (var act in args.postActions) {
						if (!args.postActions.hasOwnProperty(act)) continue; //real keys will always be numeric

						if (typeof args.postActions[act].reloadPage !== 'undefined' || typeof args.postActions[act].redirectUrl !== 'undefined' || typeof args.postActions[act].previousPage !== 'undefined') {
							postModalActions.push(args.postActions[act]);

							// Remove it from the main array so it's not done immediately
							args.postActions.splice(act, 1);
						}
					}
				}
				return postModalActions;
			};

			var modalClosedCallback = function(actions, isSuccess) {
				if (typeof actions == 'function') {
					actions({
						data: rsp,
						success: isSuccess,
						doAjaxArgs: args,
					});
				} else {
					for (var i in actions) {
						if (actions.hasOwnProperty(i)) {
							var c = actions[i];
							if (isSuccess && typeof c.redirectUrl != 'undefined') {
								window.location.href = c.redirectUrl;
							} else if (isSuccess && typeof c.reloadPage != 'undefined') {
								window.location.reload();
							} else if (isSuccess && typeof c.previousPage != 'undefined') {
								history.back();
							}
						}
					}
				}
			};

			if (f == 'resultError' || f == 'resultErrorQuiet' || f == 'resultOnly' || f == 'resultOnlyQuiet') {
				var isQuiet = (f == 'resultErrorQuiet' || f == 'resultOnlyQuiet' ? true : false);
				var errors = (typeof rsp.err_msg != 'undefined' ? rsp.err_msg : rsp.errors);
				var notices = (typeof rsp.result_msg != 'undefined' ? rsp.result_msg : rsp.notices);
				if (rsp.status == 'ok') {
					var msgCount = notices.length;
					if (msgCount == 0) {  //check if it's an object with properties (= text keys) instead of an array (= numeric keys)
						msgCount = Object.keys(notices).length;
					}
					if (!isQuiet || msgCount > 0) {
						var resultMsg = '<span class="result-text success-text">'+ args.options.textSuccess;
						if (msgCount > 0) {
							resultMsg += ' '+ args.options.textPleaseNote +':</span><br><br><span class="messages result-messages"><ul>';
							for (i in notices) {
								if (notices.hasOwnProperty(i)) {
									resultMsg += '<li>'+ notices[i] +'</li>';
								}
							}
							resultMsg += '</ul></span>';
						} else {
							resultMsg += '</span>';
						}

						var effActions = postModalActions();
						appJS.showModal({
							html: '<div class="ws-ajax-result">'+ resultMsg +'</div>',
							closedCallback: function() {
								modalClosedCallback(effActions, success);
							}
						});
					}
				} else {
					success = false;
					if (f != 'resultOnly' && f != 'resultOnlyQuiet') {
						var errMsg = '<span class="result-text error-text">'+ args.options.textErrorBecause +'</span><br><br><span class="messages error-messages"><ul>';
						for (i in errors) {
							if (errors.hasOwnProperty(i)) {
								errMsg += '<li>'+ errors[i] +'</li>';
							}
						}
						errMsg += '</ul></span>';

						var effActions = postModalActions();
						appJS.showModal({
							html: '<div class="ws-ajax-result">'+ errMsg +'</div>',
							closedCallback: function() {
								modalClosedCallback(effActions, success);
							}
						});
					}
				}
			} else if (f == 'boolean' || f == 'booleanQuiet') {
				if (rsp && f != 'booleanQuiet') {
					var effActions = postModalActions();
					appJS.showModal({
						html: '<div class="ws-ajax-result">'+ args.options.textSuccess +'</div>',
						closedCallback: function() {
							modalClosedCallback(effActions, success);
						}
					});
				} else if (!rsp) {
					success = false;

					var effActions = postModalActions();
					appJS.showModal({
						html: '<div class="ws-ajax-result">'+ args.options.textError +'</div>',
						closedCallback: function() {
							modalClosedCallback(effActions, success);
						}
					});
				}
			} else if (typeof f == 'object' && typeof f.fillDropDown != 'undefined') {
				var obj = $(f.selectSelector)[0];
				// Some code below here is copied from dropdown_clear_options(JS) and dropdown_add(JS)
				var s,cVal,cLbl,cSel,cSet=false;
				if (obj.options.length == 0) {
					cVal = cLbl = '';
				} else {
					s=obj.options[obj.selectedIndex];
					cVal=s.value;
					cLbl=s.text;
				}
				obj.options.length = 0;
				if (rsp.length > 0) {
					obj.options[0] = new Option('', '');  //add blank option in top
					var nextIndex;
					for (i in rsp) {
						nextIndex = obj.options.length;
						if (cVal.length>0 && cVal==rsp[i][f.valueColumn]) {
							cSel=true;
							cSet=true;
						} else {
							cSel=false;
						}
						obj.options[nextIndex] = new Option(rsp[i][f.labelColumn],rsp[i][f.valueColumn],cSel,cSel);
					}
				}
				if (cVal.length>0 && cSet==false) {
					var effActions = postModalActions();
					appJS.showModal({
						html: args.options.textSelectionCleared.replace('{value}', cLbl),
						closedCallback: function() {
							modalClosedCallback(effActions, success);
						}
					});
				}
			} else if (f == 'nothing') {
				//do nothing
			}

			// Post actions
			if (typeof args.postActions == 'function') {
				args.postActions({
					data: rsp,
					success: success,
					doAjaxArgs: args,
				});
			} else {
				var c;
				for (var act in args.postActions) {
					if (!args.postActions.hasOwnProperty(act)) continue; //real keys will always be numeric
					c = args.postActions[act];
					if (success && typeof c.successMessage != 'undefined') {
						appJS.showModal(c.successMessage);
					} else if (!success && typeof c.errorMessage != 'undefined') {
						appJS.showModal(c.errorMessage);
					} else if (success && typeof c.setHtml != 'undefined') {
						$(c.selector).html(c.html);
					} else if (success && typeof c.successCallback != 'undefined') {
						if (typeof c.successCallback == 'string') {
							functionName = c.successCallback;
							window[functionName]({rsp: rsp, doAjaxArgs: args});  //calling function in global scope
						} else {
							c.successCallback({rsp: rsp, doAjaxArgs: args});
						}
					} else if (!success && typeof c.errorCallback != 'undefined') {
						if (typeof c.errorCallback == 'string') {
							functionName = c.substr(21);
							window[functionName]({rsp: rsp, doAjaxArgs: args});  //calling function in global scope
						} else {
							c.errorCallback({rsp: rsp, doAjaxArgs: args});
						}
					} else if (success && typeof c.redirectUrl != 'undefined') {
						window.location.href = c.redirectUrl;
					} else if (success && typeof c.reloadPage != 'undefined') {
						window.location.reload();
					} else if (success && typeof c.previousPage != 'undefined') {
						history.back();
					}
				}
			}

		}
	};

	$.extend(parms, args.options.ajaxOptions);

	if (!args.options.skipShowProcess) {
		if (typeof parms.complete != 'undefined') {
			var parmsCopy = jQuery.extend({}, parms);
			parms.complete = function(jqXHR, textStatus) {
				appJS.hideProgressBar();
				parmsCopy.complete(jqXHR, textStatus);
			}
		} else {
			parms.complete = function(jqXHR, textStatus) {
				appJS.hideProgressBar();
			}
		}
	}

	appJS.ajax(parms);
};




/* ------------- Modal section ------------- */

appJS.showProgressBar = function(options) {
	options = $.extend({
		indicatorWidth: 150,  //pixels
		indicatorHeight: 3,  //pixels
		indicatorColor: '#00AEFF',
		moveBy: 10,  //pixels to move indicator at each interval
		moveInterval: 10,  //interval in ms between moves
		autoHide: null,  //ms after which the indicator should automatically hide
		indicatorZindex: 9999,  //z-index of indicator
	}, options);

	var addHtml = '<div class="wsyii-busy-indicator" style="position: fixed; top: 0; left: -'+ options.indicatorWidth +'px; width: '+ options.indicatorWidth +'px; height: '+ options.indicatorHeight +'px; background-color: '+ options.indicatorColor +'; z-index: '+ options.indicatorZindex +'"></div>';
	$('body').append(addHtml);

	if (options.autoHide) {
		setTimeout(function() {
			appJS.hideProgressBar();
		}, options.autoHide);
	}

	var screenWidth = $(window).width();

	var moveIt = function() {
		var $div = $('.wsyii-busy-indicator');
		if ($div.length > 0) {
			var currX = parseInt($div.css('left'), 10);
			if (currX > screenWidth) {
				$div.css('left', '-'+ options.indicatorWidth +'px');
			} else {
				$div.css('left', ''+ (currX + options.moveBy) +'px');
			}
			setTimeout(moveIt, options.moveInterval);
		}
	};

	moveIt();
};

appJS.hideProgressBar = function(options) {
	$('.wsyii-busy-indicator').fadeOut('slow', function() {
		$('.wsyii-busy-indicator').remove();
	});
};




/* ------------- Modal section ------------- */

/**
 * Show a Bootstrap modal
 *
 * @param {string} parms - String with HTML or object with these possible keys:
 *   - `title` : title/headling for the modal
 *   - `skipTitleHtml` : set to true to skip setting a title for the modal (to not override an existing title)
 *   - `html` : HTML to show as content of the modal
 *   - `customModalSelector` : selector for a custom modal to use as base for the modal (eg. `#NumOfPagesToAddModal`). Some ready-made presets are available:
 *   	- `confirm` : confirmation modal with two buttons, default to No and Yes with classes btn-no and btn-yes respectively
 *   	- `prompt` : confirmation modal with two buttons, default to Cancel and OK with classes btn-cancel and btn-ok respectively
 *   - `preventClose` : set to true to prevent closing the modal with keyboard or by clicking outside the modal
 *   - `skipCloseOnInputEnter` : set to true to prevent closing the modal when pressing Enter in the first input field
 *   - `buttonOptions` : options for the buttons. Numeric object for each button, eg. for first button and second button: {0: {text: 'No, I do not agree'}, 1: {text: 'Yes, I agree'}}
 *   	- available keys:
 *   		- `text` : set the label of the button
 *   		- `classes` : add one or more classes to the button (comma-sep.)
 *   - `hideButtons` : set to true to show no buttons in the footer
 *   - `modalOptions` : extra options to pass on to the Bootstrap modal
 *   - `successCallback` : function that will be called if user clicks an affirmative button, like Yes or OK
 *   - `openCallback`
 *   - `openedCallback`
 *   - `closeCallback`
 *   - `closedCallback`
 *
 * @return {object} - jQuery reference to the modal in property `jqModal`
 */
appJS.showModal = function(parms) {
	var modalOpts, modalSelector = '#JsHelperModal', $firstInput;
	if (typeof parms == 'string') parms = {html: parms};
	if (parms.customModalSelector === 'confirm') {
		this.initBaseConfirmModal();
		modalSelector = '#JsHelperBaseConfirmModal';
	} else if (parms.customModalSelector === 'prompt') {
		this.initBasePromptModal();
		modalSelector = '#JsHelperBasePromptModal';
	} else if (typeof parms.customModalSelector != 'undefined') {
		modalSelector = parms.customModalSelector;
	} else {
		this.initBaseAlertModal();
		modalSelector = '#JsHelperBaseAlertModal';
	}
	if ($(modalSelector).length === 0) {
		appJS.systemError(new Error('Custom modal '+ modalSelector +' has not been loaded.<br>Using default instead.'), {terminate: false});
		modalSelector = '#JsHelperBaseAlertModal';  //fallback to default modal
		appJS.initBaseAlertModal();   //ensure default modal it exists
	}

	if (typeof parms.modalOptions != 'undefined') modalOpts = $.extend({keyboard: true}, parms.modalOptions);  //keyboard:true = enable Esc keypress to close the modal
	if (typeof parms.preventClose != 'undefined') modalOpts = $.extend(modalOpts, {keyboard: false, backdrop: 'static'});  //source: http://www.tutorialrepublic.com/faq/how-to-prevent-bootstrap-modal-from-closing-when-clicking-outside.php

	if ($(modalSelector).is(':visible') && parms.allowAdditional == true) {
		// TODO: clone the modal so we can lay it on top of the existing one (but what about event handlers then?)
		// Code for cloning: $div.clone().prop('id', 'klon'+num );
		// Keep a variable with number of currently shown modals and number of created modals
		// When adding modal check that the HTML for it has been created before trying to set it
	}

	if (parms.hideButtons === true) {
		$(modalSelector).find('.modal-footer').find('button').hide();
	} else if (typeof parms.buttonOptions != 'undefined') {
		var originalButtonOptions = {};
		$(modalSelector).find('.modal-footer').find('button').each(function(indx, elem) {
			originalButtonOptions[indx] = {
				text: $(this).html()
			};
			if (typeof parms.buttonOptions[indx] != 'undefined') {
				if (parms.buttonOptions[indx].text) {
					$(this).html(parms.buttonOptions[indx].text);
				}
				if (parms.buttonOptions[indx].classes) {
					$(this).addClass(parms.buttonOptions[indx].classes);
				}
			}
		});
	}

	var openCb = function(ev) {
		if (typeof parms.openCallback == 'function') {
			parms.openCallback(this, ev);
		}
		if (typeof parms.preventClose != 'undefined') {
			$(this).find('button.close').hide();
		}

		// Enable modal stacking
		// Source: http://stackoverflow.com/a/24914782/2404541
		var zIndex = 1040 + (10 * $('.modal:visible').length);
		// var zIndex = Math.max.apply(null, Array.prototype.map.call(document.querySelectorAll('*'), function(el) {
		// 	return +el.style.zIndex;
		// })) + 10;
		$(this).css('z-index', zIndex);
		setTimeout(function() {
			$('.modal-backdrop').not('.modal-stack').css('z-index', zIndex - 1).addClass('modal-stack');
		}, 0);

		if (typeof parms.successCallback == 'function') {
			var thisModal = this;
			$(this).find('.btn-yes, .btn-ok, .btn-affirmative').one('click.JsHelperModal', function(ev2) {
				parms.successCallback(thisModal, ev2);
			});
		}
		// NOTE: tried to make a cancelCallback as well that would be called if user clicks an canceling/negative/aborting button (like on `$('.btn-no, .btn-cancel, .btn-negative, button.close')`) but I couldn't easily catch the Escape keyup event (when keyboard:true) so I didn't complete it.
	};
	$(modalSelector).one('show.bs.modal', openCb);

	var openedCb = function(ev) {
		if (typeof parms.openedCallback == 'function') {
			parms.openedCallback(this, ev);
		}
		//put focus in first form field, if any
		$firstInput = $(modalSelector).find(':input:not(button):not(textarea):first');
		if ($firstInput.length > 0) {
			$firstInput.focus().select();
			if (parms.skipCloseOnInputEnter !== true) {
				$firstInput.on('keyup.JsHelperModal', function(ev) {
					if (ev && ev.keyCode == 13) {  //it happened that ev was undefined when I opened a modal and then just click OK...! Therefore check ev
						var $btn = $(modalSelector).find('.btn-yes, .btn-ok');
						if ($btn.length > 0) {
							$btn.trigger('click');
						} else {
							// for modals without a Yes button (just the default Close button)
							$(modalSelector).modal('hide');
						}
					}
				});
			}
		}
	};
	$(modalSelector).one('shown.bs.modal', openedCb);

	var closeCb = function(ev) {
		if (typeof parms.closeCallback == 'function') {
			parms.closeCallback(this, ev);
		}

		// Remove all events handlers
		$(modalSelector).find('button').off('click');
		$(modalSelector).find('.btn-yes, .btn-ok, .btn-affirmative').off('click.JsHelperModal');
		if ($firstInput.length > 0) {
			$firstInput.off('keyup.JsHelperModal');
		}
	};
	$(modalSelector).one('hide.bs.modal', closeCb);

	var closedCb = function(ev) {
		if (typeof parms.closedCallback == 'function') {
			parms.closedCallback(this, ev);
		}

		if (parms.hideButtons === true) {
			$(modalSelector).find('.modal-footer').find('button').show();
		} else if (typeof originalButtonOptions != 'undefined') {
			$(modalSelector).find('.modal-footer').find('button').each(function(indx, elem) {
				if (typeof originalButtonOptions[indx] != 'undefined') {
					if (originalButtonOptions[indx].text) {
						$(this).html(originalButtonOptions[indx].text);
					}
					if (parms.buttonOptions[indx].classes) {
						$(this).removeClass(parms.buttonOptions[indx].classes);
					}
				}
			});
		}

		// Cleanup from modal stacking
		//   Reduce z-index for the backdrop so it falls behind any underlying modal of the one we are closing now
		var $backDrop = $('.modal-backdrop.modal-stack');
		if ($backDrop.length > 0) {
			var currModalZindex = $('.modal:visible:last').css('z-index');
			$backDrop.css('z-index', currModalZindex - 1);
		}
	};
	$(modalSelector).one('hidden.bs.modal', closedCb);

	if (Boolean(parms.skipTitleHtml) == false) {
		if (typeof parms.title == 'undefined') parms.title = 'Information';
		$(modalSelector).find('.modal-title').html(parms.title);
	}
	$(modalSelector).find('.modal-body').html(parms.html);
	$(modalSelector).modal(modalOpts);

	return {
		jqModal: $(modalSelector),
		showButtons: function() {
			$(modalSelector).find('.modal-footer').find('button').show();
		}
	};
};
/**
 * Display a result message from an operation based on a standard response format from the server
 *
 * There is also a PHP version in the Result() class called htmlOutput().
 *
 * @param {object} arrResult - Result from the server (with properties `status`, `err_msg`, `result_msg` and optionally `err_msg_ext` - or `status`, `errors`, `notices` and optionally `errorsItemized`)
 * @param {string} okMessageHtml - Message to show if successful
 * @param {string} errorMessageHtml - Message to show in case of error(s)
 * @param {object} options - Any of these properties:
 * 	- `textPleaseNote` : text to append to the OK message if there are any result messages needed to be shown (defaults to "Please note" followed by a colon)
 * @return {string} - HTML code
 */
appJS.formatStdResult = function(arrResult, okMessageHtml, errorMessageHtml, options) {
	if (typeof options == 'undefined') options = {};
	var html = '', i, errors, notices, shown = [];
	if (typeof arrResult.err_msg !== 'undefined' || typeof arrResult.result_msg !== 'undefined') {
		errors = arrResult.err_msg;
		errorsItemized = arrResult.err_msg_ext;
		notices = arrResult.result_msg;
	} else {
		errors = arrResult.errors;
		errorsItemized = arrResult.errorsItemized;
		notices = arrResult.notices;
	}
	if (arrResult.status == 'ok') {
		html = '<div class="std-func-result ok">'+ okMessageHtml;
		if (notices.length > 0) {
			html += ' <span class="pls-note">'+ (options.textPleaseNote ? options.textPleaseNote : 'Please note') +':<span><ul>';
			for (i in notices) {
				if (notices.hasOwnProperty(i)) {
					html += '<li>'+ notices[i] +'</li>';
				}
			}
			html += '</ul>';
		}
		html += '</div>';
	} else {
		html = '<div class="std-func-result error">'+ errorMessageHtml +'<ul>';
		for (i in errors) {
			if (errors.hasOwnProperty(i)) {
				html += '<li>'+ errors[i] +'</li>';
				shown.push(errors[i]);
			}
		}
		if (typeof errorsItemized != 'undefined' && errorsItemized) {
			for (i in errorsItemized) {
				if (errorsItemized.hasOwnProperty(i)) {
					for (var j in errorsItemized[i]) {
						if (errorsItemized[i].hasOwnProperty(j) && $.inArray(errorsItemized[i][j], shown) == -1) {
							html += '<li>'+ errorsItemized[i][j] +'</li>';
						}
					}
				}
			}
		}
		html += '</ul></div>';
	}
	return html;
};

appJS.initBaseAlertModal = function(msg, options) {
	if ($('#JsHelperBaseAlertModal').length == 0) {
		var popupTemplate =
			'<div id="JsHelperBaseAlertModal" class="modal fade">'+
			'  <div class="modal-dialog">'+
			'    <div class="modal-content">'+
			'      <div class="modal-header">'+
			'        <button type="button" class="close" data-dismiss="modal">&times;</button>'+
			'        <h4 class="modal-title">Information</h4>'+
			'      </div>'+
			'      <div class="modal-body">[message placeholder]</div>'+
			'      <div class="modal-footer">'+
			'        <button type="button" class="btn btn-primary" data-dismiss="modal">OK</button>'+
			'      </div>'+
			'    </div>'+
			'  </div>'+
			'</div>';
		$('body').append(popupTemplate);
	}
}

appJS.initBaseConfirmModal = function(msg, options) {
	if ($('#JsHelperBaseConfirmModal').length == 0) {
		var popupTemplate =
			'<div id="JsHelperBaseConfirmModal" class="modal fade">'+
			'  <div class="modal-dialog">'+
			'    <div class="modal-content">'+
			'      <div class="modal-header">'+
			'        <button type="button" class="close" data-dismiss="modal">&times;</button>'+
			'        <h4 class="modal-title">Confirm</h4>'+
			'      </div>'+
			'      <div class="modal-body">Are you sure you want to do this?</div>'+
			'      <div class="modal-footer">'+
			'        <button type="button" class="btn btn-default btn-no" data-dismiss="modal">No</button>'+
			'        <button type="button" class="btn btn-primary btn-yes" data-dismiss="modal">Yes</button>'+
			'      </div>'+
			'    </div>'+
			'  </div>'+
			'</div>';
		$('body').append(popupTemplate);
	}
}

appJS.initBasePromptModal = function(msg, options) {
	if ($('#JsHelperBasePromptModal').length == 0) {
		var popupTemplate =
			'<div id="JsHelperBasePromptModal" class="modal fade">'+
			'  <div class="modal-dialog">'+
			'    <div class="modal-content">'+
			'      <div class="modal-header">'+
			'        <button type="button" class="close" data-dismiss="modal">&times;</button>'+
			'        <h4 class="modal-title"></h4>'+
			'      </div>'+
			'      <div class="modal-body">[message placeholder]</div>'+
			'      <div class="modal-footer">'+
			'        <button type="button" class="btn btn-default btn-cancel" data-dismiss="modal">Cancel</button>'+
			'        <button type="button" class="btn btn-primary btn-ok" data-dismiss="modal">&nbsp; &nbsp; OK &nbsp; &nbsp;</button>'+
			'      </div>'+
			'    </div>'+
			'  </div>'+
			'</div>';
		$('body').append(popupTemplate);
	}
}




/* ------------- Javascript messaging section ------------- */

/**
 * Show a system error
 *
 * Usage example: `appJS.systemError(new Error('Custom modal has not been loaded.<br>Using default instead.'), {terminate: false});`
 * Need to instantiate Error() there in order to get the line number.
 *
 * @param {Error} ErrorObject
 * @param {object} options - Available options:
 *   - `title` : set a title for the error
 *   - `terminate` : set to false to running Javascript after this error
 */
appJS.systemError = function(ErrorObject, options) {
	if (typeof options === 'undefined') options = {};
	if (typeof options.terminate === 'undefined') options.terminate = true;

	if (options.terminate) {
		window.onerror = function(message, url, line, column, ErrorObj) {
			return appJS.errorHandler(message, url, line, column, ErrorObj.stack, ErrorObj, options);
		};

		// Javascript Error() docs: https://developer.mozilla.org/en-US/docs/Web/JavaScript/Reference/Global_Objects/Error
		throw ErrorObject;  //at least in Chrome we can't get lineNumber property until the error is thrown
	} else {
		// Some information will not be available when continuing - and eg. lineNumber is currently not available in Chrome (but it's not so important since the file usually is minified anyway)
		// See also https://stackoverflow.com/questions/1340872/how-to-get-javascript-caller-function-line-number-how-to-get-javascript-caller
		appJS.errorHandler(ErrorObject.message, ErrorObject.fileName, ErrorObject.lineNumber, null, ErrorObject.stack, ErrorObject, options);
	}
};

/**
 * Error handler - DO NOT CALL DIRECTLY
 *
 * You should always be calling appJS.systemError().
 *
 * You might want to override this function in a site's global Javascript to call the backend and send us the message.
 *
 * @param {string} message
 * @param {object} options - Options from appJS.systemError()
 * @param {string} url
 * @param {integer} line
 * @param {integer} column
 * @param {Error} ErrorObject
 */
appJS.errorHandler = function(message, url, line, column, stack, ErrorObject, options) {
	if (!options.title) {
		options.title = 'Oops, something went wrong...';
	}
	var html = '<strong>'+ options.title +'</strong><div style="padding: 15px">'+ ErrorObject.message +'</div>Please let us know.';
	var $error = $('<div />').html(html)
		.css({position: 'absolute', top: '20px', left: '20px', backgroundColor: '#ffd800', padding: '20px', zIndex: 10000, borderRadius: '1px'});
	$('body').append($error);
};


appJS.systemMsg = {
	maxMsgs: 3,
	counter: 0,
	selector: '.system-msg',
	removeAfter: 0,
	add: function(msg, options) {
		var effRemAfter;
		appJS.systemMsg.counter++;
		var time = JSON.stringify(new Date()).substr(12).replace('"', '').replace('Z', '');
		console.info(''+ appJS.systemMsg.counter +'. '+ time +' '+ msg);
		var $msg = $(appJS.systemMsg.selector);

		// Add container if none found!
		if ($msg.length === 0) {
			$msg = $('<div />').addClass('system-msg').css({
				position: 'fixed',
				bottom: '10px',
				right: '10px',
				'pointer-events': 'none',
				'z-index': 20000
			});
			$('body').append($msg);
		}

		var $div = $('<div class="msg" style="color:#a9730e"><span style="font-size:80%;background-color:#a9730e;color:white;border-radius:2px">&nbsp;'+ appJS.systemMsg.counter +'&nbsp;</span> <span style="text-align:left;font-family: monospace">'+ time +'</span> &nbsp;'+ msg +'</div>');
		$msg.append($div);
		if ($msg.find('.msg').length > appJS.systemMsg.maxMsgs) {
			$msg.find('.msg:lt('+ ($msg.find('.msg').length - appJS.systemMsg.maxMsgs) +')').remove();
		}
		if (options && typeof options.removeAfter != 'undefined') {
			effRemAfter = options.removeAfter;
		} else {
			effRemAfter = appJS.systemMsg.removeAfter;
		}
		if (effRemAfter > 0) {
			setTimeout(function() {
				$($div).slideUp(1500);
			}, effRemAfter);
		}
	}
};





/* ------------- Other common utilities ------------- */

/**
 * Set a timeout but only execute it when browser tab has focused
 *
 * Can use `document.hasFocus()` at any time to check current status.
 *
 * @param {callable} callback : Function to call on timeout
 * @param {integer} duration : Milliseconds after which the earliest possible call to the function should be.
 * @return {void}
 */
appJS.setTimeoutFocusDependent = function(callback, duration) {
	var hasFocus = true;
	var skippedDueToNotFocused = false;

	var blurHandler = function() {
		hasFocus = false;
	};
	var focusHandler = function() {
		if (skippedDueToNotFocused) {
			callback();
			window.removeEventListener('blur', blurHandler);
			window.removeEventListener('focus', focusHandler);
			return;
		}
		hasFocus = true;
	};
	window.addEventListener('blur', blurHandler);
	window.addEventListener('focus', focusHandler);

	setTimeout(function() {
		if (hasFocus) {
			callback();
		} else {
			skippedDueToNotFocused = true;
		}
	}, duration);
};
