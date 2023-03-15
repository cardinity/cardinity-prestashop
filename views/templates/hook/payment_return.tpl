{*
* @author       Cardinity
* @link         https://cardinity.com
* @license      The MIT License (MIT)
*}
{if $status == 'ok'}
    <p>{l s='Your order on' mod='cardinity'} <span class="bold"></span> {l s='is complete.' mod='cardinity'}
        <br /><br />
        {l s='We received your payment and your order #' mod='cardinity'}
        <strong>{$id_order|intval}</strong> {l s='is in preparation.' mod='cardinity'}
        <br /><br />
        {l s='We will inform you about order process by email.' mod='cardinity'}
        <br />
    </p>
{else}
    <p class="warning">
        {l s='We noticed a problem with your order. If you think this is an error, you can contact our' mod='cardinity'}
        <a href="{$link->getPageLink('contact', true)|escape:'htmlall':'UTF-8'}">{l s='customer support' mod='cardinity'}</a>.
    </p>
{/if}