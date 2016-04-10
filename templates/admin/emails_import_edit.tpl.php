<?php 
	 $admin_info = $this->admins->get_admin_info();
?>

    <?php if($site->request->get("sub_action", "string")!='add' ) { ?>
    <div class="bt-set clip">
        <div class="left">
                <span class="btn standart-size blue">
                    <a href="<?php echo DIR_ADMIN;?>?module=<?php echo $module;?>&action=advanced_import&sub_action=add" class="button hide-icon ajax_link" data-module="<?php echo $module;?>">
                            <span>Добавить новый</span> 
                    </a>
                </span>
                <br/>
        </div>
    </div>
   <?php } else { ?>
        
   <?php } ?>

<?php if((isset($email['id']) and $email['id']) or $site->request->get("sub_action", "string")=='add' or $errors) { ?>
<form action="<?php echo DIR_ADMIN; ?>?module=<?php echo $module; ?>&action=advanced_import" method="post" enctype="multipart/form-data">
                <span class="btn standart-size blue">
                    <a href="" class="button hide-icon open-site" data-module="<?php echo $module; ?>">
                        <span>Открыть сайт</span> 
                    </a>
                </span>
                <span class="btn standart-size blue">
                    <a href="" class="button hide-icon open-yandex" data-module="<?php echo $module; ?>">
                        <span>Открыть yandex</span> 
                    </a>
                </span>
                <span class="btn standart-size blue">
                    <a href="" class="button hide-icon open-social" data-module="<?php echo $module; ?>">
                        <span>Открыть cоц.cеть</span> 
                    </a>
                </span> 
                <br/>
                <span class="btn standart-size blue">
                    <a href="#" class="button check-in-base hide-icon" data-module="<?php echo $module;?>">
                            <span>Проверить по базе</span>
                    </a>
                </span>
                <span class="btn standart-size red">
                                <a href="<?php echo DIR_ADMIN;?>?module=<?php echo $module;?>&action=advanced_import&save=<?php echo isset($email['id'])?$email['id']:0; ?>" class="button hide-icon save-this" data-module="<?php echo $module;?>">
                                        <span>Сохранить контакт</span>
                                </a>
                </span>
                <script>
                $(function() { 
                    $('a.check-in-base').click(function(e){
                        e.preventDefault();
                        var form = $(this).closest('form');
                       
                        var url = '<?php echo DIR_ADMIN;?>?module=<?php echo $module;?>&action=checking_in_base&check=1&brief=1';
                        var criteriums = ['city','name', 'email', 'email2', 'tel1', 'tel2', 'tel3', 'site_url', 'vk_link'];
                        for(i = 0; i < criteriums.length; i++){
                            url += "&" + criteriums[i] + "=" + form.find('input[name='+criteriums[i]+']').val();
                        }
                        
                       sD.loadPageInner(url, $('#leftContent'));
                    });    
                    $('a.save-this').click(function(e){
                        e.preventDefault();
                        var form = $(this).closest('form');
                        form.attr('action', $(this).attr('href'));
                        form.submit();
                    });  
                    $('a.open-yandex').click(function(e){
                        e.preventDefault();
                        window.open('http://yandex.ru');
                    }); 
                    $('a.open-social').click(function(e){
                        e.preventDefault();
                        var url = $(this).closest('form').find('input[name=vk_link]').val();
                        if(url)
                        window.open(url);
                        else
                            alert('Заполните поле');
                    }); 
                    $('a.open-site').click(function(e){
                        e.preventDefault();
                        var url = $(this).closest('form').find('input[name=site_url]').val();
                        if(url){
                            $('#browser-iframe').attr('src', url);
                            $('#browser').show(500);
                        }
                        else
                            alert('Заполните поле');
                    }); 
                   
                });
                </script>

    <?php if (isset($email['id'])) { ?><input type="hidden" name="id" value="<?php echo $email['id']; ?>"><?php } ?>
        <div class="tab-content">
            <table class="twocolumns-form">
                <tr>
                    <td>
                        <label>Готовность</label>
                    </td>   
                    <td>   
                        <br/><?php if (isset($email['enabled'])) echo $email['enabled']; ?><br/><br/>
                    </td>
                </tr>
                <tr>
                	<td>
                    <label>Тип сайта</label>
                    </td>
                    <td>
                    <div class="input">
                        <select class="select" name="template_type">
                            <?php
                            foreach (System::$CONFIG['template_types'] as $key => $val) {
                                ?>
                                    <option value="<?php echo $key; ?>" <?php if (isset($email['template_type']) and $email['template_type'] == $key) echo "selected"; ?>><?php echo $val; ?></option>
                                <?php
                            }
                            ?>
                        </select>
                    </div>
                    </td>
                </tr>
                <tr>
                	<td>
                    <label>Имя заведения</label>
                    </td>
                    <td>
                    <div class="input text <?php if (isset($errors['name'])) echo "fail"; ?>">
                        <input type="text"  name="name" value="<?php if (isset($email['name'])) echo $email['name']; ?>"/>
                    </div>
                    <?php if (isset($errors['name'])) { ?><p class="error">это поле обязательно для заполнения</p><?php } ?>
                    </td>
                </tr>
                <tr>
                	<td>
                    <label>Тип заведения</label>
                    </td>
                    <td>
                    <div class="input" <?php if (isset($errors['org_type'])) echo "fail"; ?>>
                        <select class="select" name="org_type">
                        			<option value="0">Выбрать</option>
                            <?php
                            foreach ($org_types as $org_type) {
                                ?>
                                    <option value="<?php echo $org_type['id']; ?>" <?php if (isset($email['org_type']) and $email['org_type'] == $org_type['id']) echo "selected"; ?>><?php echo $org_type['name']; ?></option>
                                <?php
                            }
                            ?>
                        </select>
                    </div>
                    <?php if (isset($errors['org_type'])) { ?><p class="error">это поле обязательно для заполнения</p><?php } ?>
                    <a href="#" class="link-new-org-type">добавить новый</a>
                    </td>
                </tr>
                <tr class="wrap-new-org-type" style="display:none;">
                	<td>
                    <label>Новый тип заведения</label>
                    </td>
                    <td>
                    	<div class="input text">
                        	<input type="text"  name="new_org_type" value=""/>
                    	</div>
                    </td>
                </tr>
                <tr class="wrap-new-org-type" style="display:none;">
                	<td>
                    <label>Новый тип заведения в род. падеже</label>
                    </td>
                    <td>
                    	<div class="input text">
                        	<input type="text"  name="new_org_type_rod" value=""/>
                    	</div>
                    </td>
                </tr>
                <script>
                       $( function() {
							$('.link-new-org-type').click(function(e) {
									e.preventDefault();
									$('.wrap-new-org-type').fadeIn(500);
									$(this).closest('tr').hide();
									$(this).closest('tr').find('select').val(0);
									return false;
							});
                       });
                </script>

                <tr>
                	<td>
                    <label>Страна</label>
                    </td>
                    <td>
                    <div class="input text">
                       <input type="text" id="country2" name="country" value="<?php if (isset($country_name)) echo $country_name; ?>" placeholder="Начните вводить название"/>
                    </div>
                   <script>
                                                                                 $( function() {
                                                                                         // Подгружаемые логины

                                                                                         $("#country2").autocomplete({
                                                                                                 serviceUrl:'<?php echo DIR_ADMIN; ?>ajax_search_country.php',
                                                                                                 minChars:2,
                                                                                                 noCache: false,
                                                                                                 deferRequestBy: 500
                           });
                       });
                   </script>
                   </td>
                </tr>
                <tr>
                	<td>
                    <label>Город</label>
                    </td>
                    <td>
                    <div class="input text">
                       <input type="text" id="city2" name="city" value="<?php if (isset($city_name)) echo $city_name; ?>" placeholder="Начните вводить название"/>
                    </div>
                   <script>
                                                                                 $( function() {
                                                                                         // Подгружаемые логины

                                                                                         $("#city2").autocomplete({
                                                                                                 serviceUrl:'<?php echo DIR_ADMIN; ?>ajax_search_city.php',
                                                                                                 minChars:2,
                                                                                                 noCache: false,
                                                                                                 deferRequestBy: 500
                           });
                       });
                   </script>
                   </td>
                </tr>
                <tr>
                    <td>
                    <label>Адрес</label>
                    </td>
                    <td>
                    <div class="input text">
                        <input type="text"  name="address" value="<?php if (isset($email['address'])) echo $email['address']; ?>"/>
                    </div>
                    </td>
                </tr>
                <tr>
                     <td>
                    <label>Cайт</label>
                    </td>
                    <td>
                    <div class="input text">
                        <input type="text"  name="site_url" value="<?php if (isset($email['site_url'])) echo $email['site_url']; ?>"/>
                    </div>
                    </td>
                </tr>
                <tr>
                    <td>
                    <label>Соц.сеть</label>
                    </td>
                    <td>
                    <div class="input text">
                        <input type="text"  name="vk_link" value="<?php if (isset($email['vk_link'])) echo $email['vk_link']; ?>"/>
                    </div>
                    </td>
                </tr>
                <tr>
                    <td>
                    <label>Качество сайта</label>
                    </td>
                    <td>
                    <div class="input">
                        <select class="select" name="site_quality">
                            <?php
                            foreach (System::$CONFIG['site_quality'] as $key => $val) {
                                ?>
                                    <option value="<?php echo $key; ?>" <?php if (isset($email['site_quality']) and $email['site_quality'] == $key) echo "selected"; ?>><?php echo $val; ?></option>
                                <?php
                            }
                            ?>
                        </select>
                    </div>
                    </td>
                </tr>
                <tr>
                	<td>
                    <label>Email 1<input type="checkbox" class="main_e" name="main_e" value="1" <?php if(isset($email['main_e']) and $email['main_e'] == 1) echo 'checked'; ?>/> </label>
                    </td>
                    <td>
                    <div class="input text <?php if (isset($errors['email'])) echo "fail"; ?>">
                        <input type="text" id="page-caption" name="email" value="<?php if (isset($email['email'])) echo $email['email']; ?>"/>
                    </div>
                        <?php if (isset($errors['email'])) { ?><p class="error">это поле обязательно для заполнения</p><?php } ?>
                    </td>
                </tr>
                <tr>
                	<td>
					<label>Email 2 <input type="checkbox" class="main_e" name="main_e" value="2" <?php if(isset($email['main_e']) and $email['main_e'] == 2) echo 'checked'; ?>/> </label>
                    </td>
                    <td>
                    <div class="input text">
                        <input type="text" id="page-caption2" name="email2" value="<?php if (isset($email['email2'])) echo $email['email2']; ?>"/>
                    </div>
                    </td>
                </tr>
                <tr>
                    <td>
                    <label>Телефон 1 <input type="checkbox" class="main_t" name="main_t" value="1" <?php if(isset($email['main_t']) and $email['main_t'] == 1) echo 'checked'; ?>/> </label>
                    </td>
                    <td>
                    <div class="input text">
                        <input type="text"  name="tel1" value="<?php if (isset($email['tel1'])) echo $email['tel1']; ?>"/>
                    </div>
                    </td>
                </tr>
                <tr>
                    <td>
                    <label>Телефон 2 <input type="checkbox" class="main_t" name="main_t" value="2" <?php if(isset($email['main_t']) and $email['main_t'] == 2) echo 'checked'; ?>/> </label>
                    </td>
                    <td>
                    <div class="input text">
                        <input type="text"  name="tel2" value="<?php if (isset($email['tel2'])) echo $email['tel2']; ?>"/>
                    </div>
                    </td>
                </tr>
                <tr>
                	<td>
                    <label>Мобильный 3 <input type="checkbox" class="main_t" name="main_t" value="3" <?php if(isset($email['main_t']) and $email['main_t'] == 3) echo 'checked'; ?>/> </label>
                    </td>
                    <td>
                    <div class="input text <?php if (isset($errors['tel3'])) echo "fail"; ?>">
                        <input type="text"  name="tel3" value="<?php if (isset($email['tel3'])) echo $email['tel3']; ?>"/>
                    </div>
                        <?php if (isset($errors['tel3'])) { ?><p class="error">неверный телефон, должно быть 10 цифр</p><?php } ?>
                    </td>
                </tr>
                <tr>
                	<td>
                    <label>Мобильный 4 <input type="checkbox" class="main_t" name="main_t" value="4" <?php if(isset($email['main_t']) and $email['main_t'] == 4) echo 'checked'; ?>/> </label>
                    </td>
                    <td>
                    <div class="input text <?php if (isset($errors['tel4'])) echo "fail"; ?>">
                        <input type="text"  name="tel4" value="<?php if (isset($email['tel4'])) echo $email['tel4']; ?>"/>
                    </div>
                        <?php if (isset($errors['tel4'])) { ?><p class="error">неверный телефон, должно быть 10 цифр</p><?php } ?>
                    </td>
                </tr>
                <tr>
                    <td>
                    <label>ФИО директора</label>
                    </td>
                    <td>
                    <div class="input text">
                        <input type="text"  name="chief_name" value="<?php if (isset($email['chief_name'])) echo $email['chief_name']; ?>"/>
                    </div>
                    </td>
                </tr>
               <tr>
                    <td>
                    <label>Источник</label>
                    </td>
                    <td>
                    <div class="input text">
                        <input type="text"  name="source" value="<?php if (isset($email['source'])) echo $email['source']; ?>"/>
                    </div>
                    </td>
                </tr>

                <?php 
                
                if($admin_info['access_class'] <= 2){
                    ?>
                        <tr>
                            <td>
                            <label>Администратор</label>
                            </td>
                            <td>
                            <div class="input">
                                <select class="select" name="admin">
                                    <?php
                                    foreach ($admins as $val) {
                                        ?>
                                            <option value="<?php echo $val['id']; ?>" <?php if (isset($email['admin']) and $email['admin'] == $val['id']) echo "selected"; ?>><?php echo $val['name']; ?></option>
                                        <?php
                                    }
                                    ?>
                                </select>
                            </div>
                            </td>
                        </tr>
                    <?php
                }
               
                ?>
                <tr>
                	<td>
                    <label>Комментарий</label>
                    </td>
                    <td>
                    <div class="input textarea">
                        <textarea  name="comment"><?php if (isset($email['comment'])) echo F::br2nl($email['comment']); ?></textarea>
                    </div>
                		<input type="hidden" name="sort" value="<?php if (isset($email['sort'])) echo $email['sort']; ?>"/>
                    </td>
                </tr>
            </table>

            

        </div><!-- .tab-content end -->
           <?php if($admin_info['access_class'] <= 2 or (isset($email['id']) and $email['id'] and $admin_info['id'] == $email['admin'])) { ?>
           <div class="bt-set clip">
            	<div class="left">
                    <span class="btn standart-size blue hide-icon">
                        <button class="ajax_submit" data-success-name="Cохранено">
                            <span><img class="bicon check-w" src="<?php echo $dir_images; ?>icon.png" alt="icon"/> <i><?php if(isset($email['id']) and $email['id']) { ?>Сохранить<?php } else { ?>Добавить<?php } ?></i></span>
                        </button>
                    </span>
                </div>
                   <?php if($site->request->get("sub_action", "string")!='add' ) { ?>
                <div class="right">
                    <span class="btn standart-size red">
                                <a href="<?php echo DIR_ADMIN;?>?module=<?php echo $module;?>&action=advanced_import&del=<?php echo isset($email['id'])?$email['id']:0; ?>" class="button hide-icon " data-module="<?php echo $module;?>">
                                        <span>Удалить</span>
                                </a>
                    </span>
                </div>
                   <?php } ?>
                <div class="right">
                    <span class="btn standart-size red">
                                <a href="<?php echo DIR_ADMIN;?>?module=<?php echo $module;?>&action=advanced_import" class="button hide-icon" data-module="<?php echo $module;?>">
                                        <span>Отмена</span>
                                </a>
                    </span>
                </div>
           	</div>
            <?php } ?>


    <input type="hidden" name="tab_active" value="<?php echo $tab_active; ?>">
</form>
<?php } else if($this->request->method() == 'post') { ?>
<br/>
<h2>Успешно добавлено.</h2>
<?php } ?>
<script type="text/javascript">
    $(function(){
        $('input.main_e').each(function(i, v){
            if($(this).prop('checked')){
                $('input.main_e').not($(this)).prop('checked', false);
            }
        });
        $('input.main_e').on('change', function(){
           if($(this).prop('checked'))
               $('input.main_e').not($(this)).prop('checked', false);
        });
         $('input.main_t').each(function(i, v){
            if($(this).prop('checked')){
                $('input.main_t').not($(this)).prop('checked', false);
            }
        });
        $('input.main_t').on('change', function(){
           if($(this).prop('checked'))
               $('input.main_t').not($(this)).prop('checked', false);
        });
        
        $('.clear-form').click(function(){ 
                var form = $(this).closest('form');
                form.find('input[type=text]').val('');
                form.find('textarea').html('');
                form.find('input[type=checkbox]').prop('checked', false);
        });
    });
</script>


