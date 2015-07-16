'use strict';

swissbib.Settings = {

  init: function () {
    this.observeFormChange();
  },

  observeFormChange: function () {
    $('#settings-form').find('select').change(this.onFormChange);
  },

  onFormChange: function (event) {
    $(this).parents('form').submit();
  }
};