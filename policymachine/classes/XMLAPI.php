<?php

/**
 * Creates a Word document out of an XML file
 *
 * @category   Phpdocx
 * @package    XML
 * @copyright  Copyright (c) Narcea Producciones Multimedia S.L.
 *             (http://www.2mdc.com)
 * @license    phpdocx LICENSE
 * @link       https://www.phpdocx.com
 */
require_once dirname(__FILE__) . '/CreateDocx.php';

class XMLAPI
{
    /**
     *
     * @access public
     * @static
     * @var array
     */
    public static $rawData;
    /**
     *
     * @access private
     * @var DOMNode
     */
    private $_config;
    /**
     *
     * @access private
     * @var DOMNode
     */
    private $_content;
    /**
     *
     * @access private
     * @var CreateDocx
     */
    private $_document;
    /**
     *
     * @access private
     * @var DOMXPath
     */
    private $_docXPath;
    /**
     *
     * @access private
     * @var DOMDocument
     */
    private $_domXML;
    /**
     *
     * @access private
     * @var DOMNode
     */
    private $_layout;
    /**
     *
     * @access private
     * @var string 
     */
    private $_name;
    /**
     *
     * @access private
     * @var string
     */
    private $_outputExtension;
    /**
     *
     * @access private
     * @var DOMNode
     */
    private $_settings;
    /**
     *
     * @access private
     * @var DOMNode
     */
    private $_templateVariables;
    /**
     *
     * @access private
     * @var boolean
     */
    private $_validate;
    /**
     *
     * @access private
     * @var DOMNode
     */
    private $_wordFragments;
    
    /**
     * Construct
     *
     * @param string $xml
     * @param boolean $validate
     * @access public
     */
    public function __construct($xml, $validate = false)
    {
        // load the XML in the DOM for parsing
        $this->_domXML = new DOMDocument();
        $this->_domXML->preserveWhiteSpace = false;
        if (is_readable($xml)) {
            $xmlStr = file_get_contents($xml);
        } else {
            $xmlStr = $xml;
        }
        if (PHP_VERSION_ID < 80000) {
            $optionEntityLoader = libxml_disable_entity_loader(true);
        }
        $this->_domXML->loadXML($xmlStr);
        if (PHP_VERSION_ID < 80000) {
            libxml_disable_entity_loader($optionEntityLoader);
        }

        // create the corresponding DOMXPath element
        $this->docXPath = new DOMXPath($this->_domXML);
        $this->docXPath->registerNamespace('pdx', "http://www.phpdocx.com/main");

        $this->_wordFragments = array();

        // validate $xml if $validate is true
        $XMLSchema = dirname(__FILE__) . '/../schemas/xmldocx.xsd';
        $this->_validate = $validate;
        if ($validate) {
            $valid = $this->XMLValidation($this->_domXML, $XMLSchema);
            if (!$valid) {
                PhpdocxLogger::logger('XML config: invalid XML', 'fatal');
            }
        }

        // check if using a template or creating a Word document from scratch
        $template = false;
        $templateNodes = $this->_domXML->getElementsByTagName('template');
        if ($templateNodes->length > 0) {
            $template = $templateNodes->item(0)->getAttribute('pdx:path');
        }

        // get the name and the output extension
        $outputNodes = $this->_domXML->getElementsByTagName('output');
        if ($outputNodes->length > 0) {
            //name
            $this->_name = $outputNodes->item(0)->getAttribute('pdx:name');
            if (empty($this->_name)) {
                PhpdocxLogger::logger('The pdx:output element is lacking the required pdx:name attribute.', 'fatal');
            }
            //extension
            $this->_outputExtension = $outputNodes->item(0)->getAttribute('pdx:type');
            if (empty($this->_outputExtension)) {
                $this->_outputExtension = 'docx';
            }
        } else {
            PhpdocxLogger::logger('The loaded XML is lacking the required pdx:output element.', 'fatal');
        }

        // create the document object
        if ($this->_outputExtension == 'docx') {
            if (empty($template)) {
                if ($this->_outputExtension == 'docx') {
                    $this->_document = new CreateDocx();
                } else {
                    $this->_document = new CreateDocx($this->_outputExtension);
                }
            } else {
                $this->_document = new CreateDocxFromTemplate($template);
            }

            // stream mode
            if ($outputNodes->item(0)->hasAttribute('pdx:stream')) {
                if ($outputNodes->item(0)->getAttribute('pdx:stream') == "1") {
                    CreateDocx::$streamMode = true;
                }
            }
        }
        
    }

    /**
     * Destruct
     *
     * @access public
     */
    public function __destruct()
    {
        
    }

    /**
     * Magic method, returns current config XML element
     *
     * @access public
     * @return string Return current XML
     */
    public function __toString()
    {
       return $this->_domXML->saveXML();
    }

    /**
     * Creates a wordFragment from the XML data
     *
     * @access private
     * @return void
     */
    public function createWordFragment($id)
    {
        $target = 'document';
        $query = '//wordFragment[@wordFragmentName ="' .$id . '"]';
        $wfs = $this->_docXPath->query($query);
        if($wfs->length > 0){
           $wf = $wfs->item(0); 
           $name = $wf->getAttribute('pdx:wordFragmentName');
            $targetAttribute = $wf->getAttribute('pdx:target');
            if(!empty($targetAttribute)){
                $target = $targetAttribute;
            }
            $name = 'wordFragment_' . $name;
            if(!in_array($name, $this->_wordFragments)){
                $this->_wordFragments[$name] = new WordFragment($this->_document, $target);
                $contentNode = $child->geteElementsByTagName('content')->item(0);
                $this->parseXMLElement($contentNode, $this->_wordFragments[$name]);
            }
        }
    }
    
    /**
     * XML file with the general properties of the Word document 
     *
     * @access public
     * @param string xml
     */
    public function setDocumentProperties($xml)
    {
        $this->_docProps = new DOMDocument();
        $this->_docProps->preserveWhiteSpace = false;
        if (is_readable($xml)) {
            $xmlStr = file_get_contents($xml);
        } else {
            $xmlStr = $xml;
        }
        if (PHP_VERSION_ID < 80000) {
            $optionEntityLoader = libxml_disable_entity_loader(true);
        }
        $this->_docProps->loadXML($xmlStr);
        if (PHP_VERSION_ID < 80000) {
            libxml_disable_entity_loader($optionEntityLoader);
        }
        // validate $xml
        $XMLSchema = dirname(__FILE__) . '/../schemas/xmldocx.xsd';
        if($this->_validate){
            $valid = $this->XMLValidation($this->_docProps, $XMLSchema);
            if(!$valid){
                PhpdocxLogger::logger('setDocumentProperties: invalid XML', 'fatal');
            }
        }
        // do the parsing
        $this->parseSettings($this->_docProps->documentElement);
    }
    
    /**
     * XML file with content to be added to the Word document
     *
     * @access public
     * @param string $xml
     */
    public function addContent($xml)
    {
        $docContent = new DOMDocument();
        $docContent->preserveWhiteSpace = false;
        if (is_readable($xml)) {
            $xmlStr = file_get_contents($xml);
        } else {
            $xmlStr = $xml;
        }
        if (PHP_VERSION_ID < 80000) {
            $optionEntityLoader = libxml_disable_entity_loader(true);
        }
        $docContent->loadXML($xmlStr);
        if (PHP_VERSION_ID < 80000) {
            libxml_disable_entity_loader($optionEntityLoader);
        }
        // validate $xml
        $XMLSchema = dirname(__FILE__) . '/../schemas/xmldocx.xsd';
        if ($this->_validate) {
            $valid = $this->XMLValidation($docContent, $XMLSchema);
            if (!$valid) {
                PhpdocxLogger::logger('addContent: invalid XML', 'fatal');
            }
        }
        // do the parsing       
        $this->parseContent($docContent->documentElement);
    }

    /**
     * Renders Word document out of the XML data
     *
     * @access public
     * @param array $options
     */
    public function render()
    {        
        // parse additional config data
        $protect = false;
        $encrypt = false;
        $sign = false;
        $removeProtection = false;
        $transformDocument = false;

        $protectNodes = $this->_domXML->getElementsByTagName('protect');
        if ($protectNodes->length > 0) {
            $protect = true;
            $attributes = $protectNodes->item(0)->attributes;
            $protectOptions = $this->XMLAttributes2Array($attributes, 'array');
        } 
        $encryptNodes = $this->_domXML->getElementsByTagName('encrypt');
        if ($encryptNodes->length > 0) {
            $encrypt = true;
            $attributes = $encryptNodes->item(0)->attributes;
            $encryptOptions = $this->XMLAttributes2Array($attributes, 'array');
        }
        $signNodes = $this->_domXML->getElementsByTagName('sign');
        if ($signNodes->length > 0) {
            $sign = true;
            $attributes = $signNodes->item(0)->attributes;
            $signOptions = $this->XMLAttributes2Array($attributes, 'array');
        }
        $removeProtectionNodes = $this->_domXML->getElementsByTagName('removeProtection');
        if ($removeProtectionNodes->length > 0) {
            $removeProtection = true;
            $attributes = $removeProtectionNodes->item(0)->attributes;
            $removeProtectionOptions = $this->XMLAttributes2Array($attributes, 'array');
            $docx = new CryptoPHPDOCX();
            $docx->removeProtection($removeProtectionOptions['src'], $removeProtectionOptions['target']);
        }
        $transformDocumentNodes = $this->_domXML->getElementsByTagName('transformDocument');
        if ($transformDocumentNodes->length > 0) {
            $transformDocument = true;
            $attributes = $transformDocumentNodes->item(0)->attributes;
            $transformDocumentOptions = $this->XMLAttributes2Array($attributes, 'array');
        }

        //generate the resulting docx
        if ($this->_outputExtension == 'docx' || $this->_outputExtension == 'docm') {
            if ($protect === false && $encrypt === false && $sign === false && $removeProtection === false) {
                $this->_document->createDocx($this->_name);
            } else {
                if ($protect) {
                    $prot = new CryptoPHPDOCX();
                    $prot->protectDocx($protectOptions['src'], 
                                        $protectOptions['target'], 
                                       array('password' => $protectOptions['password']));
                    unset($prot);
                }
                if ($encrypt) {
                    $crypt = new CryptoPHPDOCX();
                    $crypt->encryptDocx($encryptOptions['src'], 
                                        $encryptOptions['target'], 
                                        array('password' => $encryptOptions['password']));
                    unset($crypt);
                }
                if ($sign) {
                    $signDoc = new SignDocx();
                    $signDoc->setDocx($signOptions['src']);
                    $signDoc->setPrivateKey($signOptions['privateKey'], $signOptions['password']);
                    $signDoc->setX509Certificate($signOptions['X509Certificate']);
                    $signDoc->sign();
                }
            }
        } else if($this->_outputExtension == 'pdf') {
            if ($protect === false && $encrypt === false && $sign === false && $transformDocument === true) {
                $this->_document = new CreateDocx();
                if (isset($transformDocumentOptions['comments'])) {
                    $transformDocumentOptions['comments'] = (bool)$transformDocumentOptions['comments'];
                }
                if (isset($transformDocumentOptions['formsfields'])) {
                    $transformDocumentOptions['formsfields'] = (bool)$transformDocumentOptions['formsfields'];
                }
                if (isset($transformDocumentOptions['lossless'])) {
                    $transformDocumentOptions['lossless'] = (bool)$transformDocumentOptions['lossless'];
                }
                if (isset($transformDocumentOptions['pdfa1'])) {
                    $transformDocumentOptions['pdfa1'] = (bool)$transformDocumentOptions['pdfa1'];
                }
                if (isset($transformDocumentOptions['toc'])) {
                    $transformDocumentOptions['toc'] = (bool)$transformDocumentOptions['toc'];
                }
                $this->_document->transformDocument($transformDocumentOptions['src'], $transformDocumentOptions['target'], $transformDocumentOptions['method'], $transformDocumentOptions);
            } else {
                if ($protect) {
                    $prot = new CryptoPHPDOCX();
                    // get the permissions
                    $permissionsOptions = explode(',', $protectOptions['type']);
                    $prot->protectPDF($protectOptions['src'], 
                                        $protectOptions['target'],
                                       array('password' => $protectOptions['password'], 'permissionsBlocked' => $permissionsOptions));
                    unset($prot);
                }
                if ($encrypt) {
                    $crypt = new CryptoPHPDOCX();
                    $crypt->encryptPDF($encryptOptions['src'], 
                                        $encryptOptions['target'],
                                        array('password' => $encryptOptions['password']));
                    unset($crypt);
                }
                if ($sign) {
                    $signPdf = new SignPDF();
                    $signPdf->setPDF($signOptions['src']);
                    $signPdf->setPrivateKey($signOptions['privateKey'], $signOptions['password']);
                    $signPdf->setX509Certificate($signOptions['X509Certificate']);
                    @$signPdf->sign($signOptions['target']);
                }
            }
        } else {
            $this->_document->createDocx($this->_name);
        }
    }
    
    /**
     * Parses config XML element
     *
     * @access private
     * @return void
     */
    private function parseConfig()
    {
        $childNodes = $this->_config->childNodes;
        
    }
    
    /**
     * Parses main content XML element
     *
     * @access private
     * @param DOMNode $content
     * @return void
     */
    private function parseContent($content)
    {
        $childNodes = $content->childNodes;

        foreach ($childNodes as $node) {
            $childSubnodes = $node->childNodes;
            foreach ($childSubnodes as $subnode) {
                if ($subnode->nodeName == 'pdx:wordFragments') {
                    $this->parseWordFragments($subnode);
                } else {
                    $this->parseXMLElement($subnode, $this->_document);
                }
            }
        }
    }

    
    /**
     * Parses settings XML element
     *
     * @access private
     * @param DOMNode $settings
     * @return void
     */
    private function parseSettings($settings)
    {
        $childNodes = $settings->firstChild->childNodes;
        foreach ($childNodes as $node) {
            $this->parseXMLElement($node, $this->_document);
        }
    }
    
    /**
     * Parses templateVariables XML element
     *
     * @access private
     * @return void
     */
    private function parseTemplateVariables()
    {
        $childNodes = $this->_templateVariables->childNodes;
        foreach ($childNodes as $node) {
            $this->parseXMLElement($node, $this->_document);
        }
    }
    
    /**
     * Parses wordFragments XML element
     *
     * @access private
     * @param $wordFragments
     * @return void
     */
    private function parseWordFragments($wordFragments)
    {
        $target = 'document';
        $childNodes = $wordFragments->childNodes;

        foreach ($childNodes as $child) {
            $name = $child->getAttribute('pdx:wordFragmentName');
            $targetAttribute = $child->getAttribute('pdx:target');
            if (!empty($targetAttribute)) {
                $target = $targetAttribute;
            }
            $name = 'wordFragment_' . $name;
            if (!in_array($name, $this->_wordFragments)) {
                $this->_wordFragments[$name] = new WordFragment($this->_document, $target);
                
                for ($i = 0; $i < $child->getElementsByTagName('content')->length; $i++) { 
                    $contentNode = $child->getElementsByTagName('content')->item($i);
                    $this->parseXMLElement($contentNode->childNodes->item(0), $this->_wordFragments[$name]);
                }
            }
        }
    }
    
    /**
     * Parses main content XML element
     *
     * @access private
     * @param DOMNode XMLnode
     * @param  DOMNode $contentTarget
     * @return void
     */
    private function parseXMLElement($XMLnode, $contentTarget = NULL)
    {
        if (empty($contentTarget)) {
            $contentTarget = $this->_document;
        }
        $nodeName = $XMLnode->nodeName;
        switch ($nodeName) {
            //settings nodes
            case 'pdx:addProperties':
                $this->XMLAPIAddProperties($XMLnode);
                break;
            case 'pdx:docxSettings':
                $this->XMLAPIDocxSettings($XMLnode);
                break;
            case 'pdx:enableRepairMode':
                $this->XMLAPIEnableRepairMode($XMLnode);
                break;
            case 'pdx:setDefaultFont':
                $this->XMLAPISetDefaultFont($XMLnode);
                break;
            case 'pdx:setEncodeUTF8':
                $this->XMLAPISetEncodeUTF8($XMLnode);
                break;
            case 'pdx:setLanguage':
                $this->XMLAPISetLanguage($XMLnode);
                break;
            case 'pdx:setMarkAsFinal':
                $this->XMLAPISetMarkAsFinal($XMLnode);
                break;
            //layout nodes
            case 'pdx:addBackgroundImage':
                $this->XMLAPIAddBackgroundImage($XMLnode);
                break;
            case 'pdx:addLineNumbering':
                $this->XMLAPIAddLineNumbering($XMLnode);
                break;
            case 'pdx:addMacroFromDoc':
                $this->XMLAPIAddMacroFromDoc($XMLnode);
                break;
            case 'pdx:addPageBorders':
                $this->XMLAPIAddPageBorders($XMLnode);
                break;  
            case 'pdx:addSection':
                $this->XMLAPIAddSection($XMLnode);
                break;
            case 'pdx:createCharacterStyle':
                $this->XMLAPICreateCharacterStyle($XMLnode);
                break;
            case 'pdx:createListStyle':
                $this->XMLAPICreateListStyle($XMLnode);
                break;
            case 'pdx:createParagraphStyle':
                $this->XMLAPICreateParagraphStyle($XMLnode);
                break;
            case 'pdx:importHeadersAndFooters':
                $this->XMLAPIImportHeadersAndFooters($XMLnode);
                break;
            case 'pdx:importListStyle':
                $this->XMLAPIImportListStyle($XMLnode);
                break;
            case 'pdx:importStyles':
                $this->XMLAPIImportStyles($XMLnode);
                break;
            case 'pdx:modifyPageLayout':
                $this->XMLAPIModifyPageLayout($XMLnode);
                break;
            case 'pdx:parseStyles':
                $this->XMLAPIParseStyles($XMLnode);
                break;
            case 'pdx:removeFooters':
                $this->XMLAPIRemoveFooters($XMLnode);
                break;
            case 'pdx:removeHeaders':
                $this->XMLAPIRemoveHeaders($XMLnode);
                break;
            case 'pdx:setBackgroundColor':
                $this->XMLAPISetBackgroundColor($XMLnode);
                break;
            case 'pdx:setDocumentDefaultStyles':
                $this->XMLAPISetDocumentDefaultStyles($XMLnode);
                break;
            //content nodes
            case 'pdx:addBookmark':
                $this->XMLAPIAddBookmark($XMLnode, $contentTarget);
                break;
            case 'pdx:addCrossReference':
                $this->XMLAPIAddCrossReference($XMLnode, $contentTarget);
                break;
            case 'pdx:addBreak':
                $this->XMLAPIAddBreak($XMLnode, $contentTarget);
                break;
            case 'pdx:addChart':
                $this->XMLAPIAddChart($XMLnode, $contentTarget);
                break;
            case 'pdx:addComment':
                $this->XMLAPIAddComment($XMLnode, $contentTarget);
                break;
            case 'pdx:addDateAndHour':
                $this->XMLAPIAddDateAndHour($XMLnode, $contentTarget);
                break;
            case 'pdx:addExternalFile':
                $this->XMLAPIAddExternalFile($XMLnode, $contentTarget);
                break;
            case 'pdx:addEndnote':
                $this->XMLAPIAddEndnote($XMLnode, $contentTarget);
                break;
            case 'pdx:addFootnote':
                $this->XMLAPIAddFootnote($XMLnode, $contentTarget);
                break;
            case 'pdx:addFormElement':
                $this->XMLAPIAddFormElement($XMLnode, $contentTarget);
                break;
            case 'pdx:addFooter':
                $this->XMLAPIAddFooter($XMLnode);
                break;
            case 'pdx:addHeading':
                $this->XMLAPIAddHeading($XMLnode, $contentTarget);
                break;
            case 'pdx:addHeader':
                $this->XMLAPIAddHeader($XMLnode);
                break;
            case 'pdx:addImage':
                $this->XMLAPIAddImage($XMLnode, $contentTarget);
                break;
            case 'pdx:addLink':
                $this->XMLAPIAddLink($XMLnode, $contentTarget);
                break;
            case 'pdx:addList':
                $this->XMLAPIAddList($XMLnode, $contentTarget);
                break;
            case 'pdx:addMathEquation':
                $this->XMLAPIAddMathEquation($XMLnode, $contentTarget);
                break;
            case 'pdx:addMergeField':
                $this->XMLAPIAddMergeField($XMLnode, $contentTarget);
                break;
            case 'pdx:addOnlineVideo':
                $this->XMLAPIAddOnlineVideo($XMLnode, $contentTarget);
                break;
            case 'pdx:addPageNumber':
                $this->XMLAPIAddPageNumber($XMLnode, $contentTarget);
                break;
            case 'pdx:addPermProtection':
                $this->XMLAPIAddPermProtection($XMLnode, $contentTarget);
                break;
            case 'pdx:addShape':
                $this->XMLAPIAddShape($XMLnode, $contentTarget);
                break;
            case 'pdx:addSimpleField':
                $this->XMLAPIAddSimpleField($XMLnode, $contentTarget);
                break;
            case 'pdx:addStructuredDocumentTag':
                $this->XMLAPIAddStructuredDocumentTag($XMLnode, $contentTarget);
                break;
            case 'pdx:addTable':
                $this->XMLAPIAddTable($XMLnode, $contentTarget);
                break;
            case 'pdx:addTableContents':
                $this->XMLAPIAddTableContents($XMLnode, $contentTarget);
                break;
            case 'pdx:addTableFigures':
                $this->XMLAPIAddTableFigures($XMLnode, $contentTarget);
                break;
            case 'pdx:addText':
                $this->XMLAPIAddText($XMLnode, $contentTarget);
                break;
            case 'pdx:addTextBox':
                $this->XMLAPIAddTextBox($XMLnode, $contentTarget);
                break;
            case 'pdx:addWordML':
                $this->XMLAPIAddWordML($XMLnode, $contentTarget);
                break;
            case 'pdx:embedHTML':
                $this->XMLAPIEmbedHTML($XMLnode, $contentTarget);
                break;
            case 'pdx:cloneWordContent':
                $this->XMLAPICloneWordContent($XMLnode);
                break;
            case 'pdx:customizeWordContent':
                $this->XMLAPICustomizeWordContent($XMLnode);
                break;
            case 'pdx:insertWordFragment':
                $this->XMLAPIInsertWordFragment($XMLnode);
                break;
            case 'pdx:moveWordContent':
                $this->XMLAPIMoveWordContent($XMLnode);
                break;
            case 'pdx:removeWordContent':
                $this->XMLAPIRemoveWordContent($XMLnode);
                break;
            case 'pdx:replaceWordContent':
                $this->XMLAPIReplaceWordContent($XMLnode);
                break;
            //template variables
            case 'pdx:clearBlocks':
                $this->XMLAPIClearBlocks($XMLnode, $contentTarget);
                break;
            case 'pdx:cloneBlock':
                $this->XMLAPICloneBlock($XMLnode, $contentTarget);
                break;
            case 'pdx:deleteTemplateBlocks':
                $this->XMLAPIDeleteTemplateBlocks($XMLnode, $contentTarget);
                break;
            case 'pdx:modifyInputFields':
                $this->XMLAPIModifyInputFields($XMLnode, $contentTarget);
                break;
            case 'pdx:processTemplate':
                $this->XMLAPIProcessTemplate($XMLnode, $contentTarget);
                break;
            case 'pdx:removeTemplateVariable':
                $this->XMLAPIRemoveTemplateVariable($XMLnode, $contentTarget);
                break;
            case 'pdx:replaceListVariable':
                $this->XMLAPIReplaceListVariable($XMLnode, $contentTarget);
                break;
            case 'pdx:replacePlaceholderImage':
                $this->XMLAPIReplacePlaceholderImage($XMLnode, $contentTarget);
                break;
            case 'pdx:replaceTableVariable':
                $this->XMLAPIReplaceTableVariable($XMLnode, $contentTarget);
                break;
            case 'pdx:replaceVariableByExternalFile':
                $this->XMLAPIReplaceVariableByExternalFile($XMLnode, $contentTarget);
                break;
            case 'pdx:replaceVariableByHTML':
                $this->XMLAPIReplaceVariableByHTML($XMLnode, $contentTarget);
                break;
            case 'pdx:replaceVariableByText':
                $this->XMLAPIReplaceVariableByText($XMLnode, $contentTarget);
                break;
            case 'pdx:replaceVariableByWordFragment':
                $this->XMLAPIReplaceVariableByWordFragment($XMLnode, $contentTarget);
                break;
            case 'pdx:replaceVariableByWordML':
                $this->XMLAPIReplaceVariableByWordML($XMLnode, $contentTarget);
                break;
            case 'pdx:setTemplateSymbol':
                $this->XMLAPISetTemplateSymbol($XMLnode, $contentTarget);
                break;
            case 'pdx:setTemplateBlockSymbol':
                $this->XMLAPISetTemplateBlockSymbol($XMLnode, $contentTarget);
                break;
            // tracking contents
            case 'pdx:acceptTracking':
                $this->XMLAPIAcceptTracking($XMLnode);
                break;
            case 'pdx:addPerson':
                $this->XMLAPIAddPerson($XMLnode);
                break;
            case 'pdx:disableTracking':
                $this->XMLAPIDisableTracking($XMLnode);
                break;
            case 'pdx:enableTracking':
                $this->XMLAPIEnableTracking($XMLnode);
                break;
            case 'pdx:rejectTracking':
                $this->XMLAPIRejectTracking($XMLnode);
                break;
            case 'pdx:mergeDocx':
            case 'pdx:mergePdf':
            case 'pdx:parseCheckboxes':
            case 'pdx:rawSearchAndReplace':
            case 'pdx:removeChapter':
            case 'pdx:removePagesPdf':
            case 'pdx:removeSection':
            case 'pdx:replaceChartData':
            case 'pdx:searchAndHighlight':
            case 'pdx:searchAndRemove':
            case 'pdx:searchAndReplace':
            case 'pdx:setLineNumbering':
            case 'pdx:splitDocx':
            case 'pdx:splitPdf':
            case 'pdx:watermarkDocx':
            case 'pdx:watermarkPdf':
            case 'pdx:watermarkRemove':
                $this->parseDocxUtilities($XMLnode);
                break;
        }
    }
    
    /**
     * Removes the namespace from tag names and attributes
     *
     * @access private
     * @param string $name
     * @return string
     */
    private function cleanNS($name)
    {
        $arrayNS = explode(':', $name);
        if (count($arrayNS) > 1) {
            return $arrayNS[1];
        } else {
            return $name;
        }
    }
    
    /**
     * Converts a DOMNodeList element in an associative array
     * 
     * @access private
     * @param DOMNodeList $nodeList
     * @return array
     */
    private function XML2Array($nodeList, $data = array())
    {
        foreach ($nodeList as $node) {
            $data = $this->XML2ArraySingleNode($node, $data);
        }
        return $data;
    }

    /**
     * Converts a DOMNodeList element in an associative array
     * 
     * @access private
     * @param DOMNode $node
     * @return array
     */
    private function XML2ArraySingleNode($node, $data = array())
    { 
        $name = $this->cleanNS($node->nodeName);
        // run over the attributes and child nodes
        $attributes = $node->attributes;
        if ($node->hasChildNodes()) {
            $childs = $node->childNodes;
            if ($childs->length == 1 && $childs->item(0)->nodeName == '#text') {
                $data[$name] = $childs->item(0)->parentNode->nodeValue;
            } else {
                $data[$name] = $this->XML2Array($childs);
            }
        } else {
            $data[$name] = $this->XMLAttributes2Array($attributes);
        }
        return $data;
    }

    /**
     * Converts a list of attributes into an array
     * 
     * @access private
     * @param DOMNodeMap $attributes
     * @param string $type it can boolean, array or mixed
     * @param array $values
     * 
     * @return mixed
     */
    private function XMLAttributes2Array($attributes, $type = 'mixed', $values = array())
    {
        $numberOfAttributes = $attributes->length;
        if ($numberOfAttributes == 0 && $type != 'array') {
            return true;
        } elseif($numberOfAttributes == 1 && $type != 'array') {
            return $attributes->item(0)->value;
        } else {
            foreach($attributes as $index => $attr){
                $values[$this->cleanNS($attr->name)] = $attr->value;
            }
            return $values;
        }
    }
    
    /**
     * Parses the borders childs to bring them to standard format
     * 
     * @access private
     * @param DOMNode $XMLNode
     * @param array $data
     * @return array
     */
    private function XMLBorderChilds2Array($XMLNode, $data = array())
    {            
        //first parse the general border properties
        $style = $XMLNode->getAttribute('pdx:style');
        $color = $XMLNode->getAttribute('pdx:color');
        $space = $XMLNode->getAttribute('pdx:space');
        $width = $XMLNode->getAttribute('pdx:width');
        if(!empty($style)){
           $data['border'] = $style;
        }
        if(!empty($color)){
           $data['borderColor'] = $color;
        }
        if(!empty($space)){
           $data['borderSpacing'] = $space;
        }
        if(!empty($width)){
           $data['borderWidth'] = $width;
        }
        // parse the individual border sides
        $sides = array('top', 'right', 'bottom', 'left', 'insideH', 'insideV');
        foreach ($sides as $side) {
            $borders = $XMLNode->getElementsByTagName($side);
            if ($borders->length > 0) {
                $border = $borders->item(0); 
                $attributes = $border->attributes;
                foreach($attributes as $index => $attr){
                    switch ($attr->name){
                        case 'style':
                            $data['border' . ucwords($side)] = $attr->value;
                            break;
                        case 'color':
                            $data['border' . ucwords($side) . 'Color'] = $attr->value;
                            break;
                        case 'space':
                            $data['border' . ucwords($side) . 'Spacing'] = $attr->value;
                            break;
                        case 'width':
                            $data['border' . ucwords($side) . 'Width'] = $attr->value;
                            break;
                    }
                }
            }
        }
        return $data;
    }

    /**
     * Parses the acceptTracking XML element and generates the required 
     * PHPDocX code
     *
     * @access private
     * @param DOMNode $XMLNode
     * @return void
     */
    private function XMLAPIAcceptTracking($XMLNode)
    {
        $childNodes = $XMLNode->childNodes;
        $referenceNode = $this->XML2Array($childNodes);

        if (!is_array($referenceNode)) {
            if ($childNodes[0]->getAttribute('pdx:customQuery')) {
                $referenceNode['referenceNode'] = array('customQuery' => $referenceNode);
            } else {
                $referenceNode['referenceNode'] = array('type' => $referenceNode);
            }
        }

        if (isset($referenceNode['referenceNode']['occurrence'])) {
            if (is_numeric($referenceNode['referenceNode']['occurrence'])) {
                $referenceNode['referenceNode']['occurrence'] = (int)$referenceNode['referenceNode']['occurrence'];
            }
        }

        if (isset($referenceNode['attribute']) && is_array($referenceNode['attribute'])) {
            $attribute = array();

            if (isset($referenceNode['attribute']['dataTag']) && !empty($referenceNode['attribute']['dataTag'])) {
                $referenceNode['referenceNode']['attributes'] = array(
                    $referenceNode['attribute']['dataTag'] => array(
                        $referenceNode['attribute']['dataAttribute'] => $referenceNode['attribute']['dataValue'],
                    )
                );
            } else {
                $referenceNode['referenceNode']['attributes'] = array(
                    $referenceNode['attribute']['dataAttribute'] => $referenceNode['attribute']['dataValue'],
                );
            }
        }
        $referenceNode = $referenceNode['referenceNode'];

        $this->_document->acceptTracking($referenceNode);
    }

    /**
     * Parses the addPerson XML element and generates the required 
     * PHPDocX code
     *
     * @access private
     * @param DOMNode $XMLNode
     * @return void
     */
    private function XMLAPIAddPerson($XMLNode)
    {
        $person = array();
        $person['author'] = $XMLNode->getAttribute('pdx:author');
        if ($XMLNode->hasAttribute('pdx:providerId')) {
            $person['providerId'] = $XMLNode->getAttribute('pdx:providerId');
        }
        if ($XMLNode->hasAttribute('pdx:userId')) {
            $person['userId'] = $XMLNode->getAttribute('pdx:userId');
        }

        $this->_document->addPerson($person);
    }

    /**
     * Parses the addProperties XML element and generates the required 
     * PHPDocX code
     *
     * @access private
     * @param DOMNode $XMLNode
     * @return void
     */
    private function XMLAPIAddProperties($XMLNode)
    {
        $childNodes = $XMLNode->childNodes;
        $values = $this->XML2Array($childNodes);
        if (isset($values['customProperties'])) {
            $cp = $XMLNode->getElementsByTagName('customProperties')->item(0);
            $customNodes = $cp->childNodes;
            $values['custom'] = array();
            foreach($customNodes as $node){
                $name = $node->getAttribute('pdx:name');
                $type = $node->getAttribute('pdx:type');
                $data = $node->nodeValue;
                $values['custom'][$name] = array($type => $data);
            }
        }
        $this->_document->addProperties($values);
    }

    /**
     * Parses the disableTracking XML element and generates the required 
     * PHPDocX code
     *
     * @access private
     * @param DOMNode $XMLNode
     * @return void
     */
    private function XMLAPIDisableTracking($XMLNode)
    {
        $this->_document->disableTracking();
    }
    
    /**
     * Parses the docxSettings XML element and generates the required 
     * PHPDocX code
     *
     * @access private
     * @param DOMNode $XMLNode
     * @return void
     */
    private function XMLAPIDocxSettings($XMLNode)
    {
        $childNodes = $XMLNode->childNodes;
        $values = $this->XML2Array($childNodes);

        $customSettings = array();
        $customSettingsTags = $XMLNode->getElementsByTagName('customSetting');
        if ($customSettingsTags->length > 0) {
            // remove the extra tag to construct it again as phpdocx needs
            unset($values['customSettings']);
            foreach ($customSettingsTags as $customSettingsTag) {
                $customSettingsAttributes = $customSettingsTag->getElementsByTagName('attribute');
                $attributes = array();
                foreach ($customSettingsAttributes as $customSettingsAttribute) {
                    $attributes[$customSettingsAttribute->getAttribute('pdx:name')] = $customSettingsAttribute->nodeValue;
                }
                $customSettings = array(
                    'tag' => $customSettingsTag->getAttribute('pdx:tag'),
                    'values' => $attributes,
                );
            }
            $values['customSetting'] = $customSettings;
        }

        $compatSettings = array();
        $compatSettings = $XMLNode->getElementsByTagName('compat');
        if ($compatSettings->length > 0) {
            // remove the extra tag to construct it again as phpdocx needs
            unset($values['compat']);
            foreach ($compatSettings as $compatSetting) {
                $compatSettingAttributes = $compatSetting->getElementsByTagName('attribute');
                $attributes = array();
                foreach ($compatSettingAttributes as $compatSettingAttribute) {
                    $attributes[$compatSettingAttribute->getAttribute('pdx:name')] = array ('val' => $compatSettingAttribute->nodeValue);
                }
            }
            $values['compat'] = $attributes;
        }

        $this->_document->docxSettings($values);
    }

    /**
     * Parses the enableTracking XML element and generates the required 
     * PHPDocX code
     *
     * @access private
     * @param DOMNode $XMLNode
     * @return void
     */
    private function XMLAPIEnableTracking($XMLNode)
    {
        $person = array();
        $person['author'] = $XMLNode->getAttribute('pdx:author');
        if ($XMLNode->hasAttribute('pdx:date')) {
            $person['date'] = $XMLNode->getAttribute('pdx:date');
        }

        $this->_document->enableTracking($person);
    }
    
    /**
     * Parses the setDefaultFont XML element and generates the required 
     * PHPDocX code
     *
     * @access private
     * @param DOMNode $XMLNode
     * @return void
     */
    private function XMLAPISetDefaultFont($XMLNode)
    {
        $font = $XMLNode->getAttribute('pdx:value');
        $this->_document->setDefaultFont($font);
    }
    
    /**
     * Parses the setEncodeUTF8 XML element and generates the required 
     * PHPDocX code
     *
     * @access private
     * @param DOMNode $XMLNode
     * @return void
     */
    private function XMLAPISetEncodeUTF8($XMLNode)
    {
        $encode = $XMLNode->getAttribute('pdx:value');
        if (!empty($encode) && $encode != 'false') {
            $this->_document->setEncodeUTF8();
        }
    }
    
    /**
     * Parses the setLanguage XML element and generates the required 
     * PHPDocX code
     *
     * @access private
     * @param DOMNode $XMLNode
     * @return void
     */
    private function XMLAPISetLanguage($XMLNode)
    {
        $lang = $XMLNode->getAttribute('pdx:value');
        $this->_document->setLanguage($lang);
    }
    /**
     * Parses the setMarkAsFinal XML element and generates the required 
     * PHPDocX code
     *
     * @access private
     * @param DOMNode $XMLNode
     * @return void
     */
    private function XMLAPISetMarkAsFinal($XMLNode)
    {
        $mark = $XMLNode->getAttribute('pdx:value');
        if (!empty($mark) && $mark != 'false') {
            $this->_document->setMarkAsFinal();
        }
    }
    
    /**
     * Parses the addBackgroundImage XML element and generates the required 
     * PHPDocX code
     *
     * @access private
     * @param DOMNode $XMLNode
     * @return void
     */
    private function XMLAPIAddBackgroundImage($XMLNode)
    {
        $src = $XMLNode->getAttribute('pdx:src');
        $this->_document->addBackgroundImage($src);
    }
    
    /**
     * Parses the addFooter XML element and generates the required PHPDocX code
     *
     * @access private
     * @param DOMNode $XMLNode
     * @return void
     */
    private function XMLAPIAddFooter($XMLNode)
    {
        $childNodes = $XMLNode->childNodes;
        $values = $this->XML2Array($childNodes);
        $footers = array();
        foreach ($values as $key => $value) {
            $footers[$key] = $this->_wordFragments['wordFragment_' . $value];
        }
        $this->_document->addFooter($footers);
    }
    
    /**
     * Parses the addHeader XML element and generates the required PHPDocX code
     *
     * @access private
     * @param DOMNode $XMLNode
     * @return void
     */
    private function XMLAPIAddHeader($XMLNode)
    {
        $childNodes = $XMLNode->childNodes;
        $values = $this->XML2Array($childNodes);
        $headers = array();
        foreach ($values as $key => $value) {
            $headers[$key] = $this->_wordFragments['wordFragment_' . $value];
        }
        $this->_document->addHeader($headers);
    }
    
    /**
     * Parses the addLineNumbering XML element and generates the required 
     * PHPDocX code
     *
     * @access private
     * @param DOMNode $XMLNode
     * @return void
     */
    private function XMLAPIAddLineNumbering($XMLNode)
    {
        $values = $this->XML2ArraySingleNode($XMLNode);
        $this->_document->addLineNumbering($values['addLineNumbering']);
    }
    
    /**
     * Parses the addMacroFromDoc XML element and generates the required 
     * PHPDocX code
     *
     * @access private
     * @param DOMNode $XMLNode
     * @return void
     */
    private function XMLAPIAddMacroFromDoc($XMLNode)
    {
        $src = $XMLNode->getAttribute('pdx:src');
        $this->_document->addMacroFromDoc($src);
    }
    
    /**
     * Parses the addPageBorders XML element and generates the required 
     * PHPDocX code
     *
     * @access private
     * @param DOMNode $XMLNode
     * @return void
     */
    private function XMLAPIAddPageBorders($XMLNode)
    {
        $values = $this->XMLBorderChilds2Array($XMLNode);
        $borderColor = $XMLNode->getAttribute('pdx:color');
        $borderSpacing = $XMLNode->getAttribute('pdx:space');
        $border = $XMLNode->getAttribute('pdx:style');
        $borderWidth = $XMLNode->getAttribute('pdx:width');
        $display = $XMLNode->getAttribute('pdx:display');
        $zOrder = $XMLNode->getAttribute('pdx:zOrder');
        $offsetFrom = $XMLNode->getAttribute('pdx:ofsetFrom');
        if (!empty($borderColor)) {
           $values['borderColor'] = $borderColor;
        }
        if (!empty($borderSpacing)) {
           $values['borderSpacing'] = $borderSpacing;
        }
        if (!empty($border)) {
           $values['border'] = $border;
        }
        if (!empty($borderWidth)) {
           $values['borderWidth'] = $borderWidth;
        }
        if (!empty($display)) {
           $values['display'] = $display;
        }
        if (!empty($zOrder)) {
           $values['zOrder'] = $zOrder;
        }
        if (!empty($offsetFrom)) {
           $values['offsetFrom'] = $offsetFrom;
        }
        $this->_document->addPageBorders($values);
    }
    
    /**
     * Parses the addSection XML element and generates the required PHPDocX code
     *
     * @access private
     * @param DOMNode $XMLNode
     * @return void
     */
    private function XMLAPIAddSection($XMLNode)
    {
        $sectionType = 'nextPage';
        $paperType = 'custom';
        $childNodes = $XMLNode->childNodes;
        $values = $this->XML2Array($childNodes);
        $sectionTypeAttribute = $XMLNode->getAttribute('pdx:type');
        if (!empty($sectionTypeAttribute)) {
            $sectionType = $sectionTypeAttribute;
        }
        $paperTypeAttribute = $XMLNode->getAttribute('pdx:paperType');
        if (!empty($paperTypeAttribute)) {
            $paperType = $paperTypeAttribute;
        }
        $this->_document->addSection($sectionType, $paperType, $values['layout']);
    }

    /**
     * Parses the cloneWordContent XML element and generates the required PHPDocX code
     *
     * @access private
     * @param DOMNode $XMLNode
     * @return void
     */
    private function XMLAPICloneWordContent($XMLNode)
    {
        $location = $XMLNode->getAttribute('pdx:location');
        if (!$location) {
            $location = 'after';
        }

        $forceAppend = $XMLNode->getAttribute('pdx:forceAppend');
        if (!$forceAppend) {
            $forceAppend = false;
        } else {
            $forceAppend = (bool)$forceAppend;
        }

        $childNodes = $XMLNode->childNodes;
        $referenceNode = $this->XML2Array($childNodes);
        $referenceNodeToBeCloned = $referenceNode['referenceToBeCloned'];
        $referenceNodeTo = $referenceNode['referenceNodeTo'];

        if (!is_array($referenceNodeToBeCloned)) {
            if ($childNodes[0]->getAttribute('pdx:customQuery')) {
                $referenceNodeToBeCloned = array('customQuery' => $referenceNodeToBeCloned);
            } else {
                $referenceNodeToBeCloned = array('type' => $referenceNodeToBeCloned);
            }
        }

        if (isset($referenceNodeToBeCloned['occurrence'])) {
            if (is_numeric($referenceNodeToBeCloned['occurrence'])) {
                $referenceNodeToBeCloned['occurrence'] = (int)$referenceNodeToBeCloned['occurrence'];
            }
        }

        if (isset($referenceNodeToBeCloned['attribute']) && is_array($referenceNodeToBeCloned['attribute'])) {
            $attribute = array();

            if (isset($referenceNodeToBeCloned['attribute']['dataTag']) && !empty($referenceNodeToBeCloned['attribute']['dataTag'])) {
                $referenceNodeToBeCloned['referenceNode']['attributes'] = array(
                    $referenceNodeToBeCloned['attribute']['dataTag'] => array(
                        $referenceNodeToBeCloned['attribute']['dataAttribute'] => $referenceNodeToBeCloned['attribute']['dataValue'],
                    )
                );
            } else {
                $referenceNodeToBeCloned['referenceNode']['attributes'] = array(
                    $referenceNodeToBeCloned['attribute']['dataAttribute'] => $referenceNodeToBeCloned['attribute']['dataValue'],
                );
            }
        }

        if (!is_array($referenceNodeTo)) {
            if ($childNodes[1]->getAttribute('pdx:customQuery')) {
                $referenceNodeTo = array('customQuery' => $referenceNodeTo);
            } else {
                $referenceNodeTo['type'] = array('type' => $referenceNodeTo);
            }
        }

        if (isset($referenceNodeTo['occurrence'])) {
            if (is_numeric($referenceNodeTo['occurrence'])) {
                $referenceNodeTo['occurrence'] = (int)$referenceNodeTo['occurrence'];
            }
        }

        if (isset($referenceNodeTo['attribute']) && is_array($referenceNodeToBeCloned['attribute'])) {
            $attribute = array();

            if (isset($referenceNodeTo['attribute']['dataTag']) && !empty($referenceNodeTo['attribute']['dataTag'])) {
                $referenceNodeTo['referenceNode']['attributes'] = array(
                    $referenceNodeTo['attribute']['dataTag'] => array(
                        $referenceNodeTo['attribute']['dataAttribute'] => $referenceNodeTo['attribute']['dataValue'],
                    )
                );
            } else {
                $referenceNodeTo['referenceNode']['attributes'] = array(
                    $referenceNodeTo['attribute']['dataAttribute'] => $referenceNodeTo['attribute']['dataValue'],
                );
            }
        }

        $this->_document->cloneWordContent($referenceNodeToBeCloned, $referenceNodeTo, $location, $forceAppend);
    }

    /**
     * Parses the customizeWordContent XML element and generates the required PHPDocX code
     *
     * @access private
     * @param DOMNode $XMLNode
     * @return void
     */
    private function XMLAPICustomizeWordContent($XMLNode)
    {
        $childNodes = $XMLNode->childNodes;
        $values = $this->XML2Array($childNodes);

        $referenceNode = $values['referenceNode'];
        $options = $values['options'];

        if (!is_array($referenceNode)) {
            if ($childNodes[0]->getAttribute('pdx:customQuery')) {
                $referenceNode['referenceNode'] = array('customQuery' => $referenceNode);
            } else {
                $referenceNode['referenceNode'] = array('type' => $referenceNode);
            }
        }

        if (isset($referenceNode['occurrence'])) {
            if (is_numeric($referenceNode['occurrence'])) {
                $referenceNode['occurrence'] = (int)$referenceNode['occurrence'];
            }
        }

        if (isset($referenceNode['attribute']) && is_array($referenceNode['attribute'])) {
            $attribute = array();

            if (isset($referenceNode['attribute']['dataTag']) && !empty($referenceNode['attribute']['dataTag'])) {
                $referenceNode['referenceNode']['attributes'] = array(
                    $referenceNode['attribute']['dataTag'] => array(
                        $referenceNode['attribute']['dataAttribute'] => $referenceNode['attribute']['dataValue'],
                    )
                );
            } else {
                $referenceNode['referenceNode']['attributes'] = array(
                    $referenceNode['attribute']['dataAttribute'] => $referenceNode['attribute']['dataValue'],
                );
            }
        }

        if (isset($referenceNode['referencePositions']) || isset($referenceNode['referenceSections']) || isset($referenceNode['referenceTypes'])) {
            $reference = array();
            if (isset($referenceNode['referencePositions'])) {
                $referencePositions = explode(',', $referenceNode['referencePositions']);
                $reference['positions'] = $referencePositions;
            }
            if (isset($referenceNode['referenceSections'])) {
                $referenceSections = explode(',', $referenceNode['referenceSections']);
                $reference['sections'] = $referenceSections;
            }
            if (isset($referenceNode['referenceTypes'])) {
                $referenceTypes = explode(',', $referenceNode['referenceTypes']);
                $reference['types'] = $referenceTypes;
            }
            $referenceNode['reference'] = $reference;
        }

        foreach ($options as $key => $value) {
            switch ($key) {
                // bool values
                case 'bold':
                case 'caps':
                case 'italic':
                case 'pageBreakBefore':
                    $options[$key] = (bool)$value;
                    break;
                // int values
                case 'borderWidth':
                case 'cellSpacing':
                case 'fontSize':
                case 'depthLevel':
                case 'gutter':
                case 'headingLevel':
                case 'height':
                case 'indent':
                case 'lineSpacing':
                case 'marginBottom':
                case 'marginFooter':
                case 'marginHeader':
                case 'marginLeft':
                case 'marginRight':
                case 'marginTop':
                case 'numberCols':
                case 'spacingBottom':
                case 'spacingLeft':
                case 'spacingRight':
                case 'spacingTop':
                case 'width':
                    $options[$key] = (int)$value;
                    break;
                // special values
                case 'border':
                    unset($options['border']);
                    $borderProps = $this->XMLBorderChilds2Array($XMLNode, $props);
                    $options = array_merge($options, $borderProps);
                    break;
                case 'columnWidths':
                    $columnWidths = array();
                    $columnValues = $XMLNode->getElementsByTagName('column');

                    foreach ($columnValues as $columnValue) {
                        $columnWidths['columnWidths'][] = (int)$columnValue->getAttribute('pdx:width');
                    }
                    $options = array_merge($options, $columnWidths);
                    break;
                default:
                    break;
            }
        }

        $this->_document->customizeWordContent($referenceNode, $options);
    }
    
    /**
     * Parses the XMLAPICreateListStyle XML element and generates the 
     * required PHPDocX code
     *
     * @access private
     * @param DOMNode $XMLNode
     * @return void
     */
    private function XMLAPICreateListStyle($XMLNode)
    {
        $name = $XMLNode->getAttribute('pdx:name');
        $childNodes = $XMLNode->childNodes;
        $values = array();
        foreach ($childNodes as $child) {
            $data = $this->XML2ArraySingleNode($child);
            $values[] = $data['item'];
        }
        $this->_document->createListStyle($name, $values);
    }

    /**
     * Parses the XMLAPICreateCharacterStyle XML element and generates the 
     * required PHPDocX code
     *
     * @access private
     * @param DOMNode $XMLNode
     * @return void
     */
    private function XMLAPICreateCharacterStyle($XMLNode)
    {
        $name = $XMLNode->getAttribute('pdx:name');
        $characterStyle = $XMLNode->firstChild;
        $values = array();
        $values = $this->parseCharacterStyles($characterStyle);
        $this->_document->createCharacterStyle($name, $values);
    }

    /**
     * Parses the XMLAPICreateParagraphStyle XML element and generates the 
     * required PHPDocX code
     *
     * @access private
     * @param DOMNode $XMLNode
     * @return void
     */
    private function XMLAPICreateParagraphStyle($XMLNode)
    {
        $name = $XMLNode->getAttribute('pdx:name');
        $paragraphStyle = $XMLNode->firstChild;
        $values = array();
        $values = $this->parseParagraphStyles($paragraphStyle);
        $this->_document->createParagraphStyle($name, $values);
    }
    
    /**
     * Parses the XMLAPIImportHeadersAndFooters XML element and generates 
     * the required PHPDocX code
     *
     * @access private
     * @param DOMNode $XMLNode
     * @return void
     */
    private function XMLAPIImportHeadersAndFooters($XMLNode)
    {
        $type = 'headerAndFooter';
        $src = $XMLNode->getAttribute('pdx:src');
        $typeAttribute = $XMLNode->getAttribute('pdx:type');
        if (empty($typeAttribute)) {
          $typeAttribute = 'headerAndFooter';
        }
        $this->_document->importHeadersAndFooters($src, $typeAttribute);
    }
    
    /**
     * Parses the importListStyle XML element and generates the required 
     * PHPDocX code
     *
     * @access private
     * @param DOMNode $XMLNode
     * @return void
     */
    private function XMLAPIImportListStyle($XMLNode)
    {
        $src = $XMLNode->getAttribute('pdx:src');
        $id = $XMLNode->getAttribute('pdx:id');
        $name = $XMLNode->getAttribute('pdx:name');

        $this->_document->importListStyle($src, $id, $name);
    }

    /**
     * Parses the importStyles XML element and generates the required 
     * PHPDocX code
     *
     * @access private
     * @param DOMNode $XMLNode
     * @return void
     */
    private function XMLAPIImportStyles($XMLNode)
    {
        $type = 'replace';
        $styleIdentifier= 'styleName';
        $src = $XMLNode->getAttribute('pdx:src');
        $typeAttribute = $XMLNode->getAttribute('pdx:importType');
        if (!empty($typeAttribute)) {
          $type = $typeAttribute;
        }
        $identifierAttribute = $XMLNode->getAttribute('pdx:styleIdentifier');
        if (!empty($identifierAttribute)) {
          $styleIdentifier = $identifierAttribute;
        }
        $styles = array();
        $styleNodes = $XMLNode->getElementsByTagName('style');
        foreach ($styleNodes as $style) {
            $styles[] = $style->getAttribute('pdx:name');
        }

        $this->_document->importStyles($src, $type, $styles, $styleIdentifier);   
    }

    /**
     * Parses the insertWordFragment XML element and generates the required PHPDocX code
     *
     * @access private
     * @param DOMNode $XMLNode
     * @return void
     */
    private function XMLAPIInsertWordFragment($XMLNode)
    {
        $wordFragmentName = $XMLNode->getAttribute('pdx:wordFragmentName');
        $content = $this->_wordFragments['wordFragment_' . $wordFragmentName];

        $location = $XMLNode->getAttribute('pdx:location');
        if (!$location) {
            $location = 'after';
        }

        $forceAppend = $XMLNode->getAttribute('pdx:forceAppend');
        if (!$forceAppend) {
            $forceAppend = false;
        } else {
            $forceAppend = (bool)$forceAppend;
        }

        $childNodes = $XMLNode->childNodes;
        $referenceNode = $this->XML2Array($childNodes);

        if (!is_array($referenceNode)) {
            if ($childNodes[0]->getAttribute('pdx:customQuery')) {
                $referenceNode['referenceNode'] = array('customQuery' => $referenceNode);
            } else {
                $referenceNode['referenceNode'] = array('type' => $referenceNode);
            }
        }

        if (isset($referenceNode['referenceNode']['occurrence'])) {
            if (is_numeric($referenceNode['referenceNode']['occurrence'])) {
                $referenceNode['referenceNode']['occurrence'] = (int)$referenceNode['referenceNode']['occurrence'];
            }
        }

        if (isset($referenceNode['attribute']) && is_array($referenceNode['attribute'])) {
            $attribute = array();

            if (isset($referenceNode['attribute']['dataTag']) && !empty($referenceNode['attribute']['dataTag'])) {
                $referenceNode['referenceNode']['attributes'] = array(
                    $referenceNode['attribute']['dataTag'] => array(
                        $referenceNode['attribute']['dataAttribute'] => $referenceNode['attribute']['dataValue'],
                    )
                );
            } else {
                $referenceNode['referenceNode']['attributes'] = array(
                    $referenceNode['attribute']['dataAttribute'] => $referenceNode['attribute']['dataValue'],
                );
            }
        }

        if (isset($referenceNode['referenceNode']['referencePositions']) || isset($referenceNode['referenceNode']['referenceSections']) || isset($referenceNode['referenceNode']['referenceTypes'])) {
            $reference = array();
            if (isset($referenceNode['referenceNode']['referencePositions'])) {
                $referencePositions = explode(',', $referenceNode['referenceNode']['referencePositions']);
                $reference['positions'] = $referencePositions;
            }
            if (isset($referenceNode['referenceNode']['referenceSections'])) {
                $referenceSections = explode(',', $referenceNode['referenceNode']['referenceSections']);
                $reference['sections'] = $referenceSections;
            }
            if (isset($referenceNode['referenceNode']['referenceTypes'])) {
                $referenceTypes = explode(',', $referenceNode['referenceNode']['referenceTypes']);
                $reference['types'] = $referenceTypes;
            }
            $referenceNode['referenceNode']['reference'] = $reference;
        }

        $this->_document->insertWordFragment($content, $referenceNode['referenceNode'], $location, $forceAppend);
    }
    
    /**
     * Parses the modifyPageLayout XML element and generates the required 
     * PHPDocX code
     *
     * @access private
     * @param DOMNode $XMLNode
     * @return void
     */
    private function XMLAPIModifyPageLayout($XMLNode)
    {
        $paperType = 'letter';
        $childNodes = $XMLNode->childNodes;
        $values = $this->XML2Array($childNodes);
        $paperTypeAttribute = $XMLNode->getAttribute('pdx:paperType');
        if (!empty($paperTypeAttribute)) {
            $paperType = $paperTypeAttribute;
        }
        if (!empty($values['layout']['sectionNumbers'])) {
            $values['layout']['sectionNumbers'] = explode(',', $values['layout']['sectionNumbers']);
        }

        $this->_document->modifyPageLayout($paperType, $values['layout']);
    }

    /**
     * Parses the moveWordContent XML element and generates the required PHPDocX code
     *
     * @access private
     * @param DOMNode $XMLNode
     * @return void
     */
    private function XMLAPIMoveWordContent($XMLNode)
    {
        $wordFragmentName = $XMLNode->getAttribute('pdx:wordFragmentName');
        $content = $this->_wordFragments['wordFragment_' . $wordFragmentName];

        $location = $XMLNode->getAttribute('pdx:location');
        if (!$location) {
            $location = 'after';
        }

        $forceAppend = $XMLNode->getAttribute('pdx:forceAppend');
        if (!$forceAppend) {
            $forceAppend = false;
        } else {
            $forceAppend = (bool)$forceAppend;
        }

        $childNodes = $XMLNode->childNodes;
        $referenceNode = $this->XML2Array($childNodes);
        $referenceNodeFrom = $referenceNode['referenceNodeFrom'];
        $referenceNodeTo = $referenceNode['referenceNodeTo'];

        if (!is_array($referenceNodeFrom)) {
            if ($childNodes[0]->getAttribute('pdx:customQuery')) {
                $referenceNodeFrom = array('customQuery' => $referenceNodeFrom);
            } else {
                $referenceNodeFrom = array('type' => $referenceNodeFrom);
            }
        }

        if (isset($referenceNodeFrom['occurrence'])) {
            if (is_numeric($referenceNodeFrom['occurrence'])) {
                $referenceNodeFrom['occurrence'] = (int)$referenceNodeFrom['occurrence'];
            }
        }

        if (isset($referenceNodeFrom['attribute']) && is_array($referenceNodeFrom['attribute'])) {
            $attribute = array();

            if (isset($referenceNodeFrom['attribute']['dataTag']) && !empty($referenceNodeFrom['attribute']['dataTag'])) {
                $referenceNodeFrom['attributes'] = array(
                    $referenceNodeFrom['attribute']['dataTag'] => array(
                        $referenceNodeFrom['attribute']['dataAttribute'] => $referenceNodeFrom['attribute']['dataValue'],
                    )
                );
            } else {
                $referenceNodeFrom['attributes'] = array(
                    $referenceNodeFrom['attribute']['dataAttribute'] => $referenceNodeFrom['attribute']['dataValue'],
                );
            }
        }

        if (!is_array($referenceNodeTo)) {
            if ($childNodes[1]->getAttribute('pdx:customQuery')) {
                $referenceNodeTo = array('customQuery' => $referenceNodeFrom);
            } else {
                $referenceNodeTo = array('type' => $referenceNodeTo);
            }
        }

        if (isset($referenceNodeTo['occurrence'])) {
            if (is_numeric($referenceNodeTo['occurrence'])) {
                $referenceNodeTo['occurrence'] = (int)$referenceNodeTo['occurrence'];
            }
        }

        if (isset($referenceNodeTo['attribute']) && is_array($referenceNodeTo['attribute'])) {
            $attribute = array();

            if (isset($referenceNodeTo['attribute']['dataTag']) && !empty($referenceNodeTo['attribute']['dataTag'])) {
                $referenceNodeTo['attributes'] = array(
                    $referenceNodeTo['attribute']['dataTag'] => array(
                        $referenceNodeTo['attribute']['dataAttribute'] => $referenceNodeTo['attribute']['dataValue'],
                    )
                );
            } else {
                $referenceNodeTo['attributes'] = array(
                    $referenceNodeTo['attribute']['dataAttribute'] => $referenceNodeTo['attribute']['dataValue'],
                );
            }
        }

        $this->_document->moveWordContent($referenceNodeFrom, $referenceNodeTo, $location, $forceAppend);
    }

    /**
     * Parses the parseStyles XML element and generates the required 
     * PHPDocX code
     *
     * @access private
     * @param DOMNode $XMLNode
     * @return void
     */
    private function XMLAPIParseStyles($XMLNode)
    {
        $this->_document->parseStyles();
    }

    /**
     * Parses the rejectTracking XML element and generates the required 
     * PHPDocX code
     *
     * @access private
     * @param DOMNode $XMLNode
     * @return void
     */
    private function XMLAPIRejectTracking($XMLNode)
    {
        $childNodes = $XMLNode->childNodes;
        $referenceNode = $this->XML2Array($childNodes);

        if (!is_array($referenceNode)) {
            if ($childNodes[0]->getAttribute('pdx:customQuery')) {
                $referenceNode['referenceNode'] = array('customQuery' => $referenceNode);
            } else {
                $referenceNode['referenceNode'] = array('type' => $referenceNode);
            }
        }

        if (isset($referenceNode['referenceNode']['occurrence'])) {
            if (is_numeric($referenceNode['referenceNode']['occurrence'])) {
                $referenceNode['referenceNode']['occurrence'] = (int)$referenceNode['referenceNode']['occurrence'];
            }
        }

        if (isset($referenceNode['attribute']) && is_array($referenceNode['attribute'])) {
            $attribute = array();

            if (isset($referenceNode['attribute']['dataTag']) && !empty($referenceNode['attribute']['dataTag'])) {
                $referenceNode['referenceNode']['attributes'] = array(
                    $referenceNode['attribute']['dataTag'] => array(
                        $referenceNode['attribute']['dataAttribute'] => $referenceNode['attribute']['dataValue'],
                    )
                );
            } else {
                $referenceNode['referenceNode']['attributes'] = array(
                    $referenceNode['attribute']['dataAttribute'] => $referenceNode['attribute']['dataValue'],
                );
            }
        }
        $referenceNode = $referenceNode['referenceNode'];
        
        $this->_document->rejectTracking($referenceNode);
    }

    /**
     * Parses the removeFooters XML element and generates the required 
     * PHPDocX code
     *
     * @access private
     * @param DOMNode $XMLNode
     * @return void
     */
    private function XMLAPIRemoveFooters($XMLNode)
    {
        $this->_document->removeFooters();
    }

    /**
     * Parses the removeHeaders XML element and generates the required 
     * PHPDocX code
     *
     * @access private
     * @param DOMNode $XMLNode
     * @return void
     */
    private function XMLAPIRemoveHeaders($XMLNode)
    {
        $this->_document->removeHeaders();
    }

    /**
     * Parses the removeWordContent XML element and generates the required PHPDocX code
     *
     * @access private
     * @param DOMNode $XMLNode
     * @return void
     */
    private function XMLAPIRemoveWordContent($XMLNode)
    {
        $childNodes = $XMLNode->childNodes;
        $referenceNode = $this->XML2Array($childNodes);

        if (!is_array($referenceNode)) {
            if ($childNodes[0]->getAttribute('pdx:customQuery')) {
                $referenceNode['referenceNode'] = array('customQuery' => $referenceNode);
            } else {
                $referenceNode['referenceNode'] = array('type' => $referenceNode);
            }
        }

        if (isset($referenceNode['referenceNode']['occurrence'])) {
            if (is_numeric($referenceNode['referenceNode']['occurrence'])) {
                $referenceNode['referenceNode']['occurrence'] = (int)$referenceNode['referenceNode']['occurrence'];
            }
        }

        if (isset($referenceNode['attribute']) && is_array($referenceNode['attribute'])) {
            $attribute = array();

            if (isset($referenceNode['attribute']['dataTag']) && !empty($referenceNode['attribute']['dataTag'])) {
                $referenceNode['referenceNode']['attributes'] = array(
                    $referenceNode['attribute']['dataTag'] => array(
                        $referenceNode['attribute']['dataAttribute'] => $referenceNode['attribute']['dataValue'],
                    )
                );
            } else {
                $referenceNode['referenceNode']['attributes'] = array(
                    $referenceNode['attribute']['dataAttribute'] => $referenceNode['attribute']['dataValue'],
                );
            }
        }

        if (isset($referenceNode['referenceNode']['referencePositions']) || isset($referenceNode['referenceNode']['referenceSections']) || isset($referenceNode['referenceNode']['referenceTypes'])) {
            $reference = array();
            if (isset($referenceNode['referenceNode']['referencePositions'])) {
                $referencePositions = explode(',', $referenceNode['referenceNode']['referencePositions']);
                $reference['positions'] = $referencePositions;
            }
            if (isset($referenceNode['referenceNode']['referenceSections'])) {
                $referenceSections = explode(',', $referenceNode['referenceNode']['referenceSections']);
                $reference['sections'] = $referenceSections;
            }
            if (isset($referenceNode['referenceNode']['referenceTypes'])) {
                $referenceTypes = explode(',', $referenceNode['referenceNode']['referenceTypes']);
                $reference['types'] = $referenceTypes;
            }
            $referenceNode['referenceNode']['reference'] = $reference;
        }

        $this->_document->removeWordContent($referenceNode['referenceNode']);
    }

    /**
     * Parses the replaceWordContent XML element and generates the required PHPDocX code
     *
     * @access private
     * @param DOMNode $XMLNode
     * @return void
     */
    private function XMLAPIReplaceWordContent($XMLNode)
    {
        $wordFragmentName = $XMLNode->getAttribute('pdx:wordFragmentName');
        $content = $this->_wordFragments['wordFragment_' . $wordFragmentName];

        $location = $XMLNode->getAttribute('pdx:location');
        if (!$location) {
            $location = 'after';
        }

        $forceAppend = $XMLNode->getAttribute('pdx:forceAppend');
        if (!$forceAppend) {
            $forceAppend = false;
        } else {
            $forceAppend = (bool)$forceAppend;
        }

        $childNodes = $XMLNode->childNodes;
        $referenceNode = $this->XML2Array($childNodes);

        if (!is_array($referenceNode)) {
            if ($childNodes[0]->getAttribute('pdx:customQuery')) {
                $referenceNode['referenceNode'] = array('customQuery' => $referenceNode);
            } else {
                $referenceNode['referenceNode'] = array('type' => $referenceNode);
            }
        }

        if (isset($referenceNode['referenceNode']['occurrence'])) {
            if (is_numeric($referenceNode['referenceNode']['occurrence'])) {
                $referenceNode['referenceNode']['occurrence'] = (int)$referenceNode['referenceNode']['occurrence'];
            }
        }

        if (isset($referenceNode['referenceNode']['referencePositions']) || isset($referenceNode['referenceNode']['referenceSections']) || isset($referenceNode['referenceNode']['referenceTypes'])) {
            $reference = array();
            if (isset($referenceNode['referenceNode']['referencePositions'])) {
                $referencePositions = explode(',', $referenceNode['referenceNode']['referencePositions']);
                $reference['positions'] = $referencePositions;
            }
            if (isset($referenceNode['referenceNode']['referenceSections'])) {
                $referenceSections = explode(',', $referenceNode['referenceNode']['referenceSections']);
                $reference['sections'] = $referenceSections;
            }
            if (isset($referenceNode['referenceNode']['referenceTypes'])) {
                $referenceTypes = explode(',', $referenceNode['referenceNode']['referenceTypes']);
                $reference['types'] = $referenceTypes;
            }
            $referenceNode['referenceNode']['reference'] = $reference;
        }

        $this->_document->replaceWordContent($content, $referenceNode['referenceNode'], $location, $forceAppend);
    }
    
    /**
     * Parses the setBackgroundColor XML element and generates the required 
     * PHPDocX code
     *
     * @access private
     * @param DOMNode $XMLNode
     * @return void
     */
    private function XMLAPISetBackgroundColor($XMLNode)
    {
        $color = $XMLNode->getAttribute('pdx:value');
        $this->_document->setBackgroundColor($color);
    }
    
    /**
     * Parses the addBookmark XML element and generates the required 
     * PHPDocX code
     *
     * @access private
     * @param DOMNode $XMLNode
     * @param mixed $contentTarget
     * @return void
     */
    private function XMLAPIAddBookmark($XMLNode, $contentTarget)
    {
        $type = $XMLNode->getAttribute('pdx:type');
        $name = $XMLNode->getAttribute('pdx:name');
        $contentTarget->addBookmark(array('type' => $type, 'name' => $name));
    }
    
    /**
     * Parses the addBreak XML element and generates the required PHPDocX code
     *
     * @access private
     * @param DOMNode $XMLNode
     * @param mixed $contentTarget
     * @return void
     */
    private function XMLAPIAddBreak($XMLNode, $contentTarget)
    {
        $type = 'line';
        $number = 1;
        $typeAttribute = $XMLNode->getAttribute('pdx:type');
        if (!empty($typeAttribute)) {
            $type = $typeAttribute;
        }
        $numberAttribute = $XMLNode->getAttribute('pdx:number');
        if (!empty($numberAttribute)) {
            $number = (int) $numberAttribute;
        }
        $contentTarget->addBreak(array('type' => $type, 'number' => $number));
    }
    
    /**
     * Parses the addChart XML element and generates the required PHPDocX code
     *
     * @access private
     * @param DOMNode $XMLNode
     * @param mixed $contentTarget
     * @return void
     */
    private function XMLAPIAddChart($XMLNode, $contentTarget)
    {
        // parse properties
        $childNodes = $XMLNode->childNodes;
        $values['type'] = $XMLNode->getAttribute('pdx:chartType');
        // parse the data
        $data = array();
        // extract legends
        $legendNodes = $XMLNode->getElementsByTagName('seriesLegend');
        if ($legendNodes->length > 0) {
            $legends = array();
            $i = 0;
            foreach($legendNodes as $legend){
                $values['data']['legend'][$i] = $legend->getAttribute('pdx:value');
                $i++;
            }
        }

        // extract the actual data
        $i = 0;
        $chartDataNode = $XMLNode->getElementsByTagName('chartData')->item(0);
        if ($chartDataNode) {
            $dataId = $chartDataNode->getAttribute('pdx:dataId');
            if (!empty($dataId)) {
                $data = $this->dataQuery($dataId, 'chart', $data);
            } else {
                $seriesNodes = $chartDataNode->getElementsByTagName('series');
                $i = 0;
                foreach($seriesNodes as $series){
                    $seriesName = $series->getAttribute('pdx:name');
                    if ($seriesName !== null) {
                        $values['data']['data'][$i]['name'] = $seriesName;
                    }
                    $dataValueNodes = $series->childNodes;
                    foreach($dataValueNodes as $dataValueNode){
                        $values['data']['data'][$i]['values'][] = $dataValueNode->getAttribute('pdx:value');
                    }
                    $i++;
                }
            }
        }
        $valuesProperties = $this->XML2Array($childNodes);
        unset($valuesProperties['data']);
        $values = array_merge($values, $valuesProperties);
        if (isset($values['externalXLSX'])) {
            $values['externalXLSX'] = array('src'=> $values['externalXLSX']);
        }

        // theme chart
        if (isset($values['theme'])) {
            $chartDataTheme = $XMLNode->getElementsByTagName('theme');
            if ($chartDataTheme->length > 0) {
                $serRgbColorsTheme = $chartDataTheme->item(0)->getElementsByTagName('serRgbColors');
                if ($serRgbColorsTheme->length > 0 && $serRgbColorsTheme->item(0)->childNodes->length > 0) {
                    // overwrite the current serRgbColors keeping data and null values
                    $newSerRgbColorsTheme = array();
                    foreach ($serRgbColorsTheme->item(0)->childNodes as $serRgbColorTheme) {
                        $newSerRgbColorsTheme[] = (empty($serRgbColorTheme->getAttribute('pdx:value'))) ? null : $serRgbColorTheme->getAttribute('pdx:value');
                    }
                    $values['theme']['serRgbColors'] = $newSerRgbColorsTheme;
                }

                $valueRgbColorsTheme = $chartDataTheme->item(0)->getElementsByTagName('valueRgbColors');
                if ($valueRgbColorsTheme->length > 0 && $valueRgbColorsTheme->item(0)->childNodes->length > 0) {
                    // overwrite the current valueRgbColors keeping data and null values
                    $newValueRgbColorsTheme = array();
                    foreach ($valueRgbColorsTheme->item(0)->childNodes as $valueRgbColorTheme) {
                        if ($valueRgbColorTheme->childNodes->length > 0) {
                            $valueRgbColorThemeNew = array();
                            $valueRgbColorsDataValuesTheme = $valueRgbColorTheme->getElementsByTagName('dataValue');
                            if ($valueRgbColorsDataValuesTheme->length > 0) {
                                foreach ($valueRgbColorsDataValuesTheme as $valueRgbColorsDataValueTheme) {
                                    $valueRgbColorThemeNew[] = (empty($valueRgbColorsDataValueTheme->getAttribute('pdx:value'))) ? null : $valueRgbColorsDataValueTheme->getAttribute('pdx:value');
                                }
                            } else {
                                $valueRgbColorThemeNew[] = null;
                            }
                            $newValueRgbColorsTheme[] = $valueRgbColorThemeNew;
                        }
                    }

                    $values['theme']['valueRgbColors'] = $newValueRgbColorsTheme;
                }
            }
        }

        $contentTarget->addChart($values);
    }
    
    /**
     * Parses the addComment XML element and generates the required PHPDocX code
     *
     * @access private
     * @param DOMNode $XMLNode
     * @param mixed $contentTarget
     * @return void
     */
    private function XMLAPIAddComment($XMLNode, $contentTarget)
    {
        $values = array();
        $attributes = $XMLNode->attributes;
        $values = $this->XMLAttributes2Array($attributes, 'array');
        $textDocument = $XMLNode->getElementsByTagName('textDocument')->item(0);
        $values['textDocument'] = $this->fetchData($textDocument, 'text');
        $values['textDocument'] = $this->parseStyles('paragraphStyle', $XMLNode, $values['textDocument']);

        $textComment = $XMLNode->getElementsByTagName('textComment')->item(0);

        $data = $textComment->getElementsByTagName('data');
        if ($data->item(0)->hasAttribute('pdx:dataType')) {
            $dataTypeAttribute = $data->item(0)->getAttribute('pdx:dataType');
        } else {
            $dataTypeAttribute = 'text';
        }
        $values['textComment'] = $this->fetchData($textComment, 'text');
        if ($dataTypeAttribute == 'wordFragment') {
            $values['textComment'] = $this->_wordFragments['wordFragment_' . $values['textComment']];
        } else {
            $values['textComment'] = $this->fetchData($textComment, 'text');
        }

        $contentTarget->addComment($values);
    }

    /**
     * Parses the addCrossReference XML element and generates the required 
     * PHPDocX code
     *
     * @access private
     * @param DOMNode $XMLNode
     * @param mixed $contentTarget
     * @return void
     */
    private function XMLAPIAddCrossReference($XMLNode, $contentTarget)
    {
        $type = $XMLNode->getAttribute('pdx:type');
        $referenceName = $XMLNode->getAttribute('pdx:referenceName');
        $value = $XMLNode->getAttribute('pdx:value');

        $contentTarget->addCrossReference($value, array('type' => $type, 'referenceName' => $referenceName));
    }
    
    /**
     * Parses the addDateAndHour XML element and generates the required 
     * PHPDocX code
     *
     * @access private
     * @param DOMNode $XMLNode
     * @param mixed $contentTarget
     * @return void
     */
    private function XMLAPIAddDateAndHour($XMLNode, $contentTarget)
    {
        $dateFormat = 'dd/MM/yyyy H:mm:ss';
        $dateFormatAttribute = $XMLNode->getAttribute('pdx:dateFormat');
        if(!empty($dateFormatAttribute)){
            $dateFormat = $dateFormatAttribute;
        }
        $values = $this->parseStyles('paragraphStyle', $XMLNode);
        $values['dateFormat'] = $dateFormat;
        $contentTarget->addDateAndHour($values);
    }
    
    /**
     * Parses the addExternalFile XML element and generates the required PHPDocX code
     *
     * @access private
     * @param DOMNode $XMLNode
     * @param mixed $contentTarget
     * @return void
     */
    private function XMLAPIAddExternalFile($XMLNode, $contentTarget)
    {
        $options = $this->XMLAttributes2Array($XMLNode->attributes, 'array');
        $options['src'] = $options['source'];

        if (isset($options['matchSource'])) {
            $options['matchSource'] = (bool)$options['matchSource'];
        }

        if (isset($options['preprocess'])) {
            $options['preprocess'] = (bool)$options['preprocess'];
        }

        if (isset($options['firstMatch'])) {
            $options['firstMatch'] = (bool)$options['firstMatch'];
        }

        $contentTarget->addExternalFile($options);
    }
    
    /**
     * Parses the addEndnote XML element and generates the required PHPDocX code
     *
     * @access private
     * @param DOMNode $XMLNode
     * @param mixed $contentTarget
     * @return void
     */
    private function XMLAPIAddEndnote($XMLNode, $contentTarget)
    {
        $values = array();
        $textEndnote = $XMLNode->getElementsByTagName('textEndnote')->item(0);
        $values['textEndnote'] = $this->fetchData($textEndnote, 'text');
        $textDocument = $XMLNode->getElementsByTagName('textDocument')->item(0);
        $values['textDocument'] = $this->fetchData($textDocument, 'text');
        $values['textDocument'] = $this->parseStyles('paragraphStyle', $XMLNode, $values['textDocument']);
        $markNodes = $XMLNode->getElementsbyTagName('endnoteMark');
        if ($markNodes->length > 0) {
            $mark = $markNodes->item(0);
            $values['endnoteMark'] = $this->parseStyles('textRunStyle', $mark);
            $customMark = $mark->getAttribute('pdx:customMark');
            if (!empty($customMark)) {
                $values['endnoteMark']['customMark'] = $customMark;
            }
        }
        $referenceNodes = $XMLNode->getElementsbyTagName('referenceMark');
        if ($referenceNodes->length > 0) {
            $reference = $referenceNodes->item(0);
            $values['referenceMark'] = $this->parseStyles('textRunStyle', $reference);
        }
        $contentTarget->addEndnote($values);
    }
    
    /**
     * Parses the addFootnote XML element and generates the required 
     * PHPDocX code
     *
     * @access private
     * @param DOMNode $XMLNode
     * @param mixed $contentTarget
     * @return void
     */
    private function XMLAPIAddFootnote($XMLNode, $contentTarget)
    {
        $values = array();
        $textFootnote = $XMLNode->getElementsByTagName('textFootnote')->item(0);
        $values['textFootnote'] = $this->fetchData($textFootnote, 'text');
        $textDocument = $XMLNode->getElementsByTagName('textDocument')->item(0);
        $values['textDocument'] = $this->fetchData($textDocument, 'text');
        $values['textDocument'] = $this->parseStyles('paragraphStyle', $XMLNode, $values['textDocument']);
        $markNodes = $XMLNode->getElementsbyTagName('footnoteMark');
        if ($markNodes->length > 0) {
            $mark = $markNodes->item(0);
            $values['footnoteMark'] = $this->parseStyles('textRunStyle', $mark);
            $customMark = $mark->getAttribute('pdx:customMark');
            if (!empty($customMark)) {
                $values['footnoteMark']['customMark'] = $customMark;
            }
        }
        $referenceNodes = $XMLNode->getElementsbyTagName('referenceMark');
        if ($referenceNodes->length > 0) {
            $reference = $referenceNodes->item(0);
            $values['referenceMark'] = $this->parseStyles('textRunStyle', $reference);
        }
        $contentTarget->addFootnote($values);
    }
    
    /**
     * Parses the addFormElement XML element and generates the required 
     * PHPDocX code
     *
     * @access private
     * @param DOMNode $XMLNode
     * @param mixed $contentTarget
     * @return void
     */
    private function XMLAPIAddFormElement($XMLNode, $contentTarget)
    {
        $type = $XMLNode->getAttribute('pdx:formElementType');
        $defaultValue = $XMLNode->getAttribute('pdx:defaultValue');
        if (isset($defaultValue)) {
            $values['defaultValue'] = $defaultValue;
        }
        $selectOptionsNodes = $XMLNode->getElementsByTagName('options');
        if ($selectOptionsNodes->length > 0) {
            $values['selectOptions'] = array();
            $items = $selectOptionsNodes->item(0)->childNodes;
            foreach ($items as $item) {
                $values['selectOptions'][] = $item->nodeValue;
            }
        }
        $values = $this->parseStyles('paragraphStyle', $XMLNode, $values);
        $contentTarget->addFormElement($type, $values);
    }
    
    /**
     * Parses the addHeading XML element and generates the required PHPDocX code
     *
     * @access private
     * @param DOMNode $XMLNode
     * @param mixed $contentTarget
     * @return void
     */
    private function XMLAPIAddHeading($XMLNode, $contentTarget)
    {
        $level = $XMLNode->getAttribute('pdx:level');
        $text = $this->fetchData($XMLNode, 'text');
        $values = $this->parseStyles('paragraphStyle', $XMLNode);
        $contentTarget->addHeading($text, $level, $values);
    }
    
    /**
     * Parses the addHTML XML element and generates the required PHPDocX code
     *
     * @access private
     * @param DOMNode $XMLNode
     * @param mixed $contentTarget
     * @return void
     */
    private function XMLAPIAddHTML($XMLNode, $contentTarget)
    {
        $values= $this->fetchData($XMLNode, 'html');
        $contentTarget->addHTML($values);
    }
    
    /**
     * arses the addImage XML element and generates the required PHPDocX code
     *
     * @access private
     * @param DOMNode $XMLNode
     * @param mixed $contentTarget
     * @return void
     */
    private function XMLAPIAddImage($XMLNode, $contentTarget)
    {
        $values['src'] = $this->fetchData($XMLNode, 'text');
        $attributes = $XMLNode->attributes;
        $values = $this->XMLAttributes2Array($attributes, 'array', $values);
        
        $childNodes = $XMLNode->childNodes;
        foreach ($childNodes as $child) {
            $attributes = $child->attributes;
            $values = $this->XMLAttributes2Array($attributes, 'array', $values);
        }
        
        if (isset($values['borderWidth'])) {
            $values['borderWidth'] = (int)$values['borderWidth'];
        }
        if (isset($values['horizontalOffset'])) {
            $values['horizontalOffset'] = (int)$values['horizontalOffset'];
        }
        if (isset($values['scaling'])) {
            $values['scaling'] = (int)$values['scaling'];
        }
        if (isset($values['width'])) {
            $values['width'] = (int)$values['width'];
        }
        if (isset($values['height'])) {
            $values['height'] = (int)$values['height'];
        }
        if (isset($values['dpi'])) {
            $values['dpi'] = (int)$values['dpi'];
        }
        if (isset($values['spacingTop'])) {
            $values['spacingTop'] = (int)$values['spacingTop'];
        }
        if (isset($values['spacingBottom'])) {
            $values['spacingBottom'] = (int)$values['spacingBottom'];
        }
        if (isset($values['spacingLeft'])) {
            $values['spacingLeft'] = (int)$values['spacingLeft'];
        }
        if (isset($values['spacingRight'])) {
            $values['spacingRight'] = (int)$values['spacingRight'];
        }
        if (isset($values['textWrap'])) {
            $values['textWrap'] = (int)$values['textWrap'];
        }
        if (isset($values['value'])) {
            $values['textWrap'] = (int)$values['value'];
        }
        if (isset($values['verticalOffset'])) {
            $values['verticalOffset'] = (int)$values['verticalOffset'];
        }
        if (isset($values['showLabel']) || isset($values['captionValue'])) {
            $values['caption'] = array(
                'showLabel' => (bool)$values['showLabel'],
                'text' => $values['captionValue'],
            );

            if (isset($values['labelCaption'])) {
                $values['caption']['label'] = $values['labelCaption'];
            }

            if (isset($values['sizeCaption'])) {
                $values['caption']['sz'] = (int)$values['sizeCaption'];
            }

            if (isset($values['lineSpacingCaption'])) {
                $values['caption']['lineSpacing'] = (int)$values['lineSpacingCaption'];
            }

            if (isset($values['colorCaption'])) {
                $values['caption']['color'] = (int)$values['colorCaption'];
            }

            if (isset($values['styleNameCaption'])) {
                $values['caption']['styleName'] = $values['styleNameCaption'];
            }
        }

        $contentTarget->addImage($values);
    }
    
    /**
     * Parses the addLink XML element and generates the required PHPDocX code
     *
     * @access private
     * @param DOMNode $XMLNode
     * @param mixed $contentTarget
     * @return void
     */
    private function XMLAPIAddLink($XMLNode, $contentTarget)
    {
        $linkText = $XMLNode->getElementsByTagName('linkText')->item(0);
        $text = $this->fetchData($linkText, 'text');
        $linkURL = $XMLNode->getElementsByTagName('linkURL')->item(0);
        $values['url'] = $this->fetchData($linkURL, 'text');
        $values = $this->parseStyles('paragraphStyle', $XMLNode, $values);
        $contentTarget->addLink($text, $values);
    }
    
    /**
     * Parses the addList XML element and generates the required PHPDocX code
     *
     * @access private
     * @param DOMNode $XMLNode
     * @param mixed $contentTarget
     * @return void
     */
    private function XMLAPIAddList($XMLNode, $contentTarget)
    {
        // get list type
        if (ctype_digit($XMLNode->getAttribute('pdx:listType'))) {
            $listType = (int)$XMLNode->getAttribute('pdx:listType');
        } else {
            $listType = $XMLNode->getAttribute('pdx:listType');
        }

        if (empty($listType)) {
           $listType = 1; 
        }

        // parse the data
        $data = array();
        $mainListNode = $XMLNode->firstChild;
        $data = $this->parseList($mainListNode);
        $values = $this->parseStyles('textRunStyle', $XMLNode, $values);

        // get useWordFragmentStyles
        $useWordFragmentStyles = $XMLNode->getAttribute('pdx:useWordFragmentStyles');
        if (isset($useWordFragmentStyles)) {
            $values['useWordFragmentStyles'] = (bool)$useWordFragmentStyles;
        }

        // get pStyle
        $pStyle = $XMLNode->getAttribute('pdx:pStyle');
        if (isset($pStyle) && $pStyle != '') {
            $values['pStyle'] = $pStyle;
        }

        $contentTarget->addList($data, $listType, $values);
    }
    
    /**
     * Parses the addMathEquation XML element and generates the required 
     * PHPDocX code
     *
     * @access private
     * @param DOMNode $XMLNode
     * @param mixed $contentTarget
     * @return void
     */
    private function XMLAPIAddMathEquation($XMLNode, $contentTarget)
    {
        $eq = $this->fetchData($XMLNode, 'text');
        $type = $XMLNode->getAttribute('pdx:type');

        $options = $this->XMLAttributes2Array($XMLNode->attributes, 'array');

        $contentTarget->addMathEquation($eq, $type, $options);
    }
    
    /**
     * Parses the addMergeField XML element and generates the required 
     * PHPDocX code
     *
     * @access private
     * @param DOMNode $XMLNode
     * @param mixed $contentTarget
     * @return void
     */
    private function XMLAPIAddMergeField($XMLNode, $contentTarget)
    {
        $attributes = $XMLNode->attributes;
        $values = $this->XMLAttributes2Array($attributes, 'array');
        $name = $values['name'];
        $options = $this->parseStyles('paragraphStyle', $XMLNode);
        $contentTarget->addMergeField($name, $values, $options);
    }
    
    /**
     * Parses the addMHT XML element and generates the required PHPDocX code
     *
     * @access private
     * @param DOMNode $XMLNode
     * @param mixed $contentTarget
     * @return void
     */
    private function XMLAPIAddMHT($XMLNode, $contentTarget)
    {
        $src = $this->fetchData($XMLNode);
        $contentTarget->addMHT($src);
    }

    /**
     * Parses the addOnlineVideo XML element and generates the required 
     * PHPDocX code
     *
     * @access private
     * @param DOMNode $XMLNode
     * @param mixed $contentTarget
     * @return void
     */
    private function XMLAPIAddOnlineVideo($XMLNode, $contentTarget)
    {
        $attributes = $XMLNode->attributes;
        $values = $this->XMLAttributes2Array($attributes);
        if (is_array($values)) {
            $contentTarget->addOnlineVideo($values['src'], $values);
        } else {
            $contentTarget->addOnlineVideo($values);
        }
    }
    
    /**
     * Parses the addPageNumber XML element and generates the required 
     * PHPDocX code
     *
     * @access private
     * @param DOMNode $XMLNode
     * @param mixed $contentTarget
     * @return void
     */
    private function XMLAPIAddPageNumber($XMLNode, $contentTarget)
    {
        if (isset($values['defaultValue'])) {
            $values['defaultValue'] = (int)$values['defaultValue'];
        } else {
            $values['defaultValue'] = 1;
        }
        $numberType = 'numerical';
        $typeAttribute = $XMLNode->getAttribute('pdx:numberType');
        if (!empty($typeAttribute)) {
            $numberType = $typeAttribute;
        }
        $pStyle = $XMLNode->getAttribute('pdx:pStyle');
        if (!empty($pStyle)) {
            $values['pStyle'] = $pStyle;
        }
        $textAlign = $XMLNode->getAttribute('pdx:textAlign');
        if (!empty($textAlign)) {
            $values['textAlign'] = $textAlign;
        }

        $values = $this->parseStyles('paragraphStyle', $XMLNode, $values);
        $contentTarget->addPageNumber($numberType, $values);
    }

    /**
     * Parses the addPermProtection XML element and generates the required 
     * PHPDocX code
     *
     * @access private
     * @param DOMNode $XMLNode
     * @param mixed $contentTarget
     * @return void
     */
    private function XMLAPIAddPermProtection($XMLNode, $contentTarget)
    {
        $attributes = $XMLNode->attributes;
        $values = $this->XMLAttributes2Array($attributes);

        $contentTarget->addPermProtection($values);
    }
    
    /**
     * Parses the addRTF XML element and generates the required PHPDocX code
     *
     * @access private
     * @param DOMNode $XMLNode
     * @param mixed $contentTarget
     * @return void
     */
    private function XMLAPIAddRTF($XMLNode, $contentTarget)
    {
        $src = $this->fetchData($XMLNode);
        $contentTarget->addRTF($src);
    }
    
    /**
     * Parses the addShape XML element and generates the required PHPDocX code
     *
     * @access private
     * @param DOMNode $XMLNode
     * @param mixed $contentTarget
     * @return void
     */
    private function XMLAPIAddShape($XMLNode, $contentTarget)
    {
        $attributes = $XMLNode->attributes;
        $values = $this->XMLAttributes2Array($attributes);
        $type = $values;
        $childNodes = $XMLNode->childNodes;

        $values = $this->XML2Array($childNodes);

        $options = array();
        foreach($values as $key => $value){
            if (is_array($value)) {
                foreach ($value as $subkey => $subvalue) {
                    $options[$subkey] = $subvalue;
                }
            } else {
                $options[$key] = $value;
            }
        }

        if (isset($options['width'])) {
            $options['width'] = (int)$options['width'];
        }
        if (isset($options['height'])) {
            $options['height'] = (int)$options['height'];
        }
        if (isset($options['marginTop'])) {
            $options['marginTop'] = (int)$options['marginTop'];
        }
        if (isset($options['marginLeft'])) {
            $options['marginLeft'] = (int)$options['marginLeft'];
        }
        if (isset($options['strokeWidth'])) {
            $options['strokeWidth'] = (int)$options['strokeWidth'];
        }
        if (isset($options['points'])) {
            $options['points'] = (int)$options['points'];
        }
        if (isset($options['arcsize'])) {
            $options['arcsize'] = (int)$options['arcsize'];
        }
        if (isset($options['coordsize'])) {
            $options['coordsize'] = (int)$options['coordsize'];
        }

        $contentTarget->addShape($type, $options);
    }
    
    /**
     * Parses the addSimpleField XML element and generates the required 
     * PHPDocX code
     *
     * @access private
     * @param DOMNode $XMLNode
     * @param mixed $contentTarget
     * @return void
     */
    private function XMLAPIAddSimpleField($XMLNode, $contentTarget)
    {
        $attributes = $XMLNode->attributes;
        $values = $this->XMLAttributes2Array($attributes, 'array');
        $values = $this->parseStyles('paragraphStyle', $XMLNode, $values);
        $name = $values['fieldName'];
        $type = 'general';
        if (!empty($values['fieldType'])) {
            $type = $values['fieldType'];
        }
        $format = null;
        if (!empty($values['fieldFormat'])) {
            $format = $values['fieldFormat'];
        }
        $contentTarget->addSimpleField($name, $type, $format, $values);
    }
    
    /**
     * Parses the addStructuredDocumentTag XML element and generates the 
     * required PHPDocX code
     *
     * @access private
     * @param DOMNode $XMLNode
     * @param mixed $contentTarget
     * @return void
     */
    private function XMLAPIAddStructuredDocumentTag($XMLNode, $contentTarget)
    {
        $type = $XMLNode->getAttribute('pdx:sdtType');
        $childNodes = $XMLNode->childNodes;
        $values = $this->XML2Array($childNodes);
        unset($values['paragraphStyle']);
        $values = $this->parseStyles('paragraphStyle', $XMLNode, $values);
        if (!empty($values['listItems'])) {
            $values['listItems'] = array();
            $items = $XMLNode->getElementsByTagName('item');
            foreach ($items as $item) {
                $valuesItem = array(
                    $item->nodeValue,
                    $item->getAttribute('pdx:value')
                );
                $values['listItems'][] = $valuesItem;
            }
        }
        $contentTarget->addStructuredDocumentTag($type, $values);
    }
    
    /**
     * Parses the addTable XML element and generates the required PHPDocX code
     *
     * @access private
     * @param DOMNode $XMLNode
     * @param mixed $contentTarget
     * @return void
     */
    private function XMLAPIAddTable($XMLNode, $contentTarget)
    {
        $tableData = array();
        $tableProperties = array();
        $rowProperties = array();
        $childNodes = $XMLNode->childNodes;
        foreach ($childNodes as $child) {
            if ($child->nodeName == 'pdx:data') {
                $tableData = $this->parseTableData($child);
                if (isset($tableData['rowProperties'])) {
                    $rowProperties[] = $tableData['rowProperties'];
                    unset($tableData['rowProperties']);
                }
            } elseif ($child->nodeName == 'pdx:tableProperties') {
                $tableProperties = $this->parseTableProperties($child);
            }
        }

        if (isset($tableProperties['borderWidth'])) {
            $tableProperties['borderWidth'] = (int)$tableProperties['borderWidth'];
        }

        $contentTarget->addTable($tableData, $tableProperties, $rowProperties);
    }
    
    /**
     * Parses the addTableContents XML element and generates the required 
     * PHPDocX code
     *
     * @access private
     * @param DOMNode $XMLNode
     * @param mixed $contentTarget
     * @return void
     */
    private function XMLAPIAddTableContents($XMLNode, $contentTarget)
    {
        $attributes = $XMLNode->attributes;
        $options = $this->XMLAttributes2Array($attributes, 'array');
        if (!empty($options['stylesTOC'])) {
            $stylesTOC = $options['stylesTOC'];
        }
        $values = $this->parseStyles('paragraphStyle', $XMLNode);
        $legendNodes = $XMLNode->getElementsByTagName('legendText');
        if ($legendNodes->length > 0) {
            $values['text'] = $legendNodes->item(0)->nodeValue;
        }
        $contentTarget->addTableContents($options, $values, $stylesTOC);
    }

    /**
     * Parses the addTableFigures XML element and generates the required 
     * PHPDocX code
     *
     * @access private
     * @param DOMNode $XMLNode
     * @param mixed $contentTarget
     * @return void
     */
    private function XMLAPIAddTableFigures($XMLNode, $contentTarget)
    {
        $attributes = $XMLNode->attributes;
        $options = $this->XMLAttributes2Array($attributes, 'array');
        $values = $this->parseStyles('paragraphStyle', $XMLNode);
        $legendNodes = $XMLNode->getElementsByTagName('legendText');
        if ($legendNodes->length > 0) {
            $values['text'] = $legendNodes->item(0)->nodeValue;
        }
        $contentTarget->addTableFigures($options, $values);
    }
    
    /**
     * Parses the addText XML element and generates the required PHPDocX code
     *
     * @access private
     * @param DOMNode $XMLNode
     * @param mixed $contentTarget
     * @return void
     */
    private function XMLAPIAddText($XMLNode, $contentTarget)
    {
        $textRuns = array();
        $paragraphStyle = $this->parseStyles('paragraphStyle', $XMLNode);
        $runNodes = $XMLNode->getElementsByTagName('textRun');
        foreach ($runNodes as $run) {
            $content = array();
            $data = $run->getElementsByTagName('data');
            if ($data->item(0)->hasAttribute('pdx:dataType')) {
                $dataTypeAttribute = $data->item(0)->getAttribute('pdx:dataType');
            } else {
                $dataTypeAttribute = 'text';
            }

            $content['text'] = $data->item(0)->nodeValue;
            $textRunStyle = $run->getElementsByTagName('textRunStyle');
            $childNodes = $textRunStyle->item(0)->childNodes;

            if ($dataTypeAttribute == 'wordFragment') {
                $content = $this->_wordFragments['wordFragment_' . $content['text']];
            } else {
                if ($childNodes) {
                    $options = $this->XML2Array($childNodes);
                    if (is_array($options) && count($options) > 0) {
                        $content = $this->parseStyles('textRunStyle', $run, $content);
                    }
                }
            }
            $textRuns[] = $content;
        }

        $contentTarget->addText($textRuns, $paragraphStyle);
    }
    
    /**
     * Parses the addTextBox XML element and generates the required PHPDocX code
     *
     * @access private
     * @param DOMNode $XMLNode
     * @param mixed $contentTarget
     * @return void
     */
    private function XMLAPIAddTextBox($XMLNode, $contentTarget)
    {
        $content = $this->fetchData($XMLNode, 'text');
        $childNodes = $XMLNode->childNodes;
        $values = $this->XML2Array($childNodes);

        if (!empty($values['padding'])) {
            foreach ($values['padding'] as $key => $value) {
               $values[$key] = $value; 
            }
            unset($values['padding']);
        }
        $borderAttribute = $XMLNode->getAttribute('pdx:border');
        if (isset($borderAttribute)) {
            $values['border'] = (bool)$borderAttribute;
        }
        $contentTarget->addTextBox($content, $values);
    }

    /**
     * Parses the addWordML XML element and generates the required PHPDocX code
     *
     * @access private
     * @param DOMNode $XMLNode
     * @param mixed $contentTarget
     * @return void
     */
    private function XMLAPIAddWordML($XMLNode, $contentTarget)
    {
        $wordml = $this->fetchData($XMLNode, 'text');
        $contentTarget->addWordML($wordml);
    }
    
    /**
     * Parses the embedHTML XML element and generates the required PHPDocX code
     *
     * @access private
     * @param DOMNode $XMLNode
     * @param mixed $contentTarget
     * @return void
     */
    private function XMLAPIEmbedHTML($XMLNode, $contentTarget)
    {
        $attributes = $XMLNode->attributes;
        $options = $this->XMLAttributes2Array($attributes, 'array');

        if (isset($options['useHTMLExtended']) && $options['useHTMLExtended'] == 'true') {
            $options['useHTMLExtended'] = true;
        } else {
            $options['useHTMLExtended'] = false;
        }

        if (isset($options['customListStyles']) && $options['customListStyles'] == 'true') {
            $options['customListStyles'] = true;
        } else {
            $options['customListStyles'] = false;
        }

        if (isset($values['cssEntityDecode']) && $values['cssEntityDecode'] == 'true') {
            $values['cssEntityDecode'] = true;
        } else {
            $values['cssEntityDecode'] = false;
        }
        
        $html = $this->fetchData($XMLNode, 'text');
        $wordStylesNodes = $XMLNode->getElementsByTagName('wordStyles');
        if ($wordStylesNodes->length > 0) {
            $strict = $wordStylesNodes->item(0)->getAttribute('pdx:strict');
            if (!empty($strict)) {
                $options['strictWordStyles'] = $strict;
            }
            $childNodes = $wordStylesNodes->item(0)->childNodes;
            if ($childNodes->length > 0) {
                $options['wordStyles'] = array();
                $styles = array();
                foreach ($childNodes as $child) {
                    $styleName = $child->getAttribute('pdx:name');
                    $type = $child->getAttribute('pdx:styleType');
                    if ($type == 'id') {
                        $styles['#' . $child->nodeValue] = $styleName;
                    } elseif ($type == 'class') {
                        $styles['.' . $child->nodeValue] = $styleName;
                    } else {
                        $styles[$child->nodeValue] = $styleName;
                    }
                }
                $options['wordStyles'] = $styles;
            }
        }
        
        $contentTarget->embedHTML($html, $options);
    }

    /**
     * Parses the clearBlocks XML element and generates the required 
     * PHPDocX code
     *
     * @access private
     * @param DOMNode $XMLNode
     * @return void
     */
    private function XMLAPIEnableRepairMode($XMLnode)
    {
        $repairModeOptions = array();

        if ($data->item(0)->hasAttribute('pdx:lastParagraph')) {
            $repairModeOptions['lastParagraph'] = true;
        }
        if ($data->item(0)->hasAttribute('pdx:lists')) {
            $repairModeOptions['lists'] = true;
        }
        if ($data->item(0)->hasAttribute('pdx:tables')) {
            $repairModeOptions['tables'] = true;
        }

        $this->_document->enableRepairMode($repairModeOptions);
    }

    /**
     * Parses the clearBlocks XML element and generates the required 
     * PHPDocX code
     *
     * @access private
     * @param DOMNode $XMLNode
     * @return void
     */
    private function XMLAPIClearBlocks($XMLnode, $contentTarget)
    {
        $this->_document->clearBlocks();
    }

    /**
     * Parses the cloneBlock XML element and generates the required 
     * PHPDocX code
     *
     * @access private
     * @param DOMNode $XMLNode
     * @return void
     */
    private function XMLAPICloneBlock($XMLNode)
    {
        $name = $XMLNode->getAttribute('pdx:variableName');
        $occurrence = 1;
        if ($XMLNode->getAttribute('pdx:occurrence')) {
            $occurrence = (int)$XMLNode->getAttribute('pdx:occurrence');
        }
        
        $this->_document->cloneBlock($name, $occurrence);
    }
    
    /**
     * Parses the deleteTemplateBlocks XML element and generates the required 
     * PHPDocX code
     *
     * @access private
     * @param DOMNode $XMLNode
     * @return void
     */
    private function XMLAPIDeleteTemplateBlocks($XMLNode)
    {
        $name = $XMLNode->getAttribute('pdx:variableName');
        $this->_document->deleteTemplateBlock($name);
    }
    
    /**
     * Parses the modifyInputFields XML element and generates the required 
     * PHPDocX code
     *
     * @access private
     * @param DOMNode $XMLNode
     * @return void
     */
    private function XMLAPIModifyInputFields($XMLNode)
    {
        $childNodes = $XMLNode->childNodes;
        foreach($childNodes as $child) {
            $name = $child->getAttribute('pdx:variableName');
            $values[$name] = $this->fetchData($child, 'text');
        }
        $this->_document->modifyInputFields($values);
    }

    /**
     * Parses the processTemplate XML element and generates the required 
     * PHPDocX code
     *
     * @access private
     * @param DOMNode $XMLNode
     * @return void
     */
    private function XMLAPIProcessTemplate($XMLnode, $contentTarget)
    {
        $templateVariables = $this->_document->getTemplateVariables();
        $this->_document->processTemplate($templateVariables);
    }
    
    /**
     * Parses the removeTemplateVariable XML element and generates the required 
     * PHPDocX code
     *
     * @access private
     * @param DOMNode $XMLNode
     * @return void
     */
    private function XMLAPIRemoveTemplateVariable($XMLNode)
    {
        $name = $XMLNode->getAttribute('pdx:variableName');
        $typeAttribute = $XMLNode->getAttribute('pdx:removeType');
        $target = $XMLNode->getAttribute('pdx:target');
        if (empty($target)) {
            $target = 'document';
        }
        if (empty($typeAttribute)) {
            $typeAttribute = 'block';
        }
        $this->_document->removeTemplateVariable($name, $typeAttribute, $target);
    }
    
    /**
     * Parses the replaceListVariable XML element and generates the required 
     * PHPDocX code
     *
     * @access private
     * @param DOMNode $XMLNode
     * @return void
     */
    private function XMLAPIReplaceListVariable($XMLNode)
    {
        $childNodes = $XMLNode->childNodes;

        // parse the data
        $data = array();
        $mainListNode = $XMLNode->firstChild;
        $data = $this->parseList($mainListNode);
        $name = $XMLNode->getAttribute('pdx:variableName');
        $firstMatch = $XMLNode->getAttribute('pdx:firstMatch');
        $parseLineBreaks = $XMLNode->getAttribute('pdx:parseLineBreaks');
        $target = $XMLNode->getAttribute('pdx:target');

        $options = array();
        if (!empty($firstMatch)) {
            $options['firstMatch'] = (bool)$firstMatch;
        }

        if (!empty($parseLineBreaks)) {
            $options['parseLineBreaks'] = (bool)$parseLineBreaks;
        }

        if (!empty($target)) {
            $options['target'] = $target;
        }
        $this->_document->replaceListVariable($name, $data, $options);
    }
    
    /**
     * Parses the replacePlaceholderImage XML element and generates the required 
     * PHPDocX code
     *
     * @access private
     * @param DOMNode $XMLNode
     * @return void
     */
    private function XMLAPIReplacePlaceholderImage($XMLNode)
    {
        $attributes = $XMLNode->attributes;
        $options = $this->XMLAttributes2Array($attributes, 'array');
        if (isset($options['firstMatch'])) {
            $options['firstMatch'] = (bool)$options['firstMatch'];
        }

        $this->_document->replacePlaceholderImage($options['name'], $options['path'], $options);
    }

    /**
     * Parses the replaceTableVariable XML element and generates the required 
     * PHPDocX code
     *
     * @access private
     * @param DOMNode $XMLNode
     * @return void
     */
    private function XMLAPIReplaceTableVariable($XMLNode)
    {
        $childNodes = $XMLNode->childNodes;

        // parse the data
        $data = array();
        $firstMatch = $XMLNode->getAttribute('pdx:firstMatch');
        $parseLineBreaks = $XMLNode->getAttribute('pdx:parseLineBreaks');
        $target = $XMLNode->getAttribute('pdx:target');

        // get table data
        $tableElements = $XMLNode->firstChild->childNodes;
        foreach ($tableElements as $tableElement) {
            $dataRow = array();
            $tableElementItems = $tableElement->childNodes;
            foreach ($tableElementItems as $tableElementItem) {
                $wordFragmentName = $tableElementItem->getAttribute('pdx:wordFragmentName');
                if (!empty($wordFragmentName)) {
                    $dataRow[$tableElementItem->getAttribute('pdx:variableName')] = $this->_wordFragments['wordFragment_' . $wordFragmentName];
                } else {
                    $dataRow[$tableElementItem->getAttribute('pdx:variableName')] = $tableElementItem->nodeValue;
                }
            }
            $data[] = $dataRow;
        }

        $options = array();
        if (isset($firstMatch)) {
            $options['firstMatch'] = (bool)$firstMatch;
        }

        if (isset($parseLineBreaks)) {
            $options['parseLineBreaks'] = (bool)$parseLineBreaks;
        }

        if (!empty($target)) {
            $options['target'] = $target;
        }

        $this->_document->replaceTableVariable($data, $options);
    }
    
    /**
     * Parses the replaceVariableByHTML XML element and generates the required 
     * PHPDocX code
     *
     * @access private
     * @param DOMNode $XMLNode
     * @return void
     */
    private function XMLAPIReplaceVariableByHTML($XMLNode)
    {
        $attributes = $XMLNode->attributes;
        $values = $this->XMLAttributes2Array($attributes, 'array');

        if (isset($values['useHTMLExtended']) && $values['useHTMLExtended'] == 'true') {
            $values['useHTMLExtended'] = true;
        } else {
            $values['useHTMLExtended'] = false;
        }

        if (isset($values['customListStyles']) && $values['customListStyles'] == 'true') {
            $values['customListStyles'] = true;
        } else {
            $values['customListStyles'] = false;
        }

        if (isset($values['stylesReplacementType']) && $values['stylesReplacementType'] == 'true') {
            $values['stylesReplacementType'] = true;
        } else {
            $values['stylesReplacementType'] = false;
        }

        if (isset($values['cssEntityDecode']) && $values['cssEntityDecode'] == 'true') {
            $values['cssEntityDecode'] = true;
        } else {
            $values['cssEntityDecode'] = false;
        }

        $var = $values['variableName'];
        $type = $values['replaceType'];
        $html = $this->fetchData($XMLNode, 'text');
        $wordStylesNodes = $XMLNode->getElementsByTagName('wordStyles');
        if ($wordStylesNodes->length > 0) {
            $strict = $wordStylesNodes->item(0)->getAttribute('pdx:strict');
            if (!empty($strict)) {
                $values['strictWordStyles'] = $strict;
            }
            $childNodes = $wordStylesNodes->item(0)->childNodes;
            if ($childNodes->length >0) {
                $values['wordStyles'] = array();
                foreach ($childNodes as $child) {
                    $styleName = $child->getAttribute('pdx:name');
                    $type = $child->getAttribute('pdx:styleType');
                    if ($type == 'id') {
                        $style = '#' . $styleName;
                    } else {
                        $style = '.' . $styleName;
                    }
                    $values['wordStyles'][] = $style;
                }
            }
        }
        
        $this->_document->replaceVariableByHTML($var, $type, $html, $values);
    }
    
    /**
     * Parses the replaceVariableByText XML element and generates the required 
     * PHPDocX code
     *
     * @access private
     * @param DOMNode $XMLNode
     * @return void
     */
    private function XMLAPIReplaceVariableByText($XMLNode)
    {
        $attributes = $XMLNode->attributes;
        $options = $this->XMLAttributes2Array($attributes, 'array');
        if (isset($options['firstMatch'])) {
            $options['firstMatch'] = (bool)$options['firstMatch'];
        }
        if (isset($options['parseLineBreaks'])) {
            $options['parseLineBreaks'] = (bool)$options['parseLineBreaks'];
        }
        if (isset($options['raw'])) {
            $options['raw'] = (bool)$options['raw'];
        }
        $childNodes = $XMLNode->childNodes;
        foreach ($childNodes as $child) {
            $name = $child->getAttribute('pdx:variableName');
            $values[$name] = $this->fetchData($child, 'text');
        }
        $this->_document->replaceVariableByText($values, $options);
    }
    
    /**
     * Parses the replaceVariableByExternalFile XML element and generates the 
     * required PHPDocX code
     *
     * @access private
     * @param DOMNode $XMLNode
     * @return void
     */
    private function XMLAPIReplaceVariableByExternalFile($XMLNode)
    {
        $attributes = $XMLNode->attributes;
        $options = $this->XMLAttributes2Array($attributes, 'array');
        if (isset($options['matchSource'])) {
            $options['matchSource'] = (bool)$options['matchSource'];
        }
        if (isset($options['firstMatch'])) {
            $options['firstMatch'] = (bool)$options['firstMatch'];
        }
        if (isset($options['preprocess'])) {
            $options['preprocess'] = (bool)$options['preprocess'];
        }
        $values = array();
        $childNodes = $XMLNode->childNodes;
        foreach ($childNodes as $child) {
            $name = $child->getAttribute('pdx:variableName');
            $values[$name] = $this->fetchData($child, 'text');
        }
        $this->_document->replaceVariableByExternalFile($values, $options);
    }
    
    /**
     * Parses the replaceVariableByWordFrament XML element and generates the required 
     * PHPDocX code
     *
     * @access private
     * @param DOMNode $XMLNode
     * @return void
     */
    private function XMLAPIReplaceVariableByWordFragment($XMLNode)
    {
        $childNodes = $XMLNode->childNodes;
        $values = $this->XML2Array($childNodes);
        $attributes = $XMLNode->attributes;
        $options = $this->XMLAttributes2Array($attributes, 'array');
        $wordFragments = array();
        foreach ($values as $value) {
            $wordFragments[$value['name']] = $this->_wordFragments['wordFragment_' . $value['wordFragmentName']];
        }
        $this->_document->replaceVariableByWordFragment($wordFragments, $options);
    }

    /**
     * Parses the replaceVariableByWordML XML element and generates the required 
     * PHPDocX code
     *
     * @access private
     * @param DOMNode $XMLNode
     * @return void
     */
    private function XMLAPIReplaceVariableByWordML($XMLNode)
    {
        $attributes = $XMLNode->attributes;
        $options = $this->XMLAttributes2Array($attributes, 'array');

        if (isset($options['firstMatch'])) {
            $options['firstMatch'] = (bool)$options['firstMatch'];
        }

        $values = array();
        $childNodes = $XMLNode->childNodes;
        foreach ($childNodes as $child) {
            $name = $child->getAttribute('pdx:name');
            $values[$child->getAttribute('pdx:name')] = $this->fetchData($child, 'text');
        }
        $this->_document->replaceVariableByWordML($values, $options);
    }

    /**
     * Parses the setDocumentDefaultStyles XML element and generates the required 
     * PHPDocX code
     *
     * @access private
     * @param DOMNode $XMLNode
     * @return void
     */
    private function XMLAPISetDocumentDefaultStyles($XMLNode)
    {
        $values = array();
        $values = $this->parseParagraphStyles($XMLNode);
        $this->_document->setDocumentDefaultStyles($values);
    }

    /**
     * Parses the setTemplateSymbol XML element and generates the required 
     * PHPDocX code
     *
     * @access private
     * @param DOMNode $XMLNode
     * @return void
     */
    private function XMLAPISetTemplateSymbol($XMLNode)
    {
        $attributes = $XMLNode->attributes;
        $values = $this->XMLAttributes2Array($attributes, 'array');
        $this->_document->setTemplateSymbol($values['symbolValue']);
    }

    /**
     * Parses the setTemplateSymbol XML element and generates the required 
     * PHPDocX code
     *
     * @access private
     * @param DOMNode $XMLNode
     * @return void
     */
    private function XMLAPISetTemplateBlockSymbol($XMLNode)
    {
        $attributes = $XMLNode->attributes;
        $values = $this->XMLAttributes2Array($attributes, 'array');
        $this->_document->setTemplateBlockSymbol($values['symbolValue']);
    }
     
    /**
     * Validates XML with a given Schema
     *
     * @access private
     * @param DOMDocument $xml
     * @param string $XMLSchema
     * @return boolean
     */
    private function XMLValidation($xml, $XMLSchema)
    {
        $message = '';
        if (!$xml->schemaValidate($XMLSchema)) {
            //activate custom errors
            libxml_use_internal_errors(true);
            //get the resulting errors
            $errors = libxml_get_errors();
            foreach ($errors as $error) {
                switch ($error->level) {
                    case LIBXML_ERR_WARNING:
                        //This is just a warning so we send it to the logger 
                        //to output the relevant info
                        $message .= 'Warning: ' . $error->code . '.' . PHP_EOL;
                        if ($error->file) {
                            $message .= 'file: ' . $error->file . '.' . PHP_EOL;
                        }
                        $return .= 'Line: ' . $error->line . '.' . PHP_EOL;
                        break;
                    case LIBXML_ERR_ERROR:
                        //Although a recoverable error we abort execution
                        $message .= 'Error: ' . $error->code . '.' . PHP_EOL;
                        if ($error->file) {
                            $message .= 'file: ' . $error->file . '.' . PHP_EOL;
                        }
                        $return .= 'Line: ' . $error->line . '.' . PHP_EOL;
                        PhpdocxLogger::logger($message, 'fatal');
                        break;
                    case LIBXML_ERR_FATAL:
                        //Fatal error: abort execution
                        $message .= 'Error: ' . $error->code . '.' . PHP_EOL;
                        if ($error->file) {
                            $message .= 'file: ' . $error->file . '.' . PHP_EOL;
                        }
                        $return .= 'Line: ' . $error->line . '.' . PHP_EOL;
                        PhpdocxLogger::logger($message, 'fatal');
                        break;
                }
                //print in the logger the resulting warnings
                PhpdocxLogger::logger($message, 'info');
                return true;
            }
            //clean teh buffer so the errors do not pile up consuming resources
            libxml_clear_errors();
        } else{
            return true;
        }
    }

    /**
     * Parses character styles
     *
     * @access private
     * @param DOMDocument $styleNode
     * @param array $data
     * @return array
     */
    private function parseCharacterStyles($styleNode, $data = array())
    {
        $styles = $styleNode->childNodes;
        foreach ($styles as $style) {
            $data = $this->XML2ArraySingleNode($style, $data);
        }

        return $data;
    }
    
    /**
     * Parses paragraph styles including borders and tabs
     *
     * @access private
     * @param DOMDocument $styleNode
     * @param array $data
     * @return array
     */
    private function parseParagraphStyles($styleNode, $data = array())
    {
        $styles = $styleNode->childNodes;
        foreach ($styles as $style) {
            $name = $style->nodeName;
            switch($name) {
                case 'pdx:border':
                    $data = $this->XMLBorderChilds2Array($style, $data);
                    break;
                case 'pdx:tabPositions':
                    $tabs = $style->childNodes;
                    foreach($tabs as $tab){
                        $attributes = $tab->attributes;
                        $data['tabPositions'][] = $this->XMLAttributes2Array($attributes);
                    }
                    break;
                default:
                    $data = $this->XML2ArraySingleNode($style, $data);
            }
        }
        return $data;
    }
    
    /**
     * Parses paragraph styles including borders and tabs
     *
     * @access private
     * @param string $nodeName
     * @param DOMDocument $node
     * @param array $data
     * @return array
     */
    private function parseStyles($nodeName, $node, $data = array())
    {
        $styleNodes = $node->getElementsByTagName($nodeName);
        if ($styleNodes->length > 0) {
            $data = $this->parseParagraphStyles($styleNodes->item(0), $data);
        }
        return $data;
    }
    
    /**
     * Parses table data
     *
     * @access private
     * @param DOMDocument $node
     * @return array
     */
    private function parseTableData($node)
    {
        $data = array();
        $rowNodes = $node->childNodes;
        $dataIndex = 0;
        foreach ($rowNodes as $row) {
            $attributesRow = $row->attributes;
            if ($attributesRow->length > 0) {
                $attributesRowData = $this->XMLAttributes2Array($attributesRow);
                $data['rowProperties'] = $attributesRowData;
            }

            $cellNodes = $row->getElementsByTagName('cell');
            $colData = array();
            $colDataIndex = 0;
            foreach ($cellNodes as $cell) {
                $cellTags = $this->XML2Array($cell->childNodes);
                $cellData = $cell->getElementsByTagName('data')->item(0);

                $dataType = $cellData->getAttribute('pdx:dataType');
                if ($dataType == 'wordFragment') {
                    $colData[$colDataIndex] = $this->_wordFragments['wordFragment_' . $cellData->nodeValue];
                } else {
                    $colData[$colDataIndex] = $cellData->nodeValue;
                }

                if (is_array($cellTags) && count($cellTags) > 1) {
                    $colData[$colDataIndex] = array('value' => $colData[$colDataIndex]) + $cellTags;

                    $borderData = $cell->getElementsByTagName('border')->item(0);
                    if ($borderData) {
                        $borderValues = $this->XMLBorderChilds2Array($borderData);
                        $colData[$colDataIndex]['border'] = $borderValues['border'];
                        $colData[$colDataIndex] = $colData[$colDataIndex] + $borderValues;
                    }

                    if (isset($colData[$colDataIndex]['colspan'])) {
                        $colData[$colDataIndex]['colspan'] = (int)$colData[$colDataIndex]['colspan'];
                    }

                    if (isset($colData[$colDataIndex]['rowspan'])) {
                        $colData[$colDataIndex]['rowspan'] = (int)$colData[$colDataIndex]['rowspan'];
                    }
                }

                $colDataIndex++;
            }
            $data[$dataIndex] = $colData;
            $dataIndex++;
        }

        return $data;
    }
    
    /**
     * Parses table properties
     *
     * @access private
     * @param DOMDocument $node
     * @return array
     */
    private function parseTableProperties($node)
    {
        $props = array();
        $propNodes = $node->childNodes;
        foreach($propNodes as $prop){
            $name = $prop->nodeName;
            switch ($name) {
                case 'pdx:border':
                    $props = $this->XMLBorderChilds2Array($prop, $props);
                    break;
                case 'pdx:float':
                    $props = $this->XML2ArraySingleNode($prop, $props);
                    foreach($props['float']['textMargins'] as $key => $value){
                        $props['float']['textMargin' . ucwords($key)] = $value;
                    }
                    unset($props['float']['textMargins']);
                    $align = $prop->getAttribute('pdx:value');
                    if(!empty($align)){
                        $props['float']['align'] = $align;
                    }else{
                        $props['float']['align'] = 'left';
                    }
                    break;
                case 'pdx:paragraphStyle':
                    $paragraphStyles = $this->parseParagraphStyles($node);
                    $props['textProperties'] = $paragraphStyles['paragraphStyle'];
                    break;
                case 'pdx:columnWidths':
                    $widthsNodes = $prop->childNodes;
                    $columnWidths = array();

                    foreach ($widthsNodes as $widthNode) {
                        $columnWidths[] = $widthNode->getAttribute('pdx:width');
                    }
                    $props['columnWidths'] = $columnWidths;
                    break;
                default:
                    $props = $this->XML2ArraySingleNode($prop, $props);
            }
        }

        return $props;
    }
    
    /**
     * Gets the data from a node
     *
     * @access private
     * @param DOMDNode $dataNode
     * @param string $type
     * $param string $key
     * @return mixed
     */
    private function fetchData($dataNode, $type, $key = '')
    {
        $result = '';
        $wordFragment = $dataNode->getAttribute('pdx:wordFragmentName');
        if (!empty($wordFragment)) {
            $result = ${'wordFragment_' . $wordFragment};
            $wf = true;
            if (!in_array($result, $this->_wordFragments)) {
                $this->createWordFragment($wordFragment);
            }
        } else {
            $data = $dataNode->getElementsByTagName('data')->item(0);
            $dataAttributes = $data->attributes;
            $dataParams = $this->XMLAttributes2Array($dataAttributes);
            if (!empty($dataParams['dataId'])) {
                $dataId = $dataParams['dataId'];
                if (isset($dataParams['dataType'])) {
                    $dataType = $dataParams['dataType'];
                } else {
                    $dataType = 'text';
                }
                $result = $this->dataQuery($dataId, $type);
            } else {
                $result = $data->nodeValue;
            }
        }
        if (empty($key) || $wf) {
            return $result;
        } else {
            return array($key => $result);
        }
    }

    /**
     * Parse the DocxUtilites nodes
     *
     * @access private
     * @param DOMNode $docxUtilitesNodes
     */
    private function parseDocxUtilities($node) {
        $nodeName = $node->nodeName;
        $docxUtilitiesOptions = $this->XMLAttributes2Array($node->attributes, 'array');
        switch ($nodeName) {
            case 'pdx:mergeDocx':
                $mergeDocxNodes = $node->getElementsByTagName('docx');
                $firstDocument = $mergeDocxNodes->item(0)->getAttribute('pdx:path');
                $i = 0;
                $mergeDocuments = array();
                foreach ($mergeDocxNodes as $mergeDocxNode) {
                    if ($mergeDocxNode->nodeName == 'pdx:docx') {
                        // avoid the first value to insert it only add the first DOCX
                        if ($i == 0) {
                            $i++;
                            continue;
                        }
                        $mergeDocuments[] = $mergeDocxNode->getAttribute('pdx:path');
                    }
                }
                $merge = new MultiMerge();
                $merge->mergeDocx($firstDocument, $mergeDocuments, $docxUtilitiesOptions['target'], $docxUtilitiesOptions);
                break;
            case 'pdx:mergePdf':
                $mergePdfNodes = $node->getElementsByTagName('pdf');
                $mergeDocuments = array();
                foreach ($mergePdfNodes as $mergePdfNode) {
                    if ($mergePdfNode->nodeName == 'pdx:pdf') {
                        $mergeDocuments[] = $mergePdfNode->getAttribute('pdx:path');;
                    }
                }
                $merge = new MultiMerge();
                $merge->mergePdf($mergeDocuments, $docxUtilitiesOptions['target'], $docxUtilitiesOptions);
                break;
            case 'pdx:parseCheckboxes':
                $checkboxNodes = $node->getElementsByTagName('checkbox');
                $checkboxes = array();
                foreach ($checkboxNodes as $checkboxNode) {
                    $checkboxes[] = (int)$checkboxNode->getAttribute('pdx:value');
                }
                $docx = new DocxUtilities();
                $docx->parseCheckboxes($docxUtilitiesOptions['source'], $docxUtilitiesOptions['target'], $checkboxes);
                break;
            case 'pdx:rawSearchAndReplace':
                $docx = new DocxUtilities();
                $docx->rawSearchAndReplace($docxUtilitiesOptions['source'], $docxUtilitiesOptions['target'], $docxUtilitiesOptions['searchTerm'], $docxUtilitiesOptions['replaceTerm']);
                break;
            case 'pdx:removeChapter':
                $docx = new DocxUtilities();
                $docx->removeChapter($docxUtilitiesOptions['source'], $docxUtilitiesOptions['target'], $docxUtilitiesOptions['searchTerm']);
                break;
            case 'pdx:removePagesPdf':
                $removePagesNodes = $node->getElementsByTagName('page');
                $removePagesPdf = array();
                $removePagesPdf['pages'] = array();
                foreach ($removePagesNodes as $removePagesNode) {
                    if ($removePagesNode->nodeName == 'pdx:page') {
                        $removePagesPdf['pages'][] = $removePagesNode->getAttribute('pdx:value');
                    }
                }
                if (isset($docxUtilitiesOptions['annotations'])) {
                    $removePagesPdf['annotations'] = true;
                }
                $docx = new PdfUtilities();
                $docx->removePagesPdf($docxUtilitiesOptions['source'], $docxUtilitiesOptions['target'], $removePagesPdf);
                break;
            case 'pdx:removeSection':
                $sectionNodes = $node->getElementsByTagName('sections');
                $options = array();
                if (isset($docxUtilitiesOptions['keepSections'])) {
                    $options['keepSections'] = (bool)$docxUtilitiesOptions['keepSections'];
                }
                $docx = new DOCXPathUtilities();
                $docx->removeSection($docxUtilitiesOptions['source'], $docxUtilitiesOptions['target'], (int)$docxUtilitiesOptions['section'], $options);
                break;
            case 'pdx:replaceChartData':
                $chartTitle = $node->getElementsByTagName('chartTitle');
                $chartLegends = $node->getElementsByTagName('chartLegend');
                $chartCategories = $node->getElementsByTagName('chartCategory');
                $chartRowNodes = $node->getElementsByTagName('chartRowValue');
                $chartData[$docxUtilitiesOptions['chartNumber']] = array();
                if ($chartTitle->length > 0) {
                    $chartData[$docxUtilitiesOptions['chartNumber']]['title'] = $chartTitle->item(0)->getAttribute('pdx:value');
                }
                if ($chartLegends->length > 0) {
                    foreach ($chartLegends as $chartLegend) {
                        $chartData[$docxUtilitiesOptions['chartNumber']]['legends'][] = $chartLegend->getAttribute('pdx:value');
                    }
                }
                if ($chartCategories->length > 0) {
                    foreach ($chartCategories as $chartCategory) {
                        $chartData[$docxUtilitiesOptions['chartNumber']]['categories'][] = $chartCategory->getAttribute('pdx:value');
                    }
                }
                if ($chartRowNodes->length > 0) {
                    foreach ($chartRowNodes as $child) {
                        if ($child->nodeName == 'pdx:chartRowValue') {
                            $rowValue = array();
                            $chartNodes = $child->getElementsByTagName('chartValue');
                            foreach ($chartNodes as $chartNode) {
                                $rowValue[] = (int)$chartNode->getAttribute('pdx:value');
                            }
                            $chartData[$docxUtilitiesOptions['chartNumber']]['values'][] = $rowValue;
                        }
                    }
                }

                $docx = new DocxUtilities();
                $docx->replaceChartData($docxUtilitiesOptions['source'], $docxUtilitiesOptions['target'], $chartData);
                break;
            case 'pdx:searchAndHighlight':
                $valueContextNodes = $node->getElementsByTagName('valueContext');
                $options = array();
                $options['highlightColor'] = $docxUtilitiesOptions['highlightColor'];
                foreach ($valueContextNodes as $valueContextNode) {
                    if ($valueContextNode->nodeName == 'pdx:valueContext') {
                        $options[$valueContextNode->getAttribute('pdx:value')] = true;
                    }
                }
                $docx = new DocxUtilities();
                $docx->searchAndHighlight($docxUtilitiesOptions['source'], $docxUtilitiesOptions['target'], $docxUtilitiesOptions['searchTerm'], $options);
                break;
            case 'pdx:searchAndRemove':
                $valueContextNodes = $node->getElementsByTagName('valueContext');
                $options = array();
                foreach ($valueContextNodes as $valueContextNode) {
                    if ($valueContextNode->nodeName == 'pdx:valueContext') {
                        $options[$valueContextNode->getAttribute('pdx:value')] = true;
                    }
                }
                $docx = new DocxUtilities();
                $docx->searchAndRemove($docxUtilitiesOptions['source'], $docxUtilitiesOptions['target'], $docxUtilitiesOptions['searchTerm'], $options);
                break;
            case 'pdx:searchAndReplace':
                $valueContextNodes = $node->getElementsByTagName('valueContext');
                $options = array();
                foreach ($valueContextNodes as $valueContextNode) {
                    if ($valueContextNode->nodeName == 'pdx:valueContext') {
                        $options[$valueContextNode->getAttribute('pdx:value')] = true;
                    }
                }
                $docx = new DocxUtilities();
                $docx->searchAndReplace($docxUtilitiesOptions['source'], $docxUtilitiesOptions['target'], $docxUtilitiesOptions['searchTerm'], $docxUtilitiesOptions['replaceTerm'], $options);
                break;
            case 'pdx:setLineNumbering':
                if (isset($docxUtilitiesOptions['distance'])) {
                    $docxUtilitiesOptions['distance'] = (int)$docxUtilitiesOptions['distance'];
                }
                if (isset($docxUtilitiesOptions['start'])) {
                    $docxUtilitiesOptions['start'] = (int)$docxUtilitiesOptions['start'];
                }
                if (isset($docxUtilitiesOptions['countBy'])) {
                    $docxUtilitiesOptions['countBy'] = (int)$docxUtilitiesOptions['countBy'];
                }
                $docx = new DocxUtilities();
                $docx->setLineNumbering($docxUtilitiesOptions['source'], $docxUtilitiesOptions['target'], $docxUtilitiesOptions);
                break;
            case 'pdx:splitDocx':
                $sectionNodes = $node->getElementsByTagName('section');
                $options = array();
                if (isset($docxUtilitiesOptions['keepSections'])) {
                    $options['keepSections'] = (bool)$docxUtilitiesOptions['keepSections'];
                }
                foreach ($sectionNodes as $sectionNode) {
                    $options['sections'][] = (int)$sectionNode->getAttribute('pdx:value');
                }
                $docx = new DOCXPathUtilities();
                $docx->splitDocx($docxUtilitiesOptions['source'], $docxUtilitiesOptions['target'], $options);
                break;
            case 'pdx:splitPdf':
                $pageNodes = $node->getElementsByTagName('page');
                $options = array();
                foreach ($pageNodes as $pageNode) {
                    $options['pages'][] = (int)$pageNode->getAttribute('pdx:value');
                }
                if (isset($docxUtilitiesOptions['annotations'])) {
                    $options['annotations'] = true;
                }
                $docx = new PdfUtilities();
                $docx->splitPdf($docxUtilitiesOptions['source'], $docxUtilitiesOptions['target'], $options);
                break;
            case 'pdx:watermarkDocx':
                $childNodes = $node->childNodes;
                $options = $this->XML2Array($childNodes);
                if (isset($options['decolorate'])) {
                    $options['decolorate'] = (bool)$options['decolorate'];
                }
                if (isset($options['remove_previous_watermarks'])) {
                    $options['remove_previous_watermarks'] = (bool)$options['remove_previous_watermarks'];
                }
                if (isset($options['opacity'])) {
                    $options['opacity'] = (float)$options['opacity'];
                }
                $docx = new DocxUtilities();
                $docx->watermarkDocx($docxUtilitiesOptions['source'], $docxUtilitiesOptions['target'], $docxUtilitiesOptions['type'], $options);
                break;
            case 'pdx:watermarkPdf':
                $childNodes = $node->childNodes;
                $options = $this->XML2Array($childNodes);
                if (isset($options['opacity'])) {
                    $options['opacity'] = (float)$options['opacity'];
                }
                if (isset($docxUtilitiesOptions['annotations'])) {
                    $options['annotations'] = true;
                }
                $docx = new PdfUtilities();
                $docx->watermarkPdf($docxUtilitiesOptions['source'], $docxUtilitiesOptions['target'], $docxUtilitiesOptions['type'], $options);
                break;
            case 'pdx:watermarkRemove':
                $docx = new DocxUtilities();
                $docx->watermarkRemove($docxUtilitiesOptions['source'], $docxUtilitiesOptions['target']);
                break;
        }
    }
    
    /**
     * Extracts the list data
     *
     * @access private
     * @param DOMNode $listNode
     * @return array
     */
    private function parseList($listNode)
    {
        $dataId = $listNode->getAttribute('pdx:data');
        if (!empty($dataId)) {
            $listArray = $this->dataQuery($dataId, 'list');
        } else {
            $listArray = array();
            $childs = $listNode->childNodes;
            foreach ($childs as $child) {
                if ($child->nodeName == 'pdx:item') {
                    $dataType = $child->getAttribute('pdx:dataType');
                    if ($dataType == 'wordFragment') {
                        $listArray[] = $this->_wordFragments['wordFragment_' . $child->nodeValue];
                    } else {
                        $listArray[] = $child->nodeValue;
                    }
                } else if($child->nodeName == 'pdx:data') {
                    $listArray[] = $this->parseList($child);
                }
            }
        }
        return $listArray;
    }
}
