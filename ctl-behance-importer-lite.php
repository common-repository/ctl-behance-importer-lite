<?php
/*
Plugin Name: CTL Behance Importer Lite
Plugin URI: http://www.codethislab.com/
Description: Behance Importer Wordpress Plugin
Version: 1.0
Author: Code This Lab srl
Author URI: http://www.codethislab.com/
License: GPL
Copyright: Code This Lab srl
Text Domain: ctl-behance-importer-lite
Domain Path: /langs
*/ 
namespace CTL_BEHANCE_IMPORTER_LITE {
    if ( ! defined( 'ABSPATH' ) ){ exit; } // Exit if accessed directly
    
    require_once ("classes/CCTLBehanceImporterLite.php");

    CCTLBehanceImporterLite::instance()->initPlugin();  
    
    register_activation_hook( __FILE__, 
        array(CCTLBehanceImporterLite::instance(), 'onActivationPlugin') );     

}


