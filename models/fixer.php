<?php
namespace app\models;

use Yii;
use app\models\Params;
use app\models\Chats;
use app\models\Bot;
app\models\Commands;

$times=time();
$commas=Commands::findAll();
foreach ($commas as $command) {
    if ($command->time-$times > 60) {
    Commands::deleteAll($command->id);
    Bot::start();
    }
}
?>
