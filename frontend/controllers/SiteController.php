<?php
namespace frontend\controllers;

use common\models\Categories;
use common\models\Products;
use Yii;
use common\models\LoginForm;
use frontend\models\PasswordResetRequestForm;
use frontend\models\ResetPasswordForm;
use frontend\models\SignupForm;
use frontend\models\ContactForm;
use yii\base\InvalidParamException;
use yii\data\ActiveDataProvider;
use yii\web\BadRequestHttpException;
use yii\web\Controller;
use yii\filters\VerbFilter;
use yii\filters\AccessControl;

/**
 * Site controller
 */
class SiteController extends Controller
{
    private $_categories;
    /**
     * @inheritdoc
     */
    public function behaviors()
    {
        return [
            'access' => [
                'class' => AccessControl::className(),
                'only' => ['logout', 'signup'],
                'rules' => [
                    [
                        'actions' => ['signup'],
                        'allow' => true,
                        'roles' => ['?'],
                    ],
                    [
                        'actions' => ['logout'],
                        'allow' => true,
                        'roles' => ['@'],
                    ],
                ],
            ],
            'verbs' => [
                'class' => VerbFilter::className(),
                'actions' => [
                    'logout' => ['post'],
                ],
            ],
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
     * @return mixed
     */
    public function actionIndex($id = 0)
    {
        $categoryDataProvider = new ActiveDataProvider([
            'query' => Categories::find()->where(['parent_id' => $id]),

        ]);

        return $this->render('index', [
            'breadcrumbs' => $this->_getBreadcrumbs($id),       // Хлебные крошки
            'categoryDataProvider' => $categoryDataProvider,    // Категории
            'productDataProvider' =>  $this->_getProducts($id), // Продукты
        ]);
    }

    /**
     * Logs in a user.
     *
     * @return mixed
     */
    public function actionLogin()
    {
        if (!\Yii::$app->user->isGuest) {
            return $this->goHome();
        }

        $model = new LoginForm();
        if ($model->load(Yii::$app->request->post()) && $model->login()) {
            return $this->goBack();
        } else {
            return $this->render('login', [
                'model' => $model,
            ]);
        }
    }

    /**
     * Logs out the current user.
     *
     * @return mixed
     */
    public function actionLogout()
    {
        Yii::$app->user->logout();

        return $this->goHome();
    }

    /**
     * Signs user up.
     *
     * @return mixed
     */
    public function actionSignup()
    {
        $model = new SignupForm();
        if ($model->load(Yii::$app->request->post())) {
            if ($user = $model->signup()) {
                if (Yii::$app->getUser()->login($user)) {
                    return $this->goHome();
                }
            }
        }

        return $this->render('signup', [
            'model' => $model,
        ]);
    }

    /**
     * Requests password reset.
     *
     * @return mixed
     */
    public function actionRequestPasswordReset()
    {
        $model = new PasswordResetRequestForm();
        if ($model->load(Yii::$app->request->post()) && $model->validate()) {
            if ($model->sendEmail()) {
                Yii::$app->session->setFlash('success', 'Check your email for further instructions.');

                return $this->goHome();
            } else {
                Yii::$app->session->setFlash('error', 'Sorry, we are unable to reset password for email provided.');
            }
        }

        return $this->render('requestPasswordResetToken', [
            'model' => $model,
        ]);
    }

    /**
     * Resets password.
     *
     * @param string $token
     * @return mixed
     * @throws BadRequestHttpException
     */
    public function actionResetPassword($token)
    {
        try {
            $model = new ResetPasswordForm($token);
        } catch (InvalidParamException $e) {
            throw new BadRequestHttpException($e->getMessage());
        }

        if ($model->load(Yii::$app->request->post()) && $model->validate() && $model->resetPassword()) {
            Yii::$app->session->setFlash('success', 'New password was saved.');

            return $this->goHome();
        }

        return $this->render('resetPassword', [
            'model' => $model,
        ]);
    }

    /**
     * Get Breadcrumbs
     *
     * @param $id
     * @return array
     */
    private function _getBreadcrumbs($id)
    {
        $breadcrumbs = [];

        if ($id > 0) {
            $this->_categories = Categories::find()->indexBy('id')->asArray()->all();
            $catId = (int)$id;
            while (true) {
                if ($catId === 0) {
                    break;
                }
                array_unshift($breadcrumbs, ['label' => $this->_categories[$catId]['title'], 'url' => $this->_categories[$catId]['id']]);
                $catId = (int)$this->_categories[$catId]['parent_id'];
            }
        }

        return $breadcrumbs;
    }

    /**
     * Get Products
     *
     * @param $id
     * @return ActiveDataProvider
     */
    private function _getProducts($id)
    {
        if ($id > 0) {  // Search all subcategories
            $columnCats = array_column($this->_categories, 'parent_id', 'id');
            $searchCats = $result = [$id];
            while (true) {
                $foundCats = [];
                foreach ($searchCats as $cat) {
                    $cats = array_keys($columnCats, $cat);
                    $foundCats = array_merge($foundCats, $cats);
                }
                if (empty($foundCats)) {
                    break;
                }
                $result = array_merge($result, $foundCats);
                $searchCats = $foundCats;
            }
            return new ActiveDataProvider([
                'query' => Products::find()->where(['category_id' => $result, 'status' => 1]),
            ]);
        } else {
            return new ActiveDataProvider([
                'query' => Products::find()->where(['status' => 1]),
            ]);
        }
    }
}
