<?php
namespace winternet\yii2;

use yii\base\Component;

class JobQueue extends Component {
	private $db = 'db';
	private $db_name = null;
	private $queueID = null;
	private $sanitize_callback = null;


	public function __construct($params = []) {
		if ($params['db']) {
			$this->db = $params['db'];
		}
		if ($params['db_name']) {
			$this->db_name = $params['db_name'];
		}
		if ($params['queueID']) {
			$this->queueID = $params['queueID'];
		}
	}

	public function startJob($jobID) {
		\Yii::$app->{$this->db}->createCommand("UPDATE ". ($this->db_name ? '`'. $this->db_name .'`.' : '') ."system_job_queue SET job_time_started = UTC_TIMESTAMP(), job_replaceID = NULL WHERE jobID = :id", ['id' => $jobID])->execute();
	}

	public function finishJob($jobID, $exit_status = 0) {
		\Yii::$app->{$this->db}->createCommand("UPDATE ". ($this->db_name ? '`'. $this->db_name .'`.' : '') ."system_job_queue SET job_time_completed = UTC_TIMESTAMP(), job_exit_status = :status WHERE jobID = :id", ['id' => $jobID, 'status' => $exit_status])->execute();
	}


	public function runJob($jobID) {
		$job = \Yii::$app->{$this->db}->createCommand("SELECT * FROM ". ($this->db_name ? '`'. $this->db_name .'`.' : '') ."system_job_queue WHERE jobiD = :id AND job_time_started IS NULL", ['id' => $jobID])->queryOne();

		if (empty($job)) return false;

		if ($job['job_queueID'] != $this->queueID) return false;  //skip jobs not in this queue

		if ($job['job_postpone_time'] && $job['job_postpone_time'] > (new DateTime())->getTimestamp() ) return false;  //skip jobs that have been postponed for later

		$this->startJob($job['jobID']);

		$command = json_decode($job['job_command']);

		if ($command->route) {
			$cmdargs = [];
			foreach ($command->params as $key => $value) {
				$cmdargs[] = '--'. $key .'="'. $value .'"';
			}

			$cmd = 'php yii '. $command->route;
			if ($cmdargs) {
				$cmd .= ' '. implode(' ', $cmdargs);
			}
		} else {
			if (!$this->$sanitize_callback || !is_callable($this->$sanitize_callback)) {
				// DON'T ALLOW UNSANITIZED COMMANDS FOR SECURITY REASONS. If someone gained access to the database and script runs in sudo (though is most cases that shouldn't be necessary) they would be able to run any command!
				throw new \yii\base\UserException('Missing function for sanitizing command from JobQueue.');
			} else {
				$cmd = $this->$sanitize_callback($command);
			}
		}

		$output = []; $exitcode = 0;
		$this->consoleLog('Command: '. $cmd);
		exec($cmd, $output, $exitcode);

		$this->finishJob($job['jobID'], $exitcode);

		return true;
	}

	public function runAll($params = []) {
		// Execute all pending jobs
		$jobs = \Yii::$app->{$this->db}->createCommand("SELECT * FROM ". ($this->db_name ? '`'. $this->db_name .'`.' : '') ."system_job_queue WHERE job_time_started IS NULL ORDER BY job_priority DESC")->queryAll();

		chdir(\Yii::getAlias('@app/'));
		$jobs_done = 0;
		$this->consoleLog('Started batch');

		foreach ($jobs as $job) {

			$was_run = $this->runJob($job['jobID']);

			if ($was_run) {
				$jobs_done++;
			}
		}

		$this->consoleLog('Ended batch ('. $jobs_done .' '. ($jobs_done == 1 ? 'job' : 'jobs') .' executed)');
	}

	public function listen($params = []) {
		// Continually run all jobs every X seconds
		$interval = 60;
		if ($params['interval']) {
			$interval = $params['interval'];
		}

		do {
			$this->run($params);
			$this->consoleLog('Sleeping '. $interval .' secs');

			\Yii::$app->{$this->db}->close();
			sleep($interval);
			\Yii::$app->{$this->db}->open();
		} while (true);
	}

	public function consoleLog($msg) {
		echo date('Y-m-d H:i:s') .'  '. $msg . PHP_EOL;
	}

	// -------------


	public function push($params = []) {
		/*
		DESCRIPTION:
		- add a job to the job queue
		INPUT:
		- $params : associative array with these keys:
			- 'queueID' (opt.) : name of queue to add the job to (if using multiple queues)
			- 'command' (req.) : command to execute. Usually in the format of {"route":"image/gen-thumbnail", "id":8256, "dest":"/storage/"}
				- non-strings are acceptable
			- 'replaceID' (opt.) : ID of the job which will cause a later job with the same ID to replace this job if it hasn't started yet
				(therefore once a job is started this ID is removed)
			- 'priority' (opt.) : set a custom priority, from -128 to 127 (default is 0)
			- 'delay_secs' (opt.) : number of seconds to delay the job (otherwise executed at next loop of the service)
		OUTPUT:
		- nothing
		*/
		if (!$params['command']) {
			throw new \yii\base\UserException('Command for adding JobQueue is not specified.');
		}

		$bindings = [];

		if ($params['replaceID']) {
			$bindings['replace'] = $params['replaceID'];
		} else {
			$bindings['replace'] = null;
		}

		$bindings['command'] = json_encode($params['command']);

		if (is_numeric($params['priority'])) {
			$bindings['priority'] = $params['priority'];
		} else {
			$bindings['priority'] = 10;
		}

		if (is_numeric($params['delay'])) {
			$bindings['delay'] = time() + $params['delay'];
		} else {
			$bindings['delay'] = null;
		}

		$bindings['queue'] = $this->queueID;

		$rowsaffected = \Yii::$app->{$this->db}->createCommand("REPLACE INTO ". ($this->db_name ? '`'. $this->db_name .'`.' : '') ."system_job_queue SET job_queueID = :queue, job_replaceID = :replace, job_command = :command, job_priority = :priority, job_postpone_time = :delay, job_time_added = UTC_TIMESTAMP()", $bindings)->execute();
		// return \Yii::$app->{$this->db}->getLastInsertID();
	}
}
