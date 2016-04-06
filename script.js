$( document ).ready(function() {
	// Check if Ajax request is running to load entries
	$ajaxRuning = false;

	attachEvents();

	/*$(window).scroll(function(){
		if ($(window).scrollTop() == $(document).height() - $(window).height()){
			getEntries();
		}
	}); */
	
	var lastScrollTop = 0;
	$(window).scroll(function(e) {
		var body = $("body")[0],
			scrollTop = body.scrollTop;

		if (scrollTop > lastScrollTop) {
			if (scrollTop >= (body.scrollHeight - window.innerHeight - 50)) {
				if(!$ajaxRuning){
					getEntries();
				}
			}
		}
		lastScrollTop = scrollTop;
	});
 
});

function attachEvents(){
	$('.entry').hover(
      function () {
          var scrollingHeightSynopsis = $(this).find('div.synopsis').height();
          var scrollingHeightTitle = $(this).find('div.title').height();
          $(this).find('div.synopsis').stop(true, true).animate({scrollTop: scrollingHeightSynopsis}, { duration: 10000, easing: 'swing' });
          $(this).find('div.title').stop(true, true).animate({scrollTop: scrollingHeightTitle}, { duration: 1000, easing: 'swing' });
          $(this).find("div.toolbar").fadeIn();
      },
      function () {
          $(this).find('div.synopsis').stop(true, true).animate({scrollTop: 0}, { duration: 1000, easing: 'swing' });
          $(this).find('div.title').stop(true, true).animate({scrollTop: 0}, { duration: 1000, easing: 'swing' });
          $(this).find("div.toolbar").fadeOut();
      }
  );
  
	$('#opacity').click(function(){
		$("#opacity").hide();
		$(".details").hide();
		$(".videos").hide();
		$(".videos").empty();
		$(".details").empty();
	});

	/*$('.details').click(function(){
		$("#opacity").hide();
		$(".details").hide();  
		$(".details").empty();
	});*/
	
	$('.videos').click(function(){
		$("#opacity").hide();
		$(".videos").hide(); 
		$(".videos").empty();
	});
	
	// Load img (thumb or fanart) with fadeIn when loaded in browser cache
    $('img').each(function(i) {
        if (this.complete) {
            $(this).fadeIn();
        } else {
            $(this).load(function() {
                $(this).fadeIn();
            });
        }
    });
	
	$("img.image-nav").hover(
        function(){
             $(this).animate({
                'marginLeft':"0"
            });
        }, function(){
            $(this).animate({
                'marginLeft':"-85"
            });
        });
}

function getEntries(){
	loc = document.location + "";
	if(loc.indexOf("?") == -1)
		loc += "?";
	//alert(loc + "&offset=" +(($(".entry").length)));
	$ajaxRuning = true;
	
	$.ajax({
	    url : loc + "&offset=" +(($(".entry").length)),
	    type: "GET",
	    /*data : "offset="+(($(".entry").length)),*/
	    beforeSend: function(data, textStatus, jqXHR){
			$("#entries").append("<div class='loading'><img src='images/loading.gif' alt='Loading...' /></div>");
	    },	
		success: function(data, textStatus, jqXHR){
			$("#entries").append(data);
			$(".loading").remove();
			attachEvents();
			$ajaxRuning = false;
	    },
	    error: function (jqXHR, textStatus, errorThrown){
	    }
	});
}

function printDetails(divDetails, id){
	// Set the height of opacity div to full page
	$("#opacity").height($(window).height());
	$("#opacity").show();
	// Place the details div from current vertical scrolling
	$("#"+divDetails).css({ top: $(window).scrollTop()+($("#"+divDetails).height()*0.75) });
	
	loc = document.location + "";
	if(loc.indexOf("?") == -1)
		loc += "?";
	
	$.ajax({
	    url : loc + "&action=detail&id=" + id,
	    type: "GET",
		beforeSend: function(data, textStatus, jqXHR){
			$("#" + divDetails).append("<div class='loading'><img src='images/loading.gif' alt='Loading...' /></div>");
	    },	
		success: function(data, textStatus, jqXHR){
			$(".loading").remove();
			$("#" + divDetails).append(data);
			//attachEvents();
	    },
	    error: function (jqXHR, textStatus, errorThrown){
	    }
	});
	
	$("#"+divDetails).slideToggle();  
}

function displayYoutube(divYoutube, idvideo){
	// Set the height of opacity div to full page
	$("#opacity").height($(window).height());
	$("#opacity").show();
	// Place the details div from current vertical scrolling
	$("#"+divYoutube).css({ top: $(window).scrollTop()+($("#"+divYoutube).height()*0.90) });
	$data = $('<iframe />', {
		frameborder: '0',
		allowfullscreen: 'true',
		src: 'https://www.youtube.com/embed/' +  idvideo,
		width: '480',
		height: '390'
	});
	$("#"+divYoutube).append($data);
	$("#"+divYoutube).slideToggle();
}

function toggleTvshowContent(className, id){
	if(!$('#'+className+'-'+id).is(":visible"))
		$('.'+className).slideUp();
	$('#'+className+'-'+id).slideToggle('slow', function() {});
}