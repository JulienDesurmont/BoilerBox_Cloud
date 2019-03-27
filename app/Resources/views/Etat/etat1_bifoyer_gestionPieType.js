<script type='text/javascript'>
var seriesPieCT	= ({{ tabPieCT|json_encode|raw }});
var seriesPieB1T = ({{ tabPieB1T|json_encode|raw }});
var seriesPieB1C = ({{ tabPieB1C|json_encode|raw }});
var seriesPieB2T = ({{ tabPieB2T|json_encode|raw }});
var seriesPieB2C = ({{ tabPieB2C|json_encode|raw }});
var seriesPieCC	= ({{ tabPieCC|json_encode|raw }});
var seriesPieCB1 = ({{ tabPieCombustibleB1|json_encode|raw }});
var seriesPieCB2 = ({{ tabPieCombustibleB2|json_encode|raw }});
var seriesPieDefauts = ({{ tabDefauts|json_encode|raw }});
var seriesPieAlarmes = ({{ tabAlarmes|json_encode|raw }});
var seriesPieAnomaliesR = ({{ tabAnomaliesR|json_encode|raw }});
var designationModule1 = {{ designation1|json_encode|raw }};
var designationModule2 = {{ designation2|json_encode|raw }};
var designationModule3 = {{ designation3|json_encode|raw }};

var tabCombustiblesB1 = {{ tabCombustiblesB1|json_encode|raw }};
var tabCombustiblesB2 = {{ tabCombustiblesB2|json_encode|raw }};
var titreDefauts = {{ titreDefauts|json_encode|raw }};
var titreAlarmes = {{ titreAlarmes|json_encode|raw }};
var titreAnomaliesR = {{ titreAnomaliesR|json_encode|raw }};
var titreCombustible1 = {{ titreCombustible1|json_encode|raw }};
var titreCombustible2 = {{ titreCombustible2|json_encode|raw }};
var foyer = {{ foyer|json_encode|raw }};
var spanTitle = '<span style="color:black;font-size:18px;font-weight:bold;">'
var occurencesDefauts = {{ occurencesDefauts|json_encode|raw }};
var occurencesAlarmes = {{ occurencesAlarmes|json_encode|raw }};
var occurencesAnomaliesR = {{ occurencesAnomaliesR|json_encode|raw }};


$(function () {
    Highcharts.setOptions({
        lang: {
	    printChart: "Imprimer",
	    downloadJPEG: "Téléchargment JPEG",
	    downloadPDF: "Téléchargement PDF",
	    downloadPNG: "Téléchargement PNG",
	    downloadSVG: "Téléchargement SVG"
        }
    });
    $('#containerCT').highcharts({
        chart: {
            plotBackgroundColor: null,
            plotBorderWidth: null,
	    	borderColor: "#4572A7",
	    	borderWidth: 0,
            plotShadow: false,
            type: 'pie',
			width: 900,
            height: 400
        },
        title: {
            text: spanTitle + designationModule1 + ' / Période</span>'
        },
        tooltip: {
            pointFormat: '{series.name}: <b>{point.percentage:.2f}%</b>'
        },
        plotOptions: {
            pie: {
                allowPointSelect: true,
                cursor:	'pointer',
                size: 170,
                dataLabels: {
                    enabled: true,
                    format: '<b>{point.name}: </b><br/>{point.percentage:.2f}%',
                    style: {
                        color: (Highcharts.theme && Highcharts.theme.contrastTextColor) || 'black'
                    }
                }
            }
        },
		colors: [
            '#E62424',
			'#1E71F6'
        ],
        series: [{
            name: 'Valeur',
            colorByPoint: true,
            data: seriesPieCT
        }],
        credits: {
            enabled: false
        }
    });

    $('#containerB1T').highcharts({
        chart: {
            plotBackgroundColor: null,
            plotBorderWidth: null,
            borderColor: "#4572A7",
            borderWidth: 0,
            plotShadow: false,
            type: 'pie',
	    	width: 420,
            height: 400
        },
        title: {
            text: spanTitle + designationModule2 + ' / Période</span>'
        },
        tooltip: {
            pointFormat: '{series.name}: <b>{point.percentage:.2f}%</b>'
        },
        plotOptions: {
            pie: {
                allowPointSelect: true,
                cursor: 'pointer',
				size: 110,
                dataLabels: {
                    enabled: true,
                    format: '<b>{point.name}: </b><br />{point.percentage:.2f} %',
                    style: {
                        color: (Highcharts.theme && Highcharts.theme.contrastTextColor) || 'black'
                    }
                }
            }
        },
        colors: [
            '#E62424',
            '#1E71F6'
        ],
        series: [{
            name: 'Valeur',
            colorByPoint: true,
            data: seriesPieB1T
        }],
        credits: {
            enabled: false
        }
    });

    $('#containerB1C').highcharts({
        chart: {
            plotBackgroundColor: null,
            plotBorderWidth: null,
            borderColor: "#4572A7",
            borderWidth: 0,
            plotShadow: false,
            type: 'pie',
	    	width: 420,
            height: 400
        },
        title: {
            text: spanTitle + spanTitle + designationModule2 + ' / '+ designationModule1 + '</span>'
        },
        tooltip: {
            pointFormat: '{series.name}: <b>{point.percentage:.2f}%</b>'
        },
        plotOptions: {
            pie: {
                allowPointSelect: true,
                cursor: 'pointer',
                size: 110,
                dataLabels: {
                    enabled: true,
                    format: '<b>{point.name}: </b><br />{point.percentage:.2f} %',
                    style: {
                        color: (Highcharts.theme && Highcharts.theme.contrastTextColor) || 'black'
                    }
                }
            }
        },
        colors: [
            '#E62424',
            '#1E71F6'
        ],
        series: [{
            name: 'Valeur',
            colorByPoint: true,
            data: seriesPieB1C
        }],
        credits: {
            enabled: false
        }
    });


    if (! $.isEmptyObject(tabCombustiblesB1)) {
    	$('#containerCB1').highcharts({
    	    chart: {
    	        plotBackgroundColor: null,
    	        plotBorderWidth: null,
    	        plotShadow: false,
				width: 900,
            	height: 400,
    	        type: 'pie'
    	    },
    	    title: {
    	        text: spanTitle + titreCombustible1 + '</span>'
    	    },
    	    tooltip: {
    	        pointFormat: '{series.name}: <b>{point.percentage:.2f}%</b>'
    	    },
    	    plotOptions: {
    	        pie: {
    	            allowPointSelect: true,
    	            cursor: 'pointer',
    	            size: 170,
    	            dataLabels: {
    	                enabled: true,
    	                format: '<b>{point.name}: </b><br />{point.percentage:.2f}%',
    	                style: {
    	                    color: (Highcharts.theme && Highcharts.theme.contrastTextColor) || 'black'
    	                }
    	            }
    	        }
    	    },
    	    series: [{
    	        name: 'Valeur',
    	        colorByPoint: true,
    	        data: seriesPieCB1
    	    }],
    	    credits: {
    	        enabled: false
    	    }
    	});
	}



    if (foyer == 'bifoyer') {
        $('#containerB2T').highcharts({
            chart: {
            	plotBackgroundColor: null,
            	plotBorderWidth: null,
            	borderColor: "#4572A7",
            	borderWidth: 0,
            	plotShadow: false,
            	type: 'pie',
	    		width: 420,
            	height: 400
            },
            title: {
            	text: spanTitle + designationModule3 + ' / Période</span>'
            },
            tooltip: {
            	pointFormat: '{series.name}: <b>{point.percentage:.2f}%</b>'
            },
            plotOptions: {
            	pie: {
                    allowPointSelect: true,
                    cursor: 'pointer',
                    size: 110,
                    dataLabels: {
                    	enabled: true,
                    	format: '<b>{point.name}: </b><br />{point.percentage:.2f} %',
                    	style: {
                    	    color: (Highcharts.theme && Highcharts.theme.contrastTextColor) || 'black'
                        }
                    }
            	}
            },
        	colors: [
                '#E62424',
                '#1E71F6'
            ],
            series: [{
            	name: 'Valeur',
            	colorByPoint: true,
            	data: seriesPieB2T
            }],
            credits: {
            	enabled: false
            }
        });

        $('#containerB2C').highcharts({
            chart: {
           	plotBackgroundColor: null,
           	plotBorderWidth: null,
            	borderColor: "#4572A7",
            	borderWidth: 0,
            	plotShadow: false,
            	type: 'pie',
	    		width: 420,
            	height: 400
            },
            title: {
            	text: spanTitle + designationModule3 + ' / ' + designationModule1 + '</span>'
            },
            tooltip: {
            	pointFormat: '{series.name}: <b>{point.percentage:.2f}%</b>'
            },
            plotOptions: {
            	pie: {
            	    allowPointSelect: true,
            	    cursor: 'pointer',
            	    size: 110,
            	    dataLabels: {
                    	enabled: true,
                    	format: '<b>{point.name}: </b><br />{point.percentage:.2f} %',
                    	style: {
                            color: (Highcharts.theme && Highcharts.theme.contrastTextColor) || 'black'
                    	}
                    }
                }
            },
        	colors: [
                '#E62424',
                '#1E71F6'
            ],
            series: [{
            	name: 'Valeur',
            	colorByPoint: true,
            	data: seriesPieB2C
            }],
            credits: {
            	enabled: false
            }
        });

        $('#containerCC').highcharts({
            chart: {
                plotBackgroundColor: null,
                plotBorderWidth: null,
                borderColor: "#4572A7",
                borderWidth: 0,
                plotShadow: false,
                type: 'pie',
                width: 900,
                height: 400
            },
            title: {
                text: spanTitle + designationModule2 + ' & ' + designationModule3 + ' / ' + designationModule1 + '</span>'
            },
            tooltip: {
                pointFormat: '{series.name}: <b>{point.percentage:.2f}%</b>'
            },
            plotOptions: {
                pie: {
                    allowPointSelect: true,
                    cursor: 'pointer',
                    size: 170,
                    dataLabels: {
                        enabled: true,
                        format: '<b>{point.name}: </b><br />{point.percentage:.2f} %',
                        style: {
                            color: (Highcharts.theme && Highcharts.theme.contrastTextColor) || 'black'
                        }
                    }
                }
            },
        	colors: [
                '#E62424',
                '#80699B',
                '#1E71F6',
            ],
            series: [{
                name: 'Valeur',
                colorByPoint: true,
                data: seriesPieCC
            }],
            credits: {
                enabled: false
            }
        });

	if (! $.isEmptyObject(tabCombustiblesB2)) {
    	$('#containerCB2').highcharts({
    	    chart: {
    	        plotBackgroundColor: null,
    	        plotBorderWidth: null,
    	        plotShadow: false,
				borderWidth: 0,
				width: 900,
            	height: 400,
    	        type: 'pie'
    	    },
    	    title: {
    	        text: spanTitle + titreCombustible2 + '</span>'
    	    },
    	    tooltip: {
    	        pointFormat: '{series.name}: <b>{point.percentage:.2f}%</b>'
    	    },
    	    plotOptions: {
    	        pie: {
    	            allowPointSelect: true,
    	            cursor: 'pointer',
    	            size: 170,
    	            dataLabels: {
    	                enabled: true,
    	                format: '<b>{point.name}: </b><br>{point.percentage:.2f}%',
    	                style: {
    	                    color: (Highcharts.theme && Highcharts.theme.contrastTextColor) || 'black'
    	                }
    	            }
    	        }
    	    },
    	    series: [{
    	        name: 'Valeur',
    	        colorByPoint: true,
    	        data: seriesPieCB2
    	    }],
    	    credits: {
    	        enabled: false
    	    }
    	});
	}


    } else {
        /* Redimensionnement des graphiques en cas de chaudière monofoyé */
        $('#containerB1T').highcharts().setSize(1300,300,false);
        $('#containerB1C').highcharts().setSize(1300,300,false);
    }


	if (occurencesDefauts != 0) {
    $('#containerDefauts').highcharts({
        chart: {
            plotBackgroundColor: null,
            plotBorderWidth: null,
            plotShadow: false,
			borderWidth: 0,
			width: 900,
			height: 400,
            type: 'pie'
        },
        title: {
            text: spanTitle + titreDefauts + '</span>'
        },
        tooltip: {
            pointFormat: '{point.designation}: <b>{point.percentage:.2f}%</b>'
        },
        plotOptions: {
            pie: {
                allowPointSelect: true,
                cursor: 'pointer',
				size: 170,
                dataLabels: {
                    enabled: true,
                    format: '<b>{point.code} </b>{point.percentage:.2f}%',
                    style: {
                        color: (Highcharts.theme && Highcharts.theme.contrastTextColor) || 'black'
                    }
                }
            }
        },
        series: [{
            name: 'Valeur',
            colorByPoint: true,
            data: seriesPieDefauts
        }],
        credits: {
            enabled: false
        }
    });
	}

	if (occurencesAlarmes != 0) {
    $('#containerAlarmes').highcharts({
        chart: {
            plotBackgroundColor: null,
            plotBorderWidth: null,
            plotShadow: false,
			borderWidth: 0,
			width: 900,
            height: 400,
            type: 'pie'
        },
        title: {
            text: spanTitle + titreAlarmes + '</span>'
        },
        tooltip: {
            pointFormat: '{point.designation}: <b>{point.percentage:.2f}%</b>'
        },
        plotOptions: {
            pie: {
                allowPointSelect: true,
                cursor: 'pointer',
				size: 170,
                dataLabels: {
                    enabled: true,
                    format: '<b>{point.code} </b>{point.percentage:.2f}%',
                    style: {
                        color: (Highcharts.theme && Highcharts.theme.contrastTextColor) || 'black'
                    }
                }
            }
        },
        series: [{
            name: 'Valeur',
            colorByPoint: true,
            data: seriesPieAlarmes
        }],
        credits: {
            enabled: false
        }
    });
	}

	if (occurencesAnomaliesR != 0) {
    $('#containerAnomaliesR').highcharts({
        chart: {
            plotBackgroundColor: null,
            plotBorderWidth: null,
            plotShadow: false,
			borderWidth: 0,
			width: 900,
            height: 400,
            type: 'pie'
        },
        title: {
            text: spanTitle + titreAnomaliesR + '</span>'
        },
        tooltip: {
            pointFormat: '{point.designation}: <b>{point.percentage:.2f}%</b>'
        },
        plotOptions: {
            pie: {
                allowPointSelect: true,
                cursor: 'pointer',
        		size: 170,
                dataLabels: {
                    enabled: true,
                    format: '<b>{point.code} </b>{point.percentage:.2f}%',
                    style: {
                        color: (Highcharts.theme && Highcharts.theme.contrastTextColor) || 'black'
                    }
                }
            }
        },
        series: [{
            name: 'Valeur',
            colorByPoint: true,
            data: seriesPieAnomaliesR
        }],
        credits: {
            enabled: false
        }
    });
	}

});

</script>
