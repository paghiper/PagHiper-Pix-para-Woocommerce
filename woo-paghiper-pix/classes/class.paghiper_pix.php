<?php
	class WC_Gateway_Woo_PagHiper_Pix extends WC_Payment_Gateway {
	
	public function __construct() {
		//init
		global $woocommerce;
        $this->id           = 'woo_paghiper_pix';
        $this->icon         = apply_filters( 'woocommerce_woo_paghiper_pix', plugins_url().'/woo-paghiper-pix/images/pix.png' );
        $this->has_fields   = true;
		$this->description   = true;
		$this->supports   = array('products');
        $this->method_title = 'PagHiper Pix';
		$this->init_settings();
		$this->init_form_fields();
		
		//configuracoes
		foreach ( $this->settings as $key => $val ) $this->$key = $val;
		
		//acoes salvar config
		add_action('woocommerce_update_options_payment_gateways_' . $this->id, array( &$this, 'process_admin_options' ) );
		
		//confirmacao
		add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );
		
		//detalhes no e-mail
		add_action( 'woocommerce_email_after_order_table', array( $this, 'email_instructions' ), 10, 2 );
		
		//segunda via
		add_action( 'woocommerce_view_order', array( $this, 'segunda_via_cliente' ), 20 );
		
		//se valido
		if ( !$this->is_valid_for_use() ) $this->enabled = false;
    }
	
	public function is_valid_for_use() {
        if ( ! in_array( get_woocommerce_currency(), apply_filters( 'woocommerce_woo_paghiper_pix_supported_currencies', array( 'BRL' ) ) ) ) return false;
        return true;
    }
	
	public function get_status_pagamento(){
		if(function_exists('wc_get_order_statuses')){
			return wc_get_order_statuses();
		}else{
			$taxonomies = array( 
				'shop_order_status',
			);
			$args = array(
				'orderby'       => 'name', 
				'order'         => 'ASC',
				'hide_empty'    => false, 
				'exclude'       => array(), 
				'exclude_tree'  => array(), 
				'include'       => array(),
				'number'        => '', 
				'fields'        => 'all', 
				'slug'          => '', 
				'parent'         => '',
				'hierarchical'  => true, 
				'child_of'      => 0, 
				'get'           => '', 
				'name__like'    => '',
				'pad_counts'    => false, 
				'offset'        => '', 
				'search'        => '', 
				'cache_domain'  => 'core'
			); 
			foreach(get_terms( $taxonomies, $args ) AS $status){
				$s[$status->slug] = __( $status->slug, 'woocommerce' );
			}
			return $s;
		}
	}
    
    public function thankyou_page($order_id) {
		//pedido
		$order = wc_get_order( $order_id );
		//se pedido por outro método
		if ('woo_paghiper_pix' !== $order->get_payment_method() ) {
			return;
		}
		//dados
		$data = get_post_meta($order->get_id(),'_dados_woo_paghiper_pix',true);
		//se ok
		if(isset($data['pix_code'])){
			include_once(dirname(__FILE__) . '/cupom.php'); 
		}else{
			return;
		}
	}
	
	public function segunda_via_cliente($order_id) {
		//pedido
		$order = wc_get_order( $order_id );
		//se pedido por outro método
		if ('woo_paghiper_pix' !== $order->get_payment_method() ) {
			return;
		}
		//dados
		$data = get_post_meta($order->get_id(),'_dados_woo_paghiper_pix',true);
		//adiciona segunda via ao layout do e-mail
		if($order->get_status()=='on-hold' && isset($data['pix_code'])){	
			$html = '<fieldset><div class="segunda_via_cliente">';
			$html .= '<h2>Pagamento Pix</h2>';
			$html .= '<p class="order_details">';
			$html .= 'Conclua o seu pagamento via o App de seu banco preferido.<br />';
			$html .= '' . sprintf( __( '<b>Transação PagHiper:</b><br>%s', 'woo-paghiper-pix' ), $data['transaction_id'] ) . '<br />';
			$html .= '' . sprintf( __( '<b>Ver Pix:</b><br>%s', 'woo-paghiper-pix' ), '<a target="_blank" href="'.$order->get_checkout_order_received_url().'">'.$order->get_checkout_order_received_url().'</a><br />');
			$html .= '</p>';
			$html .= '</div></fieldset>';
			echo $html;
		}else{
			return;
		}
	}
	
	public function email_instructions( $order, $sent_to_admin ) {
		//se pedido por outro método
		if ( $sent_to_admin || 'on-hold' !== $order->get_status() || 'woo_paghiper_pix' !== $order->get_payment_method() ) {
			return;
		}
		//dados
		$data = get_post_meta($order->get_id(),'_dados_woo_paghiper_pix',true);
		//adiciona segunda via ao layout do e-mail
		if($order->get_status()=='on-hold' && isset($data['pix_code'])){	
			$html = '<h2>Pagamento Pix</h2>';
			$html .= '<p class="order_details">';
			$html .= 'Conclua o seu pagamento via o App de seu banco preferido.<br />';
			$html .= '' . sprintf( __( '<b>Transação PagHiper:</b><br>%s', 'woo-paghiper-pix' ), $data['transaction_id'] ) . '<br />';
			$html .= '' . sprintf( __( '<b>Ver Pix:</b><br>%s', 'woo-paghiper-pix' ), '<a target="_blank" href="'.$order->get_checkout_order_received_url().'">'.$order->get_checkout_order_received_url().'</a><br />');
			$html .= '</p>';
			echo $html;
		}else{
			return;
		}
	}
	
	public function admin_options() {
		if ( $this->is_valid_for_use() ){
		?>
			<table class="form-table">
			<?php
				$this->generate_settings_html();
			?>
			</table>
		<?php }else{ ?>
			<div class="inline error"><p><strong><?php _e( 'Gateway Desativado', 'woo-paghiper-pix' ); ?></strong>: <?php _e( 'Pix PagHiper aceita o tipo e moeda de sua loja, apenas BRL.', 'woo-paghiper-pix' ); ?></p></div>
		<?php
		}
	}
	
    public function init_form_fields() {
		$form = array(
			'imagem' => array(
						'title' => "",
						'type' 			=> 'hidden',
						'description' => "<img src='".plugins_url()."/woo-paghiper-pix/images/banner.png'>",
						'default' => ''
					),
			'enabled' => array(
							'title' => "<b>Ativar/Desativar</b>",
							'type' => 'checkbox',
							'label' => __('Sim ou não.', 'woo-paghiper-pix'),
							'description' => __('Ativa ou n&atilde;o a forma de pagamento na loja.', 'woo-paghiper-pix'),
							'default' => 'yes'
						),
			'title' => array(
							'title' => "<b>Titulo</b>",
							'type' => 'text',
							'description' => __('Nome que este meio de pagamento sera mostrado na finalização.', 'woo-paghiper-pix'),
							'default' => 'Pagamento por Pix PagHiper'
						),
			'key' => array(
							'title' => "<b>ApiKey PagHiper</b>",
							'type' 			=> 'text',
							'description' => __('Chave de acesso a API da PagHiper, a mesma pode ser consultada acessando sua conta PagHiper e depois o menu "Minha Conta > Credênciais".', 'woo-paghiper-pix'),
							'default' => ''
						),
			'token' => array(
							'title' => "<b>Token PagHiper</b>",
							'type' 			=> 'text',
							'description' => __('Token de acesso a API da PagHiper, a mesma pode ser consultada acessando sua conta PagHiper e depois o menu "Minha Conta > Credênciais".', 'woo-paghiper-pix'),
							'default' => ''
						),
			'validade' => array(
							'title' => "<b>Validade em Dias</b>",
							'type' 			=> 'text',
							'description' => __('Prazo de validade em dias para o Pix (Ex: 3).', 'woo-paghiper-pix'),
							'default' => ''
						),
			'minimo' => array(
							'title' => "<b>Total M&iacute;nimo (0.00)</b>",
							'type' 			=> 'text',
							'description' => __('Total minimo para usar o módulo, por padrão o valor mínimo aceito para recebimento Pix junto a PagHiper é de 3.00, portanto não configure um valor menor que este.', 'woo-paghiper-pix'),
							'default' => ''
						),
			'pago' => array(
							'title' => "<b>Status Pago</b>",
							'type' 			=> 'select',
							'description' => "Status na loja correspondente ao Status de pagamento em quest&atilde;o",
							'default' => 'wc-completed',
							'options' => $this->get_status_pagamento(),
						),
			'cancelado' => array(
							'title' => "<b>Status Cancelado</b>",
							'type' 			=> 'select',
							'description' => "Status na loja correspondente ao Status de pagamento em quest&atilde;o",
							'default' => 'wc-cancelled',
							'options' => $this->get_status_pagamento(),
						),
			'devolvido' => array(
							'title' => "<b>Status Devolvido</b>",
							'type' 			=> 'select',
							'description' => "Status na loja correspondente ao Status de pagamento em quest&atilde;o",
							'default' => 'wc-refunded',
							'options' => $this->get_status_pagamento(),
						),
			'debug' => array(
							'title' => "<b>Modo Debug</b>",
							'type' => 'checkbox',
							'label' => __('Sim ou não.', 'woo-paghiper-pix'),
							'description' => __('Ativa o modo desenvolvedor (debug) na loja, caso tenha problemas de integração, em modo de produção o mantenha desativado.', 'woo-paghiper-pix'),
							'default' => 'no'
						),
			);
		$this->form_fields = $form;
    }

	public function payment_fields() {
		if(!isset($_GET['pay_for_order'])){
			$total_cart = number_format($this->get_order_total(), 2, '.', '');
		}else{
			$order_id = woocommerce_get_order_id_by_order_key($_GET['key']);
			$order = new WC_Order( $order_id );
			$total_cart = number_format($order->get_total(), 2, '.', '');
		}
        include_once(dirname(__FILE__) . '/layout.php'); 
    }

	public function validate_fields() {
		global $woocommerce;
		//total do pedido
		$minimo = (float)$this->settings['minimo'];
		if(!isset($_GET['pay_for_order'])){
			$total_cart = number_format($this->get_order_total(), 2, '.', '');
		}else{
			$order_id = woocommerce_get_order_id_by_order_key($_GET['key']);
			$order = new WC_Order( $order_id );
			$total_cart = number_format($order->get_total(), 2, '.', '');
		}
		//validar
		$erros = 0;
		$cpf_cnpj = new ValidaCPFCNPJPagHiperPix($this->get_post('fiscal'));
		$minimo
		if($this->get_post('fiscal')==''){
			$this->tratar_erro("Informe um CPF/CNPJ v&aacute;lido do pagador!");
			$erros++;
		}elseif(!$cpf_cnpj->valida()){
			$this->tratar_erro("O CPF/CNPJ n&atilde;o &eacute; v&aacute;lido, corrija o mesmo!");
			$erros++;
		}elseif($total_cart < $minimo){
			$this->tratar_erro("O valor m&iacute;nimo para pagamentos por Pix &eacute; de R$".$minimo."!");
			$erros++;
		}
		if($erros>0){
			return false;
		}
		return true;
	}
	
	public function tratar_erro($erro){
		global $woocommerce;
		if(function_exists('wc_add_notice')){
			wc_add_notice($erro,$notice_type = 'error' );
		}else{
			$woocommerce->add_error($erro);
		}
	}
	
	private function get_post( $name ) {
			if (isset($_POST['woo_paghiper_pix'][$name])) {
				return $_POST['woo_paghiper_pix'][$name];
			}
			return null;
	}
	
	private function criar_pix($order_id){
		//dados do pedido 
		$order = wc_get_order($order_id);
		$valor = $order->get_total();
		$pedido_id = $order->get_id();
		
		//fiscal 
		$fiscal = preg_replace('/\D/', '', $this->get_post('fiscal'));
		
		//define se é cliente ou empresa 
		$pagador = trim(preg_replace('!\s+!',' ',$order->get_billing_first_name().' '.$order->get_billing_last_name()));
		if(strlen($fiscal)==14){
			$empresa = $order->get_billing_company();
			if(!empty($empresa)){
				$pagador = $empresa;
			}					
		}
		
		//custom celular
		$celular = $order->get_meta( '_billing_cellphone' );
		
		//trata o telefone
		$telefone = '';
		if($order->get_billing_phone()!=""){
			$telefone = preg_replace('/\D/', '', $order->get_billing_phone());
		}elseif(!empty($celular)){
			$telefone = preg_replace('/\D/', '', $celular);
		}
		
		//dados do pix
        $json = array();
		$json['apiKey'] = trim($this->settings['key']);
		$json['order_id'] = $pedido_id;
		$json['payer_email'] = $order->get_billing_email();
		$json['payer_name'] = $pagador;
		$json['payer_cpf_cnpj'] = $fiscal;
		$json['payer_phone'] = $telefone;
		$json['partners_id'] = '';
		$json['notification_url'] = admin_url('admin-ajax.php?action=woo_paghiper_pix_webhook');
		$json['shipping_price_cents'] = number_format($order->get_shipping_total(), 2, '', '');
		$json['days_due_date'] = (int)$this->settings['validade'];
		
		//produtos 
		$produtos = array();
		if ( 0 < count( $order->get_items() ) ) {
			$k = 1;
			foreach ( $order->get_items() as $order_item ) {
				if ( $order_item['qty'] ) {
					$item_total = $order->get_item_total( $order_item, false );
					if ( 0 > $item_total ) {
						continue;
					}
					$item_name = $order_item['name'];
					$json['items'][$k]['item_id'] = $order_item['product_id'];
					$json['items'][$k]['description'] = substr($item_name,0,99);
					$json['items'][$k]['quantity'] = (int)$order_item['qty'];
					$json['items'][$k]['price_cents'] = number_format($item_total, 2, '', '');
					$k++;
				}
			}
		}
		
		//trata as fees (taxas)
		$descontos = abs($order->get_total_discount());
		foreach( $order->get_items('fee') as $item_id => $item_fee ){
			$fee_total = $item_fee->get_total();
			if($fee_total > 0){
				$json['items'][$k]['item_id'] = date('dmYis');
				$json['items'][$k]['description'] = substr($item_fee->get_name(),0,99);
				$json['items'][$k]['quantity'] = 1;
				$json['items'][$k]['price_cents'] = number_format($fee_total, 2, '', '');
				$k++;
			}else{
				$descontos += abs($item_fee->get_total());
			}
		}
		$json['discount_cents'] = number_format(abs($descontos), 2, '', '');
		
		//faz o request
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, 'https://pix.paghiper.com/invoice/create/');
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, FALSE);  
		curl_setopt($ch, CURLOPT_SSL_VERIFYHOST, FALSE);
        curl_setopt($ch, CURLOPT_HEADER, false);
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($json));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
		curl_setopt($ch, CURLOPT_HTTPHEADER, array(
			'Accept: application/json',
			'Content-Type: application/json'
		));
        $response = curl_exec($ch);
        $error = curl_error($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $retorno = @json_decode($response,true);
        if(!$retorno){
            $retorno = $response;
        }
        curl_close($ch);
        return array('status'=>$httpcode,'erro'=>$error,'enviado'=>$json,'retorno'=>$retorno);
	}

    public function process_payment($order_id) {
		//dados do pedido 
		$order = wc_get_order($order_id);
		//cria o pix
		$pix = $this->criar_pix($order->get_id());
		//debug 
		if($this->settings['debug']=='yes' || $pix['status'] <> 201){
			$logs = new WC_Logger();
			$logs->add( $this->id, '------------ Debug Pix PagHiper -------------' );
			$logs->add( $this->id, print_r($pix,true) );
		}
		//se ok 
		if($pix['status']==201 && isset($pix['retorno']['pix_create_request']['result']) && $pix['retorno']['pix_create_request']['result']=='success'){
			//dados 
			$id   = $pix['retorno']['pix_create_request']['transaction_id'];
			$link = $pix['retorno']['pix_create_request']['pix_code']['pix_url'];
			$qr   = $pix['retorno']['pix_create_request']['pix_code']['qrcode_base64'];
			$emv  = $pix['retorno']['pix_create_request']['pix_code']['emv'];
			//se ok cria o meta com os dados pix 
			update_post_meta($order_id,'_dados_woo_paghiper_pix',$pix['retorno']['pix_create_request']);
			update_post_meta( $order_id, '_finalizado_woo_paghiper_pix', 'true' );
			//cria o pedido na loja 
			$order->update_status('wc-on-hold', __( 'PagHiper Pix - Transação: '.$id.'', 'woo-paghiper-pix'));
			//limpa e redireciona a tela de confirmacao
			WC()->cart->empty_cart();
			$url = $order->get_checkout_order_received_url();
			//sucesso
			return array(
				'result'   => 'success',
				'redirect' => $url
			);
		}elseif(isset($pix['retorno']['response_message'])){
			//erro
            $erro = 'Erro no pagamento Pix do PagHiper: '.$pix['retorno']['response_message'];
			$this->tratar_erro($erro);
			return false;
        }elseif(!empty($pix['erro'])){
			//erro
            $erro = 'Erro de conectividade no pagamento Pix do PagHiper: '.$pix['erro'];
			$this->tratar_erro($erro);
			return false;
        }else{
			//erro
            $erro = 'Erro desconhecido ao processar pagamento Pix junto a PagHiper! (ver logs)';
			$this->tratar_erro($erro);
			return false;
        }
	}
}
?>