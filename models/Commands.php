<?php

namespace app\models;

use Yii;

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
            [['chatId', 'command', 'args'], 'required'],
            [['chatId'], 'integer'],
            [['args'], 'string'],
            [['timestamp'], 'safe'],
            [['command'], 'string', 'max' => 255],
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
            'command' => 'Command',
            'args' => 'Args',
            'timestamp' => 'Timestamp',
        ];
    }
}
