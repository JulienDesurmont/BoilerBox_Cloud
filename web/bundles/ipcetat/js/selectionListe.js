var xhr = null;
var delayTimer;

// Sélection d'une localisation, d'un genre ou d'un module dans la page d'accueil de l'etat : Analyse de marche chaudière
function etatSelection(num, type, supp) {
    var idMessage = null;
    var idGenre	= null;
    var idModule = null;
    var idLocalisation = null;
    var idCodeModule = null;
	// Module 1 : Chaudière en marche
    if (num == 1) {
		idGenre = 'genres1';
		idModule = 'modules1';
		idLocalisation = 'localisations1';
		idCodeModule = 'codeModule1';
		idMessage = 'messages1';
    }
	// Module 2 : Présence flamme
    if (num == 2) {
        idGenre = 'genres2';
        idModule = 'modules2';
        idLocalisation = 'localisations1';
        idCodeModule = 'codeModule2';
        idMessage = 'messages2';
    }
	// Module 2 bis : Présence flamme
    if (num == 3) {
        idGenre = 'genres3';
        idModule = 'modules3';
        idLocalisation = 'localisations1';
        idCodeModule = 'codeModule3';
        idMessage = 'messages3';
    }
    if(num == 4) {
		// Si la localisation est modifiée
		idLocalisation = 'localisations1';
    }
    attente();
    setTimeout(function() {
		if (num == 4) {
	    	var genreSelection = document.getElementById('genres1').value;
	    	var moduleSelection	= document.getElementById('modules1').value;
	    	var localisationSelection = document.getElementById('localisations1').value;
		$('#localisations option[value="' + localisationSelection + '"]').prop('selected', true);
		ajaxSetChoixLocalisation();
		selection('graphique', 'genre', false);
	    	//$('#selectLocalisation').html("<h3><input type='hidden' id='localisations' value='" + $("#localisations1 option:selected").val() + "' />" + $("#localisations1 option:selected").text() + "</h3>");
		} else {
	    	// Données à envoyer au serveur
	    	var genreSelection = document.getElementById(idGenre).value;
            var moduleSelection	= document.getElementById(idModule).value;
            var localisationSelection = document.getElementById(idLocalisation).value;
		}
    	if (supp == true) {
		    if (num == 4) {
				document.getElementById('codeModule1').value = "";
				document.getElementById('codeModule2').value = "";
				document.getElementById('codeModule3').value = "";
		    } else {
	        	document.getElementById(idCodeModule).value	= "";
		    }
	    }
	    if (type == 'genre') {
	        xhr = getXHR();
		    callPathAjax(xhr, 'ipc_configSelect', 'genre0', false);
		    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");  
		    var datas = "genres=" + genreSelection + "&modules=" + moduleSelection + "&localisations=" + localisationSelection;
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
		    if (num == 4) {
				document.getElementById('modules1').innerHTML = selectmodule;
				document.getElementById('modules2').innerHTML = selectmodule;
				document.getElementById('modules3').innerHTML = selectmodule;
		    }else{
		    	document.getElementById(idModule).innerHTML = selectmodule;
		    }
		    // Sélection des valeurs précédemment selectionnées
	        // Parcours de la liste déroulante
	        // Lorsqu'on trouve la valeur précédemment selectionnée on la resélectionne
		    if (num != 4) {
    	    	for (var selectionUser=0; selectionUser<moduleSelection.length; selectionUser++) {
       		     	for (var numOption=0; numOption<document.getElementById(idModule).options.length; numOption++) {
        	    	    if (document.getElementById(idModule).options[numOption].value == moduleSelection) {
        	    	        document.getElementById(idModule).options[numOption].selected 	= true;
        	    	    }
        	    	}
        		}
	    	}
        	// Liste des messages
        	// Sélection par défaut de la premiere valeur de la liste si elle existe
	    	if (num == 4) {
				document.getElementById('messages1').innerHTML = selectmessage;
				document.getElementById('messages2').innerHTML = selectmessage;
				document.getElementById('messages3').innerHTML = selectmessage;
	    	} else {
				document.getElementById(idMessage).innerHTML = selectmessage;
	    	}
            if (selectmessage.length != 0) {
				if (num == 4) {
		    		document.getElementById('messages1').options[0].selected = true;
		    		document.getElementById('messages2').options[0].selected = true;
		    		document.getElementById('messages3').options[0].selected = true;
				} else {
                    document.getElementById(idMessage).options[0].selected = true;
				}
            }
		}
        if (type == 'module') {
            var modulevalue = document.getElementById(idModule).value;
            xhr	= getXHR();
	    	callPathAjax(xhr, 'ipc_configSelect', 'module0', false);
            xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
            var datas = "genres=" + genreSelection + "&modules=" + moduleSelection + "&localisations=" + localisationSelection;
            xhr.send(datas); 
            nouvelle_liste = xhr.responseText;
            document.getElementById(idMessage).innerHTML = nouvelle_liste;
            if (nouvelle_liste.length != 0) {
				document.getElementById(idMessage).value = document.getElementById(idMessage).options[0].value;
            }
        }
        fin_attente();
        return;
    }, 50);
}


function etatSelectionMessage(num, urlDest) {
    var idCodeModule = null;
    var idMessage = null;
    if (num == 1) {
        idCodeModule = 'codeModule1';
        idMessage = 'messages1';
    }
    if (num == 2) {
        idCodeModule = 'codeModule2';
        idMessage = 'messages2';
    }
    if (num == 3) {
        idCodeModule = 'codeModule3';
        idMessage = 'messages3';
    }
    // Si un caractère est entré dans la case de sélection par mot clé avant la fin du timeout : Suppression de la recherche précédente && Initialisation d'une nouvelle recherche
    if (delayTimer) {
        window.clearTimeout(delayTimer);
    }
    var message = $('#' + idCodeModule).val();
    // Vérification : Le caractère du clavier doit être une lettre, un chiffre, un espace ou les caractères éèêçà. Les autres caractères sont supprimés du message
    pattern = /[^a-zA-Z0-9éèêçà'\s-]/g;
    var nouveauMessage = message.replace(pattern, '');
    // Si le message est vide : réinitialisation du 'select' Sinon recherche du texte parmi les messages
    if (nouveauMessage == '') {
        etatSelection(num, 'genre', true);
    } else {
        delayTimer = window.setTimeout(function() {
            etatGetMessages(idMessage, urlDest, nouveauMessage);
        }, 1000);
    }
}

// Fonction qui écrit les messages correspondant à la recherche indiquée par mots clé
function etatGetMessages(idMessage, urlDest, chaine) {
    attente();
    var xhr = getXHR();
    xhr.onreadystatechange = function() {
        if ((xhr.readyState == 4) && (xhr.status == 200)) {
            var nouvelle_liste = JSON.parse(xhr.responseText);
            var selectHtml = '';
            $.each(nouvelle_liste, function(index, value) {
                selectHtml = selectHtml + "<option value=" + index + ">" + value + "</option>";
            });
            $('#' + idMessage).html(selectHtml);
            $('#' + idMessage).get(0).click();
            fin_attente();
     		return(0);
        }
    }
    callPathAjax(xhr, 'ipc_get_messages', null, true);
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    var datas = "url=" + urlDest + "&chaine=" + chaine;
    xhr.send(datas);
}

//  - - - - - - - - - - - - - - - --- - - - - - -- - - Fonctions du SUPPRESSION DES REQUETES- - - - - - - - - - - - - - - --- - - - - - -- - -

// Fonction avec temporisation entre les étapes
function etatDeclanchementDeleteAjaxForm(etape, name, valueSubmit, typePopup) {
    switch (etape) {
        case 1:
            // Etape 1 : Déclanchement du curseur d'attente
            attente();
            setTimeout(function() {etatDeclanchementDeleteAjaxForm(2, name, valueSubmit, typePopup);}, 200);
            break;
        case 2:
            // Etape 2 : Modification de l'indicateur du choix de l'action à effectuer
            document.getElementById('choixSubmit').value = valueSubmit;
            etatDeclanchementDeleteAjaxForm(3, name, valueSubmit, typePopup);
            break;
        case 3:
            // Etape 3 : Suppression du message
            etatDeleteAjaxForm(name, typePopup);
            break;
    }
}
function etatDeleteAjaxForm(idForm, typePopup) {
    attente();
    setTimeout(function() {
        var datas = "AJAX=ajax&choixSubmit=suppressionRequete&suppression_requete=" + idForm + "&typePopup=" + typePopup;
        var $url = $('#listeUrl').attr('data-url-etat');
        $.ajax({
            url: $url,
            method: 'GET',
            data: datas,
            timeout: 10000
        })
        .done(function($message, $status) {
            var $nouvelleListe = JSON.parse($message);
            // Modification de la variable globale
            $tabRequete = $nouvelleListe;
            var $texteRequeteHtml = "<table>";
            $texteRequeteHtml = $texteRequeteHtml + "<tr>";
            switch(typePopup) {
                case 'combustibleB1':
                    $texteRequeteHtml = $texteRequeteHtml + "<td colspan='3' class='texte'>Ajouter un combustible du brûleur 1</td>";
                    break;
                case 'combustibleB2':
                    $texteRequeteHtml = $texteRequeteHtml + "<td colspan='3' class='texte'>Ajouter un combustible du brûleur 2</td>";
                    break;
                case 'compteur':
                    $texteRequeteHtml = $texteRequeteHtml + "<td colspan='3' class='texte'>Ajouter un compteur</td>";
                    break;
                case 'test':
                    $texteRequeteHtml = $texteRequeteHtml + "<td colspan='3' class='texte'>Ajouter un test</td>";
                    break;
                case 'forcage':
                    $texteRequeteHtml = $texteRequeteHtml + "<td colspan='3' class='texte'>Ajouter un forçage</td>";
                    break;
            }
            $texteRequeteHtml = $texteRequeteHtml + "<td class='actions'><a class='bouton' href='{{ path('ipc_etat') }}' target='_blank' onClick=\"onLightBox('ipc_etat', 'Sélection des requêtes etat');openLightBox();return false;\">";
            $texteRequeteHtml = $texteRequeteHtml + "<div class='bouton ajouter'></div>";
            $texteRequeteHtml = $texteRequeteHtml + "<div class='boutonname'>AJOUTER</div></a></td>";
            $texteRequeteHtml = $texteRequeteHtml + "</tr>";
            var numListe = 0;
            for (liste in $nouvelleListe) {
                $texteRequeteHtml = $texteRequeteHtml + "<tr><td class='localisation'><div class='txtlocalisation'>" + $nouvelleListe[liste]['localisation'] + "</div></td>";
                if ($nouvelleListe[liste]['code'] != null) {
                    $texteRequeteHtml = $texteRequeteHtml + "<td class='code'>" + $nouvelleListe[liste]['code'] + "</td>";
                } else {
                    $texteRequeteHtml = $texteRequeteHtml + "<td class='code'>&nbsp;</td>";
                }
                $texteRequeteHtml = $texteRequeteHtml + "<td class='designation'>" + $nouvelleListe[liste]['message'] + "</td>";
                $texteRequeteHtml = $texteRequeteHtml + "<td class='actions'>";
                $texteRequeteHtml = $texteRequeteHtml + "<a class='bouton' href='#' target='_blank' name='suppRequete_" + $nouvelleListe[liste]['numrequete'] + "' onClick=\"etatDeclanchementDeleteAjaxForm(1, this.name, 'suppressionRequete', '" + typePopup + "');return false;\"><div class='bouton supprimer'></div><div class='boutonname'>SUPPRIMER</div></a></td>";
                $texteRequeteHtml = $texteRequeteHtml + "</tr>";
                numListe ++;
            }
            $texteRequeteHtml = $texteRequeteHtml + "</table>";
            $texteRequeteHtml = $texteRequeteHtml + "<input type='hidden' id=\"nombre_requetes_" + typePopup + "\" name=\"nombre_requetes_" + typePopup + "\" value='" + $nouvelleListe.length + "'>";
            document.getElementById('typePopup').value = typePopup;
            document.getElementById('nombre_requetes').value = $nouvelleListe.length;
            switch (typePopup) {
                case 'combustibleB1':
                    $('#divEtatCombustibleB1').html($texteRequeteHtml);
                    break;
                case 'combustibleB2':
                    $('#divEtatCombustibleB2').html($texteRequeteHtml);
                    break;
                case 'compteur':
                    $('#divEtatCompteur').html($texteRequeteHtml);
                    break;
                case 'test':
                    $('#divEtatTest').html($texteRequeteHtml);
                    break;
                case 'forcage':
                    $('#divEtatForcage').html($texteRequeteHtml);
                    break;
            }
        })
        .fail(function($xhr, $status, $error) {
            alert('Erreur ' + $error);
        });
        fin_attente();
        return;
    }, 50);
}
// - - - - - - - - - - -- - - - - -- - - - - - - - - - - - - -- - - - - - - - - - - - - -- - - - - -- - - - - - - - - - - - - -- - -- - - - - - - - - - -- - - - - -- - - - - - - - - - - - - -- - -


/* Remplit la variable typePopup du fichier de popup : popupEtat.html.twig */
function etatSetTypePopupExclusion(typePopup) {
    document.getElementById('typePopup').value = typePopup;
    return(0);
}



//	Appelée lors du focus sur l'encart 'Code Message' des popups.
/*		Si un module ou un genre est selectionné : Sélection du Genre et du Module initial et raffraichissement des données par appel de la fonction 'selection'
function reinitialise_codeMessage()
{
    selection('genre', true);
}
*/

//      Fonction appelée lors de l'EDITION d'une requête enregistrée si un code message est défini : dans la page Listing/index.html.twig
//      1 Récupération du code en Majuscule
//      2 Calcul du nombre de caractères
//      2 Parcours de la liste des messages de modules
//      3 Au premier code identique trouvé : selection de celui-ci
function choixCodeMessage()
{
    var choix 		= document.getElementById('codeModule').value.toUpperCase();
    var nbCaracteres 	= choix.length;
    for(var i=0; i<document.getElementById('messages').options.length; i++)
    {
        if(document.getElementById('messages').options[i].text.substr(0, nbCaracteres) == choix)
        {
            document.getElementById('messages').options[i].selected = true;
            return;
        }
    }
}

    // Fonction qui sauvegarde les valeurs entrées et retourne le mot enregistré
    function searchMessage(e)
    {
        var caractere = String.fromCharCode(e.which);
        alert(caractere);
    }




//	Remise à vide du champs "modificationRequete"
function razUpdate()
{
    document.getElementById('modificationRequete').value='';
    razCodeModule(); 
}

function razCodeModule()
{
    document.getElementById('codeModule').value='';
}

// Fonction qui envoie les données de la popup d'ajout d'un message de module
function etatSendAjaxForm(page) {
    attente();
    setTimeout(function() {
	var typePopup = document.getElementById('typePopup').value;
        var localisationSelection = document.getElementById('localisations1').value;
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
                message_erreur_param = 'Erreur : valeur2 max < valeur2 min (' + valeur2Max + ' < ' + valeur2Min + ')';
                verif_param = false;
            }
        }
        if (messageSelection == '') {
            message_erreur_param = 'Erreur : Aucun message sélectionné';
            verif_param = false;
        }
        if (verif_param == false) {
            closeLightBox();
            $('#messageboxInfos').text(message_erreur_param);
            $('#messagebox').removeClass('cacher');
            fin_attente();
            return;
        }
        reinitialise_popup_liste();
        // Remise à vide du code module
        razCodeModule();
        var datas = "AJAX=" + ajax + "&choixSubmit=" + choixSubmit + "&listeLocalisations=" + localisationSelection + "&listeGenres=" + genreSelection + "&listeModules=" + moduleSelection + "&listeIdModules=" + messageSelection + "&codeVal1=" + codeVal1 + "&codeVal2=" + codeVal2 + "&codeVal1Min=" + valeur1Min + "&codeVal1Max=" + valeur1Max + "&codeVal2Min=" + valeur2Min + "&codeVal2Max=" + valeur2Max + "&modificationRequete=" + modificationRequete + "&typePopup=" + typePopup;
        var $url = $('#listeUrl').attr('data-url-etat');
        $.ajax({
            url: $url,
            method: 'GET',
            data: datas,
            timeout: 10000
        })
        .done(function($message, $status) {
			var $nouvelleListe = JSON.parse($message);
			$tabRequete = $nouvelleListe;
			var numListe = 0;
			var $texteRequeteHtml  = "<table>";
			$texteRequeteHtml = $texteRequeteHtml + "<tr>";		
			switch (typePopup) {            
                case 'combustibleB1':
                    $texteRequeteHtml = $texteRequeteHtml + "<td colspan='3' class='texte'>Ajouter un combustible du brûleur 1</td>";
                    break;
                case 'combustibleB2':
                    $texteRequeteHtml = $texteRequeteHtml + "<td colspan='3' class='texte'>Ajouter un combustible du brûleur 2</td>";
                    break;
				case 'compteur':
                	$texteRequeteHtml = $texteRequeteHtml + "<td colspan='3' class='texte'>Ajouter un compteur</td>";
                	break;
            	case 'test':
                	$texteRequeteHtml = $texteRequeteHtml + "<td colspan='3' class='texte'>Ajouter un test</td>";
                	break;
            	case 'forcage':
                	$texteRequeteHtml = $texteRequeteHtml + "<td colspan='3' class='texte'>Ajouter un forçage</td>";
                	break;
        	}
        	$texteRequeteHtml = $texteRequeteHtml + "<td class='actions'><a class='bouton' href='#' target='_blank' onClick='etatSetTypePopup(\"" + typePopup + "\");ouverturePopup(\"" + page + "\");return false;'>";
        	$texteRequeteHtml = $texteRequeteHtml + "<div class='bouton ajouter'></div>";
        	$texteRequeteHtml = $texteRequeteHtml + "<div class='boutonname'>AJOUTER</div></a></td>";
        	$texteRequeteHtml = $texteRequeteHtml + "</tr>";
        	for (liste in $nouvelleListe) {
            	$texteRequeteHtml = $texteRequeteHtml + "<tr><td class='localisation'><div class='txtlocalisation'>" + $nouvelleListe[liste]['localisation'] + "</div></td>";
            	if ($nouvelleListe[liste]['code'] != null) {
                	$texteRequeteHtml = $texteRequeteHtml + "<td class='code'>" + $nouvelleListe[liste]['code'] + "</td>";
            	} else {
                	$texteRequeteHtml = $texteRequeteHtml + "<td class='code'>&nbsp;</td>";
            	}
            	$texteRequeteHtml = $texteRequeteHtml + "<td class='designation'>" + $nouvelleListe[liste]['message'] + "</td>";
            	$texteRequeteHtml = $texteRequeteHtml + "<td class='actions'>";
            	$texteRequeteHtml = $texteRequeteHtml + "<a class='bouton' href='#' target='_blank' name='suppRequete_" + $nouvelleListe[liste]['numrequete'] + "' onClick=\"etatDeclanchementDeleteAjaxForm(1, this.name, 'suppressionRequete', '" + typePopup + "');return false;\" ><div class='bouton supprimer'></div><div class='boutonname'>SUPPRIMER</div></a></td>";
            	$texteRequeteHtml = $texteRequeteHtml + "</tr>";
            	numListe ++;
        	}
        	$texteRequeteHtml = $texteRequeteHtml + "</table>";
    		$texteRequeteHtml = $texteRequeteHtml + "<input type='hidden' id=\"nombre_requetes_" + typePopup + "\" name=\"nombre_requetes_" + typePopup + "\" value='" + $nouvelleListe.length + "'>";
    		switch (typePopup) {
        		case 'defChaudiere1':
        			$('#namedefChaudiere1').html(' ( ' + $nouvelleListe['0']['message'] + ' ) ');
        			$('#iddefChaudiere1').attr('value', $nouvelleListe['0']['idModule']);
        			break;
        		case 'defChaudiere2':
                	$('#namedefChaudiere2').html(' ( ' + $nouvelleListe['0']['message'] + ' ) ');
                	$('#iddefChaudiere2').attr('value', $nouvelleListe['0']['idModule']);
                	break;
            	case 'defBruleur1':
                	$('#namedefBruleur1').html(' ( ' + $nouvelleListe['0']['message'] + ' ) ');
                	$('#iddefBruleur1').attr('value', $nouvelleListe['0']['idModule']);
                	break;
            	case 'defBruleur2':
            	    $('#namedefBruleur2').html(' ( ' + $nouvelleListe['0']['message'] + ' ) ');
            	    $('#iddefBruleur2').attr('value', $nouvelleListe['0']['idModule']);
            	    break;
        		case 'defKlaxon':
        			$('#namedefKlaxon').html(' ( ' + $nouvelleListe['0']['message'] + ' ) ');
                	$('#iddefKlaxon').attr('value', $nouvelleListe['0']['idModule']);
        			break;
        		case 'defGyrophare':
        			$('#namedefGyrophare').html(' ( ' + $nouvelleListe['0']['message'] + ' ) ');
                	$('#iddefGyrophare').attr('value', $nouvelleListe['0']['idModule']);
        			break;
       	 		case 'defAcquittement':
        			$('#namedefAcquittement').html(' ( ' + $nouvelleListe['0']['message'] + ' ) ');
                	$('#iddefAcquittement').attr('value', $nouvelleListe['0']['conditions']);
        			break;
                case 'combustibleB1':
                    $('#divEtatCombustibleB1').html($texteRequeteHtml);
                    break;
                case 'combustibleB2':
                    $('#divEtatCombustibleB2').html($texteRequeteHtml);
                    break;
       	 		case 'compteur':
        			$('#divEtatCompteur').html($texteRequeteHtml);
        			break;
        		case 'test':
        			$('#divEtatTest').html($texteRequeteHtml);
                	break;
            	case 'forcage':
        			$('#divEtatForcage').html($texteRequeteHtml);
                	break;
    		}
        })
        .fail(function($xhr, $status, $error) {
            alert('Erreur ' + $error);
        });
        //closeLightBox();
        fin_attente();
        return;
    }, 50);
}



function etatSetTypePopup(typePopup) {
    document.getElementById('typePopup').value = typePopup;
    document.getElementById('nombre_requetes').value = document.getElementById('nombre_requetes_' + typePopup).value;
    return(0);
}


function etatResetAjaxForm(page)
{
    attente();
    setTimeout(function() {
		var datas = "AJAX=ajax&choixSubmit=RAZ&typePopup=null";
       	var $url = $('#listeUrl').attr('data-url-etat');
       	$.ajax({
       	    url: $url,
       	    method: 'GET',
       	    data: datas,
       	    timeout: 10000
       	})
        var $texteRequeteHtml = "<table>";
        $texteRequeteHtml = $texteRequeteHtml + "<tr>";
        $texteRequeteHtml = $texteRequeteHtml + "<td colspan='3' class='texte'>Ajouter un combustible du brûleur 1</td>";
        $texteRequeteHtml = $texteRequeteHtml + "<td class='actions'>";
        $texteRequeteHtml = $texteRequeteHtml + "<a class='bouton' href='{{ path('ipc_etat') }}' target='_blank' onClick=\"onLightBox('ipc_etat','Sélection des requêtes etat');etatSetTypePopup('combustibleB1');openLightBox();return false;\">";
        $texteRequeteHtml = $texteRequeteHtml + "<div class='bouton ajouter'></div>";
        $texteRequeteHtml = $texteRequeteHtml + "<div class='boutonname'>AJOUTER</div>";
        $texteRequeteHtml = $texteRequeteHtml + "</a>";
        $texteRequeteHtml = $texteRequeteHtml + "</td></tr>";
        $texteRequeteHtml = $texteRequeteHtml + "</table>";
        $texteRequeteHtml = $texteRequeteHtml + "<input type='hidden' id='nombre_requetes_combustibleB1' name='nombre_requetes_combustibleB1' value='0'>";
        $('#divEtatCombustibleB1').html($texteRequeteHtml);
        $('#nombre_requetes_combustibleB1').value = 0;
        var $texteRequeteHtml = "<table>";
        $texteRequeteHtml = $texteRequeteHtml + "<tr>";
        $texteRequeteHtml = $texteRequeteHtml + "<td colspan='3' class='texte'>Ajouter un combustible du brûleur 2</td>";
        $texteRequeteHtml = $texteRequeteHtml + "<td class='actions'>";
        $texteRequeteHtml = $texteRequeteHtml + "<a class='bouton' href='{{ path('ipc_etat') }}' target='_blank' onClick=\"onLightBox('ipc_etat','Sélection des requêtes etat');etatSetTypePopup('combustibleB2');openLightBox();return false;\">";
        $texteRequeteHtml = $texteRequeteHtml + "<div class='bouton ajouter'></div>";
        $texteRequeteHtml = $texteRequeteHtml + "<div class='boutonname'>AJOUTER</div>";
        $texteRequeteHtml = $texteRequeteHtml + "</a>";
        $texteRequeteHtml = $texteRequeteHtml + "</td></tr>";
        $texteRequeteHtml = $texteRequeteHtml + "</table>";
        $texteRequeteHtml = $texteRequeteHtml + "<input type='hidden' id='nombre_requetes_combustibleB1' name='nombre_requetes_combustibleB2' value='0'>";
        $('#divEtatCombustibleB2').html($texteRequeteHtml);
        $('#nombre_requetes_combustibleB2').value = 0;
        var $texteRequeteHtml = "<table>";
        $texteRequeteHtml = $texteRequeteHtml + "<tr>";
        $texteRequeteHtml = $texteRequeteHtml + "<td colspan='3' class='texte'>Ajouter un compteur</td>";
        $texteRequeteHtml = $texteRequeteHtml + "<td class='actions'>";
        $texteRequeteHtml = $texteRequeteHtml + "<a class='bouton' href='{{ path('ipc_etat') }}' target='_blank' onClick=\"onLightBox('ipc_etat','Sélection des requêtes etat');etatSetTypePopup('compteur');openLightBox();return false;\">";
        $texteRequeteHtml = $texteRequeteHtml + "<div class='bouton ajouter'></div>";
        $texteRequeteHtml = $texteRequeteHtml + "<div class='boutonname'>AJOUTER</div>";
        $texteRequeteHtml = $texteRequeteHtml + "</a>";
        $texteRequeteHtml = $texteRequeteHtml + "</td></tr>";
	    $texteRequeteHtml = $texteRequeteHtml + "</table>";
        $texteRequeteHtml = $texteRequeteHtml + "<input type='hidden' id='nombre_requetes_compteur' name='nombre_requetes_compteur' value='0'>";
	    $('#divEtatCompteur').html($texteRequeteHtml);
	    $('#nombre_requetes_compteur').value = 0;
        var $texteRequeteHtml = "<table>";
        $texteRequeteHtml = $texteRequeteHtml + "<tr>";
        $texteRequeteHtml = $texteRequeteHtml + "<td colspan='3' class='texte'>Ajouter un test</td>";
        $texteRequeteHtml = $texteRequeteHtml + "<td class='actions'>";
        $texteRequeteHtml = $texteRequeteHtml + "<a class='bouton' href='{{ path('ipc_etat') }}' target='_blank' onClick=\"onLightBox('ipc_etat','Sélection des requêtes etat');etatSetTypePopup('test');openLightBox();return false;\">";
        $texteRequeteHtml = $texteRequeteHtml + "<div class='bouton ajouter'></div>";
        $texteRequeteHtml = $texteRequeteHtml + "<div class='boutonname'>AJOUTER</div>";
        $texteRequeteHtml = $texteRequeteHtml + "</a>";
        $texteRequeteHtml = $texteRequeteHtml + "</td></tr>";
        $texteRequeteHtml = $texteRequeteHtml + "</table>";
        $texteRequeteHtml = $texteRequeteHtml + "<input type='hidden' id='nombre_requetes_test' name='nombre_requetes_test' value='0'>";
        $('#divEtatTest').html($texteRequeteHtml);
	    $('#nombre_requetes_test').value = 0;
        var $texteRequeteHtml = "<table>";
        $texteRequeteHtml = $texteRequeteHtml + "<tr>";
        $texteRequeteHtml = $texteRequeteHtml + "<td colspan='3' class='texte'>Ajouter un forçage</td>";
        $texteRequeteHtml = $texteRequeteHtml + "<td class='actions'>";
        $texteRequeteHtml = $texteRequeteHtml + "<a class='bouton' href='{{ path('ipc_etat') }}' target='_blank' onClick=\"onLightBox('ipc_etat','Sélection des requêtes etat');etatSetTypePopup('forcage');openLightBox();return false;\">";
        $texteRequeteHtml = $texteRequeteHtml + "<div class='bouton ajouter'></div>";
        $texteRequeteHtml = $texteRequeteHtml + "<div class='boutonname'>AJOUTER</div>";
        $texteRequeteHtml = $texteRequeteHtml + "</a>";
        $texteRequeteHtml = $texteRequeteHtml + "</td></tr>";
        $texteRequeteHtml = $texteRequeteHtml + "</table>";
        $texteRequeteHtml = $texteRequeteHtml + "<input type='hidden' id='nombre_requetes_forcage' name='nombre_requetes_forcage' value='0'>";
        $('#divEtatForcage').html($texteRequeteHtml);
	    $('#nombre_requetes_forcage').value = 0; 
	    $('#nombre_requetes').value = 0;
        fin_attente();
        return;
    }, 50);
}




function etat_reinitCheckbox()
{
    var typePopup                   = document.getElementById('typePopup').value;
    $('#'+typePopup).attr('checked', false);
    return;
}
