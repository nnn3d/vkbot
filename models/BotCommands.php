<?php

namespace app\models;

use Yii;
use app\models\PendingTasks;
use app\models\Commands;
use app\models\Chats;

class BotCommands {

	public static $timeDeleteDuel = 250;
	public static $timeDeleteMarriage = 250;

	public static function init()
	{
		static::updatePendingTasks();
	}

	public static function updatePendingTasks()
	{
		PendingTasks::deleteByTask(COMMAND_BOT);
		PendingTasks::add(null, ['function' => 'deleteOldDuels'], 60, null, COMMAND_BOT);
		PendingTasks::add(null, ['function' => 'deleteOldMarriage'], 600, null, COMMAND_BOT);
	}


	public static function runPendingTask($task)
	{
		if (isset($task->getArgs()['function']) && method_exists(__CLASS__, $task->getArgs()['function'])) {
			static::{$task->getArgs()['function']}();
		}
	}

	public static function deleteOldDuels()
	{
		$time = time();
		foreach (Commands::findAll(['command' => COMMAND_DUEL]) as $command) {
			if ($time - $command->time > static::$timeDeleteDuel) {
				$command->delete();
			}
		}
	}

	public static function deleteOldMarriage()
	{
		$time = time();
		foreach (Commands::findAll(['command' => COMMAND_MARRIAGE]) as $command) {
			if ($time - $command->time > static::$timeDeleteMarriage) {
				$command->delete();
			}
		}
	}

}
