/**
 * Manage favorite actions on record holding tab
 *
 */
swissbib.HoldingFavorites = {

  /**
   * @var {String}
   */
  baseUrl: '/MyResearch/Favorites',


  /**
   * Initialize record
   *
   */
  initRecord: function () {
    this.baseUrl = window.path + this.baseUrl;

    this.setupClickHandlers();
  },


  /**
   * Setup click handlers for add and remove of favorite institutions
   *
   */
  setupClickHandlers: function () {
    var that = this, allMiniActions, notFavorised,
        favoriteInstitutionCodes = this.getFavoriteInstitutionCodes();

    $.each(favoriteInstitutionCodes, function (index, institutionCode) {
      $('.miniactions-' + institutionCode).find('.institutionFavorite')
          .addClass('miniaction_favorite_remove')
          .click($.proxy(that.onRemoveFavoriteIconClick, that))
          .data('favorised', true);
    });

    allMiniActions = $('#holdings-tab').find('.miniactions');

    notFavorised = $.grep(allMiniActions, function (node, index) {
      return $(node).find('.institutionFavorite').data('favorised') !== true;
    });

    $(notFavorised).find('.institutionFavorite')
        .addClass('miniaction_favorite_add')
        .click($.proxy(this.onAddFavoriteIconClick, this));
  },


  /**
   * Handle remove click
   *
   * @param  {Object}  event
   */
  onRemoveFavoriteIconClick: function (event) {
    var institutionBox = $(event.target).parents('.institutionBox').get(0),
        institutionCode = institutionBox.id.split('-')[3];

    this.updateFavorite(institutionCode, 'delete');
  },


  /**
   * Handle add click
   *
   * @param  {Object}  event
   */
  onAddFavoriteIconClick: function (event) {
    var institutionBox = $(event.target).parents('.institutionBox').get(0),
        institutionCode = institutionBox.id.split('-')[3];

    this.updateFavorite(institutionCode, 'add');
  },


  /**
   * Send favorite update request (add or delete) for institution
   *
   * @param  {String}  institutionCode
   * @param  {String}  action
   * @param  {Function}  [callback]
   */
  updateFavorite: function (institutionCode, action, callback) {
    var url = this.baseUrl + '/' + action,
        data = {
          institution: institutionCode
        },
        success = function (response) {
          if (callback) {
            callback(institutionCode, action, response);
          } else {
            $('body').mask(vufindString.favoriteReload);
            location.reload();
          }
        };

    $.post(url, data, success);
  },


  /**
   * Get codes of favorite institutions (in favorite box)
   *
   * @returns {String[]}
   */
  getFavoriteInstitutionCodes: function () {
    var favoriteTogglers = $('#holdings-favorite').find('.institutionToggler');

    return $.map(favoriteTogglers, function (node, index) {
      return $(node).attr('id').split('-').pop();
    });
  },

  saveExpandedGroups: function() {
      var expandedGroupIds = [];

      $("#accordion .in").each(function (index) {
          var currentItem = $(this);

          //only saves state of first hierachie level
          if (currentItem.attr('level') == 1) {
              expandedGroupIds.push(currentItem.attr('id'));
          }
      });

      if(expandedGroupIds.length > 0) {
          $.cookie('expandedGroups', JSON.stringify(expandedGroupIds), {path: window.location.pathname});
      } else {
          $.cookie('expandedGroups', null, {path: window.location.pathname});
      }
  },

  getParameterByName: function(name) {
    name = name.replace(/[\[]/, "\\[").replace(/[\]]/, "\\]");
    var regex = new RegExp("[\\?&]" + name + "=([^&#]*)"),
        results = regex.exec(location.search);
    return results === null ? null : decodeURIComponent(results[1].replace(/\+/g, " "));
  }

};

/**
* Remains state of expanded accordion group
*/
$(document).ready(function () {
    //when a group is shown, save it as the active accordion group
    $("#accordion").on('shown.bs.collapse', function (e) {
        var target = $(e.target);
        //level 1 means group
        if(target.attr('level') == 1) {
            swissbib.HoldingFavorites.saveExpandedGroups();
        }
    });

    //when a group is closed, remove it as the active accordion group
    $("#accordion").on('hidden.bs.collapse', function (e) {
        var target = $(e.target);
        //level 1 means group
        if(target.attr('level') == 1) {
            //collapse childern
            target.find('.in').collapse('hide');
            swissbib.HoldingFavorites.saveExpandedGroups();
        }
    });


    //open direct link library
    var expandlib = swissbib.HoldingFavorites.getParameterByName('expandlib');
    if (expandlib != null) {
        $("#accordion #collapse-" + expandlib.split('-')[0]).collapse('show');
        $("#accordion a[href='#collapse-" + expandlib + "']").click();
    }

    //on (re)load - open previously expanded groups. if none, open favorites as default
    var expandedGroupIds = JSON.parse($.cookie('expandedGroups'));
    if (expandedGroupIds != null) {
        $.each((expandedGroupIds), function (index, value){
            $("#" + value).collapse('show');
        });
    } else {
        $("#accordion #collapse-favorite").collapse('show')
    }
});

