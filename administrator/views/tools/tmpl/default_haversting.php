<?php
use \Joomla\CMS\Language\Text;

// No direct access
defined('_JEXEC') or die('Restricted access');
?>
<form class="form-horizontal" id="formHarvesting" name="formHarvesting" method="post" enctype="multipart/form-data"
      action="<?php echo JRoute::_('index.php?option=com_administrativetools&task=tools.submitHarvesting'); ?>">
    <div class="control-group">
        <label class="control-label"
               for="linkHarvest"><?php echo Text::_('COM_ADMINISTRATIVETOOLS_HARVESTING_FIELD_LABEL0'); ?></label>
        <div class="controls">
            <div class="span4">
                <input required type="text" id="linkHarvest" name="linkHarvest" form="formHarvesting"
                       placeholder="<?php echo Text::_('COM_ADMINISTRATIVETOOLS_HARVESTING_FIELD_PLACEHOLDER'); ?>"
                       value="">
                <button class="btn btn-warning" type="button"
                        id="btnRepository"><?php echo Text::_('COM_ADMINISTRATIVETOOLS_HARVESTING_BTN_TITLE0'); ?></button>
            </div>

        </div>
    </div>

    <div class="control-group">
        <label class="control-label" for="listHarvert"><?php echo Text::_('COM_ADMINISTRATIVETOOLS_LISTS'); ?></label>
        <div class="controls">
            <select id="listHarvert" name="listHarvert" form="formHarvesting" required>
                <option selected value=""><?php echo Text::_('COM_ADMINISTRATIVETOOLS_SELECT_LIST'); ?></option>
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
        <label class="control-label"
               for="listTrans"><?php echo Text::_('COM_ADMINISTRATIVETOOLS_HARVESTING_FIELD_LABEL12'); ?></label>
        <div class="controls">
            <button disabled data-toggle="modal" data-target="#mdHarvestHeader" type="button" id="btnMapHeader"
                    class="btn" title="<?php echo Text::_('COM_ADMINISTRATIVETOOLS_HARVESTING_FIELD_LABEL1'); ?>">
                <i
                        class="icon-list"></i> <?php echo Text::_('COM_ADMINISTRATIVETOOLS_HARVESTING_BTN_TITLE1'); ?>
            </button>
        </div>
    </div>

    <div class="control-group">
        <label class="control-label"
               for="listTrans"><?php echo Text::_('COM_ADMINISTRATIVETOOLS_HARVESTING_FIELD_LABEL1'); ?></label>
        <div class="controls">
            <button disabled data-toggle="modal" data-target="#mdHarvestElement" type="button" id="btnMapElement"
                    class="btn"
                    title="<?php echo Text::_('COM_ADMINISTRATIVETOOLS_HARVESTING_FIELD_LABEL1'); ?>"><i
                        class="icon-list"></i> <?php echo Text::_('COM_ADMINISTRATIVETOOLS_HARVESTING_BTN_TITLE1'); ?>
            </button>
        </div>
    </div>

    <div class="control-group">
        <label class="control-label"
               for="downloadHarvest"><?php echo Text::_('COM_ADMINISTRATIVETOOLS_HARVESTING_FIELD_LABEL2'); ?></label>
        <div class="controls">
            <select id="downloadHarvest" name="downloadHarvest" form="formHarvesting" disabled>
                <option selected value=""><?php echo Text::_('COM_MEDIA_PITT_OPTION_1'); ?></option>
            </select>
        </div>
    </div>

    <div class="control-group">
        <label class="control-label"
               for="extractTextHarvert"><?php echo Text::_('COM_ADMINISTRATIVETOOLS_HARVESTING_FIELD_LABEL4'); ?></label>
        <div class="controls">
            <select id="extractTextHarvert" name="extractTextHarvert" form="formHarvesting" disabled>
                <option selected value=""><?php echo Text::_('COM_MEDIA_PITT_OPTION_1'); ?></option>
            </select>
        </div>
    </div>

    <div class="control-group">
        <label class="control-label"><?php echo Text::_('COM_ADMINISTRATIVETOOLS_HARVESTING_FIELD_LABEL5'); ?></label>
    </div>

    <div class="control-group">
        <label class="radio">
            <input required checked type="radio" name="syncHarvest" id="syncHarvest0" value="0" form="formHarvesting">
            <?php echo Text::_('COM_ADMINISTRATIVETOOLS_HARVESTING_FIELD_LABEL6'); ?>
        </label>
    </div>

    <div class="control-group">
        <div class="controls">
            <label><strong><?php echo Text::_('COM_ADMINISTRATIVETOOLS_HARVESTING_FIELD_LABEL7'); ?></strong></label>
        </div>
    </div>

    <div class="control-group">
        <label class="radio control-label">
            <input required type="radio" name="syncHarvest" id="syncHarvest1" value="1" form="formHarvesting">
            <?php echo Text::_('COM_ADMINISTRATIVETOOLS_HARVESTING_FIELD_LABEL8'); ?>
        </label>

        <div class="controls">
            <div class="row">
                <div class="span3">
                    <select id="dateListHarvest" name="dateListHarvest" form="formHarvesting" disabled>
                        <option selected value=""><?php echo Text::_('COM_MEDIA_PITT_OPTION_1'); ?></option>
                    </select>
                </div>

                <div class="span2 text-right">
                    <label class="control-label"><?php echo Text::_('COM_ADMINISTRATIVETOOLS_HARVESTING_FIELD_LABEL9'); ?></label>
                </div>

                <div class="span4">
                    <select id="dateRepositoryHarvest" name="dateRepositoryHarvest" form="formHarvesting" disabled>
                        <option selected
                                value=""><?php echo Text::_('COM_ADMINISTRATIVETOOLS_DUBLIN_CORE_TYPE_LABEL'); ?></option>
                        <optgroup label="Header">
                            <option value="<?php echo Text::_('COM_ADMINISTRATIVETOOLS_DUBLIN_CORE_TYPE_OPTION55'); ?>"><?php echo Text::_('COM_ADMINISTRATIVETOOLS_DUBLIN_CORE_TYPE_OPTION55'); ?></option>
                        </optgroup>
                        <optgroup label="Metadata">
                            <option value="dc:date"><?php echo Text::_('COM_ADMINISTRATIVETOOLS_DUBLIN_CORE_TYPE_OPTION4'); ?></option>
                        </optgroup>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="control-group">
        <div class="controls">
            <input type="hidden" name="idHarvest" id="idHarvest" value="" form="formHarvesting">
            <button type="submit" id="btnSave" name="btnSubmit" form="formHarvesting" class="btn btn-success"
                    value="btnSave">
                <i class="icon-apply icon-white"></i> <?php echo Text::_('COM_ADMINISTRATIVETOOLS_HARVESTING_BTN_TITLE2'); ?>
            </button>
            <button type="submit" id="btnSaveRun" name="btnSubmit" form="formHarvesting" class="btn" value="btnSaveRun">
                <i class="icon-play text-success"></i> <?php echo Text::_('COM_ADMINISTRATIVETOOLS_HARVESTING_BTN_TITLE3'); ?>
            </button>
        </div>
    </div>
</form>

<table class="table">
    <thead>
    <tr>
        <th width="5%"><?php echo Text::_('COM_ADMINISTRATIVETOOLS_HARVESTING_TABLE_TH_FIELD_NAME1'); ?></th>
        <th width="35%"><?php echo Text::_('COM_ADMINISTRATIVETOOLS_HARVESTING_TABLE_TH_FIELD_NAME2'); ?></th>
        <th width="30%"><?php echo Text::_('COM_ADMINISTRATIVETOOLS_LIST'); ?></th>
        <th width="5%"><?php echo Text::_('COM_ADMINISTRATIVETOOLS_HARVESTING_TABLE_TH_FIELD_NAME3'); ?></th>
        <th width="15%"><?php echo Text::_('COM_ADMINISTRATIVETOOLS_HARVESTING_TABLE_TH_FIELD_NAME4'); ?></th>
        <th width="5%"><?php echo Text::_('COM_ADMINISTRATIVETOOLS_HARVESTING_TABLE_TH_FIELD_NAME6'); ?></th>
        <th width="10%"><?php echo Text::_('COM_ADMINISTRATIVETOOLS_HARVESTING_TABLE_TH_FIELD_NAME5'); ?></th>
    </tr>
    </thead>
    <tbody>
    <?php
    foreach ($this->dados_tb_harvest as $value) {
        ?>
        <tr id="rowTable<?php echo $value->id; ?>">
            <td width="5%"><?php echo $value->id; ?></td>
            <td width="35%"><?php echo $value->repository; ?></td>
            <td width="20%"><?php echo $value->label; ?></td>
            <td width="5%" id="btn_status<?php echo $value->id; ?>">
                <?php
                if ($value->status === '1') {
                    ?>
                    <button type="button" class="btn" onclick="enableDisableHarvesting(<?php echo $value->id; ?>,'0');">
                        <i class="icon-ok text-success"></i></button>
                    <?php
                } else {
                    ?>
                    <button type="button" class="btn" onclick="enableDisableHarvesting(<?php echo $value->id; ?>,'1');">
                        <i class="icon-remove text-error"></i></button>
                    <?php
                }
                ?>
            </td>
            <td width="15%"><?php echo $value->date_exec; ?></td>
            <td width="5%"><?php echo $value->page_xml; ?></td>
            <td width="15%">
                <form class="formListRarvest" id="formListBtnGroup<?php echo $value->id; ?>" name="formListBtnGroup"
                      method="post" enctype="multipart/form-data"
                      action="<?php echo JRoute::_('index.php?option=com_fabrik&task=administrativetools.submitHarvesting'); ?>">
                    <input type="hidden" form="formListBtnGroup<?php echo $value->id; ?>"
                           value="<?php echo $value->id; ?>" name="idHarvest">
                </form>
                <div class="btn-group">
                    <button type="button"
                            onclick="deleteHarvesting(<?php echo $value->id; ?>,'<?php echo $this->text_message; ?>')"
                            class="btn"><i class="icon-trash text-error"></i></button>
                    <button class="btn" onclick="editHarvesting(<?php echo $value->id; ?>);"><i
                                class="icon-edit text-info"></i></button>
                    <button class="btn" type="submit" form="formListBtnGroup<?php echo $value->id; ?>"
                            value="btnRumList" name="btnSubmit"><i class="icon-play text-success"></i></button>
                </div>
            </td>
        </tr>
        <?php
    }
    ?>
    </tbody>
</table>

<div class="modal hide fade" id="mdHarvestHeader" role="dialog">
    <div class="modal-header">
        <div class="row">
            <div class="span4"><strong><?php echo Text::_('COM_ADMINISTRATIVETOOLS_ELEMENT'); ?></strong></div>
            <div class="span4 text-center">
                <strong><?php echo Text::_('COM_ADMINISTRATIVETOOLS_DUBLIN_CORE_TYPE_LABEL'); ?></strong></div>
            <div class="span4 text-right">
                <button id="btnAddHarvestingHeader" class="add btn button btn-success"><i class="icon-plus"></i>
                </button>
            </div>
        </div>
    </div>
    <div class="modal-body" id="mdMapHeader"></div>
    <div class="modal-footer">
        <button class="btn btn-primary" data-dismiss="modal" aria-hidden="true">Close</button>
    </div>
</div>

<div class="modal hide fade" id="mdHarvestElement" role="dialog">
    <div class="modal-header">
        <div class="row">
            <div class="span4"><strong><?php echo Text::_('COM_ADMINISTRATIVETOOLS_ELEMENT'); ?></strong></div>
            <div class="span4 text-center">
                <strong><?php echo Text::_('COM_ADMINISTRATIVETOOLS_DUBLIN_CORE_TYPE_LABEL'); ?></strong></div>
            <div class="span4 text-right">
                <button id="btnAddHarvesting" class="add btn button btn-success"><i class="icon-plus"></i></button>
            </div>
        </div>
    </div>
    <div class="modal-body" id="mdMapElement"></div>
    <div class="modal-footer">
        <button class="btn btn-primary" data-dismiss="modal" aria-hidden="true">Close</button>
    </div>
</div>