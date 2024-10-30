<?php
namespace CTL_BEHANCE_IMPORTER_LITE {
    if ( ! defined( 'ABSPATH' ) ){ exit; } // Exit if accessed directly
    
    interface ICTLWpPlugin{

        public function initPlugin();
        public function destroyPlugin();

        public function onInstallDBSchema();
        public function onInstallDBData();

        public function onIncludeAdminScriptsAndStyles();
        public function onAddActionLinks( $links );
        public function onAjaxAction();

        public function onActivationRedirect();
        public function onSetActivationRedirect();

        public function onPageManageSettings();
    }
}