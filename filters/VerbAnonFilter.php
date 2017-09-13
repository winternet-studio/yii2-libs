<?php
namespace winternet\yii2\filters;

use Yii;
use yii\filters\VerbFilter;
use yii\web\NotFoundHttpException;

/**
 * VerbAnonFilter is an action filter that filters by HTTP request methods.
 *
 * This is identical to Yii's own `VerbFilter` except that it doesn't disclose
 * which request methods that are allowed and returns a 404 Not Found instead.
 *
 * @author Allan Jensen www.winternet.no
 */
class VerbAnonFilter extends VerbFilter {
	/**
	 * @inheritdoc
	 */
	public function beforeAction($event) {
		$action = $event->action->id;
		if (isset($this->actions[$action])) {
			$verbs = $this->actions[$action];
		} elseif (isset($this->actions['*'])) {
			$verbs = $this->actions['*'];
		} else {
			return $event->isValid;
		}

		$verb = Yii::$app->getRequest()->getMethod();
		$allowed = array_map('strtoupper', $verbs);
		if (!in_array($verb, $allowed)) {
			$event->isValid = false;
			// NOTE: can't use MethodNotAllowedHttpException because it requires exposing the allowed methods through the Allow header
			throw new NotFoundHttpException('Not Found');
		}

		return $event->isValid;
	}
}
