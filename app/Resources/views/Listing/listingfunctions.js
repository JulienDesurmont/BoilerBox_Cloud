<script type='text/javascript'>
var maxPages;
var pageCourante;
// Dernier classement enregistré. Pour pouvoir effectuer des classements par ordre croissant ou décroissant
var lastClassement;
var ordreClassement;
// Désactivation du bouton de modification de période
$('#choixSelectionPeriode').hide();
$(document).ready(function() {
    // Mise en place d'une marge automatique 
	var marginBottom = $('footer').height()+'px';
	if (marginBottom != '0px') {
  	    document.getElementById("listingmessage").style.marginBottom = marginBottom;
	}
    maxPages = $('#maxPages').val();
    pageCourante= $('#pageCourante').val();
    if (pageCourante == 1) {
        $('.prevPages').addClass('disabled');
    }
    if (pageCourante == maxPages) {
        $('.nextPages').addClass('disabled');
    }
	// Suppression de l'événement click de la banière des périodes
	$('configperiode').off('click');
});

function defineNumPage($choice, $place) {
    attente();
    if ($choice == 'prev') {
        var $numpage = document.getElementById("pages").value;
        $numpage --;
        document.getElementById("pages").value = $numpage;
        document.forms['myForm'].submit();
    } else if ($choice == 'next') { 
        var $numpage = document.getElementById("pages").value;
        $numpage ++;
        document.getElementById("pages").value = $numpage;
        document.forms['myForm'].submit();
    } else if ($choice == 'first') { 
        document.getElementById("pages").value = 1;
        document.forms['myForm'].submit();
    } else if ($choice == 'last') {
        document.getElementById("pages").value = maxPages;
        document.forms['myForm'].submit();
    } else {
        if ($place == 'top') {
            var $numpage = document.getElementById("numpage_top").value;
        }
        if ($place == 'bottom') {
            var $numpage = document.getElementById("numpage_bottom").value;
        }
        if (document.getElementById("pages").value != $numpage) {
            document.getElementById("pages").value = $numpage;
            document.forms['myForm'].submit();
        }
    }
}

// Fonction appelée par la page listing.html.twig permettant de trier les données du listing
function trieDonnees(classement) {
	attente();
	setTimeout(function() {
 		var xhr = getXHR();
    	// On envoye les données de la Période pour créer les variables globales de date
    	callPathAjax(xhr, 'ipc_trie_donnees', null, false);
    	xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
		if (classement == lastClassement) {
		    if (ordreClassement == 'ASC') {
				ordreClassement = 'DESC';
		    } else {
				ordreClassement = 'ASC';
		    }
		} else {
		    ordreClassement = 'ASC';
		}
		lastClassement = classement;
		var datas = "classement=" + classement + "&ordre=" + ordreClassement;
    	xhr.send(datas);
		var nouvelle_liste  = JSON.parse(xhr.responseText);
		var htmlListing = "<table>";
		htmlListing += "<thead><tr><th class='genre' onClick=\"trieDonnees('genre');\">{{ 'label.page.genre'|trans }}</th><th class='localisation' onClick=\"trieDonnees('localisation');\">{{ 'label.page.localisation'|trans }}</th><th class='code' onClick=\"trieDonnees('code');\" >{{ 'label.page.code_message'|trans }}</th><th class='designation' onClick=\"trieDonnees('designation');\">{{ 'label.page.designation'|trans }}</th><th class='horodatage' onClick=\"trieDonnees('horodatage');\">{{ 'label.page.horodatage'|trans }}</th><th class='valeurs' ><span onClick=\"trieDonnees('valeur1');\">{{ 'label.page.valeur1'|trans }}</span> / <span onClick=\"trieDonnees('valeur2');\">{{ 'label.page.valeur2'|trans }}</span></th></thead>";
		htmlListing += "<tbody>";
		$.each(nouvelle_liste, function(index, donnee){
    		htmlListing += "<tr><td class='genre'>";
   			if (donnee['intitule_genre'] == 'Commande') {
				htmlListing += "<div class='genrebox genreCommande'></div>";
    		}
    		if (donnee['intitule_genre'] == 'Valeur analogique') {
    		    htmlListing += "<div class='genrebox genreAnalogique'></div>";
    		}
    		if (donnee['intitule_genre'] == 'Etat') {
    		    htmlListing += "<div class='genrebox genreEvenement'></div>";
    		}
    		if (donnee['intitule_genre'] == 'Alarme') {
    		    htmlListing += "<div class='genrebox genreAlarme'></div>";
    		}
    		if (donnee['intitule_genre'] == 'Défaut') {
    		    htmlListing += "<div class='genrebox genreCritique'></div>";
    		}
    		if (donnee['intitule_genre'] == 'Paramètre') {
    		    htmlListing += "<div class='genrebox genreParametre'></div>";
    		}
 			// Si le numéro de la donnée est composée de 3 chiffres on récupère et indiquons le dernier digit
   			if (donnee['numero_genre'].length == 3) {
				htmlListing += "<div class='indice'>" + donnee['numero_genre'].slice(2, 1) + "</div>";
    		}		
			htmlListing += "</td>";
			htmlListing += "<td class='localisation'><div class='txtlocalisation'>" + donnee['numero_localisation'] + "</div></td>";
			htmlListing += "<td class='code'>" + donnee['codeModule'] + "</td>"
			htmlListing += "<td class='designation'><strong>" + donnee['intitule_module'] + "</strong><br />";
			if (donnee['intitule_genre'] == 'Défaut' || donnee['intitule_genre'] == 'Alarme') {
				if (donnee['valeur1'] == 0) {
				    var valeurDollar = $('#indicateurVal0').val();
				    var message = donnee['message'].replace(/\$/, valeurDollar);
				    message = message.replace(/£/, donnee['valeur2']);
				    htmlListing += message;
				} else if (donnee['valeur1'] == 1) {
				    var valeurDollar = $('#indicateurVal1').val();
				    var message = donnee['message'].replace(/\$/, valeurDollar);
				    message	= message.replace(/£/, donnee['valeur2']);
    		        htmlListing += message;
				}
			} else {
				var message = donnee['message'].replace(/\$/,donnee['valeur1']);
				message = message.replace(/£/,donnee['valeur2']);
				htmlListing += message;
			}
    		htmlListing += "</td>";
    		htmlListing += "<td class='horodatage'><div class='date'>" + getDate(donnee['horodatage'], 'date') + "</div><div class='heure'>" + getDate(donnee['horodatage'], 'heure') + "." + donnee['cycle'] + "</div></td>";
    		htmlListing += "<td class='valeurs'><div class='valeur1'>" + donnee['valeur1'] + "</div><div class='valeur2'>" + donnee['valeur2'] + "</div></td>";
    		htmlListing += "</tr>";
    	});
		$('#listingDeDonnees').html(htmlListing);
		fin_attente();
	}, 200);
 }

// Fonction qui formate une date entrée au format YYYY/mm/dd HH:ii:ss - > Retourne soit la date au format dd/mm/YYYY soit l'heure.
function getDate(dateString, typeSortie) {
	var motif = /^(....)-(..)-(..)\s(.+?)$/;
	if (typeSortie == 'date') {
        var nouvelleChaine = dateString.replace(motif, '$3/$2/$1');
	}
	if (typeSortie == 'heure') {
	    var nouvelleChaine = dateString.replace(motif, '$4');
	}
	return nouvelleChaine;
}
</script>
