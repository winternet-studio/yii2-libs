<?php
namespace winternet\yii2;

use Yii;
use yii\base\Component;

class Authorization extends Component {
	/**
	 * Get the effective roles of a user after any rules have been applied
	 *
	 * In particular you can't just use `Yii::$app->authManager->getRolesByUser()`
	 * if you are using `defaultRules` on the `authManager`.
	 *
	 * @param integer $userID
	 * @return array : Role names
	 */
	public static function effectiveRolesByUser($userID) {
		$roles = array_keys(Yii::$app->authManager->getRolesByUser($userID));  //source: https://stackoverflow.com/questions/25247675/how-to-get-user-role-in-yii2
		$roles = array_filter($roles, function($role) use (&$userID) {
			return Yii::$app->authManager->checkAccess($userID, $role);
		});

		return $roles;
	}
}
