<script type='text/javascript'>
var seriesPieCT	= ({{ tabPieCT|json_encode|raw }});
var seriesPieB1T = ({{ tabPieB1T|json_encode|raw }});
var seriesPieB1C = ({{ tabPieB1C|json_encode|raw }});
var seriesPieB2T = ({{ tabPieB2T|json_encode|raw }});
var seriesPieB2C = ({{ tabPieB2C|json_encode|raw }});
var seriesPieCC	= ({{ tabPieCC|json_encode|raw }});
var seriesPieDefauts = ({{ tabDefauts|json_encode|raw }});
var seriesPieAlarmes = ({{ tabAlarmes|json_encode|raw }});
var designationModule1 = {{ designation1|json_encode|raw }};
var designationModule2 = {{ designation2|json_encode|raw }};
var designationModule3 = {{ designation3|json_encode|raw }};
var titreDefauts = {{ titreDefauts|json_encode|raw }};
var titreAlarmes = {{ titreAlarmes|json_encode|raw }};
var foyer = {{ foyer|json_encode|raw }};

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
	    	width: 1300,
            height: 300
        },
        title: {
            text: designationModule1 + ' / Période '
        },
        tooltip: {
            pointFormat: '{series.name}: <b>{point.percentage:.2f}%</b>'
        },
        plotOptions: {
            pie: {
                allowPointSelect: true,
                cursor:	'pointer',
                size: 180,
                dataLabels: {
                    enabled: true,
                    format: '<b>{point.name}</b>: {point.percentage:.2f} %',
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
            name: "Part",
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
	    width:650,
            height: 300
        },
        title: {
            text: designationModule2 + ' / Période'
        },
        tooltip: {
            pointFormat: '{series.name}: <b>{point.percentage:.2f}%</b>'
        },
        plotOptions: {
            pie: {
                allowPointSelect: true,
                cursor: 'pointer',
				size: 180,
                dataLabels: {
                    enabled: true,
                    format: '<b>{point.name}</b>: {point.percentage:.2f} %',
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
            name: "Part",
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
            borderWidth:0,
            plotShadow: false,
            type: 'pie',
	    width:650,
            height: 300
        },
        title: {
            text: designationModule2 + ' / '+ designationModule1
        },
        tooltip: {
            pointFormat: '{series.name}: <b>{point.percentage:.2f}%</b>'
        },
        plotOptions: {
            pie: {
                allowPointSelect: true,
                cursor: 'pointer',
                size: 180,
                dataLabels: {
                    enabled: true,
                    format: '<b>{point.name}</b>: {point.percentage:.2f} %',
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
            name: "Part",
            colorByPoint: true,
            data: seriesPieB1C
        }],
        credits: {
            enabled: false
        }
    });

    if(foyer == 'bifoyer')
    {
        $('#containerB2T').highcharts({
            chart: {
            	plotBackgroundColor: null,
            	plotBorderWidth: null,
            	borderColor: "#4572A7",
            	borderWidth:0,
            	plotShadow: false,
            	type: 'pie',
	    	width:650,
            	height: 300
            },
            title: {
            	text: designationModule3 + ' / Période'
            },
            tooltip: {
            	pointFormat: '{series.name}: <b>{point.percentage:.2f}%</b>'
            },
            plotOptions: {
            	pie: {
                    allowPointSelect: true,
                    cursor: 'pointer',
                    size: 180,
                    dataLabels: {
                    	enabled: true,
                    	format: '<b>{point.name}</b>: {point.percentage:.2f} %',
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
            	name: "Part",
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
            	borderWidth:0,
            	plotShadow: false,
            	type: 'pie',
	    	width:650,
            	height: 300
            },
            title: {
            	text: designationModule3 + ' / ' + designationModule1
            },
            tooltip: {
            	pointFormat: '{series.name}: <b>{point.percentage:.2f}%</b>'
            },
            plotOptions: {
            	pie: {
            	    allowPointSelect: true,
            	    cursor: 'pointer',
            	    size: 180,
            	    dataLabels: {
                    	enabled: true,
                    	format: '<b>{point.name}</b>: {point.percentage:.2f} %',
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
            	name: "Part",
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
                borderWidth:0,
                plotShadow: false,
                type: 'pie',
                width:1300,
                height: 300
            },
            title: {
                text: designationModule2 + ' & ' + designationModule3 + ' / ' + designationModule1
            },
            tooltip: {
                pointFormat: '{series.name}: <b>{point.percentage:.2f}%</b>'
            },
            plotOptions: {
                pie: {
                    allowPointSelect: true,
                    cursor: 'pointer',
                    size: 180,
                    dataLabels: {
                        enabled: true,
                        format: '<b>{point.name}</b>: {point.percentage:.2f} %',
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
                name: "Part",
                colorByPoint: true,
                data: seriesPieCC
            }],
            credits: {
                enabled: false
            }
        });


    }else{
        /* Redimensionnement des graphiques en cas de chaudière monofoyé */
        $('#containerB1T').highcharts().setSize(1300,300,false);
        $('#containerB1C').highcharts().setSize(1300,300,false);
    }


    $('#containerDefauts').highcharts({
        chart: {
            plotBackgroundColor: null,
            plotBorderWidth: null,
            plotShadow: false,
            type: 'pie'
        },
        title: {
            text: titreDefauts
        },
        tooltip: {
            pointFormat: '{series.name}: <b>{point.percentage:.2f}%</b>'
        },
        plotOptions: {
            pie: {
                allowPointSelect: true,
                cursor: 'pointer',
		size: 200,
                dataLabels: {
                    enabled: true,
                    format: '<b>{point.name}</b>: {point.percentage:.2f} %',
                    style: {
                        color: (Highcharts.theme && Highcharts.theme.contrastTextColor) || 'black'
                    }
                }
            }
        },
        series: [{
            name: "Part",
            colorByPoint: true,
            data: seriesPieDefauts
        }],
        credits: {
            enabled: false
        }
    });

    $('#containerAlarmes').highcharts({
        chart: {
            plotBackgroundColor: null,
            plotBorderWidth: null,
            plotShadow: false,
            type: 'pie'
        },
        title: {
            text: titreAlarmes
        },
        tooltip: {
            pointFormat: '{series.name}: <b>{point.percentage:.2f}%</b>'
        },
        plotOptions: {
            pie: {
                allowPointSelect: true,
                cursor: 'pointer',
		size: 200,
                dataLabels: {
                    enabled: true,
                    format: '<b>{point.name}</b>: {point.percentage:.2f} %',
                    style: {
                        color: (Highcharts.theme && Highcharts.theme.contrastTextColor) || 'black'
                    }
                }
            }
        },
        series: [{
            name: "Part",
            colorByPoint: true,
            data: seriesPieAlarmes
        }],
        credits: {
            enabled: false
        }
    });
});
</script>
