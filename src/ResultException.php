<?php
namespace winternet\yii2;

/**
 * Exception used when we want to apply errors to our Result object instead of raising an actual exception
 *
 * Typically used in a database transaction where we want to rollback because some error occurred and
 * then apply the errors to the \winternet\yii2\Result object.
 */
class ResultException extends \Exception {

	public $errors = [];

	/**
	 * @param array|string $errors : Array with errors (according to what is required for [[winternet\yii2\Result::addErrors()]]) (eg. the output from [[winternet\yii2\Result::getErrors()]]) or string with a single error message.
	 *
	 * Eg. for named errors: `['gdprAccepted' => ['GDPR consent is required.']]`
	 */
	public function __construct($errors) {
		if (is_string($errors)) {
			$errors = [ $errors ];
		}

		$this->errors = $errors;
	}

	public function getErrors() {
		return $this->errors;
	}

	/**
	 * @return string the user-friendly name of this exception
	 */
	public function getName() {
		return 'ResultException';
	}
}
