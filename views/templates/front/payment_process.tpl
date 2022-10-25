{*
* @author       Cardinity
* @link         https://cardinity.com
* @license      The MIT License (MIT)
*}
{extends "$layout"}

{block name="content"}
    
<section id="main">
    
    <header class="page-header">
        <h1>{l s='Credit or Debit Card Payment' mod='cardinity'}</h1>
    </header>
    
    <section class="page-content card card-block">
    
        {capture name=path}{l s='Credit or Debit Card Payment' mod='cardinity'}{/capture}

        <div class="cardinity">
            {*if (isset($errors) && ! empty($errors))}
                <div class="alert alert-danger">
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
                    <ul>
                        {foreach from=$errors item=error}
                            <li>{$error|escape:'htmlall':'UTF-8'}</li>
                        {/foreach}
                    </ul>
                </div>
            {/if*}
            
            <div class="form-group text-xs-center">
                <div class="top_block">
                    <p class="logo">
                        <img src="{$this_path|escape:'htmlall':'UTF-8'}views/img/mastercard.jpg" alt="MasterCard" height="30" />
                        <img src="{$this_path|escape:'htmlall':'UTF-8'}views/img/maestro.jpg" alt="Maestro" height="30" />
                        <img src="{$this_path|escape:'htmlall':'UTF-8'}views/img/visa.jpg" alt="Visa" height="30" />
                        <img src="{$this_path|escape:'htmlall':'UTF-8'}views/img/visa_secure.png" alt="Verified by Visa" height="30" />
                        <img src="{$this_path|escape:'htmlall':'UTF-8'}views/img/mastercard_secure.png" alt="MasterCard SecureCode" height="30" />
                        <img src="{$this_path|escape:'htmlall':'UTF-8'}views/img/pci.jpg" alt="PCI" height="30" />
                    </p>

                    <p>
                        {l s='Enter your Credit Card or Debit Card details below.' mod='cardinity'}
                    </p>
                </div>
            </div>
            <div class="clearfix"></div>
            {if isset($action)}
                <form action="{$action|escape:'htmlall':'UTF-8'}" id="cardinity-form">
            {else}
                <form action="{$link->getModuleLink('cardinity', 'process', ['order_id' => $orderId], true)|escape:'htmlall':'UTF-8'}"
                        method="post" id="cardinity-form" class="form-horizontal">
            {/if}
                <input type="hidden" name="order_id" value="{$orderId|intval}" />

                <div class="form-group row">
                    <label for="cardHolder" class="col-xs-4 col-form-label">{l s='Name on Card' mod='cardinity'}:</label>
                    
                    <div class="col-xs-8">
                        <input type="text" name="card_holder" class="card-holder form-control"
                                   value="{if (isset($input['card_holder']))}{$input['card_holder']|escape:'htmlall':'UTF-8'}{/if}" id="cardHolder"
                                   maxlength="32" placeholder="{l s='Name on Card' mod='cardinity'}" required />
                    </div>
                </div>
                <div class="form-group row">
                    <label for="cardPan" class="col-xs-4 col-form-label">{l s='Card Number' mod='cardinity'}:</label>

                    <div class="col-xs-8">
                        <input type="tel" name="card_pan" value="{if (isset($input['card_pan']))}{$input['card_pan']|escape:'htmlall':'UTF-8'}{/if}"
                               class="card-pan only-number form-control" id="cardPan" maxlength="24" placeholder="•••• •••• •••• ••••" required />
                    </div>
                </div>
                <div class="form-group row">
                    <label for="expirationMonth" class="col-xs-4 col-form-label">{l s='Expiration Date' mod='cardinity'}:</label>
                    
                    <div class="col-xs-4">
                        <select name="expiration_month" class="expiration-month form-control" id="expirationMonth">
                            {for $month=1 to 12}
                                <option value="{$month|intval}"
                                        {if isset($input['expiration_month']) && $input['expiration_month'] == $month}selected{/if}>
                                    {$month|escape:'htmlall':'UTF-8'}
                                </option>
                            {/for}
                        </select>
                    </div>
                    
                    <div class="col-xs-4">
                        <select name="expiration_year" class="expiration-year form-control" id="expirationYear">
                            {for $year=date('Y') to date('Y', strtotime('+ 19 years'))}
                                <option value="{$year|intval}"
                                        {if isset($input['expiration_year']) && $input['expiration_year'] == $year}selected{/if}>
                                    {$year|escape:'htmlall':'UTF-8'}
                                </option>
                            {/for}
                        </select>
                    </div>
                </div>
                <div class="form-group row">
                    <label for="cvc" class="col-xs-4 col-form-label">{l s='CVV/CVV2 Code' mod='cardinity'}:</label>

                    <div class="col-xs-8">
                        <input type="tel" name="cvc" class="cvc only-number form-control"
                               value="{if (isset($input['cvc']))}{$input['cvc']|escape:'htmlall':'UTF-8'}{/if}" id="cvc" autocomplete="off" placeholder="•••" required/>
                        <a href="{if $smarty.const._PS_VERSION_ >= 1.5}{$link->getModuleLink('cardinity', 'cvv', [], true)|escape:'htmlall':'UTF-8'}{else}{$this_path_ssl|escape:'htmlall':'UTF-8'}cvv.php{/if}"
                           class="iframe" rel="nofollow">{l s='What is CVV?' mod='cardinity'}</a>
                    </div>
                </div>
                <div class="form-group row total-amount-block">
                    <label class="col-xs-4 control-label">{l s='Order Total' mod='cardinity'}:</label>

                    <div class="col-xs-8">
                        {if isset($currency->iso_code)}
                            <strong class="bold form-control-static">{$total|escape:'htmlall':'UTF-8'} {$currency->iso_code|escape:'htmlall':'UTF-8'}</strong>
                        {else}
                            <strong class="bold form-control-static">{$total|escape:'htmlall':'UTF-8'} {$currency['iso_code']|escape:'htmlall':'UTF-8'}</strong>
                        {/if}
                    </div>
                </div>
                
                <div class="form-group row">
                    <div class="col-xs-4">
                    </div>
                    <div class="col-xs-8">
                        <button class="button btn btn-primary"
                                name="make_payment"
                                value="make_payment"
                                type="submit">
                            {l s='Make a payment' mod='cardinity'}
                        </button>
                    </div>
                </div>

                <input type='hidden' id='screen_width' name='screen_width' value='800' />                
                <input type='hidden' id='screen_height' name='screen_height' value='600' />                
                <input type='hidden' id='browser_language' name='browser_language' value='en-US' />                
                <input type='hidden' id='challenge_window_size' name='challenge_window_size' value='full-screen' />
                <input type='hidden' id='color_depth' name='color_depth' value='24' />                
                <input type='hidden' id='time_zone' name='time_zone' value='-360' />

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
    
    </section>
    
</section>
    
{/block}