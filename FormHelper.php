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
			// NOTE: the lonelyErrors are errors that have IDs that do not match anything in Yii's ActiveForm, so show those errors in a modal instead
			// TODO: move most of this JS code into FormHelper.js
			$js .= "var form = $(_clickedButton).parents('form');
var errorCount = 0, a = [];
if (typeof rsp.err_msg_ext != 'undefined') {
	var lonelyErrrors = [];
	for (var x in rsp.err_msg_ext) {
		if (rsp.err_msg_ext.hasOwnProperty(x)){
			errorCount++;
			if (typeof form.yiiActiveForm('find', x) == 'undefined') {
				lonelyErrrors.push([x, rsp.err_msg_ext[x][0]]);
			}
		}
	}
	if (lonelyErrrors.length > 0) {
		var html = [];
		for (var i = 0; i < lonelyErrrors.length; i++) {
			html.push('<li>'+ lonelyErrrors[i][0].replace(/^[a-z]+\\-/, '') +': '+ lonelyErrrors[i][1] + '</li>');
		}
		if (typeof appJS.showModal != 'undefined') {
			appJS.showModal({title: 'Errors', html: '<ul>'+ html.join('<br>') +'</ul>' });
		} else {
			alert('Errors:\\n\\n'+ html.join('\\n').replace(/<li>/g, '- ').replace(/<\\/li>/, ''));
		}
	}
	a = rsp.err_msg_ext;
}
form.yiiActiveForm('updateMessages', a, true);";  // NOTE: errorCount MUST be determined before form.yiiActiveForm() because it modifies rsp.err_msg_ext! NOTE: updateMessages should always be called so that in case there are no error any previously set errors are cleared.

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
			- 'jsBefore' : extra Javascript code to execute before doing our normal stuff
			- 'jsAfter'  : extra Javascript code to execute after doing our normal stuff
		OUTPUT:
		- Javascript expression
		*/
		if ($options['minimal']) {
			$js = "function(r,t,e) {";
			if ($options['jsBefore']) {
				$js .= $options['jsBefore'];
			}
			$js .= "alert(e+\"\\n\\n\"+\$('<div/>').html(r.responseJSON.message).text());";
			if ($options['jsAfter']) {
				$js .= $options['jsAfter'];
			}
			$js .= "}";
		} else {
			$js = "function(xhr, textStatus, errorThrown) {";
			if ($options['jsBefore']) {
				$js .= $options['jsBefore'];
			}
			$js .= "var \$bg = \$('<div/>').addClass('jfw-yii2-ajax-error-bg').css({position: 'fixed', top: '0px', left: '0px', width: '100%', backgroundColor: '#595959'}).height(\$(window).height());";
			$js .= "var \$modal = \$('<div/>').addClass('msg').css({position: 'fixed', top: '100px', left: '50%', transform: 'translateX(-50%)', width: '70%', marginLeft: 'auto', marginRight: 'auto', backgroundColor: '#EEEEEE', padding: '30px', boxShadow: '0px 0px 28px 5px #232323'});";
			$js .= "\$modal.html('<h3>'+ errorThrown +' (Status '+ xhr.status +')</h3>'+ (xhr.responseJSON ? (xhr.responseJSON.name && xhr.responseJSON.name != errorThrown ? '<h4>'+ xhr.responseJSON.name +'</h4>' : '') + (xhr.responseJSON.message ? xhr.responseJSON.message : '') : xhr.responseText) +'<div><button class=\"btn btn-primary\" onclick=\"\$(this).parent().parent().parent().remove();\">OK</button></div>');";
			$js .= "\$bg.append(\$modal);";
			$js .= "\$('body').append(\$bg);";
			if ($options['jsAfter']) {
				$js .= $options['jsAfter'];
			}
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

	/**
	 * Set timezone to use for timestamps in a form and ensure conversion back to UTC before saving in database,
	 * assuming that all timestamps are stored in UTC.
	 *
	 * Call this method in the controller, before calling the load() and save() methods.
	 * The timestamps coming back from the form must be of a format that is correctly parsed by the PHP DateTime() constructor.
	 *
	 * Note that unless the option `modifyModel = true` the timestamp on the model is not modified (because the widget usually 
	 * automatically handles the conversion), only the POSTed value is modified by this function.
	 *
	 * @param string $user_timezone Time zone of the end-user
	 * @param array $models Model to convert timestamp attributes for
	 * @param string $options Available options: 'format' (string), 'modifyModel' (boolean)
	 * @return void
	 **/
	public static function useTimeZone($user_timezone, $model, $options = []) {
		$defaults = [
			'format' => 'Y-m-d H:i:s',
			'modifyModel' => false,
			// NOT YET IMPLEMENTED. 'onlyAttributes' => [],
			// NOT YET IMPLEMENTED. 'exclAttributes' => [],
		];
		$options = array_merge($defaults, $options);

		date_default_timezone_set($user_timezone);

		foreach ($model->getTableSchema()->columns as $attribute => $column) {
			if ($column->type == 'datetime' || $column->type == 'timestamp') {
				if ($options['modifyModel']) {
					$t = new \DateTime($model->$attribute, new \DateTimeZone('UTC'));
					$t->setTimezone(new \DateTimeZone($user_timezone));
					$model->$attribute = $t->format($options['format']);
				}

				if ($_POST[ $model->formName() ][$attribute]) {
					$timestamp = new \DateTime($_POST[ $model->formName() ][$attribute], new \DateTimeZone($user_timezone));
					$timestamp->setTimezone(new \DateTimeZone('UTC'));

					// Set standard $_POST variable
					$_POST[ $model->formName() ][$attribute] = $timestamp->format($options['format']);

					// Set Yii's bodyParams so that ->request->post() works
					$post = \Yii::$app->request->getBodyParams();
					$post[ $model->formName() ][$attribute] = $timestamp->format($options['format']);
					\Yii::$app->request->setBodyParams($post);
				}
			}
		}
	}

}
