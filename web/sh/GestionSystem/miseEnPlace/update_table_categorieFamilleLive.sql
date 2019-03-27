USE ipc_master1

UPDATE t_categoriefamillelive 
SET informations = "%informations", couleur = "%couleur%", classe = "%classe%"
WHERE id = "%identifiant%";
