<?php

class WpShortPixelMediaLbraryAdapter {
    
    //count all the processable files in media library (while limiting the results to max 10000)
    public static function countAllProcessableFiles($maxId = PHP_INT_MAX, $minId = 0){
        global  $wpdb;
        
        $totalFiles = $mainFiles = $processedMainFiles = $processedTotalFiles = 
        $procLossyMainFiles = $procLossyTotalFiles = $procLosslessMainFiles = $procLosslessTotalFiles = $procUndefMainFiles = $procUndefTotalFiles = $mainUnprocessedThumbs = 0;
        $filesMap = $processedFilesMap = array();
        $limit = self::getOptimalChunkSize();
        $pointer = 0;
        $filesWithErrors = array();
        
        //count all the files, main and thumbs 
        while ( 1 ) {
            $ids = self::getPostIdsChunk($minId, $maxId, $pointer, $limit);
            if($ids === null) { 
                break; //we parsed all the results
            } 
            elseif(count($ids) == 0) {
                $pointer += $limit;
                continue;
            }
                        
            $filesList= $wpdb->get_results("SELECT * FROM " . $wpdb->prefix . "postmeta
                                        WHERE post_id IN (" . implode(',', $ids) . ")
                                          AND ( meta_key = '_wp_attached_file' OR meta_key = '_wp_attachment_metadata' )");
             
            foreach ( $filesList as $file ) 
            {
                if ( $file->meta_key == "_wp_attached_file" )
                {//count pdf files only
                    $extension = substr($file->meta_value, strrpos($file->meta_value,".") + 1 );
                    if ( $extension == "pdf" && !isset($filesMap[$file->meta_value]))
                    {
                        $totalFiles++;
                        $mainFiles++;
                        $filesMap[$file->meta_value] = 1;
                    }
                }
                else //_wp_attachment_metadata
                {
                    $attachment = unserialize($file->meta_value);
                    //processable
                    $isProcessable = false;
                    if(isset($attachment['file']) && !isset($filesMap[$attachment['file']]) && WPShortPixel::isProcessablePath($attachment['file'])){
                        $isProcessable = true;
                        if ( isset($attachment['sizes']) ) {
                            $totalFiles += count($attachment['sizes']);            
                        }
                        if ( isset($attachment['file']) )
                        {
                            $totalFiles++;
                            $mainFiles++;
                            $filesMap[$attachment['file']] = 1;
                        }
                    }
                    //processed
                    if (isset($attachment['ShortPixelImprovement'])
                        && ($attachment['ShortPixelImprovement'] > 0 || $attachment['ShortPixelImprovement'] === 0.0 || $attachment['ShortPixelImprovement'] === "0")
                        //for PDFs there is no file field so just let it pass.
                        && (!isset($attachment['file']) || !isset($processedFilesMap[$attachment['file']])) ) { 
                        
                        //add main file to counts
                        $processedMainFiles++;            
                        $processedTotalFiles++;            
                        $type = isset($attachment['ShortPixel']['type']) ? $attachment['ShortPixel']['type'] : null;
                        if($type == 'lossy') {
                            $procLossyMainFiles++;
                            $procLossyTotalFiles++;
                        } elseif($type == 'lossless') {
                            $procLosslessMainFiles++;
                            $procLosslessTotalFiles++;
                        } else {
                            $procUndefMainFiles++;
                            $procUndefTotalFiles++;
                        }
                        
                        //get the thumbs processed for that attachment
                        $thumbs = $allThumbs = 0;
                        if ( isset($attachment['ShortPixel']['thumbsOpt']) ) {
                            $thumbs = $attachment['ShortPixel']['thumbsOpt'];
                        } 
                        elseif ( isset($attachment['sizes']) ) {
                            $thumbs = count($attachment['sizes']);            
                        } 
                        if ( isset($attachment['sizes']) && count($attachment['sizes']) > $thumbs) {
                            $mainUnprocessedThumbs++;
                        } 
                        
                        //increment with thumbs processed
                        $processedTotalFiles += $thumbs;
                        if($type == 'lossy') {
                           $procLossyTotalFiles += $thumbs;
                        } else {
                           $procLosslessTotalFiles += $thumbs;
                        }
                        
                        if ( isset($attachment['file']) ) {
                            $processedFilesMap[$attachment['file']] = 1;
                        }
                    }
                    elseif($isProcessable && isset($attachment['ShortPixelImprovement']) && count($filesWithErrors) < 50) {
                        $filePath = explode("/", $attachment["file"]);
                        $name = is_array($filePath)? $filePath[count($filePath) - 1] : $file->post_id;
                        $filesWithErrors[$file->post_id] = array('Name' => $name, 'Message' => $attachment['ShortPixelImprovement']);
                    }

                }
            }   
            unset($filesList);
            $pointer += $limit;
            
        }//end while

        return array("totalFiles" => $totalFiles, "mainFiles" => $mainFiles, 
                     "totalProcessedFiles" => $processedTotalFiles, "mainProcessedFiles" => $processedMainFiles,
                     "totalProcLossyFiles" => $procLossyTotalFiles, "mainProcLossyFiles" => $procLossyMainFiles,
                     "totalProcLosslessFiles" => $procLosslessTotalFiles, "mainProcLosslessFiles" => $procLosslessMainFiles,
                     "totalMlFiles" => $totalFiles, "mainMlFiles" => $mainFiles,
                     "totalProcessedMlFiles" => $processedTotalFiles, "mainProcessedMlFiles" => $processedMainFiles,
                     "totalProcLossyMlFiles" => $procLossyTotalFiles, "mainProcLossyMlFiles" => $procLossyMainFiles,
                     "totalProcLosslessMlFiles" => $procLosslessTotalFiles, "mainProcLosslessMlFiles" => $procLosslessMainFiles,
                     "totalProcUndefMlFiles" => $procUndefTotalFiles, "mainProcUndefMlFiles" => $procUndefMainFiles,
                     "mainUnprocessedThumbs" => $mainUnprocessedThumbs,
                     "filesWithErrors" => $filesWithErrors
                    );
    }  

    protected static function getOptimalChunkSize() {
        global  $wpdb;
        $cnt = $wpdb->get_results("SELECT count(*) posts FROM " . $wpdb->prefix . "posts");
        $posts = isset($cnt) && count($cnt) > 0 ? $cnt[0]->posts : 0;
        if($posts > 100000) {
            return 20000;
        } elseif ($posts > 50000) {
            return 5000;
        } elseif($posts > 10000) {
            return 2000;
        } else {
            return 500;
        }
    }
        
    protected static function getPostIdsChunk($minId, $maxId, $pointer, $limit) {
        global  $wpdb;
        
        $ids = array();
        $idList = $wpdb->get_results("SELECT ID, post_mime_type FROM " . $wpdb->prefix . "posts
                                    WHERE ( ID <= $maxId AND ID > $minId )
                                    LIMIT $pointer,$limit");
        if ( empty($idList) ) {
            return null;
        }
        foreach($idList as $item) {
            if($item->post_mime_type != '') {
                $ids[] = $item->ID;
            }
        }
        return $ids;
    }

}
