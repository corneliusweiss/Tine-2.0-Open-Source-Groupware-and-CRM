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
 * @subpackage  Generator
 */
class ExchangeWebservices_Generator_Types
{

    /**
     * Generate types for soap service from types.xsd
     */
    function generate()
    {
        $code = "";
        $filename = $_SERVER{'DOCUMENT_ROOT'} . '/tine20/ExchangeWebservices/Generator/src/types.xsd';

        if (file_exists($filename)) {
            $reader = new XMLReader();
            $reader->open($filename);
        } else {
            exit('Konnte ' . $filename . ' nicht öffnen.');
        }

        while ($reader->read()) {

            switch ($reader->nodeType) {
                case (XMLReader::ELEMENT):

                    if ($reader->localName == "simpleType") {
                        $node = $reader->expand();
                        $dom = new DomDocument();
                        $n = $dom->importNode($node, true);
                        $dom->appendChild($n);
                        $this->parseSimpleType($dom);
                    } elseif ($reader->localName == "complexType") {
                        $node = $reader->expand();
                        $dom = new DomDocument();
                        $n = $dom->importNode($node, true);
                        $dom->appendChild($n);
                        $this->parseComplexType($dom);
                    }
                    break;

            }
        }
        return $code;
    }

    /**
     * Parse simple types.
     *
     * @param DomDocument $domDocument
     */
    private function parseSimpleType(DomDocument $domDocument)
    {
        $noderoot = $domDocument->documentElement;
        $childnodes = $noderoot->childNodes;
        $code = "";

        $class = new Zend_CodeGenerator_Php_Class();
        $docblock = $this->generateClassDocblock("Tine 2.0", "Class for an simple type.");
        $class->setName($noderoot->getAttribute('name'))->setDocblock($docblock);

        foreach ($childnodes as $domNode) {
            switch ($domNode->nodeName) {
                case "xs:annotation":
                    $dom = new DomDocument();
                    $n = $dom->importNode($domNode, true);
                    $dom->appendChild($n);
                    $this->parseAnnotation($dom, $class);
                    break;
                case "xs:restriction":
                    $dom = new DomDocument();
                    $n = $dom->importNode($domNode, true);
                    $dom->appendChild($n);
                    $this->parseRestriction($dom, $class);
                    break;
                default:
                    ;
                    break;
            }
        }

        $classFile = new Zend_CodeGenerator_Php_File();
        $classFile->setFilename($_SERVER{'DOCUMENT_ROOT'} . '/tine20/ExchangeWebservices/Generator/gen/' . $class->getName() . '.php');
        $classFile->setClass($class);

        file_put_contents($classFile->getFilename(), $classFile->generate());
    }

    /**
     * Parse complex types.
     *
     * @param DomDocument $domDocument
     */
    private function parseComplexType(DOMDocument $domDocument)
    {
        $noderoot = $domDocument->documentElement;
        $childnodes = $noderoot->childNodes;

        $class = new Zend_CodeGenerator_Php_Class();
        $docblock = $this->generateClassDocblock("Tine 2.0", "Class for an complex type.");
        $class->setName($noderoot->getAttribute('name'))->setDocblock($docblock);

        foreach ($childnodes as $domNode) {
            switch ($domNode->nodeName) {
                case "xs:sequence":
                    $dom = new DomDocument();
                    $n = $dom->importNode($domNode, true);
                    $dom->appendChild($n);
                    $this->parseSequence($dom, $class);
                    break;
                default:
                    ;
                    break;
            }
        }

        $classFile = new Zend_CodeGenerator_Php_File();
        $classFile->setFilename($_SERVER{'DOCUMENT_ROOT'} . '/tine20/ExchangeWebservices/Generator/gen/' . $class->getName() . '.php');
        $classFile->setClass($class);

        file_put_contents($classFile->getFilename(), $classFile->generate());
    }

    /**
     * Parse an annotation for a Class.
     *
     * @param DomDocument $domDocument
     * @param Zend_CodeGenerator_Php_Class $generator
     */
    private function parseAnnotation(DomDocument $domDocument, Zend_CodeGenerator_Php_Class $generator)
    {
        $noderoot = $domDocument->documentElement;
        $childnodes = $noderoot->childNodes;

        foreach ($childnodes as $domNode) {
            switch ($domNode->nodeName) {
                case "xs:documentation":
                    $desc = $generator->getDocblock()->getLongDescription();
                    $desc .= $domNode->nodeValue;
                    $generator->getDocblock()->setLongDescription($desc);
                    break;
                default:
                    ;
                    break;
            }
        }
    }

    /**
     * Parse restrictions for a class.
     *
     * @param DomDocument $domDocument
     * @param Zend_CodeGenerator_Php_Class $generator
     */
    private function parseRestriction(DomDocument $domDocument, Zend_CodeGenerator_Php_Class $generator)
    {
        $noderoot = $domDocument->documentElement;
        $childnodes = $noderoot->childNodes;

        foreach ($childnodes as $domNode) {
            switch ($domNode->nodeName) {
                case "xs:enumeration":
                    $generator->setProperties(array(
                        array(
                            'name' => str_replace(":", "_", $domNode->getAttribute('value')),
                            'visibility' => 'public',
                            'const' => true,
                            'defaultValue' => $domNode->getAttribute('value'))));
                    break;
                default:
                    ;
                    break;
            }
        }
    }

    private function parseSequence(DomDocument $domDocument, Zend_CodeGenerator_Php_Class $generator)
    {
        $noderoot = $domDocument->documentElement;
        $childnodes = $noderoot->childNodes;

        foreach ($childnodes as $domNode) {
            switch ($domNode->nodeName) {
                case "xs:element":
                    $docblock = new Zend_CodeGenerator_Php_Docblock();
                    $docblock->setLongDescription("@var " . $this->getTypeFromXsd($domNode->getAttribute('type')));
                    $generator->setProperties(array(
                        array(
                            'name' => str_replace(":", "_", $domNode->getAttribute('name')),
                            'visibility' => 'private',
                            'const' => false,
                            'docblock' => $docblock)));

                    $generator->setMethod(array(
                        'name' => 'set' . $domNode->getAttribute('name'),
                        'parameters' => array(
                            array(
                                'name' => $domNode->getAttribute('name'))),
                        'body' => '$this->' . $domNode->getAttribute('name') . ' = $' . $domNode->getAttribute('name') . ';',
                        'docblock' => new Zend_CodeGenerator_Php_Docblock(array(
                            'shortDescription' => 'Sets the bar property',
                            'tags' => array(
                                new Zend_CodeGenerator_Php_Docblock_Tag_Param(array(
                                    'paramName' => $domNode->getAttribute('name'),
                                    'datatype' => $this->getTypeFromXsd($domNode->getAttribute('type')))))))));
                    $generator->setMethod(array(
                        'name' => 'get' . $domNode->getAttribute('name'),
                        'parameters' => array(
                            array(
                                'name' => $domNode->getAttribute('name'))),
                        'body' => 'return $this->' . $domNode->getAttribute('name') . ';',
                        'docblock' => new Zend_CodeGenerator_Php_Docblock(array(
                            'shortDescription' => 'Returns the bar property',
                            'tags' => array(
                                new Zend_CodeGenerator_Php_Docblock_Tag_return(array(
                                    'datatype' => $this->getTypeFromXsd($domNode->getAttribute('type')))))))));
                    break;
                default:
                    ;
                    break;
            }
        }
    }

    /**
     * !Don't use.
     * Generate extended properties form a complex type.
     *
     * @var DOMElement
     * @return string
     * @deprecated
     */
    function generateExtendedProperty($complexTyp)
    {
        $code = "";
        $foo = new Zend_CodeGenerator_Php_Class();
        $docblock = $this->generateClassDocblock();
        $foo->setName($complexTyp->getAttribute('name'))->setDocblock($docblock);

        $properties = array();

        foreach ($complexTyp->childNodes as $cnode) {
            foreach ($cnode->childNodes as $ccnode) {
                if ($ccnode->hasAttributes()) {
                    $properties[] = new Zend_CodeGenerator_Php_Property(array(

                        'name' => $ccnode->getAttribute('name'),
                        'visibility' => 'public',
                        'docblock' => new Zend_CodeGenerator_Php_Docblock(array(
                            'shortDescription' => 'Beinhaltet die ' . $ccnode->getAttribute('name') . ' Eigenschaft',
                            'tags' => array(
                                new Zend_CodeGenerator_Php_Docblock_Tag_Return(array(
                                    'var' => $ccnode->getAttribute('type'))))))));
                }
            }
        }
        $foo->setProperties($properties);

        $code = $foo->generate();

        return $code;
    }

    /**
     * Get nodes from document with namespaces.
     *
     * @param DomDocument $domDoc
     * @param String $xpathString
     * @return DOMNodeList
     */
    public static function getNodes(DomDocument $domDoc, $xpathString)
    {
        $xp = new DOMXPath($domDoc);
        $xp->registerNamespace('t', 'http://schemas.microsoft.com/exchange/services/2006/types');
        $xp->registerNamespace('tns', 'http://schemas.microsoft.com/exchange/services/2006/types');
        $xp->registerNamespace('xs', 'http://www.w3.org/2001/XMLSchema');

        $ret = array();
        $nodes = $xp->query($xpathString);
        foreach ($nodes as $node) {
            array_push($ret, $node);
        }
        return $ret;
    }

    /**
     * Generates a default class docblock
     *
     * @param String $shortDescription
     * @param String $longDescription
     */
    private function generateClassDocblock($shortDescription = "Tine 2.0", $longDescription = "")
    {
        $docblock = new Zend_CodeGenerator_Php_Docblock(array(
            'shortDescription' => $shortDescription,
            'longDescription' => $longDescription,
            'tags' => array(
                array(
                    'name' => 'Package',
                    'description' => 'ExchangeWebservices'),
                array(
                    'name' => 'license',
                    'description' => 'http://www.tine20.org/licenses/agpl-nonus.txt AGPL Version 1 (Non-US)'),
                array(
                    'name' => 'copyright',
                    'description' => 'Copyright (c) 2008-2009 Metaways Infosystems GmbH (http://www.metaways.de)'),
                array(
                    'name' => 'author',
                    'description' => 'Tim Kiefer <tim.kiefer@kojikui.de>'),
                array(
                    'name' => 'version',
                    'description' => '$Id:$'))));
        return $docblock;
    }

    /**
     * Debug method print a DomDocument.
     * @param DomDocument $dom
     */
    private function echoDom(DomDocument $dom)
    {
        var_dump($dom->saveHTML());
    }

    /**
     * Debug method print a DomNode.
     * @param $node
     */
    private function echo_node($node)
    {
        $tmp_doc = new DOMDocument();
        $tmp_doc->appendChild($tmp_doc->importNode($node, true));
        return $tmp_doc->saveHTML();
    }

    private function getTypeFromXsd($type)
    {
        return preg_replace("/[a-z]+:/i", "", $type);
        echo $type;
    }
}