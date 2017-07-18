<?php
namespace app\models;

use Yii;
use app\models\Params;
use app\models\Chats;
use app\models\Bot;
use app\models\Commands;
class fixer extends \yii\db\ActiveRecord
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
     if ($command->time-$times > 60) {
        Commands::deleteAll($command->id);
        Bot::start();
      }
    }
}
}
fix();
?>
