<?php
/* ===========================================================================
ext.md_freeform_send_another.php ---------------------------
Send email to a person from a custom field in your form
            
INFO ---------------------------
Developed by: Ryan Masuga, masugadesign.com
Created:   Jul 17 2008
Last Mod:  Sept 25 2009 by Erik Reagan, erikreagan.com


http://expressionengine.com/docs/development/extensions.html
=============================================================================== */
if ( ! defined('EXT')) { exit('Invalid file request'); }

class Md_freeform_send_another
{
	var $settings		= array();
	
	var $name           = 'MD Freeform Send Another';
	var $class_name     = 'Md_freeform_send_another';
	var $version        = '1.1.0';
	var $description    = 'Enables Freeform email notification based on input values and CP settings';
	var $settings_exist = 'y';
	var $docs_url       = '';

// --------------------------------
//  PHP 4 Constructor
// --------------------------------
	function Md_freeform_send_another($settings='')
	{
		$this->__construct($settings);
	}

// --------------------------------
//  PHP 5 Constructor
// --------------------------------
	function __construct($settings='')
	{
		global $SESS;
		$this->settings = $settings;
	}
	
	// --------------------------------
	//  Change Settings
	// --------------------------------  
	function settings()
	{
		global $LANG;
		$settings = array();
		$settings['form_fields'] = 'recipient';
		$settings['additional_emails'] = '';
		return $settings;
	}
	
	// --------------------------------
	//  Activate Extension
	// --------------------------------
	function activate_extension()
	{
		global $DB, $PREFS;

		$hooks = array(
		  'freeform_module_insert_end' => 'freeform_module_insert_end'
		);
		
      foreach ($hooks as $hook => $method)
      {
         $sql[] = $DB->insert_string( 'exp_extensions', 
         array(
            'extension_id' 	=> '',
            'class'			=> get_class($this),
            'method'		=> $method,
            'hook'			=> $hook,
            'settings'	=> "",
            'priority'	=> 10,
            'version'		=> $this->version,
            'enabled'		=> "y"
            )
         );
      }

		// run all sql queries
		foreach ($sql as $query)
		{
			$DB->query($query);   
		}
		return TRUE;
	}
	
	
	// --------------------------------
	//  Disable Extension
	// -------------------------------- 
	function disable_extension()
	{
		global $DB;
		$DB->query("DELETE FROM exp_extensions WHERE class = '" . get_class($this) . "'");
	}
	
	// --------------------------------
	//  Update Extension
	// --------------------------------  
	function update_extension($current='')
	{
		global $DB;	
		if ($current == '' OR $current == $this->version)
		{
			return FALSE;
		}
		$DB->query("UPDATE exp_extensions
		            SET version = '".$DB->escape_str($this->version)."'
		            WHERE class = '".get_class($this)."'");
	}
	// END	
	// ============================================================================	

	// Remember, there are two hacks needed in Freeform 2.6.5 to get the $msg variable to work
	// here. You have to comment out both places where unset($msg) exists.

	function freeform_module_insert_end ($fields, $entry_id, $msg)
	{
     global $DB, $EXT, $REGX;

     // Select the appropriate form entry based on the entry_id in the hook
     $query = $DB->query("SELECT * FROM exp_freeform_entries WHERE entry_id = '".$entry_id."' LIMIT 1");
     if ( $query->num_rows == 0 )
     {
        return;
     }
     
     // New array for our new recipients
     $all_recipients = array();

     
     // Grab the recipients from the form results
     $form_fields = $this->settings['form_fields'] ? preg_split("/,|\|/" , $this->settings['form_fields'] ) : '';
     if (is_array($form_fields)) {
        foreach ($form_fields as $field) {
           // Make sure there's actually a freeform field with this name and that the field value is not empty
           if ((array_key_exists($field, $query->row) && ($query->row[$field] != ''))) {
              $all_recipients[] = $query->row[$field];
           }
        }
     }

     
     // Grab the additional email addresses from the CP settings
     $email_recipients = $this->settings['additional_emails'] ? preg_split("/,|\|/" , $this->settings['additional_emails'] ) : '';
     
     if (is_array($email_recipients)) {
        foreach ($email_recipients as $address) {
           $all_recipients[] = $address;
        }
     }
     
     
     // echo '<pre>';
     // print_r($all_recipients);
     // print_r($msg);
     // echo '</pre>';
     // exit;
     

     if ( ! class_exists('EEmail'))
     {
        require PATH_CORE.'core.email'.EXT;
     }

     $email				= new EEmail;
     $email->wordwrap	= FALSE;
     $email->mailtype	= 'html';

     foreach ($all_recipients as $recipient) {
        
        $email->initialize();
        $email->from($msg['from_email'], $msg['from_name']);	
        $email->to($recipient); 
        $email->subject($msg['subject']);	
        $email->message($REGX->entities_to_ascii($msg['msg']));		
        $email->Send();
        
     }

     unset($msg);

     $EXT->end_script = FALSE;
	}

/* END class */
}
/* End of file ext.md_freeform_send_another.php */
/* Location: ./system/extensions/ext.md_freeform_send_another.php */ 