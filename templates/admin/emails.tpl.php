
<div class="bt-set right">
					<span class="btn standart-size">
						<a href="<?php echo DIR_ADMIN;?>?module=<?php echo $module;?>&action=edit" class="button ajax_link" data-module="<?php echo $module;?>">
							<span><img class="bicon plus-s" src="<?php echo $dir_images;?>icon.png" alt="icon"/> Добавить </span>
						</a>
					</span>
				</div>

<h1><img class="users-icon" src="<?php echo $dir_images;?>icon.png" alt="icon"/>Email адреса</h1>                       
<form action="<?php echo DIR_ADMIN; ?>?module=<?php echo $module; ?>&action=<?php echo $action;?>" method="get">
    <div class="section_filtres" style="margin-top:10px;">
        <div class="input">
            <select class="select" name="org_type">
            	<option value="0">Тип клиента</option>
                            <?php
                            foreach ($org_types as $key => $val) {
                                ?>
                                    <option value="<?php echo $val['id']; ?>" <?php if (isset($org_type) and $org_type == $val['id']) echo "selected"; ?>><?php echo $val['name']; ?></option>
                                <?php
                            }
                            ?>
                        </select>
        </div>
        <div class="input text">
            <input type="text" id="city1" name="city" value="<?php if (isset($city)) echo $city; ?>" placeholder="Город"/>
        </div>
        <script>
                                                                      $(function(){
                                                                              // Подгружаемые логины

                                                                              $("#city1").autocomplete({
                                                                                      serviceUrl:'<?php echo DIR_ADMIN; ?>ajax_search_city.php',
                                                                                      minChars:2,
                                                                                      noCache: false,
                                                                                      deferRequestBy: 500
                });
            });
        </script>
    </div>
    
    <div class="section_filtres" style="margin-top:10px;">
        <label>Дата последней отправки</label>
        <div class="input date for_price">
            <input type="text" name="date_from" value="<?php if (isset($date_from) and $date_from > 0) echo date('d.m.Y', $date_from); ?>" />
        </div>
        <span class="input_sub_str" >-</span>
        <div class="input date for_price">
            <input type="text" name="date_to" value="<?php if (isset($date_to) and $date_to > 0) echo date('d.m.Y', $date_to); ?>" />
        </div>
        
        <span class="btn standart-size hide-icon">
            <button class="ajax_submit" >
                <span>Найти</span>
            </button>
        </span>
        
    </div>
    
</form>
<br/>
<h3>Всего адресов: <?php echo $emails_count; ?></h3>
<br/>
<?php
if (count($list_emails) > 0) {
    ?>

    <form action="<?php echo DIR_ADMIN; ?>?module=<?php echo $module; ?>&action=<?php echo $action;?>" method="post">
        <div class="product-table slides emails">
            <table>
                <thead>
                    <tr>
                    	
                        <th>
                            <input type="checkbox"/>
                        </th>
                        <th class="header <?php if ($sort_by == "name") echo ($sort_dir == "asc" ? "headerSortUp" : "headerSortDown"); ?>">
                        	<a href="<?php echo DIR_ADMIN; ?>?module=<?php echo $module; ?>&action=<?php echo $action;?>&sort_by=name&sort_dir=<?php echo ( ($sort_by == "name" and $sort_dir == "asc") ? "desc" : "asc"); ?><? echo $filtres_query; ?>" class="ajax_link" data-module="<?php echo $module; ?>"> Клиент <img src="<?php echo $dir_images; ?>icon.png" alt="icon"/></a>
                        </th>
                        
                        
                        <th class="header <?php if ($sort_by == "org_type") echo ($sort_dir == "asc" ? "headerSortUp" : "headerSortDown"); ?>">
                        	<a href="<?php echo DIR_ADMIN; ?>?module=<?php echo $module; ?>&action=<?php echo $action;?>&sort_by=org_type&sort_dir=<?php echo ( ($sort_by == "org_type" and $sort_dir == "asc") ? "desc" : "asc"); ?><? echo $filtres_query; ?>" class="ajax_link" data-module="<?php echo $module; ?>"> Тип клиента <img src="<?php echo $dir_images; ?>icon.png" alt="icon"/></a>
                        </th>
                        
                       <th class="header <?php if ($sort_by == "last_sending") echo ($sort_dir == "asc" ? "headerSortUp" : "headerSortDown"); ?>">
                       		<a href="<?php echo DIR_ADMIN; ?>?module=<?php echo $module; ?>&action=<?php echo $action;?>&sort_by=last_sending&sort_dir=<?php echo ( ($sort_by == "last_sending" and $sort_dir == "asc") ? "desc" : "asc"); ?><? echo $filtres_query; ?>" class="ajax_link" data-module="<?php echo $module; ?>"> Дата отправки <img src="<?php echo $dir_images; ?>icon.png" alt="icon"/></a>
                       </th>
                         
                       <th class="header <?php if ($sort_by == "sending_count") echo ($sort_dir == "asc" ? "headerSortUp" : "headerSortDown"); ?>">
                       		<a href="<?php echo DIR_ADMIN; ?>?module=<?php echo $module; ?>&action=<?php echo $action;?>&sort_by=sending_count&sort_dir=<?php echo ( ($sort_by == "sending_count" and $sort_dir == "asc") ? "desc" : "asc"); ?><? echo $filtres_query; ?>" class="ajax_link" data-module="<?php echo $module; ?>"> Кол-во писем <img src="<?php echo $dir_images; ?>icon.png" alt="icon"/></a>
                       </th>
                       
                       <th class="header <?php if ($sort_by == "enabled") echo ($sort_dir == "asc" ? "headerSortUp" : "headerSortDown"); ?>">
                       		<a href="<?php echo DIR_ADMIN; ?>?module=<?php echo $module; ?>&action=<?php echo $action;?>&sort_by=enabled&sort_dir=<?php echo ( ($sort_by == "enabled" and $sort_dir == "asc") ? "desc" : "asc"); ?><? echo $filtres_query; ?>" class="ajax_link" data-module="<?php echo $module; ?>"> Готовность <img src="<?php echo $dir_images; ?>icon.png" alt="icon"/></a>
                       </th>
			
                       <th>&nbsp;</th>
                    </tr>
                </thead>

                <tbody>
    <?php
    foreach ($list_emails as $email) {
        ?>
                        <tr class="emails update_onfly <?php echo $email['enabled'] ? '' : 'disable'; ?>">
                            <td>
                                <input type="checkbox" name="check_item[]" value="<?php echo $email['id']; ?>"/>
                                <input type="hidden" name="email_name[<?php echo $email['id']; ?>]" value="<?php echo $email['email']; ?>"/>
                            </td>
                            <td>
                                <a href="<?php echo DIR_ADMIN; ?>?module=<?php echo $module; ?>&action=edit&id=<?php echo $email['id']; ?>" class="ajax_link" data-module="<?php echo $module; ?>">
                                    <?php echo $email['name']; ?>
                                </a>
                            </td>
                            <td>
                              <?php 
                                    $iorg_types = explode('|', preg_replace('/^\||\|$/is', '', $email['org_type'])); 
                                    if($iorg_types)
                                        foreach($iorg_types as $org_id)
                                            echo $org_types[$org_id]['name'].'<br/>';
                               ?>
                            </td>
                            
                            <td>
                                    <?php echo $email['last_sending']?date('H:i d.m.Y', $email['last_sending']):''; ?>
                            </td>
                            
                            <td>
                                    <?php echo $email['sending_count']; ?>
                            </td>
                           
                            <td>
                                <?php echo $email['enabled']; ?>
                            </td>
                           
                            <td>
                                <a href="<?php echo DIR_ADMIN; ?>?module=<?php echo $module; ?>&action=edit&id=<?php echo $email['id']; ?>" data-module="<?php echo $module; ?>" ><img src="<?php echo $dir_images; ?>icon.png" class="eicon edit-s" alt="icon"/></a>
                                <a href="<?php echo DIR_ADMIN; ?>?module=<?php echo $module; ?>&action=delete&id=<?php echo $email['id']; ?>" class="delete-confirm" data-module="<?php echo $module; ?>" data-text="Вы действительно хотите удалить этот email?" title="Удалить"><img src="<?php echo $dir_images; ?>icon.png" class="eicon del-s" alt="icon"/></a>
                            </td>
                        </tr>
    <?php } ?>	

                </tbody>
                <tfoot>
                    <tr>
                    	<th>
                            <input type="checkbox"/>
                        </th>
                        <th class="header <?php if ($sort_by == "name") echo ($sort_dir == "asc" ? "headerSortUp" : "headerSortDown"); ?>">
                        	<a href="<?php echo DIR_ADMIN; ?>?module=<?php echo $module; ?>&action=<?php echo $action;?>&sort_by=name&sort_dir=<?php echo ( ($sort_by == "name" and $sort_dir == "asc") ? "desc" : "asc"); ?><? echo $filtres_query; ?>" class="ajax_link" data-module="<?php echo $module; ?>"> Клиент <img src="<?php echo $dir_images; ?>icon.png" alt="icon"/></a>
                        </th>
                        <th class="header <?php if ($sort_by == "org_type") echo ($sort_dir == "asc" ? "headerSortUp" : "headerSortDown"); ?>">
                        	<a href="<?php echo DIR_ADMIN; ?>?module=<?php echo $module; ?>&action=<?php echo $action;?>&sort_by=org_type&sort_dir=<?php echo ( ($sort_by == "org_type" and $sort_dir == "asc") ? "desc" : "asc"); ?><? echo $filtres_query; ?>" class="ajax_link" data-module="<?php echo $module; ?>"> Тип клиента <img src="<?php echo $dir_images; ?>icon.png" alt="icon"/></a>
                        </th>
                        
                       <th class="header <?php if ($sort_by == "last_sending") echo ($sort_dir == "asc" ? "headerSortUp" : "headerSortDown"); ?>">
                       		<a href="<?php echo DIR_ADMIN; ?>?module=<?php echo $module; ?>&action=<?php echo $action;?>&sort_by=last_sending&sort_dir=<?php echo ( ($sort_by == "last_sending" and $sort_dir == "asc") ? "desc" : "asc"); ?><? echo $filtres_query; ?>" class="ajax_link" data-module="<?php echo $module; ?>"> Дата отправки <img src="<?php echo $dir_images; ?>icon.png" alt="icon"/></a>
                       </th>
                         
                       <th class="header <?php if ($sort_by == "sending_count") echo ($sort_dir == "asc" ? "headerSortUp" : "headerSortDown"); ?>">
                       		<a href="<?php echo DIR_ADMIN; ?>?module=<?php echo $module; ?>&action=<?php echo $action;?>&sort_by=sending_count&sort_dir=<?php echo ( ($sort_by == "sending_count" and $sort_dir == "asc") ? "desc" : "asc"); ?><? echo $filtres_query; ?>" class="ajax_link" data-module="<?php echo $module; ?>"> Кол-во писем <img src="<?php echo $dir_images; ?>icon.png" alt="icon"/></a>
                       </th>
                       
                       <th class="header <?php if ($sort_by == "enabled") echo ($sort_dir == "asc" ? "headerSortUp" : "headerSortDown"); ?>">
                       		<a href="<?php echo DIR_ADMIN; ?>?module=<?php echo $module; ?>&action=<?php echo $action;?>&sort_by=enabled&sort_dir=<?php echo ( ($sort_by == "enabled" and $sort_dir == "asc") ? "desc" : "asc"); ?><? echo $filtres_query; ?>" class="ajax_link" data-module="<?php echo $module; ?>"> Готовность <img src="<?php echo $dir_images; ?>icon.png" alt="icon"/></a>
                       </th>
                       
                       <th>&nbsp;</th>
                    </tr>
                </tfoot>
            </table>
        </div>

    <?php $site->tpl->display('paging'); ?>
		<?php if($action=='index' or $action=='edit') { ?>
        <div class="combo">
            <span class="btn gray">
                <button>Удалить отмеченные</button>
            </span>
            <input type="hidden" name="do_active" value="delete">
            <input type="hidden" name="group_actions" value="0">
        </div>
        <?php } ?>
    </form>
<?php } else {
    ?>
    <h3>По заданными критериям ничего не найдено</h3>
    <?php
}
?>