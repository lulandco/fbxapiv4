<?php


require(__DIR__.'/class/Fbx.class.php');

// chemin du fichier de configuration; le fichier est créé si non existant
$file = getcwd().'/config/config.json';

// instance de configuration; obligatoire pour instancier serveur et client (application)
$config = new Fbx($file); //si $file est omis par défaut c'est le fichier /config/config.json

// instance du Freebox serveur
$server = new FbxServer();

//instance de l'application; configuration de base dans /config/client.ini
$client = new FbxClient();

// authorise l'application
$client->is_authorized();

if (! $client->is_authorized()) {
        $client->authorize();
}

// authentifie l'application
$client->login();

/* exemple de requètes */
//requête GET

print("<b>Infos airmedia (GET)</b><br>");

$response=$client->r_api('airmedia/receivers/');
print_r($response);
print("<br><br>");

//requête POST urlencoded

print("<b>Téléchargement de fichier (POST urlencoded)</b><br>");

$dl = urlencode("http://ipv4.download.thinkbroadband.com/5MB.zip");
$response=$client->r_api('downloads/add','POST','download_url='.$dl,CTURL);
print_r($response);
print("<br><br>");

//requête PUT

print("<b>Modifier le contrôle parental (PUT json)</b><br>"); //autoriser l'appli sur FreeBox OS

$json = json_encode(array('default_filter_mode' => 'allowed'));
$response=$client->r_api('parental/config/','PUT',$json,CTJSON);
print_r($response);
print("<br><br>");

// delogger l'application

$client->logout()


?>
