<?php
/**
 * OrendaPay for WooCommerce.
 *
 * @package   WC_orendapay_class
 * @author    Vítor Hugo Silva Gomes <vitorhugo@vitorhug.com>
 * @license   GPL-3.0+
 * @copyright 2024 OrendaPay
 *
 * @wordpress-plugin
 * Plugin Name:       OrendaPay
 * Plugin URI:        https://www.orendapay.com.br
 * Description:       Plugin de Pagamento OrendaPay para Woocommerce
 * Version:           4.3.1
 * Author:            OrendaPay Soluções Financeiras
 * Author URI:        https://www.orendapay.com.br
 * License:           GPL-3.0+
 * License URI:       http://www.gnu.org/licenses/gpl-3.0.txt
 * Text Domain:       woo-orendapay
 **/

add_filter( 'woocommerce_payment_gateways', 'orendapay_class' );

function orendapay_class( $gateways ) 
{
	$gateways[] = 'WC_orendapay_class'; 
	return $gateways;
}


/*
 * The class itself
 */
add_action( 'plugins_loaded', 'orendapay_class_init' );


/**
 * OrendaPay payment gateway class.
 *
 * @package WC_orendapay_class
 * @author    Vítor Hugo Silva Gomes <vitorhugo@vitorhug.com>
 * @license   GPL-2.0+
 * @copyright 2024 OrendaPay
 */
function orendapay_class_init() 
{
 
	class WC_orendapay_class extends WC_Payment_Gateway 
	{
 
		
		/**
		 * Constructor for the OrendaPay gateway.
		 */
 		public function __construct() 
		{
 
			$this->id = 'orendapay'; // payment gateway plugin ID
			$this->icon = 'https://www.orendapay.com.br/layout_novo/images/logo-orendapay.png'; // icon
			$this->has_fields = false; 
			$this->method_title = __( 'OrendaPay - Boleto Bancário, Pix, Crédito e Débito', 'orendapay' );
			$this->method_description = __( 'Comece a receber dinheiro via pix, boleto bancário ou cartão usando a OrendaPay Soluções em Pagamento', 'orendapay' );
			
			// Endpoint API.
			$this->api_url = 'https://www.orendapay.com.br/api/v1/cobranca';	
			
			// Load the settings.
			$this->init_form_fields();
			$this->init_settings();
			
			$this->title = $this->get_option( 'title' );
			$this->description = $this->get_option( 'description' );
			$this->enabled = $this->get_option( 'enabled' ); 
			$this->merchant_id = $this->get_option( 'merchant_id' );
			$this->auth_token = $this->get_option( 'auth_token' );
			
			$this->enabled_Boleto = $this->get_option( 'enabled_Boleto' );
			
			$this->enabled_Pix = $this->get_option( 'enabled_Pix' );
			
			$this->days_to_pay = $this->get_option('days_to_pay', 3 );
			$this->installments = $this->get_option('installments', 1 );
							
			
			$this->enabled_Card = $this->get_option( 'enabled_Card' );
			$this->enabled_Debit = $this->get_option( 'enabled_Debit' );
			$this->installment_Card = $this->get_option( 'installment_Card' );
			$this->statusPedido = $this->get_option( 'statusPedido' );
			
			
			$this->split_tipo = $this->get_option( 'split_tipo' );
			 
			$this->split_email1 = $this->get_option( 'split_email1' );
			$this->split_perc1 = $this->get_option( 'split_perc1' );
			
			$this->split_email2 = $this->get_option( 'split_email2' );
			$this->split_perc2 = $this->get_option( 'split_perc2' );
			
			$this->split_email3 = $this->get_option( 'split_email3' );
			$this->split_perc3 = $this->get_option( 'split_perc3' );
		 

			// hook saves the settings
			add_action( 'woocommerce_api_orendapay_webhook', array( $this, 'check_webhook_notification' ) );
			add_action( 'woocommerce_orendapay_webhook_notification', array( $this, 'successful_webhook_notification' ) );
			add_action( 'woocommerce_update_options_payment_gateways_' . $this->id, array( $this, 'process_admin_options' ) );
			
			add_action( 'wp_enqueue_scripts', array( $this, 'payment_scripts' ) );
			
			add_action( 'woocommerce_thankyou_' . $this->id, array( $this, 'thankyou_page' ) );
			add_action( 'woocommerce_email_after_order_table', array( $this, 'email_instructions' ), 10, 2 );
			add_action( 'woocommerce_order_details_after_order_table', array( $this, 'view_order_custom_payment_instruction' ), 10, 2 );
			
			// Display admin notices and dependencies.
			$this->admin_notices();		 
 
 		} 
 
		/**
		 * Initialize Gateway Settings Form Fields
		 */
 		public function init_form_fields()
		{
 
			$this->form_fields = array(
				'enabled' => array(
					'title'       => __('Habilitar plugin no Checkout', 'orendapay' ),
					'label'       => __('Ativar OrendaPay em seu Checkout', 'orendapay' ),
					'type'        => 'checkbox',
					'description' => '',
					'default'     => 'no'
				),
				'title' => array(
					'title'       => __('Título', 'orendapay' ),
					'type'        => 'text',
					'description' => __('Forma de Pagamento que aparecerá na tela de checkout. Padrão: Pagar com OrendaPay', 'orendapay' ),
					'default'     => __('Pagar com OrendaPay', 'orendapay' ),
					'desc_tip'    => false,
				),
				'description' => array(
					'title'       => __('Descrição', 'orendapay' ),
					'type'        => 'textarea',
					'description' => __('Descrição da Forma de Pagamento que aparecerá na tela de checkout. Padrão: Pague sua compra com a segurança OrendaPay', 'orendapay' ),
					'default'     => __('Pague sua compra com a segurança OrendaPay', 'orendapay' ),
				),
				'enabled_Boleto' => array(
					'title'       => __('Habilitar Boleto Bancário', 'orendapay' ),
					'label'       => __('Ativar OrendaPay Boleto', 'orendapay' ),
					'type'        => 'checkbox',
					'description' => '',
					'default'     => 'no'
				),		
				'enabled_Pix' => array(
					'title'       => __('Habilitar Pix', 'orendapay' ),
					'label'       => __('Ativar Pix', 'orendapay' ),
					'type'        => 'checkbox',
					'description' => '',
					'default'     => 'no'
				),					
				'days_to_pay' => array(
					'title'       => __('Dias para vencer', 'orendapay' ),
					'type'        => 'text',
					'description' => __('Informe a quantidade de dias para uma cobrança vencer', 'orendapay' ),
					'placeholder' => __('Dias para vencer a cobrança', 'orendapay'),
					'default'     => '3'
				),
				'installments' => array(
					'title'       => __('Parcelamento Máximo Boleto (Opcional)', 'orendapay' ),
					'type'        => 'text',
					'description' => __('Se desejar parcelar as suas cobranças por boleto bancário, informe um valor máximo de parcelas', 'orendapay' ),
					'placeholder' => __('Valor opcional, se utilize informar um valor maior que 1', 'orendapay' ),
					'default'     => '0'
				),
				'enabled_Debit' => array(
					'title'       => __('Habilitar Cartão de Débito', 'orendapay' ),
					'label'       => __('Ativar OrendaPay Cartão de Débito', 'orendapay' ),
					'type'        => 'checkbox',
					'description' => '',
					'default'     => 'no'
				),				
				'enabled_Card' => array(
					'title'       => __('Habilitar Cartão de Crédito', 'orendapay' ),
					'label'       => __('Ativar OrendaPay Cartão de Crédito', 'orendapay' ),
					'type'        => 'checkbox',
					'description' => '',
					'default'     => 'no'
				),
				'installment_Card' => array(
					'title'       => __('Parcelamento Máximo Cartão', 'orendapay' ),
					'type'        => 'number',
					'default'        => 1,
					'min'        => 1,
					'max'        => 12,
					'description' => __('Informe o parcelamento máximo permitido no checkout para cartão de crédito.', 'orendapay' ),
					'placeholder' => __('Parcela(s)', 'orendapay' ) 
				),
				
				
				
				'split_tipo' => array(
					'title'       => __('Se for utilizar Split, a divisão das taxas será entre as contas?', 'orendapay' ),
					'label'       => __('Ative para que a taxa seja dividida entre as contas do Split, e desative para que a sua conta originadora absorva as taxas.', 'orendapay' ),
					'type'        => 'checkbox',
					'description' => '',
					'default'     => 'no'
				),				
				
				'split_email1' => array(
					'title'       => __('Conta Split #1 (Opcional)', 'orendapay' ),
					'type'        => 'email',
					'description' => __('Se desejar utilizar Split, informe um e-mail de destino do valor', 'orendapay' ),
					'placeholder' => __('E-mail da conta recebedora do Split #1', 'orendapay' ),
					'default'     => ''
				),
				'split_perc1' => array(
					'title'       => __('Percentual Split #1 (Opcional)', 'orendapay' ),
					'type'        => 'number',
					'description' => __('Informe o percentual do Split #1, o valor deve ser entre 2 e 99', 'orendapay' ),
					'placeholder' => __('Informe o percentual do Split #1', 'orendapay' ),
					'default'     => '',
					'min'        => 1,
					'max'        => 99				
				),				
				

				'split_email2' => array(
					'title'       => __('Conta Split #2 (Opcional)', 'orendapay' ),
					'type'        => 'email',
					'description' => __('Se desejar utilizar Split, informe um e-mail de destino do valor', 'orendapay' ),
					'placeholder' => __('E-mail da conta recebedora do Split #2', 'orendapay' ),
					'default'     => ''
				),
				'split_perc2' => array(
					'title'       => __('Percentual Split #2 (Opcional)', 'orendapay' ),
					'type'        => 'number',
					'description' => __('Informe o percentual do Split #2, o valor deve ser entre 2 e 99', 'orendapay' ),
					'placeholder' => __('Informe o percentual do Split #2', 'orendapay' ),
					'default'     => '',
					'min'        => 1,
					'max'        => 99				
				),					
				
				 

				'split_email3' => array(
					'title'       => __('Conta Split #3 (Opcional)', 'orendapay' ),
					'type'        => 'email',
					'description' => __('Se desejar utilizar Split, informe um e-mail de destino do valor', 'orendapay' ),
					'placeholder' => __('E-mail da conta recebedora do Split #3', 'orendapay' ),
					'default'     => '0'
				),
				'split_perc3' => array(
					'title'       => __('Percentual Split #3 (Opcional)', 'orendapay' ),
					'type'        => 'number',
					'description' => __('Informe o percentual do Split #3, o valor deve ser entre 2 e 99', 'orendapay' ),
					'placeholder' => __('Informe o percentual do Split #3', 'orendapay' ),
					'default'     => '',
					'min'        => 1,
					'max'        => 99				
				),					
				
				
				'statusPedido' => array(
					'title'       => __('Situação do Pagamento Confirmado', 'orendapay' ),
					'type'        => 'select',
					'label' => 'Situação',
						'options' => array(
							'PROCESSANDO' => 'Processando',
							'CONCLUIDO' => 'Concluído'
					),
					'description' => __('Informe a situação em que o pedido deve ter o pagamento confirmado automaticamente.', 'orendapay' )
				),			
				'merchant_id' => array(
					'title'       => __('ID da Integração', 'orendapay' ),
					'type'        => 'text',
					'description' => __('Obtenha essa informação do seu Painel OrendaPay no menu Integrações', 'orendapay' ),
					'placeholder' => __('Informe o ID da integração OrendaPay', 'orendapay' )
				),
				'auth_token' => array(
					'title'       => __('Token de Integração', 'orendapay' ),
					'type'        => 'text',
					'description' => __('Obtenha essa informação do seu Painel OrendaPay no menu Integrações', 'orendapay' ),
					'placeholder' => __('Informe o Token de Integração OrendaPay', 'orendapay' )
				));
 	 	}
 
 
		/*
 		 * Fields validation Checkout
		 */
		public function validate_fields() 
		{
 
			if( empty( $_POST[ 'billing_first_name' ]) ) 
			{
				wc_add_notice(  'First name is required!', 'error' );
				return false;
			}
			
			if( empty( $_POST[ 'billing_cpf' ]) && empty( $_POST[ 'billing_cnpj' ]) ) 
			{
				wc_add_notice(  'billing_cpf or billing_cnpj is required!', 'error' );
				return false;
			}

			if( empty( $_POST[ 'billing_address_1' ]) ) 
			{
				wc_add_notice(  'Address is required!', 'error' );
				return false;
			}
			
			if( empty( $_POST[ 'billing_email' ]) ) 
			{
				wc_add_notice(  'Email is required!', 'error' );
				return false;
			}			

			if( empty( $_POST[ 'billing_city' ]) ) 
			{
				wc_add_notice(  'City is required!', 'error' );
				return false;
			}			

			if( empty( $_POST[ 'billing_postcode' ]) ) 
			{
				wc_add_notice(  'Postal code is required!', 'error' );
				return false;
			}

			return true;
 
		}
		
		
		
		
		public function payment_scripts() 
		{
			// and this is our custom JS in your plugin directory that works with token.js
			wp_register_script( 'orendapay', plugins_url( 'orendapay_jquery.js', __FILE__ ), array('jquery') );
			wp_register_script( 'orendapay', plugins_url( 'orenda_mask.js', __FILE__ ), array('jquery') );
			wp_enqueue_script( 'orendapay' );
		}		
		
		
		
		
		public function payment_fields() 
		{
			
			
			//Boleto bancário ATIVADO
			if($this->enabled_Boleto!='no' && $this->enabled_Card=='no')
			{
				echo 'Boleto Bancário OrendaPay';
			}			
			
			//Boleto bancário ATIVADO
			if($this->enabled_Boleto=='no' && $this->enabled_Card!='no')
			{
				echo 'Cartão de Crédito OrendaPay';
			}

			//Boleto bancário ATIVADO
			if($this->enabled_Debit=='no' && $this->enabled_Debit!='no')
			{
				echo 'Cartão de Débito OrendaPay';
			}				
			
			//Cartão Ativado
			if($this->enabled_Card!='no' || $this->enabled_Debit!='no')
			{
				
				echo '<fieldset id="wc-' . esc_attr( 'orendapay' ) . '-cc-form" class="wc-credit-card-form wc-payment-form" style="background:transparent;">';				

				//BOLETO E CARTAO DE CRÉDITO
				if($this->enabled_Boleto!='no' && $this->enabled_Card!='no' && $this->enabled_Debit=='no')
				{
					echo '<label><input required onClick="alterarCard(this.value);" type="radio" name="pagamentoOrenda" id="pagamentoOrenda" value="boleto"> Boleto Bancário</label> <BR>
				    <label><input onClick="alterarCard(this.value);" type="radio" name="pagamentoOrenda" id="pagamentoOrenda" value="cartao"> Cartão de Crédito</label> <BR>';
				 
					$displayCard = ' style="display:none;" ';
				}
				//BOLETO E CARTAO DE DÉBITO
				if($this->enabled_Boleto!='no' && $this->enabled_Card=='no' && $this->enabled_Debit!='no')
				{
					echo '<label><input required onClick="alterarCard(this.value);" type="radio" name="pagamentoOrenda" id="pagamentoOrenda" value="boleto"> Boleto Bancário</label> <BR>
				    <label><input onClick="alterarCard(this.value);" type="radio" name="pagamentoOrenda" id="pagamentoOrenda" value="debit"> Cartão de Crédito</label> <BR>';
				
					$displayCard = ' style="display:none;" ';
				} 
				//CARTÃO DE CRÉDITO E DEBITO
				if($this->enabled_Boleto=='no' && $this->enabled_Card!='no' && $this->enabled_Debit!='no')
				{
					echo '<label><input required onClick="alterarCard(this.value);" type="radio" name="pagamentoOrenda" id="pagamentoOrenda" value="debit"> Cartão de Débito</label> <BR>
				    <label><input onClick="alterarCard(this.value);" type="radio" name="pagamentoOrenda" id="pagamentoOrenda" value="cartao"> Cartão de Crédito</label> <BR>';
				
					$displayCard = ' style="display:none;" ';
				}			
				//TODAS FORMAS
				if($this->enabled_Boleto!='no' && $this->enabled_Card!='no' && $this->enabled_Debit!='no')
				{
					echo '<label><input required onClick="alterarCard(this.value);" type="radio" name="pagamentoOrenda" id="pagamentoOrenda" value="boleto"> Boleto Bancário</label><BR>
					<label><input onClick="alterarCard(this.value);" type="radio" name="pagamentoOrenda" id="pagamentoOrenda" value="debit"> Cartão de Débito</label> <BR>
				    <label><input onClick="alterarCard(this.value);" type="radio" name="pagamentoOrenda" id="pagamentoOrenda" value="cartao"> Cartão de Crédito</label> <BR>';
				
					$displayCard = ' style="display:none;" ';
				}					
				//SÓ CARTÃO DE CRÉDITO
				if($this->enabled_Boleto=='no' && $this->enabled_Card!='no' && $this->enabled_Debit=='no')
				{
					//Só cartão ativo.
					echo '<input type="hidden" name="pagamentoOrenda" id="pagamentoOrenda" value="cartao">';
				}
				//SÓ CARTÃO DE DÉBITO
				if($this->enabled_Boleto=='no' && $this->enabled_Card=='no' && $this->enabled_Debit!='no')
				{
					//Só cartão ativo.
					echo '<input type="hidden" name="pagamentoOrenda" id="pagamentoOrenda" value="debit">';
					echo "<script>$('#blocoParcelas').hide();</script>";
				}				
				
				if($this->enabled_Pix!='no')
				{
					echo '<label><input required onClick="alterarCard(this.value);" type="radio" name="pagamentoOrenda" id="pagamentoOrenda" value="pix"> Pix</label> <BR>';						
				}				
				
				echo "<script>	
				function alterarCard(valor)
				{
					if (valor == 'boleto') 
					{
						$('#orendapay_cartao').hide();
						$('#orendapay_boleto').show();
					}
					else if (valor == 'pix') 
					{
						$('#orendapay_cartao').hide();
						$('#orendapay_boleto').show();
					}					
					else if (valor == 'cartao') 
					{
						$('#orendapay_boleto').hide();
						$('#orendapay_cartao').show();
						$('#blocoParcelas').show();
						$('#orendapay_validade').mask('99/99');
						$('#orendapay_codigo').mask('999');
					}
					else if (valor == 'debit') 
					{
						$('#orendapay_boleto').hide();
						$('#orendapay_cartao').show();
						$('#blocoParcelas').hide();						
						$('#orendapay_validade').mask('99/99');
						$('#orendapay_codigo').mask('999');
					}					
				}
				</script>";

				do_action( 'woocommerce_credit_card_form_start', 'orendapay' );
 
				//boleto parcelado
				if($this->installments>1)
				{
					$displ = ' style="display:none;" ';

					
					echo '<div id="orendapay_boleto" '.$displ.'>
							<div class="form-row form-row-wide">
							<label>Parcelas <span class="required">*</span></label>
							<select name="orendapay_parcelas_boleto" id="orendapay_parcelas_boleto">';
						
							for($i=1;$i<=$this->installments;$i++)
							{
								$Tot = $this->woocommerce_instance()->cart->total / $i;
								$Tot = number_format($Tot,2,'.','');
								$txtpar="{$i}X de R$ ".$Tot;
								
								echo "<option value='$i'>$txtpar</option>";
							}
						echo '</select>
						</div></div>
					<div class="clear"></div>';
				}
				//boleto parcelado
				
				
				//CARTAO
				echo '<div id="orendapay_cartao" '.$displayCard.'>
					<div class="form-row form-row-wide">
						<label>Nome Impresso no Cartão<span class="required">*</span></label>
						<input id="orendapay_nome" name="orendapay_nome" type="text" autocomplete="off">
					</div>		
					<div class="form-row form-row-wide">					
						<label>Número do Cartão<span class="required">*</span></label>
						<input id="orendapay_numero" name="orendapay_numero" type="text" autocomplete="off">
					</div>
					<div class="form-row form-row-first">
						<label>Validade <span class="required">*</span></label>
						<input id="orendapay_validade" name="orendapay_validade" type="text" autocomplete="off" placeholder="MM/YY">
					</div> 
					<div class="form-row form-row-last">
						<label>Código Segurança <span class="required">*</span></label>
						<input id="orendapay_codigo" name="orendapay_codigo" type="text" autocomplete="off" placeholder="CVC">
					</div>
					<div class="form-row form-row-wide" id="blocoParcelas">
						<label>Parcelas <span class="required">*</span></label>
						<select name="orendapay_parcelas" id="orendapay_parcelas">';
					
					if($this->installment_Card>12){$this->installment_Card=12;}
					if($this->installment_Card<0){$this->installment_Card=1;}
					
					for($i=1;$i<=$this->installment_Card;$i++)
					{
						$Tot = $this->woocommerce_instance()->cart->total / $i;
						$Tot = number_format($Tot,2,'.','');
						$txtpar="{$i}X de R$ ".$Tot;
						if($i==1){$txtpar="À vista (R$ ".$this->woocommerce_instance()->cart->total.")";}
						
						echo "<option value='$i'>$txtpar</option>";
					}
					 
					 
					echo '</select>
					</div></div>
					<div class="clear"></div>';
			 
				do_action( 'woocommerce_credit_card_form_end', 'orendapay' );
			 
				echo '<div class="clear"></div></fieldset>';
				
				echo "<script>
				$('#place_order').click(function() 
				{
					var escolha = $('input[name=\"payment_method\"]:checked').val();

					if(escolha=='orendapay')
					{
						var chce = $('#pagamentoOrenda:checked').val();

						if(chce!='pix' && chce!='boleto' && chce!='cartao' && chce!='debit')
						{
							alert('Selecione um método de pagamento!');
							return false;
						}
					}
				});
				</script>";
			
			}
			
			
			
			
			
			
			
			
			
			
			
			
			
			
			
		 
		}		
		
		
		
		
		
		/**
		 * Create the payment data.
		 * @since  1.0.0
		 * @param  WC_Order $order Order data.
		 * @return array           Payment data.
		 */
		protected function payment_data( $order , $dados=null) 
		{
		
			$product_list = '';
			$order_item = $order->get_items();
			foreach( $order_item as $product ) 
			{
				$prodct_name[] = $product['name'];
			}
			
			$product_list = implode( ',\n', $prodct_name );
			
			$args = array(
				// Customer data.
				'customer_person_name'   => $order->billing_first_name . ' ' . $order->billing_last_name,
				// Order data.
				'amount'                 => number_format( $order->order_total, 2, ',', '' ),
				// Document data.
				'description'            => $product_list,
				'customer_email'         => $order->billing_email,
				'meta'                   => 'order-' . $order->id,
				'expire_at'              => date( 'd/m/Y', time() + ( 3 * 86400 ) ),
				'bank_billet_account_id' => $this->contract_id
			);
		
			// WooCommerce Extra Checkout Fields for Brazil person type fields.
			if ( isset( $order->billing_persontype ) && ! empty( $order->billing_persontype ) ) 
			{
				if ( 2 == $order->billing_persontype ) 
				{
					$args['customer_cnpj_cpf'] = $order->billing_cnpj;
					$cliente_cpf_cnpj = $order->billing_cnpj;
				}
				else 
				{
					$args['customer_cnpj_cpf'] = $order->billing_cpf;
					$cliente_cpf_cnpj = $order->billing_cpf;
				}
			}
			
			// Address.
			if ( isset( $order->billing_postcode ) && ! empty( $order->billing_postcode ) ) 
			{
				$args['customer_address'] = $order->billing_address_1;
				$args['customer_city_name']    = $order->billing_city;
				$args['customer_state']   = $order->billing_state;
				$args['customer_zipcode'] = $order->billing_postcode;
			
				// WooCommerce Extra Checkout Fields for Brazil neighborhood field.
				if ( isset( $order->billing_neighborhood ) && ! empty( $order->billing_neighborhood ) ) 
				{
					$args['customer_neighborhood'] = $order->billing_neighborhood;
				}
				
				// WooCommerce Extra Checkout Fields for Brazil number field.
				if ( isset( $order->billing_number ) && ! empty( $order->billing_number ) ) 
				{
					$args['customer_address_number'] = $order->billing_number;
				}
				
				// Address complement.
				if ( ! empty( $order->billing_address_2 ) ) {
					$args['customer_address_complement'] = $order->billing_address_2;
				}
			}
			
			// Phone
			if ( isset( $order->billing_phone ) && ! empty( $order->billing_phone ) ) 
			{
				$args['customer_phone_number'] = preg_replace("/\D/", "", $order->billing_phone);
			}
			
			// Sets a filter for custom arguments.
			$args = apply_filters( 'woocommerce_boletosimples_billet_data', $args, $order );
			$args = array('bank_billet' => $args );

			if($this->days_to_pay<=0){$this->days_to_pay=2;}

			//Create json POST API OrendaPay
			$vencimento = date( 'd/m/Y', time() + ( $this->days_to_pay * 86400 ) );
			$valor = number_format( $order->order_total, 2, '.', '' );
			$cliente_nome = $order->billing_first_name . ' ' . $order->billing_last_name;
			$url_call_back = get_bloginfo('url')."/wc-api/orendapay_webhook";
			 
			
			$tipo='boleto';
			$NUMERO_PARCELAS = '1';
			$INFO='';
			$ENVIAR_EMAIL='0';
			if($dados['pagamentoOrenda']=='cartao')
			{
				$tipo='credit';
				 
				$cartao_numero = $dados['orendapay_numero'];
				$cartao_nome = $dados['orendapay_nome'];
				$cartao_validade = $dados['orendapay_validade'];
				$cartao_codigo = $dados['orendapay_codigo'];
				$NUMERO_PARCELAS = $dados['orendapay_parcelas'];
				
				$order->add_order_note( __( "OrendaPay: Pagamento por cartão ($NUMERO_PARCELAS X)", 'orendapay' ) );
			}
			else if($dados['pagamentoOrenda']=='debit')
			{
				$tipo='debit';
				 
				$cartao_numero = $dados['orendapay_numero'];
				$cartao_nome = $dados['orendapay_nome'];
				$cartao_validade = $dados['orendapay_validade'];
				$cartao_codigo = $dados['orendapay_codigo'];
				$NUMERO_PARCELAS = 1;
				
				$order->add_order_note( __( "OrendaPay: Pagamento por cartão ($NUMERO_PARCELAS X)", 'orendapay' ) );
			}
			else
			{
				
				if($dados['pagamentoOrenda']=='pix')
				{
					$tipo='pix';
				}				
				
				//NUMERO DE PARCELAS DO BOLETO.
				$NUMERO_PARCELAS = $dados['orendapay_parcelas_boleto'];
				if($this->installments>1 && $NUMERO_PARCELAS>1)
				{
					$valor = $valor / $NUMERO_PARCELAS;
					$valor = number_format($valor, 2, '.', '' );
					$INFO=" ($NUMERO_PARCELAS X)";
					$ENVIAR_EMAIL=1;
					$order->add_order_note( __( 'OrendaPay: Pagamento por boleto parcelado de '.$NUMERO_PARCELAS.' X.', 'orendapay' ) );					


				}				
				else
				{
					$order->add_order_note( __( 'OrendaPay: Pagamento por boleto.', 'orendapay' ) );					
				}
				
			}
			  
			   
			$json = array
			(
			"seu_codigo"=>"$order->id",
			"descricao"=>"Pedido $order->id",
			"vencimento"=>"$vencimento",
			"valor"=>"$valor",
			"juros"=>"0.00",
			"multa"=>"0.00",
			"desconto_pontualidade"=>"0.00",
			"cliente_nome"=>"$cliente_nome",
			"cliente_cpf_cnpj"=>"$cliente_cpf_cnpj",
			"cliente_telefone"=>"$order->billing_phone",
			"cliente_email"=>"$order->billing_email",
			"cliente_endereco"=>"$order->billing_address_1",
			"cliente_cidade"=>"$order->billing_city",
			"cliente_uf"=>"$order->billing_state",
			"cliente_cep"=>"$order->billing_postcode",
			"cliente_grupo"=>"E-commerce",
			"cartao_numero"=>"$cartao_numero",
			"cartao_nome"=>"$cartao_nome",
			"cartao_validade"=>"$cartao_validade",
			"cartao_codigo"=>"$cartao_codigo",
			"NUMERO_PARCELAS"=>"$NUMERO_PARCELAS",
			"RECORRENCIA"=>"0",
			"ENVIAR_EMAIL"=>"$ENVIAR_EMAIL",		
			"ENVIAR_SMS"=>"0",		
			"ENVIO_IMEDIATO"=>"1",
			"TIPO"=>"$tipo",
			"UTILIZACAO"=>'ECOMMERCE',
			"URL_CALLBACK"=>"$url_call_back");	
			
			if(strlen($this->split_email1)>6 && $this->split_perc1>0)
			{
				$json["SPLIT"][0] = array('percentual'=>$this->split_perc1,'email'=>$this->split_email1);
			}
			
			if(strlen($this->split_email2)>6 && $this->split_perc2>0)
			{
				$json["SPLIT"][1] = array('percentual'=>$this->split_perc2,'email'=>$this->split_email2);
			}		

			if(strlen($this->split_email3)>6 && $this->split_perc3>0)
			{
				$json["SPLIT"][2] = array('percentual'=>$this->split_perc3,'email'=>$this->split_email3);
			}			
			
			if($this->split_tipo!='no')
			{
				$json["SPLIT_TIPO"] = "D";
			}
			else
			{
				$json["SPLIT_TIPO"] = "";
			}
			
			 
			return $json;
		}		     
		
		 
		
		/**
		 * Generate the billet on OrendaPay
		 * @since  1.0.0
		 * @param  WC_Order $order Order data.
		 * @return bool           Fail or success.
		 */
		protected function generate_billet( $order , $dados=null) 
		{
			
			$json = $this->payment_data( $order, $dados );
			
			$params = array( 
				'method'     => 'POST',
				'charset'    => 'UTF-8',
				'body'       => json_encode($json),
				'sslverify'  => false,
				'timeout'    => 60,
				'headers'    => array(
				    'x-ID' => $this->merchant_id,
				    'x-Token' => $this->auth_token,
					'Content-Type' => 'application/json'
				)
			);
			
			$response = wp_remote_post( $this->api_url , $params );
			
			//Json to Object
			$data = json_decode($response['body']);
			
			if($dados['orendapay_parcelas_boleto']>1)
			{
				//pegando ultima posicao - primeiro boleto.
				$ccc = $dados['orendapay_parcelas_boleto']-1;
				$dataPedido = $data->cobrancas[$ccc];
			}
			else
			{
				$dataPedido = $data->cobrancas[0];
			}

			//Ok
			if ($response['response']['code'] == 201) 
			{
				// Save billet data in order meta.
				add_post_meta( $order->id, 'orendapay_cod_cobranca', $dataPedido->codigo );
				add_post_meta( $order->id, 'orendapay_cod_transacao', $dataPedido->boleto_codigo );
				add_post_meta( $order->id, 'orendapay_url_boleto', $dataPedido->url );
				add_post_meta( $order->id, 'orendapay_linha_digitavel', $dataPedido->linha_digitavel );
				
				if(strlen($dataPedido->pix_qrcode)>5)
				{
					add_post_meta( $order->id, 'orendapay_pix_qrcode', $dataPedido->pix_qrcode );
					add_post_meta( $order->id, 'orendapay_pix_chave', $dataPedido->pix_chave );
				}
				
				if($dados['orendapay_parcelas_boleto']>1)
				{
					add_post_meta( $order->id, 'parcelas_boleto', $dados['orendapay_parcelas_boleto'] );
					
					if($dados['pagamentoOrenda']=='boleto')
					{
						$parcela = $dados['orendapay_parcelas_boleto'];
						$valor_parcela = $order->order_total / $parcela;
						$valor_parcela = number_format($valor_parcela, 2, '.', '' );
						$parcelamento = "PARCELAMENTO: $parcela X PARCELAS DE R$ $valor_parcela";
						add_post_meta( $order->id, 'orendapay_parcelamento', $parcelamento );
					}	
				}				
				
				if($dados['pagamentoOrenda']=='cartao' || $dados['pagamentoOrenda']=='debit')				
				{
					add_post_meta( $order->id, 'orendapay_cartao', 'sim' );
					add_post_meta( $order->id, 'orendapay_situacao', $dataPedido->situacao );
					add_post_meta( $order->id, 'orendapay_cod_transacao', $dataPedido->boleto_codigo );
					add_post_meta( $order->id, 'orendapay_cod_cobranca', $dataPedido->codigo );
					
				}
				
				return true;
			}
			else
			{
				return false;
			}
		}		
		
		

		/**
		 * Backwards compatibility with version prior to 2.1.
		 * @since  1.0.0
		 * @return object Returns the main instance of WooCommerce class.
		 */
		protected function woocommerce_instance() 
		{
			if ( function_exists( 'WC' ) ) 
			{
				return WC();
			}
			else 
			{
				global $woocommerce;
				return $woocommerce;
			}
		}		
		
		
 
		/**
		 * Process the payment and return the result.
		 * @since  1.0.0
		 * @param  int    $order_id Order ID.
		 * @return array            Redirect when has success and display error notices when fail.
		 */
		public function process_payment( $order_id ) 
		{ 
			$order = new WC_Order( $order_id );
			
			// Send order for API
			$transaction = $this->generate_billet( $order , $_POST );					
			
			if($transaction) 
			{
					
				// Mark as on-hold (we're awaiting the payment)
				$order->update_status( 'on-hold', __( 'Aguardando Pagamento', 'orendapay' ) );
						
				// Reduce stock levels
				$order->reduce_order_stock();
						
				// Remove cart
				$this->woocommerce_instance()->cart->empty_cart();
						
				// Sets the return url.
				if ( version_compare( $this->woocommerce_instance()->version, '2.1', '>=' ) ) 
				{
					$url = $order->get_checkout_order_received_url();
				}
				else 
				{
					$url = add_query_arg( 'key', $order->order_key, add_query_arg( 'order', $order_id, get_permalink( woocommerce_get_page_id( 'thanks' ) ) ) );
				}
				
				// Return thankyou redirect.
				return array(
					'result'   => 'success',
					'redirect' => $url 
				);
			}
			else 
			{
				
				if($_POST['pagamentoOrenda']=='cartao' || $_POST['pagamentoOrenda']=='debit')
				{
					// Added error message.
					$this->add_error( '<strong>' . $this->title . '</strong>: ' . __( 'Ocorreu um erro ao autorizar seu cartão. Tente novamente.', 'orendapay' ) );
					return array(
						'result' => 'fail'
					);					
				}
				else
				{
					// Added error message.
					$this->add_error( '<strong>' . $this->title . '</strong>: ' . __( 'Ocorreu um erro ao processar seu pagamento. Tente novamente. Ou entre em contato conosco para obter assistência.', 'orendapay' ) );
					return array(
						'result' => 'fail'
					);					
				}
				
			}			
 
	 	}
		
		
		/**
		 * Displays notifications when the admin has something wrong with the configuration.
		 * @since  1.0.0
		 * @return void
		 */
		protected function admin_notices() 
		{
			if ( is_admin() ) 
			{
				// Checks if token is not empty.
				if ( empty( $this->merchant_id ) ) 
				{
					add_action( 'admin_notices', array( $this, 'merchant_id_missing_message' ) );
				}
				
				// Checks if token is not empty.
				if ( empty( $this->auth_token) ) 
				{
					add_action( 'admin_notices', array( $this, 'auth_token_missing_message' ) );
				}				
				
				// Checks that the currency is supported.
				if ( ! $this->using_supported_currency() ) 
				{
					add_action( 'admin_notices', array( $this, 'currency_not_supported_message' ) );
				}
				
				// Checks that the currency is supported.
				if ( ! $this->using_supported_currency() ) 
				{
					add_action( 'admin_notices', array( $this, 'currency_not_supported_message' ) );
				}

				if(!class_exists('WooCommerce'))
				{
					add_action( 'admin_notices', array( $this, 'plugin_woocommerce_not_instaled' ) );
				}
				
				if(!class_exists('Extra_Checkout_Fields_For_Brazil'))
				{
					add_action( 'admin_notices', array( $this, 'class_wc_brazil_checkout_fields_not_instaled' ) );
				}				
				
			}
		}		
		
		
		/**
		* Adds error message when not configured the token.
		* @since  1.0.0
		* @return string Error Mensage.
		*/
		public function plugin_woocommerce_not_instaled() 
		{
			echo '<div class="error"><p><strong>' . __( 'OrendaPay', 'orendapay' ) . '</strong>: ' . sprintf( __( 'Instale e ative o plugin WooCommerce.', 'orendapay' ), '<a href="plugins.php">' . __( 'Click here to configure!', 'orendapay' ) . '</a>' ) . '</p></div>';
		}	


		/**
		 * Adds error message when not configured the token.
		 * @since  1.0.0
		 * @return string Error Mensage.
		 */
		public function class_wc_brazil_checkout_fields_not_instaled() 
		{
			echo '<div class="error"><p><strong>' . __( 'OrendaPay', 'orendapay' ) . '</strong>: ' . sprintf( __( 'Instale e ative o plugin WooCommerce Extra Checkout Fields for Brazil, busque pelo Plugin Brazilian Market on WooCommerce', 'orendapay' ), '<a href="plugins.php">' . __( 'Click here to configure!', 'orendapay' ) . '</a>' ) . '</p></div>';
		}			
		
		
		/**
		 * Adds error message when not configured the token.
		 * @since  1.0.0
		 * @return string Error Mensage.
		 */
		public function merchant_id_missing_message() 
		{
			echo '<div class="error"><p><strong>' . __( 'OrendaPay', 'orendapay' ) . '</strong>: ' . sprintf( __( 'Você ainda não informou seu Merchant ID de integração. %s', 'orendapay' ), '<a href="' . $this->admin_url() . '">' . __( 'Click here to configure!', 'orendapay' ) . '</a>' ) . '</p></div>';
		}		
		
		
		/**
		 * Adds error message when not configured the token.
		 * @since  1.0.0
		 * @return string Error Mensage.
		 */
		public function auth_token_missing_message() 
		{
			echo '<div class="error"><p><strong>' . __( 'OrendaPay', 'orendapay' ) . '</strong>: ' . sprintf( __( 'Você ainda não informou seu Token de Integração. %s', 'orendapay' ), '<a href="' . $this->admin_url() . '">' . __( 'Click here to configure!', 'orendapay' ) . '</a>' ) . '</p></div>';
		}
 


		/**
		 * Adds error message when an unsupported currency is used.
		 * @since  1.0.0
		 * @return string
		 */
		public function currency_not_supported_message() 
		{
			echo '<div class="error"><p><strong>' . __( 'OrendaPay', 'orendapay') . '</strong>: ' . sprintf( __( 'Currency <code>%s</code> is not supported. Works only with Brazilian Real.', 'orendapay' ), get_woocommerce_currency() ) . '</p></div>';
		}	
		
		
		/**
		 * Returns a bool that indicates if currency is amongst the supported ones.
		 * @since  1.0.0
		 * @return bool
		 */
		protected function using_supported_currency() 
		{
			return ( get_woocommerce_currency() == 'BRL' );
		}		
		
		
		/**
		 * Gets the admin url.
		 * @since  1.0.0
		 * @return string
		 */
		protected function admin_url() 
		{
			if ( version_compare( $this->woocommerce_instance()->version, '2.1', '>=' ) ) 
			{
				return admin_url( 'admin.php?page=wc-settings&tab=checkout&section=orendapay' );
			}
			
			return admin_url( 'admin.php?page=woocommerce_settings&tab=payment_gateways&section=WC_orendapay_class' );
		}		
		
 
		
		/**
		 * Adds payment instructions on thankyou page.
		 * @since  1.0.0
		 * @param  int    $order_id Order ID.
		 * @return string           Payment instructions.
		 */
		public function thankyou_page( $order_id ) 
		{
			
			$orendapay_cartao = get_post_meta( $order_id, 'orendapay_cartao', true );

			// retorno cartão
			if($orendapay_cartao=='sim')
			{
				$orendapay_cod_transacao = get_post_meta( $order_id, 'orendapay_cod_transacao', true );
				$orendapay_situacao = get_post_meta( $order_id, 'orendapay_situacao', true );
				
				$html = '<div class="woocommerce-message">';
			
		
			
				$message = __( 'Pagamento por Cartão em processamento...', 'orendapay' ) . '<br />';
				$message .= __( 'O retorno atual que tivemos da Operadora de Cartão é: '. $orendapay_situacao, 'orendapay' ) . '<br />';
				$html .= apply_filters( 'woocommerce_orendapay_thankyou_page_instructions', $message, $order_id );
				$html .= '</div>';				
			} 
			// retorno BOLETO
			else
			{
				$boleto_codigo = get_post_meta( $order_id, 'orendapay_cod_transacao', true );
				$url = get_post_meta( $order_id, 'orendapay_url_boleto', true );
				$linha_digitavel = get_post_meta( $order_id, 'orendapay_linha_digitavel', true );
				
				$html = '<div class="woocommerce-message">';
			
				if ( isset( $url ) && ! empty( $url ) ) 
				{
					$html .= sprintf( '<a class="button" href="%s" target="_blank">%s</a>', $url, __( 'Imprimir Boleto Bancário', 'orendapay' ) );
				}

		  
				$message = __( 'Clique no botão e pague o boleto. Se Pix, leia o QRCode.', 'orendapay' ) . '<br />';
				
				$orendapay_parcelamento = get_post_meta( $order_id, 'orendapay_parcelamento', true );
				if(strlen($orendapay_parcelamento)>0)
				{
					$message .= __( $orendapay_parcelamento, 'orendapay' ) . '<br />';	
				}				
				 
				//$message .= __( 'Se preferir, imprima e pague em qualquer agência bancária ou loteria. Se Pix, leia o QRCode', 'orendapay' ) . '<br />';
				
				if(strlen($linha_digitavel)>0)
				{
					$message .= __( 'Linha Digitável: '. $linha_digitavel, 'orendapay' ) . '<br />';
				}
				$html .= apply_filters( 'woocommerce_orendapay_thankyou_page_instructions', $message, $order_id );
				
				$orendapay_pix_qrcode = get_post_meta( $order_id, 'orendapay_pix_qrcode', true );
				$orendapay_pix_chave = get_post_meta( $order_id, 'orendapay_pix_chave', true );				
				
				if (strlen($orendapay_pix_qrcode)>5) 
				{
					$orendapay_cod_cobranca = get_post_meta( $order_id, 'orendapay_cod_cobranca', true );
					
					$tokenverify = md5($orendapay_cod_cobranca);
					
					$html .= "<div id='alvoPAGO'><img src='$orendapay_pix_qrcode'><br>Chave Pix: $orendapay_pix_chave</div><br>
					
					<script>
					iniciaVerificacaoPix($orendapay_cod_cobranca);
					var start_secs;
					function iniciaVerificacaoPix(cod_cobranca)
					{
						start_secs = setInterval(function() { pixVer(cod_cobranca); },6000);
					}
					function pixVer(cod_cobranca)
					{
						$.post( 
							\"https://www.orendapay.com.br/action/frmCheckPixVer.php\",
							{\"cod_cobranca\":cod_cobranca,\"tokenverify\":'$tokenverify',\"url\":\"{$_SERVER['REQUEST_URI']}\"},
							function (response)
							{
								var res = response.split('|');
								if(res[0]=='CONCLUIDA')
								{
									chamaNotificacao(res[1]);
								}
							}
						);	
					}
					function chamaNotificacao(link)
					{
						clearInterval(start_secs);
						$('.woocommerce-message').html('<center><span style=\"font-size:20px;color:green;\">Identificamos que seu pagamento por Pix foi realizado. Obrigado.</font></center>');
					}
					</script>";
				}					
				
				$html .= '</div>';
			}
			 
			echo $html;
		}
		
			
		/**
		 * Adds payment instructions on customer email.
		 * @since  1.0.0
		 * @param  WC_Order $order         Order data.
		 * @param  bool     $sent_to_admin Sent to admin.
		 * @return string                  Payment instructions.
		 */
		public function email_instructions( $order, $sent_to_admin ) 
		{
			
			$orendapay_cartao = get_post_meta( $order->id, 'orendapay_cartao', true );

			// retorno cartão
			if($orendapay_cartao=='sim')
			{
				$orendapay_cod_transacao = get_post_meta( $order->id, 'orendapay_cod_transacao', true );
				$orendapay_situacao = get_post_meta( $order->id, 'orendapay_situacao', true );
				
				$html = '<h2>' . __( 'Payment', 'orendapay' ) . '</h2>';
				 
				$html .= '<p class="order_details">';
				$message = __( 'Seu pagamento por cartão está sendo processado.', 'orendapay' ) . '<br />';
				$message .= __( 'A situação atual da sua transação é: '.$orendapay_situacao, 'orendapay' ) . '<br />';
				
				$html .= apply_filters( 'woocommerce_orendapay_email_instructions', $message, $order );
				
				$html .= '</p>';				
				
			}
			else
			{
				
				$url = get_post_meta( $order->id, 'orendapay_url_boleto', true );
				$linha_digitavel = get_post_meta( $order->id, 'orendapay_linha_digitavel', true );			
				
				$html = '<h2>' . __( 'Payment', 'orendapay' ) . '</h2>';
				 
				$html .= '<p class="order_details">';
				$message = __( 'Clique no link abaixo e pague o boleto. Se Pix, leita o QRCode.', 'orendapay' ) . '<br />';
				
				$orendapay_parcelamento = get_post_meta( $order->id, 'orendapay_parcelamento', true );
				if(strlen($orendapay_parcelamento)>0)
				{
					$message .= __( $orendapay_parcelamento, 'orendapay' ) . '<br />';	
				}					
				
				//$message .= __( 'Se preferir, pague em qualquer agência bancária ou loteria.', 'orendapay' ) . '<br />';
				if(strlen($linha_digitavel)>0)
				{  
					$message .= __( 'Linha Digitável: '.$linha_digitavel, 'orendapay' ) . '<br />';
				}
				
				$html .= apply_filters( 'woocommerce_orendapay_email_instructions', $message, $order );
				
				if ( isset( $url ) && ! empty( $url ) ) 
				{
					$html .= sprintf( '<a class="button" href="%s" target="_blank">%s</a>', $url, __( 'Imprimir Boleto', 'orendapay' ) ) . '<br />';
				}
				
				$orendapay_pix_qrcode = get_post_meta( $order->id, 'orendapay_pix_qrcode', true );
				$orendapay_pix_chave = get_post_meta( $order->id, 'orendapay_pix_chave', true );				
				if (strlen($orendapay_pix_qrcode)>5) 
				{
					$html .= "<br><img src='$orendapay_pix_qrcode'><br>Chave Pix: $orendapay_pix_chave<br>";
				}				
				
				$html .= '</p>';
			
			}
			
			echo $html;
		}		
		
		
		/**
		 * Adds payment instructions on View Order
		 * @since  1.0.0
		 * @param  int    $order_id Order ID.
		 * @return string           Payment instructions.
		 */
		public function view_order_custom_payment_instruction( $order ) 
		{
			
			$orendapay_cartao = get_post_meta( $order->id, 'orendapay_cartao', true );

			// retorno cartão
			if($orendapay_cartao=='sim')
			{
				$orendapay_cod_transacao = get_post_meta( $order->id, 'orendapay_cod_transacao', true );
				$orendapay_situacao = get_post_meta( $order->id, 'orendapay_situacao', true );			
				
				
				$html = '<div class="woocommerce-message">';
				$message = __( 'Seu pagamento por cartão está sendo processado.', 'orendapay' ) . '<br />';
				$message .= __( 'A situação atual da sua transação é: '.$orendapay_situacao, 'orendapay' ) . '<br />';
				$html .= apply_filters( 'woocommerce_orendapay_order_details_after_order_table', $message, $order->id );
				$html .= '</div>';				
			}
			else
			{
			
				$boleto_codigo = get_post_meta( $order->id, 'orendapay_cod_transacao', true );
				$url = get_post_meta( $order->id, 'orendapay_url_boleto', true );
				$linha_digitavel = get_post_meta( $order->id, 'orendapay_linha_digitavel', true );

				$html = '<div class="woocommerce-message">';
			
				if ( isset( $url ) && ! empty( $url ) ) 
				{
					$html .= sprintf( '<a class="button" href="%s" target="_blank">%s</a>', $url, __( 'Imprimir Boleto Bancário', 'orendapay' ) );
				}
		    
				$message = __( 'Clique no botão e pague o boleto no seu Internet Banking.', 'orendapay' ) . '<br />';
				$orendapay_parcelamento = get_post_meta( $order->id, 'orendapay_parcelamento', true );
				if(strlen($orendapay_parcelamento)>0)
				{
					$message .= __( $orendapay_parcelamento, 'orendapay' ) . '<br />';	
				}			  
		  
				$message = __( 'Clique no botão e pague o boleto. Se pix, leia o QRCode.', 'orendapay' ) . '<br />';
				//$message .= __( 'Se preferir, imprima e pague em qualquer agência bancária ou loteria.', 'orendapay' ) . '<br />';
				if(strlen($linha_digitavel)>0)
				{
					$message .= __( 'Linha Digitável: '. $linha_digitavel, 'orendapay' ) . '<br />';
				}
				
				$html .= apply_filters( 'woocommerce_orendapay_order_details_after_order_table', $message, $order->id );
				
				$orendapay_pix_qrcode = get_post_meta( $order->id, 'orendapay_pix_qrcode', true );
				$orendapay_pix_chave = get_post_meta( $order->id, 'orendapay_pix_chave', true );				
				if (strlen($orendapay_pix_qrcode)>5) 
				{
					$html .= "<br><img src='$orendapay_pix_qrcode'><br>Chave Pix: $orendapay_pix_chave<br>";
				}				
				
				$html .= '</div>';
			}
			
			echo $html;
		}
		
		
		/**
		 * Check API Response.
		 * @since  1.0.0
		 * @return void
		*/
		public function check_webhook_notification() 
		{
			$jsonBody = json_decode(file_get_contents('php://input'));
			
			//get values post
			$seu_codigo = trim($jsonBody->seu_codigo);	
			$situacao = trim($jsonBody->situacao);
			$numero = trim($jsonBody->numero);
			
			if(is_null($jsonBody) || $seu_codigo<=0)
				throw new Exception('Falha ao interpretar JSON do webhook: Evento do Webhook não encontrado!');
		  
			header( 'HTTP/1.1 200 OK' );
			
			//Ok, pay!
			if ($situacao=='pago' || $situacao=='capturado') 
			{
				$order_id = $seu_codigo;
				do_action( 'woocommerce_orendapay_webhook_notification', $order_id );
			}
			
			if($situacao=='nao_autorizado')
			{
				$order = new WC_Order($order_id);
				$order->add_order_note( __( 'OrendaPay: Pagamento não autorizado.', 'orendapay' ) );
			}
		}
		
		/**
		 * Successful pay notification.
		 * @since  1.0.0
		 * @param  int    $order_id Order ID.
		 * @return void   Updated the order status to approved.
		 */
		public function successful_webhook_notification($order_id)  
		{
			$order = new WC_Order($order_id);
			
			if($this->statusPedido=='CONCLUIDO')			
			{
				$order->add_order_note( __( 'OrendaPay: Pagamento Aprovado (Processando)', 'orendapay' ) );
				$order->payment_complete();				
				
				$order->add_order_note( __( 'OrendaPay: Pedido Concluído', 'orendapay' ) );
				$order->update_status('completed');
			}
			else
			{
				$order->add_order_note( __( 'OrendaPay: Pagamento Aprovado (Processando)', 'orendapay' ) );
				$order->payment_complete();
			} 
		}		
		
		 
 	}
}

?>