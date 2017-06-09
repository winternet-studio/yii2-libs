<?php
namespace winternet\yii2;

use yii\base\Component;

class JsHelper extends Component {
	public static $initModalDone = false;
	public static $defaultHideTitle = false;
	public static $defaultCloseHtml = false;
	public static $defaultButtonClose = false;
	public static $defaultButtonNo = false;
	public static $defaultButtonYes = false;

	public static function initAjax($view) {
		JsHelperAsset::register($view);  // ensure the AJAX section is available

		return self::initModal($view);
	}

	public static function initModal($view) {
		/*
		DESCRIPTION:
		- call initModal() before call the Javascript function
		- parameters for appJS.showModal():
			- string with HTML message
				OR
			- object with keys:
				- 'title' (opt.)
				- 'html'
				- 'openCallback' (opt.) : before completing the UI
				- 'openedCallback' (opt.) : after completing the UI
				- 'closedCallback' (opt.) : after completing the UI
				- 'allow_additional' (opt.)
				- 'customModalSelector' (opt.) : selector for the HTML code for a custom modal that you want to use instead of #myModal
		INPUT:
		- 
		OUTPUT:
		- 
		*/

		/*

		Example with buttons:

			appJS.showModal({
				html: 'Are you sure you want to remove the image?',
				customModalSelector: '#JsHelperModalConfirm',
				openedCallback: function(modalRef) {
					$(modalRef).('.btn-yes').on('click', function() {
						// do something...
					});
				}
			});

		*/

		// Don't return anything if already done in a previous call
		if (self::$initModalDone) {
			return '';
		}

		JsHelperAsset::register($view);  // ensure the Modal section is available

		self::$initModalDone = true;
		return self::standardModal();
	}

	public static function standardModal($options = [] ) {
		/*
		DESCRIPTION:
		- generate HTML code for a default modal with a Close button
		INPUT:
		- $options : associative array with any of these keys:
			- 'id' : id to use for the modal. Default: JsHelperModal
			- 'html' : custom message to show
			- 'title' : custom title. Default: Are you sure?
			- 'hideTitle' : set to true to not show the title bar
			- 'buttonClose' : custom text for the close button. Default: Close
			- 'hideButtons' : set to true to not show any buttons
		OUTPUT:
		- 
		*/
		ob_start();

		if (!array_key_exists('hideTitle', $options) && self::$defaultHideTitle) {
			$options['hideTitle'] = true;
		}

		// NOTE: tabindex=-1 needed for Esc to work (http://stackoverflow.com/questions/12630156/how-do-you-enable-the-escape-key-close-functionality-in-a-twitter-bootstrap-moda)
?>
<div id="<?= ($options['id'] ? $options['id'] : 'JsHelperModal') ?>" class="modal fade" role="dialog" tabindex="-1">
	<div class="modal-dialog">
		<div class="modal-content">
<?php
		if (!$options['hideTitle']) {
?>
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal"><?= (self::$defaultCloseHtml ? self::$defaultCloseHtml : '&times;') ?></button>
				<h4 class="modal-title"><?= ($options['title'] ? $options['title'] : 'Title') ?></h4>
			</div>
<?php
		}
?>
			<div class="modal-body">
				<p><?= ($options['html'] ? $options['html'] : 'We have a message for you... Unfortunately we lost it on the way :(') ?></p>
			</div>
<?php
			if (!$options['hideButtons']) {
?>
			<div class="modal-footer">
				<button type="button" class="btn btn-default" data-dismiss="modal"><?= ($options['buttonClose'] ? $options['buttonClose'] : (self::$defaultButtonClose ? self::$defaultButtonClose : 'Close')) ?></button>
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

	public static function confirmationModal($options = [] ) {
		/*
		DESCRIPTION:
		- generate HTML code for a default confirmation modal (Yes/No type of thing)
		INPUT:
		- $options : associative array with any of these keys:
			- 'id' : id to use for the modal. Default: JsHelperModalConfirm
			- 'html' : custom message to show
			- 'title' : custom title. Default: Are you sure?
			- 'hideTitle' : set to true to hide the title bar
			- 'buttonNo' : custom text for decline button. Default: No
			- 'buttonYes' : custom text for accept button. Default: Yes
		OUTPUT:
		- 
		*/
		ob_start();

		if (!array_key_exists('hideTitle', $options) && self::$defaultHideTitle) {
			$options['hideTitle'] = true;
		}

		// NOTE: tabindex=-1 needed for Esc to work (http://stackoverflow.com/questions/12630156/how-do-you-enable-the-escape-key-close-functionality-in-a-twitter-bootstrap-moda)
		// NOTE: avoid closing when button clicked: http://stackoverflow.com/questions/19073238/how-to-prevent-bootstrap-modal-from-closing-from-button-using-onclick#19078898
?>
<div id="<?= ($options['id'] ? $options['id'] : 'JsHelperModalConfirm') ?>" class="modal fade" role="dialog" tabindex="-1">
	<div class="modal-dialog">
		<div class="modal-content">
<?php
		if (!$options['hideTitle']) {
?>
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal"><?= (self::$defaultCloseHtml ? self::$defaultCloseHtml : '&times;') ?></button>
				<h4 class="modal-title"><?= ($options['title'] ? $options['title'] : 'Are you sure?') ?></h4>
			</div>
<?php
		}
?>
			<div class="modal-body">
				<p><?= ($options['html'] ? $options['html'] : 'Are you sure you want to do this?') ?></p>
			</div>
			<div class="modal-footer">
				<button type="button" class="btn btn-default btn-no" data-dismiss="modal"><?= ($options['buttonNo'] ? $options['buttonNo'] : (self::$defaultButtonNo ? self::$defaultButtonNo : 'No')) ?></button>
				<button type="button" class="btn btn-primary btn-yes" data-dismiss="modal"><?= ($options['buttonYes'] ? $options['buttonYes'] : (self::$defaultButtonYes ? self::$defaultButtonYes : 'Yes')) ?></button>
			</div>
		</div>
	</div>
</div>
<?php
		return ob_get_clean();
	}

	public static function fullscreenModal($options = [] ) {
		/*
		DESCRIPTION:
		- generate HTML code for a default almost-fullscreen modal
		INPUT:
		- $options : associative array with any of these keys:
			- 'id' : id to use for the modal. Default: JsHelperModalFullscreen
			- 'html' : custom message to show
			- 'title' : custom title. Default: Are you sure?
			- 'hideTitle' : set to true to hide the title bar
			- 'buttonClose' : custom text for the close button. Default: Close
			- 'hideButtons' : set to true to not show any buttons
		OUTPUT:
		- 
		*/
		ob_start();

		if (!array_key_exists('hideTitle', $options) && self::$defaultHideTitle) {
			$options['hideTitle'] = true;
		}

		// NOTE: tabindex=-1 needed for Esc to work (http://stackoverflow.com/questions/12630156/how-do-you-enable-the-escape-key-close-functionality-in-a-twitter-bootstrap-moda)
		// NOTE: avoid closing when button clicked: http://stackoverflow.com/questions/19073238/how-to-prevent-bootstrap-modal-from-closing-from-button-using-onclick#19078898
?>
<div id="<?= ($options['id'] ? $options['id'] : 'JsHelperModalFullscreen') ?>" class="modal fade" role="dialog" tabindex="-1">
	<div class="modal-dialog modal-lg">
		<div class="modal-content">
<?php
		if (!$options['hideTitle']) {
?>
			<div class="modal-header">
				<button type="button" class="close" data-dismiss="modal"><?= (self::$defaultCloseHtml ? self::$defaultCloseHtml : '&times;') ?></button>
				<h4 class="modal-title"><?= $options['title'] ?></h4>
			</div>
<?php
		}
?>
			<div class="modal-body"></div>
<?php
			if (!$options['hideButtons']) {
?>
			<div class="modal-footer">
				<button type="button" class="btn btn-default" data-dismiss="modal"><?= ($options['buttonClose'] ? $options['buttonClose'] : (self::$defaultButtonClose ? self::$defaultButtonClose : 'Close')) ?></button>
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

	public static function systemMsgInit($view) {
		JsHelperAsset::register($view);  // ensure the Javascript messaging section is available
	}
}
