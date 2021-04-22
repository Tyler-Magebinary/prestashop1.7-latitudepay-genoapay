{if $custom_refund && $available_amount}
    <div class="btn latitudeRefund" id="refundAction"
         style="display: none; border: 1px #ccc solid; cursor: pointer; margin-left: 5px;">
        <span class="refundBoxLabel">
            <i class="icon-exchange"></i>
            {l s=$payment_gateway_name} Full Refund
            {if $total_paid > $available_amount}
                (Gateway refund only)
            {/if}
        </span>
        <div id="latitudeRefundBox" style="display: none;">
            <input name="refund_amount" type="text" placeholder="Refund amount" value="{l s=$available_amount }"
                   data-maximum="{l s=$available_amount }" readonly>
            <button class="btn  btn-refund" style="margin: 0 2px; border: 1px #ccc solid"
                    data-return_url="{$refund_url}" data-query="{$query_data}">Refund
            </button>
            <button class="btn btn-cancel" style="border: 1px #ccc solid">Cancel</button>
        </div>
    </div>
    <script type="text/html" id="LatitudePayParitalRefundTemplate">
        <div class="cancel-product-element form-group refund-latitude-finance" style="display: block">
            <div class="checkbox">
                <div class="md-checkbox md-checkbox-inline">
                    <label><input type="checkbox" id="doPartialRefundLatitude" name="doPartialRefundLatitude"
                                  material_design="material_design" value="1">
                        <i class="md-checkbox-control"></i>Refund {l s=$payment_gateway_name}</label>
                </div>
            </div>
        </div>
    </script>
{/if}

{literal}
    <script type="text/javascript">
        ;(function ($, window, document) {
            if ($('#refundAction').length > 0) {
                var origPartialBtn = $('.partial-refund-display');
                $(origPartialBtn).show().after($('#refundAction').show());
                var latitudeRefundBoxContainer = $("#refundAction");
                var refundAmountInput = latitudeRefundBoxContainer.find("input[name='refund_amount']");
                var refundBtn = latitudeRefundBoxContainer.find(".btn-refund");

                latitudeRefundBoxContainer.on("click", function () {
                    latitudeRefundBoxContainer.find(".refundBoxLabel").hide();
                    $("#latitudeRefundBox").css('display', 'flex');
                });
                latitudeRefundBoxContainer.find(".btn-cancel").on("click", function (e) {
                    e.stopPropagation();
                    latitudeRefundBoxContainer.find(".refundBoxLabel").show();
                    $("#latitudeRefundBox").css('display', 'none');
                });
                refundBtn.on("click", function (e) {
                    e.stopPropagation();
                    if (!refundBtn.prop('disabled')) {
                        if (isValidRefundAmount(refundAmountInput)) {
                            var returnUrl = refundBtn.data('return_url');
                            var queryData = refundBtn.data('query');
                            processRefund(refundBtn, returnUrl, queryData);
                        } else {
                            alert("The maximum allowed refund amount is " + refundAmountInput.data('maximum') + " only!");
                        }
                    }
                });
            }
            $(document).on('click', '.partial-refund-display', function () {
                if ($('#doPartialRefundLatitude').length === 0) {
                    $('.refund-checkboxes-container').append($("#LatitudePayParitalRefundTemplate").html());
                }
            });
        })(jQuery, window, document);

        function processRefund(refundBtn, refundUrl, queryData) {
            refundBtn.prop('disabled', true);
            refundBtn.text("Refunding...");
            $.ajax({
                url: refundUrl,
                type: 'GET',
                data: {
                    ajax: true,
                    query_data: queryData
                },
                success: function (response) {
                    if (response.status === 'success') {
                        refundBtn.text("Success...");
                        setTimeout(window.location.reload(), 500)
                    } else {
                        refundBtn.text("Try again");
                        refundBtn.prop('disabled', false);
                        alert(response.message);
                    }
                },
                error: function (response) {
                    refundBtn.text("Try again");
                    alert(response.error);
                }
            });
        }

        function isValidRefundAmount(refundAmountInput) {
            return refundAmountInput.val() <= refundAmountInput.data('maximum');
        }
    </script>
{/literal}