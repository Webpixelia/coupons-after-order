"use strict";
// Thanks to @ysdev_web
function hideMessage(element) {
  setTimeout(() => {
    element.css('display', 'none');
  }, 5000);
}

function validateCouponAmount(input, errorDivId, decimalSeparator) {
  let customErrorMessage = couponsAfterOrderTranslations.customErrorMessage;
  let validRegExp = new RegExp("^\\d*(\\" + decimalSeparator + "\\d*)?$");

  let value = input.value;
  let isValid = validRegExp.test(value);

  let errorDiv = document.getElementById(errorDivId);

  if (!isValid) {
    if (!errorDiv) {
      // Create error message div if needed
      let errorDivMessage = document.createElement('div');
      errorDivMessage.id = errorDivId;
      errorDivMessage.textContent = customErrorMessage;
      errorDivMessage.classList.add('wccao-error-tip');
      input.parentNode.insertBefore(errorDivMessage, input.nextSibling);
    }
    return false;
  } else {
    if (errorDiv) {
      // Remove error div if present
      errorDiv.parentNode.removeChild(errorDiv);
    }
    return true;
  }
}

function getDecimalSeparator(selector) {
  // Get the HTML element that contains the decimal separator using the provided selector
  const decimalSeparatorElement = document.querySelector(selector);

  // Ensure the element exists before attempting to extract the separator
  if (decimalSeparatorElement) {
    // Extract decimal separator from data-decimal attribute
    const decimalSeparator = decimalSeparatorElement.getAttribute('data-decimal');
    return decimalSeparator;
  } else {
    console.error(`Element with selector "${selector}" not found.`);
    return null; // Or a default separator if appropriate
  }
}

if (document.querySelector('.settings-tab')) {
  ///////////////////////////////////
  // Validation start date coupon  //
  ///////////////////////////////////
  document.addEventListener('DOMContentLoaded', function () {
    const enabledStartDate = document.querySelectorAll('input[name="wccao_coupons_after_order_availability_start_enabled"]');
    const divEnabledStartDate = document.getElementById('coupon_availability_date');
    const startDateInput = document.getElementById('coupon_availability_start_date');

    function updateDateFieldDisplay() {
      if (enabledStartDate[0].checked) {
        divEnabledStartDate.style.display = 'block';
        startDateInput.setAttribute('required', 'required');
      } else if (enabledStartDate[1].checked) {
        divEnabledStartDate.style.display = 'none';
        startDateInput.removeAttribute('required');
      }
    }

    // Update the display on page load
    updateDateFieldDisplay();

    // Add an event handler for option changes
    enabledStartDate.forEach(function (option) {
      option.addEventListener('change', function () {
        updateDateFieldDisplay();
      });
    });
  });

  let startDateInput = document.getElementById('coupon_availability_start_date');
  let validityDateInput = document.getElementById('coupon-validity-date');

  // Checks if elements exist before attaching event listeners
  if (startDateInput && validityDateInput) {
    startDateInput.addEventListener('change', function () {
      validateDates();
    });
  }

  function validateDates() {
    // Checks if elements exist before accessing their values
    if (!startDateInput || !validityDateInput) {
      return;
    }

    let startDate = new Date(startDateInput.value);
    let validityDate = new Date(validityDateInput.value);
    let errorDivId = 'wccao-error-tip';
    let errorMessageDatePosterior = couponsAfterOrderTranslations.errorMessageDatePosterior;

    // Remove existing error div if present
    let existingErrorDiv = document.getElementById(errorDivId);
    if (existingErrorDiv) {
      existingErrorDiv.parentNode.removeChild(existingErrorDiv);
    }

    // Check if start date is after validity date
    if (startDate.getTime() > validityDate.getTime()) {
      // Create error message div
      let errorDivMessage = document.createElement('div');
      errorDivMessage.id = errorDivId;
      errorDivMessage.textContent = errorMessageDatePosterior;
      errorDivMessage.classList.add('wccao-error-tip');

      // Insert error div after the start date input
      startDateInput.parentNode.insertBefore(errorDivMessage, startDateInput.nextSibling);

      // Clear the start date for the user to choose another
      startDateInput.value = '';
      setTimeout(() => {
        errorDivMessage.style.display = 'none';
      }, 5000);
    }
  };

  ///////////////////////////////////
  // Conditional fields admin page //
  ///////////////////////////////////
  document.addEventListener('DOMContentLoaded', function () {
    const validityTypeField = document.querySelectorAll('input[name="wccao_coupons_after_order_validity_type"]');
    const validityDaysDiv = document.getElementById('coupon-validity-days-div');
    const validityDateDiv = document.getElementById('coupon-validity-date-div');
    const validityDaysField = document.getElementById('coupon-validity-days');
    const validityDateField = document.getElementById('coupon-validity-date');

    function updateFieldDisplay() {
        if (validityTypeField[0].checked) {
          validityDaysDiv.style.display = 'block';
          validityDateDiv.style.display = 'none';
          validityDaysField.setAttribute('required', 'required');
          validityDateField.removeAttribute('required');
        } else if (validityTypeField[1].checked) {
          validityDaysDiv.style.display = 'none';
          validityDateDiv.style.display = 'block';
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
  /////////////////////////////////
  // Not allow decimal value in //
  /////////////////////////////////
  document.addEventListener('DOMContentLoaded', function () {
    const inputIds = ['coupon-validity-days', 'coupons-after-order-count', 'coupon-validity-usage-limit'];

    inputIds.forEach(function (inputId) {
      const currentInput = document.getElementById(inputId);

      if (currentInput) {
        currentInput.addEventListener('input', function () {
          this.value = parseInt(this.value, 10) || ''; // If the conversion fails, leave the value empty
        });
      }
    });
  });

  //////////////////////////////////////////
  // Value validation of the amount field //
  //////////////////////////////////////////
  const decimalSeparator = getDecimalSeparator('.wccao_input_price');

  const inputElement = document.getElementById('coupon-amount-min');
  inputElement.addEventListener('blur', function () {
    if (!validateCouponAmount(this, 'minAmountError', decimalSeparator)) {
      this.value = ''; // Clear field value if invalid
    }
  });

} else if (document.querySelector('.email-tab')) {
    ////////////////////////
    // Toggle email model //
    ////////////////////////
    let toggleEditorLink = document.getElementById('toggleEditorLink');
    let editorDiv = document.querySelector('.wccao-editor-email');
    let textDisplayedToggle = couponsAfterOrderTranslations.textDisplayedToggle;
    let textHiddenToggle = couponsAfterOrderTranslations.textHiddenToggle; 

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

    //////////////////////////////////////////////////
    // Display changes in live from button settings //
    //////////////////////////////////////////////////
    document.addEventListener('DOMContentLoaded', function () {
      // Function to update HTML elements based on form field values
      function updateElements() {
          // Retrieve form field values
          let emailBtTitle = document.getElementById('wccao_email_bt_title').value;
          let emailBtUrl = document.getElementById('wccao_email_bt_url').value;
          let emailBtColor = document.getElementById('wccao_email_bt_color').value;
          let emailBtBgColor = document.getElementById('wccao_email_bt_bg_color').value;
          let emailBtFontSize = document.getElementById('wccao_email_bt_font_size').value;

          // Update HTML element
          let emailButton = document.getElementById('emailButton');
          emailButton.href = emailBtUrl;
          emailButton.style.fontSize = emailBtFontSize + 'px';
          emailButton.style.color = emailBtColor;
          emailButton.style.background = emailBtBgColor;
          emailButton.textContent = emailBtTitle;
      }

      // Call function on page load
      updateElements();

      // Listen for changes in form fields
      let inputFields = ['wccao_email_bt_title', 'wccao_email_bt_url', 'wccao_email_bt_color', 'wccao_email_bt_bg_color', 'wccao_email_bt_font_size'];

      inputFields.forEach(function (fieldName) {
          document.getElementById(fieldName).addEventListener('input', function () {
              // Call the function on each change
              updateElements();
          });
      });
    });

    /////////////////////////////////////////
    // Display content from editor TinyMCE //
    /////////////////////////////////////////
    setTimeout(function () {
      // Editors
      let editors = tinyMCE.get('wccao_email_content');
      let previewElements = document.getElementById('preview_email_content');

      function updatePreviewContent() {
          let content = editors.getContent();
          previewElements.innerHTML = content;
      }

      // Update <p> elements on page load
      updatePreviewContent();

      // Add an event listener for content change
      editors.on('Change', function (e) {
        // Update <p> elements with changed content
        updatePreviewContent();
      });
    }, 1000);

    ///////////////////////////////////////////////
    // Call method wccao_send_email_test in ajax //
    ///////////////////////////////////////////////
    jQuery(document).ready(function($) {
      $('#wccao-email-test-link').on('click', function(event) {
          event.preventDefault(); // Empêche le formulaire de se soumettre normalement
          let $input = $('#wccao-email-user');
          let $link = $('#wccao-email-test-link');
          let userEmail = $input.val();
          let errorMessageText = couponsAfterOrderTranslations.errorMessageText;
          let errorMessageEmptyEmail = couponsAfterOrderTranslations.errorMessageEmptyEmail;
          let errorMessageFalseEmail = couponsAfterOrderTranslations.errorMessageFalseEmail;

          // Check if the email field is empty
          if (userEmail.trim() === '') {
            alert(errorMessageEmptyEmail);
            return;
          }

          // Check if the email address is valid
          if (!isValidEmail(userEmail)) {
            alert(errorMessageFalseEmail);
            return;
          }

          // Send an AJAX request to trigger the server-side function with the entered email
          $.ajax({
              type: 'POST',
              dataType: 'json',
              async: false,
              url: ajaxurl,
              data: {
                  action: 'wccao_send_email_test',
                  security: '<?php echo wp_create_nonce("wccao_send_email_test_nonce"); ?>',
                  user_email: userEmail
              },
              beforeSend: function() {
                $link.addClass('disabled-link');
              },
              success: function(response) {
                  $('#wccao-email-success').show();
                  hideMessage($('#wccao-email-success'));
              },
              error: function(xhr, status, error) {
                alert(errorMessageText);
              },
              complete: function() {
                $link.removeClass('disabled-link');
                $input.val('');
              }
            });
        });

        // Function to check if an email address is valid
        function isValidEmail(email) {
          // Regular expression for validating an email address
          let emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
          return emailRegex.test(email);
        }
    });
} else if (document.querySelector('.misc-tab')) {
  ///////////////////////////////////////////////
  // Call method wccao_send_email_test in ajax //
  ///////////////////////////////////////////////
  jQuery(document).ready(function ($) {
    $('#wccao_generate_manually_link').on('click', function (event) {
      event.preventDefault();
      let textArea = $('#wccao_coupons_after_order_emails_and_amounts');
      let messageSpan = $('#wccao-email-message-notice');
      let textAreaContent = textArea.val();
      // Translatable variables
      let errorUndefined = couponsAfterOrderTranslations.errorUndefined;
      let errorAjaxRequest = couponsAfterOrderTranslations.errorAjaxRequest;
      let successEmailsCouponsGenerated = couponsAfterOrderTranslations.successEmailsCouponsGenerated;
      let errorInvalidFormat = couponsAfterOrderTranslations.errorInvalidFormat;

      // Validation function
      function validateTextarea(textareaValue) {
        let regex = /^[a-zA-Z0-9_.+-]+@[a-zA-Z0-9-]+\.[a-zA-Z0-9-.]+;[0-9]+\.?[0-9]*$/gm;
        return regex.test(textareaValue);
      }

      if (validateTextarea(textAreaContent)) {
        // Construct an object with email-decimal value pairs
        let lines = textAreaContent.split(/\r\n|\r|\n/);
        let dataValuesArray = [];

        for (let i = 0; i < lines.length; i++) {
          let line = lines[i].trim();
          if (line !== '') {
            let [email, value] = line.split(';');
            dataValuesArray.push({ email: email, value: parseFloat(value) });
          }
        }
        
        let jsonData = JSON.stringify(dataValuesArray);

        // Send AJAX request
        $.ajax({
          type: 'POST',
          dataType: 'html',
          url: ajaxurl,
          data: {
            action: 'wccao_manually_generate_coupons',
            security: wccao_manually_generate_coupons_nonce,
            dataArray: jsonData,
          },
          success: function (response) {

          },
          error: function (xhr, status, error) {
            let errorMessage = xhr.responseJSON ? xhr.responseJSON.data.message : errorUndefined;
            let errorCode = xhr.responseJSON ? xhr.responseJSON.data.code : '';

            messageSpan.html(errorMessage);
            messageSpan.css('display', 'block');
            messageSpan.addClass('error');
            hideMessage(messageSpan);

            console.error(errorAjaxRequest, errorMessage, errorCode);
          },
          complete: function () {
            textArea.val('');
          },
        });

        // Display a success message
        messageSpan.html(successEmailsCouponsGenerated);
        messageSpan.css('display', 'block');
        messageSpan.addClass('success');
        hideMessage(messageSpan);
      } else {
        // Display an error message
        messageSpan.html(errorInvalidFormat);
        messageSpan.css('display', 'block');
        messageSpan.addClass('error');
        hideMessage(messageSpan);
      }
    });
  });
}