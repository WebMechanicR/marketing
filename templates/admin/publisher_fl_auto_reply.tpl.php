<div class="bt-set right">
		<span class="btn standart-size <?php echo ($enabled) ? "red" : "blue"; ?>">
			<a href="<?php echo DIR_ADMIN; ?>?module=<?php echo $module;?>&action=vk_group_parser&enabled=<?php echo ($enabled)?0:1; ?>&flag=1" class="button ajax_link" data-module="<?php echo $module;?>">
				<?php if($enabled) { ?>
                <span><img class="bicon cross-w" src="<?php echo $dir_images;?>icon.png" alt="icon"/> Выключить</span>
				<?php } else { ?>
                <span><img class="bicon check-w" src="<?php echo $dir_images;?>icon.png" alt="icon"/> Включить</span>
                <?php } ?>
            </a>
		</span>
</div>

<h1><img class="users-icon" src="<?php echo $dir_images;?>icon.png" alt="icon"/> Автоответы FL.RU</h1>
                                
<h2>Статус<img class="q-ico" src="<?php echo $dir_images;?>icon.png" alt="question" rel="tooltip" title='Вы можете видеть последний обновленный статус.' /> <a style="font-size: 13px;" href="<?php echo DIR_ADMIN; ?>?module=<?php echo $module;?>&action=fl_auto_reply" class="ajax_link" data-module="<?php echo $module;?>">Обновить статус</a></h2><br/>
<style type="text/css">
    .fl_status{
        width: 80%;
        text-align: left;
        margin: 20px 0;
    }
    .stat-item{
        margin: 7px 0;
    }
    .stat-item label{
        font-size: 15px;
        font-weight: bold;
    }
    .stat-item .s-error:not(label){
        color: red;
    }
    .s-answers{
        margin-top: 20px;
        width: 100%;
    }
    .s-answers-list{
        margin: 10px;
        width: 550px;
        height: 250px;
        overflow: scroll;
    }
    .s-answers-list .s-a-item{
        margin: 0;
        padding: 7px;
        border-bottom: 1px solid gray;
    }
    .s-a-item .s-title{
        display: table-cell;
        width: 380px;
    }
    .s-a-item .s-status{
        display: table-cell;
        width: 150px;
    }
    .s-desc{
        display: none;
    }
    .s-show-desc{
        cursor: pointer;
        font-size: 10px;
        display: block;
    }
</style>
     <div id ="fl_status" class="fl_status">
        <?php if($status and $enabled) { 
            $error = "";
            if(isset($status['error']) and $status['error'])
                $error = $status['error'];
            else if(isset($status['pro_nedeed']) and $status['pro_nedeed']){
                $error = $status['pro_nedeed'];
            }
            if($error)
            {
                ?>
                <div class="stat-item s-error">
                    <label>Ошибка:</label>
                    <?php echo $status['error']; ?>
                </div>
                <?php
            }
            if(isset($status['action']))
            {
                ?>
                <div class="stat-item s-error">
                    <label>Действие:</label>
                    <?php echo $status['action']; ?>
                </div>
                <?php
            }
            if(isset($status['statistics']['viewed_rows']) and count($status['statistics']['viewed_rows'])) {
                $answers = 0;
                $suitable = 0;
                $last_answers = 0;
                $last_suitable = 0;
                foreach($status['statistics']['viewed_rows'] as $item){
                    if($item['status'] == 1){
                        $answers++;
                        if($item['moment'] and time() - $item['moment'] < 86400){
                            $last_answers++;
                        }
                    }
                    if($item['according_to_answer']){
                        $suitable++;
                        if($item['moment'] and time() - $item['moment'] < 86400){
                            $last_suitable++;
                        }
                    }
                }
                ?>
                 <div class="stat-item">
                    <label>Всего просмотрено проектов:</label>
                    <?php echo count($status['statistics']['viewed_rows']); ?>, начиная с <?php echo date('d.m H:i', $status['statistics']['moment_of_last_row']); ?>
                 </div>
                 <div class="stat-item">
                    <label>Всего подходящих проектов:</label>
                    <?php echo $suitable; ?> (за последние 24 часа: <?php echo $last_suitable; ?>)
                 </div>
                 <div class="stat-item">
                    <label>Всего ответов:</label>
                    <?php echo $answers; ?>  (за последние 24 часа: <?php echo $last_answers; ?>)
                 </div>
                <div class="s-answers">
                    <div class="s-inputs">
                        <label><input type="checkbox" class="m-answers" name="m-answers-for-24" value="1"/> За последние 24 часа</label>
                        <label><input type="checkbox" class="m-answers" name="m-answers-with-answers" value="1"/> С ответами</label>
                        <label><input type="checkbox" class="m-answers" name="m-answers-suitable" value="1" checked/> Подходящие</label>
                    </div>
                    <div class="s-answers-list">
                        <?php foreach($status['statistics']['viewed_rows'] as $item) { ?>
                        <div class="s-a-item" data-status="<?php echo $item['status']; ?>" 
                                            data-moment="<?php echo $item['moment']; ?>" 
                                            data-suitable="<?php echo (int) (bool) $item['according_to_answer']; ?>" 
                                           <?php if(!$item['according_to_answer']) { ?>hidden<?php } ?>>
                            <div class="s-title">
                                <a href="<?php echo $item['link']; ?>" target="_blank"><?php echo $item['title']; ?></a>
                                <?php if(trim($item['desc'])) { ?>
                                <div class="s-desc">
                                    <?php echo $item['desc']; ?>
                                </div>
                                <a class="s-show-desc" href="#">
                                    описание
                                </a>
                                <?php } ?>
                            </div>
                            <div class="s-status">
                                <?php if($item['according_to_answer']) { ?>
                                <div style="color: yellowgreen;">подходит 
                                    <span style="color: black; font-size: 9px;">
                                        для "<?php echo $item['according_to_answer']['anw']; ?>" 
                                        по <?php echo $item['according_to_answer']['acc_by'] == 'title'?'заголовку':'описанию'; ?>
                                    </span>
                                </div>
                                <?php } ?>
                                <?php if($item['status'] == 1) { ?>
                                <div style="color: rosybrown;">ответ отправлен <?php if(isset($item['answered_with_testing']) and $item['answered_with_testing']) echo '(тестовый)'; ?></div>
                                <?php } ?>
                                 <?php if($item['status'] == 2) { ?>
                                <div style="color: royalblue;">исполнитель определен</div>
                                 <?php } ?>
                                <?php if($item['status'] == 3) { ?>
                                <div style="color: red;">невозможно ответить</div>
                                <?php } ?>
                            </div>
                        </div>
                        <?php } ?>
                    </div>
                </div>
                <script type="text/javascript">
                        $(function(){
                            $('input.m-answers').on('change', function(){
                                var for24 = $('input[name=m-answers-for-24]').prop('checked');
                                var w_answ = $('input[name=m-answers-with-answers]').prop('checked');
                                var suit = $('input[name=m-answers-suitable]').prop('checked');
                                $.each($('.s-a-item'), function(i, v){
                                    var item = $(this);
                                    var hide = false;
                                    if(for24 && (<?php echo time(); ?> - parseInt(item.data('moment'))) > 86400)
                                        hide = true;
                                    if(w_answ && (item.data('status') != '1'))
                                        hide = true;
                                    if(suit && (!item.data('suitable')))
                                        hide = true;
                                    if(hide)
                                        item.hide();
                                    else
                                        item.show();
                                })
                            });
                            
                            $('a.s-show-desc').on('click', function(e){
                                e.preventDefault();
                                $(this).closest('.s-title').find('.s-desc').slideToggle(100);
                            })
                        })
                </script>
                <?php 
                
            } 
        } else { ?>
            Ожидание...
        <?php } ?>
     </div>

<br/><br/>
 <form action="<?php echo DIR_ADMIN; ?>?module=<?php echo $module;?>&action=fl_auto_reply" method="post">   
     <input type="hidden" name="post_flag" value="1"/>
     <div class="tabs">
         <ul class="bookmarks">
             <li class="active"><a href="#" data-name="main">Настройки</a></li>
         </ul>

         <div class="tab-content">
             <ul class="form-lines wide">
                 <li>
                     <label>Логин</label>
                     <div class="input text <?php if (isset($errors['login'])) echo "fail"; ?>">
                         <input type="text"  name="fl_login" value="<?php if (isset($settings['login'])) echo $settings['login']; ?>"/>
                         <?php if (isset($errors['login'])) { ?><p class="error">это поле обязательно для заполнения</p><?php } ?>
                     </div>
                 </li>
                 <li>
                     <label>Пароль</label>
                     <div class="input text">
                         <input type="text" name="fl_pass" value="<?php if (isset($settings['pass'])) echo $settings['pass']; ?>"/>
                     </div>
                 </li>
                 <li>
                     <label>Глубина просмотра, ч</label>
                     <div class="input text">
                         <input type="text"  name="parsing_deep" value="<?php if (isset($settings['parsing_deep'])) echo $settings['parsing_deep']; ?>"/>
                     </div>
                 </li>
                 <li>
                     <label>Интервал обновления, мин</label>
                     <div class="input text">
                         <input type="text"  name="updating_interval" value="<?php if (isset($settings['updating_interval'])) echo $settings['updating_interval']; ?>"/>
                     </div>
                 </li>
                 <li>
                    <label>Ответы</label>
                    <div class="product-table phones">
                      <table>
                        <tbody>
                        <?php 
                            $answers = isset($settings['answers'])?$settings['answers']:0;
                                    if($answers) { 
                                            $i=0;
                                            foreach($answers as $answer) {
                                          ?>
                                            <tr>
                                              <td class="phone phone-big">
                                                <label>Метка для ответа</label>
                                                <div class="input text always_visible ">
                                                    <input type ="text" name="title[<?php echo $i; ?>]"  value="<?php echo $answer['title']; ?>">
                                                </div>
                                                <label><input type="checkbox" name="test_mode[<?php echo $i; ?>]" value="1" <?php echo $answer['test_mode']?'checked':''; ?>/> Тестовый режим</label>
                                                <br/>
                                                <label>Шаблон ответа</label>
                                                <div class="input textarea ">
                                                    <textarea name="template[<?php echo $i; ?>]" ><?php echo $answer['template']; ?></textarea>
                                                </div>
                                                <label>Паттерны для заголовка проекта</label>
                                                <div class="input textarea ">
                                                    <textarea name="regexp[<?php echo $i; ?>]" ><?php echo $answer['regexp']; ?></textarea>
                                                </div>
                                                <label>Паттерны для краткого описания проекта</label>
                                                <div class="input textarea ">
                                                    <textarea name="a_regexp[<?php echo $i; ?>]" ><?php echo $answer['a_regexp']; ?></textarea>
                                                </div>
                                                <label>Специализации</label>
                                                <div class="input">
                                                    <select name="spec[<?php echo $i; ?>][]" size="20" multiple="MULTIPLE" class="multi_select">
                                                      <?php foreach ($specializations as $key=>$spec) { ?>
                                                            <option value="<?php echo $key; ?>" <?php if (in_array($key, (array) $answer['spec'])) echo "selected"; ?>><?php echo $spec; ?></option>
                                                      <?php } ?>
                                                    </select>
                                                </div>  
                                              </td>
                                              <td>
                                                    <a href="#" class="delete-inline" title="Удалить"><img src="<?php echo $dir_images;?>icon.png" class="eicon del-s" alt="icon"/></a>
                                              </td>
                                            </tr>
                                          <?php
                                          $i++;
                                         }
                                    }
                                ?>
                                           <tr>
                                                <td colspan="2">
                                                    <label>Новый ответ</label>
                                                </td>
                                          </tr>
                                          <tr>
                                              <td class="phone phone-big">
                                                <div class="input text always_visible ">
                                                    <input type ="text" name="n_title" placeholder="Метка для ответа">
                                                </div>
                                                <label><input type="checkbox" name="n_test_mode" value="1"/> Тестовый режим</label>
                                                <br/>
                                                <div class="input textarea ">
                                                    <textarea name="n_template" placeholder="Шаблон ответа"></textarea>
                                                </div>
                                                <div class="input textarea ">
                                                    <textarea name="n_regexp" placeholder="паттерны для заголовка проекта"></textarea>
                                                </div>
                                                <div class="input textarea ">
                                                    <textarea name="n_a_regexp" placeholder="паттерны для краткого описания проекта"></textarea>
                                                </div>
                                                <label>Специализации</label>
                                                <div class="input">
                                                    <select name="n_spec[]" size="20" multiple="MULTIPLE" class="multi_select">
                                                      <?php foreach ($specializations as $key=>$spec) { ?>
                                                            <option value="<?php echo $key; ?>"><?php echo $spec; ?></option>
                                                      <?php } ?>
                                                    </select>
                                                </div>  
                                              </td>
                                              <td>
                                                   
                                              </td>
                                        </tr>
                        </tbody>
                      </table>
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
                     <span><img class="bicon check-w" src="<?php echo $dir_images; ?>icon.png" alt="icon"/> <i>Сохранить</i></span>
                 </button>
             </span>
            
         </div>
     </div>
 </form>