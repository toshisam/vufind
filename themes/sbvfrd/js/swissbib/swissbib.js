'use strict';

/**
 * swissbib VuFind Javascript
 */
var swissbib = {

  /**
   * Initialize on ready.
   */
  init: function () {
    this.initUserVoiceFeedback();
    this.initBulkExport();

    this.AdvancedSearch.init();
  },


  initBulkExport: function () {
    var hasResults = $('form[name="bulkActionForm"]').find('a.singleLinkForBulk').length > 0;

    if (hasResults) {
      $('.dropdown-menu[role="export-menu"] li').click($.proxy(this.onBulkExportFormatClick, this));
    }
  },


  /**
   * Handle click on bulk export
   * Append list of record ids to existing link
   *
   * @param    {Object}    event
   */
  onBulkExportFormatClick: function (event) {
    var driver = $('div.search-tabs-box:has(ul)').length ? $('div.search-tabs-box li.active').attr('data-searchClass') : 'VuFind';
    var baseUrl = event.target.href,
      idArgs = [],
      fullUrl,
      ids = $('a.singleLinkForBulk').map(function () {
        return driver + '|' + this.href.split('/').pop()
      }).get();

    event.preventDefault();

    $.each(ids, function (index, id) {
      idArgs.push('i[]=' + id);
    });

    fullUrl = baseUrl + '&' + idArgs.join('&');

    window.open(fullUrl);
  },


  updatePageForLogin: function () {
    swissbib.updatePageForLoginParent();

    if ($('#user-favorites').length > 0) {
      Lightbox.addCloseAction(function () {
        document.location.reload(true);
      });
    }

    if (window.location.pathname.indexOf('Search/History') !== -1) {
      Lightbox.addCloseAction(function () {
        document.location.reload(true);
      });
    }
  },

  /**
   * function for the UserVoice feedback widget in swissbib green
   */
  initUserVoiceFeedback: function () {
    if (document.getElementById('feedback') === null) return;

    window.UserVoice = window.UserVoice || [];

    (function () {
      var uv = document.createElement('script');
      uv.type = 'text/javascript';
      uv.async = true;
      uv.src = '//widget.uservoice.com/JtF9LB73G7r3zwkipwE1LA.js';
      var s = document.getElementsByTagName('script')[0];
      s.parentNode.insertBefore(uv, s)
    })();

    UserVoice.push(['set', {
      accent_color: '#6aba2e',
      trigger_color: 'white',
      trigger_background_color: 'rgba(46, 49, 51, 0.6)'
    }]);

    UserVoice.push(['addTrigger', '#feedback', {
      mode: 'contact'
    }]);

    UserVoice.push(['autoprompt', {}]);
  },

  updatePageForLoginParent: function () {}
};


/**
 * Init swissbib on document ready
 */
$(document).ready(swissbib.init.bind(swissbib));


/**
 * hook into VuFind method
 */
swissbib.updatePageForLoginParent = updatePageForLogin;
updatePageForLogin = swissbib.updatePageForLogin;


/**
 * IE8 base64 support
 */
window.btoa = window.btoa || jQuery.base64.encode;
window.atob = window.atob || jQuery.base64.decode;
