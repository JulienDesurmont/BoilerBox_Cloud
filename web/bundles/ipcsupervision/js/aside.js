/* Document jQuery pour la position et la taille du panneau "Aside" */

var $chargementAside = true;

//Calcul de la position et de la hauteur du panneau Aside
function Limit(windowSize, windowWidth, fixedLimit, offsetTop, margeBottom, windowScroll, aside, alarme) {
	if (windowWidth < 800) {
		aside.removeClass('fixed');
		alarme.height(400);
	} else {
    	if (windowScroll >= fixedLimit) {
			aside.addClass('fixed');
			alarme.height(windowSize + fixedLimit - offsetTop - margeBottom);
    	} else {
			aside.removeClass('fixed');
			alarme.height(windowSize + windowScroll - offsetTop - margeBottom);
    	}
	}
}

$(document).ready(function (){
    setTimeout(function() {
		// Stockage des sélecteurs et variables
		var aside = $('aside'); // CSS: Sélecteur de la balise 'aside'
		var alarme = $('#alarme'); // CSS : Sélecteur de l'ID 'alarme'
		var windowScroll = $(window).scrollTop(); // WINDOW : Hauteur du scroll
		var windowSize = $(window).height(); // WINDOW : Hauteur de la fenêtre
		var windowWidth = $(window).width();
		var offsetTop = alarme.offset().top;
		var margeBottom	= 30;
		// Calcul de la position d'arrêt du panneau Aside
		var fixedLimit = aside.offset().top - parseFloat(aside.css('marginTop').replace(/auto/,0));
		// Déclenchement de l'événement scroll mettre à jour le positionnement au chargement de la page
		$(window).trigger('scroll');
		// Taille et position du panneau aside au chargement de la page
		Limit(windowSize, windowWidth, fixedLimit, offsetTop, margeBottom, windowScroll, aside, alarme);
		// Au scroll de la page
		$(window).scroll(function() {
			windowWidth = $(window).width();
			// Redéfinition de scrollTop dans la fonction scroll
			windowScroll = $(window).scrollTop(); 
			// Taille et position du panneau aside au scroll
			Limit(windowSize, windowWidth, fixedLimit, offsetTop, margeBottom, windowScroll, aside, alarme);
		});
		// Au redimensionnement de la page
		$(window).resize(function() {
			windowWidth = $(window).width();
			// Redéfinition de windowSize dans la fonction resize
			windowSize = $(window).height();
			// Taille et position du panneau aside au redimensionement
			Limit(windowSize, windowWidth, fixedLimit, offsetTop, margeBottom, windowScroll, aside, alarme);
		});
    }, 1000);
});
