<?php
namespace winternet\yii2\validators;

use Yii;
use yii\validators\Validator;
use yii\validators\EmailValidator;

/**
 * EmailListValidator validates that the attribute value is a list of comma-separated valid email addresses.
 *
 * @author Allan Jensen, WinterNet Studio
 */
class EmailListValidator extends Validator {
	/**
	 * @var boolean whether to check whether the email's domain exists and has either an A or MX record.
	 * Be aware that this check can fail due to temporary DNS problems even if the email address is
	 * valid and an email would be deliverable. Defaults to false.
	 */
	public $checkDNS = false;

	/**
	 * @var boolean whether validation process should take into account IDN (internationalized domain
	 * names). Defaults to false meaning that validation of emails containing IDN will always fail.
	 * Note that in order to use IDN validation you have to install and enable `intl` PHP extension,
	 * otherwise an exception would be thrown.
	 */
	public $enableIDN = false;

	/**
	 * @var string the separator between the email addresses
	 */
	public $separator = ',';

	/**
	 * @var boolean is whitespace between the email addresses allowed?
	 */
	public $allowWhitespace = true;


	/**
	 * @inheritdoc
	 */
	public function init() {
		parent::init();

		if ($this->message === null) {
			$this->message = Yii::t('yii', '{attribute} is not a valid email address.');  //keep the same as EmailValidator to take advantage of Yii's already translated messages
			// NOTE: currently not really used because to just take the error message EmailValidator gives us - or make a special one.
		}
	}

	/**
	 * @inheritdoc
	 */
	protected function validateValue($value) {
		$valid = true;
		$message = null;

		if (!is_string($value)) {
			$valid = false;
		} else {
			$validator = new EmailValidator();
			$validator->checkDNS = $this->checkDNS;
			$validator->enableIDN = $this->enableIDN;

			if ($value) {
				$emails = explode($this->separator, $value);
				foreach ($emails as $email) {
					if ($this->allowWhitespace) {
						$email = trim($email);
					}

					if (!$validator->validate($email, $error)) {
						$valid = false;
						if (!$this->allowWhitespace && $validator->validate(trim($email), $error)) {
							$message = Yii::t('app', 'Whitespace is not allowed between email addresses.');
						} else {
							$message = $error;
						}
					}
				}
			}

		}

		return ($valid ? null : [ ($message !== null ? $message : $this->message), [] ]);
	}
}
