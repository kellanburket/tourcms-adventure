<?php

class TourSwitchbox {
	
	private $view;
	private $class;
	
	function __construct($box, $data, $class) {
		$this->class = $class;
		$this->build_view($box['specs'], $box['tabs'], $data);
	}
	
	function parse_field($field, $class, $data) {
		$class_string = $class;
		if (is_array($field)) {
			if ($field['first']) {
				if ($field['format'] == 'range') {
					$first = $data->$field['first'];
					$second = $data->$field['first'];				
					$text_string = $first.'&ndash;'.$second; 
				}				
			}
			if ($field['class']) {
				$class_string = $field['class'];
			}
		
			if ($field['handle']) {
				$text_string = $data->$field['handle'];			
			}
		} else {
			$text_string = $data->$field;
		}

		return '<p class="'.$class_string.'">'.$text_string.'</p>';
	}
	
	function build_view($specs, $tabs, $data) {
		$this->view = '<div class="'.$specs['switchbox_class'].' '.$this->class.'">';
		$tabs_view = '<ul class="'.$specs['tabs_class'].' '.$this->class.'">';
		$frame_view = '<div class="arrow-left"></div>
			<div class="'.$specs['frame_class'].'">';
        
        for($i = 0; $i < count($tabs); $i++) {
			$tabs_view .= '<li class="'.$specs['tab_class'].' '.$this->class.'" id="'.$specs['tab_id'].'-'.$i.'">'.$tabs[$i]['name'].'</li>';
			$frame_view .= '<div class="'.$specs['panel_class'].' '.$this->class.'" id="'.$specs['panel_id'].'-'.$i.'">';		
			foreach($tabs[$i]['contents'] as $content) {
				$frame_view .= '<p class="'.$specs['head_class'].'"><strong>'.$content['field_head'].'</strong></p>';
				$frame_view .= $this->parse_field($content['field_handle'], $specs['field_class'], $data);	
			}
			$frame_view .= '</div>';
		}
		
		$tabs_view .= '</ul>';
		$frame_view .= '</div>';
		$this->view .= $tabs_view.$frame_view.'</div>';
	}

	function get_view() {
		return $this->view;
	}
}

?>