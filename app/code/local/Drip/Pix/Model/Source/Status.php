<?php

/**
 *
 * @category   Drip
 * @package    Drip_Pix
 * @author     Inovarti <https://www.inovarti.com.br>
 * @copyright 2022 Drip (https://usedrip.com.br/)
 */

class Drip_Pix_Model_Source_Status
{
    /**
     * @return array
     */
    public function getOptions()
    {
        return array(
            'OK'                              => Mage::helper('usedrip')->__('Seu pagamento foi aprovado!'),
            'MORE_INFO'                       => Mage::helper('usedrip')->__('Em Processo'),
            'KO'                              => Mage::helper('usedrip')->__('Seu Pagamento não foi aprovado!'),
            'CLOSED'                          => Mage::helper('usedrip')->__('Seu Pagamento Cancelado/Estornado'),
            
        );
    }

    /**
     * @param $value
     * @return string
     */
    public function getOptionText($value)
    {
        foreach ($this->getOptions() as $code => $text) {
            if ($code == $value) {
                return $text;
            }
        }
       return $value;
    }

}
