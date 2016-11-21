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

class Templates
{

    public function __construct(SendmachineApiClient $master)
    {
        $this->master = $master;
    }

    /**
     * get templates
     * @param int $limit
     * @param int $offset
     * @return array
     * {
     *    "list": [
     *        {
     *            "tpl_id",
     *            "name",
     *            "date",
     *            "mdate"
     *        },
     *        ...
     *    ],
     *    "total"
     * }
     */
    public function get($limit = 25, $offset = 0)
    {

        $params = array('limit' => $limit, 'offset' => $offset);
        return $this->master->request('/templates', 'GET', $params);
    }

    /**
     * Get a single template
     * @param int $template_id
     * @return array
     * {
     *    "body",
     *    "id",
     *    "name"
     * }
     */
    public function details($template_id)
    {

        return $this->master->request('/templates/' . $template_id, 'GET');
    }

    /**
     * Create a new template
     * @param string $name
     * @param string $body
     * @return array
     * {
     *    "status"
     * }
     */
    public function create($name, $body = "")
    {

        $params = array('name' => $name, 'body' => $body);
        return $this->master->request('/templates', 'POST', $params);
    }

    /**
     * edit template body
     * @param int $template_id
     * @param string $body
     * @return array
     * {
     *    "status"
     * }
     */
    public function update($template_id, $body = "")
    {

        $params = array('body' => $body);
        return $this->master->request('/templates/' . $template_id, 'POST', $params);
    }

    /**
     * Delete a template
     * @param int $template_id
     * @return array
     * {
     *    "status"
     * }
     */
    public function delete($template_id)
    {

        return $this->master->request('/templates/' . $template_id, 'DELETE');
    }
}
