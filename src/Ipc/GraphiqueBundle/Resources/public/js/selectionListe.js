<script type="text/javascript">
var xhr = null;
/*  Derniere valeur du champs selectionné avant début de recherche : Permet d'empêcher la modification du choix d'une liste lors d'une recherche*/
var derniereValeur = null;
var dernierModule = null;
var dernierGenre = null;
var block_valueModule = false;
var block_valueGenre = false;
var dernierType = null;
/*  Variable indiquant qu'une recherche est en cours : Permet de ne recupérer la valeur d'une liste déroulante qu'avant le début d'une recherche (et non à chaque clic de souris) */
var block_genre	= false;
var block_module = false;
var $chargementGraphique = true;

function majMaxExecTime() {
	xhr = getXHR();
	xhr.onreadystatechange = function() {
		if ((xhr.readyState == 4) && (xhr.status == 200)) {
			var nouvelle_liste = xhr.responseText;
			document.getElementById('maxExecutionTime').value = nouvelle_liste;
			document.getElementById("loader").style.display = "none";
			return(0);
		} else {
			// Tant que la recherche est en cours : Affichage de l'image loader
			document.getElementById("loader").style.display = "inline"; 
		}
	}
	xhr.open("POST", "{{ path('ipc_confChangeMaxExecTime') }}", true); 
	xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
	var maxExecTime = document.getElementById('maxExecutionTime').value;
	var datas = "maxExecTime=" + maxExecTime;
	xhr.send(datas);
}



// Récupération de la dernière valeur selectionnée
function recupSelection(type) {
	if (type == 'genre') {
		if (block_valueGenre == false) {
			dernierGenre = document.getElementById('genres').value;
			block_valueGenre = true;
		}
		if (block_genre == false) {
			block_module = true;
		}
	}
	if (type == 'module') {
		if (block_valueModule == false) {
			dernierModule = document.getElementById('modules').value;
			block_valueModule = true;
		}
		if (block_module == false) {
			// Bloquage des modifications de la liste des genres
			block_genre = true; 
		}
	}
}

// Traitement de la réponse et traitements qui suivent
function selection(type, supp) {
	// Si une requête est en cours : 	On annule la précédente recherche si elle porte sur le même type
	// On attend la fin de la recherche si elle porte sur un type différent
	if (xhr && xhr.readyState != 4) {
		if (dernierType == type) {
			xhr.abort();
			if (type == 'genre') 	{ block_genre = false; }
			if (type == 'module') 	{ block_module = false; }
		} else {
			if (type == 'genre') {
				for (var i = 0; i < document.getElementById('genres').options.length; i++) {
					if (document.getElementById('genres').options[i].value == dernierGenre) {
						document.getElementById('genres').options[i].selected = true;
					}
				}
			}
			if (type == 'module') {
				for (var i = 0; i < document.getElementById('modules').options.length; i++) {
					if (document.getElementById('modules').options[i].value == dernierModule) {
						document.getElementById('modules').options[i].selected = true;
					}
				}
			}
			return;
		}
	} else {
		dernierType = type;
	}
	block_valueGenre = false;
	block_valueModule = false;
	if (supp == true) {
		document.getElementById('codeModule').value = "";
	}
	if (type == 'genre') {
		// On récupère la valeur, on temporise et on vérifie que la valeur est toujours la même
		var genrevalue = document.getElementById('genres').value;
		xhr = getXHR();
		xhr.onreadystatechange = function() {
			if ((xhr.readyState == 4) && (xhr.status == 200)) {
				var nouvelle_liste = xhr.responseText;			//	Récupération de la réponse envoyée par le serveur
				// -La réponse correspond aux deux select à définir : module et message
				// -Elle est retournée sous forme d'une chaine de caractère
				// -La séparation entre les données des deux select est faite par le mot "ListeSuivante"
				// -Ici nous séparont les données des deux select pour les réinjecter dans les champs de la page html
				// Définition du séparateur
				var separateur = "ListeSuivante";
				// Taille du séparateur
				var sprLength = separateur.length;
				// Récupération de la position du séparateur
				var spr = nouvelle_liste.indexOf(separateur);
				// Récupération de la premiere liste (correspond au select des modules)
				var selectmodule = nouvelle_liste.slice(0,spr);
				// Récupération de la seconde liste (correspond au select des messages)
				var selectmessage = nouvelle_liste.slice(spr+sprLength);
				// Injection des données dans les listes déroulantes de la page Html
				// Liste des modules
				document.getElementById('modules').innerHTML = selectmodule;
				// Sélection par défaut de la premiere valeur de la liste
				// On selectionne les valeurs précédemment selectionnées
				for (var selectionUser = 0; selectionUser < moduleSelection.length; selectionUser++) {
					for (var numOption = 0; numOption < document.getElementById('modules').options.length; numOption++) {	//	On parcourt la liste déroulantes des intitules
						if (document.getElementById('modules').options[numOption].value == moduleSelection) {	//	Lorsqu'on trouve la valeur précédemment selectionnée on la reselectionn
							document.getElementById('modules').options[numOption].selected = true;
						}
					}
				} 
				// Liste des messages
				document.getElementById('messages').innerHTML = selectmessage;
				if (selectmessage.length != 0) {
					// Sélection par défaut de la premiere valeur de la liste
					document.getElementById('messages').options[0].selected = true;
				}
				document.getElementById("loader").style.display = "none";
				return(0);
			} else if (xhr.readyState < 4) {
				document.getElementById("loader").style.display = "inline";
			}		
		}
		// Envoye du texte au serveur
		xhr.open("POST"," {{ path('ipc_configSelect', {'type': 'genre', 'withAll':false}) }}", true); 
		// Définition de l'entête pour l'envoi de donnée par la méthode POST
		xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
		// Récupération des données à envoyer au serveur
		// - le genre
		var genreSelection = document.getElementById('genres').value;
		// - le module
		var moduleSelection = document.getElementById('modules').value;
		var localisationSelection = document.getElementById('localisations').value;
		var datas = "genres=" + genreSelection + "&modules=" + moduleSelection + "&localisations=" + localisationSelection;
		// Envoi des données
		xhr.send(datas);
	}
	if (type == 'module') {
		var modulevalue = document.getElementById('modules').value;
		xhr = getXHR();
		xhr.onreadystatechange = function() {
			// La réponse est envoyée par le serveur et disponible
			if ((xhr.readyState == 4) && (xhr.status == 200)) { 
				// Récupération de la réponse envoyée par le serveur
				nouvelle_liste = xhr.responseText;
				// Modification de la liste déroulante par les valeurs retournées
				document.getElementById('messages').innerHTML = nouvelle_liste;
				if (nouvelle_liste.length != 0) {
					// Sélection par défaut de la premiere valeur de la liste
					document.getElementById('messages').value = document.getElementById('messages').options[0].value;
				}
				document.getElementById("loader").style.display = "none";
				return(0);
			} else if (xhr.readyState < 4) {
				document.getElementById("loader").style.display = "inline";
			}
		}
		// Envoye du texte au serveur
		xhr.open("POST"," {{ path('ipc_configSelect', {'type': 'module', 'withAll':false}) }}", true); 
		// On définit l'entête pour l'envoi de donnée par la méthode POST
		xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
		// Récupération des données à envoyer au serveur
		// - le genre
		var genreSelection = document.getElementById('genres').value;
		// - le module
		var moduleSelection = document.getElementById('modules').value;
		var localisationSelection = document.getElementById('localisations').value;
		var datas = "genres="+genreSelection+"&modules="+moduleSelection+"&localisations="+localisationSelection;
		// Envoie des données
		xhr.send(datas);
	}
}


// JQuery
$(document).ready(function() {
	$('#codeModule').keypress(function(e) {
		if (e.keyCode==13) $('#messages').focus();
	});
});


function reinitialise() {
	document.getElementById('genres').options[0].selected = true;
	document.getElementById('modules').options[0].selected = true;
}

// Fonction appelée lors de l'inscription d'un code de module dans la page Listing/index.html.twig
// 1 Récupération du code en Majuscule
// 2 Parcours de la liste des messages de modules
// 3 Si un code identique est trouvé, selection de celui-ci
// 4 Réinitialisation des listes déroulantes Genre et Module afin que les paramètres 'all' soient sélectionnés
function choix(event) {
	var choix = document.getElementById('codeModule').value.toUpperCase();
	for (var i = 0; i < document.getElementById('messages').options.length; i++) {
		if (document.getElementById('messages').options[i].text.substr(0,6) == choix) {
			document.getElementById('messages').options[i].selected = true;
			document.getElementById('codeModule').value = choix;
			reinitialise();
		}
	}
}

function sendAjaxForm() {
	var localisationSelection = document.getElementById('localisations').value;
	var genreSelection = document.getElementById('genres').value;
	var moduleSelection = document.getElementById('modules').value;
	var messageSelection = document.getElementById('messages').value;
	var codeVal1 = $("input[type='radio'][name='codeVal1']").filter(':checked').val();
	var codeVal2 = $("input[type='radio'][name='codeVal2']").filter(':checked').val();
	var valeur1Min = null;
	var valeur1Max = null;
	var valeur2Min = null;
	var valeur2Max = null;
	var choixSubmit = document.getElementById('choixSubmit').value;
	var suppressionRequete = $("input[type='radio'][name='suppression_requete']").filter(':checked').val();
	var nodistinction = null;
	var ajax = 'ajax';
	if ($('input[name=nodistinction]').is(':checked')) {
		var nodistinction = document.getElementById('nodistinction').value;
	}
	switch (codeVal1) {
	case 'None':
		break;
	case 'Inf':
		var valeur1Min = document.getElementById('codeVal1Min').value;
		break;
	case 'Sup':
		var valeur1Min = document.getElementById('codeVal1Min').value;
		break;
	case 'Int':
		var valeur1Min = document.getElementById('codeVal1Min').value;
		var valeur1Max = document.getElementById('codeVal1Max').value;
		break;
	}
	switch (codeVal2) {
	case 'None':
		break;
	case 'Inf':
		var valeur2Min = document.getElementById('codeVal2Min').value;
		break;
	case 'Sup':
		var valeur2Min = document.getElementById('codeVal2Min').value;
		break;
	case 'Int':
		var valeur2Min = document.getElementById('codeVal2Min').value;
		var valeur2Max = document.getElementById('codeVal2Max').value;
		break;
	}
	xhr = getXHR();
	xhr.onreadystatechange = function() {
		if ((xhr.readyState == 4) && (xhr.status == 200)) {
			var nouvelle_liste = JSON.parse(xhr.responseText);
			var nouvelle_div = newDiv(nouvelle_liste);
			$('div.requetemessage').html(nouvelle_div);
			// Accessibilité ou non  de RAZ selon le nombre de requetes
			document.getElementById("loader").style.display = "none";
			return(0);
		} else {
			document.getElementById("loader").style.display = "inline";
		}
	}
	xhr.open("POST","{{ path('ipc_graphiques') }}", true);
	xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
	var datas = null;
	switch (choixSubmit) {
	case 'RAZ':
		datas = "AJAX=" + ajax + "&choixSubmit=" + choixSubmit;
		break;
	case 'suppressionRequete':
		datas = "AJAX=" + ajax + "&choixSubmit=" + choixSubmit + "&suppression_requete=" + suppressionRequete;
		break;
	case 'ajoutRequete':
		if (nodistinction != null) {
			datas = "AJAX=" + ajax + "&choixSubmit=" + choixSubmit + "&nodistinction=" + nodistinction + "&listeLocalisations=" + localisationSelection + "&listeGenres=" + genreSelection + "&listeModules=" + moduleSelection + "&listeIdModules=" + messageSelection + "&codeVal1=" + codeVal1 + "&codeVal2=" + codeVal2 + "&codeVal1Min=" + valeur1Min + "&codeVal1Max=" + valeur1Max + "&codeVal2Min=" + valeur2Min + "&codeVal2Max=" + valeur2Max;
		} else {
			datas = "AJAX=" + ajax + "&choixSubmit=" + choixSubmit + "&listeLocalisations=" + localisationSelection + "&listeGenres=" + genreSelection + "&listeModules=" + moduleSelection + "&listeIdModules=" + messageSelection + "&codeVal1=" + codeVal1 + "&codeVal2=" + codeVal2 + "&codeVal1Min=" + valeur1Min + "&codeVal1Max=" + valeur1Max + "&codeVal2Min=" + valeur2Min + "&codeVal2Max=" + valeur2Max;
		}
		break;
	}
	xhr.send(datas);
}

function reinitialisation() {
	var nouvelle_liste = {{ liste_req|json_encode|raw }}
	// Création de la div en fonction du tableau 'liste_req'
	var nouvelle_div = newDiv(nouvelle_liste);
	// Mise en place de la nouvelle div
	$('div.requetemessage').html(nouvelle_div);
}

function newDiv(nouvelleListe) {
	var nbRequetes = nouvelleListe.length;
	var nouvelleDiv = "<h2>" + nbRequetes + " requête";
	if (nbRequetes == 0) {
		document.getElementById('RAZ').disabled                 = true;
		document.getElementById('lance_recherche').disabled     = true;
	} else {
		document.getElementById('RAZ').disabled                 = false;
		document.getElementById('lance_recherche').disabled     = false;
	}
	if (nbRequetes > 1) {
		nouvelleDiv += 's';
	}
	nouvelleDiv += "</h2>";
	nouvelleDiv += "<table>";
	for (var numRequete = 0; numRequete < nbRequetes; numRequete++) {
		nouvelleDiv += "<tr class='noir'>";
		nouvelleDiv += "<td>Recherche " + numRequete + " : " + nouvelleListe[numRequete]['Texte'] + "</td>";
		nouvelleDiv += "<td>Suppression<input type='radio' name='suppression_requete' value='suppRequete_" + numRequete + "' onClick=\"attente();document.getElementById('choixSubmit').value='suppressionRequete';sendAjaxForm();\" /></td>";
		nouvelleDiv += "</tr>";
	}
	return(nouvelleDiv);
}

</script>
