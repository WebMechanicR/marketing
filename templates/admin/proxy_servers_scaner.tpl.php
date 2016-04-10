<div class="bt-set right">
		<span class="btn standart-size <?php echo ($enabled) ? "red" : "blue"; ?>">
			<a href="<?php echo DIR_ADMIN; ?>?module=<?php echo $module;?>&action=proxy_scaner&enabled=<?php echo ($enabled)?0:1; ?>&flag=1" class="button ajax_link" data-module="<?php echo $module;?>">
				<?php if($enabled) { ?>
                <span><img class="bicon cross-w" src="<?php echo $dir_images;?>icon.png" alt="icon"/> Выключить</span>
				<?php } else { ?>
                <span><img class="bicon check-w" src="<?php echo $dir_images;?>icon.png" alt="icon"/> Включить</span>
                <?php } ?>
            </a>
		</span>
</div>

<h1><img class="blog-icon" src="<?php echo $dir_images;?>icon.png" alt="icon"/> Сканер прокси</h1>
                                
<h2>Статус<img class="q-ico" src="<?php echo $dir_images;?>icon.png" alt="question" rel="tooltip" title='Вы можете видеть последний обновленный статус.' /> <a style="font-size: 13px;" href="<?php echo DIR_ADMIN; ?>?module=<?php echo $module;?>&action=proxy_scaner" class="ajax_link" data-module="<?php echo $module;?>">Обновить статус</a></h2><br/>

<div id="m-status">
    <?php if($scaner_status or $searching_status) { ?>
	<?php if($scaner_status) { ?>
        Просканировано всего за цикл: <?php echo $scaner_status['scanned']; ?>
        <br/>
        Из них оказалось рабочих: <?php echo $scaner_status['scanned_successful']; ?>
        <br/>
	<?php } ?>
	
        Статус импортирования: 
        <?php if(isset($searching_status['importing_stopped']) and $searching_status['importing_stopped']){
            ?>
            Ожидание...<br/>
            <?php
        }
        else if(isset($searching_status['current_import_source'])){
            ?>
            Импорт с <?php echo $searching_status['current_import_source']; ?><br/>
            <?php
        }
        ?>
        <?php if(isset($searching_status['last_found_for_importing'])){ ?>
            Последния найденные для импорта прокси: <?php echo $searching_status['last_found_for_importing'] ;?> (из них импортировано: <?php echo $searching_status['last_found_for_inserting'] ;?>)<br/>
            <?php 
        }
        ?>
	 <?php if(isset($searching_status['last_founded_proxies_in_source'])){
          ?>
	    <br/>
            Прокси с источников:
	    <?php foreach($searching_status['last_founded_proxies_in_source'] as $source => $value){
		?>
		<br><?php echo $source; ?> - <?php echo intval($value['count']); ?> всего, импортировано - <?php echo $value['imported']; ?>
		<?php
	    }
        }
        ?>  
	<br/>Активных сайтов для проверки: <?php $d = unserialize($this->settings->destinations_for_proxies); echo count($d); ?> из <?php echo count($this->proxy_servers->destinations); ?>
     <?php } else { ?>
        Ожидание начала цикла...
    <?php } ?>
</div>

<br/><br/>
 <form action="<?php echo DIR_ADMIN; ?>?module=<?php echo $module;?>&action=proxy_scaner" method="post">   
     <input type="hidden" name="post_flag" value="1"/>
     <div class="tabs">
         <ul class="bookmarks">
             <li class="active"><a href="#" data-name="main">Настройки</a></li>
         </ul>

         <div class="tab-content">
             <ul class="form-lines wide">
                 <li>
                     <label>Интервал задержки проверки, мин</label>
                     <div class="input text <?php if (isset($errors['interval'])) echo "fail"; ?>">
                         <input type="text"  name="interval" value="<?php if (isset($settings['interval'])) echo $settings['interval']; ?>"/>
                         <?php if (isset($errors['interval'])) { ?><p class="error">это поле обязательно для заполнения</p><?php } ?>
                     </div>
                 </li>
                 <li>
                     <label>Интервал задержки импорта c других источников, мин</label>
                     <div class="input text <?php if (isset($errors['interval'])) echo "fail"; ?>">
                         <input type="text"  name="import_interval" value="<?php if (isset($settings['import_interval'])) echo $settings['import_interval']; ?>"/>
                         <?php if (isset($errors['interval'])) { ?><p class="error">это поле обязательно для заполнения</p><?php } ?>
                     </div>
                 </li>
		 <li>
                     <label>Кол-во потоков для проверки</label>
                     <div class="input text <?php if (isset($errors['interval'])) echo "fail"; ?>">
                         <input type="text"  name="proxy_scaner_threads" value="<?php if (isset($settings['proxy_scaner_threads'])) echo $settings['proxy_scaner_threads']; ?>"/>
                         <?php if (isset($errors['proxy_scaner_threads'])) { ?><p class="error">это поле обязательно для заполнения</p><?php } ?>
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