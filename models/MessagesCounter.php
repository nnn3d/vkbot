<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "messagesCounter".
 *
 * @property integer $chatId
 * @property integer $userId
 * @property integer $year
 * @property integer $month
 * @property integer $day
 * @property integer $messages
 */
class MessagesCounter extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'messagesCounter';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['chatId', 'userId', 'year', 'month', 'day'], 'required'],
            [['chatId', 'userId', 'year', 'month', 'day', 'messages'], 'integer'],
        ];
    }


    public static function increment($chatId, $userId, $newMessages, $time = null)
    {
        (!is_int($time) || !isset($time)) && $time = time();
        list($year, $month, $day) = explode(" ", date("y n j", $time));
        $params = ['chatId' => $chatId, 'userId' => $userId, 'year' => $year, 'month' => $month, 'day' => $day];
        $counter = static::findOne($params);
        if (!$counter) $counter = new self($params);
        $counter->messages = $counter->messages + $newMessages;
        $counter->save();
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'Id' => 'ID',
            'chatId' => 'Chat ID',
            'userId' => 'User ID',
            'year' => 'Year',
            'month' => 'Month',
            'day' => 'Day',
            'messages' => 'Messages',
        ];
    }

    /**
     * @inheritdoc
     * @return MessagesCounterQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new MessagesCounterQuery(get_called_class());
    }
}
