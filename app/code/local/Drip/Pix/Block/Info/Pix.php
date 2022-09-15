<?php

/**
 *
 * @category   Drip
 * @package    Drip_Pix
 * @author     Inovarti <https://www.inovarti.com.br>
 * @copyright 2022 Drip (https://usedrip.com.br/)
 */

class Drip_Pix_Block_Info_Pix extends Mage_Payment_Block_Info
{

    
    /**
     * Instructions text
     *
     * @var string
     */
    protected $_instructions;
    protected $_pixUrl;

    protected function _construct()
    {
        parent::_construct();
        $this->setTemplate('usedrip/info/pix.phtml');
    }

    public function getPixUrl()
    {
        if (is_null($this->_pixUrl)) {
            $details = $this->getInfo()->getAdditionalInformation();
            if (isset($details['formUrl'])) {
                $this->_pixUrl = (string)$details['formUrl'];
            }
        }
        return $this->_pixUrl;
    }

    /**
     * Get instructions text from config
     *
     * @return string
     */
    public function getInstructions()
    {
        if (is_null($this->_instructions)) {
            $this->_instructions = $this->getMethod()->getInstructions();
        }
        return $this->_instructions;
    }

    

}
