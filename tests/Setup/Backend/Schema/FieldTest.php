<?php
// Call Setup_Backend_Schema_FieldTest::main() if this source file is executed directly.
if (!defined('PHPUnit_MAIN_METHOD')) {
    define('PHPUnit_MAIN_METHOD', 'Setup_Backend_Schema_FieldTest::main');
}

require_once 'PHPUnit/Framework.php';

//require_once '\xampp\htdocs\tine30\Setup\Backend\Schema\Field.php';

/**
 * Test class for Setup_Backend_Schema_Field.
 * Generated by PHPUnit on 2008-04-23 at 17:26:20.
 */
class Setup_Backend_Schema_FieldTest extends PHPUnit_Framework_TestCase
{
    /**
     * @var    Setup_Backend_Schema_Field
     * @access protected
     */
    protected $object;

    /**
     * Runs the test methods of this class.
     *
     * @access public
     * @static
     */
    public static function main()
    {
        require_once 'PHPUnit/TextUI/TestRunner.php';

        $suite  = new PHPUnit_Framework_TestSuite('Setup_Backend_Schema_FieldTest');
        $result = PHPUnit_TextUI_TestRunner::run($suite);
    }

    /**
     * Sets up the fixture, for example, opens a network connection.
     * This method is called before a test is executed.
     *
     * @access protected
     */
    protected function setUp()
    {
     //   $this->object = new Setup_Backend_Schema_Field;
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

// Call Setup_Backend_Schema_FieldTest::main() if this source file is executed directly.
if (PHPUnit_MAIN_METHOD == 'Setup_Backend_Schema_FieldTest::main') {
    Setup_Backend_Schema_FieldTest::main();
}
?>
