<?php 

namespace app\models;

use Yii;
use app\models\Commands;
use app\models\ChatCommands;
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
		foreach (ChatCommands::getAllCommands() as $command) {
			$msg = implode(' ', $args);
			$command->checkAndRun($chatId, $userId, $args);
		}
	}
}