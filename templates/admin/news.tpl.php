				<?php if($site->admins->get_level_access($module)==2) { ?>
                <div class="bt-set right">
					<span class="btn standart-size">
						<a href="<?php echo DIR_ADMIN; ?>?module=<?php echo $module;?>&action=add" class="button ajax_link" data-module="<?php echo $module;?>">
							<span><img class="bicon plus-s" src="<?php echo $dir_images;?>icon.png" alt="icon"/> Добавить новость</span>
						</a>
					</span>
				</div>
                <?php } ?>	
				
				<h1><img class="news-icon" src="<?php echo $dir_images;?>icon.png" alt="icon"/> Новости</h1>
         
				<?php
					if(count($list_news)>0) {
				?>
                <form action="<?php echo DIR_ADMIN; ?>?module=<?php echo $module;?>&action=group_actions<? echo $link_added_query;?>" method="post">
   				<div class="product-table">
					<table>
						<thead>
							<tr>
                            	<?php if($site->admins->get_level_access($module)==2) { ?>
								<th>
									<input type="checkbox"/>
								</th>
                                <?php } ?>
								<th style="width:400px" class="header <?php if($sort_by=="title") { echo ($sort_dir=="asc" ? "headerSortUp" : "headerSortDown");} ?>"><a href="<?php echo DIR_ADMIN;?>?module=<?php echo $module;?>&sort_by=title&sort_dir=<?php echo ( ($sort_by=="title" and $sort_dir=="asc") ? "desc" : "asc"); ?>" class="ajax_link" data-module="<?php echo $module;?>">Название <img src="<?php echo $dir_images;?>icon.png" alt="icon"/></a></th>
								<th>Фото</th>
								<th class="header <?php if($sort_by=="date_add") { echo ($sort_dir=="asc" ? "headerSortUp" : "headerSortDown");} ?>"><a href="<?php echo DIR_ADMIN;?>?module=<?php echo $module;?>&sort_by=date_add&sort_dir=<?php echo ( ($sort_by=="date_add" and $sort_dir=="desc") ? "asc" : "desc"); ?>" class="ajax_link" data-module="<?php echo $module;?>">Дата <img src="<?php echo $dir_images;?>icon.png" alt="icon"/></a></th>
								<th class="header <?php if($sort_by=="enabled") { echo ($sort_dir=="asc" ? "headerSortUp" : "headerSortDown");} ?>"><a href="<?php echo DIR_ADMIN;?>?module=<?php echo $module;?>&sort_by=enabled&sort_dir=<?php echo ( ($sort_by=="enabled" and $sort_dir=="desc") ? "asc" : "desc"); ?>" class="ajax_link" data-module="<?php echo $module;?>">Статус <img src="<?php echo $dir_images;?>icon.png" alt="icon"/></a></th>
								<th>&nbsp;</th>
							</tr>
						</thead>
						<tbody>
                        	<?php foreach($list_news as $news) { ?>
							<tr <?php echo $news['enabled'] ? '' : 'class="disable"';?>>
                            	<?php if($site->admins->get_level_access($module)==2) { ?>
								<td>
									<input type="checkbox" name="check_item[]" value="<?php echo $news['id'];?>"/>
								</td>
                                <?php } ?>
								<td>
                                	<?php if($site->admins->get_level_access($module)==2) { ?>
									<a href="<?php echo DIR_ADMIN;?>?module=<?php echo $module;?>&action=edit&id=<?php echo $news['id'];?>" class="ajax_link" data-module="<?php echo $module;?>"><?php echo $news['title'];?></a>
									<?php } else { echo $news['title']; } ?>
                                </td>
								<td>
									<?php if($news['img']!="") { ?><img src="<?php echo $content_photos_dir."small/".$news['img'];?>" alt=""/><?php } ?>
								</td>
                                <td>
                                		<?php echo date('d.m.Y', $news['date_add']);?>
                                </td>
                                <td>
                                	<?php echo $news['enabled'] ? 'Опубликовано' : 'Скрыто';?>
                                </td>
								<td class="nowrap">
 									<a href="<?php echo SITE_URL.$news_full_link."/".$news['url'];?>.htm" target="_blank" title="Посмотреть на сайте"><img src="<?php echo $dir_images;?>icon.png" class="eicon link-s" alt="icon"/></a>
                           			<?php if($site->admins->get_level_access($module)==2) { ?>
									<a href="<?php echo DIR_ADMIN;?>?module=<?php echo $module;?>&action=edit&id=<?php echo $news['id'];?>" class="ajax_link" data-module="<?php echo $module;?>" title="Редактировать"><img src="<?php echo $dir_images;?>icon.png" class="eicon edit-s" alt="icon"/></a>
									<a href="<?php echo DIR_ADMIN;?>?module=<?php echo $module;?>&action=duplicate&id=<?php echo $news['id'];?><? echo $link_added_query;?>" class="ajax_link" data-module="<?php echo $module;?>" title="Создать копию"><img src="<?php echo $dir_images;?>icon.png" class="eicon copy-s" alt="icon"/></a>
									<a href="<?php echo DIR_ADMIN;?>?module=<?php echo $module;?>&action=delete&id=<?php echo $news['id'];?><? echo $link_added_query;?>" class="delete-confirm" data-module="<?php echo $module;?>" data-text="Вы действительно хотите удалить эту новость?" title="Удалить"><img src="<?php echo $dir_images;?>icon.png" class="eicon del-s" alt="icon"/></a>
									<?php } ?>
								</td>
							</tr>
                            <?php } ?>
						</tbody>
						<tfoot>
							<tr>
                            	<?php if($site->admins->get_level_access($module)==2) { ?>
								<th>
									<input type="checkbox"/>
								</th>
                                <?php } ?>
								<th style="width:400px" class="header <?php if($sort_by=="title") { echo ($sort_dir=="asc" ? "headerSortUp" : "headerSortDown");} ?>"><a href="<?php echo DIR_ADMIN;?>?module=<?php echo $module;?>&sort_by=title&sort_dir=<?php echo ( ($sort_by=="title" and $sort_dir=="asc") ? "desc" : "asc"); ?>" class="ajax_link" data-module="<?php echo $module;?>">Название <img src="<?php echo $dir_images;?>icon.png" alt="icon"/></a></th>
								<th>Фото</th>
								<th class="header <?php if($sort_by=="date_add") { echo ($sort_dir=="asc" ? "headerSortUp" : "headerSortDown");} ?>"><a href="<?php echo DIR_ADMIN;?>?module=<?php echo $module;?>&sort_by=date_add&sort_dir=<?php echo ( ($sort_by=="date_add" and $sort_dir=="desc") ? "asc" : "desc"); ?>" class="ajax_link" data-module="<?php echo $module;?>">Дата <img src="<?php echo $dir_images;?>icon.png" alt="icon"/></a></th>
								<th class="header <?php if($sort_by=="enabled") { echo ($sort_dir=="asc" ? "headerSortUp" : "headerSortDown");} ?>"><a href="<?php echo DIR_ADMIN;?>?module=<?php echo $module;?>&sort_by=enabled&sort_dir=<?php echo ( ($sort_by=="enabled" and $sort_dir=="desc") ? "asc" : "desc"); ?>" class="ajax_link" data-module="<?php echo $module;?>">Статус <img src="<?php echo $dir_images;?>icon.png" alt="icon"/></a></th>
								<th>&nbsp;</th>
							</tr>
						</tfoot>
					</table>
				</div>
                	
                    <?php $site->tpl->display('paging'); ?>
                	
					<?php if($site->admins->get_level_access($module)==2) { ?>
                   <div class="combo">
                        <span class="btn gray">
                            <button>Скрыть отмеченные</button>
                        </span>
                        <button class="dicon arrdown">меню</button>
                        <ul>
                            <li><a href="#" data-active="hide">Скрыть отмеченные</a></li>
                            <li><a href="#" data-active="show">Опубликовать отмеченные</a></li>
                            <li><a href="#" data-active="delete">Удалить отмеченные</a></li>
                        </ul>
                        <input type="hidden" name="do_active" value="hide">
                        <input type="hidden" name="group_actions" value="0">
                    </div>
                    <?php } ?>
				</form>
				<?php } else {?>
				<h3>По заданными критериям новостей не найдено</h3>
                <?php } ?>