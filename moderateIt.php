<?php
/*
	Plugin Name: ModerateIt
	Plugin Script: moderateIt.php
	Plugin URI: https://moderate-it.net/en/
    Donate link: https://moderate-it.net/en/donate.php
	Description: Maintaining a culture of online communication in the hands of the users themselves. 
	Version: 1.0.0
    Text Domain: moderateIt
    Domain Path: /lang
	Author: ModerateIt
	Author URI: https://moderate-it.net/en/
	License:     GPL2 or later
	License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

/*
	Copyright 2018 - 2019  Also      (email: also@dvo.ru)
	This program is free software; you can redistribute it and/or modify
	it under the terms of the GNU General Public License as published by
	the Free Software Foundation; either version 2 of the License, or
	(at your option) any later version.

	This program is distributed in the hope that it will be useful,
	but WITHOUT ANY WARRANTY; without even the implied warranty of
	MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
	GNU General Public License for more details.
*/

if ( ! class_exists( 'moderateIt' ) ) {

class moderateIt {
	private $_plugin_version = '1.0.0';
	private $_plugin_webservice_url = 'https://moderate-it.net/api/';
	//private $_plugin_webservice_url = 'http://localhost/mit/api/';
	private $_plugin_site_url = 'https://moderate-it.net';
	
	private $_plugin_default_options = array(
		"mit_option_pre_anonymous" => 1,
		
		"mit_option_pre_net_violator" => 1,
		"mit_option_pre_suspect" => 1,
		"mit_option_post_anonymous" => 1,
		"mit_option_post_authorized" => 1,
		"mit_option_log_enable" => 0,
		"mit_option_log" => '',		
		"mit_option_user_token" => array(),				
		"mit_option_new_comment_id" => 0,
		"mit_option_api_key" => "public_key"
	);

	/*	 constructor */
	public function __construct() {
		

		add_action( 'plugins_loaded', array( $this, 'load_language' ) );
		
		if ( ! is_admin() || ( defined( 'DOING_AJAX' ) && true === DOING_AJAX ) ) {
			add_action( 'init', array( $this, 'frontend_init' ) );
		} else if ( is_admin() ) {
			add_action( 'admin_menu', array($this, 'mit_add_options_menu') );
			add_action( 'admin_init', array($this, 'mit_settings_init') );
		}
		
		register_deactivation_hook( __FILE__,  array( $this, 'mit_plugin_deactivate' ) );
		
		//register rest api function 
		add_action( 'rest_api_init', function () {
			register_rest_route( 'mit/v1', '/log/', array(
			  'methods' => 'GET',
			  'callback' => array($this, 'mit_log_call'),
			) );
		},20 );

    	//register rest api function 
		add_action( 'rest_api_init', function () {
			register_rest_route( 'mit/v1', '/receive/', array(
			  'methods' => 'POST',
			  'callback' => array($this, 'mit_receive_call'),
			) );
		},20 );
	}
	/* * */
	function mit_plugin_deactivate(){
	    $user_token_list=$this->get_user_tokens();
		$this->unset_webservice_checks($user_token_list);
	    delete_option('mit_settings');	
		return ;	
	}
	
	/* * */
	function find_key_user_token( $user_token ) {
		 $options = get_option( 'mit_settings' );		
         if (!isset($options['mit_option_user_token'])) return false;
		 if (count($options['mit_option_user_token'])==0) return false;
		 $s=$options['mit_option_user_token'];
		 $key = array_keys(array_filter($s, function($a){return ($a[0] == $user_token);}));
		 if (!isset($key[0]))return false;
		 return $key[0];
	}
	
	/* * */
	function get_user_tokens() {
		 $ret=array();
		 $options = get_option( 'mit_settings' );		
         if (!isset($options['mit_option_user_token'])) return $ret;
		 if (count($options['mit_option_user_token'])==0) return $ret;
		 foreach ($options['mit_option_user_token'] as $key => $value){
				 array_push($ret,$value[0]);
		 }
		 return $ret;
	}
	
	/* * */
	function set_user_token($user_token) {
		 if (!isset($user_token)) return false;
		 if ($user_token=='') return false;
		 $options = get_option( 'mit_settings' );		
	     if (!isset( $options['mit_option_user_token']))$options['mit_option_user_token']=array();
		 array_push($options['mit_option_user_token'],array($user_token,strtotime('now')));
  		 update_option('mit_settings', $options);
		return true;	
	}
	
	/* * */
	function del_user_token($user_token_key) {
		 if (!isset($user_token_key)) return false;
		 $options = get_option( 'mit_settings' );		
	     if (!isset( $options['mit_option_user_token']))return false;
		 if (count($options['mit_option_user_token'])==0) return false;
  	     unset($options['mit_option_user_token'][$user_token_key]);
  		 update_option('mit_settings', $options);
		 return true;	
	}
	
	/* * */
	function unset_old_user_tokens() {
		 $options = get_option( 'mit_settings' );		
         if (!isset($options['mit_option_user_token'])) return false;
		 if (count($options['mit_option_user_token'])==0) return false;
		 $on_check_arr=array();
		 $unset_arr=array();

		 foreach ($options['mit_option_user_token'] as $key => $value){
			 if ($value[1]>strtotime('now')- 10*60*60)//not more 10 hours for each check
				 array_push($on_check_arr,$value);
			 else 
				 array_push($unset_arr,$value[0]);
		 }
		 $options['mit_option_user_token']=$on_check_arr;
   		 update_option('mit_settings', $options);
		 $this->unset_webservice_checks($unset_arr);
		 return true;	
	}
	
	/* * */
	function unset_webservice_checks($user_token_list) {	
		if (isset($user_token_list)&&(count($user_token_list)>0))
		{
			$url=$this->_plugin_webservice_url.'unset_check.php';
			$list=json_encode($user_token_list);
			$response = wp_remote_get( "$url?list=$list");
		}
		return ;
	}
	
	/* * */
	function mit_log_call( WP_REST_Request $request ) {
		if (null === $request->get_param( 'msg' ))return ;
		$msg = strip_tags(sanitize_text_field($request->get_param( 'msg' )));
		$this->mit_log("\r\n",' USER EVENT: '.$msg);
		return 'mit_log_call';
	}
	
	/* * */
	function mit_receive_call( WP_REST_Request $request ) {
		if (null === $request->get_param( 'json' ))return ;
		
		$this->mit_log("",'SITE EVENT: RECIVED RESULT FROM M-NET');		
		$val = $request->get_param( 'json' );
		$val = (object) json_decode($val);
	    if (!isset($val->comment->user_token))	
		{
			$this->mit_log("",'PROCESS RESULT: empty token!');return ;	
		}
        $user_token_key=$this->find_key_user_token($val->comment->user_token);
		if (!key)
		{
			$m="PROCESS RESULT cannot change status of received from M-net comment with id:".$val->comment->id."( because he not find in list of comments sended to M-NET )!";
			$m=$m.'Note: Uninstalling the plugin clears all plugin settings, including list of comments sended to M-NET!';
			$this->mit_log("",$m);	
			return ;	
		}
		
		$_id=sanitize_text_field($val->comment->id);
		$_rule_text=sanitize_text_field($val->comment->rule_text);  
		$_topic=sanitize_text_field($val->comment->topic);
		$_content=sanitize_text_field($val->comment->content);
		$_autor_email=trim(sanitize_text_field($val->comment->autor_email));
		if ($_autor_email=='')$_autor_email='anonymous';
		
		if (strrpos ($val->status,'B')!==false)
		{
			 wp_spam_comment( $val->comment->id);
			 $e=array();
			 array_push($e,"SET RESULT: Set status to SPAM" );  	
			 array_push($e,"RULE: $_rule_text");  	
			 array_push($e,"TOPIC: $_topic" );  	
			 array_push($e,"CONTENT: $_content" );  	
			 array_push($e,"AUTOR: $_autor_email" );  	
			 array_push($e,"ID: $_id" );  	
			 $this->mit_log("",implode("\r\n",$e));				 
		}
		
		if (strrpos ($val->status,'G')!==false)
		{
			 wp_set_comment_status( $val->comment->id, 'approve' );
			 $e=array();
			 array_push($e,"SET RESULT: Set status to APPROVED" );  	
			 array_push($e,"RULE: $_rule_text");  	
			 array_push($e,"TOPIC: $_topic" );  	
			 array_push($e,"CONTENT: $_content" );  	
			 array_push($e,"AUTOR: $_autor_email" );  	
			 array_push($e,"ID: $_id" );  	
			 $this->mit_log("",implode("\r\n",$e));	
		}
		
		$this->del_user_token($user_token_key);
		$this->mit_log("",'PROCESS RESULT FINISH');
		$this->unset_old_user_tokens();
		return 'mit_receive_call';
	}
	 
	/* * */
	function mit_log($line_feed,$msg)	{
	   $options = get_option( 'mit_settings' );
	   if (isset($options['mit_option_log_enable'])&&$options['mit_option_log_enable']==1)
	   {
		$dd = date("Y-m-d H:i:s");    
		$options['mit_option_log']=$line_feed."$dd ".$msg."\r\n". $options['mit_option_log']; 
		update_option('mit_settings', $options);
	   }
	}
	
	/*	 destructor	 */
	public function __destruct() {
	}

	/* Load Language files for frontend and backend. */
	public function load_language() {
		$ret=load_plugin_textdomain( 'moderateIt', false, dirname(plugin_basename(__FILE__)) . '/lang/' );
	}
/////////////////////////////////////////////////////////////////////////////////////////////////////////////
 ///FRONTEND FUNCTIONS SECTION
 /////////////////////////////////////////////////////////////////////////////////////////////////////////////
 	
	/* Initialize frontend functions */
	public function frontend_init() {
		do_action('mit_frontend_init' );
		add_action( 'wp_head', array( $this, 'mit_enqueue_scripts' ) );
		add_action( 'wp_ajax_nopriv_mit_get_comment_data', array( $this, 'mit_get_comment_data' ) );
		add_action( 'wp_ajax_mit_get_comment_data', array( $this, 'mit_get_comment_data' ) );
		add_filter( 'comment_form_fields', array( $this, 'mit_filter_comment_control' ));
		add_filter( 'comment_reply_link', array( $this, 'add_mit_link' ) );
		add_action( 'comment_post', array( $this, 'mit_after_new_comment_add' )  );
	}
	
    /* * */
	public function add_mit_link( $comment_link ) {
		if ( !preg_match_all( '#^(.*)(<a.+class=["|\']comment-(reply|login)-link["|\'][^>]+>)(.+)(</a>)(.*)$#msiU', $comment_link, $matches ) ) {
			return  $comment_link;
		}
		$options = get_option( 'mit_settings' );
		if ($options['mit_option_post_anonymous']==1&& !is_user_logged_in()){
		 return  $matches[1][0] . $matches[2][0] . $matches[4][0] . $matches[5][0] .  $this->get_mit_link() .  $matches[6][0];
		}
		
		if ($options['mit_option_post_authorized']==1&& is_user_logged_in() ) {
  		 return  $matches[1][0] . $matches[2][0] . $matches[4][0] . $matches[5][0] .  $this->get_mit_link() .  $matches[6][0];
		}
		return  $comment_link;
	}
	
	/* * */
	public function get_mit_link( ) {
		global $in_comment_loop;
		if (!$in_comment_loop) 	return '';
		$comment_id = get_comment_ID();
		$comment = get_comment($comment_id);
		if ( ! $comment ) 	return '';
		return '<span style="padding-left:24px;"><small><a href="#" data-mit_comment_id='.$comment_id.' rel="nofollow"></a></small></span>' ;	
	}
	
	
    /* * */
	public function mit_after_new_comment_add( $comment_ID) {
		$options = get_option( 'mit_settings' );
		$options['mit_option_new_comment_id']=$comment_ID;
		update_option('mit_settings', $options);
	}
	
	/* * */
	public function mit_filter_comment_control($fields) {
		$new_comment_id_attr='';
		$options = get_option( 'mit_settings' );
		$newID=0;
		if (isset($options['mit_option_new_comment_id'])&& $options['mit_option_new_comment_id']!=0) 
		{
			  $newID=$options['mit_option_new_comment_id'];
			  $new_comment_id_attr=' data-mit_new_comment_id='.$newID;
			  $options['mit_option_new_comment_id']=0;
    		  update_option('mit_settings', $options);
		}
		
		$moderate_enable_field=str_replace( 'id="comment"', '"id="comment" class="mit-comment-hint mit-comment-input" readonly '.$new_comment_id_attr,$fields['comment']);
		$moderate_disable_field=str_replace( 'id="comment"','"id="comment" class="mit-comment-hint" '.$new_comment_id_attr ,$fields['comment']);
		
		$user = wp_get_current_user();
		$user_email=$user->user_email; 
		$user_id=$user->ID; 
		
		if ($options['mit_option_pre_anonymous']==1&&!$user->exists() )
		{
			$fields['comment']=$moderate_enable_field;
			return $fields;
		}	
		
     
			
		if (($options['mit_option_pre_net_violator']==1||$options['mit_option_pre_suspect']==1) && $user->exists())
		{		
			if($options['mit_option_api_key']==$this->_plugin_default_options['mit_option_api_key'])
			{
				if ($newID==0)$this->mit_log("\r\n","SITE EVENT: check users ($user_email):"
						. " statistics not available for public key."
						. "Please register personal key for the site."
						. " Pre-moderation is DISABLE for the authorized user." );	
				$fields['comment']=$moderate_disable_field;		
				return $fields;
			}
			
			$url=$this->_plugin_webservice_url.'check_user.php';
			$api_key=urlencode($options['mit_option_api_key']);
			$response = wp_remote_get( "$url?user_email=$user_email&api_key=$api_key");

   		   if(!is_wp_error( $response )&& wp_remote_retrieve_response_code( $response ) === 200 )
		   {
				$body = wp_remote_retrieve_body( $response );
				$net=json_decode($body);
				$message='';
				if (isset($net->message))$message=$net->message;
				

				if ($message=='U'&&$options['mit_option_pre_suspect'])
				{
					
					global $wpdb;
					$local = $wpdb->get_results( "SELECT count(*) as cnt FROM wp_comments WHERE user_id =$user_id "
							. " and ( comment_approved like '1') ");
					if ($local[0]->cnt>0)
					{
						if ($newID==0)$this->mit_log("\r\n","SITE EVENT: check users ($user_email):"
								. " The user is unknown in the Network and has an approved comment on the site."
								. " Pre-moderation for such users is DISABLE.");	
						$fields['comment']=$moderate_disable_field;
						return $fields;
					}
					
					if ($newID==0)$this->mit_log("\r\n","SITE EVENT: check users ($user_email):"
								. " The user is unknown in the Network and does not have an approved comment on the site."
								. " Pre-moderation for such users is ENABLE.");	
					$fields['comment']=$moderate_enable_field;
					return $fields;
				}
				
				if ($message=='B'&&$options['mit_option_pre_net_violator']==1)
				{
					if ($newID==0)$this->mit_log("\r\n","SITE EVENT: check users ($user_email): "
							. "the user violated in the Network last time. "
							. "Pre-moderation for such users is ENABLE." );	
					$fields['comment']=$moderate_enable_field;
				    return $fields;
				}
			    if ($message=='G'&&$options['mit_option_pre_net_violator']==1)
				{
					if ($newID==0)$this->mit_log("\r\n","SITE EVENT: check users ($user_email): "
							. "the user not violated in the Network last time. "
							. "Pre-moderation for such users is DISABLE." );	
					$fields['comment']=$moderate_disable_field;
				    return $fields;
				}

				if ($message!='U'&&$message!='B'&&$message!='G')
				{
					if ($newID==0)$this->mit_log("\r\n","SITE EVENT: cannot get statistic for check users ($user_email):"
					. " the details: " .$message. " Pre-moderation is DISABLE.");	
				}
				$fields['comment']=$moderate_disable_field;
				return $fields;
			}
				
		}
		
	$fields['comment']=$moderate_disable_field;		
	return $fields;
	}
	 
 
	/* * */
	public function mit_enqueue_scripts() {
		$options = get_option( 'mit_settings' );
		$mit_option_api_key=$options['mit_option_api_key'];
		if (!isset($options['mit_option_api_key']))
		{
				$this->mit_log("\r\n","SITE EVENT: api_key not install !!");	
				return;
		}

		$lang =__( 'en', 'moderateIt' );
		$button_caption =__('violates?', 'moderateIt' );
		
		wp_enqueue_style( 'mit-bootstrap-css', plugins_url( false, __FILE__ ) ."/css/bootstrap.min.css" );
		wp_enqueue_script('mit-bootstrap-js', plugins_url( false, __FILE__ ) ."/js/bootstrap.min.js", array('jquery'), $this->_plugin_version, true );
		wp_enqueue_script('mit-sc', plugins_url( false, __FILE__ ) . '/js/moderateIt.js', array( 'jquery' ), $this->_plugin_version, true );
		wp_add_inline_script( 'mit-sc', "var _mIt_Api_Key='$mit_option_api_key';", 'before' );
		wp_add_inline_script( 'mit-sc', "var _mIt_Lang='$lang'; ", 'before' );
		wp_add_inline_script( 'mit-sc', "var _mIt_Button_Caption='$button_caption'; ", 'before' );
		wp_add_inline_script( 'mit-sc', "var _mIt_WebServiceUrl='".$this->_plugin_webservice_url."';", 'before' );
		wp_add_inline_script( 'mit-sc', "var _mIt_Log_Url='".rest_url( '/mit/v1/log')."'", 'before' );
		wp_add_inline_script( 'mit-sc', "var _mIt_Ret_Url='".rest_url( '/mit/v1/receive' )."'", 'before' );
		wp_add_inline_script( 'mit-sc', "window.mIt_Get_Comment_Data =function(comment_id,user_token){ var t= jQuery.getJSON('".admin_url( 'admin-ajax.php' )."',{comment_id:comment_id,user_token:user_token, action: 'mit_get_comment_data'},  function (data){  return mIt_Set_Check(JSON.stringify(data)); }); }", 'before' );
	}

	/* * */
	public function mit_get_comment_data() {
		$in_comment_id=intval(sanitize_text_field($_GET['comment_id' ]));
		$in_user_token=sanitize_text_field ($_GET[ 'user_token' ]);
		$c = get_comment( $in_comment_id );		
		if ( empty( $c )){
		   echo json_encode(array());
		   wp_die();
		   return;
		}
		
		$email='';
		$this->set_user_token($in_user_token);
		$post = get_post($c->comment_post_ID); 
		$time=strtotime($c->comment_date);
		if ($c->user_id!=0) $email=$c->comment_author_email;
		echo json_encode(array('id'=>$c->comment_ID,'topic'=>$post->post_title,'content'=>$c->comment_content ,'autor_email'=>$email, 'time'=>$time));
		
		wp_die();
		return;
	}

 /////////////////////////////////////////////////////////////////////////////////////////////////////////////
 ///BACKEND FUNCTIONS SECTION
 /////////////////////////////////////////////////////////////////////////////////////////////////////////////
	/* * */	
	 public function mit_add_options_menu( ) {
		add_options_page( __( 'Network of Moderators', 'moderateIt' ), __( 'Network of Moderators', 'moderateIt' ), 'manage_options', 'mit-settings-page', array( $this,'mit_settings_page') );
	}
	
	/* * */	
	public function mit_settings_init( ) {

		register_setting( 'mitGroup', 'mit_settings', array( $this, 'mit_settings_save' ));
		$options = get_option('mit_settings');
		if(!$options) update_option('mit_settings', $this->_plugin_default_options);
		
		add_settings_section(
			'mitGroup_section_description',
			__( 'Capabilities', 'moderateIt' ),
			array( $this,'mitGroup_section_description_callback'),
			'mitGroup'
		);
		add_settings_field(
			'mit_option_description',
			'',
			array( $this,'mit_option_description_render'),
			'mitGroup',
			'mitGroup_section_description'
		);
		
		
		
		add_settings_section(
			'mitGroup_section_key',
			__( 'Api-key to connect to the network', 'moderateIt' ),
			array( $this,'mitGroup_section_key_callback'),
			'mitGroup'
		);
		add_settings_field(
			'mit_option_api_key',
			'',
			array( $this,'mit_option_api_key_render'),
			'mitGroup',
			'mitGroup_section_key'
		);
		
		add_settings_section(
			'mitGroup_section_area',
			__( 'Application area', 'moderateIt' ),
			array( $this,'mitGroup_section_area_callback'),
			'mitGroup'
		);
		add_settings_field(
			'mit_option_pre_anonymous',
			'',
			array( $this,'mit_option_pre_anonymous_render'),
			'mitGroup',
			'mitGroup_section_area'
		);
		
		
		
		add_settings_field(
			'mit_option_pre_net_violator',
			'',
			array( $this,'mit_option_pre_net_violator_render'),
			'mitGroup',
			'mitGroup_section_area'
		);
		
		add_settings_field(
			'mit_option_pre_suspect',
			'',
			array( $this,'mit_option_pre_suspect_render'),
			'mitGroup',
			'mitGroup_section_area'
		);
		

		
		
		add_settings_field(
			'mit_option_post_anonymous',
			'',
			array( $this,'mit_option_post_anonymous_render'),
			'mitGroup',
			'mitGroup_section_area'
		);
		
		add_settings_field(
			'mit_option_post_authorized',
			'',
			array( $this,'mit_option_post_authorized_render'),
			'mitGroup',
			'mitGroup_section_area'
		);
		

		add_settings_section(
			'mitGroup_section_log',
			__( 'Event logging', 'moderateIt' ),
			array( $this,'mitGroup_section_log_callback'),
			'mitGroup'
		);  
		add_settings_field(
			'mit_option_log_enable',
			'',
			array( $this,'mit_option_log_enable_render'),
			'mitGroup',
			'mitGroup_section_log'
		);
		add_settings_field(
			'mit_option_log',
			'',
			array( $this,'mit_option_log_render'),
			'mitGroup',
			'mitGroup_section_log'
		);
	}

	/* * */	
	public function mit_option_description_render( ) {
        _e( 'With the Network you can create an acceptable online communication culture in your community:<br>', 'moderateIt' );     
		_e( '- Anonymous users comment with pre-moderation.<br>', 'moderateIt' );     
		_e( '- Authorized users, if they do not violate, can comment without pre-moderation.<br>', 'moderateIt' );     
		_e( '- Readers can detect violations using post-moderation.<br>', 'moderateIt' );     
		_e( '- A quick introduction for all to the rules of online communication.', 'moderateIt' );     
		
	}
	
	
	/* * */	
	public function mit_option_api_key_render( ) {
		$options = get_option( 'mit_settings' );
		$lang =__( 'en', 'moderateIt' );
		$url=$this->_plugin_site_url."/$lang/cab.php"
		?>
		<input type='text' style='width:24rem;'  name='mit_settings[mit_option_api_key]' value='<?php echo $options['mit_option_api_key']; ?>'>
		<div><?php _e('Note: Please register free personal key for your site to use all сabilities of moderation<br> and read sections Terms of use and Questions about the Network.', 'moderateIt' );?> 
		<a target="blank" href="<?php echo $url; ?>"><?php _e( 'See more here.', 'moderateIt' );?></a></div>
		<?php
	}

	/* * */	
	public function mit_option_pre_anonymous_render( ) {
		$options = get_option( 'mit_settings' );
		 ?>
		 <p>	<?php
		   _e( 'Enable pre-moderation of a new comment:', 'moderateIt' );
		  ?></p>
		 <br>
		 <input   name='mit_settings[mit_option_pre_anonymous]'  value='1' type="checkbox" <?php checked( $options['mit_option_pre_anonymous'], 1 ); ?> />
		 <?php 
			 _e( ' from an anonymous user always.', 'moderateIt' );
			 ?>
		<?php
	}

	
	/* * */	
	public function mit_option_pre_net_violator_render( ) {
		$options = get_option( 'mit_settings' );
		$disabled='';
		if  ($options['mit_option_api_key']==$this->_plugin_default_options['mit_option_api_key'])
		 $disabled='disabled';

		_e('(Next options will work only with a free personal key for your site)', 'moderateIt' );			
		
		?>
         <br> <br>  
		<input <?php echo $disabled; ?> name='mit_settings[mit_option_pre_net_violator]' type="checkbox" value="1" <?php checked( $options['mit_option_pre_net_violator'], 1 ); ?> />
		<?php
			_e(' from an authorized user, for each case of violation of the Network rules,<br> '
			 . 'detected using pre- or post-moderation via the Network on any site connected to the Network.', 'moderateIt' );
		   ?>
	   <?php
	}
	
		/* * */	
	public function mit_option_pre_suspect_render( ) {
		$options = get_option( 'mit_settings' );
		$disabled='';
		if  ($options['mit_option_api_key']==$this->_plugin_default_options['mit_option_api_key'])
		 $disabled='disabled';
		?>
		<input <?php echo $disabled; ?> name='mit_settings[mit_option_pre_suspect]' type="checkbox" value="1" <?php checked( $options['mit_option_pre_suspect'], 1 ); ?> />
		<?php
			_e(' from an authorized user, if he does not have approved comment on the Network or on this Site.', 'moderateIt' );
	   ?>
	   <?php
	}

	
	
	
	/* * */	
	public function mit_option_post_anonymous_render( ) {
		$options = get_option( 'mit_settings' );
		 ?>
		 <br>
		 <p>	
		 <?php
		   _e( 'Allow  post-moderation of comments for:', 'moderateIt' );
		  ?>
		 </p>
		 <br>
		 <input name='mit_settings[mit_option_post_anonymous]'  value='1' type="checkbox" <?php checked( $options['mit_option_post_anonymous'], 1 ); ?> />
		 <?php 
			 _e( ' any readers.', 'moderateIt' );
			 ?>
		<?php
	}
	
	
	/* * */	
	public function mit_option_post_authorized_render( ) {
		$options = get_option( 'mit_settings' );
		 ?>
		 <input name='mit_settings[mit_option_post_authorized]'  value='1' type="checkbox" <?php checked( $options['mit_option_post_authorized'], 1 ); ?> />
		 <?php 
		_e( ' an authorized readers.', 'moderateIt' );
	}


	/* * */	
	public function mit_option_log_enable_render( ) {
		$options = get_option( 'mit_settings' );
		 ?>
		 
		  <input name='mit_settings[mit_option_log_enable]' type="checkbox" value="1" <?php checked( $options['mit_option_log_enable'], 1 ); ?> />
			 <?php
			 _e( 'Enable event recording ', 'moderateIt' );
			 ?>
		 
		 <?php
	}

	/* * */	
	public function mit_option_log_render( ) {
		$options = get_option( 'mit_settings' );
		$log='';
		if (isset( $options['mit_option_log'])) $log=$options['mit_option_log'];
		?>
  		<textarea rows="6" cols="100" name='mit_settings[mit_option_log]' ><?php echo $log; ?></textarea>
		<?php
	}	

	/* * */
	public function mitGroup_section_area_callback( ) {
		echo '';
	}
	/* * */	
	public function mitGroup_section_description_callback( ) {
		echo '';
	}
	
	/* * */	
	public function mitGroup_section_key_callback( ) {
		echo '';
	}
	
	/* * */	
	public function mitGroup_section_log_callback( ) {
		echo '';
	}

	/* * */	
	public function mit_settings_page( ) {
		?>
		<form action='options.php' method='post'>
			<h2><?php 
			   _e( 'Settings for Network of Moderators', 'moderateIt' );
 		    ?></h2>
			<?php
			settings_fields( 'mitGroup' );
			do_settings_sections( 'mitGroup' );
			submit_button();
			?>
		</form>
		<?php
	}
	
	/* * */		
	function mit_settings_save($input){
		
		$options = get_option('mit_settings');
		
 	   if( trim($input['mit_option_api_key'])=='')
			$input['mit_option_api_key']=$this->_plugin_default_options['mit_option_api_key'];
		
		if( !isset( $input['mit_option_pre_anonymous']))    $options['mit_option_pre_anonymous']=0;			
		
   	    if( !isset( $input['mit_option_pre_net_violator'])) $options['mit_option_pre_net_violator']=0;
		if( !isset( $input['mit_option_pre_suspect']))   $options['mit_option_pre_suspect']=0;
		 
  		if  ($options['mit_option_api_key']==$this->_plugin_default_options['mit_option_api_key']) 
		{
			$options['mit_option_pre_net_violator']=1;
			$options['mit_option_pre_suspect']=1;
		}

		 
			 
			 
		 

		
		
		if( !isset( $input['mit_option_post_anonymous']))   $options['mit_option_post_anonymous']=0;
		if( !isset( $input['mit_option_post_authorized']))  $options['mit_option_post_authorized']=0;
		


		if( !isset( $input['mit_option_log_enable'])){
			 $input['mit_option_log']='';
			 $options['mit_option_log_enable']=$this->_plugin_default_options['mit_option_log_enable'];	
		}
		$args = wp_parse_args( $input, $options);
		return $args;
	}
 //////Class  End ///////////////////////////////////////////////////////////////////////////////////////////////////////////////
 }
}
$moderateIt = new moderateIt;
///////////////////////////////////////////////////////////////////////////////////////////////////////////////
?>
