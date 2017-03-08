<form action="{$formUrl}" method="post">
    <fieldset>
        <legend>{l s='Settings' mod='cardinity'}</legend>
        <p class="description">
            {l s='Please, enter your Cardinity credentials. You can find them on your Cardinity members area under Integration -> API Settings.' mod='cardinity'}
        </p>
        <table border="0" width="500" cellpadding="0" cellspacing="0" id="form">
            <tr>
                <td width="130" style="height: 35px;">{l s='Consumer Key' mod='cardinity'}</td>
                <td>
                    <input type="text" name="consumer_key" value="{$consumerKey|escape:'html'}" style="width: 350px;" />
                </td>
            </tr>
            <tr>
                <td width="130" style="height: 35px;">{l s='Consumer Secret' mod='cardinity'}</td>
                <td>
                    <input type="text" name="consumer_secret" value="{$consumerSecret|escape:'html'}" style="width: 350px;" />
                </td>
            </tr>
            <tr>
                <td colspan="2" align="center">
                    <input class="button" name="btnSubmit" value="{l s='Save' mod='cardinity'}" type="submit" />
                </td>
            </tr>
        </table>
    </fieldset>
</form>