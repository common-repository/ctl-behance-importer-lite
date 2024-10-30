<?php
namespace CTL_BEHANCE_IMPORTER_LITE {
    if ( ! defined( 'ABSPATH' ) ) { exit; } // Exit if accessed directly
    
    //if uninstall not called from WordPress exit
    if ( !defined( 'WP_UNINSTALL_PLUGIN' ) ){
        exit();
    } 
    
    require_once ("classes/CCTLBehanceImporterLite.php");

    CCTLBehanceImporterLite::instance()->destroyPlugin();
}