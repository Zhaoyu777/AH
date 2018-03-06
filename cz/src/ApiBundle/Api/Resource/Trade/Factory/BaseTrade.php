<?php

namespace ApiBundle\Api\Resource\Trade\Factory;

use AppBundle\Common\MathToolkit;
use Biz\OrderFacade\Service\OrderFacadeService;
use Biz\System\Service\SettingService;
use Biz\User\CurrentUser;
use Codeages\Biz\Framework\Context\Biz;
use Codeages\Biz\Order\Service\OrderService;
use Codeages\Biz\Pay\Service\PayService;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\Router;

abstract class BaseTrade
{
    /**
     * @var Router
     */
    protected $router;

    /**
     * @var Biz
     */
    protected $biz;

    protected $payment = '';

    protected $platformType = '';

    public function setRouter(Router $router)
    {
        $this->router = $router;
    }

    public function setBiz($biz)
    {
        $this->biz = $biz;
    }

    protected function generateUrl($route, $parameters = array(), $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH)
    {
        return $this->router->generate($route, $parameters, $referenceType);
    }

    public function create($params)
    {
        $tradeFields = array(
            'type' => $params['type'],
            'goods_detail' => '',
            'price_type' => 'money',
            'user_id' => $params['userId'],
            'create_ip' => $params['clientIp'],
            'attach' => array(
                'user_id' => $params['userId'],
            ),
            'platform' => $this->payment,
            'platform_type' => $this->platformType,
            'app_pay' => isset($params['app_pay']) ? $params['app_pay'] : '',
            'notify_url' => $this->generateUrl('cashier_pay_notify', array('payment' => $this->payment), true),
            'return_url' => isset($params['return_url']) ? $params['return_url'] : $this->generateUrl('cashier_pay_return', array('payment' => $this->payment), true),
            'show_url' => isset($params['show_url']) ? $params['show_url'] : '',
        );

        if ($params['type'] == 'purchase' && !empty($params['orderSn'])) {
            $order = $this->getOrderService()->getOrderBySn($params['orderSn']);
            $tradeFields['amount'] = $order['pay_amount'];
            $tradeFields['order_sn'] = $order['sn'];
            $coinAmount = empty($params['coinAmount']) ? 0 : $params['coinAmount'];
            $tradeFields['coin_amount'] = MathToolkit::simple($coinAmount, 100);
            $cashAmount = $this->getOrderFacadeService()->getTradePayCashAmount($order, $coinAmount);
            $tradeFields['cash_amount'] = MathToolkit::simple($cashAmount, 100);
            $tradeFields['goods_title'] = $order['title'];
        }

        if ($params['type'] == 'recharge') {
            $tradeFields['goods_title'] = '现金充值';
            $tradeFields['order_sn'] = '';
            $tradeFields['amount'] = MathToolkit::simple($params['amount'], 100);
            $tradeFields['cash_amount'] = MathToolkit::simple($params['amount'], 100);
        }

        $tradeFields = array_merge($tradeFields, $this->getCustomFields($params));
        $trade = $this->getPayService()->createTrade($tradeFields);

        return $trade;
    }

    public function getCustomFields($params)
    {
        return array();
    }

    public function getCustomResponse($trade)
    {
        return array();
    }

    public function createResponse($trade)
    {
        $defaultResponse = array(
            'tradeSn' => $trade['trade_sn'],
            'status' => $trade['status'],
            'payUrl' => $this->generateUrl('cashier_redirect', array('tradeSn' => $trade['trade_sn'])),
        );

        if ($trade['status'] == 'paid') {
            $defaultResponse['paidSuccessUrl'] = $this->generateUrl('cashier_pay_success', array('trade_sn' => $trade['trade_sn']));

            return $defaultResponse;
        } else {
            return array_merge($defaultResponse, $this->getCustomResponse($trade));
        }
    }

    /**
     * @return SettingService
     */
    protected function getSettingService()
    {
        return $this->biz->service('System:SettingService');
    }

    /**
     * @return CurrentUser
     */
    protected function getUser()
    {
        return $this->biz['user'];
    }

    /**
     * @return PayService
     */
    protected function getPayService()
    {
        return $this->biz->service('Pay:PayService');
    }

    /**
     * @return OrderService
     */
    protected function getOrderService()
    {
        return $this->biz->service('Order:OrderService');
    }

    /**
     * @return OrderFacadeService
     */
    protected function getOrderFacadeService()
    {
        return $this->biz->service('OrderFacade:OrderFacadeService');
    }
}
