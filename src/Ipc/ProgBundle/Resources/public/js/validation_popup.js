var xhr = null;
var tabRequete = JSON.parse($("#tabloRequete").val());
var choixLocalisation = null;
var $chargementSelection = true;

function selection(page, type, supp) {
	attente();
	setTimeout(function() {
		ajaxGetChoixLocalisation();
		// Données à envoyer au serveur
		var genreSelection = document.getElementById('genres').value;
		var moduleSelection = document.getElementById('modules').value;
		var localisationSelection;
		if (choixLocalisation === null) {
			localisationSelection = document.getElementById('localisations').value;
		} else {
			document.getElementById('localisations').value = choixLocalisation;
			localisationSelection = choixLocalisation;
		}
		var reinit = false;
		if (type === 'reinitModule') {
			//reinit = true;
			type = 'module';
			moduleSelection	= 'all';
			genreSelection = 'all';
		}
		if (supp == true) {
			document.getElementById('codeModule').value = "";
		}
		if (type === 'genre') {
			xhr = getXHR();
			if (page === 'graphique') {
				callPathAjax(xhr, 'ipc_configSelect', 'genre0', false);
			} else if (page === 'listing') {
				callPathAjax(xhr, 'ipc_configSelect', 'genre1', false);
			}
			// Entête pour l'envoi de donnée par la méthode POST
			xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");  
			// A MODIFIER LORSQUE LES TABLES D'ECHANGES PAR LOCALISATION SERONT MISES EN PLACE
			// Pour l'instant on considère que every = all pour l'affichage des modules et des messages dans les listes déroulantes
			if (localisationSelection == 'every') {
				localisationSelection = 'all';
			}
			var datas = "genres=" + encodeURIComponent(genreSelection) + "&modules=" + encodeURIComponent(moduleSelection) + "&localisations=" + localisationSelection;
			xhr.send(datas);
			// Récupération de la réponse envoyée par le serveur
			// -La réponse correspond aux deux select à définir : module et message
			// -Elle est retournée sous forme d'une chaine de caractère
			// -La séparation entre les données des deux select est faite par le mot "ListeSuivante"
			// -Séparation des données des deux select pour les réinjecter dans les champs de la page html
			// Récupération de la position du séparateur
			// Récupération de la premiere liste (correspond au select des modules)
			// Récupération de la seconde liste (correspond au select des messages)
			var nouvelle_liste = xhr.responseText;
			var separateur = "ListeSuivante";
			var sprLength = separateur.length;
			var spr = nouvelle_liste.indexOf(separateur);
			var selectmodule = nouvelle_liste.slice(0, spr);
			var selectmessage = nouvelle_liste.slice(spr + sprLength);
			// Injection des données dans les listes déroulantes de la page Html
			document.getElementById('modules').innerHTML = selectmodule;
			// On sélectionne les valeurs précédemment selectionnées
			// Parcours de la liste déroulante
			// Lorsqu'on trouve la valeur précédemment sélectionnée on la resélectionne
			var $lastFound = false;
			for (var selectionUser=0; selectionUser<moduleSelection.length; selectionUser++) {
				for (var numOption=0; numOption<document.getElementById('modules').options.length; numOption++) {
					if (document.getElementById('modules').options[numOption].value == moduleSelection) {
						document.getElementById('modules').options[numOption].selected = true;
						$lastFound = true;
					}
				}
			}
			if ($lastFound == false) {
				type = 'reinitModule';
				selection(page, type, supp);
				return;
			}
			// Liste des messages
			// Sélection par défaut de la première valeur de la liste si elle existe
			document.getElementById('messages').innerHTML = selectmessage;
			if (selectmessage.length != 0) {
				document.getElementById('messages').options[0].selected = true;
			}
		}
		if (type === 'module') {
			xhr = getXHR();
			if (page === 'graphique') {
				callPathAjax(xhr, 'ipc_configSelect', 'module0', false);
			} else if (page === 'listing') {
				callPathAjax(xhr, 'ipc_configSelect', 'module1', false);
			}	
			xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
			// A MODIFIER LORSQUE LES TABLES D'ECHANGES PAR LOCALISATION SERONT MISES EN PLACE
			// Pour l'instant on considère que every = all pour l'affichage des modules et des messages dans les listes déroulantes
			if (localisationSelection === 'every') {
				localisationSelection = 'all';
			}
			var datas = "genres=" + encodeURIComponent(genreSelection) + "&modules=" + encodeURIComponent(moduleSelection) + "&localisations=" + localisationSelection;
			xhr.send(datas); 
			nouvelle_liste = xhr.responseText;
			// Mise des messages dans la liste déroulante
			document.getElementById('messages').innerHTML = nouvelle_liste;
			if (nouvelle_liste.length != 0) {
				document.getElementById('messages').value = document.getElementById('messages').options[0].value;
			}
			// Si il est demandé de réinitialiser les listes déroulantes
			if (reinit == true) {
				document.getElementById('modules').value = document.getElementById('modules').options[0].value;
				document.getElementById('genres').value = document.getElementById('genres').options[0].value;
				// En cas de Validation ou d'Annulation d'ajout d'une requête, 
				// Les listes déroulantes sont réinitialisées pour les prochaines recherches
				// et la page est raffraichie pour afficher les messages d'information du flashBag
				window.location.href = window.location.href;
			}
		}
        if (page === 'graphique') {
			selectionMessage('ipc_graphiques', 'graphique');
        } else if (page === 'listing') {
			selectionMessage('ipc_listing', 'listing');
        }
		fin_attente();
		return;
	}, 300);
}


// Appelée lors du focus sur l'encart 'Code Message' des popups.
// Si un module ou un genre est selectionné : Sélection du Genre et du Module initial et raffraichissement des données par appel de la fonction 'selection'
function reinitialise_codeMessage(page) {
	selection(page, 'genre', true);
}

// Fonction appelée lors de l'EDITION d'une requête enregistrée si un code message est défini : dans la page Listing/index.html.twig
// 1 Récupération du code en Majuscule
// 2 Calcul du nombre de caractères
// 2 Parcours de la liste des messages de modules
// 3 Au premier code identique trouvé : selection de celui-ci
function choixCodeMessage() {
	var choix = document.getElementById('codeModule').value.toUpperCase();
	var nbCaracteres = choix.length;
	for(var i=0; i<document.getElementById('messages').options.length; i++) {
		if(document.getElementById('messages').options[i].text.substr(0,nbCaracteres) == choix) {
			document.getElementById('messages').options[i].selected = true;
			return;
		}
	}
}

// Fonction qui saugegarde les valeurs entrées et retourne le mot enregistré
function searchMessage(e) {
	var caractere = String.fromCharCode(e.which);
	alert(caractere);
}

function resetAjaxForm(page) {
	attente();
	var nombre_requete = verifNombreRequetes()
	if (nombre_requete != 0) {
		setTimeout(function() {
			xhr = getXHR();
			var args = "AJAX=ajax&choixSubmit=RAZ";
			if (page == 'graphique') {
				callPathAjax(xhr,'ipc_accueilGraphique',args,false);
			} else if (page == 'listing') {
				callPathAjax(xhr,'ipc_accueilListing',args,false);
			} else if (page == 'etat') {
				callPathAjax(xhr,'ipc_accueilEtat',args,false);
			}
			xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
			// Suppression des variables de session correspondantes aux précédentes requêtes sélectionnées
			xhr.send(null);
			var texte_requete_html = "<table><thead><tr><th class='localisation'>Localisation</th><th class='code'>Code message</th><th class='designation'>Désignation</th><th class='actions'>Actions</th></tr></thead>";
			texte_requete_html = texte_requete_html + "<tbody>";
			texte_requete_html = texte_requete_html + "<tr>";
			if (page == 'graphique') {
				texte_requete_html = texte_requete_html + "<td colspan='3' class='texte'>" + traduire('label.ajout_courbe') + "</td>";
			} else if (page == 'listing') {
				texte_requete_html = texte_requete_html + "<td colspan='3' class='texte'>" + traduire('label.ajout_listing') + "</td>";
			} else if (page == 'etat') {
				texte_requete_html = texte_requete_html + "<td colspan='3' class='texte'>Ajouter un compteur</td>";
			}
			texte_requete_html = texte_requete_html + "<td class='actions'>";
			if (page == 'graphique') {
				texte_requete_html = texte_requete_html + "<a class='bouton' href='{{ path('ipc_graphiques') }}' target='_blank' onClick=\"onLightBox('ipc_graphiques','Sélection des requêtes graphique');openLightBox();return false;\">";
			} else if (page == 'listing') {
				texte_requete_html = texte_requete_html + "<a class='bouton' href='{{ path('ipc_listing') }}' target='_blank' onClick=\"onLightBox('ipc_listing','Sélection des requêtes de listing');openLightBox();return false;\">";
			} else if (page == 'etat') {
				texte_requete_html = texte_requete_html + "<a class='bouton' href='{{ path('ipc_etat') }}' target='_blank' onClick=\"onLightBox('ipc_etat','Sélection des requêtes etat');openLightBox();return false;\">";
			}  
			texte_requete_html = texte_requete_html + "<div class='bouton ajouter'></div>";
			texte_requete_html = texte_requete_html + "<div class='boutonname'>" + traduire('bouton.ajouter_requete') + "</div>";
			texte_requete_html = texte_requete_html + "</a>";
			texte_requete_html = texte_requete_html + "</td></tr>";
			texte_requete_html = texte_requete_html + "<input type='hidden' id='nombre_requetes' name='nombre_requetes' value='0'>";
			texte_requete_html = texte_requete_html + "</tbody></table>";
			$('div.requetemessage').html(texte_requete_html);
			fin_attente();
			return;
		}, 50);
	} else {
		fin_attente();
		return;
	}
}

function deleteAjaxForm(page, idForm) {
	attente();
	setTimeout(function() {
		xhr = getXHR();
		var args="AJAX=ajax&choixSubmit=suppressionRequete&suppression_requete="+idForm;
		if (page == 'graphique') {
			callPathAjax(xhr, 'ipc_accueilGraphique', args, false);
		} else if (page == 'listing') {
			callPathAjax(xhr, 'ipc_accueilListing', args, false);
		} else if (page == 'etat') {
			callPathAjax(xhr, 'ipc_accueilEtat', args, false);
		}
		xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
		xhr.send(null);
		var nouvelle_liste = JSON.parse(xhr.responseText);
		// Modification de la variable globale
		tabRequete = nouvelle_liste;
		var texte_requete_html = "<table><thead>";
		texte_requete_html = texte_requete_html + "<tr><th class='localisation'>Localisation</th><th class='code'>Code message</th><th class='designation'>Désignation</th><th class='actions'>Actions</th>";
		texte_requete_html = texte_requete_html + "</tr></thead>";
		texte_requete_html = texte_requete_html + "<tbody>";
		var numListe = 0;
		for (liste in nouvelle_liste) {
			texte_requete_html = texte_requete_html + "<tr><td class='localisation'><div class='txtlocalisation'>"+nouvelle_liste[liste]['localisation']+"</div></td>";
			if (nouvelle_liste[liste]['code'] != null) {
				texte_requete_html = texte_requete_html + "<td class='code'>"+nouvelle_liste[liste]['code']+"</td>";
			} else {
				texte_requete_html = texte_requete_html + "<td class='code'>&nbsp;</td>";
			}
			texte_requete_html = texte_requete_html + "<td class='designation'>"+nouvelle_liste[liste]['message']+"</td>";
			texte_requete_html = texte_requete_html + "<td class='actions'>";
			if (page == 'graphique') {
				texte_requete_html = texte_requete_html + "<a class='bouton' href='{{ path('ipc_graphiques') }}' target='_blank' name='modRequete_" + nouvelle_liste[liste]['numrequete'] + "' onClick=\"declanchementUpdateAjaxForm(1,'ipc_graphiques','graphique',this.name,"+numListe+",'modificationRequete');return false;\" ><div class='bouton editer'></div><div class='boutonname'>" + traduire('bouton.editer_requete') + "</div></a>";
			} else if (page == 'listing') {
				texte_requete_html = texte_requete_html + "<a class='bouton' href='{{ path('ipc_listing') }}' target='_blank' name='modRequete_" + nouvelle_liste[liste]['numrequete'] + "' onClick=\"declanchementUpdateAjaxForm(1,'ipc_listing','listing',this.name,"+numListe+",'modificationRequete');return false;\" ><div class='bouton editer'></div><div class='boutonname'>" + traduire('bouton.editer_requete') + "</div></a>";
			} else if (page == 'etat') {
				texte_requete_html = texte_requete_html + "<a class='bouton' href='{{ path('ipc_etat') }}' target='_blank' name='modRequete_" + nouvelle_liste[liste]['numrequete'] + "' onClick=\"declanchementUpdateAjaxForm(1,'ipc_etat','etat',this.name,"+numListe+",'modificationRequete');return false;\" ><div class='bouton editer'></div><div class='boutonname'>" + traduire('bouton.editer_requete') + "</div></a>";
			}
			texte_requete_html = texte_requete_html + "<a class='bouton' href='#' target='_blank' name='suppRequete_" + nouvelle_liste[liste]['numrequete'] + "' onClick=\"declanchementDeleteAjaxForm(1,'" + page + "',this.name,'suppressionRequete');return false;\"><div class='bouton supprimer'></div><div class='boutonname'>" + traduire('bouton.supprimer_requete') + "</div></a></td>";
			texte_requete_html = texte_requete_html + "</tr>";
			numListe ++;
		}
		texte_requete_html = texte_requete_html + "<tr>";
		if (page == 'graphique') {
			texte_requete_html = texte_requete_html + "<td colspan='3' class='texte'>" + traduire('label.ajout_courbe') + "</td>";
		} else if (page == 'listing') {
			texte_requete_html = texte_requete_html + "<td colspan='3' class='texte'>" + traduire('label.ajout_listing') + "</td>";
		} else if (page == 'etat') {
			texte_requete_html = texte_requete_html + "<td colspan='3' class='texte'>Ajouter un compteur</td>";
		}
		texte_requete_html = texte_requete_html + "<td class='actions'>";
		// Si la lightBox est vide, recherche des données en base, si elle a déjà été affichée réaffichage
		if (page == 'graphique') {
			texte_requete_html = texte_requete_html + "<a class='bouton' href='{{ path('ipc_graphiques') }}' target='_blank' onClick=\"onLightBox('ipc_graphiques','Sélection des requêtes graphique');openLightBox();return false;\">";
		} else if (page == 'listing') {
			texte_requete_html = texte_requete_html + "<a class='bouton' href='{{ path('ipc_listing') }}' target='_blank' onClick=\"onLightBox('ipc_listing','Sélection des requêtes de listing');openLightBox();return false;\">";
		} else if (page == 'etat') {
			texte_requete_html = texte_requete_html + "<a class='bouton' href='{{ path('ipc_etat') }}' target='_blank' onClick=\"onLightBox('ipc_etat','Sélection des requêtes etat');openLightBox();return false;\">";
		}
		texte_requete_html = texte_requete_html + "<div class='bouton ajouter'></div>";
		texte_requete_html = texte_requete_html + "<div class='boutonname'>" + traduire('bouton.ajouter_requete') + "</div>";
		texte_requete_html = texte_requete_html + "</a></td>";
		texte_requete_html = texte_requete_html + "</tr>";
		texte_requete_html = texte_requete_html + "<input type='hidden' id='nombre_requetes' name='nombre_requetes' value='"+nouvelle_liste.length+"'>";
		texte_requete_html = texte_requete_html + "</tbody></table>";
		$('div.requetemessage').html(texte_requete_html);
		fin_attente();
		return;
	}, 50);
}


//	Fonction qui envoi les données de la popup d'ajout d'un message de module 
function sendAjaxForm(page) {
	attente();
	setTimeout(function() {
		var localisationSelection = document.getElementById('localisations').value;
        if (localisationSelection == '') {
			fin_attente();
            return
        }
		var genreSelection = document.getElementById('genres').value;
		var moduleSelection = document.getElementById('modules').value;
		var messageSelection = document.getElementById('messages').value;
		var codeVal1 = $("input[type='radio'][name='codeVal1']").filter(':checked').val();
		var codeVal2 = $("input[type='radio'][name='codeVal2']").filter(':checked').val();
		var valeur1Min = null;
		var valeur1Max = null;
		var valeur2Min = null;
		var valeur2Max = null;
		var modificationRequete = document.getElementById('modificationRequete').value;
		var choixSubmit = document.getElementById('choixSubmit_add').value;
		var suppressionRequete = $("input[type='radio'][name='suppression_requete']").filter(':checked').val();
		var ajax = 'ajax';
		switch (codeVal1) {
		case undefined:
			break;
		case 'Inf':
			var valeur1Min = parseInt(document.getElementById('codeVal1Min').value);
			break;
		case 'Sup':
			var valeur1Min = parseInt(document.getElementById('codeVal1Max').value);
			break;
		case 'Int':
			var valeur1Min = parseInt(document.getElementById('codeVal1IntMin').value);
			var valeur1Max = parseInt(document.getElementById('codeVal1IntMax').value);
			break;
		}
		switch (codeVal2) {
		case undefined:
			break;
		case 'Inf':
			var valeur2Min = parseInt(document.getElementById('codeVal2Min').value);
			break;
		case 'Sup':
			var valeur2Min = parseInt(document.getElementById('codeVal2Max').value);
			break;
		case 'Int':
			var valeur2Min = parseInt(document.getElementById('codeVal2IntMin').value);
			var valeur2Max = parseInt(document.getElementById('codeVal2IntMax').value);
			break;
		}
		// Vérification des paramètres
		// Si un des paramètres n'est pas valide on affiche le message d'erreur dans la message box
		var verif_param = true;
		var message_erreur_param = '';
		if (valeur1Max != null) {
			if (valeur1Max < valeur1Min) {
				message_erreur_param = 'Erreur : valeur1 max < valeur1 min (' + valeur1Max + ' < ' + valeur1Min + ')';
				verif_param = false;
			}
		}
		if (valeur2Max != null) {
			if (valeur2Max < valeur2Min) {
				message_erreur_param = 'Erreur : valeur2 max < valeur2 min ('+valeur2Max+' < '+valeur2Min+')';
				verif_param = false;
			}
		}
		if (messageSelection == '') {
			message_erreur_param = 'Erreur : Aucun message sélectionné';
			verif_param = false;
		}
		if (verif_param == false) {
			//closeLightBox();
			$('#messageboxInfos').text(message_erreur_param);
			$('#messagebox').removeClass('cacher');
			fin_attente();
			return;
		}
		xhr = getXHR();
		var args = "AJAX=" + ajax + "&choixSubmit=" + choixSubmit + "&listeLocalisations=" + localisationSelection + "&listeGenres=" + genreSelection + "&listeModules=" + moduleSelection + "&listeIdModules=" + messageSelection + "&codeVal1=" + codeVal1 + "&codeVal2=" + codeVal2 + "&codeVal1Min=" + valeur1Min + "&codeVal1Max=" + valeur1Max + "&codeVal2Min=" + valeur2Min + "&codeVal2Max=" + valeur2Max + "&modificationRequete=" + modificationRequete;
		if (page === 'graphique') {
			callPathAjax(xhr, 'ipc_accueilGraphique', args, false);
		} else if (page === 'listing') {
			callPathAjax(xhr, 'ipc_accueilListing', args, false);
		} else if (page === 'etat') {
			callPathAjax(xhr, 'ipc_accueilEtat', args, false);
		}
		xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
		xhr.send(null);
		try {
			var nouvelle_liste = JSON.parse(xhr.responseText);
		} catch(err) {
			// En cas d'erreur : Reload de la page pour affichage du popup d'erreur
			alert('error : '+err);
			alert(xhr.responseText);
		    window.location.href = window.location.href;
			return;
		}
		// Modification de la variable globale
		tabRequete = nouvelle_liste;
		var texte_requete_html  = "<table><thead><tr><th class='localisation'>" + traduire('label.localisation') + "</th><th class='code'>" + traduire('label.code_message') + "</th><th class='designation'>" + traduire('label.designation') + "</th><th class='actions'>" + traduire('label.action') + "</th></tr></thead>";
		texte_requete_html = texte_requete_html + "<tbody>";
		var numListe = 0;
		for (liste in nouvelle_liste) {
			texte_requete_html = texte_requete_html + "<tr><td class='localisation'><div class='txtlocalisation'>" + nouvelle_liste[liste]['localisation'] + "</div></td>";
			if (nouvelle_liste[liste]['code'] != null) {
				texte_requete_html = texte_requete_html + "<td class='code'>" + nouvelle_liste[liste]['code'] + "</td>";
			} else {
				texte_requete_html = texte_requete_html + "<td class='code'>&nbsp;</td>";
			}
			texte_requete_html = texte_requete_html + "<td class='designation'>" + nouvelle_liste[liste]['message'] + "</td>";
			texte_requete_html = texte_requete_html + "<td class='actions'>";
			if (page === 'graphique') {
				texte_requete_html = texte_requete_html + "<a class='bouton' href='{{ path('ipc_graphiques') }}' target='_blank' name='modRequete_" + nouvelle_liste[liste]['numrequete'] + "' onClick=\"declanchementUpdateAjaxForm(1,'ipc_graphiques','graphique',this.name," + numListe + ",'modificationRequete');return false;\" ><div class='bouton editer'></div><div class='boutonname'>" + traduire('bouton.editer_requete') + "</div></a>";
			} else if (page === 'listing') {
				texte_requete_html = texte_requete_html + "<a class='bouton' href='{{ path('ipc_listing') }}' target='_blank' name='modRequete_" + nouvelle_liste[liste]['numrequete'] + "' onClick=\"declanchementUpdateAjaxForm(1,'ipc_listing','listing',this.name," + numListe + ",'modificationRequete');return false;\" ><div class='bouton editer'></div><div class='boutonname'>" + traduire('bouton.editer_requete') + "</div></a>";
			} else if (page === 'etat') {
				texte_requete_html = texte_requete_html + "<a class='bouton' href='{{ path('ipc_etat') }}' target='_blank' name='modRequete_" + nouvelle_liste[liste]['numrequete'] + "' onClick=\"declanchementUpdateAjaxForm(1,'ipc_etat','etat',this.name," + numListe + ",'modificationRequete');return false;\" ><div class='bouton editer'></div><div class='boutonname'>" + traduire('bouton.editer_requete') + "</div></a>";
			}
			texte_requete_html = texte_requete_html + "<a class='bouton' href='#' target='_blank' name='suppRequete_" + nouvelle_liste[liste]['numrequete'] + "' onClick=\"declanchementDeleteAjaxForm(1,'" + page + "',this.name,'suppressionRequete');return false;\" ><div class='bouton supprimer'></div><div class='boutonname'>" + traduire('bouton.supprimer_requete') + "</div></a></td>";
			texte_requete_html = texte_requete_html + "</tr>";
			numListe += 1;
		}
		texte_requete_html = texte_requete_html + "<tr>";
		if (page === 'graphique') {
			texte_requete_html = texte_requete_html + "<td colspan='3' class='texte'>" + traduire('label.ajout_courbe') + "</td>";
		} else if (page === 'listing') {
			texte_requete_html = texte_requete_html + "<td colspan='3' class='texte'>" + traduire('label.ajout_listing') + "</td>";
		} else if (page === 'etat') {
			texte_requete_html = texte_requete_html + "<td colspan='3' class='texte'>Ajouter un compteur</td>";
		}
		texte_requete_html = texte_requete_html + "<td class='actions'><a class='bouton' href='#' target='_blank' onClick='ouverturePopup(\"" + page + "\");return false;'>";
		texte_requete_html = texte_requete_html + "<div class='bouton ajouter'></div>";
		texte_requete_html = texte_requete_html + "<div class='boutonname'>" + traduire('bouton.ajouter_requete') + "</div></a></td>";
		texte_requete_html = texte_requete_html + "</tr>";
		texte_requete_html = texte_requete_html + "<input type='hidden' id='nombre_requetes' name='nombre_requetes' value='" + nouvelle_liste.length + "'>";
		texte_requete_html = texte_requete_html + "</tbody></table>";
		$('div.requetemessage').html(texte_requete_html);
		fin_attente();
		return;
	}, 50);
}

// Remise à vide du champs "modificationRequete"
function razUpdate() {
	document.getElementById('modificationRequete').value = '';
	razCodeModule(); 
}

function razCodeModule() {
	document.getElementById('codeModule').value = '';
}

function ajaxSetChoixLocalisation() {
	var localisationSelection = document.getElementById('localisations').value;
    var $url_setLocalisation = $("#localisations").attr('data-url');
    // Envoi d'une requête ajax pour sauvegarder la localisation à afficher
    $.ajax({
        type: 'get',
		async: false,
		timeout: 5000,
        url: $url_setLocalisation,
        data: "localisation=" + localisationSelection
    });
}

function ajaxGetChoixLocalisation() {
    var $url_setLocalisation = $("#localisations").attr('data-url');
    // Envoi d'une requête ajax pour sauvegarder la localisation à afficher
    $.ajax({
        type: 'get',
        url: $url_setLocalisation,
        data: "localisation=get",
		async: false,
		timeout: 5000,
        success: function($data, $textStatus, $xhr){
			choixLocalisation = $data;
        },
		error: function($xhr, $textStatus, $error){
			choixLocalisation = null;
        }
    });
}



