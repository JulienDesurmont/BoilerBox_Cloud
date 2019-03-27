<?php
//	Si les variable sont définies : affichage du mot de passe crypté
if(isset($_POST['passwd']) && isset($_POST['login']))
{
    echo "Ligne &agrave entrer dans le fichier ".realpath('gestionHtaccess/.htpasswd')." : ".$_POST['login'].":".crypt($_POST['passwd']);
    return(0);
}
?>
<form method='post'>
    <table>
    <tr><td>Entrer le login</td><td><input type='text' name='login' /></td></tr>
    <tr><td>Entrez le mot de passe</td><td><input type='text' name='passwd' /></td></tr>
    <tr><td colspan='2'><input type='submit' value='Envoye' /></td></tr>
    </table>
</form>
