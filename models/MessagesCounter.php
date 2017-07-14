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
        if ($newMessages<700) {
        $counter->messages = $counter->messages + $newMessages;
        $counter->save();
        }
    }

    public static function getSumCount($chatId, $userId, $daysAgo, $time = null)
    {
        (!is_int($time) || !isset($time)) && $time = time();
        $count = 0;
        $time = time();
        for ($i=0; $i < $daysAgo; $i++) { 
            $count += static::getDayCount($chatId, $userId, $i, $time);
        }
        return $count;
    }

    public static function getDayCount($chatId, $userId, $daysAgo, $time = null)
    {
        (!is_int($time) || !isset($time)) && $time = time();
        $count = 0;
        list($year, $month, $day) = explode(" ", date("y n j", time() - ($daysAgo * 60 * 60 * 24)));
        $counter = static::findOne(['chatId' => $chatId, 'userId' => $userId, 'year' => $year, 'month' => $month, 'day' => $day]);
        if ($counter) $count = $counter->messages;
        return $count;
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

/**
 * This is the ActiveQuery class for [[MessagesCounter]].
 *
 * @see MessagesCounter
 */
class MessagesCounterQuery extends \yii\db\ActiveQuery
{
    /*public function active()
    {
        return $this->andWhere('[[status]]=1');
    }*/

    /**
     * @inheritdoc
     * @return MessagesCounter[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return MessagesCounter|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
