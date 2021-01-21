    <form method="POST" action="?controller={$smarty.get.controller}&configure={$smarty.get.configure}&token={$smarty.get.token}">
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
                               {$allYearOptions}
                            </select>
                        </td>
                        <td style="width: 80px; text-align: center;">Month</td>
                        <td style="width: 150px">
                            <select name='month'>
                               {$allMonthOptions}
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
                <div>{$message}</div>             
                <div class="panel-footer">
                </div>
            </div>
        </form>