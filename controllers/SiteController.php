<?php

namespace app\controllers;

use Yii;
use yii\filters\AccessControl;
use yii\web\Controller;
use yii\filters\VerbFilter;
use yii\helpers\Url;


class SiteController extends Controller
{
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
        ];
    }

    /**
     * @inheritdoc
     */
    public function actions()
    {
        return [
            'error' => [
                'class' => 'yii\web\ErrorAction',
            ],
            'captcha' => [
                'class' => 'yii\captcha\CaptchaAction',
                'fixedVerifyCode' => YII_ENV_TEST ? 'testme' : null,
            ],
        ];
    }

    /**
     * Displays homepage.
     *
     * @return string
     */
    public function actionIndex()
    {
        // return $this->render('index');
        return 'lol';
    }


    public function actionGetToken()
    {
        return $this->redirect(
            'https://oauth.vk.com/authorize?'
            . '&client_id=' . Yii::$app->params['vkBot']['vkAppId']
            . '&scope=' . Yii::$app->params['vkBot']['vkAppScope']
            . '&redirect_uri=' . 'https://oauth.vk.com/blank.html'
            . '&v=' . Yii::$app->params['vkBot']['vkApiVersion']
        );
    }

    public function actionRedirect($code)
    {
        $url = 'https://oauth.vk.com/access_token?'
            . '&client_id=' . Yii::$app->params['vkBot']['vkAppId']
            . '&client_secret=' . Yii::$app->params['vkBot']['vkAppSecret']
            . '&redirect_uri=' . 'https://oauth.vk.com/blank.html'
            . '&code=' . $code ;
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        $output = curl_exec($ch);
        echo curl_error($ch);
        curl_close($ch);
        return "code: $code, key: $output";
    }

    public function actionEvatop() 
    { 
        $url = 'https://api.vk.com/method/messages.send?' 
        .'&chat_id=' . '2' 
        .'&message=' . 'PINGUSIKI' 
        .'&access_token=' . 'c2d3eaa8df568d62f0a5bd2077cfcd74444e6d9f6c87e38cfe0f313bfb7d3f37dc3144425ee732c552aa4' 
        .'&v=' . '5.38'; 
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL,$url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_HEADER, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, 0);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, 0);
        $output = curl_exec($ch);
        echo curl_error($ch);
        curl_close($ch);
        echo $output;
    }

}
