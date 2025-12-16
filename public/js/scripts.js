(function ($) {

  "use strict";
    $(document).ready(function () {
    



    // Parallax Effect
    parallaxen();




    // jQuery to collapse the navbar on scroll
    function collapseNavbar() {
    if ($(".navbar").offset().top > 50) {
        $(".navbar-fixed-top").addClass("top-nav-collapse");
    } else {
        $(".navbar-fixed-top").removeClass("top-nav-collapse");
    }
            if ($(this).scrollTop() >800) {
            $("#back-to-top").stop().animate({ opacity: '1' }, 150);
        } else {
            $("#back-to-top").stop().animate({ opacity: '0' }, 150);
        }
    }
    $(window).scroll(collapseNavbar);
    



    // Closes the Responsive Menu on Menu Item Click
    $(document).on('click','.navbar-collapse.in',function(e) {
    if( $(e.target).is('a') && $(e.target).attr('class') != 'dropdown-toggle' ) {
        $(this).collapse('hide');
    }
    });




    


    // Smooth Scroll to Anchor c) 2016 Chris Ferdinandi | MIT License | http://github.com/cferdinandi/smooth-scroll */
    smoothScroll.init({
    selector: '[data-scroll]', // Selector for links (must be a class, ID, data attribute, or element tag)
    selectorHeader: null, // Selector for fixed headers (must be a valid CSS selector) [optional]
    speed: 800, // Integer. How fast to complete the scroll in milliseconds
    easing: 'easeInOutCubic', // Easing pattern to use
    offset: 0, // Integer. How far to offset the scrolling anchor location in pixels
    callback: function ( anchor, toggle ) {} // Function to run after scrolling
    });




    // ScrollReveal
    window.sr = ScrollReveal();
    sr.reveal('.fadeHero', { duration: 1500, delay: 200 } )
    sr.reveal('.fadeIn', { duration: 1500, viewFactor: 0.6} )
    sr.reveal('.fadeLeft', { duration: 500, origin: 'left', viewFactor: 0.7,}, 200)
    sr.reveal('.fadeLeft2', { duration: 1500, origin: 'left', viewFactor: 0.7,}, 200)



        
   /*
 * ----------------------------------------------------------------------------------------
         *  COUNTER UP JS
         * ----------------------------------------------------------------------------------------
         */

        $('.counter-num').counterUp();

    });
})(jQuery);


/*const testimonial = [
  {
    name: "Bill Bailey",
    photoUrl: "/img/testimony/Bill Bailey.jfif",
    text: "This trading platform helped me recover financially when I had lost hope. I’m truly grateful for the opportunity it gave me."
  },

  {
    name: "Lucas Mason",
    photoUrl: "1647165002088.jpg",
    text: "I was stuck in serious financial trouble, but this platform changed everything for me. I can finally stand proud again."
  },

  {
    name: "Mark Donald",
    photoUrl: "IMG_20240819_145419_943.jpg",
    text: "Thanks to this platform, I was able to overcome my financial struggles and regain my confidence."
  },

  {
    name: "David Scott",
    photoUrl: "my whatsapp image.jfif",
    text: "This platform played a major role in helping me get back on my feet financially. I’m sincerely thankful."
  },

  {
    name: "Camilo Jose",
    photoUrl: "1647165002088.jpg",
    text: "I went from financial stress to stability because of this trading platform. I can’t express how grateful I am."
  },

  {
    name: "James Cooper",
    photoUrl: "IMG_20240819_145419_943.jpg",
    text: "This platform gave me a second chance financially when I needed it the most."
  },
  {
    name: "Friedrich Klaus",
    photoUrl: "my whatsapp image.jfif",
    text: "My financial situation improved drastically after using this platform. It truly made a difference."
  },

  {
    name: "Gregory Cooper",
    photoUrl: "1647165002088.jpg",
    text: "This platform helped me escape a difficult financial period and restore my dignity. I’m deeply thankful."
  },

  {
    name: "David Yosef",
    photoUrl: "IMG_20240819_145419_943.jpg",
    text: "I was overwhelmed by debt before discovering this platform. Today, my finances and confidence are back on track."
  },

];

const imgEl = document.querySelector("testi-img");
const textEl = document.querySelector(".texters");
const ussernameEl = document.querySelector(".username");

let idx = 0;

updateTestimonial();

function updateTestimonial(){
  const {name, photoUrl, text} =
  testimonial[idx];
  imgEl.src = photoUrl;
  textEl.innerText = text;
  ussernameEl.innerText = name;
  idx++;
  if (idx === testimonial.length){
    idx = 0;
  }
  setTimeout(()=>{
    updateTestimonial();
  }, 1000)
}*/
