<?php

namespace app\models;

use Yii;
use app\models\Users;
use app\models\Vk;
use app\models\Chats;
use app\models\Params;

/**
 * This is the model class for table "events".
 *
 * @property integer $id
 * @property integer $chatId
 * @property integer $userId
 * @property integer $time
 * @property string $event
 */
class Events extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'events';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['chatId', 'userId', 'time', 'event'], 'required'],
            [['chatId', 'userId', 'time'], 'integer'],
            [['event'], 'string', 'max' => 20],
        ];
    }
	
	public static function getEvent($chatId, $event) 
	{ 
		$eventList = static::findAll(['chatId' => $chatId, 'event' => $event]); 
		return $eventList; 
	}
	
    public static function setEvent($chatId, $userId, $time, $event, $midEvent = null)
    {
        if (Events::find()->where(['chatId' => $chatId, 'userId' => $userId, 'time' => $time])->exists()) return false;
        
        switch ($event){ 
            case "chat_create": 
            $event = "chat_create";
            break; 
            case "chat_title_update": 
            $event = "title_update";
			Events::changeName($chatId, $userId);
            break; 
            case "chat_photo_update": 
            $event = "photo_update";
			Events::changePhoto($chatId, $userId);
            break; 
            case "chat_invite_user":
            if($userId == $midEvent) {
                $event = "return_user";
            } else {
                $event = "invite_user";
                Events::rightsToInvite($chatId, $userId, $midEvent);
            } 
            break; 
            case "chat_kick_user": 
            if($userId == $midEvent) {
                $event = "leave_user";
			Events::returnLeaveUser($chatId, $userId);
            } else {
                $event = "kick_user";
            }
            break; 
            default:
            break; 
        }

        $self = new self([
            'chatId' => $chatId,
            'userId' => $userId,
            'time' => $time,
            'event' => $event,
        ]);
        $self->save();
    }
    
    public static function returnLeaveUser($chatId, $userId){
	    if (Chats::getChat($chatId)->adminId != '399829682') return false;
	    $chat = Chats::getChat($chatId);
	    $user = users::userExists($chatId, $userId);
	    if (($chat->inviteUser($userId)) || ($user)) {
		    $chat->sendMessage("Выход из беседы для вас крайне не желателен!");
	    }
    }
	public static function changeName($chatId, $userId) {
		$chat = Chats::getChat($chatId);
		if ($userId=='399829682') return false;
		$chatName = ChatParams::get($chatId)->chatName;
		if ($chatName!='^') {
    			Vk::get(true)->messages->editChat(['chat_id' => $chatId, 'title'=>$chatName]);
		} else {
			return false;
		}
	}
	/*public static function getLastInvite($chatId, $userId, $invitationUserId) 
	{ 
		$chat = Chats::getChat($chatId);
		$timevents = static::findAll(['chatId' => $chatId, 'event' => 'invite_user', 'userId' => $userId]);
		usort($timevents, function ($a, $b) {
                    return $b->time - $a->time;
                });
		foreach timevents as $times {
			$user = Users::getUser($chatId, $invitationUserId);
			$user->invdate=timeevents
		}
		$timevent =$timevents[1];
		$user = Users::getUser($chatId, $invitationUserId);
		$user->invdate=$timevent->time; 
		$user->save();
		return $timevent->time; 
	}*/
	
    public static function changePhoto($chatId, $userId){   
	    $user = Users::getUser($chatId, $userId);
	    if (Users::getStatus($chatId, $userId) == USER_STATUS_ADMIN || Users::getStatus($chatId, $userId) == USER_STATUS_MODER) return false;
	    $chat = Chats::getChat($chatId); 
	    $chat->sendMessage("Боюсь, что в этой беседе нельзя менять фотографию диалога. Я вынуждена сейчас же её удалить.");
	    if(!Vk::get(true)->messages->deleteChatPhoto(['chat_id' => $chatId])) $chat->sendMessage("Что-то пошло не так... Я займусь этим позже.");
    }
	    
    public static function rightsToInvite($chatId, $userId, $invitationUserId)
    {
        $chat = Chats::getChat($chatId);
	$user = Users::getUser($chatId, $userId);
	if (Chats::getChat($chatId)->adminId != '399829682') return false;
	$invitationUser = Users::getUser($chatId, $invitationUserId);
        if (Users::getStatus($chatId, $userId) != USER_STATUS_DEFAULT && Users::getStatus($chatId, $userId) != USER_STATUS_UNTOUCHABLE) {
		$rules = ChatParams::get($chat->chatId)->rules;
		$welcome = ChatParams::get($chat->chatId)->welcome;
		if (!$welcome) return false;
		$chat->sendMessage("[id{$invitationUserId}|{$invitationUser->name} {$invitationUser->secondName}], {$welcome}");
		if (!$rules) return false;
		$chat->sendMessage("Правила конфы:\n {$rules}");
		return false;
	} else if(Users::getStatus($chatId, $userId) == USER_STATUS_UNTOUCHABLE) {
		$rules = ChatParams::get($chat->chatId)->rules;
		$welcome = ChatParams::get($chat->chatId)->welcome;
		if (!$welcome) return false;
		$chat->sendMessage("[id{$invitationUserId}|{$invitationUser->name} {$invitationUser->secondName}], {$welcome}");
		if (!$rules) return false;
		$chat->sendMessage("Правила конфы:\n {$rules}");
		$user->status = USER_STATUS_DEFAULT;
		$user->save();
		$chat->sendMessage("{$user->name} {$user->secondName} использовал свое право пригласить человека.\n\nЯ уже изменила его статус.");
		return false;
	}
        $kick1 = false;
        $kick2 = false;
        
        $chat->sendMessage("Приглашать людей в эту беседу без согласования с админами запрещено.\nСогласно правилам, {$user->name} {$user->secondName} и {$invitationUser->name} {$invitationUser->secondName} будут выкинуты из чата.");
        
        if (!$chat->kickUser($invitationUserId)) {
            $chat->sendMessage("Мне не удалось кикнуть пользователя {$invitationUser->name} {$invitationUser->secondName}");
        } else {
            $kick1 = true;
            $setDo = false;
            $users = $chat->getAllActiveUsers();
            $message = "Для возвращения в беседу обращайтесь к одному из следующего списка людей: \n";
            foreach ($users as $userData) {
                $status = $userData->status;
                if($status == USER_STATUS_ADMIN) {
                    $message .= "\n vk.com/id{$userData->userId} ({$userData->name} {$userData->secondName})";
		    $setDo = true;
                }
            }
            
            if($setDo) Vk::get(true)->messages->send(['user_id' => $invitationUserId, 'message' => $message]);
        }
        
        $chat->sendMessage("У {$user->name} {$user->secondName} есть 10 секунд на последнее слово.");
        sleep(10);
        
        if (!$chat->kickUser($userId)) {
            $chat->sendMessage("Мне не удалось кикнуть пользователя {$user->name} {$user->secondName}");
        } else {
            $kick2 = true;
	    $setDo = false;	
            $users = $chat->getAllActiveUsers();
            $message = "Для возвращения в беседу обращайтесь к одному из следующего списка людей: \n";
            foreach ($users as $userData) {
                $status = $userData->status;
                if($status == USER_STATUS_ADMIN) {
                    $message .= "\n vk.com/id{$userData->userId} ({$userData->name} {$userData->secondName})";
	            $setDo = true;
                }
            }
            if($setDo) Vk::get(true)->messages->send(['user_id' => $userId, 'message' => $message]);
        }
        
	    if($chatId == '2') {
		    if($kick1 == true && $kick2 == true) {
			    $report = "Недавно я выгнала из беседы 2 участников:\n\n vk.com/id$userId ({$user->name} {$user->secondName}) (инвайтнул)\n vk.com/id$invitationUserId ({$invitationUser->name} {$invitationUser->secondName}) (инвайтнули)";
		    } else if($kick1 == true && $kick2 == false){
			    $report = "Недавно я выгнала из беседы 1 участника:\n vk.com/id$invitationUserId ({$invitationUser->name} {$invitationUser->secondName}) (инвайтнули). \n\n Однако у меня не получилось выгнать другого участника – vk.com/id$userId ({$user->name} {$user->secondName}) (инвайтнул)";
		    } else if($kick1 == false && $kick2 == true){
			    $report = "Недавно я выгнала из беседы 1 участника:\n vk.com/id$userId ({$user->name} {$user->secondName}) (инвайтнул). \n\n Однако у меня не получилось выгнать другого участника – vk.com/id$invitationUserId ({$invitationUser->name} {$invitationUser->secondName}) (инвайтнули)";
		    } else {
			    $report = "Я попыталась выгнать из беседы 2 участников, но у меня ничего не получилось:\n\n vk.com/id$userId ({$user->name} {$user->secondName}) (инвайтнул)\n vk.com/id$invitationUserId ({$invitationUser->name} {$invitationUser->secondName}) (инвайтнули)";
		    }
		    
		    Vk::get(true)->messages->send(['user_id' => '266979404', 'message' => $report]);
	    }
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'chatId' => 'Chat ID',
            'userId' => 'User ID',
            'time' => 'Time',
            'event' => 'Event',
        ];
    }

    /**
     * @inheritdoc
     * @return EventsQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new EventsQuery(get_called_class());
    }
}


/**
 * This is the ActiveQuery class for [[Events]].
 *
 * @see Events
 */
class EventsQuery extends \yii\db\ActiveQuery
{
    /*public function active()
    {
        return $this->andWhere('[[status]]=1');
    }*/

    /**
     * @inheritdoc
     * @return Events[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return Events|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
