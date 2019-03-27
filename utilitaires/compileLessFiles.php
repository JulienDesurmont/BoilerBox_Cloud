<?php
require "./system/lessc.inc.php";
$less = new lessc;

try {

    # A executer en root
    # chmod("../web/bundles/ipcprog/css', 777);
	# chmod("toBeCompiled", 777);
	# chmod 777 /src/Ipc/ProgBundle/Resources/public/css
	# Suppression des anciens fichiers css
    unlink("../web/bundles/ipcprog/css/screen-export-01.css");
    unlink("../web/bundles/ipcprog/css/print-export-01.css");

	# Copie des fichiers css au format less dans le repertoire des fichiers à compiler
	copy("../src/Ipc/ProgBundle/Resources/public/css/screen-export-01.less.css", "toBeCompiled/lessFiles/screen-export-01.less");
	copy("../src/Ipc/ProgBundle/Resources/public/css/print-export-01.less.css", "toBeCompiled/lessFiles/print-export-01.less");
	copy("../src/Ipc/ProgBundle/Resources/public/css/commun-export-01.less.css", "toBeCompiled/lessFiles/commun-export-01.less");

    # Concatenation des fichiers css au format less
    $contentCommun = file_get_contents("toBeCompiled/lessFiles/commun-export-01.less");
	$contentScreen = file_get_contents("toBeCompiled/lessFiles/screen-export-01.less");
	$contentPrint = file_get_contents("toBeCompiled/lessFiles/print-export-01.less");

	# Création des fichiers concaténés
	file_put_contents("toBeCompiled/lessFiles/tmp/screen-export-01.less", $contentCommun);
	file_put_contents("toBeCompiled/lessFiles/tmp/screen-export-01.less", $contentScreen, FILE_APPEND);

	file_put_contents("toBeCompiled/lessFiles/tmp/print-export-01.less", $contentCommun);
	file_put_contents("toBeCompiled/lessFiles/tmp/print-export-01.less", $contentPrint, FILE_APPEND);

	# Compilation des fichiers concaténés pour les passer au format css
	$less->compileFile("toBeCompiled/lessFiles/tmp/screen-export-01.less", "toBeCompiled/cssFiles/screen-export-01.css");
	$less->compileFile("toBeCompiled/lessFiles/tmp/print-export-01.less", "toBeCompiled/cssFiles/print-export-01.css");

	# Copie des fichiers css dans le répertoire public
    copy("toBeCompiled/cssFiles/screen-export-01.css", "../src/Ipc/ProgBundle/Resources/public/css/screen-export-01.css");
    copy("toBeCompiled/cssFiles/print-export-01.css", "../src/Ipc/ProgBundle/Resources/public/css/print-export-01.css");

	# Copie des fichiers css dans le répertoire partagé
	copy("toBeCompiled/cssFiles/screen-export-01.css", "../web/bundles/ipcprog/css/screen-export-01.css");
	copy("toBeCompiled/cssFiles/print-export-01.css", "../web/bundles/ipcprog/css/print-export-01.css");

	echo "Fin de compilation des fichiers \n <br />";
	echo "Veuillez lancer la commande gestionAsset : php app/console asset:install";
} catch (exception $e) {
	echo "Erreur de compilation: ".$e->getMessage();
}
?>
