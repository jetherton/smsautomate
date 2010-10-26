<?php defined('SYSPATH') or die('No direct script access.');
/**
 * SMS Automate Administrative Controller
 *
 * @author	   John Etherton
 * @package	   SMS Automate
 */

class Smsautomate_settings_Controller extends Admin_Controller
{

	function __construct()
	{
		parent::__construct();
		$this->template->this_page = 'settings';

		// If this is not a super-user account, redirect to dashboard
		if(!$this->auth->logged_in('admin') && !$this->auth->logged_in('superadmin'))
		{
			url::redirect('admin/dashboard');
		}
	}
	
	public function index()
	{
		
		$this->template->content = new View('smsautomate/smsautomate_admin');
		
		//create the form array
		$form = array
		(
		        'delimiter' => "",
			'code_word' => "",
			'whitelist' => ""
		);
		
		$errors = $form;
		$form_error = FALSE;
		$form_saved = FALSE;
				
		// check, has the form been submitted if so check the input values and save them
		if ($_POST)
		{
			// Instantiate Validation, use $post, so we don't overwrite $_POST
			// fields with our own things
			$post = new Validation($_POST);
			
			// Add some filters
			$post->pre_filter('trim', TRUE);
			$post->add_rules('delimiter', 'length[1,1]');
			$post->add_rules('code_word', 'length[1,11]');
			
			 if ($post->validate())
			{
				
				$settings = ORM::factory('smsautomate')
					->where('id', 1)
					->find();
				$settings->delimiter = $post->delimiter;
				$settings->code_word = $post->code_word;
				$settings->save();
				$form_saved = TRUE;
				$form = arr::overwrite($form, $post->as_array());
				
				//do the white list
				
				//delete everything in the white list db to make room for the new ones
				ORM::factory('smsautomate_whitelist')->delete_all();
				
				$whitelist = nl2br(trim($post->whitelist));
				if($whitelist != "" && $whitelist != null)
				{
					$whitelist_array = explode("<br />", $whitelist);
					//now put back the new ones
					foreach($whitelist_array as $item)
					{
						$whitelist_item = ORM::factory('smsautomate_whitelist');
						$whitelist_item->phone_number = trim($item);
						$whitelist_item->save();
					}
				}
			}
			
			// No! We have validation errors, we need to show the form again,
			// with the errors
			else
			{
				// repopulate the form fields
				$form = arr::overwrite($form, $post->as_array());

				// populate the error fields, if any
				$errors = arr::overwrite($errors, $post->errors('settings'));
				$form_error = TRUE;
			}
		}
		else
		{
			//get settings from the database
			$settings = ORM::factory('smsautomate')
				->where('id', 1)
				->find();
			$form['delimiter'] = $settings->delimiter;
			$form['code_word'] = $settings->code_word;
			
			//get the white listed numbers
			$whitelist = "";
			$count = 0;
			$listers = ORM::factory('smsautomate_whitelist')->find_all();
			foreach($listers as $item)
			{
				$count++;
				if($count > 1)
				{
					$whitelist = $whitelist."\n";
				}
				$whitelist = $whitelist.$item->phone_number;
			}
			$form['whitelist'] = $whitelist;
		}
		
		
		
		$this->template->content->form_saved = $form_saved;
		$this->template->content->form = $form;
		$this->template->content->form_error = $form_error;
		$this->template->content->errors = $errors;
		
	}//end index method
	
	

	
}