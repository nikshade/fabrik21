<?php
/**
* @package Joomla
* @subpackage Fabrik
* @copyright Copyright (C) 2005 Rob Clayburn. All rights reserved.
* @license http://www.gnu.org/copyleft/gpl.html GNU/GPL, see LICENSE.php
*/

// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();

jimport('joomla.application.component.view');

class FabrikViewTable extends JView{

	/**
	 * display a json object representing the table data.
	 * Not used for updating fabrik tables, use raw view for that, here in case you want to export the data to another application
	 */

function display()
	{
		$model =& $this->getModel();
		$model->setId(JRequest::getInt('tableid'));
		$table =& $model->getTable();
		$model->render();
		$rowid = JRequest::getInt('rowid');
		$data =& $model->getData();
		$nav = $model->getPagination();
		echo json_encode($data);
	}

}
?>