<?php
use \Joomla\CMS\Language\Text;

// No direct access
defined('_JEXEC') or die('Restricted access');
?>

<div class="panel panel-primary">
    <div class="panel-heading">
        <h3 class="panel-title"><?php echo Text::_('COM_ADMINISTRATIVETOOLS_IMPORT_EXPORT_LISTS_EXPORT_LISTFILE'); ?></h3>
    </div>
    <div class="panel-body">
        <form action="<?php echo JRoute::_('index.php?option=com_administrativetools&task=tools.exportList'); ?>" method="post"
        class="form-inline" id="formExportList" name="formExportList" enctype="multipart/form-data">

            <div class="form-group">
                <label for="lists"><strong><?php echo Text::_('COM_ADMINISTRATIVETOOLS_IMPORT_EXPORT_LISTS_LABEL'); ?></strong></label><br>
                <select multiple required class="form-control" id="lists" name="lists[]" size="10" style="width: 30%;">
                    <?php
                    foreach ($this->fabrikLists as $list) {
                    ?>
                        <option value="<?php echo $list->id ?>"><?php echo $list->label ?></option>
                    <?php
                    }
                    ?>
                </select>
            </div>
            <br/>
            <div class="form-group">
        <label for="exampleInputEmail1"><strong><?php echo Text::_('COM_ADMINISTRATIVETOOLS_PACKAGES_CREATE_RECORD_FORM_LABEL'); ?></strong></label>
        <fieldset class="btn-group radio">
            <label class="btn active btn-danger" id="btnN">
                <input form="formExportList" type="radio" name="record" id="opRecord0" value="0">
                <?php echo Text::_('JNO'); ?>
            </label>

            <label class="btn" id="btnS">
                <input form="formExportList" type="radio" name="record" id="opRecord1" value="1">
                <?php echo Text::_('JYES'); ?>
            </label>
        </fieldset>
    </div>

    <input type="hidden" value="0" form="formExportList" id="recordDB" name="recordDB">

            <button form="formExportList" type="submit" class="btn btn-success">
                <i class="icon-archive"></i> <?php echo Text::_('COM_ADMINISTRATIVETOOLS_IMPORT_EXPORT_LISTS_EXPORT_BTN'); ?>
            </button>

            <br/>

        </form>
    </div>
</div>

<div class="panel panel-primary">
    <div class="panel-heading">
        <h3 class="panel-title"><?php echo Text::_('COM_ADMINISTRATIVETOOLS_IMPORT_EXPORT_LISTS_IMPORT_LISTFILE'); ?></h3>
    </div>
    <div class="panel-body">
        <form action="<?php echo JRoute::_('index.php?option=com_administrativetools&task=tools.importList'); ?>" method="post"
        class="form-inline" id="formImportList" name="formImportList" enctype="multipart/form-data">

            <div class="form-group">
                <label for="listFiles"><strong><?php echo Text::_('COM_ADMINISTRATIVETOOLS_PACKAGES_UPLOAD_FILE_FORM_LABEL'); ?> </strong></label>
                <input id="listFiles" required type="file" name="listFile" form="formImportList">
                <br><br>
                <button form="formImportList" type="submit" class="btn btn-primary">
                    <span class="icon-upload icon-white"></span> <?php echo Text::_('COM_ADMINISTRATIVETOOLS_IMPORT_EXPORT_LISTS_IMPORT_BTN'); ?>
                </button>
            </div>


        </form>
    </div>
</div>
