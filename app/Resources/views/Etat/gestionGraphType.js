<script type="text/javascript">

var graphsOptions = new Object();
var tabUnit = new Object();

// Lorsque la page web est chargée : Analyse de la liste des fichiers passés en paramètre
$(document).ready(function(){
        $('#codeModule').keypress(function(e){
				if ((e.which || e.keyCode) === 13) {
                	$('#messages').focus();
				}
        });

	//var graphsOptions = new Object();

        var tabFichiersEtats 	= {{ tabFichiers|json_encode|raw  }};
	//alert(dump(tabFichiersEtats));

	$.each(tabFichiersEtats,function(key,value){
		urlFichierJour		= value['jour'];
		urlFichierHeure		= value['heure'];
		urlFichierJour		= "{{ asset('etats/"+urlFichierJour+"') | raw }}";
		urlFichierHeure		= "{{ asset('etats/"+urlFichierHeure+"') | raw }}";
		var numeroFichierJour   = key+'Jour';
		var numeroFichierHeure	= key+'Heure';

		$.ajax({
                    type: "GET",
                    url: urlFichierHeure,
                    dataType: "text",
                    success: function(data) {
                    	if(graphsOptions[numeroFichierHeure] == null)
                    	{
                            graphsOptions[numeroFichierHeure] = new Boolean();   //new Object();
                            graphsOptions[numeroFichierHeure] = processGraphData(data,numeroFichierHeure,null,null);
                    	}
                    },
                    async: false
                });
                $.ajax({
                    type: "GET",
                    url: urlFichierJour,
                    dataType: "text",
                    success: function(data) {
                    	if(graphsOptions[numeroFichierJour] == null)
                    	{
                            graphsOptions[numeroFichierJour] = new Boolean();   //new Object();
                            graphsOptions[numeroFichierJour] = processGraphData(data,numeroFichierJour,null,null);
                    	}
                    },
                    async: false
                });
	});

        /*      Pour chaque fichier : Affichage de la courbe correspondante
        for(var numFile=0;numFile<tabFichiersEtats.length;numFile++)
        {
                //      Récupération de l'url du fichier à analyser
		//	 graphique_13493_heure.csv
                urlFichier = "{{ asset('etats/"+tabFichiersEtats[numFile]+"') | raw }}";
                var tabArgFichier       = tabFichiersEtats[numFile].split('_');
                var typeFichier         = tabArgFichier[0];
                var numeroFichier       = tabArgFichier[1];
        
		if(typeFichier == 'graphique')
        	{
        	        $.ajax({
        	                type: "GET",
        	                url: urlFichier,
        	                dataType: "text",
        	                success: function(data) {
        	                        if(graphsOptions[numeroFichier] == null)
        	                        {
						graphsOptions[numeroFichier] = new Boolean();	//new Object();	
               		                        graphsOptions[numeroFichier] = processGraphData(data,numeroFichier,null,null);
                                	}
                        	},
				async: false
                	});
        	}
        	if(typeFichier == 'listing')
        	{
        	        $.ajax({
        	                type: "GET",
        	                url: urlFichier,
        	                dataType: "text",
        	                success: function(data) {processListData(data,numeroFichier);},
				async: false
        	        });
        	}
        }
	*/
        //document.getElementById("loader").style.display         = "none";
});


/**
* fonction qui récupére la liste de données retournées par les requêtes et retourne un tableau formaté avec des dates au millieme de seconde
*/
function formatabHigh(tableau)
{
        var graphDataTmp = new Array();
        for(var key=0; key<tableau.length; key++)
        {
                var valeur     	= parseFloat(tableau[key]['valeur']);
                var result      = /^(\d+)[\/-](\d+)[\/-](\d+) (\d+):(\d+):(\d+)/.exec(tableau[key]['horodatage']);
                var annee       = RegExp.$1;
                var mois        = RegExp.$2-1;
                var jour        = RegExp.$3;
                var heure       = RegExp.$4;
                var minute      = RegExp.$5;
                var seconde     = RegExp.$6;
                var horaire     = Date.UTC(annee,mois,jour,heure,minute,seconde,tableau[key]['cycle']);
                var tmp_titre   = null;
		graphDataTmp.push([horaire, valeur]);
        }
        return graphDataTmp;
}

function options()
{
        this.chart =
        {
                renderTo:       '',
                alignTicks:     false,
                zoomType:       'x',
                marginTop:      180,
                marginRight:    100,
                marginLeft:     30,
                marginBottom:   50,
                spacingTop:     30,
                spacingBottom:  40,
                plotBorderColor: '#346691',
                plotBorderWidth: 1,
                events: {
                	/*load: function() {
                	        this.renderer.image("{{ asset('images/icones/Lci.png') }}", 10, 1, 119, 66).add();
                	},*/
			selection: function(event) {
                        	if (event.xAxis) {
                                       	var datemin = Highcharts.dateFormat('%Y-%m-%d %H:%I:%S', event.xAxis[0].min);
                                       	var datemax = Highcharts.dateFormat('%Y-%m-%d %H:%I:%S', event.xAxis[0].max);
                                       	this.showLoading('Loading data from server...');
					//	Récupération du numéro du fichier (correspond au numéro du container)
					var numFichier =this.renderTo.id.substr(9);
					//	Si le graphique du fichier est incomplet, on inhibe le zoom du graphique et on recherche les points dans le fichier texte
					if(graphsOptions[numFichier] == false)
					{
						event.preventDefault();
						newGraphs(datemin,datemax,numFichier);
					}
                                       	this.hideLoading();
                        	}else{
                                	alert('Selection reset');
                                }
                	}
                }
        };
        this.title = 
	{
        	text:'',
        	x:20,
        	y:10,
        	style: {
        		color: 'black',
        		fontSize: '18px'
        	},
        	align:"left"
        };
        this.legend = 
	{
        	title:{
        		text: 'Courbes',
        		style: {
        			fontStyle: 'italic'
        		}
        	},
        	verticalAlign: 'top',
        	enabled: true,
        	align:'left',
        	x:20,
        	y:70,
        	layout:'vertical'
        };
        this.navigator = 
	{
        	enabled : false
        };
        this.scrollbar = 
	{
        	enabled: true,
        	liveRedraw: true
        };
        this.credits =  
	{
        	position:{
        		align: 'right',
        		x:-100,
        		verticalAlign: 'bottom'
        	},
        	style:{
        		fontSize: '12px'
        	},
        	text: date_heure(),
        	href: '#'
        };
        this.xAxis = 
	{
        	minRange: 3000,       //9000, // => 3000 par courbe   14000,  //40000,        //14000,        //      Définie le zoom maximum
        	type:   'datetime',
        	ordinal: false
        };
        this.yAxis =  
	{
        	gridLineWidth:0,
        	title: {
        		text: '',
        		x: -30,
        		style:{
        			color: '#FF0000'
        		}
        	},
        	labels: {
        		formatter:function(){return this.value;},
        		style:{
        			color: '#FF0000'
        		}
        	}
        };
        this.tooltip = 
	{
        	crosshairs:true,
                shared: true,
                formatter: function() {
                	var s = Highcharts.dateFormat('%e %B %Y à %H:%M:%S (%L)', this.x);
                        $.each(this.points, function(i, point) {
                       		var unit = tabUnit[this.point.series.name];
				s = s + ' <b>' + Highcharts.numberFormat(point.y,2,","," ") + ' ' + unit + '</b>';
                        });
                        return s;
                },
        };
        this.rangeSelector = 
	{
        	inputDateFormat: '%d/%m/%Y %H:%M:%S',
        	inputEditDateFormat: '%d/%m/%Y %H:%M:%S',
        	inputBoxWidth:150,
        	inputPosition:
        	{
        		x:-270,
        		y:130
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
                		type: 'minute',
                		count: 1,
                		text: '1m'
                	},{
                		type: 'hour',
                		count: 1,
                		text: '1h'
                	},{
                		type: 'day',
                		count: 1,
                		text: '1d'
                	},{
                		type: 'month',
                		count: 1,
                		text: '1m'
                	},{
                		type: 'all',
                		count: 1,
                		text: 'Tous'
               	 	}],
                inputEnabled: true,
		enabled: true
               // selected : 8,
        };
	this.navigation = 
	{
		buttonOptions:{
			theme: {
				style: {
					color: '#039',
					textDecoration: 'underline'
				}
			}
		}
	};
        this.exporting = 
	{
        	sourceWidth: 1800,
        	sourceHeight: 700,
       		scale: 1,
        	filename: 'Lci_Courbes',
        	buttons: {
        		contextButton: {
        			text: "Impression / Images / Pdf",
        			x:-90,
        			y:20
        		},
			customButton: {
				text: "Réinitialisation",
				x:-160,
                        	y:45,
            			onclick: function () {
					var numFichier = this.renderTo.id.substr(9);
				        //      Récupération de l'url du fichier à analyser
        				var urlFichier = "{{ asset('etats/graphique_"+numFichier+"') | raw }}";
        				//      Lecture du fichier
        				$.ajax({
                				type: "GET",
                				url: urlFichier,
                				dataType: "text",
                				success: function(data) {
                        				graphsOptions[numFichier] = new Boolean();	//Object();
                        				graphsOptions[numFichier] = processGraphData(data,numFichier,null,null);
                				},
                				async: false
        				});
            			},
            			symbol: 'circle'
        		}
        	}
        };
        this.series = [];
}
	
function processListData(allData,numeroFichier)
{
	var tabRequete  = new Object();
	var lines       = allData.split(/\r\n|\n/);
	var titre	= '';
	variable = "<table class='selectionListing'>";
	variable += "<th class='horodatage'>Horodatage</th><th class='codeModule'>Module</th><th class='localisation'>Localisation</th><th class='genre'>Genre</th><th class='valeur'>Valeur</th><th class='valeur'>Unité</th>";
	for (var i=0; i<lines.length; i++)
	{
		var tabItems = lines[i].split(';');
		if(i == 0)
                {
                        titre = '<h1>'+tabItems[1]+'</h1>';
                }else
                {
			if(lines[i] != '')
                        {
		 		variable += "<tr>";
				variable += "<td class='horodatage'>"+tabItems[2]+"."+tabItems[3]+"</td>";
				variable += "<td class='codeModule' align='center'>"+tabItems[0]+"</td>";
                        	variable += "<td class='localisation' align='center'>"+tabItems[1]+"</td>";
				variable += "<td class='genre' align='center'>"+tabItems[5]+"</td>";
				variable += "<td class='valeur' align='center'>"+tabItems[4]+"</td>";
				variable += "<td class='valeur' align='center'>"+tabItems[6]+"</td>";
				variable += "</tr>";
			}
		}
	}
	variable += "</table>";
	document.getElementById('listing'+numeroFichier).innerHTML = titre+variable;
	return(0);
}

function processGraphData(allData,numeroFichier,dateDebut,dateFin)
{
	var limitMaxDonnees	= 1000;
	var recuperation        = false;
	var lines               = allData.split(/\r\n|\n/);
	//var tabRequete 	= new Array();
	var tabRequete          = new Object();
        var numRequete          = 0;
        var numColonneVal       = 0;
        var numColonneCycle     = 0;
        var numColonneUnite     = 0;
        var numDonnee           = 0;
	tabRequete['Donnees'] 	= new Array();

	//	Pour chaque ligne du fichier
	for (var i=0; i<lines.length; i++) 
	{
		var tabItems = lines[i].split(';');
		//	La ligne 1 correspond à l'intitulé du module
		if(i == 0)
		{
			tabRequete['titre'] 		= tabItems[0];
			tabRequete['texte'] 		= tabItems[1];
			tabRequete['name_'+numRequete]	= tabItems[1];
			tabRequete['message']		= tabItems[1];
			numRequete ++;
		}else if(i == 1)
		{
			//	La ligne 2 correspond à la période de recherche
		 	tabRequete['unite']= tabItems[1];
		}else if(i == 2)
		{
			//	La ligne 3 correspond à l'unité
			tabRequete['unite']= tabItems[1];
		}else if(i == 3)
                {
                        //      La ligne 4 correspond à la localisation
			tabRequete['localisation']= tabItems[1];
		}else{
			//	La dernière ligne du fichier correspond à une ligne vide : On ne la prend pas en compte dans la liste des points
			if(lines[i] != '')
	                {
				//	Si les dates de début et de fin sont définies, vérification que la date de la donnée est comprise entre ces dates
				//	Découpage des champs de chaque ligne
				if((dateDebut !== null)&&(dateFin !== null))
				{
					var dateTmp = getDateObj(tabItems[0],tabItems[1]).getTime();
					if((dateTmp >= getDateObj(dateDebut,0).getTime()) && (dateTmp <= getDateObj(dateFin,0).getTime()))
					{
						tabRequete['Donnees'][numDonnee] 		= new Array();
						tabRequete['Donnees'][numDonnee]['horodatage']	= tabItems[0];
                	        	        tabRequete['Donnees'][numDonnee]['cycle']	= 0;
                        		        tabRequete['Donnees'][numDonnee]['unite']	= tabRequete['unite'];
                                		tabRequete['Donnees'][numDonnee]['valeur']	= tabItems[1];
						//      incrémentation de la variable indiquant le nombre de données traitées
	                                        numDonnee ++;
					}
				}else{
					tabRequete['Donnees'][numDonnee] 		= new Array();
					tabRequete['Donnees'][numDonnee]['horodatage']  = tabItems[0];
					tabRequete['Donnees'][numDonnee]['cycle']	= 0;
                               		tabRequete['Donnees'][numDonnee]['unite']	= tabRequete['unite'];
                               		tabRequete['Donnees'][numDonnee]['valeur']	= tabItems[1];
					//	incrémentation de la variable indiquant le nombre de données traitées
                               		numDonnee ++;
				}
			}
		}
	}


        //      Si le nombre de données est > à la limite : Création d'un tableau de moyennes/heure
        if(tabRequete['Donnees'].length > limitMaxDonnees)
        {
                tabRequete['Donnees'] 	= moyenne(tabRequete['Donnees']);
		tabRequete['texte'] 	= '(moyenne/heure) '+tabRequete['texte'];
		tabRequete['allPoints'] = false;
        }else{
		tabRequete['allPoints'] = true;
	}

	//      Définition de la série du graphique
	var numCourbe 			= 0;
	var nouvelle_serie 		= new Array();
        nouvelle_serie[numCourbe] = {
                name : tabRequete['message'],	/*tabRequete['name_'+numCourbe]+' - '+tabRequete['texte'],	/* Titre inscrit dans la légende*/
                color: '#039',
		data: formatabHigh(tabRequete['Donnees']),
		step: true,
		tooltip: {
                        valueDecimals: 2
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

	/*
	//	Création du tableau des unités	pour des graphiques multi-courbes
	if(tabUnit[tabRequete['name_'+numCourbe]+' - '+tabRequete['texte']] == null)
	{
		tabUnit[tabRequete['name_'+numCourbe]+' - '+tabRequete['texte']] = tabRequete['unite'];
	}
	*/
	//      Création du tableau des unités  pour des graphiques multi-courbes
        if(tabUnit[tabRequete['message']] == null)
        {
                tabUnit[tabRequete['message']] = tabRequete['unite'];
        }

	
	var Loption 			= new options();
	//alert('Affichage dans '+'container'+numeroFichier);
	//alert(dump(nouvelle_serie));
	Loption.chart.renderTo 		= 'container'+numeroFichier;
        Loption.series 			= nouvelle_serie;
	//	Définition du titre du graphique
	Loption.title.text		= tabRequete['titre'];
	//	Définition de l'axes des ordonnés
	Loption.yAxis.title.text 	= tabRequete['unite'];
	//	Définition des options du graphique
    	var highchartsOptions 		= Highcharts.setOptions(Highcharts.theme);
    	Highcharts.setOptions({
        	lang: {
            	months:             ["Janvier "," Février "," Mars "," Avril "," Mai "," Juin "," Juillet "," Août ","Septembre "," Octobre "," Novembre "," Décembre"],
            	weekdays:           ["Dim "," Lun "," Mar "," Mer "," Jeu "," Ven "," Sam"],
            	shortMonths:        ['Jan', 'Fev', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil','Août', 'Sept', 'Oct', 'Nov', 'Déc'],
            	decimalPoint:       ',',
            	resetZoom:          'Reset zoom',
            	resetZoomTitle:     'Reset zoom à 1:1',
            	downloadPNG:        "Télécharger au format PNG image",
            	downloadJPEG:       "Télécharger au format JPEG image",
            	downloadPDF:        "Télécharger au format PDF document",
           	downloadSVG:        "Télécharger au format SVG vector image",
        	exportButtonTitle:  "Exporter image ou document",
        	printChart:         "Imprimer le graphique",
        	loading:            "Chargement...",
        	rangeSelectorFrom:  "Du",
        	rangeSelectorTo:    "au"
        	}
    	});
	//      Création du graphique
        var chart                       = new Highcharts.Chart(Loption)
	return(tabRequete['allPoints']);
	//return(Loption);
}


function getDateObj(dateStr,millisec)
{
	var year 	= dateStr.substr(0,4);
	var month 	= dateStr.substr(5,2);
	var day 	= dateStr.substr(8,2);
	var hour 	= dateStr.substr(11,2);
	var minute 	= dateStr.substr(14,2);
	var seconde 	= dateStr.substr(17,2);
	var milliseconde = millisec;
	var dateObj 	= new Date(year,month-1,day,hour,minute,seconde,milliseconde);
	return(dateObj);
}

function date_heure()
{
        date 	= new Date;
        annee 	= date.getFullYear();
        moi 	= date.getMonth();
        mois 	= new Array('Janvier', 'F&eacute;vrier', 'Mars', 'Avril', 'Mai', 'Juin', 'Juillet', 'Ao&ucirc;t', 'Septembre', 'Octobre', 'Novembre', 'D&eacute;cembre');
        j 	= date.getDate();
        jour 	= date.getDay();
        jours 	= new Array('Dimanche', 'Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi');
        h 	= date.getHours();
        if(h<10)
        {
                h = "0"+h;
        }
        m = date.getMinutes();
        if(m<10)
        {
                m = "0"+m;
        }
        s = date.getSeconds();
        if(s<10)
        {
                s = "0"+s;
        }
        resultat = jours[jour]+' '+j+' '+mois[moi]+' '+annee+' '+h+':'+m+':'+s;
        return resultat;
}

//	Retourne les points du fichier compris entre datemin et datemax
function newGraphs(datemin,datemax,numFichier)
{
        //      Récupération de l'url du fichier à analyser
        urlFichier = "{{ asset('etats/graphique_"+numFichier+"') | raw }}";

	//	Lecture du fichier
        $.ajax({
        	type: "GET",
                url: urlFichier,
                dataType: "text",
                success: function(data) {
                       	graphsOptions[numFichier] = new Boolean();	//Object();
                        graphsOptions[numFichier] = processGraphData(data,numFichier,datemin,datemax);
                },
                async: false
	});
                   
	

	var Loption = new options();
        Loptions.chart.renderTo          = 'container'+numFichier;
        Loptions.series                  = nouvelle_serie;
        //      Définition du titre du graphique
        Loptions.title.text              = tabRequete['titre'];
        //      Définition de l'axes de ordonnées
        Loptions.yAxis.title.text        = tabRequete['unite'];
        //      Définition des options du graphique
        var highchartsOptions = Highcharts.setOptions(Highcharts.theme);
        Highcharts.setOptions({
                lang: {
                months:             ["Janvier "," Février "," Mars "," Avril "," Mai "," Juin "," Juillet "," Août ","Septembre "," Octobre "," Novembre "," Décembre"],
                weekdays:           ["Dim "," Lun "," Mar "," Mer "," Jeu "," Ven "," Sam"],
                shortMonths:        ['Jan', 'Fev', 'Mar', 'Avr', 'Mai', 'Juin', 'Juil','Août', 'Sept', 'Oct', 'Nov', 'Déc'],
                decimalPoint:       ',',
                resetZoom:          'Reset zoom',
                resetZoomTitle:     'Reset zoom à 1:1',
                downloadPNG:        "Télécharger au format PNG image",
                downloadJPEG:       "Télécharger au format JPEG image",
                downloadPDF:        "Télécharger au format PDF document",
                    downloadSVG:        "Télécharger au format SVG vector image",
                    exportButtonTitle:  "Exporter image ou document",
                    printChart:         "Imprimer le graphique",
                    loading:            "Chargement...",
                    rangeSelectorFrom:  "Du",
                    rangeSelectorTo:    "au"
                }
        });
        //      Création du graphique
        var chart                       = new Highcharts.Chart(Loptions)
        return(0);
}


//	Fonction qui calcule la moyenne par heure des points du graphique
function moyenne(tabDonnees)
{
	var tabDesHeures = new Array();
	var tabDesMoyennes = new Array();
	var nbChamps = -1;
	//	Pour chaque graphique
	for(numRequete=0;numRequete<tabDonnees.length;numRequete++)
        {
		var heure = tabDonnees[numRequete]['horodatage'].substr(0,13);
		var numColonne = tabDesHeures.indexOf(heure);
                if(numColonne == -1)
                {
                        if(nbChamps >= 0)
                        {
                                tabDesMoyennes[nbChamps]['valeur'] = parseFloat(tabDesMoyennes[nbChamps]['valeur']) / parseInt(tabDesMoyennes[nbChamps]['nbValeur']);
                        }
			nbChamps ++;
                        tabDesMoyennes[nbChamps] 		= new Array();
                        tabDesMoyennes[nbChamps] 		= tabDonnees[numRequete];
                        tabDesMoyennes[nbChamps]['cycle'] 	= 0;
                        tabDesMoyennes[nbChamps]['nbValeur'] 	= 1;
			tabDesHeures.push(heure);
		}else{
                        tabDesMoyennes[nbChamps]['valeur'] = parseFloat(tabDesMoyennes[nbChamps]['valeur']) + parseFloat(tabDonnees[numRequete]['valeur']);
                        tabDesMoyennes[nbChamps]['nbValeur'] ++;
                }	
	}
	return(tabDesMoyennes);
}






function dump(arr,level)
{
    var dumped_text="";if(!level) level=0;var level_padding="";for(var j=0;j<level+1;j++) level_padding += "    ";if(typeof(arr) == 'object'){for(var item in arr){var value = arr[item];if(typeof(value) == 'object'){dumped_text += level_padding + "'" + item + "' ...\n";dumped_text += dump(value,level+1);}else{dumped_text += level_padding + "'" + item + "' => \"" + value + "\"\n";}}}else{dumped_text = "===>"+arr+"<===("+typeof(arr)+")";}return dumped_text;
}


</script>
