<form method="post" action="admin.php?page=pedidos-woo-paghiper-pix">
<div id="tela-pedidos-woo-paghiper-pix" class="wrap">
<h1 class="wp-heading-inline">Pedidos PagHiper Pix</h1>

<hr class="wp-header-end">
<div class="tablenav">

<div class="tablenav-pages" style="margin: 1em 0;float: left;">
<select onchange="filtar_pedidos_status(this.value)" name="tipo">
<option value="">Todos Status</option>
<?php foreach($lista_status as $k=>$v){?>
<option value="<?php echo $k;?>"<?php echo ($status==$k)?' selected':'';?>><?php echo $v;?></option>
<?php } ?>
</select>
</div>

</div>

<table class="wp-list-table widefat fixed striped posts">
<thead>
<tr>
<th scope="col" style="width:60px;">Pedido</th>
<th scope="col">Cliente</th>
<th scope="col">Transa&ccedil;&atilde;o</th>
<th scope="col" style="width:100px;">Total</th>
<th scope="col">Status</th>
<th scope="col" style="width:120px;">Data</th>
<th scope="col" style="width:120px;"></th>
</tr>
</thead>
<tbody>
<?php 
foreach($pedidos as $k => $v) {
	$data = $v->get_date_created();
	$dados_pix = get_post_meta($v->get_id(),'_dados_woo_paghiper_pix',true);;
	?>
	<tr>
	<td><a href="post.php?post=<?php echo $v->get_id(); ?>&action=edit"><?php echo $v->get_id(); ?></a></td>
	<td><?php echo $v->get_billing_first_name(); ?> <?php echo $v->get_billing_last_name(); ?></td>
	<td><?php echo isset($dados_pix['transaction_id'])?$dados_pix['transaction_id']:'n/a'; ?></td>
	<td><?php echo $v->get_total(); ?></td>
	<td><?php echo wc_get_order_status_name($v->get_status()); ?></td>
	<td><?php echo $data->date('d/m/Y H:i'); ?></td>
	<td>
	<?php if($v->get_status()=='on-hold'){?>
	<a href="<?php echo $v->get_checkout_order_received_url(); ?>" target="_blank">Ver Pix</a>
	<?php } ?>
	</td>
	</tr>
	<?php 
}
?>
<?php if($total==0){?>
<tr>
<td colspan="7">Nenhum registro encontrado a sua consulta!</td>
</tr>
<?php } ?>
</tbody>
</table>

<div class="tablenav">
<div class="tablenav-pages" style="margin: 1em 0">
<?php echo $total; ?> Registros - <?php echo ($page_links)?$page_links:'';?>
</div>
</div>

</div>
</form>

<script>
function filtar_pedidos_status(status){
	location.href=window.location.href+'&status='+status;
}
</script>