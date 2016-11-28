<?php

class ShortPixelTools {
    public static function parseJSON($data) {
        if ( function_exists('json_decode') ) {
            $data = json_decode( $data );
        } else {
            require_once( 'JSON/JSON.php' );
            $json = new Services_JSON( );
            $data = $json->decode( $data );
        }
        return $data;
    }
    
    public static function snakeToCamel($snake_case) {
        return str_replace(' ', '', ucwords(str_replace('_', ' ', $snake_case)));
    }

    public static function requestIsFrontendAjax()
    {
        $script_filename = isset($_SERVER['SCRIPT_FILENAME']) ? $_SERVER['SCRIPT_FILENAME'] : '';

        //Try to figure out if frontend AJAX request... If we are DOING_AJAX; let's look closer
        if((defined('DOING_AJAX') && DOING_AJAX))
        {
            //From wp-includes/functions.php, wp_get_referer() function.
            //Required to fix: https://core.trac.wordpress.org/ticket/25294
            $ref = '';
            if ( ! empty( $_REQUEST['_wp_http_referer'] ) ) {
                $ref = wp_unslash( $_REQUEST['_wp_http_referer'] );
            } elseif ( ! empty( $_SERVER['HTTP_REFERER'] ) ) {
                $ref = wp_unslash( $_SERVER['HTTP_REFERER'] );
            }
          //If referer does not contain admin URL and we are using the admin-ajax.php endpoint, this is likely a frontend AJAX request
          if(((strpos($ref, admin_url()) === false) && (basename($script_filename) === 'admin-ajax.php')))
            return true;
        }

        //If no checks triggered, we end up here - not an AJAX request.
        return false;
    }    
}