<?php

/**
 *
 * @category   Drip
 * @package    Drip_Pix
 * @author     Inovarti <https://www.inovarti.com.br>
 * @copyright 2022 Drip (https://usedrip.com.br/)
 */

class Drip_Pix_Model_Observer
{
    /**
     * verifica os boletos vencidos
     * @return bool
     * @throws Exception
     */
    public function verificaPixVencidos()
    {
        /** @var Drip_Pix_Helper_Data $helper*/
        $helper = Mage::helper('usedrip');

        if (!$helper->isActive()) {
            return false;
        }

        $orderCollection = Mage::getModel('sales/order')->getCollection();
        $orderCollection->addAttributeToFilter('status', array('in' => array('pending','processing')));
        $orderCollection->getSelect()->joinLeft(array('payment_table' => 'sales_flat_order_payment'), "main_table.entity_id = payment_table.parent_id", array("method"), null);
        $orderCollection->addAttributeToFilter('payment_table.method', array('in' => array('usedrip_pix')));

        /** @var Mage_Sales_Model_Order $order */
        foreach ($orderCollection as $order) {
            $dataExpiracao = Mage::app()->getLocale()->date($order->getPayment()->getAdditionalInformation('expiresAt'), Zend_Date::ISO_8601, null, false);
            $dataAtual = Mage::app()->getLocale()->date(time());

            if ($dataAtual->isLater($dataExpiracao)) {

                $payment = $order->getPayment();

                 /** @var Drip_Pix_Model_Api $api */
                $api = Mage::getModel('usedrip/api');

                $orderId = $payment->getAdditionalInformation('orderId');
                if (!$orderId){
                    $checkoutId = $payment->getAdditionalInformation('id');
                    $result = $api->query($checkoutId);

                    if ($result['orderId'] ===null || in_array($result['status'], array('MORE_INFO','KO')) ){
                        $order->cancel();
                        $order->save();
                        continue;
                    }
                    $payment->setAdditionalInformation($result);
                    $payment->setCcStatus($result['status']);
                    $payment->save();
                }

                $orderId = $payment->getAdditionalInformation('orderId');
                
                //consulta order
                $result = $api->queryOrder($orderId);
                if ($result['status'] =='CLOSED' && $result['canceledAmount'] !==null){
                    $order->cancel();
                    $order->save();
                    continue;
                }else{
                    if ($result['status'] =='ACTIVE' && $result['canceledAmount'] ===null){
                        if ($order->canInvoice()) {
                            /** @var Mage_Sales_Model_Order_Invoice $invoice */
                            $invoice = Mage::getModel('sales/service_order', $order)
                                ->prepareInvoice()
                                ->setRequestedCaptureCase(Mage_Sales_Model_Order_Invoice::CAPTURE_ONLINE)
                                ->register()
                                ->pay();

                            $invoice->setEmailSent(true);
                            $invoice->getOrder()->setIsInProcess(true);

                            Mage::getModel('core/resource_transaction')
                                ->addObject($invoice)
                                ->addObject($invoice->getOrder())
                                ->save();
                        }
                        continue;
                    }
                    
                }

            } 
        }
    }
}
