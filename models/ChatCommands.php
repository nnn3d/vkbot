<?php

namespace app\models;

use Yii;
use app\models\Chats;
use app\models\Users;
use app\models\MessagesCounter;

class ChatCommands {
	private static $commands;
	private $chatId;
	private $userId;
	private $args;
	private function load($chatId, $userId, $args)
	{
		$this->chatId = $chatId;
		$this->userId = $userId;
		$this->args = $args;
	}
	private function argsEqual($set) { return count($this->args) == $set; }
	private function argsLarger($set) { return count($this->args) > $set; }
	private function argsSmaller($set) { return count($this->args) < $set; }
	private function minStatus($status) 
	{ 
		return Users::getUser($this->chatId, $this->userId)->getStatus() >= $status; 
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
			function ($chatId, $userId, $args) use ($s) 
			{
				$s->load($chatId, $userId, $args);
				return false;
			}, 
			function ($chatId, $userId, $args) 
			{
				//do something
			}
		);
		// chat top
		$commands[] = new ChatCommand(
			function ($chatId, $userId, $args) use ($s) 
			{
				$s->load($chatId, $userId, $args);
				return $s->argsEqual(1) && $s->minStatus(10) && $s->argsRegExp(['топ']);
			}, 
			function ($chatId, $userId, $args) 
			{
				$message = "Топ грязных ртов (кол-во символов):\n";
				$chat = Chats::getChat($chatId);
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
		// chat top by days
		$commands[] = new ChatCommand(
			function ($chatId, $userId, $args) use ($s) 
			{
				$s->load($chatId, $userId, $args);
				return $s->argsEqual(2) && $s->minStatus(10) && $s->argsRegExp(['топ', '[\d]{1,2}']);
			}, 
			function ($chatId, $userId, $args) 
			{
				$days = $args[1];
				$time = time();
				$chat = Chats::getChat($chatId);
				$users = $chat->getAllActiveUsers();
				$usersCount = [];
				$message = "Топ грязных ртов в течении $days дней (кол-во символов):\n";
				foreach ($users as $user) {
					$usersCount[] = [
						'user' => $user,
						'count' => MessagesCounter::getSumCount($chatId, $user->userId, $days, $time),
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

}

class ChatCommand {
	private $condition;
	private $run;

	public function __construct($condition, $run)
	{	
		if (strval(get_class($condition)) == 'Closure') $this->condition = $condition;
		if (strval(get_class($run)) == 'Closure') $this->run = $run;
	}

	public function checkAndRun($chatId, $userId, $args)
	{
		$condition = $this->condition;
		$run = $this->run;
		if (!empty($condition) && !empty($run) && $condition($chatId, $userId, $args)) {
			$run($chatId, $userId, $args);
			return true;
		} 
		return false;
	}
}