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

	public function addError($message) {
		$this->errorMessages[] = $message;
	}

	public function addErrors($arrayMessages) {
		$this->errorMessages = array_merge($this->errorMessages, $arrayMessages);
	}

	public function addNotice($message) {
		$this->resultMessages[] = $message;
	}

	public function getErrors() {
		return $this->errorMessages;
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

	public function output() {  //if I change the structure one day a new method could maybe be called toArray()...
		if (count($this->errorMessages) == 0) {
			return array(
				'status' => $this->status,
				'result_msg' => $this->resultMessages,
				'err_msg' => array(),
			);
		} else {
			$this->status = 'error';
			return array(
				'status' => $this->status,
				'result_msg' => array(),
				'err_msg' => $this->errorMessages,
			);
		}
	}
}
