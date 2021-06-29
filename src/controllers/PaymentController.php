<?php

namespace akhur\alfapay\controllers;

use akhur\sberpay\models\SberpayInvoice;
use yii\web\Controller;
use yii\web\NotFoundHttpException;

class PaymentController extends \yii\web\Controller
{
    /**
     * @inheritdoc
     */
    public function actions()
    {
        return [
            'result-payment' => [
                'class' => '\akhur\alfapay\actions\BaseAction',
                'callback' => [$this, 'resultCallback'],
            ],
            'error-payment' => [
                'class' => '\akhur\alfapay\actions\BaseAction',
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

        $merchant = \Yii::$app->get('alfapay');
        $result = $merchant->checkStatus($orderId);
        //Проверяем статус оплаты если всё хорошо обновим инвойс и редерекним
        if (isset($result['OrderStatus']) && ($result['OrderStatus'] == $merchant->successStatus)) {
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
