<?php
/**
 * Tine 2.0 - http://www.tine20.org
 * 
 * @package     Tinebase
 * @subpackage  Record
 * @license     http://www.gnu.org/licenses/agpl.html
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Matthias Greiling <m.greiling@metaways.de>
 * @version     $Id$
 */

/**
 * Test helper
 */
require_once dirname(dirname(dirname(__FILE__))) . DIRECTORY_SEPARATOR . 'TestHelper.php';

if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Tinebase_Record_ContainerTest::main');
}

require_once 'PHPUnit/Framework.php';


//require_once 'C:\xampp\htdocs\tine20\trunk\tine20\Tinebase\Record\Container.php';

/**
 * Test class for Tinebase_Record_Container.
 * Generated by PHPUnit on 2008-02-14 at 12:25:04.
 */
class Tinebase_Record_ContainerTest extends Tinebase_Record_AbstractTest
{
    /**
     * @var    Tinebase_Record_Container
     * @access protected
     */
    protected $objects;

    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
        require_once 'PHPUnit/TextUI/TestRunner.php';

        $suite  = new PHPUnit_Framework_TestSuite('Tinebase_Record_AbstractTest');
        $result = PHPUnit_TextUI_TestRunner::run($suite);
    }

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    public function setUp()
    {
		
       $this->objects['TestRecord'] = new Tinebase_Record_Container(array(), true);

	 	$this->objects['TestRecord']->setFromArray(array(
	    	'container_id'      => 200,
      	  	'container_name'    => 'test',
     	   	'container_type'    => 'shared',
    	    'container_backend' => 1,
   	     	'application_id'    => 20,
   	    	'account_grants'	=> 31,
            )
		, true);
		
		$this->expectFailure['TestRecord']['testSetId'][] = array('2','3');
		$this->expectSuccess['TestRecord']['testSetId'][] = array('2','2');
		
		
		}

    /**
     * Tears down the fixture, for example, closes a network connection.
     * This method is called after a test is executed.
     *
     * @access protected
     */
    protected function tearDown()
    {
    }
}

// Call Tinebase_Record_ContainerTest::main() if this source file is executed directly.
if (PHPUnit_MAIN_METHOD == 'Tinebase_Record_AbstractRecordTest::main') {
    Tinebase_Record_AbstractRecordTest::main();
}
?>
