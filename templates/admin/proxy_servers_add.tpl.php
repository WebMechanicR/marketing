	
				<h1><img class="catalog-icon" src="<?php echo $dir_images;?>icon.png" alt="icon"/> <?php if(isset($server['id']) and $server['id']>0) { ?>Редактировать<?php } else { ?>Добавить<?php } ?> Прокси сервер</h1>

                <form action="<?php echo DIR_ADMIN; ?>?module=<?php echo $module;?>&action=edit_proxy" method="post">
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
									<label for="page-caption">IP</label>
									<div class="input text <?php if(isset($errors['ip'])) echo "fail";?>">
										<input type="text" id="page-caption" name="ip" value="<?php if(isset($server['ip'])) echo $server['ip'];?>"/>
                                                                                <?php if(isset($errors['ip'])) { ;?><p class="error">это поле обязательно для заполнения</p><?php } ?>
									</div>
				</li>
                                <li>
									<label >Порт</label>
									<div class="input text">
										<input type="text"  name="port" value="<?php if(isset($server['port']) and $server['port']) echo $server['port']; else echo "8080"; ?>"/>
									</div>
				</li>
                                <li>
					<label>Тип</label><br/>
                                        <label><input type="checkbox" name="type_http" value="1" <?php if(isset($server['type_http']) and $server['type_http']) echo 'checked'; ?>/> HTTP</label><br/>
                                        <label><input type="checkbox" name="type_https" value="1" <?php if(isset($server['type_https']) and $server['type_https']) echo 'checked'; ?>/> HTTPS</label><br/>
                                        <label><input type="checkbox" name="type_socks4" value="1" <?php if(isset($server['type_socks4']) and $server['type_socks4']) echo 'checked'; ?>/> SOCKS4</label><br/>
                                        <label><input type="checkbox" name="type_socks5" value="1" <?php if(isset($server['type_socks5']) and $server['type_socks5']) echo 'checked'; ?>/> SOCKS5</label><br/>
				</li>
                                <li>
					<label>Уровень</label>
                                        <div class="input">
                                            <select name="anonymous" class="select">
                                                <option value="3" <?php if(isset($server['anonymous']) and $server['anonymous'] == 3) echo 'selected'; ?>>Прозрачный</option>
                                                <option value="2" <?php if(isset($server['anonymous']) and $server['anonymous'] == 2) echo 'selected'; ?>>Анонимный</option>
                                                <option value="1" <?php if(isset($server['anonymous']) and $server['anonymous'] == 1) echo 'selected'; ?>>Элитный</option>
                                            </select>
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
									<label >Страна</label>
									<div class="input text">
										<input type="text"  name="country" value="<?php if(isset($server['country'])) echo $server['country'];?>"/>
									</div>
                                                                </li>
                                                                <li>
                                                                    <label><strong>Последняя дата проверки:</strong> <?php echo (isset($server['last_checking']) and $server['last_checking'])?date('d.m H:i', $server['last_checking']):''; ?></label>
                                                                    <br/><a href="#" class="js-check-proxy">проверить</a>
                                                                </li>
                                                                <li>
									<label>Блокирован в </label><br/>
                                                                        <label><input type="checkbox" name="blocked_in_mail" value="1" <?php if(isset($server['blocked_in_mail']) and $server['blocked_in_mail']) echo 'checked'; ?>/> Mail</label><br/>
                                                                        <label><input type="checkbox" name="blocked_in_gmail" value="1" <?php if(isset($server['blocked_in_gmail']) and $server['blocked_in_gmail']) echo 'checked'; ?>/> Gmail</label><br/>
                                                                        <label><input type="checkbox" name="blocked_in_yandex" value="1" <?php if(isset($server['blocked_in_yandex']) and $server['blocked_in_yandex']) echo 'checked'; ?>/> Yandex</label><br/>
                                                                        <label><input type="checkbox" name="blocked_in_rambler" value="1" <?php if(isset($server['blocked_in_rambler']) and $server['blocked_in_rambler']) echo 'checked'; ?>/> Rambler</label><br/>
                                                                        <label><input type="checkbox" name="blocked_in_yahoo" value="1" <?php if(isset($server['blocked_in_yahoo']) and $server['blocked_in_yahoo']) echo 'checked'; ?>/> Yahoo</label><br/>
                                                                </li>
                                                                 <li>
								     <label><input type="checkbox" name="do_not_delete" value="1" <?php if(isset($server['do_not_delete']) and $server['do_not_delete']) echo 'checked'; ?>/> <strong style="text-transform: uppercase">Не удалять, если неактивен</strong></label><br/>
                                                                        
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
							<button class="delete-confirm" data-module="<?php echo $module;?>" data-text="Вы действительно хотите удалить этот сервер?" data-url="<?php echo DIR_ADMIN; ?>?module=<?php echo $module;?>&server=delete_proxy&id=<?php echo $server['id']; ?>">
								<span><img class="bicon cross-w" src="<?php echo $dir_images;?>icon.png" alt="icon"/> Удалить сервер</span>
							</button>
						</span>
					</div>
					<?php } ?>
				</div>
                <input type="hidden" name="tab_active" value="<?php echo $tab_active;?>">
				</form>