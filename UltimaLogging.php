<?php
/**
 * Description of UltimaLogging
 *
 * @author Matt
 */
class UltimaLogging {

    //TODO: write description of class - could clean this class up? function seem to share common actions

    /**
     * Writes the given error message to todays log file and prefixes the error message with the time
     *
     * @param String $message
     * @return Void
     */
    public static function logError($message)
    {
        //get config settings
        $config = UltimaConfig::getInstance();

        // make the base part of the log directory
        $logDir = $config->getUltimaDir().'logs/';

        //if the log directory doesn't exist then create it
        if(!file_exists($logDir))
        {
            @mkdir($logDir);
        }

        // make the filename based in YYYYMMDD format
        $logFilePath = $logDir.date('Ymd').'.log';

        //Append the given error message to the log file
        //is this better done by using file_put_contents?
        file_put_contents($logFilePath, date('H:i:s')." - ".$message."\r\n", FILE_APPEND);

        if($config->isDebug())
        {
            ScriptTimer::timing_milestone("<p>Wrote error to log file:<br />$message</p>");
        }
    }

    /**
     * Appends the given URL to the a file containing todays list of called URLs -
     * if a url has already been called today then it is logged to the duplicate urls file
     *
     * @param String $url
     * @return Void
     */
    public static function logCalledUrl($url)
    {
        //call the logging function and use the default logDir
        self::logUrl($url);
    }

    /**
     * Writes the $url to todays duplicateUrlsFile
     *
     * @param String $url
     * @return Void
     */
    private static function logUrl($url, $logDir="logs/called_urls/")
    {
        //get config for path to ultima root directory
        $config         = UltimaConfig::getInstance();
        $fullLogDir     = $config->getUltimaDir().$logDir;
        // make the filename based in YYYYMMDD format
        $filename       = date('Ymd').'.log';
        $logFilePath    = $fullLogDir.$filename;

        //if the log file exists then check the url is not already in there
        if(file_exists($logFilePath))
        {
            //check if the file has already been called today - if so log the url to the duplicateUrlsFile
            $calledUrls = file($logFilePath, FILE_SKIP_EMPTY_LINES);
            foreach($calledUrls as $calledUrl)
            {
                if(trim($calledUrl) == $url)
                {
                    //if the file already exists in the calledUrls file then
                    //log it as a duplicateUrl and stop looping through the array
                    //if it already exists in the duplicate_urls log then just exit
                    if($logDir != "logs/duplicate_urls/")
                    {
                        self::logUrl($url, "logs/duplicate_urls/");
                        return;
                    }
                }
            }
        }

        //if we get here the url is not a duplicate call then log it to the calledUrls file
        //Append called url to end of todays file containing all called urls
        file_put_contents($logFilePath, $url."\r\n", FILE_APPEND);

        if($config->isDebug())
        {
            ScriptTimer::timing_milestone("Wrote to calledUrls file: $url<br />");
        }

    }

}
?>
