<?php 

namespace app\models;

use Yii;
use app\models\Commands;
use app\models\Chats;
use app\models\Users;

class CommandCaller {
	
	public static function checkAll()
	{
		$commands = Commands::getAll();
		foreach ($commands as $command) {
			static::runChatCommand($command->chatId, $command->userId, $command->getArgs());
			$command->delete();
		}	
	}	

	public static function runChatCommand($chatId, $userId, $args)
	{
		Yii::info(Users::getUser($chatId, $userId)->userId, 'bot-log');
		foreach (static::$commands as $command) {
			if (
				($command['argsCount'] && $command['argsCount'] != count($args)) 
			 || ($command['status'] && Users::getUser($chatId, $userId)->getStatus() < $command['status'])
			 || (!method_exists(__CLASS__, $command['fun']))
			) continue;
			foreach ($command['args'] as $key => $arg) {
				if ($args[$key] != $arg) continue(2);
			}
			$msg = implode(' ', $args);
			static::$command['fun']($chatId, $userId, $args);
		}
	}

	public static $commands = [
		[
			'args' => ['топ'],
			'argsCount' => 1,
			'status' => 10,
			'fun' => 'commandTop',
		],
	];

	public static function commandTop($chatId, $userId, $args)
	{
		$message = "Топ грязных ртов (кол-во символов):\n";
		$chat = Chats::get($chatId);
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
}