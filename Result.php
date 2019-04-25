<?php
namespace winternet\yii2;

use Yii;
use yii\base\Component;

/**
 * This Yii component can be used to collect errors and notices (result messages) in methods/functions
 */
class Result extends Component {

	public $status = 'ok';

	protected $errorMessages = [];

	protected $resultMessages = [];

	protected $otherInformation = [];

	/**
	 * @param array $outputFromOtherInstance : Pass the output from $this->output() to rebuild the object instance
	 */
	public function __construct($outputFromOtherInstance = null) {
		if (is_array($outputFromOtherInstance)) {
			foreach ($outputFromOtherInstance as $key => $value) {
				if ($key == 'status') {
					$this->status = $outputFromOtherInstance['status'];
				} elseif ($key == 'result_msg') {
					$this->resultMessages = $outputFromOtherInstance['result_msg'];
				} elseif ($key == 'err_msg') {
					// skip, this is just a different format than err_msg_ext
				} elseif ($key == 'err_msg_ext') {
					$this->errorMessages = $outputFromOtherInstance['err_msg_ext'];
				} else {
					$this->otherInformation[$key] = $value;
				}
			}
		}
	}

	/**
	 * @param string $message
	 * @param string $namedError
	 * @param array $options : Associative array with any of these options:
	 *   - `prepend` : set true to set the error as the first message instead of adding it to the end of the list
	 */
	public function addError($message, $namedError = null, $options = []) {
		$this->setStatus('error');

		// Handle arrays in case we by mistake pass that
		if (is_array($message)) {
			$keys = array_keys($message);
			if (is_array($message[$keys[0]])) {
				// Assume array was attributes as keys which each have an array of error messages (eg. from Yii2 model->getErrors() ).
				// Only add first error message
				$message = $message[$keys[0]][0];
				if (!is_numeric($keys[0])) {
					$namedError = $keys[0];
				}
			} else {
				// Coming here is basically forbidden but we don't want to halt script I think...
				$message = implode(' ', $message);  //do this just to make sure we only store strings
			}
		}

		if ($namedError != null) {
			if ($options['prepend'] && is_array($this->errorMessages[$namedError]) && !empty($this->errorMessages[$namedError])) {
				array_unshift($this->errorMessages[$namedError], $message);
			} else {
				$this->errorMessages[$namedError][] = $message;
			}
		} else {
			if ($options['prepend'] && is_array($this->errorMessages['_generic']) && !empty($this->errorMessages['_generic'])) {
				array_unshift($this->errorMessages['_generic'], $message);
			} else {
				$this->errorMessages['_generic'][] = $message;
			}
		}
	}

	public function addErrors($arrayMessages) {  //good for passing in Yii2 model->getErrors() - which is actually handled by addAllNamedErrors()
		if (empty($arrayMessages)) return;
		if (is_array(current($arrayMessages))) {  //detect if we have an array with attributes as keys which each have an array of error messages
			$this->addAllNamedErrors($arrayMessages);
		} else {
			$this->setStatus('error');
			$this->errorMessages['_generic'] = array_merge($this->errorMessages['_generic'], $arrayMessages);
		}
	}

	public function addNamedErrors($name, $arrayMessages) {
		$this->setStatus('error');
		$this->errorMessages[$name] = array_merge( (array) $this->errorMessages[$name], $arrayMessages);
	}

	private function addAllNamedErrors($arrayNames) {
		$this->setStatus('error');
		foreach ($arrayNames as $name => $errors) {
			$this->errorMessages[$name] = array_merge( (array) $this->errorMessages[$name], $errors);
		}
	}

	public function addNotice($message) {
		$this->resultMessages[] = $message;
	}

	public function addNotices($arrayMessages) {
		if (!empty($arrayMessages)) {
			$this->resultMessages = array_merge($this->resultMessages, $arrayMessages);
		}
	}


	/**
	 * Add errors to a Yii2 model
	 *
	 * @param yii\base\Model $model : Yii2 model
	 * @param array|string $onlyAttributes : String with single attribute name or array with multiple attribute names. Or set null to apply all errors
	 */
	public function applyErrors($model, $onlyAttributes = null) {
		// Handle string argument
		if ($onlyAttributes !== null) {
			if (is_string($onlyAttributes)) {
				$onlyAttributes = [$onlyAttributes];
			} else {
				$onlyAttributes = (array) $onlyAttributes; //just make sure it's an array
			}
		}

		// Go through each attribute of the model and add any errors we have for them
		$modelAttributes = $model->getAttributes();
		foreach ($modelAttributes as $modelAttribute) {
			if ($onlyAttributes === null || in_array($modelAttribute, $onlyAttributes))
			if (!empty($this->errorMessages[$modelAttribute])) {
				foreach ($this->errorMessages[$modelAttribute] as $attributeError) {
					$model->addError($modelAttribute, $attributeError);
				}
			}
		}
	}


	public function getNamedError($name) {
		return $this->errorMessages[$name][0];
	}

	public function getNamedErrors($name) {
		return $this->errorMessages[$name];
	}

	/**
	 * Get all errors
	 *
	 * Example:
	 * ```
	 * [
	 *	 '_generic' => [
	 *	 	'Some error',
	 *	 	'Another error'
	 *	 ],
	 *	 'firstname' => [
	 *	 	'Too short.',
	 *	 	'&-character not allowed'
	 *	 ],
	 *	 'lastname' => [
	 *	 	'Too long.'
	 *	 ]
	 * ]
	 * ```
	 */
	public function getErrors() {
		return $this->errorMessages;
	}

	/**
	 * Get a flat array of all errors
	 *
	 * Eg.: `['Some error.', 'Another error.', 'firstname' => 'Too short.', 'lastname' => 'Too long.']`
	 */
	public function getErrorsFlat() {
		if (!empty($this->errorMessages)) {  // make a flat array
			$output = [];
			foreach ($this->errorMessages as $currKey => $currValue) {
				if ($currKey == '_generic') {
					foreach ($currValue as $currGenericKey => $currGenericValue) {
						$output[] = $currGenericValue;
					}
				} else {
					if (is_array($currValue)) {
						$output[$currKey] = $currValue[0];  //only return first error message of an attribute in the flat format
					} else {
						$output[$currKey] = $currValue;
					}
				}
			}
			return $output;
		}
		return [];
	}

	/**
	 * Get a string with all the errors, concatenated with a space
	 */
	public function getErrorsString() {
		return implode(' ', $this->getErrorsFlat());
	}

	public function getNotices() {
		return $this->resultMessages;
	}

	public function resetErrors() {
		$this->errorMessages = [];
	}

	public function resetNotices() {
		$this->resultMessages = [];
	}


	public function noErrors() {
		return (empty($this->errorMessages) ? true : false);
	}

	public function hasErrors() {
		return !$this->noErrors();
	}

	public function success() {
		return $this->noErrors();
	}


	public function addInfo($key, $value) {
		$this->otherInformation[$key] = $value;
	}

	public function getInfo($key) {
		return $this->otherInformation[$key];
	}


	/**
	 * Set custom status
	 *
	 * Will only have effect if $this->errorMessages is empty, otherwise it will be overwritten with `error` in $this->output().
	 * It will also be reset to `error` each time an error is added afterwards.
	 */
	public function setStatus($status) {
		$this->status = $status;
	}


	/**
	 * Format result for output
	 *
	 * LEGACY method. Use response() instead.
	 */
	public function output($options = []) {
		if (count($this->errorMessages) == 0) {
			$output = array(
				'status' => $this->status,
				'result_msg' => $this->resultMessages,
				'err_msg' => [],
				'err_msg_ext' => [],
			);
		} else {
			$this->setStatus('error');
			$output = array(
				'status' => $this->status,
				'result_msg' => [],
				'err_msg' => $this->getErrorsFlat(),  // flat array, can have a mix of integer and string/named keys
				'err_msg_ext' => $this->errorMessages,  // not flat, has named keys which then always have an array of values
			);
		}
		foreach ($this->otherInformation as $key => $value) {
			$output[$key] = $value;
		}
		return $output;
	}

	/**
	 * Format result for a response
	 *
	 * In Javascript use `Object.keys(array).forEach(function(key) { ... })` for iterating through the errors and notices.
	 */
	public function response() {
		/*
		NOTES:
		- decided not to go for the full blown "envelope" style where all below would be put under a "result" property and everything else under a "data" property. Reasons being:
			- the axios HTTP client already put this entire response body under a "data" property, so if we also add "data" one would have to write response.data.data.someThing - not too beautiful.
			- if we really need all other information gathered under one property we could just manually do that when assigning the data.
		- decided to call this method response() instead of toArray() or return()
		*/
		if (count($this->errorMessages) == 0) {
			$output = [
				'status' => $this->status,
				'notices' => $this->resultMessages,
				'errors' => [],
				'errorsItemized' => [],
			];
		} else {
			$this->setStatus('error');
			$output = [
				'status' => $this->status,
				'notices' => [],
				'errors' => $this->getErrorsFlat(),  // flat array, can have a mix of integer and string/named keys
				'errorsItemized' => $this->errorMessages,  // not flat, has named keys which then always have an array of values
			];
		}
		foreach ($this->otherInformation as $key => $value) {
			$output[$key] = $value;
		}

		Yii::$app->response->formatters[\yii\web\Response::FORMAT_JSON]['prettyPrint'] = (defined('YII_DEBUG') ? YII_DEBUG : false); // use "pretty" output in debug mode

		/*
		DECIDED NOT TO DO THE FOLLOWING AFTERALL BECAUSE:
		- if other information is added that would also be force encoded as objects and that might not be desirable
		- Object.keys(array).forEach(function(key) { ... }) works fine on arrays as well! (since arrays are just a special type of objects)
		*/
		// For JSON output enforce encoding arrays as objects so the variable types will always be the same
		// Yii::$app->response->formatters[\yii\web\Response::FORMAT_JSON]['encodeOptions'] = JSON_FORCE_OBJECT;

		return $output;
	}
}
