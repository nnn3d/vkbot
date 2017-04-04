<?php

namespace app\models;

use Yii;


/**
 * This is the model class for table "params".
 *
 * @property string $param
 * @property string $value
 */
class Params extends \yii\db\ActiveRecord
{
    public static $params;

    public static function bot($params)
    {
        if (!is_array($params)) $params = [$params];
        $botParam = Yii::$app->params['vkBot'];
        foreach ($params as $param) {
            if (isset($botParam[$param])) {
                $botParam = $botParam[$param];
            } else {
                return false;
            }
        }
        return $botParam;
    }

    public function __construct()
    {
        if (!is_array(static::$params)) static::$params = [];
    }

    public function __get($param)
    {
        if ($param == 'param' || $param == 'value') return parent::__get($param);
        if (isset(static::$params[$param]) && $param != 'run' && $param != 'stop') return $params[$param];
        $self = static::findOne(['param' => $param]);
        return $self ? $self->value : null;
    }

    public function __set($param, $value)   
    {
        if ($param == 'param' || $param == 'value') return parent::__set($param, $value);
        $var = static::findOne(['param' => $param]);
        if (!$var) $var = new self;
        $var->param = strval($param);
        $var->value = strval($value);
        $var->save();
        $params[$param] = $value;
    }

    public static function get()
    {
        return new self;
    }


    /**
     * @inheritdoc
     */
    public static function tableName()
    {
        return 'params';
    }


    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['param', 'value'], 'required'],
            [['param', 'value'], 'string', 'max' => 255],
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
        return new ParamsQuery(get_called_class());
    }
}

/**
 * This is the ActiveQuery class for [[Params]].
 *
 * @see Params
 */
class ParamsQuery extends \yii\db\ActiveQuery
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