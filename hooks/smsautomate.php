<?php defined('SYSPATH') or die('No direct script access.');
/**
 * smsautomate Hook - Load All Events
 *
 * PHP version 5
 * LICENSE: This source file is subject to LGPL license 
 * that is available through the world-wide-web at the following URI:
 * http://www.gnu.org/copyleft/lesser.html
 * @author	   Ushahidi Team <team@ushahidi.com> 
 * @package	   Ushahidi - http://source.ushahididev.com
 * @copyright  Ushahidi - http://www.ushahidi.com
 * @license	   http://www.gnu.org/copyleft/lesser.html GNU Lesser General Public License (LGPL) 
 */

class smsautomate {
	
	/**
	 * Registers the main event add method
	 */
	public function __construct()
	{
	
		// Hook into routing
		Event::add('system.pre_controller', array($this, 'add'));
		
		$this->settings = ORM::factory('smsautomate')
				->where('id', 1)
				->find();
		
	}
	
	/**
	 * Adds all the events to the main Ushahidi application
	 */
	public function add()
	{
		Event::add('ushahidi_action.message_sms_add', array($this, '_parse_sms'));		
	}

	/**
	 * Check the SMS message and parse it
	 */
	public function _parse_sms()
	{
		//the message
		$message = Event::$data->message;
		$from = Event::$data->message_from;
		$reporterId = Event::$data->reporter_id;
		$message_date = Event::$data->message_date;


		//check to see if we're using the white list, and if so, if our SMSer is whitelisted
		$num_whitelist = ORM::factory('smsautomate_whitelist')
		->count_all();
		if($num_whitelist > 0)
		{
			//check if the phone number of the incoming text is white listed
			$whitelist_number = ORM::factory('smsautomate_whitelist')
				->where('phone_number', $from)
				->count_all();
			if($whitelist_number == 0)
			{
				return;
			}
		}
		
		//the delimiter
		$delimiter = $this->settings->delimiter;
		
		//the code word
		$code_word = $this->settings->code_word;
		
		
		//split up the string using the delimiter
		$message_elements = explode($delimiter, $message);
		
		//echo Kohana::debug($message_elements);
		
		//check if the message properly exploded
		$elements_count = count($message_elements);
		
		if( $elements_count < 4) //must have code word, lat, lon, title. Which is 4 elements
		{
			return;
		}
		
		//check to see if they used the right code word, code word should be first
		if(strtoupper($message_elements[0]) != strtoupper($code_word))
		{
			return;
		}
		
		//start parsing
		//latitude
		$lat = strtoupper(trim($message_elements[1]));
		//check if there's a N or S in here and deal with it
		$n_pos = stripos($lat, "N");
		if(!($n_pos === false))
		{
			$lat = str_replace("N", "", $lat);
		}
		
		$s_pos = stripos($lat, "S");
		if(!($s_pos===false))
		{
			$lat = str_replace("S", "", $lat);
			$lat = "-".$lat; //make negative
		}
		if(is_numeric($lat))
		{
			$lat = floatval($lat);
		}
		else
		{
			return; //not valid
		}
		
		//longitude
		$lon = strtoupper(trim($message_elements[2]));
		//check if there's a W or E in here and deal with it
		$e_pos = stripos($lon, "E");
		if(!($e_pos===false))
		{
			$lon = str_replace("E", "", $lon);
		}
		
		$w_pos = stripos($lon, "W");
		if(!($w_pos===false))
		{
			$lon = str_replace("W", "", $lon);
			$lon = "-".$lon; //make negative
		}
		if(is_numeric($lon))
		{
			$lon = floatval($lon);
		}
		else
		{
			return; //not valid
		}
		
		//title
		$title = trim($message_elements[3]);
		if($title == "")
		{
			return; //need a valid title
		}
		
		$location_description = "";
		//check and see if we have a textual location
		if($elements_count >= 5)
		{
			$location_description =trim($message_elements[4]);
		}
		if($location_description == "")
		{
			$location_description = "Sent Via SMS";
		}
		
		$description = "";
		//check and see if we have a description
		if($elements_count >= 6)
		{
			$description =$description.trim($message_elements[5]);
		}
		$description = $description."\n\r\n\rThis reported was created automatically via SMS.";
		
		$categories = array();
		//check and see if we have categories
		if($elements_count >=7)
		{
			$categories = explode(",", $message_elements[6]);
		}
		
		
		//for testing:
		/*
		echo "lat: ". $lat."<br/>";
		echo "lon: ". $lon."<br/>";
		echo "title: ". $title."<br/>";
		echo "description: ". $description."<br/>";
		echo "category: ". Kohana::debug($categories)."<br/>";
		*/
		
		// STEP 1: SAVE LOCATION
		$location = new Location_Model();
		$location->location_name = $location_description;
		$location->latitude = $lat;
		$location->longitude = $lon;
		$location->location_date = $message_date;
		$location->save();
		//STEP 2: Save the incident
		$incident = new Incident_Model();
		$incident->location_id = $location->id;
		$incident->user_id = 0;
		$incident->incident_title = $title;
		$incident->incident_description = $description;
		$incident->incident_date = $message_date;
		$incident->incident_dateadd = $message_date;
		$incident->incident_mode = 2;
		// Incident Evaluation Info
		$incident->incident_active = 1;
		$incident->incident_verified = 1;
		//Save
		$incident->save();
		
		//STEP 3: Record Approval
		$verify = new Verify_Model();
		$verify->incident_id = $incident->id;
		$verify->user_id = 0;
		$verify->verified_date = date("Y-m-d H:i:s",time());
		if ($incident->incident_active == 1)
		{
			$verify->verified_status = '1';
		}
		elseif ($incident->incident_verified == 1)
		{
			$verify->verified_status = '2';
		}
		elseif ($incident->incident_active == 1 && $incident->incident_verified == 1)
		{
			$verify->verified_status = '3';
		}
		else
		{
			$verify->verified_status = '0';
		}
		$verify->save();
		
		
		// STEP 3: SAVE CATEGORIES
		ORM::factory('Incident_Category')->where('incident_id',$incident->id)->delete_all();		// Delete Previous Entries
		foreach($categories as $item)
		{
			if(is_numeric($item))
			{
				$incident_category = new Incident_Category_Model();
				$incident_category->incident_id = $incident->id;
				$incident_category->category_id = $item;
				$incident_category->save();
			}
		}

		//don't forget to set incident_id in the message
		Event::$data->incident_id = $incident->id;
		Event::$data->save();
		
	}
	

}

new smsautomate;