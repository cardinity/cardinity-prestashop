<html>
<head>
    <title>3-D Secure v2</title>
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

<form name="ThreeDForm" id="ThreeDForm" method="POST" action="{$cardinityAcsUrl|escape:'htmlall':'UTF-8'}">
    <div class="form-group">
        <button class="btn btn-primary" type=submit>Click Here</button>
        <input type="hidden" name="creq" value="{$cardinityCreqData|escape:'htmlall':'UTF-8'}" />
        <input type="hidden" name="threeDSSessionData" value="{$cardinityThreeDSSessionData|escape:'htmlall':'UTF-8'}" />
    </div>
</form>

</body>
</html>