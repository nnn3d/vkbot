<?php 

namespace app\models;

use app\models\Vk;
use app\models\Chats;
use app\models\Users;
use app\models\Params;

class Bot {

	public static function get ()
	{
		return new self;
	}

	public function start()
	{
		header('Content-Type: text/html; charset=utf-8');
		$this->init();
		$this->cycle();
	}


	private function cycle()
	{
		echo '<pre>';
		$this->longPoll();
		echo '</pre>';
	}

	private function longPoll()
	{
			// $this->getLongPollSettings();
		$server = Params::get()->longPollServer;
		$key = Params::get()->longPollKey;
		$ts = Params::get()->longPollTs;
		if (!($server) || !($key) || !($ts)) {
			$this->getLongPollSettings();
			return $this->longPoll();
		}
		$result = Vk::get()->longPoll($server, $key, $ts);
		if (isset($result['failed'])) {
			switch ($result['failed']) {
				case 1:
					Params::get()->longPollTs = $result['ts'];
					return $this->longPoll();
					break;

				case 2:
					$this->getLongPollSettings();
					Params::get()->longPollTs = $ts;
					return $this->longPoll();
					break;

				case 3:
					$this->getLongPollSettings();
					return $this->longPoll();
					break;
				
				default:
					// $this->getLongPollSettings();
					break;
			}
		}
		Params::get()->longPollTs = $result['ts'];
		array_map(function ($res)
		{
			switch ($res[0]) {
				case 4:
					if ($res[3] < 2000000000) break;
					$chatId = $res[3] - 2000000000;
					$userId = $res[7]['from'];
					$time = $res[4];
					$message = $res[6];
					$this->messageWorker($chatId, $userId, $message, $time);
					break;
				
				default:
					return;
					break;
			}

		}, $result['updates']);
		return $result;
	}

	private function getLongPollSettings()
	{
		$settings = Vk::get()->messages->getLongPollServer();
		Params::get()->longPollServer = $settings['server'];
		Params::get()->longPollKey = $settings['key'];
		Params::get()->longPollTs = $settings['ts'];
	}

	private function messageWorker($chatId, $userId, $message, $time = null)
	{
		Users::incrementCounter($chatId, $userId, strlen($message), $time);
	}

	private function init($reinit = false)
	{
		if (!Params::get()->bdVersion || $reinit) {
			// first initial here
			Params::get()->bdVersion = \Yii::$app->params['vkBot']['bdVersion'];
			Params::get()->selfId = Vk::get()->users->get();
			Chats::updateChats();
		}
		else if (\Yii::$app->params['vkBot']['bdVersion'] > Params::get()->bdVersion) {
			// bd update here
		}
	}

}
