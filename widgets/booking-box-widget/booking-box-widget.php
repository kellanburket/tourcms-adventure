<?php
class TourcmsBookingBoxWidget extends WP_Widget {
	
	private $fields;
	private $tour_id;
	private $tour_name;
	
	private $debug;
		
	function __construct() {
		parent::__construct('TourcmsBookingBoxWidget', 'TourCMS Booking Box', array('description' => ''));	
		$this->fields = array(
			'title' => array('label'=>'Title', 'type'=>'text'),
			'subtitle' => array('label'=>'Subtitle', 'type'=>'text'),
			'call_to_action' => array('label'=>'Call to Action', 'type'=>'text')			
		);
		
		$this->debug = get_option('tourcms_debug_mode');
	}

	function form($instance) {
		$instance = wp_parse_args( (array) $instance, array_flip(array_keys($this->fields)));
		//print_r($instance);
	
		echo '<table class="tourcms-widget-table">';
		foreach($instance as $field => $value) {
			if (array_key_exists($field, $this->fields)) {
				$form_field = array(
					'name'=>$this->get_field_name($field),
					'id'=>$this->get_field_id($field),
					'label'=>$this->fields[$field]['label'],
					'attributes'=>array(
						'type'=>$this->fields[$field]['type']
					)
				);
				
				if ($this->fields[$field]['type'] == 'checkbox') {
					$form_field['attributes']['checked'] = ($value == 'on') ? 'checked' : '';
				} else {
					$form_field['attributes']['value'] = $value;
				}
				
				echo $this->get_form_field($form_field);
			}
		}
		echo '</table>';
	}
	
	protected function get_label($id, $label) {
	    $form_field = '<td><label for="'.$id.'">';
        $form_field .= $label.' </label></td>';
		return $form_field;
	}
	
	protected function get_input($name, $id, $attributes) {
		$form_field = '<td><input class="widefat" name="'.$name.'" id="'.$id.'" ';
		foreach ($attributes as $attr => $val) {
			if ($val) {
				$form_field .= $attr.'='.$val.' ';
			}
		}
		$form_field .= '/></td>';
		return $form_field;
	}
	
	protected function get_form_field($field) {
    	$form_field = '<tr>';
		if ($field['attributes']['type'] == 'checkbox') {
			$form_field .= $this->get_input($field['name'], $field['id'], $field['attributes']);
			$form_field .= $this->get_label($field['id'], $field['label']);
		} else {
			$form_field .= $this->get_label($field['id'], $field['label']);
			$form_field .= $this->get_input($field['name'], $field['id'], $field['attributes']);
		}
		return $form_field.'</tr>';	
	}
	
	function update($new_instance, $old_instance) {
		$instance = wp_parse_args( (array) $old_instance, array_flip(array_keys($this->fields)));
		
		foreach ($instance as $key => $value) {
			$instance[$key] = strip_tags($new_instance[$key]);
		}
		return $instance;
	}


	function widget($args, $instance) {
		extract($args);
		global $post;
		if ($post->post_type == TOUR_PAGE || $post->post_type == MOBILE_TOUR_PAGE) {
			$this->tour_id = get_post_meta($post->ID, 'tour_id', true);
		}
		
		$channel_id = SiteConfig::get("channel_id");
		$tourcms = load_tourcms(); 
		$tours = $tourcms->list_tours($channel_id)->tour; 
		$tour_select = $this->get_tour_select_options($tours);
		
		$call_to_action = empty($instance['call_to_action']) ? '&nbsp;' : $instance['call_to_action'];
		$title = empty($instance['title']) ? '&nbsp;' : $instance['title'];
		$subtitle = empty($instance['subtitle']) ? '&nbsp;' : $instance['subtitle'];
		
		$params = 'tour_id='.$this->tour_id.'&distinct_start_dates=1';		
		$result = $tourcms->show_tour_datesanddeals($this->tour_id, $channel_id, $params);
		if ($result->error == 'OK') {
			list($year, $month, $day) = sscanf($result->dates_and_prices->date[0]->start_date, '%d-%d-%d');
			$next_available = $month.'/'.$day.'/'.$year;
		}
		
		$this->display($call_to_action, $title, $subtitle, $tour_select, $next_available);
	}
	
	function get_tour_select_options($tour) {
		global $post;
		$return = '';
		foreach ($tour as $key=>$value) {
			$return .= '<option value="'.$value->tour_id.'"';
			if ($value->tour_id == $this->tour_id) {
				$this->tour_name = $value->tour_name;
				$return .= ' selected';
				
			}
        	
        	$return .= '>'.$value->tour_name.'</option>';
        }

        return $return;
	}
	
	function display($call_to_action, $title, $subtitle, $tour_select, $next_available) { 
		$prefill = array();
		if ($this->debug) {
			$prefill['no_children'] = 1;
			$prefill['no_adults'] = 2;
			$prefill['submit'] = '';			
		} else {
			$prefill['no_children'] = 0;
			$prefill['no_adults'] = 0;
			$prefill['submit'] = 'disabled';					
		}
		?>
	
		<div id="adventure-widget-wrap">
            <div class="footertitle">
                <h4><?php echo $call_to_action; ?></h4>
            </div>
            
            <div class="choose-your-adventure-widget">
                <div class="choose-your-adventure-header">                
                    <h4 class="adventure-h4"><?php echo $title; ?>
</h4>
                    <h5 class="adventure-h5"><?php echo $subtitle; ?></h5>
                
                </div>
                
                <form id="datepicker" action="" method="post">
                    
                    <h6 class="datepicker-h6">Choose Your Adventure</h6>
                    
					<select name="datepicker_tour_id" id="datepicker-select" class="datepicker-field" value="<?php echo $this->tour_name; ?>">
						<?php echo $tour_select ?>
					</select>
                
                    <div id="datepicker-activity-date">
                        <h6 class="datepicker-h6">Activity Date</h6>
                        <div id="datepicker-pick-a-date-wrapper">
                            <input type="text" id="activity-date-field" name="datepicker_activity_date" class="datepicker-field" value="<?php echo $next_available; ?>">
                            <button id="calendar-button">
                                <img src="<?php echo TOURCMS_URL; ?>/img/calendar-icon.png" />
                            </button>
                            <div id="pop-up-calendar">

                                <div id="datepicker-head">
                                    <button id="datepicker-back-one" class="back-one datepicker-button" disabled>&larr;</button>
                                    <span id="datepicker-month"><?php echo date("F")." ".date("Y"); ?></span>
                                    <button id="datepicker-forward-one" class="forward-one datepicker-button">&rarr;</button>
                                </div>
                                <table id="datepicker-table" class="tourcms-live-calendar"></table>
                                
                            </div>
                        </div>
                    </div> 
    
                    <div id="datepicker-adults">
                        <input type="number" name="datepicker_no_adults" id="no-adults-input" value="<?php echo $prefill['no_adults']; ?>" class="guests-input datepicker-field">
                        <span id="adults-label" class="guests-label">Adults</span>
                    </div>
                    
                    <div id="datepicker-children">
                        <input type="number" name="datepicker_no_children" id="no-children-input" value="<?php echo $prefill['no_children']; ?>" class="guests-input datepicker-field">
                        <span id="children-label" class="guests-label">Children</span><span id="ages312"> (Ages 3-12)</span>
                    </div>
                    
                    <div id="datepicker-promo-code">
                        <p id="promo-label">Promotional Code</p>
                        <input type="text" name="datepicker_promo_code" id="datepicker-promo-code-input" class="datepicker-field" />
                    </div>
                    <!--
                    <div id="datepicker-refund">
                        <input type="checkbox" name="full_refund" id="full-refund-input">
                        <span>Full refund with 48 hour advance cancellation.</span>
                    </div>
                    -->
                	<div id="datepicker-submit-div">
                        <input type="submit" value="BOOK NOW" id="datepicker-submit" <?php echo $prefill['submit']; ?>>
                    </div>
                    <?php wp_nonce_field('tourcms_checkout', '_tourcms_footer_nonce', true, true); ?>            
                </form>
                   
            </div>
      	</div>
	
	<?php }
}