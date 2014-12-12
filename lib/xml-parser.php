<?php

class KBC_XML_Recursion_Helper {

	public $element;
	public $is_type_array_element;
	public $is_type_array;
	private $key;

	function __construct(&$element, $key, $is_array) {
		$this->is_type_array_element = $is_array;
		$this->element = &$element;
		$this->key = $key;
	}
	
	function &get_node() {
		if ($this->is_type_array_element) {
			$next_element = &$this->element[];
			return $next_element;
		} else {
			return $this->element[$this->key];		
		}
	}

	function process_node_value(&$value) {
		if ($value == 'true') {
			$value = true;					
		} elseif ($value == 'false') {
			$value = false;							
		} else {
			$value = $value;
		}
	}
	
	function set_node($value) {
		$this->process_node_value($value);
		
		if ($this->is_type_array_element == true) {
			$this->element[] = $value;	
		} else {
			$this->element[$this->key] = $value;			
		}
	}
	
	function parse_attributes($attributes, &$type, &$value, &$tag, &$index, $nodes) {
		if ($attributes['TYPE'] == 'array') {
			$this->is_type_array = true;		
		} 
		/*
		elseif ($attributes['TYPE'] == 'pair') {
			echo 'Index: '.$index.'<br>';
			echo 'Type: ';
			print_r($type);
			echo '<br>';
			echo 'Value: ';
			print_r($value);
			echo '<br>';
			echo 'Tag: ';
			print_r($tag);
			echo '<br>';
			print_r($nodes);
			exit;
			$i += 4;
			$type = 'complete';
			
			if ($attributes['FORMAT'] == 'range') {
				$value =  $nodes[$i + 1]['value']
			}
		}
		*/
	}
	
	
}

class KBC_XML_Parser {
	
	 static function recursive_parse(&$i, $nodes, &$element, $is_array = false) {
		
		$i++;					
		
		while($nodes[$i]['type'] != 'close') {		
			//echo 'Element: ';
			//print_r($element);
			//echo '<br>';
			
			$type = $nodes[$i]['type'];
			$value = array_key_exists('value', $nodes[$i]) ? $nodes[$i]['value'] : "";
			$tag = array_key_exists('tag', $nodes[$i]) ? $nodes[$i]['tag'] : "";			
			$attributes = array_key_exists('attributes', $nodes[$i]) ? $nodes[$i]['attributes'] : array("TYPE" => "");
				
			$helper = new KBC_XML_Recursion_Helper($element, $tag, $is_array);
			$helper->parse_attributes($attributes, $type, $value, $tag, $i, $nodes);
				
			
			if ($type == 'complete') {
				$helper->set_node($value);								

				//echo 'Completed Node('.$nodes[$i]['tag'].') Value: ';
				//print_r($element[$nodes[$i]['tag']]);
				//echo '<br>';
			} elseif ($type == 'open') {
				//echo 'Step into Node('.$nodes[$i]['tag'].')<br>';
				KBC_XML_Parser::recursive_parse($i, $nodes, $helper->get_node(), $helper->is_type_array);							
				
				/*
				if ($nodes[$i]['attributes']['TYPE'] == 'array') {		
				} else {
					KBC_XML_Parser::recursive_parse($i, $nodes, $helper->get_node());							
				}
				*/
			} elseif ($type == 'cdata' && $value = trim($value)) {
				$helper->set_node($value);
				//echo 'CDATA Node('.$nodes[$i]['tag'].')<br>';		
			}
			
			if ($i > count($nodes)) {
				$element = array_change_key_case($element, CASE_LOWER);
				return;
			}
			
			$i++;
			$element = $helper->element;	
		}
		//echo 'Step Out of Node('.$nodes[$i]['tag'].')<br><br>';
		$element = array_change_key_case($element, CASE_LOWER);
		//print_r($element);
		//echo '<br>';
		return;
	}
	
	public static function parse_file($path, $debug = false) {
		return KBC_XML_Parser::parse(simplexml_load_file($path), $debug);	
	}
	
	
	public static function parse($xml, $debug = false) {
		$parser = xml_parser_create();
		xml_parse_into_struct($parser, $xml->asXML(), $nodes, $pointers);
		xml_parser_free($parser);
		
		//print_r($nodes);
		$element = array();  
		KBC_XML_Parser::recursive_parse($i = 0, $nodes, $element);
		return $element;
	}
}

?>
