<html>
<head>
    <title>3-D Secure</title>
</head>
<script type="text/javascript">
    {literal}
    $(document).ready(function () {
        $('#ThreeDForm').submit();
    });
    {/literal}
</script>
<body>

<p>
    {l s='If your browser does not start loading the page, press the button below. You will be sent back to this site after you authorize the transaction.' mod='cardinity'}
</p>

<form name="ThreeDForm" id="ThreeDForm" method="POST" action="{$cardinityUrl|escape:'html'}">
    <button class="btn btn-primary" type=submit>Click Here</button>
    <input type="hidden" name="PaReq" value="{$cardinityData|escape:'html'}" />
    <input type="hidden" name="TermUrl" value="{$cardinityCallbackUrl|escape:'html'}" />
    <input type="hidden" name="MD" value="{$cardinityPaymentId|escape:'html'}" />
</form>
</body>
</html>