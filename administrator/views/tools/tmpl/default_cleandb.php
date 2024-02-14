<?php
use \Joomla\CMS\Language\Text;

defined('_JEXEC') or die('Restricted access');
?>
	

	<div class="control-group">
		<div class="controls">
			<button form="formCleanDB" type="submit" id="btnShowCleanDB" class="btn btn-info"> <i class="icon-search"></i>
				<?php echo Text::_('COM_ADMINISTRATIVETOOLS_TRANSFORMATION_BTN_VERIFY'); ?>
			</button>
		</div>
	</div>
	
	</br>
 
	<div class="row control-group" style="-webkit-text-size-adjust: 100%; box-sizing: border-box; margin-right: -15px; margin-left: -15px;">
		
		<div class="col-md-6" id="col1" style="-webkit-text-size-adjust: 100%;  box-sizing: border-box; position: relative; float: left;width: 33.33333333%;">
			<div class="form-group">
				<!-- <label for="lists"><strong><?php echo Text::_('COM_ADMINISTRATIVETOOLS_IMPORT_EXPORT_LISTS_LABEL'); ?></strong></label><br> -->
				<label for="lists"><strong>Tabelas não encontradas no FABRIK</strong></label><br>
				
				<select multiple required class="form-control" id="selectTablesCleanDB" name="selectTablesCleanDB[]" size="30" style="width: 90%;"></select>
			</div>
			<br/>
		</div>

		<div class="col-md-6" id="col2" style="-webkit-text-size-adjust: 100%;  box-sizing: border-box; position: relative; float: left;width: 33.33333333%;">
			<div class="form-group">
				<!-- <label for="lists"><strong><?php echo Text::_('COM_ADMINISTRATIVETOOLS_IMPORT_EXPORT_LISTS_LABEL'); ?></strong></label><br> -->
				<label for="lists"><strong>Campos não encontrados no FABRIK</strong></label><br>
				
				<select multiple required class="form-control" id="selectFieldsCleanDB" name="selectFieldsCleanDB[]" size="30" style="width: 90%;"></select>
			</div>
			<br/>
		</div>
	</div>	
 
	<div class="control-group">
		<div class="controls">
			<button id="btnDelCleanDB" class=" btn button btn-danger"><i class="icon-trash"></i>
				<?php echo Text::_('COM_ADMINISTRATIVETOOLS_TRANSFORMATION_BTN_DELETE'); ?>
			</button>

			
		</div>
	</div>
