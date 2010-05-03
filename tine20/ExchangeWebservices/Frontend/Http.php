<?php

/**
 * Tine 2.0
 *
 * @package     ExchangeWebservices
 * @license     http://www.tine20.org/licenses/agpl-nonus.txt AGPL Version 1 (Non-US)
 * @copyright   Copyright (c) 2008-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Tim Kiefer <tim.kiefer@kojikui.de>
 * @version     $Id:$
 */
/**
 * http frontend
 *
 * @package     ExchangeWebservices
 * @subpackage  Frontend
 */
class ExchangeWebservices_Frontend_Http extends Tinebase_Frontend_Abstract
{

    /**
     * app name
     *
     * @var string
     */
    protected $_applicationName = 'ExchangeWebservices';

    //change this to your WSDL URI!
    private $_WSDL_URI = "http://192.168.178.26/tine20/Microsoft-Server-EWS";

    /**
     * authenticate user
     *
     * @param String $_username
     * @param String $_password
     * @param String $_ipAddress
     * @return boolean
     */
    public function authenticate($_username, $_password, $_ipAddress)
    {
        return ExchangeWebservices_Controller::getInstance()->authenticate($_username, $_password, $_ipAddress);
    }

    /**
     * handle options request
     *
     */
    public function handle()
    {
        if (isset($_GET['wsdl'])) {
            //return the WSDL
            $this->handleWSDL();
        } elseif (isset($_GET['generate'])) {
            //generate Classes for xsd file
            $this->handleGenerate();
        } elseif (isset($_GET['test'])) {
            //simple test
            $this->handleTest();
        } else {
            //handle SOAP request
            $this->handleSOAP();
        }
    }

    /**
     * Handles a wsdl request.
     */
    private function handleWSDL()
    {
        $strategy = new Zend_Soap_Wsdl_Strategy_ArrayOfTypeComplex();
        $autodiscover = new Zend_Soap_AutoDiscover();

        $autodiscover->setOperationBodyStyle(array(
            'use' => 'literal',
            'namespace' => 'http://schemas.microsoft.com/exchange/services/2006/messages'));

        $autodiscover->setBindingStyle(array(
            'style' => 'document',
            'transport' => 'http://schemas.xmlsoap.org/soap/http'));

        $autodiscover->setClass('ExchangeWebservices_EWS');
        $autodiscover->handle();
    }

    /**
     * Handles a soap request.
     */
    private function handleSOAP()
    {
        $options = Array(
            'soap_version' => SOAP_1_2,  //'actor' - Die Aktions-URI für den Server.
            'uri' => $this->_WSDL_URI);

        $soap = new Zend_Soap_Server($this->_WSDL_URI, $options);
        $soap->setClass('ExchangeWebservices_EWS');
        $soap->handle();
    }

    /**
     * Handles a "generate" request.
     */
    private function handleGenerate()
    {
        $generator = new ExchangeWebservices_Generator_Types();
        echo $generator->generate();
        die();
    }

    /**
     * Simple test the soap server
     */
    private function handleTest()
    {
        $soapClient = new Zend_Soap_Client($this->_WSDL_URI . "/?wsdl", array());

        require_once ("ExchangeWebservices/MessageType/FindItemSoapIn.php");
        require_once ("ExchangeWebservices/MessageType/FindItemSoapOut.php");
        $fisi = new FindItemSoapIn();

        echo ($soapClient->SimpleTest(1));
        die();
    }
}