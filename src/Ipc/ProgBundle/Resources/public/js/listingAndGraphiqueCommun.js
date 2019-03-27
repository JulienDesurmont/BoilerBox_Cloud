	// Variables et fonctions utilisées dans les modules Listing et Graphique

	// Variable globales utilisées dans la fonction addCompteRequest
    var $selectCourant;
    var $selectClient;


    // Fonction qui va rechercher la liste des requêtes.
    // Lorsque l'on coche la case, va rechercher la liste des requêtes clientes si la variable '$selectClient' est vide.
    // Lorsque l'on décoche la case, va rechercher la liste des requêtes du compte courante si la variable '$selectCourant' est vide.
    // Les variables $selectClient et $selectCourant sont réinitialisées lors de l'enregistrement d'une nouvelle requête ou la suppression d'une ancienne requête.
    function addCompteRequest($page) {
        attente();
        var $url = $('#lien_url_ajax').attr('data-urlGetRequetesPerso');
        var $chkBoxIsChecked = $('#requeteClient').is(':checked');
        if ($chkBoxIsChecked == true) {
            // Enregistrement des requêtes du compte courant si la variable est vide
            if ($selectCourant == null) {
                $selectCourant = $('#selectRegPerso').html();
            }
            // Recherche et affichage des requêtes du compte client
            if ($selectClient == null) {
                $.ajax({
                    url: $url,
                    method: 'get',
                    data: 'nomUtilisateur=Client&page=' + $page,
                    timeout: 10000
                })
                .done(function($message, $status) {
                    var $tabOptions = JSON.parse($message);
                    $selectClient = "<option value='noselected' disabled selected style='color:blue'>" + traduire('select.requetes_client.titre.' + $page) + "</option>";
                    $.each($tabOptions, function(key, value) {
                        $selectClient = $selectClient + "<option value='" + value + "'>" + value + "</option>";
                    });
                    $('#selectRegPerso').html($selectClient);
                    fin_attente();
                })
                .fail(function($xhr, $status, $error) {
                    alert('Erreur ' + $error);
                    fin_attente();
                });
            // Si les requêtes du compte client sont déjà en mémoire, réaffichage de celles-ci
            } else {
                $('#selectRegPerso').html($selectClient);
                fin_attente();
            }
        // Recherche et affichage du compte courant
        } else {
            // Enregistrement des requêtes du compte client si la variable est vide
            if ($selectClient == null) {
                $selectClient = $('#selectRegPerso').html();
            }
            if ($selectCourant == null) {
				var $nomUtilisateur = "";
                $.ajax({
                    url: $url,
                    method: 'get',
					data: 'nomUtilisateur=' + $nomUtilisateur + '&page=' + $page,
                    timeout: 10000
                })
                .done(function($message, $status) {
                    var $tabOptions = JSON.parse($message);
                    $selectCourant = "<option value='noselected' disabled selected style='color:blue'>" + traduire('select.requetes_perso.titre.' + $page) + "</option>";
                    $.each($tabOptions, function(key, value) {
                        $selectCourant = $selectCourant + "<option value='" + value + "'>" + value + "</option>";
                    });
                    $('#selectRegPerso').html($selectCourant);
                    fin_attente();
                })
                .fail(function($xhr, $status, $error) {
                    alert('Erreur ' + $error);
                    fin_attente();
                });
            } else {
                $('#selectRegPerso').html($selectCourant);
                fin_attente();
            }
        }
    }


	// Fonctions utilisées pour la sauvegarde, la suppression et l'affichage des requêtes personnelles : 
	//	Elles nécessitent l'inclusion du template IpcConfigurationBundle:Configuration:popupNouvelleRequetePerso.html.twig
    function saveRequest() {
        var $url = '';
        var $page = $('#choixPage_requetePerso').val();
        if($page == 'listing') {
             $url = $('#lien_url_ajax').attr('data-urlSaveListingPersoRequest');
        }else if($page == 'graphique') {
            $url = $('#lien_url_ajax').attr('data-urlSaveGraphiquePersoRequest');
        }
        var $requeteClient = $('#chkRequeteClient').prop('checked');
        $nameRequest = $('#intituleRequetePerso').val();
        if (checkValue($nameRequest)) {
            attente();
            // Fonction ajax faisant l'enregistrement des requêtes
            $.ajax({
                url: $url,
                method: 'GET',
                data: { nom: $nameRequest, requeteClient: $requeteClient},
                timeout: 10000
            })
            .done(function($message, $status) {
                $('#ajout_requete_perso').addClass('cacher');
                $('#messageValidation').html('Requête enregistrée');
                $('#messageValidation').removeClass('cacher');
                setTimeout(function(){
                    location.reload();
                }, 500);
            })
            .fail(function($xhr, $status, $error) {
                alert('Erreur ' + $error);
                fin_attente();
            });
        }
        return 0;
    }


    function closeRequest(){
		removeShadow('popup');
		activateLinks();
        $('#lightboxSmall').addClass('cacher');
        $('#choixPage_requetePerso').val('');
		$('#intituleRequetePerso').val('');
		return;
	}

    function checkValue($valeur) {
        var $retour = $valeur.match(/^[a-zA-Z0-9\s]+$/);
        if ($retour !== null) {
            if ($valeur.length > 40) {
                alert("40 caractères maximum autorisés (" + $valeur.length + " actuellement.)");
            } else {
                return true;
            }
        } else {
            alert("Vous avez entré des caractères incorrects.\nMerci de n'utiliser que des chiffres, des lettres et des espaces.");
            return false;
        }
    }

    function supprimeRequetePerso($page) {
        attente();
        addShadow('popup');
        desactivateLinks();
		var $compte = '';
        var $chkBoxIsChecked = $('#requeteClient').is(':checked');
        // Si la checkbox 'Compte client' est cochée : Recherche d'une requête du compte client
        // Sinon recherche d'une requête en fonction du compte utilisateur
        if ($chkBoxIsChecked == true) {
            $compte = 'Client';
        }
        if($page == 'listing') {
             $url = $('#lien_url_ajax').attr('data-urlDeleteListingPersoRequest');
        }else if($page == 'graphique') {
            $url = $('#lien_url_ajax').attr('data-urlDeleteGraphiquePersoRequest');
        }
        var $nameRequest = $("#selectRegPerso option:selected").val();
        if (($nameRequest != 'noselected') && ($("#selectRegPerso option:selected").index() != 0)) {
            $.ajax({
                url: $url,
                method: 'get',
                data: 'nom=' + $nameRequest + '&compte=' + $compte,
                timeout: 10000
            })
            .done(function($message, $status) {
                $('#ajout_requete_perso').addClass('cacher');
                $('#messageValidation').html('Requête supprimée');
                $('#messageValidation').removeClass('cacher');
                $('#lightboxSmall').removeClass('cacher');
                setTimeout(function(){
                    location.reload();
                }, 500);
            })
            .fail(function($xhr, $status, $error) {
                alert('Erreur ' + $error);
				activateLinks();
				removeShadow('popup');
                fin_attente();
            });
        } else {
			activateLinks();
			removeShadow('popup');
            fin_attente();
        }
	}


	// Affiche les requêtes enregistrées lors de la sélection du titre de la requête
    function selectRequest() {
        attente();
       	var $chkBoxIsChecked = $('#requeteClient').is(':checked');
		var $compte = '';
		// Si la checkbox 'Compte client' est cochée : Recherche d'une requête du compte client
		// Sinon recherche d'une requête en fonction du compte utilisateur
        if ($chkBoxIsChecked == true) {
			$compte = 'Client';
		}
        var $url = $('#selectRegPerso').attr('data-url');
        var $nameRequest = $("#selectRegPerso option:selected" ).val();
		if ($("#selectRegPerso option:selected").index() != 0) {
        	$.ajax({
        	    url: $url,
        	    method: 'get',
        	    data: 'nom=' + $nameRequest + '&compte=' + $compte,
        	    timeout: 10000
        	})
        	.done(function($message, $status) {
				// Si le message commence par Erreur c'est que la fonction ne s'est pas executée correctement
				var $messageErreur = $message.match(/^Erreur(.+?)$/);
				if ($messageErreur != null) {
					alert('message ' + $messageErreur);
					fin_attente();
				} else {
        	    	location.reload();
				}
        	})
        	.fail(function($xhr, $status, $error) {
        	    alert('Erreur ' + $error);
        	    fin_attente();
        	});
		} else {
			fin_attente();
		}
    }


    function creerRequetePerso($pageRequete){
        addShadow('popup');
        desactivateLinks();
        setTimeout(function(){
            $('#lightboxSmall').removeClass('cacher');
            $('#choixPage_requetePerso').val($pageRequete);
        }, 100);
    }


    // Fonction appelée lors de la validation de la popup : Si une modification de requête est en cours. Enregistrement de la modification + fermeture de la popup.
    // Si une création de requête est en cours : Enregistrement de la nouvelle requête sans fermeture de la popup.
    function checkValidationPopup() {
        if ($("#modificationRequete").val() != '') {
            closeLightBox();
        }
    }


   function changeListeMessagesSize($direction) {
        if ($direction == 'in') {
            $('#messages').attr('size', 5);
        }
        if ($direction == 'out') {
            $('#messages').attr('size', 1);
            $('#messageDeLaListe').text('');
        }
    }

    function afficheListeMessage(){
        $message = $('#messages option:selected').text();
        $('#messageDeLaListe').text($message);
    }

    /* Fonction qui change la valeur du paramètre popup_simplifiee et qui réinitialise les listes */
    function changeTypeListe($page){
        attente();
        $valeur = 0;
        if ($('#maxListe').is(':checked')) {
            $valeur = 1;
        }
        $urlRequest = $('#maxListe').attr('data-url');
        $.ajax({
            type: 'post',
            url: $urlRequest,
            data: 'liste_complete=' + $valeur,
            success: function($data, $textStatus) {
                /* Rechargement de la page */
                if ($page == 'graphique') {
                    selection('graphique', 'genre', false);
                } else if ($page == 'listing') {
                    selection('listing', 'genre', false);
                }
                if ($valeur == 1) {
                    $('#maxListe').prop('checked', true);
                } else {
                    $('#maxListe').prop('checked', false);
                }
                fin_attente();
            },
            error: function($data, $textStatus, $error) {
                alert('error');
                fin_attente();
            }
        });
    }
