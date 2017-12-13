<?php
namespace winternet\yii2;

use Yii;
use yii\base\Component;

/**
 * This Yii component can be used to raise errors within the application.
 *
 * It can be configured to behave in many different ways and hence is much more flexible and 
 * useful than a standard user exception.
 */
class SystemError extends Component {

	public $senderEmail = null;
	public $developerEmail = null;
	public $adminEmail = null;


	// For noAccess() method:

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
	 * Raise a standard error
	 */
	public function error($message, $internalInfo = [], $options = []) {
		if (!is_array($options)) {
			throw new UserException('CONFIGURATION ERROR. Options parameter is not an array.', ['Message' => $message, 'InternalInfo' => $internalInfo, 'Options' => $options], ['notify' => 'developer']);
		}

		throw new UserException($message, $internalInfo, $this->mergeOptions($options));
	}

	/**
	 * Raise an error or if user is a guest redirect him to the login page
	 * 
	 * If user is not logged in he will be redirected to the login page and afterwards be redirected back to the original
	 * URL in the hope that he nows has permission to view.
	 *
	 * If user is logged in he will be shown the standard user exception.
	 */
	public function noAccess($message, $internalInfo = [], $options = []) {
		if (!is_array($options)) {
			throw new UserException('CONFIGURATION ERROR. Options parameter is not an array.', ['Message' => $message, 'InternalInfo' => $internalInfo, 'Options' => $options], ['notify' => 'developer']);
		}

		if (Yii::$app->getComponents()['user'] && Yii::$app->user->isGuest) {
			// Give user a change to login first
			Yii::$app->response->redirect(Url::to([$this->loginRoute, $this->urlParam => Url::current()]), $this->redirectStatusCode)->send();
			Yii::$app->end();
		} else {
			// User is already logged in, just throw exception
			throw new UserException($message, $internalInfo, $this->mergeOptions($options));
		}
	}

	private function mergeOptions($options) {
		if (!array_key_exists('senderEmail', $options) && $this->senderEmail) {
			$options['senderEmail'] = $this->senderEmail;
		}
		if (!array_key_exists('developerEmail', $options) && $this->developerEmail) {
			$options['developerEmail'] = $this->developerEmail;
		}
		if (!array_key_exists('adminEmail', $options) && $this->adminEmail) {
			$options['adminEmail'] = $this->adminEmail;
		}

		return $options;
	}
}
