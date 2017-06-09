if (typeof appJS != 'undefined') {
	alert('CONFLICT ERROR! The variable appJS already exists in the global namespace of Javascript. winternet/yii2-jshelper library will overwrite the variable.');
}

appJS = {};


/* ------------- AJAX section ------------- */

appJS.ajax = function(parms) {
	if (typeof parms.error == 'undefined') {
		var titleText = 'Sorry, we ran into a problem...';
		if (typeof translateText != 'undefined') {
			titleText = translateText('Sorry, we ran into a problem');
		} else if (typeof getphp != 'undefined' && getphp('Sorry_we_ran_into_a_problem')) {
			titleText = getphp('Sorry_we_ran_into_a_problem');
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




/* ------------- Modal section ------------- */

appJS.showModal = function(parms) {
	var modalOpts, modalSelector = '#JsHelperModal';
	if (typeof parms == 'string') parms = {html: parms};
	if (typeof parms.customModalSelector != 'undefined') modalSelector = parms.customModalSelector;
	if (typeof parms.modalOptions != 'undefined') modalOpts = $.extend({keyboard: true}, parms.modalOptions);  //keyboard:true = enable Esc keypress to close the modal
	if (typeof parms.preventClose != 'undefined') modalOpts = $.extend(modalOpts, {keyboard: false, backdrop: 'static'});  //source: http://www.tutorialrepublic.com/faq/how-to-prevent-bootstrap-modal-from-closing-when-clicking-outside.php

	if ($(modalSelector).is(':visible') && parms.allow_additional == true) {
		// TODO: clone the modal so we can lay it on top of the existing one
		// Code for cloning: $div.clone().prop('id', 'klon'+num );
		// Keep a variable with number of currently shown modals and number of created modals
		// When adding modal check that the HTML for it has been created before trying to set it
	}

	var openCb = function() {
		if (typeof parms.openCallback == 'function') {
			parms.openCallback(this);
			$(this).off('show.bs.modal', openCb);  //don't call again
		}
		if (typeof parms.preventClose != 'undefined') {
			$(this).find('button.close').hide();
		}
		if (1 /*try to always enable this and see if it will work out*/ || parms.stackable) {
			console.info('Enable modal stacking for '+ modalSelector);
			// Source: http://stackoverflow.com/a/24914782/2404541
			var zIndex = 1040 + (10 * $('.modal:visible').length);
			// var zIndex = Math.max.apply(null, Array.prototype.map.call(document.querySelectorAll('*'), function(el) {
			// 	return +el.style.zIndex;
			// })) + 10;
			$(this).css('z-index', zIndex);
			setTimeout(function() {
				$('.modal-backdrop').not('.modal-stack').css('z-index', zIndex - 1).addClass('modal-stack');
			}, 0);
		}
	};
	$(modalSelector).on('show.bs.modal', openCb);

	var openedCb = function() {
		if (typeof parms.openedCallback == 'function') {
			parms.openedCallback(this);
			$(this).off('shown.bs.modal', openedCb);  //don't call again
		}
		//put focus in first form field, if any
		var $firstInput = $(modalSelector).find(':input:not(button):not(textarea):first');
		if ($firstInput.length > 0) {
			$firstInput.focus().select();
			$firstInput.on('keyup', function(ev) {
				if (ev.keyCode == 13) {
					$(modalSelector).find('.btn-yes').trigger('click');
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
	};
	$(modalSelector).on('hidden.bs.modal', closedCb);

	if (Boolean(parms.skipTitleHtml) == false) {
		if (typeof parms.title == 'undefined') parms.title = 'Information';
		$(modalSelector)
			.find('.modal-title').html(parms.title).end()
			.find('.modal-body').html(parms.html);
	}
	$(modalSelector).modal(modalOpts);
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
	var html = '', i;
	if (arrResult.status == 'ok') {
		html = '<div class="std-func-result ok">'+ okMessageHtml;
		if (arrResult.result_msg.length > 0) {
			html += ' <span class="pls-note">'+ (options.textPleaseNote ? options.textPleaseNote : 'Please note') +':<span><ul>';
			for (i in arrResult.result_msg) {
				html += '<li>'+ arrResult.result_msg[i] +'</li>';
			}
			html += '</ul>';
		}
		html += '</div>';
	} else {
		html = '<div class="std-func-result error">'+ errorMessageHtml +'<ul>';
		for (i in arrResult.err_msg) {
			html += '<li>'+ arrResult.err_msg[i] +'</li>';
		}
		html += '</ul></div>';
	}
	return html;
};




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
