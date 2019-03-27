// Cache du bouton de modification de période
var $analyseBruleur = true;


// Affichage de l'image du loader pour indiquer que la page est en cours de chargement
function attente() {
	$(':visible').addClass('cursor_wait');
}

//	Cache de l'image du loader : Fin de chargement de page
function fin_attente() {
	$(':visible').removeClass('cursor_wait');
}
