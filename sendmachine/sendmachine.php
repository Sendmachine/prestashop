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

if (!defined('_PS_VERSION_')) {
    exit;
}

require_once _PS_MODULE_DIR_ . 'sendmachine/classes/Sm.php';
require_once _PS_MODULE_DIR_ . 'sendmachine/classes/SmGeneral.php';
require_once _PS_MODULE_DIR_ . 'sendmachine/classes/SmList.php';
require_once _PS_MODULE_DIR_ . 'sendmachine/classes/SmEmail.php';

class Sendmachine extends Module
{

    public function __construct()
    {

        $this->name = 'sendmachine';
        $this->tab = 'emailing';
        $this->version = "1.0.0";
        $this->author = 'Sendmachine Team';
        $this->need_instance = 0;
        $this->ps_versions_compliancy = array('min' => '1.6', 'max' => _PS_VERSION_);
        $this->bootstrap = true;
        $this->module_key = '0717092e69fe3058e04de9757015a3ab';

        parent::__construct();

        $this->displayName = $this->l('Sendmachine');
        $this->description = $this->l('The official Sendmachine plugin featuring subscribe forms, users sync, news feed, email sending and transactional campaigns.');

        $this->confirmUninstall = $this->l('Are you sure you want to uninstall the Sendmachine module?');
    }

    public function install()
    {

        if (Shop::isFeatureActive()) {
            Shop::setContext(Shop::CONTEXT_ALL);
        }

        if (!parent::install() || !Sm::setConfig(Sm::defaults()) || !$this->registerHook(array('footer'))) {
            return false;
        }

        return true;
    }

    public function uninstall()
    {

        if (!parent::uninstall() || !Configuration::deleteByName(Sm::SM_DOMAIN) || !Sm::resetConfiguration()) {
            return false;
        }

        return true;
    }

    public function hookFooter($params)
    {

        return SmList::processListSubscription($params);
    }

    public function hookdisplayMaintenance($params)
    {

        return SmList::processListSubscription($params);
    }

    public function hookDisplayRightColumn($params)
    {

        return SmList::processListSubscription($params);
    }

    public function hookDisplayLeftColumn($params)
    {

        return SmList::processListSubscription($params);
    }

    public function getContent()
    {

        $allowedValues = array();
        $category = "";
        $output = "";

        $this->context->controller->addJS($this->_path . 'views/js/sm_backend.js');
        $this->context->controller->addCSS($this->_path . 'views/css/sm_backend.css', 'all');

        if (Tools::isSubmit('submit_sendmachine_general')) {

            $allowedValues = array('plugin_enabled', 'api_username', 'api_password');
            $category = 'general';

            SmGeneral::processRequest();
        } elseif (Tools::isSubmit('submit_sendmachine_list')) {

            if (!Tools::getValue('do_refresh_lists') && !Tools::getValue('do_export_subscribers')) {

                $allowedValues = array(
                    'enable_list_subscription',
                    'subsctiption_list',
                    'double_optin',
                    'confirmation_email',
                    'welcome_voucher'
                );
                $category = 'list';
            }

            SmList::processRequest();
        } elseif (Tools::isSubmit('submit_sendmachine_email')) {

            if (!Tools::getValue('do_refresh_sender')) {

                $allowedValues = array('enable_email_sending', 'from_email', 'from_name', 'smtp_encryption', 'smtp_sender');
                $category = 'email';
            }
            SmEmail::processRequest();
        } elseif (Tools::isSubmit('submit_send_testmail')) {

            SmEmail::processMailTesting();
        } elseif (Tools::isSubmit('saveDraft')) {

            SmEmail::saveDraft();
        } elseif (Tools::isSubmit('launchCampaign')) {

            SmEmail::launchCampaign();
        } elseif (Tools::isSubmit('saveCampaign')) {

            SmEmail::saveCampaign();
        } elseif (Tools::getValue('previewSmCampaign')) {

            SmEmail::previewCampaign();
        }

        foreach (Sm::getNotifications() as $v) {

            if ("success" == $v['type']) {
                $output .= $this->displayConfirmation($v['message']);
            } elseif ("error" == $v['type']) {
                $output .= $this->displayError($v['message']);
            }
        }

        if ($category) {
            $output .= $this->updateConfigCategory($category, $allowedValues);
        }

        $output .= $this->displaySendmachineHeader();

        $output .= $this->displayGeneralForm(Sm::getConfig('general'), true);
        $output .= $this->displayListForm(Sm::getConfig('list'), Sm::pluginEnabled());
        $output .= $this->displayEmailForm(Sm::getConfig('email'), Sm::pluginEnabled());

        $output .= SmGeneral::generateLogsModal();
        $output .= SmList::generateExportModal();
        $output .= SmEmail::generateConfigureCampaign();

        $output .= Sm::generateLang();

        return $output;
    }

    private function updateConfigCategory($category = "", $allowedValues = array())
    {

        $data = array();

        foreach ($allowedValues as $v) {
            $data[$v] = Sm::getValue($v);
        }

        Sm::setConfig($category, $data);
        $response = $this->displayConfirmation($this->l('Given configuration values were successfully updated.'));

        return $response;
    }

    private function displaySendmachineHeader()
    {
        $vars = array(
            "sm_dir" => $this->_path
        );
        
        return Sm::buildTemplate('header', $vars);
    }

    private function displayGeneralForm($formData = array(), $showForm = false)
    {

        if (!$showForm) {
            return "";
        }

        $fieldsForm = SmGeneral::generateForm();

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $lang = new Language((int) Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language = $lang->id;
        $helper->allow_employee_form_lang = $lang->id;
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submit_sendmachine_general';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false) . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = array(
            'fields_value' => $formData,
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm($fieldsForm);
    }

    private function displayListForm($formData = array(), $showForm = false)
    {

        if (!$showForm) {
            return "";
        }

        $fieldsForm = SmList::generateForm();

        $formData['do_refresh_lists'] = 0;
        $formData['do_export_subscribers'] = 0;

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $lang = new Language((int) Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language = $lang->id;
        $helper->allow_employee_form_lang = $lang->id;
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submit_sendmachine_list';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false) . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = array(
            'fields_value' => $formData,
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm($fieldsForm);
    }

    private function displayEmailForm($formData = array(), $showForm = false)
    {

        if (!$showForm) {
            return "";
        }

        $formData['request_location'] = $this->context->link->getAdminLink('AdminModules');
        $formData['send_test_to'] = "";
        $formData['do_refresh_sender'] = 0;

        $fieldsForm = SmEmail::generateForm();

        $helper = new HelperForm();
        $helper->show_toolbar = false;
        $helper->table = $this->table;
        $lang = new Language((int) Configuration::get('PS_LANG_DEFAULT'));
        $helper->default_form_language = $lang->id;
        $helper->allow_employee_form_lang = $lang->id;
        $helper->identifier = $this->identifier;
        $helper->submit_action = 'submit_sendmachine_email';
        $helper->currentIndex = $this->context->link->getAdminLink('AdminModules', false) . '&configure=' . $this->name . '&tab_module=' . $this->tab . '&module_name=' . $this->name;
        $helper->token = Tools::getAdminTokenLite('AdminModules');
        $helper->tpl_vars = array(
            'fields_value' => $formData,
            'languages' => $this->context->controller->getLanguages(),
            'id_language' => $this->context->language->id,
        );

        return $helper->generateForm($fieldsForm);
    }
}
