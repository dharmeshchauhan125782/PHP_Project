<!-- ============================================================
     Booking Modal — shared across rooms.php and room-details.php
     Multi-step: 1) Stay details  2) Guest details  3) Confirm/pay
     Live price breakdown recalculated on every change (client-side
     preview only — the backend recalculates authoritatively).
     ============================================================ -->
<div class="modal-overlay" id="booking-modal">
    <div class="modal-box">
        <div class="modal-header">
            <h3 id="booking-modal-title">Book Your Stay</h3>
            <button class="modal-close" onclick="closeBookingModal()">&times;</button>
        </div>
        <div class="modal-body">
            <div class="steps-nav" id="booking-steps-nav">
                <div class="steps-nav__item active" data-step="1">1 · Stay Details</div>
                <div class="steps-nav__item" data-step="2">2 · Guest Details</div>
                <div class="steps-nav__item" data-step="3">3 · Confirm</div>
            </div>

            <div id="booking-form-msg" class="form-msg"></div>

            <!-- Step 1: Stay Details -->
            <div class="booking-step" data-step="1">
                <div class="form-row">
                    <div class="form-group">
                        <label>Check-in</label>
                        <input type="date" id="bk-checkin">
                    </div>
                    <div class="form-group">
                        <label>Check-out</label>
                        <input type="date" id="bk-checkout">
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label>Adults</label>
                        <div class="stepper">
                            <button type="button" onclick="stepGuest('adults',-1)">−</button>
                            <span id="bk-adults-val">2</span>
                            <button type="button" onclick="stepGuest('adults',1)">+</button>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Children</label>
                        <div class="stepper">
                            <button type="button" onclick="stepGuest('children',-1)">−</button>
                            <span id="bk-children-val">0</span>
                            <button type="button" onclick="stepGuest('children',1)">+</button>
                        </div>
                    </div>
                </div>
                <p style="font-size:12.5px;" id="bk-capacity-note">This room accommodates up to <strong id="bk-capacity-max">-</strong> guests.</p>

                <div class="form-group" style="margin-top:20px;">
                    <label>Meal Preferences</label>
                    <div class="checkbox-row" id="bk-meal-breakfast-row" onclick="toggleMeal('breakfast')">
                        <span class="checkbox-row__label"><input type="checkbox" id="bk-meal-breakfast" onclick="event.stopPropagation()"> Breakfast</span>
                        <span class="checkbox-row__price" id="bk-meal-breakfast-price">₹300/person/day</span>
                    </div>
                    <div class="checkbox-row" id="bk-meal-lunch-row" onclick="toggleMeal('lunch')">
                        <span class="checkbox-row__label"><input type="checkbox" id="bk-meal-lunch" onclick="event.stopPropagation()"> Lunch</span>
                        <span class="checkbox-row__price" id="bk-meal-lunch-price">₹500/person/day</span>
                    </div>
                    <div class="checkbox-row" id="bk-meal-dinner-row" onclick="toggleMeal('dinner')">
                        <span class="checkbox-row__label"><input type="checkbox" id="bk-meal-dinner" onclick="event.stopPropagation()"> Dinner</span>
                        <span class="checkbox-row__price" id="bk-meal-dinner-price">₹500/person/day</span>
                    </div>
                </div>

                <div class="price-breakdown" id="bk-price-breakdown" style="margin-top:20px;">
                    <div class="price-row"><span>Room</span><span id="bk-room-line">—</span></div>
                    <div class="price-row"><span>Nights</span><span id="bk-nights-line">—</span></div>
                    <div class="price-row"><span>Room subtotal</span><span id="bk-room-subtotal">₹0</span></div>
                    <div class="price-row" id="bk-breakfast-row" style="display:none;"><span>Breakfast</span><span id="bk-breakfast-amt">₹0</span></div>
                    <div class="price-row" id="bk-lunch-row" style="display:none;"><span>Lunch</span><span id="bk-lunch-amt">₹0</span></div>
                    <div class="price-row" id="bk-dinner-row" style="display:none;"><span>Dinner</span><span id="bk-dinner-amt">₹0</span></div>
                    <div class="price-row total"><span>Grand Total</span><span id="bk-grand-total">₹0</span></div>
                </div>
            </div>

            <!-- Step 2: Guest Details -->
            <div class="booking-step" data-step="2" style="display:none;">
                <div class="form-group">
                    <label>Guest Full Name</label>
                    <input type="text" id="bk-guest-name" placeholder="As per ID">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label>Email</label>
                        <input type="email" id="bk-guest-email" placeholder="you@example.com">
                    </div>
                    <div class="form-group">
                        <label>Phone</label>
                        <input type="text" id="bk-guest-phone" placeholder="+91 98765 43210">
                    </div>
                </div>
            </div>

            <!-- Step 3: Confirm -->
            <div class="booking-step" data-step="3" style="display:none;">
                <div id="bk-confirm-summary"></div>
            </div>
        </div>
        <div class="modal-footer">
            <button class="btn btn-ghost" id="bk-back-btn" onclick="bookingPrevStep()" style="visibility:hidden;">&larr; Back</button>
            <button class="btn btn-brass" id="bk-next-btn" onclick="bookingNextStep()">Continue &rarr;</button>
        </div>
    </div>
</div>

<!-- Booking success modal -->
<div class="modal-overlay" id="booking-success-modal">
    <div class="modal-box" style="max-width:480px;">
        <div class="modal-body" style="text-align:center; padding:48px 32px;">
            <div style="font-size:44px; margin-bottom:12px;">🗝️</div>
            <h3>Booking Request Submitted!</h3>
            <p style="margin-bottom:20px;">Your reservation is awaiting confirmation from our team.</p>
            <div class="price-breakdown" style="text-align:left;" id="bk-success-details"></div>
            <button class="btn btn-brass btn-block" style="margin-top:24px;" onclick="closeBookingSuccessModal()">Done</button>
        </div>
    </div>
</div>

<script src="<?= BASE_URL ?>/Frontend/js/booking.js"></script>
