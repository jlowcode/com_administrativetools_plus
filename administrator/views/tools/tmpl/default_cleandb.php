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

<div class="control-group">
      <label class="control-label" for="listTrans">
            <?php echo FText::_('Tabelas não encontradas no Fabrik'); ?>
      </label>
      <div class="controls">
            <ul class="list-unstyled">
                  <li>prefixo_tabela_x</li>
                  <li>prefixo_tabela_y</li>
                  <li>prefixo_tabela_z</li>
            </ul>
      </div>
</div>

<div class="control-group">
      <label class="control-label" for="listTrans">
            <?php echo FText::_('Tabelas não encontradas no Fabrik'); ?>
      </label>
      <div class="controls">
            <ul class="list-unstyled">
                  <li>tabela_elemento_1</li>
                  <li>tabela_elemento_2</li>
                  <li>tabela_elemento_3</li>
            </ul>
      </div>
</div>

