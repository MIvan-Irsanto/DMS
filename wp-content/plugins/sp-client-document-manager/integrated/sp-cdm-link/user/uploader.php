<?php

$sp_cdm_link_uploader = new sp_cdm_link_uploader;
add_filter('sp_cdm_add_buttons', array($sp_cdm_link_uploader, 'button'));
add_filter('sp_cdm_upload_bottom', array($sp_cdm_link_uploader, 'add_form'));

add_action( 'wp_ajax_sp_cdm_link_save_embed', array($sp_cdm_link_uploader, 'save_embed'));




class sp_cdm_link_uploader{
	
	
	
	
		
		
		function save_embed(){
			global $wpdb,$current_user;
			
$json = array();
			
	$insert_file['uid'] = cdm_var('uid');
	$insert_file['cid'] = cdm_var('cid');
	$insert_file['name'] =cdm_var('link-name');
	$insert_file['file'] = 'link';
	$insert_file['url'] = cdm_var('link-url');
	$insert_file['pid'] = cdm_var('pid');
	$insert_file['notes'] =  cdm_var('dlg-upload-notes');
	$insert_file['tags'] =  cdm_var('tags');

	#check if its a group
		if(cdm_cookie('cdm_group_id') ){
		$insert_file['group_id'] = cdm_cookie('cdm_group_id');
			if(sp_cdm_group_client( cdm_cookie('cdm_group_id')) != false){
				$insert_file['client_id'] = sp_cdm_group_client( cdm_cookie('cdm_group_id'));
			}
				
		}
		#check if client is set
		if(cdm_cookie('cdm_client_id')){
		$insert_file['client_id'] = cdm_cookie('cdm_client_id');	
		}
	foreach($insert_file as $key=>$value){ if(is_null($value)){ unset($insert_file[$key]); } }
	$wpdb->insert("".$wpdb->prefix."sp_cu", $insert_file);
    
        $file_id = $wpdb->insert_id;
    $uid = cdm_var('uid');   
    $post = $insert_file;
	$json['file_id'] = $wpdb->insert_id;
            
			
            
            
            
            #mail
		
            if (cdm_var('page') == 'sp-client-document-manager-fileview') {
                            $to            = $user_info->user_email;
                            $email_subject = 'sp_cu_admin_user_email_subject';
                            $email_body    = 'sp_cu_admin_user_email';
                            $email_cc      = 'sp_cu_additional_admin_cc';
                            $user_email    = false;
                        } else {
                            $to            = apply_filters('sp_cdm/mail/admin_email', get_option('admin_email'));
                            $email_subject = 'sp_cu_admin_email_subject';
                            $email_body    = 'sp_cu_admin_email';
                            $email_cc      = 'sp_cu_additional_admin_emails';
                            $user_email    = true;
                        }
            #send admin email
                        if (get_option($email_body) != "") {
                            $headers[] = "" . __("From:", "sp-client-document-manager") . " " . $current_user->user_firstname . " " . $current_user->user_lastname . " <" . $current_user->user_email . ">\r\n";
                            if (get_option($email_cc) != "") {
                                $cc_admin = explode(",", get_option($email_cc));
                                foreach ($cc_admin as $key => $email) {
                                    if ($email != "") {
                                        $pos = strpos($email, '@');
                                        if ($pos === false) {
                                            $role_emails = sp_cdm_find_users_by_role($email);
                                            foreach ($role_emails as $keys => $role_email) {
                                                $headers[] = 'Cc: ' . $role_email . '';
                                            }
                                        } else {
                                            $headers[] = 'Cc: ' . $email . '';
                                        }
                                    }
                                }
                            }
                            $message = sp_cu_process_email($file_id, stripslashes(get_option($email_body)));
                            $subject = sp_cu_process_email($file_id, stripslashes(get_option($email_subject)));
                            add_filter('wp_mail_content_type', 'set_html_content_type');
                            wp_mail($to, $subject, $message, apply_filters('sp_cdm/mail/headers', $headers, wp_get_current_user(), $to, $subject, $message), $attachments);
                            remove_filter('wp_mail_content_type', 'set_html_content_type');
                            unset($headers);
                            unset($pos);
                        }
                        if (get_option('sp_cu_user_email') != "" && $user_email == true) {
                            $subject = sp_cu_process_email($file_id, stripslashes(get_option('sp_cu_user_email_subject')));
                            $message = sp_cu_process_email($file_id, stripslashes(get_option('sp_cu_user_email')));
                            
							$user_info = get_userdata($user_ID);
							$to      = $user_info->user_email;
                            if (get_option('sp_cu_additional_user_emails') != "") {
                                $cc_user = explode(",", get_option('sp_cu_additional_user_emails'));
                                foreach ($cc_user as $key => $user_email) {
                                    if ($user_email != "") {
                                        $pos = strpos($user_email, '@');
                                        if ($pos === false) {
                                            $role_user_emails = sp_cdm_find_users_by_role($user_email);
                                            foreach ($role_user_emails as $keys => $role_user_email) {
                                                $user_headers[] = 'Cc: ' . $role_user_email . '';
                                            }
                                        } else {
                                            $user_headers[] = 'Cc: ' . $user_email . '';
                                        }
                                    }
                                }
                            }
                            $message      = apply_filters('spcdm_user_email_message', $message, $post, $uid);
                            $to           = apply_filters('spcdm_user_email_to', $to, $post, $uid);
                            $subject      = apply_filters('spcdm_user_email_subject', $subject, $post, $uid);
                            $attachments  = apply_filters('spcdm_user_email_attachments', $attachments, $post, $uid);
                            $user_headers = apply_filters('spcdm_user_email_headers', $user_headers, $post, $uid);
                            add_filter('wp_mail_content_type', 'set_html_content_type');
                            wp_mail($to, $subject, $message, apply_filters('sp_cdm/mail/headers', $user_headers, apply_filters('sp_cdm/mail/admin_email', get_option('admin_email')), $to, $subject, $message), $attachments);
                            remove_filter('wp_mail_content_type', 'set_html_content_type');
                        }
						
            
            
            #mail
            
            
            
            
            
            
            
			$json['success'] = '1';
			echo json_encode($json);
			die();
			
			
			
		}
			
	function add_form($html){
		
		global $wpdb;
		
			
	
		$html = apply_filters('sp_cdm_link_before_form' , $html);
		$html .='<div style="display:none">
		
			
			<div class="remodal" data-remodal-id="add-link" data-remodal-options="{ \'hashTracking\': false }">
			'. sp_cdm_link_uploader::upload_dialog().'
					</div></div>';
		
		return $html;
	}
	
	
	
		 function upload_dialog()
    {
        global $wpdb;
        global $current_user;
		
		if($current_user->ID != ''){
        if (cdm_var('id') != '') {
            $uid =cdm_var('id');
        } else {
            $uid = $current_user->ID;
        }
		$html = '';
        $html .= '




<form  action="' . $_SERVER['REQUEST_URI'] . '" method="post" enctype="multipart/form-data" id="cdm_embed_link" >
<input type="hidden" name="pid" value="0" class="cdm_premium_pid_field">
<input type="hidden" name="action" value="sp_cdm_link_save_embed">
<input type="hidden" name="uid" value="'.$uid .'">
';

if( cdm_var('page')== 'sp-client-document-manager-fileview') {
$html .='<input type="hidden" name="admin-uploader" value="1">';	
}
        $html .= '<div>';

		
	
		$html .='<p>
		<label>' . sprintf('%s Title', get_option('sp_cdm_link_word','Link')) . ' <span style="color:red">*<span></label>
		<input  type="text" name="link-name" class="required_name required" >';
		
		$html .='</p> ';
		if(function_exists('sp_cdm_display_categories')){
        //$html .= sp_cdm_display_projects();
        $html .= sp_cdm_display_categories();
		}
      
	
$html .=' <p>
 <label>' . sprintf('%s URL', get_option('sp_cdm_link_word','Link')) . ' <span style="color:red">*<span></label>
  <input  type="text" name="link-url" class="required_name required" >


  </p>';
	
		
		
         if (get_option('sp_cu_enable_tags') == 1) {
                $html .= '

 <p>
 <label>' . __("Tags:", "sp-client-document-manager") . '</label>
   <textarea id="tags" name="tags"  style="width:90%;height:30px"></textarea>

  </p>';
         
            
        } else {
            $html .= '<p>

    <label>' . __("Notes", "sp-client-document-manager") . ':</label>
	<textarea style="width:90%;height:50px" name="dlg-upload-notes"></textarea>

  </p>

  ';
  

        }
		if(function_exists(' display_sp_cdm_form')){
		$html .= display_sp_cdm_form();
		}
			$spcdm_form_upload_fields = '';
		$spcdm_form_upload_fields .= apply_filters('spcdm_form_upload_fields',$spcdm_form_upload_fields);
		
		$html .= $spcdm_form_upload_fields;
		
	
        $html .= '

		
			<div style="padding-top:15px">
					<input type="submit"  class="btn btn-primary" value="' . sprintf('Add %s', get_option('sp_cdm_link_word','Link')) . '">
</div>
			
				

						<div class="sp_change_indicator" ></div>	
			<div class="cdm_debug"></div>
						';
        $html .= '

</div>';
        $timestamp = time();
        $html .= '



	</form>

		

	

	';
    
      return $html;
		}
    }
		function button($html){
			if( sp_cdm_is_featured_disabled('base', 'cdm_add_link') == false){
		  $html .= '  <a href="#"  class="sp_cdm_open_modal sp_sp_cdm_add_file hide_add_file_permission" data-modal="add-link">' . sprintf(__('Add %s','sp-client-document-manager'), get_option('sp_cdm_link_word','Link'))  . '</a> ';
			}
		  return $html;
	}
	
	
	
}