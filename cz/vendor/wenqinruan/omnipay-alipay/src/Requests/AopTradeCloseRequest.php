<?php

namespace Omnipay\Alipay\Requests;

use Omnipay\Alipay\Responses\AopTradeCloseResponse;
use Omnipay\Common\Exception\InvalidRequestException;
use Omnipay\Common\Message\ResponseInterface;
/**
 * Class AopTradeCancelRequest
 * @package Omnipay\Alipay\Requests
 * @link    https://doc.open.alipay.com/doc2/apiDetail.htm?apiId=1058&docType=4
 */
class AopTradeCloseRequest extends \Omnipay\Alipay\Requests\AbstractAopRequest
{
    protected $method = 'alipay.trade.close';
    /**
     * Send the request with specified data
     *
     * @param  mixed $data The data to send
     *
     * @return ResponseInterface
     * @throws InvalidRequestException
     */
    public function sendData($data)
    {
        $data = parent::sendData($data);
        return $this->response = new \Omnipay\Alipay\Responses\AopTradeCloseResponse($this, $data);
    }
    public function validateParams()
    {
        parent::validateParams();
        $this->validateBizContentOne('out_trade_no', 'trade_no');
    }
}