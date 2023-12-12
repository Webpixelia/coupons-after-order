"use strict";
//////////////////////////////////////////////////
// Copy Coupon code in clipboard and add effect //
//////////////////////////////////////////////////
function copyCouponCode(couponCode) {
  // Select the text element to copy
  var couponTextElement = document.getElementById('coupon_' + couponCode);

  // Create a text range to select content
  var range = document.createRange();
  range.selectNode(couponTextElement);

  // Select text in range
  window.getSelection().removeAllRanges();
  window.getSelection().addRange(range);

  // Copy the text to the clipboard
  document.execCommand('copy');

  // Add the temporary visual effect class
  couponTextElement.classList.add('copy-effect');

  // Reset selection after copying
  window.getSelection().removeAllRanges();

  // Delete the class after 1000ms
  setTimeout(function() {
      couponTextElement.classList.remove('copy-effect');
  }, 1000);
}