"use strict";
// Thanks to @ysdev_web
if (document.querySelector('.settings-tab')) {
  document.addEventListener('DOMContentLoaded', function () {
    ///////////////////////////////////
    // Conditional fields admin page //
    ///////////////////////////////////
    const validityTypeField = document.querySelectorAll('input[name="coupons_after_order_validity_type"]');
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
        currentInput.addEventListener('input', function (event) {
          this.value = parseInt(this.value, 10) || ''; // If the conversion fails, leave the value empty
        });
      }
    });
    });

    //////////////////////////////////////////
    // Value validation of the amount field //
    //////////////////////////////////////////
    let decimalSeparator = getWooCommerceDecimalSeparator();

    function getWooCommerceDecimalSeparator() {
      // Get the HTML element that contains the decimal separator
      let decimalSeparatorElement = document.querySelector('.wccao_input_price');

      // Extract decimal separator from data-decimal attribute
      let decimalSeparator = decimalSeparatorElement.getAttribute('data-decimal');

      return decimalSeparator;
    }

    const inputElement = document.getElementById('coupon-amount-min');
    inputElement.addEventListener('blur', function () {
      if (!validateCouponAmount(this, 'minAmountError')) {
        this.value = ''; // Clear field value only if entered incorrectly
      }
    });

    function validateCouponAmount(input, errorDivId) {
      let customErrorMessage = couponsAfterOrderTranslations.customErrorMessage;
      let validRegExp = new RegExp("^\\d*(\\" + decimalSeparator + "\\d*)?$");

      let value = input.value;
      let isValid = validRegExp.test(value);

      let errorDiv = document.getElementById(errorDivId);

      if (!isValid) {
        if (!errorDiv) {
          // Create a new <div> element to display the error message if it does not exist
          let errorDivMessage = document.createElement('div');
          errorDivMessage.id = errorDivId;
          errorDivMessage.textContent = customErrorMessage;
          errorDivMessage.classList.add('wccao_error_tip');

          // Find the parent element of the input field
          let inputParent = input.parentNode;

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
      // Fonction pour mettre à jour les éléments HTML en fonction des valeurs des champs de formulaire
      function updateElements() {
          // Récupérer les valeurs des champs de formulaire
          let emailBtTitle = document.getElementById('wccao_email_bt_title').value;
          let emailBtUrl = document.getElementById('wccao_email_bt_url').value;
          let emailBtColor = document.getElementById('wccao_email_bt_color').value;
          let emailBtBgColor = document.getElementById('wccao_email_bt_bg_color').value;
          let emailBtFontSize = document.getElementById('wccao_email_bt_font_size').value;

          // Mettre à jour l'élément HTML
          let emailButton = document.getElementById('emailButton');
          emailButton.href = emailBtUrl;
          emailButton.style.fontSize = emailBtFontSize + 'px';
          emailButton.style.color = emailBtColor;
          emailButton.style.background = emailBtBgColor;
          emailButton.textContent = emailBtTitle;
      }

      // Appeler la fonction lors du chargement de la page
      updateElements();

      // Écouter les changements dans les champs de formulaire
      let inputFields = ['wccao_email_bt_title', 'wccao_email_bt_url', 'wccao_email_bt_color', 'wccao_email_bt_bg_color', 'wccao_email_bt_font_size'];

      inputFields.forEach(function (fieldName) {
          document.getElementById(fieldName).addEventListener('input', function () {
              // Appeler la fonction à chaque changement
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
}