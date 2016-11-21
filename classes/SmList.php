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

class SmList extends Sm
{
    public static function processRequest()
    {

        if (Tools::getValue('do_refresh_lists')) {
            return self::refreshLists();
        }

        if (Tools::getValue('do_export_subscribers')) {
            return self::exportSubscribers();
        }

        $listConfig = self::getConfig('list');

        if (
            (isset($listConfig['enable_list_subscription']) && $listConfig['enable_list_subscription']) &&
            !Tools::getValue('enable_list_subscription')
        ) {

            return self::resetNewsletterSettings();
        }

        if (!Module::isInstalled(self::nlClass())) {

            $data_resp = Module::getInstanceByName(self::nlClass());
            if (!empty($data_resp)) {
                $data_resp->install();
            }
        }

        if (!Module::isEnabled(self::nlClass())) {
            Module::enableByName(self::nlClass());
        }

        if (!Module::isEnabled(self::nlClass())) {
            self::setNotification("error", self::l('"' . self::nlClass() . '" module could not be activated.'));
            return false;
        }

        self::setHookPosition();

        Configuration::updateValue('NW_CONFIRMATION_EMAIL', (bool) Tools::getValue('confirmation_email'));
        Configuration::updateValue('NW_VERIFICATION_EMAIL', (bool) Tools::getValue('double_optin'));

        $voucher = Tools::getValue('welcome_voucher');

        if ($voucher && !Validate::isDiscountName($voucher)) {
            self::setNotification("error", self::l('The voucher code is invalid.'));
            return false;
        }

        Configuration::updateValue('NW_VOUCHER_CODE', pSQL($voucher));
        return true;
    }

    private static function refreshLists($doNotify = true)
    {

        try {
            $contactLists = self::apiClient()->lists->get();
            if (!isset($contactLists['contactlists'])) {
                $contactLists = null;
            } else {
                $contactLists = $contactLists['contactlists'];
            }
        } catch (Exception $ex) {
            unset($ex);
            $contactLists = null;
        }

        $apiConfig = self::getConfig('api_data');

        $apiConfig['contact_lists'] = $contactLists;

        self::addLog("list_refreshed", "List cache refreshed.");
        self::setConfig('api_data', $apiConfig);

        if ($doNotify) {
            self::setNotification("success", self::l("List cache successfully updated!"));
        }
        return true;
    }
    
    private static function getSubscribers()
    {
        $dbquery = new DbQuery();
        $dbquery->select('c.`id_customer` AS `id`, s.`name` AS `shop_name`, gl.`name` AS `gender`, c.`lastname`, c.`firstname`, c.`email`, c.`newsletter` AS `subscribed`, c.`newsletter_date_add`');
        $dbquery->from('customer', 'c');
        $dbquery->leftJoin('shop', 's', 's.id_shop = c.id_shop');
        $dbquery->leftJoin('gender', 'g', 'g.id_gender = c.id_gender');
        $dbquery->leftJoin('gender_lang', 'gl', 'g.id_gender = gl.id_gender AND gl.id_lang = '.(int)self::getContext()->employee->id_lang);
        $dbquery->where('c.`newsletter` = 1');

        $customers = Db::getInstance(_PS_USE_SQL_SLAVE_)->executeS($dbquery->build());

        $dbquery = new DbQuery();
        $dbquery->select('CONCAT(\'N\', n.`id`) AS `id`, s.`name` AS `shop_name`, NULL AS `gender`, NULL AS `lastname`, NULL AS `firstname`, n.`email`, n.`active` AS `subscribed`, n.`newsletter_date_add`');
        $dbquery->from('newsletter', 'n');
        $dbquery->leftJoin('shop', 's', 's.id_shop = n.id_shop');
        $dbquery->where('n.`active` = 1');

        $non_customers = Db::getInstance()->executeS($dbquery->build());

        $subscribers = array_merge($customers, $non_customers);

        return $subscribers;
    }

    private static function exportSubscribers()
    {
        $blockNewsletter = Module::getInstanceByName(self::nlClass());
        if (version_compare(_PS_VERSION_, '1.6.1', '<')) {
            $subscribers = self::getSubscribers();
        } else {
            $subscribers = $blockNewsletter->getSubscribers();
        }

        if (!$subscribers) {
            self::setNotification("error", self::l('The export was not completed.'));
            return false;
        }

        $emails = array();
        foreach ($subscribers as $subscriber) {
            array_push($emails, $subscriber['email']);
        }

        $listConfig = self::getConfig('list');
        if (!$listConfig || !isset($listConfig['subsctiption_list'])) {
            self::setNotification("error", self::l('The export was not completed.'));
            return false;
        }

        $listHash = $listConfig['subsctiption_list'];

        try {
            $res = self::apiClient()->lists->manageContacts($listHash, $emails, 'subscribe');
        } catch (Exception $ex) {
            unset($ex);
            self::setNotification("error", self::l('The export was not completed.'));
            return false;
        }

        if (isset($res['status']) && "saved" == $res['status']) {

            $contactNr = count($emails);
            self::addLog("list_export", "List exported to \"$listHash\". $contactNr contacts were exported.");
            self::refreshLists(false);
            self::setNotification("success", self::l("Your subscribers were exported successfully!"));
            return true;
        } else {

            self::setNotification("error", self::l('The export was not completed.'));
            return false;
        }
    }

    public static function processListSubscription($params = array())
    {

        $type = null;
        $emailAddress = null;
        $idShop = $params['cart']->id_shop;

        if (Tools::isSubmit('submitNewsletter')) {
            $type = "simple";
        } elseif (self::nlClass() == Tools::getValue('module') && "verification" == Tools::getValue('controller')) {
            $type = "doubleoptin";
        } else {
            return false;
        }

        $generalConfig = self::getConfig('general');
        $listConfig = self::getConfig('list');

        if (!$generalConfig || !isset($generalConfig['plugin_enabled']) || !$generalConfig['plugin_enabled']) {
            return false;
        }

        if (
            !$listConfig ||
            !isset($listConfig['enable_list_subscription']) ||
            !$listConfig['enable_list_subscription']
        ) {
            return false;
        }

        if (!isset($listConfig['subsctiption_list']) || !$listConfig['subsctiption_list']) {
            return false;
        }

        if ("simple" == $type) {

            $emailAddress = Tools::getValue('email');
        } elseif ("doubleoptin" == $type) {

            $token = Tools::getValue('token');
            $where = 'MD5(CONCAT( `email` , `newsletter_date_add`, \'' . pSQL(Configuration::get('NW_SALT')) . '\')) = \'' . pSQL($token) . '\'';
            $sql = 'SELECT `email` FROM ' . pSQL(_DB_PREFIX_ . self::nlDB()) . ' WHERE ' . $where;
            $emailAddress = Db::getInstance()->getValue($sql);
            if (!$emailAddress) {
                return false;
            }
        } else {
            return false;
        }

        self::setHookPosition();

        $sql = 'SELECT `email`, `active` FROM ' . pSQL(_DB_PREFIX_ . self::nlDB()) . ' WHERE `email` = \'' . pSQL($emailAddress) . '\' AND id_shop = ' . (int)$idShop;

        $subscriber = Db::getInstance()->getRow($sql);

        if ($subscriber && $subscriber['active']) {

            $listHash = $listConfig['subsctiption_list'];

            try {
                self::apiClient()->lists->manageContacts($listHash, array($emailAddress), 'subscribe');
            } catch (Exception $ex) {
                unset($ex);
                self::addLog("list_subscription", "$emailAddress was NOT subscribed to list \"$listHash\".");
                return false;
            }
            self::addLog(
                "list_subscription",
                "$emailAddress was subscribed to list \"$listHash\". Method used was \"$type\"."
            );
        }

        return true;
    }

    private static function setHookPosition()
    {

        $hookId = Hook::getIdByName('displayFooter');
        $ret = false;

        $blockNewsletter = Module::getInstanceByName(self::nlClass());
        $blockNewsletterPos = (int) $blockNewsletter->getPosition($hookId);

        $smPos = (int) self::getInstance()->getPosition($hookId);

        if (($blockNewsletterPos && $smPos) && ($smPos < $blockNewsletterPos)) {
            $ret = true;
            self::getInstance()->updatePosition($hookId, 1, $blockNewsletterPos);
        }

        return $ret;
    }

    public static function generateForm()
    {

        return array(
            array(
                'form' => array(
                    'legend' => array(
                        'title' => self::l('Contact List'),
                        'icon' => 'icon-user',
                    ),
                    'input' => array(
                        array(
                            'type' => 'hidden',
                            'name' => 'do_refresh_lists',
                        ),
                        array(
                            'type' => 'hidden',
                            'name' => 'do_export_subscribers',
                        ),
                        array(
                            'type' => 'switch',
                            'label' => self::l('Enable sendmachine list subscription?'),
                            'name' => 'enable_list_subscription',
                            'values' => array(
                                array(
                                    'id' => 'list_subscription_on',
                                    'value' => 1,
                                    'label' => self::l('Yes'),
                                ),
                                array(
                                    'id' => 'list_subscription_off',
                                    'value' => 0,
                                    'label' => self::l('No'),
                                ),
                            ),
                        ),
                        array(
                            'type' => 'select',
                            'label' => self::l('List where people will subscribe'),
                            'name' => 'subsctiption_list',
                            'class' => 'fixed-width-xl',
                            'options' => array(
                                'query' => self::genListDropdown(),
                                'id' => 'subsctiption_list_option',
                                'name' => 'name'
                            ),
                        ),
                        array(
                            'type' => 'switch',
                            'label' => self::l('Use double opt-in email confirmation?'),
                            'name' => 'double_optin',
                            'values' => array(
                                array(
                                    'id' => 'double_optin_on',
                                    'value' => 1,
                                    'label' => self::l('Yes'),
                                ),
                                array(
                                    'id' => 'double_optin_off',
                                    'value' => 0,
                                    'label' => self::l('No'),
                                ),
                            ),
                        ),
                        array(
                            'type' => 'switch',
                            'label' => self::l('Send a "welcome" email after subscription?'),
                            'name' => 'confirmation_email',
                            'values' => array(
                                array(
                                    'id' => 'confirmation_email_on',
                                    'value' => 1,
                                    'label' => self::l('Yes'),
                                ),
                                array(
                                    'id' => 'confirmation_email_off',
                                    'value' => 0,
                                    'label' => self::l('No'),
                                ),
                            ),
                        ),
                        array(
                            'type' => 'text',
                            'label' => self::l('Welcome voucher code'),
                            'name' => 'welcome_voucher',
                            'desc' => self::l('Leave blank to disable by default.'),
                            'class' => 'fixed-width-xl',
                        ),
                    ),
                    'submit' => array(
                        'title' => self::l('Save'),
                        'icon' => 'process-icon-save',
                        'class' => 'btn btn-default pull-right smFormListTab',
                    ),
                )
            ),
        );
    }

    public static function generateExportModal()
    {

        $config = self::getConfig();
        $listName = "";

        if (
            !isset($config['list']) ||
            !isset($config['api_data']) ||
            !isset($config['list']['subsctiption_list']) ||
            !isset($config['api_data']['contact_lists'])
        ) {
            return "";
        }

        $listHash = $config['list']['subsctiption_list'];

        foreach ($config['api_data']['contact_lists'] as $v) {

            if ($v['list_id'] === $listHash) {
                $listName = $v['name'];
                continue;
            }
        }

        if (!$listName) {
            return "";
        }

        $content = sprintf(self::l("You are about to export prestashop's newsletter subscribers to your Sendmachine account. Subscribers will be added to ~%s~ list located in your Sendmachine account."), $listName);
        return self::generateModal(
            'confirmExportModal',
            'normal',
            self::l('Confirm'),
            $content,
            self::l('Cancel'),
            self::l('Export')
        );
    }
}
