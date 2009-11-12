<?php
/**
 * Description of UltimaConfig
 *
 * @author Matt Newman
 */
class UltimaConfig {
    //singleton class for global settings for this project
    private static $_instance = FALSE;

    //global variables
    private $_ultima_root;
    private $_img_dir;
    private $_xml_dir;
    private $_xsl_dir;
    private $_html_dir;
    private $_minDaysBetweenUrlCalls;
    private $_debug;

    //public functions to access settings in read-only manner
    public function getUltimaDir()
    {
        return $this->_ultima_root;
    }
    public function getImgDir()
    {
        return $this->_img_dir;
    }
    public function getXmlDir()
    {
        return $this->_xml_dir;
    }
    public function getXslDir()
    {
        return $this->_xsl_dir;
    }
    public function getHtmlDir()
    {
        return $this->_html_dir;
    }
    public function getMinDaysBetweenUrlCalls()
    {
        return $this->_minDaysBetweenUrlCalls;
    }
    public function isDebug()
    {
        return $this->_debug;
    }

    //private constuctor means class can only be called through factory method getInstance
    private function  __construct()
    {
        // increase script execution time as it makes a lot of external http calls: 0 = no limit.
        ini_set('max_execution_time', '180');

        // set error reporting level
        error_reporting(E_ALL);

        //assign values to folder/directory settings
        $this->_ultima_root             = $_SERVER['DOCUMENT_ROOT'].'/';
        $this->_img_dir                 = $this->_ultima_root."images/";
        $this->_xml_dir                 = $this->_ultima_root."xml_files/";
        $this->_xsl_dir                 = $this->_ultima_root."xsl_files/";
        $this->_html_dir                = $this->_ultima_root."html_files/";

        //assign other settings
        $this->_minDaysBetweenUrlCalls  = 7;
        $this->_debug                   = TRUE;
    }

    public static function getInstance()
    {
        if(self::$_instance === FALSE)
        {
            self::$_instance = new UltimaConfig();
        }
        return self::$_instance;
    }
}
?>
