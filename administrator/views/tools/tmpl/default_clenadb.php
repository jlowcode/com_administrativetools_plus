<?php
defined('_JEXEC') or die('Restricted access');
?>

<form class="form-horizontal" id="formChangeList" name="formChangeList" method="post" enctype="multipart/form-data"
      action="<?php echo JRoute::_('index.php?option=com_administrativetools&task=tools.submitChangeList'); ?>">
    
      <div class="control-group">
        <div class="controls">
            <button form="formChangeList" type="submit"
                    class="btn btn-success"><?php echo FText::_('COM_ADMINISTRATIVETOOLS_CLEANDB_BTN_TITLE0'); ?></button>
        </div>
    </div>
    
</form>