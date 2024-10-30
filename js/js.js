
jQuery(document).ready(function($){	
	/**
	 * init tooltip
	 */
	$('.hw-skin-tooltip').each(function() {
		$(this).qtip({ // Grab some elements to apply the tooltip to
		    content: {
		        text: $(this).next('div')
		    }
		});
	});
	/**
	 * scroll to current skin in listview frame
	 */
	var skins_listview = $('.hwk-skins-list'),
    	scrollTo_activeSkin = $('.hwk-skins-list .hw-skin-current');

	if(scrollTo_activeSkin.length) skins_listview.scrollTop(scrollTo_activeSkin.offset().top - skins_listview.offset().top + skins_listview.scrollTop());
	
	/* Or you can animate the scrolling:
	skins_listview.animate({
	    scrollTop: scrollTo_activeSkin.offset().top - skins_listview.offset().top + skins_listview.scrollTop()
	});*/
/* 
  $( "div.widgets-sortables" ).droppable({
		  drop: function( event, ui ) {
			  var widget = $(ui.draggable).find('.hide-if-js').attr('href');
			  widget = widget.split('base=')[1];
			  if(widget == 'hwlct_taxonomy'){
				  //alert('Bạn cần nạp lại trang để thay đổi dữ liệu.');
				  //location.reload();	//reload current page
			  }
		  }
		});
  
   * 1. when dropped in
  $('div.widgets-sortables').bind('sortstop',function(event,ui){
	  
    //console.log('just dropped in');
  });
  // 2. do some stuff on load 
  console.log('onLoad');
  // 3. on action
  $(document).delegate('.our_widget_class', 'change', function(ev) {
    // you have to find the parent widget here, 
    // and do something for it. And this is not easy  
    // because the widget shouldn't have it's ID yet, but still possible. 
    // This is actually the safest part of the whole process (maybe just for me)
    console.log('the element changed');
  });
  // 4. on save
  $('body').ajaxSuccess(function(evt, request, settings) {
	  console.log('saved',request);
  });
  */
  function parseQuery(qstr)
  {
    var query = {};
    var a = qstr.split('&');
    for (var i in a)
    {
      var b = a[i].split('=');
      query[decodeURIComponent(b[0])] = decodeURIComponent(b[1]);
    }

    return query;
  }
 /* $( document ).ajaxComplete(function( event, xhr, settings ) {return;
	  var param = parseQuery(settings.data);
	  if(param['widget-id'].indexOf('hwlct_taxonomy') != -1){	//if widget has base name: 'hwlct_taxonomy' 
		//alert('Bạn cần nạp lại trang để thay đổi dữ liệu.');
		//location.reload();	//reload current page
	  }
	  
	});*/
	/**
	 * auto save widget, work properly but i dont want to use this
	 */
	$(document).ajaxComplete(function(event, XMLHttpRequest, ajaxOptions){return;

		  // determine which ajax request is this (we're after "save-widget")
		  var request = {}, pairs = ajaxOptions.data.split('&'), i, split, widget;

		  for(i in pairs){
		    split = pairs[i].split('=');
		    request[decodeURIComponent(split[0])] = decodeURIComponent(split[1]);
		  }
		  	  
		  // only proceed if this was a widget-save request
		  if(request.action && (request.action === 'save-widget')){

		    // locate the widget block
		    widget = $('input.widget-id[value="' + request['widget-id'] + '"]').parents('.widget');

		    // trigger manual save, if this was the save request 
		    // and if we didn't get the form html response (the wp bug)
		    if(!XMLHttpRequest.responseText)
		      wpWidgets.save(widget, 0, 1, 0);

		    // we got an response, this could be either our request above,
		    // or a correct widget-save call, so fire an event on which we can hook our js
		    else
		      $(document).trigger('saved_widget', widget);

		  }

		});
});