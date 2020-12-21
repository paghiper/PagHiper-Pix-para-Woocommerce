<div style="padding: 10px;
    border: 1px solid #CCC;
    border-radius: 5px;" class="tela_conclusao_pix">

<h3>Pagamento Pix</h3>

<p><b>Transa&ccedil;&atilde;o PagHiper:</b><br>
<?php echo $data['transaction_id'];?>
</p>

<p><b>QrCode Pix:</b><br>
<img style="border:1px solid #CCC; border-radius:5px;" src="data:image/png;base64,<?php echo $data['pix_code']['qrcode_base64'];?>">
</p>

<p><b>Pix Copiar/colar (clique no mesmo para copiar automaticamente):</b><br>
<code><span title="Clique para copiar automaticamente!" id="selectablepix" onclick="selectText()"><?php echo $data['pix_code']['emv'];?></span></code></p>

<p>Conclua o pagamento do pedido via App de seu banco preferido, e para pedidos por Celular use o c&oacute;digo copiar/colar exibido acima, basta apenas clicar no mesmo para copiar automaticamente, qualquer d&uacute;vida contate o atendimento de nossa loja.</p>

</div>
<input type="hidden" id="pix-copiar-colar" value="<?php echo $data['pix_code']['emv'];?>">
<script>
	function selectText() {
		copyToClipboard();
		var containerid = 'selectablepix';
		if (document.selection) { // IE
			var range = document.body.createTextRange();
			range.moveToElementText(document.getElementById(containerid));
			range.select();
		} else if (window.getSelection) {
			var range = document.createRange();
			range.selectNode(document.getElementById(containerid));
			window.getSelection().removeAllRanges();
			window.getSelection().addRange(range);
		}
	}
	function copyToClipboard() {
	  var element = jQuery('#pix-copiar-colar');
	  var $temp = jQuery("<input>");
	  jQuery("body").append($temp);
	  $temp.val(element.val()).select();
	  document.execCommand("copy");
	  $temp.remove();
	  console.log('copiado: '+element.val()+'');
	  alert('Pix copiado com sucesso!');
	}
</script>