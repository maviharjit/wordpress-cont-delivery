<?php

class ShortPixelView {
    
    private $ctrl;
    
    public function __construct($controller) {
        $this->ctrl = $controller;
    }
    
        //handling older
    public function ShortPixelView($controller) {
        $this->__construct($controller);
    }

    public function displayQuotaExceededAlert($quotaData, $averageCompression = false) 
    { ?>    
        <br/>
        <div class="wrap sp-quota-exceeded-alert">
            <?php if($averageCompression) { ?>
            <div style="float:right; margin-top: 10px">
                <div class="bulk-progress-indicator">
                    <div style="margin-bottom:5px">Average reduction</div>
                    <div id="sp-avg-optimization"><input type="text" id="sp-avg-optimization-dial" value="<?php echo("" . round($averageCompression))?>" class="dial"></div>
                    <script>
                        jQuery(function() {
                            ShortPixel.percentDial("#sp-avg-optimization-dial", 60);
                        });
                    </script>
                </div>
            </div>
            <?php } ?>
            <h3>Quota Exceeded</h3>
            <p>The plugin has optimized <strong><?php echo(number_format($quotaData['APICallsMadeNumeric'] + $quotaData['APICallsMadeOneTimeNumeric']));?> images</strong> and stopped because it reached the available quota limit.
            <?php if($quotaData['totalProcessedFiles'] < $quotaData['totalFiles']) { ?>
                <strong><?php echo(number_format($quotaData['mainFiles'] - $quotaData['mainProcessedFiles']));?> images and 
                <?php echo(number_format(($quotaData['totalFiles'] - $quotaData['mainFiles']) - ($quotaData['totalProcessedFiles'] - $quotaData['mainProcessedFiles'])));?> thumbnails</strong> are not yet optimized by ShortPixel.
            <?php } ?></p>
            <div> <!-- style='float:right;margin-top:20px;'> -->
                <a class='button button-primary' href='https://shortpixel.com/login/<?php echo($this->ctrl->getApiKey());?>' target='_blank'>Upgrade</a>
                <input type='button' name='checkQuota' class='button' value='Confirm New Quota' onclick="javascript:window.location.reload();">
            </div>
            <!-- <p>It’s simple to upgrade, just <a href='https://shortpixel.com/login/<?php echo($this->ctrl->getApiKey());?>' target='_blank'>log into your account</a> and see the available options.
            You can immediately start processing 5,000 images/month for &#36;4,99, choose another plan that suits you or <a href='https://shortpixel.com/contact' target='_blank'>contact us</a> for larger compression needs.</p> -->
            <p>Get more image credits by referring ShortPixel to your friends! <a href="https://shortpixel.com/login/<?php echo($this->ctrl->getApiKey());?>/tell-a-friend" target="_blank">Check your account</a> for your unique referral link. For each user that joins, you will receive +100 additional image credits/month.</p>
            
        </div> <?php 
    }
    
    public static function displayApiKeyAlert() 
    { ?>
        <p>In order to start the optimization process, you need to validate your API Key in the <a href="options-general.php?page=wp-shortpixel">ShortPixel Settings</a> page in your WordPress Admin.</p>
        <p>If you don’t have an API Key, you can get one delivered to your inbox, for free.</p>
        <p>Please <a href="https://shortpixel.com/wp-apikey" target="_blank">sign up</a> to get your API key.</p>
    <?php
    }
    
    public static function displayActivationNotice($when = 'activate')  { ?>
        <div class='notice notice-warning' id='short-pixel-notice-<?php echo($when);?>'>
            <?php if($when != 'activate') { ?>
            <div style="float:right;"><a href="javascript:dismissShortPixelNotice('<?php echo($when);?>')" class="button" style="margin-top:10px;">Dismiss</a></div>
            <?php } ?>
            <h3>ShortPixel Optimization</h3> <?php
            switch($when) {
                case '2h' : 
                    echo "Action needed. Please <a href='https://shortpixel.com/wp-apikey' target='_blank'>get your API key</a> to activate your ShortPixel plugin.<BR><BR>";
                    break;
                case '3d':
                    echo "Your image gallery is not optimized. It takes 2 minutes to <a href='https://shortpixel.com/wp-apikey' target='_blank'>get your API key</a> and activate your ShortPixel plugin.<BR><BR>";
                    break;
                case 'activate':
                    self::displayApiKeyAlert();
                    break;
            }
            ?>
        </div>
    <?php
    }
    
    public function displayBulkProcessingForm($quotaData,  $thumbsProcessedCount, $under5PercentCount, $bulkRan, 
                                              $averageCompression, $filesOptimized, $savedSpace, $percent, $customCount) {
        ?>
        <div class="wrap short-pixel-bulk-page">
            <h1>Bulk Image Optimization by ShortPixel</h1>
        <?php
        if ( !$bulkRan ) {
            ?>
            <div class="notice notice-info sp-floating-block sp-full-width">
                <form class='start' action='' method='POST' id='startBulk'>
                    <input type='hidden' id='mainToProcess' value='<?php echo($quotaData['mainFiles'] - $quotaData['mainProcessedFiles']);?>'/>
                    <input type='hidden' id='totalToProcess' value='<?php echo($quotaData['totalFiles'] - $quotaData['totalProcessedFiles']);?>'/>
                    <div class="bulk-stats-container">
                        <h3 style='margin-top:0;'>Your media library</h3>
                        <div class="bulk-label">Original images</div>
                        <div class="bulk-val"><?php echo(number_format($quotaData['mainMlFiles']));?></div><br>
                        <div class="bulk-label">Smaller thumbnails</div>
                        <div class="bulk-val"><?php echo(number_format($quotaData['totalMlFiles'] - $quotaData['mainMlFiles']));?></div>
                        <div style='width:165px; display:inline-block; padding-left: 5px'>
                            <input type='checkbox' id='thumbnails' name='thumbnails' onclick='ShortPixel.checkThumbsUpdTotal(this)' <?php echo($this->ctrl->processThumbnails() ? "checked":"");?>> Include thumbnails
                        </div><br>
                        <?php if($quotaData["totalProcessedMlFiles"] > 0) { ?>
                        <div class="bulk-label bulk-total">Total images</div>
                        <div class="bulk-val bulk-total"><?php echo(number_format($quotaData['totalMlFiles']));?></div>
                        <br><div class="bulk-label">Already optimized originals</div>
                        <div class="bulk-val"><?php echo(number_format($quotaData['mainProcessedMlFiles']));?></div><br>
                        <div class="bulk-label">Already optimized thumbnails</div>
                        <div class="bulk-val"><?php echo(number_format($quotaData['totalProcessedMlFiles'] - $quotaData['mainProcessedMlFiles']));?></div><br>
                        <?php } ?>
                        <div class="bulk-label bulk-total">Total to be optimized</div>
                        <div class="bulk-val bulk-total" id='displayTotal'><?php echo(number_format($quotaData['totalMlFiles'] - $quotaData['totalProcessedMlFiles']));?></div>

                        <?php if($customCount > 0) { ?>
                        <h3 style='margin-bottom:10px;'>Your custom folders</h3>
                        <div class="bulk-label bulk-total">Total to be optimized</div>
                        <div class="bulk-val bulk-total" id='displayTotal'><?php echo(number_format($customCount));?></div>                        
                        <?php  } ?>
                    </div>
                    <?php if($quotaData['totalFiles'] - $quotaData['totalProcessedFiles'] + $customCount > 0) { ?>
                    <div class="bulk-play">
                        <input type='hidden' name='bulkProcess' id='bulkProcess' value='Start Optimizing'/>
                        <a href='javascript:void(0);' onclick="document.getElementById('startBulk').submit();" class='button'>
                            <div style="width: 320px">
                                <div class="bulk-btn-img" class="bulk-btn-img">
                                    <img src='<?php echo(plugins_url( 'shortpixel-image-optimiser/res/img/robo-slider.png' ));?>'/>
                                </div>
                                <div  class="bulk-btn-txt">
                                    <span class="label">Start Optimizing</span><br>
                                    <span class='total'><?php echo(number_format($quotaData['totalFiles'] - $quotaData['totalProcessedFiles']));?></span> images
                                </div>
                                <div class="bulk-btn-img" class="bulk-btn-img">
                                    <img src='<?php echo(plugins_url( 'shortpixel-image-optimiser/res/img/arrow.png' ));?>'/>
                                </div>
                            </div>
                        </a>
                    </div>
                    <?php }  else {?>
                    <div class="bulk-play bulk-nothing-optimize">
                        Nothing to optimize! The images that you add to Media Gallery will be automatically optimized after upload.
                    </div>
                    <?php } ?>
                </form>
            </div>
            <?php if($quotaData['totalFiles'] - $quotaData['totalProcessedFiles'] > 0) { ?>
                <div class='shortpixel-clearfix'></div>
                <div class="bulk-wide">
                    <h3 style='font-size: 1.1em; font-weight: bold;'>After you start the bulk process, in order for the optimization to run, you must keep this page open and your computer running. If you close the page for whatever reason, just turn back to it and the bulk process will resume.</h3>
                </div>
            <?php } ?>
            <div class='shortpixel-clearfix'></div>
            <div class="bulk-text-container">
                <h3>What are Thumbnails?</h3>
                <p>Thumbnails are smaller images usually generated by your WP theme. Most themes generate between 3 and 6 thumbnails for each Media Library image.</p>
                <p>The thumbnails also generate traffic on your website pages and they influence your website's speed.</p>
                <p>It's highly recommended that you include thumbnails in the optimization as well.</p>
            </div>
            <div class="bulk-text-container" style="padding-right:0">
                <h3>How does it work?</h3>
                <p>The plugin processes images starting with the newest ones you uploaded in your Media Library.</p>
                <p>You will be able to pause the process anytime.</p>
                <p><?php echo($this->ctrl->backupImages() ? "<p>Your original images will be stored in a separate back-up folder.</p>" : "");?></p>
                <p>You can watch the images being processed live, right here, after you start optimizing.</p>
            </div>
            <?php
        } elseif($percent) // bulk is paused
        { ?>
            <?php echo($this->displayBulkProgressBar(false, $percent, "", $quotaData['APICallsRemaining'], $this->ctrl->getAverageCompression(), 1, $customCount));?>
            <p>Please see below the optimization status so far:</p>
            <?php $this->displayBulkStats($quotaData['totalProcessedFiles'], $quotaData['mainProcessedFiles'], $under5PercentCount, $averageCompression, $savedSpace);?>
            <?php if($quotaData['totalProcessedFiles'] < $quotaData['totalFiles']) { ?>
                <p><?php echo(number_format($quotaData['mainFiles'] - $quotaData['mainProcessedFiles']));?> images and 
                <?php echo(number_format(($quotaData['totalFiles'] - $quotaData['mainFiles']) - ($quotaData['totalProcessedFiles'] - $quotaData['mainProcessedFiles'])));
                ?> thumbnails are not yet optimized by ShortPixel.</p>
            <?php } ?>
            <p>You can continue optimizing your Media Gallery from where you left, by clicking the Resume processing button. Already optimized images will not be reprocessed.</p>
        <?php
        } else { ?>
            <div class="sp-container">
                <div class='notice notice-success sp-floating-block sp-single-width' style="height: 80px;overflow:hidden;">
                    <div style='float:left;margin:5px 20px 5px 0'><img src="<?php echo(plugins_url( 'shortpixel-image-optimiser/res/img/slider.png' ));?>"></div>
                    <div class="sp-bulk-summary">
                        <input type="text" value="<?php echo("" . round($averageCompression))?>" id="sp-total-optimization-dial" class="dial">
                    </div>
                    <p style="margin-top:4px;">
                        <span style="font-size:1.2em;font-weight:bold">Congratulations!</span><br>Your media library has been successfully optimized!
                        <span class="sp-bulk-summary"><a href='javascript:void(0);'>Summary</a></span>
                    </p>
                </div>
                <div class='notice notice-success sp-floating-block sp-single-width' style="height: 80px;overflow:hidden;padding-right: 0;">
                    <div style="float:left; margin-top:-5px">
                        <p style='margin-bottom: -2px; font-weight: bold;'>
                            Share your optimization results:
                        </p>
                        <div style='display:inline-block;    margin: 16px 16px 6px 0;float:left'>
                            <div id="fb-root"></div>
                            <script>
                                (function(d, s, id) {
                                    var js, fjs = d.getElementsByTagName(s)[0];
                                    if (d.getElementById(id)) return;
                                    js = d.createElement(s); js.id = id;
                                    js.src = "//connect.facebook.net/en_US/sdk.js#xfbml=1&version=v2.6";
                                    fjs.parentNode.insertBefore(js, fjs);
                                }(document, 'script', 'facebook-jssdk'));
                            </script>
                            <div style="float:left;width:240px;">
                                <div class="fb-like" data-href="https://www.facebook.com/ShortPixel" data-width="260" data-layout="button_count" data-action="like" data-show-faces="true" data-share="true"></div>
                            </div>
                            <div style="float:left;margin:-7px 0 0 10px">
                                <a href="https://twitter.com/share" class="twitter-share-button" data-url="https://shortpixel.com" 
                                   data-text="I just optimized my images<?php echo(0+$averageCompression>20 ? " by ".round($averageCompression) ."%" : "");?><?php echo(false && (0+$savedSpace>0) ? " saving $savedSpace" : "");?> with @ShortPixel, a great plugin for increasing #WordPress page speed:" data-size='large'>Tweet</a>
                            </div>
                            <script>
                                jQuery(function() {
                                    jQuery("#sp-total-optimization-dial").val("<?php echo("" . round($averageCompression))?>");
                                    ShortPixel.percentDial("#sp-total-optimization-dial", 60);
                                    
                                    jQuery(".sp-bulk-summary").tooltip({
                                        tooltipSource: "inline",
                                        tooltipSourceID: "#sp-bulk-stats"
                                    });
                                });
                                !function(d,s,id){//Just optimized my site with ShortPixel image optimization plugin
                                    var js,
                                        fjs=d.getElementsByTagName(s)[0],
                                        p=/^http:/.test(d.location)?'http':'https';
                                    if(!d.getElementById(id)){js=d.createElement(s);
                                    js.id=id;js.src=p+'://platform.twitter.com/widgets.js';
                                    fjs.parentNode.insertBefore(js,fjs);}}(document, 'script', 'twitter-wjs');
                            </script>
                        </div>
                    </div>
                    <?php if(0+$averageCompression>30) {?> 
                    <div class='shortpixel-rate-us' style='float:left;padding-top:0'>
                        <a href="https://wordpress.org/support/view/plugin-reviews/shortpixel-image-optimiser?rate=5#postform" target="_blank">
                            <span>
                                Please rate us!&nbsp;
                            </span><br><img src="<?php echo(plugins_url( 'shortpixel-image-optimiser/res/img/stars.png' ));?>">
                        </a>
                    </div>
                    <?php } ?>
                </div>
                <div id="sp-bulk-stats" style="display:none">
                    <?php $this->displayBulkStats($quotaData['totalProcessedFiles'], $quotaData['mainProcessedFiles'], $under5PercentCount, $averageCompression, $savedSpace);?>
                </div>            
            </div>
            <p>Go to the ShortPixel <a href='<?php echo(get_admin_url());?>options-general.php?page=wp-shortpixel#stats'>Stats</a> and see all your websites' optimized stats. Download your detailed <a href="https://api.shortpixel.com/v2/report.php?key=<?php echo($this->ctrl->getApiKey());?>">Optimization Report</a> to check your image optimization statistics for the last 40 days.</p>
            <?php 
            $failed = $this->ctrl->getPrioQ()->getFailed();
            if(count($failed)) { ?>
                <div class="bulk-progress" style="margin-bottom: 15px">
                    <p>
                        The following images could not be processed because of their limited write rights. This usually happens if you have changed your hosting provider. Please restart the optimization process after you granted write rights to all the files below.
                    </p>
                    <?php $this->displayFailed($failed); ?>
                </div>
            <?php } ?>
            <div class="bulk-progress notice notice-info  sp-floating-block  sp-double-width">
                <?php 
                $todo = $reopt = false;
                if($quotaData['totalProcessedFiles'] < $quotaData['totalFiles']) { 
                    $todo = true;
                    $mainNotProcessed = $quotaData['mainFiles'] - $quotaData['mainProcessedFiles'];
                    $thumbsNotProcessed = ($quotaData['totalFiles'] - $quotaData['mainFiles']) - ($quotaData['totalProcessedFiles'] - $quotaData['mainProcessedFiles']);
                    ?>
                    <p>
                        <?php echo($mainNotProcessed ? number_format($mainNotProcessed) . " images" : "");?> 
                        <?php echo($mainNotProcessed && $thumbsNotProcessed ? " and" : "");?> 
                        <?php echo($thumbsNotProcessed ? number_format($thumbsNotProcessed) . " thumbnails" : "");?> are not yet optimized by ShortPixel.
                        <?php if (count($quotaData['filesWithErrors'])) { ?>
                            Some have errors: 
                            <?php foreach($quotaData['filesWithErrors'] as $id => $data) {
                                if(ShortPixelMetaFacade::isCustomQueuedId($id)) {
                                    echo('<a href="'.trailingslashit(network_site_url("/")) . ShortPixelMetaFacade::filenameToRootRelative($data['Path']).'" title="'.$data['Message'].'" target="_blank">'.$data['Name'].'</a>,&nbsp;');
                                } else {
                                    echo('<a href="post.php?post='.$id.'&action=edit" title="'.$data['Message'].'">'.$data['Name'].'</a>,&nbsp;');
                                }
                            } ?>
                        <?php } ?>
                    </p>
                <?php }
                $settings = $this->ctrl->getSettings();
                $optType = $settings->compressionType == '1' ? 'lossy' : 'lossless';
                $otherType = $settings->compressionType == '1' ? 'lossless' : 'lossy';
                if(   !$this->ctrl->backupFolderIsEmpty()
                   && (   ($quotaData['totalProcLossyFiles'] > 0 && $settings->compressionType == 0)
                       || ($quotaData['totalProcLosslessFiles'] > 0 && $settings->compressionType == 1)))
                {     
                    $todo = $reopt = true;
                    $statType = $settings->compressionType == '1' ? 'Lossless' : 'Lossy';
                    $thumbsCount = $quotaData['totalProc'.$statType.'Files'] - $quotaData['mainProc'.$statType.'Files'];
                    ?>
                    <p id="with-thumbs" <?php echo(!$settings->processThumbnails ? 'style="display:none;"' : "");?>>
                        <?php echo(number_format($quotaData['mainProc'.$statType.'Files']));?> images and 
                        <?php echo(number_format($quotaData['totalProc'.$statType.'Files'] - $quotaData['mainProc'.$statType.'Files']));?> thumbnails were optimized 
                        <strong>
                            <?php echo($otherType);?>
                        </strong>. You can re-optimize 
                        <strong>
                            <?php echo($optType);?>
                        </strong> the ones that have backup.
                    </p>
                    <p id="without-thumbs" <?php echo($settings->processThumbnails ? 'style="display:none;"' : "");?>>
                        <?php echo(number_format($quotaData['mainProc'.$statType.'Files']));?> images are optimized
                        <strong>
                            <?php echo($otherType);?>
                        </strong>. You can re-optimize 
                        <strong>
                            <?php echo($optType);?>
                        </strong> the ones that have backup.
                        <?php echo($thumbsCount ? number_format($thumbsCount) . ' thumbnails will be restored to originals.' : '');?>
                    </p>
                    <?php
                } ?>
                <p>Restart the optimization process for <?php echo($todo ? 'these images' : 'new images added to your library');?> by clicking the button below. 
                    Already <strong><?php echo($todo ? ($optType) : '');?></strong> optimized images will not be reprocessed.
                    <?php if($reopt) { ?>
                    <br>Please note that reoptimizing images as <strong>lossy/lossless</strong> may use additional credits. <a href="http://blog.shortpixel.com/the-all-new-re-optimization-functions-in-shortpixel/" target="_blank">More info</a>
                    <?php } ?>
                </p>
                <form action='' method='POST' >
                    <input type='checkbox' id='bulk-thumbnails' name='thumbnails' <?php echo($this->ctrl->processThumbnails() ? "checked":"");?> onchange="ShortPixel.onBulkThumbsCheck(this)"> Include thumbnails<br><br>
                    <input type='submit' name='bulkProcess' id='bulkProcess' class='button button-primary' value='Restart Optimizing'>
                </form>
            </div>
        <?php } ?>
        </div>
        <?php
    }

    public function displayBulkProcessingRunning($percent, $message, $remainingQuota, $averageCompression, $type) {
        ?>
        <div class="wrap short-pixel-bulk-page">
            <h1>Bulk Image Optimization by ShortPixel</h1>
            <?php $this->displayBulkProgressBar(true, $percent, $message, $remainingQuota, $averageCompression, $type);?>
            <div class="sp-floating-block notice bulk-notices-parent">
                <div class="bulk-notice-container">
                    <div class="bulk-notice-msg bulk-lengthy">
                        <img src="<?php echo(plugins_url( 'shortpixel-image-optimiser/res/img/loading-dark-big.gif' ));?>">
                        Lengthy operation in progress:<br>
                        Optimizing image <a href="#" data-href="<?php echo(get_admin_url());?>/post.php?post=__ID__&action=edit" target="_blank">lala.png</a>
                    </div>
                    <div class="bulk-notice-msg bulk-error" id="bulk-error-template">
                        <div style="float: right; margin-top: -4px; margin-right: -8px;">
                            <a href="javascript:void(0);" onclick="ShortPixel.removeBulkMsg(this)" style='color: #c32525;'>&#10006;</a>
                        </div>
                        <img src="<?php echo(plugins_url( 'shortpixel-image-optimiser/res/img/exclamation-big.png' ));?>">
                        <span class="sp-err-title">Error processing file:<br></span>
                        <span class="sp-err-content"><?php echo $message; ?></span> <a class="sp-post-link" href="<?php echo(get_admin_url());?>/post.php?post=__ID__&action=edit" target="_blank">lala.png</a>
                    </div>
                </div>
            </div>
            <div class="bulk-progress bulk-slider-container notice notice-info sp-floating-block sp-full-width">
                <div  class="short-pixel-block-title"><span>Just optimized:</span><span class="filename"></span></div>
                <div class="bulk-slider">
                    <div class="bulk-slide" id="empty-slide">
                        <div class="bulk-slide-images">
                            <div class="img-original">
                                <div><img class="bulk-img-orig" src=""></div>
                              <div>Original image</div>
                            </div>
                            <div class="img-optimized">
                                <div><img class="bulk-img-opt" src=""></div>
                              <div>Optimized image</div>
                            </div>
                        </div>
                        <div class="img-info">
                            <div style="font-size: 14px; line-height: 10px; margin-bottom:16px;">Optimized by:</div>
                            <span class="bulk-opt-percent"></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
    
    public function displayBulkProgressBar($running, $percent, $message, $remainingQuota, $averageCompression, $type = 1, $customPending = false) {
        $percentBefore = $percentAfter = '';
        if($percent > 24) {
            $percentBefore = $percent . "%";
        } else {
            $percentAfter = $percent . "%";
        }
        ?>
            <div class="notice notice-info bulk-progress sp-floating-block sp-full-width">
                <div style="float:right">
                    <?php if(false) { ?>
                    <div class="bulk-progress-indicator">
                        <div style="margin-bottom:5px">Remaining credits</div>
                        <div style="margin-top:22px;margin-bottom: 5px;font-size:2em;font-weight: bold;"><?php echo(number_format($remainingQuota))?></div>
                        <div>images</div>
                    </div>
                    <?php } ?>
                    <div class="bulk-progress-indicator">
                        <div style="margin-bottom:5px">Average reduction</div>
                        <div id="sp-avg-optimization"><input type="text" id="sp-avg-optimization-dial" value="<?php echo("" . round($averageCompression))?>" class="dial"></div>
                        <script>
                            jQuery(function() {
                                ShortPixel.percentDial("#sp-avg-optimization-dial", 60);
                            });
                        </script>
                    </div>
                </div>
                <?php if($running) { ?>
                <h2><?php echo($type & 1 ? "Media Library " : "") ?><?php echo($type & 3 == 3 ? "and " : "") ?><?php echo($type & 2 ? "Custom folders " : "") ?>optimization in progress ...</h2>
                <p style="margin: 0 0 18px;">Bulk optimization has started.<br>
                    This process will take some time, depending on the number of images in your library. In the meantime, you can continue using 
                    the admin as usual, <a href='<?php echo(get_admin_url());?>' target='_blank'>in a different browser window or tab</a>.<br>
                   However, <strong>if you close this window, the bulk processing will pause</strong> until you open the media gallery or the ShortPixel bulk page again.
                </p>
                <?php } else { ?>
                <h2>Media Library <?php ($type & 2 ? "and Custom folders " : "") ?>optimization paused</h2>
                <p style="margin: 0 0 50px;">Bulk processing is paused until you resume the optimization process.</p>
                <?php }?>
                <div id="bulk-progress" class="progress" >
                    <div class="progress-img" style="left: <?php echo($percent);?>%;">
                        <img src="<?php echo(plugins_url( 'shortpixel-image-optimiser/res/img/slider.png' ));?>">
                        <span><?php echo($percentAfter);?></span>
                    </div>
                    <div class="progress-left" style="width: <?php echo($percent);?>%"><?php echo($percentBefore);?></div>
                </div>
                <div class="bulk-estimate">
                    &nbsp;<?php echo($message);?>
                </div>
                <?php if (true || ($type & 1)) { //now we display the action buttons always when a type of bulk is running ?>
                <form action='' method='POST' style="display:inline;">
                    <input type="submit" class="button button-primary bulk-cancel"  onclick="clearBulkProcessor();"
                           name="bulkProcessStop" value="Stop" style="margin-left:10px"/>
                    <input type="submit" class="button button-primary bulk-cancel"  onclick="clearBulkProcessor();"
                           name="<?php echo($running ? "bulkProcessPause" : "bulkProcessResume");?>" value="<?php echo($running ? "Pause" : "Resume processing");?>"/>
                    <?php if(!$running && $customPending) {?>
                        <input type="submit" class="button button-primary bulk-cancel"  onclick="clearBulkProcessor();"
                               name="skipToCustom" value="Only custom folders" title="Process only the custom folders, skipping the Media Library" style="margin-right:10px"/>
                    <?php }?>
                </form>
                <?php } else { ?>
                    <a href="options-general.php?page=wp-shortpixel" class="button button-primary bulk-cancel" style="margin-left:10px">Manage custom folders</a>
                <?php }?>
            </div>
        <?php
    }
    
    public function displayBulkStats($totalOptimized, $mainOptimized, $under5PercentCount, $averageCompression, $savedSpace) {?>
            <div class="bulk-progress bulk-stats">
                <div class="label">Processed Images and PDFs:</div><div class="stat-value"><?php echo(number_format($mainOptimized));?></div><br>
                <div class="label">Processed Thumbnails:</div><div class="stat-value"><?php echo(number_format($totalOptimized - $mainOptimized));?></div><br>
                <div class="label totals">Total files processed:</div><div class="stat-value"><?php echo(number_format($totalOptimized));?></div><br>
                <div class="label totals">Minus files with <5% optimization (free):</div><div class="stat-value"><?php echo(number_format($under5PercentCount));?></div><br><br>
                <div class="label totals">Used quota:</div><div class="stat-value"><?php echo(number_format($totalOptimized - $under5PercentCount));?></div><br>
                <br>
                <div class="label">Average optimization:</div><div class="stat-value"><?php echo($averageCompression);?>%</div><br>
                <div class="label">Saved space:</div><div class="stat-value"><?php echo($savedSpace);?></div>
            </div>
        <?php
    }
     
    public function displayFailed($failed) {
        ?>
            <div class="bulk-progress bulk-stats">
                <?php foreach($failed as $fail) { 
                    if($fail->type == ShortPixelMetaFacade::CUSTOM_TYPE) {
                        $meta = $fail->meta;
                        ?> <div class="label"><a href="<?php echo(trailingslashit(network_site_url("/")) . $fail->meta->getWebPath());?>"><?php echo(substr($fail->meta->getName(), 0, 80));?> - ID: C-<?php echo($fail->id);?></a></div><br/>
                    <?php } else {
                        $meta = wp_get_attachment_metadata($fail);
                        ?> <div class="label"><a href="/wp-admin/post.php?post=<?php echo($fail->id);?>&action=edit"><?php echo(substr($fail->meta["file"], 0, 80));?> - ID: <?php echo($fail->id);?></a></div><br/>
                    <?php }
                }?>
            </div>
        <?php
    }

    function displaySettings($showApiKey, $editApiKey, $quotaData, $notice, $resources = null, $averageCompression = null, $savedSpace = null, $savedBandwidth = null, 
                         $remainingImages = null, $totalCallsMade = null, $fileCount = null, $backupFolderSize = null, 
                         $customFolders = null, $folderMsg = false, $addedFolder = false, $showAdvanced = false) { 
        //wp_enqueue_script('jquery.idTabs.js', plugins_url('/js/jquery.idTabs.js',__FILE__) );
        ?>        
        <h1>ShortPixel Plugin Settings</h1>
        <p style="font-size:18px">
            <a href="https://shortpixel.com/<?php echo($this->ctrl->getVerifiedKey() ? "login/".$this->ctrl->getApiKey() : "pricing");?>" target="_blank" style="font-size:18px">
                Upgrade now
            </a> |
            <a href="https://shortpixel.com/contact/<?php echo($this->ctrl->getEncryptedData());?>" target="_blank" style="font-size:18px">Support </a>
        </p>
        <?php if($notice !== null) { ?>
        <br/>
        <div style="background-color: #fff; border-left: 4px solid <?php echo($notice['status'] == 'error' ? '#ff0000' : ($notice['status'] == 'warn' ? '#FFC800' : '#7ad03a'));?>; box-shadow: 0 1px 1px 0 rgba(0, 0, 0, 0.1); padding: 1px 12px;;width: 95%">
                  <p><?php echo($notice['msg']);?></p>
        </div>
        <?php } ?>
        <?php if($folderMsg) { ?>
        <br/>
        <div style="background-color: #fff; border-left: 4px solid #ff0000; box-shadow: 0 1px 1px 0 rgba(0, 0, 0, 0.1); padding: 1px 12px;;width: 95%">
                  <p><?php echo($folderMsg);?></p>
        </div>
        <?php } ?>

        <article id="shortpixel-settings-tabs" class="sp-tabs">
            <form name='wp_shortpixel_options' action='options-general.php?page=wp-shortpixel&noheader=true'  method='post' id='wp_shortpixel_options'>
                <section <?php echo($showAdvanced ? "" : "class='sel-tab'");?> id="tab-settings">
                    <h2><a class='tab-link' href='javascript:void(0);' data-id="tab-settings">General</a></h2>
                    <?php $this->displaySettingsForm($showApiKey, $editApiKey, $quotaData);?>
                </section> 
                <?php if($this->ctrl->getVerifiedKey()) {?>
                <section <?php echo($showAdvanced ? "class='sel-tab'" : "");?> id="tab-adv-settings">
                    <h2><a class='tab-link' href='javascript:void(0);' data-id="tab-adv-settings">Advanced</a></h2>
                    <?php $this->displayAdvancedSettingsForm($customFolders, $addedFolder);?>
                </section>
                <?php } ?>
            </form><span style="display:none">&nbsp;</span><?php //the span is a trick to keep the sections ordered as nth-child in styles: 1,2,3,4 (otherwise the third section would be nth-child(2) too, because of the form)
            if($averageCompression !== null) {?>
            <section id="tab-stats">
                <h2><a class='tab-link' href='javascript:void(0);' data-id="tab-stats">Statistics</a></h2>
                <?php
                    $this->displaySettingsStats($quotaData, $averageCompression, $savedSpace, $savedBandwidth, 
                                                $remainingImages, $totalCallsMade, $fileCount, $backupFolderSize);?>
            </section> 
            <?php }
            if($resources !== null) {?>
            <section id="tab-resources">
		        <h2><a class='tab-link' href='javascript:void(0);' data-id="tab-resources">WP Resources</a></h2>
                <?php echo((isset($resources['body']) ? $resources['body'] : "Please reload"));?>
            </section>
            <?php } ?>
        </article>
        <script>
            jQuery(document).ready(function () {
                ShortPixel.adjustSettingsTabs();
                jQuery( window ).resize(function() {
                    ShortPixel.adjustSettingsTabs();
                });
                if(window.location.hash) {
                    var target = 'tab-' + window.location.hash.substring(window.location.hash.indexOf("#")+1)
                    ShortPixel.switchSettingsTab(target);
                }
                jQuery("article.sp-tabs a.tab-link").click(function(){ShortPixel.switchSettingsTab(jQuery(this).data("id"))});
            });
        </script>
        <?php
    }    
    
    public function displaySettingsForm($showApiKey, $editApiKey, $quotaData) {
        $settings = $this->ctrl->getSettings();
        $checked = ($this->ctrl->processThumbnails() ? 'checked' : '');
        $checkedBackupImages = ($this->ctrl->backupImages() ? 'checked' : '');
        $cmyk2rgb = ($this->ctrl->getCMYKtoRGBconversion() ? 'checked' : '');
        $removeExif = ($settings->keepExif ? '' : 'checked');
        $resize = ($this->ctrl->getResizeImages() ? 'checked' : '');
        $resizeDisabled = ($this->ctrl->getResizeImages() ? '' : 'disabled');        
        $minSizes = $this->ctrl->getMaxIntermediateImageSize();
        $thumbnailsToProcess = isset($quotaData['totalFiles']) ? ($quotaData['totalFiles'] - $quotaData['mainFiles']) - ($quotaData['totalProcessedFiles'] - $quotaData['mainProcessedFiles']) : 0;
        ?>
        <div class="wp-shortpixel-options">
        <?php if($this->ctrl->getVerifiedKey()) { ?>
            <p>New images uploaded to the Media Library will be optimized automatically.<br/>If you have existing images you would like to optimize, you can use the <a href="<?php echo(get_admin_url());?>upload.php?page=wp-short-pixel-bulk">Bulk Optimization Tool</a>.</p>
        <?php } else { 
            if($showApiKey) {?>
            <h3>Step 1:</h3>
            <p style='font-size: 14px'>If you don't have an API Key, <a href="https://shortpixel.com/wp-apikey<?php echo( $this->ctrl->getAffiliateSufix() );?>" target="_blank">sign up here.</a> It's free and it only takes one minute, we promise!</p>
            <h3>Step 2:</h3>
            <p style='font-size: 14px'>Please enter here the API Key you received by email and press Validate.</p>
            <?php } 
        }?>
        <table class="form-table">
            <tbody>
                <tr>
                    <th scope="row"><label for="key">API Key:</label></th>
                    <td>
                        <?php 
                        $canValidate = false;
                        if($showApiKey) {
                            $canValidate = true;?>
                        <input name="key" type="text" id="key" value="<?php echo( $this->ctrl->getApiKey() );?>" 
                               class="regular-text" <?php echo($editApiKey ? "" : 'disabled') ?>>
                        <?php } elseif(defined("SHORTPIXEL_API_KEY")) { 
                            $canValidate = true;?>
                        <input name="key" type="text" id="key" disabled="true" placeholder="Multisite API Key" class="regular-text">
                        <?php } ?>
                        <input type="hidden" name="validate" id="valid" value=""/>
                        <button type="button" id="validate" class="button button-primary" title="Validate the provided API key"
                                onclick="ShortPixel.validateKey()" <?php echo $canValidate ? "" : "disabled"?>>Validate</button>
                        <?php if($showApiKey && !$editApiKey) { ?>
                        <p class="settings-info">Key defined in wp-config.php.</p>
                        <?php } ?>
                        
                    </td>
                </tr>
        <?php if (!$this->ctrl->getVerifiedKey()) { //if invalid key we display the link to the API Key ?>
            </tbody>
        </table>
        <?php } else { //if valid key we display the rest of the options ?>
                <tr>
                    <th scope="row">
                        <label for="compressionType">Compression type:</label>
                    </th>
                    <td>
                        <input type="radio" name="compressionType" value="1" <?php echo( $this->ctrl->getCompressionType() == 1 ? "checked" : "" );?>>Lossy (recommended)</br>
                        <p class="settings-info"> <b>Lossy compression: </b>lossy has a better compression rate than lossless compression.</br>The resulting image is identical with the original to the human eye. You can run a test for free 
                            <a href="https://shortpixel.com/online-image-compression" target="_blank">here</a>.</p></br>
                        <input type="radio" name="compressionType" value="0" <?php echo( $this->ctrl->getCompressionType() != 1 ? "checked" : "" );?>>Lossless
                        <p class="settings-info"><b>Lossless compression: </b> the shrunk image will be identical with the original and smaller in size.</br>In some rare cases you will need to use 
                        this type of compression. Some technical drawings or images from vector graphics are possible situations.</p>
                    </td>
                </tr>
            </tbody>
        </table>
        <table class="form-table">
            <tbody>
                <tr>
                    <th scope="row"><label for="thumbnails">Also include thumbnails:</label></th>
                    <td><input name="thumbnails" type="checkbox" id="thumbnails" <?php echo( $checked );?>> Apply compression also to 
                            <strong>image thumbnails.</strong> <?php echo($thumbnailsToProcess ? "(" . number_format($thumbnailsToProcess) . " thumbnails to optimize)" : "");?>
                        <p class="settings-info">It is highly recommended that you optimize the thumbnails as they are usually the images most viewed by end users and can generate most traffic.<br>Please note that thumbnails count up to your total quota.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="backupImages">Image backup</label></th>
                    <td>
                        <input name="backupImages" type="checkbox" id="backupImages" <?php echo( $checkedBackupImages );?>> Save and keep a backup of your original images in a separate folder.
                        <p class="settings-info">You <strong>need to have backup active</strong> in order to be able to restore images to originals or to convert from Lossy to Lossless and back.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="cmyk2rgb">CMYK to RGB conversion</label></th>
                    <td>
                        <input name="cmyk2rgb" type="checkbox" id="cmyk2rgb" <?php echo( $cmyk2rgb );?>>Adjust your images for computer and mobile screen display.
                        <p class="settings-info">Images for the web only need RGB format and converting them from CMYK to RGB makes them smaller.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="removeExif">Remove EXIF</label></th>
                    <td>
                        <input name="removeExif" type="checkbox" id="removeExif" <?php echo( $removeExif );?>>Remove the EXIF tag of the image (recommended).
                        <p class="settings-info"> EXIF is a set of various pieces of information that are automatically embedded into the image upon creation. This can include GPS position, camera manufacturer, date and time, etc.  
                            Unless you really need that data to be preserved, we recommend removing it as it can lead to <a href="http://blog.shortpixel.com/how-much-smaller-can-be-images-without-exif-icc" target="_blank">better compression rates</a>.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="resize">Resize large images</label></th>
                    <td>
                        <input name="resize" type="checkbox" id="resize" <?php echo( $resize );?>> to maximum
                        <input type="text" name="width" id="width" style="width:70px" 
                               value="<?php echo( max($this->ctrl->getResizeWidth(), min(1024, $minSizes['width'])) );?>" <?php echo( $resizeDisabled );?>/> pixels wide &times;
                        <input type="text" name="height" id="height" style="width:70px" value="<?php echo( max($this->ctrl->getResizeHeight(), min(1024, $minSizes['height'])) );?>" <?php echo( $resizeDisabled );?>/> pixels high (original aspect ratio is preserved and image is not cropped)
                        <p class="settings-info"> 
                            Recommended for large photos, like the ones taken with your phone. Saved space can go up to 80% or more after resizing.<br/>
                        </p>
                        <div style="margin-top: 10px;">
                            <input type="radio" name="resize_type" id="resize_type_outer" value="outer" <?php echo($settings->resizeType == 'inner' ? '' : 'checked') ?> style="margin: -50px 10px 60px 0;">
                            <img src="<?php echo(plugins_url( 'shortpixel-image-optimiser/res/img/resize-outer.png' ));?>" title="Sizes will be greater or equal to the corresponding value. For example, if you set the resize dimensions at 1000x1200, an image of 2000x3000px will be resized to 1000x1500px while an image of 3000x2000px will be resized to 1800x1200px">
                            <input type="radio" name="resize_type" id="resize_type_inner" value="inner" <?php echo($settings->resizeType == 'inner' ? 'checked' : '') ?> style="margin: -50px 10px 60px 35px;">
                            <img src="<?php echo(plugins_url( 'shortpixel-image-optimiser/res/img/resize-inner.png' ));?>" title="Sizes will be smaller or equal to the corresponding value. For example, if you set the resize dimensions at 1000x1200, an image of 2000x3000px will be resized to 800x1200px while an image of 3000x2000px will be resized to 1000x667px">
                        </div>
                    </td>
                </tr>
            </tbody>
        </table>
        <p class="submit">
            <input type="submit" name="save" id="save" class="button button-primary" title="Save Changes" value="Save Changes"> &nbsp;
            <input type="submit" name="save" id="bulk" class="button button-primary" title="Save and go to the Bulk Processing page" value="Save and Go to Bulk Process"> &nbsp;
        </p>
        </div>
        <script>
            jQuery(document).ready(function () {
                ShortPixel.setupGeneralTab(document.wp_shortpixel_options.compressionType, 
                                       Math.min(1024, <?php echo($minSizes['width']);?>),
                                       Math.min(1024, <?php echo($minSizes['height']);?>));
            });
        </script>
        <?php }
    }
    
    public function displayAdvancedSettingsForm($customFolders = false, $addedFolder = false) {
        $settings = $this->ctrl->getSettings();
        $minSizes = $this->ctrl->getMaxIntermediateImageSize();
        $hasNextGen = $this->ctrl->hasNextGen();
        $frontBootstrap = ($settings->frontBootstrap ? 'checked' : '');
        $includeNextGen = ($settings->includeNextGen ? 'checked' : '');
        $createWebp = ($settings->createWebp ? 'checked' : '');
        ?>
        <div class="wp-shortpixel-options">
        <?php if(!$this->ctrl->getVerifiedKey()) { ?>
            <p>Please enter your API key in the General tab first.</p>
        <?php } else { //if valid key we display the rest of the options ?>
        <table class="form-table">
            <tbody>
                <tr>
                    <th scope="row"><label for="resize">Additional media folders</label></th>
                    <td>
                        <?php if($customFolders) { ?>
                            <table class="shortpixel-folders-list">
                                <tr style="font-weight: bold;">
                                    <td>Folder name</td>
                                    <td>Type &amp;<br>Status</td>
                                    <td>Files</td>
                                    <td>Last change</td>
                                    <td></td>
                                </tr>
                            <?php foreach($customFolders as $folder) {
                                $typ = $folder->getType(); 
                                $typ = $typ ? $typ . "<br>" : "";
                                $stat = $this->ctrl->getSpMetaDao()->getFolderOptimizationStatus($folder->getId());
                                $cnt = $folder->getFileCount();
                                $st = ($cnt == 0 
                                    ? "Empty"
                                    : ($stat->Total == $stat->Optimized 
                                        ? "Optimized"
                                        : ($stat->Optimized + $stat->Pending > 0 ? "Pending" : "Waiting")));
                                $err = $stat->Failed > 0 && !$st == "Empty" ? " ({$stat->Failed} failed)" : "";
                                $action = ($st == "Optimized" || $st == "Empty" ? "monitoring" : "optimizing");
                                $fullStat = $st == "Empty" ? "" : "Optimized: " . $stat->Optimized . ", Pending: " . $stat->Pending . ", Waiting: " . $stat->Waiting . ", Failed: " . $stat->Failed;
                                ?>
                                <tr>
                                    <td>
                                        <?php echo($folder->getPath()); ?>
                                    </td>
                                    <td>
                                        <?php if(!($st == "Empty")) { ?>
                                        <a href="javascript:none();"  title="<?php echo $fullStat; ?>" style="text-decoration: none;">
                                            <img src='<?php echo(plugins_url( 'shortpixel-image-optimiser/res/img/info-icon.png' ));?>' style="margin-bottom: -2px;"/>
                                        </a>&nbsp;<?php  } echo($typ.$st.$err); ?>

                                    </td>
                                    <td>
                                        <?php echo($cnt); ?> files
                                    </td>
                                    <td>
                                        <?php echo($folder->getTsUpdated()); ?>
                                    </td>
                                    <td>
                                        <input type="button" class="button remove-folder-button" data-value="<?php echo($folder->getPath()); ?>" title="Stop <?php echo($action . " " . $folder->getPath()); ?>" value="Stop <?php echo $action;?>">
                                        <input type="button" style="display:none;" class="button button-alert recheck-folder-button" data-value="<?php echo($folder->getPath()); ?>" title="Full folder refresh, check each file of the folder if it changed since it was optimized. Might take up to 1 min. for big folders." value="Refresh">
                                    </td>
                                </tr>
                            <?php }?>
                            </table>
                        <?php } ?>
                        <input type="hidden" name="removeFolder" id="removeFolder"/>
                        <input type="hidden" name="recheckFolder" id="removeFolder"/>
                        <input type="text" name="addCustomFolderView" id="addCustomFolderView" class="regular-text" value="<?php echo($addedFolder);?>" disabled style="width: 50em;max-width: 70%;">&nbsp;
                        <input type="hidden" name="addCustomFolder" id="addCustomFolder" value="<?php echo($addedFolder);?>"/>
                        <input type="hidden" id="customFolderBase" value="<?php echo $this->ctrl->getCustomFolderBase(); ?>">
                        <a class="button button-primary select-folder-button" title="Select the images folder on your server" href="javascript:void(0);">Select ... </a>
                        <input type="submit" name="saveAdv" id="saveAdvAddFolder" class="button button-primary" title="Add Folder" value="Add Folder">
                        <p class="settings-info"> Use the Select... button to select site folders. ShortPixel will optimize images and PDFs from the specified folders and their subfolders. The optimization status for each image or PDF in these folders can be seen in the <a href="upload.php?page=wp-short-pixel-custom">Other Media list</a>, under the Media menu.</p>
                        <div class="sp-folder-picker-shade">
                            <div class="sp-folder-picker-popup">
                                <div class="sp-folder-picker-title">Select the images folder</div>
                                <div class="sp-folder-picker"></div>
                                <input type="button" class="button button-info select-folder-cancel" value="Cancel" style="margin-right: 30px;">
                                <input type="button" class="button button-primary select-folder" value="Select">
                            </div>
                        </div>
                        <script>
                            jQuery(document).ready(function () {
                                ShortPixel.initFolderSelector();
                            });
                        </script>
                    </td>
                </tr>
                <?php if($hasNextGen) { ?>
                <tr>
                    <th scope="row"><label for="nextGen">Optimize NextGen galleries</label></th>
                    <td>
                        <input name="nextGen" type="checkbox" id="nextGen" <?php echo( $includeNextGen );?>> Optimize NextGen galleries.
                        <p class="settings-info">Check this to add all your current NextGen galleries to the custom folders list and to also have all the future NextGen galleries and images optimized automatically by ShortPixel.</p>
                    </td>
                </tr>
                <?php } ?>
                <tr>
                    <th scope="row"><label for="createWebp">WebP versions</label></th>
                    <td>
                        <input name="createWebp" type="checkbox" id="createWebp" <?php echo( $createWebp );?>> Create also <a href="http://blog.shortpixel.com/how-webp-images-can-speed-up-your-site/" target="_blank">WebP versions</a> of the images <strong>for free</strong>.
                        <p class="settings-info">WebP images can be up to three times smaller than PNGs and 25% smaller than JPGs. Choosing this option <strong>does not use up additional credits</strong>.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="authentication">HTTP AUTH credentials</label></th>
                    <td>
                        <input name="siteAuthUser" type="text" id="siteAuthUser" value="<?php echo( $settings->siteAuthUser );?>" class="regular-text" placeholder="User"><br>
                        <input name="siteAuthPass" type="text" id="siteAuthPass" value="<?php echo( $settings->siteAuthPass );?>" class="regular-text" placeholder="Password">
                        <p class="settings-info"> Only fill in these fields if your site (front-end) is not publicly accessible and visitors need a user/pass to connect to it. If you don't know what is this then just <strong>leave the fields empty</strong>.</p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="resize">Process in front-end</label></th>
                    <td>
                        <input name="frontBootstrap" type="checkbox" id="resize" <?php echo( $frontBootstrap );?>> Automatically optimize images added by users in front end.
                        <p class="settings-info">Check this if you have users that add images or PDF documents from custom forms in the front-end. This could increase the load on your server if you have a lot of users simultaneously connected.</p>
                    </td>
                </tr>
            </tbody>
        </table>
        <p class="submit">
            <input type="submit" name="saveAdv" id="saveAdv" class="button button-primary" title="Save Changes" value="Save Changes"> &nbsp;
            <input type="submit" name="saveAdv" id="bulkAdvGo" class="button button-primary" title="Save and go to the Bulk Processing page" value="Save and Go to Bulk Process"> &nbsp;
        </p>
        </div>
        <script>
            jQuery(document).ready(function () { ShortPixel.setupAdvancedTab();});
        </script>
        <?php }
    }
    
    function displaySettingsStats($quotaData, $averageCompression, $savedSpace, $savedBandwidth, 
                         $remainingImages, $totalCallsMade, $fileCount, $backupFolderSize) { ?>
        <a id="facts"></a>
        <h3>Your ShortPixel Stats</h3>
        <table class="form-table">
            <tbody>
                <tr>
                    <th scope="row"><label for="averagCompression">Average compression of your files:</label></th>
                    <td><?php echo($averageCompression);?>%</td>
                </tr>
                <tr>
                    <th scope="row"><label for="savedSpace">Saved disk space by ShortPixel</label></th>
                    <td><?php echo($savedSpace);?></td>
                </tr>
                <tr>
                    <th scope="row"><label for="savedBandwidth">Bandwith* saved with ShortPixel:</label></th>
                    <td><?php echo($savedBandwidth);?></td>
                </tr>
            </tbody>
        </table>

        <p style="padding-top: 0px; color: #818181;" >* Saved bandwidth is calculated at 10,000 impressions/image</p>

        <h3>Your ShortPixel Plan</h3>
        <table class="form-table">
            <tbody>
                <tr>
                    <th scope="row" bgcolor="#ffffff"><label for="apiQuota">Your ShortPixel plan</label></th>
                    <td bgcolor="#ffffff">
                        <?php echo($quotaData['APICallsQuota']);?>/month, renews in <?php echo(floor(30 + (strtotime($quotaData['APILastRenewalDate']) - time()) / 86400));?> days, on <?php echo(date('M d, Y', strtotime($quotaData['APILastRenewalDate']. ' + 30 days')));?> ( <a href="https://shortpixel.com/login/<?php echo($this->ctrl->getApiKey());?>" target="_blank">Need More? See the options available</a> )<br/>
                        <a href="https://shortpixel.com/login/<?php echo($this->ctrl->getApiKey());?>/tell-a-friend" target="_blank">Join our friend referral system</a> to win more credits. For each user that joins, you receive +100 images credits/month.
                    </td>
                </tr>
                <tr>
                    <th scope="row"><label for="usedQUota">One time credits:</label></th>
                    <td><?php echo(  number_format($quotaData['APICallsQuotaOneTimeNumeric']));?></td>
                </tr>
                <tr>
                    <th scope="row"><label for="usedQUota">Number of images processed this month:</label></th>
                    <td><?php echo($totalCallsMade);?> (<a href="https://api.shortpixel.com/v2/report.php?key=<?php echo($this->ctrl->getApiKey());?>" target="_blank">see report</a>)</td>
                </tr>
                <tr>
                    <th scope="row"><label for="remainingImages">Remaining** images in your plan:  </label></th>
                    <td><?php echo($remainingImages);?> images</td>
                </tr>
            </tbody>
        </table>

        <p style="padding-top: 0px; color: #818181;" >** Increase your image quota by <a href="https://shortpixel.com/login/<?php echo($this->ctrl->getApiKey());?>" target="_blank">upgrading your ShortPixel plan.</a></p>

        <table class="form-table">
            <tbody>
                <tr>
                    <th scope="row"><label for="totalFiles">Total number of processed files:</label></th>
                    <td><?php echo($fileCount);?></td>
                </tr>
                <?php if($this->ctrl->backupImages()) { ?>
                <tr>
                    <th scope="row"><label for="sizeBackup">Original images are stored in a backup folder. Your backup folder size is now:</label></th>
                    <td>
                        <form action="" method="POST">
                            <?php echo($backupFolderSize);?>
                            <input type="submit"  style="margin-left: 15px; vertical-align: middle;" class="button button-secondary" name="emptyBackup" value="Empty backups"/>
                        </form>
                    </td>
                </tr>
                <?php } ?>
            </tbody>
        </table> 
        <div style="display:none">

        </div>    
        <?php        
    }

    public function renderCustomColumn($id, $data){ ?> 
        <div id='sp-msg-<?php echo($id);?>' class='column-wp-shortPixel'>
            <?php switch($data['status']) {
                case 'n/a': ?> 
                    Optimization N/A <?php
                    break;
                case 'notFound': ?> 
                    Image does not exist. <?php
                    break;
                case 'invalidKey': 
                    if(defined("SHORTPIXEL_API_KEY")) { // multisite key - need to be validated on each site but it's not invalid
                        ?> Please <a href="options-general.php?page=wp-shortpixel">go to Settings</a> to validate the API Key. <?php
                    } else {
                        ?> Invalid API Key. <a href="options-general.php?page=wp-shortpixel">Check your Settings</a> <?php
                    } 
                    break;
                case 'quotaExceeded': 
                    echo($this->getQuotaExceededHTML(isset($data['message']) ? $data['message'] : ''));
                    break;
                case 'optimizeNow': 
                    if($data['showActions']) { ?>  
                        <a class='button button-smaller button-primary' href="javascript:manualOptimization('<?php echo($id)?>')">Optimize now</a> 
                    <?php }
                    echo($data['message']);
                    if(isset($data['thumbsTotal']) && $data['thumbsTotal'] > 0) {
                        echo("<br>+" . $data['thumbsTotal'] . " thumbnails");
                    }
                    break;
                case 'retry': ?> 
                    <?php echo($data['message'])?>  <a class='button button-smaller button-primary' href="javascript:manualOptimization('<?php echo($id)?>')">Retry</a> <?php
                    break;
                case 'pdfOptimized': 
                case 'imgOptimized': 
                    $this->renderListCell($id, $data['showActions'], 
                            !$data['thumbsOpt'] && $data['thumbsTotal'], $data['thumbsTotal'], $data['backup'], $data['type'],
                            $this->getSuccessText($data['percent'],$data['bonus'],$data['type'],$data['thumbsOpt'],$data['thumbsTotal']));
                    break;
                }
                //die(var_dump($data));
                ?>
        </div>
        <?php 
    }
    
    public function getSuccessText($percent, $bonus, $type, $thumbsOpt = 0, $thumbsTotal = 0) {
        return ($percent ? 'Reduced by <strong>' . $percent . '%</strong> ' : '')
                                  .(!$bonus ? ' ('.$type.')':'')
                                  .($bonus && $percent ? '<br>' : '') 
                                  .($bonus ? 'Bonus processing' : '') 
                                  .($bonus ? ' ('.$type.')':'') . '<br>'
                                  .($thumbsOpt ? "+" . $thumbsOpt . ($thumbsTotal > $thumbsOpt ? " of ".$thumbsTotal : '') . " thumbnails optimized" : '');
    }
    
    public function renderListCell($id, $showActions, $optimizeThumbs, $thumbsTotal, $backup, $type, $message) {
        if($showActions) { ?>
            <div class='sp-column-actions'>
            <?php if($optimizeThumbs) { ?>
            <a class='button button-smaller button-primary' href="javascript:optimizeThumbs(<?php echo($id)?>);">
                Optimize <?php echo($thumbsTotal);?> thumbnails
            </a>
            <?php }
            if($backup) {
                if($type) { 
                    $invType = $type == 'lossy' ? 'lossless' : 'lossy'; ?>
                    <a class='button button-smaller' href="javascript:reoptimize('<?php echo($id)?>', '<?php echo($invType)?>');" title="Reoptimize from the backed-up image">
                        Re-optimize <?php echo($invType)?>
                    </a><?php
                } ?>
                <a class='button button-smaller' href="admin.php?action=shortpixel_restore_backup&attachment_ID=<?php echo($id)?>">
                    Restore backup
                </a>
            <?php } ?>
        </div> 
        <?php } ?> 
        <div class='sp-column-info'>
            <?php echo($message);?>
        </div> <?php
    }
    
    public function getQuotaExceededHTML($message = '') {
        return "<div class='sp-column-actions' style='width:110px;'> 
        <a class='button button-smaller button-primary' href='https://shortpixel.com/login/". $this->ctrl->getApiKey() . "' target='_blank'>Extend Quota</a>
        <a class='button button-smaller' href='admin.php?action=shortpixel_check_quota'>Check&nbsp;&nbsp;Quota</a></div>
        <div class='sp-column-info'>" . $message . " Quota Exceeded.</div>";
    }
}
