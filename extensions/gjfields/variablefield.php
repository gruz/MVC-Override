<?php
/**
 * @package     GJFileds
 *
 * @copyright   Copyright (C) All rights reversed.
 * @license - http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL or later
 */

defined('JPATH_PLATFORM') or die;
jimport('joomla.form.formfield');
jimport('joomla.form.helper');

if (!class_exists('JFormFieldGJFields')) {include ('gjfields.php');}
class JFormFieldVariablefield extends JFormFieldGJFields
{
	/**
	 * The form field type.
	 *
	 * @var    string
	 *
	 * @since  11.1
	 */
	protected $type = 'variablefield';


	/**
	 * getLabel
	 *
	 * If it meets a group start like `{group1`, then it sets an sets a flag,
	 * and later, till the end on the last open group, doesn't output
	 * anything, but saves the XML element object to $GLOBALS['variablefield'].
	 * Only when the last open group is closed, like `group1}`, then the class knows
	 * the XML structure - which fields are inside the group.
	 * After we have the whole picture of the group structure, we output
	 * group once or several times - depending on the values in the
	 * strating group field `{group1` value.
	 * So we need to get the whole group structure and then to clone it
	 * as many times as we need.
	 *
	 * This function determines either to store current field and sets some flags,
	 * or Either outside the group OR at the stage of outputting it shows normal label.
	 *
	 * @author Gruz <arygroup@gmail.com>
	 * @param	type	$name	Description
	 * @return	type			Description
	 */
	function __construct($form = null) {
		parent::__construct($form);
		// Add CSS and JS once, define base global flag - runc only once
		$this->langShortCode = null;//is used for building joomfish links
		$this->default_lang = JComponentHelper::getParams('com_languages')->get('site');
		$language = JFactory::getLanguage();
		$language->load('lib_gjfields', __DIR__, 'en-GB', true);
		$language->load('lib_gjfields', __DIR__, $this->default_lang, true);

	}

	protected function prepareGroupField () {
		$basetype = isset($this->element['basetype']) ? $this->element['basetype'] : 'text';
		$basetype = (string) $basetype;
		$first_char = JString::substr ($this->element['name'],0,1);
		$last_char = JString::substr ($this->element['name'],JString::strlen($this->element['name'])-1,1);
		if ($basetype == 'group') {//If start or end of group
			if ($first_char == "{") {// Починаю нову групу (може бути будь-якого рівня вкладеності)
				$GLOBALS['variablefield']['output'] = false;
				$GLOBALS['variablefield']['current_group'][] = (string)$this->element['name'].'}';// Lower in the code I know, that last element of array $GLOBALS['variablefield']['current_group'] is always the current group.
				$this->groupstate = 'start';
				$GLOBALS['variablefield']['fields'][] = clone $this;// I must clone
			}
			// Закінчую поточну групу і перевіряю, чи завершено останній блок
			// Якщо завершено останній блок, то роблю ітерацію по всіх збережених полях і виводжу їх у getInput,
			else if ($last_char == "}") {
				$this->groupstate = 'end';
				$GLOBALS['variablefield']['fields'][] = clone $this;
				array_pop($GLOBALS['variablefield']['current_group']);
				if (empty($GLOBALS['variablefield']['current_group'])) {
					$GLOBALS['variablefield']['output'] = true;
				}
			}
		}
		else if(!empty($GLOBALS['variablefield']['current_group'])) {// If in element from inside the group
			$this->groupstate = 'continue';
			$this->defaultvalue = $this->value;
			$GLOBALS['variablefield']['fields'][] = clone $this;
		}

	}
	protected function getLabel($flag = false) {
$debug = true;
$debug = false;
		// Here I handle dependant load of categories
		static $count = array();
		static $from_params = null;
		static $defaults = array();
		//~ static $runNumber = 0;
		//~ $runNumber++;
		if (isset($this->element['label'])) {
			$this->element['label'] = $this->_replaceNestedJtextConstants($this->element['label']);
		}
		if (isset($this->element['description'])) {
			$this->element['description'] = $this->_replaceNestedJtextConstants($this->element['description']);
		}

		if (!$flag || !isset($this->origname)) {
			$this->origname = (string)$this->fieldname;
		}

		if (!isset($defaults[$this->origname])) {
			$default = $this->getAttribute('default');
			//~ if (trim($default) != '') {
			//~ }
			$defaults[$this->origname] = $default;
		}
		$this->defaults = $defaults;
//~ echo '<pre style="background:#efefef">'.PHP_EOL;
//~ print_r($this->origname);
//~ echo '</pre>'.PHP_EOL;

		if (isset($this->element['source_parameter']) && isset($this->element['target_parameter']) && $flag) {
			//$this_field_name = $this->name;
			$this_field_name = (string)$this->element['name'];
			$this_field_name = $this->origname;
//~ echo '<pre> $this_field_name ='.PHP_EOL;
//~ print_r($this_field_name);
//~ echo '</pre>'.PHP_EOL;
if($debug) dumpMessage($this_field_name);
if($debug) dumpTrace();
			if (empty($from_params)) {
				$key_in_params = (string)$GLOBALS['variablefield']['fields'][0]->element['name'];
				$from_params = $GLOBALS['variablefield']['fields'][0]->form->getData()->toObject()->params->{$key_in_params};
//~ echo '<pre>'.PHP_EOL;
//~ print_r($from_params);
//~ echo '</pre>'.PHP_EOL;
if($debug) dump ($from_params,'$from_params');
			}
//~ echo '<pre style="background:red"> defaults = '.PHP_EOL;
//~ print_r($defaults);
//~ echo '</pre>'.PHP_EOL;
if($debug) dump ($this,'$this');
if($debug) dumpMessage($this_field_name);

			if (!isset($count[$this_field_name])) {
				$count[$this_field_name] = 0;
			} else {
				$count[$this_field_name]++;
				$count[$this_field_name]++;
			}
			$index = $count[$this_field_name];
			$source_parameters = explode(',',(string)$this->element['source_parameter']);
			$target_parameters = explode(',',(string)$this->element['target_parameter']);
//if($debug) dump ($this_field_name,'$this_field_name');
if($debug) dump ((string)$this->element['target_parameter'],(string)$this->element['source_parameter']);
			$get_joomla_content_type_by_id = (string)$this->element['get_joomla_content_type_by_id'];
//if($debug) dump ($get_joomla_content_type_by_id,'$get_joomla_content_type_by_id');
			foreach($source_parameters as $k=>$source_parameter) {
				$values = array();
//~ if($debug) dump ($source_parameter,'$source_parameter');
//~ if($debug) dump ($count[$this_field_name],'$count[$this_field_name]');
if($debug) dump ($index,'$index');

				if (!isset($from_params[$source_parameter])) {
					for ($i = 0; $i <$index+1 ; $i++) {
						if ($i == $index) {
							$values[] = $defaults[$source_parameter];
						} else {
							$values [] = null;
						}
					}
if($debug) dump ($values,'$values 1');
				} else {
					$values = $from_params[$source_parameter];
if($debug) dump ($values,'$values 2');
				}
				if (is_array($values[$index])) {
					$this->element[$target_parameters[$k]] = implode(',',$values[$index]);
				}
				else {
if($debug) dumpMessage (' Setting <b>'.$target_parameters[$k] . '</b> to  <b>'. $values[$index].'</b>');
					$this->element[$target_parameters[$k]] = $values[$index];
				}
			}
		}

		$basetype = isset($this->element['basetype']) ? $this->element['basetype'] : 'text';
		$basetype = (string) $basetype;
		if ($basetype == 'group' || !empty($GLOBALS['variablefield']['current_group'])) {//If start or end of group
			return null;
		}
		else {
			//Let show the script, that the group has ended
			$formfield = JFormHelper::loadFieldType($basetype);
			$formfield->setup($this->element,'');
			return $formfield->getLabel();
		}
		return null;
	}


	/**
	 * Method to get the field input markup.
	 *
	 * @return  string  The field input markup.
	 *
	 */
	public function getInput() {
		$this->prepareGroupField();
		if (!isset($GLOBALS['variablefield'])) { return $this->getInputHelper().PHP_EOL; }// If we process a field, not a group, then retun HTML for field (but prepared in my function getInputHelper() )
		// If we process a group and it's not OUTPUT, means not the end of the last open group, then output nothing
		// but output all the fields and maybe several time otherwise
		if ($GLOBALS['variablefield']['output'] !== true) { return null; }// If it's not final stage, then just return
		$groupStartField = $GLOBALS['variablefield']['fields'][0];
		$current_values_temp = (array)$groupStartField->value;
		$current_group = $groupStartField->fieldname;
		$current_values = array();// Тут будуть зберігатись пересортовані значення полів для активної групи
		foreach ($current_values_temp as $fieldname=>$values) {
			$group_number = 0;
			$values = (array) $values;
			foreach ($values as $value) {
				if ($value == 'variablefield::'.$current_group) {
					$group_number++;
				}
				else if (is_array($value) && $value[0] == 'variablefield::'.$current_group){
					$group_number++;
				}
				else if (is_array($value) ) {
					$current_values[$group_number][$fieldname][0][] = $value[0];
				}
				else {
					$current_values[$group_number][$fieldname][] = $value;
				}
			}
		}
		$output = '';
		$arrayLength = count($current_values);
		$length	= isset($groupStartField->element['length']) ? (int) $groupStartField->element['length'] : 1;
        $length = max($length, $arrayLength);
		$maxRepeatLength	= isset($groupStartField->element['maxrepeatlength']) ? (int) $groupStartField->element['maxrepeatlength']: 0;//If the maximum field length is 1, the we do not need to output the clone buttons
		if($maxRepeatLength > 0) {
			$length = min($length, $maxRepeatLength);
		}
		for ($i = 0; $i < $length; $i++) {
			for ($k = 0; $k < count($GLOBALS['variablefield']['fields']); $k++) {// Iterate all fields from the XML file inside the group
				$field = $GLOBALS['variablefield']['fields'][$k];
				switch ($field->groupstate) { // В залежності чи поточний елемент - це start, end групи чи всередині групи continue, робимо своє
					case 'start':
						$field->group_header = isset($current_values[$i][(string)$field->fieldname][0])?$current_values[$i][(string)$field->fieldname][0]:'';
						$field->open = isset($current_values[$i][(string)$field->fieldname][1])?$current_values[$i][(string)$field->fieldname][1]:'1';
						$ruleUniqID = !empty($current_values[$i]['__ruleUniqID'][0])?$current_values[$i]['__ruleUniqID'][0]:uniqid();
						$output .= $field->groupStartHTML($ruleUniqID);
						break;
					case 'end':
						$output .= $field->groupEndHTML();
						break;
					case 'continue':// Тут, якщо це поле всередині групи
					default :
//break;
						$field->name = 'jform[params]['.$current_group.']['.(string)$field->fieldname.']';// Перевормували навзу поля, щоби вона враховувала групу

						$field->value = isset($current_values[$i][(string)$field->fieldname])?$current_values[$i][(string)$field->fieldname]:$field->defaultvalue;

						if(version_compare(JVERSION,'3.0','ge') && $this->HTMLtype == 'div' ) {
							$output .= PHP_EOL.'<div class="control-group">'.PHP_EOL;
							$output .= PHP_EOL.'<div class="control-label">'.PHP_EOL.$field->getLabel(true).PHP_EOL.'</div><!-- control-label of a variable field -->'.PHP_EOL;
							$output .= PHP_EOL.'<div class="controls">'.PHP_EOL.$field->getInputHelper().PHP_EOL.'</div><!-- controls of a variable field -->'.PHP_EOL;
							$output .= PHP_EOL.'</div><!-- control-group -->';
						}
						else {
							$output .= $field->getLabel(true).PHP_EOL;
							$output .= $field->getInputHelper().PHP_EOL;//This outputs the field several times, if it's a repeatable
						}

						switch ((string)$field->element['basetype']) {
							case 'blockquote':
							case 'toggler':
							case 'note':
							case 'notefixed':
							case 'spacer':

								break;
							default :
								//Let show the script, that the group has ended
								$formfield = JFormHelper::loadFieldType('hidden');
								$formfield->setup($field->element,'');
								$formfield->value = 'variablefield::'.$current_group;
								$output .= $formfield->getInput().PHP_EOL;
								break;
						}



						if(!version_compare(JVERSION,'3.0','ge')  && $this->HTMLtype == 'li' ) {
							$output .= '</li>'.PHP_EOL.'<li>';
						}
						break;
				}

			}
		}
		unset($GLOBALS['variablefield']);
		return $output;

	}

	protected function getInputHelper () {

		switch ((string)$this->element['basetype']) {
			case 'radio':
			case 'checkbox':
				JFactory::getApplication()->enqueueMessage(JText::_('LIB_VARIABLEFILED_WRONG_BASETYPE').' <u>'.(string)$this->element['basetype'].'</u> ', 'error');
				break;
		}
		$originalValue = (array)$this->value;

		//how many tabs
		$arrayLength = count($originalValue);
		$length	= isset($this->element['length']) ? (int) $this->element['length'] : 1;
        $length = max($length, $arrayLength);
		$maxRepeatLength	= isset($this->element['maxrepeatlength']) ? (int) $this->element['maxrepeatlength']: 0;//If the maximum field length is 1, the we do not need to output the clone buttons
		if($maxRepeatLength > 0) {
			$length = min($length, $maxRepeatLength);
		}

		$basetype = isset($this->element['basetype']) ? (string)$this->element['basetype'] : 'text';
		$basetype = (string) $basetype;
		$formfield = JFormHelper::loadFieldType($basetype);
		$this->element['clean_name'] = (string)$this->element['name'];
		$this->element['name'] = $this->name.'[]';
		if(isset($this->element->option)) {
			foreach ($this->element->option as $Item) {
				$Item[0] = $this->_replaceNestedJtextConstants ($Item[0]);

			}
		}
		$formfield->setup($this->element,'');

		$output = '';
		if ($maxRepeatLength==1) {
			$formfield->id = $formfield->id.uniqid();
			$formfield->value = isset($originalValue[0])? $originalValue[0]:'';
			//;
			if (isset($this->defaults[$this->origname])) {
				$out = str_replace('id="','data-default="'.htmlspecialchars(JText::_($this->defaults[$this->origname])).'" id="', $formfield->getInput());
				$output .= $out;
			} else {
				$output .= $formfield->getInput().PHP_EOL;
			}
		}
		else {
			$output = '<div class="variablefield_div repeating_block" >'.PHP_EOL;
			$formfield->id = $formfield->id.uniqid();
			for($i=0; $i<$length; $i++) {
				$output .=  $this->blockElementStartHTML();

				$formfield->id = $formfield->id.'_'.$i;
				$formfield->value = isset($originalValue[$i])? $originalValue[$i]:'';
				$output .= $formfield->getInput().PHP_EOL;

				$output .= $this->blockElementEndHTML();
			}
			$output .= PHP_EOL.'</div><!-- repeatable field (many cloned fields) -->';
		}
		if(version_compare(JVERSION,'3.0','ge')  && $this->HTMLtype == 'div' ) {
			return $output;
			return PHP_EOL.'<div class="controls">'.PHP_EOL.$output.PHP_EOL.'</div>'.PHP_EOL.'</div>'.PHP_EOL;
		}
		else {
			return $output;
		}
	}

	function groupStartHTML($ruleUniqID='') {
		$output = '';
		if(version_compare(JVERSION,'3.0','ge')  && $this->HTMLtype == 'div'  ) {
			$output .=  '</div><!-- controls OR my empty div !-->'.$this->blockElementStartHTML(true,$ruleUniqID);
			$output .= PHP_EOL.'<div class="sliderContainer">'.PHP_EOL;

		}
		else {
			$output .= PHP_EOL.'</li></ul>'.PHP_EOL;
			$output .=  $this->blockElementStartHTML(true,$ruleUniqID);
			$output .= PHP_EOL.'<div class="sliderContainer"><ul class="adminformlist"><li>'.PHP_EOL;
		}
		//Let show the script, that the group has ended
		$formfield = JFormHelper::loadFieldType('text');
		$formfield->setup($this->element,'');
		$formfield->id = '';
		$formfield->readonly = 'readonly';
		$formfield->class = 'ruleUniqID';
		$formfield->name = 'jform[params]['.$this->fieldname.'][__ruleUniqID][]';// Remake field name to use group name
		$formfield->value = !empty($ruleUniqID)?$ruleUniqID:'';
		$output.= '<span class="ruleUniqID">UniqID: '.$formfield->getInput().'</span>'.PHP_EOL;

		//Let show the script, that the group has ended
		$formfield = JFormHelper::loadFieldType('hidden');
		$formfield->setup($this->element,'');
		$formfield->id = '';
		$formfield->class = 'ruleUniqID';
		$formfield->name = 'jform[params]['.$this->fieldname.'][__ruleUniqID][]';// Remake field name to use group name
		$formfield->value = 'variablefield::'.$this->fieldname;
		$output.= $formfield->getInput().PHP_EOL;
		return $output;
	}

	function groupEndHTML() {
		$output = '';
		if(version_compare(JVERSION,'3.0','ge')  && $this->HTMLtype == 'div'  ) {
			$output .= PHP_EOL.'<span class="cleaner"></span></div><!-- sliderContainer --><span class="cleaner"></span>'.$this->blockElementEndHTML(true).'<div>';
		}
		else {
			$output .= PHP_EOL.'</li></ul></div>'.$this->blockElementEndHTML(true);
			$output .= PHP_EOL;
			$output .= '<ul class="adminformlist"><li>'.PHP_EOL;
		}
		return $output;
	}

	function blockElementStartHTML($isGroup = false,$ruleUniqID = '') {
		$output = '';
		$maxRepeatLength	= isset($this->element['maxrepeatlength']) ? (int) $this->element['maxrepeatlength']: 0;

		$buttons = '';
		if ($maxRepeatLength !== 1) {
			$buttons .= '<a class="variablefield_buttons reset_current_slide hasTip" title="'.JText::_('JSEARCH_RESET').'">--</a> ';
			$buttons .= '<a class="variablefield_buttons move_up_slide hasTip" title="'.JText::_('JLIB_HTML_MOVE_UP').'">&#8657;</a>';
			$buttons .= '<a class="variablefield_buttons move_down_slide hasTip" title="'.JText::_('JLIB_HTML_MOVE_DOWN').'">&#8659;</a>';
			$buttons .= '<a data-max-repeat-length="'.$maxRepeatLength.'" class="variablefield_buttons add_new_slide hasTip" title="'.JText::_('JTOOLBAR_DUPLICATE').'" >+</a>';
			$buttons .= '<a class="variablefield_buttons delete_current_slide hasTip" title="'.JText::_('JTOOLBAR_REMOVE').'">-</a>';
		}

		if ($isGroup) {

			// Group name consist of specially prepared below Label and text Input with class .hide
			// Prepare Label
			$formfield = JFormHelper::loadFieldType('text');
			$formfield->setup($this->element,'');//Load current XML to get all XML attributes in $formfield
			$formfield->labelClass = 'groupSlider ';
			$formfield->labelclass = 'groupSlider '; // Need to set class like this in J3.2+

			$formfield->class = 'groupSlider '; // Need to set class like this in J3.2+
			if (!empty($this->group_header)) { //We get Label text either from stored plugin params, or from XML attributes
				$text = $this->group_header;
			}
			else {
				$text = $formfield->element['label'] ? (string) $formfield->element['label'] : (string) $formfield->element['name'];
			}
			$goupname = 'variablegroup__'.str_replace('{','',$this->element['name']);//Is needed for toggler JS
			$output .= '<div class="variablefield_div repeating_group '.$goupname.'" >'.'<div class="buttons_container">';

			$text = JText::_($text);
			$formfield->element['label']  = '';

			// Prepare buttons
			$editbutton  = '<a class="hasTip editGroupName editGroupNameButton" title="'.JText::_('JACTION_EDIT').'::">✍</a>';
			$editbutton .= '<a class="hasTip cancelGroupNameEdit editGroupNameButton hide " title="'.JText::_('JCANCEL').'::">✕</a>';
			$output .=  $formfield->getLabel().$editbutton.'<span style="float:right;">'.$buttons.'</span>';
			//$formfield->getLabel();
			//$output .=  $editbutton.$buttons;

			// Prepare input field for group name
			$formfield->element['size']  = '';

			$formfield->element['class'] = 'groupnameEditField';
			$formfield->class = 'groupnameEditField'; // Need to set class like this in J3.2+

			//$formfield->value = htmlspecialchars($text, ENT_COMPAT, 'UTF-8');
			//$formfield->value = addslashes($text);
			$formfield->value = $text;
			$formfield->name = 'jform[params]['.$this->fieldname.']['.(string)$this->fieldname.'][]';// Remake field name to use group name
			$formfield->element['readonly'] = 'true';
			$formfield->readonly = 'true';// Need to set class like this in J3.2+


			JHTML::_('behavior.tooltip');
			$output .= '<span class="hdr-wrppr inactive hasTip" title="'.JText::_('LIB_VARIABLEFILED_GROUPNAME_TIP').'">'.$formfield->getInput().'</span>'.PHP_EOL;
			$output .= '</div><!-- buttons_container -->' ;

			//Field to store group status - opened or closed
			$formfield = JFormHelper::loadFieldType('hidden');
			$formfield->setup($this->element,'');
			$formfield->name = 'jform[params]['.$this->fieldname.']['.(string)$this->fieldname.'][]';// Remake field name to use group name

			$formfield->element['class'] = 'groupState';
			$formfield->class = 'groupState'; // Need to set class like this in J3.2+

			$formfield->element['disabled'] = null;
			$formfield->element['onchange'] = null;
			$formfield->value = $this->open;
			$output .= $formfield->getInput().PHP_EOL;


			//Let show the script, that the group has ended
			$formfield = JFormHelper::loadFieldType('hidden');
			$formfield->setup($this->element,'');
			$formfield->name = 'jform[params]['.$this->fieldname.']['.(string)$this->fieldname.'][]';// Remake field name to use group name
			$formfield->value = 'variablefield::'.$this->fieldname;
			$output .= $formfield->getInput().PHP_EOL;
		}
		else {
			$output .= '<div class="variablefield_div repeating_element" >'.'<div class="buttons_container">'.$buttons.'</div><!-- buttons_container -->' ;
		}


		return $output;
	}
	function blockElementEndHTML() {
		$output = '';
		if(version_compare(JVERSION,'3.0','ge')  && $this->HTMLtype == 'div'  ) {
			$output .= PHP_EOL.'</div><!-- variablefield_div repeating_group -->'.PHP_EOL;
		}
		else {
			$output .= PHP_EOL.'</div><!-- variablefield_div repeating_group -->'.PHP_EOL;
		}
		return $output;
	}

	protected function _replaceNestedJtextConstants($text) {
		$text = JText::_($text);
		if (empty($text)) return;
		preg_match_all('/#~#([^#~#]*)#~#/i',$text,$matches);
		if (!empty($matches[0])) {
			foreach ($matches[1] as $k=>$string) {
				if (JText::_($string) != $string) {
					$text = str_replace($matches[0][$k],JText::_($string),$text);
				}
			}
		}
		return $text;
	}

}

