USE ipc_master1

UPDATE t_moduleEnteteLive 
SET designation = "%designation%", description = "%description%", typeFamilleLive_id = "%idFamille%" 
WHERE id = "%identifiant%";
