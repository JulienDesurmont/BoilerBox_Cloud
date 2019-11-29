<script type="text/javascript">
var $chargementGestionCourbes = true;
var xhr = null;		
// Nombre de coubres comportant au moins une donnée
var nb_de_requetes = 0;	
var dateImpression = new Date();		
// Récupération de la date courante
date_heure();
// Nombre de requêtes affichées dans le graphique (dont le nb de points > 0)
var nb_de_requetes_en_cours	= null;	
var titre;
var unitt = [];
// Bleu, Vert Clair, Rouge foncé, Orange, Violet, Vert foncé, Rouge clair, Gris
var les_couleurs= ['#0404B4','#088A08','#B18904','#B40404','#929703','#B404AE','#04B4AE','#0000FF','#01DF01','#DBA901','#FF0000','#8F8F6E','#FF00FF','#0B0B61','#0B610B','#61380B','#610B0B','#5E610B','#610B4B','#0B615E','#00FFFF','#5858FA','#82FA58','#F7D358','#C758FB','#FA5858','#FA58F4','#81F7F3','#421A55']
var les_series = [];
var last_series	= [];
var axes_y = [];
var axe_def = [];
var num_axe = -1;
var les_nouvelles_series = [];
// Graphique des données initiales
var graphData = [];
// Graphique des données retournées par la requête AJAX
var graphData2 = [];
// Graphique en cours d'affichage
var graphEnCours = [];
var numGraphique = 0;
var lastGraphDate = null;
var firstGraphDate = null;
var firstGraphTime = null;
var lastGraphTime = null;
var tabLastHorodatage = [];
var tabLastHorodatage = ({{ tabLastHorodatage|json_encode|raw }});
var tabZoom = [];
// Indice du zoom en cours d'affichage
var indiceTabZoom = -1;
var unite = new unites();
var nbDecimal = {{ nbDecimal }};
var tooltipMax = true;
var tabTooltip = [];
// Variables utilisées pour la création de la légende d'impression
var $titreLegende = "<span><b><i>" + traduire('label.titre.legende') + "</i></b></span><br /><br />";
var $legende = $titreLegende;
var $tabLegende = [$titreLegende];

var $objLegend;
var $objScrollbar;

var chart1;

function afficheChart(chartOptions) {
    var saveSerie = chartOptions.series;
    chart1 = new Highcharts.StockChart(chartOptions);
    chartOptions.series = saveSerie;
}


// Objet de l'axe des ordonnés
function setAxe($numGraphique, $numAxe, $allowDecimal, $position) {
    var laxe;
    if ($position == 'left') {
        laxe = {
            allowDecimals: $allowDecimal,
            endOnTick: true,
            maxPadding: 0.2,
            gridLineWidth: 0,
            offset: 30,
            labels: {
                x: -20 * ($numAxe - 1),
                style:{
                    color: les_couleurs[$numGraphique]
                }
            },
            title: {
                align: 'high',
                margin: -20,
                text: graphData[$numGraphique]['unite'],
                x: -20 * ($numAxe - 1),
                style: {
                    color: les_couleurs[$numGraphique]
                }
            },
			opposite: false
        }
    }
    if ($position == 'right') {
        laxe = {
            allowDecimals: $allowDecimal,
            endOnTick: true,
            maxPadding: 0.2,
            gridLineWidth: 0,
            offset: 30,
            labels: {
                x: 25 * ($numAxe - 2),
                style:{
                    color: les_couleurs[$numGraphique]
                }
            },
            title: {
                align: 'high',
                margin: -5,
                rotation: 270,
                text: graphData[$numGraphique]['unite'],
                x: 25 * ($numAxe - 2),
                style: {
                    color: les_couleurs[$numGraphique]
                }
            },
			opposite: true
        }
    }
    return laxe;
}

// Calcul du nombre de courbe ayant au moins une donnée
var nombreCourbes = {{ liste_req_pour_graphique|length }};
for (j = 0; j < nombreCourbes; j++) {
	{% for i in 1..liste_req_pour_graphique|length %}
		var i = {{ i-1 }};
		if (i == j) {
			var nb_donnees  = {{ liste_req_pour_graphique[i-1]['MaxDonnees'] }};
			if (nb_donnees != 0) {
				nb_de_requetes++;
			}
		}
	{% endfor %}
}

// A l'affichage de la page : Création des courbes en fonction de la variable liste_req_pour_graphique
for (j = 0; j < nombreCourbes; j++) {
	{% for i in 1..liste_req_pour_graphique|length %}
		var i = {{ i-1 }};
		if (i == j) {
			var nb_donnees 	= {{ liste_req_pour_graphique[i-1]['NbDonnees'] }};
			if (nb_donnees == 0) {
				numGraphique--;
			} else {
				//	Par défaut la courbe est affichée donc on l'indique dans le tableau des séries à afficher dans l'objet tooltip
				tabTooltip.push(numGraphique);
				graphData[numGraphique] = [];
				graphData[numGraphique]['Donnees'] = ({{ liste_req_pour_graphique[i-1]['Donnees']|json_encode|raw }});
				// Récupération de la dernière date du graph
				var lastTime = graphData[numGraphique]['Donnees'][nb_donnees-1]['horodatage'];
				var reg = /^(\d+)-(\d+)-(\d+) (\d+):(\d+):(\d+)/.exec(lastTime);
				var lastDate = Date.UTC(RegExp.$1,RegExp.$2-1,RegExp.$3,RegExp.$4,RegExp.$5,RegExp.$6,graphData[numGraphique]['Donnees'][nb_donnees-1]['cycle']);
				// Récupération de la première date du graph
				var firstTime = graphData[numGraphique]['Donnees'][0]['horodatage'];
				var reg = /^(\d+)-(\d+)-(\d+) (\d+):(\d+):(\d+)/.exec(firstTime);
				var firstDate = Date.UTC(RegExp.$1,RegExp.$2-1,RegExp.$3,RegExp.$4,RegExp.$5,RegExp.$6,graphData[numGraphique]['Donnees'][0]['cycle']);
				if (! lastGraphDate) {
					lastGraphDate = lastDate;
					lastGraphTime = lastTime;
				} else if (lastDate > lastGraphDate) {
					lastGraphDate = lastDate;
					lastGraphTime = lastTime;
				}
				// Récupération de la première date du graph
				if (! firstGraphDate) {
					firstGraphTime = firstTime;
					firstGraphDate = firstDate;
				} else if (firstDate < firstGraphDate) {
					firstGraphTime = firstTime;
					firstGraphDate = firstDate;
				}
			}
			numGraphique++;
		}
	{% endfor %}
}

// Définition des points de début et de fin de graphique
graphData['firstTime'] = firstGraphTime;
graphData['lastTime'] = lastGraphTime;
numGraphique = 0;
for (j = 0; j < nombreCourbes; j++) {
	{% for i in 1..liste_req_pour_graphique|length %}
		var i = {{ i-1 }};
		if (i == j) {
			var nb_donnees = {{ liste_req_pour_graphique[i-1]['NbDonnees'] }};
			if (nb_donnees == 0) {
				numGraphique--;
			} else {
				graphData[numGraphique] = [];
				graphData[numGraphique]['Donnees'] = ({{ liste_req_pour_graphique[i-1]['Donnees'] | json_encode | raw }});
				graphData[numGraphique]['texte'] = {{ liste_req_pour_graphique[i-1]['TexteRecherche'] | json_encode | raw }};
				graphData[numGraphique]['allPoints'] = {{ liste_req_pour_graphique[i-1]['AllPoints'] | json_encode | raw }};
				graphData[numGraphique]['localisation'] = {{ liste_req_pour_graphique[i-1]['Localisation'] | json_encode | raw }};
				graphData[numGraphique]['idLocalisation'] = {{ liste_req_pour_graphique[i-1]['id_localisations'] | json_encode | raw }};
				graphData[numGraphique]['numeroGenre'] = {{ liste_req_pour_graphique[i-1]['numeroGenre'] | json_encode | raw }};
				// Dans la fonction formatHigh, création de unitt
				graphData[numGraphique]['data'] = formatabHigh(graphData[numGraphique], firstGraphDate, lastGraphDate);
				graphData[numGraphique]['title'] = graphData[numGraphique]['numeroGenre'] + ';' + titre;
				graphData[numGraphique]['legende'] = fctSupprimeCaracteres(titre, null);
				graphData[numGraphique]['unite'] = unitt['unite'];
				graphData[numGraphique]['nbPoints'] = nb_donnees;
				// Définition des différentes séries du graphique
				// Si un axe identique est déjà définit on ne le redéfinit pas
				if ($.inArray(unitt['unite'], axe_def) == -1) {
					num_axe++;
					axe_def[num_axe] = unitt['unite'];
					// On détermine les paramètres en fonction de la place de l'axe y sur le graphique (1 axe sur deux à gauche, 1 axe sur deux à droite)
					if ((num_axe % 2) == 0) {
						// Paramètres particuliers pour les booleens
						if (axe_def[num_axe].toLowerCase() == 'bool') {
							axes_y[num_axe] = setAxe(numGraphique, num_axe, false, 'left');
						} else {
							axes_y[num_axe] = setAxe(numGraphique, num_axe, true, 'left');
						}
					} else {
                        if (axe_def[num_axe].toLowerCase() == 'bool') {
							axes_y[num_axe] = setAxe(numGraphique, num_axe, false, 'right');
						} else {
							axes_y[num_axe] = setAxe(numGraphique, num_axe, true, 'right');
						}
					}
				}
				les_series[numGraphique] = {
					// Utilisé pour la légende et la première partie des infos bulles ( = nom du module )
					name: graphData[numGraphique]['title'],
					zIndex:	5-numGraphique,
					visible: true,
					yAxis: $.inArray(unitt['unite'], axe_def),	//	Retourne l'index de la clé du tableau axe_def pour la valeur unitt['unite']
					color: les_couleurs[numGraphique],
					data: graphData[numGraphique]['data'],
					step: true,
					tooltip: {
						valueDecimals: nbDecimal
					},
					marker: {
						enabled: false
					},
					dataGrouping: {
						enabled: false,
						forced : true,
						units: [
							[
								'minute',
								[10]
							]
						]
					}
				};
				// Tableau Unite : unite(titre) => unite 
				unite[graphData[numGraphique]['title']]	= graphData[numGraphique]['unite'];
				titre = '';
				unitt['unite'] = '';
			}
			numGraphique++;
		}
	{% endfor %}
}
// Redéfinition du nombre de requêtes affichées dans le graphique (dont le nb de points > 0)
nb_de_requetes_en_cours = numGraphique;
// Détermination du dernier graphique affiché : 
graphEnCours = graphData;
// Définition des dernières séries affichées :
defineLastSerie(les_series);
var $chartMarginBottom = 80 + 20 * nb_de_requetes;
if ($chartMarginBottom > 200) {
	$chartMarginBottom = 200;
}
// Marge de gauche et de droite 30 * nombre d'unité à afficher
var $margeParUnite = 40;
var $chartMarginLeft = 0;
if (axes_y.length % 2 == 0) {
	$chartMarginLeft = (parseInt(axes_y.length/2,10))*$margeParUnite+1;
} else {
	$chartMarginLeft = (parseInt(axes_y.length/2,10)+1)*$margeParUnite+1;
}
var $chartMarginRight = parseInt(axes_y.length/2,10)*$margeParUnite+1;

$(function() {
	var highchartsOptions = Highcharts.setOptions(Highcharts.theme);
	Highcharts.setOptions({
		lang: {
			months:	["Janvier "," Février "," Mars "," Avril "," Mai "," Juin "," Juillet "," Août ","Septembre "," Octobre "," Novembre "," Décembre"],
			weekdays: ["Dim "," Lun "," Mar "," Mer "," Jeu "," Ven "," Sam"],
			shortMonths: ['Jan', 'Fev', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil','Août', 'Sept', 'Oct', 'Nov', 'Déc'],
			decimalPoint: ',',
			resetZoom: 'Reset zoom',
			resetZoomTitle: 'Reset zoom à 1:1',
			loading: "Chargement...",
			rangeSelectorFrom: "Période du",
			rangeSelectorTo: "au",
			rangeSelectorZoom: ''
		}
	});
	chartOptions = {
        boost: {
        	useGPUTranslations: false // = Valeur par défaut
    	},
		chart: { 
			renderTo: 'container',
			alignTicks: false,
			zoomType: 'x',
			marginLeft:	$chartMarginLeft,
			marginRight: $chartMarginRight,
			marginBottom: $chartMarginBottom,
			marginTop: 10,
			panning: true,
        	panKey: 'shift',
			events:	{
				load: function (e) {
                    var chart = this,
                        buttons = chart.rangeSelector.buttons;
						$.each(buttons, function(index,value) {
							buttons[index].on('click', function () {
                        		// Lors d'un zoom : Suppression des valeurs du tableau des zoom au dessus de l'indice courant
                        		// Incrémentation de l'indice de zoom pour savoir à quel zoom on est
                        		indiceTabZoom ++;
                        		$('#indiceZoom').val(indiceTabZoom);
                        		if (typeof(tabZoom[indiceTabZoom]) != 'undefined') {
                        		    // Suppression des valeurs de zoom à partir de l'indice courant ( = suppression des anciennes infos de zooms )
                        		    var deleteCount = compteElementObjet(tabZoom) - indiceTabZoom;
                        		    tabZoom.splice(indiceTabZoom,deleteCount);
                        		    $('#tableauZoom').val(JSON.stringify(tabZoom));
                        		    // Désactivation du zoom suivant
                        		    $('#zoomSuivant').addClass('btn-disable');
                        		}
                        		// Initialisation d'un nouveau tableau pour l'indice courant
                        		tabZoom[indiceTabZoom] = new Object();
								chart.rangeSelector.clickButton(index,{
                            }, true);
							// Initialisation des valeurs indiquant les paramètres de début et de fin de graphique
                            tabZoom[indiceTabZoom]['debut'] = chart.xAxis[0].getExtremes().min;
                            tabZoom[indiceTabZoom]['fin'] = chart.xAxis[0].getExtremes().max;
                            // Par défaut all est défini à false : On suppose que toutes les valeurs ne sont pas récupérées
							tabZoom[indiceTabZoom]['all'] = true;
                        	$('#tableauZoom').val('');
                        	$('#tableauZoom').val(JSON.stringify(tabZoom));
                        	// Affichage des zooms à partir du deuxième zoom car le précédent du 1er correspond au graphique initial
                        	if (indiceTabZoom == 1) {
                        	    $('#zoomPrecedent').removeClass('btn-disable');
                        	}
                    	});
					});
                },
				selection: function(event) {
					if (event.xAxis) {
						// Lors d'un zoom : Suppression des valeurs du tableau des zoom au dessus de l'indice courant
						// Incrémentation de l'indice de zoom pour savoir à quel zoom on est
						indiceTabZoom ++;
						$('#indiceZoom').val(indiceTabZoom);
						if (typeof(tabZoom[indiceTabZoom]) != 'undefined') {
							// Suppression des valeurs de zoom à partir de l'indice courant ( = suppression des anciennes infos de zooms )
							var deleteCount = compteElementObjet(tabZoom) - indiceTabZoom;
							tabZoom.splice(indiceTabZoom,deleteCount);
							$('#tableauZoom').val(JSON.stringify(tabZoom));
							// Désactivation du zoom suivant
							$('#zoomSuivant').addClass('btn-disable');
						}
						// Initialisation d'un nouveau tableau pour l'indice courant
						tabZoom[indiceTabZoom] = new Object();
						// Pas de requête à faire en base
						var search = false;
						for (var numgraph = 0; numgraph < nb_de_requetes_en_cours; numgraph++) {
							// Une des courbe n'a pas tous les points récupérés : Une recherche sera donc faite en base de donnée
							if (graphEnCours[numgraph]['allPoints'] == false) {
								search = true;
							}
						}
						// Initialisation des valeurs indiquant les paramètres de début et de fin de graphique
						var min;
						var max;
						if (event.xAxis[0].min < event.xAxis[0].max) {
							min = Highcharts.dateFormat('%Y-%m-%d %H:%M:%S', event.xAxis[0].min);
							max = Highcharts.dateFormat('%Y-%m-%d %H:%M:%S', event.xAxis[0].max);
							tabZoom[indiceTabZoom]['debut'] = event.xAxis[0].min;
							tabZoom[indiceTabZoom]['fin'] = event.xAxis[0].max;
							// Par défaut all est défini à false : On suppose que toutes les valeurs ne sont pas récupérées
							tabZoom[indiceTabZoom]['all'] = false;
						} else {
							min = Highcharts.dateFormat('%Y-%m-%d %H:%M:%S', event.xAxis[0].max);
							max	= Highcharts.dateFormat('%Y-%m-%d %H:%M:%S', event.xAxis[0].min);
							tabZoom[indiceTabZoom]['debut'] = event.xAxis[0].max;
							tabZoom[indiceTabZoom]['fin']   = event.xAxis[0].min;
							// Par défaut all est défini à false : On suppose que toutes les valeurs ne sont pas récupérées
							tabZoom[indiceTabZoom]['all'] = false;
						}
						// Si une recherche doit être faite en base de donnée : Inhibition du zoom de highchart
						if (search == true) {
							// Inhibition du zoom de highchart
							event.preventDefault();
							// Message d'attente
							this.showLoading('Loading data from server...');
							// Indication sur le zoom précédent de la nom completude des courbes à partir de l'indice 1 et si la valeur n'est pas déjà renseignée
							if (indiceTabZoom > 0) {
								if (typeof(tabZoom[indiceTabZoom-1]['all']) == 'undefined') {
									tabZoom[indiceTabZoom-1]['all'] = false;
								}
							}
							// Recherche Sql & Affichage des nouveaux graphiques
							newGraphs(min, max, null);
							// Fin du message d'attente
							this.hideLoading();
						} else {
							// Si la recherche ne doit pas être faite en base
							tabZoom[indiceTabZoom]['all'] 	= true;
						}
						$('#tableauZoom').val('');
						$('#tableauZoom').val(JSON.stringify(tabZoom));
						// Affichage des zooms à partir du deuxième zoom car le précédent du 1er correspond au graphique initial
						if (indiceTabZoom == 1) {
							$('#zoomPrecedent').removeClass('btn-disable');
						}
					} else {
						alert('Selection reset');
					}
				}
			}
		},
		plotOptions: {
			series: {
				turboThreshold: 0,
				events: {
					legendItemClick: function (e) {
						// Modification du tableau des courbes à afficher dans le tooltip
						fillTabTooltip(this.index, this.visible);	
					}
				},
                states: {
                    inactive: {
                        opacity: 1
                    }
                }
			}
		},
		legend: {
			title: {
				text: 'Légende',
				style: {
					fontStyle: 'italic'
				}
			},
			enabled: true,
			borderWidth: 0,
			align: 'left',
			itemHoverStyle: {
				color: 'red'
			},
			maxHeight: 150,
			navigation: {
				arrowSize: 16,
				style: {
					fontSize: '14px'
				}
			},
			layout: 'vertical',
			x: $chartMarginLeft - 10,
			labelFormatter:function() { return fctSupprimeCaracteres(this.name, this.color); }
		},
		navigator: {
			enabled : false
		},
		scrollbar: {
			enabled: true,
			showFull: false,
			barBackgroundColor: 'gray',
			height: 15,
			barBorderRadius: 7,
			barBorderWidth: 0,	
			buttonBackgroundColor: 'gray',
			buttonBorderWidth: 0,
			buttonArrowColor: 'white',
			buttonBorderRadius: 7,
			rifleColor: 'white',
			trackBackgroundColor: 'white',
			trackBorderWidth: 1,
			trackBorderColor: 'silver',
			trackBorderRadius: 7
		},
		credits: {
			enabled: false
		},
		xAxis: {
			minTickInterval: 2,
			type: 'datetime',
			// Espacement entre les points afin de garder une distance réelle entre 2 points
			ordinal: false
		},
		yAxis: axes_y,
		// Action lors du survol de la souris
		tooltip: {
        	positioner: function (boxWidth, boxHeight, point) {
        	    return {x:140, y:35 };
        	},
        	backgroundColor: 'rgba(250,250,250,0.7)',
        	borderColor: 'black',
			crosshair: true,
        	borderRadius: 10,
        	borderWidth: 2,
        	useHTML: true,
			shared: true,
			formatter: function() {
				var s = '<b>' + Highcharts.dateFormat('%e %B %Y à %H:%M:%S (%L)', this.x) + '</b><br />';
				$.each(this.points, function(i, point) {
					s = s + recupCourbesPoints(point.x);
					return false;
				});
				return s;
			},
		},
		rangeSelector: {
			inputDateFormat: '%d/%m/%Y %H:%M:%S.%L',
			inputEditDateFormat: '%d/%m/%Y %H:%M:%S.%L',
			inputBoxWidth: 170,
			inputPosition: {
				x: 10 - $chartMarginRight
			},
			// Custom parser to parse the %H:%M:%S.%L format
			inputDateParser: function (value) {
				ladate = value.split(/ /);
				annee = ladate[0].split(/\//);
				value = ladate[1].split(/[:\.]/);
				return Date.UTC(
					parseInt(annee[2], 10),
					parseInt(annee[1]-1, 10),
					parseInt(annee[0], 10),
					parseInt(value[0], 10),
					parseInt(value[1], 10),
					parseInt(value[2], 10),
					parseInt(value[3], 10)
				);
			},
			buttonTheme: {
				width:40
			},
			buttons: [{
				type: 'second',
				count: 30,
				text: '30s'
			}, {
				type: 'minute',
				count: 5,
				text: '5mn'
			}, {
				type: 'minute',
				count: 30,
				text: '30mn'
			}, {
				type: 'hour',
				count: 1,
				text: '1h'
			}, {
				type: 'hour',
				count: 12,
				text: '12h' 
			}, {
				type: 'day',
				count: 1,
				text: '1d'
			}, {
				type: 'day',
				count: 15,
				text: '15d'
			}, {
				type: 'month',
				count: 1,
				text: '1m'
			}, {
				type: 'all',
				count: 1,
				text: 'Tous'
			}],
			inputEnabled: true,
			selected: 8,	
		},
		series: les_series,
		navigation: {
			buttonOptions: {
				theme: {
					style: {
						color: '#039',
						textDecoration: 'underline'
					}
				}
			}
		},
		// Les options d'exports des graphiques
		exporting: {
			enabled: false
		}
	};
});
$(document).ready(function() {
	cacheCompression();
	//	Affichage du graphique à l'affichage de la page
	//	Modification du tooltip
	checkTooltip();
	//chart1 = new Highcharts.StockChart(chartOptions);
	afficheChart(chartOptions);
	// Création de la légende
	$('#legendeGraphique').html($legende);
    //  Pour chaque courbe : Mise en place des nouveaux points affichés pour la courbe
    for (var nomRequete=0; nomRequete < nb_de_requetes; nomRequete++) {
        newSession(nomRequete, chart1.series[nomRequete].processedXData, chart1.series[nomRequete].processedYData);
    }
	//	Lors de la modification d'un bouton SELECT, Récupération du numéro de courbe affecté (numeroReq], du nombre du pas (num), du type de pas(type)
	$('select').change(function() {
		$('#siteIpc').css('cursor','wait');
		//	Récupération du numéro de requête affectée par le changement (Numéro définit dans l'id du select : ex pas_1
		var idSelect = $(this).attr('id');
		var $valeur_pas	= null;
		var $valeur = idSelect.split('_');
		// Numéro de la requête impactée par le select ( = 2eme arguments : ex pas_2 impact requête 2)
		var typeSelect = $valeur[0];
		var numeroReq = $valeur[1];
		// Récupération de la valeur du select de Pas
		var checkedPasValue = $("#pas_"+numeroReq+" option:selected").val();
		// Récupération de la valeur du select de Compression
		var checkedCompressValue = $("#compression_"+numeroReq+" option:selected").val();
		// Si la selection concerne le bouton Compression : Affichage du pas si la compression est != all sinon cache du pas
        if (typeSelect == 'compression') {
            if (checkedCompressValue == 'all') {
				$('#select-pas_'+numeroReq).addClass('cacher');		
				// Récupération des séries de données en cours d'affichage
               	var lesSeries = last_series;
                lesSeries[numeroReq].dataGrouping.enabled = false;
                // Remise du titre d'origine de la courbe
                // Si un graphique retourné par AJAX est disponible il correspond au graphique affiché avant les zooms
                if (graphData2.length != 0) {
                    lesSeries[numeroReq].name = graphData2[numeroReq]['title'];
                    newTextCourbe = graphData2[numeroReq]['texte'];
              	} else {
                	// Redéfinition du titre des courbes
                    lesSeries[numeroReq].name = graphData[numeroReq]['title'];
                    // Redéfinition du type de la Recherche
                    newTextCourbe = graphData[numeroReq]['texte'];
                }
                // On affecte les nouvelles séries aux options du graph
                chartOptions.series = lesSeries;
			    // Modification du tooltip
				checkTooltip();
                //chart1 = new Highcharts.StockChart(chartOptions);
				afficheChart(chartOptions);
                // Remise de la recherche d'origine
                modifTexteRecherche(numeroReq, newTextCourbe, chart1.series[numeroReq].processedXData.length);
			} else {
		    	if ($('#select-pas_'+numeroReq).hasClass('cacher')) {
		        	$('#select-pas_'+numeroReq).removeClass('cacher');
		    	}
			}
        }
	    // Si le bouton selectionné est 'Complete' aucune requête n'est faite sur modification du pas : Sinon valeurs possibles : average, high, low
		// Sinon valeurs possibles : average, high, low
	    if (checkedCompressValue != 'all') {
			// Récupération de la valeur du SELECT selectionné d'id Pas_
			$valeur = checkedPasValue.split('_');
			var num	= $valeur[0];
			var type = $valeur[1];
			var lesSeries = last_series;
			lesSeries[numeroReq].dataGrouping.enabled = true;
			lesSeries[numeroReq].dataGrouping.units	= [[type,[num]]];
			lesSeries[numeroReq].dataGrouping.approximation	= checkedCompressValue;
			chartOptions.series = lesSeries;	
			// Définition du nouveau titre de la courbe
			lesSeries[numeroReq].name = nouveauTitre(traduireMessage(graphData[numeroReq]['Donnees'][0]['message']), graphData[numeroReq]['unite'], checkedCompressValue, type, num, graphData[numeroReq]['localisation'], 'Titre');
			// Création de la nouvelle indication de Recherche pour afficher sur la page html
            $new_recherche = nouveauTitre(traduireMessage(graphData[numeroReq]['Donnees'][0]['message']), graphData[numeroReq]['unite'], checkedCompressValue, type, num, graphData[numeroReq]['localisation'], 'Recherche');
        	// Modification du tooltip
			checkTooltip();
			// Création du nouveau graphique
			//chart1 = new Highcharts.StockChart(chartOptions);
			afficheChart(chartOptions);
			// Modification des messages de la page affichage_graphique.html.twig indiquant la recherche en cours
            modifTexteRecherche(numeroReq,$new_recherche,chart1.series[numeroReq].processedXData.length);
	        // Mise en place des nouveaux points affichés pour la courbe
            newSession(numeroReq,chart1.series[numeroReq].processedXData,chart1.series[numeroReq].processedYData);
			// Ajout d'un parametre au tableau unit
            unite[lesSeries[numeroReq].name] = graphData[numeroReq]['unite'];
	    }
	    $('#siteIpc').css('cursor','initial');
	});
    //  Si une série est enregistrée dans la page c'est que la page est raffraichie ou affichée après un téléchargement
    if ($('#tableauZoom').val() != '') {
        tabZoom = JSON.parse($('#tableauZoom').val());
        last_series = JSON.parse($('#derniereSerie').val());
        indiceTabZoom = $('#indiceZoom').val();
	    var nombreElementsZoom = compteElementObjet(tabZoom);
        // La sauvegarde des séries se fait jusqu'au premier graphique ayant tous les points récupérés
        if (tabZoom[indiceTabZoom]['all'] == false) {
            setOldGraphique(tabZoom[indiceTabZoom]['series'],tabZoom[indiceTabZoom]['graphiques']);
        } else {
            // Si la recherche ne comporte pas de champs 'graphique' c'est qu'elle concerne une période du graphique affiché
            // Dans ce cas : Affichage du dernier graphique enregistré dans le tableau des zooms + affichage de la période désiré sur ce graphique
            for (numZoom = nombreElementsZoom - 1; numZoom >= 0; numZoom--) {
            	if (tabZoom[numZoom]['all'] == false) {
                    setOldGraphique(tabZoom[numZoom]['series'],tabZoom[numZoom]['graphiques']);
                }
            }
            // Modification des périodes directement sur le graphique
            var chartExtrem = $('#container').highcharts();
            var xExt = chartExtrem.xAxis[0].setExtremes(tabZoom[indiceTabZoom]['debut'], tabZoom[indiceTabZoom]['fin'], true);
        }
	    // Affichage des zooms précédents et suivants : Si il y a au moins deux zoom possibles affichage des boutons
	    // Affichage du bouton suivant si l'indice est < au nombre de zooms
	    // Affichage du bouton précédent si l'indice est > 0
	    if (nombreElementsZoom > 1) {
			if (indiceTabZoom < nombreElementsZoom - 1) {
                $('#zoomSuivant').removeClass('btn-disable');
			}
			if (indiceTabZoom > 0) {
                $('#zoomPrecedent').removeClass('btn-disable');
			}
	    }
    }
});


// Remise du graphique initial
function reinitialiseGraphique() {
	// Réinitialisation du tableau des zooms et de l'indice
	tabZoom = new Object();
	indiceTabZoom = -1;
	$('#indiceZoom').val(indiceTabZoom);
	if (! $('#zoomPrecedent').hasClass('btn-disable')) {
        $('#zoomPrecedent').addClass('btn-disable');
	}
	if (! $('#zoomSuivant').hasClass('btn-disable')) {
	    $('#zoomSuivant').addClass('btn-disable');
	}
    // Affichage de l'image loader ( = attente d'une requête en cours )
	$('#siteIpc').css('cursor','wait');
    // Réinitialisation du graphique AJAX
    graphData2 = [];
    // Redefinition des séries d'origine
    var lesSeries = les_series;
	defineLastSerie(les_series);
    // Redéfinition du graphique initial comme graphique en cours d'affichage
    graphEnCours = graphData;
    // Redéfinition du nombre de requêtes affichées dans le graphique (dont le nb de points > 0)
    nb_de_requetes_en_cours = graphEnCours.length;
    // Suppression du groupement des courbes
    for (var numSeries =0 ; numSeries < nb_de_requetes_en_cours; numSeries++) {
        lesSeries[numSeries].dataGrouping.enabled=false;
    }
    // Selection des options 'completes' pour l'ensemble des courbes. et redéfinition du message initial
    for (var nomRequete =0 ; nomRequete < nb_de_requetes; nomRequete++) {
	    // Remise du champs Compression à 'Toutes les valeurs'
	    document.getElementById('compression_'+nomRequete).options[0].selected = true;
	    // Remise du select 'Pas' à 10 minutes 
	    document.getElementById('pas_'+nomRequete).options[5].selected = true;
	    // Cache des select de 'Pas'
	    $('#select-pas_' + nomRequete).addClass('cacher');
	    var nouveautext = '( ' + graphData[nomRequete]['texte'] + ' )';
        $("#typeRecherche" + nomRequete).text(nouveautext);
	    $("#nbPoints_" + nomRequete).text(graphData[nomRequete]['nbPoints']);
        // Création du nouveau titre de la courbe en reprenant les paramètres initiaux
        lesSeries[nomRequete].name=graphData[nomRequete]['title'];
        // Affichage ou Cache des champs de compression
        cacheCompressionV2(nomRequete, graphData[nomRequete]['allPoints'], graphData[nomRequete]['nbPoints']);
    }
    // On affecte les nouvelles séries aux options du graph
    chartOptions.series 	= lesSeries;
    // Modification du tooltip
	checkTooltip();
    //chart1 = new Highcharts.StockChart(chartOptions);
	afficheChart(chartOptions);
    //  Pour chaque courbe : Mise en place des nouveaux points affichés pour la courbe
    for (var nomRequete = 0; nomRequete < nb_de_requetes; nomRequete++) {
        newSession(nomRequete,chart1.series[nomRequete].processedXData,chart1.series[nomRequete].processedYData);
    }
    // Cache de l'image loader
	$('#siteIpc').css('cursor','initial');
}


// Mise en place d'un graphique sauvegardé
function setOldGraphique(oldSeries, oldGraphique) {
    // Affichage de l'image loader ( = curseur attente )
    $('#siteIpc').css('cursor','wait');
    // Réinitialisation du graphique AJAX
    graphData2 = [];
    // Redéfinition des séries 
    var lesSeries = oldSeries;
	// Maj de l'indication de la série en cours d'affichage
	defineLastSerie(oldSeries);
    // Redéfinition du graphique initial comme graphique en cours d'affichage
    graphEnCours = oldGraphique;
    // Redéfinition du nombre de requêtes affichées dans le graphique (dont le nb de points > 0)
    nb_de_requetes_en_cours	= graphEnCours.length;
    // Suppression du groupement des courbes
    for (var numSeries = 0; numSeries < nb_de_requetes_en_cours; numSeries++) {
        lesSeries[numSeries].dataGrouping.enabled = false;
    }
    //  Selection des options 'completes' pour l'ensemble des courbes. et redéfinition du message initial
    for (var nomRequete = 0; nomRequete < nb_de_requetes; nomRequete++) {
        // Remise du champs Compression à 'Toutes les valeurs'
        document.getElementById('compression_'+nomRequete).options[0].selected = true;
        // Remise du select 'Pas' à 10 minutes
        document.getElementById('pas_'+nomRequete).options[5].selected = true;
        // Cache des select de 'Pas'
        $('#select-pas_'+nomRequete).addClass('cacher');
        var nouveautext = '( ' + oldGraphique[nomRequete]['texte'] + ' )';
        $("#typeRecherche"+nomRequete).text(nouveautext);
        $("#nbPoints_"+nomRequete).text(oldGraphique[nomRequete]['nbPoints']);
        // Création du nouveau titre de la courbe en reprenant les paramètres initiaux
        lesSeries[nomRequete].name = oldGraphique[nomRequete]['title'];
        // Affichage ou Cache des champs de compression
        cacheCompressionV2(nomRequete, oldGraphique[nomRequete]['allPoints'], oldGraphique[nomRequete]['nbPoints']);
    }
    // On affecte les nouvelles séries aux options du graph
    chartOptions.series	= lesSeries;
    // Modification du tooltip
	checkTooltip();
    // On réaffiche le graphique
    //chart1 = new Highcharts.StockChart(chartOptions);
	afficheChart(chartOptions);
    // Pour chaque courbe : Mise en place des nouveaux points affichés pour la courbe
    for (var nomRequete = 0; nomRequete < nb_de_requetes; nomRequete++) {
        newSession(nomRequete, chart1.series[nomRequete].processedXData, chart1.series[nomRequete].processedYData);
    }
    // Cache de l'image loader
    $('#siteIpc').css('cursor','initial');
}


// Fonction qui retourne un titre selon la valeur, le type et le pas indiqué
// Retourne soit un titre, soit le type de recherche
function nouveauTitre(message, unite, valeur, type_pas, num_pas, localisation, type_recherche) {
	var new_titre = '';
	if (type_recherche == 'Titre') {
		new_titre += message + ' - ' + unite + ' - (';
	}
	switch(valeur) {
    case 'average' :
		new_titre += traduire("periode.compression.moyenne");
	break;
    case 'high' :
		new_titre += traduire("periode.compression.maximum");
	break;
    case 'low' :
		new_titre += traduire("periode.compression.minimum");
	break;
	}
	$pluriel = '';
	if (num_pas > 1) {
	    new_titre += ' ' +  num_pas + ' ';
	    $pluriel = 's';
	}
	switch(type_pas) {
	case 'second' :
		new_titre += traduire("periode.duree.seconde") + $pluriel;
	break;
    case 'minute' :
		new_titre += traduire("periode.duree.minute") + $pluriel;	
	break;
	case 'hour' :
		new_titre += traduire("periode.duree.heure") + $pluriel;
	break;
	case 'day' :
		new_titre += traduire("periode.duree.jour") + $pluriel;
    break;
    case 'month':
		new_titre += traduire("periode.duree.mois");
    break;
	}

	if (type_recherche == 'Titre') {
	    new_titre += ' ' + traduire('label.sur') + ' ' + localisation + ')';
	}
	return(new_titre);
}

// Modifie le texte indiquant le type de recherche effectuée d'une fonction
function modifTexteRecherche(num, valeur, nbPoints) {
	var nouveautext = '( ' + valeur + ' )';
	$("#typeRecherche" + num).text(nouveautext);
	$("#nbPoints_" + num).text(nbPoints);
}

// Retourne la date courante
function date_heure() {
	date = new Date;
	annee = date.getFullYear();
	moi = date.getMonth();
	mois = ['Janvier', 'Février', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Août', 'Septembre', 'Octobre', 'Novembre', 'Décembre'];
	j 	= date.getDate();
	jour = date.getDay();
	jours = ['Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi'];
	h = date.getHours();
	if (h < 10) {
	    h = "0" + h;
	}
	m = date.getMinutes();
	if (m < 10) {
	    m = "0" + m;
	}
	s = date.getSeconds();
	if (s < 10) {
	    s = "0" + s;
	}
	resultat = jours[jour] + ' ' + j + ' ' + mois[moi] + ' ' + annee + ' ' + h + ':' + m + ':' + s;
	return resultat;
}


// Cache des champs de compression (sur la page affichage_graphique.html.twig) pour la requete num si tous les points ne sont pas récupérés ou si le nombre de données = 0
function cacheCompressionV2(num, allPoints, nbDonnees) {
    if ((allPoints == false)||(nbDonnees == 0)) {
        $('.'+num).css({"visibility":"hidden"});
        document.getElementById("tabCourbe_"+num+"_init").value="true";
    } else {
        $('.'+num).css({"visibility":"visible"});
        document.getElementById("tabCourbe_"+num+"_init").value="false";
    }
}

// Cache des champs de compression (sur la page affichage_graphique.html.twig) pour les requêtes dont tous les points ne sont pas affichés
function cacheCompression() {
    var idCss=0;
    // = lors de calcul de moyennes/max/min
   	for (j = 0; j < nombreCourbes; j++) {
        {% for i in 1..liste_req_pour_graphique|length %}
	    	var i = {{ i -1 }};
	    	if (i == j) {
                var nb_donnees = {{ liste_req_pour_graphique[i-1]['NbDonnees'] }};
                var texte = {{ liste_req_pour_graphique[i-1]['TexteRecherche']|json_encode|raw }};
                var allPoints = {{ liste_req_pour_graphique[i-1]['AllPoints']|json_encode|raw }};
                // Si tous les points ne sont pas affichés ou si le nombre de points max = 0, cache des champs de Compression
                if ((allPoints == false)||(nb_donnees == 0)) {
                    $('.'+idCss).css({"visibility":"hidden"});
                } else {
                    $('.'+idCss).css({"visibility":"visible"});
                }
                // Si le nombre de données = 0 => La requête n'est pas affichée dans la page / Pas de css
                if (nb_donnees == 0) {
                    idCss --;
                }
            	idCss ++;
	    	}
        {% endfor %}
	}
}

function affichePopupCompression() {
	$('#blockPiedDePage').removeClass('cacher');
    $('#lightboxFooter').removeClass('cacher');
	$('#pagePrincipaleBody').css('bottom', '120px');
}

function closePopupCompression() {
	$('#blockPiedDePage').addClass('cacher');
    $('#lightboxFooter').addClass('cacher');
	$('#pagePrincipaleBody').css('bottom', '0px');
}

function switchPopupCompression() {
	if ($('#lightboxFooter').hasClass('cacher')) {
	    affichePopupCompression();
	} else {
	    closePopupCompression();
	}
}

// Fonction qui affiche ou cache les infos bulles
function changeTooltip() {
    if ($('#infoTooltip').is(':checked')) {
		minimiseInfoTooltip();
    } else {
		checkTooltip();
		rechargeGraphique();
	}
}

function changeLegende() {
	if ($('#infoLegende').is(':checked')) {
		resetLegende('fill');
	} else {
		resetLegende('clear');
	}
	rechargeGraphique();
}

function minimiseInfoTooltip() {
	if ($('#infoTooltip').is(':checked')) {
		if ($('#infoTooltipSize').is(':checked')) {
			tooltipMax = false;
		} else {
			tooltipMax = true;
		}
		refreshInfoTooltip();
		rechargeGraphique();
	}
}

// Fonction qui recrée l'objet tooltip en enlevant les courbes désactivées dans la légende
function refreshInfoTooltip() {
    chartOptions.tooltip = {
        positioner: function (boxWidth, boxHeight, point) {
            return {x:140, y:35 };
        },
        backgroundColor: 'rgba(250,250,250,0.7)',
        borderColor: 'black',
		crosshair: true,
        borderRadius: 10,
        borderWidth: 2,
        useHTML: true,
        shared: true,
        formatter: function() {
            var s = '<b>' + Highcharts.dateFormat('%e %B %Y à %H:%M:%S (%L)', this.x) + '</b><br />';
            $.each(this.points, function(i, point) {
                s = s + recupCourbesPoints(point.x);
                return false;
            });
            return s;
        }
    };
}
/* Fonction qui récupére la liste des points de chaque courbe à une heure donnée
// Utilisée au survol de la courbe par tooltip
// Pour les courbes compressée par highChart,on n'affiche que la valeur du point lors du survol (car nous n'avons pas la liste des points dans une variable)
function recupCourbesPointsCompressed(horodatage) {
    var message = '';
    var tabSerie = Array();
	var $valeurPrecedente = 0;
    var $unite;
    var $color;
    var $name;

    $.each(chart1.series, function(index0) {
		$unite = unite[this.name];
		$color = this.color;
		$name = this.name;
		if ($.inArray(index0, tabTooltip) != -1) {
        	console.log(index0, ' - ', 'test2 ', this.name, ' - ', this.color, ' - ', unite[this.name]);
        	$.each(this.points, function(index1) {
				if (this.x > horodatage) {
					if (tooltipMax === true) {
						message = message + '- ' +  '<span style="color:'  + $color + '">' +  fctRemplaceCaracteres($name, $valeurPrecedente) + ' : ' + $valeurPrecedente + ' ' + $unite + '</span><br />';
					} else {
						message = message + '- ' + '<span style="color:' + $color + '">' + $valeurPrecedente + ' ' + $unite + '</span><br />';
					}
					return false;
				}
				$valeurPrecedente = Highcharts.numberFormat(this.y, nbDecimal, ",", " ");
        	});
		}
    });
	return(message);
}
*/



// Redessine le graphique avec les nouvelles options et resélectionne la période affichée
function rechargeGraphique() {
  	$.each(last_series, function(keySerie, valueSerie) {
		//	Si la série n'est pas dans le tableau tooltip, on ne l'affiche pas
		if ($.inArray(keySerie, tabTooltip) == -1) {
			last_series[keySerie].visible = false;
		} else {
			last_series[keySerie].visible = true;
		}
	});
	chartOptions.series = last_series;

	//chart1 = new Highcharts.StockChart(chartOptions);
	afficheChart(chartOptions);
	// Remise de la période demandée avant le clic sur Infos-Bulle
	// Permet d'afficher la bonne période en cas de zoom géré par highchart (cad zoom sans requête Ajax)
    if (tabZoom[indiceTabZoom]['all'] == true) {
        var chartExtrem = $('#container').highcharts();
        var xExt = chartExtrem.xAxis[0].setExtremes(tabZoom[indiceTabZoom]['debut'], tabZoom[indiceTabZoom]['fin'], true);
    }
}

// Mise en place d'une échelle personnalisée
function changeEchelle(type) {
	$numGraphique = 0;
	$num_axe = -1;
	axes_y = [];
	$mini = parseInt($('#echelleMin').val());
	$maxi = parseInt($('#echelleMax').val());
	$pasEchelle = parseInt($('#echellePas').val());
	// Réinitialisation du tableau des ordonnés
	axe_def = [];
    for (j = 0; j < nombreCourbes; j++) {
		// Parcours de chaque jeu de courbe
		//   Si il y a au moins une donnée dans la courbe on incrémente l'axe des ordonnés
    	{% for i in 1..liste_req_pour_graphique|length %}
            var i = {{ i-1 }};
	    	if (i == j) {
            	var nb_donnees = {{ liste_req_pour_graphique[i-1]['NbDonnees'] }};
            	if (nb_donnees == 0) {
                	$numGraphique--;
            	} else {
					// Si l'axe des ordonnés n'est pas encore défini
					if ($.inArray(graphData[$numGraphique]['unite'], axe_def) == -1) {
						// On défini le nouvel axeY
						$num_axe++;
						axe_def[$num_axe] = graphData[$numGraphique]['unite'];
                		if (($num_axe % 2) == 0) {
		    				if ($('#infoEchelle').is(':checked')) {
                    			axes_y[$num_axe] = {
                    	    		allowDecimals: true,
									endOnTick: true,
                                    maxPadding: 0.2,
                    	    		gridLineWidth: 0,
									min: $mini,
                                    max: $maxi,
									tickInterval: $pasEchelle,
                    	    		offset: 30,
                    	    		labels: {
                    	        		x: -20 * ($num_axe - 1),
                    	        		style: {
                    	        	    	color: les_couleurs[$numGraphique]
                    	        		}
                    	    		},
                    	    		title: {
                    	        		align: 'high',
                    	        		margin: -20,
                    	        		text: graphData[$numGraphique]['unite'],
                    	        		x: -20 * ($num_axe - 1),
                    	        		style: {
                    	        		    color: les_couleurs[$numGraphique]
                    	        		}
                    	    		}
                    			};
								//axes_y[$num_axe] = setAxe($numGraphique, $num_axe, true, 'left');
		    				} else {
								if (axe_def[$num_axe].toLowerCase() == 'bool') {
									axes_y[$num_axe] = setAxe($numGraphique, $num_axe, false, 'left');
								} else {
									axes_y[$num_axe] = setAxe($numGraphique, $num_axe, true, 'left');
								}
		    				}
                		} else {
		    				if ($('#infoEchelle').is(':checked')) {
                                axes_y[$num_axe] = {
                                    allowDecimals: true,
									endOnTick: true,
									maxPadding: 0.2,
                                    gridLineWidth: 0,
									min: $mini,
									max: $maxi,
									tickInterval: $pasEchelle,
                                    offset: 30,
                                    labels: {
                                        x: 20 * ($num_axe - 2),
                                        style: {
                                            color: les_couleurs[$numGraphique]
                                        }
                                    },
                                    title: {
                                        align: 'high',
                                        margin: -5,
										rotation: 270,
                                        text: graphData[$numGraphique]['unite'],
                                        x: 20 * ($num_axe - 2),
                                        style: {
                                            color: les_couleurs[$numGraphique]
                                        }
                                    },
									opposite: true
                                };
								//axes_y[$num_axe] = setAxe($numGraphique, $num_axe, true, 'right');
		    				} else {
                    	        if (axe_def[$num_axe].toLowerCase() == 'bool') {
									axes_y[$num_axe] = setAxe($numGraphique, $num_axe, false, 'right');
								} else {
									axes_y[$num_axe] = setAxe($numGraphique, $num_axe, true, 'right');
								}
		    				}
                		}
					}
	       	 		$numGraphique++;
            	}
	    	}
		{% endfor %}
	}
    chartOptions.yAxis = axes_y;
    chartOptions.series = last_series;
	// Mise à jour du graphique seulement si la box est cochée
	if (type == 'maj') {
	    if ($('#infoEchelle').is(':checked')) {
			//chart1 = new Highcharts.StockChart(chartOptions);
			afficheChart(chartOptions);
	    }
	} else {
        //chart1 = new Highcharts.StockChart(chartOptions);
		afficheChart(chartOptions);
	}
	// Remise de la période demandée avant le clic sur Infos-Bulle
    // Permet d'afficher la bonne période en cas de zoom géré par highchart (cad zoom sans requête Ajax)
	if (tabZoom[indiceTabZoom]['all'] == true) {
        var chartExtrem = $('#container').highcharts();
        var xExt = chartExtrem.xAxis[0].setExtremes(tabZoom[indiceTabZoom]['debut'], tabZoom[indiceTabZoom]['fin'], true);
    }
}

function setNewZoom(typeZoom) {
	// Récupération du zoom actuel
	switch(typeZoom) {
	case 'precedent':
		indiceTabZoom --;
		$('#indiceZoom').val(indiceTabZoom);
		//	Lors d'un clic sur précédent : Activation du zoom suivant si il n'est pas activé
		if ($('#zoomSuivant').hasClass('btn-disable')) {
            $('#zoomSuivant').removeClass('btn-disable');
        }
		if (indiceTabZoom == 0) {
        	$('#zoomPrecedent').addClass('btn-disable');
      	}
	break;
    case 'suivant':
		indiceTabZoom ++;
		$('#indiceZoom').val(indiceTabZoom);
		if ($('#zoomPrecedent').hasClass('btn-disable')) {
		    $('#zoomPrecedent').removeClass('btn-disable');
		}
		if (indiceTabZoom == compteElementObjet(tabZoom)-1) {
        	$('#zoomSuivant').addClass('btn-disable');
        }
	break;
	}
    if (typeof(tabZoom[indiceTabZoom]) != 'undefined') {
	    if (tabZoom[indiceTabZoom]['all'] == false) {
			// La recherche était faite en base si tous les points n'étaient pas récupérés : Affichage des series récupérées de la base
			setOldGraphique(tabZoom[indiceTabZoom]['series'], tabZoom[indiceTabZoom]['graphiques']);
	    } else {
	        // Modification des périodes directement sur le graphique
            var chartExtrem = $('#container').highcharts();
            var xExt = chartExtrem.xAxis[0].setExtremes(tabZoom[indiceTabZoom]['debut'], tabZoom[indiceTabZoom]['fin'], true);
        }
	}
}

// Supprime les caractères $ et £ 
// Création de la légende pour l'impression
function fctSupprimeCaracteres(texte, couleur) {
	var newTexte = null;
	// Extraction de l'information concernant le numéro du genre
	pattern_genre = /^.+?;/;
	newTexte = texte.replace(pattern_genre,'');
	// Modification des caractères spéciaux
	pattern = /=?\s?[\$£]/g;
	newTexte = newTexte.replace(pattern,'');
	if (couleur != null) {
		if ($('#legendeGraphique').html() == '') {
			$legende = $legende + "<span style='color:" + couleur + ";'>" +  newTexte + "</span><br />";
			$tabLegende.push("<span style='color:" + couleur + ";'>" +  newTexte + "</span><br />");
		}
	}
	return(newTexte);
}

function fctRemplaceCaracteres(texte, valeur, valeur2) {
	var newTexte = texte;
	// Récupération du numéro du genre du message
	var result = /^(.+?);/.exec(texte);
	var numGenre = RegExp.$1;
	// Extraction de l'information concernant le numéro du genre
	pattern_genre = /^.+?;/;
	newTexte = texte.replace(pattern_genre, '');
	// Si le numéro du genre est 1, 2 ou compris entre 110 et 139 le caractère $ est à remplacer par Activation / Désactivation sinon par la valeur
	if (numGenre == 1 || numGenre == 2 || (numGenre >= 110 && numGenre <= 139)) {
		// Modification du caractère $ par Activation ou désactivation
		pattern = /\$/;
		switch (parseInt(valeur)) {
		case 0: 
			newTexte = newTexte.replace(pattern, 'Désactivé : ');
			break;
		case 1:
			newTexte = newTexte.replace(pattern, 'Activé : ');	
			break;
		}
	} else {
		// Modification du caractère $ par la valeur 1 - Si la valeur ne contient pas de decimal on affiche que la partie entière
		pattern = /\$/;
		newTexte = newTexte.replace(pattern, isEntier(valeur));	
	}
	// Dans tous les cas le caractère £ est à remplacer par la valeur 2
	patternLivre = /£/;
	newTexte = newTexte.replace(patternLivre, isEntier(valeur2));
	return(newTexte);
}

// Retourne true si l'horaire de fin de période est > à la date max
function ajoutMaxDate(heureAVerifier, idLoc) {
	for (var key_idLoc in tabLastHorodatage) {
		if(key_idLoc == idLoc) {
			var result = /^(\d+)-(\d+)-(\d+) (\d+):(\d+):(\d+)/.exec(tabLastHorodatage[key_idLoc]);
			var annee = RegExp.$1;
			var mois = RegExp.$2-1;
			var jour = RegExp.$3;
			var heure = RegExp.$4;
			var minute = RegExp.$5;
			var seconde = RegExp.$6;
			var horaire = Date.UTC(annee, mois, jour, heure, minute, seconde, '000');
			if (horaire > heureAVerifier) {
				return(true);
			}
		}
	}
	return(false);
}


function newSession(numCourbe, tabCourbeX, tabCourbeY) {
	tableauRetourX = JSON.stringify(tabCourbeX);
	tableauRetourY = JSON.stringify(tabCourbeY);
	document.getElementById('tabCourbe_' + numCourbe + '_X').value = tableauRetourX;
	document.getElementById('tabCourbe_' + numCourbe + '_Y').value = tableauRetourY;
}


// AJAX : Fonction qui transmet une date de début et une date de fin ( correspondantes à la selection effectuée sur le graph )
// Et retourne les courbes à afficher
function newGraphs(datemin, datemax, choix) {
    $('#siteIpc').css('cursor','wait');
    graphData2 = [];
    les_nouvelles_series = [];
    lastGraphDate = null;
    firstGraphDate = null;
    firstGraphTime = null;
    lastGraphTime = null;
    if (xhr && xhr.readyState != 4) {
        // On attend la fin de la requete précédente
        xhr.abort();
        indiceTabZoom --;
		$('#indiceZoom').val(indiceTabZoom);
    }
    // On récupère la valeur, on temporise et on vérifie que la valeur est toujours la même
    xhr = getXHR();
    xhr.onreadystatechange = function() {
        if ((xhr.readyState == 4) && (xhr.status == 200)) {
        	// Récupération de la réponse envoyée par le serveur
            // On recoit une réponse au format json
            $nouvelleListe = $.parseJSON(xhr.responseText);
            // Remise du curseur au format de sélection
            $('#siteIpc').css('cursor','initial');
            var numGraphique = 0;
            for (i = 0; i < $nouvelleListe.length; i++) {
                var nb_donnees=$nouvelleListe[i]['NbDonnees'];
                if (nb_donnees == 0) {
                    numGraphique--;
                } else {
                    graphData2[numGraphique] = [];
                    graphData2[numGraphique]['Donnees'] = $nouvelleListe[i]['Donnees'];
                    // Récupération de la première date du graph
                    var firstTime = graphData2[numGraphique]['Donnees'][0]['horodatage'];
                    var reg = /^(\d+)-(\d+)-(\d+) (\d+):(\d+):(\d+)/.exec(firstTime);
                    var firstDate = Date.UTC(RegExp.$1, RegExp.$2-1, RegExp.$3, RegExp.$4, RegExp.$5, RegExp.$6, graphData2[numGraphique]['Donnees'][0]['cycle']);
                    // Récupération de la dernière date du graph
                    var lastTime = graphData2[numGraphique]['Donnees'][nb_donnees-1]['horodatage'];
                    // Expression régulière sur l'horodatage pour la création d'une date UTC avec prise en compte des cycle comme millisecondes
                    var reg = /^(\d+)-(\d+)-(\d+) (\d+):(\d+):(\d+)/.exec(lastTime);
                    var lastDate = Date.UTC(RegExp.$1, RegExp.$2-1, RegExp.$3, RegExp.$4, RegExp.$5, RegExp.$6, graphData2[numGraphique]['Donnees'][nb_donnees-1]['cycle']);
                    if (! lastGraphDate) {
                        lastGraphTime = lastTime;
                        lastGraphDate = lastDate;
                    } else if (lastDate > lastGraphDate) {
                        lastGraphTime = lastTime;
                        lastGraphDate = lastDate;
                    }
                    // Récupération de la première date du graph
                    if (! firstGraphDate) {
                        firstGraphTime = firstTime;
                        firstGraphDate = firstDate;
                    } else if (firstDate < firstGraphDate) {
                        firstGraphTime = firstTime;
                        firstGraphDate = firstDate;
                    }
                }
                numGraphique++;
            }
            // Définition des paramètres de début et de fin de graphique
            graphData2['firstTime'] = firstGraphTime;
            graphData2['lastTime'] = lastGraphTime;
            numGraphique = 0;
            for (i = 0; i < $nouvelleListe.length; i++) {
                var nb_donnees = $nouvelleListe[i]['NbDonnees'];
                if (nb_donnees == 0) {
                    numGraphique--;
                } else {
                    graphData2[numGraphique] = [];
                    graphData2[numGraphique]['Donnees'] = $nouvelleListe[i]['Donnees'];
                    // Le texte correspond à la recherche effectuée : Tous les points, Moyenne, Max, Min
                    graphData2[numGraphique]['texte'] = $nouvelleListe[i]['TexteRecherche'];
                    graphData2[numGraphique]['allPoints'] = $nouvelleListe[i]['AllPoints'];
                    graphData2[numGraphique]['localisation'] = $nouvelleListe[i]['Localisation'];
                    graphData2[numGraphique]['idLocalisation'] = $nouvelleListe[i]['id_localisations'];
					graphData2[numGraphique]['numeroGenre'] = $nouvelleListe[i]['numeroGenre'];
                    graphData2[numGraphique]['data'] = formatabHigh(graphData2[numGraphique],firstGraphDate,lastGraphDate);
                    graphData2[numGraphique]['title'] = graphData2[numGraphique]['numeroGenre'] + ';' + titre;
                    graphData2[numGraphique]['legende'] = fctSupprimeCaracteres(titre, null);
                    graphData2[numGraphique]['unite'] = unitt['unite'];
                    graphData2[numGraphique]['nbPoints'] = nb_donnees;
                    // Définition des différentes séries du graphique
                    // Si un axe identique est déjà définit on ne le redéfinit pas
                    if ($.inArray(unitt['unite'], axe_def) == -1) {
                        num_axe++;
                        axe_def[num_axe] = unitt['unite'];
                        if ((num_axe % 2) == 0) {
                            if (axe_def[num_axe].toLowerCase() == 'bool') {
								axes_y[num_axe] = {
                                    allowDecimals: false,
                                    endOnTick: true,
                                    maxPadding: 0.2,
                                    offset: 30,
                                    gridLineWidth: 0,
                                    labels: {
                                        x: -10 * (num_axe - 1),
                                        style: {
                                            color: les_couleurs[numGraphique]
                                        }
                                    },
                                    title: {
                                        align: 'high',
                                        text: graphData2[numGraphique]['unite'],
                                        margin: -20,
                                        x: -10 * (num_axe - 1),
                                        style: {
                                            color: les_couleurs[numGraphique]
                                        }
                                    }
                                };
							} else {
                            	axes_y[num_axe] = {
                            		allowDecimals: true,
                            	    endOnTick: true,
				    				maxPadding: 0.2,
                                	offset: 30,
                                	gridLineWidth: 0,
                                	labels: {
                                	    x: -10 * (num_axe - 1),
                                	    style: {
                                	        color: les_couleurs[numGraphique]
                                	    }
                                	},
                                	title: {
                                	    align: 'high',
                                	    text: graphData2[numGraphique]['unite'],
                                	    margin: -20,
                                	    x: -10 * (num_axe - 1),
                                	    style: {
                                	        color: les_couleurs[numGraphique]
                                	    }
                                	}
                            	};
							}
                        } else {
                            if (axe_def[num_axe].toLowerCase() == 'bool') {
                                axes_y[num_axe] = {
                                    allowDecimals: false,
                                    endOnTick: true,
                                    maxPadding: 0.2,
                                    offset: 30,
                                    gridLineWidth:0,
                                    labels: {
                                        x: 10 * (num_axe - 2),
                                        style: {
                                            color: les_couleurs[numGraphique]
                                        }
                                    },
                                    title: {
                                        align: 'high',
                                        rotation: 270,
                                        text: graphData2[numGraphique]['unite'],
                                        margin: -5,
                                        x: 10 * (num_axe - 1),
                                        style:{
                                            color: les_couleurs[numGraphique]
                                        }
                                    },
                                    opposite: true
                                };
							} else {
                            	axes_y[num_axe] = {
                            	    allowDecimals: true,
                            	    endOnTick: true,
				    				maxPadding: 0.2,
                            	    offset: 30,
                            	    gridLineWidth:0,
                            	    labels: {
                            	        x: 10 * (num_axe - 2),
                            	        style: {
                            	            color: les_couleurs[numGraphique]
                            	        }
                            	    },
                            	    title: {
                            	        align: 'high',
                            	        rotation: 270,
                            	        text: graphData2[numGraphique]['unite'],
                            		    margin: -5,
                                	    x: 10 * (num_axe - 1),
                               	     	style:{
                               	         	color: les_couleurs[numGraphique]
                               	     	}
                                	},
                                	opposite: true
                            	};
							}
                        }
                    }
                    les_nouvelles_series[numGraphique] = {
                        // Affichage de tous les points
                        name: graphData2[numGraphique]['title'],
                        zIndex: 5-numGraphique,
                        yAxis: $.inArray(unitt['unite'], axe_def),
                        color: les_couleurs[numGraphique],
                        data: graphData2[numGraphique]['data'],
                        step: true,
                        tooltip: {
                            valueDecimals: nbDecimal
                        },
                        marker: {
                            enabled: false
                        },
                        dataGrouping: {
                            enabled: false,
                            forced : true,
                            units: [
                                [
                                    'minute',
                                    [10]
                                ]
                            ]
                        }
                    };
                    // Tableau Unite : unite(titre) => unite */
                    unite[graphData2[numGraphique]['title']] = graphData2[numGraphique]['unite'];
                    titre = '';
                    unitt['unite'] = '';
                    // Modification des messages de la page affichage_graphique.html.twig indiquant la recherche en cours
                    modifTexteRecherche(numGraphique, $nouvelleListe[i]['TexteRecherche'], $nouvelleListe[i]['NbDonnees']);
                    // Affichage ou cache des choix de compressions
                    cacheCompressionV2(numGraphique, $nouvelleListe[i]['AllPoints'], $nouvelleListe[i]['NbDonnees']);
                }
                numGraphique++;
            }
            // Redéfinition du nombre de requêtes affichées dans le graphique (dont le nb de points > 0)
            nb_de_requetes_en_cours = numGraphique;
            // Remise de la sélection des boutons radio sur l'option 'Complete'
            for (var nomRequete = 0; nomRequete < nb_de_requetes; nomRequete++) {
                document.getElementById('compression_'+nomRequete).options[0].selected = true;
            }
            // Si le choix de compression est différent de null c'est qu'une compression sur toutes les données a été demandée : Resélection du bouton radio correspondant pour toutes les courbes
            if (choix != null) {
                $valeur = choix.split('_');
                for (var numOption = 0; numOption < document.getElementById('compression_' + nomRequete).options.length; numOption++) {
                    if (document.getElementById('compression_'+nomRequete).options[numOption].value == $valeur[0]) {
                        document.getElementById('compression_'+nomRequete).options[numOption].selected = true;
                    }
                }
            }
            chartOptions.series = les_nouvelles_series;
            // Détermination du dernier graphique affiché :
            graphEnCours = graphData2;
            // Définition des dernières séries affichées :
			defineLastSerie(les_nouvelles_series);
            // Modification du tooltip
			checkTooltip();
            // Création du nouveau graphique
            //chart1 = new Highcharts.StockChart(chartOptions);
			afficheChart(chartOptions);
            // Pour chaque courbe : Mise en place des nouveaux points affichés pour la courbe
            for (var nomRequete = 0; nomRequete < nb_de_requetes; nomRequete++) {
                newSession(nomRequete, chart1.series[nomRequete].processedXData, chart1.series[nomRequete].processedYData);
            }
            // On ajoute les séries au tableau si toutes les données du graph précédent ne sont pas récupérées et au départ (DOUBLON AVEC TAB INITIAL)
			defineTableauZoom(last_series,graphEnCours);
            return(0);
        }
    }
    // Envoye du texte au serveur
    // On établit la connexion
    xhr.open("POST","{{ path('ipc_graphListReqSelect') }}", true);
    // On définit l'entête pour l'envoi de donnée par la méthode POST
    xhr.setRequestHeader("Content-Type", "application/x-www-form-urlencoded");
    if (choix != null) {
        var datas = "datemin=" + datemin + "&datemax=" + datemax + "&choix=" + choix;
    } else {
        var datas = "datemin=" + datemin + "&datemax=" + datemax;
    }
    // On envoi les données
    xhr.send(datas);
}

/**
* fonction qui récupére la liste de données retournées par les requêtes et retourne un tableau formaté avec des dates au millieme de seconde
*/
function formatabHigh(tableau, datemin, datemax) {
	unitt['y'] = 0;
	var graphDataTmp = [];
	for (var key = 0; key < tableau['Donnees'].length; key++) {
		var valeur1 = parseFloat(tableau['Donnees'][key]['valeur1']);
		var valeur2 = parseFloat(tableau['Donnees'][key]['valeur2']);
		var sizeDecimal = parseInt(valeur1).toString().length;
		if (sizeDecimal > unitt['y']) {
			unitt['y'] = sizeDecimal;
		}
		var result = /^(\d+)-(\d+)-(\d+) (\d+):(\d+):(\d+)/.exec(tableau['Donnees'][key]['horodatage']);
		var annee = RegExp.$1;
		var mois = RegExp.$2-1;
		var jour = RegExp.$3;
		var heure = RegExp.$4;
		var minute = RegExp.$5;
		var seconde = RegExp.$6;
		var horaire = Date.UTC(annee,mois,jour,heure,minute,seconde,tableau['Donnees'][key]['cycle']);
		var tmp_titre = null;
		// Traitement spécial pour un tableau d'un point
		if (tableau['Donnees'].length == 1) {
			// Si l'horaire du point est > date min on ajoute un point en date min
			if (horaire > datemin) {
				graphDataTmp.push({'x':datemin, 'y':valeur1, 'z':valeur2});
			}
			// Ajout du point du graph
			graphDataTmp.push({'x':horaire, 'y':valeur1, 'z':valeur2});
			// Si l'horaire du point est < date max on ajoute un point en date max : SSS la datemax est inférieur ou égale à la derniere date de récupération des données de la localisation
			if (horaire < datemax) {
				graphDataTmp.push({'x':datemax, 'y':valeur1, 'z':valeur2});
			}
		} else {
			// Premiere et Dernière date identique pour tous
			// Lors du parcours des données.
			// Si la première données à un horaire < à l'horaires minimum du graphique i
			if ((key ==0 ) && (horaire > datemin)) {
				// Enregistrement de la datemin comme date minimale et de la valeur en cours d'analyse comme valeur à la date min
				graphDataTmp.push({'x':datemin, 'y':valeur1, 'z':valeur2});
				// Enregistrement de l'horaire en cours d'analyse
				graphDataTmp.push({'x':horaire, 'y':valeur1, 'z':valeur2});
			} else if ((key == (tableau['Donnees'].length - 1)) && (datemax > horaire)) {
				// Enregistrement de l'horaire en cours d'analyse
				graphDataTmp.push({'x':horaire, 'y':valeur1, 'z':valeur2});
				// Enregistrement de la datemax comme date maximale  et de la valeur en cours d'analyse comme valeur à la date max
				if (ajoutMaxDate(datemax, tableau['idLocalisation']) == true) {
					graphDataTmp.push({'x':datemax, 'y':valeur1, 'z':valeur2});
				}
			} else {
				graphDataTmp.push({'x':horaire, 'y':valeur1, 'z':valeur2});
			}
		}
		// Création du titre : Message du Module - unite - (type de recherce)
		if (! titre) {
			tmp_titre = traduireMessage(tableau['Donnees'][key]['message']);
			// Ajout du type de la recherche de la courbe / Complete / Moyenne par pas / Min, Max par pas
			var match = tableau['texte'].match(/moyenne par/);
			if (match != null) {
				var pasCompression = /^.+?par (.+?)$/.exec(tableau['texte']);
				var pas = traduire(RegExp.$1);
				var compression = traduire('periode.compression.moyenne') + ' ' + pas;
			} else {
				var match = tableau['texte'].match(/maximum par/);
				if (match != null) {
					var pasCompression = /^.+?par (.+?)$/.exec(tableau['texte']);
					var pas = traduire(RegExp.$1);
					var compression = traduire('periode.compression.maximum') + ' ' + pas;
				} else {
					var match = tableau['texte'].match(/minimum par/);
					if (match != null) {
						var pasCompression = /^.+?par (.+?)$/.exec(tableau['texte']);
						var pas = traduire(RegExp.$1);
						var compression = traduire('periode.compression.minimum') + ' ' + pas;
					} else {
						var compression = traduire('periode.compression.complete');
					}
				}
			}
			unitt['unite'] = tableau['Donnees'][key]['unite'];
			titre = tmp_titre + ' (' + compression + ' ' + traduire('label.sur') + ' ' + tableau['localisation'] + ')';
		}
	}
	return graphDataTmp;
}


// Fonction qui récupére la liste des points de chaque courbe à une heure donnée
// Utilisée au survol de la courbe par tooltip
// Pour les courbes compressée par highChart,on n'affiche que la valeur du point lors du survol (car nous n'avons pas la liste des points dans une variable)
// L'objet de cette fonction est d'afficher la valeur des points de toutes les courbes, même si ils ne sont pas sous le curseur de la souris. Pour cela, pour les séries n'ayant pas de points sous le curseur, ...
// on récupère l'emplacementde la série (axes x) et on affiche les valeur des points précédents
function recupCourbesPoints(curseur_horodatage) {
    var $horodatage = curseur_horodatage;
    var $message = '';
    var $valeurPrecedente = 0;
	var $valeur2Precedente = 0;
    var $unite;
    var $color;
    var $name;

	$.each(chartOptions.series, function(index_serie) {
		$name = this.name;
		$unite = unite[$name];
		$color = this.color;
		// Si la série parcourue est dans la liste des séries à afficher
        if ($.inArray(index_serie, tabTooltip) != -1) {
			// On parcours tous les points de la série
			$.each(chartOptions.series[index_serie].data, function(index_data, point) {
				$x = point['x'];
				$y = point['y'];
				$z = point['z'];
				if ($x > $horodatage) {
					if (tooltipMax === true) {
                        $message = $message + '- ' +  '<span style="color:'  + $color + '">' + fctRemplaceCaracteres($name, $valeurPrecedente, $valeur2Precedente) + ' : ' + isEntier($valeurPrecedente) + ' ' + $unite + '</span><br />';
                    } else {
                        $message = $message + '- ' + '<span style="color:' + $color + '">' + isEntier($valeurPrecedente) + ' ' + $unite + '</span><br />';
                    }
                    return false;
                }
                $valeurPrecedente = Highcharts.numberFormat($y, nbDecimal, ",", " ");
				$valeur2Precedente = Highcharts.numberFormat($z, nbDecimal, ",", " ");
            });
		}
	});
    return($message);
}


function unites() {
}

// Définition des dernières séries affichées
function defineLastSerie(series) {
	// On place le contenu de la dernière série affichées dans la page html pour pouvoir la récupérer après un téléchargement
	json_series = JSON.stringify(series);
	$('#derniereSerie').val('');
	$('#derniereSerie').val(json_series);
	last_series = series;
	return(0);
}

function defineTableauZoom(last_series, graphEnCours) {
	// On place le contenu du tableau des zooms dans la page html pour pouvoir le récupérer après un téléchargement
	tmpTabGraph = [];
	for (numreq = 0; numreq < graphEnCours.length; numreq++) {
		tmpTabGraph[numreq]	= new Object();
		tmpTabGraph[numreq]['texte'] = graphEnCours[numreq]['texte'];
		tmpTabGraph[numreq]['nbPoints']	= graphEnCours[numreq]['nbPoints'];
		tmpTabGraph[numreq]['title'] = graphEnCours[numreq]['title'];
		tmpTabGraph[numreq]['allPoints'] = graphEnCours[numreq]['allPoints'];
	}
	tabZoom[indiceTabZoom]['series'] = last_series;
	tabZoom[indiceTabZoom]['graphiques'] = tmpTabGraph;
	$('#tableauZoom').val(JSON.stringify(tabZoom));
	return(0);
}

function compteElementObjet(objet) {
	var nbProprietes = 0;
	for (var i in objet) {
		if (objet.hasOwnProperty(i)) {
			nbProprietes += 1;
		}
	}
	return(nbProprietes)
}

// On vérifie si la courbe doit être affichée dans le tooltip ou pas
function fillTabTooltip(serieIndex, visibilite) {
	//	Si la visibilité de la courbe AVANT LE CLIC = false :  La nouvelle visibilité = true et La courbe doit être affichée. Si elle n'est pas dans le tableau, on l'ajoute
	if (visibilite === false) {
		if ($.inArray(serieIndex, tabTooltip) == -1) {
			tabTooltip.push(serieIndex);
		}
	} else {
		//	La courbe ne doit pas être affichée et doit être retirée du tableau si elle y est
		var indexSerieASupp = $.inArray(serieIndex, tabTooltip);
		if (indexSerieASupp != -1 ) {
            tabTooltip.splice(indexSerieASupp, 1);
        }
	}
	return(0);
}

// Enlevement de toutes les courbes affichées dans la légende
function resetLegende($type) {
	// Suppression de toutes les courbes du tableau des courbes à afficher
	if ($type == 'clear') {
		for(i=0; i < last_series.length; i++) {
			fillTabTooltip(i, true)
    	}
	}
	//	Ajout de toutes les courbes dans le tableaux des courbes à afficher sur le graphique
	if ($type == 'fill') {
        for(i=0; i < last_series.length; i++) {
            fillTabTooltip(i, false)
        }
	}
}

// Fonction qui supprime l'information tooltip si la checkbox n'est pas cochée.
function checkTooltip() {
    if (! $('#infoTooltip').is(':checked') ) {
        chartOptions.tooltip = {
            positioner: function (boxWidth, boxHeight, point) {
                return {x:140, y:35 };
            },
            backgroundColor: 'white',
            borderColor: 'white',
            crosshair: true,
            borderWidth: 0,
            useHTML: true,
            shared: true,
            formatter: function() {
                return "";
            }
		}
	}
}

// Fonction appelée lors du clic sur le bouton d'impression
// Permet d'afficher la légende complète
function preimpression() {
	$('#container').addClass('containerImpression');
    var minData = chart1.xAxis[0].getExtremes().min;
    var maxData = chart1.xAxis[0].getExtremes().max;
	var lesSeries = last_series;
	chartOptions.series = lesSeries;
	$objScrollbar = chartOptions.scrollbar;
	chartOptions.scrollbar = {
		enabled: false
	};
    chartOptions.chart.marginBottom = 25;
	// Sauvegarde de l'objet légende pour restauration après l'impression
	$objLegend = chartOptions.legend;
	chartOptions.legend = {
		enabled: false
	};
	//chart1 = new Highcharts.StockChart(chartOptions);
	afficheChart(chartOptions);

	// Création de la légende html qui ne comporte que les courbes affichées
	var $tabLegendeHtml = [$tabLegende[0]];
 	for(i=0; i < chart1.series.length; i++) {
		if(chart1.series[i].visible == true) {
			$tabLegendeHtml.push($tabLegende[i + 1]);
		}
    }
	$legendeHtml = $tabLegendeHtml.join('');
	$('#legendeGraphique').html($legendeHtml);

	var chartExtrem = $('#container').highcharts();
	var xExt = chartExtrem.xAxis[0].setExtremes(minData, maxData);
	return 0;
}

function impression_graphique() {
    setTimeout(function() {
        print();
		finimpression();
    }, 2000);
}

function finimpression() {
	$('#container').removeClass('containerImpression');
    var minData = chart1.xAxis[0].getExtremes().min;
    var maxData = chart1.xAxis[0].getExtremes().max;
    var lesSeries = last_series;
	chartOptions.series = lesSeries;
	chartOptions.scrollbar = $objScrollbar;
	chartOptions.chart.marginBottom = $chartMarginBottom;
    chartOptions.legend = $objLegend;
	//chart1 = new Highcharts.StockChart(chartOptions);
	afficheChart(chartOptions);
	var chartExtrem = $('#container').highcharts();
    var xExt = chartExtrem.xAxis[0].setExtremes(minData, maxData);
    return 0;
}

// fonction qui permet de traduire le message de titre
function traduireMessage($message) {
    var $urlTraduction = $('#lien_url_ajax').attr('data-urlTraduction');
	var $messageTraduit = $message;
    $.ajax({
        method: 'get',
		async: false,
        url: $urlTraduction,
        data: {'message':$message},
        success: function($data, $textStatus) {
			$messageTraduit = $data;
        },
        error: function($data, $textStatus, $error) {
			$messageTraduit = $message;
        }
    });
	return $messageTraduit;
}


function isEntier(nombre) {
    // Transformation de la virgule en point
    pattern_virgule = /,/;
    nombre = nombre.replace(pattern_virgule, '\.');
	// Suppression des espaces
	pattern_espace = /\s/;
	nombre = nombre.replace(pattern_espace, '');
    var entier = parseInt(nombre);
    var result = parseFloat(nombre) - entier;
    if (result == 0) {
       return entier;
   } else {
       return nombre;
   }
}

</script>
