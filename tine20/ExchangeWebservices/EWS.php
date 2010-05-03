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
 * EWS server provide the soap functions.
 *
 * @package     ExchangeWebservices
 */
require_once ("MessageType/FindItemSoapIn.php");
require_once ("MessageType/FindItemSoapOut.php");

class ExchangeWebservices_EWS
{

    public function __construct()
    {

    }

    /**
     * This method takes ...
     *
     * @param integer $inputParam
     * @return integer
     */
    public function SimpleTest($inputParam)
    {
        return $inputParam;
    }

    /**
     * Find an item.
     *
     * @param FindItemSoapIn
     * @return FindItemSoapOut
     */
    public function FindItem($fisi)
    {
        return array();
    }

}