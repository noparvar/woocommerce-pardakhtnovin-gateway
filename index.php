<?php
/*
Plugin Name: WooCommerce Pardakhtnovin Gateway
Version: 1.1.0
Description:  Integrate the Pardakhtnovin payment gateway seamlessly into your WooCommerce store. Provide a reliable and user-friendly payment option for your customers, enhancing their checkout experience.
 Text Domain: wc-pardakhtnovin
 Domain Path: /languages
Plugin URI: https://www.noparvar.net/portfolio-archive/woocommerce-pardakhtnovin-gateway-plugin/
Author: Mohsen Noparvar
Author URI: https://www.noparvar.net
License: GPLv2
License URI: https://www.gnu.org/licenses/gpl-2.0.html
*/

if (!defined('ABSPATH') ) exit;
function Load_PardakhtNovin_Gateway() {

	// Check if WooCommerce Payment Gateway base class exists
	if ( class_exists( 'WC_Payment_Gateway' ) && !class_exists( 'WC_Gateway_Pardakhtnovin' ) && !function_exists('Woocommerce_Add_PardakhtNovin_Gateway') ) {

		// Define a function to add the PardakhtNovin gateway to WooCommerce payment gateways
		add_filter('woocommerce_payment_gateways', 'Woocommerce_Add_PardakhtNovin_Gateway' );
		function Woocommerce_Add_PardakhtNovin_Gateway($methods) {
			// Add the PardakhtNovin gateway class to the list of available gateways
			$methods[] = 'WC_Gateway_Pardakhtnovin';
			return $methods;
		}

		class WC_Gateway_Pardakhtnovin extends WC_Payment_Gateway {
			private $username;
			private $password;
			private $certpassword;
			private $terminal;
            private $failed_massage;

            /**
			 * Constructor for the payment gateway.
			 */
			public function __construct() {
				// Set unique identifier for the payment gateway
				$this->id = 'eghtesadnovin';

				// Display name for the payment gateway
				$this->method_title = __('Pardakhtnovin Gateway', 'wc-pardakhtnovin');

				// Description for the payment gateway
				$this->method_description = __('The WooCommerce Pardakhtnovin Gateway Plugin allows you to integrate the Pardakhtnovin payment gateway with your WooCommerce store.', 'wc-pardakhtnovin');

				// Set an icon for the payment gateway (using a filter to apply logo)
				$this->icon = apply_filters('WC_EghtesadNovin_logo', WP_PLUGIN_URL . "/" . plugin_basename(dirname(__FILE__)) . '/assets/images/logo.png');

				// Indicate if this payment gateway requires additional fields during checkout
				$this->has_fields = false;

				// Initialize form fields and settings
				$this->init_form_fields();
				$this->init_settings();

				// Retrieve and set settings values
				$this->title = $this->settings['title'];
				$this->description = $this->settings['description'];

				$this->username = $this->settings['username'];
				$this->password = $this->settings['password'];

				$this->certpassword = $this->settings['certpassword'];
				$this->terminal = $this->settings['terminal'];

                $this->failed_massage = $this->settings['failed_massage'];

				// Add actions to handle receipt and return from the gateway
				add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'process_admin_options'));
				add_action('woocommerce_update_options_payment_gateways_' . $this->id, array($this, 'upload_cert_file'));

				add_action('woocommerce_receipt_' . $this->id, array($this, 'Send_to_EghtesadNovin_Gateway_By_PardakhtNovin'));
				add_action('woocommerce_api_' . strtolower(get_class($this)), array($this, 'Return_from_EghtesadNovin_Gateway_By_PardakhtNovin'));
			}

			/**
			 * Display the admin options for the payment gateway.
			 */
			public function admin_options() {
				?>
				<h2><?php _e('Pardkhtnovin Gateway Settings', 'wc-pardakhtnovin'); ?></h2>
				<h3><?php _e('Certificate file settings', 'wc-pardakhtnovin'); ?></h3>
				<table class="form-table">
					<tbody>
					<tr>
						<th scope="row" class="titledesc">
							<span class="woocommerce-help-tip"></span>
							<label for="woocommerce_eghtesadnovin_cetificate"><?php _e('Upload the certificate file', 'wc-pardakhtnovin'); ?></label>
						</th>
						<td class="forminp">
							<fieldset>
								<legend class="screen-reader-text"><span><?php _e('Upload the certificate file', 'wc-pardakhtnovin'); ?></span></legend>
								<label for="woocommerce_eghtesadnovin_cetificate">
									<input type="file" name="certificateToUpload" id="certificateToUpload"><?php _e('Choose the certificate file', 'wc-pardakhtnovin'); ?>
								</label>
								<br>
							</fieldset>
							<p class="description">
								<?php
								// Check if the certificate file has been uploaded and display appropriate message
								$certFileStatus = get_option('novin_certificate_file');
								if ($certFileStatus && file_exists($certFileStatus)) {
									echo '<span style="color:green">' . __('The certificate file is already uploaded.', 'wc-pardakhtnovin') . '</span><br>';
								} else {
									echo '<span style="color:red">' . __('The certificate file is not uploaded yet.', 'wc-pardakhtnovin') . '</span><br>';
								}

                                _e('The certificate file must have pem extension.', 'wc-pardakhtnovin');
                                echo '<br>';

								// Check if the directory is writable and display a message accordingly
								if (!is_writable($this->get_home_directory_address() . '/')) {
									echo '<span style="color:red">' . __('The Home directory is not writable!', 'wc-pardakhtnovin') . '</span><br>';
								}
								?>
							</p>
						</td>
					</tr>
					</tbody>
				</table>
				<table class="form-table">
					<?php
					// Generate and display the settings fields
					$this->generate_settings_html();
					?>
				</table>
				<?php
			}

			/**
			 * Initialize form fields and settings for the payment gateway.
			 */
			public function init_form_fields(){
				$this->form_fields = apply_filters('WC_EghtesadNovin_Config',
					array(
						// Account Configuration Section
						'account_config' => array(
							'title'       => __( 'Pardakhtnovin Account Configurations', 'wc-pardakhtnovin' ),
							'type'        => 'title',
							'description' => '',
						),
						'terminal' => array(
							'title'       => __( 'Terminal ID', 'wc-pardakhtnovin' ),
							'type'        => 'text',
							'description' => __( 'Terminal ID of Pardakhtnovin Gateway', 'wc-pardakhtnovin' ),
							'default'     => '',
							'desc_tip'    => true
						),
						'username' => array(
							'title'       => __( 'Username', 'wc-pardakhtnovin' ),
							'type'        => 'text',
							'description' => __( 'Username of Pardakhtnovin Gateway', 'wc-pardakhtnovin' ),
							'default'     => '',
							'desc_tip'    => true
						),
						'password' => array(
							'title'       => __( 'Password', 'wc-pardakhtnovin' ),
							'type'        => 'text',
							'description' => __( 'Password of Pardakhtnovin Gateway', 'wc-pardakhtnovin' ),
							'default'     => '',
							'desc_tip'    => true
						),
						'certpassword' => array(
							'title'       => __( 'Certificate Password', 'wc-pardakhtnovin' ),
							'type'        => 'text',
							'description' => __( 'Certificate Password', 'wc-pardakhtnovin' ),
							'default'     => '',
							'desc_tip'    => true
						),

						// Base Configuration Section
						'base_config' => array(
							'title'       => __( 'Plugin Configurations', 'wc-pardakhtnovin' ),
							'type'        => 'title',
							'description' => '',
						),
						'enabled' => array(
							'title'   => __( 'Enable / Disable', 'wc-pardakhtnovin' ),
							'type'    => 'checkbox',
							'label'   => __( 'Enable', 'wc-pardakhtnovin' ),
							'description' => __( 'Check to enable Novinpardakht Gateway', 'wc-pardakhtnovin' ),
							'default' => 'yes',
							'desc_tip'    => true,
						),
						'title' => array(
							'title'       => __( 'Gateway Title', 'wc-pardakhtnovin' ),
							'type'        => 'text',
							'description' => __( 'Enter the title you want to display to the user during checkout and payment', 'wc-pardakhtnovin' ),
							'default'     => __( 'Pardakhtnovin', 'wc-pardakhtnovin' ),
							'desc_tip'    => true,
						),
						'description' => array(
							'title'       => __( 'Gateway Description', 'wc-pardakhtnovin' ),
							'type'        => 'text',
							'desc_tip'    => true,
							'description' => __( 'Enter the description you want to display to the user during checkout and payment', 'wc-pardakhtnovin' ),
							'default'     => __( 'Secure payment with all Shetab Cards with Pardakhtnovin', 'wc-pardakhtnovin' )
						),

						// Payment Configuration Section
						'payment_config' => array(
							'title'       => __( 'Payment Configurations', 'wc-pardakhtnovin' ),
							'type'        => 'title',
							'description' => '',
						),
						'success_massage' => array(
							'title'       => __( 'Successful Payment Message', 'wc-pardakhtnovin' ),
							'type'        => 'textarea',
                            'description' => __( 'Enter the message you want to display to the user after a successful payment. You can also use the shortcode {transaction_id} to display the transaction tracking code and {SaleOrderId} to display the payment request number.', 'wc-pardakhtnovin' ),
                            'default'     => __( 'Thank you. Your order has been successfully paid. Your payment transaction tracking code is {transaction_id} and the payment request number is {SaleOrderId}.', 'wc-pardakhtnovin' ),
						),
						'cancelled_massage' => array(
                            'title'       => __( 'Cancellation Message', 'wc-pardakhtnovin' ),
							'type'        => 'textarea',
                            'description' => __( 'Enter the message you want to display to the user after they cancel the payment. This message will be shown after returning from the bank.', 'wc-pardakhtnovin' ),
                            'default'     => __( 'Your payment has been canceled.', 'wc-pardakhtnovin' ),
						),
                        'failed_massage' => array(
                            'title' => __('Failed Payment Message', 'wc-pardakhtnovin'),
                            'type' => 'textarea',
                            'description' => __('Enter the message you want to display to the user after a payment failure. You can also use the shortcode {fault} to display the reason for the error. This error reason is sent from PardakhtNovin payment gateway.', 'wc-pardakhtnovin'),
                            'default' => __('Your payment has failed. Please try again or contact the site administrator if you encounter any issues.', 'wc-pardakhtnovin'),
                        )
					)
				);
			}

			/**
			 * Retrieves the address of the home directory.
			 *
			 * @return string The address of the home directory.
			 */
			public function get_home_directory_address() {
				// Get the document root path from the server environment
				$documentRoot = $_SERVER['DOCUMENT_ROOT'];

				$directorySeparator = "/";

				// Remove the trailing slash or backslash, if present
				$cleanedPath = rtrim($documentRoot, '/\\');

				// Split the cleaned path into an array
				$pathParts = explode($directorySeparator, $cleanedPath);

				// Remove the last part of the path
				array_pop($pathParts);

				// Reassemble the path using the directory separator
				// Return the modified path as the home directory address
				return implode($directorySeparator, $pathParts);
			}

			/**
			 * Uploads the certificate file if provided.
			 */
			public function upload_cert_file() {
				if (isset($_FILES["certificateToUpload"]["tmp_name"]) && !empty($_FILES["certificateToUpload"]["name"])) {
					$this->handle_uploaded_certificate_file($_FILES["certificateToUpload"]);
				}
			}

			/**
			 * Handles the uploaded certificate file.
			 *
			 * @param array $uploadedFile The uploaded file details.
			 */
			private function handle_uploaded_certificate_file($uploadedFile) {
				$fileExtension = strtolower(pathinfo($uploadedFile["name"], PATHINFO_EXTENSION));
				$allowedExtension = 'pem';

				if ($fileExtension === $allowedExtension) {
					$uniqueFilename = round(microtime(true)) . '.' . $fileExtension;
					$destinationPath = $this->get_home_directory_address() . '/' . $uniqueFilename;

					if (move_uploaded_file($uploadedFile["tmp_name"], $destinationPath)) {
						// Display a success notice and update the option value
						add_action('admin_notices', array($this, 'display_upload_success_notice'));
						update_option('novin_certificate_file', $destinationPath, true);
					} else {
						// Display an error notice if file upload fails
						add_action('admin_notices', array($this, 'display_upload_failure_notice'));
					}
				} else {
					// Display an error notice for invalid file extension
					add_action('admin_notices', array($this, 'display_invalid_extension_notice'));
				}
			}

			/**
			 * Displays a notice for invalid file extension.
			 */
			public function display_invalid_extension_notice() {
				echo '<div class="error notice"><p>' . __('The uploaded file extension is not pem. Please upload the correct file.', 'wc-pardakhtnovin') . '</p></div>';
			}

			/**
			 * Displays a notice for file upload failure.
			 */
			public function display_upload_failure_notice() {
                echo '<div class="error notice"><p>' . __('Error uploading the certificate file. Please check the access to the home directory on your server.', 'wc-pardakhtnovin') . '</p></div>';
			}

			/**
			 * Displays a notice for successful file upload.
			 */
			public function display_upload_success_notice() {
                echo '<div class="error notice"><p>' . __('The certificate file was successfully uploaded.', 'wc-pardakhtnovin') . '</p></div>';
			}

			/**
			 * Process the payment for an order.
			 *
			 * @param int $order_id The ID of the order being processed.
			 * @return array The result of the payment processing.
			 */
			public function process_payment($order_id) {
				// Create a new instance of the WC_Order class for the given order ID
				$order = new WC_Order($order_id);

				// Return a success response with a redirect URL to the checkout payment page
				return array(
					'result'   => 'success',                        // Indicate successful payment processing
					'redirect' => $order->get_checkout_payment_url(true)  // Redirect to the checkout payment page
				);
			}


			/**
			 * Handle errors that occur when sending a request to the payment gateway.
			 *
			 * @param string    $error     The error message.
			 * @param WC_Order  $order     The WooCommerce order object.
			 * @param int       $order_id  The ID of the order.
			 */
			public function error_in_Gateway_By_Pardakhtnovin($error, $order, $order_id) {
				// Create a note with the error message and add it to the order's notes
                $note = sprintf(__('Error occurred while sending to the payment gateway: %s', 'wc-pardakhtnovin'), $error);
				$note = apply_filters('WC_EghtesadNovin_Send_to_Gateway_Failed_Note', $note, $order_id, 1);
				$order->add_order_note($note);

				// Create a notice with the error message and display it to the user
                $notice = sprintf(__('An error occurred while connecting to the payment gateway: <br/>%s', 'wc-pardakhtnovin'), $error);
				$notice = apply_filters('WC_EghtesadNovin_Send_to_Gateway_Failed_Notice', $notice, $order_id, 1);
				if ($notice) {
					wc_add_notice($notice, 'error');
				}

				// Trigger an action to handle the case of a failed payment gateway connection
				do_action('WC_EghtesadNovin_Send_to_Gateway_Failed', $order_id, 1);
			}

			/**
			 * Generate the checkout form for the payment gateway.
			 *
			 * @param int       $order_id    The ID of the WooCommerce order.
			 * @param woocommerce  $woocommerce The WooCommerce class instance.
			 */
			private function generate_checkout_form($order_id, $woocommerce) {
				// Generate the HTML for the checkout form
				$form = '<form action="" method="POST" class="eghtesadnovin-checkout-form" id="eghtesadnovin-checkout-form">
                            <input type="submit" name="eghtesadnovin_submit" class="button alt" id="eghtesadnovin-payment-button" value="' . __('Pay', 'wc-pardakhtnovin') . '"/>
                            <a class="button cancel" href="' . $woocommerce->cart->get_checkout_url() . '">' . __('Back to checkout', 'wc-pardakhtnovin') . '</a>
                        </form><br/>';

				// Perform actions before displaying the form
				do_action('WC_EghtesadNovin_Gateway_Before_Form', $order_id, $woocommerce);

				// Display the generated form
				echo $form;

				// Perform actions after displaying the form
				do_action('WC_EghtesadNovin_Gateway_After_Form', $order_id, $woocommerce);
			}

			/**
			 * Calculate the payment amount based on the order.
			 *
			 * @param WC_Order $order The WooCommerce order object.
			 *
			 * @return int The calculated payment amount.
			 */
			private function calculate_amount($order) {
				// Get the order currency and total amount
				$currency = $order->get_order_currency();
				$Amount = intval($order->order_total);

				// Apply filters before currency conversion
				$Amount = apply_filters('WC_EghtesadNovin_Gateway_before_check_currency', $Amount, $currency);

				// Convert currency if applicable
				$currencyConversionMap = array(
					'irt', 'toman', 'iran toman', 'iranian toman', 'iran-toman', 'iranian-toman',
					'iran_toman', 'iranian_toman', 'تومان', 'تومان ایران'
				);
				if (in_array(strtolower($currency), $currencyConversionMap)) {
					$Amount *= 10;
				} elseif (strtolower($currency) === 'irht') {
					$Amount *= 1000 * 10;
				} elseif (strtolower($currency) === 'irhr') {
					$Amount *= 1000;
				}

				// Apply filters after currency conversion
				return apply_filters('WC_EghtesadNovin_Gateway_after_check_currency', $Amount, $currency);
			}

			/**
			 * Initialize the SOAP client for communication with the payment gateway.
			 *
			 * @param WC_Order $order     The WooCommerce order object.
			 * @param int      $order_id  The ID of the WooCommerce order.
			 * @return nusoap_client|false The initialized SOAP client or false if there's an error.
			 */
			private function initialize_soap_client($order, $order_id) {
				// Check if the NuSoap client class exists, and include it if not
				if (!class_exists('nusoap_client')) {
					include_once("inc/nusoap.php");
				}

				// Create a new instance of the NuSoap client
				$client = new nusoap_client('https://pna.shaparak.ir/ref-payment2/jax/merchantService?wsdl', true);
				//$client = new nusoap_client('http://mockserver.test/mock_soap_server.php?wsdl', true);

				// Check for any errors during client creation
				$err = $client->getError();
				if ($err) {
					// Handle the error and return false
					$this->error_in_Gateway_By_Pardakhtnovin($err, $order, $order_id);
					return false;
				}

				// Return the initialized SOAP client
				return $client;
			}

			/**
			 * Perform merchant login using the provided SOAP client.
			 *
			 * @param nusoap_client $client    The initialized SOAP client.
			 * @param WC_Order      $order     The WooCommerce order object.
			 * @param int           $order_id  The ID of the WooCommerce order.
			 * @return array|false The login data or false if there's an error.
			 */
			private function perform_merchant_login($client, $order, $order_id) {
				$userName = $this->username;
				$userPassword = $this->password;

				// Call the 'MerchantLogin' method using the provided credentials
				$login = $client->call('MerchantLogin', array('param' => array('Password' => $userPassword, 'UserName' => $userName)));

				// Check for faults in the SOAP call
				if ($client->fault) {
					$this->error_in_Gateway_By_Pardakhtnovin('<h2>Fault (Expect - The request contains an invalid SOAP body)</h2>', $order, $order_id);
					return false;
				} else {
					$err = $client->getError();
					if ($err) {
						$this->error_in_Gateway_By_Pardakhtnovin($err, $order, $order_id);
						return false;
					}
				}

				// Return the login data retrieved from the SOAP call
				return $login['return'];
			}

			/**
			 * Prepare the web service context for SOAP requests.
			 *
			 * @param string $sessionId   The session ID obtained from merchant login.
			 * @param string $userName    The username for the merchant.
			 * @param string $userPassword The password for the merchant.
			 * @return array The prepared web service context.
			 */
			private function prepare_ws_context($sessionId, $userName, $userPassword) {
				return array(
					'SessionId' => $sessionId,
					'UserName' => $userName,
					'Password' => $userPassword
				);
			}

			/**
			 * Prepare parameters for the purchase request.
			 *
			 * @param array    $wsContext  The prepared web service context.
			 * @param string   $terminalId The terminal ID for the payment gateway.
			 * @param WC_Order $order      The WooCommerce order object.
			 * @param int      $order_id   The ID of the WooCommerce order.
			 *
			 * @return array The prepared purchase parameters.
			 */
			private function prepare_purchase_parameters($wsContext, $terminalId, $order, $order_id) {
				$reserveNum = date('ymdHis');
				$redirectUrl = add_query_arg('wc_order', $order_id, WC()->api_request_url('WC_Gateway_Pardakhtnovin'));

				return array(
					'param' => array(
						'WSContext' => $wsContext,
						'TransType' => 'enGoods',
						'ReserveNum' => $reserveNum,
						'TerminalId' => $terminalId,
						'Amount' => $this->calculate_amount($order),
						'RedirectUrl' => $redirectUrl
					)
				);
			}

			/**
			 * Handle a SOAP fault by generating an error message and passing it to the error handling method.
			 *
			 * @param WC_Order $order The WooCommerce order object.
			 * @param int $order_id The order ID.
			 */
			private function handle_soap_fault($order, $order_id) {
				$errorMessage = '<h2>Fault (Expect - The request contains an invalid SOAP body)</h2>';
				$this->error_in_Gateway_By_Pardakhtnovin($errorMessage, $order, $order_id);
			}

			/**
			 * Call the GenerateTransactionDataToSign SOAP method.
			 *
			 * @param array            $purchaseParams The prepared purchase parameters.
			 * @param nusoap_client    $client         The initialized SOAP client.
			 * @param WC_Order         $order          The WooCommerce order object.
			 * @param int              $order_id       The ID of the WooCommerce order.
			 *
			 * @return bool|array False on failure, or the response data on success.
			 */
			private function call_generate_transaction_data_to_sign($purchaseParams, $client, $order, $order_id) {
				// Call the GenerateTransactionDataToSign SOAP method
				$response = $client->call('GenerateTransactionDataToSign', $purchaseParams);

				if ($client->fault) {
					$this->handle_soap_fault($order, $order_id);
					return false;
				}

				$err = $client->getError();
				if ($err) {
					$this->error_in_Gateway_By_Pardakhtnovin($err, $order, $order_id);
					return false;
				}

				return $response;
			}

			/**
			 * Create a directory for storing messages.
			 *
			 * @return string The path to the created messages' directory.
			 */
			private function create_messages_directory() {
				$messagesFolder = $this->get_home_directory_address() . '/novinMessages/';

				if (!is_dir($messagesFolder)) {
					mkdir($messagesFolder, 0777, true);
				}

				return $messagesFolder;
			}

			/**
			 * Prepare signature files for the message.
			 *
			 * @param string $messagesFolder The path to the messages' folder.
			 * @param string $dataToSign The data to be signed.
			 *
			 * @return array An array containing the paths to the message and signature files.
			 */
			private function prepare_signature_files($messagesFolder, $dataToSign) {
				$fileName = uniqid();
				$msgFile = $messagesFolder . $fileName . '-msd.txt';
				$sgdFile = $messagesFolder . $fileName . '-sgd.txt';

				$fp = fopen($msgFile, "w");
				fwrite($fp, $dataToSign);
				fclose($fp);

				$fs = fopen($sgdFile, "w");
				fwrite($fs, "");
				fclose($fs);

				return array('msgFile' => $msgFile, 'sgdFile' => $sgdFile);
			}


			/**
			 * Sign the data using OpenSSL.
			 *
			 * @param array $signatureFiles An array containing the paths to the message and signature files.
			 *
			 * @return string The generated signature.
			 */
			private function sign_with_openssl($signatureFiles) {
				$certFileStatus = get_option('novin_certificate_file');
				$certPassword = $this->certpassword;

				openssl_pkcs7_sign(
					$signatureFiles['msgFile'],
					$signatureFiles['sgdFile'],
					'file://' . realpath($certFileStatus),
					array(
						'file://' . realpath($certFileStatus),
						$certPassword
					),
					array(),
					PKCS7_NOSIGS
				);

				$data = file_get_contents($signatureFiles['sgdFile']);
				$parts = explode("\n\n", $data, 2);
				$string = $parts[1];
				$parts1 = explode("\n\n", $string, 2);

				return $parts1[0];
			}

			/**
			 * Generate a signed data token using the GenerateSignedDataToken SOAP method.
			 *
			 * @param nusoap_client $client The SOAP client.
			 * @param array $wsContext The web service context.
			 * @param string $uniqueId The unique ID.
			 * @param string $signature The signature.
			 * @param WC_Order $order The WooCommerce order object.
			 * @param int $order_id The order ID.
			 * @return mixed|false The response from the SOAP call, or false on error.
			 */
			private function generate_signed_data_token($client, $wsContext, $uniqueId, $signature, $order, $order_id) {
				$response = $client->call('GenerateSignedDataToken', array(
					'param' => array(
						'WSContext' => $wsContext,
						'UniqueId' => $uniqueId,
						'Signature' => $signature,
					),
				));

				if ($client->fault) {
					$this->handle_soap_fault($order, $order_id);
					return false;
				}

				$err = $client->getError();
				if ($err) {
					$this->error_in_Gateway_By_Pardakhtnovin($err, $order, $order_id);
					return false;
				}

				return $response;
			}

			/**
			 * Perform merchant login, prepare purchase parameters, generate signed data token, and return the token.
			 *
			 * @param WC_Order $order The WooCommerce order object.
			 * @param int $order_id The order ID.
			 * @return string|false The generated signed purchase token, or false on error.
			 */
			private function login_and_generate_signed_token($order, $order_id) {
				// Initialize SOAP client
				$client = $this->initialize_soap_client($order, $order_id);
				if (!$client) {
					return false;
				}

				// Perform merchant login
				$loginData = $this->perform_merchant_login($client, $order, $order_id);
				if (!$loginData) {
					return false;
				}

				// Prepare web service context using login data
				$wsContext = $this->prepare_ws_context($loginData['SessionId'], $this->username, $this->password);

				// Prepare purchase parameters
				$purchaseParams = $this->prepare_purchase_parameters($wsContext, $this->terminal, $order, $order_id);
				if (!$purchaseParams) {
					return false;
				}

				// Call GenerateTransactionDataToSign to obtain data for signing
				$getPurchaseParamsToSign = $this->call_generate_transaction_data_to_sign($purchaseParams, $client, $order, $order_id);
				if (!$getPurchaseParamsToSign) {
					return false;
				}

				// Extract necessary values
				$uniqueId = $getPurchaseParamsToSign['return']['UniqueId'];
				$dataToSign = $getPurchaseParamsToSign['return']['DataToSign'];

				// Create a directory for messages if not exists
				$messagesFolder = $this->create_messages_directory();

				// Prepare files for signing
				$signatureFiles = $this->prepare_signature_files($messagesFolder, $dataToSign);

				// Sign the data using OpenSSL
				$signature = $this->sign_with_openssl($signatureFiles);

				// Generate signed data token
				$generateSignedPurchaseToken = $this->generate_signed_data_token($client, $wsContext, $uniqueId, $signature, $order, $order_id);

				if ($generateSignedPurchaseToken === false) {
					return false;
				}

				return $generateSignedPurchaseToken['return']['Token'];
			}

			/**
			 * Display a notice, perform redirection to the payment gateway.
			 *
			 * @param string $generateSignedPurchaseToken The generated signed purchase token.
			 */
			private function display_notice_and_redirect($generateSignedPurchaseToken) {
                _e( 'Connecting to payment gateway...', 'wc-pardakhtnovin' );

				// Create a hidden form for redirection to the payment gateway
				echo '<form id="redirect_to_novin" method="post" action="https://pna.shaparak.ir/_ipgw_/payment/" style="display:none !important;">
                        <input type="hidden" id="token" name="token" value="' . esc_attr($generateSignedPurchaseToken) . '">
                        <input type="hidden" id="language" name="language" value="fa">
                        <input type="submit" value="Pay">
                      </form>
                      <script type="text/javascript">
                        document.getElementById("redirect_to_novin").submit();
                      </script>';
			}

			/**
			 * Send the user to the EghtesadNovin gateway for payment.
			 *
			 * @param int $order_id The order ID.
			 */
			public function Send_to_EghtesadNovin_Gateway_By_PardakhtNovin($order_id) {
				// Check if the certificate file exists
				$certFileStatus = get_option('novin_certificate_file');
				if (!$certFileStatus || !file_exists($certFileStatus)) {
					// Return early if the certificate is missing
					return;
				}

				// Set the session order ID for EghtesadNovin gateway
				global $woocommerce;
				$woocommerce->session->order_id_eghtesadnovin = $order_id;

				// Check if the payment form is submitted
				if (isset($_POST["eghtesadnovin_submit"])) {
					// Initialize order object
					$order = new WC_Order($order_id);

					// Generate the signed purchase token
					$generateSignedPurchaseToken = $this->login_and_generate_signed_token($order, $order_id);
					if (!$generateSignedPurchaseToken){
						return;
					}

					// Display a notice and redirect to the payment gateway
					$this->display_notice_and_redirect($generateSignedPurchaseToken);
				} else {
					// Generate and display the checkout form
					$this->generate_checkout_form($order_id, $woocommerce);
				}
			}

			/**
			 * Verifies the merchant transaction with the payment gateway.
			 *
			 * @param array       $loginData An array containing login session data.
			 * @param nusoap_client $client The initialized SOAP client.
			 * @param WC_Order    $order The WooCommerce order object.
			 * @param int         $order_id The WooCommerce order ID.
			 *
			 * @return bool|mixed False on failure, otherwise the verified transaction amount.
			 */
			private function verify_merchant_transaction($loginData, $client, $order, $order_id) {
				// Prepare the Web Service Context using login data
				$wsContext = $this->prepare_ws_context($loginData['SessionId'], $this->username, $this->password);

				// Prepare parameters for the VerifyMerchantTrans SOAP call
				$params = array(
					'param' => array(
						'WSContext' => $wsContext,
						'Token' => $_POST['token'],
						'RefNum' => $_POST['RefNum']
					)
				);

				// Call the VerifyMerchantTrans SOAP method
				$verifyTrans = $client->call('VerifyMerchantTrans', $params);

				// Check if the SOAP call resulted in a fault
				if ($client->fault) {
					$this->error_in_Gateway_By_Pardakhtnovin('<h2>Fault (Expect - The request contains an invalid SOAP body)</h2>', $order, $order_id);
					return false;
				} else {
					$err = $client->getError();
					if ($err) {
						$this->error_in_Gateway_By_Pardakhtnovin($err, $order, $order_id);
						return false;
					}
				}

				// Return the verified transaction amount
				return $verifyTrans['return']['Amount'];
			}


			/**
			 * Redirects to the success page after a successful payment.
			 *
			 * @param woocommerce $woocommerce The WooCommerce instance.
			 * @param int $order_id The WooCommerce order ID.
			 * @param WC_Order $order The WooCommerce order object.
			 * @param string $transaction_id The transaction ID.
			 * @param string $SaleOrderId The Sale Order ID.
			 */
			private function redirect_to_success_page($woocommerce, $order_id, $order, $transaction_id, $SaleOrderId) {
				// Update settleSaleOrderId and settleSaleReferenceId if present
				if (isset($_POST['ResNum'])) {
					update_post_meta($order_id, 'WC_EghtesadNovin_settleSaleOrderId', $_POST['ResNum']);
				}
				if (isset($_POST['RefNum'])) {
					update_post_meta($order_id, 'WC_EghtesadNovin_settleSaleReferenceId', $_POST['RefNum']);
				}

				// Update the transaction ID if available
				if ($transaction_id && ($transaction_id != 0)) {
					update_post_meta($order_id, '_transaction_id', $transaction_id);
				}

				// Mark the order as payment complete and empty the cart
				$order->payment_complete($transaction_id);
				$woocommerce->cart->empty_cart();

				// Add a note to the order
                $Note = sprintf(__('Payment was successful.<br/>Transaction Tracking Code (Reference Code): %s <br/> Transaction Request Number: %s', 'wc-pardakhtnovin'), $transaction_id, $SaleOrderId);
				$Note = apply_filters('WC_EghtesadNovin_Return_from_Gateway_Success_Note', $Note, $order_id, $transaction_id, $SaleOrderId);
				if ($Note) {
					$order->add_order_note($Note, 1);
				}

				// Trigger success action hooks and redirect to success page
				do_action('WC_EghtesadNovin_Return_from_Gateway_Success', $order_id, $transaction_id, $SaleOrderId);
				wp_redirect(add_query_arg('wc_status', 'success', $this->get_return_url($order)));
				exit;
			}

			/**
			 * Redirects the user to the WooCommerce cart page.
			 */
			private function redirect_to_cart() {
				global $woocommerce;

				// Redirect to the cart page
				wp_redirect($woocommerce->cart->get_checkout_url());
				exit;
			}

			/**
			 * Handles the case when the order ID is missing.
			 * Redirects to the cart page and displays an error notice.
			 */
			private function handle_missing_order_id($order_id) {
				global $woocommerce;

				// Create an error notice
                $fault = __('Order number does not exist.', 'wc-pardakhtnovin');
				$Notice = wpautop(wptexturize($this->failed_massage));
				$Notice = str_replace('{fault}', $fault, $Notice);
				$Notice = apply_filters('WC_EghtesadNovin_Return_from_Gateway_No_Order_ID_Notice', $Notice, $order_id, $fault);

				// Display the error notice if available
				if ($Notice) {
					wc_add_notice($Notice, 'error');
				}

				// Trigger action for handling missing order ID
				do_action('WC_EghtesadNovin_Return_from_Gateway_No_Order_ID', $order_id, $fault);

				// Redirect to the cart page
				wp_redirect($woocommerce->cart->get_checkout_url());
				exit;
			}

			/**
			 * Handles the return from EghtesadNovin gateway.
			 * Validates the transaction and redirects accordingly.
			 */
			public function Return_from_EghtesadNovin_Gateway_By_PardakhtNovin() {
				global $woocommerce;

				// Get the order ID from URL or session
				$order_id = isset($_GET['wc_order']) ? $_GET['wc_order'] : $woocommerce->session->order_id_eghtesadnovin;

				// Check if order ID is available
				if (!$order_id) {
					$this->handle_missing_order_id($order_id);
					return;
				}

				// Check the State parameter from the payment gateway
				if (isset($_POST['State']) && $_POST['State'] === 'OK') {
					$order = new WC_Order($order_id);

					// Initialize SOAP client
					$client = $this->initialize_soap_client($order, $order_id);
					if (!$client) {
						return;
					}

					// Perform merchant login
					$loginData = $this->perform_merchant_login($client, $order, $order_id);
					if (!$loginData) {
						return;
					}

					// Verify the merchant transaction
					$verifyResult = $this->verify_merchant_transaction($loginData, $client, $order, $order_id);
					if ($verifyResult === false) {
						return;
					}

					// Redirect to the success page
					$this->redirect_to_success_page($woocommerce, $order_id, $order, $_POST['RefNum'], $_POST['ResNum']);
				} else {
					// Redirect to the cart page
					$this->redirect_to_cart();
				}
			}
		}
	}
}
add_action('plugins_loaded', 'Load_PardakhtNovin_Gateway', 0);