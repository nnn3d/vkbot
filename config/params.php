<?php
require_once(__DIR__ . '/constants.php');
$botName = YII_TEST ? 'тест' : 'Мия'; // основное имя. в помощи и подсказках будет использоваться это имя.
return [
    'adminEmail' => 'admin@example.com',
    'vkBot' => [
    	'vkConfig' => [
    		// 'client_id' => '5912357',
    		// 'scope' => '69634',
    		// 'secret_key' => 'z10qtaeMP0UoNGBobIxz',
    		'v' => '5.60',
			'access_token' => 'fdec60ac1f13e4e2454d8d39883f284ed4dc18e47ef3e37747bbd09d0387e1212b9c0429cfa54fb35f533',
    	],
        'statusLabels' => [
            USER_STATUS_ADMIN => 'админ',
            USER_STATUS_MODER => 'модер',
            USER_STATUS_UNTOUCHABLE => 'неприкасаемый',
            USER_STATUS_DEFAULT => 'юзер',
        ],
        'statusMap' => [
            'админ' => USER_STATUS_ADMIN,
            'модер' => USER_STATUS_MODER,
            'неприкасаемый' => USER_STATUS_UNTOUCHABLE,
            'юзер' => USER_STATUS_DEFAULT,
        ],
    	'bdVersion' => 1,
        'name' => $botName,
    ],
];
