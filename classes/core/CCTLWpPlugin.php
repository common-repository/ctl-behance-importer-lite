<?php
namespace CTL_BEHANCE_IMPORTER_LITE {
    if ( ! defined( 'ABSPATH' ) ){ exit; } // Exit if accessed directly
    
    require_once "CCTLUtils.php";

    class CCTLWpPlugin extends CCTLUtils{

        protected $_szCompatibleVersion       = null;
        protected $_szPluginDir               = null;
        protected $_szPluginPrefixTables      = null;
        protected $_szPluginName              = null;
        protected $_szVersion                 = null;
        
        protected $_oDB                       = null;
        protected $_aDBTables                 = null;
        protected $_szTableSettings           = null;
        protected $_szAdminPageUrl            = null;
        protected $_aAdminMenu                = null;
        protected $_aMessages                 = null;
        
        protected function __construct(
                            $szPluginDir, 
                            $szPluginName, 
                            $szPluginPrefixTables,
                            $_szVersion,
                            $szCompatibleVersion ) {
            
            $this->_szPluginDir  = $szPluginDir;
            $this->_szPluginName = $szPluginName;
            $this->_szPluginPrefixTables = $szPluginPrefixTables;
            $this->_szVersion            = $_szVersion;
            $this->_szCompatibleVersion  = $szCompatibleVersion;
            
            $this->_szAdminPageUrl = admin_url() . "admin.php?page=" . $this->_szPluginDir . "-";  

            parent::__construct();

            global $wpdb;
            $this->_oDB = $wpdb;
            $this->_aDBTables  = array();            
            $this->_aAdminMenu = array();  
            $this->_aMessages  = array(); 

            load_plugin_textdomain(
                $this->getPluginDir(), false,
                $this->getPluginDir() . "/langs/" );             
            
            $this->__initTableNames();            
            $this->__initAdminMenu();  
        }
        
        
        protected function __addMessage( $szKey, $szMessage, $iCode = 0 ){          
            $this->_aMessages[$szKey] = array ( 
                                            "msg"  => $szMessage,
                                            "code" => $iCode ) ;
        }
        
        protected function __getMessage( $szKey ){    
            if( isset($this->_aMessages[$szKey]) ){
                return $this->_aMessages[$szKey];
            }else{
                return null;
            }
        }
        
        protected function __initSubMenuPages(){
            // insert sub menu pages
        }

        public function onMenu(){
            add_menu_page(
                $this->_szPluginName,
                $this->_szPluginName,
                'manage_options',
                $this->_szPluginDir . '-settings',
                array( $this, 'onPageManageSettings'),
                "none"
            );
            
            add_submenu_page( $this->_szPluginDir . '-settings', _v("Settings"), _v("Settings"), 'manage_options',
                $this->_szPluginDir . '-settings', array( $this,"onPageManageSettings") );

            $this->__initSubMenuPages();    
            
            add_submenu_page( $this->_szPluginDir . '-settings', _v("Documentation"), _v("Documentation"), 'manage_options',
                $this->_szPluginDir . '-documentation', array( $this,"onPageDocumentation") );

            add_submenu_page( $this->_szPluginDir . '-settings', _v("About us"), _v("About us"), 'manage_options',
                $this->_szPluginDir . '-about-us', array( $this,"onPageAboutUs") );            
        }
        
        public function initPlugin(){

            add_action( 'admin_init',
                array( $this, 'onAdminInitPlugin') );

            add_action( 'admin_menu',
                array( $this, 'onMenu' ) );

            add_filter( 'plugin_action_links_' . $this->_szPluginDir . '/'. $this->_szPluginDir . '.php',
                array( $this,'onAddActionLinks') );
        }

        public function onInstallDBData(){
            if(  !$this->__checkPluginVersion() ||
                $this->__checkDbData() ){
                return false;
            }

            $this->__installDBData();
        }

        protected function __installDBData(){
            $this->_oDB->insert(
                $this->_szTableSettings,
                array(
                    'time' => current_time( 'mysql' ),
                    'option_name' => 'destroy-db',
                    'option_value' => "0"
                )
            );
        }

        protected function __initAdminMenu(){
            array_push($this->_aAdminMenu, array(
                "label" => "documentation",
                "title" => _v("Documentation")
            ) );
            
            array_push($this->_aAdminMenu, array(
                "label" => "about-us",
                "title" => _v("About Us")
            ) );  
            
            array_push($this->_aAdminMenu, array(
                "label" => "ctl-plugins",
                "title" => _v("CTL Plugins")
            ) );   
        }
        
        protected function __printAdminMenu($szSection){
            ?>
            <h2 id="wsal-tabs" class="nav-tab-wrapper">
                <?php
                    foreach( $this->_aAdminMenu as $oLink ){
                        if( $oLink["label"] == "ctl-plugins"){
                            $target = "_blank";
                            $href = "https://codecanyon.net/collections/5964631-ctl-plugins/?ref=codethislab"; 
                        }else{
                            $target = "_self";
                            $href = $this->_szAdminPageUrl . $oLink["label"]; 
                        }                        
                        echo '<a target="'. $target .'" href="'. $href .'" class="nav-tab '. (strcmp($szSection, $oLink["label"]) == 0 ? "nav-tab-active" : "") .'">'. $oLink["title"] . '</a>';
                    }
                ?>
            </h2>
            <?php
        }        
        
        public function onInstallDBSchema(){                       
            $szInstalledVersion = get_option( $this->_szPluginDir . '-version' );

            if( ($szInstalledVersion != $this->_szVersion) ) {
                $this->__installDBSchema();
                add_option( $this->_szPluginDir . '-version', $this->_szVersion );
            }
        }

        protected function __installDBSchema(){
            $charset_collate = $this->_oDB->get_charset_collate();

            // create table settings
            $sql = "CREATE TABLE ". $this->_szTableSettings . " (
                id bigint(20) NOT NULL AUTO_INCREMENT,
                time datetime DEFAULT '0000-00-00 00:00:00' NOT NULL,
                option_name varchar(64) CHARACTER SET ascii NOT NULL,
                option_value text CHARACTER SET ascii NOT NULL,
                UNIQUE KEY id (id),
                UNIQUE KEY option_name (option_name)
                 ) $charset_collate;";
            $this->_oDB->query( $sql );
        }
        
        protected function __isTable( $szName ){
            if($this->_oDB->get_var("SHOW TABLES LIKE '$szName'") != $szName) {
                return false;
            }else{
                return true;
            }
        }

        protected function __initTableNames(){
            $this->_szTableSettings =
                $this->_oDB->prefix . $this->_szPluginPrefixTables . "settings";
            array_push($this->_aDBTables, $this->_szTableSettings);
        }

        public function getSettings( &$aSettings ){
            if( !$this->__isTable($this->_szTableSettings) ){
               return false; 
            }
            
            //$this->_oDB->hide_errors();
            $oRow = $this->_oDB->get_row( "SELECT * FROM " . $this->_szTableSettings . " WHERE option_name = 'destroy-db'");
            if($oRow){
                $aSettings["destroy-db"] = intVal($oRow->option_value);  
            } 
            
            return true;
        }

        public function destroyPlugin(){
            //if uninstall not called from WordPress exit
            if ( !defined( 'WP_UNINSTALL_PLUGIN' ) ){
                echo "end 1";
                exit();
            }

            $aSettings = array();
            $this->getSettings($aSettings);

            if( isset($aSettings["destroy-db"]) && $aSettings["destroy-db"] == 0 ){
                return;
            }

            $option_name =  $this->_szPluginDir . '-version';

            $installed_ver = get_option( $option_name );
            if( ($installed_ver == $this->_szVersion) ) {
                foreach( $this->_aDBTables as $szTableName){
                    $this->_oDB->query( "DROP TABLE IF EXISTS ". $szTableName );
                }
            }

            delete_option( $option_name );
            // For site options in multisite
            delete_site_option( $option_name );
        }

        public function onAdminInitPlugin(){
            $this->onIncludeAdminScriptsAndStyles();
            $this->onActivationRedirect();
        }

        public function getPluginUrl(){
            return plugins_url() . "/" .$this->_szPluginDir;
        } 
        
        protected function __registerStyle( $szCode, $szFile){
            wp_register_style( $this->_szPluginDir . '-' . $szCode, 
                               $this->getPluginUrl() .'/css/' . $szFile );    
            wp_enqueue_style( $this->_szPluginDir . '-' . $szCode );
        }
        protected function __registerScript( $szCode, $szFile, $aDependencies = array(), $oLocalization = null, $szLocalizationVar = null ){

            wp_register_script( $this->_szPluginDir . '-' . $szCode, 
                                $this->getPluginUrl() . '/js/' . $szFile,
                                    $aDependencies);
            wp_enqueue_script( $this->_szPluginDir . '-' . $szCode ); 
            
            
            if( $oLocalization != null ){
                wp_localize_script( $this->_szPluginDir . '-' . $szCode, $szLocalizationVar, $oLocalization );
            }
        }        
        
        public function onIncludeAdminScriptsAndStyles(){ 
            $this->__registerStyle('commons', "commons.css");

            $this->__registerScript('commons', 'commons.js');
        }        
        
        
        public function onActivationPlugin(){
            $this->onInstallDBSchema();
            $this->onInstallDBData();
            $this->onSetActivationRedirect();
        }

        public function getPluginDir(){
            return $this->_szPluginDir;
        }

        protected function __checkPluginVersion(){
            $szInstalledVer = get_option($this->_szPluginDir . '-version');
            if(!$szInstalledVer){
                return false;
            }

            $aVersions = explode(" ", $szInstalledVer);

            //-----------------------------------------------------------------
            // migliorare la funzione di verifica compatibilità
            if( intval($aVersions[0]) != intval($this->_szCompatibleVersion) ){
                return false;
            }
            //-----------------------------------------------------------------

            return true;
        }

        protected function __checkDbData(){
            $oRow = $this->_oDB->get_row( "SELECT * FROM " . $this->_szTableSettings);
            if( !$oRow ){
                return false;
            }
            return true;
        }

        
        public function onPageDocumentation(){
            ?>
            <div class="wrap <?php echo $this->_szPluginDir?>-documentation-container"> 
                <h1><?php _p("Documentation"); ?></h1>
                <?php
                $this->__printAdminMenu("documentation");
                $this->__printDocumentation();
                ?> 
            </div>
            <?php
            $this->__printFooter();
        }        
 
        protected function __printMessage( $szHighPriorityMessage = null, $bFormat = true ){
            $szMsg = filter_input(INPUT_GET, 'msg');

            if( $szHighPriorityMessage != null ){
                $szMsg = $szHighPriorityMessage;
            }
            
            if( $szMsg == ""){
                return;
            }
            
            $bError   = false;
            $oMessage = $this->__getMessage($szMsg);
            $iCode    = 0;
            
            if( $oMessage == null ){
                $szOutput = $szMsg;
            }else{
                $szOutput = $oMessage["msg"];
                $iCode    = $oMessage["code"];
            }
     
            echo "<br>";
            
            if( $bFormat == true ){
                $this->__formatMessage($szOutput, $iCode); 
            }else{
                echo "<p>". $szOutput ."</p>";
            }
        }
        
        
        protected function __formatMessage( $szMessage, $iCode = 0 ){
            switch($iCode){
                case 0:{
                    $szStyle = "updated";
                }break;
                case 1:{
                    $szStyle = "update-nag";
                }break;            
                case 2:{
                    $szStyle = "error";
                }break;            
            }
?>
            <div class="<?php echo $szStyle; ?> below-h2 notice is-dismissible">
                <p><?php echo $szMessage; ?></p>                 
                <button type="button" class="notice-dismiss"><span class="screen-reader-text"><?php _p("Hide this notice."); ?></span></button>
            </div>
<?php            
        }

        public function onPageAboutUs(){
            
            ?>
            <div class="wrap <?php echo $this->_szPluginDir?>-about-us-container"> 
            <h1><?php _p("About Us"); ?></h1>
            <?php
            $this->__printAdminMenu("about-us");
            ?>             
                <div id="fb-root"></div>
                <script>(function(d, s, id) {
                    var js, fjs = d.getElementsByTagName(s)[0];
                    if (d.getElementById(id)) return;
                    js = d.createElement(s); js.id = id;
                    js.src = "//connect.facebook.net/it_IT/sdk.js#xfbml=1&version=v2.5&appId=49655289489";
                    fjs.parentNode.insertBefore(js, fjs);
                }(document, 'script', 'facebook-jssdk'));</script>
                      
                <br>
                <img width="100%;" src="<?php echo $this->getPluginUrl(); ?>/images/ctl_banner.jpg">

                <p>We started as game developers then we broadened our horizons to explore new worlds.</p>
                <p>As the days went by, our working group got bigger, we acquired new skills and now we are a team able to deal with all that concern the digital world.</p>

                <p>We live in Naples, a city that spreads passion.</p>
                <p>The same passion that allows us creating new and original digital contents to meet our customers’ requirements tirelessly.</p>

                <p>Our “motto” is: <strong>If you can imagine it, we can create it for you</strong>.</p>
                <p>We are here to welcome your ideas to make them come true.</p>


                <div style="vertical-align: top; display: flex;">
                    <div style="max-width: 300px; width:100%; display: inline-block; margin-top: 15px; margin-right: 15px;">
                        <a class="twitter-timeline" href="https://twitter.com/CodeThisLab" data-widget-id="654309982813483009">Tweets by @CodeThisLab</a>
                        <script>!function(d,s,id){var js,fjs=d.getElementsByTagName(s)[0],p=/^http:/.test(d.location)?'http':'https';if(!d.getElementById(id)){js=d.createElement(s);js.id=id;js.src=p+"://platform.twitter.com/widgets.js";fjs.parentNode.insertBefore(js,fjs);}}(document,"script","twitter-wjs");</script>
                    </div>

                    <div style="max-width: 300px; width:100%; display: inline-block; margin-top: 15px;">
<div class="fb-page" data-href="https://www.facebook.com/codethislabsrl" data-tabs="timeline" data-small-header="true" data-adapt-container-width="true" data-hide-cover="false" data-show-facepile="true"><blockquote cite="https://www.facebook.com/codethislabsrl" class="fb-xfbml-parse-ignore"><a href="https://www.facebook.com/codethislabsrl">Code This Lab srl</a></blockquote></div>
                    </div>

                </div>
            </div>
            <?php
            $this->__printFooter();
        }      
        
        protected function __printDocumentation(){
            ?>
            <p><?php _p("Write something useful...") ?></p>
            <?php
        }
        
        protected function __printFooter( $szClass = "" ){
            ?>
            <div class="wrap">
                <div class="<?php echo $szClass; ?>" style="text-align: left; margin-top: 15px; font-size: 13px; line-height: 20px; color: #777; border-top: 1px solid #ccc; padding-top: 15px;">
                    <div>
                        <span>Copyright ©</span>
                        <span>Code This Lab srl 2009-<?php echo date("Y"); ?></span>
                    </div>
                    <div>
                        <span>VAT IT06367951214</span>
                        <span>REA NA810739</span>
                    </div>
                    <div>
                        <span>cap soc. €16'000,00 i.v.</span>
                    </div>
                    <div>
                        <a target="_blank" href="mailto:info@codethislab.com">info@codethislab.com</a>
                    </div>
                    <div>
                        <a target="_blank" href="http://www.codethislab.com">www.codethislab.com</a>
                    </div>
                </div>
            </div>
            <?php
        }


        public function onSetActivationRedirect(){
            add_option( $this->_szPluginDir . '_do_activation_redirect', true);
        }

        public function onActivationRedirect() {
            if (get_option( $this->_szPluginDir . '_do_activation_redirect', false)) {
                delete_option( $this->_szPluginDir . '_do_activation_redirect');

                if(!isset($_GET['activate-multi']) &&
                    is_admin() &&
                    is_plugin_active($this->_szPluginDir . "/" .
                        $this->_szPluginDir . ".php") &&
                    filter_input(INPUT_GET, 'action') != "deactivate") {
                    wp_redirect(admin_url() .
                        "admin.php?page=".$this->_szPluginDir."-settings" );
                }
            }
        }
    }
}