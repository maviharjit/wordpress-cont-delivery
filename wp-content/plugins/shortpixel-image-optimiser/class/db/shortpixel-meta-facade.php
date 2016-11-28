<?php

class ShortPixelMetaFacade {
    const MEDIA_LIBRARY_TYPE = 1;
    const CUSTOM_TYPE = 2;
    
    private $ID;
    private $type;
    private $meta;
    private $spMetaDao;
    private $rawMeta;
    
    public function __construct($ID) {
        if(strpos($ID, 'C-') === 0) {
            $this->ID = substr($ID, 2);
            $this->type = self::CUSTOM_TYPE;
        } else {
            $this->ID = $ID;
            $this->type = self::MEDIA_LIBRARY_TYPE;
        }        
        $this->spMetaDao = new ShortPixelCustomMetaDao(new WpShortPixelDb());
    }
    
    public static function getNewFromRow($item) {
        return new ShortPixelMetaFacade("C-" . $item->id); 
    }
    
    function setRawMeta($rawMeta) {
        if($this->type == self::MEDIA_LIBRARY_TYPE) {
            $this->rawMeta = $rawMeta;
            $this->meta = self::rawMetaToMeta($this->ID, $rawMeta);
        }
    }

    function getMeta($refresh = false) {
        if($refresh || !isset($this->meta)) {
            if($this->type == self::CUSTOM_TYPE) {
                $this->meta = $this->spMetaDao->getMeta($this->ID);
            } else {
                $rawMeta = wp_get_attachment_metadata($this->ID);
                $this->meta = self::rawMetaToMeta($this->ID, $rawMeta);
                $this->rawMeta = $rawMeta;
            }        
        }
        return $this->meta;
    }
    
    private static function rawMetaToMeta($ID, $rawMeta) {
        return new ShortPixelMeta(array(
                    "id" => $ID,
                    "path" => get_attached_file($ID),
                    "webPath" => (isset($rawMeta["file"]) ? $rawMeta["file"] : null),
                    "thumbs" => (isset($rawMeta["sizes"]) ? $rawMeta["sizes"] : array()),
                    "message" =>(isset($rawMeta["ShortPixelImprovement"]) ? $rawMeta["ShortPixelImprovement"] : null),
                    "compressionType" =>(isset($rawMeta["ShortPixel"]["type"]) ? ($rawMeta["ShortPixel"]["type"] == "lossy" ? 1 : 0) : null),
                    "thumbsOpt" =>(isset($rawMeta["ShortPixel"]["thumbsOpt"]) ? $rawMeta["ShortPixel"]["thumbsOpt"] : null),
                    "thumbsTodo" =>(isset($rawMeta["ShortPixel"]["thumbsTodo"]) ? $rawMeta["ShortPixel"]["thumbsTodo"] : false),
                    "backup" => !isset($rawMeta['ShortPixel']['NoBackup']),
                    "status" => (!isset($rawMeta["ShortPixel"]) ? 0 
                                 : (isset($rawMeta["ShortPixelImprovement"]) && is_numeric($rawMeta["ShortPixelImprovement"]) ? 2 
                                    : (isset($rawMeta["ShortPixel"]["WaitingProcessing"]) ? 1 
                                       : -500))),
                    "retries" =>(isset($rawMeta["ShortPixel"]["Retries"]) ? $rawMeta["ShortPixel"]["Retries"] : 0),
                ));
    }

    function check() {
        if($this->type == self::CUSTOM_TYPE) {
            $this->meta = $this->spMetaDao->getMeta($this->ID);
            return $this->meta;
        } else {
            return wp_get_attachment_url($this->ID);
        }        
    }
    
    function updateMeta($newMeta = null) {
        if($newMeta) {
            $this->meta = $newMeta;
        }
        if($this->type == self::CUSTOM_TYPE) {
            $this->spMetaDao->update($this->meta);
            if($this->meta->getExtMetaId()) {
                ShortPixelNextGenAdapter::updateImageSize($this->meta->getExtMetaId(), $this->meta->getPath());
            }
        }
        elseif($this->type == ShortPixelMetaFacade::MEDIA_LIBRARY_TYPE) {
            $duplicates = ShortPixelMetaFacade::getWPMLDuplicates($this->ID);
            foreach($duplicates as $_ID) {
                $rawMeta = wp_get_attachment_metadata($_ID);
                if(!is_array($rawMeta)) {
                    $rawMeta = array("previous_meta" => $rawMeta, 'ShortPixel' => array());
                }
                $rawMeta['ShortPixel']['type'] = ($this->meta->getCompressionType() == 1 ? "lossy" : "lossless");
                $rawMeta['ShortPixel']['exifKept'] = $this->meta->getKeepExif();
                $rawMeta['ShortPixel']['date'] = date("Y-m-d", strtotime($this->meta->getTsOptimized()));
                //thumbs were processed if settings or if they were explicitely requested
                $rawMeta['ShortPixel']['thumbsOpt'] = $this->meta->getThumbsOpt();
                //if thumbsTodo - this means there was an explicit request to process thumbs for an image that was previously processed without
                // don't update the ShortPixelImprovement ratio as this is only calculated based on main image
                if($this->meta->getThumbsTodo()) {
                    $rawMeta['ShortPixel']['thumbsTodo'] = true;
                } else {
                    $rawMeta['ShortPixelImprovement'] = "".round($this->meta->getImprovementPercent(),2);
                    unset($rawMeta['ShortPixel']['thumbsTodo']);
                }
                if($this->meta->getActualWidth() && $this->meta->getActualHeight()) {
                    $rawMeta['width'] = $this->meta->getActualWidth();
                    $rawMeta['height'] = $this->meta->getActualHeight();
                }
                if(!$this->meta->getBackup()) {
                    $rawMeta['ShortPixel']['NoBackup'] = true;
                }
                if($this->meta->getStatus() !== 1) {
                    unset($rawMeta['ShortPixel']['WaitingProcessing']);
                }
                wp_update_attachment_metadata($this->ID, $rawMeta);
            }
        }        
    }

    function deleteMeta() {
        if($this->type == self::CUSTOM_TYPE) {
            throw new Exception("Not implemented 1");
        } else {
            unset($this->rawMeta['ShortPixel']);
            wp_update_attachment_metadata($this->ID, $this->rawMeta);
        }        
    }
    
    function incrementRetries($count = 1) {
        if($this->type == self::CUSTOM_TYPE) {
            $this->meta->setRetries($this->meta->getRetries() + $count);
            $this->updateMeta();
        } else {
            $this->rawMeta['ShortPixel']['Retries'] = isset($this->rawMeta['ShortPixel']['Retries']) ? $this->rawMeta['ShortPixel']['Retries'] + $count : $count;
            $this->meta->setRetries($this->rawMeta['ShortPixel']['Retries']);
            wp_update_attachment_metadata($this->ID, $this->rawMeta);
        }        
    }
    
    function setWaitingProcessing($status = true) {
        if($status) {
            $this->meta->setStatus(1);
        }
        if($this->type == self::CUSTOM_TYPE) {
            $this->updateMeta();
        } else {
            if($status) {
                $this->rawMeta['ShortPixel']['WaitingProcessing'] = true;
            } else {
                unset($this->rawMeta['ShortPixel']['WaitingProcessing']);
            }
            wp_update_attachment_metadata($this->ID, $this->rawMeta);
        }        
    }
    
    function setError($errorCode, $errorMessage) {
        $this->meta->setMessage('Error: <i>' . $errorMessage . '</i>');
        $this->meta->setStatus($errorCode);
        if($this->type == self::CUSTOM_TYPE) {
            if($errorCode == ShortPixelAPI::ERR_FILE_NOT_FOUND) {
                $this->spMetaDao->delete($this->meta);
            } else {
                $this->spMetaDao->update($this->meta);
            }
        } else {
            $this->rawMeta['ShortPixelImprovement'] = $this->meta->getMessage();
            unset($this->rawMeta['ShortPixel']['WaitingProcessing']);
            wp_update_attachment_metadata($this->ID, $this->rawMeta);
        }        
    }
    
    public function getURLsAndPATHs($processThumbnails, $onlyThumbs = false) {
        if($this->type == self::CUSTOM_TYPE) {
            $meta = $this->getMeta();
            $urlList[] = str_replace(get_home_path(), network_site_url("/"), $meta->getPath());
            $filePaths[] = $meta->getPath();
            return array("URLs" => $urlList, "PATHs" => $filePaths);
        } else {
            if ( !parse_url(WP_CONTENT_URL, PHP_URL_SCHEME) ) {//no absolute URLs used -> we implement a hack
               $url = get_site_url() . wp_get_attachment_url($this->ID);//get the file URL 
            }
            else {
                $url = wp_get_attachment_url($this->ID);//get the file URL
            }
            $urlList[] = $url;
            $path = get_attached_file($this->ID);//get the full file PATH
            $filePath[] = $path;
            $this->addRetina($path, $url, $filePath, $urlList);
            
            $meta = $this->getMeta();
            $sizes = $meta->getThumbs();

            //it is NOT a PDF file and thumbs are processable
            if (    strtolower(substr($filePath[0],strrpos($filePath[0], ".")+1)) != "pdf" 
                 && ($processThumbnails || $onlyThumbs) 
                 && count($sizes)) 
            {
                foreach( $sizes as $thumbnailInfo ) {
                    $tUrl = str_replace(ShortPixelAPI::MB_basename($urlList[0]), $thumbnailInfo['file'], $url);
                    $tPath = str_replace(ShortPixelAPI::MB_basename($filePath[0]), $thumbnailInfo['file'], $path);
                    $urlList[] = $tUrl;
                    $filePath[] = $tPath;
                    $this->addRetina($tPath, $tUrl, $filePath, $urlList);
                }            
            }
            if(!count($sizes)) {
                WPShortPixel::log("getURLsAndPATHs: no meta sizes for ID " . $this->ID . " : " . json_encode($this->rawMeta));
            }
            
            if($onlyThumbs) { //remove the main image
                array_shift($urlList);
                array_shift($filePath);
            }
        }
        return array("URLs" => $urlList, "PATHs" => $filePath);
    }
    
    protected function addRetina($path, $url, &$fileList, &$urlList) {
        $ext = pathinfo($path, PATHINFO_EXTENSION);
        $retinaPath = substr($path, 0, strlen($path) - 1 - strlen($ext)) . "@2x." . $ext;
        if(file_exists($retinaPath)) {
//            echo($retinaPath . " added\n");
            $urlList[] = substr($url, 0, strlen($url) -1 - strlen($ext)) . "@2x." . $ext;
            $fileList[] = $retinaPath;   
        }
    }

    public static function getWPMLDuplicates( $id ) {
        global $wpdb;
        
        $parentId = get_post_meta ($id, '_icl_lang_duplicate_of', true );
        if($parentId) $id = $parentId;

        $duplicates = $wpdb->get_col( $wpdb->prepare( "
            SELECT pm.post_id FROM {$wpdb->postmeta} pm
            WHERE pm.meta_value = %s AND pm.meta_key = '_icl_lang_duplicate_of' 
        ", $id ) );
        
        if(!in_array($id, $duplicates)) $duplicates[] = $id;

        $transTable = $wpdb->get_results("SELECT COUNT(1) hasTransTable FROM information_schema.tables WHERE table_schema='{$wpdb->dbname}' AND table_name='{$wpdb->prefix}icl_translations'");
        if(isset($transTable[0]->hasTransTable) && $transTable[0]->hasTransTable > 0) {
            $transGroupId = $wpdb->get_results("SELECT trid FROM {$wpdb->prefix}icl_translations WHERE element_id = {$id}");
            if(count($transGroupId)) {
                $transGroup = $wpdb->get_results("SELECT element_id FROM {$wpdb->prefix}icl_translations WHERE trid = " . $transGroupId[0]->trid);
                foreach($transGroup as $trans) {
                    $duplicates[] = $trans->element_id;
                }
            }
        }
        return array_unique($duplicates);
    }
    
    public static function pathToWebPath($path) {
        //$upl = wp_upload_dir();
        //return str_replace($upl["basedir"], $upl["baseurl"], $path);
        return str_replace(get_home_path(), site_url()."/", $path);
    }

    public static function pathToRootRelative($path) {
        //$upl = wp_upload_dir();
        $pathParts = explode('/', $path);
        unset($pathParts[count($pathParts) - 1]);
        $path = implode('/', $pathParts);
        return str_replace(get_home_path(), "", $path);
    }
    
    public static function filenameToRootRelative($path) {
        return str_replace(get_home_path(), "", $path);
    }
    
    public static function getMaxMediaId() {
        global  $wpdb;
        $queryMax = "SELECT max(post_id) as QueryID FROM " . $wpdb->prefix . "postmeta";
        $resultQuery = $wpdb->get_results($queryMax);
        return $resultQuery[0]->QueryID;
    }
    
    public static function getMinMediaId() {
        global  $wpdb;
        $queryMax = "SELECT min(post_id) as QueryID FROM " . $wpdb->prefix . "postmeta";
        $resultQuery = $wpdb->get_results($queryMax);
        return $resultQuery[0]->QueryID;
    }
    
    public static function isCustomQueuedId($id) {
        return substr($id, 0, 2) == "C-";
    }
    
    public static function stripQueuedIdType($id) {
        return intval(substr($id, 2));
    }
    
    public function getQueuedId() {
        return self::queuedId($this->type, $this->ID);
    }
    
    public static function queuedId($type, $id) {
        return ($type == self::CUSTOM_TYPE ? "C-" : "") . $id;
    }
    
    function getId() {
        return $this->ID;
    }

    function getType() {
        return $this->type;
    }

    function setId($ID) {
        $this->ID = $ID;
    }

    function setType($type) {
        $this->type = $type;
    }
    
    function getRawMeta() {
        return $this->rawMeta;
    }
    
    /**
     * return subdir for that particular attached file - if it's media library then last 3 path items, otherwise substract the uploads path
     * Has trailing directory separator (/)
     * @param type $file
     * @return string
     */
    static public function returnSubDir($file, $type)
    {
        if(strstr($file, get_home_path())) {
            $path = str_replace( get_home_path(), "", $file);
        } else {
            $path =  (substr($file, 1));
        }
        $pathArr = explode('/', $path);
        unset($pathArr[count($pathArr) - 1]);
        return implode('/', $pathArr) . '/';
    } 
    
    public static function isMediaSubfolder($path) {
        $uploadDir = wp_upload_dir();
        $uploadBase = $uploadDir["basedir"];
        $uploadPath = $uploadDir["path"];
        //contains the current media upload path
        if(ShortPixelFolder::checkFolderIsSubfolder($uploadPath, $path)) {
            return true;
        }
        //contains one of the year subfolders of the media library
        if(strpos($path, $uploadPath) == 0) {
            $pathArr = explode('/', str_replace($uploadBase . '/', "", $path));
            if(   count($pathArr) >= 1 
               && is_numeric($pathArr[0]) && $pathArr[0] > 1900 && $pathArr[0] < 2100 //contains the year subfolder
               && (   count($pathArr) == 1 //if there is another subfolder then it's the month subfolder
                   || (is_numeric($pathArr[1]) && $pathArr[1] > 0 && $pathArr[1] < 13) )) {
                return true;
            }
        }
        return false;
    }
}
