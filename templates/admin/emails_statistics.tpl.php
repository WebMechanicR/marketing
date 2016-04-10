                <div class="bt-set right">
					<span class="btn standart-size">
						<a href="<?php echo DIR_ADMIN; ?>?module=<?php echo $module;?>&action=export_statistics" class="button hide-icon" target="_blank">
							<span>Экспорт в CSV</span>
						</a>
					</span>
				</div>
<h1><img class="news-icon" src="<?php echo $dir_images;?>icon.png" alt="icon"/> Статистика</h1>
<h2> Всего писем отправлено: <?php echo $total_count; ?></h2>
<h2> Всего писем открыто: <?php echo $total_opened_count; ?></h2>
<?php if(count($statistics)>0) { ?>
<div class="product-table emails">
  <table>
    <thead>
      <tr>
        <th>Дата</th>
        <th>Рассылка</th>
        <th>Отправлено писем</th>
        <th>Открыто писем</th>
      </tr>
    </thead>
    <tbody>
      <?php foreach($statistics as $t_email) { ?>
      <tr>
        <td>
          <?php echo date('d.m.Y', $t_email['date_add']); ?>
        </td>
        <td>
          <?php echo $t_email['group_name']; ?>
        </td>
        <td>
          <?php echo $t_email['mails_sent']; ?>
        </td>
       <td>
          <?php echo $t_email['opened_mails']; ?>
        </td>
     </tr>
      <?php } ?>
    </tbody>
    <tfoot>
      <tr>
        <th>Дата</th>
        <th>Рассылка</th>
        <th>Отправлено писем</th>
        <th>Открыто писем</th>
      </tr>
    </tfoot>
  </table>
</div>
<?php $site->tpl->display('paging'); ?>
<?php }  else { ?>
<h3>По заданными критериям ничего не найдено</h3>
<?php } ?>
