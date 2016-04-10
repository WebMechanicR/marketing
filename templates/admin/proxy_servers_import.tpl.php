<h1><img class="catalog-icon" src="<?php echo $dir_images;?>icon.png" alt="icon"/>Импорт</h1>
<form action="<?php echo DIR_ADMIN; ?>?module=<?php echo $module;?>&action=proxy_import" method="post">
<?php if(($post_flag and $errorMessage) or !$post_flag) { ?>
          <div class="input_smart_file export_products_file">
               <span class="btn standart-size">
                   <span class="button">
                     <span><img class="bicon plus-s" src="<?php echo $dir_images;?>icon.png" alt="icon"/> Выбрать файл</span>
                   </span>
               </span>
            <span class="file_name"></span>
        <input type="file" name="file">
       </div><br>
	<ul class="form-lines export_products">
   <li>
       <label>CSV разделитель</label>
       <div class="input" style="width: 200px">
            <select name="separator" class="select">
                 <option value=";">Точка с запятой</option>
                 <option value=",">Запятая</option>
                 <option value="t">Табуляция</option>
            </select>
        </div>
    </li>
    <input type="hidden" name="operation" value="add"/>
    </ul>
    <div class="bt-set">
		<span class="btn standart-size blue hide-icon">
			  <button class="ajax_submit" >
                          <span>Импортировать</span>
                    </button>
		</span>
    </div>

<?php if($errorMessage) { ?>
		<p><strong><?php echo $errorMessage;?></strong><p>
<?php } ?>


<?php } if($post_flag and !$errorMessage and !$post_continue) { ?>
	<h2>Укажите соответствие полей импортируемого файла с полями в таблице базы данных.</h2>
        <input type = "hidden" name = "post_continue" value = "1"/>
        <input type = "hidden" name = "file_name" value = "<?php echo $fileName; ?>"/>
        <input type = "hidden" name = "operation" value = "<?php echo $operation; ?>"/>
        <input type = "hidden" name = "separator" value = "<?php echo $separator; ?>"/>
				<div class="product-table import_fields">
					<table>
						<thead>
							<tr>
								<th>Поля в файле</th>
								<th class="db_field">Поля в базе данных</th>
							</tr>
						</thead>
                        
						<tbody>
      	<?php
      		$i = 0;
      		foreach( $uploadedFields as $uploadedField) {  ?>
            	<tr>
                	<td>
						<?php echo $uploadedField; ?>
                     </td>
                     <td>
                        <div class="input">
                            <select name="fieldAssoc_<?php echo $i++; ?>" class="select">
                                <?php foreach($listFields as $myField){ ?>
                                    <option value = "<?php echo $myField; ?>" <?php echo (mb_strtolower(trim($myField)) == mb_strtolower(trim($uploadedField))) ? 'selected' : ''; ?>><?php echo $myField; ?></option>
                                <?php } ?>
                            </select>
                        </div>
                     </td>
                </tr>
		<?php } ?>
						</tbody>
					</table>
				</div>
            
    <div class="bt-set">
		<span class="btn standart-size blue hide-icon">
					<button class="ajax_submit" >
                          <span><?php echo  ($operation == "add") ? "Добавить в базу данных" : "Обновить базу данных";?></span>
                    </button>
                    
		</span>
    </div>
</form>

<?php  }
        if($post_continue and !$errorMessage) { ?>
      <h2>Импорт завершен.</h2>
      <p><?php echo  ($operation == "add") ? "Добавлено" : "Обновлено";?> <strong><?php echo $successWritings.' '.F::get_right_okonch($successWritings, "записи", "запись", "записи"); ?></strong>.</p>
	  <?php if($ignoredWritings) { ?><p>Пропущено <strong><?php echo $ignoredWritings.' '.F::get_right_okonch($ignoredWritings, "записи", "запись", "записи"); ?></strong>.</p><?php } ?>
      <div class="bt-set">
					<span class="btn standart-size">
						<a href="<?php echo DIR_ADMIN; ?>?module=<?php echo $module;?>&action=proxy_import" class="button ajax_link hide-icon" data-module="<?php echo $module;?>">
							<span>Новый импорт</span>
						</a>
					</span>
				</div>
<?php } ?>

