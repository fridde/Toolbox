<?php 
	
	namespace Fridde;
	
	class HTML extends \DOMDocument
	{
		
		private $html;
		public $title;
		public $head;
		public $body;
		const EMPTY_ELEMENTS = ["area","base","br","col","command","embed","hr","img","input","link","meta","param","source"];
		const STD_CSS = "stylesheet.css";
		public $includables;
		
		function __construct ()
		{
			parent::__construct();
			$args = func_get_args();
			$this->title = (isset($args[0]) ? $args[0] : "");
			$this->initialize();
		}
		
		private function initialize()
		{
			$this->preserveWhiteSpace = false;
			$this->html = $this->add($this, 'html');
			$this->head = $this->add($this->html, 'head');
			$this->body = $this->add($this->html, "body");
			$meta_attributes = array("http-equiv" => "Content-Type", "content" => "text/html; charset=UTF-8");
			$this->add($this->head, 'meta', "", $meta_attributes);
			$this->add($this->head, 'title', $this->title);
			$this->includables = $this->getIncludables();
			if(is_readable(self::STD_CSS)) {
				$this->addCss(self::STD_CSS);
			}
		}
		/**
			* [Summary].
			*
			* [Description]
			
			* @param [Type] $[Name] [Argument description]
			*
			* @return [type] [name] [description]
		*/ 
		public function render($echo = true)
		{
			$prequel = "<!DOCTYPE html>\n";
			$this->formatOutput = true;
			$output = $this->saveHTML();
			
			if($echo){
				echo $prequel. $output;
			} 
			else {
				return $prequel . $output;
			}
		}
		/**
			* [Summary].
			*
			* [Description]
			
			* @param [Type] $[Name] [Argument description]
			*
			* @return [type] [name] [description]
		*/ 
		
		
		public function getIncludables(){
			
			$includables = false;
			$file_name = "includables.ini";
			if(is_readable($file_name)){
				$includables = parse_ini_file($file_name, true);
			}
			return $includables;
		}
		/**
			* [Summary].
			*
			* [Description]
			
			* @param [Type] $[Name] [Argument description]
			*
			* @return [type] [name] [description]
		*/ 
		public function prepareForExtraction($default_array, $args) {
			
			$args_names = array_keys($default_array);
			/* check if the arguments are given as a single array with keys corresponding to the keys of $default_array, e.g
			myFunction(["class" => "redClass", "id" => "mainFrame"]) */
			if(count($args) == 1 && count(array_intersect_key($default_array, $args)) > 0) {
				$return_array = array_merge($default_array, $args[0]);
			} 
			else { // args is a numerical array that follows the order of the default array
				$return_array = $default_array;
				foreach($args as $key => $arg){
					$name = $args_names[$key];
					$return_array[$name] = $arg;
				}
			}
			return $return_array;
		}
		/**
			* Add a HTML-element to another element(node).
			*
			* [Description]
			
			* @param Node $node The node to attach this element to
			* @param string $tag The tag to use for the element OR an array of elements each in turn build as id => [tag, content, attributes]
			* @param string $content The content for the element.
			* @param array $attributes The attributes given as "attribute_name" => "attribute_value" OR just as "attribute_name" if the attribute doesn't need a value, e.g. "hidden"
			*
			* @return [type] [name] [description]
		*/ 
		
		public function create()
		{
			$def = ["tag" => null, "content" => "", "atts" => array(), "return_as_array" => false];
			extract($this->prepareForExtraction($def, func_get_args()));
			
			$is_single_element = is_string($tag);
			if($is_single_element){
				$element_array = [[$tag, $content, $atts]];
			} 
			else if(is_string(reset($tag))){
				$element_array = [$tag];
			}
			/* $tag consists of an array of elements that each in turn consist of [tag, content, atts]. 
			The respective keys for each array (if given) are the respective id's of each element */
			else {
				$element_array = $tag;
			}
			
			$return_array = array();
			foreach($element_array as $key => $element_parts){
				
				$tag = (isset($element_parts["tag"]) ? $element_parts["tag"] : $element_parts[0]);
				$content = (isset($element_parts[1]) ? $element_parts[1] : "");
				$content = (isset($element_parts["content"]) ? $element_parts["content"] : $content);  // a value with a key takes precedence over a value that is in the right order
				$content = (in_array($tag, self::EMPTY_ELEMENTS) ? "" : $content); // if the tag belongs to the list of void/empty elements, the content is ignored
				$atts = (isset($element_parts[2]) ? $element_parts[2] : array());
				$atts = (isset($element_parts["atts"]) ? $element_parts["atts"] : $atts);
				
				if(!is_numeric($key)){
					$atts["id"] = $key;
				}
				if(trim($tag) == ""){
					throw new \Exception("The tag name can't be empty!");
				}
				$element = $this->createElement($tag, $content);
				
				/* this part is to enable a short-hand notation where the first element of $atts (attribute array) is 
					given as ["class", "id"]. In this case all other class- or id-attributes given in $atts
				are overwritten. */
				$first_att = reset($atts);
				if(is_array($first_att)){
					if(isset($first_att[0])){
						$old_class = (isset($atts["class"])) ? $atts["class"] . " " : "";
						$atts["class"] = $old_class . $first_att[0];
					}
					if(isset($first_att[1])){
						$atts["id"] = $first_att[1];
					}
					array_shift($atts);
				}
				//adding attributes to the element
				foreach($atts as $attribute_name => $attribute_value){
					
					if(is_numeric($attribute_name)){
						$attribute = $this->createAttribute($attribute_value);
						$element->appendChild($attribute);
					} 
					else if($attribute_value != ""){
						$attribute = $this->createAttribute($attribute_name);
						$attribute->value = $attribute_value;
						$element->appendChild($attribute);
					}
				}
				
				$return_array[$key] = $element;
			}
			if($return_as_array){
				return $return_array;
			} 
			else {
				return reset($return_array);
			}
			
		}
		/**
			* [Summary].
			*
			* [Description]
			
			* @param [Type] $[Name] [Argument description]
			*
			* @return [type] [name] [description]
		*/ 
		public function add()
		{
			$def = ["node" => null, "tag" => null, "content" => "", "atts" => array(), "first" => false];
			extract($this->prepareForExtraction($def, func_get_args()));
			
			$element_array = $this->create($tag, $content, $atts, true);
			$first_child = $node->firstChild;
			$return_array = array();
			
			foreach($element_array as $id => $element){
				if($first && isset($first_child)){
					$return_array[$id] = $node->insertBefore($element, $first_child);
				}
				else {
					$return_array[$id] = $node->appendChild($element);
				}
			}
			if(count($return_array) > 1){
				return $return_array;
			}
			else {
				return reset($return_array);
			}
		}
		
		/**
			* [Summary].
			*
			* [Description]
			
			* @param [Type] $[Name] [Argument description]
			*
			* @return [type] [name] [description]
		*/ 
		public function addFirst()
		{
			$def = ["node" => null, "tag" => null, "content" => "", "atts" => array()];
			extract($this->prepareForExtraction($def, func_get_args()));
			
			return $this->add($node, $tag, $content, $atts, true);
		}
		
		
		
		/**
			* SUMMARY OF add_hidden_input
			*
			* will add hidden input fields to a node. 
			*
			* @param NodeElement $node The node to add the fields to, presumably a form of some kind.
			* @param array $array The array has to have the names given as array-keys and the values given as array-values
			*
			* @return TYPE NAME DESCRIPTION
		*/
		public function addHiddenInput($node, $array)
		{
			foreach($array as $name => $value){
				$this->add($node, "input", "", array("hidden", "name" => $name, "value" => $value));
			}
		}
		
		
		/**
			* [Summary].
			*
			* [Description]
			
			* @param [Type] $[Name] [Argument description]
			*
			* @return [type] [name] [description]
		*/ 
		
		public function addLink()
		{
			$def = ["node" => null, "adress" => "", "content" => "", "atts" => array()];
			extract($this->prepareForExtraction($def, func_get_args()));
			
			if($adress != ""){
				$atts["href"] = $adress;
			}
			if($content == ""){
				$content = $adress;
			}
			$link = $this->add($node, "a", $content, $atts);
			return $link;
		}
		
		/**
			* [Summary].
			*
			* [Description]
			
			* @param [Type] $[Name] [Argument description]
			*
			* @return [type] [name] [description]
		*/ 
		public function addButton()
		{
			$def = ["node" => null, "content" => "", "type" => "button", "form_id" => "",
			"formaction" => "", "atts" => array()];
			extract($this->prepareForExtraction($def, func_get_args()));
			
			$atts["type"] = $type;
			if($form_id != ""){
				$atts["form"] = $form_id;
			}
			if($formaction != "" && $type == "submit"){
				$atts["formaction"] = $formaction;
			}
			$button = $this->add($node, "button", $content, $atts);
			return $button;
		}
		
		public function addDiv()
		{
			$def = ["node" => null, "class" => "", "atts" => array()];
			extract($this->prepareForExtraction($def, func_get_args()));
			
			$atts["class"] = (isset($atts["class"])) ? $atts["class"] : $class;
			
			$div = $this->add($node, "div", "", $atts);
			
			return $div;
		}
		
		/**
			* [Summary].
			*
			* [Description]
			
			* @param [Type] $[Name] [Argument description]
			*
			* @return [type] [name] [description]
		*/ 
		public function addForm() 
		{
			$def = ["node" => null, "id" => substr(uniqid(), 0, 5), "action" => "", "content" => "", "method" => "post", "atts" => array()];
			extract($this->prepareForExtraction($def, func_get_args()));
			
			$atts["method"] = $method;
			$atts["id"] = $id;
			if($action != ""){
				$atts["action"] = $action;
			}
			$form = $this->add($node, "form", $content, $atts);
			return $form;
		}
		
		/**
			* [Summary].
			*
			* [Description]
			
			* @param [Type] $[Name] [Argument description]
			*
			* @return [type] [name] [description]
		*/ 
		public function addList() 
		{
			$def = ["node" => null, "elements" => array(), "type" => "ul", "atts" => array()];
			extract($this->prepareForExtraction($def, func_get_args()));
			
			$list = $this->add($node, $type, "", $atts);
			$list_elements = array();
			foreach($elements as $element){
				$content = (is_array($element) ? $element[0] : $element);
				$atts = (is_array($element) ? $element[1] : array());
				$list_elements[] = $this->add($list, "li", $content, $atts);
			}
			return $list_elements;
		}
		/**
			* [Summary].
			*
			* [Description]
			
			* @param [Type] $[Name] [Argument description]
			*
			* @return [type] [name] [description]
		*/ 
		public function addIframe()
		{
			$def = ["node" => null, "src" => null, "atts" => array()];
			extract($this->prepareForExtraction($def, func_get_args()));
			
			$atts = array_merge($atts, ["src" => $src]);
			$iframe = $this->add($node, "iframe", "", $atts);
			return $iframe;
		} 
		
		public function addImg()
		{
			$def = ["node" => null, "src" => null, "atts" => array()];
			extract($this->prepareForExtraction($def, func_get_args()));
			
			$atts = array_merge($atts, ["src" => $src]);
			$img = $this->add($node, "img", "", $atts);
			return $img;
		} 
		
		/**
			* [Summary].
			*
			* [Description]
			
			* @param [Type] $[Name] [Argument description]
			*
			* @return [type] [name] [description]
		*/ 
		public function addInput()
		{
			$def = ["node" => null, "name" => null, "type" => "text", "atts" => array()];
			extract($this->prepareForExtraction($def, func_get_args()));
			
			$atts["type"] = $type;
			if($type != "submit"){
				$atts["name"] = $name;
			}
			else if (!isset($atts["value"])) {
				$atts["value"] = "Submit";
			}
			
			$input = $this->add($node, "input", "", $atts);
			return $input;
		} 
		/**
			* [Summary].
			*
			* [Description]
			
			* @param [Type] $[Name] [Argument description]
			*
			* @return [type] [name] [description]
		*/ 
		public function addSelect()
		{
			$def = ["node" => null, "name" => null, "options" => array() , "atts" => array()];
			extract($this->prepareForExtraction($def, func_get_args()));
			
			$atts["name"] = $name;
			$select = $this->add($node, "select", "", $atts);
			$select_array = array();
			foreach($options as $option_text => $option){
				if(is_array($option)){ 
					// given as [option_text, option_value, atts]. If option_value is an empty string, it is assumed to be equal option_text
					$option_text = $option[0];
					$option_value = (isset($option[1]) &&  $option[1] != "" ? $option[1] : $option_text);
					$option_atts = (isset($option[2]) ? $option[2] : array());
				}
				else {
					$option_text = $option_value = $option;
					$option_atts = array();
				}
				$option_atts["value"] = $option_value;
				
				$select_array[] = $this->add($select, "option", $option_text, $option_atts);
			}
			return $select_array;
		} 
		
		/**
			* [Summary].
			*
			* [Description]
			
			* @param [Type] $[Name] [Argument description]
			*
			* @return [type] [name] [description]
		*/ 
		public function addSingleCss()
		{
			$def = ["arg0" => null, "is_css_content" => false, "node" => null];
			extract($this->prepareForExtraction($def, func_get_args()));
			
			$includables = $this->includables;
			
			$is_local = isset($includables["css_local"][$arg0]);
			$is_remote = isset($includables["css_remote"][$arg0]);
			$is_file = substr($arg0, -4) == ".css";
			
			$file_name = false;
			$content = "";
			$node = (!$node ? $this->head : $node);
			
			if($is_local){
				$file_name = $includables["css_local"][$arg0];
			}
			else if($is_remote){
				$file_name = $includables["css_remote"][$arg0];
			}
			else if($is_file){
				$file_name = $arg0;
			}
			else if($is_css_content){ 
				$content = $arg0;
			}
			else {
				throw new \Exception("Given argument to addCss() is neither valid abbreviation given in includables.ini OR a valid css-file OR actual style content ");
			}
			
			if(!$is_css_content){
				$atts = ["rel" => "stylesheet", "type" => "text/css", "href" => $file_name];
				$tag = "link";
			}
			else {
				$atts = array();
				$tag = "style";
			}
			$this->add($node, $tag, $content, $atts);
		}
		
		/**
			* [Summary].
			*
			* [Description]
			
			* @param [Type] $[Name] [Argument description]
			*
			* @return [type] [name] [description]
		*/ 
		public function addCss()
		{
			$def = ["arg0" => null, "is_css_content" => false, "node" => null];
			extract($this->prepareForExtraction($def, func_get_args()));
			
			$css_array = array();
			if(is_array($arg0)){
				$css_array = $arg0;
			}
			else {
				$css_array = [[$arg0, $is_css_content, $node]];
			}
			foreach($css_array as $single_css){
				if(is_array($single_css)){
					$single_arg0 = $single_css[0];
					$single_is_css_content = (isset($single_css[1]) ? $single_css[1] : false);
					$single_node = (isset($single_css[2]) ? $single_css[2] : null);
				}
				else {
					$single_arg0 = $single_css;
					$single_is_css_content = false;
					$single_node = null;
				}
				$this->addSingleCss($single_arg0, $single_is_css_content, $single_node);
			}
		}
		/**
			* [Summary].
			*
			* [Description]
			
			* @param [Type] $[Name] [Argument description]
			*
			* @return [type] [name] [description]
		*/ 
		//file_name or abbreviation, is_verbatim, 
		public function addSingleJs()
		{
			$def = ["arg0" => null, "is_script_content" => false, "node" => null];
			extract($this->prepareForExtraction($def, func_get_args()));
			
			$includables = $this->includables;
			
			$is_local = isset($includables["js_local"][$arg0]);
			$is_remote = isset($includables["js_remote"][$arg0]);
			$is_file = substr($arg0, -3) == ".js";
			
			$file_name = false;
			$content = "";
			$atts = ["type" => "text/javascript"];
			$node = (!$node ? $this->head : $node);
			
			if($is_local){
				$file_name = $includables["js_local"][$arg0];
			}
			else if($is_remote){
				$file_name = $includables["js_remote"][$arg0];
			}
			else if($is_file){
				$file_name = $arg0;
			}
			else if($is_script_content){ 
				$content = $arg0;
			}
			else {
				throw new \Exception("Given argument to addJs() is neither valid abbreviation given in includables.ini OR a valid js-file OR actual script content ");
			}
			
			if(!$is_script_content){
				$atts["src"] = $file_name;
			}
			$this->add($node, "script", $content, $atts);
		}
		/**
			* [Summary].
			*
			* [Description]
			
			* @param [Type] $[Name] [Argument description]
			*
			* @return [type] [name] [description]
		*/ 
		public function addJs()
		{
			$def = ["arg0" => null, "is_script_content" => false, "node" => null];
			extract($this->prepareForExtraction($def, func_get_args()));
			
			$js_array = array();
			if(is_array($arg0)){
				$js_array = $arg0;
			}
			else {
				$js_array = [[$arg0, $is_script_content, $node]];
			}
			foreach($js_array as $single_js){
				if(is_array($single_js)){
					$single_arg0 = $single_js[0];
					$single_is_script_content = (isset($single_js[1]) ? $single_js[1] : false);
					$single_node = (isset($single_js[2]) ? $single_js[2] : null);
				}
				else {
					$single_arg0 = $single_js;
					$single_is_script_content = false;
					$single_node = null;
				}
				$this->addSingleJs($single_arg0, $single_is_script_content, $single_node);
			}
		}
		
		/**
			* SUMMARY OF addTable
			*
			* DESCRIPTION
			*
			* @param TYPE ($array, $id = "sortable", $class = "display stripe") ARGDESCRIPTION
			*
			* @return TYPE NAME DESCRIPTION
		*/
		public function addTable()
		{
			$def = ["node" => null, "array" => null, "headers" => array(), "atts" => array(), "options" => array()];
			extract($this->prepareForExtraction($def, func_get_args()));
			
			$table = $this->add($node, "table", "", $atts);
			
			if(count($array) == 0 || count(reset($array)) == 0) {
				throw new \Exception("Empty array given to addTable()");
			}
			
			// if the first row has no keys, we won't need a header
			if(count($headers) == count(reset($array))){
				$col_names = $headers;
			} 
			else if(count($headers) > 0){
				throw new \Exception("Number of headers given for addTable() should match number of columns in table");
			}
			else {
				$col_names = array_keys(reset($array));
			}
			
			$all_numeric = count(array_filter($col_names, function($k){return !is_numeric($k);})) == 0;
			array_walk($col_names, function(&$k){$k = (is_numeric($k)?"":$k);});
			
			if(!$all_numeric){
				$header = $this->add($table, "thead");
				$header_tr = $this->add($header, "tr");
				foreach($col_names as $col_name){
					$this->add($header_tr, "th", $col_name);
				}
			}
			$tbody = $this->add($table, "tbody");
			foreach($array as $row_key => $row){
				$tr = $this->add($tbody, "tr");
				foreach($row as $col_key => $cell){
					$this->add($tr, "td", $cell);
				}
			}
			return $table;
		}
		
		/**
			* [Summary].
			*
			* [Description]
			
			* @param [Type] $[Name] [Argument description]
			*
			* @return [type] [name] [description]
		*/ 
		public function addTextarea()
		{
			$def = ["node" => null, "content" => "", "name" => "", "rows" => "4", "cols" => "50", "atts" => array()];
			extract($this->prepareForExtraction($def, func_get_args()));
			
			if(is_array($content)){
				$atts["placeholder"] = reset($content);
				$content = "";
			}
			$atts["rows"] = $rows;
			$atts["cols"] = $cols;
			if($name != ""){
				$atts["name"] = $name;
			}
			
			$textarea = $this->add($node, "textarea", $content, $atts);
			return $textarea;
		} 
		
		/**
			* [Summary].
			*
			* [Description]
			
			* @param [Type] $[Name] [Argument description]
			*
			* @return [type] [name] [description]
		*/ 
		public function addFontAwesome()
		{
			
			$def = ["node" => null, "icon_name" => null, "size" => "", "atts" => array()];
			extract($this->prepareForExtraction($def, func_get_args()));
			
			$class = "fa fa-" . $icon_name;
			$class .= ($size != "" ? " fa-" . $size : "");
			$class .= (isset($atts["class"]) ? " " . $class : "");
			$atts["class"] = $class;
			
			$icon = $this->add($node, "i", "", $atts);
			return $icon;
		} 
		
		/**
			* [Summary].
			*
			* [Description] See https://api.jqueryui.com/theming/icons/
			
			* @param [Type] $[Name] [Argument description]
			*
			* @return [type] [name] [description]
		*/ 
		public function addUicon()
		{
			$def = ["node" => null, "icon_name" => null, "atts" => array()];
			extract($this->prepareForExtraction($def, func_get_args()));
			
			$class = "ui-icon ui-icon-" . $icon_name;
			$class .= (isset($atts["class"]) ? " " . $class : "");
			$atts["class"] = $class;
			
			$icon = $this->add($node, "span", "", $atts);
			return $icon;
		} 
		/**
			* SUMMARY OF create_bootstrap_navbar
			*
			* DESCRIPTION
			*
			* @param TYPE ($nav_args) ARGDESCRIPTION
			*
			* @return TYPE NAME DESCRIPTION
		*/
		
		public function addBsNav()
		{
			/* will return an array with a the matching arguments for a bootstrap-navbar
				the incoming arguments should be given as following
				0: (string) type of navbar. Possible types: "" (for default), fixed (for fixed header)
				$param array link_array: in the form of "Name to Show" => "link to lead to"
				If a menu-item should have a dropdown instead, build a recursive array, e.g. array("Homepage" => "index.html", "Topics" => array("Cars" => "cars.html", "Horses" => "horses.html"), "About me" => "about.html")
				If your navbar should contain a left and right menu, the link-array should contain exactly two arrays with the keys given as LEFT and RIGHT
				2: (string) id of the navbar
				3: (array) header of the site given as a double
			*/
			
			$def = ["link_array" => array(), "header" => array(), "type" => "fixed-top", "atts" => array(), "node" => $this->body, "first" => true];
			extract($this->prepareForExtraction($def, func_get_args()));
			
			$nav_type_classes = ["fixed-top" => "fixed-top"];
			$nav_class = "navbar";
			$nav_class .= (isset($nav_type_classes[$type]) ? " navbar-" . $nav_type_classes[$type] : "");
			
			$atts["class"] = (isset($atts["class"]) ? $atts["class"] . " " . $nav_class : $nav_class);
			
			$nav = $this->add($node, "nav", "", $atts, $first);
			$container = $this->add($nav, "div", "", ["class" => "container-fluid"]);
			
			if(count($header) == 1){
				list($display_name, $link) = each($header);
				$header = $this->add($container, "div", "", ["class" => "navbar-header"]);
				$header_link = $this->add($header, "a", $display_name, ["class" => "navbar-brand", "href" => $link]);
			}
			
			if(!(isset($link_array["LEFT"]) && isset($link_array["RIGHT"]))){
				// e.g. [["text1" => "link1", "text2" => "link2"]]
				if(count($link_array) == 1 && is_array(reset($link_array))){
					$link_array = ["LEFT" => reset($link_array)];
				}
				// e.g.["text1" => "link1", "text2" => "link2"]
				else {
					$link_array = ["LEFT" => $link_array];
				}
			}
			
			foreach($link_array as $side => $linkList){
				$ul_atts["class"] = "nav navbar-nav";
				$ul_atts["class"] .= ($side == "RIGHT" ? " navbar-right" : "");
				$ul = $this->add($container, "ul", "", $ul_atts);
				
				foreach($linkList as $show_name => $link){
					if(is_array($link)){
						$li = $this->add($ul, "li", "", ["class" => "dropdown"]);
						$a = $this->addLink($li, "#", $show_name, ["class" => "dropdown-toggle", "data-toggle" => "dropdown"]);
						$this->add($a, "span", "", ["class" => "caret"]);
						$dropdown_ul = $this->add($li, "ul", "", ["class" => "dropdown-menu"]);
						foreach($link as $dd_show_name => $dd_link){
							$dd_li = $this->add($dropdown_ul, "li");
							$this->addLink($dd_li, $dd_link, $dd_show_name);
						}
					}
					else {
						$li = $this->add($ul, "li");
						$this->addLink($li, $link, $show_name);
					}
				}
			}
			
			return $nav;
		}
		
		
		
		/**
			* SUMMARY OF create_bootstrap_tabs
			*
			* DESCRIPTION
			*
			* @param Node $node The node to attach the tabs to.
			* @param array $tab_array The array of tabs given in the format [id_1 => tab_title_1, id_2 => tab_title_2, ...] 
			*
			* @return array $node_array An array containing of the created id-prefix (to ensure uniqueness) and the tab-nodes in the format [first_id => first_node, second_id => second_node]
		*/
		
		public function addBsTabs()
		{
			$def = ["node" => null, "tab_array" => array(), "atts" => array()];
			extract($this->prepareForExtraction($def, func_get_args()));
			
			/* we have to ensure that every tab has a unique id. this piece converts $tab_array from
				["tab1_title", "tab2_title", "tab3_title"]
				to
			[["tab1_5fg", "tab1_title"][["tab2_5fg", "tab2_title"]][["tab3_5fg", "tab3_title"]]]*/
			$tab_id_prefix = "tab_" . rand(0,999) . "_";
			
			array_unshift($atts, ["nav nav-tabs"]);
			$ul = $this->add($node, "ul", "", $atts);
			$tab_container = $this->add($node, "div", "", [["tab-content"]]);
			$return_array = array();
			
			$i = 0;
			foreach($tab_array as $tab_id_suffix => $tab_title){
				$div_class = "tab-pane fade";
				$li_class = "";
				$id = $tab_id_prefix . $tab_id_suffix ;
				$title = $tab_title;
				if($i == 0){
					$li_class .= "active";
					$div_class .= " in active";
				}
				$li = $this->add($ul, "li", "", [[$li_class]]);
				$a_atts = ["data-toggle" => "tab", "href" => "#" . $id];
				$this->add($li, "a", $title, $a_atts);
				
				$return_array[$id] = $this->add($tab_container, "div", "", [[$div_class, $id]]);
				$i++;
			}
			array_unshift($return_array, $tab_id_prefix);
			
			return $return_array;
		}
		
		/**
			* [Summary].
			*
			* [Description]
			
			* @param [Type] $[Name] [Argument description]
			*
			* @return [type] [name] [description]
		*/
		public function addBsModal()
		{
			
			/*
				modal
				--dialog
				----content
				------header 
				--------close-button
				--------title
				------body
				------footer
				--------close-button
				--------save-button
			*/
			$def = ["node" => null, "options" => array(), "atts" => array()];
			extract($this->prepareForExtraction($def, func_get_args()));
			$possible_options = ["title" => "", "id" => "login_modal", "button_texts" => ["Close", "Save changes"]];
			$options = array_merge($possible_options, $options);
			
			$modal = $this->add($node, "div", "", [["modal fade", $options["id"]], "tabindex" => "-1", "role" => "dialog", "aria-hidden" => "true"]);
			$dialog = $this->add($modal, "div", "", [["modal-dialog"], "role" => "document"]);
			$content = $this->add($dialog, "div", "", [["modal-content"]]);
			$header = $this->add($content, "div", "", [["modal-header"]]);
			$body = $this->add($content, "div", "", [["modal-body"]]);
			$footer = $this->add($content, "div", "", [["modal-footer"]]);
			
			$close_button = $this->add($header, "button", "", [["close"], "data-dismiss" => "modal", "aria-label" => "Close"]);
			$this->add($close_button, "span", "&times;", ["aria-hidden" => "true"]);
			$this->add($header, "h4", $options["title"], [["modal-title"]]);
			
			$this->add($footer, "button", $options["button_texts"][0], [["btn btn-secondary"], "data-dismiss" => "modal"]);
			$this->add($footer, "button", $options["button_texts"][1], [["btn btn-primary", $options["id"]. "_submit"]]);
			
			return ["modal" => $modal, "header" => $header, "body" => $body, "footer" => $footer];
		}
		
		public static function partition($array, $columns = 2, $horizontal = true)
		{
			if($horizontal){
				$partition = array();
				$i = 0;
				foreach($array as $key => $value){
					$partition[$i % $columns][$key] = $value;
					$i++;
				}
			}
			else {
				$partition = array_chunk($array, ceil(count($array)/$columns), true);
			}
			return $partition;
		}
	}
