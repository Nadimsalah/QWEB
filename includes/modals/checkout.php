<div id="checkout-modal">
            <div class="co-header">
                <div class="co-title">Checkout</div>
                <button onclick="closeCheckoutModal()" style="width: 32px; height: 32px; border-radius: 50%; background: rgba(255,255,255,0.1); border: none; color: #fff; cursor: pointer; display: flex; align-items: center; justify-content: center;"><i class="fa-solid fa-xmark"></i></button>
            </div>
            <div class="co-scrollable">
                <div class="pm-section-title">Order Items</div>
                <div id="co-items-list" style="margin-bottom: 24px;"></div>
                
                <div class="pm-section-title">Payment Method</div>
                <button class="pay-method-btn active" id="pay-btn-COD" onclick="selectPayment('COD')">
                    <div class="pay-icon"><i class="fa-solid fa-money-bill-wave"></i></div>
                    <div class="pay-text">Cash on Delivery</div>
                    <i class="fa-solid fa-circle-check pay-check"></i>
                </button>
                <button class="pay-method-btn" id="pay-btn-CARD" onclick="selectPayment('CARD')">
                    <div class="pay-icon"><i class="fa-regular fa-credit-card"></i></div>
                    <div class="pay-text">Bank Card (Visa/Mastercard)</div>
                    <i class="fa-solid fa-circle-check pay-check"></i>
                </button>
                <button class="pay-method-btn" id="pay-btn-QOON" onclick="selectPayment('QOON')">
                    <div class="pay-icon" style="background:#f50057; color:#fff;"><i class="fa-solid fa-wallet"></i></div>
                    <div class="pay-text">Qoon Pay</div>
                    <i class="fa-solid fa-circle-check pay-check"></i>
                </button>

                <div style="margin-top: 24px; padding-top: 16px; border-top: 1px dashed rgba(255,255,255,0.1); display: flex; justify-content: space-between; align-items: center;">
                    <span style="font-size: 16px; font-weight: 600; color: rgba(255,255,255,0.7);">Grand Total</span>
                    <span style="font-size: 22px; font-weight: 800; color: #fff;" id="co-grand-total">0 MAD</span>
                </div>
                
                <button class="co-checkout-btn" id="confirm-order-btn" onclick="confirmOrderMock()">Confirm Order</button>
            </div>
        </div>