<?php
namespace winternet\yii2\traits;

/**
 * Find or save models and raise error if it fails
 *
 * Adds methods to ActiveRecord that require finding a model when doing a find(), or saving successfully when doing a save().
 * If no model(s) are found or save fails an exception is thrown.
 */
trait ErrorIfNoSuccess {
	/**
	 * @param array $options : Available options:
	 *   - `message` : error message to use if model was not found
	 *   - `errorDetails` : array
	 *   - `errorOptions` : array
	 */
	public static function findOneOrFail($condition, $options = []) {
		$model = static::findOne($condition);

		if ($model == null) {
			static::raiseError($options['message'] ?? 'Failed to find '. static::class .' model.', (array) $options['errorDetails'], (array) $options['errorOptions']);
		}

		return $model;
	}

	/**
	 * @param array $options : Available options (also passed to `findOfUser()`):
	 *   - `message` : error message to use if model was not found
	 *   - `errorDetails` : array
	 *   - `errorOptions` : array
	 */
	public static function findOfUserOrFail($condition, $options = []) {
		$model = static::findOfUser($condition, $options);

		if ($model == null || empty($model)) {
			static::raiseError($options['message'] ?? 'Failed to find '. static::class .' model.', (array) $options['errorDetails'], (array) $options['errorOptions']);
		}

		return $model;
	}

	/**
	 * @param array $options : Available options:
	 *   - `message` : error message to use if model was not found
	 *   - `errorDetails` : array
	 *   - `errorOptions` : array
	 */
	public static function findMinimumOneOrFail($condition, $options = []) {
		$model = static::findAll($condition);

		if (empty($model)) {
			static::raiseError($options['message'] ?? 'Failed to find at least one '. static::class .' model.', (array) $options['errorDetails'], (array) $options['errorOptions']);
		}

		return $model;
	}

	/**
	 * @param array $options : Available options:
	 *   - `message` : error message to use if model failed to save
	 */
	public function saveOrFail($runValidation = true, $attributeNames = null, $options = []) {
		$result = $this->save($runValidation, $attributeNames);
		if (!$result) {
			static::raiseError($options['message'] ?? 'Failed to save '. get_class($this), ['Errors' => $this->getErrors(), 'Model' => $this->toArray() ]);
		}
		return $result;
	}

	public static function raiseError($message, $internalInfo = [], $options = []) {
		$componentName = 'system';
		$componentErrorMethod = 'error';
		if (isset(\Yii::$app->params['winternetYii2-systemErrorComponent'])) {  //option to override the default
			$componentName = \Yii::$app->params['winternetYii2-systemErrorComponent']['name'];
			$componentErrorMethod = \Yii::$app->params['winternetYii2-systemErrorComponent']['errorMethod'];
		}

		if (\Yii::$app->has($componentName) && \Yii::$app->$componentName->hasMethod($componentErrorMethod)) {
			// Normally: \Yii::$app->system->error()
			\Yii::$app->$componentName->$componentErrorMethod($message, $internalInfo, $options);
		} else {
			new \winternet\yii2\UserException($message, $internalInfo, $options);
		}
	}
}
