<?php

if( ! class_exists( 'WP_List_Table' ) ) {
    require_once( ABSPATH . 'wp-admin/includes/class-wp-list-table.php' );
}        

class ShortPixelListTable extends WP_List_Table {

    protected $ctrl;
    protected $spMetaDao;
    protected $hasNextGen;
    
    public function __construct($ctrl, $spMetaDao, $hasNextGen) {
        parent::__construct( array(
            'singular' => 'Image', //singular name of the listed records
            'plural'   => 'Images', //plural name of the listed records
            'ajax'     => false //should this table support ajax?
        ));
        $this->ctrl = $ctrl;
        $this->spMetaDao = $spMetaDao;
        $this->hasNextGen = $hasNextGen;
    }

    // define the columns to display, the syntax is 'internal name' => 'display name'
    function get_columns() {
        $columns = array();

        //pe viitor. $columns['cb'] = '<input type="checkbox" />';
        $columns['name'] = 'Filename';
        $columns['folder'] = 'Folder';
        $columns['media_type'] = 'Type';
        $columns['status'] = 'Status';
        $columns['options'] = 'Options';
        //$columns = apply_filters('shortpixel_list_columns', $columns);

        return $columns;
    }

    function column_cb( $item ) {
        return sprintf('<input type="checkbox" name="bulk-optimize[]" value="%s" />', $item->id);
    }
    
    function column_default( $item, $column_name ) {
        switch( $column_name ) { 
            case 'name':
                $title = '<a href="" title="'.$item->folder.'"><strong>' . $item->name . '</strong></a>';

                $url = ShortPixelMetaFacade::pathToWebPath($item->folder);
                $actions = array(
                    'optimize' => sprintf( '<a href="?page=%s&action=%s&image=%s&_wpnonce=%s">Optimize</a>', 
                            esc_attr( $_REQUEST['page'] ), 'optimize', absint( $item->id ), wp_create_nonce( 'sp_optimize_image' ) ),
                    'retry' => sprintf( '<a href="?page=%s&action=%s&image=%s&_wpnonce=%s">Retry</a>', 
                            esc_attr( $_REQUEST['page'] ), 'optimize', absint( $item->id ), wp_create_nonce( 'sp_optimize_image' ) ),
                    'restore' => sprintf( '<a href="?page=%s&action=%s&image=%s&_wpnonce=%s">Restore</a>', 
                            esc_attr( $_REQUEST['page'] ), 'restore', absint( $item->id ), wp_create_nonce( 'sp_restore_image' ) ),
                    'redolossless' => sprintf( '<a href="?page=%s&action=%s&image=%s&_wpnonce=%s">Re-optimize lossless</a>', 
                            esc_attr( $_REQUEST['page'] ), 'redo', absint( $item->id ), wp_create_nonce( 'sp_redo_image' ) ),
                    'redolossy' => sprintf( '<a href="?page=%s&action=%s&image=%s&_wpnonce=%s">Re-optimize lossy</a>', 
                            esc_attr( $_REQUEST['page'] ), 'redo', absint( $item->id ), wp_create_nonce( 'sp_redo_image' ) ),
                    'quota' => sprintf( '<a href="?page=%s&action=%s&image=%s&_wpnonce=%s">Check quota</a>', 
                            esc_attr( $_REQUEST['page'] ), 'quota', absint( $item->id ), wp_create_nonce( 'sp_check_quota' ) ),
                    'view' => sprintf( '<a href="%s" target="_blank">View</a>', $url )
                );
                $settings = $this->ctrl->getSettings();
                $actionsEnabled = array();
                if($settings->quotaExceeded) {
                    $actionsEnabled['quota'] = true;
                } elseif($item->status == 0 || $item->status == 1 || $item->status == 3 ) {
                    $actionsEnabled['optimize'] = true;
                } elseif($item->status == 2) {
                    $actionsEnabled['restore'] = true;
                    $actionsEnabled['redo'.($item->compression_type == 1 ? "lossless" : "lossy")] = true;
                } elseif($item->status == 3 || $item->status < 0) {
                    $actionsEnabled['retry'] = true;
                }
                $actionsEnabled['view'] = true;
                $title = $title . $this->row_actions($actions, false, $item->id, $actionsEnabled );
                return $title;
            case 'folder':
                return ShortPixelMetaFacade::pathToRootRelative($item->folder);
            case 'status':
                switch($item->status) {
                    case 3: $msg = "Restored";
                        break;
                    case 2: $msg = 0 + $item->message  == 0 ? "Bonus processing" : "Reduced by <strong>" . $item->message . "%</strong>" . (0 + $item->message < 5 ? "<br>Bonus processing." : "");
                        break;
                    case 1: $msg = "<img src=\"" . plugins_url( 'shortpixel-image-optimiser/res/img/loading.gif') . "\" class='sp-loading-small'>&nbsp;Pending";
                        break;
                    case 0: $msg = "Waiting";
                        break;
                    default:
                        if($item->status < 0) {
                            $msg = $item->message . "(code: " . $item->status . ")";
                        } else {
                            $msg = "";
                        }
                }
                return "<div id='sp-cust-msg-C-" . $item->id . "'>" . $msg . "</div>";
                break;
            case 'options':
                return ($item->compression_type == 1 ? "Lossy" : "Lossless") 
                     . ($item->keep_exif == 1 ? "": ", Keep EXIF") 
                     . ($item->cmyk2rgb ? "": ", Preserve CMYK");
            case 'media_type':
                return $item->$column_name;
            default:
                return print_r( $item, true ) ; //Show the whole array for troubleshooting purposes
        }
    }
    
    public function no_items() {
        echo('No images avaliable. Go to <a href="options-general.php?page=wp-shortpixel#adv-settings">Advanced Settings</a> to configure additional folders to be optimized.');
    }
    
    /**
    * Columns to make sortable.
    *
    * @return array
    */
    public function get_sortable_columns() {
        $sortable_columns = array(
          'name' => array( 'name', true ),
          'folder' => array( 'folder', true ),
          'status' => array( 'status', false )
        );

        return $sortable_columns;
    }
    
    /**
     * Handles data query and filter, sorting, and pagination.
     */
    public function prepare_items() {

        $this->_column_headers = $this->get_column_info();
        
        $this->_column_headers[0] = $this->get_columns();

        /** Process actions */
        $this->process_actions();

        $perPage     = $this->get_items_per_page( 'images_per_page', 20 );
        $currentPage = $this->get_pagenum();
        $total_items  = $this->record_count();

        $this->set_pagination_args( array(
          'total_items' => $total_items, //WE have to calculate the total number of items
          'per_page'    => $perPage //WE have to determine how many items to show on a page
        ));
        
        $orderby = ( ! empty( $_GET['orderby'] ) ) ? $_GET['orderby'] : 'ts_added';
        // If no order, default to asc
        $order = ( ! empty($_GET['order'] ) ) ? $_GET['order'] : 'desc';
        
        $this->items = $this->spMetaDao->getPaginatedMetas($this->hasNextGen, $perPage, $currentPage, $orderby, $order);
        return $this->items;
    }    
    
    public function record_count() {
        return $this->spMetaDao->getCustomMetaCount();
    }
    
    public function action_optimize_image( $id ) {
        $this->ctrl->optimizeCustomImage($id);
    }
    
    public function action_restore_image( $id ) {
        $this->ctrl->doCustomRestore($id);
    }
    
    public function action_redo_image( $id ) {
        $this->ctrl->redo('C-' . $id);
    }
    
    public function process_actions() {

        //Detect when a bulk action is being triggered...
        $nonce = isset($_REQUEST['_wpnonce']) ? esc_attr($_REQUEST['_wpnonce']) : false;
        switch($this->current_action()) {
            case 'optimize':
                if (!wp_verify_nonce($nonce, 'sp_optimize_image')) {
                    die('Error.');
                } else {
                    $this->action_optimize_image(absint($_GET['image']));
                    wp_redirect(esc_url(add_query_arg()));
                    exit;
                }
                break;
            case 'restore':
                if (!wp_verify_nonce($nonce, 'sp_restore_image')) {
                    die('Error.');
                } else {
                    $this->action_restore_image(absint($_GET['image']));
                    wp_redirect(esc_url(add_query_arg()));
                    exit;
                }
                break;
            case 'redo':
                if (!wp_verify_nonce($nonce, 'sp_redo_image')) {
                    die('Error.');
                } else {
                    $this->action_redo_image(absint($_GET['image']));
                    wp_redirect(esc_url(add_query_arg()));
                    exit;
                }
                break;
        }

        // If the delete bulk action is triggered
        if (( isset($_POST['action']) && $_POST['action'] == 'bulk-optimize' ) || ( isset($_POST['action2']) && $_POST['action2'] == 'bulk-optimize' )
        ) {

            $optimize_ids = esc_sql($_POST['bulk-optimize']);

            // loop over the array of record IDs and delete them
            foreach ($optimize_ids as $id) {
                $this->action_optimize_image($id);
            }

            wp_redirect(esc_url(add_query_arg()));
            exit;
        }
    }

    protected function row_actions($actions, $always_visible = false, $id = false, $actionsEnabled = false ) {
        if($id === false) {
            return parent::row_actions($actions, $always_visible);
        }
        $action_count = count( $actions );
        $i = 0;

        if ( !$action_count )
            return '';

        $out = '<div class="' . ( $always_visible ? 'row-actions visible' : 'row-actions' ) . '">';
        foreach ( $actions as $action => $link ) {
            ++$i;
            ( $i == $action_count ) ? $sep = '' : $sep = ' | ';
            $action_id = $action . "_" . $id;
            $display = (isset($actionsEnabled[$action])?"":" style='display:none;'");
            $out .= "<span id='$action_id' class='$action' $display>$link$sep</span>";
        }
        $out .= '</div>';

        $out .= '<button type="button" class="toggle-row"><span class="screen-reader-text">' . __( 'Show more details' ) . '</span></button>';

        return $out;
    }
}
