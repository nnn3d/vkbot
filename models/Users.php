<?php

namespace app\models;

use Yii;
use app\models\Params;
use app\models\Chats;
use app\models\MessagesCounter;

/**
 * This is the model class for table "users".
 *
 * @property integer $id
 * @property integer $userId
 * @property integer $status
 * @property string $name
 * @property string $secondName
 * @property integer $chatId
 * @property integer $messages
 */
class Users extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'users';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['userId', 'name', 'secondName', 'chatId'], 'required'],
            [['userId', 'status', 'chatId', 'messages', 'lastActivity'], 'integer'],
            [['name', 'secondName'], 'string', 'max' => 255],
        ];
    }

    public function beforeSave($insert)
    {
        !$this->lastActivity && $this->lastActivity = time();
        if (parent::beforeSave($insert)) {
            
            return true;
        } else {
            return false;
        }
    }

    public static function getUser($chatId, $userId)
    {
        if ($userId == Params::get()->selfId) return new self;
        return static::userExists($chatId, $userId) ? static::findOne(['chatId' => $chatId, 'userId' => $userId]) : static::updateUser($chatId, $userId);
    }

    public static function getUserByName($chatId, $name, $secondName = '', $load = false)
    {
        $users = Chats::getChat($chatId)->getAllActiveUsers();
        foreach ($users as $user) {
            if (preg_match("/{$name}.*/ui", $user->name) && preg_match("/{$secondName}.*/ui", $user->secondName)) {
                return $user;
            }
        }
        return null;
    }

    public static function getStatus($chatId, $userId)
    {
        if (Params::get()->selfId == $userId) return 10;
        return static::getUser($chatId, $userId)->status;
    }

    public static function userExists($chatId, $userId, $load = false)
    {
        if ($userId == Params::get()->selfId) return false;
        if ($load) {
            $chatUsers = Chats::getChat($chatId)->loadUsers();
            $chatUsers = array_map(function ($user)
            {
                return $user['id'];
            }, $chatUsers);
            return in_array($userId, $chatUsers);
        }
        else return static::findOne(['chatId' => $chatId, 'userId' => $userId]) && true;
    }

    

    public static function setUser($chatId, $userId, $name, $secondName, $status = null, $messages = null, $lastActivity = null)
    {
        if ($userId == Params::get()->selfId) return;
        $user = static::userExists($chatId, $userId) ? static::getUser($chatId, $userId) : new self(['chatId' => intval($chatId), 'userId' => intval($userId)]);
        if (Chats::getChat($chatId)->adminId == $userId) $status = 10;
        $user->name = strval($name);
        $user->secondName = strval($secondName);
        $status != null && $user->status = intval($status);
        $messages != null && $user->messages = intval($messages);
        $lastActivity != null && $user->lastActivity = intval($lastActivity);
        $user->save();
        return $user;
    }

    public static function updateUser($chatId, $userId) 
    {

        $chatUsers = Chats::getChat($chatId)->loadUsers();
        foreach ($chatUsers as $elem) {
            if ($elem['id'] == $userId) $user = $elem;
        }
        if (!$user) return false;
        return static::setUser($chatId, $userId, $user['first_name'], $user['last_name']);
    }

    public static function incrementCounter($chatId, $userId, $newMessages, $time = null)   
    {
        if (!$user = static::getUser($chatId, $userId)) return false;
        $user->messages = $user->messages + $newMessages;
        $user->lastActivity = $time;
        $user->save();
        MessagesCounter::increment($chatId, $userId, $newMessages, $time);
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'userId' => 'User ID',
            'status' => 'Status',
            'name' => 'Name',
            'secondName' => 'Second Name',
            'chatId' => 'Chat ID',
            'messages' => 'Messages',
        ];
    }

    /**
     * @inheritdoc
     * @return UsersQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new UsersQuery(get_called_class());
    }
}
