<?php
if ( !function_exists( 'download_url' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/file.php' );
}

class ShortPixelAPI {
    
    const STATUS_SUCCESS = 1;
    const STATUS_UNCHANGED = 0;
    const STATUS_ERROR = -1;
    const STATUS_FAIL = -2;
    const STATUS_QUOTA_EXCEEDED = -3;
    const STATUS_SKIP = -4;
    const STATUS_NOT_FOUND = -5;
    const STATUS_NO_KEY = -6;
    const STATUS_RETRY = -7;
    
    const ERR_FILE_NOT_FOUND = -2;
    const ERR_TIMEOUT = -3;
    const ERR_SAVE = -4;
    const ERR_SAVE_BKP = -5;
    const ERR_INCORRECT_FILE_SIZE = -6;

    private $_settings;
    private $_maxAttempts = 10;
    private $_apiEndPoint;


    public function __construct($settings) {
        $this->_settings = $settings;
        $this->_apiEndPoint = $this->_settings->httpProto . '://api.shortpixel.com/v2/reducer.php';
        //# TODO verifica- pare la oha: add_action('processImageAction', array(&$this, 'processImageAction'), 10, 4);
    }

    //# TODO verifica- pare la oha
    //#public function processImageAction($url, $filePaths, $itemHandler, $time) {
    //#    $this->processImage($URLs, $PATHs, $itemHandler, $time);
    //#}

    /**
     * sends a compression request to the API
     * @param array $URLs - list of urls to send to API
     * @param Boolean $Blocking - true means it will wait for an answer
     * @param ShortPixelMetaFacade $itemHandler - the Facade that manages different types of image metadatas: MediaLibrary (postmeta table), ShortPixel custom (shortpixel_meta table)
     * @param int $compressionType 1 - lossy, 0 - lossless
     * @return response from wp_remote_post or error
     */
    public function doRequests($URLs, $Blocking, $itemHandler, $compressionType = false) {
        $requestParameters = array(
            'plugin_version' => PLUGIN_VERSION,
            'key' => $this->_settings->apiKey,
            'lossy' => $compressionType === false ? $this->_settings->compressionType : $compressionType,
            'cmyk2rgb' => $this->_settings->CMYKtoRGBconversion,
            'keep_exif' => ($this->_settings->keepExif ? "1" : "0"),
            'convertto' => ($this->_settings->createWebp ? urlencode("+webp") : ""),
            'resize' => $this->_settings->resizeImages + 2 * ($this->_settings->resizeType == 'inner' ? 1 : 0),
            'resize_width' => $this->_settings->resizeWidth,
            'resize_height' => $this->_settings->resizeHeight,
            'urllist' => $URLs
        );
        $arguments = array(
            'method' => 'POST',
            'timeout' => 15,
            'redirection' => 3,
            'sslverify' => false,
            'httpversion' => '1.0',
            'blocking' => $Blocking,
            'headers' => array(),
            'body' => json_encode($requestParameters),
            'cookies' => array()
        );
        //add this explicitely only for https, otherwise (for http) it slows down the request
        if($this->_settings->httpProto !== 'https') {
            unset($arguments['sslverify']);
        }
        
        WpShortPixel::log("Calling API with params : " . json_encode($arguments));

        $response = wp_remote_post($this->_apiEndPoint, $arguments );
        
        //only if $Blocking is true analyze the response
        if ( $Blocking )
        {
            WpShortPixel::log("API response : " . json_encode($response));
            
            //die(var_dump(array('URL: ' => $this->_apiEndPoint, '<br><br>REQUEST:' => $arguments, '<br><br>RESPONSE: ' => $response )));
            //there was an error, save this error inside file's SP optimization field
            if ( is_object($response) && get_class($response) == 'WP_Error' ) 
            {
                $errorMessage = $response->errors['http_request_failed'][0];
                $errorCode = 503;
            }
            elseif ( isset($response['response']['code']) && $response['response']['code'] <> 200 )
            {
                $errorMessage = $response['response']['code'] . " - " . $response['response']['message'];
                $errorCode = $response['response']['code'];
            }
            
            if ( isset($errorMessage) )
            {//set details inside file so user can know what happened
                $itemHandler->setError($errorCode, $errorMessage);
                $itemHandler->incrementRetries();
                //$meta = wp_get_attachment_metadata($ID);
                //$meta['ShortPixelImprovement'] = 'Error: <i>' . $errorMessage . '</i>';
                //unset($meta['ShortPixel']['WaitingProcessing']);
                //wp_update_attachment_metadata($ID, $meta);
                return array("response" => array("code" => $errorCode, "message" => $errorMessage ));
            }

            return $response;//this can be an error or a good response
        }
                
        return $response;
    }

    /**
     * parse the JSON response
     * @param $response
     * @return parsed array
     */
    public function parseResponse($response) {
        $data = $response['body'];
        $data = ShortPixelTools::parseJSON($data);
        return (array)$data;
    }

    /**
     * handles the processing of the image using the ShortPixel API
     * @param array $URLs - list of urls to send to API
     * @param array $PATHs - list of local paths for the images
     * @param ShortPixelMetaFacade $itemHandler - the Facade that manages different types of image metadatas: MediaLibrary (postmeta table), ShortPixel custom (shortpixel_meta table)
     * @return status/message array
     */
    public function processImage($URLs, $PATHs, $itemHandler = null) {    
        return $this->processImageRecursive($URLs, $PATHs, $itemHandler, 0);       
    }
    
    /**
     * handles the processing of the image using the ShortPixel API - cals itself recursively until success
     * @param array $URLs - list of urls to send to API
     * @param array $PATHs - list of local paths for the images
     * @param ShortPixelMetaFacade $itemHandler - the Facade that manages different types of image metadatas: MediaLibrary (postmeta table), ShortPixel custom (shortpixel_meta table)
     * @param type $startTime - time of the first call
     * @return status/message array
     */
    private function processImageRecursive($URLs, $PATHs, $itemHandler = null, $startTime = 0) 
    {    
        //#$meta = wp_get_attachment_metadata($ID);
        
        $PATHs = self::CheckAndFixImagePaths($PATHs);//check for images to make sure they exist on disk
        if ( $PATHs === false ) {
            $msg = 'The file(s) do not exist on disk.';
            //#$meta['ShortPixelImprovement'] = $msg;
            //#wp_update_attachment_metadata($ID, $meta);
            $itemHandler->setError(self::ERR_FILE_NOT_FOUND, $msg );
            return array("Status" => self::STATUS_SKIP, "Message" => $msg, "Silent" => $itemHandler->getType() == ShortPixelMetaFacade::CUSTOM_TYPE ? 1 : 0);
        }
        
        //tries multiple times (till timeout almost reached) to fetch images.
        if($startTime == 0) { 
            $startTime = time(); 
        }        
        $apiRetries = $this->_settings->apiRetries;
        
        if( time() - $startTime > MAX_EXECUTION_TIME) 
        {//keeps track of time
            if ( $apiRetries > MAX_API_RETRIES )//we tried to process this time too many times, giving up...
            {
                //#$meta['ShortPixelImprovement'] = 'Timed out while processing.';
                //#unset($meta['ShortPixel']['WaitingProcessing']);
                //#wp_update_attachment_metadata($ID, $meta);
                $itemHandler->setError(self::ERR_TIMEOUT, 'Timed out while processing.');
                $itemHandler->incrementRetries();
                $this->_settings->apiRetries = 0; //fai added to solve a bug?
                return array("Status" => self::STATUS_SKIP, 
                             "Message" => ($itemHandler->getType() == ShortPixelMetaFacade::CUSTOM_TYPE ? "Image ID: " : "Media ID: ")
                                         . $itemHandler->getId() .' Skip this image, try the next one.');                
            }
            else
            {//we'll try again next time user visits a page on admin panel
                $apiRetries++;
                $this->_settings->apiRetries = $apiRetries;
                return array("Status" => self::STATUS_RETRY, "Message" => 'Timed out while processing. (pass '.$apiRetries.')', 
                             "Count" => $apiRetries);
            }
        }
        
        //#$compressionType = isset($meta['ShortPixel']['type']) ? ($meta['ShortPixel']['type'] == 'lossy' ? 1 : 0) : $this->_settings->compressionType;
        $meta = $itemHandler->getMeta();
        $compressionType = $meta->getCompressionType() !== null ? $meta->getCompressionType() : $this->_settings->compressionType;
        $response = $this->doRequests($URLs, true, $itemHandler, $compressionType);//send requests to API

        if($response['response']['code'] != 200)//response <> 200 -> there was an error apparently?
            return array("Status" => self::STATUS_FAIL, "Message" => "There was an error and your request was not processed.");
        
        $APIresponse = $this->parseResponse($response);//get the actual response from API, its an array
        
        if ( isset($APIresponse[0]) ) //API returned image details
        {
            foreach ( $APIresponse as $imageObject ) {//this part makes sure that all the sizes were processed and ready to be downloaded
                if ( $imageObject->Status->Code == 0 || $imageObject->Status->Code == 1  ) {
                    sleep(1);
                    return $this->processImageRecursive($URLs, $PATHs, $itemHandler, $startTime);    
                }        
            }
            
            $firstImage = $APIresponse[0];//extract as object first image
            switch($firstImage->Status->Code) 
            {
            case 2:
                //handle image has been processed
                if(!isset($firstImage->Status->QuotaExceeded)) {
                    $this->_settings->quotaExceeded = 0;//reset the quota exceeded flag
                }
                return $this->handleSuccess($APIresponse, $PATHs, $itemHandler, $compressionType);
            default:
                //handle error
                if ( !file_exists($PATHs[0]) ) {
                    $itemHandler->incrementRetries(2);
                    $err = array("Status" => self::STATUS_NOT_FOUND, "Message" => "File not found on disk. "
                                 . ($itemHandler->getType() == ShortPixelMetaFacade::CUSTOM_TYPE ? "Image " : "Media ")
                                 . " ID: " . $itemHandler->getId());
                }
                elseif ( isset($APIresponse[0]->Status->Message) ) {
                    //return array("Status" => self::STATUS_FAIL, "Message" => "There was an error and your request was not processed (" . $APIresponse[0]->Status->Message . "). REQ: " . json_encode($URLs));                
                    $err = array("Status" => self::STATUS_FAIL, "Code" => (isset($APIresponse[0]->Status->Code) ? $APIresponse[0]->Status->Code : ""), 
                                 "Message" => "There was an error and your request was not processed (" . $APIresponse[0]->Status->Message . ")");                
                } else {
                    $err = array("Status" => self::STATUS_FAIL, "Message" => "There was an error and your request was not processed");
                }
                
                $itemHandler->incrementRetries();
                $meta = $itemHandler->getMeta();
                if($meta->getRetries() >= MAX_FAIL_RETRIES) {
                    $meta->setStatus($APIresponse[0]->Status->Code);
                    $meta->setMessage($APIresponse[0]->Status->Message);
                    $itemHandler->updateMeta($meta);
                }
                return $err;
            }
        }
        
        if(!isset($APIresponse['Status'])) {
            WpShortPixel::log("API Response Status unfound : " . json_encode($APIresponse));
            return array("Status" => self::STATUS_FAIL, "Message" => "Unecognized API response. Please contact support.");
        } else {
            switch($APIresponse['Status']->Code) 
            {            
                case -403:
                    @delete_option('bulkProcessingStatus');
                    $this->_settings->quotaExceeded = 1;
                    return array("Status" => self::STATUS_QUOTA_EXCEEDED, "Message" => "Quota exceeded.");
                    break;                
            }

            //sometimes the response array can be different
            if (is_numeric($APIresponse['Status']->Code)) {
                return array("Status" => self::STATUS_FAIL, "Message" => $APIresponse['Status']->Message);
            } else {
                return array("Status" => self::STATUS_FAIL, "Message" => $APIresponse[0]->Status->Message);
            }
        }
    }
    
    /**
     * sets the preferred protocol of URL using the globally set preferred protocol.
     * If  global protocol not set, sets it by testing the download of a http test image from ShortPixel site. 
     * If http works then it's http, otherwise sets https
     * @param string $url
     * @param bool $reset - forces recheck even if preferred protocol is already set
     * @return url with the preferred protocol
     */
    public function setPreferredProtocol($url, $reset = false) {
        //switch protocol based on the formerly detected working protocol
        if($this->_settings->downloadProto == '' || $reset) {
            //make a test to see if the http is working
            $testURL = 'http://api.shortpixel.com/img/connection-test-image.png';
            $result = download_url($testURL, 10);
            $this->_settings->downloadProto = is_wp_error( $result ) ? 'https' : 'http';
        }
        return $this->_settings->downloadProto == 'http' ? 
                str_replace('https://', 'http://', $url) :
                str_replace('http://', 'https://', $url);


    }
    
    /**
     * handles the download of an optimized image from ShortPixel API
     * @param type $fileData - info about the file
     * @param int $compressionType - 1 - lossy, 0 - lossless
     * @return status/message array
     */
    private function handleDownload($fileData, $compressionType){
        //var_dump($fileData);
        if($compressionType)
        {
            $fileType = "LossyURL";
            $fileSize = "LossySize";
            $webpType = "WebPLossyURL";
            $webpSize = "WebPLossySize";
        }    
        else
        {
            $fileType = "LosslessURL";
            $fileSize = "LoselessSize";
            $webpType = "WebPLosslessURL";
            $webpSize = "WebPLosslessSize";
        }
        
        $downloadTimeout = max(ini_get('max_execution_time') - 10, 15);        
        
        if($webpType !== "NA") {
            $webpURL = $this->setPreferredProtocol(urldecode($fileData->$webpType));
            $webpTempFile = download_url($webpURL, $downloadTimeout);
        } 
        $webpTempFile = is_wp_error( $webpTempFile ) ? "NA" : $webpTempFile;
        
        //if there is no improvement in size then we do not download this file
        if ( $fileData->OriginalSize == $fileData->$fileSize )
            return array("Status" => self::STATUS_UNCHANGED, "Message" => "File wasn't optimized so we do not download it.", "WebP" => $webpTempFile);
        
        $correctFileSize = $fileData->$fileSize;
        $fileURL = $this->setPreferredProtocol(urldecode($fileData->$fileType));
 
        $tempFile = download_url($fileURL, $downloadTimeout);
        if(is_wp_error( $tempFile )) 
        { //try to switch the default protocol
            $fileURL = $this->setPreferredProtocol(urldecode($fileData->$fileType), true); //force recheck of the protocol
            $tempFile = download_url($fileURL, $downloadTimeout);
        }    

        //on success we return this
        $returnMessage = array("Status" => self::STATUS_SUCCESS, "Message" => $tempFile, "WebP" => $webpTempFile);
        
        if ( is_wp_error( $tempFile ) ) {
            @unlink($tempFile);
            $returnMessage = array(
                "Status" => self::STATUS_ERROR, 
                "Message" => "Error downloading file ({$fileData->$fileType}) " . $tempFile->get_error_message());
        } 
        //check response so that download is OK
        elseif( filesize($tempFile) != $correctFileSize) {
            $size = filesize($tempFile);
            @unlink($tempFile);
            $returnMessage = array(
                "Status" => self::STATUS_ERROR, 
                "Message" => "Error downloading file - incorrect file size (downloaded: {$size}, correct: {$correctFileSize} )");
        }
        elseif (!file_exists($tempFile)) {
            $returnMessage = array("Status" => self::STATUS_ERROR, "Message" => "Unable to locate downloaded file " . $tempFile);
        }
        return $returnMessage;        
    }

    /**
     * handles a successful optimization, setting metadata and handling download for each file in the set
     * @param type $APIresponse - the response from the API - contains the optimized images URLs to download
     * @param type $PATHs - list of local paths for the files
     * @param ShortPixelMetaFacade $itemHandler - the Facade that manages different types of image metadatas: MediaLibrary (postmeta table), ShortPixel custom (shortpixel_meta table)
     * @param int $compressionType - 1 - lossy, 0 - lossless
     * @return status/message array
     */
    private function handleSuccess($APIresponse, $PATHs, $itemHandler, $compressionType) {
        $counter = $savedSpace =  $originalSpace =  $optimizedSpace =  $averageCompression = 0;
        $NoBackup = true;
                
        $fileType = ( $compressionType ) ? "LossySize" : "LoselessSize";
        
        //download each file from array and process it
        foreach ( $APIresponse as $fileData )
        {
            if ( $fileData->Status->Code == 2 ) //file was processed OK
            {
                if ( $counter == 0 ) { //save percent improvement for main file
                    $percentImprovement = $fileData->PercentImprovement;
                } else { //count thumbnails only
                    $this->_settings->thumbsCount = $this->_settings->thumbsCount + 1;
                }
                $downloadResult = $this->handleDownload($fileData,$compressionType);
                
                if ( $downloadResult['Status'] == self::STATUS_SUCCESS ) {
                    $tempFiles[$counter] = $downloadResult;
                } 
                //when the status is STATUS_UNCHANGED we just skip the array line for that one
                elseif( $downloadResult['Status'] == self::STATUS_UNCHANGED ) {
                    //this image is unchanged so won't be copied below, only the optimization stats need to be computed
                    $originalSpace += $fileData->OriginalSize;
                    $optimizedSpace += $fileData->$fileType;
                    $tempFiles[$counter] = $downloadResult;
                }
                else { 
                    return array("Status" => $downloadResult['Status'], "Message" => $downloadResult['Message']);
                }
                
            }    
            else { //there was an error while trying to download a file
                $tempFiles[$counter] = "";
            }
            $counter++;
        }
        
        //figure out in what SubDir files should land
        //#$SubDir = $this->returnSubDir(get_attached_file($ID));
        $fullSubDir = str_replace(wp_normalize_path(get_home_path()), "", wp_normalize_path(dirname($itemHandler->getMeta()->getPath()))) . '/';
        //die("Uploads base: " . SP_UPLOADS_BASE . " FullSubDir: ". $fullSubDir . " PATH: " . dirname($itemHandler->getMeta()->getPath()));
        $SubDir = ShortPixelMetaFacade::returnSubDir($itemHandler->getMeta()->getPath(), $itemHandler->getType());
        
        //if backup is enabled - we try to save the images
        if( $this->_settings->backupImages )
        {
            $source = $PATHs; //array with final paths for these files

            if( !file_exists(SP_BACKUP_FOLDER) && !@mkdir(SP_BACKUP_FOLDER, 0777, true) ) {//creates backup folder if it doesn't exist
                return array("Status" => self::STATUS_FAIL, "Message" => "Backup folder does not exist and it cannot be created");
            }
            //create subdir in backup folder if needed
            @mkdir( SP_BACKUP_FOLDER . '/' . $fullSubDir, 0777, true);
            
            foreach ( $source as $fileID => $filePATH )//create destination files array
            {
                $destination[$fileID] = SP_BACKUP_FOLDER . '/' . $fullSubDir . self::MB_basename($source[$fileID]);     
            }
            //die("IZ BACKUP: " . SP_BACKUP_FOLDER . '/' . $SubDir . var_dump($destination));
            
            //now that we have original files and where we should back them up we attempt to do just that
            if(is_writable(SP_BACKUP_FOLDER)) 
            {
                foreach ( $destination as $fileID => $filePATH )
                {
                    if ( !file_exists($filePATH) )
                    {         
                        if ( !@copy($source[$fileID], $filePATH) )
                        {//file couldn't be saved in backup folder
                            $msg = 'Cannot save file <i>' . self::MB_basename($source[$fileID]) . '</i> in backup directory';
                            //#ShortPixelAPI::SaveMessageinMetadata($ID, $msg);
                            $itemHandler->setError(self::ERR_SAVE_BKP, $msg);
                            $itemHandler->incrementRetries();
                            return array("Status" => self::STATUS_FAIL, "Message" => $msg);
                        }
                    }
                }
                $NoBackup = true;
            } else {//cannot write to the backup dir, return with an error
                //#ShortPixelAPI::SaveMessageinMetadata($ID, 'Cannot save file in backup directory');
                $msg = 'Cannot save file in backup directory';
                $itemHandler->setError(self::ERR_SAVE_BKP, $msg);
                $itemHandler->incrementRetries();
                return array("Status" => self::STATUS_FAIL, "Message" => $msg);
            }

        }//end backup section

        $writeFailed = 0;
        $firstImage = true;
        $width = $height = null;
        $resize = $this->_settings->resizeImages;
        
        if ( !empty($tempFiles) )
        {
            //overwrite the original files with the optimized ones
            foreach ( $tempFiles as $tempFileID => $tempFile )
            { 
                if(!is_array($tempFile)) continue;
                
                $targetFile = $PATHs[$tempFileID];

                if($tempFile['Status'] == self::STATUS_SUCCESS) { //if it's unchanged it will still be in the array but only for WebP (handled below)
                    $tempFilePATH = $tempFile["Message"];
                    if ( file_exists($tempFilePATH) && file_exists($PATHs[$tempFileID]) && is_writable($PATHs[$tempFileID]) ) {
                        copy($tempFilePATH, $targetFile);
                        if($firstImage) { //this is the main image
                            $firstImage = false;
                            if($resize) {
                                $size = getimagesize($PATHs[$tempFileID]);
                                $width = $size[0];
                                $height = $size[1];
                            }
                        }
                        //Calculate the saved space
                        $fileData = $APIresponse[$tempFileID];
                        $savedSpace += $fileData->OriginalSize - $fileData->$fileType;
                        $originalSpace += $fileData->OriginalSize;
                        $optimizedSpace += $fileData->$fileType;
                        $averageCompression += $fileData->PercentImprovement;
                        WPShortPixel::log("HANDLE SUCCESS: Image " . $PATHs[$tempFileID] . " original size: ".$fileData->OriginalSize . " optimized: " . $fileData->$fileType);

                        //add the number of files with < 5% optimization
                        if ( ( ( 1 - $APIresponse[$tempFileID]->$fileType/$APIresponse[$tempFileID]->OriginalSize ) * 100 ) < 5 ) {
                            $this->_settings->under5Percent++; 
                        }
                    } 
                    else {
                        $writeFailed++;
                    }
                    @unlink($tempFilePATH);
                }

                $tempWebpFilePATH = $tempFile["WebP"];
                if(file_exists($tempWebpFilePATH)) {
                    $targetWebPFile = dirname($targetFile) . '/' . basename($targetFile, '.' . pathinfo($targetFile, PATHINFO_EXTENSION)) . ".webp";
                    copy($tempWebpFilePATH, $targetWebPFile);
                }
                @unlink($tempWebpFilePATH);
            }        
            
            if ( $writeFailed > 0 )//there was an error
            {
                $msg = 'Optimized version of ' . $writeFailed . ' file(s) couldn\'t be updated.';
                //#ShortPixelAPI::SaveMessageinMetadata($ID, 'Error: optimized version of ' . $writeFailed . ' file(s) couldn\'t be updated.');
                $itemHandler->setError(self::ERR_SAVE, $msg);
                $itemHandler->incrementRetries();
                update_option('bulkProcessingStatus', "error");
                return array("Status" => self::STATUS_FAIL, "Code" =>"write-fail", "Message" => $msg);
            }
        } elseif( 0 + $fileData->PercentImprovement < 5) {
            $this->_settings->under5Percent++; 
        }
        //old average counting
        $this->_settings->savedSpace += $savedSpace;
        $averageCompression = $this->_settings->averageCompression * $this->_settings->fileCount /  ($this->_settings->fileCount + count($APIresponse));
        $this->_settings->averageCompression = $averageCompression;
        $this->_settings->fileCount += count($APIresponse);
        //new average counting
        $this->_settings->totalOriginal += $originalSpace;
        $this->_settings->totalOptimized += $optimizedSpace;
        
        //update metadata for this file
        $meta = $itemHandler->getMeta();
//        die(var_dump($percentImprovement));
        if($meta->getThumbsTodo()) {
            $percentImprovement = $meta->getImprovementPercent();
        }
        $meta->setMessage($originalSpace 
                ? number_format(100.0 - 100.0 * $optimizedSpace / $originalSpace, 2)
                : "Couldn't compute thumbs optimization percent. Main image: " . $percentImprovement);
        WPShortPixel::log("HANDLE SUCCESS: Image optimization: ".$meta->getMessage());
        $meta->setCompressionType($compressionType);
        $meta->setCompressedSize(filesize($meta->getPath()));
        $meta->setKeepExif($this->_settings->keepExif);
        $meta->setTsOptimized(date("Y-m-d H:i:s"));
        $meta->setThumbsOpt(($meta->getThumbsTodo() ||  $this->_settings->processThumbnails) ? count($meta->getThumbs()) : 0);
        $meta->setThumbsTodo(false);
        if($width && $height) {
            $meta->setActualWidth($width);
            $meta->setActualHeight($height);
        }
        $meta->setRetries($meta->getRetries() + 1);
        $meta->setBackup(!$NoBackup);
        $meta->setStatus(2);
        
        $itemHandler->updateMeta($meta);
        if(!$originalSpace) { //das kann nicht sein, alles klar?!
            throw new Exception("OriginalSpace = 0. APIResponse" . json_encode($APIresponse));
        }
        
        //we reset the retry counter in case of success
        $this->_settings->apiRetries = 0;
        
        return array("Status" => self::STATUS_SUCCESS, "Message" => 'Success: No pixels remained unsqueezed :-)', "PercentImprovement" => $meta->getMessage());
    }//end handleSuccess
        
    /**
     * a basename alternative that deals OK with multibyte charsets (e.g. Arabic)
     * @param string $Path
     * @return string
     */
    static public function MB_basename($Path){
        $Separator = " qq ";
        $Path = preg_replace("/[^ ]/u", $Separator."\$0".$Separator, $Path);
        $Base = basename($Path);
        $Base = str_replace($Separator, "", $Base);
        return $Base;  
    }
    
    /**
     * sometimes, the paths to the files as defined in metadata are wrong, we try to automatically correct them
     * @param type $PATHs
     * @return boolean|string
     */
    static public function CheckAndFixImagePaths($PATHs){

        $ErrorCount = 0;
        $uploadDir = wp_upload_dir();
        $Tmp = explode("/", $uploadDir['basedir']);
        $TmpCount = count($Tmp);
        $StichString = $Tmp[$TmpCount-2] . "/" . $Tmp[$TmpCount-1];
        //files exist on disk?
        foreach ( $PATHs as $Id => $File )
        {
            //we try again with a different path
            if ( !file_exists($File) ){
                //$NewFile = $uploadDir['basedir'] . "/" . substr($File,strpos($File, $StichString));//+strlen($StichString));
                $NewFile = $uploadDir['basedir'] . substr($File,strpos($File, $StichString)+strlen($StichString));
                if (file_exists($NewFile)) {
                    $PATHs[$Id] = $NewFile;
                } else {
                    $ErrorCount++;
                }
            }
        }
        
        if ( $ErrorCount > 0 ) {
            return false;
        } else {
            return $PATHs;
        }
    }

    static public function getCompressionTypeName($compressionType) {
        return $compressionType == 1 ? 'lossy' : 'lossless';
    }
    
    static private function SaveMessageinMetadata($ID, $Message)
    {
        $meta = wp_get_attachment_metadata($ID);
        $meta['ShortPixelImprovement'] = $Message;
        unset($meta['ShortPixel']['WaitingProcessing']);
        wp_update_attachment_metadata($ID, $meta);
    }
}
