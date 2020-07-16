{*
* @author       Cardinity
* @link         https://cardinity.com
* @license      The MIT License (MIT)
*}
{extends "$layout"}

{block name="content"}
    <script type="text/javascript">
        {literal}
        document.addEventListener("DOMContentLoaded", function() {
            document.getElementById("redirectForm").submit();
        });
        {/literal}
    </script>
    <section id="main">

        <p>
            {l s='If your browser does not start loading the page, press the button below. You will be sent back to this site after you authorize the transaction.' mod='cardinity'}
        </p>

        <form name="checkout" method="POST" action="https://checkout.cardinity.com" id="redirectForm">
            <button class="btn btn-primary" type=submit>Click Here</button>
			<input type="hidden" name="amount" value="{$attributes['amount']}" />
			<input type="hidden" name="country" value="{$attributes['country']}" />
			<input type="hidden" name="currency" value="{$attributes['currency']}" />
			<input type="hidden" name="description" value="{$attributes['description']}" />
			<input type="hidden" name="order_id" value="{$attributes['order_id']}" />
			<input type="hidden" name="project_id" value="{$attributes['project_id']}" />
			<input type="hidden" name="return_url" value="{$attributes['return_url']}" />
			<input type="hidden" name="signature" value="{$attributes['signature']}" />
      </form>

    </section>
    
{/block}