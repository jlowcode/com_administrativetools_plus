<?php
defined('_JEXEC') or die('Restricted access');
?>

<form class="form-horizontal" id="formTransformation" name="formTransformation" method="post" enctype="multipart/form-data"
      action="<?php echo JRoute::_('index.php?option=com_administrativetools&task=tools.rumTransformationTool'); ?>">
    <div class="control-group">
        <label class="control-label" for="listTrans"><?php echo FText::_('COM_ADMINISTRATIVETOOLS_LISTS'); ?></label>
        <div class="controls">
            <select id="listTrans" name="listTrans" form="formTransformation" required>
                <option selected value=""><?php echo FText::_('COM_ADMINISTRATIVETOOLS_SELECT_LIST'); ?></option>
                <?php
                foreach ($this->list as $vl_list) {
                    ?>
                    <option value="<?php echo $vl_list->id; ?>"><?php echo $vl_list->label; ?></option>
                    <
                    <?php
                }
                ?>
            </select>
        </div>
    </div>

    <div class="control-group">
        <label class="control-label" for="combo_elementSourceTrans"><?php echo FText::_('COM_ADMINISTRATIVETOOLS_TRANSFORMATION_FIELD_LABEL'); ?></label>
        <div class="controls" id="combo_elementSourceTrans"></div>
    </div>

    <div class="control-group">
        <label class="control-label" for="typeTrans"><?php echo FText::_('COM_ADMINISTRATIVETOOLS_TRANSFORMATION_TYPE_LABEL'); ?></label>
        <div class="controls">
            <select id="typeTrans" name="typeTrans" form="formTransformation" required>
                <option value=""><?php echo FText::_('COM_ADMINISTRATIVETOOLS_TRANSFORMATION_TYPE_VALUE0'); ?></option>
                <option value="1"><?php echo FText::_('COM_ADMINISTRATIVETOOLS_TRANSFORMATION_TYPE_VALUE1'); ?></option>
                <option value="2"><?php echo FText::_('COM_ADMINISTRATIVETOOLS_TRANSFORMATION_TYPE_VALUE2'); ?></option>
                <option value="3"><?php echo FText::_('COM_ADMINISTRATIVETOOLS_TRANSFORMATION_TYPE_VALUE3'); ?></option>
                <option value="4"><?php echo FText::_('COM_ADMINISTRATIVETOOLS_TRANSFORMATION_TYPE_VALUE4'); ?></option>
                <option value="5"><?php echo FText::_('COM_ADMINISTRATIVETOOLS_TRANSFORMATION_TYPE_VALUE5'); ?></option>
                <option value="6"><?php echo FText::_('COM_ADMINISTRATIVETOOLS_TRANSFORMATION_TYPE_VALUE6'); ?></option>
            </select>
        </div>
    </div>

    <div class="control-group" id="row_updateDB">
        <label class="control-label" for="updateDB"><?php echo FText::_('COM_ADMINISTRATIVETOOLS_TRANSFORMATION_UPDATE_LABEL'); ?></label>
        <div class="controls">
            <select id="updateDB" name="updateDB" form="formTransformation">
                <option value="<?php echo FText::_('COM_ADMINISTRATIVETOOLS_TRANSFORMATION_UPDATE_DELETE_VALUE1'); ?>"><?php echo FText::_('COM_ADMINISTRATIVETOOLS_TRANSFORMATION_UPDATE_DELETE_VALUE1'); ?></option>
                <option selected value="<?php echo FText::_('COM_ADMINISTRATIVETOOLS_TRANSFORMATION_UPDATE_DELETE_VALUE2'); ?>"><?php echo FText::_('COM_ADMINISTRATIVETOOLS_TRANSFORMATION_UPDATE_DELETE_VALUE2'); ?></option>
                <option value="<?php echo FText::_('COM_ADMINISTRATIVETOOLS_TRANSFORMATION_UPDATE_DELETE_VALUE3'); ?>"><?php echo FText::_('COM_ADMINISTRATIVETOOLS_TRANSFORMATION_UPDATE_DELETE_VALUE3'); ?></option>
                <option value="<?php echo FText::_('COM_ADMINISTRATIVETOOLS_TRANSFORMATION_UPDATE_DELETE_VALUE4'); ?>"><?php echo FText::_('COM_ADMINISTRATIVETOOLS_TRANSFORMATION_UPDATE_DELETE_VALUE4'); ?></option>
            </select>
        </div>
    </div>

    <div class="control-group" id="row_deleteDB">
        <label class="control-label" for="deleteDB"><?php echo FText::_('COM_ADMINISTRATIVETOOLS_TRANSFORMATION_DELETE_LABEL'); ?></label>
        <div class="controls">
            <select id="deleteDB" name="deleteDB" form="formTransformation">
                <option value="<?php echo FText::_('COM_ADMINISTRATIVETOOLS_TRANSFORMATION_UPDATE_DELETE_VALUE1'); ?>"><?php echo FText::_('COM_ADMINISTRATIVETOOLS_TRANSFORMATION_UPDATE_DELETE_VALUE1'); ?></option>
                <option selected value="<?php echo FText::_('COM_ADMINISTRATIVETOOLS_TRANSFORMATION_UPDATE_DELETE_VALUE2'); ?>"><?php echo FText::_('COM_ADMINISTRATIVETOOLS_TRANSFORMATION_UPDATE_DELETE_VALUE2'); ?></option>
                <option value="<?php echo FText::_('COM_ADMINISTRATIVETOOLS_TRANSFORMATION_UPDATE_DELETE_VALUE3'); ?>"><?php echo FText::_('COM_ADMINISTRATIVETOOLS_TRANSFORMATION_UPDATE_DELETE_VALUE3'); ?></option>
                <option value="<?php echo FText::_('COM_ADMINISTRATIVETOOLS_TRANSFORMATION_UPDATE_DELETE_VALUE4'); ?>"><?php echo FText::_('COM_ADMINISTRATIVETOOLS_TRANSFORMATION_UPDATE_DELETE_VALUE4'); ?></option>
            </select>
        </div>
    </div>

    <div class="control-group" id="row_combo_elementDestTrans">
        <label class="control-label" for="combo_elementDestTrans"><?php echo FText::_('COM_ADMINISTRATIVETOOLS_TRANSFORMATION_FIELD_ELEMENT_LABEL'); ?></label>
        <div class="controls" id="combo_elementDestTrans"></div>
    </div>

    <div class="control-group" id="row_delimiterTransf">
        <label class="control-label" for="delimiterTransf"><?php echo FText::_('COM_ADMINISTRATIVETOOLS_TRANSFORMATION_DELIMITER_LABEL'); ?></label>
        <div class="controls">
            <input type="text" id="delimiterTransf" name="delimiterTransf" form="formTransformation" class="span1">
        </div>
    </div>

    <div class="control-group" id="row_repeat">
        <label class="control-label" for="delimiterTransf"><?php echo FText::_('COM_ADMINISTRATIVETOOLS_TRANSFORMATION_TABLE_REPEAT_LABEL'); ?></label>
        <div class="controls">
            <input type="checkbox"  id="tableRepeat" name="tableRepeat" form="formTransformation" value="1">
        </div>
    </div>

    <div class="control-group" id="row_thumbs_crops">
        <label class="control-label" for="delimiterTransf"><?php echo FText::_('COM_ADMINISTRATIVETOOLS_TRANSFORMATION_REFAZ_THUMBS_CROPS_LABEL'); ?></label>
        <div class="controls">
            <input type="checkbox" id="thumbsCrops" name="thumbsCrops" form="formTransformation" value="1">
        </div>
    </div>

    <div class="control-group">
        <div class="controls">
            <button form="formTransformation" type="submit"
                    class="btn btn-success"><?php echo FText::_('COM_ADMINISTRATIVETOOLS_TRANSFORMATION_BTN_SUBMIT_TITLE'); ?></button>
        </div>
    </div>
</form>