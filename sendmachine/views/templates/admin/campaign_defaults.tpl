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

<h3>||SITENAME||</h3>
<p>
    <span>{l s='To see this email in your browser, click' mod='sendmachine'}</span>
    <a href="[[PERMALINK]]">{l s='here' mod='sendmachine'}</a>
    <span>.</span>
</p>
<div>
    ||REPEAT_PRODUCT[10][0]||
    <div>
        <img width="180" src="||PRODUCT_IMG||" alt="||PRODUCT_TITLE||">
        <br>
        <a target="_blank" href="||PRODUCT_URL||">||PRODUCT_TITLE||</a>
        <br>
        <span>{l s='Price:' mod='sendmachine'} ||PRODUCT_PRICE||, {l s='quantity:' mod='sendmachine'} ||PRODUCT_QTY|| {l s='items' mod='sendmachine'} - ||PRODUCT_AVAILABILITY||</span>
        <br>
        <span>||PRODUCT_DESCRIPTION||</span>
    </div>
    ||\REPEAT_PRODUCT||
</div>
<p>
    <span>{l s='If you don\'t want to receive this messages anymore, unsubscribe by clicking' mod='sendmachine'}</span>
    <a href="[[UNSUB_LINK]]">{l s='here' mod='sendmachine'}</a>
    <span>!</span>
</p>