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

<div class="modal fade" id="{$id|escape:'htmlall':'UTF-8'}" aria-labelledby="{$id|escape:'htmlall':'UTF-8'}Label">
    <div class="modal-dialog" style="{$modalSize|escape:'htmlall':'UTF-8'}">
        <div class="modal-content">
            <div class="modal-header">
                <button type="button" class="close" data-dismiss="modal" aria-label="Close"><span aria-hidden="true">&times;</span></button>
                <h4 class="modal-title" id="{$id|escape:'htmlall':'UTF-8'}">{$title|escape:'htmlall':'UTF-8'}</h4>
            </div>
            <div class="modal-body">%s</div>
            <div class="modal-footer">
                {if $cancelButtonLabel}
                    <button type="button" class="btn btn-default" data-dismiss="modal">{$cancelButtonLabel|escape:'htmlall':'UTF-8'}</button>
                {/if}
                {if $confirmButtonLabel}
                    <button type="button" class="btn btn-primary {$id|escape:'htmlall':'UTF-8'}OkButton">{$confirmButtonLabel|escape:'htmlall':'UTF-8'}</button>
                {/if}
            </div>
        </div>
    </div>
</div>