				<?php $site->tpl->display('list_revisions'); ?>
                
				<h1><img class="news-icon" src="<?php echo $dir_images;?>icon.png" alt="icon"/> <?php if(isset($news['id']) and $news['id']>0) { ?>Редактировать<?php } else { ?>Добавить<?php } ?> новость</h1>

                <form action="<?php echo DIR_ADMIN; ?>?module=<?php echo $module;?>&action=edit" method="post" enctype="multipart/form-data">
                <?php if(isset($news['id'])) { ?><input type="hidden" name="id" value="<?php echo $news['id'];?>"><?php } ?>
				
				<div class="tabs">
					<ul class="bookmarks">
						<li <?php if($tab_active=="main")  { ?>class="active"<?php } ?>><a href="#" data-name="main">Содержание</a></li>
						<li <?php if($tab_active=="photo")  { ?>class="active"<?php } ?>><a href="#" data-name="photo">Фото</a></li>
						<li <?php if($tab_active=="seo")  { ?>class="active"<?php } ?>><a href="#" data-name="seo">SEO</a></li>
						<li <?php if($tab_active=="other")  { ?>class="active"<?php } ?>><a href="#" data-name="other">Другие поля</a></li>
					</ul>

					<div class="tab-content">
											
							<ul class="form-lines wide left">
								<li>
									<label for="page-caption">Заголовок</label>
									<div class="input text <?php if(isset($errors['title'])) echo "fail";?>">
										<input type="text" id="page-caption" name="title" <?php if(!isset($news['id']) or $news['id']<1) { ?>class="title_for_slug"<?php } ?> value="<?php if(isset($news['title'])) echo $news['title'];?>"/>
	                                    <?php if(isset($errors['title'])) { ;?><p class="error">это поле обязательно для заполнения</p><?php } ?>
									</div>
								</li>
                                <li>
                                    <label>Краткий текст</label>
                                    <div class="frame small_editor">
                                        <textarea name="brief_description" id="brief_description"><?php if(isset($news['brief_description'])) echo $news['brief_description'];?></textarea>
                                        <script>
                                            CKEDITOR.replace( 'brief_description', {height: 150} );
                                        </script>
                                    </div>
                                </li>
							</ul>
							
							<ul class="form-lines narrow right">
								<li>
									<label>Статус</label>
									<div class="input">
										<select class="select" name="enabled">
											<option value="1" <?php if(isset($news['enabled']) and $news['enabled']==1) echo "selected";?>>Опубликована</option>
											<option value="0" <?php if(isset($news['enabled']) and $news['enabled']==0) echo "selected";?>>Скрыта</option>
										</select>
									</div>
								</li>
                                <li>
                                    <label>Дата</label>
                                    	<input type="hidden" name="h" value="<?php if(isset($news['date_add'])) echo date('H', $news['date_add']);?>">
                                    	<input type="hidden" name="m" value="<?php if(isset($news['date_add'])) echo date('i', $news['date_add']);?>">
                                    <div class="input date">
                                        <input type="text" name="date" value="<?php if(isset($news['date_add'])) echo date('d.m.Y', $news['date_add']);?>" />
                                    </div>
                                </li>
							</ul>
							
							<div class="clear"></div>
							

							<label>Текст</label>
							<div class="frame editor">
								<textarea name="body" id="body"><?php if(isset($news['body'])) echo $news['body'];?></textarea>
                                <script>
									CKEDITOR.replace( 'body', {height: 500} );
								</script>
							</div>
							
						
					</div><!-- .tab-content end -->
					
					<div class="tab-content">
						<?php $site->tpl->display('content_photos'); ?>
					</div><!-- .tab-content end -->
					<div class="tab-content ">
						<ul class="form-lines wide">
							<li>
									<label for="page-meta-title">Заголовок страницы</label>
									<div class="input text ">
										<input  class="seo-input" type="text" id="page-meta-title" name="meta_title" value="<?php if(isset($news['meta_title'])) echo $news['meta_title'];?>"/>
									</div>
							</li>
							<li>
								<label>Описание страницы (meta description)</label>
								<div class="input textarea">
									<textarea class="seo-input" cols="30" rows="10" name="meta_description"><?php if(isset($news['meta_description'])) echo $news['meta_description'];?></textarea>
								</div>
								<p class="small">рекомендуется не больше 250 символов</p>
							</li>
							<li>
								<label>Ключевые слова (meta keywords)</label>
								<div class="input textarea">
									<textarea class="seo-input" cols="30" rows="10" name="meta_keywords"><?php if(isset($news['meta_keywords'])) echo $news['meta_keywords'];?></textarea>
								</div>
								<p class="small">все слова пишутся через запятую, слова должны встречаться в тексте, рекомендуется не больше 10 слов</p>
							</li>
						</ul>
					</div><!-- .tab-content end -->
					<div class="tab-content">
						<ul class="form-lines wide left">
							<li>
									<label for="page-url">URL <img class="q-ico" src="<?php echo $dir_images;?>icon.png" alt="question" rel="tooltip" title='имя файла должно содержать только латинские символы и символы "_", "-", ".".' /></label>
									<div class="input text <?php if(isset($errors['url'])) echo "fail";?>">
										<input type="text" id="page-url" name="url" class="url_slug" value="<?php if(isset($news['url'])) echo $news['url'];?>"/>
	                                    <?php if(isset($errors['url']) and $errors['url']=="no_url") { ;?><p class="error">это поле обязательно для заполнения</p><?php } ?>
	                                    <?php if(isset($errors['url']) and $errors['url']=="error_url") { ;?><p class="error">недопустимый URL</p><?php } ?>
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
                                <span><img class="bicon check-w" src="<?php echo $dir_images;?>icon.png" alt="icon"/> <i>Сохранить</i></span>
                            </button>
						</span>
                        <span class="btn standart-size blue hide-icon">
							<button class="submit_and_exit">
								<span>Сохранить и выйти</span>
							</button>
						</span>
                   </div>
					<?php if(isset($news['id']) and $news['id']>0) { ?>
                   <div class="right">
						<span class="btn standart-size red">
							<button class="delete-confirm" data-module="<?php echo $module;?>" data-text="Вы действительно хотите удалить эту новость?" data-url="<?php echo DIR_ADMIN; ?>?module=<?php echo $module;?>&action=delete&id=<?php echo $news['id']; ?>">
								<span><img class="bicon cross-w" src="<?php echo $dir_images;?>icon.png" alt="icon"/> Удалить новость</span>
							</button>
						</span>
					</div>
					<?php } ?>
				</div>
                <input type="hidden" name="tab_active" value="<?php echo $tab_active;?>">
				</form>