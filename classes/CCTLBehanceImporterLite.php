<?php
namespace CTL_BEHANCE_IMPORTER_LITE {
    if ( ! defined( 'ABSPATH' ) ){ exit; } // Exit if accessed directly
    
    require_once "core/CCTLWpPlugin.php";
    require_once "core/ICTLWpPlugin.php";
    
    function _p( $key ){
        _e($key, "ctl-behance-importer-lite");
    }
    function _v( $key ){
        return __($key, "ctl-behance-importer-lite");
    }
        
    class CCTLBehanceImporterLite extends CCTLWpPlugin implements ICTLWpPlugin {

        private $_szTableWorks              = null;
        
        static protected $_oInstance        = null;
                
        static public function instance(){
            if(CCTLBehanceImporterLite::$_oInstance == null ){
                CCTLBehanceImporterLite::$_oInstance = new CCTLBehanceImporterLite();
            }
            return CCTLBehanceImporterLite::$_oInstance;
        } 
        
        protected function __construct() {
            parent::__construct(
                                "ctl-behance-importer-lite",
                                "CTL Behance Importer Lite",
                                "ctl_be_imp_lite_",
                                "1.0",
                                "1");
            //-----------------------------------
            // Add Messages
            $this->__addMessage("err_db", _v("There's an error into DB."),2);
            $this->__addMessage("save_settings", _v("Settings have been saved."));
            $this->__addMessage("no_gallery_in_db_with_this_id", _v("No galleries in DB with this id!"));
            $this->__addMessage("no_work_in_db_with_this_id", _v("No works in DB with this id!"));
            $this->__addMessage("work_not_cached", _v("You have to cache the work before publishing it on the website!"));
            $this->__addMessage("err_plugin_settings", _v("To manage works and galleries, you have to set your") .
                            ' <a href="'. admin_url() . 'admin.php?page=' . 
                            $this->_szPluginDir . '-settings">' . 
                            _v("plugin settings") . '</a> ' . _v("properly") .'.', 2);
            $this->__addMessage("works_deleted", _v("All Works Deleted."));
            $this->__addMessage("some_works_imported_updated", _v("Some Works Imported."));
            $this->__addMessage("works_imported_updated", _v("All Works Imported."));
            $this->__addMessage("galleries_deleted", _v("All Galleries Deleted."));
            $this->__addMessage("gallery_deleted", _v("Gallery Deleted."));   
            $this->__addMessage("gallery_only_pro_version", _v("Galleries available only in PRO Version."),1);   
        }        
        
        protected function __initTableNames(){            
            parent::__initTableNames();
            
            $this->_szTableWorks =
                $this->_oDB->prefix . $this->_szPluginPrefixTables . "works";
            array_push($this->_aDBTables, $this->_szTableWorks);
        }
        
        public function initPlugin(){
            parent::initPlugin();

            add_action( 'wp_ajax_' . $this->_szPluginDir,
                array( $this, 'onAjaxAction') );
            add_action( 'wp_ajax_nopriv_' . $this->_szPluginDir,
                array( $this, 'onAjaxAction') );                  
            add_action('media_buttons', 
                    array( $this, 'onShortCodeAddMediaButtons') );            
        }

        protected function __initAdminMenu(){          
            array_push($this->_aAdminMenu, array(
                "label" => "settings",
                "title" => _v("Settings")
            ) );

            array_push($this->_aAdminMenu, array(
                "label" => "manage-works",
                "title" => _v("Works")
            ) );

            array_push($this->_aAdminMenu, array(
                "label" => "manage-galleries",
                "title" => _v("Galleries") . " (PRO)"
            ) );
            
            parent::__initAdminMenu();
        }      

        private function __ajaxServerImportWorkImages(){
            $id    = filter_input(INPUT_POST, "id", FILTER_SANITIZE_NUMBER_INT);
            $oWork = $this->getWork($id)[0];
            
            $oDataWork = json_decode($oWork->data_work, true);

            if ( $this->__copyFileFromUrl(
                                            $oDataWork["covers"]["original"],
                                            array("post_title" => $oWork->name )
                                            ) == false ){
                return "res=false";
            }
            
            foreach( $oDataWork["modules"] as $oModule ){
                switch($oModule["type"]){
                    case "image":{
                        if ( $this->__copyFileFromUrl(
                            $oModule["sizes"]["original"],
                            array("post_title" => $oWork->name)
                        ) == false ){
                            return "res=false";
                        }
                    }break;
                }
            }

            $this->_oDB->query("UPDATE " . $this->_szTableWorks . " SET state = 'full_cached', time = NOW() WHERE id_behance = " . $id);

            return "res=true";
        }

        private function __ajaxServerChooseWorkDialog(){
           ?>
<div class="ctl-behance-importer-lite-shortcode-wrapper wp-core-ui">
<?php

    $oWorks = $this->getWorks(true);

    if( count($oWorks) > 0 ) {
    ?>

    <div class="ctl-behance-importer-lite-shortcode-filter">   
        <label><?php _p("Digit some letters or choose a work from the list below"); ?>:</label>
        <input type="text">
    </div>
   
    <div class="ctl-behance-importer-lite-shortcode-work-list-wrapper">   
        <ul>
        <?php
        foreach ($oWorks as $oWork) {
            $oDataWork = json_decode($oWork->data_work, true);
        ?>
            <li data-work-id-behance="<?php echo $oWork->id_behance; ?>">
                                    <img width="32"
                                         src="<?php
                                            if( $oWork->state == "full_cached"){
                                                echo $this->getCachedImageFromBehanceUrl($oDataWork["covers"]["original"]);
                                            }else{
                                                echo $oDataWork["covers"]["original"];
                                            }
                                         ?>"/>
                                    <span><?php echo $oWork->name; ?></span>
                                </li>
                            <?php
                            }
                        ?>
                    </ul>
                </div>
                <div class="ctl-behance-importer-lite-hidden">
                    <label><?php _p("Final shortcode"); ?></label>
                    <textarea class="ctl-behance-importer-lite-shortcode-output"></textarea>
                </div>


                <div class="ctl-behance-importer-lite-shortcode-btn-wrapper">
                    <div onclick="ctl_behance_importer_lite_shortcode_insert_work()" class="button-primary"><?php _p("Add"); ?></div>
                    <div onclick="ctl_behance_importer_lite_shortcode_close();" class="button-primary"><?php _p("Close"); ?></div>
                </div>
            <?php
            }else{
                ?>
                <div class="error below-h2">
                        <p><?php _p("You have to import and cache works before publishing them on the website!"); ?></p>
                </div>
                <?php
            }
            ?>
</div>
            <?php
        }        
        
        private function  __ajaxServerDeleteWork(){
            $id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);
            
            $this->_oDB->query( "DELETE FROM " . $this->_szTableWorks .
                " WHERE id_behance = " . $id);
            
            return "res=true";
        }

        private function  __ajaxServerDeleteWorks(){
            $this->_oDB->query( "TRUNCATE TABLE " . $this->_szTableWorks);
            return "res=true";
        }

        private function __ajaxServerImportWorkData(){
            $aSettings = array();
            $this->getSettings($aSettings);

            $id = filter_input(INPUT_POST, 'id');
            
            
            $res = $this->__stripResponseHeader(
                $this->__sendToHost("www.behance.net","GET","/v2/projects/" . $id .
                    "/?client_id=" . $aSettings["behance-api-key"] ));

            $http_code = "null";
            $result    = "false";
            $desc      = "null";

            if($res) {
                $result = "true";

                $oData = json_decode($res,true);
                if( isset($oData["http_code"]) ){
                    $http_code = $oData["http_code"];
                }

                if( isset($oData["project"]) ){
                    $json = addslashes(json_encode($oData["project"]));
                    $this->_oDB->query( "UPDATE " . $this->_szTableWorks . " SET state = 'work_cached', data_work='". $json ."', time = NOW() WHERE id_behance = " . $id);
                }
            }

            return "res=$result&desc=$desc&http_code=$http_code";
        }

        private function __ajaxServerImportWorksSummary(){
            $page = filter_input(INPUT_POST, 'page');

            $aSettings = array();
            if( $this->getSettings($aSettings) == false ){
                return "res=false&desc=err_db";
            }

            $res = $this->__stripResponseHeader(
                $this->__sendToHost("www.behance.net","GET","/v2/users/" . $aSettings["behance-user"] .
                    "/projects?client_id=" .
                    $aSettings["behance-api-key"] . "&page=". $page));

            $http_code = "null";
            $result    = "false";
            $desc      = "null";
            $projects  = "null";

            if($res){
                $result = "true";

                $oData = json_decode($res,true);
                if( isset($oData["http_code"]) ){
                    $http_code = $oData["http_code"];
                }

                if( isset($oData["projects"]) ){
                    $projects = count($oData["projects"]);
                    
                    $iCnt = 0;
                    
                    foreach($oData["projects"] as $oProject ){
                        
                        if( $iCnt == 5 ){
                            break;
                        }
                        
                        // insert or update
                        $json = addslashes(json_encode($oProject));
                        $szWorkName = addslashes($oProject["name"]);


                        $szQuery =  "INSERT IGNORE INTO " . $this->_szTableWorks;
                        $szQuery .=  " (id_behance, name, data_summary, behance_user, state, time) VALUES( " .
                            $oProject["id"]. ",'" . $szWorkName ."' , '". $json. "', '". $aSettings["behance-user"] . "', 'summary', NOW() ) ;";
                        
                        $this->_oDB->query($szQuery );
                        
                        $iCnt += 1;
                    }
                }
            }

            return "res=$result&desc=$desc&projects=$projects&http_code=$http_code&page=".$page;
        }

        public function onShortCodeAddMediaButtons(){
            ?>
            <button type="button" onClick="__onCTLBehanceImporterLiteOpenAddWorkDialog();" class="button" data-editor="content"><span class="ctl-behance-importer-lite-media-button ctl-behance-importer-lite-icon-briefcase"></span><?php _p("Add Work");?></button>  
            
            <?php
        }

        public function getCachedImageFromBehanceUrl( $szUrl ){
            return $this->__getImageUrlFromMediaGalleryByName($this->__getFileNameFromUrl($szUrl));
        }

        private function __printShortCodeWork($oWorkRecord, $bStripStyleFromHtml = false ){

            if( count($oWorkRecord) == 0 ){
                $this->__printMessage("no_work_in_db_with_this_id");
                return;
            }

            $oWorkRecord = $oWorkRecord[0];

            if( $oWorkRecord->state == "summary" ){
                $this->__printMessage("work_not_cached");
                return;
            }

            $oWork = json_decode($oWorkRecord->data_work, true);

            
            $aLines = explode("\n", $oWork["description"]);
            foreach( $aLines as $szLine ){
                echo "<p>$szLine</p>";
            }
            ?>

            <div class="ctl-behance-importer-lite-spacer"></div>
            
            <?php

            for ( $i =0; $i < count($oWork["modules"]); $i++ ){

                $oModule = $oWork["modules"][$i];

                switch($oModule["type"]){
                    case "image":{
                        ?>
                        <img src="<?php
                            if( $oWorkRecord->state == "full_cached"){
                                echo $this->getCachedImageFromBehanceUrl($oModule["sizes"]["original"]);
                            }else{
                                echo $oModule["sizes"]["original"];
                            }
                        ?>">
                        <?php
                    }break;
                    case "text":{
                        if( $bStripStyleFromHtml == true ){
                            $szText = strip_tags($oModule["text"], '<a>');
                            $szText = preg_replace('/(<[^>]+) style=".*?"/i', '$1', $szText);
                            echo "<p>" .  $szText . "</p>";
                        }else{
                            echo "<p>" . $oModule["text"] . "</p>";
                        }
                    }break;
                    case "embed":{
                        echo '<div class="rwd-video">';
                        
                        $szEmbed = preg_replace('#<(.+?)style=(:?"|\')?[^"\']+(:?"|\')?(.*?)>#si', '<\\1 \\2>', $oModule["embed"]);
                        
                        echo $szEmbed;
                        
                        echo '</div>';
                    }break;
                    default:{
                        var_dump($oModule);
                    }
                }
                ?>
                <div class="ctl-behance-importer-lite-spacer"></div>
                <?php
            }
           
        }

        public function printShortCodeWork($idBehanceWork ){
            $oWorkRecord = $this->getWork($idBehanceWork);
            $this->__printShortCodeWork($oWorkRecord, true);
        }

        public function onAjaxAction(){
            $szExecute = filter_input(INPUT_POST, 'execute');
            
            switch($szExecute){
                case "choose-work-dialog":{
                    $szResult = $this->__ajaxServerChooseWorkDialog();
                }break;            
                case "import-works-summary":{
                    $szResult = $this->__ajaxServerImportWorksSummary();
                }break;
                case "import-work-data":{
                    $szResult = $this->__ajaxServerImportWorkData();
                }break;
                case "import-work-images":{
                    $szResult = $this->__ajaxServerImportWorkImages();
                }break;          
                case "delete-works":{
                    $szResult = $this->__ajaxServerDeleteWorks();
                }break;
                case "delete-work":{
                    $szResult = $this->__ajaxServerDeleteWork();
                }break;
                case "get-work-html":{
                    $szResult = $this->printShortCodeWork(filter_input(INPUT_POST, 'id_behance'));                  
                }break;
                case "get-popup-work":{
                    $oDataWork    = $this->getWork(filter_input(INPUT_POST, 'id_behance'))[0];
                    $oDataSummary = json_decode($oDataWork->data_summary);
                    $oDataWork    = json_decode($oDataWork->data_work);
                    
                    $oReturnedWork = array(
                        "title" => $oDataWork->name,
                        "views" => $oDataSummary->stats->views,
                        "appreciations" => $oDataSummary->stats->appreciations,
                        "comments" => $oDataSummary->stats->comments,
                        "cover" => $this->getCachedImageFromBehanceUrl($oDataSummary->covers->original),
                        "description" => $oDataWork->description,
                        "tags" => $oDataWork->tags,
                        "fields" => $oDataSummary->fields    
                    );
                    
                    $szResult = json_encode($oReturnedWork);
                }break;            
                default:{
                    $szResult = "res=false&desc=action_no_set";
                }
            }

            echo $szResult;
            wp_die();
        }

        protected function __installDBSchema(){
            parent::__installDBSchema();

            $charset_collate = $this->_oDB->get_charset_collate();

            // create table works
            $sql = "CREATE TABLE ". $this->_szTableWorks . " (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
                name text CHARACTER SET utf8 NOT NULL,
                id_behance bigint(20) NOT NULL ,
                data_summary text CHARACTER SET utf8 NOT NULL,
                data_work   text CHARACTER SET utf8 NOT NULL,
                behance_user varchar(64) CHARACTER SET ascii NOT NULL,
                state varchar(12) CHARACTER SET ascii NOT NULL,
                UNIQUE KEY id (id),
                UNIQUE KEY id_behance (id_behance)
                 ) $charset_collate;";
            $this->_oDB->query( $sql );
          
        }

        protected function __installDBData(){
            parent::__installDBData();

            $this->_oDB->insert(
                $this->_szTableSettings,
                array(
                    'time' => current_time( 'mysql' ),
                    'option_name' => 'behance-api-key',
                    'option_value' => ""
                )
            );

            $this->_oDB->insert(
                $this->_szTableSettings,
                array(
                    'time' => current_time( 'mysql' ),
                    'option_name' => 'behance-user',
                    'option_value' => ""
                )
            );
            
            $this->_oDB->insert(
                $this->_szTableSettings,
                array(
                    'time' => current_time( 'mysql' ),
                    'option_name' => 'gallery-layout',
                    'option_value' => "0"
                )
            );   
            
            $this->_oDB->insert(
                $this->_szTableSettings,
                array(
                    'time' => current_time( 'mysql' ),
                    'option_name' => 'gallery-css',
                    'option_value' => ""
                )
            );               
            $this->_oDB->insert(
                $this->_szTableSettings,
                array(
                    'time' => current_time( 'mysql' ),
                    'option_name' => 'gallery-item-padding',
                    'option_value' => "5"
                )
            );   
     
            $this->_oDB->insert(
                $this->_szTableSettings,
                array(
                    'time' => current_time( 'mysql' ),
                    'option_name' => 'gallery-hover-transparency',
                    'option_value' => "0.5"
                )
            );       
            $this->_oDB->insert(
                $this->_szTableSettings,
                array(
                    'time' => current_time( 'mysql' ),
                    'option_name' => 'gallery-hover-color',
                    'option_value' => "#000000"
                )
            );     
            $this->_oDB->insert(
                $this->_szTableSettings,
                array(
                    'time' => current_time( 'mysql' ),
                    'option_name' => 'gallery-size',
                    'option_value' => "1"
                )
            );   
            $this->_oDB->insert(
                $this->_szTableSettings,
                array(
                    'time' => current_time( 'mysql' ),
                    'option_name' => 'gallery-open-work-link',
                    'option_value' => "1"
                )
            );             
        }

        public function onAddActionLinks ( $links ) {
            $mylinks = array(
                '<a href="' . admin_url() . 'admin.php?page='. $this->_szPluginDir . '-settings">' . _v("Settings") . '</a>',
                '<a href="' . admin_url() . 'admin.php?page='. $this->_szPluginDir . '-manage-works">'._v("Manage Works") . '</a>',
                '<a href="' . admin_url() . 'admin.php?page='. $this->_szPluginDir . '-manage-galleries">'._v("Manage Galleries") . '</a>'
            );
            return array_merge( $mylinks, $links );
        }


        protected function __initSubMenuPages(){
            add_submenu_page( $this->_szPluginDir . '-settings', _v("Manage Works"), _v("Works"), 'manage_options',
                $this->_szPluginDir . '-manage-works', array( $this,"onPageManageWorks") );

            add_submenu_page( null, null, null, 'manage_options',
                $this->_szPluginDir . '-page-work', array( $this,"onPageWork") );


            add_submenu_page( $this->_szPluginDir . '-settings', _v("Manage Galleries"), _v("Galleries"), 'manage_options',
                $this->_szPluginDir . '-manage-galleries', array( $this,"onPageManageGalleries") ); 
        }        
        
        private function __validateSettings( $aSettings ){
            if( !isset($aSettings["behance-user"]) || strlen(trim($aSettings["behance-user"])) == 0 ){
                return false;
            }
            if( !isset($aSettings["behance-api-key"]) || strlen(trim($aSettings["behance-api-key"])) == 0 ){
                return false;
            }
            return true;
        }


        public function getWork( $idBehance ){
            return $this->_oDB->get_results( "SELECT * FROM " . $this->_szTableWorks . " WHERE id_behance = $idBehance");
        }

        public function getWorks( $bCachedWorks = false ){
            if ( $bCachedWorks == false ){ 
                $oWorks = $this->_oDB->get_results( "SELECT * FROM " . $this->_szTableWorks . " ORDER BY id_behance DESC");
            }else{
                $oWorks = $this->_oDB->get_results( "SELECT * FROM " . $this->_szTableWorks .
                    " WHERE state = 'work_cached' OR state = 'full_cached' ORDER BY id_behance DESC");
            }
            return $oWorks;
        }       

        public function getSettingGalleryCSS(){
            $oRow = $this->_oDB->get_row( "SELECT * FROM " . $this->_szTableSettings . 
                    " WHERE option_name = 'gallery-css'");
            return $oRow->option_value;              
        }
        public function getSettingGalleryLayout(){  
            $oRow = $this->_oDB->get_row( "SELECT * FROM " . $this->_szTableSettings . 
                    " WHERE option_name = 'gallery-layout'");
            return intval($oRow->option_value);              
        }

        
        public function getSettingGallerySize(){  
            $oRow = $this->_oDB->get_row( "SELECT * FROM " . $this->_szTableSettings . 
                    " WHERE option_name = 'gallery-size'");
            return intval($oRow->option_value);              
        }        
        public function getSettingGalleryOpenWorkLink(){  
            $oRow = $this->_oDB->get_row( "SELECT * FROM " . $this->_szTableSettings . 
                    " WHERE option_name = 'gallery-open-work-link'");
            return intval($oRow->option_value);              
        }   
        public function getSettingGalleryItemHoverColor(){
            $oRow = $this->_oDB->get_row( "SELECT * FROM " . $this->_szTableSettings . 
                    " WHERE option_name = 'gallery-hover-color'");
            return $oRow->option_value;    
        }
        public function getSettingGalleryItemPadding(){  
            $oRow = $this->_oDB->get_row( "SELECT * FROM " . $this->_szTableSettings . 
                    " WHERE option_name = 'gallery-item-padding'");
            return intval($oRow->option_value);              
        }
        
        public function getSettings( &$aSettings ){
            if ( parent::getSettings($aSettings) == false ){
                return false;
            }

            $oRow = $this->_oDB->get_row( "SELECT * FROM " . $this->_szTableSettings . 
                    " WHERE option_name = 'behance-user'");
            if($oRow){
                $aSettings["behance-user"] = $oRow->option_value;
            }

            $oRow = $this->_oDB->get_row( "SELECT * FROM " . $this->_szTableSettings . 
                    " WHERE option_name = 'behance-api-key'");
            if($oRow){
                $aSettings["behance-api-key"] = $oRow->option_value;  
            }
            
            $oRow = $this->_oDB->get_row( "SELECT * FROM " . $this->_szTableSettings . 
                    " WHERE option_name = 'gallery-layout'");
            if($oRow){
                $aSettings["gallery-layout"] = $oRow->option_value;
            }

            $oRow = $this->_oDB->get_row( "SELECT * FROM " . $this->_szTableSettings . 
                    " WHERE option_name = 'gallery-css'");
            if($oRow){
                $aSettings["gallery-css"] = $oRow->option_value;
            }
                             
            $oRow = $this->_oDB->get_row( "SELECT * FROM " . $this->_szTableSettings . 
                    " WHERE option_name = 'gallery-item-padding'");
            if($oRow){
                $aSettings["gallery-item-padding"] = $oRow->option_value; 
            }

            $oRow = $this->_oDB->get_row( "SELECT * FROM " . $this->_szTableSettings . 
                    " WHERE option_name = 'gallery-hover-color'");
            if($oRow){
                $aSettings["gallery-hover-color"] = $oRow->option_value; 
            }
             
            $oRow = $this->_oDB->get_row( "SELECT * FROM " . $this->_szTableSettings .
                    " WHERE option_name = 'gallery-hover-transparency'");
            if($oRow){
                $aSettings["gallery-hover-transparency"] = $oRow->option_value; 
            }

            $oRow = $this->_oDB->get_row( "SELECT * FROM " . $this->_szTableSettings .
                    " WHERE option_name = 'gallery-size'");
            if($oRow){
                $aSettings["gallery-size"] = $oRow->option_value; 
            }
                         
            $oRow = $this->_oDB->get_row( "SELECT * FROM " . $this->_szTableSettings .
                    " WHERE option_name = 'gallery-open-work-link'");
            if($oRow){
                $aSettings["gallery-open-work-link"] = $oRow->option_value; 
            }
            
            return true;
        }

        private function __saveSettings(){

            $szBehanceApiKey = filter_input(INPUT_POST, 'behance-api-key'); 
            $idBehanceUser   = filter_input(INPUT_POST, 'behance-user'); 
            $szGalleryCSS    = filter_input(INPUT_POST, 'gallery-css'); 
            $iDestroyDB           = filter_input(INPUT_POST, 'destroy-db', FILTER_SANITIZE_NUMBER_INT);
            $iGalleryOpenWorkLink = filter_input(INPUT_POST, 'gallery-open-work-link', FILTER_SANITIZE_NUMBER_INT);
            $iGalleryItemPadding  = filter_input(INPUT_POST, 'gallery-item-padding', FILTER_SANITIZE_NUMBER_INT);
            $iGalleryLayout       = filter_input(INPUT_POST, 'gallery-layout', FILTER_SANITIZE_NUMBER_INT);
            $iGallerySize         = filter_input(INPUT_POST, 'gallery-size', FILTER_SANITIZE_NUMBER_INT);
            $fGalleryHoverTransparency = filter_input(INPUT_POST, 'gallery-hover-transparency', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION );
            $szGalleryHoverColor  = filter_input(INPUT_POST, 'gallery-hover-color'); 
            
            if( isset($szBehanceApiKey) ){
                $szApiKey = trim($szBehanceApiKey); 
                $this->_oDB->query( "UPDATE " . $this->_szTableSettings . 
                        " SET option_value = '" . $szApiKey .
                        "', time = NOW() WHERE option_name = 'behance-api-key'");  
            }
            if( isset($idBehanceUser) ){
                $szBehanceUser = trim($idBehanceUser); 
                $this->_oDB->query( "UPDATE " . $this->_szTableSettings .
                        " SET option_value = '" .$szBehanceUser.
                        "', time = NOW() WHERE option_name = 'behance-user'");
            }
            
            if( isset($szGalleryCSS) ){
                $szGalleryCSS = trim($szGalleryCSS); 
                $this->_oDB->query( "UPDATE " . $this->_szTableSettings .
                        " SET option_value = '". $szGalleryCSS.
                        "', time = NOW() WHERE option_name = 'gallery-css'");  
            }
            

            if( !isset($iDestroyDB) ){
                $iDestroyDB = 0;
            }
            $this->_oDB->query( "UPDATE " . $this->_szTableSettings .
                    " SET option_value = '". $iDestroyDB.
                    "', time = NOW() WHERE option_name = 'destroy-db'");
            
                
            if( !isset($iGalleryOpenWorkLink) ){
                $iGalleryOpenWorkLink = 0;
            }
            $this->_oDB->query( "UPDATE " . $this->_szTableSettings .
                    " SET option_value = '". $iGalleryOpenWorkLink.
                    "', time = NOW() WHERE option_name = 'gallery-open-work-link'");                
                        

                
            if( isset($iGalleryItemPadding) ){
                $this->_oDB->query( "UPDATE " . $this->_szTableSettings .
                    " SET option_value = '". $iGalleryItemPadding.
                    "', time = NOW() WHERE option_name = 'gallery-item-padding'");  
            }            
            
            if( isset($iGalleryLayout) ){
                $this->_oDB->query( "UPDATE " . $this->_szTableSettings .
                    " SET option_value = '". $iGalleryLayout.
                    "', time = NOW() WHERE option_name = 'gallery-layout'");
            }
            
            if( isset($iGallerySize) ){
                $iGallerySize = intval($iGallerySize); 
                $this->_oDB->query( "UPDATE " . $this->_szTableSettings .
                    " SET option_value = '". $iGallerySize.
                    "', time = NOW() WHERE option_name = 'gallery-size'");  
            }  
            
            
            if( isset($fGalleryHoverTransparency) ){
                $this->_oDB->query( "UPDATE " . $this->_szTableSettings .
                    " SET option_value = '". $fGalleryHoverTransparency.
                    "', time = NOW() WHERE option_name = 'gallery-hover-transparency'"); 
            }  
            if( isset($szGalleryHoverColor) ){
                $szGalleryHoverColor = trim($szGalleryHoverColor); 
                $this->_oDB->query( "UPDATE " . $this->_szTableSettings .
                    " SET option_value = '". $szGalleryHoverColor.
                    "', time = NOW() WHERE option_name = 'gallery-hover-color'"); 
            }              

            $this->__printMessage("save_settings");
        }

        public function onPageManageSettings(){        
            ?>
            <div class="wrap ctl-behance-importer-lite-admin-wrapper">
                <h1><?php _p("General Settings"); ?></h1>

                <?php
                    $szAction = filter_input(INPUT_GET, 'action');
                    switch($szAction){
                        case "save_settings":{
                            $this->__saveSettings();
                        }break;
                    }
 
                    $this->__printAdminMenu("settings");                    
                    
                    $aSettings = array();                    
                    if ( $this->getSettings($aSettings) == false ){
                        $this->__printMessage("err_db");
      
                    }else{
                        $this->__printMessage();
?>
                                <br>
                <form method="post" action="<?php echo admin_url(); ?>admin.php?page=ctl-behance-importer-lite-settings&action=save_settings" novalidate="novalidate">
                    <input type="hidden" name="option_page" value="general"><input type="hidden" name="action" value="update"><input type="hidden" id="_wpnonce" name="_wpnonce" value="ec469a1835"><input type="hidden" name="_wp_http_referer" value="/poker/wp-admin/options-general.php">

                    <div class="nav-tabs">
                        <table id="tab-settings" class="form-table wsal-tab widefat">
                            <tr>
                                <th scope="row"><label for="behance-api-key">API KEY / CLIENT ID</label></th>
                                <td><input name="behance-api-key" type="text" id="behance-api-key" value="<?php echo $aSettings["behance-api-key"]; ?>" class="regular-text" aria-describedby="behance-api-key-description">
                                    <p id="behance-api-key-description" class="description"><?php _p("To get an API key, please login on"); ?> <a href="https://www.behance.net/dev/apps" target="_blank">https://www.behance.net/dev/apps</a> <?php _p("with your credentials." ); ?></p></td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="behance-user"><?php _p("Behance Username"); ?></label></th>
                                <td><input name="behance-user" type="text" id="behance-user" aria-describedby="behance-user-description" value="<?php echo $aSettings["behance-user"]; ?>" class="regular-text">
                                    <p id="behance-user-description" class="description"><?php _p("Enter your Behance Username." ); ?></p></td>
                            </tr>
                            <tr>
                                <th scope="row"><label for="gallery-layout"><?php _p("Gallery Layout"); ?><br>
                                    (<?php _p("settings available only in PRO version"); ?>)</label></th>
                                <td><label for="gallery-layout">
                                    <input disabled type="radio" name="gallery-layout" value="0" 
                                        <?php echo ($aSettings["gallery-layout"] == "0" ? 
                                                            'checked="checked"' : ''); ?>>
                                    <span><?php _p("title on hover"); ?></span>
                                    <input disabled type="radio" name="gallery-layout" value="1" 
                                        <?php echo ($aSettings["gallery-layout"] == "1" ? 
                                                            'checked="checked"' : ''); ?>>
                                    <span><?php _p("title visible"); ?></span>
                                    <p id="gallery-layout-description" class="description"><?php _p("Choose the layout that better fits with your theme." ); ?></p>
                                </td>
                            </tr>    
                            <tr>
                                <th scope="row"></th>
                                <td><label for="gallery-size">
                                    <input disabled type="radio" name="gallery-size" value="0" 
                                        <?php echo ($aSettings["gallery-size"] == "0" ? 
                                                            'checked="checked"' : ''); ?>>
                                    <span><?php _p("small"); ?></span>
                                    <input disabled type="radio" name="gallery-size" value="1" 
                                        <?php echo ($aSettings["gallery-size"] == "1" ? 
                                                            'checked="checked"' : ''); ?>>
                                    <span><?php _p("medium"); ?></span>
                                    <input disabled type="radio" name="gallery-size" value="2" 
                                        <?php echo ($aSettings["gallery-size"] == "2" ? 
                                                            'checked="checked"' : ''); ?>>
                                    <span><?php _p("large"); ?></span>                                    
                                    <p id="gallery-size-description" class="description"><?php _p("Choose the layout tiles size that better fits with your theme." ); ?></p>
                                </td>
                            </tr>       
                            <tr>
                                <th scope="row"></th>
                                <td><input disabled name="gallery-hover-color" type="text" id="gallery-hover-color" aria-describedby="gallery-hover-color-description" value="<?php echo $aSettings["gallery-hover-color"]; ?>" class="regular-text">
                                    <p id="gallery-hover-color-description" class="description"><?php _p("Insert gallery hover color in hexadecimal format (#000000)." ); ?></p></td>
                            </tr>    
                            <tr>
                                <th scope="row"></th>
                                <td><input disabled name="gallery-hover-transparency" type="range" name="voto" min="0" max="1" step="0.1" id="gallery-hover-color" aria-describedby="gallery-hover-transparency-description" value="<?php echo $aSettings["gallery-hover-transparency"]; ?>" class="regular-text">
                                    <p id="gallery-hover-transparency-description" class="description"><?php _p("Insert gallery hover trasparency." ); ?></p></td>
                            </tr>                              
                            <tr>
                                <th scope="row"></th>
                                <td><input disabled name="gallery-item-padding" type="text" onkeypress='return event.charCode >= 48 && event.charCode <= 57' id="gallery-item-padding" aria-describedby="gallery-item-padding-description" value="<?php echo $aSettings["gallery-item-padding"]; ?>" class="small-text">
                                    <p id="gallery-item-padding-description" class="description"><?php _p("Insert gallery item padding (px)." ); ?></p></td>
                            </tr>                             
                            <tr>
                                <th scope="row"></th>
                                <td>
                                    <textarea disabled name="gallery-css" type="text" id="behance-user" aria-describedby="gallery-css-description" class="regular-text"><?php echo $aSettings["gallery-css"]; ?></textarea>
                                    <p id="gallery-css-description" class="description"><?php _p("Override our css styles here." ); ?></p>
                                
                                
                                </td>
                            </tr>   
                            <tr>
                                <th scope="row"><label for="gallery-open-work-link"></th>
                                <td><label for="gallery-open-work-link">
                                        <input disabled name="gallery-open-work-link" type="checkbox" id="gallery-open-work-link" value="1" <?php echo ( strcmp($aSettings["gallery-open-work-link"],"1") == 0 ? "checked=checked" : ""); ?> aria-describedby="gallery-open-work-link-description"><?php _p("Open work"); ?></label>
                                    <p id="gallery-open-work-link-description" class="description"><?php _p("If you have created an article using the work shortcode previuosly, it will be reached by gallery item. Otherwise a popup will be opened." ); ?></p></td>
                            </tr>                            
                            <tr>
                                <th scope="row"><label for="destroy-db"><?php _p("Remove DB"); ?></label></th>
                                <td><label for="destroy-db">
                                        <input name="destroy-db" type="checkbox" id="destroy-db" value="1" <?php echo ( strcmp($aSettings["destroy-db"],"1") == 0 ? "checked=checked" : ""); ?> aria-describedby="destroy-db-description"><?php _p("Remove DB during plugin updates"); ?></label>
                                        <p id="destroy-db-description" class="description"><?php _p("In case of plugin updates all your works, galleries and settings will be deleted."); ?><br>
                                            <span class="ctl-behance-importer-lite-red-disclaimer"><?php _p("To keep your DB leave this checkbox blank."); ?></span></p>
                                </td>
                            </tr>
                        </table>
                    </div>

                    <p class="submit">
                        <input type="submit" name="submit" id="submit" class="button button-primary" value="<?php _p("Save Changes"); ?>">
                        <a href="https://codecanyon.net/item/ctl-behance-importer/17102393?ref=codethislab" 
                       class="button ctl-behance-importer-lite-pro-btn"><?php _p("Get Pro Version" ); ?></a>
                    </p>
                    
                </form>
                <?php
                    }
   
                ?>
            </div>

            <?php
            
            $this->__printFooter();
        }

        protected function __printFooter( $szClass = "" ){
            $this->__printTrademarks("ctl-behance-importer-lite-admin-trademarks-wrapper");
            
            parent::__printFooter($szClass);
        }
        
        protected  function __printDocumentation(){
            ?>
                <br>               
                <img width="100%;" src="<?php echo $this->getPluginUrl(); ?>/images/banner-full.jpg">

    <h2>About this Plugin</h2>

    <p>You have already published all your works on Behance, now it’s time for you to get a website but you don’t want to waste more time?</p>

    <p>A client asked you to import all his projects from Behance to his website and you don’t know where to start?</p>

    <p>You are in the right place!</p>

    <p><strong>CTL Behance Importer</strong> allows you to move all your projects from every portfolio on Behance directly to your website in a glance!</p>

    <p>With CTL Behance Importer Lite you can:</p>

    <ul>
        <li><strong>Import all the images of your works into WordPress</strong><br>
        You can use them to create sliders and covers for your posts or pages.</li>

        <li><strong>Get rid of the limit of 150 requests per hour imposed by Behance</strong><br>
        Your visitors will be free to view all works endlessly.</li>

        <li><strong>Exploit all the seo tools of WordPress to index your works</strong><br>
        You can choose to import your projects as real WordPress articles and not just  pop up. In this way you’ll be able to index your contents using all the seo tools provided by WordPress and  scale the Google raking.</li>

        <li><strong>Customize the contents of your projects</strong><br>
        You’ll be able to personalize every single work using the content editor of WordPress easily and intuitively.</li>        
    </ul>

    <p>It's not enough for all your works?</p>
    
    <p><strong>Get Behance Importer Pro!</strong></p>
    
    <p>What changes?</p>
    
    <ul>      
        <li><strong>No Limits to the number of works you can import</strong><br>
        Do you have 10-100- or 100,000 projects in your portfolio? No problem! You have no limits!</li>
        
        <li><strong>Create unlimited galleries with your projects and set your showing order</strong><br>
        You’ll be free to organize your works and gather them however you want. No more category limits as in Behance!</li>

        <li><strong>Responsive Gallery</strong><br>
        The gallery will fit to the theme of your layout responsively.</li>
    </ul>


    <p><strong>Minimum Requirements:</strong></p>

    <ul>
        <li>PHP 4.3</li>
        <li>WordPress 4.5.3</li>
        <li>HTML5</li>
        <li>Canvas</li>
        <li>Javascript / jQuery</li>
    </ul>

    <h2>Installation</h2>

    <p>This plugin installation is the same as any other WordPress plugin. For further instructions, refer to the two options below:</p>

    <h3>Installation by ZIP File</h3>

    <p>From your WordPress dashboard, choose 'Add New' under the 'Plugins' category.<br>
    Select 'Upload' from the set of links at the top of the page (the second link).<br>
    From here, search the zip file titled 'ctl-behance-importer.zip' included in your plugin purchase and click the 'Install Now' button.<br>
    Once installation is complete, activate the plugin to enable its features.</p>

    <h3>Installation by FTP</h3>

    <p>Find the directoy titled 'ctl-behance-importer'.<br>
                Upload it and all the files in the plugins directory of your WordPress.<br>
                Install (/WORDPRESS-DIRECTORY/wp-content/plugins/).<br>
    From your WordPress dashboard, choose 'Installed Plugins' under the 'Plugins' category.<br>
    Locate the newly added plugin and click on the 'Activate' link to enable its features.</p>

    <h2>Plugin Settings</h2>

    <p>In this panel you can manage the plugin settings.</p>

    <div class="ctl-behance-importer-lite-img-guide">
        <img src="<?php echo plugins_url() . "/" . $this->_szPluginDir; ?>/images/plugin_settings.png"/>
    </div>

    <h3>API KEY / CLIENT ID</h3>

    <p>To get an API key, please login on <a href="https://www.behance.net/dev/apps" target="_blank">https://www.behance.net/dev/apps</a> with your credentials.</p>


    <h3>Behance Username</h3>
    <p>Enter your Behance Username.</p>

    <h3>Gallery Layout</h3>
    <p>Choose the layout that better fits with your theme.</p>

    <p>Choose the layout tiles size that better fits with your theme.</p>
    <p>Insert gallery hover color in hexadecimal format (#000000).</p>
    <p>Insert gallery hover trasparency.</p>
    <p>Insert gallery item padding (px).</p>
    <p>Override our css styles.</p>
    <p>Choose to open a popup or the related article when a user clicks on a gallery item.</p>


    <h2>Manage Works</h2>

    <p>In this panel you can manage the works import phase.</p>

    <div class="ctl-behance-importer-lite-img-guide">
        <img src="<?php echo plugins_url() . "/" . $this->_szPluginDir; ?>/images/page_works.png"/>
    </div>

    <h3>Import Works</h3>

    <p>The import of your Behance portfolio takes place in 3 stages:</p>

    <ul>
        <li>Import Summary</li>
        <li>Import Work Data</li>
        <li>Import Work Images</li>
    </ul>

    <p>Due to the Behance api time limits <a target="_blank" href="https://www.behance.net/dev/api/endpoints/">https://www.behance.net/dev/api/endpoints/</a> the second phase is delayed by default, so it may require a few minutes. The total time depends on your number of works.</p>


    <h3>Delete Works</h3>
    <p>This action is not reversible! All works and galleries will be deleted!</p>


    <h2>Manage Galleries</h2>

    <p>In this panel you can view and delete all the galleries you've created.</p>

    <div class="ctl-behance-importer-lite-img-guide">
        <img src="<?php echo plugins_url() . "/" . $this->_szPluginDir; ?>/images/manage_galleries.png"/>
    </div>

    <p>You can easily find a gallery by its name.</p>

    <p>Every gallery can be edited and deleted.</p>

    <h3>Create Gallery</h3>
    <p>This action opens the "Create Gallery" page.</p>            

    <h3>Delete Galleries</h3>
    <p class="alert-msg">This action is not reversible! All galleries will be deleted!</p>


    <h2 class="jump-edit-gallery ctl-doc-title">Edit Gallery</h2>

    <p>In this panel you can edit a gallery.</p>

    <div class="ctl-behance-importer-lite-img-guide">
        <img class="img-responsive" src="<?php echo plugins_url() . "/" . $this->_szPluginDir; ?>/images/edit_gallery.png"/>
    </div>

    <h3>Gallery Name</h3>

    <p>Type or edit a gallery name.</p>


    <h3>Gallery Works</h3>    
    <p>Choose the works you want to add in the gallery from your portfolio and order them as you want. To move each work you can drag it up/down or click on the "Move Up"/ "Move Down" buttons</p>


    <h3>Select All Works</h3>
    <p>Import all works into the gallery.</p>

    <h3>Clear All Works from the Gallery</h3>
    <p>Remove all entries.</p>     

    <h3>Save Changes</h3>
    <p>Save the Gallery.</p>   

    <h2>Shortcodes</h2>

    <p>This plugin installs 2 buttons on Wordpress Visual Editor.</p>

    <ul>
        <li>Add Gallery of Works</li>
        <li>Add Work</li>
    </ul>            

    <div class="ctl-behance-importer-lite-img-guide">
        <img src="<?php echo plugins_url() . "/" . $this->_szPluginDir; ?>/images/shortcode_general.png"/>
    </div>

    <h3>Add Gallery of Works</h3>
    <div class="ctl-behance-importer-lite-img-guide ctl-behance-importer-lite-max-width-500">
        <img src="<?php echo plugins_url() . "/" . $this->_szPluginDir; ?>/images/shortcode_add_gallery.png"/>
    </div>
    <p>Choose among all the galleries you've created the one/s you want to add in the page.</p>

    <h4>Gallery example</h4>
    <div class="ctl-behance-importer-lite-img-guide">
        <img src="<?php echo plugins_url() . "/" . $this->_szPluginDir; ?>/images/gallery.png"/>
    </div>               

    <h4>Popup with small content</h4>
    <div class="ctl-behance-importer-lite-img-guide ctl-behance-importer-lite-max-width-850">
        <img src="<?php echo plugins_url() . "/" . $this->_szPluginDir; ?>/images/popup_small_content.png"/>
    </div>            
    <p>This popup includes the work cover, description, categories and tags.</p>

    <h4>Popup with full content</h4>
    <div class="ctl-behance-importer-lite-img-guide ctl-behance-importer-lite-max-width-850">
        <img src="<?php echo plugins_url() . "/" . $this->_szPluginDir; ?>/images/popup_full_content.png"/>
    </div>  
    <p>This popup includes the description and the sections of all works.</p>

    <h3>Add Work</h3>
    <div class="ctl-behance-importer-lite-img-guide ctl-behance-importer-lite-max-width-500">
        <img src="<?php echo plugins_url() . "/" . $this->_szPluginDir; ?>/images/shortcode_add_work.png"/>
    </div>
    <p>Choose among all imported works the one/s you want to add in the page.</p>

    <h2>Uninstall</h2>

    <p>This plugin can be deleted in the same way of any other WordPress plugin.</p>

    <h2>Acknowledgements</h2>

    <p>Thank you very much for purchasing this plugin. We'd be glad to help you if you have any questions related to the plugin. We'll do our best to assist you. If you have a more general question about the plugins on CodeCanyon, you might consider visiting the forums and asking your question in the "Item Discussion" section.</p>

      <?php
        }


        private function __printTrademarks( $szClass ){
            ?>
            <div class="wrap <?php echo $szClass; ?>" >
                <p class="trademarks">Trademarks:</p>
                <p>The company, product and service names used in this plugin are for identification purposes only. All trademarks and registered trademarks are the property of their respective owners.</p>

                <p>Behance® is registered trademarks of the Adobe Systems Incorporated in the United States and in other countries.</p>

                <p>Affordable Code This Lab srl is not affiliated with Adobe Systems Incorporated.</p>

                <p>All other trademarks or registered trademarks are the property of their respective owners.</p>
            </div>
            <?php
        }
        
        private function __printManageWorksButtons(){
            ?>
            <div class="submit">
                <span class="button button-primary
                ctl-behance-importer-lite-action-import-summary"><?php _p("Import Summary" ); ?></span>

                <span class="button button-primary
                ctl-behance-importer-lite-action-import-works"><?php _p("Import Work Data" ); ?></span>

                <span class="button button-primary
                ctl-behance-importer-lite-action-import-images"><?php _p("Import Work Images" ); ?></span>

                <span class="button button-primary
                ctl-behance-importer-lite-action-delete-works"><?php _p("Delete Works" ); ?></span>
                
                <a href="https://codecanyon.net/item/ctl-behance-importer/17102393?ref=codethislab" 
                       class="button ctl-behance-importer-lite-pro-btn"><?php _p("Get Pro Version" ); ?></a>                
            </div>
            <?php
        }

        

        public function onPageManageGalleries(){
            $aSettings  = array();
            $this->getSettings($aSettings);
            ?>
            <div class="wrap ctl-behance-importer-lite-admin-wrapper">
                <h1><?php _p("Manage Galleries"); ?></h1>
                <?php                    
                    $this->__printAdminMenu("manage-galleries");
                    $this->__printMessage("gallery_only_pro_version");          
                ?>
                <div class="submit">
                    <a href="https://codecanyon.net/item/ctl-behance-importer/17102393?ref=codethislab" 
                       class="button ctl-behance-importer-lite-pro-btn"><?php _p("Get Pro Version" ); ?></a>
                </div>
                
                
            </div>
            <?php
            $this->__printFooter();
        }

        public function onPageWork(){
            $idBehanceWork = filter_input(INPUT_GET, 'idBehanceWork');
            $oWorkRecord = $this->getWork($idBehanceWork);
            ?>
            <div class="wrap ctl-behance-importer-lite-work-preview-wrapper">
                <h1><?php echo $oWorkRecord[0]->name; ?></h1>
                <?php
                    $this->__printAdminMenu("manage-works");
                    $this->__printMessage();
                ?>
                <?php
                    $this->__printShortCodeWork($oWorkRecord, true);
                ?>
                
                <p class="submit">
                    <a class="button button-primary" href="<?php echo $this->_szAdminPageUrl . "manage-works"; ?>"><?php _p("Back"); ?></a>
                    <a href="https://codecanyon.net/item/ctl-behance-importer/17102393?ref=codethislab" 
                       class="button ctl-behance-importer-lite-pro-btn"><?php _p("Get Pro Version" ); ?></a>                    
                    </p>
            </div>
            <?php
            $this->__printFooter();
        }

        public function onPageManageWorks(){
            $oWorks    = $this->getWorks();
            $aSettings = array();
            $this->getSettings($aSettings);
            ?>
            <div class="wrap ctl-behance-importer-lite-admin-wrapper">
                <h1><?php _p("Manage Works"); ?></h1>
                <?php
                    $this->__printAdminMenu("manage-works");
                    $this->__printMessage();
                ?>
                <?php
                    if( $this->__validateSettings($aSettings) == false ){
                        $this->__printMessage("err_plugin_settings");
                        ?>
                        <div class="tablenav top">
                            <a href="<?php echo $this->_szAdminPageUrl . "settings"?>" class="button button-primary"><?php _p("Settings" ); ?></a>
                        </div>                 
                    <?php
                        }else if( count($oWorks) == 0 ) {
                            $this->__printMessage(_v("To Import your Works click on Import button."));
                    ?>
                        <div class="tablenav top">
                            <span class="button button-primary
                            ctl-behance-importer-lite-action-import-summary"><?php _p("Import or Update Summary"); ?></span>
                        </div>
                    <?php
                        }else{
                        $this->__printManageWorksButtons();
                    ?>

                        <table class="form-table wsal-tab widefat">
                            <tbody>
                            <tr>
                                <th scope="row">
                                    <label for="works-totals"><?php _p("Number of Works"); ?></label>
                                </th>
                                <td>
                                    <p><?php
                                            echo count($oWorks) . " ";
                                        ?></p>
                                    <p class="description"><?php _p("Limited to max 5 works, to unlock unlimited works import "); ?> <a href="https://codecanyon.net/item/ctl-behance-importer/17102393?ref=codethislab"><?php _p("Get Pro Version"); ?></a>.</p>
                                </td>
                            </tr>
                            <tr>
                                <th scope="row">
                                    <label for="works-totals"><?php _p("Legenda"); ?></label>
                                </th>
                                <td>
                                    <ul class="ctl-behance-importer-lite-legenda-traffic-lights">
                                        <li><img src="<?php echo plugins_url() . "/" . $this->_szPluginDir; ?>/images/traffic_light_circle_red.png" /><?php _p("Only work name imported"); ?></li>
                                        <li><img src="<?php echo plugins_url() . "/" . $this->_szPluginDir; ?>/images/traffic_light_circle_yellow.png" /><?php _p("Images not imported yet"); ?></li>
                                        <li><img src="<?php echo plugins_url() . "/" . $this->_szPluginDir; ?>/images/traffic_light_circle_green.png" /><?php _p("Ready to be published"); ?></li>
                                    </ul>
                                </td>
                            </tr>                            
                            <tr>
                                <th scope="row">
                                    <label for="works-filter"><?php _p("Search by Work Name"); ?></label>
                                </th>
                                <td>
                                    <input type="text" placeholder="" class="works-filter regular-text">
                                </td>
                            </tr>
                            </tbody>
                        </table>

                        <table class="wp-list-table widefat striped list-works">
                            <thead>
                            <tr>
                                <th scope="col" id="work-title" class="manage-column column-primary">
                                    <p><?php _p("Work Name"); ?></p>
                                </th>
                                <th scope="col" id="work-behance-user" class="manage-column">
                                    <p><?php _p("Behance User"); ?></p>
                                </th>
                                <th scope="col" id="work-actions" class="manage-column">
                                    <p><?php _p("State"); ?></p>
                                </th>
                                <th scope="col" id="work-categories" class="manage-column">
                                    <p><?php _p("Categories"); ?></p>
                                </th>
                                <th scope="col" id="work-tags" class="manage-column">
                                    <p><?php _p("Tags"); ?></p>
                                </th>
                                <th scope="col" id="work-image" class="manage-column">
                                    <p><?php _p("Work Cover"); ?></p>
                                </th>
                            </tr>
                            </thead>


                            <tbody id="the-list" data-wp-lists="list:works">
                            <?php
                                foreach($oWorks as $oWork){
                                    $oWorkSummary = json_decode( $oWork->data_summary, true );
                                    ?>
                                    <tr data-work-id="<?php echo $oWorkSummary["id"];?>">

                                        <td class="work-title column-work-title has-row-actions column-primary"
                                            data-colname="<?php _p("Work Title"); ?>">
                                            <a href="<?php echo $this->_szAdminPageUrl . "page-work";?>&idBehanceWork=<?php echo $oWork->id_behance; ?>"><strong><?php echo $oWorkSummary["name"]; ?></strong></a>

                                            <div class="row-actions">
                                                <span class="edit">
                                                    <a href="<?php echo $this->_szAdminPageUrl . "page-work";?>&idBehanceWork=<?php echo $oWork->id_behance; ?>"><?php _p("View"); ?></a> |
                                                </span>
                                                
                                                <?php if ($oWork->state != "full_cached"){
                                                    ?>
                                                <span class="edit">
                                                    <a href="#" class="ctl-behance-importer-lite-action-import-work"><?php _p("Cache"); ?></a> |
                                                 </span>
                                                <?php
                                                }
                                                ?>

                                                
                                                
                                                 <span class="trash">
                                                    <a href="#" class="ctl-behance-importer-lite-action-delete-work"><?php _p("Delete"); ?></a> |
                                                 </span>
                                            </div>
                                            <button type="button" class="toggle-row">
                                                <span class="screen-reader-text">Edit</span>
                                            </button>
                                        </td>

                                        <td class="work-behance-user column-work-behance-user"
                                            data-colname="<?php _p("Behance User"); ?>">
                                            <a target="_blank" href="https://www.behance.net/<?php echo$oWork->behance_user; ?>"><?php echo $oWork->behance_user ?></a>
                                        </td>
                                        <td class="work-state column-work-state"
                                            data-colname="<?php _p("State"); ?>"
                                            data-state="<?php echo $oWork->state; ?>">
                                            <?php
                                                switch($oWork->state){
                                                    case "summary":{
                                                        ?>
                                                        <img src="<?php echo plugins_url() . "/" . $this->_szPluginDir; ?>/images/traffic_light_circle_red.png"/>
                                                        <?php
                                                    }break;
                                                    case "work_cached":{
                                                        ?>
                                                        <img src="<?php echo plugins_url() . "/" . $this->_szPluginDir; ?>/images/traffic_light_circle_yellow.png"/>
                                                        <?php
                                                    }break;
                                                    case "full_cached":{
                                                        ?>
                                                        <img src="<?php echo plugins_url() . "/" . $this->_szPluginDir; ?>/images/traffic_light_circle_green.png"/>
                                                        <?php
                                                    }break;
                                                }
                                            ?>
                                        </td>
                                        <td class="work-categories column-work-categories"
                                            data-colname="<?php _p("Categories"); ?>">
                                            <ul class="ctl-behance-importer-lite-list-tags">
                                            <?php
                                                foreach( $oWorkSummary["fields"] as $szField ){
                                                    echo "<li>" . $szField . "</li>";
                                                }
                                            ?>
                                            </ul>
                                        </td>
                                        <td class="work-categories column-work-tags"
                                            data-colname="<?php _p("Tags"); ?>">
                                            <?php
                                                if( strcmp($oWork->data_work, "") != 0 ){
                                                    $oDataWork = json_decode( $oWork->data_work, true );
                                                    if( isset($oDataWork["tags"])){
                                                        echo "<ul class='ctl-behance-importer-lite-list-tags'>";
                                                        foreach( $oDataWork["tags"] as $szTag ){
                                                            echo "<li>" . $szTag . "</li>";
                                                        }
                                                        echo "</ul>";
                                                    }
                                                }
                                            ?>
                                        </td>
                                        <td class="work-image column-work-image"
                                            data-colname="<?php _p("Work Image"); ?>">
                                            <img alt="<?php echo $oWorkSummary["name"]; ?>" src="<?php

                                                if( $oWork->state == "full_cached"){
                                                    echo $this->getCachedImageFromBehanceUrl($oWorkSummary["covers"]["115"]);
                                                }else{
                                                    echo $oWorkSummary["covers"]["115"];
                                                }

                                            ?>" class="avatar photo" height="80" />
                                        </td>
                                    </tr>
                                    <?php
                                }
                            ?>
                            </tbody>


                        </table>

                        <?php

                        $this->__printManageWorksButtons();
                    }
                ?>
            </div>
            <?php
            $this->__printFooter();
        }

        public function onIncludeAdminScriptsAndStyles(){
            
            parent::onIncludeAdminScriptsAndStyles();
            
            $this->__registerStyle('animation', "animation.css");
            $this->__registerStyle('dingbats', "ctl-behance-importer-lite-dingbats.css");
            $this->__registerStyle('shortcodes', "shortcodes.css");
            $this->__registerStyle('admin', "admin.css");

            $g_oLocalizationShortcodes = array(
                "Add Gallery of Works" => _v("Add Gallery of Works"),
                "To Add a Gallery switch the editor to Visual Mode" => _v("To Add a Gallery switch the editor to Visual Mode"),
                "Add Work"  => _v("Add Work"),
                "To Add a Work switch the editor to Visual Mode" => _v("To Add a Work switch the editor to Visual Mode"),
                "Ooops! Something Went Wrong!" =>
                            _v("Ooops! Something Went Wrong!"),   
                "Loading" =>
                            _v("Loading"),   
                "DO NOT REMOVE THIS SHORTCODE! WE NEED IT TO LINK THIS POST TO A GALLERY THAT INCLUDE THIS WORK. THIS SHORTCODE WILL NOT APPEAR IN THE POST" =>
                            _v("DO NOT REMOVE THIS SHORTCODE! WE NEED IT TO LINK THIS POST TO A GALLERY THAT INCLUDE THIS WORK. THIS SHORTCODE WILL NOT APPEAR IN THE POST")
            );   
            
            $this->__registerScript('shortcodes', 
                                    'shortcodes.js',
                                    array('jquery'),
                                    $g_oLocalizationShortcodes, 
                                    'g_oLocalizationShortcodes');
            
            $g_oLocalizationAdmin = array(
                "Are you sure to delete the work from db? This action isn't reversible!" => 
                            _v("Are you sure to delete the work from db? This action isn't reversible!"),
                "Loading" => 
                            _v("Loading"),  
                "Stop" => 
                            _v("Stop"),
                "Import" => 
                            _v("Import"),                
                "Are you sure to delete all works from db? This action isn't reversible and all galleries will be lost!" =>
                            _v("Are you sure to delete all works from db? This action isn't reversible and all galleries will be lost!"),
                "Are you sure to delete all galleries from db? This action isn't reversible!" =>
                            _v("Are you sure to delete all galleries from db? This action isn't reversible!"), 
                "Caching Images for Work" =>
                            _v("Caching Images for Work"),  
                "Loading Next Work" =>
                            _v("Loading Next Work"),   
                "Ooops! Something Went Wrong!" =>
                            _v("Ooops! Something Went Wrong!"),  
                "Getting summary, page" =>
                            _v("Getting summary, page"),  
                "Loading Next Page" =>
                            _v("Loading Next Page"),    
                "Please type a name for the gallery" =>
                            _v("Please type a name for the gallery"),  
                "Remove" =>
                            _v("Remove"),  
                "Move up" =>
                            _v("Move up"),  
                "Move down" =>
                            _v("Move down"),    
                "Are you sure to delete the gallery from db? This action isn't reversible!"
                            => _v("Are you sure to delete the gallery from db? This action isn't reversible!")
                
            );
            
            $this->__registerScript('admin', 
                                    'admin.js', 
                                    array(
                                        'jquery', 'jquery-ui-sortable'
                                    ),
                                    $g_oLocalizationAdmin, 
                                    'g_oLocalizationAdmin'); 
        }

    }    
}    