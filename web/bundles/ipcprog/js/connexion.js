//  Fonction executée après la validation d'un popup messagebox
function validation_messagebox() {
	$('#messagebox').addClass('cacher');
	removeShadow('message');
	activateLinks();
}

function closeMessageBox() {
	if ($('#messagebox').hasClass('cacher') == false) {
		$('#messagebox').addClass('cacher');
		activateLinks();
		removeShadow('message');
	}
}

function activateLinks() {
	$('#pagePrincipaleBody a').each(function(){
		$(this).removeClass('btn-disable');
	});
}

function desactivateLinks() {
	// Lors de l'ouverture de la box, désactivation de la validation du login/mot de passe
	$('#pagePrincipaleBody a').each(function(){
		$(this).addClass('btn-disable');
	});
}

function removeShadow() {
	$('#siteIpc').off("click",closeMessageBox);
	$('#pagePrincipaleBody').removeClass('shadowing');
}

// Fonction qui définie la taille de l'écran afin que la div d'identifiant siteIpc couvre la totalité de l'écran
function setSiteIpcDimension() {
	var hauteur = window.innerHeight + 'px';
	var largeur = window.innerWidth + 'px';
	$('#siteIpc').width(largeur);
	$('#siteIpc').height(hauteur);
}
