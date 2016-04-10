				<?php $site->tpl->display('list_revisions'); ?>
                
				<h1><img class="catalog-icon" src="<?php echo $dir_images;?>icon.png" alt="icon"/> <?php if(isset($server['id']) and $server['id']>0) { ?>Редактировать<?php } else { ?>Добавить<?php } ?> SMTP сервер</h1>

                <form action="<?php echo DIR_ADMIN; ?>?module=<?php echo $module;?>&action=edit" method="post" enctype="multipart/form-data">
                <?php if(isset($server['id'])) { ?><input type="hidden" name="id" value="<?php echo $server['id'];?>"><?php } ?>
				
				<div class="tabs">
					<ul class="bookmarks">
						<li class="active"><a href="#" data-name="main">Содержание</a></li>
					</ul>

					<div class="tab-content">
											
				<ul class="form-lines wide left">
					
                                <li>
									<label >Заголовок</label>
									<div class="input text">
										<input type="text" name="alt_name" value="<?php if(isset($server['alt_name'])) echo $server['alt_name'];?>"/>
									</div>
				</li>    
				<li>
									<label for="page-caption">Хост</label>
									<div class="input text <?php if(isset($errors['host'])) echo "fail";?>">
										<input type="text" id="page-caption" name="host" value="<?php if(isset($server['host'])) echo $server['host'];?>"/>
                                                                                <?php if(isset($errors['host'])) { ;?><p class="error">это поле обязательно для заполнения</p><?php } ?>
									</div>
				</li>
                                <li>
									<label >Логин</label>
									<div class="input text">
										<input type="text" name="login" value="<?php if(isset($server['login'])) echo $server['login'];?>"/>
									</div>
				</li>
                                 <li>
									<label >Пароль</label>
									<div class="input text">
										<input type="text"  name="password" value="<?php if(isset($server['password'])) echo $server['password'];?>"/>
									</div>
				</li>
                                   <li>
									<label >Порт</label>
									<div class="input text">
										<input type="text"  name="port" value="<?php if(isset($server['port']) and $server['port']) echo $server['port']; else echo "25"; ?>"/>
									</div>
				</li>
                                 <li>
									<label >Защита соединения</label>
									<div class="input text">
										<input type="text"  name="secure" value="<?php if(isset($server['secure']) and $server['secure']) echo $server['secure']; ?>"/>
									</div>
				</li>
                                 <li>
									<label >Отладочная SMTP информация</label>
									<div class="input textarea">
                                                                            <textarea readonly><?php echo htmlspecialchars(isset($server['debug_info'])?$server['debug_info']:''); ?></textarea>
									</div>
				</li>
                               
								
							</ul>
                                            <ul class="form-lines right">
                                                                <li>
									<label>Статус</label>
									<div class="input">
										<select class="select" name="enabled">
											<option value="1" <?php if(isset($server['enabled']) and $server['enabled']==1) echo "selected";?>>Активен</option>
											<option value="0" <?php if(isset($server['enabled']) and $server['enabled']==0) echo "selected";?>>Не активен</option>
										</select>
									</div>
                                                                        <input type="hidden" name="sort" value="<?php if(isset($server['sort'])) echo $server['sort'];?>"/>
								</li>
                                                                <li>
                                                                    <label><strong>Отправлено писем:</strong> <?php echo isset($server['sending_count'])?$server['sending_count']:0; ?></label>
                                                                </li>
                                                                <li>
									<label>Суточный лимит (по умолчанию - 300)</label>
									<div class="input text">
										<input type="text" name="day_limit" value="<?php if(isset($server['day_limit'])) echo $server['day_limit'];?>"/>
									</div>
                                                                </li>  
                                                                <li>
									<label>В спаме у</label><br/>
                                                                        <label><input type="checkbox" name="spam_in_mail" value="1" <?php if(isset($server['spam_in_mail']) and $server['spam_in_mail']) echo 'checked'; ?>/> Mail</label><br/>
                                                                        <label><input type="checkbox" name="spam_in_gmail" value="1" <?php if(isset($server['spam_in_gmail']) and $server['spam_in_gmail']) echo 'checked'; ?>/> Gmail</label><br/>
                                                                        <label><input type="checkbox" name="spam_in_yandex" value="1" <?php if(isset($server['spam_in_yandex']) and $server['spam_in_yandex']) echo 'checked'; ?>/> Yandex</label><br/>
                                                                        <label><input type="checkbox" name="spam_in_rambler" value="1" <?php if(isset($server['spam_in_rambler']) and $server['spam_in_rambler']) echo 'checked'; ?>/> Rambler</label><br/>
                                                                        <label><input type="checkbox" name="spam_in_yahoo" value="1" <?php if(isset($server['spam_in_yahoo']) and $server['spam_in_yahoo']) echo 'checked'; ?>/> Yahoo</label><br/>
                                                                </li> 
                                            </ul>
							
							<div class="clear"></div>
					</div><!-- .tab-content end -->
						
				</div><!-- .tabs end -->
				
				<div class="bt-set clip">
                	<div class="left">
						<span class="btn standart-size blue hide-icon">
                        	<button class="ajax_submit" data-success-name="Cохранено">
                                <span><img class="bicon check-w" src="<?php echo $dir_images;?>icon.png" alt="icon"/> <i>Сохранить</i></span>
                            </button>
						</span>
                        <span class="btn standart-size blue hide-icon">
							<button class="submit_and_exit">
								<span>Сохранить и выйти</span>
							</button>
						</span>
                   </div>
					<?php if(isset($server['id']) and $server['id']>0) { ?>
                   <div class="right">
						<span class="btn standart-size red">
							<button class="delete-confirm" data-module="<?php echo $module;?>" data-text="Вы действительно хотите удалить этот сервер?" data-url="<?php echo DIR_ADMIN; ?>?module=<?php echo $module;?>&server=delete&id=<?php echo $server['id']; ?>">
								<span><img class="bicon cross-w" src="<?php echo $dir_images;?>icon.png" alt="icon"/> Удалить сервер</span>
							</button>
						</span>
					</div>
					<?php } ?>
				</div>
                <input type="hidden" name="tab_active" value="<?php echo $tab_active;?>">
				</form>