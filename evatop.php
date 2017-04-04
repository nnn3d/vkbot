function send($id , $message)
{
    $url = 'https://api.vk.com/method/messages.send';
    $params = array(
        'peer_id' => '2000000002',    // Кому отправляем
        'message' => 'PINGUSIKI',   // Что отправляем
        'access_token' => '88ef9a3da5dbd0a4f9f1b64b727c6a8302a5066f0cabf825a02f3bc34568126aee070f02500d2548bc760',  // access_token можно вбить хардкодом, если работа будет идти из под одного юзера
        'v' => '5.37',
    );

    // В $result вернется id отправленного сообщения
    $result = file_get_contents($url, false, stream_context_create(array(
        'http' => array(
            'method'  => 'POST',
            'header'  => 'Content-type: application/x-www-form-urlencoded',
            'content' => http_build_query($params)
        )
    )));
}