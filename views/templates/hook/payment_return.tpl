{*
*  @author    Latitude Finance
*  @copyright Latitude Finance
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*}
<h1 style="text-transform: uppercase;">{l s="Order received" d="Modules.Latitude_Official.Admin"}</h1>
<p>{l s="Thank you. Your order has been received." d="Modules.Latitude_Official.Admin"}</p>

<ul>
    <li>{l s="Order number:" d="Modules.Latitude_Official.Admin"}<span style="font-weight: bold;">{$order_id}</span></li>
    <li>{l s="Date:" d="Modules.Latitude_Official.Admin"}<span style="font-weight: bold;">{$invoice_date}</span></li>
    <li>{l s="Email:" d="Modules.Latitude_Official.Admin"}<span style="font-weight: bold;">{$email}</span></li>
    <li>{l s="Total:" d="Modules.Latitude_Official.Admin"}<span style="font-weight: bold;">{$currency_code}{$order_total_amount}</span></li>
    <li>{l s="Payment method:" d="Modules.Latitude_Official.Admin"}<span style="font-weight: bold;">{$payment_method}</span></li>
</ul>
