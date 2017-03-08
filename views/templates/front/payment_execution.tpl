{capture name=path}{l s='Credit or Debit Card Payment' mod='cardinity'}{/capture}

<h2>{l s='Order summary' mod='cardinity'}</h2>

{assign var='current_step' value='payment'}
{include file="$tpl_dir./order-steps.tpl"}

{if $nbProducts <= 0}
    <p class="alert alert-warning">
        {l s='Your shopping cart is empty.' mod='cardinity'}
    </p>
{else}
    <div class="cardinity">
        <div class="top_block">
            <div class="logo">
                <img src="{$this_path|escape:'html'}views/img/mastercard.jpg" alt="MasterCard" height="30" />
                <img src="{$this_path|escape:'html'}views/img/maestro.jpg" alt="Maestro" height="30" />
                <img src="{$this_path|escape:'html'}views/img/visa.jpg" alt="Visa" height="30" />
                <img src="{$this_path|escape:'html'}views/img/visa_secure.png" alt="Verified by Visa" height="30" />
                <img src="{$this_path|escape:'html'}views/img/mastercard_secure.png" alt="MasterCard SecureCode" height="30" />
                <img src="{$this_path|escape:'html'}views/img/pci.jpg" alt="PCI" height="30" />
            </div>

            <div>
                {l s='You have chosen to pay by Credit Card or Debit Card.' mod='cardinity'}
            </div>
        </div>
        <div class="clearfix"></div>

        <form action="{if $smarty.const._PS_VERSION_ >= 1.5}{$link->getModuleLink('cardinity', 'validation', [], true)|escape:'html'}{else}{$this_path_ssl}validation.php{/if}"
              method="post" id="cardinity-form" class="form-horizontal">
            {if (count($currencies) > 1)}
                <div class="control-group">
                    <label for="currencies" class="control-label">{l s='Select currency' mod='cardinity'}:</label>

                    <div class="controls">
                        <select name="currency" id="currencies" class="currency form-control"
                                onchange="setCurrency($('#currencies').val());">
                            {foreach from=$currencies item=supportedCurrency}
                                <option value="{$supportedCurrency.id_currency|escape:'intval'}"
                                        {if $supportedCurrency.id_currency == $customerCurrency}selected="selected"{/if}>{$supportedCurrency.name}</option>
                            {/foreach}
                        </select>
                    </div>
                </div>
            {/if}
            <div class="control-group total-amount-block">
                <label class="control-label">{l s='Order total' mod='cardinity'}:</label>

                <div class="controls">
                    <span class="price">{$total} {$currency->iso_code|escape:'html'}</span>
                </div>
            </div>

            <p class="cart_navigation clearfix" id="cart_navigation">
                {if $smarty.const._PS_VERSION_ < 1.6}
                    <input type="submit" class="exclusive_large button-medium"
                           value="{l s='I confirm my order' mod='cardinity'}" />
                    <br />
                {else}
                    <button class="button btn btn-default button-medium" type="submit">
                        <span>{l s='I confirm my order' mod='cardinity'}<i class="icon-chevron-right right"></i></span>
                    </button>
                {/if}
                <a class="button-exclusive btn btn-default"
                   href="{if $smarty.const._PS_VERSION_ < 1.6}{$link->getPageLink('order.php', true)}?step=3{else}{$link->getPageLink('order', true, NULL, "step=3")|escape:'html':'UTF-8'}{/if}">
                    <i class="icon-chevron-left"></i>{l s='Other payment methods' mod='cardinity'}
                </a>
            </p>
        </form>
    </div>
{/if}