<script type="text/javascript">
var $refreshInterval;
// Si refreshVar = 1: Empêche l'action de cloture modbus
var refreshVar = 0;
// Rafraichissement des données live de la partie listing toutes les secondes
// Prochaine page de live qui sera affichée
var nextLivePage = 'listing';
// Rafraichissement des données live de la partie graphique toutes les minutes
var dureeRefresh = dureeRefreshListing;
var tableauDesUnites = {'%':'Pourcentage','°C':'Température','Bool':'Booleen','T':'Tonne','Bar':'Bar'};
var tabColor = ['#7cb5ec', '#f15c80', '#8d4653', '#f7a35c','#31B404','#4B088A','#00FFFF','#0101DF','#0101DF'];
var ajaxEnCours = false;
var pageLive = {{ pageLive|json_encode|raw }};
var serieAffichee = false;
var premierHorodatage = {{ premierHorodatage|json_encode|raw }};
var derniersEvenements = {{ derniersEvenements|json_encode|raw }};
var dureeRefreshListing = {{ liveRefreshListing|json_encode|raw }};
var dureeRefreshGraphique = {{ liveRefreshGraphique|json_encode|raw }};
var alarmeScrollTop = {{ alarmeScrollTop|json_encode|raw }};
// On autorise 1 minute de recherche : Cloture des fichiers + Transfert ° Import en base - Avant timeout
var $delayModbus = 60000;
// Timeout avant abandon de l'attente de réponse modbus = Valeur de rafraichissement de la page des listing - 300
var $modbusTimeout = dureeRefreshListing - 300;
var $commandePing;
var $timeoutAutomate = {{ timeoutAutomate|json_encode|raw }};
var $pingTimeout = {{ pingTimeout|json_encode|raw }};
var $pingInterval = {{ pingIntervalle|json_encode|raw }};
var $pingUrl = $("#ping").attr('data-url');
var $getInfoModbusUrl = $("#ipc_supervision_get_infosModbus").attr('data-url');
var $noResponseModbus = 0;
// La requête getInfosModbus ne doit pas être envoyée si il n'y a pas eu de réponse de la requête précédente
var $responseModbus = true;
var $responsePing = true;
var $nbTimeoutModbus = 0;
var $nbTimeoutPing = 0;
// Indique que l'action de cloture modbus initial est en cours
var $clotureModbus = false;


$(document).ready(function () {
    setInterval(function() {
        $(".blink").fadeToggle();
    }, 500);

	// Variable permettant d'indiquer la localisation en cours d'analyse dans le live version mobile
    var $autom = {{ automateActif | json_encode | raw  }};
    var $infosAside = {{ infosAside | json_encode | raw  }};
    if ($infosAside === 'checked') {
        $('#infoAside').prop('checked', true);
        changeAside();
    }
    $('#automate-actif').text(' : ' + $autom);
    var serieValue = $('#selectSeriesGraphiques').data('value');
    $("#selectSeriesGraphiques option[value='" + serieValue + "']").prop("selected", true);
    $('#seriesGraphiques').change(function(){
        var $serie_number = 'live_modules' + $('#seriesGraphiques option:selected').attr('data-number');
        var $serie_name = $('#seriesGraphiques option:selected').attr('value');
        var $description_module = $('#seriesGraphiques').attr('data-value');
        setNewSerie($serie_number, $serie_name, $description_module, true);
    });

    $('#allPage').addClass('pageCachee');

	//      Affichage de la partie définie par la variable pageLive
	switch (pageLive) {
	case 'Listing':
		// 1 : Cache du graphique + des informations spécifiques aux graphiques
		$('#container_live').addClass('cacher');
		$('#supervision_info_graphique').addClass('cacher');
		// 2 : Afichage des listings + des informations spécifiques aux listings
		$('#live_listing').removeClass('cacher');
		$('#supervision_info_listing').removeClass('cacher');
		// 3 : Modification du bouton pour switcher sur la page des Graphiques depuis la page des Listing
		$('#supervision_live_change').html("<a href='#'>&lsaquo;</a> <a href='#'>" + traduire('live.lien.etat') + "</a> / <a href='#' class='inactive' id='changeAffichageLive' onClick=\"changeAffichageLive('Graphique');return false;\" >" + traduire('live.lien.graphique') + "</a> <a href='#'>&rsaquo;</a>");
		// 4 : Modification de la durée de rafraichissement automatique de la page
		dureeRefresh = dureeRefreshListing;
		// 5 : Définition de la prochaine page à afficher lors du rafraichissement automatique
		nextLivePage = 'listing';
		break;
	case 'Graphique':
		$('#allPage').addClass('waitingHighchart');
		// Variable indiquant qu'une série a été affichée : Permet de réafficher la série après un switch sur Listing sans refaire de recherche en base
		serieAffichee = true;
		// 1 : Affichage des informations spécifiques à la partie graphique
		$('#live_options_graphiques').removeClass('cacher');
		$('#seriesGraphiques').removeClass('cacher');
		// 2 : Modification du bouton pour switcher sur la page des Listing depuis la page des Graphiques
		$('#supervision_live_change').html("<a href='#'>&lsaquo;</a> <a href='#' class='inactive' id='changeAffichageLive' onClick=\"changeAffichageLive('Listing');return false;\" >" + traduire('live.lien.etat') + "</a> / <a href='#'>" + traduire('live.lien.graphique') + "</a> <a href='#'>&rsaquo;</a>");
		// 3 : Modification de la durée de rafraichissement automatique de la page
		dureeRefresh = dureeRefreshGraphique;
		// 4 : Définition de la prochaine page à afficher lors du rafraichissement automatique
		nextLivePage = 'graphique';
		break;
	}

    pingServeur(false);

	// Mise en place de la fonction de rafraichissement automatique
	// ajoutInterval() : Est appelée dans la fonction changeEvenement();

    //  Recherche des derniers évènements et selection de la partie aside affichée
    switch (derniersEvenements) {
    case 'derniers défauts':
        $('#titreDefaut').addClass('active');
        changeEvenement('defauts');
        break;
    case 'dernières alarmes':
        $('#titreAlarme').addClass('active');
        changeEvenement('alarmes');
        break;
    case 'derniers évènements':
        $('#titreEvenement').addClass('active');
        changeEvenement('evenements');
        break;
    }

	$('#alarme').scrollTop(alarmeScrollTop);
    /* Quand je clique sur l'icône je rajoute une classe au body */
    $('#header__icon').click(function(e){
        e.preventDefault();
        $('body').toggleClass('with--sidebar');
    });
	setTimeout(function(){
		supervision_refresh_initial();
	}, 1000);
});

function changeEvenement(evenement) {
	$('#allPage').toggleClass('waiting', true);
	refreshVar = 1;
	// Annulation du rafraichissement automatique
	supprimeInterval();
	setTimeout(function () {
		// Récupération de l'heure du dernier fichier présent en base de données.
		defineLastTimeEvenement();
		var xhrCE = getXHR();
		xhrCE.onreadystatechange = function() {
			if (xhrCE.readyState == 4) {
				if (xhrCE.status == 200) {
					var $tabEvenements = $.parseJSON(xhrCE.responseText);
					var $nouvelHtml = '<ul>';
					$.each($tabEvenements, function(key, value){
            			$nouvelHtml = $nouvelHtml + '<li>';
            			$nouvelHtml = $nouvelHtml + '<div class="calendar" style="background:'+value['couleur']+'">';
            			$nouvelHtml = $nouvelHtml + '<div class="day">' + myGetInfoDate(value['horodatage'], 'day') + '</div>';
            			$nouvelHtml = $nouvelHtml + '<div class="month">' + value['moisFr'] + ' ' + myGetInfoDate(value['horodatage'], 'smallyear') + '</div>';
            			$nouvelHtml = $nouvelHtml + '</div>';
            			$nouvelHtml = $nouvelHtml + '<div class="message">';
            			$nouvelHtml = $nouvelHtml + '<time>' + myGetInfoDate(value['horodatage'], 'hour') + ':' + myGetInfoDate(value['horodatage'], 'minute') + ':' + myGetInfoDate(value['horodatage'], 'second') + '.' + value['cycle'] + '</time>';
            			$nouvelHtml = $nouvelHtml + '<h1>' + value['module']+ '</h1>';
            			$nouvelHtml = $nouvelHtml + '<p>' + value['message']+ '</p>';
            			$nouvelHtml = $nouvelHtml + '</div>';
        			});
        			var $nouvelHtml = $nouvelHtml + '</ul>';
        			$('#alarme').html($nouvelHtml);
        			switch (derniersEvenements) {
        			case 'derniers défauts':
        			    $('#titreDefaut').removeClass('active');
        		    	break;
        			case 'dernières alarmes':
        			    $('#titreAlarme').removeClass('active');
        			    break;
        			case 'derniers évènements':
        			    $('#titreEvenement').removeClass('active');
        			    break;
       		 		}
        			switch (evenement) {
        			case 'evenements':
        			    $('#titreEvenement').addClass('active');
        		    	derniersEvenements = 'derniers évènements';
        		    	break;
        			case 'alarmes':
        			    $('#titreAlarme').addClass('active');
        			    derniersEvenements = 'dernières alarmes';
       	 	    		break;
      	 	 		case 'defauts':
            			$('#titreDefaut').addClass('active');
            			derniersEvenements = 'derniers défauts';
            			break;
        			}
					//	Remise de la fonction avec rafraichissement automatique
        			ajoutInterval();
					refreshVar = 0;
        			$('#allPage').removeClass('waiting');
					return(0);
				} else {
					ajoutInterval();
					refreshVar = 0;
					$('#allPage').removeClass('waiting');
					return(1);
				}
			}
		}
		// Réinitialisation de la variable de session tabDonneesLive afin de rechercher la liste des points pour les graphiques
		callPathAjax(xhrCE, 'ipc_ajax_getEvenements2', evenement, true);
		xhrCE.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
		xhrCE.send();
	}, 100);
	return(0);
}

// Fonction executée lors du choix d'un automate à afficher
function changeAutomate(keyAutomate) {
    // Récupération de l'index de l'automate actif
    // Si l'automate activé est l'automate affiché : Pas d'action
    // Sinon affichage des données de l'automate activé
    var indexAutomateActif = {{ indexAutomate }};
    if (indexAutomateActif != keyAutomate) {
        $('#allPage').toggleClass('waiting', true);
        // Temporisation pour attente du changement de curseur
        setTimeout(function () {
            // 1 : Annulation du rafraichissement automatique
            supprimeInterval();
            // 2 : Modification Ajax de la variable de session indexAutomate + modification de la pageLive (=Listing) + Réinitialisation des variables deleteTabEvenements et deleteTabDonneesLive
            $urlRequest = $('#ipc_set_indexAutomate').attr('data-url');
			$.ajax({
				type: 'post',
				url: $urlRequest,
				timeout: $timeoutAutomate,
				data: "variable=automate&index=" + keyAutomate,
				success: function($data, $textStatus) {
					window.location.href  = window.location.href;
				},
				error: function($data, $textStatus, $error) {
					changeAutomate(keyAutomate);
				}
			});
        },100);
        return(0);
    }
    return(0);
}



// Fonction appelée lors du clic sur le bouton switch pour changer la page du live affichée (Graphique ou Listing)
function changeLive(pageLive, newSerie, reloadPage) {
	var premierLive = {{ premierLive|json_encode|raw }};
	// 1 : Annulation du rafraichissement automatique
	supprimeInterval();
	// 2 : Modification Ajax de la variable de session pageLive pour sauvegarder l'information de la page à afficher lors du rafraichissement automatique
	var xhrCL = getXHR();
	callPathAjax(xhrCL, 'ipc_set_indexAutomate', null, false);
	xhrCL.setRequestHeader("Content-Type","application/x-www-form-urlencoded");
	var data = "variable=pageLive&index=" + pageLive;
	xhrCL.send(data);
	// 2 bis : Si le changement demande l'affichage du live graphique : Affichage de la premiere série par défaut
	if (newSerie == true) {
		setNewSerie(premierLive, null, null, reloadPage);
	}
	// 3 : Redéfinition de la fonction de rafraichissement automatique
	ajoutInterval();
	return(0);
}

function changeAffichageLive(newLive) {
	$('#allPage').addClass('waiting');
	//      Changement de la variable de session indiquant la page du live à afficher
	switch(newLive) {
	case 'Listing':
		// 1 : Cache du graphique + des informations spécifiques aux graphiques
		$('#container_live').addClass('cacher');
		$('#live_options_graphiques').addClass('cacher');
		$('#seriesGraphiques').addClass('cacher');
		// 2 : Affichage des listings + des informations spécifiques aux listings
		$('#live_listing').removeClass('cacher');
		$('#supervision_info_listing').removeClass('cacher');
		// 3 : Modification du bouton pour switcher sur la page des Graphiques depuis la page des Listing
		$('#supervision_live_change').html("<a href='#'>&lsaquo;</a> <a href='#'>" + traduire('live.lien.etat') + "</a> / <a href='#' class='inactive' id='changeAffichageLive' onClick=\"changeAffichageLive('Graphique');return false;\" >" + traduire('live.lien.graphique') + "</a> <a href='#'>&rsaquo;</a>");	
		// 4 : Modification de la durée de rafraichissement automatique de la page
		dureeRefresh = dureeRefreshListing;
		// 5 : Définition de la prochaine page à afficher lors du rafraichissement automatique
		nextLivePage = 'listing';
		// 6 : Changement de la variable de session indiquant la page Live à afficher lors du rafraichissement automatique
		changeLive('Listing', false, false);
		$('#allPage').removeClass('waiting');
		break;
	case 'Graphique':
		if (serieAffichee == false) {
			//  Si aucune série précédemment affichée :
			//  Lors du clic sur graphique affichage de la série 1 par défaut
			changeLive('Graphique', true, true);
		} else {
			// Si une série a précédemment été affichée : réaffichage de la série
			// 1 : Affichage du graphique + des informations spécifiques aux graphiques
			$('#container_live').removeClass('cacher');
			$('#live_options_graphiques').removeClass('cacher');
			$('#seriesGraphiques').removeClass('cacher');
			// 2 : Cache des listings + des informations spécifiques aux listings
			$('#live_listing').addClass('cacher');
			$('#supervision_info_listing').addClass('cacher');
			// 3 : Modification du bouton pour switcher sur la page des Listing depuis la page des Graphiques
			$('#supervision_live_change').html("<a href='#'>&lsaquo;</a> <a href='#' class='inactive' id='changeAffichageLive' onClick=\"changeAffichageLive('Listing');return false;\" >" + traduire('live.lien.etat') + "</a> / <a href='#'>" + traduire('live.lien.graphique') + "</a> <a href='#'>&rsaquo;</a>");
			// 4 : Modification de la durée de rafraichissement automatique de la page
			dureeRefresh = dureeRefreshGraphique;
			// 5 : Définition de la prochaine page à afficher lors du rafraichissement automatique
			nextLivePage = 'graphique';
			// 6 : Changement de la variable de session induiquant la page Live à afficher lors du rafraichissement automatique
			changeLive('Graphique', false, false);
			$('#allPage').removeClass('waiting');
		}
		break;
	}
	return(0);
}

function ajoutInterval() {
    $commandePing = setInterval(function() {
		//console.log('2_pi');
		if ($responsePing == false) {
			$nbTimeoutPing = $nbTimeoutPing + 1;
			$("#ping").attr('class', 'serveurInactif');
            $("#ping").text(traduire('live.label.serveur_occupe'));
			return 1;
		}
		$nbTimeoutPing = 0;
		//console.log('2_..ing');
		$responsePing = false;
        $.ajax({
            type: 'get',
            url: $pingUrl,
            success: function($data, $textStatus) {
				//console.log('2_..true ok');
				$responsePing = true;
                $("#ping").attr('class', 'serveurActif');
                $("#ping").text($data);
				return 0;
            },
            error: function(xhr, textStatus, error) {
				//console.log('2_..true nok');
				$responsePing = true;
                $("#ping").attr('class', 'serveurInactif');
                $("#ping").text(traduire('live.label.serveur_inactif') + ' : ' + textStatus);
                // Si le ping ne répond pas on retest immédiatement
                pingServeur(true);
				return 1;
            }
        });
    }, $pingInterval);

	$refreshInterval = setInterval(function () {
		//console.log('inter ' + $responseModbus);
		if ($responseModbus == false) {
			$nbTimeoutModbus = $nbTimeoutModbus + 1;
			//console.log('Pas de réponse depuis ' + $nbTimeoutModbus);
            $("#ping").attr('class', 'serveurInactif');
            $("#ping").text(traduire('live.label.serveur_occupe'));
			return 1;
		}
		$nbNoResponseModbus = 0;
		//console.log('.....vention');
		$responseModbus = false;
		// Récupération de la position du scroll pour réafficher l'élément de la page en cours de visualisation
		valScrollTop = $(window).scrollTop();
		alarmeScrollTop = $('#alarme').scrollTop();
		if (nextLivePage == 'graphique') {
        	$('body').css("overflow", "hidden");
        	$('#alarme').css("overflow", "hidden");
			// Condition pour ne pas lancer des requêtes ajax avant de recevoir la réponse de la précédente requête
			if (ajaxEnCours == false) {
				ajaxEnCours = true;
				$('#allPage').toggleClass('waiting', true);
				setTimeout(function () {
					var xhrAI = getXHR();
					// Réinitialisation de la variable de session tabDonneesLive afin de rechercher la liste des points pour les graphiques
					callPathAjax(xhrAI, 'ipc_supervision_reinit_session', 'evenement=tabDonneesLive&valScrollTop=' + valScrollTop + '&alarmeScrollTop=' + alarmeScrollTop, false);
					xhrAI.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
					xhrAI.send();
					window.location.href = window.location.href;
				}, 100);
			}
		} else {
			if (ajaxEnCours == false) {
				ajaxEnCours = true;
				$.ajax({
					type: 'post',
					url: $getInfoModbusUrl,
					success: function($data, $textStatus) {
						$noResponseModbus = 0;
						// On indique avoir reçu la réponse de la requête
						$responseModbus = true;
						//console.log(' . . . . . . . .. . . . . . .  response ok');
						var tabRetour = $.parseJSON($data);
						var tabLiveEnTetes = tabRetour[0];
						var tabTuiles = tabRetour[1];
						var indexAutomate = {{ indexAutomate|json_encode|raw }};
            			$("#ping").attr('class', 'serveurActif');
            			$("#ping").text(traduire('live.label.serveur_actif'));
						gestionTuiles(tabTuiles, indexAutomate);
						gestionEntete(tabLiveEnTetes);
						ajaxEnCours = false;
						return(0);
					},
					error: function(xhr, textStatus, erreur) {
						$responseModbus = true;
						//console.log(' . . . . . . . .. . . . . . .  response nok : ' + erreur + ' -> ' + textStatus);
						$noResponseModbus = $noResponseModbus + 1;
						if ($noResponseModbus >= 2) {
                        	$("#ping").attr('class', 'serveurInactif');
                        	$("#ping").text('Erreur serveur : ' + erreur);
						}
						ajaxEnCours = false;
						return 1;
					}	
				});
			}
			return(0);
		}
	}, dureeRefresh);
	return(0);
}

function supprimeInterval() {
	clearInterval($refreshInterval);
	clearInterval($commandePing);
	return(0);
}

// Fonction qui supprime les variables de session permettant la recherche d'une nouvelle liste de modules pour l'affichage graphique
function setNewSerie(numSerie, nameNewSerie, nameActiveSerie, reloadPage) {
	if( (nameNewSerie === null ) || (nameNewSerie !== nameActiveSerie) ) {
		supprimeInterval();
		$('#allPage').toggleClass('waiting', true);
		// Désactivation des liens
		$('#supervision_refresh').css({'pointer-events':'none'});
		var xhrSNS = getXHR();
		callPathAjax(xhrSNS, 'ipc_set_liveModules', null, false);
		xhrSNS.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
		var data = 'liveModules=' + numSerie;
		xhrSNS.send(data);
		// Rechargement de la page pour effectuer la recherche des données graphique
		if (reloadPage === true) {
			window.location.href = window.location.href;
		}
	}
}

// Création de l'Objet ActiveX pour la communication Ajax
function getXHR() {
	var xhr;
	try { 
		xhr = new ActiveXObject('Msxml2.XMLHTTP');
	} catch (e) { 
		try { 
			xhr = new ActiveXObject('Microsoft.XMLHTTP');
		} catch (e2) { 
			try { 
				xhr = new XMLHttpRequest();
			} catch (e3) { 
				xhr = false; 
			}
		}
	} 
	return xhr; 
}


function supervision_refresh_initial() {
        var $startTime = new Date().getTime();
        var $elapsedTime = 0;
        var $intervalDelayModbus;
	if ($('#aside').hasClass('cacher')) {
		// Permet d'indiquer que la cloture est en cours et qu'il faut attendre la fin du traitement
		$clotureModbus = true;
		var $urlSessionAside = $('#aside').attr('data-url')
		// Modification de la variable de session indiquant que la mise à jour a été faite.
		$.ajax({
			type: 'get',
			url: $urlSessionAside,
			async: true,
			error: function(xhr, textStatus) {
				supervision_refresh_initial();
				return(1);
			}
		});
        // Annulation du rafraichissement automatique
        supprimeInterval();
        // Fonction qui rafraichie les données : Fonction ajax pour cloture des fichiers sur les automates par requête modbus +
        // téléchargement des fichiers par Ftp +
        // Recherche des données correspondantes aux requêtes utilisateur
        setTimeout(function () {
            var xhrSR = getXHR();
			xhrSR.onreadystatechange = function(){
				if (xhrSR.readyState == 4 && xhrSR.status == 200) {
					var xhrSR2 = getXHR();	
					xhrSR2.onreadystatechange = function(){
						if (xhrSR2.readyState == 4 && xhrSR2.status == 200) {
							clearInterval($intervalDelayModbus);
							// Indique la fin du traitement de cloture modbus: L'affichage des données peut se faire et sera à jour
							$clotureModbus = false;
							//window.location.href = window.location.href;	
							return(0);
						}
					} 
					// Réinitialisation des variables tabEvenements et tabDonneesLive afin de réeffectuer les recherches avec prise en compte des nouveaux fichiers
					callPathAjax(xhrSR2, 'ipc_supervision_reinit_session', 'evenement=reinit', true);
					xhrSR2.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
					xhrSR2.send();
					return(0);
				}
			}
            // Appel de la fonction modbus de cloture et téléchargement des fichiers
            callPathAjax(xhrSR, 'ipc_supervision_modbus_cloture_ftp', null, true);
            xhrSR.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            xhrSR.send();
			// Annulation de la requête Modbus si elle met plus de x seconde à répondre
			var $intervalDelayModbus = setInterval(function(){
				$elapsedTime = new Date().getTime() - $startTime;
				if ($elapsedTime > $delayModbus) {
					clearInterval($intervalDelayModbus);
					xhrSR.abort();
					supervision_refresh_initial();
					//window.location.href = window.location.href;
					return(0);
				}
			},5000);
        },100);
    }
    return(0);
}

function affichage_evenement(){
	// Si la cloture modbus est en cours: Attente de la fin du traitement avant raffraichissement de la page
	if ($clotureModbus === true){
		$('#searchModbus').addClass('blink');
		var $intervalClotureModbus = setInterval(function(){
			if ($clotureModbus === false){
				clearInterval($intervalClotureModbus);
				window.location.href = window.location.href;
			}
		}, 1000);
	} else {
		window.location.href = window.location.href;
	}
}

function supervision_refresh() {
    var $startTime = new Date().getTime();
    var $elapsedTime = 0;
    var $intervalDelayModbus;
    if (refreshVar == 0) {
                $('#searchModbus').addClass('blink');
        refreshVar = 1;
        // Annulation du rafraichissement automatique
        supprimeInterval();
        // Fonction qui rafraichie les données : Fonction ajax pour cloture des fichiers sur les automates par requête modbus +
        // téléchargement des fichiers par Ftp +
        // Recherche des données correspondantes aux requêtes utilisateur
        setTimeout(function () {
            var xhrSR = getXHR();
                        xhrSR.onreadystatechange = function(){
                                if (xhrSR.readyState == 4 && xhrSR.status == 200) {
                                        var xhrSR2 = getXHR();
                                        xhrSR2.onreadystatechange = function(){
                                                if (xhrSR2.readyState == 4 && xhrSR2.status == 200) {
                                                        clearInterval($intervalDelayModbus);
                                                        window.location.href = window.location.href;
                                                        return(0);
                                                }
                                        }
                                        // Réinitialisation des variables tabEvenements et tabDonneesLive afin de réeffectuer les recherches avec prise en compte des nouveaux fichiers
                                        callPathAjax(xhrSR2, 'ipc_supervision_reinit_session', 'evenement=reinit', true);
                                        xhrSR2.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                                        xhrSR2.send();
                                        return(0);
                                }
                        }
            // Appel de la fonction modbus de cloture et téléchargement des fichiers
            callPathAjax(xhrSR, 'ipc_supervision_modbus_cloture_ftp', null, true);
            xhrSR.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            xhrSR.send();
                        // Annulation de la requête Modbus si elle met plus de x secondes à répondre
                        var $intervalDelayModbus = setInterval(function(){
                                $elapsedTime = new Date().getTime() - $startTime;
                                if ($elapsedTime > $delayModbus) {
                                        clearInterval($intervalDelayModbus);
                                        xhrSR.abort();
                                        window.location.href = window.location.href;
                                        return(0);
                                }
                        },5000);
        },100);
    }
    return(0);
}

function callPathAjax(xhrToCall, fonction, args, asynchrone) {
	$urlRequest = $('#' + fonction).attr('data-url');
	switch (fonction) {
	case 'ipc_supervision_modbus_cloture_ftp':
		xhrToCall.open("POST", $urlRequest, asynchrone);
		break;
	case 'ipc_set_indexAutomate':
		xhrToCall.open("POST", $urlRequest, asynchrone);
		break;
	case 'ipc_supervision_reinit_session':
		$urlRequest = $urlRequest + '?' + args;
		xhrToCall.open("GET", $urlRequest, asynchrone);
		break;
	case 'ipc_set_liveModules':
		xhrToCall.open("POST", $urlRequest, asynchrone);
		break;
	case 'ipc_supervision_get_infosModbus':
		xhrToCall.open("POST", $urlRequest, asynchrone);
		break;
	case 'ipc_ajax_getEvenements2':
		$urlRequest = $urlRequest + '/' + args;
		xhrToCall.open("GET", $urlRequest, asynchrone);
		break;
	}
}

//	Fonction qui affiche ou cache les catègories lors du clic sur le titre de la catégorie
function toggleCategorie(keyCategorie) {
	$('#categorie' + keyCategorie).toggleClass('cacher');
	return(0);
}

//	Date passé en paramètre : 2016-03-24 09:51:51 -> La fonction retourne une des infos de la date
function myGetInfoDate($date,$type) {
	switch ($type) {
	case 'year':
		return formatNumber($date.substr(0,4));
		break;
	case 'smallyear':
		return formatNumber($date.substr(2,2));
		break;
	case 'month':
		return formatNumber($date.substr(5,2));
		break;
	case 'day':
		return formatNumber($date.substr(8,2));
		break;
	case 'hour':
		return formatNumber($date.substr(11,2));
		break;
	case 'minute':
		return formatNumber($date.substr(14,2));
		break;
	case 'second':
		return formatNumber($date.substr(17,2));
		break;	
	default :
		return formatNumber($date);
		break;
	}
}
  
function formatNumber($nombre) {
	if ($nombre.match(/^.$/)) {
		return '0' + $nombre;
	}
	return $nombre;
}

function defineLastTimeEvenement() {
	// Fonction ajax permettant de connaitre l'heure du dernier fichier importé
	$urlGetLastTimeEvenement = $('#lastEvenement').attr('data-url');
	$.ajax({
		type: 'get',
		url: $urlGetLastTimeEvenement,
		success: function($data,$textStatus) {
			$('#lastEvenement').html($data);
		}
	});
}

function changeAside() {
    $('#allAside').toggleClass('cacher');
}

function hideTitle() {
    $('#tuilesSansTitre').toggleClass('cacher');
    $('#tuilesAvecTitre').toggleClass('cacher');
}

// Affichage d'une variable de type Tableau
function dump(arr,level) {
	var dumped_text="";if(!level) level=0;var level_padding="";for(var j=0;j<level+1;j++) level_padding +="    ";if(typeof(arr) == 'object'){for(var item in arr){var value = arr[item];if(typeof(value) == 'object'){dumped_text += level_padding + "'" + item + "' ...\n";dumped_text += dump(value,level+1);} else {dumped_text += level_padding + "'" + item + "' => \"" + value + "\"\n";}}}else {dumped_text = "===>"+arr+"<===("+typeof(arr)+")";}return dumped_text;
}

function pingServeur($asynchrone) {
    $.ajax({
        type: 'get',
        url: $pingUrl,
		async: $asynchrone,
        timeout: $pingTimeout,
        success: function($data, $textStatus) {
            $("#ping").attr('class', 'serveurActif');
            $("#ping").text($data);
        },
        error: function(xhr, textStatus) {
            $("#ping").attr('class', 'serveurInactif');
            $("#ping").text(traduire('live.label.serveur_inactif') + ' : ' + textStatus);
			//	En mode Sychrone, le programme attend un retour correct du ping pour continuer (Permet d'éviter le lancement de requêtes getInfosModbus en rafale - sans réponse du serveur)
			if ($asynchrone === false) {
				pingServeur($asynchrone);
			}
        }
    });
	return (0);
}

// Fonction javascript pour traduire des mots
function traduire(key) {
    var JsTranslations = $('#js-vars').data('translations');
    if (JsTranslations[key]) {
        return JsTranslations[key];
    } else {
        console.log('Translation not found: '+key);
        return key;
    }
}

</script>
