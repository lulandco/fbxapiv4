# fbxapiv4
Classe PHP client Freebox OS api v4  

## Prérequis
Une freebox v6.  
Un serveur web opérationnel avec PHP 7.  

## Installation
Copier l'arborescense des fichiers dans un repertoire accessible du serveur web.  

## Configuration
Les paramètres généraux de l'application cliente sont à modifier dans le fichier ```/config/client.ini``` .  
La configuration générale (serveur, client et autorisation) se sauvegarde dans le fichier ```/config/config.json```.  

## Requêtes
Si disponible sur le serveur, les requêtes se font en ssl (certificat **free** dans ```/cert```).  
La librairie CURL n'est pas utilisée.   

## Utilisation

### chemin du fichier de configuration
le fichier est créé si non existant  
```$file = getcwd().'/config/config.json';```

### instance de configuration
obligatoire pour instancier serveur et client (application)  
si ```$file``` est omis c'est par défaut le fichier ```/config/config.json```  
```$config = new Fbx($file);```

### instance du Freebox serveur
```$server = new FbxServer();```

### instance de l'application
configuration de base dans /config/client.ini  
```$client = new FbxClient();```

### authorise l'application
```$client->is_authorized();

if (! $client->is_authorized()) {
        $client->authorize();
}
```
### authentifie l'application
```$client->login();```

### envoie une requète
```$response=$client->r_api(URL,METHOD,CONTENT,TYPE);```  
URL = chaîne (peut être absolue ou relative à l'API_DOMAIN du serveur).  
METHOD = chaîne : **GET** | POST | PUT   
CONTENT = contenu sous forme d'une chaîne  
TYPE = type de contenu : CTTEXT | CTMULTI | CTJSON | CTURL  

Exemples d'utilisation dans le fichier ```freebox.php```  

### délogge l'application
```$client->logout();```  

## Auteur
* **Lul Andco** - *Projet Initial* - [lulandco](https://github.com/lulandco)  

## Licence
Ce projet est publié sous licence GNU GPL v3 - voir les détails dans [LICENSE.md](LICENSE.md)  
