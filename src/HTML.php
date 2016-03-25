<?php 
	
	namespace Fridde;
	
	class HTML extends \DOMDocument{
		
		private $html;
		public $title;
		public $head;
		public $body;
		const EMPTY_ELEMENTS = ["area","base","br","col","command","embed","hr","img","input","link","meta","param","source"];
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
			if(count($args) == 1 && is_array($args[0]) && !isset($args[0][0])) {
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
		
		public function add()
		{
			/* $node, $tag, $content, $attributes
			*/
			$def = ["node" => null, "tag" => null, "content" => "", "atts" => array()];
			extract($this->prepareForExtraction($def, func_get_args()));
			
			$element_array = array();
			if(is_string($tag)){
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
			foreach($element_array as $key => $element){
				
				$tag = (isset($element["tag"]) ? $element["tag"] : $element[0]);
				$content = (isset($element[1]) ? $element[1] : "");
				$content = (isset($element["content"]) ? $element["content"] : $content);  // a value with a key takes precedence over a value that is in the right order
				$content = (in_array($tag, self::EMPTY_ELEMENTS) ? "" : $content); // if the tag belongs to the list of void/empty elements, the content is ignored
				$atts = (isset($element[2]) ? $element[2] : array());
				$atts = (isset($element["atts"]) ? $element["atts"] : $atts);
				
				if(!is_numeric($key)){
					$atts["id"] = $key;
				}
				$element = $this->createElement($tag, $content);
				
				//adding attributes to the element
				foreach($atts as $attribute_name => $attribute_value){
					if(is_numeric($attribute_name)){
						$attribute = $this->createAttribute($attribute_value);
					} 
					else {
						$attribute = $this->createAttribute($attribute_name);
						$attribute->value = $attribute_value;
					}
					$element->appendChild($attribute);
				}
				$return_array[$key] = $node->appendChild($element);
			}
			if(count($return_array) == 1){
				return $return_array[0];
			} 
			return $return_array;
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
		
		public function create_bootstrap_navbar()
		{
			/* will return an array with a the matching arguments for a bootstrap-navbar
				the incoming arguments should be given as following
				0: (string) type of navbar. Possible types: "" (for default), fixed (for fixed header)
				1: (array) links: in the form of "Name to Show" => "link to lead to"
				If a menu-item should have a dropdown instead, build a recursive array, e.g. array("Homepage" => "index.html", "Topics" => array("Cars" => "cars.html", "Horses" => "horses.html"), "About me" => "about.html")
				If your navbar should contain a left and right menu, the link-array should contain exactly two arrays with the keys given as LEFT and RIGHT
				2: (string) id of the navbar
				3: (array) header of the site given as a double
			*/
			
			$def = ["node" => null, "icon_name" => null, "atts" => array()];
			extract($this->prepareForExtraction($def, func_get_args()));
			
			$type = $nav_args[0];
			$links = $nav_args[1];
			$id = $nav_args[2];
			$headerArray = $nav_args[3];
			$attributes = array("class" => "navbar");
			if($id){$attributes["id"] = $id;}
			
			switch($type){
				case "fixed":
				$attributes["class"] .= " navbar-default navbar-fixed-top";
				break;
				
				default:
				$attributes["class"] .= " navbar-default";
				break;
			}
			
			$header = "";
			if($headerArray){
				$displayName = array_keys($headerArray);
				$displayName = $displayName[0];
				$link = $headerArray[$displayName];
				$header .= tag("a", $displayName, array("href" => $link, "class" => "navbar-brand"));
			}
			$linkContent = array("LEFT" => "", "RIGHT" => "");
			if(!(count($links) == 2 && isset($links["LEFT"]) && isset($links["RIGHT"]))){
				$links = array("LEFT" => $links, "RIGHT" => array());
			}
			
			foreach($links as $side => $linkList){
				foreach($linkList as $showName => $link){
					if(gettype($link) == "array"){
						$dd_preText = tag("a", $showName . qtag("span", "" , "caret"), array("class" => "dropdown-toggle", "data-toggle"=> "dropdown", "href" => "#"));
						$dd_menu = "";
						foreach($link as $ddShowName => $dropdownListLink){
							$a = qtag("a", $ddShowName, $dropdownListLink);
							$l = tag("li", $a);
							$dd_menu .= $l;
						}
						$dd_list = qtag("ul", $dd_menu ,"dropdown-menu");
						$l = tag("li", $dd_preText . $dd_list, "dropdown");
						$linkContent[$side] .= $l;
					}
					else {
						$a = qtag("a", $showName, $link);
						$l = tag("li", $a);
						$linkContent[$side] .= $l;
					}
				}
			}
			
			
			$navbarContent = qtag("ul", $linkContent["LEFT"] , "nav navbar-nav");
			if($linkContent["RIGHT"] != ""){
				$navbarContent .= qtag("ul", $linkContent["RIGHT"], "nav navbar-nav navbar-right");
			}
			$div0_1 = qtag("div", $header, "navbar-header");
			$div0_2 = qtag("div", $navbarContent);
			$div0 = qtag("div", $div0_1 . $div0_2, "container-fluid");
			$content = $div0;
			$resultArray = array("content" => $content, "attributes" => $attributes);
			return $resultArray;
			
			
		}
		/**
			* SUMMARY OF create_bootstrap_tabs
			*
			* DESCRIPTION
			*
			* @param TYPE ($tab_args) ARGDESCRIPTION
			*
			* @return TYPE NAME DESCRIPTION
		*/
		
		public static function create_bootstrap_tabs($tab_args)
		{
			
			$type = $tab_args[0]; // yet unused
			$tabContent = $tab_args[1];
			$id = $tab_args[2];
			$attributes = array("class" => "container");
			if($id){$attributes["id"] = $id;}
			
			list($content, $ul, $contentDiv) = array_fill(0,20,"");
			$i = 0;
			$firstElement = get_element($tabContent);
			foreach($tabContent as $showName => $text){
				$i++;
				$tab_id = "tab_id_" . $i;
				$liAtts = array();
				$contentElementAtts = array("id" => $tab_id, "class" => "tab-pane fade");
				if($showName == $firstElement){
					$liAtts["class"] = "active";
					$contentElementAtts["class"] .= " in active";
				}
				$li = tag("a", $showName, array("data-toggle" => "tab", "href" => "#" . $tab_id));
				$ul .= tag("li", $li, $liAtts);
				
				$contentDiv .= tag("div", $text, $contentElementAtts);
			}
			$content .= qtag("ul", $ul, "nav nav-tabs");
			
			$content .= qtag("div", $contentDiv, "tab-content");
			
			$resultArray = array("content" => $content, "attributes" => $attributes);
			return $resultArray;
		}
	}
