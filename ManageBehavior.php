<?php
namespace winternet\yii2;

use yii\base\Component;

class ManageBehavior extends Component {
	private $model;
	private $suspendedBehaviors = [];

	function __construct($model) {
		$this->model = $model;
	}

	public function suspendBehavior($className, $temporary_config = null) {
		// NOT YET IMPLEMENTED: set $temporary_config to array with config for the behavior so that it is modified instead of completely disabled
		foreach ($this->model->behaviors() as $key => $behav) {
			if ($behav['class'] == $className) {
				$this->suspendedBehaviors[$className] = $behav;
				$this->model->detachBehavior($key);
			}
		}
	}

	public function resumeBehavior($className) {
		if ($this->suspendedBehaviors[$className]) {
			foreach ($this->model->behaviors() as $key => $behav) {
				if ($behav['class'] == $className) {
					$this->model->attachBehavior($key, $this->suspendedBehaviors[$className]);
					unset($this->suspendedBehaviors[$className]);
				}
			}
		} else {
			new \app\components\Error('Cannot resume Behavior as it has not been suspended.', ['ClassName' => $className]);
		}
	}

	public function resumeAllBehaviors() {
		/*
		DESCRIPTION:
		- resumes all suspended behaviors
		INPUT:
		- nothing
		OUTPUT:
		- nothing
		*/
		foreach ($this->model->behaviors() as $key => $behav) {
			if ($this->suspendedBehaviors[$behav['class']]) {
				$this->model->attachBehavior($key, $this->suspendedBehaviors[$behav['class']]);
				unset($this->suspendedBehaviors[$behav['class']]);
			}
		}
	}
}
