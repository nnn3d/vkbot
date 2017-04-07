<?php

namespace app\models;

use app\models\ChatParams;
use app\models\Chats;
use app\models\MessagesCounter;
use app\models\PChart;
use app\models\PendingTasks;
use app\models\Users;

class ChatCommands
{
    private static $commands;
    private $chatId;
    private $userId;
    private $args;
    private $argsCountSkip;
    private function load($command)
    {
        $this->chatId        = $command->chatId;
        $this->chatId        = $command->messageId;
        $this->userId        = $command->userId;
        $this->args          = $command->getArgs();
        $this->argsCountSkip = $command->argsCountSkip;
    }
    private function argsEqual($set)
    {return count($this->args) == $set;}
    private function argsLarger($set)
    {return count($this->args) > $set;}
    private function argsSmaller($set)
    {return count($this->args) < $set;}
    private function minStatus($status)
    {
        return Users::getStatus($this->chatId, $this->userId) >= $status;
    }
    private function argsRegExp($set)
    {
        foreach ($set as $key => $arg) {
            if (!isset($this->args[$key]) || !preg_match("/^{$arg}/iu", $this->args[$key])) {
                return false;
            }

        }
        return true;
    }

    public static function getAllCommands()
    {
        if (isset(static::$commands)) {
            return static::$commands;
        }

        $s        = new self;
        $commands = [];
        
        $commands[] = new ChatCommand(
            'брак',
            'Показывает ваш текущий гражданский статус',
            function ($command) use ($s) {
                $s->load($command);
                return $s->argsEqual(1) && $s->argsRegExp(['брак']);
            },
            function ($command) {
                if ($command->getArgs()[0] == 'браки') return false;
                $chat     = Chats::getChat($command->chatId);
                $marriage = ChatParams::get($command->chatId)->{CHAT_PARAM_MARRIAGE};
                $botName  = Params::bot('name');
                if ($marriage) {
                    $value = $marriage;  
                        $pioneerUserId = $command->userId;
                        $value = unserialize($marriage);
                        if (!is_array($value)) return false;
                        $divorce       = false;
                        $arrayDataMarriage = array();
                        array_filter($value, function ($merr) use ($pioneerUserId, &$divorce, &$arrayDataMarriage) {
                            if (in_array($pioneerUserId, $merr)) {
                                $divorce = true;
                                $arrayDataMarriage = $merr;
                                return false;
                            }
                            return true;
                        });
                        if ($divorce) {
                            $spouce1 = $arrayDataMarriage[0];
                            $spouce2 = $arrayDataMarriage[1];
                            $timeBeginMarriage = $arrayDataMarriage[2];
                            $messageTime = ChatCommands::timeToStr(time() - $timeBeginMarriage);
                        } else {
                            $chat->sendMessage("В данной беседе вы не состоите ни с кем в браке.");
                            return false;
                        }
                        $pioneerUser = Users::getUser($command->chatId, $pioneerUserId);
                        if($spouce1 == $command->userId) {
                            $spouce = $spouce2;
                        } else if($spouce2 == $command->userId){
                            $spouce = $spouce1;
                        }
                        $spouce = Users::getUser($command->chatId, $spouce);
                        $chat->sendMessage("Запись №000".rand(100, 999)."\n{$pioneerUser->name} {$pioneerUser->secondName} в счастливом браке c {$spouce->name} {$spouce->secondName} вот уже целых $messageTime", ['forward_messages' => $command->messageId]);
                        return false;
                } else {
                    $chat->sendMessage("В данной беседе вы не состоите ни с кем в браке.");
                    return false;
                }   
            }
        );
        
        $commands[] = new ChatCommand(
            'развод',
            'Расторгает брак, если вы в нем состоите',
            function ($command) use ($s) {
                $s->load($command);
                return $s->argsEqual(1) && $s->argsRegExp(['развод']);
            },
            function ($command) {
                $chat     = Chats::getChat($command->chatId);
                $marriage = ChatParams::get($command->chatId)->{CHAT_PARAM_MARRIAGE};
                if (!$marriage) {
                    return false;
                }
                $value = unserialize($marriage);
                if (!is_array($value)) {
                    return false;
                }

                $pioneerUserId = $command->userId;
                $pioneerUser   = Users::getUser($command->chatId, $command->userId);
                $divorce       = false;
                $arrayDataMarriage = array();
                $newValue      = array_filter($value, function ($merr) use ($pioneerUserId, &$divorce, &$arrayDataMarriage) {
                    if (in_array($pioneerUserId, $merr)) {
                        $divorce = true;
                        $arrayDataMarriage = $merr;
                        return false;
                    }
                    return true;
                });
                ChatParams::get($command->chatId)->{CHAT_PARAM_MARRIAGE} = serialize($newValue);
                if ($divorce) {
                    $spouse1 = $arrayDataMarriage[0];
                    $spouse2 = $arrayDataMarriage[1];
                    $timeBeginMarriage = $arrayDataMarriage[2];
                        
                    $user1 = Users::getUser($command->chatId, $spouse1);
                    $user2 = Users::getUser($command->chatId, $spouse2);

                    $messageTime = ChatCommands::timeToStr(time() - $timeBeginMarriage);

                    $chat->sendMessage("С сожалением я помещаю запись №000".rand(100, 999)." в архив.\n{$user1->name} {$user1->secondName} и {$user2->name} {$user2->secondName} с данного момента в разводе.\n\nЭтот брак продлился всего ".$messageTime);
                }

                return false;
            }
        );

		$commands[] = new ChatCommand( 
			'ливы { количесвто дней }', 
			'Последние выходы.', 
			function ($command) use ($s) { 
				$s->load($command); 
				return $s->argsEqual(2) && $s->argsRegExp(['ливы','[\d]{1,2}']); 
			}, 
			function ($command) { 
				$message = "Из конфы вышли:\n"; 
				$event = "leave_user"; 
				$days = intval($command->getArgs()[1]);
				$chat = Chats::getChat($command->chatId); 
				$users = $chat->getAllActiveUsers();
				$eventList = Events::getEvent($chat->chatId, $event);
				$n=0;
			foreach ($eventList as $userId) { 
				$user = Users::getUser($chat->chatId, $userId->userId);
				$checkUs = Users::userExists($chat->chatId, $userId->userId);
				if (in_array($user, $users)) {
					$where='в конфе';
				} else {
					$where='вышел';
				}
				$currenttime=time() - $userId->time;
				$messageTime = ChatCommands::timeToStr($currenttime);
				$timearr = ChatCommands::timeToArr($currenttime);
				if (!isset($timearr[3])){
					$timearr[3]=0;
				}
				if ($days > ($timearr[3])) {
				$n++;
				$message .= "\n{$n}. {$user->name} {$user->secondName} $messageTime $where"; 
				}
			} 
			$chat->sendMessage($message); 
			}
		);
		
		
        $commands[] = new ChatCommand(
            'брак { да или нет }',
            '',
            function ($command) use ($s) {
                $s->load($command);
                return $s->argsEqual(2) && $s->argsRegExp(['брак', '(да|нет)']);
            },
            function ($command) {
                $chat = Chats::getChat($command->chatId);
                $brak = Commands::findOne(['command' => COMMAND_MARRIAGE, 'chatId' => $command->chatId]);
                if (!$brak) {
                    return false;
                }

                $user1 = Users::getUser($command->chatId, $brak->getArgs()[0]);
                $user2 = Users::getUser($command->chatId, $brak->getArgs()[1]);

                if ($command->userId != $user1->userId) {
                    return false;
                }

                $botName = Params::bot('name');
                if ($command->getArgs()[1] == 'нет') {
                    $chat->sendMessage("К моему сожалению, я слышу отказ. Я не могу зарегистрировать ваш брак.");
                    $brak->delete();
                    return false;
                } else if ($command->getArgs()[1] == 'да') {
                    $marriage = ChatParams::get($command->chatId)->{CHAT_PARAM_MARRIAGE};

                    if (!$marriage) {
                        $value = [[$user1->userId, $user2->userId, time()]];

                    } 
					{
                        $value = unserialize($marriage);
                        if (!is_array($value)) {
                            $value = [];
                        }

                        $newValue = [$user1->userId, $user2->userId, time()];
                        $value[]  = $newValue;

                    }
                    ChatParams::get($command->chatId)->{CHAT_PARAM_MARRIAGE} = serialize($value);

                    $chat->sendMessage("Уважаемые новобрачные, с полным соответствием c законодательством ваш брак зарегистрирован.
Я торжественно объявляю вас мужем и женой!
Поздравьте друг друга супружеским поцелуем! \n\n
В книге ЗАГСА создана запись №000".rand(100, 999));
                    $brak->delete();
                    return false;
                }
            },
            ['hidden' => true]
        );

        $commands[] = new ChatCommand(
            'топ браков',
            'Показывает топ самых крепких браков. Работает только в беседах, в которых больше 5 пар.',
            function ($command) use ($s) {
                $s->load($command);
                return $s->argsEqual(2) && $s->argsRegExp(['топ', 'браков']);
            },
            function ($command) {
                $chat       = Chats::getChat($command->chatId);
                $marriage   = ChatParams::get($command->chatId)->{CHAT_PARAM_MARRIAGE};

                if (!$marriage) return false;
                $marriages = unserialize($marriage);
		$countMarriages = count($marriages);

		if($countMarriages > 5 && is_array($marriages)) {
			$message = "Топ самых крепких пар:\n";
			$i = 1;
			$timeBeginMarriage = 0;
			
			foreach ($marriages as $m) {
				$user1 = Users::getUser($command->chatId, $m[0]);
				$user2 = Users::getUser($command->chatId, $m[1]);
				$timeBeginMarriage = $m[2];
				$messageTime = ChatCommands::timeToStr(time() - $timeBeginMarriage);
				
				if($i < 4) {
				        $message .= "\n $i. {$user1->name} {$user1->secondName} 💝 {$user2->name} {$user2->secondName} \n🎀 $messageTime";
				} else {
					$message .= "\n $i. {$user1->name} {$user1->secondName} ❤ {$user2->name} {$user2->secondName} \n🎀 $messageTime";
				}
				
				$i++;
			}
	
			$chat->sendMessage($message);
		} else {
			return false;
		}
            }
        );
	    
        $commands[] = new ChatCommand(
            'браки',
            'Показывает существующие браки',
            function ($command) use ($s) {
                $s->load($command);
                return $s->argsEqual(1) && $s->argsRegExp(['браки']);
            },
            function ($command) {
                $chat       = Chats::getChat($command->chatId);
                $marriage   = ChatParams::get($command->chatId)->{CHAT_PARAM_MARRIAGE};
                $errMessage = "Нет браков в этой беседе";
                if (!$marriage) {
                    $chat->sendMessage($errMessage);
                    return false;
                }
                $marriages = unserialize($marriage);
		$countMarriages = count($marriages);
                if (!is_array($marriages) || $countMarriages == 0) {
                    $chat->sendMessage($errMessage);
                    return false;
                }

                $message = "Зарегистрированные браки в этой беседе:\n";

                foreach ($marriages as $m) {
                    $user1 = Users::getUser($command->chatId, $m[0]);
                    $user2 = Users::getUser($command->chatId, $m[1]);
                    $message .= "\n {$user1->name} {$user1->secondName} ❤ {$user2->name} {$user2->secondName}";
                }
		
		if($countMarriages > 5) {
		    $message .= "\n\n Доступен топ самых крепких браков! (".Params::bot('name')." топ браков)";
		}

                $chat->sendMessage($message);
            }
        );

        $commands[] = new ChatCommand(
            'брак { имя [ + фамилия ] участника }',
            'Заключет брак с одним из участников беседы',
            function ($command) use ($s) {
                $s->load($command);
                return $s->argsLarger(1) && $s->argsRegExp(['брак', '(?!да|нет)']);
            },
            function ($command) {
                $chat = Chats::getChat($command->chatId);
                if (Commands::find()->where(['command' => COMMAND_MARRIAGE, 'chatId' => $command->chatId])->exists()) {
                    $chat->sendMessage("В данный момент регистрируется другая пара. Ждите.");
                    return false;
                }
                $name       = $command->getArgs()[1];
                $secondName = isset($command->getArgs()[2]) ? $command->getArgs()[2] : '';
                $user       = Users::getUserByName($command->chatId, $name, $secondName);
                if (!$user) {
                    $chat->sendMessage("Я не могу найти человека с таким именем среди участников конференции");
                    return false;
                }
                if ($command->userId == $user->userId) {
                    $chat->sendMessage("Жениться на самом себе пока нельзя...");
                    return false;
                }
                $marriage = ChatParams::get($command->chatId)->{CHAT_PARAM_MARRIAGE};
                $botName  = Params::bot('name');
                if ($marriage) {
                    $value = $marriage;
                    $secondDiverse = false;

                    if (substr_count($value, $user->userId) >= 1) {
                        $pioneerUserId = $user->userId;
                        $secondDiverse = true;
                    } else if (substr_count($value, $command->userId) >= 1) {        
                        $pioneerUserId = $command->userId;
                        $secondDiverse = true;
                    }
                    
                    if($secondDiverse) {
                        $value = unserialize($marriage);
                        if (!is_array($value)) return false;
                        $divorce       = false;
                        $arrayDataMarriage = array();
                        array_filter($value, function ($merr) use ($pioneerUserId, &$divorce, &$arrayDataMarriage) {
                            if (in_array($pioneerUserId, $merr)) {
                                $divorce = true;
                                $arrayDataMarriage = $merr;
                                return false;
                            }
                            return true;
                        });
                        if ($divorce) {
                            $spouce1 = $arrayDataMarriage[0];
                            $spouce2 = $arrayDataMarriage[1];
                            $timeBeginMarriage = $arrayDataMarriage[2];
                            $messageTime = ChatCommands::timeToStr(time() - $timeBeginMarriage);
                        }
                        $pioneerUser = Users::getUser($command->chatId, $pioneerUserId);
                        if($spouce1 == $command->userId) {
                            $spouce = $spouce2;
                        } else if($spouce2 == $command->userId){
                            $spouce = $spouce1;
                        } else if($spouce1 == $user->userId) {
                            $spouce = $spouce2;
                        } else if($spouce2 == $user->userId) {
                            $spouce = $spouce1;
                        } else {
                            $chat->sendMessage("Сейчас я не могу зарегистрировать ваш брак. Давайте попробуем позднее?");
                            return false;
                        }
                        $spouce = Users::getUser($command->chatId, $spouce);
                        if($pioneerUserId == $command->userId) {
                            $deal = 'Вы';
                        } else {
                            $deal = "{$pioneerUser->name} {$pioneerUser->secondName}";
                        }
                        $chat->sendMessage("Я не могу зарегистрировать ваш брак.\n$deal уже в счастливом браке c {$spouce->name} {$spouce->secondName} вот уже целых $messageTime", ['forward_messages' => $command->messageId]);
                        return false;
                    }
                }
                    
                $pioneerUser = Users::getUser($command->chatId, $command->userId);
                $args        = [
                    $user->userId,
                    $command->userId,
                ];
                $message = "Дорогие Жених и Невеста! Дорогие гости!
Мы рады приветствовать Вас на официальной церемонии бракосочетания. Двое счастливых сейчас находятся в нашем зале:
\n\n
{$pioneerUser->name} {$pioneerUser->secondName}
и
{$user->name} {$user->secondName}
\n\n
Перед тем как официально заключить Ваш брак я хотела бы услышать: является ли Ваше желание свободным, искренним и взаимным, с открытым ли сердцем, по собственному ли желанию и доброй воле вы заключаете брак?
\n\n
Согласие невесты я уже получила, поскольку именно она подала заявку на заключение брака.
\n\n
Теперь прошу ответить вас, {$user->name} {$user->secondName}, согласны ли вы вступить в законный брак?
Вы можете обдумать свое решение в течении 10 мин.
\n\n
[Команда: $botName брак да \ нет]";
                Commands::add($command->chatId, null, $args, null, COMMAND_MARRIAGE);

                $chat->sendMessage($message);
            }
        );

        $commands[] = new ChatCommand(
            'дуэль { имя [ + фамилия ] участника }',
            'Вызвать участника на дуэль.',
            function ($command) use ($s) {
                $s->load($command);
                return $s->argsLarger(1) && $s->argsRegExp(['дуэль', '[^+-]']);
            },
            function ($command) {
                $chat = Chats::getChat($command->chatId);
                if (Commands::find()->where(['command' => COMMAND_DUEL, 'chatId' => $command->chatId])->exists()) {
                    $chat->sendMessage("Дуэль уже идет, для новой еще не время!");
                    return false;
                }
                $name       = $command->getArgs()[1];
                $secondName = isset($command->getArgs()[2]) ? $command->getArgs()[2] : '';
                $user       = Users::getUserByName($command->chatId, $name, $secondName);
                if (!$user) {
                    $chat->sendMessage("Я не могу найти оппонента с таким именем среди участников конференции");
                    return false;
                }
                if ($command->userId == $user->userId) {
                    $chat->sendMessage("Нельзя вызвать на дуэль самого себя");
                    return false;
                }
                $pioneerUser = Users::getUser($command->chatId, $command->userId);
                $args        = [
                    $user->userId,
                    $command->userId,
                ];
                $botName = Params::bot('name');
                $message = "{$user->name} {$user->secondName}, вас приглашает на дуэль {$pioneerUser->name} {$pioneerUser->secondName}, заручившись подержкой боженьки.\n Принимаете ли вы вызов? (команда \"$botName дуэль +\" или \"$botName дуэль -\" для отказа)";
                Commands::add($command->chatId, null, $args, null, COMMAND_DUEL);

                $chat->sendMessage($message);
            }
        );

        $commands[] = new ChatCommand(
            'дуэль { + / - }',
            'Описание',
            function ($command) use ($s) {
                $s->load($command);
                return $s->argsEqual(2) && $s->argsRegExp(['дуэль', '[+-]']);
            },
            function ($command) {
                $chat = Chats::getChat($command->chatId);
                $duel = Commands::findOne(['command' => COMMAND_DUEL, 'chatId' => $command->chatId]);
                if (!$duel) {
                    return Chats::getChat(16)->sendMessage('no2');
                }

                $user1 = Users::getUser($command->chatId, $duel->getArgs()[0]);
                if ($user1->userId != $command->userId) {
                    return Chats::getChat(16)->sendMessage('no1');
                }

                if ($command->getArgs()[1] == '-') {
                    $chat->sendMessage("{$user1->name} {$user1->secondName} отклонил дуэль, жалкий трус!");
                    $duel->delete();
                    return false;
                }
                $botName = Params::bot('name');
                $prefix  = "$botName битва ";
                $str     = substr(strtolower(md5(uniqid(rand(), true))), 0, 6);
                preg_match_all('/./us', $prefix . $str, $ar);
                $strrev = join('', array_reverse($ar[0]));
                $args   = [
                    $user1->userId,
                    $duel->getArgs()[1],
                    $str,
                ];
                $duel->delete();
                Commands::add($command->chatId, null, $args, null, COMMAND_DUEL);
                $chat->sendMessage("Битва начинается! Победит тот, кто первым наберет строку '{$strrev}' наоборот!");
            },
            ['hidden' => true]
        );

        $commands[] = new ChatCommand(
            'битва { ответ }',
            'Описание',
            function ($command) use ($s) {
                $s->load($command);
                return $s->argsEqual(2) && $s->argsRegExp(['битва']);
            },
            function ($command) {
                $chat = Chats::getChat($command->chatId);
                $duel = Commands::findOne(['command' => COMMAND_DUEL, 'chatId' => $command->chatId]);
                if (!$duel) {
                    return;
                }

                $user1 = Users::getUser($command->chatId, $duel->getArgs()[0]);
                $user2 = Users::getUser($command->chatId, $duel->getArgs()[1]);
                if ($command->userId == $user1->userId) {
                    $winUser   = $user1;
                    $looseUser = $user2;
                } else if ($command->userId == $user2->userId) {
                    $winUser   = $user2;
                    $looseUser = $user1;
                } else {
                    return false;
                }

                if ($command->getArgs()[1] == $duel->getArgs()[2]) {
                    $chat->sendMessage("Поздравляю! {$winUser->name} {$winUser->secondName} победил, {$looseUser->name} {$looseUser->secondName} проиграл в этой честной битве!");
                    $duel->delete();
                }
                return false;
            },
            ['hidden' => true]
        );

        $commands[] = new ChatCommand(
            'повторяй { количество минут } { команда полностью }',
            'Добавить повторяющееся событие. Например "' . Params::bot('name') . ' повторяй 5 кто бот" будет выполнять команду "кто" каждые 5 мин.',
            function ($command) use ($s) {
                $s->load($command);
                return $s->argsLarger(2) && $s->argsRegExp(['повторяй', '[\d]+']);
            },
            function ($command) {
                $minutes = intval($command->getArgs()[1]);
                if ($minutes < 1) {
                    $minutes = 1;
                }

                $taskArgs  = array_slice($command->getArgs(), 2);
                $taskArgsS = implode(' ', $taskArgs);
                $chat      = Chats::getChat($command->chatId);
                $pc        = clone $command;
                $pc->setArgs($taskArgs);
                if (!ChatCommands::isCommand($pc)) {
                    $chat->sendMessage("Команды '$taskArgsS' не существует или недостаточно прав");
                    return false;
                }
                PendingTasks::add($command->chatId, $taskArgs, $minutes * 60, $command->messageId);
                $chat->sendMessage("Добавлена команда '$taskArgsS' с повторением раз в $minutes мин.");
            },
            ['statusDefault' => USER_STATUS_ADMIN]
        );

        $commands[] = new ChatCommand(
            'не повторяй',
            'Убирает повторяющуюся задачу. Посмотреть задачи можно командой "'.Params::bot('name').' покажи повторения".',
            function ($command) use ($s) {
                $s->load($command);
                return $s->argsLarger(2) && $s->argsRegExp(['не', 'повторяй']);
            },
            function ($command) {
                $taskArgs = implode(' ', array_slice($command->getArgs(), 2));
                $chat     = Chats::getChat($command->chatId);
                $message  = '';
                foreach (PendingTasks::findAll(['chatId' => $command->chatId]) as $task) {
                    $taskArgsS = implode(' ', $task->getArgs());
                    if (preg_match("/{$taskArgs}.*/", $taskArgsS)) {
                        $minutes = $task->timeRepeat / 60;
                        $message .= "\nУдалена команда '$taskArgsS' с повторением раз в $minutes мин.";
                        $task->delete();
                    }
                }
                if (!$message) {
                    $message = "Ни одна команда с повторением не удалена";
                }

                $chat->sendMessage($message);
            },
            ['statusDefault' => USER_STATUS_ADMIN]
        );

        $commands[] = new ChatCommand(
            'покажи повторения',
            'Показывает повторяющиеся задачи.',
            function ($command) use ($s) {
                $s->load($command);
                return $s->argsEqual(2) && $s->argsRegExp(['покажи', 'повторения']);
            },
            function ($command) {
                $chat    = Chats::getChat($command->chatId);
                $message = '';
                foreach (PendingTasks::findAll(['chatId' => $command->chatId]) as $task) {
                    $taskArgsS = implode(' ', $task->getArgs());
                    $minutes   = $task->timeRepeat / 60;
                    $message .= "\nКоманда '$taskArgsS' с повторением раз в $minutes мин.";
                }
                if (!$message) {
                    $message = "Нет команд с повторением";
                } else {
                    $message = "Команды с повторением:\n" . $message;
                }

                $chat->sendMessage($message);
            },
            ['statusDefault' => USER_STATUS_ADMIN]
        );

        $commands[] = new ChatCommand(
            'актив',
            'Показывает время с последнего сообщения пользователя.',
            function ($command) use ($s) {
                $s->load($command);
                return $s->argsEqual(1) && $s->argsRegExp(['актив']);
            },
            function ($command) {
                $time        = time();
                $chat        = Chats::getChat($command->chatId);
                $users       = $chat->getAllActiveUsers();
                $usersActive = [];
                $message     = "Топ последней активности участников:\n";
                usort($users, function ($a, $b) {
                    return $b->lastActivity - $a->lastActivity;
                });
                foreach ($users as $num => $user) {
                    $n                          = $num + 1;
                    $am                         = ChatCommands::timeToStr($time - $user->lastActivity);
                    !$user->lastActivity && $am = 'не активен';
                    $message .= "\n{$n}. {$user->name} {$user->secondName} ({$am})";
                }
                $chat->sendMessage($message);
            }
        );

        $commands[] = new ChatCommand(
            'общий топ',
            'Количесвто символов всех участников за все время.',
            function ($command) use ($s) {
                $s->load($command);
                return $s->argsEqual(2) && $s->argsRegExp(['общий', 'топ']);
            },
            function ($command) {
                $message = "Топ активности участников (кол-во символов):\n";
                $chat    = Chats::getChat($command->chatId);
                $users   = $chat->getAllActiveUsers();
                usort($users, function ($a, $b) {
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
        $commands[] = new ChatCommand(
            'стат { количество дней } { имя [ + фамилия ] участника }',
            'Количесвто символов участника за указанный срок.',
            function ($command) use ($s) {
                $s->load($command);
                return $s->argsLarger(2) && $s->argsSmaller(5) && $s->argsRegExp(['стат', '[\d]{1,2}']);
            },
            function ($command) {
                $days = intval($command->getArgs()[1]);
                $time = time();
                $chat = Chats::getChat($command->chatId);

                $name       = $command->getArgs()[2];
                $secondName = isset($command->getArgs()[3]) ? $command->getArgs()[3] : '';
                $user       = Users::getUserByName($command->chatId, $name, $secondName);
                if (!$user) {
                    $chat->sendMessage("Не найден участник беседы $name $secondName");
                    return false;
                }
                $message = "Статистика пользователя {$user->name} {$user->secondName} за последние $days дней (кол-во символов):\n";
                $count   = [];
                $write   = false;
                for ($i = $days - 1; $i >= 0; $i--) {
                    $c     = MessagesCounter::getDayCount($command->chatId, $user->userId, $i, $time);
                    $write = $write || $c > 0;
                    if ($write) {
                        $count[] = [
                            'date'  => date("d.m.y", time() - ($i * 60 * 60 * 24)),
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
        $commands[] = new ChatCommand(
            'топ { количество дней }',
            'Количество символов всех участников указанный срок.',
            function ($command) use ($s) {
                $s->load($command);
                return $s->argsEqual(2) && $s->argsRegExp(['топ', '[\d]{1,2}']);
            },
            function ($command) {
                $days       = intval($command->getArgs()[1]);
                $time       = time();
                $chat       = Chats::getChat($command->chatId);
                $users      = $chat->getAllActiveUsers();
                $usersCount = [];
                $message    = "Топ активности участников в течении последних $days дней (кол-во символов):\n";
                foreach ($users as $user) {
                    $usersCount[] = [
                        'user'  => $user,
                        'count' => MessagesCounter::getSumCount($command->chatId, $user->userId, $days, $time),
                    ];
                }
                usort($usersCount, function ($a, $b) {
                    return $b['count'] - $a['count'];
                });
                foreach ($usersCount as $num => $item) {
                    $n = $num + 1;
                    $message .= "\n{$n}. {$item['user']->name} {$item['user']->secondName} ({$item['count']})";
                }
                $chat->sendMessage($message);
            }
        );

        $commands[] = new ChatCommand(
            'кто или кого { любой вопрос }',
            'В ответ дает случайного участника.',
            function ($command) use ($s) {
                $s->load($command);
                return $s->argsLarger(1) && $s->argsRegExp(['(кто|кого)']);
            },
            function ($command) {
                $chat   = Chats::getChat($command->chatId);
                $users  = $chat->getAllActiveUsers();
                $r      = mt_rand(0, count($users) - 1);
                $c      = implode(' ', array_slice($command->getArgs(), 1));
                $countC = substr_count($c, '?');
                $c      = trim($c, "?");
		if (empty($c)) return false;
                if ($countC == '1') {
                    $chat->sendMessage("Cчитаю, что \"$c\" - {$users[$r]->name} {$users[$r]->secondName}", ['forward_messages' => $command->messageId]);
                } else {
                    $chat->sendMessage("Я думаю, что {$users[$r]->name} {$users[$r]->secondName}", ['forward_messages' => $command->messageId]);
                }
            }
        );

        $commands[] = new ChatCommand(
            'доступ { админ / модер / юзер } { название команды }',
            'Выставляет уровень допуска для команды.',
            function ($command) use ($s) {
                $s->load($command);
                return $s->argsLarger(2) && $s->argsRegExp(['доступ']);
            },
            function ($command) {
                $statusMap = Params::bot(['statusMap']);
                $statusArg = $command->getArgs()[1];
                if (isset($statusMap[$statusArg])) {
                    $status = $statusMap[$statusArg];
                } else {
                    return false;
                }

                $chat           = Chats::getChat($command->chatId);
                $commandArgsS   = implode(' ', array_slice($command->getArgs(), 2));
                $changedCommand = ChatCommands::getCommandByRegExp($commandArgsS);
                if (empty($changedCommand)) {
                    $chat->sendMessage("Команда '$commandArgsS' не найдена");
                    return false;
                }
                $commandName = $changedCommand->getName();
                if ($changedCommand->getStatus()) {
                    $chat->sendMessage("Статус команды '$commandName' не может быть изменен");
                    return false;
                }
                $name                                    = CHAT_PARAMS_COMMAND_PREFIX . $changedCommand->getName();
                ChatParams::get($command->chatId)->$name = $status;
                $chat->sendMessage("Статус выполнения команды '$commandName' установлен на '$statusArg'");
            },
            ['statusDefault' => USER_STATUS_ADMIN]
        );

        $commands[] = new ChatCommand(
            'кик { имя [ + фамилия ] участника }',
            'Кикает указанного участника из беседы.',
            function ($command) use ($s) {
                $s->load($command);
                return $s->argsLarger(1) && $s->argsRegExp(['кик']);
            },
            function ($command) {
                $chat       = Chats::getChat($command->chatId);
                $name       = $command->getArgs()[1];
                $secondName = isset($command->getArgs()[2]) ? $command->getArgs()[2] : '';
                $user       = Users::getUserByName($command->chatId, $name, $secondName);
                if (!$user) {
                    $chat->sendMessage("Не найден участник беседы '$name $secondName'");
                    return false;
                } else if ($user->userId == $command->userId) {
                    $chat->sendMessage("Нельзя себя кикнуть");
                    return false;
                } else if (Users::getStatus($command->chatId, $user->userId) != USER_STATUS_DEFAULT) {
                    $chat->sendMessage("Этого пользователя нельзя кикнуть");
                    return false;
                }
                $chat->sendMessage("Пользователь {$user->name} {$user->secondName} будет кикнут");
                if (!$chat->kickUser($user->userId)) {
                    $chat->sendMessage("Не удалось кикнуть пользователя {$user->name} {$user->secondName}");
                }
            },
            ['statusDefault' => USER_STATUS_MODER]
        );

        $commands[] = new ChatCommand(
            'правила',
            'Выдает правила беседы',
            function ($command) use ($s) {
                $s->load($command);
                return $s->argsEqual(1) && $s->argsRegExp(['правила']);
            },
            function ($command) {
                $chat  = Chats::getChat($command->chatId);
                $rules = ChatParams::get($command->chatId)->rules;
                $chat->sendMessage("Правила конфы:\n$rules");
            },
            ['statusDefault' => USER_STATUS_MODER]
        );

        $commands[] = new ChatCommand(
            'новые правила',
            'Устанавливает правила беседы',
            function ($command) use ($s) {
                $s->load($command);
                return $s->argsLarger(2) && $s->argsRegExp(['новые', 'правила']);
            },
            function ($command) {
                $chat                                    = Chats::getChat($command->chatId);
                $rules                                   = ChatParams::get($command->chatId)->rules;
                $c                                       = implode(' ', array_slice($command->getArgs(), 2));
                $countC                                  = substr_count($c, '?');
                $c                                       = trim($c, "?");
                ChatParams::get($command->chatId)->rules = $c;
                $chat->sendMessage("Правила для беседы устанволены!", ['forward_messages' => $command->messageId]);
            },
            ['statusDefault' => USER_STATUS_MODER]
        );

        $commands[] = new ChatCommand(
            'статус { модер / юзер } { имя [ + фамилия ] участника }',
            'Выставить уровень доступа к командам для участника.',
            function ($command) use ($s) {
                $s->load($command);
                return $s->argsLarger(1) && $s->argsRegExp(['статус']);
            },
            function ($command) {
                $statusMap = Params::bot(['statusMap']);
                $statusArg = $command->getArgs()[1];
                if (isset($statusMap[$statusArg]) && $statusMap[$statusArg] != USER_STATUS_ADMIN) {
                    $status = $statusMap[$statusArg];
                } else {
                    return false;
                }

                $chat       = Chats::getChat($command->chatId);
                $name       = $command->getArgs()[2];
                $secondName = isset($command->getArgs()[3]) ? $command->getArgs()[3] : '';
                $user       = Users::getUserByName($command->chatId, $name, $secondName);
                if (!$user) {
                    $chat->sendMessage("Не найден участник беседы '$name $secondName'");
                    return false;
                }
                if ($user->userId == $command->userId) {
                    $chat->sendMessage("Собственный статус не может быть изменен");
                    return false;
                }
                if (Users::getStatus($command->chatId, $user->userId) == USER_STATUS_ADMIN) {
                    $chat->sendMessage("Статус данного пользователя не может быть изменен");
                    return false;
                }
                if (Users::getStatus($command->chatId, $command->userId) <= $status) {
                    $chat->sendMessage("Вы не можете устанавливать данный статус");
                    return false;
                }
                $user->status = $status;
                $user->save();
                $chat->sendMessage("Статус пользователя '{$user->name} {$user->secondName}' установлен на '$statusArg'");
            },
            ['statusDefault' => USER_STATUS_MODER]
        );

        $commands[] = new ChatCommand(
            'статус участников',
            'Показать уровни доступов всех участников.',
            function ($command) use ($s) {
                $s->load($command);
                return $s->argsEqual(2) && $s->argsRegExp(['статус', 'участников']);
            },
            function ($command) {
                $statusLabels = Params::bot(['statusLabels']);
                $chat         = Chats::getChat($command->chatId);
                $users        = $chat->getAllActiveUsers();
                $message      = "Статус участников беседы:\n";
                usort($users, function ($a, $b) {
                    return $b->status - $a->status;
                });
                foreach ($users as $user) {
                    $message .= "\n{$user->name} {$user->secondName} ({$statusLabels[$user->status]})";
                }
                $chat->sendMessage($message);
            }
        );

        $commands[] = new ChatCommand(
            'команды',
            'Показать все команды.',
            function ($command) use ($s) {
                $s->load($command);
                return $s->argsEqual(1) && $s->argsRegExp(['команды']);
            },
            function ($command) {
                $chat      = Chats::getChat($command->chatId);
                $commandsL = ChatCommands::getAllCommands();
                $message   = "Команды бота:\n";
                $commands  = array_filter($commandsL, function ($command) {
                    return !$command->hidden;
                });
                usort($commands, function ($a, $b) {
                    return strcasecmp($a->getName(), $b->getName());
                });
                foreach ($commands as $num => $c) {
                    $n = $num + 1;
                    $message .= "\n{$n}. '{$c->getName()}' - {$c->getDesc()}";
                }
                $message .= "\n\n{} - параметр \n[] - не обязательный параметр";
                $chat->sendMessage($message);
            }
        );

        $commands[] = new ChatCommand(
            'график стат { количество дней }',
            'Показать график статистики за несколько дней',
            function ($command) use ($s) {
                $s->load($command);
                return $s->argsEqual(3) && $s->argsRegExp(['график', 'стат', '[\d]+']);
            },
            function ($command) {
                $days   = intval($command->getArgs()[2]);
                $valArr = [];
                $time   = time();
                $chat   = Chats::getChat($command->chatId);

                $message = "Статистика пользователей за последние $days дней \n";
                $users   = $chat->getAllActiveUsers();
                foreach ($users as $user) {
                    $count = [];
                    for ($i = $days - 1; $i >= 0; $i--) {
                        $c       = MessagesCounter::getDayCount($command->chatId, $user->userId, $i, $time);
                        $count[] = $c;
                    }
                    $valArr["{$user->name} {$user->secondName}"] = $count;
                }
                uasort($valArr, function ($a, $b) use ($days) {
                    return $b[$days - 1] - $a[$days - 1];
                });
                $photoDir = PChart::drawAllStat($valArr, $days);
                $res      = ChatCommands::saveMessagePhoto($photoDir);
                $chat->sendMessage($message, [
                    "attachment" => "photo{$res[0]['owner_id']}_{$res[0]['id']}",
                ]);
            },
            ['statusDefault' => USER_STATUS_MODER]
        );

        static::$commands = $commands;
        return $commands;
    }

    public static function isCommand($commandToCheck)
    {
        $result = false;
        foreach (static::getAllCommands() as $command) {
            $result = $result || $command->check($commandToCheck);
        }
        return $result;
    }

    public static function getCommandByRegExp($name)
    {
        foreach (static::getAllCommands() as $command) {
            if (preg_match("/{$name}.*/", $command->getName())) {
                return $command;
            }

        }
    }

    public static function timeToStr($seconds)
    {
        $times = [];

        $count_zero = false;
        $periods    = [60, 3600, 86400];

        for ($i = 2; $i >= 0; $i--) {
            $period = floor($seconds / $periods[$i]);
            if (($period > 0) || ($period == 0 && $count_zero)) {
                $times[$i + 1] = $period;
                $seconds -= $period * $periods[$i];

                $count_zero = true;
            }
        }
        $times[0] = $seconds;

        $msg = '';
        isset($times[3]) && $msg .= $times[3] . ' дн. ';
        isset($times[2]) && $msg .= $times[2] . ' ч. ';
        isset($times[1]) && $msg .= $times[1] . ' мин. ';
        isset($times[0]) && $msg .= $times[0] . ' сек.';
        return $msg;
    }
	
	public static function timeToArr($seconds){
		$times = [];
        $count_zero = false;
        $periods    = [60, 3600, 86400];

        for ($i = 2; $i >= 0; $i--) {
            $period = floor($seconds / $periods[$i]);
            if (($period > 0) || ($period == 0 && $count_zero)) {
                $times[$i + 1] = $period;
                $seconds -= $period * $periods[$i];

                $count_zero = true;
            }
        }
        $times[0] = $seconds;
		return $times;
	}
	
    public static function saveMessagePhoto($photoDir)
    {
        $res       = Vk::get()->photos->getMessagesUploadServer();
        $uploadUrl = $res['upload_url'];

        $ch         = curl_init();
        $parameters = [
            'photo' => class_exists('CurlFile', false) ? new CURLFile($photoDir, 'image/png') : "@{$photoDir}",
        ];

        curl_setopt($ch, CURLOPT_URL, $uploadUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $parameters);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $curl_result = curl_exec($ch);
        curl_close($ch);

        $result = json_decode($curl_result);
        return Vk::get(true)->photos->saveMessagesPhoto([
            'photo'  => stripslashes($result->photo),
            'server' => $result->server,
            'hash'   => $result->hash,
        ]);
    }

}

class ChatCommand
{
    private $name;
    private $desc;
    private $condition;
    private $run;
    private $status;
    private $statusDefault;
    public $hidden;

    public function __construct($name, $desc, $condition, $run, $params = [])
    {
        $this->name = $name;
        $this->desc = $desc;

        if (strval(get_class($condition)) == 'Closure') {
            $this->condition = $condition;
        }
        if (strval(get_class($run)) == 'Closure') {
            $this->run = $run;
        }
        $this->load($params);
    }

    public function load($params)
    {
        foreach ($params as $key => $param) {
            if (property_exists(__CLASS__, $key)) {
                $this->$key = $param;
            }
        }
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
        $run       = $this->run;
        return (!empty($condition) && !empty($run) && $condition($command) && $this->statusCheck($command));
    }

    private function statusCheck($command)
    {
        $neededStatus = $this->getRequiredStatus($command->chatId);
        $userStatus   = Users::getStatus($command->chatId, $command->userId);
        return $userStatus >= $neededStatus;
    }

    public function getRequiredStatus($chatId)
    {
        $name         = CHAT_PARAMS_COMMAND_PREFIX . $this->name;
        $neededStatus = $this->status;
        if (empty($neededStatus)) {
            $neededStatus = ChatParams::get($chatId)->$name;
        }

        if (empty($neededStatus)) {
            $neededStatus = $this->statusDefault;
        }

        if (empty($neededStatus)) {
            $neededStatus = USER_STATUS_DEFAULT;
        }

        return $neededStatus;
    }

    public function getName()
    {
        return $this->name;
    }
    public function getDesc()
    {
        return $this->desc;
    }
    public function getStatus()
    {
        return $this->status;
    }
}
