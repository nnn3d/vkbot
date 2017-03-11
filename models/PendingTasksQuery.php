<?php

namespace app\models;

/**
 * This is the ActiveQuery class for [[PendingTasks]].
 *
 * @see PendingTasks
 */
class PendingTasksQuery extends \yii\db\ActiveQuery
{
    /*public function active()
    {
        return $this->andWhere('[[status]]=1');
    }*/

    /**
     * @inheritdoc
     * @return PendingTasks[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return PendingTasks|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
