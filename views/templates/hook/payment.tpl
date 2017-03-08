<div class="row">
    <div class="col-xs-12">
        <p class="payment_module">
            <a class="cheque"
               style="{if $smarty.const._PS_VERSION_ >= 1.6}background-image: url({$this_path}views/img/card.png); background-position: 15px 25px; background-repeat: no-repeat;{else}{/if}"
               href="{if $smarty.const._PS_VERSION_ >= 1.5}{$link->getModuleLink('cardinity', 'payment', [], true)}{else}{$this_path_ssl}payment.php{/if}"
               title="{l s='Pay by Credit Card or Debit Card' mod='cardinity'}">
                {if $smarty.const._PS_VERSION_ < 1.6}
                    <img src="{$this_path|escape:'html'}views/img/card_15.png"
                         {if $smarty.const._PS_VERSION_ < 1.5}style="position: relative;top: 10px;"{/if}
                         alt="{l s='Pay by Credit Card or Debit Card' mod='cardinity'}" />
                {/if}
                {l s='Pay by Credit Card or Debit Card' mod='cardinity'}
                <br />
                <span style="{if $smarty.const._PS_VERSION_ < 1.5}display:block;margin: -6px 0 5px 100px;{else}display: inline-block;margin-top: 5px;{/if}">
                    <img src="{$this_path|escape:'html'}views/img/mastercard.jpg" alt="MasterCard" height="20" />
                    <img src="{$this_path|escape:'html'}views/img/maestro.jpg" alt="Maestro" height="20" />
                    <img src="{$this_path|escape:'html'}views/img/visa.jpg" alt="Visa" height="20" />
                    <img src="{$this_path|escape:'html'}views/img/visa_secure.png" alt="Verified by Visa" height="20" />
                    <img src="{$this_path|escape:'html'}views/img/mastercard_secure.png" alt="MasterCard SecureCode" height="20" />
                    <img src="{$this_path|escape:'html'}views/img/pci.jpg" alt="PCI" height="20" />
                </span>
            </a>
        </p>
    </div>
</div>