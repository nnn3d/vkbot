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

    public static function setEvent($chatId, $userId, $time, $event, $midEvent = null)
    {
        if (Events::find()->where(['chatId' => $chatId, 'userId' => $userId, 'time' => $time])->exists()) return false;
        
        switch ($event){ 
            case "chat_create": 
            $event = "chat_create";
            break; 
            case "chat_title_update": 
            $event = "title_update";
            break; 
            case "chat_photo_update": 
            $event = "photo_update";
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
    
    public static function rightsToInvite($chatId, $userId, $invitationUserId)
    {
        $chat = Chats::getChat($chatId);
        if (Users::getStatus($chatId, $userId) != USER_STATUS_DEFAULT) return false;
        $user = Users::getUser($chatId, $userId);
        $invitationUser = Users::getUser($chatId, $invitationUserId);
        $kick1 = false;
        $kick2 = false;
        
        $chat->sendMessage("Приглашать людей в эту беседу без согласования с админами запрещено.\nСогласно правилам, {$user->name} {$user->secondName} и {$invitationUser->name} {$invitationUser->secondName} будут выкинуты из чата.");
        
        if (!$chat->kickUser($invitationUserId)) {
            $chat->sendMessage("Мне не удалось кикнуть пользователя {$invitationUser->name} {$invitationUser->secondName}");
        } else {
            $kick1 = true;
            $statusLabels = Params::bot(['statusLabels']);
            $users = $chat->getAllActiveUsers();
            $message = "Для возвращения в беседу обращайтесь к одному из следующего списка людей: \n";
            usort($users, function ($a, $b) {
                return $b->status - $a->status;
            });
            foreach ($users as $userData) {
                $status = $statusLabels[$userData->status];
                if($status == 'модер') {
                    $message .= "\n vk.com/id{$userData->userId} ({$userData->name} {$userData->secondName})";
                }
            }
            
            Vk::get(true)->messages->send(['user_id' => $invitationUserId, 'message' => $message]);
        }
        
        $chat->sendMessage("У {$user->name} {$user->secondName} есть 10 секунд на последнее слово.");
        sleep(10);
        
        if (!$chat->kickUser($userId)) {
            $chat->sendMessage("Мне не удалось кикнуть пользователя {$user->name} {$user->secondName}");
        } else {
            $kick2 = true;
            $statusLabels = Params::bot(['statusLabels']);
            $users = $chat->getAllActiveUsers();
            $message = "Для возвращения в беседу обращайтесь к одному из следующего списка людей: \n";
            usort($users, function ($a, $b) {
                return $b->status - $a->status;
            });
            foreach ($users as $userData) {
                $status = $statusLabels[$userData->status];
                if($status == 'модер') {
                    $message .= "\n vk.com/id{$userData->userId} ({$userData->name} {$userData->secondName})";
                }
            }
            
            Vk::get(true)->messages->send(['user_id' => $userId, 'message' => $message]);
        }
        
        if($kick1 == true && $kick2 == true) {
            $report = 'Было кикнуто 2 участника: {$user->name} {$user->secondName} (инвайтнул) и {$invitationUser->name} {$invitationUser->secondName} (инвайтнули)';
        } else if($kick1 == true && $kick2 == false){
            $report = 'Был кикнут 1 участник: {$invitationUser->name} {$invitationUser->secondName} (инвайтнули). \n Кикнуть {$user->name} {$user->secondName} (инвайтнул) не удалось.';
        } else if($kick1 == false && $kick2 == true){
            $report = 'Был кикнут 1 участник: {$user->name} {$user->secondName} (инвайтнул). \n Кикнуть {$invitationUser->name} {$invitationUser->secondName} (инвайтнули) не удалось.';
        } else {
            $report = 'Не удалось кикнуть 2 участников: {$user->name} {$user->secondName} (инвайтнул) и {$invitationUser->name} {$invitationUser->secondName} (инвайтнули).';
        }
        
        Vk::get(true)->messages->send(['user_id' => '266979404', 'message' => $report]);
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
