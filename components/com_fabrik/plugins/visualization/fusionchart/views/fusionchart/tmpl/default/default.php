<?php
// Check to ensure this file is included in Joomla!
defined('_JEXEC') or die();
$row = $this->row;
?>
<div id="<?php echo $this->containerId;?>">
	<?php if (JRequest::getVar('show-title', $this->params->get('show-title', '1'))) {?>
		<h1><?php echo $row->label;?></h1>
	<?php }?>
	<br/>
	<p><?php echo $row->intro_text;?></p>
	<?php echo $this->loadTemplate('filter'); ?>
	<br/>
	<?php echo $this->chart; ?>
</div>