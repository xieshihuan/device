var wid = $("body,html").width()
//手机导航toggle
function phoneNavToggle(obj) {
	$(obj).toggleClass("on")
	$("#phone-nav").fadeToggle(300)
	$(".header-wrapper .search").removeClass("on")
	$(".search-form").hide()
}

$(".num span").countUp()
$(".phone-nav li > a").click(function(){
    $(".phone-nav ul li").removeClass("on")
    $(this).parent("li").toggleClass("active").siblings().removeClass("active");
    $(this).next(".navs").slideToggle(500).parent().siblings().find(".navs").slideUp(500)
    $(".phone-nav li .item").removeClass("on")
    $(".phone-nav li .second").hide()
})
$(".phone-nav li .item>a").click(function(){
    $(this).parent(".item").toggleClass("on").siblings().removeClass("on");
    $(this).next(".second").slideToggle(500).parent().siblings().find(".second").slideUp(500)
})

$(window).on("scroll", function(){
    var top = $(window).scrollTop();
	if(wid < 1024) {
		if(top > 100) {
			$(".header-wrapper").addClass("on")
		}else{
			$(".header-wrapper").removeClass("on")
		}
	}else{
		if(top > 100) {
			$(".search-form").addClass("before").hide()
		}else{
			$(".search-form").removeClass("before")
		}
	}
	if(top > 100) {
		$(".gotop").fadeIn()
	}else{
		$(".gotop").fadeOut()
	}
	
	
	if($('.ndbot')[0]){
	    var bt = $('.ndbot').offset().top - 110,
	        boxt = bt + $('.ndbot').height() - $('.ndbotleft').height() - 96;
	    if(top > bt && top < boxt){
	        $('.ndbot').addClass('cur');
	        $('.ndbot').removeClass('cur2');
	    }else if(top >= boxt){
	        $('.ndbot').addClass('cur2').removeClass('cur');
	    }else{
	        $('.ndbot').removeClass('cur');
	        $('.ndbot').removeClass('cur2');
	    }
	}
	
})


function searchToggle(){
    $(".header-wrapper .search").toggleClass("on")
    $(".search-form").slideToggle(500)
	$(".nav-icon").removeClass("on")
	$("#phone-nav").fadeOut(300)
}

// 手机回到顶部
function gotop() {
    $("body,html").animate({
        scrollTop: 0
    }, 800);
}

function itemToggle(obj) {
    $(obj).toggleClass("extend")
    $(obj).next(".nav").slideToggle(500)
}


$(function(){
    if ($(".wow").length) {
        if (!(/msie [6|7|8|9]/i.test(navigator.userAgent))) {
            var wow = new WOW({
                boxClass: 'wow',
                animateClass: 'animated',
                offset: 50,
                mobile: true,
                live: true

            });
            wow.init();
        };
    }
	$(".pc-nav-box .detail").mCustomScrollbar({
		theme:"dark",
		autoHideScrollbar:true,
		scrollButtons:{
			enable:true
		}
	});
	$(".child-box .top a").eq(0).addClass("on")
	$(".child-box .top a").click(function(){
		$(this).addClass("on").siblings().removeClass("on")
		$(".pc-nav-box .detail .item").eq($(this).index()).siblings().hide()
		$(".pc-nav-box .detail .item").eq($(this).index()).fadeIn(300)
	})
	$(".pc-nav-box .detail .item").eq(0).fadeIn()
})

if(wid < 1024) {
    $(".friendlink .caption").click(function(){
        $(".friendlink").toggleClass("on")
        $(".friendlink .list").slideToggle()
    })
}

// banner 轮播        
var index_banner = new Swiper('.index-banner-swiper', {
	navigation: {
	  nextEl: '.index-banner-swiper .next',
	  prevEl: '.index-banner-swiper .prev',
	},
	pagination: {
		el: '.index-banner-swiper .swiper-pagination',
		clickable: true,
	},
	autoplay: {
		autoplay: 5000,
		disableOnInteraction: false,
	},
	loop: true,
	speed: 1200
})
$(".index-banner-wrapper").hover(function(){
	index_banner.autoplay.stop()
},function(){
	index_banner.autoplay.start()
})

var index_goods_swiper = new Swiper('.index-goods-swiper', {
	speed: 1200,
	effect: "fade",
	on: {
		init: function(){
			$(".index-box-1 .cat-box a").eq(this.activeIndex).addClass("on")
		}, 
		slideChangeTransitionStart: function () {
			$(".index-box-1 .cat-box a").eq(this.activeIndex).addClass("on").siblings().removeClass("on");
		},
	},
})
$(".index-box-1 .cat-box a").hover(function(){
	$(this).stop().addClass("on").siblings().removeClass("on")
	index_goods_swiper.slideTo($(this).index())
})

if(wid > 1024) {
	$(".index-box-4 a").each(function(i){
		$(this).addClass("wow fadeInLeft50").attr("data-wow-delay",i*80+"ms")
	})
	if ($('.index-box-4').length > 0) {
		var liW = (wid*0.487).toFixed(2)
		$('.index-box-4 .list > a').eq(1).css({
			'width': liW
		});
		var indexbox4 = parseFloat($('.index-box-4').width().toFixed(2));
		indexbox42 = parseFloat(((indexbox4 - liW - 24) / 2).toFixed(2));
		$('.index-box-4 .list > a').mouseenter(function() {
			$(this).siblings().stop().animate({
				'width': indexbox42
			}, 500, 'linear')
			$(this).stop().animate({
				'width': liW
			}, 500, 'linear')
		})
	}

	$(window).resize(function() {
		var wid = $("body,html").width()
		var liW = (wid*0.487).toFixed(2)
		var indexbox4_b = (wid - liW - 24) / 2
		$('.index-box-4 .list > a').each(function(i) {
			if(i==1) {
				$(this).width(liW);
			}else{
				$(this).css({
					'width': indexbox4_b
				})
			}
		})
	})
}else{
	$(".index-box-4 a").each(function(i){
		$(this).addClass("wow fadeInUp50").attr("data-wow-delay",i*80+"ms")
	})
}

$(".index-box-1 .cat-box a").each(function(i){
	$(this).addClass("wow fadeInUp50").attr("data-wow-delay",i*80+"ms")
})
$(".data li").each(function(i){
	$(this).addClass("wow fadeInUp50").attr("data-wow-delay",i*80+"ms")
})
	
$(".tab-box a").eq(0).addClass("on")
$(".index-box-3 .info .item").eq(0).show()
$(".index-box-3 .map .item").eq(0).show().addClass("on")
$(".tab-box a").click(function(){
	$(this).addClass("on").siblings().removeClass("on")
	$(".index-box-3 .info .item").eq($(this).index()).fadeIn(300).siblings().hide()
	$(".index-box-3 .map .item").eq($(this).index()).fadeIn(300).addClass("on").siblings().hide().removeClass("on")
})
$(".index-box-3 .map .item").each(function(){
	$(this).find("ul li").each(function(i){
		$(this).css("animation-delay",i*80+"ms")
	})
})
