Библиотека для приема платежей через интернет для Сбербанк.
===========================================================
Библиотека для приема платежей через интернет для Сбербанк.

Установка с помощью Composer
------------


```
php composer.phar require akhur/yii2-sberpay "*"
```

или добавьте в composer.json

```
"akhur/yii2-sberpay": "*"
```

Подключение компонента
-----

```php
[
    'components' => [
        'sberpay' => [
            'class' => 'akhur\sberpay\Merchant',
            'sessionTimeoutSecs' => 60 * 60 * 24 * 7,
            'merchantLogin' => '',
            'merchantPassword' => '',
            'orderModel' => '', //модель таблицы заказов
            'isTest' => false,
            'registerPreAuth' => false,
            'returnUrl' => '/payment/result-payment',
            'failUrl' => '/payment/error-payment',
        ],
        //..
    ],
];
```

Пример работы библиотеки
-----

```
class PaymentController extends \yii\web\Controller
{
    /**
     * @inheritdoc
     */
    public function actions()
    {
        return [
            'result-payment' => [
                'class' => '\akhur\sberpay\actions\BaseAction',
                'callback' => [$this, 'resultCallback'],
            ],
            'error-payment' => [
                'class' => '\akhur\sberpay\actions\BaseAction',
                'callback' => [$this, 'failCallback'],
            ],
        ];
    }

    public function resultCallback($orderId)
    {
        /* @var $model SberpayInvoice */
        $model = SberpayInvoice::findOne(['orderId' => $orderId]);
        if (is_null($model)) {
            throw new NotFoundHttpException();
        }

        $merchant = \Yii::$app->get('sberpay');
        $result = $merchant->checkStatus($orderId);
        //Проверяем статус оплаты если всё хорошо обновим инвойс и редерекним
        if (isset($result['OrderStatus']) && ($result['OrderStatus'] != $merchant->successStatus)) {
            //обработка при успешной оплате $model->related_id номер заказа
            echo 'ok';
        } else {
            $this->redirect($merchant->failUrl.'?orderId=' . $orderId);
        }
    }
    
    public function failCallback($orderId)
    {
        /* @var $model SberpayInvoice */
        $model = SberpayInvoice::findOne(['orderId' => $orderId]);
        if (is_null($model)) {
            throw new NotFoundHttpException();
        }
        //вывод страницы ошибки $model->related_id номер заказа

        echo 'error payment';
    }
}
```

