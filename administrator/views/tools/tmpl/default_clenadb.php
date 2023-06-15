<?php
defined('_JEXEC') or die('Restricted access');
?>

<form class="form-horizontal" id="formCleanDb" name="formCleanDb" method="post" enctype="multipart/form-data"
      action="<?php echo JRoute::_('index.php?option=com_administrativetools&task=tools.cleanBd'); ?>">
      <div class="control-group">
            <button form="formCleanDb" type="submit"
                    class="icon-white"><?php echo FText::_('COM_ADMINISTRATIVETOOLS_CLEANDB_BTN_TITLE0'); ?></button>
    </div>
</form>


