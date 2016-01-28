<?php
namespace console\controllers;

use Yii;
use yii\console\Controller;

class RbacController extends Controller
{

    /**
     * Add admin role for user ID=1
     */
    public function actionInit()
    {
        $auth = Yii::$app->authManager;

        $admin = $auth->createRole('admin');
        $auth->add($admin);
        $auth->assign($admin, 1);
    }
}