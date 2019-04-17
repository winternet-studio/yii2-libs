<?php
namespace winternet\yii2\react;

use Yii;
use yii\base\Component;

/**
 * This is used to deal with create-react-app
 */
class CreateReactApp extends Component {
	public $path = null;

	/**
	 * @param string $path : Absolute path to the create-react-app folder that contains the package.json file
	 */
	function __construct($path) {
		$this->path = rtrim($path, '/');
	}

	/**
	 * @param mixed $data : Data that will be passed on to React through `window.pageData` in Javascript
	 */
	public function getIndexPage($data) {
		$view = Yii::$app->controller->getView();
		$view->registerLinkTag(['rel' => 'manifest', 'href' => '%PUBLIC_URL%/manifest.json']);
		$view->registerJs('window.pageData = '. json_encode($data), $view::POS_END);

		$pageHtml = '
			<noscript>You need to enable JavaScript to run this app.</noscript>
			<div id="root"></div>
		';

		if (YII_ENV_DEV) {
			// Render the page without the react scripts and inject that into the react development system (since React itself will inject the scripts)
			$this->developmentInjection(Yii::$app->controller->renderContent($pageHtml));

			// Get the compiled scripts to inject them into the HTML
			$craConfig = json_decode(file_get_contents($this->path .'/package.json'));
			$port = 3000;
			if (preg_match("/PORT=(\\d+)/i", $craConfig->scripts->start, $match)) {
				$port = $match[1];
			}
			$devServer = 'http://localhost:'. $port;

			$devHtml = file_get_contents($devServer);
			$reactScripts = $this->getReactScripts($devHtml);
			foreach ($reactScripts as $reactScript) {
				$view->registerJsFile($devServer . $reactScript .'?_='. microtime(true));
			}
		}

		// Now render again, this time including the react scripts, for the Yii output
		return Yii::$app->controller->renderContent($pageHtml);
	}

	public function developmentInjection($html) {
		$this->removeYiiDebuggerToolbar($html);

		file_put_contents($this->path .'/public/index.html', $html);

		return $html;
	}

	public function removeYiiDebuggerToolbar(&$html) {
		// Remove <div>
		$html = preg_replace('|<div id="yii-debug-toolbar".*</div>|siU', '', $html);

		// Remove <style>
		preg_match_all("|<style.*</style>|siU", $html, $styleMatches, PREG_SET_ORDER);
		foreach ($styleMatches as $style) {
			if (stripos($style[0], 'yii-debug') !== false) {
				$html = str_replace($style[0], '', $html);
			}
		}

		// Remove <script>
		preg_match_all("|<script.*</script>|siU", $html, $scriptMatches, PREG_SET_ORDER);
		foreach ($scriptMatches as $script) {
			if (stripos($script[0], 'yii-debug') !== false) {
				$html = str_replace($script[0], '', $html);
			}
		}
	}

	public function getReactScripts($html) {
		$scripts = [];

		preg_match_all("|<script.*</script>|siU", $html, $scriptMatches, PREG_SET_ORDER);
		foreach ($scriptMatches as $script) {
			$dom = new \DOMDocument;
			$dom->loadHTML($script[0]);
			$scriptElement = $dom->getElementsByTagName('script');
			$src = $scriptElement[0]->getAttribute('src');
			if (substr($src, 0, 8) == '/static/') {
				$scripts[] = $src;
			}
		}

		return $scripts;
	}
}
