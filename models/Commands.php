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

    public $argsCountSkip = false;

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
            [['chatId', 'userId', 'messageId', 'time'], 'integer'],
            [['args'], 'string'],
            [['command'], 'string', 'max' => 255],
        ];
    }


    public static function addFromMessage($chatId, $userId, $message, $messageId = null, $command = COMMAND_USER)
    {
        $botName = Params::bot('name');
        $args = explode(' ', $message);
        $msg = implode(' ', $args);
        // Yii::info("check name " . "/{$botName}[\W]{0, 1}/i" . "and $args[0]", 'bot-log');
        if (!isset($args[1]) || !preg_match("/{$botName}[\W]?/iu" || !preg_match("/miya[\W]?/iu", $args[0])) return;
        static::add($chatId, $userId, array_slice($args, 1), $messageId, $command);
        Yii::info("add command '$message' from chat $chatId", 'bot-log');
    }

    public static function add($chatId, $userId = null, $args, $messageId = null, $command = COMMAND_USER)  
    {
        $self = new self([
            'chatId' => $chatId,
            'userId' => $userId,
            'command' => $command,
            'messageId' => $messageId,
            'args' => serialize($args),
            'time' => time(),
        ]);
        $self->save();
    }

    public function getArgs()
    {
        $data = preg_replace('!s:(\d+):"(.*?)";!e', "'s:'.strlen('$2').':\"$2\";'", $this->args);
        return unserialize($data);
    }

    public function setArgs($args)
    {
        $this->args = serialize($args);
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
