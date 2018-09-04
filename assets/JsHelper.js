if (typeof appJS != 'undefined') {
	alert('CONFLICT ERROR! The variable appJS already exists in the global namespace of Javascript. winternet/yii2/JsHelper library will overwrite the variable.');
}

appJS = {
	translateText: null
};


/* ------------- AJAX section ------------- */

appJS.ajax = function(parms) {
	if (typeof parms.error == 'undefined') {
		var titleText = 'Sorry, we ran into a problem...';
		if (appJS.translateText !== null) {
			titleText = appJS.translateText('Sorry, we ran into a problem');
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
				html += 'Response from server: '+ (rsp.responseText === '' ? '<span style="color: #B0B0B0">EMPTY STRING</span>' : rsp.responseText);
				html += '<br />Text status: '+ status +'<br />Error thrown: '+ err;
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
	$.ajax(parms);
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
	appJS.disableSubmitElements = [];
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

appJS.doAjax = function(url, params, responseFormat, postActions, options, passInfo) {
	/*
	DESCRIPTION:
	- make a standard webservice call
	INPUT:
	- url : URL of the webservice
	- params : object with parameters to send to the webservice
	- responseFormat : how to interpret the data being returned from the webservice. Available options:
		- 'nothing' : don't do any postprocessing as nothing is being returned, or the returned should only be processed by post-processing function
		- 'resultError'       : the following fields are returned in an array: 'status' (with 'ok' or 'error'), 'err_msg' (array), 'result_msg' (array)
		- 'resultErrorQuiet' : same as above but status will only be given if there are specific messages to go along with it
		- 'resultOnly' : like 'resultError' but use the 'errorCallback:' in postActions to handle ALL aspects of dealing with errors (= error msgs not automatically shown)
		- 'resultOnlyQuiet' : like 'resultOnly' but with the difference described in 'resultErrorQuiet'
		- 'boolean'       : webservice returns true or false which respectively means succeeded or failed, and the user will be told so
		- 'booleanQuiet' : same as above but user will only be notified if operation failed
		- object with these keys for removing all existing entries in a dropdown box and fill with the items in the returned multi-dimensional associative array:
			{
				fillDropDown: true,
				selectSelector: 'selector for the select input field',
				valueColumn: 'name of key in the returned associative array that should be the value for the dropdown option',
				labelColumn: 'name of key in the returned associative array that should be the label for the dropdown option'
			}
			- note that if an item is currently selected it will retain that selection if one of the new items have the same value
	- postActions : array of actions to be done after the webservice has completed. An action is an object with one following keys:
		- 'successMessage' : set value with a message to the user if operation succeeds, eg. "Thank you for contacting us."
		- 'errorMessage' : set value with a message to the user if operation fails, eg. "Sorry, you message could not be sent."
		- 'reloadPage' : set to true to reload the page if operation succeeds (must be the last action to perform)
		- 'previousPage' : set to true to go back to the previous page in the browser history (must be the last action to perform)
		- 'redirectUrl' : set value to a URL to redirect to if operation succeeds (must be the last action to perform)
		- 'setHtml' : set to true to set the HTML (innerHtml property) for an element on the page if operation succeeds. Additional required keys:
			- 'selector' : selector for the element to set HTML for
			- 'html' : HTML to be set
		- 'successCallback' : set value to a string with function name or an anonymous function to call if operation succeeds. One argument is passed which will be an object with the following properties:
			- data : response from server
			- format : format of response data
			- inputParams : parameters sent to server
		- 'errorCallback' : set value to a string with function name or an anonymous function to call if operation fails. One argument is passed which will be an object with the following properties:
			- data : response from server
			- format : format of response data
			- inputParams : parameters sent to server
		- instead of one of the above you can also pass a function name in a string or write an anonymous function directly to be executed. One argument is passed which will be an object with the following properties:
			- data : response from server
			- success : boolean true or false based on response from server
			- format : format of response data
			- inputParams : parameters sent to server
	- options : object with nay of these options:
		- 'confirmMessage' : set a string with message to have the user confirm before executing the AJAX call
		- 'skipShowProcess' : set to true to not dim the page and show process status
		- 'requireSsl' : set to true to require SSL for transmitting this request to the server
		- 'ajaxOptions' : extra options for the jQuery ajax() call
		- 'textSuccess' : set custom message
		- 'textErrorBecause' : set custom message
		- 'textError' : set custom message
		- 'textPleaseNote' : set custom message
		- 'textSelectionCleared' : set custom message
	OUTPUT:
	- nothing (as everything is handled within the call itself)
	*/
	if (!$.isPlainObject(params)) params = {};
	if (!$.isPlainObject(options)) options = {};
	if (!$.isPlainObject(options.ajaxOptions)) options.ajaxOptions = {};

	options = $.extend({  //defaults
		confirmMessage: null,
		textSuccess: 'Operation completed successfully.',
		textErrorBecause: 'Sorry, operation could not be completed because:',
		textError: 'Sorry, the operation failed.',
		textPleaseNote: 'Please note',
		textSelectionCleared: 'Please note that current selection ({value}) in dropdown box was not re-selected after changing its options.',
	}, options);

	if (options.confirmMessage !== null) {
		this.showModal({
			customModalSelector: '#JsHelperBaseConfirmModal',
			title: (options.confirmTitle ? options.confirmTitle : 'Confirm'),
			html: (options.confirmMessage === true ? 'Are you sure you want to do this?' : options.confirmMessage),
			openedCallback: function(modalRef) {
				$(modalRef).find('.btn-yes').on('click', function() {
					options.confirmMessage = null;
					appJS.doAjax(url, params, responseFormat, postActions, options, passInfo);
				});
			}
		});
		return;
	}

	if (!options.skipShowProcess) {
		appJS.showProgressBar();
	}

	if (options.requireSsl) {
		if (url.substr(0, 4) == 'http') {
			if (url.substr(0, 5) != 'https') {
				appJS.showModal('This data may only be sent over a secure connection. Please contact website developer.');
			}
		} else if (document.location.protocol != 'https:') {
			appJS.showModal('This data may only be sent over a secure connection. Please contact website developer.');
		}
	}

	var parms = {
		url: url,
		type: ($.isEmptyObject(options) ? 'GET' : 'POST'),
		data: params,
		success: function(rsp, jqXHR, textStatus) {
			var i, args, functionName;
			var f = responseFormat;
			var success = true;
			var postModalActionsActivated = false;

			if (!f) f = '';
			postActions = (typeof postActions == 'string' ? [postActions] : postActions);  //if string was provided, convert it to an array

			// Postpone some post actions until after modal has been closed
			var postModalActions = function(allActions) {
				var postModalActions = [];
				for (var act in allActions) {
					if (!allActions.hasOwnProperty(act)) continue; //real keys will always be numeric

					if (typeof allActions[act].reloadPage !== 'undefined' || typeof allActions[act].redirectUrl !== 'undefined' || typeof allActions[act].previousPage !== 'undefined') {
						postModalActions.push(allActions[act]);

						// Remove it from the main array so it's not done immediately
						allActions.splice(act, 1);
					}
				}
				return postModalActions;
			};

			var modalClosedCallback = function(actions, isSuccess) {
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
			};

			if (f == 'resultError' || f == 'resultErrorQuiet' || f == 'resultOnly' || f == 'resultOnlyQuiet') {
				var isQuiet = (f == 'resultErrorQuiet' || f == 'resultOnlyQuiet' ? true : false);
				if (rsp.status == 'ok') {
					var msgCount = rsp.result_msg.length;
					if (msgCount == 0) {  //check if it's an object with properties (= text keys) instead of an array (= numeric keys)
						msgCount = Object.keys(rsp.result_msg).length;
					}
					if (!isQuiet || msgCount > 0) {
						var resultMsg = '<span class="result-text success-text">'+ options.textSuccess;
						if (msgCount > 0) {
							resultMsg += ' '+ options.textPleaseNote +':</span><br><br><span class="messages result-messages"><ul>';
							for (i in rsp.result_msg) {
								if (rsp.result_msg.hasOwnProperty(i)) {
									resultMsg += '<li>'+ rsp.result_msg[i] +'</li>';
								}
							}
							resultMsg += '</ul></span>';
						} else {
							resultMsg += '</span>';
						}

						var effActions = postModalActions(postActions);
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
						var errMsg = '<span class="result-text error-text">'+ options.textErrorBecause +'</span><br><br><span class="messages error-messages"><ul>';
						for (i in rsp.err_msg) {
							if (rsp.err_msg.hasOwnProperty(i)) {
								errMsg += '<li>'+ rsp.err_msg[i] +'</li>';
							}
						}
						errMsg += '</ul></span>';

						var effActions = postModalActions(postActions);
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
					var effActions = postModalActions(postActions);
					appJS.showModal({
						html: '<div class="ws-ajax-result">'+ options.textSuccess +'</div>',
						closedCallback: function() {
							modalClosedCallback(effActions, success);
						}
					});
				} else if (!rsp) {
					success = false;

					var effActions = postModalActions(postActions);
					appJS.showModal({
						html: '<div class="ws-ajax-result">'+ options.textError +'</div>',
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
					var effActions = postModalActions(postActions);
					appJS.showModal({
						html: options.textSelectionCleared.replace('{value}', cLbl),
						closedCallback: function() {
							modalClosedCallback(effActions, success);
						}
					});
				}
			} else if (f == 'nothing') {
				//do nothing
			}

			// Post actions
			if (typeof postActions == 'function') {
				args = {
					data: rsp,
					success: success,
					format: format,
					inputParams: inputParams,
				}
				postActions(args);
			} else {
				var c;
				for (var act in postActions) {
					if (!postActions.hasOwnProperty(act)) continue; //real keys will always be numeric
					c = postActions[act];
					if (success && typeof c.successMessage != 'undefined') {
						appJS.showModal(c.successMessage);
					} else if (!success && typeof c.errorMessage != 'undefined') {
						appJS.showModal(c.errorMessage);
					} else if (success && typeof c.setHtml != 'undefined') {
						$(c.selector).html(c.html);
					} else if (success && typeof c.successCallback != 'undefined') {
						if (typeof c.successCallback == 'string') {
							functionName = c.successCallback;
							window[functionName]({rsp: rsp, format: format, inputParams: inputParams});  //calling function in global scope
						} else {
							c.successCallback({rsp: rsp, format: format, inputParams: inputParams});
						}
					} else if (!success && typeof c.errorCallback != 'undefined') {
						if (typeof c.errorCallback == 'string') {
							functionName = c.substr(21);
							window[functionName]({rsp: rsp, format: format, inputParams: inputParams});  //calling function in global scope
						} else {
							c.errorCallback({rsp: rsp, format: format, inputParams: inputParams});
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

	$.extend(parms, options.ajaxOptions);

	if (!options.skipShowProcess) {
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

appJS.showModal = function(parms) {
	/*
	DESCRIPTION:
	- show a Bootstrap modal
	INPUT:
	- parms : string with HTML or object with these possible keys:
		- 'title' : title/headling for the modal
		- 'skipTitleHtml' : set to true to skip setting a title for the modal (to not override an existing title)
		- 'html' : HTML to show as content of the modal
		- 'customModalSelector' : selector for a custom modal to use as base for the modal (eg. #NumOfPagesToAddModal). Some ready-made presets are available:
			- '#JsHelperBaseConfirmModal' : confirmation modal with two buttons, default to No and Yes with classes btn-no and btn-yes respectively
			- '#JsHelperBasePromptModal' : confirmation modal with one button, default to Cancel and OK with classes btn-cancel and btn-ok respectively
		- 'preventClose' : set to true to prevent closing the modal with keyboard or by clicking outside the modal
		- 'skipCloseOnInputEnter' : set to true to prevent closing the modal when pressing Enter in the first input field
		- 'buttonOptions' : options for the buttons. Numeric object for each button, eg. for first button and second button: {0: {text: 'No, I do not agree'}, 1: {text: 'Yes, I agree'}}
			- available keys:
				- 'text' : set the label of the button
				- 'classes' : add one or more classes to the button (comma-sep.)
		- 'hideButtons' : set to true to show no buttons in the footer
		- 'modalOptions' : extra options to pass on to the Bootstrap modal
		- 'openCallback'
		- 'openedCallback'
		- 'closeCallback'
		- 'closedCallback'
	OUTPUT:
	- object with a jQuery reference to the modal in property `jqModal`
	*/
	var modalOpts, modalSelector = '#JsHelperModal';
	if (typeof parms == 'string') parms = {html: parms};
	if (parms.customModalSelector === '#JsHelperBaseConfirmModal') {
		this.initBaseConfirmModal();
		modalSelector = '#JsHelperBaseConfirmModal';
	} else if (parms.customModalSelector === '#JsHelperBasePromptModal') {
		this.initBasePromptModal();
		modalSelector = '#JsHelperBasePromptModal';
	} else if (typeof parms.customModalSelector != 'undefined') {
		modalSelector = parms.customModalSelector;
	} else {
		this.initBaseAlertModal();
		modalSelector = '#JsHelperBaseAlertModal';
	}
	if (typeof parms.modalOptions != 'undefined') modalOpts = $.extend({keyboard: true}, parms.modalOptions);  //keyboard:true = enable Esc keypress to close the modal
	if (typeof parms.preventClose != 'undefined') modalOpts = $.extend(modalOpts, {keyboard: false, backdrop: 'static'});  //source: http://www.tutorialrepublic.com/faq/how-to-prevent-bootstrap-modal-from-closing-when-clicking-outside.php

	if ($(modalSelector).is(':visible') && parms.allowAdditional == true) {
		// TODO: clone the modal so we can lay it on top of the existing one
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

	var openCb = function() {
		if (typeof parms.openCallback == 'function') {
			parms.openCallback(this);
			$(this).off('show.bs.modal', openCb);  //don't call again
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
	};
	$(modalSelector).on('show.bs.modal', openCb);

	var openedCb = function() {
		if (typeof parms.openedCallback == 'function') {
			parms.openedCallback(this);
			$(this).off('shown.bs.modal', openedCb);  //don't call again
		}
		//put focus in first form field, if any
		var $firstInput = $(modalSelector).find(':input:not(button):not(textarea):first');
		if ($firstInput.length > 0 && parms.skipCloseOnInputEnter !== true) {
			$firstInput.focus().select();
			$firstInput.on('keyup', function(ev) {
				if (ev.keyCode == 13) {
					var $btn = $(modalSelector).find('.btn-yes');
					if ($btn.length > 0) {
						$btn.trigger('click');
					} else {
						// for modals without a Yes button (just the default Close button)
						$(modalSelector).modal('hide');
					}
				}
			});
		}
	};
	$(modalSelector).on('shown.bs.modal', openedCb);

	var closeCb = function() {
		if (typeof parms.closeCallback == 'function') {
			parms.closeCallback(this);
			$(this).off('hide.bs.modal', closeCb);  //don't call again
		}
	};
	$(modalSelector).on('hide.bs.modal', closeCb);

	var closedCb = function() {
		if (typeof parms.closedCallback == 'function') {
			parms.closedCallback(this);
			$(this).off('hidden.bs.modal', closedCb);  //don't call again
		}
		//remove all events handlers on buttons
		$(modalSelector).find('button').off('click');

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
	$(modalSelector).on('hidden.bs.modal', closedCb);

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
appJS.formatStdResult = function(arrResult, okMessageHtml, errorMessageHtml, options) {
	/*
	DESCRIPTION:
	- display a result message from an operation based on a standard response format from the server
	INPUT:
	- arrResult : an parsed object with the result from the server (with properties 'status', 'err_msg', 'result_msg')
	- okMessageHtml : message to show if successful
	- errorMessageHtml : message to show in case of error(s)
	- options : object with any of these properties:
		- 'textPleaseNote' : text to append to the OK message if there are any result messages needed to be shown (defaults to "Please note" followed by a colon)
	OUTPUT:
	- string with HTML code
	*/
	var html = '', i, shown = [];
	if (arrResult.status == 'ok') {
		html = '<div class="std-func-result ok">'+ okMessageHtml;
		if (arrResult.result_msg.length > 0) {
			html += ' <span class="pls-note">'+ (options.textPleaseNote ? options.textPleaseNote : 'Please note') +':<span><ul>';
			for (i in arrResult.result_msg) {
				if (arrResult.result_msg.hasOwnProperty(i)) {
					html += '<li>'+ arrResult.result_msg[i] +'</li>';
				}
			}
			html += '</ul>';
		}
		html += '</div>';
	} else {
		html = '<div class="std-func-result error">'+ errorMessageHtml +'<ul>';
		for (i in arrResult.err_msg) {
			if (arrResult.err_msg.hasOwnProperty(i)) {
				html += '<li>'+ arrResult.err_msg[i] +'</li>';
				shown.push(arrResult.err_msg[i]);
			}
		}
		if (typeof arrResult.err_msg_ext != 'undefined') {
			for (i in arrResult.err_msg_ext) {
				if (arrResult.err_msg_ext.hasOwnProperty(i)) {
					for (var j in arrResult.err_msg_ext[i]) {
						if (arrResult.err_msg_ext[i].hasOwnProperty(j) && $.inArray(arrResult.err_msg_ext[i][j], shown) == -1) {
							html += '<li>'+ arrResult.err_msg_ext[i][j] +'</li>';
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
