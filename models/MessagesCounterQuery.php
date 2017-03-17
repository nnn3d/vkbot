<?php

namespace app\models;

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
