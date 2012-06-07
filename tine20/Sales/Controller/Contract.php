<?php
/**
 * contract controller for Sales application
 * 
 * @package     Sales
 * @subpackage  Controller
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Philipp Schuele <p.schuele@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 *
 */

/**
 * contract controller class for Sales application
 * 
 * @package     Sales
 * @subpackage  Controller
 */
class Sales_Controller_Contract extends Tinebase_Controller_Record_Abstract
{
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct() {
        $this->_applicationName = 'Sales';
        $this->_backend = new Sales_Backend_Contract();
        $this->_modelName = 'Sales_Model_Contract';
    }    
    
    /**
     * holds the instance of the singleton
     *
     * @var Sales_Controller_Contract
     */
    private static $_instance = NULL;
    
    /**
     * the singleton pattern
     *
     * @return Sales_Controller_Contract
     */
    public static function getInstance() 
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Sales_Controller_Contract();
        }
        
        return self::$_instance;
    }        

    /****************************** overwritten functions ************************/

    /**
     * get by id
     *
     * @param string $_id
     * @return Tinebase_Record_RecordSet
     */
    public function get($_id)
    {
        $sharedContracts = $this->getSharedContractsContainer();
        return parent::get($_id, $sharedContracts->getId());
    }
    
    /**
     * Checks if number is unique if manual generated
     * @param Tinebase_Record_Interface $r
     * @param Boolean $update true if called un update
     * @throws Tinebase_Exception_Duplicate
     */
    private function _checkNumberUniquity($r, $update = false)
    {
        $filterArray = array(array('field' => 'number', 'operator' => 'equals', 'value' => $r->__get('number')));
        if($update) {
            $filterArray[] = array('field' => 'id', 'operator' => 'notin', 'value' => $r->getId());
        }
        $filter = new Sales_Model_ContractFilter($filterArray);
        $existing = $this->search($filter);

        if(count($existing->toArray()) > 0) {
            $e = new Tinebase_Exception_Duplicate(_('The number you have tried to set is already in use!'));
            $e->setData($existing);
            $e->setClientRecord($r);
            throw $e;
        }
        return true;
    }
    /**
     * Checks if number is unique if manual generated
     * @param Tinebase_Record_Interface $r
     * @throws Tinebase_Exception_Record_Validation
     */
    private function _checkNumberType($_record)
    {
        $number = $_record->__get('number');
        if(empty($number)) {
            throw new Tinebase_Exception_Record_Validation('Please use a contract number!');
        } elseif ((Sales_Config::getInstance()->get('contractNumberValidation', 'integer') == 'integer') && !is_numeric($number)) {
            throw new Tinebase_Exception_Record_Validation('Please use a decimal number as contract number!');
        }
    }
    
    /**
     * @see Tinebase_Controller_Record_Abstract::update()
     */
    public function update(Tinebase_Record_Interface $_record, $_duplicateCheck = TRUE)
     {
        if($_duplicateCheck) {
            $this->_checkNumberUniquity($_record, true);
        }
        $this->_checkNumberType($_record);
        return parent::update($_record, $_duplicateCheck);
    }
    
    /**
     * add one record
     *
     * @param   Tinebase_Record_Interface $_record
     * @return  Sales_Model_Contract
     */
    public function create(Tinebase_Record_Interface $_record)
    {
        // add container
        $_record->container_id = self::getSharedContractsContainer()->getId();

        if(Sales_Config::getInstance()->get('contractNumberGeneration', 'auto') == 'auto') {
            // add number if configured auto
            $numberBackend = new Sales_Backend_Number();
            $number = $numberBackend->getNext(Sales_Model_Number::TYPE_CONTRACT, Tinebase_Core::getUser()->getId());
            $_record->number = $number->number;
        } else {
            // check uniquity if not autogenerated
            $this->_checkNumberUniquity($_record, false);
        }
        // check type
        $this->_checkNumberType($_record);
        
        return parent::create($_record);
    }

    /**
     * get (create if it does not exist) container for shared contracts
     * 
     * @return Tinebase_Model_Container|NULL
     */
    public static function getSharedContractsContainer()
    {
        $sharedContracts = NULL;
        $appId = Tinebase_Application::getInstance()->getApplicationByName('Sales')->getId();
        
        try {
            $sharedContractsId = Tinebase_Config::getInstance()->getConfig(Sales_Model_Config::SHAREDCONTRACTSID, $appId, '')->value;
            $sharedContracts = Tinebase_Container::getInstance()->get($sharedContractsId);
        } catch (Tinebase_Exception_NotFound $tenf) {
            $newContainer = new Tinebase_Model_Container(array(
                'name'              => 'Shared Contracts',
                'type'              => Tinebase_Model_Container::TYPE_SHARED,
                'backend'           => 'Sql',
                'application_id'    => $appId,
            ));
            $sharedContracts = Tinebase_Container::getInstance()->addContainer($newContainer, NULL, TRUE);
            
            Tinebase_Config::getInstance()->setConfigForApplication(Sales_Model_Config::SHAREDCONTRACTSID, $sharedContracts->getId(), 'Sales');
            
            // add grants for groups
            $groupsBackend = Tinebase_Group::factory(Tinebase_Group::SQL);
            $adminGroup = $groupsBackend->getDefaultAdminGroup();
            $userGroup  = $groupsBackend->getDefaultGroup();
            Tinebase_Container::getInstance()->addGrants($sharedContracts, Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP, $userGroup, array(
                Tinebase_Model_Grants::GRANT_READ,
                Tinebase_Model_Grants::GRANT_EDIT
            ), TRUE);
            Tinebase_Container::getInstance()->addGrants($sharedContracts, Tinebase_Acl_Rights::ACCOUNT_TYPE_GROUP, $adminGroup, array(
                Tinebase_Model_Grants::GRANT_ADD,
                Tinebase_Model_Grants::GRANT_READ,
                Tinebase_Model_Grants::GRANT_EDIT,
                Tinebase_Model_Grants::GRANT_DELETE,
                Tinebase_Model_Grants::GRANT_ADMIN
            ), TRUE);
        }
        
        return $sharedContracts;
    }
}
