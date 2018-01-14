<?php

require(__DIR__.'/JConfig.class.php');
require(__DIR__.'/Request.class.php');

/**
* Classe de configuration pour l'ensemble des objets (client, serveur ...)
* client = l'application / serveur = le FreeBox Server
*/

class Fbx {
    
    /**
    * @var Config Instance de configuration
    * @var Request Requète de base
    */
    
    protected static $_config;
    public static $_request;
    
    /**
    * Constructeur
    * @param string $file Chemin du fichier de configuration json
    */
    
    public function __construct($file) {
    
        if (! isset(self::$_config)) {  // empêche la création de plusieurs instances de configuration
            self::$_config = new JConfig($file);
        }
        self::$_request = new Request();
    }
}

/**
* Trait des methodes communes pour la creation, l'initialisation,
* et la gestion de la configuration des objets
*/


trait ConfigTools {

    /** Constructeur générique de l'objet */
    
    public function __construct() {
        $default = $this->is_config_set() ? false  : $this->init_config();
        $this->load_config($default);
        if ($default) {
            $this->save_config();
        }
    }
    
    // Force la définition d'une methode d'initialisation de l'objet

    abstract function init_config();
    
    /** Methode qui renvoit la configuration del'objet
    * @return array
    */
    
    protected function get_config() {
    
        $obj = get_class($this);
        $cfg = parent::$_config;
        
        return $cfg->data[$obj];
    }
    
    /**
    * Méthode qui teste si l'object est configuré dans l'instance de configuration
    * @return boolean
    */    
    
    protected function is_config_set() {
    
        return isset(self::$_config->data[get_class($this)]); // objet configuré ?
    }
   
    
    /**
    * Méthode qui charge les propriétés des objets suivant l'instance de configuration
    * ou des paramètres par défaut
    */
    
    protected function load_config($default=false) {
    
        $obj = get_class($this);
        $cfg = parent::$_config;
        
        $data = $default?:$cfg->data[$obj];
        
        foreach ($data as $key => $value) { // initialise l'objet
            $key = strtoupper($key);
            if (property_exists($obj,$key)) {
                $this->{$key} = $value;
            }
        }
        $this->update_request();
    }


    /**
    * Méthode qui met à jour l'instance de configuration et la sauvgarde
    */
    
    protected function save_config() {
        
        $obj = get_class($this);
        $cfg = parent::$_config;
        
        $cfg->data[$obj] = get_object_vars($this);
        
        $cfg->write();
    }
}

/**
* Classe du FreeBox Server
*/



class FbxServer extends Fbx {

    use ConfigTools;

    public $API_DOMAIN;
    public $UID;
    public $HTTPS_AVAILABLE;
    public $HTTPS_PORT;
    public $DEVICE_NAME;
    public $API_VERSION;
    public $API_BASE_URL;
    public $DEVICE_TYPE;
    
    
    protected function init_config() {
    
        $serveur = json_decode(file_get_contents('http://mafreebox.free.fr/api_version'));
        
        return $serveur;
    }
    
    protected function update_request() { //UTILISER LES METHODES DE CLASS REQUEST
    
        $url = ($this->HTTPS_AVAILABLE ? "https://".$this->API_DOMAIN.":".$this->HTTPS_PORT : 'http://mafreebox.free.fr').$this->API_BASE_URL."v".intval($this->API_VERSION)."/";
        parent::$_request->command->url=$url;
        
        $http = array ('http' => array('Host: '.$this->API_DOMAIN));
        $http = ! $this->HTTPS_AVAILABLE ? $http : $http + array('ssl' => array(    'cafile' => getcwd()."/cert/ca.pem",
                                                                                        'capath' => getcwd()."/cert/",
                                                                                    )
                                                                    );
        parent::$_request->headers->set_arr_item($http);
        
    }
        
    
}


/**
* Classe de l'application
*/

class FbxClient extends Fbx {

    Use ConfigTools;

    public $APP_ID;
    public $APP_NAME;
    public $APP_VERSION;
    public $DEVICE_NAME;
    public $SERVER_URL;
    
    protected $APP_TOKEN;
    protected $TRACK_ID;
    
    protected function init_config() {

        return parse_ini_file(__DIR__.'/../config/client.ini');
    }
    
    protected function update_request($session_token=false) {
        if ($session_token) {
            parent::$_request->headers->set_item('http','header', array('X-Fbx-App-Auth: '.$session_token));
        }    
    }
    
         /**
    * Méthode qui envoie une requete sur l'API Freebox
    * @param string $url chemin relatif l'api à la base (ex 'airmedia/receivers )
    * @param string $method 'GET/POST/PUT' ... Par défaut 'GET'
    * @param string $data les données 'POST'
    * @param string $type le type des données 'POST' : 'json/url/file' ou tout type valide. Par défaut 'json'
    * @return array $response la reponse json sous la forme d'un tableau
    */    
    
    public function r_api($url,$method='GET',$content="",$content_type="") {
    
        $request = clone parent::$_request;
        $base_url = $request->command->url;
        
        $request->command->url=$base_url.$url;
        $request->command->method=$method;
        
        $request->content->data=$content;
        $request->content->type=$content_type;
        
        return $request->send();
    }
    
    
    protected function set_token() {
    
        $url = 'login/authorize/';
        $method = 'POST';
        $content = json_encode(array( 'app_id' => $this->APP_ID,
                                        'app_name' => $this->APP_NAME,
                                        'app_version' => $this->APP_VERSION,
                                        'device_name' => $this->DEVICE_NAME,
                                        ));
        $content_type = 'content-type:application/json';
        
        $response = $this->r_api($url,$method,$content,$content_type);
        
        if ($response['success']) { //récupération du track_id et de l'app_token de l'application
            $this->TRACK_ID = $response['result']['track_id'];
            $this->APP_TOKEN = $response['result']['app_token'];
            $this->save_config(); //sauvegarde du track_id et de l'app_token
            
            return true;
        }
        return false;        
    }
    
     /**
    * Teste si l'application est autorisée
    * @return boolean
    */
    
    
    public function is_authorized() {
        if(isset($this->TRACK_ID)) {
            return ($this->r_api('login/authorize/'.$this->TRACK_ID)['result']['status']==='granted');
        }
        return false;
    }
    
     /**
    * Procèdure d'authorisation de l'application
    * @return boolean True si procèdure finalisée avec succès, False sinon
    */
    
    public function authorize() {
        if ($this->set_token()) {

            do { // lance la procedure d'authorisation; suit l'avancement toutes les 3 secondes
                $response = $this->r_api('login/authorize/'.$this->TRACK_ID);
                sleep(3);
            }
            while ($response['result']['status']==='pending');
            
            if ($response['result']['status']==='granted') {
                return true;
            }
            return false;
        }
        return false;
    }
    
     /**
    * Ouvre une session pour l'application
    * @return boolean True si loggée, False si erreur d'authentification
    */
    
    public function login() {
        $response = $this->r_api('login/');
        if ($response['success']) {
            $challenge = $response['result']['challenge'];
            
            $content = json_encode(array( 'app_id' => $this->APP_ID,
                                            'app_version' => $this->APP_VERSION,
                                            'password' => hash_hmac('sha1',$challenge,$this->APP_TOKEN),
                                            ));
            $content_type = 'content-type:application/json';
            
            $response = $this->r_api('login/session/','POST',$content,$content_type);
            
            if($response['success']) {
                $this->update_request($response['result']['session_token']);
                return true;
            }
        }
        return false;
    }
    
    /**
    * Ferme la session pour l'application
    * @return boolean
    */
    
    public function logout() {
        return ($this->r_api('login/logout/','POST'));
    }
    
}

?> 
