<?php

namespace app\models;

use app\models\Chats;
use app\models\ChatParams;
use app\models\MessagesCounter;
use app\models\PendingTasks;
use app\models\Users;
use app\models\PChart;

class ChatCommands
{
    private static $commands;
    private $chatId;
    private $userId;
    private $args;
    private $argsCountSkip;
    private function load($command)
    {
        $this->chatId = $command->chatId;
        $this->chatId = $command->messageId;
        $this->userId = $command->userId;
        $this->args   = $command->getArgs();
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
            'брак { да или нет }',
            'Описание',
            function ($command) use ($s)
            {
                $s->load($command);
                return $s->argsEqual(2) && $s->argsRegExp(['брак', '(да|нет)']);
            }, 
            function ($command) 
            {
                $chat = Chats::getChat($command->chatId);
                $brak = Commands::findOne(['command' => COMMAND_MARRIAGE, 'chatId' => $command->chatId]);
		if (!$brak) return false;

                $user1 = Users::getUser($command->chatId, $brak->getArgs()[0]);
		$user2 = Users::getUser($command->chatId, $brak->getArgs()[1]);
		$pioneerUser = Users::getUser($command->chatId, $command->userId);
		    
		if ($pioneerUser == $user2) return false;
		unset($pioneerUser);
                
		$botName = Params::bot('name');
                if ($command->getArgs()[1] == 'нет') {
                    $chat->sendMessage("{$user1->name} {$user1->secondName} не согласился");
                    $brak->delete();
                    return false;
                } else if ($command->getArgs()[1] == 'да') {
		    $marriage = ChatParams::findOne(['param' => COMMAND_MARRIAGE, 'chatId' => $command->chatId]);
		    $value = array['$user1|$user2'];
		    if(!$marriage) ChatParams::setMarriage($command->chatId, COMMAND_MARRIAGE, $value);
		    ChatParams::updateMarriage($command->chatId, COMMAND_MARRIAGE, $value);	
		    $chat->sendMessage("{$user1->name} {$user1->secondName} и {$user2->name} {$user2->secondName} теперь женаты!");
		    $brak->delete();
		    return false;
		}
            },
            ['hidden' => true]
        );
	    
	$commands[] = new ChatCommand(
            'брак { имя [ + фамилия ] участника }',
            'Описание',
            function ($command) use ($s)
            {
                $s->load($command);
                return $s->argsLarger(1) && $s->argsRegExp(['брак', '(?!да|нет)']);
            }, 
            function ($command) 
            {
                $chat = Chats::getChat($command->chatId);
                if (Commands::find()->where(['command' => COMMAND_MARRIAGE, 'chatId' => $command->chatId])->exists()) {
                    $chat->sendMessage("В данный момент регистрируется другая пара. Ждите.");
                    return false;
                }
                $name = $command->getArgs()[1];
                $secondName = isset($command->getArgs()[2]) ? $command->getArgs()[2] : '';
                $user = Users::getUserByName($command->chatId, $name, $secondName);
                if (!$user) {
                    $chat->sendMessage("Я не могу найти человека с таким именем среди участников конференции");
                    return false;
                } 
                if ($command->userId == $user->userId) {
                    $chat->sendMessage("Жениться на самом себе пока нелья...");
                    return false;
                } 
                $pioneerUser = Users::getUser($command->chatId, $command->userId);
                $args = [
                    $user->userId,
                    $command->userId,
                ];
                $botName = Params::bot('name');
                $message = "Брак между {$user->name} {$user->secondName} и {$pioneerUser->name} {$pioneerUser->secondName}.\n (команда \"$botName брак да\" или \"$botName брак нет\" для отказа)";
                Commands::add($command->chatId, null, $args, null, COMMAND_MARRIAGE);

                $chat->sendMessage($message);
            }
        );
	    
        $commands[] = new ChatCommand(
            'дуэль { имя [ + фамилия ] участника }',
            'Вызвать участника на дуэль.',
            function ($command) use ($s)
            {
                $s->load($command);
                return $s->argsLarger(1) && $s->argsRegExp(['дуэль', '[^+-]']);
            }, 
            function ($command) 
            {
                $chat = Chats::getChat($command->chatId);
                if (Commands::find()->where(['command' => COMMAND_DUEL, 'chatId' => $command->chatId])->exists()) {
                    $chat->sendMessage("Дуэль уже идет, для новой еще не время!");
                    return false;
                }
                $name = $command->getArgs()[1];
                $secondName = isset($command->getArgs()[2]) ? $command->getArgs()[2] : '';
                $user = Users::getUserByName($command->chatId, $name, $secondName);
                if (!$user) {
                    $chat->sendMessage("Я не могу найти оппонента с таким именем среди участников конференции");
                    return false;
                } 
                if ($command->userId == $user->userId) {
                    $chat->sendMessage("Нельзя вызвать на дуэль самого себя");
                    return false;
                } 
                $pioneerUser = Users::getUser($command->chatId, $command->userId);
                $args = [
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
            function ($command) use ($s)
            {
                $s->load($command);
                return $s->argsEqual(2) && $s->argsRegExp(['дуэль', '[+-]']);
            }, 
            function ($command) 
            {
                $chat = Chats::getChat($command->chatId);
                $duel = Commands::findOne(['command' => COMMAND_DUEL, 'chatId' => $command->chatId]);
                if (!$duel) return Chats::getChat(16)->sendMessage('no2');
                $user1 = Users::getUser($command->chatId, $duel->getArgs()[0]);
                if ($user1->userId != $command->userId) return Chats::getChat(16)->sendMessage('no1');
                if ($command->getArgs()[1] == '-') {
                    $chat->sendMessage("{$user1->name} {$user1->secondName} отклонил дуэль, жалкий трус!");
                    $duel->delete();
                    return false;
                }
                $botName = Params::bot('name');
                $prefix = "$botName битва ";
                $str = substr(strtolower(md5(uniqid(rand(), true))),0,6);
                preg_match_all('/./us', $prefix . $str, $ar);
                $strrev = join('',array_reverse($ar[0]));
                $args = [
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
            function ($command) use ($s)
            {
                $s->load($command);
                return $s->argsEqual(2) && $s->argsRegExp(['битва']);
            }, 
            function ($command) 
            {
                $chat = Chats::getChat($command->chatId);
                $duel = Commands::findOne(['command' => COMMAND_DUEL, 'chatId' => $command->chatId]);
                if (!$duel) return;
                $user1 = Users::getUser($command->chatId, $duel->getArgs()[0]);
                $user2 = Users::getUser($command->chatId, $duel->getArgs()[1]);
                if ($command->userId == $user1->userId) {
                    $winUser = $user1;
                    $looseUser = $user2;
                } else if ($command->userId == $user2->userId) {
                    $winUser = $user2;
                    $looseUser = $user1;
                } else return false;
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
            'Добавить повторяющееся событие. Например "'.Params::bot('name').' повторяй 5 кто бот" будет выполнять команду "кто" каждые 5 мин.',
            function ($command) use ($s) {
                $s->load($command);
                return $s->argsLarger(2) && $s->argsRegExp(['повторяй', '[\d]+']);
            },
            function ($command) {
                $minutes   = intval($command->getArgs()[1]);
                if ($minutes < 1) $minutes = 1;
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
            'Убирает повторяющуюся задачу. Посмотреть задачи можно командой "/$botName покажи повторения/".',
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
            'топ активность',
            'Показывает время с последнего сообщения пользователя.',
            function ($command) use ($s) {
                $s->load($command);
                return $s->argsEqual(2) && $s->argsRegExp(['топ', 'активность']);
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
            'Количесвто символов всех участников указанный срок.',
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
            'кто { любой вопрос }',
            'В ответ дает случайного участника.',
            function ($command) use ($s) {
                $s->load($command);
                return $s->argsLarger(1) && $s->argsRegExp(['кто']);
            },
            function ($command) {
                $chat  = Chats::getChat($command->chatId);
                $users = $chat->getAllActiveUsers();
                $r     = mt_rand(0, count($users) - 1);
                $c     = implode(' ', array_slice($command->getArgs(), 1));
                $countC = substr_count($c, '?');
                $c = trim($c, "?");
                if ($countC == '1') {
                    $chat->sendMessage("Cчитаю, что \"$c\" - {$users[$r]->name} {$users[$r]->secondName}", $command->messageId);
                } else if (empty($c)) {
                    return false;
                } else {
                    $chat->sendMessage("Я думаю, что {$users[$r]->name} {$users[$r]->secondName}", ['forward_messages'=>$command->messageId]);
                }
            }
        );

        $commands[] = new ChatCommand(
            'установить статус команды { админ / модер / все } { название команды }',
            'Выставляет уровень допуска для команды.',
            function ($command) use ($s) {
                $s->load($command);
                return $s->argsLarger(4) && $s->argsRegExp(['установить', 'статус', 'команды']);
            },
            function ($command) {
            	$statusMap = Params::bot(['statusMap']);
            	$statusArg = $command->getArgs()[3];
            	if (isset($statusMap[$statusArg])) $status = $statusMap[$statusArg];
            	else return false;

                $chat  = Chats::getChat($command->chatId);
            	$commandArgsS = implode(' ', array_slice($command->getArgs(), 4));
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
            	$name = CHAT_PARAMS_COMMAND_PREFIX . $changedCommand->getName();
            	ChatParams::get($command->chatId)->$name = $status;
                $chat->sendMessage("Статус выполнения команды '$commandName' установлен на '$statusArg'");
            },
            ['statusDefault' => USER_STATUS_ADMIN]
        );
		
		$commands[] = new ChatCommand(
            'кикнуть участника { имя [ + фамилия ] участника }',
            'Кикает указанного участника из беседы.',
            function ($command) use ($s) {
                $s->load($command);
                return $s->argsLarger(2) && $s->argsRegExp(['кикнуть', 'участника']);
            },
            function ($command) {
                $chat  = Chats::getChat($command->chatId);
            	$name       = $command->getArgs()[2];
            	$secondName = isset($command->getArgs()[3]) ? $command->getArgs()[3] : '';
            	$user       = Users::getUserByName($command->chatId, $name, $secondName);
            	if (!$user) {
            	    $chat->sendMessage("Не найден участник беседы '$name $secondName'");
            	    return false;
            	}
            	else if ($user->userId == $command->userId) {
            		$chat->sendMessage("Нельзя себя кикнуть");
            		return false;
            	}
            	else if (Users::getStatus($command->chatId, $user->userId) != USER_STATUS_DEFAULT) {
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
				$chat = Chats::getChat($command->chatId);
				$rules = ChatParams::get($command->chatId)->rules;
				$chat->sendMessage("Правила конфы:\n$rules");
			},
			['statusDefault' => USER_STATUS_MODER]
		);
		
		$commands[] = new ChatCommand( 
			'установить правила',
			'Выдает правила беседы',
			function ($command) use ($s) {
				$s->load($command);
				return $s->argsLarger(2) && $s->argsRegExp(['установить', 'правила']);
			},
			function ($command) {
				$chat = Chats::getChat($command->chatId);
				$rules = ChatParams::get($command->chatId)->rules;
				$c     = implode(' ', array_slice($command->getArgs(), 2));
                $countC = substr_count($c, '?');
                $c = trim($c, "?");
				ChatParams::get($command->chatId)->rules = $c;
				$chat->sendMessage("Правила для беседы устанволены!", ['forward_messages'=>$command->messageId]);
			},
			['statusDefault' => USER_STATUS_MODER]
		);
		
        $commands[] = new ChatCommand(
            'установить статус участника { модер / юзер } { имя [ + фамилия ] участника }',
            'Выставить уровень доступа к командам для участника.',
            function ($command) use ($s) {
                $s->load($command);
                return $s->argsLarger(3) && $s->argsRegExp(['установить', 'статус', 'участника']);
            },
            function ($command) {
            	$statusMap = Params::bot(['statusMap']);
            	$statusArg = $command->getArgs()[3];
            	if (isset($statusMap[$statusArg]) && $statusMap[$statusArg] != USER_STATUS_ADMIN) 
            		$status = $statusMap[$statusArg];
            	else return false;

                $chat  = Chats::getChat($command->chatId);
            	$name       = $command->getArgs()[4];
            	$secondName = isset($command->getArgs()[5]) ? $command->getArgs()[5] : '';
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
                $chat  = Chats::getChat($command->chatId);
            	$users = $chat->getAllActiveUsers();
            	$message = "Статус участников беседы:\n";
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
                $chat  = Chats::getChat($command->chatId);
                $commandsL = ChatCommands::getAllCommands();
                $message = "Команды бота:\n";
                $commands = array_filter($commandsL, function ($command)
                {
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
                $days = intval($command->getArgs()[2]);
                $valArr = [];
                $time = time();
                $chat = Chats::getChat($command->chatId);

                $message = "Статистика пользователей за последние $days дней \n";
                $users = $chat->getAllActiveUsers();
                foreach ($users as $user) {
                    $count = [];
                    for ($i = $days - 1; $i >= 0; $i--) {
                        $c     = MessagesCounter::getDayCount($command->chatId, $user->userId, $i, $time);
                        $count[] = $c;
                    }
                    $valArr["{$user->name} {$user->secondName}"] = $count;
                }
                uasort($valArr,  function ($a, $b) use ($days)
                {
                    return $b[$days-1] - $a[$days-1];
                });
                $photoDir = PChart::drawAllStat($valArr, $days);
                $res = ChatCommands::saveMessagePhoto($photoDir);
                $chat->sendMessage($message, [
                    "attachment" => "photo{$res[0]['owner_id']}_{$res[0]['id']}"
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
    	    if (preg_match("/{$name}.*/", $command->getName())) return $command;
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



    public static function saveMessagePhoto($photoDir)
    {
        $res = Vk::get()->photos->getMessagesUploadServer();
        $uploadUrl = $res['upload_url'];

        $ch = curl_init();
        $parameters = [
            'photo' => class_exists('CurlFile', false) ? new CURLFile($photoDir, 'image/png') : "@{$photoDir}"
        ];

        curl_setopt($ch, CURLOPT_URL, $uploadUrl);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $parameters);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $curl_result = curl_exec($ch);
        curl_close($ch);

        $result = json_decode($curl_result);
        return Vk::get(true)->photos->saveMessagesPhoto([
            'photo' => stripslashes($result->photo),
            'server' => $result->server,
            'hash' => $result->hash,
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
    	$userStatus = Users::getStatus($command->chatId, $command->userId);
    	return $userStatus >= $neededStatus;
    }

    public function getRequiredStatus($chatId)
    {
    	$name = CHAT_PARAMS_COMMAND_PREFIX . $this->name;
    	$neededStatus = $this->status;
    	if (empty($neededStatus)) $neededStatus = ChatParams::get($chatId)->$name;
        if (empty($neededStatus)) $neededStatus = $this->statusDefault;
    	if (empty($neededStatus)) $neededStatus = USER_STATUS_DEFAULT;
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
