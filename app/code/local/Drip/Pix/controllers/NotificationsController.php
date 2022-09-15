<?php

/**
 *
 * @category   Drip
 * @package    Drip_Pix
 * @author     Inovarti <https://www.inovarti.com.br>
 * @copyright 2022 Drip (https://usedrip.com.br/)
 */

class Drip_Pix_NotificationsController extends Mage_Core_Controller_Front_Action
{
    public function customAction(){

        $helper = Mage::helper('usedrip');
        $helper->log(print_r($this->getRequest()->getParams(), 1), 'usedrip_body_notification.log');

        $checkoutId = $this->getRequest()->getParam('checkoutId');

        if ($checkoutId && $checkoutId !==null) {
            
            try {
                /** @var Drip_Pix_Model_Api $api */
                $api = Mage::getModel('usedrip/api');

                $result = $api->query($checkoutId);
                if ($result && $result['status'] !== null &&  $id = trim($result['merchantCode'])) {
                    /** @var $order Mage_Sales_Model_Order */
                    $order = Mage::getModel('sales/order')->loadByIncrementId($id);
                    if ($order->getId()) {
                        $payment = $order->getPayment();

                        if ($result['id'] != $payment->getAdditionalInformation('id')){
                            $this->getResponse()->setBody('OK');
                            return;
                        }
                        
                        try {
                            $payment->setAdditionalInformation($result);
                            $payment->setCcStatus($result['status']);
                            $payment->save();
                                    
                            switch ($result['status']) {
                                case 'OK':
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
                                    break;
                                case 'KO':
                                    $payment->setIsTransactionClosed(1);
                                    $payment->setShouldCloseParentTransaction(1);
                                    $payment->save();
                                    
                                    if ($order->canCancel()) {
                                        $order->cancel();
                                        $order->save();
                                    }else{
                                        if ($order->canCreditmemo()) {
                                            $service = Mage::getModel('sales/service_order', $order);
                                            $creditmemo = $service->prepareCreditmemo(array());
                                            $creditmemo->setPaymentRefundDisallowed(true)->register();
                                            Mage::getModel('core/resource_transaction')
                                                ->addObject($creditmemo)
                                                ->addObject($creditmemo->getOrder())
                                                ->save();
                                        }
                                    }
                                    break;
                                case 'MORE_INFO':
                                    if (in_array($order->getState(), array('complete'))) break;

                                    $dataExpiracao = Mage::app()->getLocale()->date($order->getPayment()->getAdditionalInformation('expiresAt'), Zend_Date::ISO_8601, null, false);
                                    $dataAtual = Mage::app()->getLocale()->date(time());
                                    if ($dataExpiracao->isLater($dataAtual)) break;

                                    $payment->setIsTransactionClosed(1);
                                    $payment->setShouldCloseParentTransaction(1);
                                    $payment->save();
                                    
                                    if ($order->canCancel()) {
                                        $order->cancel();
                                        $order->save();
                                    }else{
                                        if ($order->canCreditmemo()) {
                                            $service = Mage::getModel('sales/service_order', $order);
                                            $creditmemo = $service->prepareCreditmemo(array());
                                            $creditmemo->setPaymentRefundDisallowed(true)->register();
                                            Mage::getModel('core/resource_transaction')
                                                ->addObject($creditmemo)
                                                ->addObject($creditmemo->getOrder())
                                                ->save();
                                        }
                                    }
                                    break;
                            }
                            
                            $this->getResponse()->setRedirect('/sales/order/view/order_id/'.$order->getId());
                            return;
                        } catch (Exception $e) {
                            $this->getResponse()
                                ->setHttpResponseCode(500)
                                ->setBody($e->getMessage());
                            return;
                        }
                    }else{
                        $this->getResponse()->setRedirect('/customer/account');
                        return;
                    }
                    
                }
            } catch (Exception $e) {

                $this->getResponse()
                    ->setHttpResponseCode(500)
                    ->setBody($e->getMessage());
                return;
            }
        }
        if ($checkoutId !==null){
            $this->getResponse()->setRedirect('/');
            return;
        }

        $this->getResponse()
            ->setHttpResponseCode(404)
            ->setBody('NOT FOUND');
    }
}
