<script type='text/javascript'>
var $containerWidth;
var $tooltipWidth;
var $itemStyleWidth;
var $itemWidth;
var $urlSetTabZoom = $('#container_live').attr('data-path');
var $tabZoom;
var $windowSize;
var $windowWidth;
var chartOptions;
var saveExtremes;
// Variable du nombre de courbes
var nombreCourbes = {{ donneesGraphique|length }};

var zoomButton
var containerTop;
var hauteurContainer;
var hauteurContainerMobile;
var minHeight = 500;

var $legendSizeMobile = nombreCourbes * 55;
var $hauteurGraphiqueMobile = 500;

$(document).ready(function () {
    if (pageLive === 'Graphique') {
        saveExtremes = false;
        var $infosBulles = {{ infosBulles | json_encode | raw  }};
        if ($infosBulles === 'checked') {
            $('#infoTooltip').prop('checked', true);
        }
        $tabZoom = ({{ tabZoom | json_encode | raw }});
        $windowSize = $(window).height(); // WINDOW : Hauteur de la fenêtre
        // Redéfinition des options d'affichage graphique
        $windowWidth = $(window).width();
        $containerWidth = $('#container_live').width() - 50;
		$tooltipWidth = $containerWidth * 4 / 5;
        if ($windowWidth > 1000) {
			containerTop = $('#container_live').offset().top;
        	hauteurContainer = $windowSize - containerTop - 33;
        	if (hauteurContainer < minHeight) {
        	    hauteurContainer = minHeight;
        	}
			$('#container_live').height(hauteurContainer);
            setChartOptions();
            $itemWidth = $containerWidth / 2;
            $itemStyleWidth = $containerWidth / 2 - 30;
        } else {
			hauteurContainerMobile = $hauteurGraphiqueMobile + $legendSizeMobile;
			$('#container_live').height(hauteurContainerMobile);
            setChartOptionsLive();
            $itemWidth = $containerWidth;
            $itemStyleWidth = $containerWidth - 30;
        }
        setHighchart();
        $(document).on("keydown", disableF5);
    }
});

// Au redimensionnement de la page
$(window).resize(function () {
    if (pageLive === 'Graphique') {
        if ($(window).width() !== $windowWidth) {
            // Redéfinition de $windowSize dans la fonction resize
            $windowSize = $(window).height();
            // Redéfinition des options d'affichage graphique
            $windowWidth = $(window).width();
            saveExtremes = false;
            $containerWidth = $('#container_live').width() - 50;
	    $tooltipWidth = $containerWidth * 4 / 5;
            if ($windowWidth > 1000) {
            	containerTop = $('#container_live').offset().top;
            	hauteurContainer = $windowSize - containerTop - 33;
            	if (hauteurContainer < minHeight) {
            	    hauteurContainer = minHeight;
            	}
				$('#container_live').height(hauteurContainer);
                setChartOptions();
                $itemWidth = $containerWidth / 2;
                $itemStyleWidth = $containerWidth / 2 - 30;
            } else {
				hauteurContainerMobile = $hauteurGraphiqueMobile + $legendSizeMobile;
				$('#container_live').height(hauteurContainerMobile);
                setChartOptionsLive();
                $itemWidth = $containerWidth;
                $itemStyleWidth = $containerWidth - 30;
            }
            setHighchart();
        }
    }
});

function disableF5(e) { 
	if ((e.which || e.keyCode) === 116) { 
		e.preventDefault(); 
	}
}

// Variable du tableau des données
var tabDesDonnees = ({{ donneesGraphique|json_encode|raw }});
// Variable premiere donnée
var tabFirstData = [];
var tabLastData	= [];
// Tableau des séries
var tabSeries = [];
// Nombre de points max par courbe (Changer également la variable $limit_sql_messages dans le controller SupervisionController
var nbPointsMaxParCourbe = 5000;
// Variable de début et de fin de période du graphique
var debutPeriode;
var finPeriode;
// Parcours des deux courbes
var numDonnee;
var nombreDeDonnees;
var horaireUtc;
var valeur;

// Parcours des deux courbes
for(indexIdModule in tabDesDonnees) {
	tabDesDonnees[indexIdModule]['serie'] = new Array();
	var numDonnee = 0;
	//  Parcours de chaque donnée du tableau des données
	var nombreDeDonnees = tabDesDonnees[indexIdModule]['donnees'].length;
	for(indexNumeroDonnee in tabDesDonnees[indexIdModule]['donnees']) {
		var horaireUtc  = createUtcData(tabDesDonnees[indexIdModule]['donnees'][indexNumeroDonnee]['horodatage'],tabDesDonnees[indexIdModule]['donnees'][indexNumeroDonnee]['cycle']);
		var valeur = parseFloat(tabDesDonnees[indexIdModule]['donnees'][indexNumeroDonnee]['valeur1']);
		//      Ajout d'une valeur à la série de la courbe
		if(numDonnee < nbPointsMaxParCourbe) {
			tabDesDonnees[indexIdModule]['serie'].unshift([horaireUtc,valeur]);
		}
		//      Récupération de la premiere donnée de chaque courbe
		if(numDonnee == 0) {
			tabLastData[indexIdModule] = [];
			tabLastData[indexIdModule]['horodatageUtc'] = horaireUtc;
			tabLastData[indexIdModule]['valeur'] = valeur;
		}
		//      Récupération de la dernière valeur du graphique ( La variable numDonnee débute à 0 d'où la recherche == nbPointsMaxParCourbe-1 )
		if((numDonnee == nombreDeDonnees-1)||(numDonnee == nbPointsMaxParCourbe-1)) {
			tabFirstData[indexIdModule] = [];
			tabFirstData[indexIdModule]['horodatageUtc'] = horaireUtc;
			tabFirstData[indexIdModule]['valeur'] = valeur;
		}
		//      Sortie de boucle lorsque le nombre de points récupéré atteind la valeur maximale définie
		if(numDonnee == nbPointsMaxParCourbe-1) {
			break;
		}
		numDonnee ++;
	}
}

// Ajustage des courbes pour qu'elles débutent et se terminent au même horodatage
tabDesDonnees = addFirstAndLastData(tabDesDonnees,tabFirstData,tabLastData);
// Rappel : indexIdModule = identifiant des modules analysés (ex 390 et 158)
// Récupération des unités
var tabUnites = [];
var tabObjYAxis = [];
var tabObjYAxisLive = [];
var numSerie = 0;
var numAxeY = -1;

for (indexIdModule in tabDesDonnees) {
	if ($.inArray(tabDesDonnees[indexIdModule]['unite'],tabUnites) == -1) {
		tabUnites.push(tabDesDonnees[indexIdModule]['unite']);
		// Incrémentation du numéro indiquant le nombre d'axes Y
		numAxeY += 1;
		// Recherche du titre à affecter à l'axe
		var titreAxe = '';
		for (key in tableauDesUnites) {
			if (key == tabDesDonnees[indexIdModule]['unite']) {
				titreAxe = tableauDesUnites[key];
			}
		}
		// Ajout d'un champs au tableau de l'objet yAxis
		if (numAxeY % 2 === 0) {
            if (tabDesDonnees[indexIdModule]['unite'].toLowerCase() == 'bool') {
                objYAxis = {
                    allowDecimals: false,
                    gridLineWidth: 0,
                    endOnTick: true,
                    title: {
                        x: -30 * numAxeY,
                        text: titreAxe,
                        style: {
                            color: tabColor[numSerie]
                        }
                    },
                    labels: {
                        x: -30 * numAxeY,
                        format: '{value} '+tabDesDonnees[indexIdModule]['unite'],
                        style: {
                            color: tabColor[numSerie]
                        }
                    },
                    opposite: false
                }
            } else {
				objYAxis = {
					allowDecimals: true,
					gridLineWidth: 0,
					endOnTick: true,
					title: {
						x: -30 * numAxeY,
						text: titreAxe,
						style: {
							color: tabColor[numSerie]
						}
					},
					labels: {
						x: -30 * numAxeY,
						format: '{value} '+tabDesDonnees[indexIdModule]['unite'],
						style: {
							color: tabColor[numSerie]
						}
					},
					opposite: false
				}
			}
		} else {
            if (tabDesDonnees[indexIdModule]['unite'].toLowerCase() == 'bool') {
            	objYAxis = {
                	allowDecimals: false,
                    gridLineWidth: 0,
                    endOnTick: true,
                    title: {
                        x: 30 * numAxeY,
                        text: titreAxe,
                        style: {
                            color: tabColor[numSerie]
                        }
                    },
                    labels: {
                        x: 30 * numAxeY,
                        format: '{value} '+tabDesDonnees[indexIdModule]['unite'],
                        style: {
                            color: tabColor[numSerie]
                        }
                    },
                    opposite: true
                }
            } else {
				objYAxis = {
					allowDecimals: true,
					gridLineWidth: 0,
					endOnTick: true,
					title: {
						x: 30 * numAxeY,
						text: titreAxe,
						style: {
							color: tabColor[numSerie]
						}
					},
					labels: {
						x: 30 * numAxeY,
						format: '{value} '+tabDesDonnees[indexIdModule]['unite'],
						style: {
							color: tabColor[numSerie]
						}
					},
					opposite: true
				}
			}
		}
		tabObjYAxis.push(objYAxis);
		tabObjYAxisLive.push({gridLineWidth:0, labels:{enabled:false},visible:false});
		tabSeries[numSerie] = {
			name: tabDesDonnees[indexIdModule]['message'] + ' (' + tabDesDonnees[indexIdModule]['unite'] + ')',
			yAxis: numAxeY,
			data: tabDesDonnees[indexIdModule]['serie']
		};
	}else{
		tabSeries[numSerie] = {
			name: tabDesDonnees[indexIdModule]['message'] + ' (' + tabDesDonnees[indexIdModule]['unite'] + ')',
			yAxis: $.inArray(tabDesDonnees[indexIdModule]['unite'],tabUnites),
			data: tabDesDonnees[indexIdModule]['serie']
		};
	}
	numSerie ++;
}


function createUtcData(horodatage,cycle) {
	var result = /^(\d+)-(\d+)-(\d+) (\d+):(\d+):(\d+)/.exec(horodatage);
	var annee = RegExp.$1;
	var mois  = RegExp.$2-1;
	var jour  = RegExp.$3;
	var heure = RegExp.$4;
	var minute = RegExp.$5;
	var seconde = RegExp.$6;
	var horaire = Date.UTC(annee,mois,jour,heure,minute,seconde,cycle);
	return(horaire);
}


//  Fonction ajoutDeLaPremière valeur de chaque courbe pour que les courbes débutent au début du graphique
function addFirstAndLastData(tableauDonnees, tableauFirstData, tableauLastData) {
	firstIdModule = null;
	firstHoraire = null;
	lastIdModule = null;
	lastHoraire = null;
	// Création du premier point des courbes
	firstHoraire = createUtcData(premierHorodatage,'00');
	debutPeriode = firstHoraire;
	for (indexIdModule in tableauDonnees) {
		//  Modification de l'horodatage si le premier est < horodatage de début
		if (tableauDonnees[indexIdModule]['serie'][0][0] < firstHoraire) {
			tableauDonnees[indexIdModule]['serie'][0][0] = firstHoraire;
		}
	}
	// Création du point à la fin de la période ( = date du rafraichissement de la page )
	// Pour chaque courbe : Ajout d'un point dont l'horodatage = heure de fin de recherche && valeur = dernière valeur de la courbe
	var dateDeFin           = {{ dateDeFin|json_encode|raw }};
	var dateUtcDeFin        = createUtcData(dateDeFin,"0");
	// Ajout d'une seconde à la date de fin
	dateUtcDeFin            = dateUtcDeFin + 1000;
	for (indexIdModule in tableauDonnees)
	{
		// Récupération du dernier point de la courbe
		var lastValue = tableauDonnees[indexIdModule]['serie'][tableauDonnees[indexIdModule]['serie'].length - 1]['1'];
		// Ajout d'un point dont l'horodatage = heure de fin de recherche && valeur = dernière valeur de la courbe
		tableauDonnees[indexIdModule]['serie'].push([dateUtcDeFin,lastValue]);
	}
	finPeriode = dateUtcDeFin;
	return(tableauDonnees);
}

// Recherche de la taille des marges
var margeGauche = 60;
var margeDroite = 60;
var nbAxesY = tabObjYAxis.length;
// La marge de gauche est de 80 * nombre d'axes à afficher à gauche
margeGauche = 60 * (parseInt(nbAxesY / 2) + (nbAxesY % 2));
// La marge de droite est de 60 * nombre d'axes à afficher à droite
margeDroite = 60 * (parseInt(nbAxesY / 2));
// Marge droite minimum = 60;
if(margeDroite == 0) {
	margeDroite = 60;
}

var highchartsOptions = Highcharts.setOptions(Highcharts.theme);

Highcharts.setOptions({
	lang : {
		months : ["Janvier "," Février "," Mars "," Avril "," Mai "," Juin "," Juillet "," Août ","Septembre "," Octobre "," Novembre "," Décembre"],
		weekdays : ["Dim "," Lun "," Mar "," Mer "," Jeu "," Ven "," Sam"],
		shortMonths : ['Jan', 'Fev', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil','Août', 'Sept', 'Oct', 'Nov', 'Déc'],
		decimalPoint : ',',
		resetZoom : 'Reset zoom',
		resetZoomTitle : 'Reset zoom à 1:1',
		loading : "Chargement...",
		rangeSelectorFrom : 'Du ',
		rangeSelectorTo : 'au  ',
		rangeSelectorZoom:''
	}
});

function setChartOptions() {
chartOptions = {
	chart : {
		renderTo : 'container_live',
		type : 'line',
		marginTop : 0,
		marginRight : margeDroite,
		marginLeft : margeGauche,
		marginBottom : 160,
		zoomType : 'x',
		events: {
			selection:  function(event) {
				saveExtremes = true;
                if (event.xAxis) {
                    if (event.xAxis[0].min < event.xAxis[0].max) {
                        $tabZoom['debut'] = event.xAxis[0].min;
                        $tabZoom['fin'] = event.xAxis[0].max;
                    } else {
                        $tabZoom['debut'] = event.xAxis[0].min;
                        $tabZoom['fin'] = event.xAxis[0].max;
                    }
                }
            }
		}
	},
	colors : tabColor,
	plotOptions : {
		series : {
			step : true,
			dataGrouping : {
				enabled : false,
				smoothed : true,
				approximation : 'low'
			},
			marker : {
				enabled : false,
				symbol : 'diamond',
				turboThreshold : 0,
				radius : 2
			}
		}
	},
	legend : {
		enabled : true,
		maxHeight: 200,
		title : {
                        text : ''
                },
		layout : 'horizontal',
		verticalAlign : 'bottom',
		borderColor : '#c1c1c1',
                borderRadius : 0,
                margin : -10,
                align : 'left',
                x : 10,
                y : 15,
		labelFormatter : function() {
                        return fctSupprimeCaracteres(this.name, null, null);
                },
		itemHoverStyle : {
                        color : 'blue'
                },
		itemWidth : 0,
                itemStyle : {
                        color : 'black',
                        width : 0,
                        textOverflow: 'ellipsis',
                        overflow: 'hidden'
                },
		width : 0
	},
	navigator : {
		enabled : false
	},
	scrollbar : {
		enabled : true,
		barBackgroundColor : 'gray',
		barBorderRadius : 7,
		barBorderWidth : 0,
		buttonBackgroundColor : 'gray',
		buttonBorderWidth : 0,
		buttonArrowColor : 'yellow',
		buttonBorderRadius : 7,
		rifleColor : 'yellow',
		trackBackgroundColor : 'white',
		trackBorderWidth : 1,
		trackBorderColor : 'silver',
		trackBorderRadius : 7
	},
	credits : {
		enabled : false
	},
	xAxis : {
		minRange : 1000,
		type : 'datetime',
		ordinal : false,
		events : {
			setExtremes : function(event) {
				setTimeout(function(){
					if (saveExtremes === true) {
						debutPeriode = event.min;
						finPeriode = event.max;
						var expReg = /^(.+?)\./;
						var tabPeriode = expReg.exec(debutPeriode.toString());
						// Recherche des pattern millisecondes
						if (tabPeriode !== null) {
							$tabZoom['debut'] = tabPeriode[1];
						} else {
							$tabZoom['debut'] = debutPeriode;
						}
						tabPeriode = expReg.exec(finPeriode.toString());
						if (tabPeriode !== null) {
							$tabZoom['fin'] = tabPeriode[1];
						} else {
							$tabZoom['fin'] = finPeriode;
						}
						var $dataAjax = 'min=' + $tabZoom['debut'] + '&max=' + $tabZoom['fin'];
						$.ajax({
							type: 'get',
							url: $urlSetTabZoom,
							data: $dataAjax
						});
					}
				},200);
			}
		}
	},
	yAxis : tabObjYAxis,
	rangeSelector : {
		inputDateFormat : '%d/%m/%Y %H:%M:%S.%L',
		inputEditDateFormat : '%d/%m/%Y %H:%M:%S.%L',
		inputBoxWidth : 170,
		// Custom parser to parse the %H:%M:%S.%L format
		inputDateParser : function(value) {
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
		buttonTheme : {
			width : 40,
			x : 20 
		},
		buttons : [{
			type : 'all',
			count : 1,
			text : 'Reset'
		}],
		labelStyle : {
			visibility : 'hidden'
		}
	},
	tooltip : {
		positioner: function () {
            return { x: 0, y: 0 };
        },
		backgroundColor: 'rgba(250,250,250,0.7)',
		borderColor: 'black',
		borderRadius: 10,
		borderWidth: 2,
		crosshairs : [true, true],
		shared : true,
		useHTML: true,
		formatter : function() {
                                var s = "<div style='width:" + $tooltipWidth + "px !important; white-space:normal;'>";
                                s = s + '<b>'+ Highcharts.dateFormat('%e %B %Y à %H:%M:%S (%L)', this.x) +'</b><br />';
                                $.each(this.points, function(i, point) {
                                        s = s + recupCourbesPoints(point.x);
                                        return false;
                                });
                                s = s + '</div>'
                                return s;
		}
	},
	navigation : {
		buttonOptions : {
			theme : {
				style : {
					color : '#039',
					textDecoration : 'underline'
				}
			}
		}
	},
	exporting : {
		enabled : false
	},
	series : tabSeries
};
}

function setChartOptionsLive() {
	chartOptions = {
	    chart : {
	        renderTo : 'container_live',
	        type : 'line',
			marginTop : 40,
			marginRight : 0,
			marginLeft : 5,
	        spacingBottom : 140,
	        zoomType : 'x',
	        events: {
				selection:  function(event) {
					saveExtremes = true;
					if (event.xAxis) {
						if (event.xAxis[0].min < event.xAxis[0].max) {
							$tabZoom['debut'] = event.xAxis[0].min;
							$tabZoom['fin'] = event.xAxis[0].max;
						} else {
							$tabZoom['debut'] = event.xAxis[0].min;
							$tabZoom['fin'] = event.xAxis[0].max;
						}
					}
				}
			}
		},
	    colors : tabColor,
	    plotOptions : {
	        series : {
	            step : true,
	            dataGrouping : {
	                enabled : false,
	                smoothed : true,
	                approximation : 'low'
	            },
	            marker : {
	                enabled : false,
	                symbol : 'diamond',
	                turboThreshold : 0,
	                radius : 2
	            }
	        }
	    },
	    legend : {
	        enabled : true,
	        title : {
	            text : ''
	        },
	        layout : 'vertical',
	        borderColor : '#c1c1c1',
	        borderRadius : 0,
	        width : 0,
	        labelFormatter : function() {
	            return fctSupprimeCaracteres(this.name, null, null);
	        },
	        itemWidth : 0,
	        itemStyle : {
				textOverflow: 'ellipsis',
				overflow: 'hidden',
           		color : 'black',
		    	width : 0
	        },
        	itemHoverStyle : {
				color: 'blue'
        	}
    	},
    	navigator : {
    	    enabled : false
    	},
    	scrollbar : {
    	    enabled : true,
    	    barBackgroundColor : 'gray',
    	    barBorderRadius : 7,
    	    barBorderWidth : 0,
    	    buttonBackgroundColor : 'gray',
    	    buttonBorderWidth : 0,
    	    buttonArrowColor : 'yellow',
    	    buttonBorderRadius : 7,
    	    rifleColor : 'yellow',
    	    trackBackgroundColor : 'white',
    	    trackBorderWidth : 1,
    	    trackBorderColor : 'silver',
    	    trackBorderRadius : 7
    	},
    	credits : {
    	    enabled : false
    	},
    	xAxis : {
    	    minRange : 1000,
    	    type : 'datetime',
    	    ordinal : false,
    	    events : {
    	        setExtremes : function(event) {
    	            setTimeout(function(){
    	                if (saveExtremes === true) {
    	                    debutPeriode = event.min;
    	                    finPeriode = event.max;
    	                    var expReg = /^(.+?)\./;
    	                    var tabPeriode = expReg.exec(debutPeriode.toString());
    	                    // Recherche des pattern millisecondes
        	                if (tabPeriode !== null) {
        	                    $tabZoom['debut'] = tabPeriode[1];
        	                } else {
        	                    $tabZoom['debut'] = debutPeriode;
        	                }
        	                tabPeriode = expReg.exec(finPeriode.toString());
        	                if (tabPeriode !== null) {
        	                    $tabZoom['fin'] = tabPeriode[1];
        	                } else {
        	                    $tabZoom['fin'] = finPeriode;
        	                }
        	                var $dataAjax = 'min=' + $tabZoom['debut'] + '&max=' + $tabZoom['fin'];
        	                $.ajax({
        	                    type: 'get',
        	                    url: $urlSetTabZoom,
        	                    data: $dataAjax
        	                });
        	            }
        	        },200);
        	    }
        	}
    	},
		yAxis : tabObjYAxisLive,
    	rangeSelector : {
    	    inputDateFormat : '%d/%m/%y %H:%M',
    	    inputEditDateFormat : '%d/%m/%y %H:%M',
    	    inputBoxWidth : 100,
			inputPosition : {align:'left', x:10},
    	    // Custom parser to parse the %H:%M:%S.%L format
    	    inputDateParser : function(value) {
    	        ladate = value.split(/ /);
    	        annee = ladate[0].split(/\//);
    	        value = ladate[1].split(/[:\.]/);
    	        return Date.UTC(
    	            parseInt(annee[2], 10),
    	            parseInt(annee[1]-1, 10),
    	            parseInt(annee[0], 10),
    	            parseInt(value[0], 10),
    	            parseInt(value[1], 10),
					0,
					0
    	        );
    	    },
    	    buttonTheme : {
    	        width : 40,
				x: 20,
				y: 5
    	    },
    	    buttons : [{
    	        type : 'all',
    	        count : 1,
    	        text : 'Reset'
    	    }]
    	},
    	tooltip : {
			positioner: function () {
    			return { x: 0, y: 0 };
    	    },
			backgroundColor: 'rgba(250,250,250,0.7)',
			borderColor: 'black',
			borderRadius: 10,
			borderWidth: 2,	
    	    crosshairs : [true, true],
    	    shared : true,
			useHTML: true,
    	    formatter : function() {
    	    	var s = "<div style='width:" + $tooltipWidth + "px !important; white-space:normal;'>";
    	        s = s + '<b>'+ Highcharts.dateFormat('%e %B %Y à %H:%M:%S (%L)', this.x) +'</b><br />';
    	        $.each(this.points, function(i, point) {
    	        	s = s + recupCourbesPoints(point.x);
    	            return false;
    	        });
    	        s = s + '</div>'
    	        return s;
    	    }
    	},
    	navigation : {
    	    buttonOptions : {
    	        theme : {
    	            style : {
    	                color : '#039',
    	                textDecoration : 'underline'
    	            }
    	        }
    	    }
    	},
    	exporting : {
    	    enabled : false
    	},
    	series : tabSeries
	};
}

var chart1 = new Highcharts.StockChart(chartOptions);

// Supprime les caractères $ et £
// Si une valeur est indiquée : Découpage du text pour extraire l'unité et affichage du texte sous la forme [ text : valeur(unite) ]
// Pour la version mobile on effectue un retour à la ligne au premier caractère - rencontré
function fctSupprimeCaracteres(texte, color, valeur) {
    var newTexte = null;
    // Extraction de l'information concernant le numéro du genre
    pattern_genre = /^.+?;/;
    newTexte = texte.replace(pattern_genre,'');
    // Modification des caractères spéciaux
    pattern = /=?\s?[\$£]/g;
    newTexte = newTexte.replace(pattern,'');
    if ((valeur !== null) && (color !== null)) {
        pattern_unite = /^(.+)\((.+?)\)$/;
        tabTextTransforme = pattern_unite.exec(newTexte);
	var spanColorS = '<span style="color:' + color + '">';
        if (tabTextTransforme !== null)
        {
        	newTextTransforme = spanColorS + tabTextTransforme[1] + ' : <b style="color:black;">' + valeur + ' ' + tabTextTransforme[2] + '</b></span>';
        } else {
        	newTextTransforme = spanColorS + newTexte + ' : <b style="color:black;">' + valeur + '</b></span>';
        }
        return(newTextTransforme);
    }
    return(newTexte);
}


function setInfosBullesStatut() {
	var $urlInfosBulles = $('#infoTooltip').attr('data-url');
	var $valeurInfosBulles;
	if ($('input[name=chkInfosBulles]').is(':checked')) {
		$valeurInfosBulles = 'checked';
	} else {
		$valeurInfosBulles = 'unchecked';
	}
	var $dataAjax = 'isChecked=' + $valeurInfosBulles;
	$.ajax({
		url: $urlInfosBulles,
		data: $dataAjax
	});
}

function setInfosAsideStatut() {
	var $urlInfosAside = $('#infoAside').attr('data-url');
	var $valeurInfosAside;
	if ($('input[name=chkInfosAside]').is(':checked')) {
		$valeurInfosAside = 'checked';
	} else {
		$valeurInfosAside = 'unchecked';
	}
	var $dataAjax = 'isChecked=' + $valeurInfosAside;
	$.ajax({
		url: $urlInfosAside,
		data: $dataAjax
	});
	return 0;
}

function changeTooltip() {
    	$('#hideChart').width($('#live_titre').width());
    	$('#hideChart').height('100%');
	$('#hideChart').show();
	if ($('#infoTooltip').is(':checked')) {
		chartOptions.tooltip = Array();
	} else {
		chartOptions.tooltip = {
			positioner: function () {
                        	return { x: 0, y: 0 };
                	},
			backgroundColor: 'rgba(250,250,250,0.7)',
			borderColor: 'black',
			borderRadius: 10,
			borderWidth: 2,
			crosshairs : [true, true],
			shared : true,
			useHTML: true,
			formatter : function() {
                                var s = "<div style='width:" + $tooltipWidth + "px !important; white-space:normal;'>";
				s = s + '<b>'+ Highcharts.dateFormat('%e %B %Y à %H:%M:%S (%L)', this.x) +'</b><br />';
				$.each(this.points, function(i, point) {
                                        s = s + recupCourbesPoints(point.x);
                                        return false;
                                });	
				s = s + '</div>'
                                return s;
                        }
		};
	}
	chartOptions.series = tabSeries;
	var chart1 = new Highcharts.StockChart(chartOptions, function(){
		setTimeout(function(){
			var chartExtrem = $('#container_live').highcharts();
			zoomButton = chartExtrem.rangeSelector.buttons;
        	for (var i = 0; i < zoomButton.length; i++) {
        	    $(zoomButton[i].element).hide();
        	}
		if (($tabZoom['debut'] !== null) && ($tabZoom['fin'] !== null)) {
			var xExt = chartExtrem.xAxis[0].setExtremes(parseInt($tabZoom['debut']), parseInt($tabZoom['fin']));
		}
    	    $('#hideChart').hide();
		},200);
    });
}

/*
function changeCompression(typeCompression) {
	chartOptions.series = tabSeries;
	// Changement du choix de compression
	chartOptions.plotOptions.series.dataGrouping.approximation = typeCompression;
	var chart1 = new Highcharts.StockChart(chartOptions);
	// Remise de la période demandée avant le clic sur Infos-Bulle
	// Permet d'afficher la bonne période en cas de zoom géré par highchart (cad zoom sans requête Ajax)
	var chartExtrem  = $('#container_live').highcharts();
	var xExt = chartExtrem.xAxis[0].setExtremes(debutPeriode,finPeriode);
}
*/

// Fonction qui récupére la liste des points de chaque courbe à une heure donnée
// Utilisée au survol de la courbe par tooltip
function recupCourbesPoints(horodatage) {
	var message = '';
	var tabSerie = Array();
	$.each(tabSeries,function(keySerie,valueSerie){
		$.each(valueSerie['data'],function(key,value){
			if (value[0] > horodatage) {
				valeurInf = Highcharts.numberFormat(valueSerie['data'][key - 1][1],2,","," ");
				// Message avec modification des caractères $
				message = message + fctSupprimeCaracteres(tabSeries[keySerie].name, chartOptions.colors[keySerie], valeurInf) + '<br />';
				return false;
			}
		});
	});
	return(message);
}

function setHighchart() {
	chartOptions.legend.width = $containerWidth;
	chartOptions.legend.itemWidth = $itemWidth;
	chartOptions.legend.itemStyle = {color:'black',width:$itemStyleWidth,textOverflow:'ellipsis',overflow:'hidden'};
	chartOptions.series = tabSeries;
	$('#hideChart').width($('#live_titre').width());
	$('#hideChart').height('100%');
	$('#hideChart').show();
	var chart1 = new Highcharts.StockChart(chartOptions, function(){
		setTimeout(function(){
			$(window).scrollTop(valScrollTop);
			changeTooltip();
			var chartExtrem = $('#container_live').highcharts();
			zoomButton = chartExtrem.rangeSelector.buttons;
			for (var i = 0; i < zoomButton.length; i++) {
				$(zoomButton[i].element).hide();
			}
  			if (($tabZoom['debut'] !== null) && ($tabZoom['fin'] !== null)) {
				chartExtrem.xAxis[0].setExtremes(parseInt($tabZoom['debut']), parseInt($tabZoom['fin']));
    		}
			$('#allPage').removeClass('waitingHighchart');
			$('#hideChart').hide();
		},800);
	});
}

function resetChart() {
	saveExtremes = true;
	$(zoomButton[0].element).click();
	return 0;
}

</script>
