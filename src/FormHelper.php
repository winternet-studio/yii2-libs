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
	 * - 'view' : View object (normally automatically determined)
	 * - 'on_error' : name of callback function when submission caused some errors
	 * - 'on_success' : name of callback function when submission succeeded
	 * - 'on_successJS' : Javascript code to execute on success. Available variables are: rsp, form, errorCount
	 * - 'on_complete' : name of callback function that will always be called
	 * - 'on_completeJS' : Javascript code to execute on complete. Available variables are: rsp, form, errorCount
	 * - 'submitButtonTooltipText' : set custom text to use as the tooltip shown for a few seconds when the form contains errors (done by wsYii2.FormHelper.HighlightTabbedFormErrors.checkForErrors())
	 *
	 * @return JsExpression Javascript code
	 **/
	public static function processAjaxSubmit($options = []) {
		$js = "function(rsp) {";
		if (@$options['form']) {
			if (!@$options['view']) {
				$options['view'] = Yii::$app->controller->getView();
				if (!$options['view']) {
					new \winternet\yii2\UserException('Failed to automatically determine the view.');
				}
			}
			FormHelperAsset::register($options['view']);  //required for wsYii2 to be available

			// Apply the server-side generated errors to the form fields
			$js .= "var form = $('#". $options['form']->getId() ."');";
			$js .= "var applyResult = wsYii2.FormHelper.applyServerSideErrors(form, rsp);";
			$js .= "var errorCount = applyResult.errorCount;";

			// Always check if we are on a tabbed form and need to show the right tab
			$js .= "wsYii2.FormHelper.HighlightTabbedFormErrors.checkForErrors('#". $options['form']->options['id'] ."', ". json_encode(['submitButtonTooltipText' => @$options['submitButtonTooltipText']]) .");";
		} else {
			$js .= "var form, errorCount;";
			$js .= "if (rsp.err_msg) { errorCount = rsp.err_msg.length; } else if (rsp.errors) { errorCount = rsp.errors.length; }";
		}

		if (@$options['on_complete']) {
			$js .= $options['on_complete'] .'({form:form, rsp:rsp, errorCount:errorCount});';
		}
		if (@$options['on_completeJS']) {
			$js .= self::bindTags($options['on_completeJS'], $options['form']);
		}
		if (@$options['on_error'] || @$options['on_success']) {
			$js .= "if (errorCount > 0) {". (@$options['on_error'] ? $options['on_error'] .'({form:form, rsp:rsp, errorCount:errorCount});' : '') ."} else {". (@$options['on_success'] ? $options['on_success'] .'({form:form, rsp:rsp, errorCount:errorCount});' : '') ."}";
		}
		if (@$options['on_successJS']) {
			$js .= "if (errorCount == 0) {". self::bindTags($options['on_successJS'], @$options['form']) ."}";
		}
		$js .= "}";
		return new \yii\web\JsExpression($js);
	}

	protected static function bindTags($js, $form) {
		return str_replace('{currentForm}', "#". $form->options['id'], $js);
	}

	/**
	 * Generate Javascript code for handling a failed Ajax request with a JSON response, eg. a 500 Internal Server Error
	 *
	 * @param array $options : Associative array with any of these keys:
	 *   - `minimal` : set to true to generate very minimal code
	 *   - `jsBefore` : extra Javascript code to execute before doing our normal stuff
	 *   - `jsAfter`  : extra Javascript code to execute after doing our normal stuff
	 * @return yii\web\JsExpression
	 */
	public static function processAjaxSubmitError($options = []) {
		if (@$options['minimal']) {
			$js = "function(r,t,e) {";
			if (@$options['jsBefore']) {
				$js .= $options['jsBefore'];
			}
			$js .= "alert(e+\"\\n\\n\"+\$('<div/>').html((r.responseJSON && r.responseJSON.message ? r.responseJSON.message : r.responseText)).text());";
			if (@$options['jsAfter']) {
				$js .= $options['jsAfter'];
			}
			$js .= "}";
		} else {
			$js = "function(xhr, textStatus, errorThrown) {";
			if (@$options['jsBefore']) {
				$js .= $options['jsBefore'];
			}
			$js .= "var \$bg = \$('<div/>').addClass('jfw-yii2-ajax-error-bg').css({position: 'fixed', top: '0px', left: '0px', width: '100%', backgroundColor: '#595959'}).height(\$(window).height());";
			$js .= "var \$modal = \$('<div/>').addClass('msg').css({position: 'fixed', top: '100px', left: '50%', transform: 'translateX(-50%)', width: '70%', marginLeft: 'auto', marginRight: 'auto', backgroundColor: '#EEEEEE', padding: '30px', boxShadow: '0px 0px 28px 5px #232323'});";
			$js .= "\$modal.html('<h3>'+ errorThrown +' (Status '+ xhr.status +')</h3>'+ (xhr.responseJSON ? (xhr.responseJSON.name && xhr.responseJSON.name != errorThrown ? '<h4>'+ xhr.responseJSON.name +'</h4>' : '') + (xhr.responseJSON.message ? xhr.responseJSON.message : '') : xhr.responseText) +'<div><button class=\"btn btn-primary\" onclick=\"\$(this).parent().parent().parent().remove();\">OK</button></div>');";
			$js .= "\$bg.append(\$modal);";
			$js .= "\$('body').append(\$bg);";
			if (@$options['jsAfter']) {
				$js .= $options['jsAfter'];
			}
			$js .= "}";
		}
		return new \yii\web\JsExpression($js);
	}

	/**
	 * Add errors/notices from a Yii model to a Result instance
	 *
	 * Adding notices require a public property on the model called `_resultNotices`.
	 * 
	 * @param winternet\yii2\Result|null : Result instance, or provide null to have the method create an instance for you
	 * @param yii\base\model $model : A Yii model
	 * @param array $options : Available options:
	 *   - `forActiveForm` : set true to use attribute names as needed by ActiveForm
	 *
	 * @return winternet\yii2\Result
	 */
	public static function addModelResult($result, &$model, $options = []) {
		if (!$result) {
			$result = new \winternet\yii2\Result();
		}

		// Add errors
		foreach ($model->getErrors() as $attribute => $errors) {
			if (!empty($options['forActiveForm'])) {
				// Generate the form field ID so Yii ActiveForm client-side can apply the error message
				$attributeId = \yii\helpers\Html::getInputId($model, $attribute);
			} else {
				$attributeId = $attribute;
			}

			$result->addNamedErrors($attributeId, $errors);
		}

		// Add notices
		if ($result->noErrors() && property_exists($model, '_resultNotices') && is_array($model->_resultNotices) && !empty($model->_resultNotices)) {
			$result->addNotices($model->_resultNotices);
		}

		return $result;
	}

	/**
	 * Add errors from a Yii model to a standard result array
	 *
	 * @deprecated Should use the new [addModelResult()] instead.
	 *
	 * The new key 'err_msg_ext' MUST then be used for processing it (because 'err_msg' might not contain all error messages).
	 *
	 * @param array|winternet\yii2\Result : Empty variable (null, false, whatever), Result instance, or an associative array in this format: ['status' => 'ok|error', 'result_msg' => [], 'err_msg' => []]
	 * @param yii\base\model $model : A Yii model
	 * @param array $options : Associative array with any of these keys:
	 *   - `add_existing` : add the existing 'err_msg' array entries to 'err_msg_ext'
	 * @return array||winternet\yii2\Result : Result instance or Associative array in the format of $result but with the new key 'err_msg_ext'
	 */
	public static function addResultErrors($result, &$model, $options = []) {
		$usingResultClass = ($result && $result instanceof \winternet\yii2\Result ? true : false);

		if (!is_array($result) && !$usingResultClass) {
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
				$modelName = get_class($model);
				$modelName = mb_strtolower(substr($modelName, strrpos($modelName, '\\')+1));
			}

			$attributeId = $modelName .'-'. mb_strtolower($attr);

			if ($usingResultClass) {
				$result->addNamedErrors($attributeId, $errors);
			} else {
				$result['err_msg_ext'][$attributeId] = $errors;
			}
		}


		if (!$usingResultClass) {
			if (@$options['add_existing']) {
				if (!empty($result['err_msg'])) {
					$result['err_msg_ext']['_generic'] = $result['err_msg'];
				}
			}

			// Ensure correct status
			if (!empty($result['err_msg']) || !empty($result['err_msg_ext'])) {
				$result['status'] = 'error';
			}
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
	 * If option `modifyModel = false` only the POSTed value is modified by this function. Existing values in the model will not be
	 * modified (because the widget usually automatically handles the conversion).
	 *
	 * If option `modifyModel = true` also the timestamp of existing values in the model will be modified, eg. so that those values
	 * can be directly shown in the form.
	 *
	 * @param string $userTimeZone : Time zone of the end-user
	 * @param array $model : Model to convert timestamp attributes for (can just be an empty model)
	 * @param string $options : Available options:
	 *   - `format` (string) : how to format date using (as per date()). Default: `Y-m-d H:i:s`
	 *   - `modifyModel` (boolean) : default is false
	 *   - `multipleModels` (boolean) : set true to assume that the POST contains multiple of the given model,
	 *        ie. that the form of the fields are `$_POST[modelName][index][attribute]` instead of just the normal `$_POST[modelName][attribute]`
	 *        Default false.
	 *   - `customPostData` (array) : inject POST values manually if $_POST is not to be used.
	 *        Then the modified values are returned instead and they are not written back into neither $_POST nor Yii's request body params.
	 *
	 * @return void
	 **/
	public static function useTimeZone($userTimeZone, $model, $options = []) {
		$defaults = [
			'format' => 'Y-m-d H:i:s',
			'modifyModel' => false,
			'multipleModels' => false,
			'customPostData' => null,
			// NOT YET IMPLEMENTED. 'onlyAttributes' => [],
			// NOT YET IMPLEMENTED. 'exclAttributes' => [],
		];
		$options = array_merge($defaults, $options);

		date_default_timezone_set($userTimeZone);

		if ($options['customPostData']) {
			$postData = $options['customPostData'];
		} else {
			$postData = $_POST;
		}

		$formName = $model->formName();
		foreach ($model->getTableSchema()->columns as $attribute => $column) {
			if ($column->type == 'datetime' || $column->type == 'timestamp') {
				// Modify model value from UTC to user's timezone
				if ($options['modifyModel']) {
					$model->$attribute = Common::changeTimezone($model->$attribute, 'UTC', $userTimeZone, $options['format']);
				}

				if ($options['multipleModels']) {
					if (is_array($postData[$formName])) {
						foreach ($postData[$formName] as $currModelIndex => $currModelValues) {
							if ($postData[$formName][$currModelIndex][$attribute]) {
								$timestamp = Common::changeTimezone($postData[$formName][$currModelIndex][$attribute], $userTimeZone, 'UTC', $options['format']);

								if (!$options['customPostData']) {
									// Set standard $_POST variable
									$_POST[$formName][$currModelIndex][$attribute] = $timestamp;

									// Set Yii's bodyParams so that ->request->post() works
									$post = \Yii::$app->request->getBodyParams();
									$post[$formName][$currModelIndex][$attribute] = $timestamp;
									\Yii::$app->request->setBodyParams($post);
								} else {
									$postData[$formName][$currModelIndex][$attribute] = $timestamp;
								}
							}
						}
					}
				} else {
					if (@$postData[$formName][$attribute]) {
						$timestamp = Common::changeTimezone($postData[$formName][$attribute], $userTimeZone, 'UTC', $options['format']);

						if (!$options['customPostData']) {
							// Set standard $_POST variable
							$_POST[$formName][$attribute] = $timestamp;

							// Set Yii's bodyParams so that ->request->post() works
							$post = \Yii::$app->request->getBodyParams();
							$post[$formName][$attribute] = $timestamp;
							\Yii::$app->request->setBodyParams($post);
						} else {
							$postData[$formName][$attribute] = $timestamp;
						}
					}
				}
			}
		}

		if ($options['customPostData']) {
			return $postData;
		}
	}

}
