<?php
 /** 
  *@package Plugin Installer
 */
/*
Plugin Name: Plugin Installer
Description: Plugin Installer allows you to select a list of plugins to install.
Author: CmantikWeb - Dev. Carlos Rivas,  Dev Abdiangel Urdaneta
Author URI: https://cmantikweb.com/
Version: 1.0.0
License: GPLv2 or later
License URI: https://opensource.org/licenses/GPL-2.0
Text Domain: plugin-installer

*/

// Security check
if ( ! function_exists( 'add_action' ) ) {
    echo 'You don\'t have permission to access this file.';
    die;
  }

class PluginInstaller{

  private $api;
  private $plugin_folder;
  private $plugin_folder_local;
  private $local_args;
  private $local_plugins;
  private $my_directory;


  public function __construct(){
    $plugin = plugin_basename( __FILE__ );
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );

    //-------------------------------------------------------------------
    // GO TO LINE 332 FOR WORDPRESS REPOSITORIES PLUGIN DOWNLOAD/INSTALL.
    //-------------------------------------------------------------------

    //-------------------------------------------------------------------
    // LOCAL PLUGINS INSTALLATION (.ZIP FILES).
    //-------------------------------------------------------------------
    /* Use this variable below to describe the directory where your plugins live in your
    computer/server if you don't specify the root directory where the plugins are it will throw an error. 
    */

    $this->my_directory = '/opt/lampp/htdocs/wordpress/wp-content/plugins/'; //REPLACE THIS WITH THE ACTUAL DIRECTORY FOR YOUR LOCAL PLUGINS

    add_action( 'admin_menu', array( $this, 'plginstMenu' ));
    add_action( 'admin_enqueue_scripts',array( $this, 'enqueue_scripts' ));
    add_action( 'wp_ajax_takePlugins', array( $this, 'takePlugins' ));
    add_action( 'wp_ajax_extractLocalPlugins', array( $this, 'extractLocalPlugins' ));
    add_filter( "plugin_action_links_$plugin", array( $this, 'customSettingsLink' ));
  }
  
    // Main menu link
  public function customSettingsLink($links) {
    $link = (admin_url('/options-general.php?page=plugin-installer')); 
    $settings_link = sprintf('<a href="%s">' .(esc_html( 'Settings')) . '</a>', esc_url($link));
    array_push($links, $settings_link);
      return $links;
  }

  //Main Menu

  public function plginstMenu(){
    add_options_page( 'Plugin Installer', 
    'Plugin Installer', 
    'manage_options', 
    'plugin-installer', 
    array($this, 'plginstOptionsPage'));
  }

  public function plginstOptionsPage() {
    if (!current_user_can('manage_options')) {
      return;
    }
    ?>
  <div class="wrap">

    <h1>
      <?= esc_html(get_admin_page_title()); ?>
    </h1>
    
    <div class="pinst__first-panel">
      <h3>Plugins Instalados</h3>
      <div class="pinst__plugins">
        <ul>
          <li>PODS</li>
          <li>PODS</li>
          <li>PODS</li>
          <li>PODS</li>
          <li>PODS</li>
          <li>PODS</li>
          <li>PODS</li>
        </ul>
      </div>
    </div>
    <div class="pinst__second-panel">
      <h3>Instalar plugin desde repositorio WP</h3>

      <div class="pinst__input-group">
        <label class="pinst__label" for="url-request">From Url</label>
        <input type="text" id="url-request" class="pinst__input">
      </div>

      <h3>Or</h3>

      <div class="pinst__input-group">
        <label class="pinst__label-file button button-secondary" for="upload-plugin">Upload</label>
        <input class="pinst__input-file" type="file" hidden id="upload-plugin">
      </div>

      <button type="button" class="pinst__button button button-primary">Install Plugins</button>
      <a class="pinst__link" href="https://www.thinkdifferent.es/plugins-permitidos">Ver lista de plugins a solicitar instalación nueva</a>
    </div>

  </div> 
  <?php
  }


  

  public function takePlugins(){ 
    include_once( ABSPATH . 'wp-admin/includes/plugin-install.php' );
    $args = array(
      'path' => ABSPATH.'wp-content/plugins/',
      'preserve_zip' => false
    );

    $json = array(
      'msg' => array()
    );

    $dircontents = scandir($this->my_directory);

    $local_plugins_unpacked = null;
    $plugins_unpacked = null;

    $plugins = $_POST['plugins'];
    var_dump($plugins);
      
      /*Checking if the list of plugins is empty, if isn't empty
      execute the request to the API of wordpress.org*/

      if(!empty($plugins)){
        foreach($plugins as $plugin) {
            $this->api = plugins_api( 'plugin_information', array(
              'slug' => $plugin,
              'fields' => array(
                'downloadlink' => true,
                'slug' => true,
              ),
          ));

          // Try to download the plugin.
          if($this->api->slug == '') {

            $msg = 'The plugins\' slug array has an empty value, this will throw an error installing';
            array_push($json['msg'],$msg);

          } else {
            $download = $this->PluginDownload($this->api->download_link, $args['path'].$this->api->slug.'.zip');
          }

          
          /* Checking if the download process was successful or failed to
          continue the process, if the download failed, the process will stop*/
          
          if ($download){
            $unpack = $this->PluginUnpack($args, $args['path'].$this->api->slug.'.zip');
          }
          /* Checking if the unzip process was successful or failed to
          continue the process*/

          if ($unpack){

            $plugins_unpacked = 1;

          }
        }
      }		

      /*Checking if the list of plugins is empty, if isn't empty
      execute unzip process.*/
      if(!empty($dircontents)){

        foreach ($dircontents as $file) {

          $extension = pathinfo($file, PATHINFO_EXTENSION);

          if ($extension == 'zip') {

            $unpack_local = $this->PluginUnpack($this->local_args, $this->my_directory.$file);
          
            /* Checking if the unzip process was successful or failed to
            continue the process*/

            if($unpack_local){

              $local_plugins_unpacked = 1;

            } else {

              $msg = 'There was an error installing'.' '. $file .'.';
              array_push($json['msg'],$msg);

            }
          }
        }
      }	

      /* Checking if plugins coming from repositories and local were successfully unzipped to
      proceed Installation and Activation.*/

      if($local_plugins_unpacked === 1 || $plugins_unpacked === 1){
        $var = get_plugins();

      foreach($var as $key => $data) {
        $install_path = $args['path']. $key;

        $install = $this->PluginActivate($install_path);

        /* Checking if the install process was successful or failed to
        finish the process*/

        if($install == false) {

          $msg = $data['Name'].' '.'was successfully installed and activated.';
          array_push($json['msg'],$msg);

        } else {

          $msg = 'There was an error activating'.' '.$data['Name'] .'.';
          array_push($json['msg'],$msg);

        }
      }            
    }		
    
    wp_send_json($json);
    
    wp_die();        
  }

  public function uploadPlugins() {
    // You need to add server side validation and better error handling here

    $data = array();

    if(isset($_POST['files']))
    {  
        $error = false;
        $files = array();

        $uploaddir = $this->my_directory;
        foreach($_FILES as $file)
        {
            if(move_uploaded_file($file['tmp_name'], $uploaddir .basename($file['name'])))
            {
                $files[] = $uploaddir .$file['name'];
            }
            else
            {
                $error = true;
            }
        }
        $data = ($error) ? array('error' => 'There was an error uploading your files') : array('files' => $files);
    }
    else
    {
        $data = array('success' => 'Form was submitted', 'formData' => $_POST);
    }

    echo json_encode($data);
  }

  public function viewLocalPlugins(){

    // directory we want to scan
    $dircontents = scandir($this->my_directory);
    
    // list the contents
    echo '<ul>';
    foreach ($dircontents as $file) {
      $extension = pathinfo($file, PATHINFO_EXTENSION);
      if ($extension == 'zip') {
        echo "<li>$file </li>";
      }
    }
    echo '</ul>';
    return $this;
  }


  // Function to download the plugin.
  public function PluginDownload($url, $path){
      $ch = curl_init($url);
      curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
      $data = curl_exec($ch);
      curl_close($ch);
      if(file_put_contents($path, $data)){
        return true;
			}else{
        echo '<p> You have bad internet connection. Check your connection and try again. </p>';
			 return false;
			}
  }
  // Function to unzip the plugin.
  public function PluginUnpack($args, $filename){
    $zip = zip_open($filename); 
    if(is_resource($zip)){
      while($entry = zip_read($zip))
      {
        $file_check = substr(zip_entry_name($entry), -1) == '/' ? false : true;
        $file_path = $args['path'].zip_entry_name($entry);
        if($file_check){
          if(zip_entry_open($zip,$entry,"r")){
            $fstream = zip_entry_read($entry, zip_entry_filesize($entry));
            file_put_contents($file_path, $fstream );
            chmod($file_path, 0777);
          }
          zip_entry_close($entry);
        }
        else{
          $dir = $args['path'].zip_entry_name($entry);
          $check_dir = file_exists($dir) && is_dir($dir);
          if(!$check_dir){
              mkdir($file_path);
              chmod($file_path, 0777);
            }else{
              echo '<p> Plugin is already installed!!. </p>';
              break;
            }
          }
        }
      zip_close($zip);
      }else{
        echo '<p>You have provided a wrong file path. Check the filepath and try again.<p><br>';
      }
    if($args['preserve_zip'] === false){
      unlink($filename);
    }
    if(!$check_dir){
      return true;
    }else{
      return false;
    }
  }
  //Function to install the plugin.
  public function PluginActivate($install_path){
		include_once( ABSPATH . 'wp-admin/includes/plugin.php' );
    $get_current = get_option('active_plugins');
    $plugin = plugin_basename(trim($install_path));

    if(!in_array($plugin, $get_current)){
      $get_current[] = $plugin;
      sort($get_current);
      do_action('activate_plugin', trim($plugin));
      update_option('active_plugins', $get_current);
      do_action('activate_'.trim($plugin));
      do_action('activated_plugin', trim($plugin));
      return true;
    }
    else
    	return false;
  }
  
  public function enqueue_scripts() {
    wp_enqueue_script(
      'ajax-script',
      plugin_dir_url( __FILE__ ) . 'assets/installer.js',
      array( 'jquery' )
    );

    wp_localize_script(
      'ajax-script', 'ajax_object', array(
        /* Use this array to determinate the plugins that will be downloaded,
        uncomment the plugins array and, innsert the plugin's slug in the array to 
        determine which plugins will be installed.
        USE THE PLUGIN'S SLUGS TO FILL THE ARRAY BELOW. 
        Example:
        ------
        'plugins' => array(
          'wordpress-seo','jetpack','uk-cookie-consent'
        )
        ------ */
        'plugins' => array(
          'jetpack','uk-cookie-consent'
        ) 
      )
    );

    wp_enqueue_style( 'plugin-installer', plugin_dir_url( __FILE__ ) . 'assets/installer.css');
  }
}

// new PluginInstaller();