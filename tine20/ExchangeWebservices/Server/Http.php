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
 * http server
 *
 * @package     ExchangeWebservices
 * @subpackage  Server
 */
class ExchangeWebservices_Server_Http extends Tinebase_Server_Abstract
{

    /**
     * handler for EWS requests
     *
     * @return boolean
     */
    public function handle ()
    {
        try {
            $this->_initFramework();
        } catch (Zend_Session_Exception $exception) {
            Tinebase_Core::getLogger()->warn(__METHOD__ . '::' . __LINE__ . ' invalid session. Delete session cookie.');
            Zend_Session::expireSessionCookie();
            header('WWW-Authenticate: Basic realm="EWS for Tine 2.0"');
            header('HTTP/1.1 401 Unauthorized');
            return;
        }
        Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' is EWS request.');
        $auth = new Zend_Auth_Adapter_Http_Ntlm(
        array('log' => new Zend_Log(new Zend_Log_Writer_Stream('/tmp/ntlm.log')),
            'resolver' => new Zend_Auth_Adapter_Http_Ntlm_Resolver_Mock(),
        	'session' => new Zend_Session_Namespace('ntlm'),
        	'targetInfo' => array(
        					'domain' => 'DOMAIN',
        					'servername' => 'SERVER',
        					'dnsdomain' => 'domain.com',
        					'fqserver' => 'server.domain.com'
                            )
                )
        );

        $authResult = $auth->authenticate();
        if (! $authResult->isValid()) {
            echo $auth->getResponse();
            die();
        }


        // successfull auth
        $ewsFrontend = new ExchangeWebservices_Frontend_Http();

        $ewsFrontend->handle();
    }
}