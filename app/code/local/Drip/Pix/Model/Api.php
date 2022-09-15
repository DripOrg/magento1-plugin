<?php

/**
 *
 * @category   Drip
 * @package    Drip_Pix
 * @author     Inovarti <https://www.inovarti.com.br>
 * @copyright 2022 Drip (https://usedrip.com.br/)
 */

class Drip_Pix_Model_Api extends Varien_Object
{
    const SANDBOX_ENDPOINT = 'https://sbx-drip-be.herokuapp.com/api/v1';
    const PRODUCTION_ENDPOINT = 'https://drip-be.herokuapp.com/api/v1';
    
    /**
     * Created Order 
     *
     * @param array $data
     * @return array
     */
    public function createOrder(array $data)
    {
        $response = $this->request($this->getBaseUrl() . '/checkouts', $data, Zend_Http_Client::POST);
        return $response;
    }
    


    /**
     * Retrieve query info
     *
     * @param string @id
     * @return array
     */
    public function query($id)
    {
        if (!$id) return false;
        $response = $this->request($this->getBaseUrl() . '/checkouts/' . $id, array(), Zend_Http_Client::GET);
        return $response;
    }

    /**
     * Retrieve query order info
     *
     * @param string @id
     * @return array
     */
    public function queryOrder($id)
    {
        if (!$id) return false;
        $response = $this->request($this->getBaseUrl() . '/merchant/orders/'. $id .'/detail', array(), Zend_Http_Client::GET);
        return $response;
    }

    /**
     * Refund a previously captured transaction
     *
     * @param int $id
     * @return array
     */
    public function cancel($id)
    {
        if (!$id) return false; 

        $response = $this->request($this->getBaseUrl() . '/merchant/orders/'. $id .'/cancel', array(), Zend_Http_Client::PUT);
        return $response;
    }

    /**
     * Send the HTTP request and return an HTTP response object
     *
     * @param string $url
     * @param array $data
     * @param string $method
     * @return array
     */
    public function request($url, array $data, $method='POST')
    {
        try {
            $config = array(
                'adapter' => 'Zend_Http_Client_Adapter_Curl',
                'timeout' => 120
            );

            $client = new Zend_Http_Client($url,$config);
            $client->setMethod($method);
            if ($method==Zend_Http_Client::POST){
                $client->setRawData(json_encode($data), 'application/json');
            }

            //$client->setAuth(Mage::helper('usedrip')->getApiKey());
            $_log = array('url'=>$url, 'data'=>$data);

            Mage::helper('usedrip')->log(json_encode($_log));
            $client->setHeaders('X-API-KEY',Mage::helper('usedrip')->getApiKey());
            $response = $client->request();
            Mage::helper('usedrip')->log(print_r(json_decode($response->getBody()),1));
            return Mage::helper('core')->jsonDecode($response->getBody());
        } catch (Zend_Http_Client_Exception $e) {
            Mage::throwException(Mage::helper('usedrip')->__($e->getMessage()));
        } catch (Exception $e) {
            Mage::helper('usedrip')->log('connection failed: ' . $e->getMessage());
        }
    }

    /**
     * Retrieve base URL
     *
     * @return string
     */
    public function getBaseUrl()
    {
        if (Mage::helper('usedrip')->getMode() == Drip_Pix_Model_Source_Mode::MODE_SANDBOX) {
            return self::SANDBOX_ENDPOINT;
        }
        return self::PRODUCTION_ENDPOINT;
    }
}
