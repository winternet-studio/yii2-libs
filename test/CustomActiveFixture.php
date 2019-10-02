<?php
namespace winternet\yii2\test;

use yii\helpers\ArrayHelper;

/**
 * Custom version of [[yii\test\ActiveFixture]] that will not delete all records in table when unloading fixtures
 */
class CustomActiveFixture extends \yii\test\ActiveFixture {
	/**
	 * {@inheritdoc}
	 *
	 * This custom version will not delete all records in table when unloading fixtures but only the fixture data that was inserted
	 */
	protected function resetTable() {
		$table = $this->getTableSchema();
		$IDs = ArrayHelper::getColumn($this->data, 'discount_codeID');
		if (!empty($IDs)) {
			$this->db->createCommand()->delete($table->fullName, ['in', 'discount_codeID', $IDs])->execute();
			if ($table->sequenceName !== null) {
				$this->db->createCommand()->executeResetSequence($table->fullName, 1);
			}
		}
	}
}
