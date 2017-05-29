<?php 

namespace app\models;

use Yii;
use app\models\Vk;
use app\models\Chats;
use app\models\Users;
use app\models\Params;
use app\models\PendingTasks;
use app\models\Commands;
use app\models\CommandCaller;
use app\models\BotCommands;

class Bot {

	public $runCode;

	public static function get ()
	{
		return new self;
	}

	public function start()
	{
		header('Content-Type: text/html; charset=utf-8');
		Yii::info('start bot', 'bot-log');
		$this->init();
		BotCommands::init();
		while (Params::get()->run == $this->runCode) {
			$this->cycle();
		}
		$this->stop();
	}


	private function cycle()
	{
		$this->longPoll();
		CommandCaller::checkAll();
		PendingTasks::checkAll();
	}

	private function longPoll()
	{
		$server = Params::get()->longPollServer;
		$key = Params::get()->longPollKey;
		$ts = Params::get()->longPollTs;
		// Yii::info("start longPoll with params: \n server: $server \n key: $key \n ts: $ts", 'bot-log');
		if (!($server) || !($key) || !($ts)) {
			$this->getLongPollSettings();
			return $this->longPoll();
		}
		$result = Vk::get()->longPoll($server, $key, $ts);
		if (isset($result['failed'])) {
			Yii::warning("longPoll failed with code {$result['failed']}", 'bot-log');
			switch ($result['failed']) {
				case 1: // need new 'ts' from response
					// $this->loadNewMessages();
					Params::get()->longPollTs = $result['ts'];
					return $this->longPoll();
					break;

				case 2: // long poll key times up
					$this->getLongPollSettings();
					Params::get()->longPollTs = $ts;
					return $this->longPoll();
					break;

				case 3: // need new key and ts
					$this->getLongPollSettings();
					return $this->longPoll();
					break;
				
				default:
					// $this->getLongPollSettings();
					break;
			}
		}
		array_map(function ($res)
		{
			switch ($res[0]) {
				case 4: //new message
					if ($res[3] < 2000000000) break;
					$chatId = intval($res[3]) - 2000000000;
					$userId = $res[7]['from'];
					if(isset($res[7]['source_act'])) $chatEventAct = $res[7]['source_act'];
					$time = $res[4];
					if(isset($res[7]['source_mid'])) {
						$chatEventMid = $res[7]['source_mid'];
					} else {
						$chatEventMid = null;
					}
					$message = $res[6];
					$messageId = $res[1];
					$this->messageWorker($chatId, $userId, $message, $messageId, $time);
					if(isset($chatEventAct)) Events::setEvent($chatId, $userId, $time, $chatEventAct, $chatEventMid);
					break;
				
				default:
					return;
					break;
			}

		}, is_array($result['updates']) ? $result['updates'] : []);
		Params::get()->longPollTs = $result['ts'];
		return $result;
	}

	private function getLongPollSettings()
	{
		$settings = Vk::get()->messages->getLongPollServer();
		Params::get()->longPollServer = $settings['server'];
		Params::get()->longPollKey = $settings['key'];
		Params::get()->longPollTs = $settings['ts'];
		Yii::info("get new longPoll params: \n server: {$settings['server']} \n key: {$settings['key']} \n ts: {$settings['ts']}", 'bot-log');
	}

	private function messageWorker($chatId, $userId, $message, $messageId, $time = null)
	{
		if ($message>750) return false;
		Users::incrementCounter($chatId, $userId, mb_strlen(str_replace(" ","",$message), 'UTF-8'), $time);
		Commands::addFromMessage($chatId, $userId, $message, $messageId);
	}

	private function loadNewMessages()
	{
		$ts = Params::get()->longPollTs;
		$this->getLongPollSettings();
		$response = Vk::get()->messages->getLongPollHistory([
			'ts' => $ts,
			'msgs_limit' => 10000,
			'events_limit' => 50000,
		]);
		array_map(function ($message)
		{
			if (!isset($messages['chat_id'])) return;
			$this->messageWorker($message['chat_id'], $message['user_id'], $message['body'], $message['date'], $message['id']);	
		}, $response['messages']['items']);
	}

	private function init($reinit = false)
	{
		$this->runCode = microtime() . rand(1, 10000);
		Params::get()->run = $this->runCode;


		if (!Params::get()->bdVersion || $reinit) {
			// first initial here
			Yii::info("first init", 'bot-log');
			Params::get()->bdVersion = \Yii::$app->params['vkBot']['bdVersion'];
			Params::get()->selfId = Vk::get()->users->getR()[0]['id'];
			// Chats::updateChats();
		}
		else if (Params::bot('bdVersion') != Params::get()->bdVersion) {
			// bd update here
		}
	}

	private function stop()
	{
		
	}

}
