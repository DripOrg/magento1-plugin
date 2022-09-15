<?php

/**
 *
 * @category   Drip
 * @package    Drip_Pix
 * @author     Inovarti <https://www.inovarti.com.br>
 * @copyright 2022 Drip (https://usedrip.com.br/)
 */

class Drip_Pix_Helper_Order extends Varien_Object
{
    /**
     * Retrieves Order Request Data
     *
     * @param Mage_Sales_Model_Order $order
     * @return array
     */
    public function getRequestData(Mage_Sales_Model_Order $order)
    {
        $result = array();
        $result['orderId']      = $order->getIncrementId();
        $result['resolveUrl']   = Mage::getBaseUrl(Mage_Core_Model_Store::URL_TYPE_LINK) . "usedrip/notifications/custom";
        $result['amount']       = $this->_formatNumber($order->getBaseGrandTotal());
        $result['merchantCode'] = $order->getIncrementId();
        return $result;
    }

    /**
     * Retrieves Customer Basic Data
     *
     * @param Mage_Sales_Model_Order $order
     * @return array
     */
    public function getCustomerData(Mage_Sales_Model_Order $order)
    {
        /** @var  Mage_Customer_Model_Address $address */
        $address = $this->_getAddress($order);

        $result = array(
            "customerCpf" => $this->_getDocumentNumber($order),
            "customerName" => $this->_getCustomerName($order),
            "customerEmail" => $address->getEmail(),
            "customerAddressCep" => $this->_getAddressPostalCode($address),
            "customerAddressStreet" => $this->_getAddressStreet($address),
            "customerAddressNeighborhood" => $this->_getAddressNeighborhood($address),
            "customerAddressCity" => $address->getCity(),
            "customerAddressState" => $address->getRegionCode(),
            "customerAddressNumber" => $this->_getAddressStreetNumber($address),
            "customerAddressComplement" => $this->_getAddressComplement($address),
        );

        return $result;
    }

    /**
     * Retrieves Order Items
     *
     * @param Mage_Sales_Model_Order $order
     * @return array
     */
    public function getShoppingCart(Mage_Sales_Model_Order $order)
    {

        $helper = Mage::helper('usedrip');
        $products = array();

        /** @var  Mage_Sales_Model_Order_Item $item */
        foreach ($order->getAllVisibleItems() as $item) {
            /** @var Mage_Catalog_Model_Product $product */
            $product = $item->getProduct();
            $image = (string)Mage::helper('catalog/image')->init($product, 'image');
            /*
             "merchantCode": "MyCode-123",
                "productId": "MyCode-123",
                "name": "Nice Sunglasses",
                "created": "2022-01-18",
                "modified": "2022-02-15",
                "featured": true,
                "description": "Sunglasses with UVA and UVB protection",
                "link": "https://mystore.com/my-product",
                "quantity": 2,
                "amount": "100.00",
                "fullAmount": "150.00",
                "totalSales": 200,
                "stockQuantity": 10,
                "backorders": 2,
                "categories": [
                    "Yellow"
                ],
                "principalImage": "https://mystore.com/my-product/principal.jpg",
                "ratingCount": 52,
                "averageRating": "4.3",
                "totalAmount": "200.00"
            */
            $products[] = array(
                "merchantCode" => $item->getSku(),
                "productId" => $item->getSku(),
                "name" => $item->getName(),
                "link" => $product->getProductUrl(),
                "quantity" => intval($item->getQtyOrdered()),
                "amount" => $this->_formatNumber($item->getBasePrice()),
                "totalAmount" => $this->_formatNumber($item->getBaseRowTotal()),
                "principalImage" => $image,
            );
        }
        $result['products'] = $products;

        return $result;
    }

    /**
     * Retrieves Address Object
     *
     * @param Mage_Sales_Model_Order $order
     * @return Mage_Sales_Model_Order_Address
     */
    protected function _getAddress(Mage_Sales_Model_Order $order)
    {
        return $order->getIsVirtual() ? $order->getBillingAddress() : $order->getShippingAddress();
    }

    /**
     * Retrieves Customer Document Number (CPF/CNPJ)
     *
     * @param Mage_Sales_Model_Order $order
     * @return mixed
     */
    protected function _getDocumentNumber(Mage_Sales_Model_Order $order)
    {

        $document = preg_replace("/[^0-9]/", "", $order->getCustomerTaxvat());
        if (strlen($document) < 11 && $document) {
            return str_pad($document, 11, 0, STR_PAD_LEFT);
        }
        return $document;
    }
   

    /**
     * Retrieves Customer Name
     *
     * @param Mage_Sales_Model_Order $order
     * @return mixed
     */
    protected function _getCustomerName(Mage_Sales_Model_Order $order)
    {
        $document = preg_replace("/[^0-9]/", "", $order->getCustomerTaxvat());
        if (strlen($document) > 11 && $document) {
            return $order->getCustomerName();
        }
        return $order->getCustomerFirstname();
    }

    /**
     * Retrieves Address Street
     *
     * @param $address
     * @return string
     */
    protected function _getAddressStreet($address)
    {
        return $address->getStreet(1);
    }

    /**
     * Retrieves Address Street Number
     *
     * @param $address
     * @return string
     */
    protected function _getAddressStreetNumber($address)
    {
        return ($address->getStreet(2)) ? $address->getStreet(2) : 'SN';
    }

    /**
     * Retrieves Address Complement
     *
     * @param $address
     * @return string
     */
    protected function _getAddressComplement($address)
    {
        return $address->getStreet(3);
    }

    /**
     * Retrieves Address Neighborhood
     *
     * @param $address
     * @return string
     */
    protected function _getAddressNeighborhood($address)
    {
        return $address->getStreet(4);
    }

    /**
     * Retrieves Address Postal Code
     *
     * @param $address
     * @return string
     */
    protected function _getAddressPostalCode($address)
    {
        return preg_replace('/[^0-9]/', '', $address->getPostcode());
    }
    /**
     * Format Number
     *
     * @param $number
     * @return string
     */
    public function _formatNumber($number)
    {
        $number = Mage::getSingleton('core/locale')->getNumber($number);
        return (float) sprintf('%0.2f', $number);
    }
}
