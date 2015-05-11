/**
 * Manage accordion auto expand/collapse actions on record holding tab
 *
 */
swissbib.Accordion = {

  /**
   * @var {String}
   */
  cookieName: 'expandedGroups',


  /**
   * Saves state of expanded groups
   *
   */
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
          $.cookie(swissbib.Accordion.cookieName, JSON.stringify(expandedGroupIds), {path: window.location.pathname});
      } else {
          $.cookie(swissbib.Accordion.cookieName, null, {path: window.location.pathname});
      }
  },

  /**
   * Get QueryString Parameter
   *
   */
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
            swissbib.Accordion.saveExpandedGroups();
        }
    });

    //when a group is closed, remove it as the active accordion group
    $("#accordion").on('hidden.bs.collapse', function (e) {
        var target = $(e.target);
        //level 1 means group
        if(target.attr('level') == 1) {
            //collapse childern
            target.find('.in').collapse('hide');
            swissbib.Accordion.saveExpandedGroups();
        }
    });


    //open direct link library
    var expandlib = swissbib.Accordion.getParameterByName('expandlib');
    if (expandlib != null) {
        $("#accordion #collapse-" + expandlib.split('-')[0]).collapse('show');
        $("#accordion a[href='#collapse-" + expandlib + "']").click();
    }

    //on (re)load - open previously expanded groups. if none, open favorites as default
    var expandedGroupIds = JSON.parse($.cookie(swissbib.Accordion.cookieName));
    if (expandedGroupIds != null) {
        $.each((expandedGroupIds), function (index, value){
            $("#" + value).collapse('show');
        });
    } else {
        $("#accordion #collapse-favorite").collapse('show')
    }
});

