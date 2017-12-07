<?php
namespace winternet\yii2;

use Yii;
use yii\base\Component;
use yii\helpers\Url;

/**
 * This class can be used in place of raising a user exception when user doesn't not have access to the resource.
 *
 * If user is not logged in he will be redirected to the login page and afterwards be redirected back to the original
 * URL in the hope that he nows has permission to view.
 *
 * If user is logged in he will be shown the standard user exception.
 */
class NoAccess extends Component {

	/**
	 * The route for the login page
	 */
	public $loginRoute = 'site/login';

	/**
	 * Query string parameter name of the variable containing the URL to redirect to after logging in
	 */
	public $urlParam = 'redir';

	/**
	 * HTTP redirect status code to use
	 */
	public $redirectStatusCode = 307;

	/**
	 * Main method to use for handling the error
	 */
	public function error($message, $arrInternalInfo = [], $directives = 'AUTO') {
		if (Yii::$app->user && Yii::$app->user->isGuest) {
			// Give user a change to login first
			Yii::$app->response->redirect(Url::to([$this->loginRoute, $this->urlParam => Url::current()]), $this->redirectStatusCode)->send();
			Yii::$app->end();
		} else {
			// User is already logged in, just throw exception
			throw new UserException($message, $arrInternalInfo, $directives);
		}
	}
}
