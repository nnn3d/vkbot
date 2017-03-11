<?php

namespace app\models;

use Yii;
use app\models\Vk;
use app\models\Users;

/**
 * This is the model class for table "chats".
 *
 * @property integer $id
 * @property integer $chatId
 * @property integer $adminId
 */
class Chats extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'chats';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['chatId', 'adminId'], 'required'],
            [['chatId', 'adminId', 'lastMessageId'], 'integer'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'chatId' => 'Chat ID',
            'adminId' => 'Admin ID',
            'lastMessageId' => 'Last Message ID',
        ];
    }

    public static function addChatFromMessage($message)
    {
        $chat = new self;
        $chat->chatId = $message['chat_id'];
        $chat->adminId = $message['admin_id'];
        $chat->lastMessageId = $message['id'];
        return $chat;
    }

    public static function addChat($chatId)
    {
        $chat = static::findOne(['chatId' => $chatId]);
        if (!$chat) {
            $chatInfo = Vk::get()->messages->getDialogs([
                'start_message_id' => 2000000000 + intval($chatId),
                'count' => 1,
            ]);
            $chat = static::addChatFromMessage($chatInfo['items'][0]['message']);
            $chat->save();
        }
        return $chat;
    }

    public static function getChat($chatId)
    {
        $chat = static::findOne(['chatId' => $chatId]);
        if (!$chat) $chat = static::addChat($chatId);
        return $chat;
    }

    public function getUser($userId)
    {
        $user = Users::getUser($chatId, $userId);
    }

    public function userExists($userId, $load = false)
    {
        return Users::userExists($this->chatId, $userId, $load);
    }

    public function updateUsers($load = false)
    {
        foreach ($this->loadUsers() as $user) {
            Users::updateUser($this->chatId, $user['id']);
        }
    }

    public function loadUsers()
    {
        return Vk::get()->messages->getChatUsers(['chat_id' => $this->chatId, 'fields' => 'photo']);
    }

    public static function updateChats()
    {
        $chats = static::loadChatsRecursive();
        foreach ($chats as $elem) {
            static::addChatFromMessage($elem['message']);
        }
    }

    private static function loadChatsRecursive($unread = false, $offset = 0, $prevCount = 0)
    {
        $params = [
            'count' => 200,
            'offset' => $offset,
            'unread' => $unread,
        ];
        $chats = Vk::get()->messages->getDialogs($params);
        $items = $chats['items'];
        if (count($items) + $prevCount < $chats['count']) $items = array_merge($items, static::loadChatsRecursive($unread, $offset + $params['count'], count($items) + $prevCount));
        $items = array_filter($items, function ($var)
        {
            return isset($var['message']['chat_id']);
        });
        return $items;
    }


    /**
     * @inheritdoc
     * @return ChatsQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new ChatsQuery(get_called_class());
    }
}
