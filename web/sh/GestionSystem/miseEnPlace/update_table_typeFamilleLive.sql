USE ipc_master1

UPDATE t_typefamillelive
SET  designation = "%designation%", informations = "%informations%", disposition = "%disposition%" 
WHERE id = "%identifiant%";
