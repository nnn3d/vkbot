<?php
/*
Обновление токена:
Токен будет передан в GET запрос в ссылке, доступной после авторизации:
https://oauth.vk.com/authorize?client_id=5711037&scope=offline,messages&redirect_uri=https://oauth.vk.com/blank.html&display=page&v=5.45&response_type=token

Здесь:
client_id = id приложения;
scope = права доступа (offline - доступ в любое время);
redirect_uri = ссылка перенаправления;
display = способ отображения авторизации;
v = версия;
response_type = тип полученной информации.
*/

header("Content-Type: text/html; charset=utf-8");

class Token {
    public static function get()
    {
        return file_get_contents('token.txt');
    }

    public static function set($token)
    {
        file_put_contents('token.txt', $token);
    }
}

class Request {
    protected $link = 'https://api.vk.com/method/';
    protected $method;
    protected $params;
    protected $receive = null;
    protected $response = null;
    protected $pagination = 0;
    public $item = null;
    public $result = null;

    public function __construct($method, $params = [])
    {
        // defaults
        $this->params = array_merge([
            'v' => '5.60',
            'access_token' => Token::get(),
        ], $params);
        $this->method = $method;
    }

    public function __invoke($method, $params = [])
    {
        return new self($method, $params);
    }

    public function get($params = [])
    {
        $link = $this->link . $this->method . '?';
        $params = array_merge($this->params, $params);
        foreach ($params as $param => $value) {
            $link .= "{$param}={$value}&";
        }
        $link = substr($link,0,-1);
        $res = file_get_contents($link);
        $this->receive = json_decode($res, true);
        if (isset($this->receive['error'])) {
            if ($this->receive['error']['error_code'] == 6) {
                sleep(0.2);
                return $this->get($params);
            } else {
                $this->error($this->receive['error']);
            }
        }
        if (isset($this->receive['response'])) {
            $this->result = $this->receive['response'];
            $this->response = $this->receive['response'];
        }
        return $this;
    } 

    public function resultTo($keys)
    {
        $result = $this->response;
        foreach ($keys as $key) {
            if (!isset($result[$key])) {
                $this->error([
                    'error_code' => '-1', 
                    'error_msg' => 'в result нет ключа ' . $key,
                    'request_params' => $keys,
                ]);
                return $this;
            }
            $result = $result[$key];
        }
        $this->result = $result;
        return $this;
    }

    public function paginationStart()
    {
        $this->pagination = 0;
    }

    public function paginationNext()
    {
        $i = $this->pagination;
        if (!isset($this->result[$i])) {
            $this->warning([
                    'error_code' => '-2', 
                    'error_msg' => 'в result нет ключа ' . $i,
                ]);
            return false;
        }
        $this->item = $this->result[$i];
        $this->pagination++;
        return true;
    }

    public function error($error)
    {
        echo '<script> console.error("' . $error['error_msg'] . '"); </script>';
    }

    public function warning($warn)
    {
        echo '<script> console.log("' . $error['error_msg'] . '"); </script>';
    }
}

class Dialog extends Request {
    protected $peer_id = null;
    protected $chat_id = null;
    protected $dialog = null;
    public $users = null;
    public $statistic = null;

    public function __construct($peer_id)
    {
        parent::__construct('messages.getHistory', [
            'offset' => 0, 
            'count' => 200,
            'peer_id' => $peer_id,
        ]);
        $this->peer_id = $peer_id;
        $prefix = '20000000';
        if (strpos($peer_id, $prefix) >= 0) {
            $this->chat_id = substr($peer_id, strlen($prefix));
            $this->dialog = new Request('messages.getChatUsers', [
                'chat_id' => $this->chat_id,
                'fields' => 'photo_50',
            ]);
        }
    }

    public function getCount($num)
    {
        $offset = 0;
        $count = 200;
        $items = [];
        for ($offset = 0; $offset < $num; $offset += $count) { 
            if ($offset + $count > $num) $count = $num - $offset;
            $loadItems = (new self($this->peer_id))->get(['offset' => $offset, 'count' => $count])->result['items'];
            $items = array_merge($items, $loadItems);
        }
        $this->response['items'] = $items;
        return $this;
    }

    public function getUsers()
    {
        if ($this->dialog !== null)
            $this->users = $this->dialog->get()->result;
        else $this->users = [];
    }

    public function getUser($id)
    {
        if ($this->users === null) $this->getUsers();
        foreach ($this->users as $user) {
            if ($user['id'] == $id) return $user; 
        }
        return [];
    }

    public function getStatistic($onlyUsers = false)
    {
        $st = [];
        foreach ($this->response['items'] as $msg) {
            if ($onlyUsers && !$this->getUser($msg['user_id'])) continue;
            if ( !isset($st[ $msg['user_id'] ]) ) $st[ $msg['user_id'] ] = 0;
            $st[ $msg['user_id'] ]++;
        }
        arsort($st);
        $this->statistic = $st;
        return $this;
    }

    public function paginationStiticticStart($onlyUsers = false)
    {
        if ($this->statistic === null) $this->getStatistic();
        $st = [];
        foreach ($this->statistic as $user => $info) {
            array_push($st, ['user' => $user, 'info' => $info]);
        }
        $this->result = $st;
        $this->paginationStart();
    }
}

class Chats extends Request {
    public function __construct()
    {
        parent::__construct('messages.getDialogs', [
            'count' => 50,
        ]);
    }

    public function get($params = [])
    {
        parent::get($params);
        if ($this->response !== null) {
            $items = [];
            foreach ($this->response['items'] as $item) {
                if (isset($item['message']['chat_id'])) {
                    $item['message']['chat_id'] = '20000000' . $item['message']['chat_id'];
                    $items[] = $item;
                }
            }
            $this->response['items'] = $items;
            $this->result = $this->response;
        }
        return $this;
    }

    public function paginationStart()
    {
        $this->result = $this->response['items'];
        parent::paginationStart();
    }
}

?>

<!DOCTYPE html>
<html>
<head>
    <title>VK</title>
    <meta charset="utf-8">
</head>
<body>
<?php 
    $chats = new Chats;
    $chats->get()->resultTo(['items'])->paginationStart();
?>
<form method="GET">
    <p>
    <h4>Чат:</h4>
    <?php while($chats->paginationNext()): ?>
        <?php $chat = $chats->item; ?>
        <div>
            <label for="chat<?= $chat['message']['chat_id'] ?>">
                <img src="<?= $chat['message']['photo_50'] ?>">
                <input id="chat<?= $chat['message']['chat_id'] ?>" type="radio" name="chat" value="<?= $chat['message']['chat_id'] ?>" <?= $chat['message']['chat_id'] == $_GET['chat'] ? 'checked' : ''?>>
                <?= $chat['message']['title'] . ' (' . $chat['in_read'] . ' сообщений всего)' ?>
            </label>
        </div>
    <?php endwhile; ?>
    </p>
    <p>
        <h4>Загрузить сообщений:</h4>
        <input type="text" name="count" autofocus="on" autocomplete="off" value="<?= (int)$_GET['count'] ?>">
    </p>
    <input type="submit">
</form>
<?php if($_GET['count'] > 1 && isset($_GET['chat'])): ?>
    <?php
        $msg = new Dialog($_GET['chat']);
        $msg->getCount((int)$_GET['count'])->paginationStiticticStart(true);
    ?>
    <div id="results">
        <?php while($msg->paginationNext()): ?>
            <p>
                <?php $user = $msg->getUser($msg->item['user']); ?>
                <img src="<?= $user['photo_50'] ?>">
                <?php echo $user['first_name'], ' ', $user['last_name'], ' - ', $msg->item['info'], ' сообщений'; ?>
            </p>
        <?php endwhile; ?>
    </div>
<?php endif; ?>
</body>
</html>

<?php
// $access_token = Token::get();
// $peers = "2000000021";
// $offset = "0";
// $link = "https://api.vk.com/method/messages.getHistory?count=200&offset=".$offset."&v=5.60&access_token=".$access_token."&peer_id=".$peers;
// $data = file_get_contents($link);
// $data = Request::getResult('messages.getHistory', [
//     'offset' => 0, 
//     'peer_id' => $peers,
//     'count' => 200,
// ]);
// // echo $data;
// if($msgJSONGet = json_decode($data)) {
// $errs = $msgJSONGet->error_code;
// if(empty($errs)){
// 	$msgJSONGet = $msgJSONGet->response;
// 	$countmsg = $msgJSONGet->count;
// 	$count_episode_msg = $countmsg / 200;
//     $count_episode_msg = ceil($count_episode_msg);
// 	$count_episode_msg = 5;
// 	$i = 1;

// 	do {
//         if($i >= 2) {
//         	$offset = $i * 200;
//         	$link = "https://api.vk.com/method/messages.getHistory?count=200&offset=".$offset."&peer_id=".$peers."&v=5.60&access_token=".$access_token;
//         	$data = file_get_contents($link);
//         	$msgJSONGet = json_decode($data);
//         	if(!empty($msgJSONGet->error_code)) die("try again later, pidor");
//         	$msgJSONGet = $msgJSONGet->response;
//         }

//         $msgs_data = $msgJSONGet->items;
//         $msgs_data = json_encode($msgs_data);
//         $msgs_data = substr($msgs_data, 1, -1);
//         if($i != $count_episode_msg) $b_msgs_data = $b_msgs_data.",".$msgs_data;
//         if($i == "1") $b_msgs_data = $msgs_data;

//         $i++;
//     } while($i <= $count_episode_msg);

//     function plur($d) {
//     	$d = abs($d) % 100;
//     	$dd = $d % 10;
//     	if ($d > 10 && $d < 20) return "сообщений";
//     	if ($dd > 1 && $dd < 5) return "сообщения";
//     	if ($dd == 1) return "сообщение";
//     	return "сообщений";
//     }

//     $tpl = file_get_contents("index.tpl");
//     $tpl = str_replace("[count-title]", $countmsg, $tpl); // в index.tpl [count-title] означает количество сообщений
//     $tpl = str_replace("[plural-end]", plur($countmsg), $tpl); // в index.tpl [plural-end] означает окончание числителя
//     $tpl = str_replace("[js-var]", '<script type="text/javascript">var arr = ['.$b_msgs_data.'];</script>', $tpl);
//     echo $tpl;
// }
// }

?>