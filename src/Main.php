<?php

namespace NovemBit\wp\plugins\RuntimeDebugger;

use WP_Hook;

class Main
{

    private static $_instance;

    public static function instance()
    {
        if (!isset(self::$_instance)) {
            self::$_instance = new self();
        }
        return self::$_instance;
    }

    private function __construct()
    {
        $this->redefineCoreFunction();

        if ( isset( $_REQUEST['track_hooks'] ) ) {

            $file_path = WP_CONTENT_DIR.'/wp-runtime-debugger/';

            if(!file_exists($file_path)){
                mkdir($file_path);
            }

            $file_name = str_replace( '?', '__', $_SERVER['REQUEST_URI'] );
            $file_name = preg_replace( '/(?:\W)+/', '-', $file_name );

            if ( ! $file_name ) {
                $file_name = 'hook_tracker';
            }

            $file_name = time() . $file_name;
            $offset = 0;
            do {
                ++ $offset;
                $file_full_path = $file_path . $file_name . '-' . $offset . '.csv';
            } while ( file_exists( $file_full_path ) );
            $handle = fopen( $file_full_path, 'w' ) or die( 'Cannot open file:  ' . $file_full_path );
            if ( ! defined( 'FILE_TO_LOG' ) ) {
                define( 'FILE_TO_LOG', $handle );
            }
        }
    }

    private function redefineCoreFunction()
    {

        if (!function_exists('runkit_function_redefine')) {
            echo "Please install runkit extension";
            wp_die();
        }

        runkit_method_redefine(Wp_Hook::class, 'apply_filters', ' $value, $args', file_get_contents(__DIR__.'/core/WP_Hook_apply_filters'));

        runkit_method_redefine(Wp_Hook::class, 'do_all_hook', '&$args',file_get_contents(__DIR__.'/core/WP_Hook_do_all_hook'));

    }


}