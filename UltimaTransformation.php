<?php
/**
 * Description of UltimaTransformation
 *
 * @author Matt
 */

require_once 'UltimaLogging.php';
require_once 'ScriptTimer.php';

class UltimaTransformation {

    private $_xmlDoc;
    private $_xmlFilePath;
    private $_xslFilePath;
    private $_saveAsFilePath;
    private $_requestType;
    private $_requestId;

    private function __construct($xmlFilename)
    {
        //get config settings
        $config = UltimaConfig::getInstance();

        //private member variable assignment
        $this->_xmlFilePath     = $config->getXmlDir().$xmlFilename;
        $this->_requestId       = stristr($xmlFilename, '_');
        $this->_requestType     = substr($xmlFilename, 0, stripos($xmlFilename, '_'));
        $this->_xslFilePath     = $config->getXslDir().$this->_requestType.'.xsl';
        
        // check XML and XSLT files exist
        if(!file_exists($this->_xmlFilePath))
        {
            UltimaLogging::logError("XML file for transformation does not exist: " . $this->_xmlFilePath);
            return false;
        }
        if(!file_exists($this->_xslFilePath))
        {
            UltimaLogging::logError("XSL file not found in expected location: " . $this->_xslFilePath);
            return false;
        }

        // create & load XML file into DOMDocument
        $this->_xmlDoc = new DOMDocument;
        $this->_xmlDoc->substituteEntities = true;
        // loadXML will fail if document is not valid XML
        if ($this->_xmlDoc->load($this->_xmlFilePath) == false)
        {
            //write to log file
            UltimaLogging::logError("XML file for transformation may not be valid (load failed): " . $this->_xmlFilePath);
            return false;
        }

        // set the path as which to save the result of the transformation to
        $this->_saveAsFilePath  = $config->getHtmlDir().$this->getSaveAsFilePath();
        //create the saveAs directory if it doesn't exist
        @mkdir(pathinfo($this->_saveAsFilePath, PATHINFO_DIRNAME.'/'), NULL, true);

        // check if the result of the transformation needs updating
        if ($this->isResultFileOutOfDate())
        {
            //create xslt processor
            $xslDoc = new DOMDocument;
            if ($xslDoc->load($this->_xslFilePath) == false)
            {
                //write to log file
                UltimaLogging::logError("XSL file for transformation may not be valid (load failed): " . $this->_xslFilePath);
                return false;
            }

            $xslProc = new XSLTProcessor();
            $xslProc->importStyleSheet($xslDoc);

            //save result of transformation to custom xml file
            $xslProc->transformToUri($this->_xmlDoc, $this->_saveAsFilePath);

            //TODO: remove timing
            ScriptTimer::timing_milestone("transformToUri($this->_saveAsFilePath)");

            //in some versions of PHP internal XSLTProcessor and DOMDocument generated broken XHTLM code - let's to our own 'htmlizing'
            /*
            $output = ltrim(substr($output, strpos($output, '?'.'>')+2)); // removing <?xml
            // some browsers does not support empty div, iframe, script and textarea tags
            $output = preg_replace("!<(div|iframe|script|textarea)([^>]*?)/>!s", "<$1$2></$1>", $output);
            // meta tag should have extra space before />
            $output = preg_replace("!<(meta)([^>]*?)/>!s", "<$1$2 />", $output);
            // nobody needs 9, 10, 13 chars
            $output = preg_replace("!&#(9|10|13);!s", '', $output);
            // lets substitute some UTF8 chars to HTML entities
            $output = str_replace(chr(0xc2).chr(0x97), '&mdash;', $output);
            $output = str_replace(chr(0xc2).chr(0xa0), '&nbsp;', $output);
             */
        }

        return true;
    }

    public static function transformEntireFeed()
    {
        $config = UltimaConfig::getInstance();

        $dir = opendir($config->getXmlDir());

        while(false !== ($file = readdir($dir)))
        {
            if(stripos($file, '.') > 0)
            {
                //TODO: remove timing
                ScriptTimer::timing_milestone("filename = $file");
            
                $transformation = new UltimaTransformation($file);
            }
        }
    }

    public static function transformFileFromFeed($xmlFilename)
    {
        $config = UltimaConfig::getInstance();
        $i = 0;

        $dir = opendir($config->getXmlDir());

        while(false !== ($file = readdir($dir)))
        {
            $i++;

            if($i <= 1)
            {
            //TODO: remove timing
            ScriptTimer::timing_milestone("filename = $xmlFilename");

            $transformation = new UltimaTransformation($xmlFilename);
            }
        }
    }

    /**
     * Gets the filepath to save the transformation result
     *
     * @return String - the directory path and filename
     */
    private function getSaveAsFilePath()
    {
        //create the variable to hold the xPath
        $xPathQuery = "";
        $filename   = "index.html";
        $folderPath = "";

        //create the xPath based on the type of file
        switch($this->_requestType)
        {
            case "category":
                $xPathQuery = "ssxmlquery/category/fullfoldername";
                break;

            case "item":
                $xPathQuery = "ssxmlquery/item/category1/fullfoldername";
                $filename   = "";
                break;

            default:
                //assume this is topcategories?
                break;
        }

        if($xPathQuery != "")
        {
            // create the xPath object _after_ loading the xml source, otherwise the query won't work:
            $xPath = new DOMXPath($this->_xmlDoc);
            // now get the directory name in the first element of a DOMNodeList:
            $nodeList   = $xPath->query($xPathQuery);
            $folderPath = substr($nodeList->item(0)->nodeValue, strlen("products/"));
        }

        if($filename == "")
        {
            //get the items title value to use as it file name
            $xPathQuery = "ssxmlquery/item/title";
            $nodeList   = $xPath->query($xPathQuery);
            //cleanse the title to that it'd make a valid and good url
            $filename   = self::cleanNodeName($nodeList->item(0)->nodeValue).'.html';
        }

        return $folderPath.$filename;
    }

    /**
     * Checks if the resultant transformation may be have expired
     * based on the modified times of the xml, xsl and resultant file
     *
     * @return Bool - TRUE when file has expired, FALSE when its fine
     */
    private function isResultFileOutOfDate()
    {
        // Modification times of source XML file, XSLT file and result file
        $xmlTimeStamp       = filemtime($this->_xmlFilePath);
        $xslTimeStamp       = filemtime($this->_xslFilePath);
        $resultTimeStamp    = @filemtime($this->_saveAsFilePath);

        //if the timestamp of either the xml or xsl files are more recent than the result then return false
        if(($resultTimeStamp > $xmlTimeStamp) && ($resultTimeStamp > $xslTimeStamp))
        {
            // transformation result is up to date and doesn't need processing
            return false;
        }

        //if we get here then the file is out of date and needs processing
        return true;
    }

    //TODO:
    private function getCategoryImages()
    {
        // parse _xmlFilePath for categories to call
        $xmlDoc = new DOMDocument;

        // loadXML will fail if document is not valid XML
        if ($xmlDoc->load($this->_xmlFilePath) == false)
        {
            //write error to log file
            UltimaLogging::logError("Xml file may not be valid: ".$this->_xmlFilePath);
            return false;
        }

        //get the image urls in a DOMNodeList:
        $xpathToImages = "ssxmlquery/category/*[contains(name(), 'image')]";
        $imagesNodeList = $xPath->query($xpathToImages);

        //save the images to the images directory
        foreach ($imagesNodeList as $node)
        {
            $image_url = $node->attributes->getNamedItem("href")->nodeValue;
            self::saveImage($image_url);
        }
    }

    //TODO:
    private static function saveImage()
    {
        //get config settings
        $config = UltimaConfig::getInstance();

        // Get the name of the image from the uri
        $image_info = pathinfo($image_url);
        $saveAsPathAndFilename = str_replace(" ", "_", $image_info['basename']);

        //encode the url being called
        $call_url = implode("/", array_map("rawurlencode", explode("/", $image_url)));

        // check if the file exists locally if not then save it
        // TODO: Check modification date of image - again cahcing?
        if(!file_exists($config->getImgDir().$saveAsPathAndFilename))
        {
            //timer break
            ScriptTimer::timing_milestone("saving image: ".$config->getImgDir()."$saveAsPathAndFilename");

            //TODO: implement any error handling for saving images
            copy(str_replace("%3A", ":", $call_url), $config->getImgDir().$saveAsPathAndFilename);
        }
    }

    /*
     * Removes or replaces unwanted characters with either - or _
     *
     * @param String $nodeName
     * @return String - the cleaned $nodeName
     */
    private static function cleanNodeName($nodeName)
    {
        //array of characters to replace in node name
        $replace_array = array(" " => "_", "/" => "-", "\\" => "-", "*" => "", "\""=>"", "'"=>"", "_-_"=>"-", "__"=>"_");

        return strtr(trim(strtolower($nodeName)), $replace_array);
    }

}
?>
