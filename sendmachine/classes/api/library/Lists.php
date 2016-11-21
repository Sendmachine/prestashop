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

class Lists
{

    public function __construct(SendmachineApiClient $master)
    {
        $this->master = $master;
    }

    /**
     * get all contact lists
     * @param string $limit
     * @param string $offset
     * @return array
     * {
     *    "contactlists": [
     *        {
     *            "list_id",
     *            "name",
     *            "lastsenttolist",
     *            "state",
     *            "subscribed",
     *            "unsubscribed",
     *            "cleaned",
     *            "total"
     *        },
     *        ...
     *    ],
     *    "total"
     * }
     */
    public function get($limit = 25, $offset = 0)
    {
        $params = array('limit' => $limit, 'offset' => $offset);
        return $this->master->request('/list', 'GET', $params);
    }

    /**
     * Get list recpipients
     * @param string $list_id
     * @param int $limit
     * @param int $offset
     * @param string $filter (all, subscribed, unsubscribed, cleaned)
     * @param string $order_by (email, added, lastmod)
     * @param int $segment_id
     * @return array
     * {
     *    "contacts": [
     *        {
     *            "email",
     *            "status",
     *            "added",
     *            "lastmod"",
     *            "rating",
     *            "macros"
     *        },
     *        ...
     *    ],
     *    "total"
     *    "twostep"
     * }
     */
    public function recipients(
        $list_id,
        $limit = 25,
        $offset = 0,
        $filter = 'all',
        $order_by = 'email',
        $segment_id = 0
    ) {
        $params = array(
            'limit' => $limit,
            'offset' => $offset,
            'filter' => $filter,
            'orderby' => $order_by,
            'sid' => $segment_id
        );
        return $this->master->request('/list/' . $list_id, 'GET', $params);
    }
    
    /**
     * get list details
     * @param string $list_id
     * @return array
     * {
     *    "list_id",
     *    "list_hash",
     *    "name",
     *    "cdate",
     *    "mdate",
     *    "lastsenttolist",
     *    "state",
     *    "subscribed",
     *    "unsubscribed",
     *    "cleaned",
     *    "send_goodbye",
     *    "alias"",
     *    "default_from_name",
     *    "default_from_email",
     *    "company",
     *    "address": {
     *        "address",
     *        "city",
     *        "country",
     *        "zip_code"
     *    },
     *    "phone",
     *    "subscription_reminder",
     *    "default_subject",
     *    "website",
     *      "twostep"
     * }
     */
    public function details($list_id)
    {
        return $this->master->request('/list/'.$list_id.'/details', 'GET');
    }

    /**
     * Create a new contact list 
     * @param array $data
     * {
     *      "website",
     *      "default_from_name",
     *      "default_from_email",
     *      "address" => {
     *       "zip_code",
     *          "country",
     *          "city",
     *          "address"
     *      },
     *      "phone",
     *      "name",
     *      "subscription_reminder",
     *      "company" ,
     *      "send_goodbye",
     *      "default_subject"
     * }
     * @return array
     * {
     *    "status",
     *    "id"
     * }
     */
    public function create($data)
    {
        $params = array('list_details' => $data);
        return $this->master->request('/list', 'POST', $params);
    }

    /**
     * manage contacts from list
     * @param string $list_id
     * @param string $emails
     * @param string $action (subscribe, unsubscribe)
     * @param string $list_name
     * @return array
     * {
     *    "status",
     *    "columns"
     * }
     */
    public function manageContacts($list_id, $emails = "", $action = 'subscribe', $list_name = null)
    {
        $params = array(
            'contacts' =>$emails,
            'action' => $action,
            'name' => $list_name
        );
        return $this->master->request('/list/' . $list_id, 'POST', $params);
    }
    
    /**
     * 
     * @param string $list_id
     * @param string $email
     * @param array $data
     * @return array
     * {
     *    "status",
     *    "columns"
     * }
     */
    public function manageContact($list_id, $email, $data)
    {
        return $this->master->request('/list/' . $list_id.'/rcpt/'.$email, 'POST', $data);
    }
    
    /**
     * 
     * @param string $list_id
     * @param string $email
     * @return array
     * {
     *    "contacts": [
     *        {
     *            "email",
     *            "status",
     *            "added",
     *            "rating",
     *            "macros"
     *        }
     *    ]
     * }
     */
    public function contactDetails($list_id, $email)
    {
        return $this->master->request('/list/' . $list_id.'/rcpt/'.$email, 'GET');
    }
    
    /**
     * edit contactlist details
     * @param string $list_id
     * @param array $data
     * @return array
     * {
     *    "status"
     * }
     */
    public function edit($list_id, $data)
    {
        $params = array('list_details' => $data);
        return $this->master->request('/list/'.$list_id.'/details', 'POST', $params);
    }

    /**
     * Delete a contact list
     * @param string $list_id
     * @return array
     * {
     *    "status"
     * }
     */
    public function delete($list_id)
    {
        return $this->master->request('/list/' . $list_id, 'DELETE');
    }

    /**
     * List segments from any given contact list
     * @param string $list_id
     * @param int $limit
     * @param int $offset
     * @param string $orderby (adate, name)
     * @param string $sort (desc, asc)
     * @return array
     * {
     *    "segment_list": [
     *        {
     *            "segment_id",
     *            "contactlist_id",
     *            "name",
     *            "state",
     *            "type",
     *            "adate",
     *            "mdate",
     *            "lastsenttosegment"
     *        },
     *       ...
     *    ],
     *    "total"
     * }
     */
    public function listSegments($list_id, $limit = 25, $offset = 0, $orderby = "adate", $sort = "desc")
    {
        $params = array(
            'limit' => $limit,
            'offset' => $offset,
            'orderby' => $orderby,
            'sort' => $sort
        );
        return $this->master->request('/list/'.$list_id.'/segment', 'GET', $params);
    }
    
    /**
     * Get contact list's fields
     * @param int $list_id
     * @return array
     * {
     *    "custom_fields": [
     *        {
     *            "name",
     *            "form_name",
     *            "visible",
     *            "required",
     *            "cf_type",
     *            "options"
     *        }
     *    ]
     * }
     */
    public function customFields($list_id)
    {
        return $this->master->request('/list/'.$list_id.'/custom_fields', 'GET');
    }
}
