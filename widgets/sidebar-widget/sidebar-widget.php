<?php
class TourcmsSidebarWidget extends WP_Widget {
	
	private $fields;
	private $sales_tax = 7.167;
	function __construct() {
		parent::__construct('TourcmsSidebarWidget', 'TourCMS Sidebar', array('description' => ''));
        $this->fields = array(
			'include_mobile' => array('label'=>'Include Mobile Elements', 'type'=>'checkbox'),
			'include_tablet' => array('label'=>'Include Tablet Elements', 'type'=>'checkbox'),
			'include_desktop' => array('label'=>'Include Desktop Elements', 'type'=>'checkbox')
		);
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
		//echo 'Old Instance: ';
		//print_r($instance);
		//echo '<br>New Instnace: ';
		//print_r($new_instance);
		//echo '<br>';
		
		foreach ($instance as $key => $value) {
			$instance[$key] = strip_tags($new_instance[$key]);
		}
		return $instance;
	}
		
	function widget($args, $instance) {
		require_once('switchbox/switchbox.php');

		extract($args);
		$include_tablet = ($instance['include_tablet'] == 'on') ? true : false;
		$include_mobile = ($instance['include_mobile'] == 'on') ? true : false;
		$include_desktop = ($instance['include_desktop'] == 'on') ? true : false;

		global $post;
		$channel_id = get_option('tourcms_channel_id');
		$api_key = get_option('tourcms_api_key');
		
		$tour_id = get_post_meta($post->ID, 'tour_id', true);
		$params = 'id='.$tour_id.'&show_options=1';
		$tourcms = new TourCMS();
		$tour = $tourcms->show_tour($tour_id, $channel_id, $params)->tour;

		if (!class_exists(KBC_XML_Parser)) {
			require_once(TOURCMS_ROOT.'/lib/xml-parser.php');
		}
		
		$tabs = KBC_XML_Parser::parse_file(dirname(__FILE__).'/switchbox/tabs.xml', true);
		$tablet_switchbox = new TourSwitchbox($tabs, $tour, 'tourcms-tablet');
		$mobile_switchbox = new TourSwitchbox($tabs, $tour, 'tourcms-mobile');
	
		$args = array(
			'tour_id' => $tour_id,
			'user' => uniqid(),
			'display_month' => $this->get_display_month(),
			'rates' => $this->get_rates($tour->new_booking->people_selection->rate, $tour->options->option),			
			'tour_name'=> $tour->tour_name_long,
		);
		
		if ($tour->options->option) {
			$args['options'] = $this->get_options($tour->options->option);
		}
		
			
		
		wp_enqueue_style("choose_your_adventure_mobile_css", $plugin_url."/css/mobile-style.css", null, false, false);

		
		
		
		if (get_post_type() == MOBILE_TOUR_PAGE) {
			$args['tablet_booking_elements'] = $this->get_tablet_booking_elements($tour->longdesc, $tour->images->image->url_large, $tablet_switchbox->get_view());
			//$args['mobile_booking_elements'] = $this->get_tablet_booking_elements($tour->longdesc, $tour->images->image->url_large, $mobile_switchbox->get_view());			
		} else {
			$args['desktop_booking_elements'] = $this->get_desktop_booking_elements();		
		}

	
		$this->display($args);				
	}
	
	private function get_display_month() {
		$today = intval(date('d'));
		$year = date("Y");	
		$month = date("n");
		$days_in_month = cal_days_in_month(CAL_GREGORIAN, intval($month), intval($year));
		
		$return = '';
		
		if ($days_in_month != intval($today)) {
            $return .= '<input type="hidden" name="current_month" value="'.date("n", strtotime("now")).'">';
            $return .= '<input type="hidden" name="current_year" value="'.date("Y", strtotime("now")).'">';
            $return .= '<span id="sb-tour-month">'.date("F").' '.date("Y").'</span>';
        } else {
            $return .= '<input type="hidden" name="current_month" value="'.date("n", strtotime("+1 day")).'">';
            $return .= '<input type="hidden" name="current_year" value="'.date("Y", strtotime("+1 day")).'">';
            $return .= '<span id="sb-tour-month">';
            $return .= date("F", strtotime("+1 day"));
            $return .= ' '.date("Y", strtotime("+1 day"));
            $return .= '</span>';
        }
		return $return;
	}
	
	public function get_tablet_booking_elements($description, $image, $switcher) {
		$elements = '<div id="tablet-booking-elements">
            <img class="sb-tour-thumbnail" src="'.$image.'">
			<p class="sb-tour-description">'.$description.'</p>';
		$elements .= $switcher;
		$elements .= '</div>';
		return $elements;			
	}
	
	public function get_desktop_booking_elements() {
		global $post;
		$elements = '<div class="sb-divider"></div>';
        $elements .= '<div id="desktop-trip-info">';
		$elements .= '<div id="sb-special-information">';
		$elements .= get_post_meta($post->ID, 'special_information', true);	            
        $elements .= '</div></div>';
		return $elements;
	}
	
	public function get_rates($rates, $options = 0) {
		
		$return = '';
		foreach($rates as $rate) {
			$return .= '<div class="sb-tour-rates" id="'.$rate->label_1.'">';
  			$return .= '<p class="sb-tour-guests-label">'.$rate->label_1;
			$return .= ($rate->label_2) ? ' '.$rate->label_2.'</p>' : '</p>';
			$return .= '<input type="number" min="0" name="no_'.strtolower($rate->label_1).'" class="sb-guests-input sb-confirm-field" data-category="rate" data-kind="'.$rate->label_1.'" />';
        	$return .= '</div>';  
		}
		
		if ($options) {
			foreach ($options as $key=>$rate) {
				if ((string) $rate->group_title == 'Infants') {
					$return .= '<div class="sb-tour-rates" id="'.((string) $rate->option_name).'">';
					$return .= '<p class="sb-tour-guests-label">'.((string) $rate->option_name);
					$return .= ' 2 & under</p>';
					$return .= '<input type="number" min="0" name="no_'.strtolower(((string) $rate->option_name)).'" class="sb-guests-input sb-confirm-field" data-category="rate" data-kind="'.((string) $rate->option_name).'" />';
					$return .= '</div>';  
				}
			}
		}
		return $return;		
	}

	public function get_options($tour_options) {
	 	$i = 0; 
		$return = '';
		foreach ($tour_options as $key=>$option) {
			
			$display_price = round(floatval($option->from_price) / (floatval(floatval(get_option('tourcms_sales_tax')) / 100) + 1), 2);
			if ((string) $option->group_title == 'Infants') continue;
			$return .= '<tr class="sb-tour-upgrades-tr">';
			$return .= '<td class="sb-tour-upgrades-td">'.$option->option_name.': </td>';
			$return .= '<td class="sb-tour-upgrades-td sb-tour-price-td">US$'.$display_price.'</td>';	
			$return .= '<td class="sb-tour-upgrades-td">';
			$return .= 	'<fieldset name="sb-tour-option['.$i.']" class="sb-tour-option">';
			$return .= 		'<input type="number" name="option_number" id="option-number-field-'.$i.'" class="sb-confirm-field" data-category="option" data-kind="'.$option->option_name.'" min="0">';
			$return .= 		'<input type="hidden" name="option_kind" value="'.$option->option_name.'" id="option-kind-field-'.$i.'">';
			$return .= 		'<input type="hidden" name="option_rate" value="'.$display_price.'" id="option-rate-field-'.$i.'" >';
			$return .= 	'</fieldset>';
			$return .= '</td>';					
			$return .= '</tr>';		
			$i++;
		}
		return $return;
	}
	
	public function display($args) { 
		extract($args);
		?> 	
        <div id="sb-tour-widget-wrap">
            <div id="sb-tour-header">
                <h4 class="sb-tour-h4"><?php echo $tour_name; ?></h4>
   			</div>
            <!-- Tablet Booking Elements -->
           	<?php echo $mobile_booking_elements; ?>	
            <!-- / -->
            <!-- Tablet Booking Elements -->
           	<?php echo $tablet_booking_elements; ?>	
            <!-- / -->

            <!--Begin Tour Form -->
            <form id="sb-tour-form" action="" method="post">
            <input name="callback" type="hidden" value="start_booking_engine">
            <input name="tour_id" type="hidden" value="<?php echo $tour_id; ?>">
            <input name="user_id" type="hidden" value="<?php echo $user; ?>">
            <div id="sb-tour-pick-a-date-wrapper">
                <div id="sb-tour-calendar">
                  
                    <div id="sb-tour-head">
                        <button id="sb-tour-back-one" class="back-one sb-tour-button" disabled>&larr;</button>
                        <?php echo $display_month; ?>
                        <button id="sb-tour-forward-one" class="forward-one sb-tour-button">&rarr;</button>
                    </div>
                   
                    <div id="tourcms-sidebar-table" class="tourcms-live-calendar"></div>
                </div>
                <ul class="availability-key">
                    <li class="a-key-li">Selected<div id="selected-key"></div></li>
                    <li class="a-key-li">Available<div id="available-key"></div></li>
                    <li class="a-key-li">Unavailable<div id="unavailable-key"></div></li>                   	
                </ul>
                <div class="sb-tour-activity-date-wrap">
                    <p class="sb-tour-p" id="activity-date-lb">Activity Date</p>
                    <input type="text" id="sb-tour-activity-date-field" name="activity_date" class="sb-confirm-field" disabled />
                </div>
            </div>
            
            <div class="sb-divider"></div>
            
            <div class="sb-rates">
                <?php echo $rates; ?>
            </div>            
            
            <div class="sb-divider"></div>
            
            <?php if($options) { ?>
            	<h5 class="confirm-booking-h5">Available Upgrades</h5>
            
                
                <table id="sb-available-upgrades">
                    <tbody>
                       <?php echo $options; ?>
                    </tbody> 
                </table>
            	<div class="sb-divider"></div>
			<?php } ?>
            
            
            
            <div id="sb-tour-promo-code">
                <p id="sb-promo-label">Promotional Code</p>
                <input type="text" name="promo_code" id="promo-code-input" class="sb-confirm-field" />
            </div>
            <div id="sb-tour-savings-box">
                <p id="sb-tour-you-saved-text">                    	
                </p>
            </div>
            <div class="sb-divider"></div>
            
            <div id="tourcms-totals">
            
            </div>
                
            <div id="sb-tour-submit-div">
                <button id="sb-submit">
                	<span id="sb-tour-submit-text">BOOK NOW</span>
                	<div id="sb-tour-spinning-loader"></div>
                </button>
            </div>
         	<?php wp_nonce_field('tourcms_checkout', '_tourcms_sidebar_nonce', true, true); ?>            
            </form>
            <?php echo $desktop_booking_elements; ?>
            <!-- End Tour Form -->
    	</div> <?php
	}
}