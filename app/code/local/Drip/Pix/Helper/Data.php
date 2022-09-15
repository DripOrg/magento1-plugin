<?php

/**
 *
 * @category   Drip
 * @package    Drip_Pix
 * @author     Inovarti <https://www.inovarti.com.br>
 * @copyright 2022 Drip (https://usedrip.com.br/)
 */

class Drip_Pix_Helper_Data extends Mage_Core_Helper_Abstract
{

    /**
     * Se ativo para criar o log
     *
     * @return mixed
     */
    public function isDebug()
    {
        return Mage::getStoreConfig('payment/usedrip_pix/debug_mode');
    }

    /**
     * Verifica se esta ativo
     *
     * @return mixed
     */
    public function isActive()
    {
        return Mage::getStoreConfig('payment/usedrip_pix/active');
    }

    public function getMode()
    {
        return Mage::getStoreConfig('payment/usedrip_pix/mode');
    }

    public function getApiKey()
    {
        return Mage::getStoreConfig('payment/usedrip_pix/api_key');
    }
    
   
    public function log($message, $file = "usedrip.log")
    {
        if ($this->isDebug()) {
            Mage::log($message, null, $file, true);
        }
    }

   
}
