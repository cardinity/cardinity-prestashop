<?php
/**
 * MIT License
 *
 * Copyright (c) 2023 Cardinity Payment Gateway
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to deal
 * in the Software without restriction, including without limitation the rights
 * to use, copy, modify, merge, publish, distribute, sublicense, and/or sell
 * copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in
 * all copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR
 * IMPLIED, INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY,
 * FITNESS FOR A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE
 * AUTHORS OR COPYRIGHT HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER
 * LIABILITY, WHETHER IN AN ACTION OF CONTRACT, TORT OR OTHERWISE, ARISING FROM,
 * OUT OF OR IN CONNECTION WITH THE SOFTWARE OR THE USE OR OTHER DEALINGS IN THE
 * SOFTWARE.
 *
 * DISCLAIMER
 *
 * Do not edit or add to this file if you wish to upgrade PrestaShop to newer
 * versions in the future. If you wish to customize PrestaShop for your
 * needs please refer to http://www.prestashop.com for more information.
 *
 *  @author    Cardinity <info@cardinity.com>
 *  @copyright 2023 Cardinity Payment Gateway
 *  @license   https://opensource.org/licenses/MIT  The MIT License
 *
 * Don't forget to prefix your containers with your own identifier
 * to avoid any conflicts with others containers.
 */

/**
 * Installs all tables in the mysql.sql file, using the default mysql connection
 */

/* Change and uncomment this when you need to: */

/*
mysql_connect('localhost', 'root');
if (mysql_errno())
{
    die(' Error '.mysql_errno().': '.mysql_error());
}
mysql_select_db('test');
*/

$sql = file_get_contents(dirname(__FILE__) . '/mysql.sql');
$ps = explode('#--SPLIT--', $sql);

foreach ($ps as $p) {
    $p = preg_replace('/^\s*#.*$/m', '', $p);

    mysql_query($p);
    if (mysql_errno()) {
        exit(' Error ' . mysql_errno() . ': ' . mysql_error());
    }
}
