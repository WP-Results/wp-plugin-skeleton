jQuery(function($) {
  SwaActivities = {
    'API': {
      'activities': function()
      {
        return SwaActivitiesInfo.API.url + '/activities';
      }
    }
  };

  function getUrlVars()
  {
    var vars = [], hash;
    var hashes = window.location.href.slice(window.location.href.indexOf('?') + 1).split('&');
    for(var i = 0; i < hashes.length; i++)
    {
        hash = hashes[i].split('=');
        vars.push(hash[0]);
        vars[hash[0]] = hash[1];
    }
    return vars;
  }
  
  function enable_waypoint()
  {
    $container.waypoint(function() {
        $('#swa-new-item-alert').click();
      },{
      'context': '#swa-activities-scroller'
    });

    $container.waypoint({
      'handler': function(direction, waypoint) {
        waypoint.disable();
        $('#swa-loading').show();
        
        var after = $container.find('li').last().data('id');
        
        $.get(SwaActivities.API.activities(), $.extend({}, SwaActivitiesInfo.args, {'after': after}), function(data) {
          $('#swa-loading').hide();
          if(data.length==0) return;
          var text = tmpl(data);
          var e = $(text);
          var top_class = $container.find('li').last().hasClass('even') ? 'even' : 'odd';
          var alt_class = top_class=='even' ? 'odd' : 'even';
          var elems = e.children('li');
          var next_class = (elems.length % 2 == 0) ? top_class : alt_class;
          elems.each(function(i,e) {
            var e = $(e);
            e.addClass(next_class);
            next_class = (next_class == top_class) ? alt_class : top_class;
          });
          $container.append(elems);
          $.waypoints('refresh');
          var trim = $container.find('li').length - SwaActivitiesInfo.args.list_size;
          while(trim>0)
          {
            $container.find('li:last-child').remove();
            trim--;
          }
          if(trim<0)
          {
            waypoint.enable();
          }
        }).fail(function() {
          waypoint.enable();
        });
      },
      'offset': function() {
        var offset = $('#swa-activities-scroller').height() - $container.height() + Math.round($container.height()/3);
        return offset;
      },
      'context': '#swa-activities-scroller',
    });
  }
  
  function refresh(timeout)
  {
    current_timer = setTimeout(_refresh, SwaActivitiesInfo.REFRESH_RATE);
  }
  
  function _refresh()
  {
    $.get(SwaActivities.API.activities(), $.extend({}, SwaActivitiesInfo.args, {'since': since}), function(data) {
      if(data.length==0) return;
      var text = tmpl(data);
      var e = $(text);
      since = e.children('li').first().data('id');
      e.children('li').addClass('highlight').css('display', 'none');
      var top_class = $container.find('li').first().hasClass('even') ? 'even' : 'odd';
      var alt_class = top_class=='even' ? 'odd' : 'even';
      $container.prepend(e.children('li'));
      var elems = $container.find('.highlight');
      var next_class = (elems.length % 2 == 0) ? top_class : alt_class;
      if($('#swa-activities-scroller').scrollTop()==0)
      {
        $container.find('li.highlight').slideDown(500).promise().done(function() {
          elems.each(function(i,e) {
            var e = $(e);
            e.css('display', 'block');
            var h = e.outerHeight();
            e.addClass(next_class);
            next_class = (next_class == top_class) ? alt_class : top_class;
          });
          elems.removeClass('highlight');
          elems.effect('highlight', 3000);
        });
        $('#swa-new-item-alert').hide();
      } else {
        $('#swa-new-item-alert').slideDown();
        $container.find('li.highlight').toggle().promise().done(function() {
          elems.each(function(i,e) {
            var e = $(e);
            e.css('display', 'block');
            var h = e.outerHeight();
            $('#swa-activities-scroller').scrollTop($('#swa-activities-scroller').scrollTop()+h);
            e.addClass(next_class);
            next_class = (next_class == top_class) ? alt_class : top_class;
          });
        });
      }
      // Trim container
      var trim = $container.find('li').length - SwaActivitiesInfo.args.list_size;
      while(trim>0)
      {
        $container.find('li:last-child').remove();
        trim--;
      }
      $.waypoints('refresh');
    }).always(function() {
      refresh();
    });
    
  }

  var current_timer;
  var since;
  var qs = getUrlVars();
  var $container = $('#swa_activities');
  var tmpl = doT.template($('#template').text()); 
  var user_id = SwaActivitiesInfo.args.user_id;

  $('.widget_bp_swa_widget .tool.me').click( function() {
    $('.widget_bp_swa_widget .tool').removeClass('selected');
    $(this).addClass('selected');
    clearTimeout(current_timer);
    SwaActivitiesInfo.args.user_id = user_id;
    since = null;
    init();
  });
  $('.widget_bp_swa_widget .tool.all').click( function() {
    $('.widget_bp_swa_widget .tool').removeClass('selected');
    $(this).addClass('selected');
    clearTimeout(current_timer);
    SwaActivitiesInfo.args.user_id = null;
    since = null;
    init();
  });
  
  function init()
  {
    $('.widget_bp_swa_widget .tool').removeClass('selected');
    if(SwaActivitiesInfo.args.user_id)
    {
      $('.widget_bp_swa_widget .tool.me').addClass('selected');
    } else {
      $('.widget_bp_swa_widget .tool.all').addClass('selected');
    }
    $(this).addClass('selected');
    var loader = function() {
      $.get(SwaActivities.API.activities(), SwaActivitiesInfo.args, function(data) {
        $container.append( $("<li>There are no activities yet.</li>") );
        if(data.length==0) return;
        $container.empty();
        var text = tmpl(data);
        var e = $(text);
        since = e.children('li').first().data('id');
        $container.append(e.children('li'));
        $container.find('li').removeClass('even');
        $container.find('li').removeClass('odd');
        $container.find('li:even').addClass('even');
        $container.find('li:odd').addClass('odd');
        
        enable_waypoint();
        refresh();
      }).fail(function() {
        $container.empty();
        $container.append( $("<li>There was a problem reaching the server. Retrying.</li>") );
        setTimeout(loader, 5000);
      });
      
    };
    
    loader();
  }
  
  init();
  
  $('#swa-new-item-alert').click( function() {
    $('#swa-activities-scroller').animate( {
      'scrollTop': 0,
    }, 1000);
    $(this).slideUp(500);
    var elems = $container.find('.highlight');
    elems.removeClass('highlight');
    elems.effect('highlight', 3000);
  });
});