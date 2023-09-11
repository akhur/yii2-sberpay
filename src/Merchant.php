<?php

namespace akhur\sberpay;

use akhur\sberpay\models\SberpayInvoice;
use Yii;
use yii\base\BaseObject;
use yii\base\ErrorException;
use yii\base\InvalidConfigException;
use yii\helpers\Json;
use yii\helpers\Url;
use yii\web\Response;
use yii\helpers\StringHelper;

class Merchant extends BaseObject
{
    public $merchantLogin;

    public $merchantPassword;

    /**
     * @var bool Если true будет использован тестовый сервер
     */
    public $isTest = false;

    /**
     * @var bool Использовать или нет двухстадийную оплату.
     * По умолчанию - нет.
     */
    public $registerPreAuth = false;

    /**
     * @var string Url адрес страницы для возврата с платежного шлюза
     * необходимо указывать без host
     */
    public $returnUrl = '/sberbank/result';


    /**
     * @var string Url адрес страницы ошибки оплаты
     * необходимо указывать без host
     */
    public $failUrl = '/sberbank/fail';

    /**
     * @var string Адрес платежного шлюза
     */
    public $url = 'https://securepayments.sberbank.ru/payment/rest/';

    /**
     * @var string Тестовый адрес платежного шлюза
     */
    public $urlTest = 'https://3dsec.sberbank.ru/payment/rest/';

    /* @var int Время жизни заказа в секундах */
    public $sessionTimeoutSecs = 1200;

    const STATUS_SUCCESS = 2;

    public $orderModel;

    /**
     * @var string Суффикс в номере заказа, чтобы не было дублей, если не будет то будет браться название orderModel
     */
    public $suffix;

    /**
     * Создание оплаты редеректим в шлюз сберабнка
     * @param $orderID - id заказа
     * @param $sum - сумма заказа
     * @param null $description - описание заказа
     * @param null $jsonParams - параметры в формате json
     * @return mixed
     */
    public function create($orderID, $sum, $description = null, $jsonParams = null)
    {
        $relatedModel = $this->getRelatedModel();

        $invoice = SberpayInvoice::findOne(['related_id' => $orderID . '-' . $this->getNumberSuffix(), 'related_model' => $relatedModel]);

        if ($invoice) {
            return $invoice->url;
        }

        $data = [
            'orderNumber' => $orderID . '-' . $this->getNumberSuffix(),
            'amount' => $sum * 100,
            'returnUrl' => Url::to($this->returnUrl, true),
            'failUrl' => Url::to($this->failUrl, true),
        ];
        if ($description) {
            $data['description'] = $description;
        }
        if ($jsonParams) {
            $data['jsonParams'] = $jsonParams;
        }

        $response = $this->send('register.do', $data);

        if (array_key_exists('errorCode', $response)) {
            throw new ErrorException($response['errorMessage']);
        }
        $orderId = $response['orderId'];
        $formUrl = $response['formUrl'];

        SberpayInvoice::addSberbank($orderID, $this->getNumberSuffix(), $this->relatedModel, $orderId, $formUrl, $data);

        return $formUrl;
    }

    public function continue($orderID, $sum)
    {
        $data = [
            'orderId' => $orderID,
            'amount' => $sum * 100,
        ];

        $response = $this->send('deposit.do', $data);

        if (array_key_exists('errorCode', $response)) {
            throw new ErrorException($response['errorMessage']);
        }
        $orderId = $response['orderId'];
        $formUrl = $response['formUrl'];

        return Yii::$app->response->redirect($formUrl);
    }

    public function complete($orderId)
    {
        $post = [];
        $post['orderId'] = $orderId;
        return $this->send($this->actionStatus, $post);
    }

    /**
     * Проверка статуса зазаказа
     * @param $orderID - id заказа
     * @return mixed
     */
    public function checkStatus($orderID)
    {
        $data = ['orderId' => $orderID];

        return $this->send('getOrderStatus.do', $data);
    }

    /**
     * Откправка запроса в api сбербанка
     * @param $action string типа запрос
     * @param $data array Параметры которые передаём в запрос
     * @return mixed Ответ сбербанка
     */
    public function send($action, $data)
    {
        $authData = [
            'userName' => $this->merchantLogin,
            'password' => $this->merchantPassword,
        ];
        $data = array_merge($authData,$data);

        $url = ($this->isTest ? $this->urlTest : $this->url) . $action;

        $curl = curl_init();
        curl_setopt($curl, CURLOPT_URL, $url);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_POST, true);
        curl_setopt($curl, CURLOPT_POSTFIELDS, http_build_query($data));
        $out = curl_exec($curl);
        curl_close($curl);

        return Json::decode($out);
    }

    public function getRelatedModel()
    {
        return strtolower(StringHelper::basename($this->orderModel));
    }

    /**
     * @return string
     */
    public function getNumberSuffix()
    {
        return $this->suffix ? $this->suffix : $this->getRelatedModel();
    }

    /**
     * @return int
     */
    public function getSuccessStatus()
    {
        return self::STATUS_SUCCESS;
    }
}
