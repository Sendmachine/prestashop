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

defined('CURL_SSLVERSION_DEFAULT') || define('CURL_SSLVERSION_DEFAULT', 0);

require_once _PS_MODULE_DIR_ . 'sendmachine/classes/api/library/SendmachineError.php';
require_once _PS_MODULE_DIR_ . 'sendmachine/classes/api/library/HttpError.php';
require_once _PS_MODULE_DIR_ . 'sendmachine/classes/api/library/Account.php';
require_once _PS_MODULE_DIR_ . 'sendmachine/classes/api/library/Sender.php';
require_once _PS_MODULE_DIR_ . 'sendmachine/classes/api/library/Campaigns.php';
require_once _PS_MODULE_DIR_ . 'sendmachine/classes/api/library/Lists.php';
require_once _PS_MODULE_DIR_ . 'sendmachine/classes/api/library/Templates.php';

class SendmachineApiClient
{
    /**
     * api host
     * @var string
     */
    private $api_host = 'https://api.sendmachine.com';

    /**
     * api username
     * @var string 
     */
    private $username;

    /**
     * api password
     * @var string 
     */
    private $password;

    /**
     * Curl resource
     * @var resource
     */
    private $curl;

    /*
     * for debugging
     */
    private $debug = false;

    /**
     * connect to api
     * @param string $username
     * @param string $password
     */
    public function __construct($username = null, $password = null)
    {
        if (!$username || !$password) {

            list($username, $password) = $this->checkConfig();
        }

        if (!$username || !$password) {

            throw new SendmachineError("You must provide a username and password", "no_username_password");
        }

        $this->username = $username;
        $this->password = $password;

        $this->curl = curl_init();

        $this->campaigns = new Campaigns($this);
        $this->sender = new Sender($this);
        $this->lists = new Lists($this);
        $this->account = new Account($this);
        $this->templates = new Templates($this);
    }

    public function request($url, $method, $params = array())
    {
        $ch = $this->curl;
        
        switch (Tools::strtoupper($method)) {
            case 'GET':
                if (count($params)) {
                    $url .= "?" . http_build_query($params);
                }
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "GET");
                break;
            case 'PUT':
            case 'POST':
                $params = Tools::jsonEncode($params);
                $_data = array(
                    'Content-Type: application/json',
                    'Content-Length: ' . Tools::strlen($params)
                );

                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "POST");
                curl_setopt($ch, CURLOPT_POSTFIELDS, $params);
                curl_setopt($ch, CURLOPT_HTTPHEADER, $_data);
                break;
            case 'DELETE':
                curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "DELETE");
                curl_setopt($ch, CURLOPT_HTTPHEADER, array('Content-Type: application/json'));
                break;
        }
        
        $final_url = $this->api_host . $url;
        
        curl_setopt($ch, CURLOPT_SSLVERSION, CURL_SSLVERSION_DEFAULT);
        curl_setopt($ch, CURLOPT_USERPWD, $this->username . ":" . $this->password);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_URL, $final_url);
        curl_setopt($ch, CURLOPT_VERBOSE, $this->debug);

        if ($this->debug) {
            $start = microtime(true);
            $this->log('URL: ' . $this->api_host . $url . (is_string($params) ? ", params: " . $params : ""));
        }

        $response = curl_exec($ch);
        $info = curl_getinfo($ch);

        if ($this->debug) {
            $time = microtime(true) - $start;
            $this->log('Completed in ' . number_format($time * 1000, 2) . 'ms');
            $this->log('Response: ' . $response);
        }

        if (curl_error($ch)) {

            throw new HttpError("API call to $this->api_host$url failed.Reason: " . curl_error($ch));
        }

        $result = Tools::jsonDecode($response, true);
        if ($response && !$result) {
            $result = $response;
        }

        if ($info['http_code'] >= 400) {

            $this->setError($result);
        }

        return $result;
    }

    public function __destruct()
    {

        if (is_resource($this->curl)) {

            curl_close($this->curl);
        }
    }

    public function log($msg)
    {
        error_log($msg);
    }

    public function checkConfig()
    {

        $config_paths = array(".sendmachine.conf", "/etc/.sendmachine.conf");
        $username = null;
        $password = null;

        foreach ($config_paths as $path) {

            if (file_exists($path)) {

                if (!is_readable($path)) {

                    throw new SendmachineError("Configuration file ($path) does not have read access.", "config_not_readable");
                }

                $config = parse_ini_file($path);

                $username = empty($config['username']) ? null : $config['username'];
                $password = empty($config['password']) ? null : $config['password'];
                break;
            }
        }

        return array($username, $password);
    }

    public function setError($result)
    {

        if (is_array($result)) {

            if (empty($result['error_reason'])) {

                if (!empty($result['status'])) {
                    $result['error_reason'] = $result['status'];
                } else {
                    $result['error_reason'] = "Unexpected error";
                }
            }

            throw new SendmachineError($result['error_reason'], $result['status']);
        }
    }
}
