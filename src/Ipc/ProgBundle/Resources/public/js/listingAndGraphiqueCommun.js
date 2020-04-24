	// Variables et fonctions utilisées dans les modules Listing et Graphique

	// Variable globales utilisées dans la fonction addCompteRequest
    var $selectCourant;
    var $selectClient;


    // Fonction qui va rechercher la liste des requêtes.
    // Lorsque l'on coche la case, va rechercher la liste des requêtes clientes si la variable '$selectClient' est vide.
    // Lorsque l'on décoche la case, va rechercher la liste des requêtes du compte courante si la variable '$selectCourant' est vide.
    // Les variables $selectClient et $selectCourant sont réinitialisées lors de l'enregistrement d'une nouvelle requête ou la suppression d'une ancienne requête.
	function addCompteRequest($page, $compte) {
        attente();
        var $url = $('#lien_url_ajax').attr('data-urlGetRequetesPerso');
		$.ajax({
			url: $url,
			method: 'get',
			data: 'nomUtilisateur=' + $compte + '&page=' + $page,
			timeout: 10000
		})
		.done(function($message, $status) {
			// Raffraichissement de la page
			window.location.href = window.location.href;
		})
		.fail(function($xhr, $status, $error) {
			alert('Erreur ' + $error);
			fin_attente();
		});
    }


    function closeRequest(){
		removeShadow('popup');
		activateLinks();
        $('#lightboxSmall').addClass('cacher');
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

	// Fonction qui supprime une requête personnelle.
    function supprimeRequetePerso($page) {
		// Récupération de l'id de la requête à supprimer
		var $id_requete_selected = $("#selectRegPerso option:selected" ).val();
		// Appel ajax de la fonction de suppression de requête personnelle
		var $url_suppression_requete_personnelle = $('#selectRegPerso').attr('data-suppressionRequetePersonnelle') + '?id_requete=' + $id_requete_selected + '&page=' + $page;
		window.location.href = $url_suppression_requete_personnelle;
	}


	// Affiche les requêtes enregistrées lors de la selection du titre de la requête
	function selectRequestPerso() {
		// Récupération de l'id de la requête
		var $id_requete_selected = $("#selectRegPerso option:selected" ).val();
		// Appel ajax pour modification de l'id de la requête
		var $url_modification_id_requete_selected = $('#selectRegPerso').attr('data-url');
		$.ajax({
			url: $url_modification_id_requete_selected,
			method: 'get',
			data: 'id_requete=' + $id_requete_selected,
			timeout: 10000
		})
		.done(function() {
			// Si la modification de l'id de la requête à afficher s'est bien effectuée, on appelle le controller qui va modifier le contenu de la variable liste_req et afficher la page index avec la requête
			var $url_change_liste_req = $('#selectRegPerso').attr('data-changeListeReq');
			window.location.href = $url_change_liste_req;
		});
    }


    function creerRequetePerso($pageRequete){
        addShadow('popup');
        desactivateLinks();
        setTimeout(function(){
            $('#lightboxSmall').removeClass('cacher');
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
        var $message = $('#messages option:selected').text();
		var $pattern_code = /^.+?\s/;
		var $code = $.trim($message.match($pattern_code));
        $('#messageDeLaListe').text($message);
		$('#codeModule').val($code);
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
