{*
*  @author    Latitude Finance
*  @copyright Latitude Finance
*  @license   http://opensource.org/licenses/afl-3.0.php  Academic Free License (AFL 3.0)
*}
<div style="width: 100%">
    <img style="max-width: 100%;" class="lpay_snippet" src="{$images_api_url}{$images_api_version}/snippet.svg?amount={$amount}{if $full_block == true}&style=checkout{/if}&services={if $services }{$services}{else}LPAY{/if}{if $services == 'LPAYPLUS' || $services == 'LPAY,LPAYPLUS'}&terms={$payment_terms}{/if}" alt="{$gateway_name}" />
</div>
