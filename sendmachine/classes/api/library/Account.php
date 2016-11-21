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

class Account
{

    public function __construct(SendmachineApiClient $master)
    {
        $this->master = $master;
    }

    /**
     * Get details about the current active package of the user
     * @return array
     * {
     *     "package": {
     *         "name",
     *         "state",
     *         "credits",
     *         "interval",
     *         "price",
     *         "currency",
     *         "custom_fields",
     *         "period_min",
     *         "period_max",
     *         "contract_type",
     *         "max_credit",
     *         "mcountsent",
     *         "prod_id",
     *         "info_type"
     *     }
     * }
     */
    public function package()
    {
        return $this->master->request('/account/package', 'GET');
    }

    /**
     * Get details about the current rating
     * @return array
     * {
     *     "score"
     * }
     */
    public function rating()
    {
        return $this->master->request('/account/rating', 'GET');
    }

    /**
     * The SMTP user and password are also used for API Auth. 
     * @return array
     * {
     *    "smtp": {
     *        "hostname",
     *        "port",
     *        "ssl_tls_port",
     *        "starttls_port",
     *        "username",
     *        "password",
     *        "state"
     *    }
     * }
     */
    public function smtp()
    {
        return $this->master->request('/account/smtp', 'GET');
    }

    /**
     * get user details
     * @return array
     * {
     *    "user": {
     *        "email",
     *        "sex",
     *        "first_name",
     *        "last_name",
     *        "country",
     *        "phone_number",
     *        "mobile_number",
     *        "state",
     *        "language"
     *    }
     * }
     */
    public function details()
    {
        return $this->master->request('/account/user', 'GET');
    }
}
