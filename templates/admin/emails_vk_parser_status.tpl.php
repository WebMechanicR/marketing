<div class="bt-set right">
		<span class="btn standart-size <?php echo ($enabled) ? "red" : "blue"; ?>">
			<a href="<?php echo DIR_ADMIN; ?>?module=<?php echo $module;?>&action=vk_group_parser&enabled=<?php echo ($enabled)?0:1; ?>&flag=1" class="button ajax_link" data-module="<?php echo $module;?>">
				<?php if($enabled) { ?>
                <span><img class="bicon cross-w" src="<?php echo $dir_images;?>icon.png" alt="icon"/> Выключить</span>
				<?php } else { ?>
                <span><img class="bicon check-w" src="<?php echo $dir_images;?>icon.png" alt="icon"/> Включить</span>
                <?php } ?>
            </a>
		</span>
</div>

<h1><img class="users-icon" src="<?php echo $dir_images;?>icon.png" alt="icon"/> Парсер групп в VK</h1>
                                
<h2>Последний статус:<img class="q-ico" src="<?php echo $dir_images;?>icon.png" alt="question" rel="tooltip" title='Вы можете видеть статус в реальном времени.' /></h2><br/>
     <div id ="mail_status">
        <?php 
            echo $status;
         ?>
     </div>
<br/><a href="<?php echo DIR_ADMIN; ?>?module=<?php echo $module;?>&action=vk_group_parser" class="ajax_link" data-module="<?php echo $module;?>">Обновить</a> статус.<br><br>

<p class="small">
    Выберите тип клиента<p>
    <div class="input">
            <select size="20" multiple="MULTIPLE" class="multi_select" id="org_type">
                            <?php
                            foreach ($org_types as $key => $val) {
                                ?>
                                    <option value="<?php echo $val['id']; ?>" <?php if (isset($org_type) and $org_type == $val['id']) echo "selected"; ?>><?php echo $val['name']; ?></option>
                                <?php
                            }
                            ?>
                        </select>
    </div>
 <p class="small">   
    и откройте в новом окне <a id="link_for_import" data-href="<?php echo DIR_ADMIN; ?>?module=emails&action=get_emails_from_clientbase&run_import=1&p=-1" 
                             href="<?php echo DIR_ADMIN; ?>?module=emails&action=get_emails_from_clientbase&run_import=1&p=-1" target="_blank">ссылку</a> 
    для импорта email адресов из таблиц vk_groups в общую таблицу
</p>
<script>
    $('#org_type').on('change', function(){
        var val = "";
        $.each($(this).find('option:selected'), function(i, v){
            val = val + '&org_type[]=' + $(this).val();
        });
        $('#link_for_import').attr('href', $('#link_for_import').data('href') + val);
    });
</script>

<br/><br/>
 <form action="<?php echo DIR_ADMIN; ?>?module=<?php echo $module;?>&action=vk_group_parser" method="post">   
     <input type="hidden" name="post_flag" value="1"/>
     <div class="tabs">
         <ul class="bookmarks">
             <li class="active"><a href="#" data-name="main">Настройки</a></li>
         </ul>

         <div class="tab-content">
             <ul class="form-lines wide">
                 <li>
                     <label>Типы клиентов для текущего импорта</label>
                     <div class="input">
                         <select class="multi_select" name="org_types[]" multiple="multiple">
                             <?php
                             foreach ($org_types as $org) {
                                 ?>
                                    <option value="<?php echo $org['id']; ?>" <?php if (in_array($org['id'], (array) $selected_org_types)) echo "selected"; ?>><?php echo $org['name']; ?></option>
                             <?php } ?>
                         </select>
                     </div>
                 </li>
             </ul>
             <div class="clear"></div>
         </div><!-- .tab-content end -->
     </div><!-- .tabs end -->

     <div class="bt-set clip">
         <div class="left">
             <span class="btn standart-size blue hide-icon">
                 <button class="ajax_submit" data-success-name="Cохранено">
                     <span><img class="bicon check-w" src="<?php echo $dir_images; ?>icon.png" alt="icon"/> <i>Сохранить</i></span>
                 </button>
             </span>
            
         </div>
     </div>
 </form>