<?php 
if(is_admin()) {
	//filtro
	function handle_custom_query_var_woo_paghiper_pix( $query, $query_vars ) {
		if ( ! empty( $query_vars['_finalizado_woo_paghiper_pix'] ) ) {
			$query['meta_query'][] = array(
				'key' => '_finalizado_woo_paghiper_pix',
				'value' => esc_attr( $query_vars['_finalizado_woo_paghiper_pix'] ),
			);
		}
		return $query;
	}
	
	//lista de pedidos
	function woo_paghiper_pix_pedidos() {
		global $title, $wpdb;
		//objeto 
		$obj = new WC_Gateway_Woo_PagHiper_Pix();
		$lista_status = $obj->get_status_pagamento();

		//paginacao 
		$pagina = (int)isset($_GET['pg'])?$_GET['pg']:1;
		$status = isset($_GET['status'])?$_GET['status']:'';
		$args = array(
			'_finalizado_woo_paghiper_pix' => 'true',
			'limit' => get_option( 'posts_per_page' ),
			'orderby' => 'ID',
			'payment_method' => array($obj->id),
			'order' => 'DESC',
			'paged' => $pagina,
			'paginate' => true
		);

		//filtra por status 
		if(!empty($status)){
			$args = array_merge($args,array('status' => trim($status)));
		}

		//se visualiza pedido individual ou geral
		if(isset($_GET['pedido'])){
			$orders = new stdClass;
			$orders->max_num_pages = 1;
			$orders->total = 1;
			$orders->orders[] = new wc_get_order((int)($_GET['pedido']));
		}else{
			$orders = wc_get_orders( $args );
		}

		$page_links = paginate_links( array(
			'base' => add_query_arg( 'pg', '%#%' ),
			'format' => '',
			'prev_text' => __( '&laquo;', 'woo-paghiper-pix' ),
			'next_text' => __( '&raquo;', 'woo-paghiper-pix' ),
			'total' => $orders->max_num_pages,
			'current' => $pagina
		) );
		$total = $orders->total;
		$pedidos = $orders->orders;
		//chama o layout
		include_once(WOO_PAGHIPER_PIX.'/classes/lista_pedidos.php');
	}
	
	//ativa menu
	add_action('admin_menu', function(){
		add_menu_page( 'PagHiper Pix', 'PagHiper Pix', null, 'woo-paghiper-pix', null, 'dashicons-text-page', '60.5' );
		add_submenu_page( 'woo-paghiper-pix', 'Pedidos', 'Pedidos', 'edit_shop_orders', 'pedidos-woo-paghiper-pix', 'woo_paghiper_pix_pedidos' );
	});
	
	//filtro custom
	add_filter( 'woocommerce_order_data_store_cpt_get_orders_query',  'handle_custom_query_var_woo_paghiper_pix', 10, 2 );
}
?>