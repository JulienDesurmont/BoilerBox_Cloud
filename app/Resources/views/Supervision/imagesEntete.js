<script type='text/javascript'>
var tabLiveEnTetes = {{ tabLiveEnTetes|json_encode|raw }};

function imgError(image) {
	image.onerror = "";
	var regex = /svg\/(.+?).svg/;
	match = regex.exec(image.src);
	if (match != null) {
		var newSrc = 'img/' + match[1] + '.png';
		image.src = newSrc;
	}
	image.className = 'img png';
	return true;
}

function addChiffre($nombre) {
	if ($nombre.match(/^\d$/)) {
		return('0' + $nombre);
	}
}

function gestionTuiles(tabTuiles,indexAutomate) {
	for (key in tabTuiles) {
		if (tabTuiles[key]['placement'].toLowerCase() == 'corps') {
			var $unite = tabTuiles[key]['unite'];
			if (! $unite) {
				$unite = '';
			}
			if ((tabTuiles[key]['valeurEntreeVrai'] == null) || (tabTuiles[key]['valeurEntreeVrai'] == tabTuiles[key]['valeur'])) {
				if (tabTuiles[key]['valeurSortieVrai'] != null) {
					$('.var_' + tabTuiles[key]['famille'].toUpperCase()).html('<p>' + tabTuiles[key]['valeurSortieVrai'] + '</p>');
				} else {
					$('.var_' + tabTuiles[key]['famille'].toUpperCase()).html('<p>' + tabTuiles[key]['valeur'] + "&nbsp;" + $unite + '</p>');
				}
			} else {
				if (tabTuiles[key]['valeurSortieFaux'] != null) {
					$('.var_' + tabTuiles[key]['famille'].toUpperCase()).html('<p>' + tabTuiles[key]['valeurSortieFaux'] + '</p>');
				} else {
					$('.var_' + tabTuiles[key]['famille'].toUpperCase()).html('<p>' + tabTuiles[key]['valeur'] + "&nbsp;" + $unite + '</p>');
				}
			}
		}
	}
}

function gestionEntete(tabEntete) {
	$.each(tabEntete, function(key, value) {
		switch(value['famille']) {
		case 'enTeteGenerateur':
			if (value['label1'] === 'Arrêt') {
				$('#' + value['id'] + ' path.svg-ge-generateur-contour').attr('class','svg-ge-generateur-contour svg-ge-red-s');
				$('#' + value['id'] + ' polygon.svg-ge-generateur-eclair').attr('class','svg-ge-generateur-eclair svg-ge-red-f');
				$('#' + value['id']).attr('class','generateur red');
			} else if(value['label1'] === 'Production') {
				$('#' + value['id'] + ' path.svg-ge-generateur-contour').attr('class','svg-ge-generateur-contour svg-ge-green-s');
				$('#' + value['id'] + ' polygon.svg-ge-generateur-eclair').attr('class','svg-ge-generateur-eclair svg-ge-green-f');
				$('#' + value['id']).attr('class','generateur green');
			} else if(value['label1'] === 'Maintien en température') {
				$('#' + value['id'] + ' path.svg-ge-generateur-contour').attr('class','svg-ge-generateur-contour svg-ge-blue-s');
				$('#' + value['id'] + ' polygon.svg-ge-generateur-eclair').attr('class','svg-ge-generateur-eclair svg-ge-blue-f');
				$('#' + value['id']).attr('class','generateur blue');
			}
			$('#' + value['id']+' p').html(value['label1']);
			break;
		case 'enTeteBruleur':
			$('#' + value['id'] + ' div').attr('class','txt');
			var $newLabel = value['label1'];
			if (value['label1'] !== 'Eteint') {
				$newLabel = $newLabel + '<span>%</span>';
			} else {
				$newLabel = '<span>' + $newLabel + '</span>';
			}
			$('#' + value['id'] + ' p').html($newLabel);
			break;
		case 'enTeteBruleurBiFoyer':
			$('#' + value['id']+' div').attr('class','txt double');
			var $newLabel = value['label1'];
			if (value['label1'] !== 'Eteint') {
				$newLabel = $newLabel + '<span>%</span>';
			} else {
				$newLabel = '<span>' + $newLabel + '</span>';
			}
			$('#' + value['id'] + ' p:nth-child(2)').html($newLabel);
			$newLabel = value['label2'];
			if (value['label2'] !== 'Eteint') {
				$newLabel = $newLabel + '<span>%</span>';
			} else {
				$newLabel = '<span>' + $newLabel + '</span>';
			}
			$('#' + value['id'] + ' p:last-child').html($newLabel);
			break;
		case 'enTeteEtat':
			var $nbEvenements = value['label1'];
			var $nbAlarmes = value['label2'];
			var $nbDefauts = value['label3'];
			if ($nbEvenements + $nbAlarmes + $nbDefauts == 0) {
				$('#' + value['id'] + ' path.svg-ge-etat-contour').removeClass('svg-ge-none');
				$('#' + value['id'] + ' g.svg-ge-etat-alerte').attr('class','svg-ge-etat-alerte svg-ge-grey-s');
				$('#' + value['id'] + ' path.svg-ge-etat-alerte-3').attr('class','svg-ge-etat-alerte-3 svg-ge-none');     
				$('#' + value['id'] + ' div.txt p:nth-child(2)').attr('class','');
				$('#' + value['id'] + ' div.txt p:nth-child(3)').attr('class','');
				$('#' + value['id'] + ' div.txt p:nth-child(4)').attr('class','');
			} else {
				$('#'+value['id'] + ' path.svg-ge-etat-contour').attr('class','svg-ge-etat-contour svg-ge-none');
				$('#'+value['id'] + ' path.svg-ge-etat-alerte-3').removeClass('svg-ge-none');
				if ($nbDefauts > 0) {
					$('#'+value['id'] + ' g.svg-ge-etat-alerte').attr('class','svg-ge-etat-alerte svg-ge-red-s');
					if($nbEvenements > 0) { $('#' + value['id']+' div.txt p:nth-child(2)').attr('class','green'); }
					if($nbAlarmes > 0) { $('#' + value['id'] + ' div.txt p:nth-child(3)').attr('class','orange'); }
					$('#'+value['id'] + ' div.txt p:nth-child(4)').attr('class','red');
				} else if ($nbAlarmes > 0) {
					$('#'+value['id'] + ' g.svg-ge-etat-alerte').attr('class','svg-ge-etat-alerte svg-ge-orange-s');	
					if($nbEvenements > 0) { $('#' + value['id'] + ' div.txt p:nth-child(2)').attr('class','green'); }
					$('#'+value['id'] + ' div.txt p:nth-child(3)').attr('class','orange');
					if($nbDefauts > 0) { $('#' + value['id'] + ' div.txt p:nth-child(4)').attr('class','red'); }
				} else {
					$('#'+value['id'] + ' g.svg-ge-etat-alerte').attr('class','svg-ge-etat-alerte svg-ge-green-s');
					$('#'+value['id'] + ' div.txt p:nth-child(2)').attr('class','green');
					if($nbAlarmes > 0) { $('#' + value['id'] + ' div.txt p:nth-child(3)').attr('class','orange'); }
					if($nbDefauts > 0) { $('#' + value['id'] + ' div.txt p:nth-child(4)').attr('class','red'); }
				}

				if ($nbEvenements == 0 ) {
					$('#' + value['id'] + ' div.txt p:nth-child(2)').attr('class','');
                }
				if ($nbAlarmes == 0 ) {
					$('#' + value['id'] + ' div.txt p:nth-child(3)').attr('class','');
                }
				if ($nbDefauts == 0 ) {
					$('#' + value['id'] + ' div.txt p:nth-child(4)').attr('class','');
                }
			}
			$newLabel = $nbEvenements + '<span>Evénement';
			if ($nbEvenements > 1 ) {
				$newLabel = $newLabel + 's';
			}
			$newLabel = $newLabel + '</span>';
			$('#' + value['id'] + ' p:nth-child(2)').html($newLabel);
			$newLabel = $nbAlarmes + '<span>Alarme';
			if ($nbAlarmes > 1 ) {
				$newLabel = $newLabel + 's';
			}
			$newLabel = $newLabel + '</span>';
			$('#' + value['id'] + ' p:nth-child(3)').html($newLabel);
			$newLabel = $nbDefauts + '<span>Défaut';
			if ($nbDefauts > 1 ) {
				$newLabel = $newLabel + 's';
			}
			$newLabel = $newLabel + '</span>';
			$('#' + value['id'] + ' p:last-child').html($newLabel);
			break;
		case 'enTeteCombustible':
			$('#' + value['id'] + ' div').attr('class','txt');
			$('#' + value['id'] + ' p').html(value['label1']);
			break;
		case 'enTeteCombustibleBiFoyer':
			$('#' + value['id'] + ' div').attr('class','txt double');
			$('#' + value['id'] + ' p:nth-child(2)').html(value['label1']);
			$('#' + value['id'] + ' p:last-child').html(value['label2']);
			break;
		case 'enTeteNiveau':
			$('#' + value['id'] + ' p').html(value['label1'] + '<span>%</span>');
			break;
		case 'enTetePression':
			$('#' + value['id'] + ' p').html(value['label1'] + '<span>bar</span>');
			break;
		case 'enTeteConductivite':
			$('#' + value['id'] + ' p').html(value['label1'] + '<span>µS</span>');
			break;
		case 'enTeteDebit':
			$('#' + value['id'] + ' p').html(value['label1'] + '<span>Nm<sup>3</sup>/h</span>');
			break;
		case 'enTeteTemperatureDepart':
			$('#' + value['id'] + ' p').html(value['label1'] + '<span>°c</span>');
			break;
		case 'enTeteTemperatureRetour':
			$('#' + value['id'] + ' p').html(value['label1'] + '<span>°c</span>');
			break;
		default:
			$('#' + value['id'] + ' p').html(value['label1']);
			break;
		}
	});
}


$(document).ready(function () {
	$(function() {
		jQuery('img.svg').each(function () {
			var $img = jQuery(this);
			var imgID = $img.attr('id');
			var imgClass = $img.attr('class');
			var imgURL = $img.attr('src');
			var imgAlt = $img.attr('alt');
			jQuery.get(imgURL, function(data) {
				// Get the SVG tag, ignore the rest
				var $svg = jQuery(data).find('svg');
				// Add replaced image's ID to the new SVG
				if (typeof imgID !== 'undefined') {
					$svg = $svg.attr('id', imgID);
				}
				// Add replaced image's classes to the new SVG
				if (typeof imgClass !== 'undefined') {
					$svg = $svg.attr('class', imgClass + ' replaced-svg');
				}
				// Remove any invalid XML tags as per http://validator.w3.org
				$svg = $svg.removeAttr('xmlns:a');
				// Check if the viewport is set, else we gonna set it if we can.
				if (!$svg.attr('viewBox') && $svg.attr('height') && $svg.attr('width')) {
					$svg.attr('viewBox', '0 0 ' + $svg.attr('height') + ' ' + $svg.attr('width'))
				}
				// Replace image with new SVG
				$img.replaceWith($svg);
			}, 'xml');
		});
		setTimeout(function(){
			gestionEntete(tabLiveEnTetes);
		},100);
	});
});

</script>
