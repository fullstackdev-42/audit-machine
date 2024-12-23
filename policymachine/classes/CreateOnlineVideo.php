<?php

/**
 * Create online videos
 *
 * @category   Phpdocx
 * @package    elements
 * @copyright  Copyright (c) Narcea Producciones Multimedia S.L.
 *             (http://www.2mdc.com)
 * @license    phpdocx LICENSE
 * @link       https://www.phpdocx.com
 */
class CreateOnlineVideo extends CreateElement
{
    /**
     * @access private
     * @var CreateOnlineVideo
     * @static
     */
    private static $_instance = NULL;

    /**
     *
     * @access private
     * @var string
     */
    private $_name;

    /**
     *
     * @access private
     * @var int
     */
    private $_rId;

    /**
     *
     * @access private
     * @var string
     */
    private $_textWrap;

    /**
     *
     * @access private
     * @var int
     */
    private $_sizeX;

    /**
     *
     * @access private
     * @var int
     */
    private $_sizeY;

    /**
     *
     * @access private
     * @var int
     */
    private $_dpi;

    /**
     *
     * @access private
     * @var int
     */
    private $_dpiCustom;

    /**
     *
     * @access private
     * @var int
     */
    private $_spacingTop;

    /**
     *
     * @access private
     * @var int
     */
    private $_spacingBottom;

    /**
     *
     * @access private
     * @var int
     */
    private $_spacingLeft;

    /**
     *
     * @access private
     * @var int
     */
    private $_spacingRight;

    /**
     *
     * @access private
     * @var int
     */
    private $_jc;

    /**
     *
     * @access private
     * @var string
     */
    private $_border;

    /**
     *
     * @access private
     * @var string
     */
    private $_borderDiscontinuous;

    /**
     *
     * @access private
     * @var int
     */
    private $_scaling;

    /**
     * Construct
     *
     * @access public
     */
    public function __construct()
    {
        $this->_name = '';
        $this->_rId = '';
        $this->_rIdImage = '';
        $this->_image = '';
        $this->_textWrap = '';
        $this->_sizeX = '';
        $this->_sizeY = '';
        $this->_spacingTop = '';
        $this->_spacingBottom = '';
        $this->_spacingLeft = '';
        $this->_spacingRight = '';
        $this->_jc = '';
        $this->_border = '';
        $this->_borderDiscontinuous = '';
        $this->_scaling = '';
        $this->_dpiCustom = 0;
        $this->_dpi = 96;
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
     *
     * @return string
     * @access public
     */
    public function __toString()
    {
        return $this->_xml;
    }

    /**
     *
     * @return CreateOnlineVideo
     * @access public
     * @static
     */
    public static function getInstance()
    {
        if (self::$_instance == NULL) {
            self::$_instance = new CreateOnlineVideo();
        }
        return self::$_instance;
    }

    /**
     * Setter. Name
     *
     * @access public
     * @param string $name
     */
    public function setName($name)
    {
        $this->_name = $name;
    }

    /**
     * Getter. Name
     *
     * @access public
     * @return <type>
     */
    public function getName()
    {
        return $this->_name;
    }

    /**
     * Setter. Rid
     *
     * @access public
     * @param string $rId
     */
    public function setRId($rId)
    {
        $this->_rId = $rId;
    }

    /**
     * Getter. Rid
     *
     * @access public
     * @return <type>
     */
    public function getRId()
    {
        return $this->_rId;
    }

    /**
     * Create online video
     *
     * @access public
     * @param array $args[0]
     */
    public function createOnlineVideo()
    {
        $this->_xml = '';
        $this->_name = '';
        $this->_rId = '';
        $this->_rIdImage = '';
        $this->_image = '';
        $this->_textWrap = '';
        $this->_sizeX = '';
        $this->_sizeY = '';
        $this->_spacingTop = '';
        $this->_spacingBottom = '';
        $this->_spacingLeft = '';
        $this->_spacingRight = '';
        $this->_jc = '';
        $this->_border = '';
        $this->_borderDiscontinuous = '';
        $this->_scaling = '';
        $this->_dpiCustom = 0;
        $this->_dpi = 96;
        $args = func_get_args();

        if (isset($args[0]['rId']) && (isset($args[0]['image']))) {
            $attributes = getimagesize($args[0]['image']);

            if (!isset($args[0]['textWrap']) || $args[0]['textWrap'] < 0 ||
                    $args[0]['textWrap'] > 5
            ) {
                $textWrap = 0;
            } else {
                $textWrap = $args[0]['textWrap'];
            }

            if (isset($args[0]['sizeX'])) {
                $tamPxX = $args[0]['sizeX'];
            } elseif (isset($args[0]['scaling'])) {
                $tamPxX = $attributes[0] * $args[0]['scaling'] / 100;
            } else {
                $tamPxX = $attributes[0];
            }

            if (isset($args[0]['scaling'])) {
                $tamPxY = $attributes[1] * $args[0]['scaling'] / 100;
            } elseif (isset($args[0]['sizeY'])) {
                $tamPxY = $args[0]['sizeY'];
            } else {
                $tamPxY = $attributes[1];
            }
            if (isset($args[0]['dpi'])) {
                $this->_dpiCustom = $args[0]['dpi'];
            }
            $this->setName($args[0]['image']);
            $this->setRId($args[0]['rId']);
            $this->_rIdImage = $args[0]['rIdImage'];
            $top = '0';
            $bottom = '0';
            $left = '0';
            $right = '0';

            switch ($attributes['mime']) {
                case 'image/png':
                    list($dpiX, $dpiY) = $this->getDpiPng($args[0]['image']);
                    $tamWordX = round($tamPxX * 2.54 / $dpiX * CreateImage::CONSTWORD);
                    $tamWordY = round($tamPxY * 2.54 / $dpiY * CreateImage::CONSTWORD);

                    if (isset($args[0]['spacingTop'])) {
                        $top = round(
                                $args[0]['spacingTop'] * 2.54 /
                                $dpiX * CreateImage::CONSTWORD
                        );
                    }
                    if (isset($args[0]['spacingBottom'])) {
                        $bottom = round(
                                $args[0]['spacingBottom'] * 2.54 /
                                $dpiX * CreateImage::CONSTWORD
                        );
                    }
                    if (isset($args[0]['spacingLeft'])) {
                        $left = round(
                                $args[0]['spacingLeft'] * 2.54 /
                                $dpiX * CreateImage::CONSTWORD
                        );
                    }
                    if (isset($args[0]['spacingRight'])) {
                        $right = round(
                                $args[0]['spacingRight'] * 2.54 /
                                $dpiX * CreateImage::CONSTWORD
                        );
                    }
                    break;
                case 'image/jpg':
                case 'image/jpeg':
                    list($dpiX, $dpiY) = $this->getDpiJpg($args[0]['image']);
                    $tamWordX = round(
                            $tamPxX * 2.54 /
                            $dpiX * CreateImage::CONSTWORD
                    );
                    $tamWordY = round(
                            $tamPxY * 2.54 /
                            $dpiY * CreateImage::CONSTWORD
                    );
                    if (isset($args[0]['spacingTop'])) {
                        $top = round(
                                $args[0]['spacingTop'] * 2.54 /
                                $dpiX * CreateImage::CONSTWORD
                        );
                    }
                    if (isset($args[0]['spacingBottom'])) {
                        $bottom = round(
                                $args[0]['spacingBottom'] * 2.54 /
                                $dpiX * CreateImage::CONSTWORD
                        );
                    }
                    if (isset($args[0]['spacingLeft'])) {
                        $left = round(
                                $args[0]['spacingLeft'] * 2.54 /
                                $dpiX * CreateImage::CONSTWORD
                        );
                    }
                    if (isset($args[0]['spacingRight'])) {
                        $right = round(
                                $args[0]['spacingRight'] * 2.54 /
                                $dpiX * CreateImage::CONSTWORD
                        );
                    }
                    break;
                case 'image/gif':
                    if ($this->_dpiCustom > 0) {
                        $this->_dpi = $this->_dpiCustom;
                    }
                    $tamWordX = round(
                            $tamPxX * 2.54 /
                            $this->_dpi * CreateImage::CONSTWORD
                    );
                    $tamWordY = round(
                            $tamPxY * 2.54 /
                            $this->_dpi * CreateImage::CONSTWORD
                    );
                    if (isset($args[0]['spacingTop'])) {
                        $top = round(
                                $args[0]['spacingTop'] * 2.54 /
                                $this->_dpi * CreateImage::CONSTWORD
                        );
                    }
                    if (isset($args[0]['spacingBottom'])) {
                        $bottom = round(
                                $args[0]['spacingBottom'] * 2.54 /
                                $this->_dpi * CreateImage::CONSTWORD
                        );
                    }
                    if (isset($args[0]['spacingLeft'])) {
                        $left = round(
                                $args[0]['spacingLeft'] * 2.54 /
                                $this->_dpi * CreateImage::CONSTWORD
                        );
                    }
                    if (isset($args[0]['spacingRight'])) {
                        $right = round(
                                $args[0]['spacingRight'] * 2.54 /
                                $this->_dpi * CreateImage::CONSTWORD
                        );
                    }
                    break;
            }

            $this->generateP();
            if (isset($args[0]['imageAlign'])) {
                $this->generatePPR();
                $this->generateJC($args[0]['imageAlign']);
            }
            $this->generateR();
            $this->generateRPR();
            $this->generateNOPROOF();
            $this->generateDRAWING();
            if ($textWrap == 0) {
                $this->generateINLINE();
            } else {
                if ($textWrap == 3) {
                    $this->generateANCHOR(1);
                } else {
                    $this->generateANCHOR();
                }
                $this->generateSIMPLEPOS();
                if (isset($args[0]['relativeToHorizontal'])) {
                    $this->generatePOSITIONH($args[0]['relativeToHorizontal']);
                } else {
                    $this->generatePOSITIONH();
                }
                if (isset($args[0]['float']) && ($args[0]['float'] == 'left' || $args[0]['float'] == 'right' || $args[0]['float'] == 'center')) {
                    $this->generateALIGN($args[0]['float']);
                }
                if (isset($args[0]['horizontalOffset']) && is_numeric($args[0]['horizontalOffset'])) {
                    $this->generatePOSOFFSET($args[0]['horizontalOffset']);
                } else {
                    $this->generatePOSOFFSET(0);
                }
                if (isset($args[0]['relativeToVertical'])) {
                    $this->generatePOSITIONV($args[0]['relativeToVertical']);
                } else {
                    $this->generatePOSITIONV();
                }
                if (isset($args[0]['verticalAlign'])) {
                    $this->generateALIGN($args[0]['verticalAlign']);
                }
                if (isset($args[0]['verticalOffset']) && is_numeric($args[0]['verticalOffset'])) {
                    $this->generatePOSOFFSET($args[0]['verticalOffset']);
                } else {
                    $this->generatePOSOFFSET(0);
                }
            }

            $this->generateEXTENT($tamWordX, $tamWordY);
            $this->generateEFFECTEXTENT($left, $top, $right, $bottom);

            switch ($textWrap) {
                case 1:
                    $this->generateWRAPSQUARE();
                    break;
                case 2:
                case 3:
                    $this->generateWRAPNONE();
                    break;
                case 4:
                    $this->generateWRAPTOPANDBOTTOM();
                    break;
                case 5:
                    $this->generateWRAPTHROUGH();
                    $this->generateWRAPPOLYGON();
                    $this->generateSTART();
                    $this->generateLINETO();
                    break;
                default:
                    break;
            }
            $this->generateDOCPR();
            if (isset($args[0]['rIdHyperlink'])) {
                $this->generateHYPERLINK($args[0]['rIdHyperlink']);
            }
            $this->generateCNVGRAPHICFRAMEPR();
            $this->generateGRAPHICPRAMELOCKS(1);
            $this->generateGRAPHIC();
            $this->generateGRAPHICDATA();
            $this->generatePIC();
            $this->generateNVPICPR();
            $this->generateCNVPR();
            $this->generateCNVPICPR();
            $this->generateBLIPFILL();
            $this->generateBLIP();
            $this->generateSTRETCH();
            $this->generateFILLRECT();
            $this->generateSPPR();
            $this->generateXFRM();
            $this->generateOFF();
            $this->generateEXT($tamWordX, $tamWordY);
            $this->generatePRSTGEOM();
            $this->generateAVLST();
            if (isset($args[0]['borderStyle']) ||
                    isset($args[0]['borderWidth']) ||
                    isset($args[0]['borderColor'])) {
                //width
                if (isset($args[0]['borderWidth'])) {
                    $this->generateLN($args[0]['borderWidth'] * CreateImage::TAMBORDER);
                } else {
                    $this->generateLN(CreateImage::TAMBORDER);
                }
                //color
                if (isset($args[0]['borderColor'])) {
                    $this->generateSOLIDFILL($args[0]['borderColor']);
                } else {
                    $this->generateSOLIDFILL('000000');
                }
                //style
                if (isset($args[0]['borderStyle'])) {
                    $this->generatePRSTDASH($args[0]['borderStyle']);
                } else {
                    $this->generatePRSTDASH('solid');
                }
            }

            $this->cleanTemplate();
        } else {
            PhpdocxLogger::logger('There was an error adding the online video', 'fatal');
        }
    }

    /**
     * Generate w:blip
     *
     * @param string $cstate
     * @access protected
     */
    protected function generateBLIP($cstate = 'print')
    {
        $xml = '<' . CreateImage::NAMESPACEWORD1 .
                ':blip r:embed="rId' . $this->_rIdImage .
                '" cstate="' . $cstate .
                '">
                <a:extLst>
                    <a:ext uri="{28A0092B-C50C-407E-A947-70E740481C1C}">
                        <a14:useLocalDpi val="0" xmlns:a14="http://schemas.microsoft.com/office/drawing/2010/main"/>
                    </a:ext>
                    <a:ext uri="{C809E66F-F1BF-436E-b5F7-EEA9579F0CBA}">
                        <wp15:webVideoPr embeddedHtml="&lt;iframe width=&quot;560&quot; height=&quot;315&quot; src=&quot;https://www.youtube.com/embed/S-nHYzK-BVg&quot; frameborder=&quot;0&quot; allow=&quot;accelerometer; autoplay; encrypted-media; gyroscope; picture-in-picture&quot; allowfullscreen&gt;&lt;/iframe&gt;" h="315" w="560" xmlns:wp15="http://schemas.microsoft.com/office/word/2012/wordprocessingDrawing"/>
                    </a:ext>
                </a:extLst>
                </' . CreateImage::NAMESPACEWORD1 .
                ':blip>__GENERATEBLIPFILL__';
        $this->_xml = str_replace('__GENERATEBLIPFILL__', $xml, $this->_xml);
    }

    /**
     * Generate w:docpr
     *
     * @param string $id
     * @param string $name
     * @access protected
     */
    protected function generateDOCPR($id = '1', $name = 'Video')
    {
        $id = rand(999999, 999999999);
        $xml = '<' . CreateImage::NAMESPACEWORD . ':docPr id="' . $id .
                '" name="' . $name . '" descr="' . $this->getName() .
                '">__GENERATEDOCPR__</' . CreateImage::NAMESPACEWORD .
                ':docPr>__GENERATEINLINE__';

        $this->_xml = str_replace('__GENERATEINLINE__', $xml, $this->_xml);
    }

    /**
     * Generate w:hlinkClick
     *
     * @param string $rIdHyperlink
     * @access protected
     */
    protected function generateHYPERLINK($rIdHyperlink)
    {
        $id = rand(999999, 999999999);
        $xml = '<a:hlinkClick r:id="rId' . $rIdHyperlink .
                '" xmlns:a="http://schemas.openxmlformats.org/drawingml/2006/main"/>';

        $this->_xml = str_replace('__GENERATEDOCPR__', $xml, $this->_xml);
    }

    /**
     * Get image jpg dpi
     *
     * @access private
     * @param string $filename
     * @return array
     */
    private function getDpiJpg($filename)
    {
        if ($this->_dpiCustom > 0) {
            return array($this->_dpiCustom, $this->_dpiCustom);
        }
        $a = fopen($filename, 'r');
        $string = fread($a, 20);
        fclose($a);
        $type = hexdec(bin2hex(substr($string, 13, 1)));
        $data = bin2hex(substr($string, 14, 4));
        if ($type == 1) {
            $x = substr($data, 0, 4);
            $y = substr($data, 4, 4);
            return array(hexdec($x), hexdec($y));
        } else if ($type == 2) {
            $x = floor(hexdec(substr($data, 0, 4)) / 2.54);
            $y = floor(hexdec(substr($data, 4, 4)) / 2.54);
            return array($x, $y);
        } else {
            return array($this->_dpi, $this->_dpi);
        }
    }

    /**
     * Get image png dpi
     *
     * @access private
     * @param string $filename
     * @return array
     */
    private function getDpiPng($filename)
    {
        if ($this->_dpiCustom > 0) {
            return array($this->_dpiCustom, $this->_dpiCustom);
        }
        $a = fopen($filename, 'r');

        $dpi = false;

        $buf = array();

        $x = 0;
        $y = 0;
        $units = 0;

        while (!feof($a)) {
            array_push($buf, ord(fread($a, 1)));
            if (count($buf) > 13) {
                array_shift($buf);
            }
            if (count($buf) < 13) {
                continue;
            }
            if ($buf[0] == ord('p') && $buf[1] == ord('H') && $buf[2] == ord('Y') && $buf[3] == ord('s')) {
                $x = ($buf[4] << 24) + ($buf[5] << 16) + ($buf[6] << 8) + $buf[7];
                $y = ($buf[8] << 24) + ($buf[9] << 16) + ($buf[10] << 8) + $buf[11];
                $units = $buf[12];
                break;
            }
        }

        fclose($a);

        if ($x == $y) {
            $dpi = $x;
        }

        if ($dpi != false && $units == 1) {
            // meters
            $dpi = round($dpi * 0.0254);
        }

        if ($dpi) {
            return array($dpi, $dpi);
        } else {
            return array($this->_dpi, $this->_dpi);
        }
    }

}
