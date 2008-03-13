<?php
/**
 * Tine 2.0
 * 
 * @package     Tinebase
 * @subpackage  Server
 * @license     http://www.gnu.org/licenses/old-licenses/gpl-2.0.html
 * @copyright   Copyright (c) 2007-2008 Metaways Infosystems GmbH (http://www.metaways.de)
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @version     $Id$
 */

/**
 * HTTP interface to Tine
 *
 * @package     Tinebase
 * @subpackage  Server
 */
class Tinebase_Http extends Tinebase_Application_Http_Abstract
{
    /**
     * displays the login dialog
     *
     */
    public function login()
    {
        $view = new Zend_View();
        $view->title="Tine 2.0";

        $view->setScriptPath('Tinebase/views');

        try {
            $loginConfig = new Zend_Config_Ini($_SERVER['DOCUMENT_ROOT'] . '/../config.ini', 'login');
            $view->defaultUsername = (isset($loginConfig->username)) ? $loginConfig->username : '';
            $view->defaultPassword = (isset($loginConfig->password)) ? $loginConfig->password : '';
        } catch (Zend_Config_Exception $e) {
            $view->defaultUsername = '';
            $view->defaultPassword = '';
        }
        
        header('Content-Type: text/html; charset=utf-8');
        echo $view->render('login.php');
    }
    
    /**
     * Returns all JS files which must be included for Tinebase
     *
     * @todo refactor js stuff so that all js files could be included
     * before regestry gets included!
     * 
     * @return array Array of filenames
     */
    public function getJsFilesToInclude()
    {
        return array(
            // base framework
            //self::_appendFileTime("../ExtJS/adapter/ext/ext-base.js"),
            //self::_appendFileTime("../ExtJS/ext-all-debug.js"),
            // Tinebase
            //self::_appendFileTime("Tinebase/js/Tinebase.js"),
            self::_appendFileTime("Tinebase/js/Container.js"),
            // Ext user extensions
            self::_appendFileTime("Tinebase/js/ExtUx.js"),
            self::_appendFileTime("Tinebase/js/ux/Percentage.js"),
            self::_appendFileTime("Tinebase/js/ux/PopupWindow.js"),
            self::_appendFileTime("Tinebase/js/ux/Wizard.js"),
            self::_appendFileTime("Tinebase/js/ux/grid/CheckColumn.js"),
            self::_appendFileTime("Tinebase/js/ux/grid/QuickaddGridPanel.js"),
            self::_appendFileTime("Tinebase/js/DatepickerRange.js"),
            // Tine 2.0 specific widgets
            self::_appendFileTime("Tinebase/js/Widgets.js"),
            self::_appendFileTime("Tinebase/js/AccountpickerPanel.js"),
            self::_appendFileTime("Tinebase/js/widgets/ContainerSelect.js"),
            self::_appendFileTime("Tinebase/js/widgets/ContainerGrants.js"),
            self::_appendFileTime("Tinebase/js/widgets/ContainerTree.js"),
        );
    }
    
    public function getCssFilesToInclude()
    {
    	return array(
    	   self::_appendFileTime("Tinebase/css/ux/Wizzard.css"),
    	   self::_appendFileTime("Tinebase/css/Tinebase.css"),
    	);
    }
    
    public function mainScreen()
    {
        $userApplications = Zend_Registry::get('currentAccount')->getApplications();

        $view = new Zend_View();

        $view->setScriptPath('Tinebase/views');

        //$view->jsIncludeFiles = array('extjs/build/locale/ext-lang-de-min.js');
        $view->jsIncludeFiles = $this->getJsFilesToInclude();
        $view->cssIncludeFiles = array();
        $view->initialData = array();
        
        foreach($userApplications as $application) {
            $applicationName = $application->app_name;
            $httpAppName = ucfirst($applicationName) . '_Http';
            if(class_exists($httpAppName)) {
                $application = new $httpAppName;
                
                $view->jsIncludeFiles = array_merge($view->jsIncludeFiles, (array)$application->getJsFilesToInclude());
                $view->cssIncludeFiles = array_merge($view->cssIncludeFiles, (array)$application->getCssFilesToInclude());
                
                $view->initialData[ucfirst($applicationName)] = $application->getInitialMainScreenData();
            }
        }
        
        $view->configData = array(
            'timeZone' => Zend_Registry::get('userTimeZone'),
            'currentAccount' => Zend_Registry::get('currentAccount')->toArray()
        );
        
        
        $view->title="Tine 2.0";

        header('Content-Type: text/html; charset=utf-8');
        echo $view->render('mainscreen.php');
    }

	/**
	 * activate user account
	 *
	 * @param 	string $id
	 * 
	 * @todo	update tables
	 */
	public function activateAccount ( $id ) 
	{
		Zend_Registry::get('logger')->debug(__METHOD__ . '::' . __LINE__ . ' activate account for ' . $_username);
       	
		//@todo set new expire_date in DB
		//@todo update registration table and get username
		
		$view = new Zend_View();
        $view->title="Tine 2.0 User Activation";
        //$view->username = $_username;
        $view->loginUrl = $_SERVER['SERVER_NAME'].$_SERVER['PHP_SELF'];

        $view->setScriptPath('Tinebase/views');
        
        header('Content-Type: text/html; charset=utf-8');
        echo $view->render('activate.php');
	
	}
	
	/**
	 * generate captcha
	 *
	 * @todo	add to user registration
	 * @todo 	save security code in db/session
	 */
	public function generateCaptcha () 
	{	
		$security_code = substr(md5(uniqid()), 4, 4);
		
       	//Set the image width and height
        $width = 120;
        $height = 20;

        // Create the image resource
        $image = ImageCreate($width, $height);  
		
        // get colors, black background, set security code, add some lines
        $white = ImageColorAllocate($image, 255, 255, 255);
        $black = ImageColorAllocate($image, 0, 0, 0);
        $grey = ImageColorAllocate($image, 204, 204, 204);
        ImageFill($image, 0, 0, $black);
        ImageString($image, 4, 40, 4, $security_code, $white);
        ImageRectangle($image,0,0,$width-1,$height-1,$grey);
        imageline($image, 0, $height/2, $width, $height/2, $grey);
        imageline($image, $width/2, 0, $width/2, $height, $grey);

        //Tell the browser what kind of file is come in
        header("Content-Type: image/jpeg");

        //Output the newly created image in jpeg format
        ImageJpeg($image);		
	}
}