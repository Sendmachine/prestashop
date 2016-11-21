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

<p class="campaignBaseHelp">
    <span>{l s='For more details on how to build your template and a complete keyword dictionary, click' mod='sendmachine'}</span>
    <a href="" class="keywordDictionaryShow">{l s='here' mod='sendmachine'}</a>
    <span>.</span>
</p>

<div class="campaignExtraHelp" style="display: none;">
    <h4 class="sm_keywords_title">{l s='Keyword dictionary:' mod='sendmachine'}</h4>
    <ul class="sm_keywords_ul">
        <li><code>||SITENAME||</code> - {l s='Your site\'s title.' mod='sendmachine'}</li>
        <li><code>||SITEURL||</code> - {l s='Site\'s URL.' mod='sendmachine'}</li>
        <li><code>||ADMINEMAIL||</code> - {l s='Site\'s admin email address.' mod='sendmachine'}</li>
        <li><code>||PRODUCT_URL||</code> - {l s='Display product\'s url.' mod='sendmachine'}</li>
        <li><code>||PRODUCT_TITLE||</code> - {l s='Your product\'s title' mod='sendmachine'}</li>
        <li><code>||PRODUCT_DESCRIPTION||</code> - {l s='Product\'s description. Max character number is 300.' mod='sendmachine'}</li>
        <li><code>||PRODUCT_PRICE||</code> - {l s='Product\'s price.' mod='sendmachine'}</li>
        <li><code>||PRODUCT_QTY||</code> - {l s='Product\'s quantity.' mod='sendmachine'}</li>
        <li><code>||PRODUCT_IMG||</code> - {l s='Product\'s main image.' mod='sendmachine'}</li>
        <li><code>||PRODUCT_TAX||</code> - {l s='Product\'s tax.' mod='sendmachine'}</li>
        <li><code>||PRODUCT_SUPPLIER_NAME||</code> - {l s='Product\'s supplier name.' mod='sendmachine'}</li>
        <li><code>||PRODUCT_MANUFACTURER_NAME||</code> - {l s='Product\'s manufacturer name.' mod='sendmachine'}</li>
        <li><code>||PRODUCT_AVAILABILITY||</code> - {l s='Product\'s availability.' mod='sendmachine'}</li>
        <li><code>||REPEAT_PRODUCT[item_nr][category_id]||</code> - {l s='Start repeating products (defaults to 10 items and category 0).' mod='sendmachine'}</li>
        <li><code>||\REPEAT_PRODUCT||</code> - {l s='Stop repeating products.' mod='sendmachine'}</li>
    </ul>
    <p>
        <span>{l s='For a list of available Sendmachine macros click' mod='sendmachine'}</span>
        <a target="_blank" href="http://blog.sendmachine.ro/index.php/macro-email-marketing/">{l s='here' mod='sendmachine'}</a>
        <span>.</span>
    </p>
</div>