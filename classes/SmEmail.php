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

class SmEmail extends Sm
{

    private static $campaignParams = array(
        'campaign_name',
        'campaign_subject',
        'campaign_contact_list',
        'campaign_sender',
        'campaign_content'
    );

    public static function processRequest()
    {
        
        if (Tools::getValue('do_refresh_sender')) {
            return self::refreshSender();
        }
        
        $emailConfig = self::getConfig('email');
        $pre = (isset($emailConfig['enable_email_sending']) && $emailConfig['enable_email_sending']);
        
        if ($pre && !Tools::getValue('enable_email_sending')) {

            return self::resetSMTPSettings();
        }
        
        if (!Tools::getValue('smtp_sender')) {
            self::$requestValue['enable_email_sending'] = 0;
            self::setNotification("error", self::l('No sender was set. We can\'t enable SMTP sending through sendmachine.'));
            return false;
        }

        $apiConfig = self::getConfig('api_data');

        if (!isset($apiConfig['smtp_settings'])) {
            self::setNotification("error", self::l('Api connecting failed.'));
            return false;
        }

        $smtpSettings = $apiConfig['smtp_settings'];

        $encryptionTypes = array(
            'off' => 'port',
            'ssl' => 'ssl_tls_port',
            'tls' => 'starttls_port'
        );

        $encryption = Tools::getValue('smtp_encryption');

        if (!in_array($encryption, array_keys($encryptionTypes))) {
            self::setNotification("error", self::l('Api connecting failed.'));
            return false;
        }

        $port = $smtpSettings[$encryptionTypes[$encryption]];

        Configuration::updateValue('PS_MAIL_METHOD', 2);
        Configuration::updateValue('PS_MAIL_TYPE', 1);
        
        Configuration::updateValue('PS_SHOP_EMAIL', Tools::getValue('smtp_sender'));

        Configuration::updateValue('PS_MAIL_DOMAIN', '');
        Configuration::updateValue('PS_MAIL_SERVER', $smtpSettings['hostname']);
        Configuration::updateValue('PS_MAIL_USER', $smtpSettings['username']);
        Configuration::updateValue('PS_MAIL_PASSWD', $smtpSettings['password']);
        Configuration::updateValue('PS_MAIL_SMTP_ENCRYPTION', $encryption);
        Configuration::updateValue('PS_MAIL_SMTP_PORT', $port);

        return true;
    }
    
    public static function refreshSender()
    {

        try {
            $senderAddresses = self::apiClient()->sender->get();
            if (!isset($senderAddresses['senderlist'])) {
                $senderAddresses = null;
            } else {
                $senderAddresses = $senderAddresses['senderlist'];
            }
        } catch (Exception $ex) {
            unset($ex);
            $senderAddresses = null;
        }

        $apiConfig = self::getConfig('api_data');

        $apiConfig['sender_addresses'] = $senderAddresses;

        self::addLog("sender_refreshed", "List cache refreshed.");
        self::setConfig('api_data', $apiConfig);

        self::setNotification("success", self::l("Sender cache successfully updated!"));
        
        return true;
    }

    public static function processMailTesting()
    {

        $emailConfig = self::getConfig('email');

        if (!isset($emailConfig['enable_email_sending']) || !$emailConfig['enable_email_sending']) {
            echo "email_sending_disabled";
        }
        
        $_data = array(
            'PS_SHOP_EMAIL',
            'PS_MAIL_SERVER',
            'PS_MAIL_USER',
            'PS_MAIL_PASSWD',
            'PS_MAIL_SMTP_ENCRYPTION',
            'PS_MAIL_SMTP_PORT'
        );

        $configuration = Configuration::getMultiple($_data);
        $to = Tools::getValue('testEmail');

        $subject = self::l("Prestashop test message");
        $content = self::buildTemplate("test_email", array("tab" => "email"));

        if (Mail::sendMailTest(
            true,
            $configuration['PS_MAIL_SERVER'],
            $content,
            $subject,
            'text/html',
            $to,
            $configuration['PS_SHOP_EMAIL'],
            $configuration['PS_MAIL_USER'],
            $configuration['PS_MAIL_PASSWD'],
            $configuration['PS_MAIL_SMTP_PORT'],
            $configuration['PS_MAIL_SMTP_ENCRYPTION']
        )) {
            echo "ok";
        } else {
            echo "general_error";
        }
    }

    public static function checkSender()
    {
        $generalConfig = self::getConfig('general');
        $emailConfig = self::getConfig('email');
        $apiConfig = self::getConfig('api_data');
        
        if (!$generalConfig || !isset($generalConfig['plugin_enabled']) || !$generalConfig['plugin_enabled']) {
            return "";
        }

        if (!$emailConfig || !isset($emailConfig['enable_email_sending']) || !$emailConfig['enable_email_sending']) {
            return "";
        }
        
        if (!Configuration::get('PS_SHOP_EMAIL')) {
            return "";
        }

        if (!isset($apiConfig['sender_addresses']) || !is_array($apiConfig['sender_addresses'])) {
            return self::buildTemplate("empty_sender", array("tab" => "email"));
        }

    }
    
    public static function saveDraft()
    {

        $params = Tools::getValue('params');

        foreach (self::$campaignParams as $v) {
            if (isset($params[$v])) {
                if ("campaign_content" == $v) {
                    $html = true;
                } else {
                    $html = false;
                }
                Configuration::updateValue('SM_' .  Tools::strtoupper($v), $params[$v], $html);
            }
        }

        echo "ok";
    }

    public static function launchCampaign()
    {

        $campaignId = self::saveCampaign(true);

        if ($campaignId) {

            try {
                $resp = self::apiClient()->campaigns->send($campaignId);
            } catch (Exception $ex) {
                unset($ex);
                echo self::l("An unexpected error occurred. Please try again later.");
                die;
            }

            if (!isset($resp['status'])) {
                echo self::l("An unexpected error occurred. Please try again later.");
                die;
            }

            if ("launched" != $resp['status']) {
                echo $resp['status'];
                die;
            }

            self::addLog("campaign_launched", "Campaign \"$campaignId\" was launched.");
            echo "ok";
            die;
        }

        echo self::l("An unexpected error occurred. Please try again later.");
        die;
    }

    public static function saveCampaign($returnCampaignId = false)
    {

        $name = Configuration::get('SM_CAMPAIGN_NAME');
        $subject = Configuration::get('SM_CAMPAIGN_SUBJECT');
        $contactList = Configuration::get('SM_CAMPAIGN_CONTACT_LIST');
        $sender = Configuration::get('SM_CAMPAIGN_SENDER');
        $decoded_content = html_entity_decode(urldecode(Configuration::get('SM_CAMPAIGN_CONTENT')));
        $content = self::parseTplContent($decoded_content);

        if (!$name || !$subject || !$contactList || !$sender) {
            echo self::l("Required fields are not filled in.");
            die;
        }

        try {
            $resp = self::apiClient()->campaigns->create(array(
                'name' => $name,
                'subject' => $subject,
                'contactlist_id' => $contactList,
                'sender_email' => $sender
            ));
        } catch (Exeption $ex) {
            unset($ex);
            echo self::l("An unexpected error occurred. Please try again later.");
            die;
        }

        if (!isset($resp['status'])) {
            echo self::l("An unexpected error occurred. Please try again later.");
            die;
        }

        if ("created" != $resp['status']) {
            echo $resp['status'];
            die;
        }

        try {
            $resp2 = self::apiClient()->campaigns->update($resp['id'], array('body_html' => $content));
        } catch (Exception $ex) {
            unset($ex);
            echo self::l("An unexpected error occurred. Please try again later.");
            die;
        }

        if (!isset($resp2['status'])) {
            echo self::l("An unexpected error occurred. Please try again later.");
            die;
        }

        if ("saved" != $resp2['status']) {
            echo $resp['status'];
            die;
        }

        if ($returnCampaignId) {
            return $resp['id'];
        } else {
            self::addLog("campaign_saved", "Campaign \"" . $resp['id'] . "\" was saved.");
            echo "ok";
            die;
        }
    }

    public static function previewCampaign()
    {

        $defaults = self::defaults();

        $content = Configuration::get('SM_CAMPAIGN_CONTENT') ?
                Configuration::get('SM_CAMPAIGN_CONTENT') :
                $defaults['campaign']['tpl_content'];
        echo self::parseTplContent(html_entity_decode(urldecode($content)));
        die;
    }

    private static function parseTplContent($content = "")
    {

        $rule = "/\|\|REPEAT_PRODUCT\[(\d*?)\]\[(\d*?)\]\|\|([\s\S]*?)\|\|\\\REPEAT_PRODUCT\|\|/i";
        $baseMacros = array(
            "SITENAME" => Configuration::get('PS_SHOP_NAME'),
            "SITEURL" => Configuration::get('PS_SHOP_URL'),
            "ADMINEMAIL" => Configuration::get('PS_SHOP_EMAIL'),
        );

        $_content = preg_replace_callback($rule, array('SmEmail', 'parseTplReplaceCallback'), $content);
        
        return sprintf(self::buildTemplate("campaign_parsed", array("tab" => "email")), self::doParseMacros($_content, $baseMacros));
    }

    private static function parseTplReplaceCallback($match)
    {

        if (!$match || !isset($match[1]) || !isset($match[2]) || !isset($match[3])) {
            return "";
        }

        $limit = 20;

        $limit = (int) $match[1];
        $category = (int) $match[2];
        $content = $match[3];

        $products = Product::getProducts(self::getContext()->language->id, 0, $limit, 'id_product', 'DESC', $category);

        $toRet = "";

        foreach ($products as $v) {

            $linkObj = new Link();
            $link = $linkObj->getProductLink($v['id_product']);
            $image = Product::getCover((int) $v['id_product']);
            $scheme = $_SERVER['REQUEST_SCHEME'] . '://';
            $imgLink = $scheme . $linkObj->getImageLink($v['link_rewrite'], $image['id_image']);
            $qty = Product::getQuantity($v['id_product']);
            $_round = round(Product::getPriceStatic($v['id_product']), 2);
            $price = $_round . " " . self::getContext()->currency->name;

            $macros = array(
                'PRODUCT_TAX' => $v['tax_name'],
                'PRODUCT_IMG' => $imgLink,
                'PRODUCT_TITLE' => $v['name'],
                'PRODUCT_PRICE' => $price,
                'PRODUCT_QTY' => $qty,
                'PRODUCT_SUPPLIER_NAME' => $v['supplier_name'],
                'PRODUCT_MANUFACTURER_NAME' => $v['manufacturer_name'],
                'PRODUCT_URL' => $link,
                'PRODUCT_DESCRIPTION' => $v['description_short'],
                'PRODUCT_AVAILABILITY' => $v['available_now']
            );
            $toRet .= self::doParseMacros($content, $macros);
        }

        return $toRet;
    }

    private static function doParseMacros($content = "", $macros = array())
    {

        foreach ($macros as $k => $v) {
            $content = preg_replace("/(\|\|$k\|\|)/i", $v, $content);
        }
        return $content;
    }

    public static function generateForm()
    {
        
        $emailConfig = self::getConfig('email');
        $generateTestBox = $emailConfig['enable_email_sending'];

        $toret = array(
            array(
                'form' => array(
                    'legend' => array(
                        'title' => self::l('Email'),
                        'icon' => 'icon-envelope',
                    ),
                    'input' => array(
                        array(
                            'type' => 'hidden',
                            'name' => 'do_refresh_sender',
                        ),
                        array(
                            'type' => 'switch',
                            'label' => self::l('Enable email sending through Sendmachine?'),
                            'name' => 'enable_email_sending',
                            'values' => array(
                                array(
                                    'id' => 'email_sending_on',
                                    'value' => 1,
                                    'label' => self::l('Yes'),
                                ),
                                array(
                                    'id' => 'email_sending_off',
                                    'value' => 0,
                                    'label' => self::l('No'),
                                ),
                            ),
                        ),
                        array(
                            'type' => 'radio',
                            'label' => self::l('SMTP Encryption'),
                            'name' => 'smtp_encryption',
                            'values' => array(
                                array(
                                    'id' => 'smtp_encryption_open',
                                    'value' => 'off',
                                    'label' => self::l('No encryption'),
                                    'checked' => 'checked',
                                ),
                                array(
                                    'id' => 'smtp_encryption_ssl',
                                    'value' => 'ssl',
                                    'label' => self::l('SSL encryption'),
                                ),
                                array(
                                    'id' => 'smtp_encryption_tls',
                                    'value' => 'tls',
                                    'label' => self::l('TLS encryption'),
                                ),
                            ),
                        ),
                        array(
                            'type' => 'select',
                            'label' => self::l('Sender address'),
                            'name' => 'smtp_sender',
                            'class' => 'fixed-width-xl',
                            'options' => array(
                                'query' => self::genSenderDropdown(),
                                'id' => 'sender_list_option',
                                'name' => 'name'
                            ),
                            'desc' => self::checkSender(),
                        ),
                    ),
                    'submit' => array(
                        'title' => self::l('Save'),
                        'icon' => 'process-icon-save',
                        'class' => 'btn btn-default pull-right smFormEmailTab',
                    ),
                ),
            ),
        );
        
        if ($generateTestBox) {
            
            array_push($toret, array(
                'form' => array(
                    'legend' => array(
                        'title' => self::l('Test Email Configuration'),
                        'icon' => 'icon-puzzle-piece',
                    ),
                    'input' => array(
                        array(
                            'type' => 'text',
                            'label' => self::l('Email to'),
                            'name' => 'send_test_to',
                            'desc' => self::buildTemplate('testmail_notify', array("tab" => "email")),
                            'class' => 'preventFormSubmit'
                        ),
                        array(
                            'type' => 'hidden',
                            'name' => 'request_location',
                        ),
                    ),
                    'submit' => array(
                        'title' => self::l('Send a test email'),
                        'icon' => 'process-icon-refresh',
                        'class' => 'btn btn-default pull-right verifEmailSendmachine',
                    ),
                ),
            ));
        }

        return $toret;
    }

    public static function generateConfigureCampaign()
    {

        $defaults = self::defaults();

        $tplContent = Configuration::get('SM_CAMPAIGN_CONTENT') ?
                html_entity_decode(urldecode(Configuration::get('SM_CAMPAIGN_CONTENT'))) :
                $defaults['campaign']['tpl_content'];

        $formData = array(
            'campaign_name' => Configuration::get('SM_CAMPAIGN_NAME'),
            'campaign_subject' => Configuration::get('SM_CAMPAIGN_SUBJECT'),
            'campaign_contact_list' => Configuration::get('SM_CAMPAIGN_CONTACT_LIST'),
            'campaign_sender' => Configuration::get('SM_CAMPAIGN_SENDER'),
            'campaign_content' => $tplContent,
        );
                
        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->default_form_language = self::getContext()->language->id;
        $helper->tpl_vars = array(
            'fields_value' => $formData,
            'languages' => self::getContext()->controller->getLanguages(),
        );

        $fields_form = array(
            array(
                "form" => array(
                    'tinymce' => true,
                    'input' => array(
                        array(
                            'type' => 'text',
                            'label' => self::l('Campaign name'),
                            'name' => 'campaign_name'
                        ),
                        array(
                            'type' => 'text',
                            'label' => self::l('Campaign subject'),
                            'name' => 'campaign_subject'
                        ),
                        array(
                            'type' => 'select',
                            'label' => self::l('Contact list'),
                            'name' => 'campaign_contact_list',
                            'class' => 'force_full_width',
                            'options' => array(
                                'query' => self::genListDropdown(true),
                                'id' => 'subsctiption_list_option',
                                'name' => 'name'
                            ),
                        ),
                        array(
                            'type' => 'select',
                            'label' => self::l('Sender address'),
                            'name' => 'campaign_sender',
                            'class' => 'force_full_width',
                            'options' => array(
                                'query' => self::genSenderDropdown(true),
                                'id' => 'sender_list_option',
                                'name' => 'name'
                            ),
                        ),
                        array(
                            'type' => 'textarea',
                            'label' => self::l('Template content'),
                            'name' => 'campaign_content',
                            'autoload_rte' => true,
                            'desc' => self::buildTemplate('campaign_description', array("tab" => "email"))
                        ),
                    ),
                )
            )
        );

        $vars = array(
            "admin_token" => Tools::getAdminTokenLite('AdminModules'),
        );

        $content = sprintf(self::buildTemplate("campaign", $vars), $helper->generateForm($fields_form));

        $toRet = self::generateModal(
            'configureCampaignModal',
            'large',
            self::l('Send campaign'),
            $content,
            false,
            false
        );

        return $toRet;
    }

    private static function genSenderDropdown($emptyItem = false)
    {

        $values = array();

        if ($emptyItem) {
            array_push($values, array('sender_list_option' => "", 'name' => ""));
        }

        $apiConfig = self::getConfig('api_data');

        if ($apiConfig && isset($apiConfig['sender_addresses'])) {

            if ($apiConfig['sender_addresses']) {

                foreach ($apiConfig['sender_addresses'] as $v) {
                    array_push($values, array('sender_list_option' => $v['email'], 'name' => $v['email']));
                }
            }
        }

        return $values;
    }
}
