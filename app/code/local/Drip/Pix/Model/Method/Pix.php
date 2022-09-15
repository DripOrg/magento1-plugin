<?php

/**
 *
 * @category   Drip
 * @package    Drip_Pix
 * @author     Inovarti <https://www.inovarti.com.br>
 * @copyright 2022 Drip (https://usedrip.com.br/)
 */

class Drip_Pix_Model_Method_Pix extends Drip_Pix_Model_Method_Abstract
{
    /**
     * Payment method code
     *
     * @var string
     */
    protected $_code = 'usedrip_pix';

    /**
     * Bank Transfer payment block paths
     *
     * @var string
     */
    protected $_formBlockType = 'usedrip/form_pix';
    protected $_infoBlockType = 'usedrip/info_pix';

    protected $_canOrder  = true;

    /**
     * Get instructions text from config
     *
     * @return string
     */
    public function getInstructions()
    {
        return trim($this->getConfigData('instructions'));
    }

     /**
     * Order payment abstract method
     *
     * @param Varien_Object $payment
     * @param float $amount
     *
     * @return $this
     */
    public function order(Varien_Object $payment, $amount)
    {
        if (!$this->canOrder()) {
            Mage::throwException(Mage::helper('payment')->__('Order action is not available.'));
        }
        $this->_placeOrder();
        return $this;
    }

    /**
     * Place an order
     *
     * @return $this
     * @throws Mage_Core_Exception
     */
    public function _placeOrder()
    {

        try {

            $payment = $this->getInfoInstance();

            /** @var Mage_Sales_Model_Order $order */
            $order = $payment->getOrder();

            /** @var Drip_Pix_Helper_Data $helper*/
            $helper = Mage::helper('usedrip');
            /** @var Drip_Pix_Helper_Order $helperOrder*/
            $helperOrder = Mage::helper('usedrip/order');
            
            $sale = array();
            $sale += $helperOrder->getRequestData($order);
            $sale += $helperOrder->getCustomerData($order);
            $sale += $helperOrder->getShoppingCart($order);
            
            /** @var Drip_Pix_Model_Api $api */
            $api = Mage::getModel('usedrip/api');
            $result = $api->createOrder($sale);
            if ($result){
                $payment->setAdditionalInformation($result);
                $payment->setTransactionId($result['id']);
                $payment->setCcStatus($result['status']);
                $payment->setTransactionAdditionalInfo(Mage_Sales_Model_Order_Payment_Transaction::RAW_DETAILS, $result);
                $payment->save();
                $order->addStatusHistoryComment(Mage::helper('usedrip')->__('DRIP - Pedido Criado'));
                $order->save();
            }
        } catch (Mage_Core_Exception $e) {
            Mage::throwException(Mage::helper('usedrip')->__($e->getMessage()));
            $helper->log('Mage_Core_Exception: ' . $e->getMessage());
        } catch (Exception $e) {
            Mage::throwException(Mage::helper('usedrip')->__($e->getMessage()));
            $helper->log('connection failed(T): ' . $e->getMessage());
        } 

        return $this;
    }

}
