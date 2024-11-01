<?php
/*
	Plugin Name: Add To Cart redirect for WooCommerce
	Plugin URI: https://softsprint.net/add-to-cart-redirect-for-woocommerce/
	Description: Plugin sets a redirect link to the WooCommerce "Add To Cart" button
	Author: SoftSprint
	Version: 1.0.2
	Author URI: https://softsprint.net/
*/

add_filter('woocommerce_loop_add_to_cart_link', 'atcrfw_custom_product_link');
function atcrfw_custom_product_link()
{
	global $post;
	global $product;
	if (isset($post->ID)) {
		$link = get_post_meta($post->ID, 'links_for_sold');
		if (isset($link[0])) {
			$link = json_decode($link[0], true);
		}
		if (!empty($link)) {
			$new_tab = get_post_meta($post->ID, 'new_tab', true);
			if ($new_tab) {
				$target = "_blank";
			} else {
				$target = "_self";
			}
			echo '<a href="' . esc_url($link) . '" target="' . $target . '" class="button product_type_simple add_to_cart_button">'.$product->add_to_cart_text().'</a>';
		} else {
			$current_link = sprintf(
				'<a href="%s" data-quantity="%s" class="%s" %s>%s</a>',
				esc_url($product->add_to_cart_url()),
				esc_attr(isset($args['quantity']) ? $args['quantity'] : 1),
				esc_attr(isset($args['class']) ? $args['class'] : 'button'),
				isset($args['attributes']) ? wc_implode_html_attributes($args['attributes']) : '',
				esc_html($product->add_to_cart_text())
			);

			echo $current_link;
		}
	}
}

add_action('woocommerce_single_product_summary','atcrfw_single_product_button',15);
function atcrfw_single_product_button()
{

	global $post;

	if (isset($post->ID)) {
		$link = get_post_meta($post->ID, 'links_for_sold');
		if (isset($link[0])) {
			$link = json_decode($link[0], true);
		}
		if (!empty($link)) {
			$new_tab = get_post_meta($post->ID, 'new_tab', true);
			if ($new_tab) {
				$target = "_blank";
			} else {
				$target = "_self";
			}
			echo '<script>var link_redirect_after_add_to_cart = "' . $link . '"; var target_for_add_to_cart_redirect_link = "'.$target.'"</script>';
		}else{
			echo '<script>var link_redirect_after_add_to_cart = "";</script>';
		}
	}
}


class ATCRFW_WooProductLinks
{
	public $DS;
	public $templateDir;
	public $metaNameLinksForSold;
	public $metaNameSoldLinks;
	public $metaNameNewTab;
	public $delimiter;
	public $orderSoldLinkMetaName;
	public $adminOrderColumnName;

	public static $instance = Null;
	public static $currentOrderId = 0;
	public static $currentOrderSoldLinks = array();


	public static function getInstance()
	{
		if (!self::$instance)
			self::$instance = new self();
		return self::$instance;
	}

	public function __construct()
	{

		self::$currentOrderId = 0;
		self::$currentOrderSoldLinks = array();

		$this->DS = DIRECTORY_SEPARATOR;
		$this->templateDir = dirname(__FILE__) . $this->DS . 'templates' . $this->DS;
		$this->metaNameLinksForSold = 'links_for_sold';
		$this->metaNameSoldLinks = 'sold_links';
		$this->metaNameNewTab = 'new_tab';
		$this->orderSoldLinkMetaName = 'order_sold_link';

		$this->adminOrderColumnName = __('Link', 'woocommerce');


		add_action('admin_enqueue_scripts', array($this, 'includeAdminStyleAndScript'));
		add_action('wp_enqueue_scripts', array($this, 'includeTarasStyleAndScript'));


		add_filter('woocommerce_product_data_tabs', array($this, 'createProductDataTab'));
		add_filter('woocommerce_product_data_panels', array($this, 'productLinkTabContent'));

		add_action('woocommerce_process_product_meta_simple', array($this, 'saveProductLinks'));
		add_action('woocommerce_process_product_meta_variable', array($this, 'saveProductLinks'));

		add_action('woocommerce_checkout_order_processed', array($this, 'createNewOrder'), PHP_INT_MAX);

		add_action('woocommerce_thankyou', array($this, 'autoCompetedStatus'));
	}


	public function includeAdminStyleAndScript()
	{
		wp_enqueue_script('woo-pl-admin', plugin_dir_url(__FILE__) . '/assets/js/admin.js');
		wp_enqueue_style('woo-pl-admin', plugin_dir_url(__FILE__) . '/assets/css/admin.css');
	}
	public function includeTarasStyleAndScript()
	{
		wp_enqueue_script('woo-pl-admin', plugin_dir_url(__FILE__) . '/assets/js/admin.js');
	}


	public function autoCompetedStatus($order_id)
	{
		if (!$order_id) {
			return;
		}
		$order = wc_get_order($order_id);
		$order->update_status('completed');
	}

	public function createNewOrder($order_id)
	{
		$order = new WC_Order($order_id);
		$orderData = $order->get_data();
		foreach ($order->get_items() as $orderItem) {
			$productId = $orderItem->get_product_id();
			$links = get_post_meta($productId, $this->metaNameLinksForSold);
			if (!empty($links) && isset($links[0]) && !empty($links[0])) {
				$linksArray = json_decode($links[0], true);
				if (!empty($linksArray)) {
					$soldLink = array_shift($linksArray);
					$orderSoldLinks = get_post_meta($order_id, $this->orderSoldLinkMetaName);
					$orderSoldLinkForSafe = array();
					if (!empty($orderSoldLinks) && isset($orderSoldLinks[0]) && !empty($orderSoldLinks[0])) {
						$orderSoldLinkForSafe = json_decode($orderSoldLinks[0], true);
					}
					if (!isset($orderSoldLinkForSafe[$productId])) {
						$orderSoldLinkForSafe[$productId] = $soldLink;
						$orderSoldLinkForSafeStr = json_encode($orderSoldLinkForSafe);
						delete_post_meta($order_id, $this->orderSoldLinkMetaName);
						update_post_meta($order_id, $this->orderSoldLinkMetaName, $orderSoldLinkForSafeStr);
						$linksStr = json_encode($linksArray);
						delete_post_meta($productId, $this->metaNameLinksForSold);
						update_post_meta($productId, $this->metaNameLinksForSold, $linksStr);
						$soldProductLinks = array();
						$soldLinksFromProduct = get_post_meta($productId, $this->metaNameSoldLinks);
						if (!empty($soldLinksFromProduct) && isset($soldLinksFromProduct[0]) && !empty($soldLinksFromProduct[0])) {
							$soldProductLinks = json_decode($soldLinksFromProduct[0], true);
						}
						$soldProductLinks[$soldLink] = array('link' => $soldLink, 'date' => date('d/m/Y H:i'), 'email' => $orderData['billing']['email'], 'status' => __('Sold', 'woocommerce'));
						$soldProductLinksStr = json_encode($soldProductLinks);
						delete_post_meta($productId, $this->metaNameSoldLinks);
						update_post_meta($productId, $this->metaNameSoldLinks, $soldProductLinksStr);
					}
				}
			}
		}
	}


	public function createProductDataTab($tabs)
	{
		$tabs['productLinks'] = array(
			'label'		=> __('Product links', 'woocommerce'),
			'target'	=> 'product_link_tab',
			'class'		=> array('show_if_simple', 'show_if_variable'),
		);
		return $tabs;
	}


	public function saveProductLinks($post_id)
	{
		if (isset($_POST['product_link'])) {
			$links = esc_url_raw($_POST['product_link']);
			$linksForSave = $links;
			$linksStr = json_encode($linksForSave);
			delete_post_meta($post_id, $this->metaNameLinksForSold);
			update_post_meta($post_id, $this->metaNameLinksForSold, $linksStr);
		}

		if (isset($_POST['new_tab'])) {
			$new_tab = sanitize_text_field($_POST['new_tab']);
			update_post_meta($post_id, $this->metaNameNewTab, $new_tab);
		} else {
			update_post_meta($post_id, $this->metaNameNewTab, 0);
		}
	}

	public function productLinkTabContent()
	{
		global $post;

		$linksForSold = '';
		if (isset($post->ID)) {
			$linksArray = array();
			$links = get_post_meta($post->ID, $this->metaNameLinksForSold);
			if (!empty($links) && isset($links[0]) && !empty($links[0])) {
				$linksArray = json_decode($links[0], true);
				$linksForSold = $linksArray;
			}

			$new_tab = get_post_meta($post->ID, $this->metaNameNewTab, true);
		}
		include($this->templateDir . 'admin/product-link-tab.php');
	}
}

add_action('init', 'atcrfw_createWooProductLinksObject');
function atcrfw_createWooProductLinksObject()
{
	$GLOBALS['atcrfw_product_links'] = ATCRFW_WooProductLinks::getInstance();
}

if (!function_exists('atcrfw_debug')) {
	function atcrfw_debug($var = Null, $exit = true)
	{
		echo '<pre>';
		var_dump($var);
		echo '</pre>';

		if ($exit)
			exit();
	}
}
