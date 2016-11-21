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

class SmGeneral extends Sm
{

    public static function processRequest()
    {

        $apiUsername = Tools::getValue('api_username');
        $apiPassword = Tools::getValue('api_password');

        $emptyData = array(
            'plugin_enabled' => 0,
            'api_username' => '',
            'api_password' => ''
        );

        if (!$apiUsername || !$apiPassword) {
            self::addLog("api_connected", "No credentials were provided.");
            self::$requestValue['plugin_enabled'] = 0;
            self::resetConfiguration();
            return false;
        }
        
        $_credentials = array(
            'api_username' => $apiUsername,
            'api_password' => $apiPassword
        );

        try {
            $credentialsOk = self::apiClient($_credentials)->account->package();
        } catch (Exception $ex) {
            unset($ex);
            $credentialsOk = false;
        }

        if (!$credentialsOk) {
            self::addLog("api_connected", "Provided API credentials are not valid.");
            self::$requestValue = $emptyData;
            self::resetConfiguration();
            self::setNotification("error", self::l('Provided API credentials are not valid.'));
            return false;
        }
        
        self::addLog("api_connected", "Api connected successfully.");
        
        $generalConfig = self::getConfig('general');
        
        if (
            $generalConfig['api_username'] != $apiUsername ||
            $generalConfig['api_password'] != $apiPassword
        ) {
            self::addLog("api_connected", "API credentials were updated.");
            self::resetConfiguration();
        }

        try {
            $contactLists = self::apiClient($_credentials)->lists->get();
            if (!isset($contactLists['contactlists'])) {
                $contactLists = null;
            } else {
                $contactLists = $contactLists['contactlists'];
            }
        } catch (Exception $ex) {
            unset($ex);
            $contactLists = null;
        }

        try {
            $smtpSettings = self::apiClient($_credentials)->account->smtp();
            if (!isset($smtpSettings['smtp'])) {
                $smtpSettings = null;
            } else {
                $smtpSettings = $smtpSettings['smtp'];
            }
        } catch (Exception $ex) {
            unset($ex);
            $smtpSettings = null;
        }

        try {
            $senderAddresses = self::apiClient($_credentials)->sender->get();
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

        $apiConfig['contact_lists'] = $contactLists;
        $apiConfig['smtp_settings'] = $smtpSettings;
        $apiConfig['sender_addresses'] = $senderAddresses;

        self::setConfig('api_data', $apiConfig);

        $pre = (isset($generalConfig['plugin_enabled']) && $generalConfig['plugin_enabled']);
        
        if ($pre && !Tools::getValue('plugin_enabled')) {
            self::resetConfiguration();
        }

        return true;
    }

    public static function generateForm()
    {

        return array(
            array(
                "form" => array(
                    'legend' => array(
                        'title' => self::l('General'),
                        'icon' => 'icon-cogs',
                    ),
                    'input' => array(
                        array(
                            'type' => 'switch',
                            'label' => self::l('Enable Sendmachine module?'),
                            'name' => 'plugin_enabled',
                            'values' => array(
                                array(
                                    'id' => 'sendmachine_plugin_on',
                                    'value' => 1,
                                    'label' => self::l('Yes'),
                                ),
                                array(
                                    'id' => 'sendmachine_plugin_off',
                                    'value' => 0,
                                    'label' => self::l('No'),
                                ),
                            ),
                        ),
                        array(
                            'type' => 'text',
                            'label' => self::l('API USERNAME'),
                            'name' => 'api_username',
                        ),
                        array(
                            'type' => 'text',
                            'label' => self::l('API PASSWORD'),
                            'name' => 'api_password',
                        ),
                    ),
                    'submit' => array(
                        'title' => self::l('Save'),
                        'icon' => 'process-icon-save',
                        'class' => 'btn btn-default pull-right smFormGeneralTab',
                    ),
                )
            )
        );
    }

    public static function generateLogsModal()
    {

        $logs = self::getLog();

        if (!$logs) {
            $content = self::l("Activity log is empty.");
        } else {
            $content = self::buildTemplate('general_logs', array("logs" => $logs));
        }

        return self::generateModal('readLogs', 'large', self::l('Activity logs'), $content, self::l('Close'), false);
    }
}
