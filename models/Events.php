<?php

namespace app\models;

use Yii;
use app\models\Users;
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

    public static function setEvent($chatId, $userId, $time, $event)
    {
        if (Events::find()->where(['chatId' => $chatId, 'userId' => $userId, 'time' => $time])->exists()) return false;
        $self = new self([
            'chatId' => $chatId,
            'userId' => $userId,
            'time' => $time,
            'event' => $event,
        ]);
        $self->save();
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
