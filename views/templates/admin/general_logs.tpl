{*
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
*  @author    PrestaShop SA <contact@prestashop.com>
*  @copyright 2007-2016 PrestaShop SA
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*  International Registered Trademark & Property of PrestaShop SA
*}

<div>
    <p>{l s='Here you can monitor plugin\'s activity during it\'s instalation.' mod='sendmachine'}</p>
    <br>
    <p>{l s='Choose a category:' mod='sendmachine'}</p>
    <select class='smActivityLogSelect' style='width: 300px;'>
        <option></option>
        {foreach from=$logs key=key item=log}
            <option value="{$key|escape:'htmlall':'UTF-8'}">{$key|escape:'htmlall':'UTF-8'}</option>
        {/foreach}
    </select>
    <div class="logSheetWrapper">
        {foreach from=$logs key=key item=log}
            <div class="log_sheet_{$key|escape:'htmlall':'UTF-8'} logSheet">
                {foreach from=$log item=entry}
                    <code>{$entry|escape:'htmlall':'UTF-8'}</code><br>
                {/foreach}
            </div>
        {/foreach}
    </div>
</div>