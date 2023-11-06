// Conditional fields admin page
document.addEventListener('DOMContentLoaded', function() {
    const validityTypeField = document.querySelectorAll('input[name="coupons_after_order_validity_type"]');
    const validityDaysField = document.getElementById('coupon-validity-days-field');
    const validityDateField = document.getElementById('coupon-validity-date-field');

    function updateFieldDisplay() {
        if (validityTypeField[0].checked) {
            validityDaysField.style.display = 'block';
            validityDateField.style.display = 'none';
            validityDaysField.setAttribute('required', 'required');
            validityDateField.removeAttribute('required');
        } else if (validityTypeField[1].checked) {
            validityDaysField.style.display = 'none';
            validityDateField.style.display = 'block';
            validityDaysField.removeAttribute('required');
            validityDateField.setAttribute('required', 'required');
        }
    }

    // Update the display on page load
    updateFieldDisplay();

    // Add an event handler for option changes
    validityTypeField.forEach(function(option) {
        option.addEventListener('change', function() {
            updateFieldDisplay();
        });
    });
});

// Value validation of the amount field
function getWooCommerceDecimalSeparator() {
  // Get the HTML element that contains the decimal separator
  var decimalSeparatorElement = document.querySelector('.wccao_input_price');

  // Extract decimal separator from data-decimal attribute
  var decimalSeparator = decimalSeparatorElement.getAttribute('data-decimal');

  return decimalSeparator;
}
  
function validateCouponAmount(input, errorDivId) {
  const { __, _x, _n, sprintf } = wp.i18n;
  var decimalSeparator = getWooCommerceDecimalSeparator();
  var customErrorMessage = sprintf(__( 'Please enter a numeric value and the defined decimal separator (%s), without thousands separators or currency symbols', 'coupons-after-order' ), decimalSeparator);
  var validRegExp = new RegExp("^\\d*(\\" + decimalSeparator + "\\d*)?$");

  var value = input.value;
  var isValid = validRegExp.test(value);

  var errorDiv = document.getElementById(errorDivId);

  if (!isValid) {
    if (!errorDiv) {
      // Create a new <div> element to display the error message if it does not exist
      var errorDivMessage = document.createElement('div');
      errorDivMessage.id = errorDivId;
      errorDivMessage.textContent = customErrorMessage;
      errorDivMessage.classList.add('wccao_error_tip');

      // Find the parent element of the input field
      var inputParent = input.parentNode;

      // Insert the newly created <div> element right after the input field
      inputParent.insertBefore(errorDivMessage, input.nextSibling);
    }
  } else {
    if (errorDiv) {
      // Remove the <div> element if present (in case of previous error)
      errorDiv.parentNode.removeChild(errorDiv);
    }
  }
}