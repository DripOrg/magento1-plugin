<?php

/**
 *
 * @category   Drip
 * @package    Drip_Pix
 * @author     Inovarti <https://www.inovarti.com.br>
 * @copyright 2022 Drip (https://usedrip.com.br/)
 */

abstract class Drip_Pix_Model_Method_Abstract extends Mage_Payment_Model_Method_Abstract
{

    protected $_isGateway               = true;
    protected $_canUseForMultishipping  = false;
    protected $_canCapture              = true;
    protected $_canRefund               = true;


    /**
     * Get usedrip payment data
     *
     * @return array
     */
    abstract public function _placeOrder();

    /**
     * Cancel payment abstract method
     *
     * @param Varien_Object $payment
     *
     * @return Mage_Payment_Model_Abstract
     */
    public function cancel(Varien_Object $payment)
    {
        return $this->void($payment);
    }

    /**
     * Void payment abstract method
     *
     * @param Varien_Object $payment
     *
     * @return Mage_Payment_Model_Abstract
     */
    public function void(Varien_Object $payment)
    {
        if (!$this->canVoid($payment)) {
            Mage::throwException(Mage::helper('payment')->__('Void action is not available.'));
        }
        return $this->refund($payment, $payment->getOrder()->getBaseTotalDue());
    }

    /**
     * Refund specified amount for payment
     *
     * @param Varien_Object $payment
     * @param float $amount
     *
     * @return Mage_Payment_Model_Abstract
     */
    public function refund(Varien_Object $payment, $amount)
    {

        if (!$this->canRefund()) {
            Mage::throwException(Mage::helper('payment')->__('Refund action is not available.'));
        }
        /** @var Drip_Pix_Model_Api $api */
        $api = Mage::getModel('usedrip/api');

        $orderId = $payment->getAdditionalInformation('orderId');
        if (!$orderId){
             $checkoutId = $payment->getAdditionalInformation('id');
             $result = $api->query($checkoutId);

             if ($result['orderId'] ===null){
                Mage::throwException(Mage::helper('usedrip')->__('Pedido não pode ser cancelado na Drip (ORDERID)'));
                return $this;
             }

             $payment->setAdditionalInformation($result);
             $payment->setCcStatus($result['status']);
             $payment->save();
        }

        $orderId = $payment->getAdditionalInformation('orderId');

        $result = $api->cancel($orderId);
        /** @var Drip_Pix_Helper_Data $helper */
        $helper = Mage::helper('usedrip');
        $helper->log(print_r($result, 1), 'usedrip_cancel.log');
        //CLOSED

        //consulta order
        $result = $api->queryOrder($orderId);
        if ($result['status'] =='CLOSED' && $result['canceledAmount'] !==null){
            $payment
            ->setTransactionId($result['id'] . '_refunded')
            ->setParentTransactionId($result['id'])
            ->setCcStatus($result['status'])
            ->setIsTransactionClosed(1)
            ->setShouldCloseParentTransaction(1)
            ->setTransactionAdditionalInfo(Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS, $result);
            $payment->save();
        }else{
            Mage::throwException(Mage::helper('usedrip')->__('Pedido não pode ser cancelado na Drip'));
            return $this;
            
        }

        return $this;
    }
}
