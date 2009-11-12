<?php
/**
 * Description of UltimaFeed
 *
 * @author Matt Newman
 */

require_once 'UltimaLogging.php';
require_once 'UltimaConfig.php';
require_once 'UltimaHelpers.php';

class UltimaFeed
{
    //private member variables
    private $_url;
    private $_requestId;
    private $_requestType;
    private $_xmlFilePath;

    public function __construct($url)
    {
        //get config settings
        $config = UltimaConfig::getInstance();

        //initialise private member variables
        $this->_url = self::cleanUrl($url);
        
        //get the querystring parameters of the call to the ultima feed
        $pathinfo = Helpers::getUrlParams($this->_url);

        //store the request type as this is commonly used or needed in this class
        $this->_requestType = strtolower($pathinfo['queryname']);

        //create the _requestId - which is used as the basis for the filename
        switch($this->_requestType)
        {
            case "topcategories":
                $this->_requestId   = $this->_requestType;
                break;

            case "category":
                $this->_requestId   = $this->_requestType."_".$pathinfo['categoryid'];
                break;

            case "item":
                $this->_requestId   = $this->_requestType."_".$pathinfo['itemid'];
                break;

            default:
                //deal with unhandled query type
                $this->_requestId   = 'unknown-type';
                //write message to error log about unhandled request type
                UltimaLogging::logError("Unknown request type made: $this->_url");
                break;
        }

        $this->_xmlFilePath = $config->getXmlDir().$this->_requestId.'.xml';

        //saves the result of the call to _url to _xmlFilePath
        $this->cacheFeedItem();
    }

    //TODO: create as public function
    private static function getEntireFeed()
    {
        //look for top_categories.xml or check the _requestType is 'topcategories' then call getAllSubCategoriesAndItems()

        //create topcategories url request then call getAllSubCategoriesAndItems
        $url = "";
        $UltimaFeed = new UltimaFeed($url);
        $UltimaFeed->getAllSubCategoriesAndItems();
    }

    /**
     * Parses the _xmlFilename created from _url and gets all urls in the file
     * and creates a new instance of UltimaFeed and then calls itself for all those urls
     *
     * @return Void
     */
    public function getAllSubCategoriesAndItems()
    {
        //parse XML getting all URLs to call
        $urlsArray = $this->getUrlsToCall();
        
        foreach($urlsArray as $index => $url)
        {
            //TODO: remove timer break
            ScriptTimer::timing_milestone("Calling UltimaFeed($url)");
            
            //recurse through feed
            $feed = new UltimaFeed($url);
            $feed->getAllSubCategoriesAndItems();
        }
    }

    /**
     * if _xmlFilePath doesn't exist or is out of date then saves _url to _xmlFilePath and logs the call
     *
     * @return Void
     */
    private function cacheFeedItem()
    {
        //get config settings
        $config = UltimaConfig::getInstance();

        //check if xml file exists locally or if its due for recaching
        if(!file_exists($this->_xmlFilePath))
        {
            //if the feed hasn't been called before then save it locally
            copy($this->_url, $this->_xmlFilePath);

            //Append called url to end of filelist showing all called urls
            UltimaLogging::logCalledUrl($this->_url);
        }
        else
        {
            //get the date the file was last called
            $fileLastWrittenDate = date('Ymd', filemtime($this->_xmlFilePath));
            
            if(($fileLastWrittenDate + $config->getMinDaysBetweenUrlCalls()) < date('Ymd'))
            {
                copy($this->_url, $this->_xmlFilePath);

                //Append called url to end of filelist showing all called urls
                UltimaLogging::logCalledUrl($this->_url);
            }
            
        }
    }

    /**
     * Gets an array of all urls using xPath from the _xmlFileName document
     *
     * @return Array - array containing the top category urls or FALSE on error
     */
    private function getUrlsToCall()
    {
        $urlsArray = array();

        // create new DOMDocument ready to run a series of xPath queries
        $xmlDoc = new DOMDocument;

        // loadXML will fail if document is not valid XML
        if ($xmlDoc->load($this->_xmlFilePath) == false)
        {
            //write error to log file
            UltimaLogging::logError("Xml file may not be valid: ".$this->_xmlFilePath);
            return false;
        }

        // create the xPath object _after_ loading the xml source, otherwise the query won't work:
        $xPath = new DOMXPath($xmlDoc);

        //get all top-level categories urls
        switch($this->_requestType)
        {
            case "topcategories":
                // now get the category nodes from top_category in a DOMNodeList:
                $nodeList = $xPath->query("ssxmlquery/categories/category");
                // add each url to the $urlsArray
                foreach ($nodeList as $node)
                {
                    $urlsArray[] = $node->attributes->getNamedItem("href")->nodeValue;
                }
                //finished with this case
                break;

            case "category":
                // get the sub-category nodes in a DOMNodeList:
                $subcategoryNodeList = $xPath->query("ssxmlquery/category/subcategories/category");
                // get the item nodes in a DOMNodeList:
                $itemNodeList = $xPath->query("ssxmlquery/category/items/item");

                // add each subcategory url to the $urlsArray
                foreach ($subcategoryNodeList as $node)
                {
                    //get the next category url to call
                    $urlsArray[] = $node->attributes->getNamedItem("href")->nodeValue;
                }

                // add each item url to the $urlsArray
                foreach ($itemNodeList as $node)
                {
                    $urlsArray[] = $node->attributes->getNamedItem("href")->nodeValue;
                }
                //finished with this case
                break;

            default:
                //this deals with items and possible unknown request types becasue we don't have any more urls to get
                break;
        }

        return $urlsArray;
    }

    /**
     * Cleans and returns the $url
     *
     * @return String - $url
     */
    private static function cleanUrl($url)
    {
        return trim(html_entity_decode($url));
    }
    
}
?>
