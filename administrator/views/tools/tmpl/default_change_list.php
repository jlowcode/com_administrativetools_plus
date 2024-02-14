<?php
use \Joomla\CMS\Language\Text;

defined('_JEXEC') or die('Restricted access');
?>

<form class="form-horizontal" id="formChangeList" name="formChangeList" method="post" enctype="multipart/form-data"
      action="<?php echo JRoute::_('index.php?option=com_administrativetools&task=tools.submitChangeList'); ?>">
    <div class="control-group">
        <div class="controls">
            <label><strong><?php echo Text::_('COM_ADMINISTRATIVETOOLS_CHANGE_LIST_FIELD_LABEL0'); ?></strong></label>
        </div>
    </div>

    <div class="control-group">
        <label class="control-label" for="nameList"><?php echo Text::_('COM_ADMINISTRATIVETOOLS_LISTS'); ?></label>
        <div class="controls">
            <select id="nameList" name="nameList" form="formChangeList" required>
                <option selected value=""><?php echo Text::_('COM_ADMINISTRATIVETOOLS_SELECT_LIST'); ?></option>
                <?php
                foreach ($this->list as $vl_list) {
                    ?>
                    <option value="<?php echo $vl_list->id; ?>"><?php echo $vl_list->label; ?></option>
                    <?php
                }
                ?>
            </select>
        </div>
    </div>

    <div class="control-group">
        <label class="control-label" for="name"><?php echo Text::_('COM_ADMINISTRATIVETOOLS_CHANGE_LIST_NEW_NAME_FORM_LABEL'); ?></label>
        <div class="controls">
            <input form="formChangeList" type="text" class="form-control" id="name" name="name" required
                   placeholder="<?php echo Text::_('COM_ADMINISTRATIVETOOLS_CHANGE_LIST_NEW_NAME_FORM_LABEL'); ?>">
        </div>
    </div>

    <div class="control-group">
        <div class="controls">
            <button form="formChangeList" type="submit"
                    class="btn btn-success"><?php echo Text::_('COM_ADMINISTRATIVETOOLS_TRANSFORMATION_BTN_SUBMIT_TITLE'); ?></button>
        </div>
    </div>
</form>