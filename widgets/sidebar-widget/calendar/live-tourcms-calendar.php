<?php
class LiveTourCMSCalendar {

	public $selected_month;
	public $selected_year;
	public $selected_day;
	public $first_day_of_month;
	public $days_in_month;
	
	private $tour_id;
	private $channel_id;
	
	function __construct($args = array()) {
		extract($args);
		
		@ $this->table_id = $args['table_id'];
		@ $this->weekday_class = $args['weekday_class'];
		@ $this->day_class = $args['day_class'];

		@ $this->tour_id = ($tour_id) ? $tour_id : 0;
		@ $this->selected_day = ($selected_day) ? $selected_day : 0;
		@ $this->selected_month = ($selected_month) ? $selected_month : 0;
		@ $this->selected_year = ($selected_year) ? $selected_year : 0;
		
		$today = intval(date('d'));
		$year = date("Y");	
		$month = date("n");
		
		if (!$this->selected_day) {
			$this->selected_day = $today;	
		}
		if (!$this->selected_month) {
			$this->selected_month = $month;
		}
		if (!$this->selected_year) {
			$this->selected_year = $year;
		}
		
		$this->first_day_of_month = intval(date('w', strtotime("1-".$this->selected_month."-".$this->selected_year)));
		$this->days_in_month = cal_days_in_month(CAL_GREGORIAN, intval($this->selected_month), intval($this->selected_year));
		
		if ($this->selected_day == $today && $this->selected_month == $month && $this->selected_year == $year && $this->days_in_month == $today) {
			$this->selected_day = 1;
			
			if ($month == "12") {
				$this->selected_month = 1;
				$this->selected_year = intval(date("Y")) + 1;	
			} else {
				$this->selected_month = intval(date("n")) + 1;
			}

			$this->first_day_of_month = intval(date('w', strtotime("1-".$this->selected_month."-".$this->selected_year)));
			$this->days_in_month = cal_days_in_month(CAL_GREGORIAN, intval($this->selected_month), intval($this->selected_year));
		}
	}
	
	private function get_available_dates($tourcms, $channel_id) {	
		//echo 'Search Date Begin: ';
		$search_begin_date = $this->selected_year.'-'.str_pad($this->selected_month, 2, '0', STR_PAD_LEFT).'-01';
		//echo '<br>Search Date End: ';
		$search_end_date = $this->selected_year.'-'.str_pad($this->selected_month, 2, '0', STR_PAD_LEFT).'-'.$this->days_in_month;
		//echo '<br>';
		$params_string = 'between_date_start='.$search_begin_date.'&between_date_end='.$search_end_date;
		
		$availability = $tourcms->show_tour_datesanddeals($this->tour_id, $channel_id, $params_string);

		if (!is_object($availability->dates_and_prices)) {
			return false;
		} else {
			return $availability->dates_and_prices->date;
		}
	}
		
	public function display_calendar($tourcms, $channel_id) {
		
		$available_dates = $this->get_available_dates($tourcms, $channel_id);
		$days_gone_by = 0;
    	$months = array("January", "February", "March", "April", "May", "June", "July", "August", "September", "October", "November", "December");
		$days_of_week = array("Su", "Mo", "Tu", "We", "Th", "Fr", "Sa");
		
		$return = '<table id="'.$this->table_id.'" class="tourcms-live-calendar"><tr>';
        
		foreach($days_of_week as $value) {
			$return .= '<td class="'.$this->weekday_class.'">'.$value.'</td>';
		}
        $return .= '</tr><tr>';
		
		for ($i = 0; $i < $this->first_day_of_month; $i++) {
        	$return .= '<td class="date_empty date_td"></td>';
            $days_gone_by++; 
		}
			
		$date_prefix = $this->selected_year."-".(($this->selected_month > 9) ? $this->selected_month : "0".$this->selected_month)."-";
				
		$ii = 0;		
		for ($i = 1; $i <= $this->days_in_month; $i++) {
			$class = "date_any";
			if (is_object($available_dates[$ii])) {
				//print_r($available_dates[$ii]);
				$next_available_date = strip_tags($available_dates[$ii]->start_date->asXML());
				$current_date = ($i < 10 ? $date_prefix."0".$i : $date_prefix.$i);
					
				if ($next_available_date == $current_date) {
					$class .= " available";
					$id = 'a';
					do {
						if (count($available_dates) == ++$ii)
							break;
					} while (strip_tags($available_dates[$ii]->start_date->asXML()) == $next_available_date);
				} else {
					$class .= " unavailable";
					$id = 'u';
				}
			} else {
				$class .= " unavailable";
				$id = 'u';
			}
			
			$return .= '<td class="day-td '.$this->day_class.' '.$class.'">'.$i.'</td>';
			$days_gone_by++;
			
			if ($days_gone_by % 7 == 0) {
				$return .= '</tr><tr>';
			}
		}
		$return .= '</tr></table>';
		return $return;
	}
	
	public function review_dates() {
		$today = intval(date('d'));
		$year = date("Y");	
		$month = date("n");
		
		echo 'Day: '.$this->selected_day.', '.$today.'<br>';
		echo 'Month: '.$this->selected_month.', '.$month.'<br>';
		echo 'Year: '.$this->selected_year.': '.$year.'<br>';
		echo 'Days in Month: '.$this->days_in_month.'<br>';
	}

}