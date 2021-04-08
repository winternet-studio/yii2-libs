<?php
namespace winternet\yii2;

use Yii;
use yii\base\Component;

/**
 * This Yii component can be used to collect errors and notices (result messages) in methods/functions
 *
 * Note: It does not require to be used within a Yii application.
 *
 * Sample code:
 * ```
 * public function foo() {
 * 	$result = new \winternet\yii2\Result();
 *
 * 	if (somethingGoesWrong) {
 * 		$result->addError('Some message');
 * 	}
 *
 * 	if ($result->noErrors()) {
 * 		// continue with something
 * 	}
 *
 * 	$result->addData('rowsAffected', $x);
 *
 * 	return $result;
 * }
 *
 * ```
 */
class Result extends Component implements \JsonSerializable {

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
	 * Define what json_encode() should output when being passed this object
	 *
	 * For example used when response is set to JSON in controller action and we just return this object (as per the crud2 generator).
	 */
	public function jsonSerialize() {
		return $this->response();
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

	/**
	 * Add multiple errors
	 *
	 * @param array $arrayMessages : Array of errors. Good for passing in Yii2 model->getErrors() - which is actually handled by addAllNamedErrors(). Examples:
	 * ```
	 * [
	 *     'usr_firstname' => [
	 *         'Name is too short.',
	 *     ]
	 * ]
	 * ```
	 * ```
	 * [
	 *     '_generic' => [
	 *         'Person is already registered as arrived.',
	 *         'Booking has been canceled.',
	 *     ]
	 * ]
	 * ```
	 * ```
	 * [
	 *     'Person is already registered as arrived.',
	 *     'Booking has been canceled.',
	 * ]
	 * ```
	 * @param array $options : Available options:
	 *   - `prefix` : Prefix each error message with this string. Very useful when doing the same operation on multiple records in order to identity which record the individual error message is about.
	 *   - `suffix` : Suffix each error message with this string.
	 *   - `errorIfEmpty` : String with a generic error message to add if $arrayMessages is empty
	 */
	public function addErrors($arrayMessages, $options = []) {
		if (empty($arrayMessages)) {
			if ($options['errorIfEmpty']) {
				$this->addError($options['errorIfEmpty']);
			}
			return;
		}

		if ($options['prefix']) {
			$arrayMessages = $this->augmentMessages($arrayMessages, $options['prefix'], 'prefix');
		} elseif ($options['suffix']) {
			$arrayMessages = $this->augmentMessages($arrayMessages, $options['suffix'], 'suffix');
		}

		if (is_array(current($arrayMessages))) {  //detect if we have an array with attributes as keys which each have an array of error messages
			$this->addAllNamedErrors($arrayMessages);
		} else {
			// $arrayMessages is an array of strings
			$this->setStatus('error');
			$this->errorMessages['_generic'] = array_merge( (array) $this->errorMessages['_generic'], $arrayMessages);
		}
	}

	/**
	 * @param array $options : Available options:
	 *   - `prefix` : Prefix each error message with this string. Very useful when doing the same operation on multiple records in order to identity which record the individual error message is about.
	 *   - `suffix` : Suffix each error message with this string.
	 */
	public function addNamedErrors($name, $arrayMessages, $options = []) {
		$this->setStatus('error');

		if ($options['prefix']) {
			$arrayMessages = $this->augmentMessages($arrayMessages, $options['prefix'], 'prefix');
		} elseif ($options['suffix']) {
			$arrayMessages = $this->augmentMessages($arrayMessages, $options['suffix'], 'suffix');
		}

		$this->errorMessages[$name] = array_merge( (array) $this->errorMessages[$name], $arrayMessages);
	}

	private function addAllNamedErrors($arrayNames) {
		$this->setStatus('error');
		foreach ($arrayNames as $name => $errors) {
			if (isset($this->errorMessages[$name])) {
				$this->errorMessages[$name] = array_merge( (array) $this->errorMessages[$name], $errors);
			} else {
				$this->errorMessages[$name] = $errors;
			}
		}
	}

	/**
	 * Augment each message with a string as prefix or suffix
	 *
	 * @param array $arrayMessages : Same structure as passed to [[addErrors()]]
	 * @param string $string : String as prefix or suffix
	 * @param string $action : `prefix` or `suffix`
	 * @return array : Modified version of `$arrayMessages`
	 */
	public function augmentMessages($arrayMessages, $string, $action = 'prefix') {
		if (!empty($arrayMessages)) {
			if (is_array(current($arrayMessages))) {  //detect if we have an array with attributes as keys which each have an array of error messages
				foreach ($arrayMessages as $name => &$messages) {
					$messages = array_map(function($item) use (&$string, $action) {
						if ($action === 'prefix') {
							return $string . $item;
						} else {
							return $item . $string;
						}
					}, $messages);
				}
			} else {
				// $arrayMessages is an array of strings
				$arrayMessages = array_map(function($item) use (&$string, $action) {
					if ($action === 'prefix') {
						return $string . $item;
					} else {
						return $item . $string;
					}
				}, $arrayMessages);
			}
		}
		return $arrayMessages;
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
						$output[$currKey] = current($currValue);  //only return first error message of an attribute in the flat format
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
	 * Get a the first error (in flat format)
	 */
	public function getFirstErrorFlat() {
		return $this->getErrorsFlat()[0];
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


	/**
	 * Add arbitrary data to the result
	 *
	 * This method will also overwrite any existing data for the given key.
	 */
	public function addData($key, $value) {
		$this->otherInformation[$key] = $value;
	}

	public function getData($key) {
		return $this->otherInformation[$key];
	}

	public function getAllData() {
		return $this->otherInformation;
	}

	/**
	 * Filter data so that only the given properties (or array keys) are retained, or apply a custom filter function
	 *
	 * If $arrayOrCallable is an array, objects will be converted to arrays.
	 *
	 * Useful for ensuring that a result with undesired data is not sent client-side.
	 *
	 * @param string $key : Key that was used in addData()
	 * @param array|callback $arrayOrCallable : Array of property or array keys, or a callback function which takes the current value as first argument and which return the new info value to be stored
	 */
	public function filterData($key, $arrayOrCallable) {
		if (is_array($arrayOrCallable)) {
			if (!isset($this->otherInformation[$key]) || !$this->otherInformation[$key]) {
				// do nothing
			} elseif (is_object($this->otherInformation[$key]) || is_array($this->otherInformation[$key])) {
				if (is_object($this->otherInformation[$key])) {
					if (@constant('YII_BEGIN_TIME') && $this->otherInformation[$key] instanceof \yii\base\Model) {
						$this->otherInformation[$key] = $this->otherInformation[$key]->toArray($arrayOrCallable);
					} else {
						$this->otherInformation[$key] = get_object_vars($obj);
					}
				}
				if (is_array($this->otherInformation[$key])) {
					foreach ($this->otherInformation[$key] as $arrayKey => $arrayValue) {
						if (!in_array($arrayKey, $arrayOrCallable, true)) {
							unset($this->otherInformation[$key][$arrayKey]);
						}
					}
				}
			} else {
				throw new \Exception('Result information is not an object or an array.');
			}
		} elseif (is_callable($arrayOrCallable)) {
			if (!isset($this->otherInformation[$key]) || $this->otherInformation[$key] === null) {
				// do nothing
			} else {
				$this->otherInformation[$key] = $arrayOrCallable($this->otherInformation[$key]);
			}
		} else {
			throw new \Exception('Result  information is not an object or an array.');
		}
	}

	/**
	 * Filter result data so that only the keys specified are retained
	 *
	 * Useful for ensuring that a result with undesired keys are not sent client-side
	 *
	 * @param array $keys : Keys 
	 */
	public function filterAllData($keys) {
		foreach ($this->otherInformation as $key => $value) {
			if (!in_array($key, $keys, true)) {
				unset($this->otherInformation[$key]);
			}
		}
	}

	/**
	 * Raise error if an input array contains parameters/attributes the user does not have permission to set or does not exist
	 *
	 * @param $model : Yii2 model that has the valid parameters/attributes set as safe attributes (using scenarios)
	 * @param $attibutes : Associative array with attribute/value pairs. Example where eg. `invalidfield` will cause error:
	 * ```
	 * [
	 *   'firstname' => 'John',
	 *   'lastname' => 'Doe',
	 *   'invalidfield' => 'Something',
	 * ]
	 * ```
	 */
	public function validateModelAttributes($model, $attributes) {
		if (!is_array($attributes)) {
			$this->addError('Input is not an array.');
		} else {
			$invalidAttributes = array_diff(array_keys($attributes), $model->safeAttributes());
			if (!empty($invalidAttributes)) {
				$this->addError('Invalid parameters: '. implode(', ', $invalidAttributes));
				$this->addData('invalidParameters', array_values($invalidAttributes));  //array_values() gets rid of non-sequential numeric indexes
			}
		}
	}

	/**
	 * @deprecated Use [[addData()]] instead.
	 */
	public function addInfo($key, $value) {
		$this->addData($key, $value);
	}

	/**
	 * @deprecated Use [[getData()]] instead.
	 */
	public function getInfo($key) {
		return $this->getData($key);
	}

	/**
	 * @deprecated Use [[getAllData()]] instead.
	 */
	public function getAllInfo() {
		return $this->getAllData();
	}

	/**
	 * @deprecated Use [[filterData()]] instead.
	 */
	public function filterInfo($key, $arrayOrCallable) {
		$this->filterData($key, $arrayOrCallable);
	}

	/**
	 * @deprecated Use [[filterAllData()]] instead.
	 */
	public function filterAllInfo($keys) {
		$this->filterAllData($keys);
	}


	/**
	 * Set custom status
	 *
	 * Will only have effect if $this->errorMessages is empty, otherwise it will be overwritten with `error` in $this->response().
	 * It will also be reset to `error` each time an error is added afterwards.
	 */
	public function setStatus($status) {
		$this->status = $status;
	}


	/**
	 * Merge another [Result] instance into this one
	 *
	 * @param winternet\yii2\Result $Result
	 */
	public function merge($Result) {
		// Determine the final status
		if ($Result->status === 'error') {
			// error overrides anything else
			$this->status = 'error';
		} elseif ($this->status === 'ok' && $Result->status !== 'ok') {
			// have custom status from $Result
			$this->status = $Result->status;
		}

		$this->errorMessages = array_merge($this->errorMessages, $Result->getErrors());
		$this->resultMessages = array_merge($this->resultMessages, $Result->getNotices());
		$this->otherInformation = array_merge($this->otherInformation, $Result->getAllData());
	}


	/**
	 * Format result for output
	 *
	 * @deprecated Use [[response()]] instead.
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
	 *
	 * And if you just need all errors joined in a string you can do `Object.values(errors).join(' ')`.
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
			if (!in_array($key, ['status', 'notices', 'errors', 'errorsItemized'], true)) {  //protect these values
				$output[$key] = $value;
			}
		}

		if (@constant('YII_BEGIN_TIME') && !Yii::$app->request->isConsoleRequest) {
			// use "pretty" output in debug mode
			$formatter =& Yii::$app->response->formatters[\yii\web\Response::FORMAT_JSON];
			if (is_array($formatter)) {  //sometimes an array, other times an object
				$formatter['prettyPrint'] = (defined('YII_DEBUG') ? YII_DEBUG : false);
			} else {
				$formatter->prettyPrint = (defined('YII_DEBUG') ? YII_DEBUG : false);
			}

			/*
			DECIDED NOT TO DO THE FOLLOWING AFTERALL BECAUSE:
			- if other information is added that would also be force encoded as objects and that might not be desirable
			- Object.keys(array).forEach(function(key) { ... }) works fine on arrays as well! (since arrays are just a special type of objects)
			*/
			// For JSON output enforce encoding arrays as objects so the variable types will always be the same
			// Yii::$app->response->formatters[\yii\web\Response::FORMAT_JSON]['encodeOptions'] (or ->encodeOptions) = JSON_FORCE_OBJECT;
		}

		return $output;
	}


	/**
	 * Display the result message in HTML
	 *
	 * There is also a Javascript version in JsHelper.js
	 *
	 * @param string okMessageHtml : Message to show if successful
	 * @param string errorMessageHtml : Message to show in case of error(s)
	 * @param object options : Any of these properties:
	 * 	 - `textPleaseNote` : text to append to the OK message if there are any result messages needed to be shown (defaults to "Please note" followed by a colon)
	 * @return string : HTML code
	 */
	public function outputHtml($okMessageHtml = null, $errorMessageHtml = null, $options = []) {
		if (!$okMessageHtml) {
			$okMessageHtml = 'The operation completed successfully.';
		}
		if (!$errorMessageHtml) {
			$errorMessageHtml = 'Sorry, we could not complete the operation because:';
		}
		if ($this->status === 'ok') {
			$html = '<div class="alert alert-success std-func-result ok">'. $okMessageHtml;
			if (!empty($this->resultMessages)) {
				$html .= ' <span class="pls-note">'. ($options['textPleaseNote'] ? $options['textPleaseNote'] : 'Please note') .':<span><ul>';
				foreach ($this->resultMessages as $notice) {
					$html .= '<li>'. $notice .'</li>';
				}
				$html .= '</ul>';
			}
			$html .= '</div>';
		} else {
			$html = '<div class="alert alert-danger std-func-result error">'. $errorMessageHtml .'<ul>';
			foreach ($this->getErrorsFlat() as $error) {  //eventually we maybe want to do something special for itemized messages instead of just using the flat array
				$html .= '<li>'. $error .'</li>';
			}
			$html .= '</ul></div>';
		}
		return $html;
	}
}
