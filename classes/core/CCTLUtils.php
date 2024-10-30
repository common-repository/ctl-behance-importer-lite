<?php
namespace CTL_BEHANCE_IMPORTER_LITE {    
    if ( ! defined( 'ABSPATH' ) ){ exit; } // Exit if accessed directly
    
    use \RecursiveDirectoryIterator;    
    use \RecursiveIteratorIterator;
    
    class CCTLUtils
    {
        protected function __construct() {
            /*
            error_reporting(E_ALL); 
            ini_set('display_errors', 1);
            */
        }

        protected function __checkRecursiveFileExists($filename, $directory){

            try {
                // loop through the files in directory
                foreach(new RecursiveIteratorIterator( new RecursiveDirectoryIterator($directory)) as $file) {
                    // if the file is found
                    if( $filename == basename($file) ) {
                        return true;
                    }
                }
                // if the file is not found
                return false;
            } catch(Exception $e) {
                // if the directory does not exist or the directory
                // or a sub directory does not have sufficent
                //permissions return false
                var_dump($e);
                wp_die();
            }

        }

        protected function __getFileNameFromUrl($szUrlFile ){
            $path = parse_url($szUrlFile, PHP_URL_PATH);
            return basename($path);
        }

        protected function __getImageUrlFromMediaGalleryByName($filename){
            $posts = $this->_oDB->posts;
            $attachment = $this->_oDB->get_results("SELECT ID FROM $posts WHERE guid LIKE '%$filename%';"  );

            if($attachment){
                return  wp_get_attachment_url($attachment[0]->ID);
            }else{
                return "";
            }
        }

        protected function __copyFileFromUrl( $szUrlFile, $post_data = null ){
            $upload_dir = wp_upload_dir();

            $path = parse_url($szUrlFile, PHP_URL_PATH);
            $szFileName = basename($path);

            if ( !$this->__checkRecursiveFileExists($szFileName, $upload_dir["basedir"]) ){                
                $this->__somaticAttachExternalImage($szUrlFile,0, null, null, $post_data);
                return true;
            }else{
                // exist in media library an image with same name
                return true;
            }
        }

        protected function __somaticAttachExternalImage(
            $url = null, $post_id = null, $thumb = null,
            $filename = null, $post_data = array() ) {
            if ( !$url ) return new WP_Error('missing', "Need a valid URL and post ID...");
            require_once( ABSPATH . 'wp-admin/includes/file.php' );
            // Download file to temp location, returns full server path to temp file, ex; /home/user/public_html/mysite/wp-content/26192277_640.tmp
            $tmp = download_url( $url );

            // If error storing temporarily, unlink
            if ( is_wp_error( $tmp ) ) {
                @unlink($file_array['tmp_name']);   // clean up
                $file_array['tmp_name'] = '';
                return $tmp; // output wp_error
            }

            preg_match('/[^\?]+\.(jpg|JPG|jpe|JPE|jpeg|JPEG|gif|GIF|png|PNG)/', $url, $matches);    // fix file filename for query strings
            $url_filename = basename($matches[0]);                                                  // extract filename from url for title
            $url_type = wp_check_filetype($url_filename);                                           // determine file type (ext and mime/type)

            // override filename if given, reconstruct server path
            if ( !empty( $filename ) ) {

                $filename = sanitize_file_name($filename);
                $tmppath = pathinfo( $tmp );                                                        // extract path parts
                $new = $tmppath['dirname'] . "/". $filename . "." . $tmppath['extension'];          // build new path
                rename($tmp, $new);                                                                 // renames temp file on server
                $tmp = $new;                                                                        // push new filename (in path) to be used in file array later
            }

            // assemble file data (should be built like $_FILES since wp_handle_sideload() will be using)
            $file_array['tmp_name'] = $tmp;                                                         // full server path to temp file

            if ( !empty( $filename ) ) {
                $file_array['name'] = $filename . "." . $url_type['ext'];                           // user given filename for title, add original URL extension
            } else {
                $file_array['name'] = $url_filename;                                                // just use original URL filename
            }

            // set additional wp_posts columns
            if ( empty( $post_data['post_title'] ) ) {
                $post_data['post_title'] = basename($url_filename, "." . $url_type['ext']);         // just use the original filename (no extension)
            }

            // make sure gets tied to parent
            if ( empty( $post_data['post_parent'] ) ) {
                $post_data['post_parent'] = $post_id;
            }

            // required libraries for media_handle_sideload
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/media.php');
            require_once(ABSPATH . 'wp-admin/includes/image.php');

            // do the validation and storage stuff
            $att_id = media_handle_sideload( $file_array, $post_id, null, $post_data );             // $post_data can override the items saved to wp_posts table, like post_mime_type, guid, post_parent, post_title, post_content, post_status

            // If error storing permanently, unlink
            if ( is_wp_error($att_id) ) {
                @unlink($file_array['tmp_name']);   // clean up
                return $att_id; // output wp_error
            }

            // set as post thumbnail if desired
            if ($thumb) {
                set_post_thumbnail($post_id, $att_id);
            }

            return $att_id;
        }

        protected function __sendToHost( $host, $method='GET', $path='/', $data='', $useragent=0){

            $method = strtoupper($method);

            $fp = fsockopen($host, 80) or die("Unable to open socket");

            fputs($fp, "$method $path HTTP/1.1\r\n");
            fputs($fp, "Host: $host\r\n");
            fputs($fp, "Content-type: application/x-www-form-urlencoded\r\n");

            if ($method == 'POST') {
                fputs($fp, "Content-length: " . strlen($data) . "\r\n");
            }

            if ($useragent) {
                fputs($fp, "User-Agent: MSIE\r\n");
            }

            fputs($fp, "Connection: close\r\n\r\n");

            if ($method == 'POST') {
                fputs($fp, $data);
            }

            $buf ="";

            while (!feof($fp)){
                $buf .= fgets($fp,128);
            }

            fclose($fp);

            return $buf;
        }

        protected function __stripResponseHeader($source) {
            list($header, $body) = explode("\r\n\r\n", $source, 2);

            if (preg_match('/Transfer\\-Encoding:\\s+chunked\\r\\n/',$header)){
                $aLines = explode("\r\n", $body);
                $response = $aLines[1];
            }else {
                $response = $body;
            }

            return $response;
        }
    }
}