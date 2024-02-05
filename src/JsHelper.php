<?php
namespace winternet\yii2;

use Yii;
use yii\base\Component;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\helpers\Url;

class JsHelper extends Component {
	public static $initModalDone = false;
	public static $defaultHideTitle = false;
	public static $defaultCloseHtml = false;
	public static $defaultButtonClose = false;
	public static $defaultButtonNo = false;
	public static $defaultButtonYes = false;

	public static $translateTextCallback = null;

	public static function initAjax($view = null) {
		if (!$view) {
			$view = Yii::$app->controller->getView();
		}

		self::loadAssets($view);  // ensure the AJAX section is available

		return self::initModal($view);
	}


	/**
	 * Generate Javascript code for making a standard AJAX call
	 *
	 * The AJAX call should produce standard result/output JSON object with `status`, `result_msg`, and `err_msg` keys in an array
	 *
	 * @param string $label
	 * @param string $url URL to call, or string with only letters a-z and hyphen to use the action of that name on current controller. In this case the $params will be sent as query string
	 * instead of POST variables.
	 * @param array|\yii\db\ActiveRecord $params An array or a Yii model. If Yii model: array is sent with key='id' and value=primary key of the model
	 * @param array $options
	 * @return JsExpression Javascript code
	 **/
	public static function ajaxLink($label, $url, $params = [], $options = []) {
		$defaults = [
			'linkOptions' => [],
			'responseFormat' => 'resultErrorQuiet',
			'postActions' => [ ['reloadPage' => true] ],
			'callOptions' => [],
			'confirmMessage' => null,
		];

		$options = array_merge($defaults, $options);

		if ($params instanceof \yii\db\ActiveRecord) {
			// $params is a Yii model
			$params = ['id' => $params->primaryKey];
		}

		if (preg_match("/^[a-z\\-]+$/", $url)) {
			$url = Url::to(  array_merge([Yii::$app->controller->id .'/'. $url], $params)  );
			$params = [];
		}

		if ($options['confirmMessage']) {
			$options['callOptions']['confirmMessage'] = $options['confirmMessage'];
		}

		self::initAjax();

		$options['linkOptions']['onclick'] = "appJS.doAjax({url: ". json_encode($url) .", params: ". json_encode($params) .", responseFormat: ". json_encode($options['responseFormat']) .", postActions: ". Json::encode($options['postActions']) .", options: ". Json::encode($options['callOptions']) ."});return false;";

		return Html::a($label, '#', $options['linkOptions']);
	}

	/**
	 * Initialize modals
	 *
	 * Call `initModal()` before call the Javascript function
	 *
	 * Parameters for appJS.showModal():
	 *
	 *	- string with HTML message
	 *		- OR
	 *	- object with keys:
	 *		- `title` (opt.)
	 *		- `html`
	 *		- `openCallback` (opt.) : before completing the UI
	 *		- `openedCallback` (opt.) : after completing the UI
	 *		- `closedCallback` (opt.) : after completing the UI
	 *		- `allow_additional` (opt.)
	 *		- `customModalSelector` (opt.) : selector for the HTML code for a custom modal that you want to use instead of #myModal
	 *
	 *	Example with buttons:
	 *
	 *	```
	 *	appJS.showModal({
	 *		html: 'Are you sure you want to remove the image?',
	 *		customModalSelector: '#JsHelperModal',
	 *		openedCallback: function(modalRef) {
	 *			$(modalRef).find('.btn-yes').on('click', function() {
	 *				// do something...
	 *			});
	 *		}
	 *	});
	 *	```
	 *
	 * @param string $view View to use if not the default (optional)
	 * @return string HTML
	 **/
	public static function initModal($view = null) {
		if (!$view) {
			$view = \Yii::$app->controller->getView();
		}

		// Don't return anything if already done in a previous call
		if (self::$initModalDone) {
			return '';
		}

		self::loadAssets($view);  // ensure the Modal section is available

		self::$initModalDone = true;
		return self::standardModal();
	}

	/**
	 * Generate HTML code for a default modal with a Close button
	 *
	 * @param array $options Associative array with any of these keys:
	 * - `id` : id to use for the modal. Default: JsHelperModal
	 * - `html` : custom message to show
	 * - `title` : custom title. Default: Are you sure?
	 * - `hideTitle` : set to true to not show the title bar
	 * - `buttonClose` : custom text for the close button. Default: Close
	 * - `hideButtons` : set to true to not show any buttons
	 **/
	public static function standardModal($options = [] ) {
		ob_start();

		if (!array_key_exists('hideTitle', $options) && self::$defaultHideTitle) {
			$options['hideTitle'] = true;
		}

		// NOTE: tabindex=-1 needed for Esc to work (http://stackoverflow.com/questions/12630156/how-do-you-enable-the-escape-key-close-functionality-in-a-twitter-bootstrap-moda)
?>
<div id="<?= (@$options['id'] ? $options['id'] : 'JsHelperModal') ?>" class="modal fade" role="dialog" tabindex="-1">
	<div class="modal-dialog">
		<div class="modal-content">
<?php
		if (!@$options['hideTitle']) {
?>
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal"><?= (self::$defaultCloseHtml ? self::$defaultCloseHtml : '&times;') ?></button>
				<h4 class="modal-title"><?= (@$options['title'] ? $options['title'] : 'Information') ?></h4>
			</div>
<?php
		}
?>
			<div class="modal-body">
				<p><?= (@$options['html'] ? $options['html'] : '[message placeholder]') ?></p>
			</div>
<?php
			if (!@$options['hideButtons']) {
?>
			<div class="modal-footer">
				<button type="button" class="btn btn-default" data-dismiss="modal"><?= (@$options['buttonClose'] ? $options['buttonClose'] : (self::$defaultButtonClose ? self::$defaultButtonClose : 'OK')) ?></button>
			</div>
<?php
			}
?>
		</div>
	</div>
</div>
<?php
		return ob_get_clean();
	}

	/**
	 * Generate HTML code for a default confirmation modal (Yes/No type of thing)
	 *
	 * @param array $options Associative array with any of these keys:
	 *	- `id` : id to use for the modal. Default: JsHelperModalConfirm
	 *	- `html` : custom message to show
	 *	- `title` : custom title. Default: Are you sure?
	 *	- `hideTitle` : set to true to hide the title bar
	 *	- `buttonNo` : custom text for decline button. Default: No
	 *	- `buttonYes` : custom text for accept button. Default: Yes
	 *
	 * @return string HTML
	 **/
	public static function confirmationModal($options = [] ) {
		ob_start();

		if (!array_key_exists('hideTitle', $options) && self::$defaultHideTitle) {
			$options['hideTitle'] = true;
		}

		// NOTE: tabindex=-1 needed for Esc to work (http://stackoverflow.com/questions/12630156/how-do-you-enable-the-escape-key-close-functionality-in-a-twitter-bootstrap-moda)
		// NOTE: avoid closing when button clicked: http://stackoverflow.com/questions/19073238/how-to-prevent-bootstrap-modal-from-closing-from-button-using-onclick#19078898
?>
<div id="<?= (@$options['id'] ? $options['id'] : 'JsHelperModalConfirm') ?>" class="modal fade" role="dialog" tabindex="-1">
	<div class="modal-dialog">
		<div class="modal-content">
<?php
		if (!@$options['hideTitle']) {
?>
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal"><?= (self::$defaultCloseHtml ? self::$defaultCloseHtml : '&times;') ?></button>
				<h4 class="modal-title"><?= (@$options['title'] ? $options['title'] : 'Are you sure?') ?></h4>
			</div>
<?php
		}
?>
			<div class="modal-body">
				<p><?= (@$options['html'] ? $options['html'] : 'Are you sure you want to do this?') ?></p>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-default btn-no" data-dismiss="modal"><?= (@$options['buttonNo'] ? $options['buttonNo'] : (self::$defaultButtonNo ? self::$defaultButtonNo : 'No')) ?></button>
				<button type="button" class="btn btn-primary btn-yes" data-dismiss="modal"><?= (@$options['buttonYes'] ? $options['buttonYes'] : (self::$defaultButtonYes ? self::$defaultButtonYes : 'Yes')) ?></button>
			</div>
		</div>
	</div>
</div>
<?php
		return ob_get_clean();
	}

	/**
	 * Generate HTML code for a default almost-fullscreen modal
	 *
	 * @param array $options Associative array with any of these keys:
	 *	- `id` : id to use for the modal. Default: JsHelperModalFullscreen
	 *	- `html` : custom message to show
	 *	- `title` : custom title. Default: Are you sure?
	 *	- `hideTitle` : set to true to hide the title bar
	 *	- `buttonClose` : custom text for the close button. Default: Close
	 *	- `hideButtons` : set to true to not show any buttons
	 *
	 * @return string HTML
	 **/
	public static function fullscreenModal($options = [] ) {
		ob_start();

		if (!array_key_exists('hideTitle', $options) && self::$defaultHideTitle) {
			$options['hideTitle'] = true;
		}

		// NOTE: tabindex=-1 needed for Esc to work (http://stackoverflow.com/questions/12630156/how-do-you-enable-the-escape-key-close-functionality-in-a-twitter-bootstrap-moda)
		// NOTE: avoid closing when button clicked: http://stackoverflow.com/questions/19073238/how-to-prevent-bootstrap-modal-from-closing-from-button-using-onclick#19078898
?>
<div id="<?= (@$options['id'] ? $options['id'] : 'JsHelperModalFullscreen') ?>" class="modal fade" role="dialog" tabindex="-1">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
<?php
		if (!@$options['hideTitle']) {
?>
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal"><?= (self::$defaultCloseHtml ? self::$defaultCloseHtml : '&times;') ?></button>
				<h4 class="modal-title"><?= @$options['title'] ?></h4>
			</div>
<?php
		}
?>
			<div class="modal-body"></div>
<?php
			if (!@$options['hideButtons']) {
?>
			<div class="modal-footer">
				<button type="button" class="btn btn-default" data-dismiss="modal"><?= (@$options['buttonClose'] ? $options['buttonClose'] : (self::$defaultButtonClose ? self::$defaultButtonClose : 'OK')) ?></button>
			</div>
<?php
			}
?>
		</div>
	</div>
</div>
<?php
		return ob_get_clean();
	}

	public static function systemMsgInit($options = []) {
		if (!@$options['view']) {
			$options['view'] = \Yii::$app->controller->getView();
		}

		self::loadAssets($options['view']);  // ensure the Javascript messaging section is available

		$js = [];
		if (@$options['removeAfter']) {
			$js[] = 'appJS.systemMsg.removeAfter = '. (int) $options['removeAfter'] .';';
		}
		if (@$options['selector']) {
			$js[] = 'appJS.systemMsg.selector = '. json_encode($options['selector']) .';';
		}
		if (!empty($js)) {
			$options['view']->registerJs(implode('', $js));
		}
	}

	/**
	 * Split <script> tags from output generated by Yii2's renderAjax() method
	 *
	 * @param string $html : Output from renderAjax()
	 * @param boolean $html : Set true to skip JSON encoding the output
	 * @return array
	 */
	public static function extractScripts($html, $skipJsonEncode = false) {
		$output = [
			'html' => null,
			'embeddedJs' => [],
			'linkedJs' => [],
		];
		if (preg_match_all("|<script.*</script>|siU", $html, $matches)) {
			foreach ($matches[0] as $script) {
				$innerHtml = trim(strip_tags($script));
				if ($innerHtml) {
					$output['embeddedJs'][] = $innerHtml;
				}
				if (stripos($script, '<script>') === false && preg_match('|src="(.*)"|iU', $script, $srcMatch)) {
					$output['linkedJs'][] = $srcMatch[1];
				}

				$html = str_replace($script, '', $html);
			}
			$output['html'] = trim($html);
		} else {
			$output['html'] = $html;
		}
		if ($skipJsonEncode) {
			return $output;
		} else {
			return json_encode($output);
		}
	}

	private static function loadAssets($view) {
		JsHelperAsset::register($view);  // ensure the AJAX section is available
		if (self::$translateTextCallback) $view->registerJs('appJS.translateText = '. self::$translateTextCallback .';');
	}
}
