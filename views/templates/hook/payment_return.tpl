{if $status == 'ok'}
    <p>{l s='Your order on' mod='cardinity'} <span class="bold">{$shop_name|escape:'html'}</span> {l s='is complete.' mod='cardinity'}
        <br /><br />
        {l s='We received your payment and your order #' mod='cardinity'}
        <strong>{$id_order|escape:'intval'}</strong> {l s='is in preparation.' mod='cardinity'}
        {l s='You can view this order' mod='cardinity'} <a href="history.php">{l s='here' mod='cardinity'}</a>.
        <br /><br />
        {l s='We will inform you about order process by email.' mod='cardinity'}
        <br />
    </p>
{else}
    <p class="warning">
        {l s='We noticed a problem with your order. If you think this is an error, you can contact our' mod='cardinity'}
        <a href="{$link->getPageLink('contact', true)|escape:'html'}">{l s='customer support' mod='cardinity'}</a>.
    </p>
{/if}