<?php
namespace app\models;
use Yii;
use app\models\Params;
use app\models\Chats;
use app\models\Commands;
use app\models\Bot;

class Fixer
{
     public static function tableName()
    {
        return 'commands';
    }
    /**
     * @inheritdoc
     */
    public function rules()
    {
        return [
            [['chatId'], 'required'],
            [['chatId', 'userId', 'messageId', 'time'], 'integer'],
            [['args'], 'string'],
            [['command'], 'string', 'max' => 255],
        ];
    }
    public static function fix()
    {
    $times=time();
    $flag=0;
    $commas=Commands::findAll($command=="user");
    foreach ($commas as $command) {
     if ($times-$command->time > 60) {
        $command->delete();
        if ($flag==0){
        Bot::start();
        $flag=1;
        }
      }
     }
    }
     public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'chatId' => 'Chat ID',
            'command' => 'Command',
            'args' => 'Args',
            'timestamp' => 'Timestamp',
        ];
    }
}
?>
