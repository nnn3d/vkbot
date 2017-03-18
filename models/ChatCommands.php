<?php

namespace app\models;

use Yii;
use app\models\Chats;
use app\models\Users;
use app\models\MessagesCounter;
use app\models\PendingTasks;

class ChatCommands {
	private static $commands;
	private $chatId;
	private $userId;
	private $args;
	private function load($command)
	{
		$this->chatId = $command->chatId;
		$this->userId = $command->userId;
		$this->args = $command->getArgs();
	}
	private function argsEqual($set) { return count($this->args) == $set; }
	private function argsLarger($set) { return count($this->args) > $set; }
	private function argsSmaller($set) { return count($this->args) < $set; }
	private function minStatus($status) 
	{ 
		return Users::getStatus($this->chatId, $this->userId) >= $status; 
	}
	private function argsRegExp($set) 
	{ 
		foreach ($set as $key => $arg) {
			if (!preg_match("/{$arg}/iu", $this->args[$key])) return false;
		}
		return true;
	}

	public static function getAllCommands()
	{
		if (isset(static::$commands)) return static::$commands;
		$s = new self;
		$commands = [];
		// example
		$commands[] = new ChatCommand(
			function ($command) use ($s) 
			{
				$s->load($command);
				return false;
			}, 
			function ($command) 
			{
				//do something
			}
		);

		$commands['addPendingTask'] = new ChatCommand(
			function ($command) use ($s) 
			{
				$s->load($command);
				return $s->argsLarger(2) && $s->minStatus(10) && $s->argsRegExp(['повторяй', '[\d]*']);
			}, 
			function ($command) 
			{
				$minutes = $command->getArgs()[1];
				$taskArgs = array_slice($command->getArgs(), 2);
				$taskArgsS = implode(' ', $taskArgs);
				$chat = Chats::getChat($command->chatId);
				$pc = clone $command;
				$command->setArgs($taskArgs);
				if (!ChatCommands::isCommand($pc)) {
					$chat->sendMessage("Команды '$taskArgsS' не существует или недостаточно прав");
					return false;
				}
				PendingTasks::add($command->chatId, $taskArgs, $minutes * 60);
				$chat->sendMessage("Добавлена команда '$taskArgsS' с повторением раз в $minutes мин.");
			}
		);

		$commands['removePendingTask'] = new ChatCommand(
			function ($command) use ($s) 
			{
				$s->load($command);
				return $s->argsLarger(2) && $s->minStatus(10) && $s->argsRegExp(['не', 'повторяй']);
			}, 
			function ($command) 
			{
				$taskArgs = implode(' ', array_slice($command->getArgs(), 2));
				$chat = Chats::getChat($command->chatId);
				$message = '';
				foreach (PendingTasks::findAll(['chatId' => $command->chatId]) as $task) {
					$taskArgsS = implode(' ', $task->getArgs());
					if ( preg_match("/{$taskArgs}.*/", $taskArgsS) ) {
						$minutes = $task->timeRepeat / 60;
						$message .= "\nУдалена команда '$taskArgsS' с повторением раз в $minutes мин.";
						$task->delete();
					}
				}
				if (!$message) $message = "Ни одна команда с повторением не удалена";
				$chat->sendMessage($message);
			}
		);

		$commands['showPendingTask'] = new ChatCommand(
			function ($command) use ($s) 
			{
				$s->load($command);
				return $s->argsEqual(2) && $s->minStatus(10) && $s->argsRegExp(['покажи', 'повторения']);
			}, 
			function ($command) 
			{
				$taskArgs = implode(' ', array_slice($command->getArgs(), 2));
				$chat = Chats::getChat($command->chatId);
				$message = '';
				foreach (PendingTasks::findAll(['chatId' => $command->chatId]) as $task) {
					$taskArgsS = implode(' ', $task->getArgs());
					$minutes = $task->timeRepeat / 60;
					$message .= "\nКоманда '$taskArgsS' с повторением раз в $minutes мин.";
				}
				if (!$message) $message = "Нет команд с повторением";
				else $message = "Команды с повторением:\n" . $message;
				$chat->sendMessage($message);
			}
		);

		// chat top
		$commands['top'] = new ChatCommand(
			function ($command) use ($s) 
			{
				$s->load($command);
				return $s->argsEqual(1) && $s->minStatus(10) && $s->argsRegExp(['топ']);
			}, 
			function ($command) 
			{
				$message = "Топ активности участников (кол-во символов):\n";
				$chat = Chats::getChat($command->chatId);
				$users = $chat->getAllActiveUsers();
				usort($users, function ($a, $b)
				{
					return $b->messages - $a->messages;
				});
				foreach ($users as $num => $user) {
					$n = $num + 1;
					$message .= "\n{$n}. {$user->name} {$user->secondName} ({$user->messages})";
				}
				$chat->sendMessage($message);
			}
		);

		// user stat by days
		$commands['statUserByDays'] = new ChatCommand(
			function ($command) use ($s) 
			{
				$s->load($command);
				return $s->argsLarger(2) && $s->argsSmaller(5) && $s->minStatus(1) && $s->argsRegExp(['стат', '[\d]{1,2}']);
			}, 
			function ($command) 
			{
				$days = $command->getArgs()[1];
				$time = time();
				$chat = Chats::getChat($command->chatId);

				$name = $command->getArgs()[2];
				$secondName = isset($command->getArgs()[3]) ? $command->getArgs()[3] : '';
				$user = Users::getUserByName($command->chatId, $name, $secondName);
				if (!$user) {
					$chat->sendMessage("Не найден участник беседы $name $secondName");
					return false;
				}
				$message = "Статистика пользователя {$user->name} {$user->secondName} за последние $days дней (кол-во символов):\n";
				$count = [];
				$write = false;
				for ($i=$days - 1; $i >= 0; $i--) { 
					$c = MessagesCounter::getDayCount($command->chatId, $user->userId, $i, $time);
					$write = $write || $c > 0;
					if ($write) {
						$count[] = [
							'date' => date("d.m.y", time() - ($i * 60 * 60 * 24)),
							'count' => $c,
						];
					}
				}
				foreach (array_reverse($count) as $item) {
					$message .= "\n{$item['date']} - {$item['count']} символов";
				}
				$chat->sendMessage($message);
			}
		);

		// chat top by days
		$commands['topByDays'] = new ChatCommand(
			function ($command) use ($s) 
			{
				$s->load($command);
				return $s->argsEqual(2) && $s->minStatus(10) && $s->argsRegExp(['топ', '[\d]{1,2}']);
			}, 
			function ($command) 
			{
				$days = $command->getArgs()[1];
				$time = time();
				$chat = Chats::getChat($command->chatId);
				$users = $chat->getAllActiveUsers();
				$usersCount = [];
				$message = "Топ активности участников в течении последних $days дней (кол-во символов):\n";
				foreach ($users as $user) {
					$usersCount[] = [
						'user' => $user,
						'count' => MessagesCounter::getSumCount($command->chatId, $user->userId, $days, $time),
					];
				}
				usort($usersCount, function ($a, $b)
				{
					return $b['count'] - $a['count'];
				});
				foreach ($usersCount as $num => $item) {
					$n = $num + 1;
					$message .= "\n{$n}. {$item['user']->name} {$item['user']->secondName} ({$item['count']})";
				}
				$chat->sendMessage($message);
			}
		);


		static::$commands = $commands;
		return $commands;
	}

	public static function isCommand($commandCheck)
	{
		foreach (static::getAllCommands() as $command) {
			$result = $result || $command->check($commandCheck);
		}
		return $result;
	}

}

class ChatCommand {
	private $condition;
	private $run;

	public function __construct($condition, $run)
	{	
		if (strval(get_class($condition)) == 'Closure') $this->condition = $condition;
		if (strval(get_class($run)) == 'Closure') $this->run = $run;
	}

	public function checkAndRun($command)
	{
		$run = $this->run;
		if ($this->check($command)) {
			$run($command);
			return true;
		} 
		return false;
	}

	public function check($command)
	{
		$condition = $this->condition;
		$run = $this->run;
		return (!empty($condition) && !empty($run) && $condition($command));
	}
}