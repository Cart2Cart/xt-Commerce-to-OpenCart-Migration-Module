<?php
class ControllerModuleC2c extends Controller {
	private $error = array();

	public function index() {
		$this->language->load('module/c2c');

		$this->document->setTitle($this->language->get('heading_title'));
		$this->document->addStyle('view/stylesheet/cart2cart/c2c.css');
        $this->document->addStyle('http://netdna.bootstrapcdn.com/font-awesome/4.0.3/css/font-awesome.css');
		$this->document->addScript('view/javascript/cart2cart/md5-min.js');
		$this->document->addScript('view/javascript/cart2cart/c2c.js');

		$this->load->model('cart2cart/worker');

		$worker =  $this->model_cart2cart_worker;
		
		$settings = $this->getSettings();

		$showButton = 'install';
		if ($worker->isBridgeExist()){
			$showButton = 'uninstall';
		}
		$this->data['showButton'] = $showButton;
		 $loginStatus = $settings['Cart2CartLoginStatus'];

		if ($loginStatus == ''){
			$loginStatus = 'No';
		}
		$this->data['loginStatus'] = $loginStatus;
		$this->data['cartName'] = $this->language->get('cartName');
		$this->data['sourceCartName'] = $sourceCartName = $this->language->get('sourceCartName');
        $this->data['sourceCartNameImg'] = $sourceCartNameImg = $this->language->get('sourceCartNameImg');
        $this->data['sourceCartNameLink'] = $sourceCartNameLink = $this->language->get('sourceCartNameLink');
		$this->data['storeToken'] =  $settings['Cart2CartStoreToken'];
		$this->data['cart2cartRemoteHost'] =  $settings['Cart2cartRemoteHost'];
		$this->data['cart2cartRemoteUsername'] =  $settings['Cart2cartRemoteUsername'];
		$this->data['cart2cartRemoteDirectory'] =  $settings['Cart2cartRemoteDirectory'];
		$this->data['cart2CartLoginEmail'] =  $settings['Cart2CartLoginEmail'];
		$this->data['cart2CartLoginKey'] =  $settings['Cart2CartLoginKey'];
        $this->data['sourceCartLogo'] = 'http://www.shopping-cart-migration.com/images/stories/'.strtolower($sourceCartNameImg).'.gif';
		$this->data['banner'] = $this->language->get('banner');
		$this->data['cart2cart_logo'] = $this->language->get('cart2cart_logo');
		$this->data['referer_text'] = $this->language->get('referer_text');

		$this->data['breadcrumbs'] = array();

		$this->data['breadcrumbs'][] = array(
			'text'      => $this->language->get('text_home'),
			'href'      => $this->url->link('common/home', 'token=' . $this->session->data['token'], 'SSL'),
			'separator' => false
		);

		$this->data['breadcrumbs'][] = array(
			'text'      => $this->language->get('text_module'),
			'href'      => $this->url->link('extension/module', 'token=' . $this->session->data['token'], 'SSL'),
			'separator' => ' :: '
		);

		$this->data['breadcrumbs'][] = array(
			'text'      => $this->language->get('heading_title'),
			'href'      => $this->url->link('module/c2c', 'token=' . $this->session->data['token'], 'SSL'),
			'separator' => ' :: '
		);

		$this->data['cancel'] = $this->url->link('extension/module', 'token=' . $this->session->data['token'], 'SSL');

		$this->data['heading_title'] = $this->language->get('heading_title');
		$this->data['button_cancel'] = $this->language->get('button_cancel');
		$this->data['text_installed'] = $this->language->get('text_installed');

		$this->template = 'module/c2c.tpl';
		$this->children = array(
			'common/header',
			'common/footer'
		);

		$this->response->setOutput($this->render());
	}

	protected function validate() {
		if (!$this->user->hasPermission('modify', 'module/c2c')) {
			$this->error['warning'] = $this->language->get('error_permission');
		}

		if (!$this->error) {
			return true;
		} else {
			return false;
		}
	}
	
	public function saveToken(){
		$settings = $this->getSettings();
		$settings['Cart2CartStoreToken'] = $_REQUEST['c2c_token'];
		$this->setSettings($settings);
	}

	public function saveFtp(){
		$this->load->model('cart2cart/ftpUpload');
		$settings = $this->getSettings();
		$settings['Cart2cartRemoteHost'] = $_REQUEST['host'];
		$settings['Cart2cartRemoteUsername'] = $_REQUEST['user'];
		$settings['Cart2cartRemoteDirectory'] = $_REQUEST['dir'];
		$this->setSettings($settings);
		
		set_error_handler(array($this, 'warning_handler'), E_WARNING);

		$c2cFtpUpload = $this->model_cart2cart_ftpUpload;
		if (
			$c2cFtpUpload->init(
				$_REQUEST['host'],
				$_REQUEST['user'],
				$_REQUEST['pass'],
				$_REQUEST['dir'],
				$settings['Cart2CartStoreToken'])
		){
			$c2cFtpUpload->uploadBridge();
		}

		echo json_encode(array(
			'messages' => $c2cFtpUpload->messages,
			'messageType' => $c2cFtpUpload->messageType
			));

	}

	public function installBridge(){
		$settings = $this->getSettings();
		$this->load->model('cart2cart/worker');
		$worker =  $this->model_cart2cart_worker;
		$worker->installBridge();
		$worker->updateToken($settings['Cart2CartStoreToken']);
	}

	public function removeBridge(){
		$this->load->model('cart2cart/worker');
		$worker =  $this->model_cart2cart_worker;
		$worker->unInstallBridge();
	}

	public function saveLoginStatus(){
		$settings = $this->getSettings();
		$settings['Cart2CartLoginStatus']	= $_REQUEST['status'];
		$settings['Cart2CartLoginEmail']	= $_REQUEST['email'];
		$settings['Cart2CartLoginKey']		= $_REQUEST['encPass'];
		echo 'set status ' . $_REQUEST['status'];
		$this->setSettings($settings);
	}

	protected function getSettings(){
		$this->load->model('setting/setting');
		$settings = $this->model_setting_setting->getSetting('cart2cart');
		if(count($settings) == 0){
			$settings = $this->clearFtpInfo();
		}
		return $settings;
	}
	
	public function clearFtpInfo(){
		$this->load->model('setting/setting');
		$settings = array(
			'Cart2CartStoreToken' 		=> '',
			'Cart2cartRemoteHost' 		=> '',
			'Cart2cartRemoteUsername'	=> '',
			'Cart2cartRemoteDirectory' 	=>'',
			'Cart2CartLoginStatus'		=> 'No',
			'Cart2CartLoginEmail'		=> '',
			'Cart2CartLoginKey'		=> ''
			);
		$this->model_setting_setting->editSetting('cart2cart', $settings);
		return $settings;
	}

	protected function setSettings($settings){
		$this->load->model('setting/setting');
		$this->model_setting_setting->editSetting('cart2cart', $settings);
	}
	static function warning_handler($errno, $errstr) { 
		//echo "error handled $errstr";
		// need to suppress  ftp warnings
	}
}