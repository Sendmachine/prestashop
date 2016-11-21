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

class Sender
{

    public function __construct(SendmachineApiClient $master)
    {
        $this->master = $master;
    }

    /**
     * get sender list
     * @param string $status (active, pending, active+pending, all)
     * @param string $type (email, domain, all)
     * @param string $group (none, domain, flat)
     * @param int $limit
     * @param int $offset
     * @return array
     * {
     *    "senderlist": [
     *        {
     *            "email",
     *            "type",
     *            "emailtype",
     *            "status",
     *            "label"
     *        },
     *        ...
     *    ],
     *    "total"
     * }
     */
    public function get($status = 'active', $type = 'email', $group = null, $limit = null, $offset = null)
    {
        $params = array(
            'status' => $status,
            'type' => $type,
            'group' => $group,
            'limit' => $limit,
            'offset' => $offset
        );
        return $this->master->request('/sender', 'GET', $params);
    }

    /**
     * add a new sender
     * @param string $email
     * @return array
     * {
     *    "sender": {
     *        "address",
     *        "type"
     *    },
     *    "status"
     * }
     */
    public function add($email)
    {
        $params = array('type' => 'email', 'address' => $email);
        return $this->master->request('/sender', 'POST', $params);
    }
    
    /**
     * delete sender by email address
     * @param type $sender_email
     * @return array
     * {
     *    "status"
     * }
     */
    public function delete($sender_email)
    {
        return $this->master->request('/sender/'.$sender_email, 'DELETE');
    }
}
