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
    public function fix()
    {
    $times=time();
    $commas=Commands::findAll();
    foreach ($commas as $command) {
     if ($times-$command->time > 60) {
        Commands::deleteAll($command->id);
        Bot::start();
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
