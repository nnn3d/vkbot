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
			if ($command->command == COMMAND_USER) {
				static::runChatCommand($command);
				$command->delete();
			}
		}	
	}	

	public static function runChatCommand($command)
	{
		foreach (ChatCommands::getAllCommands() as $chatCommand) {
			$chatCommand->checkAndRun($command);
		}
	}
}