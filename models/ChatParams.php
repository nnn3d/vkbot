<?php

namespace app\models;

use Yii;


/**
 * This is the model class for table "params".
 *
 * @property string $param
 * @property string $value
 */
class ChatParams extends \yii\db\ActiveRecord
{
    public static $params;

    public function __construct($params = '')
    {
        if (is_array($params)) parent::__construct($params);
        else $this->chatId = intval($params);
        if (!is_array(static::$params)) static::$params = [];
        if (!isset(static::$params[$this->chatId])) static::$params[$this->chatId] = [];
    }

    public function __get($param)
    {
        if ($param == 'param' || $param == 'value' || $param == 'chatId') return parent::__get($param);
        if (isset(static::$params[$this->chatId][$param])) return $params[$this->chatId][$param];
        $self = static::findOne(['chatId' => $this->chatId, 'param' => $param]);
        return $self ? $self->value : null;
    }

    public function __set($param, $value)   
    {
        if ($param == 'param' || $param == 'value' || $param == 'chatId') return parent::__set($param, $value);
        $var = static::findOne(['chatId' => $this->chatId, 'param' => $param]);
        if (!$var) $var = new self($this->chatId);
        $var->param = strval($param);
        $var->value = strval($value);
        $var->save();
        $params[$this->chatId][$param] = $value;
    }

    public static function get($chatId)
    {
        return new self($chatId);
    }


    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'chatParams';
    }


    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['chatId', 'param', 'value'], 'required'],
            [['param'], 'string', 'max' => 255],
            [['value'], 'string'],
            [['chatId'], 'integer'],
        ];
    }

    
    /**
     * @inheritdoc
     */
    public function brak()
    {
        return [
            [['chatId', 'param', 'value'], 'required'],
            [['param'], 'string', 'max' => 255],
            [['value'], 'string'],
            [['chatId'], 'integer'],
        ];
    }

    /**
     * @inheritdoc
     */
    public function attributeLabels()
    {
        return [
            'param' => 'Param',
            'value' => 'Value',
        ];
    }

    /**
     * @inheritdoc
     * @return ParamsQuery the active query used by this AR class.
     */
    public static function find()
    {
        return new ChatParamsQuery(get_called_class());
    }
}

/**
 * This is the ActiveQuery class for [[Params]].
 *
 * @see Params
 */
class ChatParamsQuery extends \yii\db\ActiveQuery
{
    /*public function active()
    {
        return $this->andWhere('[[status]]=1');
    }*/

    /**
     * @inheritdoc
     * @return Params[]|array
     */
    public function all($db = null)
    {
        return parent::all($db);
    }

    /**
     * @inheritdoc
     * @return Params|array|null
     */
    public function one($db = null)
    {
        return parent::one($db);
    }
}
