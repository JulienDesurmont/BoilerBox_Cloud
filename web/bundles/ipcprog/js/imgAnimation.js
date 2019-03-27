<script type="text/javascript">
//images/icones/
$(document).ready(function(){
	var chemin='/Symfony/web/images/icones/';
        $('img').hover(function(){
                var choix 		= this.src;
		var over = choix.lastIndexOf('over');
		if(over == -1)
		{
                	var indexFichier 	= choix.lastIndexOf('/');      // Index du nom du fichier
                	var extension 		= choix.lastIndexOf('.');         // Index de l'extension
                	var sizeFileName 	= extension - indexFichier;    // Taille du nom du fichier
                	var nomfichier 		= choix.substr(indexFichier+1,sizeFileName-1);
                	var newExtension 	= '-over.png';
                	var nouveauFichier 	= chemin+nomfichier+newExtension;
                	this.src 		= nouveauFichier;
		}
        },
        function(){
                var choix 		= this.src;
		var over = choix.lastIndexOf('over');
                if(over !=  -1)
                {
                	var indexFichier 	= choix.lastIndexOf('/');      // Index du nom du fichier
                	var extension 		= choix.lastIndexOf('-');         // Index de l'extension
                	var sizeFileName 	= extension - indexFichier;    // Taille du nom du fichier
                	var nomfichier 		= choix.substr(indexFichier+1,sizeFileName-1);
                	var newExtension 	= '.png';
                	var nouveauFichier 	= chemin+nomfichier+newExtension;
                	this.src 		= nouveauFichier;
		}
        });
});
</script>
