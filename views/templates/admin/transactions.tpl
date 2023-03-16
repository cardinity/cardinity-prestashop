{*
* @author       Cardinity
* @link         https://cardinity.com
* @license      The MIT License (MIT)
*}
    <form method="POST" action="?controller={$smarty.get.controller|escape:'htmlall':'UTF-8'}&configure={$smarty.get.configure|escape:'htmlall':'UTF-8'}&token={$smarty.get.token|escape:'htmlall':'UTF-8'}">
        <div class="panel" id="fieldset_0">
            <div class="panel-heading">
                <i class="icon-eye"></i> Transaction History
            </div>                   
                <input type="hidden" name="subaction" value="downloadlog" />                
                <table>
                    <tr>
                        <td style="width: 80px; text-align: center;">Year</td>
                        <td style="width: 150px">
                            <select name='year'>
                               {$allYearOptions nofilter}
                            </select>
                        </td>
                        <td style="width: 80px; text-align: center;">Month</td>
                        <td style="width: 150px">
                            <select name='month'>
                               {$allMonthOptions nofilter}
                            </select>
                        </td>
                        <td class="actions" style="padding-left: 20px">
                            <span class="pull-right">
                                <button type="submit" class="btn btn-default" >
                                <i class="icon-download"></i> Download
                                </button>
                            </span>
                        </td>
                    </tr>
                </table>   
                
                <br/>
                <div>{$message|escape:'htmlall':'UTF-8'}</div>             
                <div class="panel-footer">
                </div>
            </div>
        </form>