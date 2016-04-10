<div class="bt-set right">
					<span class="btn standart-size">
						<a href="<?php echo DIR_ADMIN; ?>?module=<?php echo $module;?>&action=add" class="button ajax_link" data-module="<?php echo $module;?>">
							<span><img class="bicon plus-s" src="<?php echo $dir_images;?>icon.png" alt="icon"/> Добавить сервер</span>
						</a>
					</span>
</div>
<h1><img class="catalog-icon" src="<?php echo $dir_images;?>icon.png" alt="icon"/> SMTP Сервера</h1>
<form action="<?php echo DIR_ADMIN; ?>?module=<?php echo $module; ?>&action=<?php echo $action;?>" method="get">
    <div class="section_filtres" >
        <div class="input text">
            <input type="text" id="login" name="login" value="<?php if (isset($login)) echo $login; ?>" placeholder="Логин"/>
        </div>
        <span class="btn standart-size hide-icon">
            <button class="ajax_submit" >
                <span>Найти</span>
            </button>
        </span>
    </div>
                                    
</form>
                <?php
					if(count($list_servers)>0) {
				?>

                <form action="<?php echo DIR_ADMIN; ?>?module=<?php echo $module;?>" method="post">
				<div class="product-table slides sortable">
					<table>
						<thead>
							<tr>
								<th>
									<input type="checkbox"/>
								</th>
								<th >Хост</th>
								<th>Логин</th>
                                                                <th>Отправлено писем</th>
								<th>Статус</th>
                                                                <th>Позиция</th>
								<th>&nbsp;</th>
							</tr>
						</thead>
                        
						<tbody>
                                <?php 
							foreach($list_servers as $server) { 
							?>
							<tr class="update_onfly <?php echo $server['enabled'] ? '' : 'disable';?>">
                                                                <td>
									<input type="checkbox" name="check_item[]" value="<?php echo $server['id'];?>"/>
                                                                        <input type="hidden" name="server_name[<?php echo $server['id']; ?>]" value="<?php echo $server['host']; ?>"/>
								</td>
								<td>
                                                                    <a href="<?php echo DIR_ADMIN;?>?module=<?php echo $module;?>&action=edit&id=<?php echo $server['id'];?>" class="ajax_link" data-module="<?php echo $module;?>"><?php echo $server['host'];?></a>
                                                                    <?php echo $server['alt_name']; ?>
								</td>
								<td>
									<?php echo $server['login']; ?>
								</td>
                                                                <td>
									<?php echo $server['sending_count']; ?>
								</td>
                                                                <td>
                                                                        <?php echo $server['enabled'] ? 'Активен' : 'Не активен';?>
                                                                </td>
                                                                <td>
                                                                        <img src="<?php echo $dir_images;?>icon.png" class="eicon lines-s" alt="icon"/>
                                                                </td>
								<td>
									<a href="<?php echo DIR_ADMIN;?>?module=<?php echo $module;?>&action=edit&id=<?php echo $server['id'];?>" class="ajax_link" data-module="<?php echo $module;?>" title="Редактировать"><img src="<?php echo $dir_images;?>icon.png" class="eicon edit-s" alt="icon"/></a>
									<a href="<?php echo DIR_ADMIN;?>?module=<?php echo $module;?>&action=duplicate&id=<?php echo $server['id'];?>" class="ajax_link" data-module="<?php echo $module;?>" title="Создать копию"><img src="<?php echo $dir_images;?>icon.png" class="eicon copy-s" alt="icon"/></a>
									<a href="<?php echo DIR_ADMIN; ?>?module=<?php echo $module;?>&action=delete&id=<?php echo $server['id']; ?>" class="delete-confirm" data-module="<?php echo $module;?>" data-text="Вы действительно хотите удалить этот сервер" title="Удалить"><img src="<?php echo $dir_images;?>icon.png" class="eicon del-s" alt="icon"/></a>
								</td>
							</tr>
						<?php } ?>	
							
						</tbody>
						<tfoot>
							<tr>
								<th>
									<input type="checkbox"/>
								</th>
								<th >Хост</th>
								<th>Логин</th>
                                                                  <th>Отправлено писем</th>
								<th>Статус</th>
                                                                <th>Позиция</th>
								
                                <th>&nbsp;</th>
							</tr>
						</tfoot>
					</table>
				</div>
                
                    
                     <?php $site->tpl->display('paging'); ?>
                    
                   <div class="combo">
                        <span class="btn gray">
                            <button>Деактивировать отмеченные</button>
                        </span>
                        <button class="dicon arrdown">меню</button>
                        <ul>
                            <li><a href="#" data-active="hide">Деактивировать отмеченные</a></li>
                            <li><a href="#" data-active="show">Активировать отмеченные</a></li>
                            <li><a href="#" data-active="delete">Удалить отмеченные</a></li>
                        </ul>
                        <input type="hidden" name="do_active" value="hide">
                        <input type="hidden" name="group_actions" value="0">
                    </div>
				</form>
				<?php } ?>