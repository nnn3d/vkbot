<?php

namespace app\models;

/**
 * This is the ActiveQuery class for [[Chats]].
 *
 * @see Chats
 */
class ChatsQuery extends \yii\db\ActiveQuery
{
    /*public function active()
    {
        return $this->andWhere('[[status]]=1');
    }*/

    /**
     * @inheritdoc
     * @return Chats[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return Chats|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
