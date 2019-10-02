<?php
namespace winternet\yii2\test;

use yii\base\InvalidConfigException;
use yii\helpers\ArrayHelper;

/**
 * Custom version of [[yii\test\ActiveFixture]] that will not delete all records in table when unloading fixtures
 */
class CustomActiveFixture extends \yii\test\ActiveFixture {
	/**
	 * {@inheritdoc}
	 *
	 * This custom version will not delete all records in table when unloading fixtures but only the fixture data that was inserted.
	 * Currently only usable when a primary key exists and consists of a exactly one column.
	 */
	protected function resetTable() {
		$table = $this->getTableSchema();

		if (count($table->primaryKey) !== 1) {
            throw new InvalidConfigException(self::class .' currently only supports tables with a primary key consisting of a exactly one column.');
		}

		$IDs = ArrayHelper::getColumn($this->data, $table->primaryKey[0]);
		if (!empty($IDs)) {
			$this->db->createCommand()->delete($table->fullName, ['in', $table->primaryKey[0], $IDs])->execute();
			if ($table->sequenceName !== null) {
				$this->db->createCommand()->executeResetSequence($table->fullName, 1);
			}
		}
	}
}
