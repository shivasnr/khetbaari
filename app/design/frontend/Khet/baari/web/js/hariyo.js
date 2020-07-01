require([
    'jquery'
    ], 
function($){
    $(".level0 > a").on('mouseover', function(event) {   
        $("ul.submenu li:first-child ul").css("display", "block");    
    });
    $(window).scroll(function() {
        if ($(document).scrollTop() > 10) {
          $('.panel.wrapper').addClass('shrink');
        } else {
          $('.panel.wrapper').removeClass('shrink');
        }
      });
      $(window).on("scrollstop",function(){
        $('.panel.wrapper').removeClass('shrink');
     });
     
     $(".block-search .label").click(function(){
        var x = document.querySelectorAll(".block-search input#search");
        x[0].style = "display: block !important";
        if($('label').hasClass('active')){
          x[0].style = "display: none !important";
        }
     });
     
    }
)