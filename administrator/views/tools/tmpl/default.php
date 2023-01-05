<?php
// No direct access
defined('_JEXEC') or die('Restricted access');
?>
<div class="col-md-12">
    <div class="row-fluid">
        <div class="col-md-12">
            <!-- Nav tabs -->
            <ul class="nav nav-tabs" role="tablist">
                <li role="presentation" class="<?php echo $this->tab1; ?>"><a href="#package" aria-controls=""
                                                                              role="tab" data-toggle="tab">
                        <?php echo FText::_('COM_ADMINISTRATIVETOOLS_TITLE_NAV_TABS_PACKEGE'); ?></a></li>
                <li role="presentation" class="<?php echo $this->tab4; ?>"><a href="#importandexportlists" aria-controls=""
                                                                              role="tab" data-toggle="tab">
                        <?php echo FText::_('COM_ADMINISTRATIVETOOLS_TITLE_NAV_TABS_IMPORT_EXPORT_LISTS'); ?></a></li>
                <li role="presentation" class="<?php echo $this->tab2; ?>"><a href="#transformation"
                                                                              aria-controls="profile" role="tab"
                                                                              data-toggle="tab">
                        <?php echo FText::_('COM_ADMINISTRATIVETOOLS_TITLE_NAV_TABS_TRANSFORMATION'); ?></a></li>
                <li role="presentation" class="<?php echo $this->tab3; ?>"><a href="#haversting"
                                                                              aria-controls="messages" role="tab"
                                                                              data-toggle="tab">
                        <?php echo FText::_('COM_ADMINISTRATIVETOOLS_TITLE_NAV_TABS_HAVERSTING'); ?></a></li>
                <li role="presentation" class="<?php echo $this->tab5; ?>"><a href="#chargelist"
                                                                              aria-controls="messages" role="tab"
                                                                              data-toggle="tab">
                        <?php echo FText::_('COM_ADMINISTRATIVETOOLS_TITLE_NAV_TABS_CHANGE_LIST'); ?></a></li>
                <!-- Fabrik sync lists 1.0 -->
                <li role="presentation" class="<?php echo $this->tab6; ?>"><a href="#synclist"
                                                                              aria-controls="messages" role="tab"
                                                                              data-toggle="tab">
                        <?php echo FText::_('COM_ADMINISTRATIVETOOLS_TITLE_NAV_TABS_SYNC_LIST'); ?></a></li>
            </ul>

            <!-- Tab panes -->
            <div class="tab-content">
                <div role="tabpanel" class="tab-pane <?php echo $this->tab1; ?>" id="package">
                    <?php echo $this->loadTemplate('packege'); ?>
                </div>
                <div role="tabpanel" class="tab-pane <?php echo $this->tab4; ?>" id="importandexportlists">
                    <?php echo $this->loadTemplate('importandexportlists'); ?>
                </div>
                <div role="tabpanel" class="tab-pane <?php echo $this->tab2; ?>" id="transformation">
                    <?php echo $this->loadTemplate('element_transformation'); ?>
                </div>
                <div role="tabpanel" class="tab-pane <?php echo $this->tab3; ?>" id="haversting">
                    <?php echo $this->loadTemplate('haversting'); ?>
                </div>
                <div role="tabpanel" class="tab-pane <?php echo $this->tab5; ?>" id="chargelist">
                    <?php echo $this->loadTemplate('change_list'); ?>
                </div>
                <!-- Fabrik sync lists 1.0 -->
                <div role="tabpanel" class="tab-pane <?php echo $this->tab6; ?>" id="synclist">
                    <?php echo $this->loadTemplate('sync_list'); ?>
                </div>
            </div>
        </div>
    </div>
</div>