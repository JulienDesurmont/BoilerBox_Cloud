//	Liste des fonctions
//function ajaxGetSessionsInfos()
//function getXHR()
//function actionMenu(urlLien,idMenuActif)
//function switchActive(idMenuActif,pageActive)
//function switchInactive(idMenuInactif,pageActive)
//function addActions(action)
//function modificationPeriode(choixSelection)
//function setSiteIpcDimension()
//function setSiteIpcSmallDimension() 
//function addCloseCalendarFunction()
//function removeCloseCalendarFunction()
//function addShadow(type)
//function addLowShadow()
//function removeLowShadow()
//function removeShadow(typeShadow)
//function desactivateLowLinks()
//function activateLowLinks()
//function desactivateLinks()
//function activateLinks()
//function validation_messagebox()
//function closeMessageBox()
//function cacheMessageBox()
//function closeCalendar()
//function cacheCalendarBox()
//function addCloseMessageFunction()
//function removeCloseMessageFunction()
//function dateDuJour()
//function dateHeureActuelle()
//function attente()
//function fin_attente()
//function dump(arr,level)
//function impression()
//function majMaxExecTime()
//function submitForm(idForm)
//function callPathAjax(xhr,fonction,args,asynchrone)
//function defineDates()
//function inverseDate(dateString)
//function createTextDate(dateString)
//function fillNumber(num)
//function newTitle(page)
//function infoPeriode()
//function refresh(formulaireName)

//function preparePopup(page)
//function onLightBox(page,titre)
//function closeLightBox()
//function closeLightBoxSmall()
//function openLightBox()
//function openLightBoxDiffere()
//function buttonSwitch(idScript)
//function buttonSlides(idButton,nom,typeChoix)
//function switch_from_text(idButtonRadio)
//function switch_from_label(idButtonRadio)
//function switch_schemabox(idButtonRadio,idButton,nom,typeChoix)
//function switch_txt(idButton,nom,typeChoix)
//function switch_exclusion_message(idButton)
//function reinitialise_popup_liste()
//function appelPopup(newUrl,typePopup)
//function ouverturePopup(page)
//function validationPopup(idForm)
//function addClosePopupFunction()
//function removeClosePopupFunction()
//function cachePopupBox()
//function checkNbMaxRequetes(page)
//function selectionMessage(urlDest,page)
//function getMessages(urlDest,chaine)
//function verifNombreRequetes()
//function declanchementUpdateAjaxForm(etape,url,page,name,index,valueSubmit)
//function declanchementDeleteAjaxForm(etape,page,name,valueSubmit)
//function traduire

var delayTimer;
var url;
var checkRequete;
var message = '';
var multiplesTouches = 0;
var lastMultiplesTouches = 1;
var numMultiplesTouches = 0;
var messagePrecedent = '';
var choixPeriode = 'modifier';
var $chargementJs = true;
var $checkDeDate;

desactivateLinks();

// Cache de la fenêtre lightbox
// Gestion des MENUS : Appel AJAX pour effectuer la modification de la variable de session 'menu_courant'
// Inhibition du raffraichissement par F5
// Cache de l'image du loader
$(document).ready(function() {
	var tabSession = Array();
	var userLabel = $('#userLabel').text();
	if (userLabel == '') {
		tabSession = ajaxGetSessionsInfos();
		$('#userLabel').text(tabSession['userLabel']);
	}
	$('#lightbox').click(function(){
		// Fonction qui annule la fermeture du lighbox lors d'un clic dedans
		return false;
	});
	$('#messagebox').click(function(){
		// Fonction qui annule la fermeture du messagebox lors d'un clic dedans
		return false;
	});
	/*
	$('#lightboxSmall').click(function(){
		// Fonction qui annule la fermeture du lighboxSmall lors d'un clic dedans
		return false;
	});
	*/
	// Lors du click sur la banière : Si le menu choix de la période n'est pas activé : Activation du menu de choix de période
	$('configperiode').on('click',function(){
		if (choixPeriode == 'modifier') {
			// Ouverture de la partie : Modification de période
			modificationPeriode('modifier');	
		}
		return false;
	});
	// Ajout des fonctions des événements sur la banières des périodes
	addActions('periode');
	// Inhibition du raffraichissement par F5
	$(document).keydown(function(event) {
		if (event.which == 116) {
			// Gestion du bouton F5
			return false;
		} else if (event.which == 13) {
			//	Gestion du bouton Enter : Cloture de la message Box Ou Validation de la popup d'ajout de requete
			if ($('#messageboxTexte').text().trim() != '') {
    			closeMessageBox();
			}
			//	Validation des popups d'ajout de requêtes
			if (! $('#lightbox').hasClass('cacher')) {
				$("#validPopup").click();
			}
		} else if (event.which == 27) {
			//	Sur click du bouton Echap/ Fermeture des popups ou Cloture de la message Box 
            if (! $('#lightbox').hasClass('cacher')) {
                $("#fermerPopup").click();
            }
			if ($('#messageboxTexte').text().trim() != '') {
				closeMessageBox();
			}
		} else if (event.which == 8) {
			//	Bouton retour arriere. 
			//	Le bouton retour arrière ne fait rien sauf sur les objet de class .inputText lorsqu'ils sont sélectionné
			if (! $('.inputText').is(':focus')) {
				return false;
			}
        }
	});
	// Action qui place le focus sur la liste des messages lorsqu'un code module est validé
	$('#codeModule').keypress(function(e){
		if (e.which == 13) $('#messages').focus();
	});
	// Si un message d'info est présent : Affichage du messagebox
	var tabMessagesInfo = $.trim($('#messageboxInfos').text());
	if (tabMessagesInfo != '') {
		$('#messagebox').removeClass('cacher');
		addShadow('message');
		desactivateLinks();
	}
	// Mise des dates de début et de fin si elle ne sont pas définies
	if ($('#date_d').val() == '') {
		$('#date_d').val(dateDuJour());
		$('#date_f').val(dateDuJour());
	}
	activateLinks();
	fin_attente();
	var htmlPeriode	= $.trim($('div.entetePeriode').text());
	var pattern = /^check:/
	var match = pattern.exec(htmlPeriode);
	if (match != null) {
		$checkDeDate = htmlPeriode.charAt(6);
		htmlPeriode = htmlPeriode.substring(7);
		// Si la période n'est pas inclue dans toutes les périodes d'analyses des localisations du site courant :  affichage du bouton d'information
		if (parseInt($checkDeDate) == 0) {
			if ($('#boutonInfoPeriode').hasClass('cacher')) {
				$('#boutonInfoPeriode').removeClass('cacher');
			}
		}
		$('div.entetePeriode').text(htmlPeriode);
	}	
	$('#periodeDefinie').removeClass('cacher');
});

// Fonction appelée lors du chargement des pages d'accueil Listing et Graphique afin de précharger la popup
function preparePopup(page) {
    if ($.trim($('#lightbox').text()).length === 0) {
        xhr = getXHR();
        xhr.onreadystatechange = function() {
            if ((xhr.readyState == 4) && (xhr.status == 200)) {
                document.getElementById('lightbox').innerHTML = xhr.responseText;
                if (page == 'ipc_listing') {
                    selection('listing', 'reinitModule', true);
                } else if(page =='ipc_graphique') {
                    selection('graphique', 'reinitModule', true);
                }
                return (0);
            }
        }
        callPathAjax(xhr, page, null, false);
        xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
        xhr.send();
    }
}


// Fonction appelée lors de l'ajout d'une requête
function onLightBox(page, titre) {
    var checkPage;
    if (page === 'ipc_graphiques') {
        checkPage = 'graphique';
    }
    if (page === 'ipc_listing') {
        checkPage = 'listing';
    }
    if (page === 'ipc_etat') {
        checkPage = 'etat';
    }
    checkRequete = checkNbMaxRequetes(checkPage);
    // Si le nombre de requêtes autorisées n'est pas dépassé ( ou lors de la configuration sans limitation du nombre de requêtes)
    if (checkRequete == true) {
        // Si la lightbox n'est pas vide : Affichage de la lightbox
        if ($.trim($('#lightbox').text()).length === 0) {
            attente();
            setTimeout(function() {
                xhr = getXHR();
                callPathAjax(xhr, page, null, false);
                xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
                xhr.send();
                document.getElementById('lightbox').innerHTML = xhr.responseText;
                fin_attente();
            }, 50);
            return (true);
        } else {
            return (false);
        }
    } else {
        return (false);
    }
}

function closeLightBox() {
    if ($('#lightbox').hasClass('cacher') == false) {
        $('#lightbox').addClass('cacher');
        activateLinks();
        removeShadow('popup');
    }
}


function closeLightBoxSmall() {
    if ($('#lightboxSmall').hasClass('cacher') == false) {
        $('#lightboxSmall').addClass('cacher');
        activateLinks();
        removeShadow('popup');
    }
}


function openLightBox() {
    if (checkRequete == true) {
        setTimeout(function() {
            addShadow('popup');
            desactivateLinks();
            $('#lightbox').removeClass('cacher');
            fin_attente();
        }, 50);
    }
}

function openLightBoxDiffere() {
    if (checkRequete == true) {
        setTimeout(function() {
            addShadow('popup');
            desactivateLinks();
            $('#lightbox').removeClass('cacher');
            fin_attente();
        }, 50);
    }
}

//Fonction qui active ou désactive les scripts
//Utilisée par src/Ipc/ConfigurationBundle/Resources/views/Configuration/gestionScripts.html.twig
function buttonSwitch(idScript) {
    var xhr = getXHR();
    var datas = "";
    if (idScript === 'all') {
        switch ($("input[name='allActiv']:checked").val()) {
        case "aall":
            $('div .boutonSwitch').each(function () {
                $(this).attr('class', 'off boutonSwitch');
            });
            $('.gestion_infosScripts span').each(function () {
                $(this).text('Inactif');
            });
            datas = "script=" + idScript + "&action=desactivation";
            break;
        case "sall":
            $('div .boutonSwitch').each(function () {
                $(this).attr('class', 'on boutonSwitch');
            });
            $('.gestion_infosScripts span').each(function () {
                $(this).text('Actif');
            });
            datas = "script=" + idScript + "&action=activation";
            break;
        }
        callPathAjax(xhr, 'ipc_set_script', datas, true);
    } else {
        // Activation du service
        if ($('#' + idScript).hasClass('off')) {
            $('#' + idScript).removeClass('off');
            $('#' + idScript).addClass('on');
            datas = "script=" + idScript + "&action=activation";
            callPathAjax(xhr, 'ipc_set_script', datas, true);
            $('span.' + idScript).text('Actif');
        } else if ($('#' + idScript).hasClass('on')) {
            $('#' + idScript).removeClass('on');
            $('#' + idScript).addClass('off');
            datas = "script=" + idScript + "&action=desactivation";
            callPathAjax(xhr, 'ipc_set_script', datas, true);
            $('span.' + idScript).text('Inactif');
        }
        $("input[name='allActiv'][value='sall']").attr('checked', false);
        $("input[name='allActiv'][value='aall']").attr('checked', false);
    }
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    xhr.send();
}

// Fonction qui change la classe des divs des popups afin de rendre disponible ou non les informations
function buttonSlides(idButton, nom, typeChoix) {
    // Si la demande concerne la mise 'on' du bouton
    // On accepte la sélection des inputs
    // Envoie vers la fonction d'affichage des hachures et du text associé
    // Si la demande concerne la mise 'off' du bouton
    // On interdit la sélection des inputs
    // Désélection des boutons radios
    // Suppression du text
    if ($('#' + idButton).hasClass('off')) {
        $('#' + idButton).removeClass('off');
        $('#' + idButton).addClass('on');
        $('#' + idButton + ' input').each(function () {
            this.disabled = false;
            if (this.type === 'radio') {
                if ((this.name === nom) && (this.value === typeChoix)) {
                    this.checked = true;
                }
            }
        });
        var typeRadio = null;
        switch (typeChoix) {
        case 'Inf':
            typeRadio = 'Min';
            break;
        case 'Int':
            typeRadio = 'Int';
            break;
        case 'Sup':
            typeRadio = 'Max';
            break;
        }
        var idRadioButton = 'radio_' + nom + typeRadio;
        switch_schemabox(idRadioButton, idButton, nom, typeChoix);
    } else if ($('#' + idButton).hasClass('on')) {
        $('#' + idButton).removeClass('on');
        $('#' + idButton).addClass('off');
        $('#' + idButton + ' input').each(function () {
            this.disabled = true;
            if (this.type === 'radio') {
                this.checked = false;
            }
        });
        $('#' + idButton + ' div.txt').html("");
    }
}

// Fonction qui selectionne le radio box correspondant au message de valeur qui recoit le focus
function switch_from_text(idButtonRadio) {
    //  Timeout pour que le clic se fasse aprés le click = return false de l'evenement onready
    setTimeout(function () {
        $('#' + idButtonRadio).click();
    }, 10);
}

// Fonction qui selectionne le radio box correspondant au message de valeur qui recoit le focus
function switch_from_label(idButtonRadio) {
    if ($('#' + idButtonRadio).attr('disabled') == undefined) {
        // Timeout pour que le clic se fasse aprés le click = return false de l'evenement onready
        setTimeout(function() {
            $('#' + idButtonRadio).click();
        }, 10);
    }
}


// Fonction qui met en place les hachage sur les graphique en fonction de la condition sur la valeur (Sup, inf Int)
function switch_schemabox(idButtonRadio,idButton,nom,typeChoix) {
    setTimeout(function() {
        document.getElementById(idButtonRadio).checked = true;
        var newHtml = '';
        switch (typeChoix) {
        case 'Inf':
            newHtml = "<div class='schemabox'><div class='hachures-b'></div></div>";
            break;
        case 'Int':
            newHtml = "<div class='schemabox'><div class='hachures-h'></div><div class='hachures-b'></div></div>";
            break;
        case 'Sup':
            newHtml = "<div class='schemabox'><div class='hachures-h'></div></div>";
            break;
        }
        $('#'+idButton+' div.schemabox').html(newHtml);
        switch_txt(idButton,nom,typeChoix);
    }, 10);
}

function switch_txt(idButton, nom, typeChoix) {
    // Si le bouton radio est selectionné, modification du champ text avec la nouvelle valeur
    if ($('#' + idButton + " input[type='radio'][name='" + nom + "'][value='" + typeChoix + "']").is(':checked')) {
        switch(typeChoix) {
        case 'Sup':
            var valeur1 = '';
            // Recherche des valeurs à afficher (correspondant aux input type=text qui sont frere du bouton radio mentionné par 'idButton')
            $('#' + idButton + " input[type='radio'][name='" + nom + "'][value='" + typeChoix + "']").siblings().each(function() {
                if (this.type == 'text') {
                    valeur1 = this.value;
                }
            });
            $('#' + idButton + ' div.txt').html("VALEUR&nbsp;< <span>" + valeur1 + "</span>");
            break;
        case 'Int':
            var valeur1 = '';
            var valeur2 = '';
            var check_valeur1 = false;
            // Recherche des valeurs à afficher (correspondant aux input type=text qui sont frere du bouton radio mentionné par 'idButton')
            $('#' + idButton + " input[type='radio'][name='" + nom + "'][value='" + typeChoix + "']").siblings().each(function() {
                if (this.type == 'text') {
                    if (check_valeur1 == false) {
                        valeur1 = this.value;
                        check_valeur1 = true;
                    } else {
                        valeur2 = this.value;
                    }
                }
            });
            $('#' + idButton + ' div.txt').html("<span>" + valeur1 + "</span> <&nbsp;VALEUR&nbsp;< <span>" + valeur2 + "</span>");
            break;
        case 'Inf':
            var valeur1 = '';
            // Recherche des valeurs à afficher (correspondant aux input type=text qui sont frere du bouton radio mentionné par 'idButton')
            $('#' + idButton + " input[type='radio'][name='" + nom + "'][value='" + typeChoix + "']").siblings().each(function() {
                if (this.type == 'text') {
                    valeur1 = this.value;
                }
            });
            $('#' + idButton + ' div.txt').html("<span>" + valeur1 + "</span> <&nbsp;VALEUR");
            break;
        }
    }
}

// Fonction qui affiche ou cache la partie 'Filtre' en fonction du checkbox
function switch_exclusion_message(idButton) {
    setTimeout(function() {
        if ($('#' + idButton).is(':checked')) {
            document.getElementById(idButton).checked = false;
            reinitialise_popup_liste();
			$('#exclure').addClass('cacher');
        } else {
			document.getElementById(idButton).checked = true;
            $('#exclure').removeClass('cacher');
        }
    }, 10);
}

// Fonction qui redéfinie les paramètres initiaux de la popup
function reinitialise_popup_liste() {
    if (document.getElementById('afficheroptions').checked == true) {
        document.getElementById('afficheroptions').checked = false;
        $('#exclure').addClass('cacher');
    }
    if ($('#buttons1').hasClass('on')) {
        $('#buttons1').removeClass('on');
        $('#buttons1').addClass('off');
        $('#buttons1 input').each(function() {
            this.disabled = true;
            if (this.type == 'radio') {
                this.checked = false;
            }
        });
        $('#buttons1 div.txt').html("");
    }
    if ($('#buttons2').hasClass('on')) {
        $('#buttons2').removeClass('on');
        $('#buttons2').addClass('off');
        $('#buttons2 input').each(function() {
            this.disabled = true;
            if (this.type == 'radio') {
                this.checked = false;
            }
        });
        $('#buttons2 div.txt').html("");
    }
}

// Fonction d'appel d'une popup
function appelPopup(newUrl, typePopup) {
    attente();
    setTimeout(function() {
        url = newUrl;
        // Vérification du nombre de requêtes maximum autorisé
        var createLightBox = onLightBox(url, typePopup);
        if (createLightBox == true) {
            openLightBoxDiffere();
        } else {
            openLightBox();
        }
    }, 200);
    return(0);
}

function ouverturePopup(page) {
    // Vérification du nombre de requêtes maximum autorisé
    checkRequete = checkNbMaxRequetes(page);
    if (checkRequete == true) {
        // Suppression des anciennes données de la popup
        razUpdate();
        // Ouverture de la popup
        openLightBox();
    }
    return(0);
}

// Fonction qui valide les formulaires des pages index des listings et graphiques
function validationPopup(idForm) {
    setTimeout(function() {
        var nombre_requete = verifNombreRequetes();
        if (nombre_requete != 0) {
            var messagePeriode = $.trim($('#periodeDefinie').text());
            var pattern = /^Du/;
            var match = pattern.exec(messagePeriode);
            if (match == null) {
                $('#messageboxTexte').text(traduire('info.periode.none'));
                $('#messagebox').removeClass('cacher');
                addCloseMessageFunction();
            } else {
                attente();
				stopPing();
                submitForm(idForm);
            }
        }
    }, 10);
}

function addClosePopupFunction() {
   	$('#siteIpc').on("click", cachePopupBox);
}

function removeClosePopupFunction() {
    $('#siteIpc').off("click", cachePopupBox);
}

// Fonction qui cache les box
function cachePopupBox() {
    closeLightBox();
    closeLightBoxSmall();
}

function checkNbMaxRequetes(page) {
    var xhr = getXHR();
    var datas = "page=" + page;
    callPathAjax(xhr, 'ipc_check_max_requetes', datas, false);
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    xhr.send();
    // Récupération du nombre de requêtes maximum préconisées
    var nombre_max_requetes = $.trim(xhr.responseText);
    // Récupération du nombre de requêtes demandées
    var nombre_requete_en_cours = verifNombreRequetes();
    if (nombre_requete_en_cours < nombre_max_requetes) {
        return true;
    } else {
        var message = '';
        if (page == 'listing') {
            messageErreur = 'Attention, vous demandez trop de listing simultanément. En fonction du type de connection, des lenteurs ou déconnexions sont possibles.';
        } else if (page == 'graphique') {
            messageErreur = 'Attention, vous demandez trop de courbes simultanément. En fonction du type de connection, des lenteurs ou déconnexions sont possibles.';
        } else if (page == 'etat') {
            messageErreur = 'Attention, vous demandez trop de requêtes simultanément. En fonction du type de connection, des lenteurs ou déconnexions sont possibles.';
        }
        $('#messageboxTexte').text(messageErreur);
        $('#messagebox').removeClass('cacher');
        setTimeout(function(){
            addShadow('message');
            desactivateLinks();
        }, 2);
        // Restriction des requêtes
        //return false;
        // Pas de restriction
        return true;
    }
}


function selectionMessage(urlDest, page) {
    // Inhibe les liens de la popup
    $('#submitPopup a').each(function(){
        $(this).addClass('btn-disable');
    });
    //  Si un caractère est entré dans la case de sélection par mot clé avant la fin du timeout : Suppression de la recherche précédente && Initialisation d'une nouvelle recherche
    if (delayTimer) {
        window.clearTimeout(delayTimer);
    }
    var message = $('#codeModule').val();
    // Vérification : Le caractère du clavier doit être une lettre, un chiffre, un espace ou les caractères éèêçà. Les autres caractères sont supprimés du message
    pattern = /[^a-zA-Z0-9éèêçà'\s-]/g;
    var nouveauMessage  = message.replace(pattern, '');
    $('#codeModule').val(nouveauMessage);
    //  Si le message et le précédent message sont vides : réinitialisation du 'select' Sinon recherche du texte parmi les messages
    if (nouveauMessage == '') {
        //  Si le message précédent n'était pas vide : réinitialisation du 'select'
        if (messagePrecedent != '') {
            selection(page, 'genre', true);
        }
        // Redonne l'acces aux liens de la popup
        $('#submitPopup a').each(function(){
            $(this).removeClass('btn-disable');
        });
    } else {
        delayTimer = window.setTimeout(function() {
            getMessages(urlDest, nouveauMessage);
        }, 1000);
    }
    messagePrecedent = nouveauMessage;
}


//  Fonction qui écrit les messages correspondant à la recherche indiquée par mots clé
function getMessages(urlDest, chaine) {
    attente();
    var xhr = getXHR();
    xhr.onreadystatechange = function() {
        if ((xhr.readyState == 4) && (xhr.status == 200)) {
            var nouvelle_liste = JSON.parse(xhr.responseText);
            var selectHtml = '';
            $.each(nouvelle_liste, function(index, value) {
                selectHtml = selectHtml + "<option value=" + index + ">" + value + "</option>";
            });
            $('#messages').html(selectHtml);
            $('#messages').get(0).click();
            // Redonne l'acces aux liens de la popup
            $('#submitPopup a').each(function(){
                $(this).removeClass('btn-disable');
            });
            fin_attente();
            return(0);
        }
    }
    callPathAjax(xhr, 'ipc_get_messages', null, true);
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    var datas = "url=" + urlDest + "&chaine=" + chaine;
    xhr.send(datas);
}


// Function qui vérifie l'indication du nombre de requêtes demandées
function verifNombreRequetes() {
    return(document.getElementById('nombre_requetes').value);
}




//  *************************************** Modification d'une requête popup enregistrée.
function declanchementUpdateAjaxForm(etape, url, page, name, index, valueSubmit) {
    switch(etape) {
    case 1:
        attente();
        setTimeout(function() {declanchementUpdateAjaxForm(2, url, page, name, index, valueSubmit);}, 200);
        break;
    case 2:
        document.getElementById('choixSubmit').value = valueSubmit;
        updateAjaxForm(url, page, name, index);
        break;
    }
}


// Modification de la popup : Par simulation du click sur Nouvelle popup
// Execution des fonctions de recherche
// Ouverture de la popup
function updateAjaxForm(url, page, idForm, keyTabRequete) {
    if (page == 'graphique') {
        onLightBox(url, 'Sélection des requêtes graphiques');
    } else if (page == 'listing') {
        onLightBox(url, 'Sélection des requêtes de listing');
    } else if (page == 'etat') {
        onLightBox(url, 'Sélection des requêtes etat');
    }
    attente();
    var code = tabRequete[keyTabRequete]['code'];
    var localisation = tabRequete[keyTabRequete]['localisation'];
    var numrequete = tabRequete[keyTabRequete]['numrequete'];
    // Récupération de l'identifiant de la requête à modifier
    var infoIdRequete = idForm.split('_');
    var idRequete = infoIdRequete[1];
    setTimeout(function() {
        // Identifiant de la requête en cours de modification
        document.getElementById('modificationRequete').value = idRequete;
        // Désignation de la requête
        var message = tabRequete[keyTabRequete]['message'];
        // Séparation du message et des éventuels conditions sur les valeurs
        // Tableau dont les champs correspondent aux choix sur les valeurs
        var expValeurs = message.match(/\(Valeur.+?\)/g);
        if (expValeurs != null) {
            // Affiche le filtre
            switch_exclusion_message('afficheroptions');
            // Parcours des choix sur les valeurs
            for (var i = 0; i < expValeurs.length; i++) {
                // Récupération du numéro de la valeur (1 ou 2), du type de filtre(inf~, sup~, int~), de la valeur de référence
                var parametreMessage = /Valeur\s(\d+?)\s(.+?)\s(.+?)$/.exec(expValeurs[i]);
                var numValeur = $.trim(RegExp.$1);
                var choixComparaison = $.trim(RegExp.$2);
                var textValeur = $.trim(RegExp.$3);
                var valeurMin = null;
                var valeurMax = null;
                var idBouton = 'buttons'+numValeur;
                var numCode = 'codeVal'+numValeur;
                $('#'+idBouton).removeClass('cacher');
                switch (choixComparaison) {
                case 'supérieure':
                    var parametreValeur = /(\d+)\)$/.exec(textValeur);
                    valeurMin = RegExp.$1;
                    buttonSlides(idBouton, numCode, 'Inf');
                    document.getElementById(numCode+'Min').value = valeurMin;
                    break;
                case 'comprise':
                    var parametreValeur = /(\d+)\set\s(\d+)\)$/.exec(textValeur);
                    valeurMin = RegExp.$1;
                    valeurMax = RegExp.$2;
                    buttonSlides(idBouton, numCode, 'Int');
                    document.getElementById(numCode+'IntMin').value = valeurMin;
                    document.getElementById(numCode+'IntMax').value = valeurMax;
                    break;
                case 'inférieure':
                    var parametreValeur = /(\d+)\)$/.exec(textValeur);
                    valeurMax = RegExp.$1;
                    buttonSlides(idBouton, numCode, 'Sup');
                    document.getElementById(numCode+'Max').value = valeurMax;
                    break;
                }
            }
            var newMessage = /^(.+?)\(.+\)$/.exec(message);
            message = $.trim(RegExp.$1);
        }
        // Si l'attribut de localisation est un champs caché récupération de sa valeur sinon récupération du select selectionné
        // L'attribut est de type hidden lorque la recherche concerne l'ajout d'un compteur d'Etat
        if ($('#localisations').attr('type') != 'hidden') {
            // Sélection de la localisation
            for (var i = 0; i < document.getElementById('localisations').options.length; i++) {
                var tabLoc = document.getElementById('localisations').options[i].text.split('-');
                var numloc = null;
                if (tabLoc[1]) {
                    numloc = $.trim(tabLoc[0]);
                }
                if (numloc == localisation) {
                    document.getElementById('localisations').options[i].selected = true;
                    ajaxSetChoixLocalisation();
                }
            }
        }
        // Récupération des modules associès à la localisation
        if ((page == 'graphique')||(page == 'etat')) {
            selection('graphique', 'genre', false);
        } else if (page == 'listing') {
            selection('listing', 'genre', false);
        }
        setTimeout(function() {
            // Si un code message est définit on place le code et on recherche le texte associé
            if (code != null) {
                document.getElementById('codeModule').value = code;
                choixCodeMessage();
            } else {
                // Sinon récupération des informations de la recherche grâce au message
                var match=message.match(/:/);
                if (match != null) {
                    // Sélection des genres
                    var expGenre = /Genre :(.+?)$/.exec(message);
                    if (expGenre != null) {
                        var designationGenre = $.trim(RegExp.$1);
                        for (var i = 0; i < document.getElementById('genres').options.length; i++) {
                            if(document.getElementById('genres').options[i].text == designationGenre) {
                                document.getElementById('genres').options[i].selected = true;
                            }
                        }
                    }
                    // Sélection des modules
                    var expModule = null;
                    var designationModule = null;
                    expModule = /Module :(.+?) ayant.*$/.exec(message);
                    if (expModule != null) {
                        designationModule = $.trim(RegExp.$1);
                    } else {
                        expModule = /Module :(.+?)$/.exec(message);
                        if (expModule != null) {
                            designationModule = $.trim(RegExp.$1);
                        }
                    }
                    if (expModule != null) {
                        for (var i = 0; i < document.getElementById('modules').options.length; i++) {
                            if (document.getElementById('modules').options[i].text == designationModule) {
                                document.getElementById('modules').options[i].selected = true;
                            }
                        }
                    }
                    // Modification de la liste des messages en fonctions des genres/modules sélectionnés
                    if (expModule != null) {
                        if (expGenre != null) {
                            selection('listing', 'genre', true);
                        } else {
                            selection('listing', 'module', true);
                        }
                    } else if (expGenre != null) {
                        selection('listing', 'genre', true);
                    }
                }
            }
            openLightBox();
        }, 50);
    }, 100);
}
/* ************************************************************************************************* */







// Fonction avec temporisation entre les étapes
function declanchementDeleteAjaxForm(etape, page, name, valueSubmit) {
    switch(etape) {
    case 1:
        attente();
        setTimeout(function(){declanchementDeleteAjaxForm(2, page, name, valueSubmit);}, 200);
        break;
    case 2:
        document.getElementById('choixSubmit').value = valueSubmit;
        declanchementDeleteAjaxForm(3, page, name, valueSubmit);
        break;
    case 3:
        deleteAjaxForm(page, name);
        break;
    }
}




function ajaxGetSessionsInfos() {
	var xhr = getXHR();
	callPathAjax(xhr,'ipc_get_infos_session',null,false);
	xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
	try {
		xhr.send();
		var tabSession = $.parseJSON(xhr.responseText);
	} catch(e) {
		var tabSession = new Array();
		tabSession['userLabel'] = '';
		tabSession['pageTitle'] = '';
		tabSession['pageActive'] = ''; 
	}
	return(tabSession);
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

// - - - - - - - - - - - - - - - - - - - - - - - - - -  FONCTION UTILISEE DANS LA PAGE FirstLayout.html.twig - - - - - - - - - - - - - - - - - - - - - - - - - -

// Actions entreprise lors d'un clic dans la barre des menus
// Récupération du titre de la page courante
// Ajout de la classe pageactive ( mise en surbrillance) au lien de la page active
// Suppression de la classe pageactive au lien de la page courante
// Redéfinition de la variable de session pageActive : En asynchrone pour s'assurer de la modification de la variable de session avant de changer de page 
// L'execution de la page = action du lien a(href) => Va nécessiter la récupération de la nouvelle variable de session
function actionMenu(urlLien, idMenuActif) {
	var xhr = getXHR();
	// Récupération de l'ancienne page active
	var oldPageActive = $('#pageActive').val();
	// Récupération de la class à mettre dans l'ancien menu actif
	var oldClass = oldPageActive.toLowerCase();
	// Définition de la nouvelle page active
	var newPageActive = idMenuActif;
	$('#pageActive').val(newPageActive);
	// Définition de la class active
	var classActive = newPageActive.toLowerCase() + '-active';
	// Désactivation de la fonction onmouseout qui cause un problème de désactivation de la mise en évidence
	document.getElementById(newPageActive).onmouseout = function(){};
	// Mise en évidence de la nouvelle page active
	// Image
	$('#' + newPageActive + ' div:first-child').attr('class','img ' + classActive);
	// Div text
	$('#' + newPageActive + ' div:nth-child(2)').addClass('pageactive');
	// Suppression de la mise en évidence de l'ancienne page active
	if (oldPageActive != '') {
		$('#' + oldPageActive + ' div:first-child').attr('class','img ' + oldClass);
		$('#' + oldPageActive + ' div:nth-child(2)').removeClass('pageactive');
	}
	attente();
	desactivateLinks();
	// Définition de la nouvelle pageActive
	setTimeout(function() {
		callPathAjax(xhr,'ipc_define_page_active',null,false);
		xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
		var datas = "pageActive=" + newPageActive;
		xhr.send(datas);
		window.location.href = urlLien;
	}, 30);
}

// Fonction qui change la couleur du menu lors de son survol par la souris (changement si le menu survollé n'est pas le menu actif) 
function switchActive(idMenuActif, pageActive) {
	if (pageActive != idMenuActif) {
		classMenu = idMenuActif.toLowerCase() + '-active';
		$('#' + idMenuActif + ' div:first-child').attr('class', 'img ' + classMenu);
		$('#' + idMenuActif + ' div:nth-child(2)').addClass('pageactive');
	}
}

// Fonction qui change la couleur du menu lorsque la souris a terminée le survol (changement si le menu survollé n'est pas le menu actif)
function switchInactive(idMenuInactif, pageActive) {
	if(pageActive != idMenuInactif) {
		classMenu = idMenuInactif.toLowerCase();
		$('#' + idMenuInactif + ' div:first-child').attr('class', 'img ' + classMenu);
		$('#' + idMenuInactif + ' div:nth-child(2)').removeClass('pageactive');
	}
}
// - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - - -

// Actions effectuées lors du passage de la souris sur la banière des périodes
// Modification de la variable d'action lors du survol des boutons de l'interface
function addActions(action) {
	switch(action) {
	case 'periode':
		$('#boutonInfoPeriode').hover(function(){
			choixPeriode = 'information';
		}, function() {
			choixPeriode = 'modifier';
		});
		$('#boutonModificationPeriode').hover(function(){
			choixPeriode = 'valider';
		}, function() {
			choixPeriode = 'modifier';
		});
		break;
	}
}

function modificationPeriode(choixSelection) {
	setTimeout(function() {
		if (choixSelection == 'modifier') {
			attente();
			setTimeout(function() {
				addLowShadow();
				// Modification des valeurs de début et de fin de période
				// Récupération Ajax des dates de début(num=1) ou de fin(num=2) précédemment choisies
				xhr = getXHR();
				callPathAjax(xhr, 'ipc_get_date', null, false);
				xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
				var datas = "typeDate=all";
				xhr.send(datas);
				if (xhr.responseText != '') {
					// Si une période a déjà été choisie : Sélection de la même période
					var tabDate	= JSON.parse(xhr.responseText);
					var deb_day	= tabDate['debut']['jj']
					var deb_month = tabDate['debut']['mm'];
					var deb_year = tabDate['debut']['aaaa'];
					var date_debut = createTextDate(deb_day + '-' + deb_month + '-' + deb_year);
					$('#date_d').val(date_debut);
					// Sélection des minutes et des heures de début
					$('#heure_d option[value="' + tabDate['debut']['hh'] + '"]').prop('selected', true);
					$('#minute_d option[value="' + tabDate['debut']['ii'] + '"]').prop('selected', true);
					var fin_day = tabDate['fin']['jj']
					var fin_month = tabDate['fin']['mm'];
					var fin_year = tabDate['fin']['aaaa'];
					var date_fin = createTextDate(fin_day + '-' + fin_month + '-' + fin_year);
					$('#date_f').val(date_fin);
					// Sélection des minutes et des heures de fin
					$('#heure_f option[value="' + tabDate['fin']['hh'] + '"]').prop('selected', true);
					$('#minute_f option[value="' + tabDate['fin']['ii'] + '"]').prop('selected', true);
				}
				// Affichage de la banière 'sélection de période'
				$('#definitionPeriode').removeClass('cacher');
				// Cache du texte de la période
				$('#periodeDefinie').addClass('cacher');
				// Création du bouton de Validation de période
				$('#choixSelectionPeriode').html('<div class="bouton redNb"><div id="boutonInfoPeriode" class="bgbouton cacher"><a href="#" class="right" onClick="infoPeriode();return false;" >Attention</a></div></div><div class="bouton green"><div id="boutonModificationPeriode" class="bgbouton"><a href="#" class="right" onClick="defineDates();modificationPeriode(\'valider\');return false;" >valider</a></div></div>');
				// Modification de la variable globale afin de permettre l'ouverture de la partie 'Modification de période' par clic sur la banière
				choixPeriode = 'valider';
				fin_attente();
			}, 300);
		} else {
			attente();
			setTimeout(function() {
				$urlSaveDate = $('#pageActive').attr('data-urlSaveDate');
				$.ajax({
                        url: $urlSaveDate,
                        timeout: 10000
                })
				.done(function($message, $status) {
					$('#definitionPeriode').addClass('cacher');
					$('#periodeDefinie').removeClass('cacher');
					$('#choixSelectionPeriode').html('<div class="bouton redNb"><div id="boutonInfoPeriode" class="bgbouton cacher"><a href="#" class="right" onClick="infoPeriode();return false;" >Attention</a></div></div><div class="bouton blueC"><div id="boutonModificationPeriode" class="bgbouton"><a href="#" class="right" onClick="modificationPeriode(\'modifier\');return false;" >modifier</a></div></div>');
        			// Si la période choisi ne correpond pas aux périodes de fonctionnement des localisations affichage du bouton d'info
        			if (parseInt($checkDeDate) == 0) {
                    	$('#boutonInfoPeriode').removeClass('cacher');
        			}
					// Ajout des fonctions des événements sur la banières des périodes
					addActions('periode');
					// Modification de la variable globale afin d'indiquer l'ouverture de la partie 'Modification de période'
                	choixPeriode = 'modifier';
                	removeLowShadow();
                	fin_attente();
				})
				.fail(function($xhr, $status, $error) {
					alert('Erreur : ' + $error);
					fin_attente();
				});
			}, 100);
		}
	}, 50);
}

// Fonction qui définie la taille de l'écran afin que la div d'identifiant siteIpc couvre la totalité de l'écran
function setSiteIpcDimension() {
	var hauteur = $(window).height() + 'px';
	var largeur = $(window).width() + 'px';
	$('#siteIpc').width(largeur);
	$('#siteIpc').height(hauteur);
}

function setSiteIpcSmallDimension() {
	var largeur = $(window).width() + 'px';
	$('#siteIpc').width(largeur);
	$('#siteIpc').height('0px');
}

function addCloseCalendarFunction() {
	setTimeout(function() {
		$('#siteIpc').on("click",cacheCalendarBox);
	}, 10);
}

function removeCloseCalendarFunction() {
	$('#siteIpc').off("click",cacheCalendarBox);
}

function addShadow(type) {
	if (type == 'popup') {
		addClosePopupFunction();
	}
	if (type == 'message') {
		addCloseMessageFunction();
	}
	$('#pagePrincipalHeader').addClass('shadowing');
	$('#pagePrincipaleMenu').addClass('shadowing');
	$('#pagePrincipaleBody').addClass('shadowing');
}

// Ajoute le shadow au site
function addLowShadow() {
	$('page').addClass('shadowing');
	desactivateLowLinks();
}

// Retire le shadow du site
function removeLowShadow() {
	$('page').removeClass('shadowing');
	activateLowLinks();
}

function removeShadow(typeShadow) {
	if (typeShadow == 'calendar') {
		// Suppression de l'evenement onClick
		removeCloseCalendarFunction();
	}
	if (typeShadow == 'popup') {
		removeClosePopupFunction();
	}
	if (typeShadow == 'message') {
		removeCloseMessageFunction();
	}
	$('#pagePrincipalHeader').removeClass('shadowing');
	$('#pagePrincipaleMenu').removeClass('shadowing');
	$('#pagePrincipaleBody').removeClass('shadowing');
}

// Désactivation des liens
function desactivateLowLinks() {
	// Lors de l'ouverture de la box, tous les liens sont désactivés
	$('header a').each(function() {
		$(this).addClass('btn-disable');
	});
	$('page a').each(function() {
		$(this).addClass('btn-disable');
	});
}

// Activation des liens
function activateLowLinks() {
	// Lors de l'ouverture de la box, tous les liens sont désactivés
	$('header a').each(function() {
		$(this).removeClass('btn-disable');
	});
	$('page a').each(function() {
		$(this).removeClass('btn-disable');
	});
}

function desactivateLinks() {
	// Lors de l'ouverture de la box, tous les liens sont désactivés
	$('#pagePrincipalHeader a').each(function() {
		$(this).addClass('btn-disable');
	});
	$('#pagePrincipaleBody a').each(function() {
		$(this).addClass('btn-disable');
	});
}

function activateLinks() {
	// Lors de l'ouverture de la box, tous les liens sont désactivés
	$('#pagePrincipalHeader a').each(function(){
		$(this).removeClass('btn-disable');
	});
	$('#pagePrincipaleBody a').each(function(){
		$(this).removeClass('btn-disable');
	});
}

//  Fonction executée après la validation d'un popup messagebox
function validation_messagebox() {
	$('#messagebox').addClass('cacher');
	removeShadow('message');
	activateLinks();
}

function annulation_messagebox() {
    validation_messagebox();
}

//  Fonction executée après la validation d'un popup messagebox
function continuation_messagebox() {
    // modifie la valeur de la variable de SESSION
    setValidationMessageBox('continue');
    // retire la popup messageBox
    validation_messagebox();
    // simule le clic sur Rechercher
    $('#continue_action').click();
}

// Fonction qui modifie le paramètre validationMessageBox par appel ajax
function setValidationMessageBox($valeur) {
    xhr = getXHR();
    callPathAjax(xhr, 'ipc_set_new_session_vars', null, false);
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    var datas = "variable=validation_message_box&valeur=" + $valeur;
    xhr.send(datas);
}



function closeMessageBox() {
	if ($('#messagebox').hasClass('cacher') == false) {
		$('#messageboxTexte').text('');
		$('#messagebox').addClass('cacher');
		activateLinks();
		removeShadow('message');
	}
}

// Fonction qui cache les box
function cacheMessageBox() {
	closeMessageBox();
}

function closeCalendar() {
	if (($('#ds_conclass').hasClass('cacher') == false) || ($('#ds_conclass2').hasClass('cacher') == false)) {
		if ($('#ds_conclass').hasClass('cacher') == false) {
			$('#ds_conclass').addClass('cacher');
		} else {
			$('#ds_conclass2').addClass('cacher');
		}
		activateLinks();
		removeShadow('calendar');
	}
}

function cacheCalendarBox() {
	closeCalendar();
}

function addCloseMessageFunction() {
	$('#siteIpc').on("click",cacheMessageBox);
}

function removeCloseMessageFunction() {
	$('#siteIpc').off("click",cacheMessageBox);
}

function dateDuJour() {
	var months = ['janvier','février','mars','avril','mai','juin','juillet','août','septembre','octobre','novembre','décembre'];
	var dateJour = new Date();
	var strDateDebut = dateJour.getDate() + ' ' + months[dateJour.getMonth()] + ' ' + dateJour.getFullYear();	
	return(strDateDebut);
}

function dateHeureActuelle() {
	var months = ['01','02','03','04','05','06','07','08','09','10','11','12'];
	var dateJour = new Date();
	var strDateDebut = dateJour.getDate() + '/' + months[dateJour.getMonth()] + '/' + dateJour.getFullYear() + ' ' + fillNumber(dateJour.getHours()) + ':' + fillNumber(dateJour.getMinutes()) + ':' + fillNumber(dateJour.getSeconds());
	return(strDateDebut);
}

// Affichage de l'image du loader pour indiquer que la page est en cours de chargement
function attente() {
	$(':visible').addClass('cursor_wait');
}

function fin_attente() {
	$(':visible').removeClass('cursor_wait');
}

// Affichage d'une variable de type Tableau
function dump(arr,level) {
	var dumped_text = "";
	if (!level) level = 0;
	var level_padding = "";
	for (var j = 0; j < level + 1; j++) level_padding += "    ";
	if (typeof(arr) == 'object') {
		for (var item in arr) {
			var value = arr[item];
			if (typeof(value) == 'object') {
				dumped_text += level_padding + "'" + item + "' ...\n";
				dumped_text += dump(value,level+1);
			} else {
				dumped_text += level_padding + "'" + item + "' => \"" + value + "\"\n";
			}
		}
	} else {
		dumped_text = "===>" + arr + "<===(" + typeof(arr) + ")";
	}
	return dumped_text;
}

function impression() {
	setTimeout(function() {
		print();
	}, 1000);
}

function impressionTitre() {
    setTimeout(function() {
        setTitreTitle();
        print();
        setInitialTitle();
    }, 1000);
}

function majMaxExecTime() {
	xhr = getXHR();
	xhr.onreadystatechange = function() {
		if ((xhr.readyState == 4) && (xhr.status == 200)) {
			var nouvelle_liste = xhr.responseText;
			document.getElementById('maxExecutionTime').value = nouvelle_liste;
			return(0);
		}
	}
	callPathAjax(xhr,'ipc_confChangeMaxExecTime',null,true);
	xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
	var maxExecTime = document.getElementById('maxExecutionTime').value;
	var datas = "maxExecTime=" + maxExecTime;
	xhr.send(datas);
}


function submitForm(formName) {
	document.forms[formName].submit();
}

function callPathAjax(xhr, fonction, args, asynchrone) {
	var pathUrl	= window.location.href;
	var mode = pathUrl.substr(0,5);	//	http  / https
	var pattern_ip = /^.+?\/\/(.+?)\//;
	var match = pattern_ip.exec(pathUrl);
	var adresse_ip = null;
	var deb_url = null;
	if (match != null) {
		adresse_ip = match[1];
	} else {
		adresse_ip = 'none';
	}
	if (mode == 'https') {
		deb_url = "https://"+adresse_ip;
	} else {
		deb_url = "http://"+adresse_ip;
	}
	var version	= null;
	var myRegExp = /^.+?boiler-box.fr.*$/;
	if (myRegExp.test(pathUrl)) {
		version = 'BoilerBox';
	} else {
		version = 'HTTP';
	}
	switch (fonction) {
    case 'ipc_set_new_session_vars':
        xhr.open("POST", deb_url + $('#lien_url_ajax').attr('data-urlSetNewSessionVars'), false);
        break;
	case 'ipc_trie_donnees':
		xhr.open("POST", deb_url + $('#lien_url_ajax').attr('data-urlTrieDonnees'), false);
		break;
	case 'ipc_get_messages':
		xhr.open("POST", deb_url + $('#lien_url_ajax').attr('data-urlGetMessages'), true);
		break;
	case 'ipc_get_infos_periode':
		xhr.open("POST", deb_url + $('#lien_url_ajax').attr('data-urlGetInfosPeriode'), false);
		break;
	case 'ipc_conf_majPeriodeAnalyse':
		xhr.open("POST", deb_url + $('#lien_url_ajax').attr('data-urlConfMajPeriodeAnalyse'), false);
		break;
	case 'ipc_check_max_requetes':
		xhr.open("GET", deb_url + $('#lien_url_ajax').attr('data-urlCheckMaxRequetes') + '?' + args, false);
		break;
	case 'ipc_get_infos_session':
		xhr.open("POST", deb_url + $('#lien_url_ajax').attr('data-urlGetInfosSession'), false);
		break;
	case 'ipc_get_date':
		xhr.open("POST", deb_url + $('#lien_url_ajax').attr('data-urlGetDate'),  false);
		break;
	case 'ipc_define_dates':
		xhr.open("POST", deb_url + $('#lien_url_ajax').attr('data-urlDefineDates'), false);
		break;
	case 'ipc_define_page_active':
		xhr.open("POST", deb_url + $('#lien_url_ajax').attr('data-urlDefinePageActive'), false);
		break;
	case 'ipc_confChangeMaxExecTime':
		xhr.open("POST", deb_url + $('#lien_url_ajax').attr('data-urlConfChangeMaxExecTime'), true);
		break;
	case 'ipc_configSelect':
		switch(args) {
		case 'genre0':
			if (asynchrone == false) {
				xhr.open("POST", deb_url + $('#lien_url_ajax').attr('data-urlConfigSelect') + "/genre0", false);
			} else {
				xhr.open("POST", deb_url + $('#lien_url_ajax').attr('data-urlConfigSelect') + "/genre0", true);
			}
			break;
		case 'genre1':
			if (asynchrone == false) {
				xhr.open("POST", deb_url + $('#lien_url_ajax').attr('data-urlConfigSelect') + "/genre1", false);
			} else {
				xhr.open("POST", deb_url + $('#lien_url_ajax').attr('data-urlConfigSelect') + "/genre1", true);
			}
			break;
		case 'module0':
			if (asynchrone == false) {
				xhr.open("POST", deb_url + $('#lien_url_ajax').attr('data-urlConfigSelect') + "/module0", false);
			} else {
				xhr.open("POST", deb_url + $('#lien_url_ajax').attr('data-urlConfigSelect') + "/module0", true);
			}
			break;
		case 'module1':
			if (asynchrone == false) {
				xhr.open("POST", deb_url + $('#lien_url_ajax').attr('data-urlConfigSelect') + "/module1", false);
			} else {
				xhr.open("POST", deb_url + $('#lien_url_ajax').attr('data-urlConfigSelect') + "/module1", true);
			}
			break;
		}
		break;
	case 'ipc_accueilGraphique':
		xhr.open("GET", deb_url + $('#lien_url_ajax').attr('data-urlAccueilGraphique') + '?' + args, false);
		break;
	case 'ipc_accueilListing':
		xhr.open("GET", deb_url + $('#lien_url_ajax').attr('data-urlAccueilListing') + '?' + args, false);
		break;
	case 'ipc_accueilEtat':
		xhr.open("GET", deb_url + "/web/app.php/etat/index?" + '?' +  args, false);
		break;
	case 'ipc_etat':
		if (asynchrone == false) {
			xhr.open("POST", deb_url + $('#lien_url_ajax').attr('data-urlEtat'), false);
		} else {
			xhr.open("POST", deb_url + $('#lien_url_ajax').attr('data-urlEtat'), true);
		}
		break;
	case 'ipc_listing':
		if (asynchrone == false) {
			xhr.open("GET", deb_url + $('#lien_url_ajax').attr('data-urlListing'), false);
		} else {
			xhr.open("GET", deb_url + $('#lien_url_ajax').attr('data-urlListing'), true);
		}
		break;
	case 'ipc_graphiques':
		if (asynchrone == false) {
			xhr.open("GET", deb_url + $('#lien_url_ajax').attr('data-urlGraphiques'), false);
		} else {
			xhr.open("GET", deb_url + $('#lien_url_ajax').attr('data-urlGraphiques'), true);
		}
		break;
	case 'ipc_modbus_cloture_ftp':
		xhr.open("POST", deb_url + $('#lien_url_ajax').attr('data-urlModbusClotureFtp'), false);
		break;
	case 'ipc_read_modbus':
		xhr.open("POST", deb_url + $('#lien_url_ajax').attr('data-urlReadModbus'), false);
		break;
	case 'ipc_set_script':
		xhr.open("GET", deb_url + $('#lien_url_ajax').attr('data-urlSetScript') + '?' + args, asynchrone);
		break;
	}
}

// Fonction AJAX appelée lors de la modification de la période de recherche
function defineDates() {
	attente();
	setTimeout(function() {
		var date_d = inverseDate(document.getElementById('date_d').value);
		var date_f = inverseDate(document.getElementById('date_f').value);
		var heure_d = document.getElementById('heure_d').value;
		var heure_f = document.getElementById('heure_f').value;
		var minute_d = document.getElementById('minute_d').value;
		var minute_f = document.getElementById('minute_f').value;
		var datedebut = date_d+" "+heure_d+":"+minute_d+":00";
		var datefin = date_f+" "+heure_f+":"+minute_f+":00";
		var xhr = getXHR();
		// On envoye les données de la Période pour créer les variables globale de date
		callPathAjax(xhr, 'ipc_define_dates', null, false);
		xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
		var datas = "datedebut="+datedebut+"&datefin="+datefin;
		xhr.send(datas);
		var nouvelle_liste = $.trim(xhr.responseText);
		// Le message de la période définie remplace le message précédent
		// Si le message ne débute pas par 'check:\d' c'est que la période est incorrecte
		// $checkDeDate indique si la période est inclue dans toutes les périodes d'analyses des localisations du site courant ( = 1 ) ou pas ( = 0 )
		var pattern	= /^check:/
		var match = pattern.exec(nouvelle_liste);
		// Si la période commence par check : Récupération de l'information 'Période inclue dans les périodes d'analyse' + Récupération du message sans l'information [check:]
		if (match != null) {
			$checkDeDate = nouvelle_liste.charAt(6);
			nouvelle_liste = nouvelle_liste.substring(7);
		}
		// Si la période n'est pas inclue dans toutes les périodes d'analyses des localisations du site courant :  affichage du bouton d'information
		// Si le message ne débute pas par 'Du' c'est que la période est incorrecte
		var pattern	= /^Du/;
		var match = pattern.exec(nouvelle_liste);
		if (match != null) {
			$('div.entetePeriode').text(nouvelle_liste);	
		} else {
			$('#messageboxTexte').text(nouvelle_liste);
			$('#messagebox').removeClass('cacher');
			addShadow('message');
			desactivateLinks();
		}
		// Si la période choisi ne correpond pas aux périodes de fonctionnement des localisations affichage du bouton d'info
		if (parseInt($checkDeDate) == 0) {
			if ($('#boutonInfoPeriode').hasClass('cacher')) {
				setTimeout(function() {
					$('#boutonInfoPeriode').removeClass('cacher');
				}, 200);
			}
		}
		fin_attente();
		return;
	}, 10);
}

function inverseDate(dateString) {
	// La date est passée en parametre sous le format : JJ Mois Annee
	var months = ['janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];
	pattern = /^(\d+)\s(.+?)\s(\d{4})$/;
	var match = pattern.exec(dateString);
	var mois = null;
	var jour = null;
	var annee = null;
	if (match != null) {
		var jour = match[1];
		var mois = match[2];
		var annee = match[3];
		for (key in months) {
			if (months[key] == mois) {
				mois = parseInt(key) + 1;
				break;
			}
		}
	}
	var dateInversee = fillNumber(jour) + '-' + fillNumber(mois) + '-' + annee;
	return(dateInversee);
}

// Fonction qui recoit une date au format jj-mm-aaaa et retourne la date en texte
function createTextDate(dateString) {
	var months = ['janvier', 'février', 'mars', 'avril', 'mai', 'juin', 'juillet', 'août', 'septembre', 'octobre', 'novembre', 'décembre'];
	var days = ['dimanche', 'lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi'];
	var jour = null;
	var mois = null;
	var annee = null;
	var pattern = /^(\d{2})-(\d{2})-(\d{4})$/;
	var match = pattern.exec(dateString);
	var date;
	var textDate;
	if (match != null) {
		jour = days[parseInt(match[1])];
		mois = months[parseInt(match[2])];
		annee = match[3];
		date = new Date(parseInt(match[3]), parseInt(match[2]) - 1, parseInt(match[1]), 0, 0, 0, 0);
		textDate = fillNumber(date.getDate()) + ' ' + months[date.getMonth()] + ' ' + annee; 
	}
	return(textDate);
}

function fillNumber(num) {
	if (num.toString().length == 1) {
		return("0" + num);
	}
	return(num);
}

function newTitle(page) {
	var months = ['Jan','Fév','Mar','Avr','Mai','Jun','Jul','Aou','Sep','Oct','Nov','Déc'];
	var newDate = new Date();
	var date = fillNumber(newDate.getDate()) + '-' + months[newDate.getMonth()] + '-'+newDate.getFullYear();
	var heure = fillNumber(newDate.getHours()) + ':' + fillNumber(newDate.getMinutes()) + ':'+fillNumber(newDate.getSeconds());
	var newTitle =  page + ' ' + date + ' ' + heure;
	document.title = newTitle;
	document.title = '';
}

function setTitreTitle() {
	$('#enTeteInitial').addClass('cacher');
	$('#enTeteTitre').removeClass('cacher');
	var d = new Date();
	var month = d.getMonth() + 1;
	var day = d.getDate();
	var output = addZero(day) + '/' + addZero(month) + '/' + d.getFullYear() + ' ' + addZero(d.getHours()) + ':' + addZero(d.getMinutes());
	/*
	var output = (('' + day).length < 2 ? '0' : '') + day + '/' +
	(('' + month).length < 2 ? '0' : '') + month + '/' +
	d.getFullYear() + ' ' + 
	d.getHours() + ':' + 
	d.getMinutes();
	*/
	$('#enTeteTitre').append("<br /><span style='font-size:16px;'>" + output + '</span>');
	return 0;
}

function addZero(numTime) {
    if (numTime < 10) {
        numTime = "0" + numTime;
    }
    return numTime;
}

function setInitialTitle()
{
	$('#enTeteTitre').addClass('cacher');
	$('#enTeteInitial').removeClass('cacher');
	return 0;
}

function infoPeriode() {
	var xhr	= getXHR();
	callPathAjax(xhr,'ipc_get_infos_periode',null,false);
	xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
	xhr.send();
	var tabInfosPeriode	= JSON.parse(xhr.responseText);
	var html = '<table><tr><td colspan="3" class="centrer">Périodes d\'analyse</td></tr><tr><td class="centrer">Localisation</td><td class="centrer">Date de début</td><td class="centrer">Date de fin</td></tr>';
	$.each(tabInfosPeriode, function(key, value) {
		html += "<tr><td>" + value['designation'] + "("+value['numero'] + ")</td><td>" + value['dateDeb'] + "</td>";
		if(value['dateFin'] != null) {
			html += "<td>" + value['dateFin'] + "</td>";
		} else {
			html += "<td>" + dateHeureActuelle() + "</td>";
		}
		html += "</tr>";
	});
	html += "</table>";
	$('#messageboxTexte').html(html);
	$('#messagebox').removeClass('cacher');
	addShadow('message');
	desactivateLinks();
}

// Fonction spécifique CLOUD 
// Fonction qui rafraichie les données : Fonction ajax pour cloture des fichiers sur les automates par requête modbus + 
// téléchargement des fichiers par Ftp +
// Recherche des données correspondantes aux requêtes utilisateur
/*VERSION IPC : 
function refresh(formulaireName) {
	attente();
	choixPeriode = 'refresh';
	setTimeout(function(){
		var xhr = getXHR();
		// On envoye les données de la Période pour créer les variables globale de date
		callPathAjax(xhr,'ipc_modbus_cloture_ftp',null,false);
		xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
		xhr.send();
		validationPopup(formulaireName);
		fin_attente();
		window.location.replace(); 
	}, 100);
}
VERSION CLOUD*/
function refresh(formulaireName, pageAccueil) {
    attente();
	$controller_urlClotureModbus = $('#lien_url_ajax').attr('data-urlHttpModbusClotureFtp');
	$.ajax({
		url: $controller_urlClotureModbus,
		method: 'GET'
	}).done(function($urlClotureModbus){
        document.location.href = pageAccueil;
		window.open($urlClotureModbus);
		fin_attente();
	}).error(function(err) {
		alert('erreur. Merci de voir le message de la console');
		fin_attente();
	});
	fin_attente();
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
