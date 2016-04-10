                <div class="bt-set right">
					<span class="btn standart-size">
						<a href="<?php echo DIR_ADMIN; ?>?module=<?php echo $module;?>&action=add_mail" class="button ajax_link" data-module="<?php echo $module;?>">
							<span><img class="bicon plus-s" src="<?php echo $dir_images;?>icon.png" alt="icon"/> Создать рассылку</span>
						</a>
					</span>
				</div>
				<h1><img class="catalog-icon" src="<?php echo $dir_images;?>icon.png" alt="icon"/> Рассылки</h1>
                <?php
					if(count($list_emails)>0) {
				?>

                <form action="<?php echo DIR_ADMIN; ?>?module=<?php echo $module;?>&action=mails" method="post">
				<div class="product-table emails sortable">
					<table>
						<thead>
							<tr>
								<th>
									<input type="checkbox"/>
								</th>
								<th>Название</th>
								<th>Статус</th>
                                                                <th>Позиция</th>
								<th>&nbsp;</th>
							</tr>
						</thead>
                        
						<tbody>
                        <?php 
							foreach($list_emails as $email) { 
							?>
							<tr class="update_onfly <?php echo $email['enabled'] ? '' : 'disable';?>">
                            	<td>
									<input type="checkbox" name="check_item[]" value="<?php echo $email['id'];?>"/>
                                    <input type="hidden" name="email_name[<?php echo $email['id']; ?>]" value="<?php echo $email['title']; ?>"/>
								</td>
								<td>
                                	<a href="<?php echo DIR_ADMIN;?>?module=<?php echo $module;?>&action=edit_mail&id=<?php echo $email['id'];?>" class="ajax_link" data-module="<?php echo $module;?>"><?php echo $email['title'];?></a>
								</td>
								
                                <td>
                                	<?php echo $email['enabled'] ? 'Показано' : 'Скрыто';?>
                                </td>
                                <td>
                                	<img src="<?php echo $dir_images;?>icon.png" class="eicon lines-s" alt="icon"/>
                               </td>
								<td>
									<a href="<?php echo DIR_ADMIN;?>?module=<?php echo $module;?>&action=edit_mail&id=<?php echo $email['id'];?>" class="ajax_link" data-module="<?php echo $module;?>" title="Редактировать"><img src="<?php echo $dir_images;?>icon.png" class="eicon edit-s" alt="icon"/></a>
									<a href="<?php echo DIR_ADMIN;?>?module=<?php echo $module;?>&action=duplicate_mail&id=<?php echo $email['id'];?>" class="ajax_link" data-module="<?php echo $module;?>" title="Создать копию"><img src="<?php echo $dir_images;?>icon.png" class="eicon copy-s" alt="icon"/></a>
									<a href="<?php echo DIR_ADMIN; ?>?module=<?php echo $module;?>&action=delete_mail&id=<?php echo $email['id']; ?>" class="delete-confirm" data-module="<?php echo $module;?>" data-text="Вы действительно хотите удалить эту рассылку?" title="Удалить"><img src="<?php echo $dir_images;?>icon.png" class="eicon del-s" alt="icon"/></a>
								</td>
							</tr>
						<?php } ?>	
							
						</tbody>
						<tfoot>
							<tr>
								<th>
									<input type="checkbox"/>
								</th>
								<th>Название письма</th>
								<th>Статус</th>
                                <th>Позиция</th>
								<th>&nbsp;</th>
							</tr>
						</tfoot>
					</table>
				</div>
                
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
				</form>
				<?php } ?>