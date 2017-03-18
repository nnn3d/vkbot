<?php

namespace app\models;

use Yii;
use app\models\Params;

/**
 * This is the model class for table "commands".
 *
 * @property integer $id
 * @property integer $chatId
 * @property string $command
 * @property string $args
 * @property string $timestamp
 */
class Commands extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'commands';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['chatId'], 'required'],
            [['chatId', 'userId'], 'integer'],
            [['args'], 'string'],
            [['timestamp'], 'safe'],
            [['command'], 'string', 'max' => 255],
        ];
    }


    public static function addFromMessage($chatId, $userId, $message, $command = 'user')
    {
        $botName = Params::bot('name');
        $args = explode(' ', $message);
        $msg = implode(' ', $args);
        // Yii::info("check name " . "/{$botName}[\W]{0, 1}/i" . "and $args[0]", 'bot-log');
        if (!isset($args[1]) || !preg_match("/{$botName}[\W]?/iu", $args[0])) return;
        static::add($chatId, $userId, array_slice($args, 1), $command);
        Yii::info("add command '$message' from chat $chatId", 'bot-log');
    }

    public static function add($chatId, $userId, $args, $command = 'user')  
    {
        $self = new self([
            'chatId' => $chatId,
            'userId' => $userId,
            'command' => $command,
            'args' => serialize($args),
        ]);
        $self->save();
    }

    public function getArgs()
    {
        return unserialize($this->args);
    }

    public static function getByChat($chatId)
    {
        return static::findAll(['chatId' => $chatId]);
    }

    public static function getAll()
    {
        return static::find()->all();
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'chatId' => 'Chat ID',
            'command' => 'Command',
            'args' => 'Args',
            'timestamp' => 'Timestamp',
        ];
    }
}
