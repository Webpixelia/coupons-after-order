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
var decimalSeparator = getWooCommerceDecimalSeparator();

function getWooCommerceDecimalSeparator() {
  // Get the HTML element that contains the decimal separator
  var decimalSeparatorElement = document.querySelector('.wccao_input_price');

  // Extract decimal separator from data-decimal attribute
  var decimalSeparator = decimalSeparatorElement.getAttribute('data-decimal');

  return decimalSeparator;
}

const inputElement = document.getElementById('coupon-amount-min');
inputElement.addEventListener('blur', function () {
  if (!validateCouponAmount(this, 'minAmountError')) {
    this.value = ''; // Clear field value only if entered incorrectly
  }
});
  
function validateCouponAmount(input, errorDivId) {
  var customErrorMessage = couponsAfterOrderTranslations.customErrorMessage;
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
    return false;
  } else {
    if (errorDiv) {
      // Remove the <div> element if present (in case of previous error)
      errorDiv.parentNode.removeChild(errorDiv);
    }
    return true;
  }
}

// Toggle email model
var toggleEditorLink = document.getElementById('toggleEditorLink');
var editorDiv = document.querySelector('.wccao-editor-email');
var textDisplayedToggle = couponsAfterOrderTranslations.textDisplayedToggle;
var textHiddenToggle = couponsAfterOrderTranslations.textHiddenToggle;

toggleEditorLink.addEventListener('click', function (event) {
    event.preventDefault();
    if (editorDiv.style.display === 'none') {
        editorDiv.style.display = 'block';
        toggleEditorLink.textContent = textHiddenToggle;
    } else {
        editorDiv.style.display = 'none';
        toggleEditorLink.textContent = textDisplayedToggle;
    }
});

// Display content from editor TinyMCE and input "coupon-prefix"
setTimeout(function () {
  // Editors
  var editors = [tinyMCE.get('editor_before_email'), tinyMCE.get('editor_after_email')];
  var previewElements = [document.getElementById('preview_before'), document.getElementById('preview_after')];

  function updatePreviewContent() {
    for (var i = 0; i < editors.length; i++) {
      var content = editors[i].getContent();
      previewElements[i].innerHTML = content;
    }
  }

  // Update <p> elements on page load
  updatePreviewContent();

  // Add an event listener for content change
  for (var i = 0; i < editors.length; i++) {
    editors[i].on('Change', function (e) {
      // Update <p> elements with changed content
      updatePreviewContent();
    });
  }

  // Input field "coupon-prefix"
  var inputCouponPrefix = document.getElementById('coupon-prefix');
  var value = inputCouponPrefix.value;
  if (value !== '') {
    var spanElements = document.querySelectorAll('.prefix-coupon');
    for (var i = 0; i < spanElements.length; i++) {
      spanElements[i].innerHTML = value;
    }
  }

  // Add an event listener for content change on coupon-prefix
  var inputCouponPrefix = document.getElementById('coupon-prefix');

  inputCouponPrefix.addEventListener('input', function () {
    var value = inputCouponPrefix.value;
    var spanElements = document.querySelectorAll('.prefix-coupon');

    for (var i = 0; i < spanElements.length; i++) {
      if (value === '') {
        spanElements[i].innerHTML = 'ref';
      } else {
        spanElements[i].innerHTML = value;
      }
    }
  });
}, 1000);