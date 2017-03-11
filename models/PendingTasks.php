<?php

namespace app\models;

use Yii;

/**
 * This is the model class for table "pendingTasks".
 *
 * @property integer $id
 * @property integer $task
 * @property integer $args
 * @property integer $month
 * @property integer $day
 * @property integer $hour
 * @property integer $minute
 * @property integer $lastRun
 */
class PendingTasks extends \yii\db\ActiveRecord
{
    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'pendingTasks';
    }

    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['task', 'args', 'month', 'day', 'hour', 'minute'], 'required'],
            [['task', 'args', 'month', 'day', 'hour', 'minute', 'lastRun'], 'integer'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'task' => 'Task',
            'args' => 'Args',
            'month' => 'Month',
            'day' => 'Day',
            'hour' => 'Hour',
            'minute' => 'Minute',
            'lastRun' => 'Last Run',
        ];
    }

    /**
     * @inheritdoc
     * @return PendingTasksQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new PendingTasksQuery(get_called_class());
    }
}
