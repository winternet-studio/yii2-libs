<?php
namespace winternet\yii2\modules;

use Yii;

/**
 * Handle automatic page reloading when source files are changed
 *
 * For developers to easily see changes reflected on their site.
 *
 * Patterned after gulp-websocket server at https://github.com/SimonTart/gulp-websocket-server.
 */
class GulpReload extends \yii\base\Module {

	public $websocketHost = 'localhost';

	public $websocketPort = 4000;

	public $websocketPath = '/ws';

	/**
	 * @var null|callable : To manually modify the Javascript provide a function that takes a string as first argument and returns the modified string of Javascript code
	 */
	public $jsCallback = null;

	public function init() {
		parent::init();

		if (Yii::$app->request->isConsoleRequest) {
			return;
		}

        // Delay attaching event handler to the view component after it is fully configured (idea from yii\debug\Module)
        $app =& Yii::$app;
        $app->on(\yii\base\Application::EVENT_BEFORE_REQUEST, function () use ($app) {
            $app->getView()->on(\yii\web\View::EVENT_END_BODY, [$this, 'clientJs']);
        });
	}

	public function clientJs() {
		ob_start();
		// NOTE: increment the version number every time a change is made (so that $jsCallback can know if there are changes)
?>
//v1.
const ws = new WebSocket('ws://<?= $this->websocketHost ?>:<?= $this->websocketPort . $this->websocketPath ?>');
ws.addEventListener('error', err => {
	// console.error(err);
});
ws.addEventListener('message', event => {
	if (event.data === 'reload') {
		console.info('Reload requested by gulp');
		location.reload(true);
	} else {
		console.log('Unknown gulp event: '+ event.data);
		console.log(event);
	}
});
<?php
		$js = ob_get_clean();
		if (is_callable($this->jsCallback)) {
			$js = $this->jsCallback($js);
		}

		Yii::$app->controller->getView()->registerJs($js);
	}

}
