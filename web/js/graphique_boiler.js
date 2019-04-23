;var $chargementGraphique=!0;
;$('#choixSelectionPeriode').hide();$(document).ready(function(){closePopupCompression();$('configperiode').off('click');$(document).on('keypress',function(e){if((e.which||e.keyCode)===105){if($('#infoTooltip').prop('checked')==!0){$('#infoTooltip').prop('checked',!1)}
else{$('#infoTooltip').prop('checked',!0)};changeTooltip()}})});function imprimeCsv(e,o){if($('#impression').val()!='no'){switch(e){case'yesCsv':$('#impression').val('yesCsv');break;case'yesPCsv':break};submitForm(o);$('#impression').val('no')}
else{return 1};return 0};