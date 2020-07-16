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
            document.getElementById("ThreeDForm").submit();
        });
        {/literal}
    </script>
    <section id="main">

        <p>
            {l s='If your browser does not start loading the page, press the button below. You will be sent back to this site after you authorize the transaction.' mod='cardinity'}
        </p>

        <form name="ThreeDForm" id="ThreeDForm" method="POST" action="{$cardinityUrl|escape:'htmlall':'UTF-8'}">
            <div class="form-group">
                <button class="btn btn-primary" type=submit>Click Here</button>
                <input type="hidden" name="PaReq" value="{$cardinityData|escape:'htmlall':'UTF-8'}" />
                <input type="hidden" name="TermUrl" value="{$cardinityCallbackUrl|escape:'htmlall':'UTF-8'}" />
                <input type="hidden" name="MD" value="{$cardinityPaymentId|escape:'htmlall':'UTF-8'}" />
            </div>
        </form>

    </section>
    
{/block}