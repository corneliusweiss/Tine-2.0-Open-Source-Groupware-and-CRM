<?php
/**
 * class to hold message cache data
 * 
 * @package     Felamimail
 * @license     http://www.gnu.org/licenses/agpl.html AGPL Version 3
 * @author      Lars Kneschke <l.kneschke@metaways.de>
 * @copyright   Copyright (c) 2009-2010 Metaways Infosystems GmbH (http://www.metaways.de)
 * @version     $Id:Category.php 5576 2008-11-21 17:04:48Z p.schuele@metaways.de $
 * 
 * @todo        add flags as consts here?
 * @todo        add more CONTENT_TYPE_ constants
 */

/**
 * class to hold message cache data
 * 
 * @package     Felamimail
 * @property    string  $subject        the subject of the email
 * @property    string  $from           the address of the sender
 * @property    string  $content_type   the address of the sender
 * @property    array   $to             the to receipients
 * @property    array   $cc             the cc receipients
 * @property    array   $bcc            the bcc receipients
 * @property    array   $structure      the message structure
 * @property    string  $messageuid     the message uid on the imap server
 */
class Felamimail_Model_Message extends Tinebase_Record_Abstract
{
    /**
     * date format constants
     *
     */
    const DATE_FORMAT = 'EEE, d MMM YYYY hh:mm:ss zzz';
    const DATE_FORMAT_RECEIVED = 'dd-MMM-YYYY hh:mm:ss zzz';
    
    /**
     * message content type (rfc822)
     *
     */
    const CONTENT_TYPE_MESSAGE_RFC822 = 'message/rfc822';

    /**
     * content type html
     *
     */
    const CONTENT_TYPE_HTML = 'text/html';

    /**
     * content type plain text
     *
     */
    const CONTENT_TYPE_PLAIN = 'text/plain';
    
    /**
     * attachment filename regexp 
     *
     */
    //const ATTACHMENT_FILENAME_REGEXP = "/name=\"*([\w\-\._ ]+)\"*/u";
    const ATTACHMENT_FILENAME_REGEXP = "/name=\"(.*)\"/";
    
    /**
     * email address regexp
     */
    // '/(?<!mailto:)([a-z0-9_\+-\.]+@[a-z0-9-\.]+\.[a-z]{2,4})/i';
    const EMAIL_ADDRESS_REGEXP = '/([a-z0-9_\+-\.]+@[a-z0-9-\.]+\.[a-z]{2,5})/i'; 
    
    /**
     * quote string ("> ")
     * 
     * @var string
     */
    const QUOTE = '&gt; ';
    
    /**
     * key in $_validators/$_properties array for the field which 
     * represents the identifier
     * 
     * @var string
     */    
    protected $_identifier = 'id';    
    
    /**
     * application the record belongs to
     *
     * @var string
     */
    protected $_application = 'Felamimail';

    /**
     * list of zend validator
     * 
     * this validators get used when validating user generated content with Zend_Input_Filter
     *
     * @var array
     */
    protected $_validators = array(
        'id'                    => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'original_id'           => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'messageuid'            => array(Zend_Filter_Input::ALLOW_EMPTY => false), 
        'folder_id'             => array(Zend_Filter_Input::ALLOW_EMPTY => false), 
        'subject'               => array(Zend_Filter_Input::ALLOW_EMPTY => true), 
        'from'                  => array(Zend_Filter_Input::ALLOW_EMPTY => true), 
        'to'                    => array(Zend_Filter_Input::ALLOW_EMPTY => true), 
        'cc'                    => array(Zend_Filter_Input::ALLOW_EMPTY => true), 
        'bcc'                   => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'received'              => array(Zend_Filter_Input::ALLOW_EMPTY => true), 
        'sent'                  => array(Zend_Filter_Input::ALLOW_EMPTY => true), 
        'size'                  => array(Zend_Filter_Input::ALLOW_EMPTY => true), 
        'flags'                 => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'timestamp'             => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'body'                  => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'structure'             => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'text_partid'           => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'html_partid'           => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'has_attachment'        => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'headers'               => array(Zend_Filter_Input::ALLOW_EMPTY => true),
        'content_type'          => array(
            Zend_Filter_Input::ALLOW_EMPTY => true,
            Zend_Filter_Input::DEFAULT_VALUE => self::CONTENT_TYPE_PLAIN,
            'InArray' => array(self::CONTENT_TYPE_HTML, self::CONTENT_TYPE_PLAIN)
        ),
        'attachments'           => array(Zend_Filter_Input::ALLOW_EMPTY => true),
    // save email as contact note
        'note'                  => array(Zend_Filter_Input::ALLOW_EMPTY => true, Zend_Filter_Input::DEFAULT_VALUE => 0),
    // Felamimail_Message object
        'message'               => array(Zend_Filter_Input::ALLOW_EMPTY => true),
    );
    
    /**
     * name of fields containing datetime or or an array of datetime information
     *
     * @var array list of datetime fields
     */
    protected $_datetimeFields = array(
        'timestamp',
        'received',
        'sent',
    );
    
    /**
     * fills a record from json data
     *
     * @param array $recordData
     * 
     * @todo    get/detect delimiter from row? could be ';' or ','
     * @todo    add recipient names
     */
    protected function _setFromJson(array &$recordData)
    {
        // explode email addresses if multiple
        $recipientType = array('to', 'cc', 'bcc');
        $delimiter = ';';
        foreach ($recipientType as $field) {
            if (!empty($recordData[$field])) {
                $recipients = array();
                foreach ($recordData[$field] as $addresses) {
                    if (substr_count($addresses, '@') > 1) {
                        $recipients = array_merge($recipients, explode($delimiter, $addresses));
                    } else {
                        // single recipient
                        $recipients[] = $addresses;
                    }
                }
                
                foreach ($recipients as $key => &$recipient) {
                    // get address 
                    // @todo get name here
                    //<*([a-zA-Z@_\-0-9\.]+)>*/
                    if (preg_match(self::EMAIL_ADDRESS_REGEXP, $recipient, $matches) > 0) {
                        $recipient = $matches[1];
                    }
                    if (empty($recipient)) {
                        unset($recipients[$key]);
                    }
                }

                //if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' ' . print_r($recipients, true));
                
                $recordData[$field] = array_unique($recipients);
            }
        }
    }    

    /**
     * get body as plain text with replaced blockquotes, stripped tags and replaced <br>s
     * -> use DOM extension
     * 
     * @return string
     */
    public function getPlainTextBody()
    {
        $result = '';
        
        $dom = new DOMDocument('1.0', 'utf-8');
        // use a hack to make sure html is loaded as utf8 (@see http://php.net/manual/en/domdocument.loadhtml.php#95251)
        $dom->loadHTML('<?xml encoding="UTF-8">' . $this->body);
        $bodyElements = $dom->getElementsByTagName('body');
        if ($bodyElements->length > 0) {
            $result = $this->_addQuotesAndStripTags($bodyElements->item(0));
            $result = html_entity_decode($result, ENT_COMPAT, 'UTF-8');
        } else {
            if (Tinebase_Core::isLogLevel(Zend_Log::DEBUG)) Tinebase_Core::getLogger()->debug(__METHOD__ . '::' . __LINE__ . ' No body element found.');
        }
        
        return $result;
    }
    
    /**
     * convert blockquotes to quotes ("> ") and strip tags
     * 
     * this function uses tidy or DOM to recursivly walk the dom tree of the html mail
     * @see http://php.net/manual/de/tidy.root.php
     * @see http://php.net/manual/en/book.dom.php
     * 
     * @param tidyNode|DOMNode $_node
     * @param integer $_quoteIndent
     * @return string
     * 
     * @todo we can transform more tags here, i.e. the <strong>BOLDTEXT</strong> tag could be replaced with *BOLDTEXT*
     * @todo think about removing the tidy code
     */
    protected function _addQuotesAndStripTags($_node, $_quoteIndent = 0) {
        
        $result = '';
        
        $hasChildren = ($_node instanceof DOMNode) ? $_node->hasChildNodes() : $_node->hasChildren();
        $nameProperty = ($_node instanceof DOMNode) ? 'nodeName' : 'name';
        $valueProperty = ($_node instanceof DOMNode) ? 'nodeValue' : 'value';
        
        if ($hasChildren) {
            $lastChild = NULL;
            $children = ($_node instanceof DOMNode) ? $_node->childNodes : $_node->child;
            
            foreach ($children as $child) {
                $isTextLeaf = ($child instanceof DOMNode) ? $child->{$nameProperty} == '#text' : ! $child->{$nameProperty};
                if ($isTextLeaf) { 
                    // leaf -> add quotes and append to content string
                    if ($_quoteIndent > 0) {
                        $result .= str_repeat(self::QUOTE, $_quoteIndent) . $child->{$valueProperty};
                        // add newline if parent is div
                        if ($_node->{$nameProperty} == 'div') {
                            $result .=  "\n" . str_repeat(self::QUOTE, $_quoteIndent);
                        }
                    } else {
                        // add newline if parent is div
                        if ($_node->{$nameProperty} == 'div') {
                            $result .= "\n";
                        }
                        $result .= $child->{$valueProperty};
                    }
                    
                } else if ($child->{$nameProperty} == 'blockquote') {
                    //  opening blockquote
                    $_quoteIndent++;
                    
                } else if ($child->{$nameProperty} == 'br') {
                    // reset quoted state on newline
                    if ($lastChild !== NULL && $lastChild->{$nameProperty} == 'br') {
                        // add quotes to repeating newlines
                        $result .= str_repeat(self::QUOTE, $_quoteIndent);
                    }
                    $result .= "\n";
                }
                
                $result .= $this->_addQuotesAndStripTags($child, $_quoteIndent);
                
                if ($child->{$nameProperty} == 'blockquote') {
                    // closing blockquote
                    $_quoteIndent--;
                    // add newline after last closing blockquote
                    if ($_quoteIndent == 0) {
                        $result .= "\n";
                    }
                }
                
                $lastChild = $child;
            }
        }
        
        return $result;
    }    
}
