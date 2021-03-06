<?php
/**
 * @package Joomla
 * @subpackage Fabrik
 * @copyright Copyright (C) 2005 Rob Clayburn. All rights reserved.
 * @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
 */

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

jimport('joomla.application.component.model');

class FabrikModelGroup extends JModel{

	/** @var object parameters */
	var $_params = null;

	/** @var int id of group to load */
	var $_id = null;

	/** @var object group table */
	var $_group = null;

	/** @var object form model */
	var $_form 		= null;

	/** @var object table model */
	var $_table 		= null;

	var $_joinModel = null;

	/** @var array of element plugins */
	var $elements = null;

	/** @var array of published element plugins */
	var $publishedElements = null;

	/** @var array of published element plugins shown in the table */
	var $publishedTableElements = null;

	/** @var int how many times the group's data is repeated */
	var $_repeatTotal = null;

	/** @var array of form ids that the group is in (maximum of one value)*/
	var $_formsIamIn = null;

	/** @var bol can the group be viewed (set to false if no elements are visible in the group**/
	var $canView = null;

	/**
	 * @param database A database connector object
	 */

	function __construct()
	{
		parent::__construct();
	}

	/**
	 * Method to set the group id
	 *
	 * @access	public
	 * @param	int	group ID number
	 */

	function setId($id)
	{
		// Set new group ID
		$this->_id		= $id;
	}

	public function getId()
	{
		return $this->_id;
	}

	function &getGroup()
	{
		if (is_null($this->_group)) {
			JTable::addIncludePath(JPATH_ADMINISTRATOR.DS.'components'.DS.'com_fabrik'.DS.'tables');
			$this->_group =& JTable::getInstance('Group', 'FabrikTable');
			$this->_group->load($this->_id);
		}
		return $this->_group;
	}

	/**
	 * can you view the group
	 * @param bol is the group in an editable view
	 * @return bol
	 */

	function canView()
	{
		if (!is_null($this->canView)) {
			return $this->canView;
		}
		$elementModels =& $this->getPublishedElements();
		$this->canView = false;
		foreach ($elementModels as $elementModel) {
			// $$$ hugh - added canUse() check, corner case, see:
			// http://fabrikar.com/forums/showthread.php?p=111746#post111746
			if (!$elementModel->canView() && !$elementModel->canUse()) {
				continue;
			}
			$this->canView = true;
		}
		return $this->canView;
	}

	/**
	 * set the context in which the element occurs
	 *
	 * @param object form model
	 * @param object table model
	 */

	function setContext(&$formModel, &$tableModel)
	{
		$this->_form 		=& $formModel;
		$this->_table 	=& $tableModel;
	}

	/**
	 * get an array of forms that the group is in
	 * NOTE: now a group can only belong to one form
	 * @return array form ids
	 */

	function getFormsIamIn()
	{
		if (!isset($this->_formsIamIn)) {
			$db =& JFactory::getDBO();
			$sql = "SELECT form_id FROM #__fabrik_formgroup WHERE group_id = ".(int)$this->_id;
			$db->setQuery($sql);
			$this->_formsIamIn = $db->loadResultArray();
		}
		return $this->_formsIamIn;
	}

	/**
	 * returns array of elements in the group
	 *
	 * NOTE: pretty sure that ->elements will already be loaded
	 * within $formModel->getGroupsHiarachy()
	 *
	 * @return array element objects (bound to element plugin)
	 */

	function getMyElements()
	{
		//note dont use static vars here. As static vars are class-methods - so there is only
		//one static value for all generated group objects.
		if (!isset ($this->elements)) {
			$group =& $this->getGroup();
			$this->elements = array();
			$form =& $this->getForm();
			$pluginManager =& $form->getPluginManager();
			$allGroups =& $pluginManager->getFormPlugins($this->_form);
			if (empty($this->elements)) {
				//horrible hack for when saving group
				$this->elements =& $allGroups[$this->_id]->elements;
			}
		}
		return $this->elements;
	}

	/**
	 * randomise the element list (note the array is the pre-rendered elements)
	 * @param $elements array form views processed/formatted list of elements
	 * that the form template uses
	 * @return null
	 */
	function randomiseElements(&$elements)
	{
		if ($this->getParams()->get('random', false) == true) {
			$keys = array_keys($elements);
			shuffle($keys);
			foreach ($keys as $key) {
				$new[$key] = $elements[$key];
			}
			$elements = $new;
		}
	}

	/**
	 * get the groups form model
	 * @return object form model
	 */

	function getForm()
	{
		if (!isset($this->_form)) {
			$formids = $this->getFormsIamIn();
			$formid = empty($formids ) ? 0 : $formids[0];
			$this->_form =& JModel::getInstance('Form', 'FabrikModel');
			$this->_form->setId($formid);
			$this->_form->getForm();
			$this->_form->getTableModel();
		}
		return $this->_form;
	}

	/**
	 * get the groups table model
	 * @return object table model
	 */
	function getTableModel()
	{
		return $this->getForm()->getTableModel();
	}

	/**
	 * get an array of published elements
	 *
	 * @return array published element objects
	 */

	function getPublishedElements()
	{
		if (!isset($this->publishedElements)) {
			$this->publishedElements = array();
			$elements =& $this->getMyElements();
			foreach ($elements as $elementModel) {
				if ($elementModel->getElement()->state == 1) {
					$this->publishedElements[] = $elementModel;
				}
			}
		}
		return $this->publishedElements;
	}

	public function getPublishedTableElements()
	{
		if (!isset($this->publishedTableElements)) {
			$this->publishedTableElements = array();
			$elements =& $this->getMyElements();
			foreach ($elements as $elementModel) {
				$element =& $elementModel->getElement();
				if ($element->state == 1 && $element->show_in_table_summary && $elementModel->canView()) {
					$this->publishedTableElements[] = $elementModel;
				}
			}
		}
		return $this->publishedTableElements;
	}
	/*
	 * is the group a repeat group
	 *
	 * @return bol
	 */

	public function canRepeat()
	{
		$params =& $this->getParams();
		return $params->get('repeat_group_button');
	}

	/**
	 * is the group a join?
	 *
	 * @return bol
	 */

	public function isJoin()
	{
		return $this->getGroup()->is_join;
	}

	/**
	 * get the group's associated join model
	 *
	 * @return object join model
	 */

	public function getJoinModel()
	{
		$group =& $this->getGroup();
		if (is_null($this->_joinModel)) {
			$this->_joinModel =& JModel::getInstance('Join', 'FabrikModel');
			$this->_joinModel->setId($group->join_id);
			$js = $this->getTableModel()->getJoins();
			// $$$ rob set join models data from preloaded table joins - reduced load time
			for ($x=0; $x < count($js); $x ++) {
				if ($js[$x]->id == $group->join_id) {
					$this->_joinModel->setData($js[$x]);
					break;
				}
			}

			$this->_joinModel->getJoin();
		}
		return $this->_joinModel;
	}

	/**
	 * load params
	 *
	 * @return object params
	 */

	function &loadParams()
	{
		$this->_params =  new fabrikParams($this->_group->attribs);
		return $this->_params;
	}

	/**
	 * get group params
	 *
	 * @return object params
	 */

	function &getParams()
	{
		if (!$this->_params) {
			$this->_params = $this->loadParams();
		}
		return $this->_params;
	}

	/**
	 * creates a html dropdown off all groups
	 * @param int selected group id
	 * @return string group list
	 */

	function makeDropDown( $selectedId = 0, $defaultlabel = '')
	{
		if ($defaultlabel == '') {
			$defaultlabel = JText::_('COM_FABRIK_PLEASE_SELECT');
		}
		$db =& JFactory::getDBO();
		$sql = "SELECT id AS value, name AS text FROM #__fabrik_groups ORDER BY name";
		$db->setQuery($sql);
		$aTmp[] = JHTML::_('select.option', "-1", $defaultlabel);
		$groups = $db->loadObjectList();
		$groups = array_merge($aTmp, $groups);
		$list = JHTML::_('select.genericlist',  $groups, 'filter_groupId', 'class="inputbox"  onchange="document.adminForm.submit();"', 'value', 'text', $selectedId);
		return $list;
	}

	/**
	 * make a group object to be used in the form view. Object contains
	 * group display properties
	 * @param object form model
	 * @return object group display properties
	 */

	function getGroupProperties(&$formModel)
	{
		$w				= new FabrikWorker();
		$group				= new stdClass();
		$groupTable		=& $this->getGroup();
		$params				=& $this->getParams();

		if (!isset($this->_editable)) {
			$this->_editable = $formModel->_editable;
		}
		if ($this->_editable) {
			//if all of the groups elements are not editable then set the group to uneditable
			$elements =& $this->getPublishedElements();
			$editable = false;
			foreach ($elements as $element) {
				if ($element->canUse()) {
					$editable = true;
				}
			}
			if (!$editable) {
				$this->_editable = false;
			}
		}
		$group->editable = $this->_editable;
		$group->canRepeat = $params->get('repeat_group_button', '0');
		$addJs 				= str_replace('"', "'",  $params->get('repeat_group_js_add'));
		$group->addJs = str_replace(array("\n", "\r"), "",  $addJs);
		$delJs 				= str_replace('"', "'",  $params->get('repeat_group_js_delete'));
		$group->delJs = str_replace(array("\n", "\r"), "",  $delJs);
		$showGroup 		= $params->def('repeat_group_show_first', '1');

		$pages =& $formModel->getPages();

		$startpage = isset($formModel->sessionModel->last_page) ? $formModel->sessionModel->last_page: 0;
		// $$$ hugh - added array_key_exists for (I think!) corner case where group properties have been
		// changed to remove (or change) paging, but user still has session state set.  So it was throwing
		// a PHP 'undefined index' notice.
		if (array_key_exists($startpage, $pages) && is_array($pages[$startpage]) && !in_array($groupTable->id, $pages[$startpage]) || $showGroup == 0) {
			$groupTable->css .= ";display:none;";
		}
		$group->css 		= trim(str_replace(array("<br />", "<br>"), "", $groupTable->css));
		$group->id 			= $groupTable->id;

		if (JString::stristr($groupTable->label , "{Add/Edit}")) {
			$replace = ((int)$formModel->_rowId === 0) ? JText::_('ADD') : JText::_('EDIT');
			$groupTable->label  = str_replace("{Add/Edit}", $replace, $groupTable->label);
		}
		$group->title = $w->parseMessageForPlaceHolder($groupTable->label, $formModel->_data, false);

		$group->name		= $groupTable->name;
		$group->displaystate = ($group->canRepeat == 1 && $formModel->_editable) ? 1 : 0;
		$group->maxRepeat = (int)$params->get('repeat_max', '');
		$group->showMaxRepeats = $params->get('show_repeat_max', '0') == '1';
		return $group;
	}

	/**
	 * copies a group, form group and its elements
	 * @return an array of new element id's keyed on original elements that have
	 * been copied
	 *
	 * (when copying a table (and hence a group) the groups join is copied in table->copyJoins)
	 */

	function copy()
	{
		$elements =& $this->getMyElements();
		$group = $this->getGroup();
		//newGroupNames set in table copy
		$newNames = JRequest::getVar('newGroupNames', array());
		if (array_key_exists($group->id, $newNames)) {
			$group->name = $newNames[$group->id];
		}
		$group->id = 0;
		$group->store();

		$newElements = array();
		foreach ($elements as $element) {
			$origElementId = $element->getElement()->id;
			$copy = $element->copyRow($origElementId, '', $group->id);
			$newElements[$origElementId] =  $copy->id;
		}
		$this->elements = null;
		$elements =& $this->getMyElements();

		//create form group
		$formid = isset($this->_newFormid) ? $this->_newFormid : $this->getForm()->_id;
		$formGroup = JTable::getInstance('FormGroup', 'Table');
		$formGroup->form_id = $formid;
		$formGroup->group_id = $group->id;
		$formGroup->ordering = 999999;
		if (!$formGroup->store()) {
			JError::raiseError(500, $formGroup->getError());
		}
		$formGroup->reorder(" form_id = '$formid'");
		return $newElements;
	}

}
?>