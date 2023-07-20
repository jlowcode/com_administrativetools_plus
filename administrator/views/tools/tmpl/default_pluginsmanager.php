<?php
defined('_JEXEC') or die('Restricted access');
?>
	
	<div class="panel panel-primary">
		<div class="panel-heading">
			<h3 class="panel-title"><?php echo FText::_('COM_ADMINISTRATIVETOOLS_PLUGINS_MANAGER_LIST'); ?></h3>
		</div>
   </div>

	<div class="panel-body">
		<div class="control-group">
			<div class="controls">
				<select id="pluginsManagerTypeList" name="pluginsManagerTypeList" required>
					<option selected 	value="0"><?php echo FText::_('COM_ADMINISTRATIVETOOLS_PLUGINS_MANAGER_TYPE_VALUE0'); ?></option>
					<option 				value="1"><?php echo FText::_('COM_ADMINISTRATIVETOOLS_PLUGINS_MANAGER_TYPE_VALUE1'); ?></option>
					<option 				value="2"><?php echo FText::_('COM_ADMINISTRATIVETOOLS_PLUGINS_MANAGER_TYPE_VALUE2'); ?></option>
				</select>
			</div>

			<div class="controls">
				<select id="pluginsManagerChooseList" name="pluginsManagerChooseList" required>
					<option selected 	value="0"> --- </option>
				</select>
			</div>

			<br>

			
			<div class="controls" id="divPluginsManagerTypeParams">
				<label><strong>Configurações encontradas</strong></label>
				<select id="pluginsManagerTypeParams" name="pluginsManagerTypeParams" required>
					<option selected 	value="0"> --- </option>
				</select>
			</div>


			<div class="controls">
				<select id="pluginsManagerAction" name="pluginsManagerAction" required>
					<option selected 	value="0"> --- </option>
					<option 				value="1"><?php echo FText::_('COM_ADMINISTRATIVETOOLS_PLUGINS_MANAGER_ACTION1'); ?></option>
					<option 				value="2"><?php echo FText::_('COM_ADMINISTRATIVETOOLS_PLUGINS_MANAGER_ACTION2'); ?></option>
				</select>
			</div>

			<br>

			
			
			<div class="form-group" id="divSelectFieldsPluginsManager">
				<!-- <label for="lists"><strong><?php echo FText::_('COM_ADMINISTRATIVETOOLS_IMPORT_EXPORT_LISTS_LABEL'); ?></strong></label><br> -->
				<label><strong>Escolha as listas ou formulários que receberão a configuração</strong></label>
			
				<select multiple required class="form-control" id="selectFieldsPluginsManager" name="selectFieldsPluginsManager" size="30" style="width: 30%;"></select>
			
			<br/>
	
			
				<div class="controls">
					<button type="submit" id="btnPluginsManagerExecute" class="btn btn btn-success">
						<?php echo FText::_('COM_ADMINISTRATIVETOOLS_PLUGINS_MANAGER_BTN_EXECUTE'); ?>
					</button>
				</div>
			</div>
			
		</div>	
	</div>	
	<br/>