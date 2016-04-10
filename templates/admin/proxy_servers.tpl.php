<div class="bt-set right">
					<span class="btn standart-size">
						<a href="<?php echo DIR_ADMIN; ?>?module=<?php echo $module;?>&action=add_proxy" class="button ajax_link" data-module="<?php echo $module;?>">
							<span><img class="bicon plus-s" src="<?php echo $dir_images;?>icon.png" alt="icon"/> Добавить прокси сервер</span>
						</a>
					</span>
</div>
<h1><img class="catalog-icon" src="<?php echo $dir_images;?>icon.png" alt="icon"/> Прокси Сервера</h1>
<form action="<?php echo DIR_ADMIN; ?>?module=<?php echo $module; ?>&action=<?php echo $action;?>" method="get">
    <div class="section_filtres">
        <div class="input">
            <select name="enabled" class="select">
                <option value="0">Активность</option>
                <option value="1" <?php echo $enabled == 1?'selected':''; ?>>Не активен</option>
                <option value="2" <?php echo $enabled == 2?'selected':''; ?>>Активен</option>
            </select>
        </div>
        <div class="input text">
            <input name="country" type="text" value="<?php echo $country; ?>" placeholder="Страны (через запятую)"/>
        </div>
	<div style="display: inline-block;">
	    <label>Уровень</label><br/>
	    <div class="input">
		<select name="anonymous[]" class="multi_select" multiple="MULTIPLE">
		    <option value="0">Уровень</option>
		    <option value="3" <?php echo in_array(3, $levels)?'selected':''; ?>>Прозрачный</option>
		    <option value="2" <?php echo in_array(2, $levels)?'selected':''; ?>>Анонимный</option>
		    <option value="1" <?php echo in_array(1, $levels)?'selected':''; ?>>Элитный</option>
		</select>
	    </div>
	</div>
	<div class="input text">
            <input name="uptime" type="text" value="<?php echo $uptime; ?>" placeholder="Аптайм до (сек)"/>
        </div>
    </div>
    <div class="section_filtres" >
        <label>Дата последней проверки</label>
        <div class="input date for_price">
            <input type="text" name="date_from" value="<?php if (isset($date_from) and $date_from > 0) echo date('d.m.Y', $date_from); ?>" />
        </div>
        <span class="input_sub_str" >-</span>
        <div class="input date for_price">
            <input type="text" name="date_to" value="<?php if (isset($date_to) and $date_to > 0) echo date('d.m.Y', $date_to); ?>" />
        </div>
	<div class="input">
            <select name="private" class="select">
                <option value="0">Приватность</option>
                <option value="1" <?php echo $private == 1?'selected':''; ?>>Публичные</option>
                <option value="2" <?php echo $private == 2?'selected':''; ?>>Приватные</option>
            </select>
        </div>
	<div style="display: inline-block;">
	    <label>Тип</label><br/>
	    <div class="input">
		<select name="types[]" class="multi_select" multiple="MULTIPLE">
		    <option value="4" <?php echo in_array(4, $types)?'selected':''; ?>>SOCKS5</option>
		    <option value="3" <?php echo in_array(3, $types)?'selected':''; ?>>SOCKS4</option>
		    <option value="2" <?php echo in_array(2, $types)?'selected':''; ?>>HTTPS</option>
		    <option value="1" <?php echo in_array(1, $types)?'selected':''; ?>>HTTP</option>
		</select>
	    </div>
	</div>
        <span class="btn standart-size hide-icon">
            <button class="ajax_submit" >
                <span>Найти</span>
            </button>
        </span>
    </div>                            
</form>

<br/>
<h2>Всего прокси серверов: <?php echo $proxy_count; ?></h2>
<br/>
                <?php
					if(count($list_proxies)>0) {
				?>

                <form action="<?php echo DIR_ADMIN; ?>?module=<?php echo $module;?>" method="post">
				<div class="product-table slides sortable">
					<table>
						<thead>
							<tr>
								<th>
									<input type="checkbox"/>
								</th>
								<th class="header <?php if($sort_by=="id") { echo ($sort_dir=="asc" ? "headerSortUp" : "headerSortDown");} ?>"><a href="<?php echo DIR_ADMIN;?>?module=<?php echo $module."&action=index".$filter_query;?>&sort_by=id&sort_dir=<?php echo ( ($sort_by=="id" and $sort_dir=="desc") ? "asc" : "desc"); ?>" class="ajax_link" data-module="<?php echo $module;?>">Номер<img src="<?php echo $dir_images;?>icon.png" alt="icon"/></a></th>				
								<th>IP</th>
								<th>Тип</th>
								<th class="header <?php if($sort_by=="uptime") { echo ($sort_dir=="asc" ? "headerSortUp" : "headerSortDown");} ?>"><a href="<?php echo DIR_ADMIN;?>?module=<?php echo $module."&action=index".$filter_query;?>&sort_by=uptime&sort_dir=<?php echo ( ($sort_by=="uptime" and $sort_dir=="desc") ? "asc" : "desc"); ?>" class="ajax_link" data-module="<?php echo $module;?>">Аптайм (сек) <img src="<?php echo $dir_images;?>icon.png" alt="icon"/></a></th>				
                                                                <th class="header <?php if($sort_by=="last_checking") { echo ($sort_dir=="asc" ? "headerSortUp" : "headerSortDown");} ?>"><a href="<?php echo DIR_ADMIN;?>?module=<?php echo $module."&action=index".$filter_query;?>&sort_by=last_checking&sort_dir=<?php echo ( ($sort_by=="last_checking" and $sort_dir=="desc") ? "asc" : "desc"); ?>" class="ajax_link" data-module="<?php echo $module;?>">Последняя проверка <img src="<?php echo $dir_images;?>icon.png" alt="icon"/></a></th>				
                                                                <th>Страна</th>
                                                                <th>Логин</th>
                                                                <th>Статус</th>
								<th>&nbsp;</th>
							</tr>
						</thead>
                        
						<tbody>
                                <?php 
				
							foreach($list_proxies as $server) { 
							?>
							<tr class="update_onfly <?php echo $server['enabled'] ? '' : 'disable';?>">
								<td>
								    <input type="checkbox" name="check_item[]" value="<?php echo $server['id'];?>"/>
								    <input type="hidden" name="proxy_server_name[<?php echo $server['id']; ?>]" value="<?php echo $server['login']; ?>"/>
								</td>
								<td>
								    <?php echo $server['id']; ?>
								</td>
								<td>
                                                                    <a href="<?php echo DIR_ADMIN;?>?module=<?php echo $module;?>&action=edit_proxy&id=<?php echo $server['id'];?>" class="ajax_link" data-module="<?php echo $module;?>"><?php echo $server['ip'];?><?php echo $server['port']?':'.$server['port']:''; ?></a>
                                                                    <?php echo $server['alt_name']; ?>
								</td>
								<td>
									<?php
                                                                            if($server['type_http'])
                                                                                echo 'HTTP<br/>';
                                                                            if($server['type_https'])
                                                                                echo 'HTTPS<br/>';
                                                                            if($server['type_socks4'])
                                                                                echo 'SOCKS4<br/>';
                                                                            if($server['type_socks5'])
                                                                                echo 'SOCKS5<br/>';
                                                                            if($server['anonymous'] == 2)
                                                                                echo '<span style="color: yellowgreen;">Анонимный</span><br/>';
                                                                            if($server['anonymous'] == 1)
                                                                                echo '<span style="color: green;">Элитный</span><br/>';
									    if($server['anonymous'] == 3)
										echo '<span style="color: #CC6666 ;">Прозрачный</span><br/>';
                                                                        ?>
								</td>
                                                                <td>
								    <?php if($server['uptime']) { 
									echo F::number_format($server['uptime']);
								     } ?>
								</td>
                                                                <td>
									<?php echo $server['last_checking']?date('d.m H:i', $server['last_checking']):''; ?>
								</td>
                                                                <td>
                                                                    <?php echo $server['country']; ?>
                                                                </td>
								<td>
									<?php echo $server['login']; ?>
								</td>
                                                                <td>
                                                                        <?php echo $server['enabled'] ? 'Активен' : 'Не активен';?>
                                                                </td>
								<td>
									<a href="<?php echo DIR_ADMIN;?>?module=<?php echo $module;?>&action=edit_proxy&id=<?php echo $server['id'];?>" class="ajax_link" data-module="<?php echo $module;?>" title="Редактировать"><img src="<?php echo $dir_images;?>icon.png" class="eicon edit-s" alt="icon"/></a>
									<a href="<?php echo DIR_ADMIN; ?>?module=<?php echo $module;?>&action=delete_proxy&id=<?php echo $server['id']; ?>" class="delete-confirm" data-module="<?php echo $module;?>" data-text="Вы действительно хотите удалить этот сервер" title="Удалить"><img src="<?php echo $dir_images;?>icon.png" class="eicon del-s" alt="icon"/></a>
								</td>
							</tr>
						<?php } ?>	
							
						</tbody>
						<tfoot>
							<tr>
							    <th>
									<input type="checkbox"/>
								</th>
								<th class="header <?php if($sort_by=="id") { echo ($sort_dir=="asc" ? "headerSortUp" : "headerSortDown");} ?>"><a href="<?php echo DIR_ADMIN;?>?module=<?php echo $module."&action=index".$filter_query;?>&sort_by=id&sort_dir=<?php echo ( ($sort_by=="id" and $sort_dir=="desc") ? "asc" : "desc"); ?>" class="ajax_link" data-module="<?php echo $module;?>">Номер<img src="<?php echo $dir_images;?>icon.png" alt="icon"/></a></th>				
								<th>IP</th>
								<th>Тип</th>
								<th class="header <?php if($sort_by=="uptime") { echo ($sort_dir=="asc" ? "headerSortUp" : "headerSortDown");} ?>"><a href="<?php echo DIR_ADMIN;?>?module=<?php echo $module."&action=index".$filter_query;?>&sort_by=uptime&sort_dir=<?php echo ( ($sort_by=="uptime" and $sort_dir=="desc") ? "asc" : "desc"); ?>" class="ajax_link" data-module="<?php echo $module;?>">Аптайм (сек) <img src="<?php echo $dir_images;?>icon.png" alt="icon"/></a></th>				
                                                                <th class="header <?php if($sort_by=="last_checking") { echo ($sort_dir=="asc" ? "headerSortUp" : "headerSortDown");} ?>"><a href="<?php echo DIR_ADMIN;?>?module=<?php echo $module."&action=index".$filter_query;?>&sort_by=last_checking&sort_dir=<?php echo ( ($sort_by=="last_checking" and $sort_dir=="desc") ? "asc" : "desc"); ?>" class="ajax_link" data-module="<?php echo $module;?>">Последняя проверка <img src="<?php echo $dir_images;?>icon.png" alt="icon"/></a></th>				
                                                                <th>Страна</th>
                                                                <th>Логин</th>
                                                                <th>Статус</th>
								<th>&nbsp;</th>
							</tr>
						</tfoot>
					</table>
				</div>
                
                    
                                <?php $site->tpl->display('paging'); ?>
                    
                 <div class="combo">
                        <span class="btn gray">
                            <button>Удалить отмеченные</button>
                        </span>
                        <button class="dicon arrdown">меню</button>
                        <ul>
                            <li><a href="#" data-active="delete">Удалить отмеченные</a></li>
			    <li><a href="#" data-active="hide">Скрыть отмеченные</a></li>
			    <li><a href="#" data-active="show">Активировать отмеченные</a></li>
                        </ul>
                        <input type="hidden" name="do_active" value="delete">
                        <input type="hidden" name="group_actions" value="0">
                    </div>
				</form>
		<?php } else { ?>
		Ничего не найдено
		<?php } ?>
