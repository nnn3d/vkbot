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
    private $params;

    public function __get($param)
    {
        if ($param == 'param' || $param == 'value') return parent::__get($param);
        if (isset($params[$param])) return $params[$param];
        return self::get()->findOne(['param' => $param])->value;
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
