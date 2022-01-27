<?php

namespace akhur\sberpay\actions;

use yii\base\Action;
use yii\base\Controller;
use yii\base\InvalidConfigException;
use yii\web\BadRequestHttpException;

/**
 * Class BaseAction
 * @package robokassa
 */
class BaseAction extends Action
{
    /**
     * @var string the controller method that this inline action is associated with
     */
    public $actionMethod;


    public $callback;

    /**
     * @param string $id the ID of this action
     * @param Controller $controller the controller that owns this action
     * @param string $actionMethod the controller method that this inline action is associated with
     * @param array $config name-value pairs that will be used to initialize the object properties
     */
    public function __construct($id, $controller, $actionMethod, $config = [])
    {
        $this->actionMethod = $actionMethod;
        parent::__construct($id, $controller, $config);
    }

    /**
     * @param mixed $orderID
     * @return mixed
     * @throws InvalidConfigException
     */
    protected function callback($orderID)
    {
        if (!is_callable($this->callback)) {
            throw new InvalidConfigException('"' . get_class($this) . '::callback" should be a valid callback.');
        }
        $response = call_user_func($this->callback, $orderID);
        return $response;
    }
    
    /**
     * Runs the action.
     * @throws BadRequestHttpException
     * @throws InvalidConfigException
     */
    public function run()
    {
        if (!isset($_REQUEST['orderId'])) {
            throw new BadRequestHttpException;
        }

        return $this->callback($_REQUEST['orderId']);
    }
}
