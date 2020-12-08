{capture name=path}{l s='Credit or Debit Card Payment' mod='cardinity'}{/capture}

<h2>{l s='Credit or Debit Card Payment' mod='cardinity'}</h2>

<div class="cardinity">
    {if (isset($errors) && ! empty($errors))}
        <div class="{if $smarty.const._PS_VERSION_ < 1.6}error{else}alert alert-danger{/if}">
            <p>
                {l s='There' mod='cardinity'}
                {if (count($errors) > 1)}
                    {l s='are' mod='cardinity'}
                {else}
                    {l s='is' mod='cardinity'}
                {/if}
                {count($errors)}
                {if (count($errors) > 1)}
                    {l s='errors' mod='cardinity'}
                {else}
                    {l s='error' mod='cardinity'}
                {/if}
            </p>
            {if $smarty.const._PS_VERSION_ < 1.6}
            <ol>{else}
                <ul>{/if}
                    {foreach from=$errors item=error}
                        <li>{$error|escape:'html'}</li>
                    {/foreach}
                {if $smarty.const._PS_VERSION_ < 1.6}</ol>{else}</ul>{/if}
        </div>
    {/if}
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
            {l s='Enter your Credit Card or Debit Card details below.' mod='cardinity'}
        </div>
    </div>
    <div class="clearfix"></div>
    <form action="{if $smarty.const._PS_VERSION_ >= 1.5}{$link->getModuleLink('cardinity', 'process', ['order_id' => $orderId], true)|escape:'html'}{else}{$this_path_ssl}process.php?order_id={$orderId}{/if}"
          method="post" id="cardinity-form" class="form-horizontal">
        <input type="hidden" name="order_id" value="{$orderId|escape:'intval'}" />

        <div class="control-group">
            <label for="cardHolder" class="control-label">{l s='Name on Card' mod='cardinity'}:</label>

            <div class="controls">
                <input type="text" name="card_holder" class="card-holder form-control"
                       value="{if (isset($input['card_holder']))}{$input['card_holder']}{/if}" id="cardHolder"
                       maxlength="32" placeholder="{l s='Name on Card' mod='cardinity'}" required />
            </div>
        </div>
        <div class="control-group">
            <label for="cardPan" class="control-label">{l s='Card Number' mod='cardinity'}:</label>

            <div class="controls">
                <input type="tel" name="card_pan" value="{if (isset($input['card_pan']))}{$input['card_pan']}{/if}"
                       class="card-pan only-number form-control" id="cardPan" maxlength="24" placeholder="•••• •••• •••• ••••" required />
            </div>
        </div>
        <div class="control-group">
            <label for="expirationMonth" class="control-label">{l s='Expiration Date' mod='cardinity'}:</label>

            <div class="controls">
                <select name="expiration_month" class="expiration-month form-control" id="expirationMonth">
                    {for $month=1 to 12}
                        <option value="{$month|escape:'intval'}"
                                {if isset($input['expiration_month']) && $input['expiration_month'] == $month}selected{/if}>{$month}</option>
                    {/for}
                </select>
                /
                <select name="expiration_year" class="expiration-year form-control" id="expirationMonth">
                    {for $year=date('Y') to date('Y', strtotime('+ 19 years'))}
                        <option value="{$year|escape:'intval'}"
                                {if isset($input['expiration_year']) && $input['expiration_year'] == $year}selected{/if}>{$year}</option>
                    {/for}
                </select>
            </div>
        </div>
        <div class="control-group">
            <label for="cvc" class="control-label">{l s='CVV/CVV2 Code' mod='cardinity'}:</label>

            <div class="controls">
                <input type="tel" name="cvc" class="cvc only-number form-control"
                       value="{if (isset($input['cvc']))}{$input['cvc']}{/if}" id="cvc" autocomplete="off" placeholder="•••" required/>
                <a href="{if $smarty.const._PS_VERSION_ >= 1.5}{$link->getModuleLink('cardinity', 'cvv', [], true)}{else}{$this_path_ssl}cvv.php{/if}"
                   class="iframe" rel="nofollow">{l s='What is CVV?' mod='cardinity'}</a>
            </div>
        </div>
        <div class="control-group total-amount-block">
            <label class="control-label">{l s='Order Total' mod='cardinity'}:</label>

            <div class="controls">
                <span class="price">{$total} {$currency->iso_code|escape:'html'}</span>
            </div>
        </div>

        <p class="cart_navigation clearfix" id="cart_navigation">
            {if $smarty.const._PS_VERSION_ < 1.6}
                <input type="hidden" name="make_payment" value="make_payment" />
                <input type="submit" class="exclusive_large button-medium"
                       value="{l s='Make a payment' mod='cardinity'}" />
            {else}
                <button class="button btn btn-default button-medium" name="make_payment" value="make_payment"
                        type="submit">
                    <span>{l s='Make a payment' mod='cardinity'}<i class="icon-chevron-right right"></i></span>
                </button>
            {/if}
        </p>

        <input type='hidden' id='screen_width' name='screen_width' value='' />                
                <input type='hidden' id='screen_height' name='screen_height' value='' />                
                <input type='hidden' id='browser_language' name='browser_language' value='' />                
                <input type='hidden' id='challenge_window_size' name='challenge_window_size' value='' />
                <input type='hidden' id='color_depth' name='color_depth' value='' />                
                <input type='hidden' id='time_zone' name='time_zone' value='' />
    </form>
</div>
<script type="text/javascript">
{literal}
document.addEventListener("DOMContentLoaded", function() {
    document.getElementById("screen_width").value = window.innerWidth;
    document.getElementById("screen_height").value = window.innerHeight;
    document.getElementById("browser_language").value = navigator.language;
    document.getElementById("color_depth").value = screen.colorDepth;
    document.getElementById("time_zone").value = new Date().getTimezoneOffset();

    var availChallengeWindowSizes = [
                    [600, 400],
                    [500, 600],
                    [390, 400],
                    [250, 400]
    ];

    var cardinity_screen_width = window.innerWidth;
    var cardinity_screen_height = window.innerHeight;
    document.getElementById("challenge_window_size").value = 'full-screen';

                //display below 800x600        
    if (!(cardinity_screen_width > 800 && cardinity_screen_height > 600)) {                        
                    //find largest acceptable size
        availChallengeWindowSizes.every(function(element, index) {
            console.log(element);
            if (element[0] > cardinity_screen_width || element[1] > cardinity_screen_height) {
                                //this challenge window size is not acceptable
                console.log('skip');
                return true;
            } else {
                document.getElementById("challenge_window_size").value = element[0]+'x'+element[1];
                console.log(element[0]+'x'+element[1]);
                return false;
            }        
        });
    }
                
});
{/literal}
</script>