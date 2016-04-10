				<?php $site->tpl->display('list_revisions'); ?>
                
				<h1><img class="catalog-icon" src="<?php echo $dir_images;?>icon.png" alt="icon"/> <?php if(isset($email['id']) and $email['id']>0) { ?>Редактировать<?php } else { ?>Добавить<?php } ?> рассылку</h1>

                <form action="<?php echo DIR_ADMIN; ?>?module=<?php echo $module;?>&action=edit_mail" method="post" enctype="multipart/form-data">
                <?php if(isset($email['id'])) { ?><input type="hidden" name="id" value="<?php echo $email['id'];?>"><?php } ?>
				

					<div class="tab-content">
											
							<ul class="form-lines wide left">
								<li>
									<label for="page-caption">Название</label>
									<div class="input text <?php if(isset($errors['title'])) echo "fail";?>">
										<input type="text" id="page-caption" name="title" value="<?php if(isset($email['title'])) echo $email['title'];?>"/>
                                                                                <?php if(isset($errors['title'])) { ;?><p class="error">это поле обязательно для заполнения</p><?php } ?>
									</div>
								</li>
                                                                <li>
									<label for="page-url">Тема</label>
									<div class="input text <?php if(isset($errors['theme'])) echo "fail";?>">
										<input type="text" id="page-url" name="theme" value="<?php if(isset($email['theme'])) echo $email['theme'];?>"/>
									</div>
                                                                        <?php if(isset($errors['theme'])) { ?><p class="error">это поле обязательно для заполнения</p><?php } ?>
								</li>
                                                                <li>
									<label>Шаблоны</label>
									<div class="input <?php if(isset($errors['templates'])) echo "fail";?>">
                                                                                <select class="multi_select" name="templates[]" multiple="multiple">
                                                                                    <?php
                                                                                        @$et = (array) unserialize($email['templates']);
                                                                                        
                                                                                        foreach($templates as $tpl) {
                                                                                    ?>
											<option value="<?php echo $tpl; ?>" <?php if(in_array($tpl, $et)) echo "selected";?>><?php echo $tpl; ?></option>
                                                                                    <?php } ?>
                                                                                </select>
									</div>
                                                                        <?php if(isset($errors['templates'])) { ?><p class="error">это поле обязательно для заполнения</p><?php } ?>
                                                                        <p class="small error">
                                                                            Используйте в шаблоне запись вида #{word1|word2|word3} для случайного выбора из множества вариантов (для обхода спам-фильтров)<br/>
                                                                            Не забудьте добавить конструкции img src=#{<?php echo SITE_URL; ?>?module=emails&action=register_opening_email&ti=<?php echo isset($email['id'])?$email['id']:0; ?>|url_to_reg2} для возможности отслеживания письма<br/>
                                                                            и img src=#{<?php echo SITE_URL; ?>?module=emails&action=unsubscribe&ti=<?php echo isset($email['id'])?$email['id']:0; ?>|url_to_reg3} для предоставления возможности отписываться от рассылки
                                                                        </p>
                                                                        <input type="hidden" name="sort" value="<?php if(isset($email['sort'])) echo $email['sort'];?>"/>
								</li>
                                                                <li>
                                                                    <label>SQL фильтр адресов</label>
                                                                    <div class="input textarea">
                                                                        <textarea  name="sql"><?php if (isset($email['sql'])) echo F::br2nl($email['sql']); ?></textarea>
                                                                    </div>
                                                                    Адресов выбрано: <strong><?php echo isset($email['count_by_sql'])?$email['count_by_sql']:intval(0); ?></strong> <a href="" class="sending-link">обновить</a>
                                                                    <input type="hidden" name="sort" value="<?php if(isset($email['sort'])) echo $email['sort'];?>"/>
                                                                </li>
                                                                <?php if(isset($email['id']) and $email['id']) { ?>
                                                                <li>
                                                                    <label> Статус <a href="<?php echo DIR_ADMIN; ?>?module=<?php echo $module;?>&action=edit_mail&id=<?php echo isset($email['id'])?$email['id']:0; ?>">обновить статус</a>
                                                                    
                                                                    
                                                                     <?php 
                                                                            if($this->request->get('send') and isset($delivery_info))
                                                                            {
                                                                                ?>
                                                                                    <br/>
                                                                                    <span style="color: red">
                                                                                        Помещено в очередь <br>
                                                                                        макс. ожидание до начала отправки: <?php echo $delivery_info['waiting_to_start']; ?> сек.<br>
                                                                                        приблизительное время отправки: <?php echo $delivery_info['waiting_to_complete']; ?> сек.<br>
                                                                                        писем в рассылке: <?php echo $delivery_info['num_emails']; ?><br>
                                                                                        серверов задействованно: <?php echo $delivery_info['num_servers']; ?><br>
                                                                                    </span>
                                                                                <?php
                                                                            }
                                                                        ?>
                                                                    </label>
                                                                    <div style="margin: 7px 0 ;" id="m-status">
                                                                        Обновляется ...
                                                                    </div>
                                                                    <br/>
                                                                    <a class="red btn sending-link" href="<?php echo DIR_ADMIN; ?>?module=<?php echo $module;?>&action=edit_mail&id=<?php echo isset($email['id'])?$email['id']:0; ?>&send=1" style="padding: 10px; color: white; text-align: center; cursor: pointer;">
                                                                        Разослать
                                                                    </a>
                                                                    
                                                                </li>
                                                                <li>
                                                                    <label>
                                                                        Отправлять (периодически) тестовую рассылку по указанным адресам (отправляется со случайных серверов)
                                                                        <?php 
                                                                            if($this->request->get('send_test'))
                                                                            {
                                                                                ?>
                                                                                    <br/><span style="color: red">Помещено в очередь (макс. ожидание до отправки: <?php echo isset($waiting_to_test)?$waiting_to_test:0; ?> сек.)</span>
                                                                                <?php
                                                                            }
                                                                        ?>
                                                                    </label>
                                                                    <div class="input text <?php if(isset($errors['test_mails'])) echo "fail";?>">
									<input type="text" name="test_mails" value="<?php if(isset($email['test_mails'])) echo $email['test_mails'];?>"/>
                                                                        <?php if(isset($errors['test_mails'])) { ;?><p class="error">это поле обязательно для заполнения</p><?php } ?>
								    </div>
                                                                    <p class="small">
                                                                            адреса через запятую
                                                                    </p>
                                                                    <a class="red btn sending-link" href="<?php echo DIR_ADMIN; ?>?module=<?php echo $module;?>&action=edit_mail&id=<?php echo isset($email['id'])?$email['id']:0; ?>&send_test=1" style="padding: 10px; color: white; text-align: center; cursor: pointer;">
                                                                         Тест
                                                                    </a>
                                                                </li>
                                                                <?php } ?>
							</ul>
                                                        <ul class="form-lines right">
                                                            <li><label><input type="checkbox" name="send_unsubscribed" value="1" <?php echo (isset($email['send_unsubscribed']) and $email['send_unsubscribed'])?'checked':''; ?>/> Рассылать отписавшимся </label></li>
                                                        </ul>
                                                        <script type="text/javascript">
                                                            $('a.sending-link').on('click', function(e){
                                                                e.preventDefault();
                                                                $(this).text('ждите...');
                                                                var self = $(this);
                                                                var data = $(this).closest('form').serialize();
                                                                $.post($(this).closest('form').attr('action'), data, function(data){
                                                                    if(data!=1)
                                                                        self.closest('#contentHelper').html(data);
                                                                    else
                                                                        window.location.href=self.attr('href');
                                                                })
                                                            });
                                                            $('#m-status').load('<?php echo DIR_ADMIN; ?>?module=<?php echo $module;?>&action=status #mail_status');
                                                        </script>
							
							<div class="clear"></div>
					</div><!-- .tab-content end -->
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
					<?php if(isset($email['id']) and $email['id']>0) { ?>
                                        <div class="right">
						<span class="btn standart-size red">
							<button class="delete-confirm" data-module="<?php echo $module;?>" data-text="Вы действительно хотите удалить эту рассылку?" data-url="<?php echo DIR_ADMIN; ?>?module=<?php echo $module;?>&action=delete_mail&id=<?php echo $email['id']; ?>">
								<span><img class="bicon cross-w" src="<?php echo $dir_images;?>icon.png" alt="icon"/> Удалить</span>
							</button>
						</span>
					</div>
					<?php } ?>
				</div>
                <input type="hidden" name="tab_active" value="<?php echo $tab_active;?>">
				</form>