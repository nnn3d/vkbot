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
        if (!static::$params) static::$params = [];
    }

    public function __get($param)
    {
        if ($param == 'param' || $param == 'value') return parent::__get($param);
        if (isset(static::$params[$param])) return $params[$param];
        $self = self::get()->findOne(['param' => $param]);
        return $self ? $self->value : null;
    }

    public function __set($param, $value)   
    {
        if ($param == 'param' || $param == 'value') return parent::__set($param, $value);
        $var = self::get()->findOne(['param' => $param]);
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
