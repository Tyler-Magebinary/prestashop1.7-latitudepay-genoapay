<?php
/**
 * Class OrderHelper
 *  @author    Latitude Finance
 *  @copyright Latitude Finance
 *  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
 */

class OrderHelper
{
    public static function getTotalRefundedAmount($orderId)
    {
        $result = 0;
        $rTrans = LatitudeRefundTransaction::fromOrderId((int) $orderId);
        if (!$rTrans) {
            return $result;
        }
        foreach ($rTrans as $tran) {
            $result += (float) $tran['refund_amount'];
        }
        return $result;
    }

    public function createRefundTransaction($data)
    {
        $rTransaction = new LatitudeRefundTransaction();
        foreach ($data as $field => $value) {
            $rTransaction->set($field, $value);
        }
        if ($rTransaction->save()) {
            return $rTransaction;
        }
        return false;
    }
}
