<?php
    // Fabrik sync lists 1.0
    defined('_JEXEC') or die('Restricted access');
    if(!$this->connection->port) {
        $port = '3306';
    } else {
        $port = $this->connection->port;
    }
?>

<form action="<?php echo JRoute::_('index.php?option=com_administrativetools&task=tools.submitSyncLists'); ?>" class="form-horizontal" id="submitSyncLists" name="submitSyncLists" method="post" enctype="multipart/form-data">
    <!-- Begin - Fabrik sync lists 2.0 -->
    <!-- Id Task: 13 -->
    <div class="control-group">
        <label class="control-label" for="urlApi"><?php echo FText::_('COM_ADMINISTRATIVETOOLS_SYNC_LIST_URL_API'); ?></label>
        <div class="controls">
            <input form="submitSyncLists" type="text" value="<?php echo $this->connection->urlApi ?>" class="form-control" id="urlApi" name="urlApi" required placeholder="<?php echo FText::_('COM_ADMINISTRATIVETOOLS_SYNC_LIST_URL_API'); ?>">
        </div>
    </div>
    
    <div class="control-group">
        <label class="control-label" for="keyApi"><?php echo FText::_('COM_ADMINISTRATIVETOOLS_SYNC_LIST_KEY_API'); ?></label>
        <div class="controls">
            <input form="submitSyncLists" type="text" value="<?php echo $this->connection->keyApi ?>" class="form-control" id="keyApi" name="keyApi" required placeholder="<?php echo FText::_('COM_ADMINISTRATIVETOOLS_SYNC_LIST_KEY_API'); ?>">
        </div>
    </div>

    <div class="control-group">
        <label class="control-label" for="secretApi"><?php echo FText::_('COM_ADMINISTRATIVETOOLS_SYNC_LIST_SECRET_API'); ?></label>
        <div class="controls">
            <input form="submitSyncLists" type="text" value="<?php echo $this->connection->secretApi ?>" class="form-control" id="secretApi" name="secretApi" required placeholder="<?php echo FText::_('COM_ADMINISTRATIVETOOLS_SYNC_LIST_SECRET_API'); ?>">
        </div>
    </div>
    <!-- End - Fabrik sync lists 2.0 -->

    <div class="control-group">
        <label class="control-label" for="host"><?php echo FText::_('COM_ADMINISTRATIVETOOLS_SYNC_LIST_IP_HOST'); ?></label>
        <div class="controls">
            <input form="submitSyncLists" type="text" value="<?php echo $this->connection->host ?>" class="form-control" id="host" name="host" placeholder="<?php echo FText::_('COM_ADMINISTRATIVETOOLS_SYNC_LIST_IP_HOST'); ?>">
        </div>
    </div>

    <div class="control-group">
        <label class="control-label" for="port"><?php echo FText::_('COM_ADMINISTRATIVETOOLS_SYNC_LIST_PORT'); ?></label>
        <div class="controls">
            <input form="submitSyncLists" type="text" value="<?php echo $port ?>" class="form-control" id="port" name="port" placeholder="<?php echo FText::_('COM_ADMINISTRATIVETOOLS_SYNC_LIST_PORT'); ?>">
        </div>
    </div>

    <div class="control-group">
        <label class="control-label" for="name"><?php echo FText::_('COM_ADMINISTRATIVETOOLS_SYNC_LIST_NAME_DB'); ?></label>
        <div class="controls">
            <input form="submitSyncLists" type="text" value="<?php echo $this->connection->name ?>" class="form-control" id="name" name="name" placeholder="<?php echo FText::_('COM_ADMINISTRATIVETOOLS_SYNC_LIST_NAME_DB'); ?>">
        </div>
    </div>

    <div class="control-group">
        <label class="control-label" for="prefix"><?php echo FText::_('COM_ADMINISTRATIVETOOLS_SYNC_LIST_JOOMLA_PREFIX'); ?></label>
        <div class="controls">
            <input form="submitSyncLists" type="text" value="<?php echo $this->connection->prefix ?>" class="form-control" id="prefix" name="prefix" placeholder="<?php echo FText::_('COM_ADMINISTRATIVETOOLS_SYNC_LIST_JOOMLA_PREFIX'); ?>">
        </div>
    </div>

    <div class="control-group">
        <label class="control-label" for="user"><?php echo FText::_('COM_ADMINISTRATIVETOOLS_SYNC_LIST_USER'); ?></label>
        <div class="controls">
            <input form="submitSyncLists" type="text" value="<?php echo $this->connection->user ?>" class="form-control" id="user" name="user" placeholder="<?php echo FText::_('COM_ADMINISTRATIVETOOLS_SYNC_LIST_USER'); ?>">
        </div>
    </div>

    <div class="control-group">
        <label class="control-label" for="password"><?php echo FText::_('COM_ADMINISTRATIVETOOLS_SYNC_LIST_PASSWORD'); ?></label>
        <div class="controls">
            <input form="submitSyncLists" type="password" value="<?php echo $this->connection->password ?>" class="form-control" id="password" name="password" placeholder="<?php echo FText::_('COM_ADMINISTRATIVETOOLS_SYNC_LIST_PASSWORD'); ?>">
        </div>
    </div>

    <div id="div_buttons">
        <div class="control-group">
            <div class="controls">
                <input class="btn btn-info" type="submit" name="connectSync" formmethod="post" form="submitSyncLists" value="<?php echo FText::_('COM_ADMINISTRATIVETOOLS_TRANSFORMATION_BTN_CONNECT'); ?>">
            </div>
        </div>

        <div class="control-group">
            <div class="controls">
                <input class="btn btn-success" type="submit" name="saveConfiguration" formmethod="post" form="submitSyncLists" value="<?php echo FText::_('COM_ADMINISTRATIVETOOLS_TRANSFORMATION_BTN_SAVE'); ?>">
            </div>
        </div>
    </div>

    <div>
        <strong id="subtitle"><?php echo FText::_('COM_ADMINISTRATIVETOOLS_SYNC_LIST_LABEL1'); ?></strong>
        <div style="margin-bottom: 50px;" id="lists_finded">

        </div>
        <div id="div_sync">
            <strong><?php echo FText::_('COM_ADMINISTRATIVETOOLS_SYNC_LIST_LABEL2'); ?></strong>
            <div class="type_sync">
                <p><?php echo FText::_('COM_ADMINISTRATIVETOOLS_SYNC_LIST_LABEL3'); ?></p>
                <div class="first_type">
                    <input type="radio" name="model_type" value="identical" id="model_type_identical" checked>
                    <label for="model_type_identical"><?php echo FText::_('COM_ADMINISTRATIVETOOLS_SYNC_LIST_LABEL_IDENTICAL'); ?></label>
                </div>
                <div class="first_type">
                    <input type="radio" name="model_type" value="merge" id="model_type_merge">
                    <label for="model_type_merge"><?php echo FText::_('COM_ADMINISTRATIVETOOLS_SYNC_LIST_LABEL_MERGE'); ?></label>
                </div>
                <div>
                    <input type="radio" name="model_type" value="none" id="model_type_none">
                    <label for="model_type_none"><?php echo FText::_('COM_ADMINISTRATIVETOOLS_SYNC_LIST_LABEL_NONE'); ?></label>
                </div>
            </div>
            <div class="type_sync">
                <p><?php echo FText::_('COM_ADMINISTRATIVETOOLS_SYNC_LIST_LABEL4'); ?></p>
                <div class="first_type">
                    <input type="radio" name="data_type" value="identical" id="data_type_identical" checked>
                    <label for="data_type_identical"><?php echo FText::_('COM_ADMINISTRATIVETOOLS_SYNC_LIST_LABEL_IDENTICAL'); ?></label>
                </div>
                <div class="first_type">
                    <input type="radio" name="data_type" value="merge" id="data_type_merge">
                    <label for="data_type_merge"><?php echo FText::_('COM_ADMINISTRATIVETOOLS_SYNC_LIST_LABEL_MERGE'); ?></label>
                </div>
                <div>
                    <input type="radio" name="data_type" value="none" id="data_type_none">
                    <label for="data_type_none"><?php echo FText::_('COM_ADMINISTRATIVETOOLS_SYNC_LIST_LABEL_NONE'); ?></label>
                </div>
            </div>
            <div class="type_sync">
                <p>Joomla:</p>
                <div class="first_type">
                    <input type="checkbox" name="joomla_menus" id="joomla_menus">
                    <label for="joomla_menus">Menus</label>
                </div>
                <div class="first_type">
                    <input type="checkbox" name="joomla_modules" id="joomla_modules">
                    <label for="joomla_modules"><?php echo FText::_('COM_ADMINISTRATIVETOOLS_SYNC_LIST_LABEL_MODULES'); ?></label>
                </div>
                <div class="first_type">
                    <input type="checkbox" name="joomla_themes" id="joomla_themes">
                    <label for="joomla_themes"><?php echo FText::_('COM_ADMINISTRATIVETOOLS_SYNC_LIST_LABEL_THEMES'); ?></label>
                </div>
                <div>
                    <input type="checkbox" name="joomla_extensions" id="joomla_extensions">
                    <label for="joomla_extensions"><?php echo FText::_('COM_ADMINISTRATIVETOOLS_SYNC_LIST_LABEL_EXTENSIONS'); ?></label>
                </div>
            </div>
        <div>

        <div id="div_buttons">
            <div class="control-group" id="div_button_search">
                <div class="controls">
                    <input class="btn btn-info" type="submit" name="searchLists" formmethod="post" form="submitSyncLists" value="<?php echo FText::_('COM_ADMINISTRATIVETOOLS_TRANSFORMATION_BTN_SEARCH_LISTS'); ?>">
                </div>
            </div>

            <div class="control-group">
                <div class="controls">
                    <input class="btn btn-success" type="submit" name="syncLists" formmethod="post" form="submitSyncLists" value="<?php echo FText::_('COM_ADMINISTRATIVETOOLS_TRANSFORMATION_BTN_SYNC'); ?>">
                </div>
            </div>
        </div>
    </div>
</form>