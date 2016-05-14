<?php
	namespace Fridde;
	
	/***************************************************************************************************
		/* AUTHOR
        Christian Vigh, 01/2010.
		API: http://www.phpclasses.org/package/9413-PHP-Load-and-edit-configuration-INI-format-files.html
		
		// Instanciate an IniFile object for file example.ini
		$inifile 	=  IniFile::LoadFromFile ( 'example.ini' ) ;
		
		// Note that you can specify a default value if the parameter is not defined
		$listen 	=  $inifile->getKey ( 'Network', 'Listen', '127.0.0.0' ) ;
		$port 		=  $inifile->getKey ( 'Network', 'Port' ) ;
		
		$inifile->setKey ( 'Results', 'LastUpdate', date ( 'Y/m/d H:i:s' ) ) ;
		$inifile->setKey ( 'Results', 'Status', 0 ) ;
		
		$inifile->save ( ) ;
		
		IniFile class -
        .INI file management. This class tries as much as possible to preserve the original
        text formatting including comments when data is loaded from an existing .INI file.
        This means that if you use the save() method to save .INI file contents, the generated
        file will have exactly the same contents as the original file (except for the
        modifications you made between loading and saving), and comments will be preserved.
		
        The differences with a real .INI file are the following :
        - Comments :
		. A mono-line comment can be introduced with the '#', ';' and '//' construct
		. A multi-line comment is enclosed between '/*' and '* /'.
		- You cannot put comments at the end of a section key definitions. Comments are
		considered to be part of the key value.
		- Section key definitions can be multiline. In that case, you have to use the '<<'
		construct as in the following example :
		
		mykey =<<
        some text for my key value
        (continued)
        END
		
		By default, a multiline key definition ends with a line containing only the word 'END'.
		You can specify however a different termination, after the '<<' construct :
		
		mykey =<<KEYEND
		some text for my key value
        (continued)
		KEYEND
		
		In both examples, the value of the 'mykey' key will be :
		
		"some text for my key value{EOL}(continued)"
		
		The "{EOL}" string will be either "\n" on Unix systems or "\r\n" on Windows systems.
		However, if the IniFile object has been created with an existing .INI file
		contents, the EOL string will be that of the initial file.
		
		Key definitions can be stored outside any section definition (ie, at the very beginning
		of the .INI file). In that case, they are placed in an section whose name is the
		empty string ("").
		
	===========================================================================================*/
	class  IniFile
	{
		// Ini file path
		public $File = null;
		// Reformat on save (align definitions)
		private $AlignmentOption = self::ALIGN_NONE;
		// Ini file items
		private $Items = array();
		// EOL type
		private $CRLF;
		private $EOL;
        // Dirty flag
        private $Dirty = false;
		// Separator between a key and a value
		public $Separator = '=';
		
		// Ini entry types
		const   INI_COMMENT = 'any';        // Comment or spaces
		const    INI_SECTION = 'section';        // Section name
		const    INI_ENTRY = 'entry';        // Section entry
		
		// Load disposition option
		const    LOAD_ANY = 0;            // The .ini file is loaded if it exists, or will be created
		const    LOAD_EXISTING = 1;            // The .ini file is loaded. If it does not exist, an error will occur
		const    LOAD_NEW = 2;            // The .ini file is recreated, whether existing or not
		
		// Key definitions alignment options
		const    ALIGN_NONE = 0;            // No alignment
		const    ALIGN_SECTION = 1;            // Aligns the equal signs within a section only
		const   ALIGN_FILE = 2;            // Aligns the equal signs within the whole file
		
		/*********************************************************************************************/
		/*********************************************************************************************/
		/*********************************************************************************************/
		/******                                                                                 ******/
		/******                          CONSTRUCTOR & MAGIC METHODS                            ******/
		/******                                                                                 ******/
		/*********************************************************************************************/
		/*********************************************************************************************/
		/*********************************************************************************************/
		
		// Constructor : does nothing. A IniFile object must be created by using the LoadFromxxx methods
		public function __construct($separator = '=')
		{
			// Determine the current EOL string, depending on OS type.
			// This setting may be overriden if .INI file contents coming from a different OS are loaded.
			// Determine if we run under Windows or Unix
			
			if (!strncasecmp(php_uname('s'), 'windows', 7)) {
				$this->CRLF = true;
				$this->EOL = "\r\n";
			} 
			else {
				$this->CRLF = false;
				$this->EOL = "\n";
			}
			$this->Separator = $separator;
		}
		
		// Conversion to string : returns the contents of the .INI file
		public function __tostring()
		{
			return  $this->AsString();
		}
		
		//
		// __AddTextBlock -
		//	Adds a text block in the chain of .INI file contents. This can include comments but also,
		//	more simply, newline separators between a section name and the next line
		//
		private function __AddTextBlock($value)
		{
			if (strlen($value)) {
				$this->Items [] = array('type' => self::INI_COMMENT, 'value' => $value);
			}
			
			return  '';
		}
		
		//
		// __Compact -
		//	Called when elements from the $this->Items array are unset, to remove empty slots
		//
		private function __Compact()
		{
			$this->Items = array_values($this->Items);
		}
		
		//
		// __EOLReplace -
		//	Internally, end of line inidicators are stored as "\n", whatever the original .INI file format
		//	(Windows or Unix). This function restores the original EOL indicator.
		//
		private function __EOLReplace($value)
		{
			if ($this->CRLF) {
				$value = str_replace("\n", "\r\n", $value);
			}
			
			return  $value;
		}
		
		//
		// __FindKey -
		//	Searches for the specified key in the specified section. Returns the key index in the
		//  	$this->Items array, or false if key not found.
		//
		private function __FindKey($section, $key)
		{
			$section = $this->__NormalizeName($section);
			$key = $this->__NormalizeName($key);
			$index = $this->__FindSection($section);
			
			if ($index  ===  false) {
				return  false;
			}
			
			for ($i = $index + 1; $i < count($this->Items); ++$i) {
				$item = $this->Items [$i];
				
				if ($item [ 'type' ]  ==  self::INI_ENTRY  &&
				!strcasecmp($item [ 'name'], $key)) {
					return  $i;
				} 
				elseif ($item [ 'type' ]  ==  self::INI_SECTION) {
					break;
				}
			}
			
			return  false;
		}
		
		//
		// __FindSection -
		//	Searches for the specified section. Returns the section index in the $this->Items array,
		// 	or false if section not found.
		//
		private function __FindSection($name)
		{
			$name = $this->__NormalizeName($name);
			$index = 0;
			
			foreach ($this->Items  as  $item) {
				if ($item [ 'type' ]  ==  self::INI_SECTION  &&
				!strcasecmp($item [ 'value'], $name)) {
					return  $index;
				}
				
				++$index;
			}
			
			return  false;
		}
		
		//
		// __Load -
		//	Does the real job of parsing the input file or string contents.
		//	The '$file' parameter is used only when displaying error messages.
		//
		//	Notes :
		//	. If the input string contains duplicate section names, their contents will be merged
		//	. If the input string contains duplicate key names, subsequent key definitions will
		//	  override the original one.
		//
		private function __Load($contents, $file = null)
		{
			if (!$file) {
				$file = '(string)';
			}
			
			$key_value_separator = $this->Separator;
			
			$single_re = '/^
			(?P<name> [^ \t'.$key_value_separator.']+)
			(
			(?P<sep>  \s*'.$key_value_separator.'\s*)
			(?P<value> .*)
			)?
			$/isx';
			$multi_re = '/^'.
			'(?P<name> [^'.$key_value_separator.']+)'.
			'(?P<sep>  \s*'.$key_value_separator.'\s* \<\<\<? \s* (?P<word> [^\s]*) .* )'.
			'$/isx';
			
			// Check if we have a Unix or Windows file
			$crlf = (strpos($contents, "\r\n")  !==  false);
			
			if ($crlf) {
				// Windows file
				
				// The input string contains "\r\n" end-of-line characters ; replace them with a single "\n"
				// This is used to simplify input string parsing
				$this->CRLF = true;
				$this->EOL = "\r\n";
				$contents = str_replace("\r", '', $contents);
			}
			else {
				// Unix file
				
				$this->CRLF = false;
				$this->EOL = "\n";
			}
			
			// Some initializations
			$contents_length = strlen($contents);    // Length of the input string
			$text_value = '';                // Contains parsed comments and newlines, anything that is not part of a
			// section or key definitions
			$line = 0;                // Current line and char in line during parsing
			$char = 1;                // (used only when displaying error messages)
			$section = null;            // Current section name
			$errhead = '';                // Prefix string used when displaying error messages
			
			// Parse input string
			$i_start = 0;
			
			for ($i = 0; $i < $contents_length; ++$i) {
				// Get current and next chars
				$ch = substr($contents, $i, 1);
				$chnext = ($i + 1  <  $contents_length) ? substr($contents, $i + 1, 1) : null;
				
				// Complain if we have a construct other than '//' or '/*'
				if ($ch  ==  '/'  &&  $chnext  !=  '*'  &&  $chnext  !=  '/') {
					throw (new Exception("$errhead Comment character '/' not followed by single-line comment character ('/') or multiline comment character (*)."));
				}
				
				// Single-line comment
				if ($ch  ==  ';'  ||  $ch  ==  '#'  ||  !strncmp(substr($contents, $i, 2), '//', 2)) {
					// Locate the end of line
					$end = strpos($contents, "\n", $i);
					
					// If no end of line, this means that we are on the last line of the file
					if ($end  ===  false) {
						$text_value    .= substr($contents, $i);
						$i = $contents_length;
					}
                    // Otherwise extract the comment portion and update current char and current index accordingly
					else {
						$text_value    .= substr($contents, $i, $end - $i);
						$i = $end;
						$ch = substr($contents, $i, 1);
					}
				}
				// Multiline comment (note : nested multiline comments are not allowed)
				elseif (!strncmp(substr($contents, $i, 2), '/*', 2)) {
					$end = strpos($contents, '*/', $i);
					
					// no comment end found : complain
					if ($end  ===  false) {
						throw (new Exception("$errhead Unterminated multiline comment."));
					}
					// Otherwise extract the comment part. Consecutive comments are catenated
					else {
						$str = substr($contents, $i, $end - $i + 1);
						
						for ($j = 1; $j < strlen($str); ++$j) {
							$sch = $str [$j];
							$this->__SetPosition($file, $sch, $line, $char, $errhead);
						}
						
						$text_value    .= $str;
						$i = $end + 1;
						$ch = substr($contents, $i, 1);
					}
				}
                //  Opening bracket : this is a section name
				elseif ($ch  ==  '[') {
					// Add any comments that have been found so far
					$text_value = $this->__AddTextBlock($text_value);
					
					// Locate the closing bracket
					$end = strpos($contents, ']', $i);
					
					// Complain if not found
					if ($end  ===  false) {
						throw (new Exception("$errhead Unfinished section start."));
					}
					
					// Extract the section name
					$section = trim(substr($contents, $i + 1, $end - $i - 1));
					$section = $this->__NormalizeName($section);
					
					// If section does not already exist, append it to the Items array
					if ($this->__FindSection($section)  ===  false) {
						$this->Items [] = array('type' => self::INI_SECTION, 'value' => $section);
					}
					
					$i = $end + 1;
					
					// This code is for the case where "[section_name]" represents the last characters of the input string,
					// without a terminating newline
					if ($i  <  $contents_length) {
						$ch = substr($contents, $i, 1);
					}
				}
                // if we fall here, we are arriving on a key=value definition (if the line starts with spaces, they will be
                // included in the preceding comment block)
				elseif ($ch  >  ' ') {
					// If $section is null, this means that we have not encountered a section name so far.
					// In that case, we create the unnamed section to store the key definitions that are at the beginning
					// of the input string
					if ($section  ===  null) {
						$section = '';
						$this->Items [] = array('type' => self::INI_SECTION, 'value' => '');
					}
					
					// Regular expressions to match a single-line key definition, or the start of multiline one
					// Add any comments encountered so far
					$text_value = $this->__AddTextBlock($text_value);
					
					// Find the end of the key definition line
					// If no newline found, this means that the definition is at the last line of the file and
					// that it is not terminated with a newline character
					$nlpos = strpos($contents, "\n", $i);
					
					if ($nlpos  ===  false) {
						$nlpos = $contents_length;
					}
					
					// Extract the key definition and update the pointer accordingly
					$entry = substr($contents, $i, $nlpos - $i);
					$i = $nlpos;
					
					// Multiline definition
					if (preg_match($multi_re, $entry, $matches)) {
						$name = trim($matches [ 'name' ]);
						$separator = $matches [ 'sep' ];
						$word = trim($matches [ 'word' ]);
						$multiline = true;
						
						$closing = strpos($contents, "\n$word", $i);
						
						if ($closing  ==  false) {    // Closing delimiter is on end of file, no trailing newline
							throw (new Exception("$errhead Unterminated multiline entry '$name'."));
						}
						
						// Extract definition and update pointer to current char
						$value = substr($contents, $i + 1, $closing - $i - 1);
						$i     +=  strlen($value) + strlen($word) + 1;
						
						// Allow for spaces after the ending keyword
						while ($i  <  $contents_length  &&  $contents [$i]  !=  "\n"  &&  ctype_space($contents [$i])) {
							$i++;
						}
						
						if ($i  <  $contents_length) {        // Skip ending newline
							$i++;
						}
					}
					// Single-line definition
					elseif (preg_match($single_re, $entry, $matches)) {
						$name = $matches [ 'name' ];
						$separator = (isset($matches [ 'sep'   ])) ?  $matches [ 'sep'   ] : '=';
						$value = (isset($matches [ 'value' ])) ?  $matches [ 'value' ] : '';
						$word = '';
						$multiline = false;
					}
                    // Neither multiline, nor single-line : complain
                    else {
                        $this->__SetPosition($file, '', $line + 1, $char, $errhead);
                        throw (new Exception("$errhead Invalid entry :\n\t$entry."));
					}
					
					// Build the new item entry
					$vch = substr($value, 0, 1);
					
					if ($vch  ==  ' '  ||  $vch  ==  "\t") {
						$value = substr($value, 1);
					}
					
					$value = rtrim($value);
					
					$item = array(
					'type' => self::INI_ENTRY,
					'section' => $section,
					'name' => $this->__NormalizeName($name),
					'separator' => $separator,
					'value' => $value,
					'multiline' => $multiline,
					'word' => $word,
					);
					
					// Check if the key already exists
                    $key_index = $this->__FindKey($section, $name);
					
					// If yes, replace the original definition with the current one
                    if ($key_index  !==  false) {
                        $this->Items [ $key_index ] = $item;
					}
					// Otherwise, append it to our list of .INI file items
					else {
						$this->Items [] = $item;
					}
					
					// This code is for the case where the key definition represents the last characters of the input string,
					// without a terminating newline
					if ($i  <  $contents_length) {
						$ch = $contents [$i];
					}
				}
				
				// Update current line
				$delta = $i - $i_start;
				
				if ($delta) {
					$line_count = substr_count($contents, "\n", $i_start, $delta);
					$line        +=  $line_count;
				}
				
				$i_start = $i;
				
                // All possible .INI file element types have been processed ; update the error message prefix
				if ($i  <  $contents_length) {
					$text_value .= $ch;
					$this->__SetPosition($file, $ch, $line, $char, $errhead);
				}
			}
			
			// Create a new comment block if remaining comments or end of lines have been found
			$text_value = $this->__AddTextBlock($text_value);
		}
		
		//
		// __NormalizeName -
		//	Normalizes a section or key name : removes enclosing spaces and duplicate spaces within the name.
		//
		private function __NormalizeName($name)
		{
			$name = trim($name);
			$name = preg_replace('/\s+/', ' ', $name);
			
			return  $name;
		}
		
		//
		// __SetPosition -
		//	Sets the prefix of the error message to be displayed upon error.
		//
        private function __SetPosition($file, $ch, $line, &$char, &$errhead)
        {
            $file = basename($file);
			
            if ($ch  ==  "\n"  ||  $ch  ==  '') {
                ++$line;
                $char = 1;
			}
			else {
                $char++;
			}
			
            $errhead = "$file: line $line, char $char :";
		}
		
		
		/*
			
			NAME
			AlignDefinitions - Aligns the key definitions.
			
			PROTOTYPE
			$inifile->AlignDefinition ( $align_option = null ) ;
			
			DESCRIPTION
			Aligns the key definitions within .INI file sections, so that the equal signs before
			the key values remain aligned.
			
			PARAMETERS
			$align_option -
            Can be anyone of the following values :
            ALIGN_NONE -
			No alignment is performed, the initial key definitions are left as is.
			
            ALIGN_SECTION -
			Equal signs in key definitions are aligned on the same column, but
			only at the section level.
			
            ALIGN_FILE -
			Equal signs in key definitions are align on the same column at a file-level.
			
			null (default value) -
			The alignment option is taken from the value specified with the
			SetAlignment() method. The default value is ALIGN_NONE.
			
		--------------------------------------------------------------------------------------------*/
		public function AlignDefinitions($option = null)
		{
			// Get the actual alignment option
			if ($option  ===  null) {
				$option = $this->AlignmentOption;
			}
			
			if ($option  ==  self::ALIGN_NONE) {
				return;
			}
			
			// Get the sections list
			$sections = $this->GetSections();
			
			// If alignment at the file level, compute the maximum length of a key name
			if ($option  ==  self::ALIGN_FILE) {
				$maxlength = -1;
				$items = $this->getAllKeys();
				
				foreach ($items  as  $section => $keys) {
					foreach ($keys  as  $name => $value) {
						$length = strlen($name);
						
						if ($maxlength  <  $length) {
							$maxlength = $length;
						}
					}
				}
			}
			
			// Loop through each section of the .INI file
			foreach ($sections  as  $section) {
				$keys = $this->getKeys($section);
				
				// If alignment at the section level, compute the maximum length of a key name for that section
				if ($option  ==  self::ALIGN_SECTION) {
					$maxlength = -1;
					
					foreach ($keys  as  $key => $value) {
						$length = strlen($key);
						
						if ($length  >  $maxlength) {
							$maxlength = $length;
						}
					}
				}
				
				// Now, for each key in the current section, adjust the value of the 'separator' entry
				// to make sure that all equal signs will be aligned
				foreach ($keys  as  $key => $value) {
					$index = $this->__FindKey($section, $key);
					$item = &$this->Items [ $index ];
					$sep = preg_replace('/\s*=\s*/', ' = ', $item [ 'separator' ]);
					$length = $maxlength - strlen($item [ 'name' ]);
					
					if ($length  >  0) {
						$sep = str_repeat(' ', $length).$sep;
					}
					
					$item [ 'separator' ] = $sep;
				}
			}
		}
		
		/*-------------------------------------------------------------------------------------------
			
			NAME
            AppendFromArray,
            AppendFromFile,
            AppendFromString - Appends .INI definitions
			
			PROTOTYPE
            $status = $inifile->AppendFromArray  ( $array ) ;
            $status = $inifile->AppendFromFile   ( $file ) ;
            $status = $inifile->AppendFromString ( $string ) ;
			
			DESCRIPTION
            Appends definitions from the specified .INI file contents.
			
			PARAMETERS
            $array (array of strings) -
			.INI file definitions. The EOL string is determined by the OS version.
			
            $file (string) -
			File to be read.
			
			$string (string) -
            String to be read.
			
			RETURNS
            True if the operation was successful, false otherwise.
			
		--------------------------------------------------------------------------------------------*/
		public function AppendFromArray($array)
		{
			$this->Dirty = true;
			
			return  $this->__Load(implode($this->EOL, $array), null, $this->Separator);
		}
		
		public function AppendFromFile($file)
		{
			global        $Application;
			
			$inifile = $Application->GetAbsolutePath($file);
			
			if (file_exists($inifile)) {
				$this->Dirty = true;
				
				return  $this->__Load(file_get_contents($file), $file, $this->Separator);
			}
			else {
				return  false;
			}
		}
		
		public function AppendFromString($string)
		{
			$this->Dirty = true;
			
			return  $this->__Load($string, null, $this->Separator);
		}
		
		/*-------------------------------------------------------------------------------------------
			
			NAME
            AppendSection - Appends a section to the current .INI file.
			
			PROTOTYPE
            $inifile->AppendSection ( $section, $comment_before = null, $comment_after = null ) ;
			
			DESCRIPTION
            Appends a section to the .INI file.
			
			PARAMETERS
            $section (string) -
			Section name. If the section already exists, nothing happens.
			
            $comment_before, $comment_after (string) -
			Comments to be appended before and after the section name (optional).
			
		--------------------------------------------------------------------------------------------*/
		public function AppendSection($section, $comment_before = null, $comment_after = null)
		{
			$section = $this->__NormalizeName($name);
			$index = $this->__FindSection($section);
			
			if ($index  !==  false) {
				return;
			}
			
			if ($comment_before  !==  null) {
				$this->__AddTextBlock($comment_before."\n", false);
			}
			
			$this->Items [] = array('type' => self::INI_SECTION, 'value' => $section);
			
			$this->__AddTextBlock("\n", false);
			
			if ($comment_after  !==  null) {
				$this->__AddTextBlock($comment_after."\n", false);
			}
			
			$this->Dirty = true;
			
			return  true;
		}
		
		/*-------------------------------------------------------------------------------------------
			
			NAME
            AsString - Returns the .INI file as a string
			
			PROTOTYPE
            $text = $inifile->AsString ( $full = true ) ;
			
			DESCRIPTION
            Returns the contents of the .INI file as a string.
			
			PARAMETERS
            $full (boolean) -
			If true (the default), the initial .INI file comments will be included in the
			result.
			
		--------------------------------------------------------------------------------------------*/
        public function AsString($full = true)
        {
            $result = '';
            $this->AlignDefinitions();
			
			// Loop through items
			foreach ($this->Items  as  $item) {
				switch ($item [ 'type']) {
					// Comment block or newline separator
					case    self::INI_COMMENT :
                    if (!$full) {
                        break;
					}
					
                    $value = $item [ 'value' ];
					
                    if ($this->CRLF) {
                        $value = $this->__EOLReplace($value);
					}
					
                    $result .= $value;
                    break;
					
					// Section name
					case    self::INI_SECTION :
                    if ($item [ 'value' ]) {
                        $result .= '['.$item [ 'value' ].']';
					}
                    break;
					
					// Section entry. Handle multiline and single-line keys
					case    self::INI_ENTRY :
                    if ($item [ 'multiline']) {
                        $value = $this->__EOLReplace($item [ 'value' ]);
                        $result .= $item [ 'name' ].$item [ 'separator' ].$this->EOL.
						$value.$this->EOL.
						$item [ 'word' ];
					}
					else {
                        $result .= $item [ 'name' ].$item [ 'separator' ].$item [ 'value' ];
					}
                    break;
					
					default :
                    throw (new Exception("Unknow entry type '".$item [ 'type']."'."));
				}
			}
			
			// All done, return
			return  $result;
		}
		
		/*-------------------------------------------------------------------------------------------
			
			NAME
			ClearKey - Clears a key value.
			
			PROTOTYPE
			$inifile->ClearKey ( $section, $key ) ;
			
			DESCRIPTION
            Clears a key value. This is the equivalent of calling :
			
			$inifile->setKey ( $section, $key, "" ) ;
			
			PARAMETERS
            $section (string) -
			Section name.
			
            $key (string) -
			Key name.
			
			RETURN VALUE
            True if the section/key pair exists, false otherwise.
			
		--------------------------------------------------------------------------------------------*/
		public function ClearKey($section, $key)
		{
			$index = $this->__FindKey($section, $key);
			
			if ($index  !==  false) {
				$item = &$this->Items [ $index ];
				$item [ 'value' ] = '';
				$item [ 'multiline' ] = false;
				
				$this->Dirty = true;
				
				return  true;
			}
			else {
				return  false;
			}
		}
		
		/*-------------------------------------------------------------------------------------------
			
			NAME
            ClearSection - Clears a section contents.
			
			PROTOTYPE
            $status = $inifile->ClearSection ( $section ) ;
			
			DESCRIPTION
            Clears a section contents, without removing the section name from the .INI file.
			
			PARAMETERS
            $section (string) -
			Section name. Specify an empty string ("") for the unnamed global section.
			
			RETURN VALUE -
            true if the section exists, false otherwise.
			
		--------------------------------------------------------------------------------------------*/
		public function ClearSection($section)
		{
			// Find the section
			$index = $this->__FindSection($section) + 1;
			$count = count($this->Items);
			
			if ($index  ===  false) {
				return  false;
			}
			
			// Isolate section contents in the Items array
			$start = $index;
			$end = $count;
			
			for ($i = $index + 1; $i < $count; ++$i) {
				$item = $this->Items [$i];
				
				// End of section is either end of file or before beginning of next section
				if ($item [ 'type' ]  ==  self::INI_SECTION) {
					$end = $i;
					break;
				}
			}
			
			// If previous entry before section end is a comment, then let's say it belongs to the next item
			// so don't clear it
			if ($this->Items [ $end - 1 ] [ 'type' ]  ==  self::INI_COMMENT) {
				$end--;
			}
			
			// Remove section contents
			for ($i = $start; $i  <  $end; ++$i) {
				unset($this->Items [$i]);
			}
			
			// Compact the Items array
			$this->__Compact();
			$this->Dirty = true;
			
			// All done, return
			return  true;
		}
		
		/*-------------------------------------------------------------------------------------------
			
			NAME
			GetAlignment - Get the alignment value.
			
			PROTOTYPE
			$align = $inifile->GetAlignment ( ) ;
			
			DESCRIPTION
            Gets the section keys alignment option.
			
			RETURN VALUE
            IniFile::ALIGN_NONE -
			No alignment takes place. The .INI file will be written back as is.
			
            IniFile::ALIGN_SECTION -
			Individual keys within a section will be aligned according to the longest key
			name in the section.
			
			IniFile::ALIGN_FILE -
            Keys will be aligned at the .INI file level.
			
		--------------------------------------------------------------------------------------------*/
		public function GetAlignment()
		{
			return  $this->AlignmentOption;
		}
		
		/*-------------------------------------------------------------------------------------------
			
			NAME
			getAllKeys - Gets the whole .INI file contents.
			
			PROTOTYPE
            $result = $inifile->getAllKeys ( ) ;
			
			DESCRIPTION
            Returns all the sections and corresponding keys defined in the .INI file.
			
			RETURN VALUE
            The function returns an associative array corresponding to the sections defined in
            the .INI file ; the value of each item is itself an associative array whoses keys are
            key names and whose values are references to the actual key value.
			
            For example, given the following .INI file :
			
            ;---------------------------------------------
            Global = 1
			
            [General]
            save = true
            Upload = false
            ;---------------------------------------------
			
            the function will return :
			
            $result = array (
			"" => array ( 'Global' => 1 ),
			"General" => array ( 'save' => true, 'Upload' => false )
            )
			
			NOTES
            Since the key values are references to the actual value, you can directly modify a
            key's contents, as in the following example :
			
			$result [ 'General' ][ 'save' ] = false ;
			
            instead of calling :
			
			$inifile->setKey ( 'General', 'save', false ) ;
			
            Note however that in the first case, multiline values will not be correctly handled,
            so the direct modification of a value should only be used for single-line values.
            If you don't want to bother with single- or multi-line values, simply call the
            setKey() method.
			
		--------------------------------------------------------------------------------------------*/
		public function getAllKeys()
		{
			$result = array();
			$current = array();
			$section = '';
			
			// Loop through items
			foreach ($this->Items  as  $item) {
				// When a new section is encountered, add the keys collected so far to the previous section
				if ($item [ 'type' ]  ==  self::INI_SECTION) {
					if ($item [ 'value' ]  !=  $section) {
						$result [ $section ] = $current;
						$current = array();
						$section = $item [ 'value' ];
					}
				}
				// Otherwise, in case of a key, simply collect it
				elseif ($item [ 'type']  ==  self::INI_ENTRY) {
					$current [ $item [ 'name' ] ] = &$item [ 'value' ];
				}
			}
			
			// Don't forget the keys belonging to the very last section in the .INI file
			if (count($current)) {
				$result [ $section ] = $current;
			}
			
			// All done, return
			return  $result;
		}
		
		/*-------------------------------------------------------------------------------------------
			
			NAME
			GetBooleanKey - Gets a boolean key value.
			
			PROTOTYPE
			$value = $inifile->getKey ( $section, $key, $default = null ) ;
			
			DESCRIPTION
            Gets a boolean key from the specified section.
			
			PARAMETERS
            $section (string) -
			Section name.
			
            $key (string) -
			Key name.
			
			$default (any) -
			Default value if the key does not exist.
			
			RETURN VALUE
            The boolean value, or $default if the key does not exist.
			An invalid boolean value will generate an error.
			
			NOTES
			The GetxxxKey functions do not return a reference to the underlying value. The setKey()
			method must be used to modify the value, if needed.
			
		--------------------------------------------------------------------------------------------*/
		private static $BooleanValuesTable = array(
		'' => false,
		'on' => true,
		'yes' => true,
		'true' => true,
		'checked' => true,
		'1' => true,
		'off' => false,
		'no' => false,
		'false' => false,
		'unchecked' => false,
		'0' => false,
		);
		
		private static function BooleanValue($value)
		{
			// Trim any whitespace and convert to lowercase
			$value = trim(strtolower($value));
			
			// If the value is numeric, return either true (non-zero) or false (null value)
			if (is_numeric($value)) {
				return  ($value) ? true : false;
			}
			
			// Other cases : loop through the boolean value keywords to retrieve the appropriate boolean constant
			foreach (self::$BooleanValuesTable  as  $name => $constant) {
				if (!strcmp($name, $value)) {
					return  $constant;
				}
			}
			
			// Otherwise return false : this means that we failed to interpret the value as a boolean constant
			return;
		}
		
		public function GetBooleanKey($section, $key, $default = null)
		{
			$result = $default;
			
			$value = $this->getKey($section, $key, null);
			
			if ($value  !==  null  &&  ($bvalue = self::BooleanValue($value))  !==  null) {
				$result = $bvalue;
			}
			else {
				throw (new Exception("Invalid boolean value \"$value\" for the \"$key\" value of the [$section] section."));
			}
			
			return  $result;
		}
		
		/*-------------------------------------------------------------------------------------------
			
			NAME
			getKey - Gets a key value.
			
			PROTOTYPE
			$value = $inifile->getKey ( $section, $key, $default = null ) ;
			
			DESCRIPTION
            Gets a section key value.
			
			PARAMETERS
            $section (string) -
			Section name.
			
            $key (string) -
			Key name.
			
			$default (any) -
			Default value if the key does not exist.
			
			RETURN VALUE
            A reference to the key value, or $default if the key does not exist.
			
		--------------------------------------------------------------------------------------------*/
		public function &getKey($section, $key, $default = null)
		{
			$false = $default;
			$index = $this->__FindSection($section);
			
			if ($index  ===  false) {
				return $false;
			}
			
			$keys = (is_array($key)) ?  $key : array($key);
			
			foreach ($keys  as  $key) {
				$key = $this->__NormalizeName($key);
				
				for ($i = $index + 1; $i < count($this->Items); ++$i) {
					$item = &$this->Items [$i];
					
					if ($item [ 'type']  ==  self::INI_ENTRY  &&  !strcasecmp($item [ 'name' ], $key)) {
						return $item [ 'value' ];
					} 
					elseif ($item [ 'type' ]  ==  self::INI_SECTION) {
						break;
					}
				}
			}
			
			return $false;
		}
		
		/*-------------------------------------------------------------------------------------------
			
			NAME
			getKeys - Gets the key list for a given section.
			
			PROTOTYPE
			$keys = $inifile->getKeys ( $section, $keys_by_reference = true, $regex = null ) ;
			
			DESCRIPTION
            Gets the key names/values for the specified section.
			
			PARAMETERS
            $section (string) -
			Section name. Specify the empty string for the unnamed global section.
			
			$keys_by_reference (boolean) -
			When true, key values are returned by reference rather than by copy.
			
			$regex (string) -
			When specified, only the matching keys will be returned. The regular expression
            is to be given without any anchor or options.
			
			RETURN VALUE
            An associative array of section key/value pairs.
            The key value is a reference to the actual key value so you can modify it without
            calling the setKey() method. Note that in this case, the single or multiline state of
            the value will not be correctly handled. If you don't want to bother with single or
            multiline state of a value, use the setKey() method instead.
			
		--------------------------------------------------------------------------------------------*/
		public function getKeys($section, $keys_by_reference = true, $regex = null)
		{
			// Find the section
			$index = $this->__FindSection($section);
			
			if ($index  ===  false) {
				return  array();
			}
			
			$result = array();
			
			if ($regex) {
				$regex = "/^ $regex $/imsx";
			}
			
			// Section found, loop through items
			for ($i = $index + 1; $i < count($this->Items); ++$i) {
				$item = &$this->Items [$i];
				
				// Collect all keys...
				if ($item [ 'type' ]  ==  self::INI_ENTRY) {
					// If a regex is specified, exclude the non-matching keys
					if ($regex  &&  !preg_match($regex, $item [ 'name' ], $match)) {
						continue;
					}
					
					// Allow a 'name' item to be specified in the regexp
					if (isset($match [ 'name' ])) {
						$name = $match [ 'name' ];
					}
					else {
						$name = $item [ 'name' ];
					}
					
					if ($keys_by_reference) {
						$resulting_item = &$item [ 'value' ];
					}
					else {
						$resulting_item = $item [ 'value' ];
					}
					
					// Collect either a reference to the value or the value itself
					$result [ $name ] = $resulting_item;
				}
				// And stop if we encounter another section (or the end of file)
				elseif ($item [ 'type' ]  ==  self::INI_SECTION) {
					break;
				}
			}
			
			// All done, return
            return  $result;
		}
		
		/*-------------------------------------------------------------------------------------------
			
			NAME
            GetSections - Gets the section list.
			
			PROTOTYPE
            $sections = $inifile->GetSections ( $regex = null ) ;
			
			DESCRIPTION
            Returns a list of section names defined in the .INI file.
			
			PARAMETERS
			$regex (string) -
			If specified, only the section names matching the regular expression will be
			returned.
			Don't specify any anchor in the input string since the regular expression will
			be replaced by the following string :
			
			/^ \s* $regex \s* $/imsx
			
			RETURN VALUE
            An array of section names. If the global unnamed section contains keys, then this array
            will also have an empty string as element.
			When a regular expression is specified, the returned value is an associative array that
			contains the following entries :
			
			- name :
			Full section name.
			
			- match :
			Matched regular expression.
			
		--------------------------------------------------------------------------------------------*/
		public function GetSections($regex = null)
		{
			$result = array();
			
			if ($regex) {
				$regex = "/^ \s* $regex \s* $/imsx";
			}
			
			foreach ($this->Items  as  &$item) {
				if ($item [ 'type' ]   ==  self::INI_SECTION) {
					if ($regex) {
						$match = array();
						
						if (preg_match($regex, $item [ 'value' ], $match)) {
							$result    [] = array('name' => $item [ 'value' ], 'match' => $match);
						}
					}
					else {
						$result [] = $item [ 'value' ];
					}
				}
			}
			
			return  $result;
		}
		
		/*-------------------------------------------------------------------------------------------
			
			NAME
			InsertSection - Inserts a section before another one.
			
			PROTOTYPE
            $status = $inifile->InsertSection ( $section, $section_before,
			$comment_before = null,
			$comment_after  = null ) ;
			
			DESCRIPTION
            Inserts a section before another one.
			
			PARAMETERS
            $section (string) -
			Section to be inserted.
			
            $section_before (string) -
			Section before which $section is to be inserted.
			
			$comment_before, $comment_after (string) -
            When those parameters are specified, a comment is inserted before and/or
            after the section name.
			
			RETURN VALUE
            True when everything is ok, false if an error occurred.
			
            NOTES
			You cannot insert a section before the global unnamed section ("").
			
		--------------------------------------------------------------------------------------------*/
		public function InsertSection($section, $section_before, $comment_before = null, $comment_after = null)
		{
			// It is forbidden to insert a section before the global unnamed section
			if (!$section_before) {
				return  false;
			}
			
			// Find section
			$index = $this->__FindSection($section_before);
			
			// Append the section if it does not already exists, then return
			if ($index  ===  false) {
				return  $this->AppendSection($section, $comment_before, $comment_after);
			}
			
			// Add a comment before, if any was specified
			if ($comment_before  !==  null) {
				$this->__AddTextBlock($comment_before."\n", false);
			}
			
			// Insert the section
			$section = $this->__NormalizeName($section);
			$item = array('type' => self::INI_SECTION, 'value' => $section);
			array_slice($this->Items, $index, 0, $item);
			
			// Add a comment after, if any
			if ($comment_after  !==  null) {
				$this->__AddTextBlock($comment_after."\n", false);
			}
			
			// All done, return
			$this->Dirty = true;
			
			return  true;
		}
		
		/*-------------------------------------------------------------------------------------------
			
			NAME
			IsDirty - Checks the dirty flag.
			
			PROTOTYPE
			$status = $inifile->IsDirty ( ) ;
			
			DESCRIPTION
			Checks if the dirty flag is set, ie if modifications occurred since the initial
			loading of the .INI file.
			
			RETURN VALUE
            True if the dirty flag is set, false otherwise.
			
		--------------------------------------------------------------------------------------------*/
		public function IsDirty()
		{
			return  $this->Dirty;
		}
		
		/*-------------------------------------------------------------------------------------------
			
			NAME
            IsKeyDefined - Checks if a key is defined.
			
			PROTOTYPE
            $status = $inifile->IsKeyDefined ( $section, $key ) ;
			
			DESCRIPTION
            Checks if the specified key is defined in the specified section.
			
			PARAMETERS
            $section (string) -
			Section name.
			
            $key (string) -
			Key name.
			
			RETURN VALUE
            True if the specified section/key pair exists, false otherwise.
			
		--------------------------------------------------------------------------------------------*/
		public function IsKeyDefined($section, $key)
		{
			$index = $this->__FindKey($section, $key);
			
			if ($index  ===  false) {
				return  false;
			}
			else {
				return  true;
			}
		}
		
		/*-------------------------------------------------------------------------------------------
			
			NAME
			IsSectionDefined - Checks if the specified section exists.
			
			PROTOTYPE
            $status = $inifile->IsSectionDefined ( $section ) ;
			
			DESCRIPTION
			Checks if the specified section exists within the .INI file.
			
			PARAMETERS
            $section (string) -
			Section name.
			
			RETURN VALUE
            True if the specified section exists, false otherwise.
			
		--------------------------------------------------------------------------------------------*/
		public function IsSectionDefined($section)
		{
			$index = $this->__FindSection($section);
			
			if ($index  ===  false) {
				return  false;
			}
			else {
				return  true;
			}
		}
		
		/*-------------------------------------------------------------------------------------------
			
			NAME
            LoadFromArray,
            LoadFromFile,
            LoadFromString - Creates a IniFile object.
			
			PROTOTYPE
            $inifile = IniFile::LoadFromArray  ( $array, $separator = '=' ) ;
            $inifile = IniFile::LoadFromFile   ( $file, $load_option = IniFile::LOAD_ANY, $separator = '=' ) ;
            $inifile = IniFile::LoadFromString ( $string, $separator = '=' ) ;
			
			DESCRIPTION
            You can create an empty .INI file object by using the new operator ; however, if you
            already have contents to be loaded, use one of these three static functions. It will
            create a IniFile object, load the contents you specified, and return a reference
            to the object.
			
			PARAMETERS
            $array (array of strings) -
			Array of strings containing the .INI file. The EOL string will be determined
			by the current OS ( "\r\n" for Windows, "\n" for Unix).
			
            $string (string) -
			.INI file contents. The EOL string will be deduced from the supplied string.
			
			$file (string) -
            .INI file whose contents are to be loaded.
			
			$load_option (enum) -
            One of the following values :
            - LOAD_ANY :
			The specified file is loaded. If it does not exist, it will be created.
			
            - LOAD_NEW :
			The specified file is created. If it already exists, it will be
			overridden.
			
            - LOAD_EXISTING :
			The specified file is loaded. If it does not exist, an error message
			will be printed.
			
			$separator (string) -
            Separator to be used for separating key names from their values.
			
			RETURN VALUE
            Returns the created IniFile object, with the specified contents.
			
		--------------------------------------------------------------------------------------------*/
		public static function LoadFromArray($array, $separator = '=')
		{
			$object = new self($separator);
			$object->__Load(implode($this->EOL, $array));
			
			return  $object;
		}
		
		public static function LoadFromFile($inifile, $load_option = self::LOAD_ANY, $separator = '=')
		{
			global        $Application;
			
			$load = false;
			
			switch ($load_option) {
				case    self::LOAD_ANY :
                if (file_exists($inifile)) {
                    $load = true;
                    break;
				}
				else {
					$fp = @fopen($inifile, 'w');
					
					if (!$fp) {
						throw (new Exception("The .ini file '$file' could not be created."));
					}
					
					fclose($fp);
				}
				break;
				
				case    self::LOAD_NEW :
                $fp = @fopen($inifile, 'w');
				
                if (!$fp) {
                    throw (new Exception("The .ini file '$inifile' cannot be created."));
				}
				
                fclose($fp);
                break;
				
				case    self::LOAD_EXISTING :
                if (file_exists($inifile)) {
                    $load = true;
				}
				else {
                    throw (new Exception("The .ini file '$inifile' does not exist."));
				}
                break;
				
				default :
                throw (new Exception("Invalid value '$load_option' specified for the load option parameter of the IniFile constructor."));
			}
			
			$object = new self($separator);
			$object->File = $inifile;
			
			if ($load) {
				$object->__Load(file_get_contents($inifile), $inifile, $separator);
			}
			
			return  $object;
		}
		
		public static function LoadFromString($string, $separator = '=')
		{
			$object = new self($separator);
			$object->__Load($string, null, $separator);
			
			return  $object;
		}
		
		/*-------------------------------------------------------------------------------------------
			
			NAME
			RemoveKey - Removes a key from a section.
			
			PROTOTYPE
			$inifile->RemoveKey ( $section, $key, $clear_comment_before = true ) ;
			
			DESCRIPTION
            Removes the specified key within a section.
			
			PARAMETERS
            $section (string) -
			Name of the section containing the key to be removed.
			
            $key (string) -
			Key to remove.
			
			$clear_comment_before (boolean) -
            When true, clears the comment before the specified key, if any.
			
			RETURN VALUE
            True if the key exists, false otherwise.
			
		--------------------------------------------------------------------------------------------*/
		public function RemoveKey($section, $key, $clear_comment_before = true)
		{
			$index = $this->__FindKey($section, $key);
			
			if ($index  !==  false) {
				unset($this->Items [ $index ]);
				
				if ($clear_comment_before  &&  $index  &&  $this->Items [ $index - 1 ] [ 'type' ]  ==  self::INI_COMMENT) {
					unset($this->Items [ $index - 1 ]);
				}
				
				$this->Dirty = true;
				$this->__Compact();
				
				return  true;
			}
			else {
				return  false;
			}
		}
		
		/*-------------------------------------------------------------------------------------------
			
			NAME
			RemoveSection - Removes the specified section.
			
			PROTOTYPE
			$inifile->RemoveSection ( $section, $clear_comment_before ) ;
			
			DESCRIPTION
            Removes the specified section, with its contents.
			
			PARAMETERS
            $section (string) -
			Section to be removed. Specify the empty string ("") for the global unnamed
			section.
			
            $clear_comment_before (boolean) -
			When true, clears the comment before the specified section, if any.
			
			RETURN VALUE
            True if the section exists and has been successfully removed, false otherwise.
			
			NOTES
            Unlike the ClearSection() method, the RemoveSection() also removes the section name
            from the .INI file.
			
		--------------------------------------------------------------------------------------------*/
		public function RemoveSection($section, $clear_comment_before = true)
		{
			// Find the section start
			$index = $this->__FindSection($section);
			$count = count($this->Items);
			
			// Fail if it does not exist
			if ($index  ===  false) {
				return  false;
			}
			
			// Locate the section end, which is either the start of a new section or the end of the .INI file
			$start = $index;
			$end = $count;
			
			for ($i = $index + 1; $i < $count; ++$i) {
				$item = $this->Items [$i];
				
				if ($item [ 'type' ]  ==  self::INI_SECTION) {
					$end = $i;
					break;
				}
			}
			
			// Check if we need to clear a potential comment before the section
			if ($clear_comment_before  &&  $start) {
				if ($this->Items [ $start - 1 ] [ 'type' ]  ==  self::INI_COMMENT) {
					$this->Items [ $start - 1 ] [ 'value' ] = "\n\n";
					--$start;
				}
			}
			
			if ($this->Items [ $end - 1 ] [ 'type' ]  ==  self::INI_COMMENT) {
				$end--;
			}
			
			// Remove all section entries, including section name (and comment before)
			for ($i = $start; $i  <  $end; ++$i) {
				unset($this->Items [$i]);
			}
			
			// All done, reorder the Items array and return
			$this->Dirty = true;
			$this->__Compact();
			
			return  true;
		}
		
		/*-------------------------------------------------------------------------------------------
			
			NAME
			RenameKey - Renames a key in a section.
			
			PROTOTYPE
			$status = $inifile->RenameKey ( $section, $old, $new ) ;
			
			DESCRIPTION
			Renames a key contained in the specified section, from $old to $new.
			
			PARAMETERS
            $section (string) -
			Section containing the key to be renamed. Use the empty string ("") for the
			global unnamed section.
			
            $old (string) -
			Key to be renamed.
			
			$new (string) -
            New name for the key.
			
			RETURN VALUE
            This function returns true if the operation was successful, or false if one of the
            following conditions occurred :
            - The section specified by $section does not exist
			- The key specified by $old does not exist
			- The key specified by $new already exist
			
		--------------------------------------------------------------------------------------------*/
		public function RenameKey($section, $old, $new)
		{
			$old_index = $this->__FindKey($section, $old);
			$new = $this->__NormalizeName($new);
			$new_index = $this->__FindKey($section, $new);
			
			if ($new_index  !==  false  ||  $old_index  ===  false) {
				return  false;
			}
			
			$item = &$this->Items [ $old_index ];
			$item [ 'name' ] = $new;
			
			$this->Dirty = true;
			
			return  true;
		}
		
		/*-------------------------------------------------------------------------------------------
			
			NAME
			RenameSection - Renames a section.
			
			PROTOTYPE
			$inifile->RenameSection ( $old, $new ) ;
			
			DESCRIPTION
            Renames a section.
			
			PARAMETERS
            $old (string) -
			Section to be renamed.
			
            $new (string) -
			New name for the section.
			
			RETURN VALUE
            The function returns true if the operation was successful, and false if one of the
            following conditions occurs :
            - The section specified by $old does not exist
            - The section specified by $new already exist
			
		--------------------------------------------------------------------------------------------*/
		public function RenameSection($old, $new)
		{
			$old_index = $this->__FindSection($old);
			$new = $this->__NormalizeName($new);
			$new_index = $this->__FindSection($new);
			
			if ($new_index  !==  false  ||  $old_index  ===  false) {
				return  false;
			}
			
			$item = &$this->Items [ $old_index ];
			$item [ 'value' ] = $new;
			
			$this->Dirty = true;
			
			return  true;
		}
		
		/*-------------------------------------------------------------------------------------------
			
			NAME
			save - saves the current .INI file
			
			PROTOTYPE
            $inifile->save ( $forced = false, $file = null ) ;
			
			DESCRIPTION
            saves the contents of the current .INI file object.
			
			PARAMETERS
            $forced (boolean) -
			Normally, the .INI file is saved if and only if the dirty flag is set.
			You can override this behavior and perform a forced save whatever the initial
			value of the dirty flag, by setting this parameter to true.
			
            $file (string) -
			Output file name. This can be used to save .INI file contents to a different
			file than the original one (when the LoadFromFile() method has been used).
			Note however that an error will occur if :
			- The $file parameter has not been specified
			- The .INI file contents were loaded through the LoadFromArray() or
			LoadFromString() methods
			- The $inifile->File property has not been set by the caller.
			
		--------------------------------------------------------------------------------------------*/
		public function save($forced = false, $file = null)
		{
			if (!$file  &&  !$this->File) {
				throw (new Exception('IniFile::save() called, but not file has been specified.'));
			}
			
			if (!$this->Dirty  &&  !$forced) {
				return;
			}
			
			if (!$file) {
				$file = $this->File;
			}
			
			file_put_contents($file, $this->AsString());
			$this->Dirty = false;
		}
		
		/*-------------------------------------------------------------------------------------------
			
			NAME
			SetAlignment - Set the alignment value.
			
			PROTOTYPE
			$align = $inifile->SetAlignment ( $alignment ) ;
			
			DESCRIPTION
            Sets the section keys alignment option.
			
			PARAMETERS
            $alignment (enum) -
			IniFile::ALIGN_NONE -
			No alignment takes place. The .INI file will be written back as is.
			
			IniFile::ALIGN_SECTION -
			Individual keys within a section will be aligned according to the longest key
			name in the section.
			
            IniFile::ALIGN_FILE -
			Keys will be aligned at the .INI file level.
			
		--------------------------------------------------------------------------------------------*/
		public function SetAlignment($alignment)
		{
			$this->AlignmentOption = $alignment;
		}
		
		/*-------------------------------------------------------------------------------------------
			
			NAME
			setKey - Sets or define a new key value.
			
			PROTOTYPE
			$inifile->setKey ( $section, $key, $value,
			$comment_before = null, $comment_after = null ) ;
			
			DESCRIPTION
            Adds a new section key or changes an existing one.
			
			PARAMETERS
            $section (string) -
			Section containing the key to be set.
			
            $key (string) -
			Name of the key to be added or changed.
			
			$value (string) -
            Key value. A multiline key value will be handled correctly.
			
			$comment_before, $comment_after (string) -
            If specified, a comment will be added before and/or after the key.
			
		--------------------------------------------------------------------------------------------*/
		public function setKey($section, $key, $value, $comment_before = null, $comment_after = null)
		{
			// Find the section key
			$section = $this->__NormalizeName($section);
			$key = $this->__NormalizeName($key);
			$index = $this->__FindKey($section, $key);
			
			// If found, set its value
			if ($index  !==  false) {
				$this->Items [ $index ] [ 'value' ] = $value;
			}
			// Otherwise append it to the section
			else {
				// Locate the section
				$index = $this->__FindSection($section);
				
				// If the section does not exist, append it to the .INI file
				if ($index  ===  false) {
					$this->__AddTextBlock("\n", false);
					$this->AppendSection($section);
					$index = $this->__FindSection($section);
				}
				
				// Replace Windows EOL sequences with Unix ones
				$value = str_replace("\r\n", "\n", $value);
				
				// Handle the multiline state of the key
				if (strpos($value, "\n")  !==  false) {
					$multiline = true;
					$word = 'END';
				}
				else {
					$multiline = false;
					$word = '';
				}
				
                // Create the item
				$item = array(
				'type' => self::INI_ENTRY,
				'section' => $section,
				'name' => $key,
				'separator' => ' = ',
				'value' => $value,
				'multiline' => $multiline,
				'word' => $word,
                );
				
				// Append it to the Items array
				$this->Items [] = $item;
				$this->__AddTextBlock("\n", false);
			}
			
			// All done, return
			$this->Dirty = true;
			
			return  true;
		}
	}
