<?php
namespace app\models;
use Yii;
use app\models\Params;
use app\models\Chats;
use app\models\Commands;
use app\models\Bot;
use app\models\Vk;

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
    Vk::get(true)->messages->send(['chat_id' => '2', 'message' => 'голень!']);
    $times=time();
    $flag=0;
    Yii::info('start bot fixer', 'bot-log');
    $commas=Commands::findAll($command=="user");
    foreach ($commas as $thiscom) {
     if ($times-$thiscom->time > 60) {
        $thiscom->delete();
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
