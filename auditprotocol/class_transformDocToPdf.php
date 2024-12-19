<?php

/**
 * Transform documents using LibreOffice
 *
 * @category   Phpdocx
 * @package    trasform
 * @copyright  Copyright (c) Narcea Producciones Multimedia S.L.
 *             (http://www.2mdc.com)
 * @license    phpdocx LICENSE
 * @version    2018.01.05
 * @link       https://www.phpdocx.com
 */

require_once $_SERVER['DOCUMENT_ROOT'].'/policymachine/classes/TransformDocAdv.php';

class TransformDocToPdf extends TransformDocAdv
{

    /**
      *modified the original phpdocx function of class TransformDocAdvLibreOffice
      * @access public
     * @param $docSource
     * @param $docDestination
     * @param string $tempDir
     * @param array $options :
     *      · method : 'direct' (default), 'script' ; 'direct' method uses
     *                  passthru and 'script' uses a external script.
     *                  If you're using Apache and 'direct' doesn't work use 'script'
     *      · 'debug' : false (default) or true. It shows debug information about the plugin conversion
     *      · 'toc' : false (default) or true. It generates the TOC before exporting the document
     *      · 'pdfa1' : false (default) or true. It generates the TOC before exporting the document
     *      · 'outdir' : set the outdir path. Useful when the PDF output path is not the same than the running script
     * @return document path if success
    */
    public function transformDocument($docSource, $docDestination, $tempDir = null, $options = array(), $version = null)
    {
        // get the file info
        $sourceFileInfo = pathinfo($docSource);
        $sourceExtension = $sourceFileInfo['extension'];
        
        if (!isset($options['method'])) {
            $options['method'] = 'direct';
        }
        if (!isset($options['debug'])) {
            $options['debug'] = false;
        }
        if (!isset($options['toc'])) {
            $options['toc'] = false;
        }

        $destination = explode('.', $docDestination);
        $extension = strtolower(array_pop($destination));
        // if (!in_array($extension, $this->_allowedExtensions)) {
        //     PhpdocxLogger::logger('The chosen extension is not supported', 'fatal');
        // }

        $phpdocxconfig = PhpdocxUtilities::parseConfig();
        // print_r($phpdocxconfig);
        $libreOfficePath = $phpdocxconfig['transform']['path'];

        // set outputstring for debugging
        $outputDebug = '';
        if (PHP_OS == 'Linux' || PHP_OS == 'Darwin' || PHP_OS == ' FreeBSD') {
            if (!$options['debug']) {
                $outputDebug = ' > /dev/null 2>&1';
            }
        } elseif (substr(PHP_OS, 0, 3) == 'Win' || substr(PHP_OS, 0, 3) == 'WIN') {
            if (!$options['debug']) {
                $outputDebug = ' > nul 2>&1';
            }
        }

        // if the outdir option is set use it as target path, instead use the dir path 
        if (isset($options['outdir'])) {
            $outdir = $options['outdir'];
        } else {
            $outdir = $sourceFileInfo['dirname'];
        }

        if ($options['method'] == 'script') {
            passthru('php ' . dirname(__FILE__) . '/../lib/convertSimple.php -s ' . $docSource . ' -e ' . $extension . ' -p ' . $libreOfficePath . ' -t ' . $options['toc'] . ' -o ' . $outdir . $outputDebug);
        } else {
            if ((isset($options['toc']) && $options['toc'] === true) && (!isset($options['pdfa1']) || (isset($options['pdfa1']) && $options['pdfa1'] === false))) {
                if ($extension == 'docx') {
                    passthru($libreOfficePath . ' --invisible "macro:///Standard.Module1.SaveToDocxToc(' . realpath($docSource) . ')" ' . $outputDebug);
                } else {
                    passthru($libreOfficePath . ' --invisible "macro:///Standard.Module1.SaveToPdfToc(' . realpath($docSource) . ')" ' . $outputDebug);
                }
            } elseif ((isset($options['toc']) && $options['toc'] === true) && (isset($options['pdfa1']) && $options['pdfa1'] === true)) {
                passthru($libreOfficePath . ' --invisible "macro:///Standard.Module1.SaveToPdfA1Toc(' . realpath($docSource) . ')" ' . $outputDebug);
            } elseif ((isset($options['pdfa1']) && $options['pdfa1'] === true) && (!isset($options['toc']) || (!isset($options['toc']) || $options['toc'] === false))) {
                passthru($libreOfficePath . ' --invisible "macro:///Standard.Module1.SaveToPdfA1(' . realpath($docSource) . ')" ' . $outputDebug);
            } else {
                passthru("export HOME=/tmp/ && {$libreOfficePath} --invisible --convert-to {$extension} \"{$docSource}\" --outdir {$outdir} {$outputDebug}");
                // passthru("sudo {$libreOfficePath} --invisible --convert-to {$extension} \"{$docSource}\" --outdir {$outdir} {$outputDebug}");
                // echo "export HOME=/tmp/ && {$libreOfficePath} --invisible --convert-to {$extension} \"{$docSource}\" --outdir {$outdir} {$outputDebug}";
            }
        }
        // echo $docDestination;
        if (file_exists($docDestination)) {
            return $docDestination;
        } else {
            return false;
        }
    }
}
