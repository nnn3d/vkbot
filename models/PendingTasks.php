<?php

namespace app\models;

use Yii;
use app\models\CommandCaller;
use app\models\Commands;
use app\models\Params;

/**
 * This is the model class for table "pendingTasks".
 *
 * @property integer $id
 * @property string $task
 * @property string $args
 * @property integer $timeRepeat
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
            [['task', 'timeRepeat'], 'required'],
            [['timeRepeat', 'lastRun'], 'integer'],
            [['task', 'args'], 'string'],
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
            'timeRepeat' => 'Time Repeat',
            'lastRun' => 'Last Run',
        ];
    }

    public function getArgs()
    {
        return unserialize($this->args);
    }

    public static function add($chatId, $args, $timeRepeat, $task = 'user', $lastRun = null)
    {
        empty($lastRun) && $lastRun = time();
        (new PendingTasks([
            'task' => $task,
            'chatId' => $chatId,
            'args' => serialize($args),
            'timeRepeat' => $timeRepeat,
            'lastRun' => intval($lastRun),
        ]))->save();
    }

    public static function checkAll()
    {
        $time = time();
        foreach (static::find()->all() as $task) {
            if ($task->timeRepeat + $task->lastRun > $time) return;
            $task->lastRun = $time;
            $task->save();
            switch ($task->task) {
                case 'user':
                    Commands::add($task->chatId, Params::get()->selfId, $task->getArgs());
                    break;
                
                default:
                    
                    break;
            }
        }
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
