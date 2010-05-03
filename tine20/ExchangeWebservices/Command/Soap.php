<?php

/**
 * Tine 2.0
 *
 * @package     ExchangeWebservices
 * @subpackage  ExchangeWebservices
 * @license     http://www.tine20.org/licenses/agpl-nonus.txt AGPL Version 1 (Non-US)
 *              NOTE: According to sec. 8 of the AFFERO GENERAL PUBLIC LICENSE (AGPL),
 *              Version 1.
 * @copyright   Copyright (c) 2008-2009 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Tim Kiefer <tim.kiefer@kojikui.de>
 * @version     $Id:$
 */

/**
 * abstract class for all commands using wbxml encoded content
 *
 * @package     ExchangeWebservices
 * @subpackage  ExchangeWebservices
 */

abstract class ExchangeWebservices_Command_Soap
{

    /**
     * the constructor
     *
     */
    public function __construct()
    {}

    /**
     * this abstract function must be implemented the commands
     * this function processes the incoming request
     *
     */
    abstract public function handle();

    /**
     * this function generates the response for the client
     * could get overwriten by the command
     *
     */
    public function getResponse()
    {    #echo $buffer;
    }
}