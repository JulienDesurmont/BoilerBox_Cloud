// Désactivation du bouton de modification de période
$('#choixSelectionPeriode').hide();

$(document).ready(function(){
    closePopupCompression();
    // Suppression de l'événement click de la banière des périodes
    $('configperiode').off('click');
    $(document).on('keypress', function(e){
        if ((e.which || e.keyCode) === 105) {
            if ($('#infoTooltip').prop('checked') == true) {
                $('#infoTooltip').prop('checked', false);
            } else {
                $('#infoTooltip').prop('checked', true);
            }
            changeTooltip();
        }
    });
});

// Fonction qui effectue l'impression des données dans un fichier csv si la var 'impression' n'est pas = 'no' ( Permet d'éviter de lancer plusieurs fois la demande d'impression'
function imprimeCsv($typeCsv, formName){
    if ($('#impression').val() != 'no'){
        switch($typeCsv){
            case 'yesCsv':
                $('#impression').val('yesCsv');
                break;
            case 'yesPCsv':
                break;
        }
        submitForm(formName);
        $('#impression').val('no');
    } else {
            return 1;
    }
    return 0;
}

