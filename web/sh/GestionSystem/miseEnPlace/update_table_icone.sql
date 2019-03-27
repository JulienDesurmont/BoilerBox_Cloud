USE ipc_master1

UPDATE t_icone
SET  designation = "%designation%", url = "%url%", alt="%alt%"
WHERE id = "%identifiant%";
