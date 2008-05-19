<?php
/**
 * Tine 2.0
 *
 * @package     Addressbook
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id$
 */
/**
 * sql backend class for the addressbook
 *
 * @package     Addressbook
 */
class Addressbook_Backend_Sql implements Addressbook_Backend_Interface
{
    /**
     * @var Zend_Db_Adapter_Abstract
     */
    protected $_db;
    /**
     * the constructor
     *
     * don't use the constructor. use the singleton 
     */
    private function __construct ()
    {
        $this->_db = Zend_Registry::get('dbAdapter');
    }
    /**
     * don't clone. Use the singleton.
     *
     */
    private function __clone ()
    {
        
    }
    /**
     * holdes the instance of the singleton
     *
     * @var Addressbook_Backend_Sql
     */
    private static $_instance = NULL;
    /**
     * the singleton pattern
     *
     * @return Addressbook_Backend_Sql
     */
    public static function getInstance ()
    {
        if (self::$_instance === NULL) {
            self::$_instance = new Addressbook_Backend_Sql();
        }
        return self::$_instance;
    }
    /**
     * add or updates a contact
     *
     * This functions gets removed, when Cornelius move the history stuff to it's final location
     * 
     * @param Addressbook_Model_Contact $_contactData the contactdata
     * @deprecated
     * @return Addressbook_Model_Contact
     */
 
    /**
     * get list of contacts from given addressbooks
     *
     * @param  Tinebase_Record_RecordSet $_container  container id's to read the contacts from
     * @param  Addressbook_Model_Filter  $_filter     string to search for in contacts
     * @param  Tinebase_Model_Pagination $_pagination 
     * @return Tinebase_Record_RecordSet subtype Addressbook_Model_Contact
     */
    public function getContacts (Tinebase_Record_RecordSet $_container, Addressbook_Model_Filter  $_filter, Tinebase_Model_Pagination $_pagination)
    {
        if (count($_container) === 0) {
            throw new Exception('$_container can not be empty');
        }
        $select = $this->_db->select();
        $select->where($this->_db->quoteInto($this->_db->quoteIdentifier('container_id') . ' IN (?)', $_container->getArrayOfIds()));
        $result = $this->_getContactsFromTable($select, $_filter, $_pagination);
        return $result;
    }
    /**
     * get total count of contacts from given addressbooks
     *
     * @param  Tinebase_Record_RecordSet $_container container id's to read the contacts from
     * @param  Addressbook_Model_Filter  $_filter the search filter
     * @return int                       count of all other users contacts
     */
    public function getCountOfContacts (Tinebase_Record_RecordSet $_container, Addressbook_Model_Filter $_filter)
    {
        if (count($_container) === 0) {
            throw new Exception('$_container can not be empty');
        }
        $select = $this->_db->select();
        $select->from(SQL_TABLE_PREFIX . 'addressbook', array('count' => 'COUNT(*)'));
        $select->where($this->_db->quoteInto($this->_db->quoteIdentifier('container_id') . ' IN (?)', $_container->getArrayOfIds()));
        $this->_addFilter($select, $_filter);
        $result = $this->_db->fetchOne($select);
        return $result;
    }
    /**
     * add the fields to search for to the query
     *
     * @param  Zend_Db_Select           $_select current where filter
     * @param  Addressbook_Model_Filter $_filter the string to search for
     * @return void
     */
    protected function _addFilter (Zend_Db_Select $_select, Addressbook_Model_Filter $_filter)
    {
        $_select->where($this->_db->quoteInto($this->_db->quoteIdentifier('n_family') . ' LIKE ?', '%' . trim($_filter->query) . '%'))
                ->orWhere($this->_db->quoteInto($this->_db->quoteIdentifier('n_given') . ' LIKE ?', '%' . trim($_filter->query) . '%'))
                ->orWhere($this->_db->quoteInto($this->_db->quoteIdentifier('org_name') . ' LIKE ?', '%' . trim($_filter->query) . '%'))
                ->orWhere($this->_db->quoteInto($this->_db->quoteIdentifier('email') . ' LIKE ?', '%' . trim($_filter->query) . '%'));
        
        if (! empty($_filter->tag)) {
            Tinebase_Tags::appendSqlFilter($_select, $_filter->tag);
        }
    }
    /**
     * internal function to read the contacts from the database
     *
     * @param  Zend_Db_Select                     $_where where filter
     * @param  Addressbook_Model_Filter  $_filter
     * @param  Tinebase_Model_Pagination $_pagination
     * @return Tinebase_Record_RecordSet subtype Addressbook_Model_Contact
     */
    protected function _getContactsFromTable (Zend_Db_Select $_select, Addressbook_Model_Filter $_filter, Tinebase_Model_Pagination $_pagination)
    {
        $_select->from(SQL_TABLE_PREFIX . 'addressbook');
        $this->_addFilter($_select, $_filter);
        $_pagination->appendPagination($_select);
        $rows = $this->_db->fetchAssoc($_select);
        $result = new Tinebase_Record_RecordSet('Addressbook_Model_Contact', $rows, true);
        return $result;
    }
    /**
     * add a contact
     *
     * @param Addressbook_Model_Contact $_contactData the contactdata
     * @return Addressbook_Model_Contact
     */
    public function addContact (Addressbook_Model_Contact $_contactData)
    {
        if (! $_contactData->isValid()) {
            throw new Exception('invalid contact');
        }
        if ($_contactData->getId() == NULL) {
            $_contactData->setId($_contactData->generateUID());
        }    
        $contactData = $_contactData->toArray();
        //if (empty($_contactData->id)) {
        //    $contactData['id'] = Tinebase_Account_Model_Account::generateUID();
        //}
        // tags are not property of this backend
        unset($contactData['tags']);
        
        
        $this->_db->insert(SQL_TABLE_PREFIX . 'addressbook', $contactData);

        return $this->getContact($_contactData->getId());
    }
    /**
     * update an existing contact
     *
     * @param Addressbook_Model_Contact $_contactData the contactdata
     * @return Addressbook_Model_Contact
     */
    public function updateContact (Addressbook_Model_Contact $_contactData)
    {
        if (! $_contactData->isValid()) {
            throw new Exception('invalid contact');
        }
        $contactId = Addressbook_Model_Contact::convertContactIdToInt($_contactData);
        $contactData = $_contactData->toArray();
        unset($contactData['id']);
        // tags are not property of this backend
        unset($contactData['tags']);
        $where = $this->_db->quoteInto($this->_db->quoteIdentifier('id') . ' = ?', $contactId);
        $this->_db->update(SQL_TABLE_PREFIX . 'addressbook', $contactData, $where);
        return $this->getContact($contactId);
    }
    /**
     * delete contact identified by contact id
     *
     * @param int $_contactId contact ids
     * @return int the number of rows deleted
     */
    public function deleteContact ($_contactId)
    {
        $contactId = Addressbook_Model_Contact::convertContactIdToInt($_contactId);
        $where = $this->_db->quoteInto($this->_db->quoteIdentifier('id') . ' = ?', $contactId);
        $result = $this->_db->delete(SQL_TABLE_PREFIX . 'addressbook', $where);
        return $result;
    }
    /**
     * fetch one contact identified by contactid
     *
     * @param int $_contactId
     * @return Addressbook_Model_Contact 
     */
    public function getContact ($_contactId)
    {
        $contactId = Addressbook_Model_Contact::convertContactIdToInt($_contactId);
        $select = $this->_db->select()->from(SQL_TABLE_PREFIX . 'addressbook')->where($this->_db->quoteInto($this->_db->quoteIdentifier('id') .' = ?', $contactId));
        $row = $this->_db->fetchRow($select);
        if (NULL == $row) {
            throw new UnderflowException('contact not found');
        }
        $result = new Addressbook_Model_Contact($row);
        return $result;
    }
}
