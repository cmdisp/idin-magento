<?php

/**
 * @method int getRegistrationId()
 * @method CMGroep_Idin_Model_Registration setRegistrationId(int $value)
 * @method string getEntranceCode()
 * @method CMGroep_Idin_Model_Registration setEntranceCode(string $value)
 * @method string getTransactionId()
 * @method CMGroep_Idin_Model_Registration setTransactionId(string $value)
 * @method string getCustomerId()
 * @method CMGroep_Idin_Model_Registration setCustomerId(string $value)
 * @method string getTransactionResponse()
 * @method CMGroep_Idin_Model_Registration setTransactionResponse(string $value)
 *
 * Class CMGroep_Idin_Model_Registration
 */
class CMGroep_Idin_Model_Registration extends Mage_Core_Model_Abstract
{

    protected function _construct()
    {
        $this->_init('cmgroep_idin/registration');
    }

}