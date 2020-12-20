<?php
/*
Plugin Name: PagHiper Pix Woocommerce
Description: Integra&ccedil;&atilde;o aos Pagamentos por Pix da PagHiper.
Version: 1.0
Author: PagHiper
Author URI: https://www.paghiper.com/
Copyright: © 2009-2020 PagHiper.
License: BSD License (3-clause)
Dev: Bruno Alencar - Loja5.com.br
*/

//define a dir do modulo
define('WOO_PAGHIPER_PIX', untrailingslashit( plugin_dir_path( __FILE__ ) ));
define('WOO_PAGHIPER_PIX_ARQUIVO', __FILE__ );

//atalhos
function plugin_action_links_woo_paghiper_pix( $links ) {
    $plugin_links = array();
	$plugin_links[] = '<a href="' . esc_url( admin_url( 'admin.php?page=wc-settings&tab=checkout&section=woo_paghiper_pix' ) ) . '">' . __( 'Configurar', 'woo-paghiper-pix' ) . '</a>';
    return array_merge( $plugin_links, $links );
}

//funcao de inicializacao do plugin
function woo_paghiper_pix_init() {
	//verifica a versao do woo 3.x ou superior
	if ( defined( 'WC_VERSION' ) && version_compare( WC_VERSION, '3.0.0', '>=' ) ) {
		
		//se existe a funcao de pagamento
		if ( !class_exists( 'WC_Payment_Gateway' ) ) return;
		
		//cria a class de pagamento
		if ( !class_exists( 'WC_Gateway_Woo_PagHiper_Pix' ) ){
			include_once(WOO_PAGHIPER_PIX.'/classes/class.paghiper_pix.php' );
			include_once(WOO_PAGHIPER_PIX.'/classes/class.fiscal.php' );
			include_once(WOO_PAGHIPER_PIX.'/classes/class.acoes_admin.php' );
		}
		
		//cria o gateway
		function woocommerce_add_woo_paghiper_pix($methods) {
			$methods[] = 'WC_Gateway_Woo_PagHiper_Pix';
			return $methods;
		}
		add_filter('woocommerce_payment_gateways', 'woocommerce_add_woo_paghiper_pix' );
		
		//menus de acesso rapido a config do plugin
		if(is_admin()) {
			add_filter('plugin_action_links_'.plugin_basename( __FILE__ ),'plugin_action_links_woo_paghiper_pix');
		}

	}else{
		//se woo antigo
		add_action( 'admin_notices',function(){
			echo '<div class="error">';
			echo '<p><strong>Pix PagHiper:</strong> Requer vers&atilde;o Woo 3.x ou superior, atualize seu Woocommerce para vers&atilde;o compativel!</p>';
			echo '</div>';
		});
	}
}

//inicializa o plugin
add_action('plugins_loaded', 'woo_paghiper_pix_init', 0);

//retorno de dados 
add_action('wp_ajax_woo_paghiper_pix_webhook', 'woo_paghiper_pix_webhook');
add_action('wp_ajax_nopriv_woo_paghiper_pix_webhook','woo_paghiper_pix_webhook');
function woo_paghiper_pix_webhook(){
	//config
	$obj = new WC_Gateway_Woo_PagHiper_Pix();
	//modo debug 
	if($obj->settings['debug']=='yes'){
		$logs = new WC_Logger();
		$logs->add( $obj->id, 'Debug IPN PagHiper Pix' );
		$logs->add( $obj->id, print_r($_REQUEST,true) );
	}
	//se retornar os dados nescessarios
    if(isset($_POST['transaction_id']) && isset($_POST['notification_id']) && isset($_POST['apiKey']) && $_POST['apiKey']==trim($obj->settings['key'])){
		//json consulta a transacao pix
		$json = array();
		$json['token'] = trim($obj->settings['token']);
		$json['apiKey'] = trim($obj->settings['key']);
		$json['transaction_id'] = trim($_POST['transaction_id']);
		$json['notification_id'] = trim($_POST['notification_id']);
		$ch = curl_init();
		curl_setopt($ch, CURLOPT_URL, 'https://pix.paghiper.com/invoice/notification/');
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
		$retorno = @json_decode($response,true);
		curl_close($ch);
		//processa o resultado 
		if(isset($retorno['status_request']['result']) && $retorno['status_request']['result']=='success'){
			$order = wc_get_order((int)$retorno['status_request']['order_id']);
			$id = $json['transaction_id'];
			if($retorno['status_request']['status']=='paid'){
				//se pago
				if(str_replace('wp-','',$order->get_status())!=str_replace('wp-','',$obj->settings['pago'])){
					$order->update_status($obj->settings['pago'], __( 'PagHiper Pix IPN - Transação: '.$id.'', 'woo-paghiper-pix'));
				}				
			}elseif($retorno['status_request']['status']=='canceled'){
				//se cancelado
				if(str_replace('wp-','',$order->get_status())!=str_replace('wp-','',$obj->settings['cancelado'])){
					$order->update_status($obj->settings['cancelado'], __( 'PagHiper Pix IPN - Transação: '.$id.'', 'woo-paghiper-pix'));
				}
			}elseif($retorno['status_request']['status']=='refunded'){
				//se devolvido
				if(str_replace('wp-','',$order->get_status())!=str_replace('wp-','',$obj->settings['devolvido'])){
					$order->update_status($obj->settings['devolvido'], __( 'PagHiper Pix IPN - Transação: '.$id.'', 'woo-paghiper-pix'));
				}
			}
		}else{
			//erro ao consultar
			$logs = new WC_Logger();
			$logs->add( $obj->id, 'Erro IPN PagHiper Pix:');
			$logs->add( $obj->id, print_r($retorno,true));
		}
	}
	die('IPN PagHiper Pix');
}
?>