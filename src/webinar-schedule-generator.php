<?php

namespace MarcWitteveen\WebinarSchedule;

<?php
/**
 * Webinar - Class that creates a uniform date and time schedule for either
 * an evergreen or scheduled webinar
 * @package Launch
 * @author Marc Witteveen <marc.witteveen@gmail.com>
 *
 * Change log
 * version 1.0.0 Marc Witteveen
 * - Class developed
 * version 1.1.0 Marc Witteveen, 7th January 2018
 * - Added method to get leway date
 * - Added method to get current time in timezone
 * - Rewrote EvergreenSchedule to include Leway time 
 * version 1.1.1 Marc Witteveen, 2nd January 2020
 * - Added timezone to RollingSchedule method
 * - Changed logic inside SetTimezone method
 *
 * Todo
 * - Rewrite RollingSchedule to include Leway time
 * - Rewrite StandardSchedule to include Leway time
 * - Create a method to return time in seconds for the next webinar to start for an automatic page refresh  
 */
class WebinarSchedule{
	/**
     * The Webinar Schedule version number.
     * @var string
     */
    public $version = '1.1.1';
	
	/**
     * The timezone used in the script
     * @var string
     */
	private $timezone = 'America/New_York';
	
	/**
     * The webinar type for which a playout schedule is generated
     * @var string
     */
	private $webinarType = 'standard';
	
	/**
     * Available dates of the webinar
     * @var array
     */
	private $availableDates = array();
	
	/**
     * The time when the running webinar is still available for registration
     * @var string
     */
	private $optinLeway = 'PT0H05M';
	
	/**
	 * Array with the days of the week
	 * @var array
	 */
	private $days = array('Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday');
	
	/*
	 * Class constructor
	 * @param String $type The type of webinar that is being used
	 */
	public function __construct($type = 'standard') {
		$this->webinarType = $type;
	}
	
	/*
	 * Set the timezone used in the script
	 * @param String $timezone The timezone that is used.
	 */
	public function SetTimezone($timezone = 'America/New_York') {
		$this->timezone = (empty($timezone))?'America/New_York':$timezone;
	}
	
	/*
	 * Returns the set timezone
	 */
	public function GetTimezone() {
		return $this->timezone;
	}
	
	/**
	 * Returns the current time in the configured timezone
	 * @param String $format Format the returned date according to the provided format
	 * @return String The time in the configured timezone
	 */
	public function GetTimezoneTime($format = "Y-m-d H:i:s") {
		$objCurrentTime = new DateTime();
		$objCurrentTime->setTimezone(new DateTimeZone($this->timezone));
		return $objCurrentTime->format($format);
	}
	
	/*
	 * Load the webinar schedule into the webinar 
	 *  @param Array $schedule A playout list or callender
	 */
	public function SetSchedule($schedule) {
		switch($this->webinarType) {
			case 'evergreen':
				$this->availableDates = $this->EvergreenSchedule($schedule);
				break;
			case 'rolling':
				$this->availableDates = $this->RollingSchedule($schedule);
				break;
			case 'standard':
			default:
				$this->availableDates = $this->StandardSchedule($schedule);
				break;
		}
	}
	
	/*
	 * Set the time when the running webinar is still available for registration
	 * @param String $time The time when the running webinar is still available for registration
	 */
	public function SetOptinLeway($time = 'PT0H05M'){
		if (!empty($time)) {
			$this->optinLeway = $time;
		}
	}
	
	/*
	 * Get the time when the running webinar is still available for registration
	 * @return String The time when the running webinar is still available for registration
	 */
	public function GetOptinLeway() {
		return $this->optinLeway;
	}
	
	/*
	 * Generate an array of with date and time that will be displayed as available 
	 * dates for registration 
	 * @param Array $schedule An list that represents the days of the week and times with the playout should ocure
	 * @return Array List that represents the playout schedule containing the date as a key and playout times are
	 * values and returned as an Array
	 */
	private function EvergreenSchedule($schedule) {
		try {
			//date_default_timezone_set($this->timezone);
			$objCurrentTime = new DateTime();
			$objCurrentTime->setTimezone(new DateTimeZone($this->timezone));
			
			// Loop trough the schedule dates and times
			foreach($schedule as $key => $value) {
				//echo "Day of the week in schedule: " . $key . " - current day of the week " . $objCurrentTime->format('w') . "<br>";
				if ($objCurrentTime->format('w') == $key) { // This is the current day, we should check if the last webinar is over or not	
					// Set the internal pointer of an array to its last element
					$lastTimeOfTheSelectedDay = end($value);
					$tmpWebinarDate = $objCurrentTime->format('Y-m-d');
					$objTmpWebinarDateTime = new DateTime($tmpWebinarDate . " " . $lastTimeOfTheSelectedDay, new DateTimeZone($this->timezone));	
					$objTmpWebinarDateTime->add(new DateInterval($this->optinLeway));
					
					//$objTmpWebinarDateTime->format("d F - H:i:s") . "<br>";
					
					//$objCurrentTime->format("d F - H:i:s") . "<br>";
					
					if ($objTmpWebinarDateTime > $objCurrentTime) {
						//echo "<p>webinar plus optin leway is over</p>";
						$datetime = new DateTime('next ' . $this->days[$key], new DateTimeZone($this->timezone));
						$dateKey = $datetime->format('Y-m-d');
						//$dateKey = date("Y-m-d", strtotime('next ' . $this->days[$key]));
					} else {
						//echo "<p>webinar plus optin leway is not over</p>";
						//$dateKey = date("Y-m-d", strtotime('this ' . $this->days[$key]));
						$datetime = new DateTime('this ' . $this->days[$key], new DateTimeZone($this->timezone));
						$dateKey = $datetime->format('Y-m-d');
						//echo "Date: <strong>" . $dateKey . "</strong><br>";
					}
					// Set the internal pointer of an array to its first element
					reset($value);
				} elseif (($objCurrentTime->format('w') > $key) || ($objCurrentTime->format('w') == 0)) {
					$datetime = new DateTime('this ' . $this->days[$key], new DateTimeZone($this->timezone));
					$dateKey = $datetime->format('Y-m-d');
					//$dateKey = date("Y-m-d", strtotime('this ' . $this->days[$key]));	
				} else {
					$datetime = new DateTime('this ' . $this->days[$key], new DateTimeZone($this->timezone));
					$dateKey = $datetime->format('Y-m-d');
					//$dateKey = date("Y-m-d", strtotime('this ' . $this->days[$key]));
				}
				//echo "Date: " . $dateKey . "<br>";
				$tempAvailableDates[$dateKey] = $value;
			}
			
			/*echo "<pre>";
			print_r($tempAvailableDates);
			echo "</pre>";*/
			ksort($tempAvailableDates);
			return $tempAvailableDates;
		} catch (Exception $e) {
    		echo $e->getMessage();
		}
	}
	
	/*
	 * Load the standard playout schedule
	 * @param Array $schedule List of playout dates and times
	 * @return Array List that represents the playout schedule containing the date as a key and playout times are
	 * values and returned as an Array
	 */
	private function StandardSchedule($schedule) {
		return $schedule;
	}

	/**
	 * Load the rolling playout schedule
	 * @param Array $schedule List of playout dates and times
	 * @return Array List that represents the playout schedule containing the date as a key and playout times are
	 * values and returned as an Array
	 */
	private function RollingSchedule($schedule) {
		try {
			$return = [];
			foreach($schedule as $dateValue => $timeValue) {
				$datetime = new DateTime($dateValue, new DateTimeZone($this->timezone));
				$dateFormated = $datetime->format('Y-m-d');
				$return[$dateFormated] = $timeValue;
			}
			return $return;
		} catch (Exception $e) {
    		echo $e->getMessage();
		}
	}
	
	/*
	 * Test if all planned weninars are over
	 * @return Boolean Return True when the webinar is over else False
	 */
	public function IsOver() {
		try {
			$objCurrentTime = new DateTime();
			$objCurrentTime->setTimezone(new DateTimeZone($this->timezone));
			
			foreach($this->availableDates as $webinarDate => $webinarTimes) {
				foreach($webinarTimes as $webinarTime) {
					$objWebinarDateTime = new DateTime($webinarDate . " " . $webinarTime, new DateTimeZone($this->timezone));	
					$objWebinarDateTime->add(new DateInterval($this->optinLeway));
					if ($objCurrentTime < $objWebinarDateTime) {
						return false;	
					}
					unset($objWebinarDateTime);
				}
			}
			return true;
		} catch (Exception $e) {
    		echo $e->getMessage();
		}
    }
	
    /**
     * Get the next Dates that the webinar is available, limited by the $limit parameter
     * @param Integer $limit The number of dates that should be returned
	 * @param String $dateFormat In what format the dates should be returned
	 * @param String $timeFormat In what format the webinar times should be returned
	 * @param String $webinarTimeFormat In what format the webinar times should be returned
     */
    public function GetDates($limit = 2, $dateFormat = 'l, F jS', $timeFormat = 'g:ia ', $webinarTimeFormat = 'H:i') {
        try {
			$validDates = array();
			$objCurrentTime = new DateTime();
			$objCurrentTime->setTimezone(new DateTimeZone($this->timezone));
			foreach($this->availableDates as $webinarDate => $webinarTimes) {
				foreach($webinarTimes as $webinarTime) {
					$objWebinarDateTime = new DateTime($webinarDate . " " . $webinarTime, new DateTimeZone($this->timezone));	
					$objWebinarDateTime->add(new DateInterval($this->optinLeway));
					if ($objCurrentTime < $objWebinarDateTime) {
						if(!isset($validDates[$webinarDate])){
							$validDates[$webinarDate] = array(
								'text' => date($dateFormat, strtotime($webinarDate)),
								'day' => date('l', strtotime($webinarDate)),
								'timeValue' => array(),
								'timeText' => array()
							);
						}
						
						$validDates[$webinarDate]['timeValue'][] = date($webinarTimeFormat, strtotime($webinarTime));
						$validDates[$webinarDate]['timeText'][] = date($timeFormat, strtotime($webinarTime)) . 'Eastern';
					}
					unset($objWebinarDateTime);
				}
				if (count($validDates) >= $limit){
					break;
				}
			}
			return $validDates;
		} catch (Exception $e) {
    		echo $e->getMessage();
		}
    }	
}