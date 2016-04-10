<h1><img class="users-icon" src="<?php echo $dir_images;?>icon.png" alt="icon"/> Статус</h1>
                                
<h2>Статус отправки:<img class="q-ico" src="<?php echo $dir_images;?>icon.png" alt="question" rel="tooltip" title='Вы можете видеть статус отправки в реальном времени.'/></h2><br/>
     <div id ="mail_status">
        <?php 
            echo $status;
         ?>
         
     </div>
<br/><a href="<?php echo DIR_ADMIN; ?>?module=<?php echo $module;?>&action=status" class="ajax_link" data-module="<?php echo $module;?>">Обновить</a> статус.