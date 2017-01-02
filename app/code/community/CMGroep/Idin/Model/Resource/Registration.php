<?php
 
class CMGroep_Idin_Model_Resource_Registration extends Mage_Core_Model_Resource_Db_Abstract
{

    protected function _construct()
    {
        $this->_init('cmgroep_idin/registration', 'registration_id');
    }

}