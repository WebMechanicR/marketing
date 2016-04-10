
		<h1><img class="tools-icon" src="<?php echo $dir_images;?>icon.png" alt="icon"/> Настройки</h1>

                <form action="<?php echo DIR_ADMIN; ?>?module=settings&action=edit" method="post" enctype="multipart/form-data">
                    <input type ="hidden" name="settings_flag" value="1"/>
                <?php if(isset($page_t['id'])) { ?><input type="hidden" name="id" value="<?php echo $page_t['id'];?>"><?php } ?>

				<div class="tabs">
					<ul class="bookmarks">
						<li <?php if($tab_active=="main")  { ?>class="active"<?php } ?>><a href="#" data-name="main">Основные</a></li>
						<!--<li <?php if($tab_active=="header")  { ?>class="active"<?php } ?>><a href="#" data-name="header">Шапка и подвал</a></li>
						<li <?php if($tab_active=="images")  { ?>class="active"<?php } ?>><a href="#" data-name="images">Фон, логотип, favicon</a></li>
						<li <?php if($tab_active=="seo")  { ?>class="active"<?php } ?>><a href="#" data-name="seo">SEO</a></li>
						<li <?php if($tab_active=="codes")  { ?>class="active"<?php } ?>><a href="#" data-name="codes">Виджеты и счетчики</a></li>
						<li <?php if($tab_active=="system")  { ?>class="active"<?php } ?>><a href="#" data-name="system">Системные</a></li>
                                                <?php ;//if($admin_info['id'] == 1 ) { ?><li <?php if($tab_active=="seo_promo")  { ?>class="active"<?php } ?>><a href="#" data-name="seo_promo">SEO промо</a></li><?php ;//} ?>
					-->
      </ul>

					<div class="tab-content">
							<ul class="form-lines wide">
								<li>
									<label>Интервал отправки email для одного сервера</label>
									<div class="input text">
										<input type="text" name="sending_interval" value="<?php echo $site->settings->sending_interval;?>"/>
									</div>
								</li>
                                                                <li>
									<label for="site_phone">Телефоны</label>
									<div class="input text">
										<input type="text" id="site_phone2" name="site_phone" value="<?php echo $site->settings->site_phone;?>"/>
									</div>
								</li>
								<li>
									<label for="site_email">Email администратора</label>
									<div class="input text">
										<input type="text" id="site_email2" name="site_email" value="<?php echo $site->settings->site_email;?>"/>
									</div>
								</li>
						</ul>
					</div>

                                </div>	

				<div class="bt-set clip">
                	<div class="left">
						<span class="btn standart-size blue hide-icon">
                        	<button class="ajax_submit" data-success-name="Cохранено">
                                <span><img class="bicon check-w" src="<?php echo $dir_images;?>icon.png" alt="icon"/> <i>Сохранить</i></span>
                            </button>
						</span>
                   </div>
				</div>
                <input type="hidden" name="tab_active" value="<?php echo $tab_active;?>">
				</form>