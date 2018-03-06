<?php

namespace ApiBundle\Api\Resource\OrderInfo;

use ApiBundle\Api\Resource\Coupon\CouponFilter;
use ApiBundle\Api\Resource\Filter;

class OrderInfoFilter extends Filter
{
    public function filter(&$data)
    {
        $data['availableCoupons'] = array_values($data['availableCoupons']);
        $couponFilter = new CouponFilter();
        $couponFilter->filters($data['availableCoupons']);

        $orderInfo = array(
            'targetId' => $data['targetId'],
            'targetType' => $data['targetType'],
            'totalPrice' => strval($data['totalPrice']),
            'title' => $data['title'],
            'account' => empty($data['account']) ? new \stdClass() : $data['account'],
            'hasPayPassword' => empty($data['hasPayPassword']) ? 0 : 1,
            'verifiedMobile' => empty($data['verifiedMobile']) ? '' : $data['verifiedMobile'],
            'coinName' => $data['coinName'],
            'cashRate' => empty($data['cashRate']) ? 0 : $data['cashRate'],
            'priceType' => empty($data['priceType']) ? 'RMB' : $data['priceType'],
            'coinPayAmount' => empty($data['coinPayAmount']) ? 0 : strval($data['coinPayAmount']),
            'maxCoin' => empty($data['maxCoin']) ? 0 : strval($data['maxCoin']),
            'availableCoupons' => $data['availableCoupons'],
            'unitType' => isset($data['unitType']) ? $data['unitType'] : '',
            'duration' => isset($data['duration']) ? $data['duration'] : '',
            'buyType' => isset($data['buyType']) ? $data['buyType'] : '',
            'fullCoinPayable' => $this->fullCoinPayable($data)
        );

        $data = $orderInfo;
    }

    private function fullCoinPayable($data)
    {
        if (empty($data['priceType'])) {
            return 0;
        }

        if ($data['priceType'] == 'Coin') {
            return 1;
        }

        if (!empty($data['coinPayAmount']) && $data['cashRate'] != 0 && $data['maxRate'] == 100) {
            return 1;
        }

        return 0;
    }
}