<?php
class TourcmsHomepageWidget extends WP_Widget {
	
	private $fields;
	private $tour_id;
	private $tour_name;
	
	private $debug;
		
	function __construct() {
		parent::__construct('TourcmsHomepageWidget', 'TourCMS Homepage Widget', array('description' => 'Book tours on the homepage'));	

		$this->fields = array();
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
		//print_r($instance);
		//print_r($new_instance);
		//exit;
		
		foreach ($instance as $key => $value) {
			$instance[$key] = sanitize_text_field($new_instance[$key]);
		}
		return $instance;
	}


	function widget($args, $instance) {		
		$this->display();
	}
	
	function display() {  ?>
		
		<div class="row homepage-widget-background"></div>
		<div class="row homepage-widget">
			<input type="hidden" name="user_id">
			<ul class="row">
				<li class="col-sm-6">
					<div>
						<p class="yellar-text">Where Do You Want to Go?</p>
						<select class="datepicker-field white" name="tour_id">
						</select>
					</div>
				</li>
				<li class="col-sm-6">
					<div>
						<p class="yellar-text">Check In</p>
	                    <div id="datepicker-pick-a-date-wrapper">
							<div>
		                    	<input id="calendar-date" type="text" id="activity-date-field" name="tour_date" class="datepicker-field" placeholder="MM/DD/YYYY">
		                        <div id="calendar-button"></div>
							</div>
	                        <div id="pop-up-calendar" class="tourcms-live-calendar">
	
	                            <div id="datepicker-head">
	                                <button id="datepicker-back-one" class="back-one datepicker-button" disabled>&larr;</button>
	                                <span id="datepicker-month"><?php echo date("F")." ".date("Y"); ?></span>
	                                <button id="datepicker-forward-one" class="forward-one datepicker-button">&rarr;</button>
	                            </div>								
								<table class="datepicker-calendar">
								</table>									                            
	                        </div>
	                    </div>
					</div>
				</li>
				<li class="col-sm-6">
					<div>
						<p class="yellar-text">Adults</p>
	                    <input type="number" name="datepicker_no_adults" id="no-adults-input" value="" class="datepicker-field" min="0">
	                    <input type="hidden" name="adult_rate" id="adult_rate">
					</div>
				</li>				
				<li class="col-sm-6">
					<div>
						<p class="yellar-text">Children</p>
						<input type="number" name="datepicker_no_children" id="no-children-input" class="datepicker-field" min="0">
	                    <input type="hidden" name="child_rate" id="child_rate">
					</div>
				</li>				
				<li class="col-sm-6">
                    <?php wp_nonce_field('tourcms_checkout', '_tourcms_homepage_nonce', true, true); ?>            
					<div>
						<button id="search-now" class="yellar black-text">
							<span class="button-text">Book Now</span>
							<span class="fa fa-circle-o-notch spinner fa-spin"></span>
						</button>
					</div>
				</li>				
			</ul>
		</div> <?php
		}
}