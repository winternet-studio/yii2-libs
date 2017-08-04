<?php
namespace winternet\yii2;

use Yii;
use yii\base\Component;

class FormHelper extends Component {

	/**
	 * Generate Javascript code for handling response of an Ajax request that produces standard result/output JSON object with 'status', 'result_msg', and 'err_msg' keys in an array
	 *
	 * @param array $options Associative array with any of these keys:
	 * - 'form' : ActiveForm object
	 * - 'view' : View object. Required for automatic handling of form using Bootstrap Tabs
	 * - 'on_error' : name of callback function when submission caused some errors
	 * - 'on_success' : name of callback function when submission succeeded
	 * - 'on_successJS' : Javascript code to execute on success. Available variables are: rsp, form, errorCount
	 * - 'on_complete' : name of callback function that will always be called
	 * - 'on_completeJS' : Javascript code to execute on complete. Available variables are: rsp, form, errorCount
	 * 
	 * @return JsExpression Javascript code
	 **/
	public static function processAjaxSubmit($options = []) {
		$js = "function(rsp) {";
		if ($options['form']) {
			// Apply the server-side generated errors to the form fields
			$js .= "var form = $(_clickedButton).parents('form');
var errorCount = 0, a = [];
if (typeof rsp.err_msg_ext != 'undefined') {
	for (var x in rsp.err_msg_ext) {if (rsp.err_msg_ext.hasOwnProperty(x)){errorCount++;}}
	a = rsp.err_msg_ext;
}form.yiiActiveForm('updateMessages', a, true);";  // NOTE: errorCount MUST be determined before form.yiiActiveForm() because it modifies rsp.err_msg_ext! NOTE: updateMessages should always be called so that in case there are no error any previously set errors are cleared.

			if ($options['view']) {
				FormHelperAsset::register($options['view']);  //required for wsYii2 to be available
				$js .= "wsYii2.FormHelper.HighlightTabbedFormErrors.checkForErrors('#". $options['form']->options['id'] ."');";  //always check if we are on a tabbed form and need to show the right tab
			}
		} else {
			$js .= "var form, errorCount;";
			$js .= "if (rsp.err_msg) errorCount = rsp.err_msg.length;";
		}

		if ($options['on_complete']) {
			$js .= $options['on_complete'] .'({form:form, rsp:rsp, errorCount:errorCount});';
		}
		if ($options['on_completeJS']) {
			$js .= self::bindTags($options['on_completeJS'], $options['form']);
		}
		if ($options['on_error'] || $options['on_success']) {
			$js .= "if (errorCount > 0) {". ($options['on_error'] ? $options['on_error'] .'({form:form, rsp:rsp, errorCount:errorCount});' : '') ."} else {". ($options['on_success'] ? $options['on_success'] .'({form:form, rsp:rsp, errorCount:errorCount});' : '') ."}";
		}
		if ($options['on_successJS']) {
			$js .= "if (errorCount == 0) {". self::bindTags($options['on_successJS'], $options['form']) ."}";
		}
		$js .= "}";
		return new \yii\web\JsExpression($js);
	}

	protected static function bindTags($js, $form) {
		return str_replace('{currentForm}', "#". $form->options['id'], $js);
	}

	public static function processAjaxSubmitError($options = []) {
		/*
		DESCRIPTION:
		- generate Javascript code for handling a failed Ajax request with a JSON response, eg. a 500 Internal Server Error
		INPUT:
		- $options : associative array with any of these keys:
			- 'minimal' : set to true to generate very minimal code
		OUTPUT:
		- Javascript expression
		*/
		if ($options['minimal']) {
			$js = "function(r,t,e) {";
			$js .= "alert(e+\"\\n\\n\"+\$('<div/>').html(r.responseJSON.message).text());";
			$js .= "}";
		} else {
			$js = "function(xhr, textStatus, errorThrown) {";
			$js .= "var \$bg = \$('<div/>').addClass('jfw-yii2-ajax-error-bg').css({position: 'fixed', top: '0px', left: '0px', width: '100%', backgroundColor: '#595959'}).height(\$(window).height());";
			$js .= "var \$modal = \$('<div/>').addClass('msg').css({position: 'fixed', top: '100px', left: '50%', transform: 'translateX(-50%)', width: '70%', marginLeft: 'auto', marginRight: 'auto', backgroundColor: '#EEEEEE', padding: '30px', boxShadow: '0px 0px 28px 5px #232323'});";
			$js .= "\$modal.html('<h3>'+ errorThrown +'</h3>'+ xhr.responseJSON.message +'<div><button class=\"btn btn-primary\" onclick=\"\$(this).parent().parent().parent().remove();\">OK</button></div>');";
			$js .= "\$bg.append(\$modal);";
			$js .= "\$('body').append(\$bg);";
			$js .= "}";
		}
		return new \yii\web\JsExpression($js);
	}

	public static function addResultErrors($result, &$model, $options = []) {
		/*
		DESCRIPTION:
		- add errors from a Yii model to a standard result array
		- the new key 'err_msg_ext' MUST then be used for processing it (because 'err_msg' might not contain all error messages)
		INPUT:
		- $result : empty variable (null, false, whatever) or an associative array in this format: ['status' => 'ok|error', 'result_msg' => [], 'err_msg' => []]
		- $model : a Yii model
		- $options : associative array with any of these keys: 
			- 'add_existing' : add the existing 'err_msg' array entries to 'err_msg_ext'
		OUTPUT:
		- associative array in the format of $result but with the new key 'err_msg_ext'
		*/
		if (!is_array($result)) {
			$result = [
				'status' => 'ok',
				'result_msg' => [],
				'err_msg' => [],
			];
		}

		$modelErrors = $model->getErrors();

		foreach ($modelErrors as $attr => $errors) {
			// Generate the form field ID so Yii ActiveForm client-side can apply the error message
			if (!$modelName) {
				$modelName = $model::className();
				$modelName = mb_strtolower(substr($modelName, strrpos($modelName, '\\')+1));
			}

			$result['err_msg_ext'][$modelName .'-'. mb_strtolower($attr) ] = $errors;
		}


		if ($options['add_existing']) {
			if (!empty($result['err_msg'])) {
				$result['err_msg_ext']['_global'] = $result['err_msg'];
			}
		}

		// Ensure correct status
		if (!empty($result['err_msg']) || !empty($result['err_msg_ext'])) {
			$result['status'] = 'error';
		}

		return $result;
	}

	/**
	 * Generate Javascript code for remembering the selected tab in Bootstrap Tabs between page loads
	 *
	 * Done using HTML5 Local Storage
	 *
	 * @param yii\web\View $view
	 * @return string Javascript code
	 **/
	public static function TabbedFormRetainSelected($view) {
		FormHelperAsset::register($view);

		// TODO: develop this
	}

	/**
	 * Generate Javascript code for notifying about errors on the page and show the tab having the first error
	 *
	 * The init() function (and therefore calling `FormHelper::HighlightTabbedFormErrors()`) is only useful when
	 * normal form submit is done, not when doing AJAX submit (unless the event 'afterValidate' is triggered
	 * on the form before submit)
	 *
	 * @param yii\web\View $view
	 * @param yii\widgets\ActiveForm $activeForm
	 * @return string Javascript code
	 **/
	public static function HighlightTabbedFormErrors($view, $activeForm) {
		FormHelperAsset::register($view);

		$view->registerjs("wsYii2.FormHelper.HighlightTabbedFormErrors.init('#". $activeForm->options['id'] ."');");
	}

	/**
	 * Generate Javascript code for warning about leaving a page if it has unsaved changes
	 *
	 * Run this JS code after form has been saved: `wsYii2.FormHelper.WarnLeavingUnsaved.markSaved('#your-form-id')`
	 *
	 * @param yii\web\View $view
	 * @param yii\widgets\ActiveForm $activeForm
	 * @return string Javascript code
	 **/
	public static function WarnLeavingUnsaved($view, $activeForm) {
		FormHelperAsset::register($view);

		$view->registerJs("wsYii2.FormHelper.WarnLeavingUnsaved.init('#". $activeForm->options['id'] ."');");
	}
}
