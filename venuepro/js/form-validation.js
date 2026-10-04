/**
 * VenuePro — Global Form Validation
 * F-1: Required fields (marked with * in label OR with HTML required attribute)
 * get a red border + red label when empty on submit attempt.
 * Submission is blocked until all required fields are filled.
 */
(function () {
  'use strict';

  var ERROR_BORDER  = '2px solid #dc2626';
  var ERROR_BG      = '#fff5f5';
  var ERROR_COLOR   = '#dc2626';
  var NORMAL_BORDER = '';
  var NORMAL_BG     = '';
  var NORMAL_COLOR  = '';

  function getLabel(field) {
    // Try <label for="id">, then closest wrapping label, then previous sibling
    var lbl = null;
    if (field.id) lbl = document.querySelector('label[for="' + field.id + '"]');
    if (!lbl)      lbl = field.closest('label');
    if (!lbl) {
      var prev = field.previousElementSibling;
      if (prev && prev.tagName === 'LABEL') lbl = prev;
    }
    if (!lbl) {
      // Walk up to parent and look for label sibling
      var parent = field.parentElement;
      if (parent) {
        lbl = parent.querySelector('label');
        if (!lbl && parent.parentElement) lbl = parent.parentElement.querySelector('label');
      }
    }
    return lbl;
  }

  function isRequired(field) {
    if (field.hasAttribute('required')) return true;
    var lbl = getLabel(field);
    return lbl && lbl.textContent.includes('*');
  }

  function markError(field) {
    field.style.border    = ERROR_BORDER;
    field.style.background = ERROR_BG;
    field.dataset.vpError = '1';
    var lbl = getLabel(field);
    if (lbl) {
      lbl.dataset.vpOrigColor = lbl.style.color || '';
      lbl.style.color = ERROR_COLOR;
      lbl.dataset.vpError = '1';
    }
  }

  function clearError(field) {
    field.style.border    = NORMAL_BORDER;
    field.style.background = NORMAL_BG;
    delete field.dataset.vpError;
    var lbl = getLabel(field);
    if (lbl && lbl.dataset.vpError) {
      lbl.style.color = lbl.dataset.vpOrigColor || NORMAL_COLOR;
      delete lbl.dataset.vpError;
      delete lbl.dataset.vpOrigColor;
    }
  }

  function validateForm(form) {
    var fields = form.querySelectorAll('input, textarea, select');
    var hasError = false;
    var firstError = null;

    fields.forEach(function (field) {
      // Skip hidden, submit, button, file fields
      if (['hidden', 'submit', 'button', 'reset', 'file', 'checkbox', 'radio'].includes(field.type)) return;
      if (!isRequired(field)) return;

      var empty = field.value.trim() === '';
      if (empty) {
        markError(field);
        if (!firstError) firstError = field;
        hasError = true;
      } else {
        clearError(field);
      }
    });

    if (firstError) firstError.focus();
    return !hasError;
  }

  // Clear error styling as user types / selects
  function attachClearListeners(form) {
    form.querySelectorAll('input, textarea, select').forEach(function (field) {
      if (['hidden', 'submit', 'button', 'reset', 'file'].includes(field.type)) return;
      field.addEventListener('input',  function () { if (field.value.trim()) clearError(field); });
      field.addEventListener('change', function () { if (field.value.trim()) clearError(field); });
    });
  }

  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('form').forEach(function (form) {
      // Skip forms that handle their own submit (they use e.preventDefault already)
      // We intercept at the capture phase before custom handlers
      attachClearListeners(form);

      form.addEventListener('submit', function (e) {
        var valid = validateForm(form);
        if (!valid) {
          e.preventDefault();
          e.stopImmediatePropagation();
        }
      }, true); // capture phase — runs before other handlers
    });
  });
})();
