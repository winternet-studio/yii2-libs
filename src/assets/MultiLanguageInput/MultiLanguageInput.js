var multiLangInputWidget = {

	// instances: {},

	init: function(id, inputType, options) {
		var myself = this;

		//NOT USED this.instances[id] = {inputType: inputType, options: options};

		$('.'+ id +'-lang-input, #'+ id +'-plain').on('change', function(ev) {
			myself.updateValue(id);
		});

		$('#'+ id +' .language-handler button').on('click', function(ev) {
			myself.addLanguage(id);
		});

		$('#'+ id +' .input-group-addon a').on('click', function(ev) {
			myself.moveTop(id, ev.target);
		});

		$('#'+ id +' .disable-ml').on('click', function(ev) {
			myself.switchToNormal(id, options);
		});

		$('#'+ id +' .enable-ml').on('click', function(ev) {
			myself.switchToMulti(id, options);
		});
	},

	updateValue: function(id) {
		var data = [];
		if ($('#'+ id).hasClass('multi-lang-enabled')) {
			$('.'+ id +'-lang-input').each(function() {
				var lang = $(this).closest('.lang-input').attr('data-lang');
				var txt = $.trim($(this).val()).replace(',,,', '...');
				if (txt.length > 0) {
					data.push(lang.toUpperCase() +'='+ txt);
				}
			});
		} else {
			data.push( $('#'+ id +'-plain').val() );
		}
		$('.'+ id +'-compiled-input').val(data.join(',,,'));
	},

	addLanguage: function(id) {
		var lang = $('#'+ id +' .language-handler select').val();
		$('#'+ id +' .lang-input[data-lang='+ lang +']').show();
		$("#"+ id +" .language-handler select option[value='"+ lang +"']").remove();

		if ($("#"+ id +" .language-handler select option").length == 0) {
			$('#'+ id +' .language-handler').hide();
		}
	},

	moveTop: function(id, elem) {
		$(elem).closest('.lang-input').prependTo('#'+ id +' .language-inputs');
		this.updateValue(id);  //to compile texts in the right order
	},

	switchToNormal: function(id, options) {
		var myself = this;

		var translationsCount = 0;
		var oneTranslation = null;
		$('.'+ id +'-lang-input').each(function() {
			var txt = $.trim($(this).val()).replace(',,,', '...');
			if (txt.length > 0) {
				translationsCount++;
				oneTranslation = txt;
			}
		});

		if (translationsCount > 1) {
			myself.showAlert(options.txtOnlyOneTransl, options);
		} else {
			if (translationsCount == 0) {
				$('#'+ id +'-plain').show().val('');
			} else {
				$('#'+ id +'-plain').show().val(oneTranslation);
			}
			$('#'+ id +' .language-inputs, #'+ id +' .language-handler').hide();
			$('#'+ id +' .disable-ml, #'+ id +' .enable-ml').toggle();
			$('#'+ id).removeClass('multi-lang-enabled').addClass('multi-lang-disabled');
		}

		this.updateValue(id);
	},

	switchToMulti: function(id, options) {
		var currText = $('#'+ id +'-plain').val();
		$('.'+ id +'-lang-input').val('');
		$('#'+ id +'-plain').hide();

		$('#'+ id +' .language-inputs, #'+ id +' .language-handler').show();

		var $tmp = $('#'+ id +' .lang-input').first();
		$tmp.show();  //ensure the first language (=primary is always shown)
		$("#"+ id +" .language-handler select option[value='"+ $tmp.attr('data-lang') +"']").remove();  //and remove that from the list of langs to add

		$('.'+ id +'-lang-input:visible').first().val(currText);
		$('#'+ id +' .disable-ml, #'+ id +' .enable-ml').toggle();
		$('#'+ id).removeClass('multi-lang-disabled').addClass('multi-lang-enabled');

		this.updateValue(id);
	},

	showAlert: function(msg, options) {
		var popupTemplate =
		  '<div class="modal fade">'+
		  '  <div class="modal-dialog">'+
		  '    <div class="modal-content">'+
		  '      <div class="modal-header">'+
		  '        <button type="button" class="close" data-dismiss="modal">&times;</button>'+
		  '        <h4 class="modal-title">'+ options.txtModalTitle +'</h4>'+
		  '      </div>'+
		  '      <div class="modal-body">'+ msg +'</div>'+
		  '      <div class="modal-footer">'+
		  '        <button type="button" class="btn btn-primary" data-dismiss="modal">'+ options.txtOkButton +'</button>'+
		  // '        <button type="button" class="btn btn-link" data-dismiss="modal">Cancel</button>'+
		  '      </div>'+
		  '    </div>'+
		  '  </div>'+
		  '</div>';

		$(popupTemplate).modal();
	}
};
