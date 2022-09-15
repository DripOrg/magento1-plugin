<?php

/**
 *
 * @category   Drip
 * @package    Drip_Pix
 * @author     Inovarti <https://www.inovarti.com.br>
 * @copyright 2022 Drip (https://usedrip.com.br/)
 */

class Drip_Pix_Model_Source_Mode
{
    const MODE_SANDBOX          = 1;
    const MODE_PRODUCTION       = 0;

    public function toOptionArray()
    {
        return array(
            array(
                'value' => self::MODE_PRODUCTION,
                'label' => Mage::helper('usedrip')->__('Production')
            ),
            array(
                'value' => self::MODE_SANDBOX,
                'label' => Mage::helper('usedrip')->__('Sandbox')
            ),
        );
    }
}
