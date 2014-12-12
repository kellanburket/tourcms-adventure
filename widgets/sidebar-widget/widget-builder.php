<?php

class WidgetBuilder extends WP_Widget {

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
	
}

?>