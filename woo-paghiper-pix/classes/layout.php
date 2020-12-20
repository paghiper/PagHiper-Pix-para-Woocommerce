<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
//se pf ou pj
$fiscal = '';
$customer_id = get_current_user_id();
if(get_user_meta( $customer_id, 'billing_cnpj', true )){
	$fiscal = get_user_meta( $customer_id, 'billing_cnpj', true );
}elseif(get_user_meta( $customer_id, 'billing_cpf', true )){
	$fiscal = get_user_meta( $customer_id, 'billing_cpf', true );
}
?>
<script style="display:none;">
<!--
//so numeros
function isNumberKey(evt) {
	var charCode = (evt.which) ? evt.which : event.keyCode
	if (charCode > 31 && (charCode < 48 || charCode > 57)){
		return false;
	}
	return true;
}
//auto copia o cpf/cnpj do cadastro se existir
setTimeout(function(){
	jQuery("#billing_cpf,#billing_cnpj" ).on({
		keyup: function() {
			var fiscal = jQuery( this ).val();
			console.log(fiscal);
			jQuery('#fiscal-woo-paghiper-pix').val(fiscal.replace(/\D/g, ''));
		},
	});
}, 500);
//-->
</script>
   
<div id="tela-woo-paghiper-pix" style="width:100%;">

<p style="margin-bottom: 5px;">Informe ou confirme o CPF ou CNPJ para finalizar seu pedido, ap&oacute;s finalizado voc&ecirc; pode pagar o Pix no App de seu banco preferido.</p>

<fieldset class="wc-payment-form">

<p class="form-row form-row-wide woocommerce-validated">
<label style="padding: 5px 0 5px 5px;">CPF/CNPJ:</label>
<input style="box-shadow: inset 2px 0 0 #0f834d;height:40px;" onselectstart="return false" onCopy="return false" onCut="return false" onDrag="return false" onDrop="return false" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false" maxlength="14" onkeypress="return isNumberKey(event)" type="text" class="input-text" placeholder="CPF ou CNPJ" id="fiscal-woo-paghiper-pix" name="woo_paghiper_pix[fiscal]" value="<?php echo preg_replace('/\D/','',$fiscal);?>">
</p>

</fieldset>

</div>	