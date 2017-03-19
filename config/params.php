<?php
$botName = YII_TEST ? 'тест' : 'торч';
return [
    'adminEmail' => 'admin@example.com',
    'vkBot' => [
    	'vkConfig' => [
    		// 'client_id' => '5912357',
    		// 'scope' => '69634',
    		// 'secret_key' => 'z10qtaeMP0UoNGBobIxz',
    		'v' => '5.60',
            'access_token' => 'c2d3eaa8df568d62f0a5bd2077cfcd74444e6d9f6c87e38cfe0f313bfb7d3f37dc3144425ee732c552aa4',
    		// 'access_token' => 'd32d00466e6fe538344a41da14a8d42412c4af78a001a961b4c525562ea8fb3aed32e5513c79d2e699f80',
    	],
    	'bdVersion' => 1,
        'name' => $botName,
    ],
];
