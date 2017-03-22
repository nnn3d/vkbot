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

    public function sendMessage($message, $forwardMessages = '')
    {
        Vk::get()->messages->send([
            'chat_id' => $this->chatId,
            'message' => $message,
            'forward_messages' => $forwardMessages,
        ]);
    }
	
	public function kickUser($userId)
	{
		return Vk::get(true)->messages->removeChatUser([
			'chat_id' => $this->chatId,
			'user_id' => $userId,
		]);
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
            $chatInfo = Vk::get()->messages->getHistory([
                'peer_id' => 2000000000 + intval($chatId),
                'count' => 1,
            ]);
            $chatInfo2 = Vk::get()->messages->getChat([
                'chat_id' => $chatId,
            ]);
            $chatInfo['items'][0]['admin_id'] = $chatInfo2['admin_id'];
            $chat = static::addChatFromMessage($chatInfo['items'][0]);
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

    public static function chatExists($chatId)
    {
        return static::find()->where(['chatId' => $chatId])->exists();
    }

    public static function getAllChats($load = false)
    {
        if ($load) static::updateChats();
        return static::find()->all();
    }

    public function getAllUsers($load = false)
    {
        if ($load) $this->updateUsers();
        return Users::findAll(['chatId' => $this->chatId]);
    }

    public function getAllActiveUsers()
    {
        $users = $this->getAllUsers(true);
        $activeUsers = array_map(function ($user)
        {
            return $user['id'];
        }, $this->loadUsers());
        $resultUsers = [];
        foreach ($users as $user) {
           if (in_array($user->userId, $activeUsers)) $resultUsers[] = $user; 
        }
        return $resultUsers;
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
            Users::setUser($this->chatId, $user['id'], $user['first_name'], $user['last_name']);
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
            $chat = static::addChatFromMessage($elem['message']);
            if (!static::chatExists($chat->chatId)) {
                $chat->save();
            }
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
        if (count($items) + $prevCount < $chats['count']) {
            $items = array_merge(
                $items, 
                static::loadChatsRecursive(
                    $unread, 
                    $offset + $params['count'], 
                    count($items) + $prevCount
                )
            );
        }
        $itemsF = array_filter($items, function ($var)
        {
            return isset($var['message']['chat_id']);
        });
        return $itemsF;
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

/**
 * This is the ActiveQuery class for [[Chats]].
 *
 * @see Chats
 */
class ChatsQuery extends \yii\db\ActiveQuery
{
    /*public function active()
    {
        return $this->andWhere('[[status]]=1');
    }*/

    /**
     * @inheritdoc
     * @return Chats[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return Chats|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}