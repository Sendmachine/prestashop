<?php
/**
 * 2007-2016 PrestaShop
 *
 * NOTICE OF LICENSE
 *
 * This source file is subject to the Academic Free License (AFL 3.0)
 * that is bundled with this package in the file LICENSE.txt.
 * It is also available through the world-wide-web at this URL:
 * http://opensource.org/licenses/afl-3.0.php
 * If you did not receive a copy of the license and are unable to
 * obtain it through the world-wide-web, please send an email
 * to license@prestashop.com so we can send you a copy immediately.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 * @author    PrestaShop SA <contact@prestashop.com>
 * @copyright 2007-2016 PrestaShop SA
 * @license   http://opensource.org/licenses/afl-3.0.php Academic Free License (AFL 3.0)
 * International Registered Trademark & Property of PrestaShop SA
 */

class Sm
{

    private static $notifications = array();
    private static $config = null;
    private static $instance = null;
    private static $context = null;
    private static $_nlClass = null;
    private static $_nlDB = null;
    protected static $apiClient = null;
    protected static $requestValue = null;

    const SM_DOMAIN = "sendmachine";

    public static function defaults()
    {

        return array(
            'general' => array(
                'plugin_enabled' => 0,
                'api_username' => '',
                'api_password' => '',
            ),
            'list' => array(
                'enable_list_subscription' => 1,
                'subsctiption_list' => '',
                'double_optin' => 0,
                'confirmation_email' => 0,
                'welcome_voucher' => ''
            ),
            'email' => array(
                'enable_email_sending' => 0,
                'smtp_encryption' => 'off',
                'smtp_sender' => ''
            ),
            'api_config' => array(
                'contact_lists' => null,
                'smtp_settings' => null,
                'sender_addresses' => null
            ),
            'campaign' => array(
                'tpl_content' => self::buildTemplate("campaign_defaults", array("tab" => "general")),
            )
        );
    }

    public static function getConfig($key = null)
    {

        if (!self::$config) {
            self::$config = Tools::jsonDecode(Configuration::get(self::SM_DOMAIN), true);
        }

        if ($key) {
            return isset(self::$config[$key]) ? self::$config[$key] : null;
        }

        return self::$config;
    }

    public static function setConfig($key = null, $val = null)
    {

        if (!self::$config) {
            self::getConfig();
        }

        if ($val) {
            self::$config[$key] = $val;
        } else {
            self::$config = $key;
        }

        return Configuration::updateValue(self::SM_DOMAIN, Tools::jsonEncode(self::$config));
    }

    public static function pluginEnabled()
    {

        $config = self::getConfig('general');

        return isset($config['plugin_enabled']) ? (bool) $config['plugin_enabled'] : false;
    }

    public static function l($string = "", $specific = "")
    {

        return self::getInstance()->l($string, $specific);
    }

    public static function getValue($key = "", $default = null)
    {
        if (isset(Sm::$requestValue[$key])) {
            return Sm::$requestValue[$key];
        }

        return Tools::getValue($key, $default);
    }

    public static function apiClient($params = array())
    {

        if (!self::$apiClient) {

            require_once _PS_MODULE_DIR_ . 'sendmachine/classes/api/SendmachineApiClient.php';

            $api_username = "";
            $api_password = "";

            if (isset($params['api_username']) && isset($params['api_password'])) {

                $api_username = $params['api_username'];
                $api_password = $params['api_password'];
            } else {

                $config = self::getConfig('general');

                $api_username = $config['api_username'];
                $api_password = $config['api_password'];
            }

            self::$apiClient = new SendmachineApiClient($api_username, $api_password);
        }

        return self::$apiClient;
    }

    public static function getNotifications()
    {

        return self::$notifications;
    }

    public static function setNotification($type = "", $notification = "")
    {

        array_push(self::$notifications, array('type' => $type, 'message' => $notification));

        return true;
    }

    public static function generateModal(
        $id = '',
        $size = 'normal',
        $title = '',
        $content = '',
        $cancelButtonLabel = '',
        $confirmButtonLabel = ''
    ) {

        $modalSizes = array(
            'small' => "width: 300px",
            'normal' => "",
            'large' => "width: 900px"
        );

        $modalSize = isset($modalSizes[$size]) ? $modalSizes[$size] : $modalSizes['normal'];

        $vars = array(
            'id' => $id,
            'modalSize' => $modalSize,
            'title' => $title,
            'cancelButtonLabel' => $cancelButtonLabel,
            'confirmButtonLabel' => $confirmButtonLabel
        );
        
        return sprintf(self::buildTemplate('modal', $vars), $content);
    }

    public static function resetConfiguration()
    {

        self::resetSMTPSettings();
        self::resetNewsletterSettings();
        self::resetCampaignSettings();
        self::setConfig(self::defaults());

        return true;
    }

    protected static function resetSMTPSettings()
    {

        Configuration::updateValue('PS_MAIL_METHOD', 1);
        Configuration::updateValue('PS_MAIL_TYPE', 3);

        Configuration::updateValue('PS_MAIL_DOMAIN', '');
        Configuration::updateValue('PS_MAIL_SERVER', '');
        Configuration::updateValue('PS_MAIL_USER', '');
        Configuration::updateValue('PS_MAIL_PASSWD', '');
        Configuration::updateValue('PS_MAIL_SMTP_ENCRYPTION', '');
        Configuration::updateValue('PS_MAIL_SMTP_PORT', '');

        return true;
    }

    protected static function resetNewsletterSettings()
    {

        Configuration::updateValue('NW_CONFIRMATION_EMAIL', 0);
        Configuration::updateValue('NW_VERIFICATION_EMAIL', 0);
        Configuration::updateValue('NW_VOUCHER_CODE', '');

        return true;
    }

    protected static function resetCampaignSettings()
    {

        Configuration::updateValue('SM_CAMPAIGN_NAME', '');
        Configuration::updateValue('SM_CAMPAIGN_SUBJECT', '');
        Configuration::updateValue('SM_CAMPAIGN_CONTACT_LIST', '');
        Configuration::updateValue('SM_CAMPAIGN_SENDER', '');
        Configuration::updateValue('SM_CAMPAIGN_CONTENT', '');

        return true;
    }

    protected static function genListDropdown($emptyItem = false)
    {

        $values = array();
        $listConfig = self::getConfig('list');
        $selectedList = isset($listConfig['subsctiption_list']) ? $listConfig['subsctiption_list'] : null;

        if (!$selectedList || $emptyItem) {
            array_push($values, array('subsctiption_list_option' => "", 'name' => ""));
        }

        $apiConfig = self::getConfig('api_data');

        if ($apiConfig && isset($apiConfig['contact_lists'])) {

            if ($apiConfig['contact_lists']) {

                foreach ($apiConfig['contact_lists'] as $v) {
                    $_arr = array(
                        'subsctiption_list_option' => $v['list_id'],
                        'name' => $v['name'] . " (" . $v['subscribed'] . ")"
                    );
                    array_push($values, $_arr);
                }
            }
        }

        return $values;
    }

    protected static function addLog($category = "", $data = "")
    {

        $content = Tools::jsonDecode(Configuration::get('SM_ACTIVITY_LOG'), true);
        $date = date("Y-m-d H:i");
        $rand = rand();
        $content[$category][] = "[$date] #$rand - " . $data;

        Configuration::updateValue('SM_ACTIVITY_LOG', Tools::jsonEncode($content));
    }

    public static function getLog($category = null)
    {

        $content = Tools::jsonDecode(Configuration::get('SM_ACTIVITY_LOG'), true);

        return ($category && isset($content[$category])) ? $content[$category] : $content;
    }

    public static function generateLang()
    {

        $lang = array(
            'testmailErrorAddress' => self::l('This email address is not valid.'),
            'testmailErrorGeneral' => self::l('Error: Please check your configuration.'),
            'testmailErrorNotConfigured' => self::l('Error: You did not enabled email sending through Sendmachine.'),
            'testmailSuccess' => self::l('A test email has been sent to the email address you provided.'),
            'logsButton' => self::l('Logs'),
            'configureCampaignButton' => self::l('Configure Campaign'),
            'exportButton' => self::l('Export'),
            'refreshListsButton' => self::l('Refresh lists'),
            'CampaignLaunchConfirm' => self::l('You are about to launch this campaign. Are you sure you want to do this?'),
            'campaignLaunchSuccess' => self::l('Campaign launched successfully.'),
            'unexpectedError' => self::l('An unexpected error occurred. Try again later.'),
            'campaignSaveSuccess' => self::l('Campaign saved successfully to your Sendmachine account.'),
            'campaignSaveConfirm' => self::l('You are about to save this campaign to your Sendmachine account. Are you sure you want to do this?'),
            'draftSaveSuccess' => self::l('Content saved successfully.'),
            'refreshSenderButton' => self::l('Refresh sender list'),
        );
        
        $vars = array("lang" => Tools::jsonEncode($lang));

        return self::buildTemplate('translations', $vars);
    }

    protected static function getInstance()
    {

        if (!self::$instance) {
            self::$instance = Module::getInstanceByName('sendmachine');
        }

        return self::$instance;
    }

    protected static function getContext()
    {

        if (!self::$context) {
            self::$context = Context::getContext();
        }

        return self::$context;
    }
    
    public static function buildTemplate($tpl = null, $vars = array())
    {
        return self::getContext()->smarty->fetch(_PS_MODULE_DIR_."sendmachine/views/templates/admin/$tpl.tpl", $vars);
    }
    
    public static function nlClass()
    {
        if (!self::$_nlClass) {
            if (version_compare(_PS_VERSION_, '1.7', '<')) {
                self::$_nlClass = "blocknewsletter";
            } else {
                self::$_nlClass = "ps_emailsubscription";
            }
        }

        return self::$_nlClass;
    }

    public static function nlDB()
    {
        if (!self::$_nlDB) {
            if (version_compare(_PS_VERSION_, '1.7', '<')) {
                self::$_nlDB = "newsletter";
            } else {
                self::$_nlDB = "emailsubscription";
            }
        }

        return self::$_nlDB;
    }
}
