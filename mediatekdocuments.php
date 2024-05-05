<?php
//Liste des URL acceptés
$acceptedUris = ['/auth', '/livre', '/dvd', '/commande', '/commandedocument', '/revue', '/exemplaire', '/genre', '/public', '/rayon', '/etat', '/suivi', '/abonnement'];
$uriCount = sizeof($acceptedUris);

$uri = isset($_SERVER['REDIRECT_URL']) ? $_SERVER['REDIRECT_URL'] : $_SERVER['REQUEST_URI'];
$uri = str_replace('/htdocs', '', $uri);

//Vérifie si l'URL est parmis la liste des URL acceptés
$isAcceptedUri = false;
for ($i = 0; $i < $uriCount; $i++) {
    if (strpos($uri, $acceptedUris[$i]) === 0) {
        $isAcceptedUri = true;
        break;
    }
}

//Si c'est pas le cas, erreur 404
if (!$isAcceptedUri) {
    http_response_code(404);
    return;
}


//On informe le type du résultat
header('Content-Type: application/json');
//Inclusion du controller d'authentification
require "controllers/AuthController.php";

//Petite exception, si l'URL cible l'authentification, il faut que la méthode soit en POST, sinon erreur 400
if ($uri == '/auth') {
    if ($_SERVER['REQUEST_METHOD'] != 'POST') {
        http_response_code(400);
        echo json_encode(['error' => 'Auth request should be in POST']);
        return;
    }

    //On émet le résultat de l'authentification
    echo json_encode(AuthController::authenticate());
    return;
}

//On interprète les paramètres en fonction de la méthode de la requête
switch ($_SERVER['REQUEST_METHOD']) {
    case 'PUT':
        $params = parsePutParams();
        break;
    case 'DELETE':
        $params = parseDeleteParams($uri);
        break;
    case 'POST':
        $params = parsePostParams();
        break;
    case 'GET':
        $params = parseGetParams($uri);
        break;
}

//Toutes les requêtes doivent être authentifiées, sinon erreur 403
//if (AuthController::isAuthenticated() == false) {
//http_response_code(403);
//echo json_encode(['error' => 'Unauthorized']);
//return;
//}

//On cherche le controller associé à la table demandée
$controller = getDataController($params['table']);
if ($controller == null) {
    http_response_code(400);
    echo json_encode(['error' => 'Bad data type ' . $params['table'], $params]);
    return;
}

//On récupère le résultat auprès du controller
$result = getResult($controller, $params);
if ($result === false) {
    //On émet une erreur si le controller renvoie false
    echo json_encode(['error' => 'Invalid request']);
} else {
    //Sinon on émet le résultat renvoyé par le controller
    echo json_encode($result);
}


//Fonction de récupération d'une instance du controller en fonction du nom de la table demandée
function getDataController($tableName)
{
    $controllersByTableName = [
        'livre' => 'BookController',
        'dvd' => 'DvdController',
        'commande' => 'CommandeController',
        'commandedocument' => 'CommandeDocumentController',
        'revue' => 'ReviewController',
        'exemplaire' => 'CopyController',
        'genre' => 'BaseDataController',
        'public' => 'BaseDataController',
        'rayon' => 'BaseDataController',
        'etat' => 'BaseDataController',
        'suivi' => 'BaseDataController',
        'abonnement' => 'SubscriptionController'
    ];

    if (isset($controllersByTableName[$tableName])) {
        $controllerName = $controllersByTableName[$tableName];
        //Tous les controllers doivent étendre la classe abstraite DataController
        require "controllers/DataController.php";
        require "controllers/$controllerName.php";
        return new $controllerName();
    } else {
        //Si on ne trouve pas de controller, on renvoie null
        return null;
    }
}

//On exécute la fonction du controller qui correspond selon la méthode de requête
function getResult($controller, $params)
{
    switch ($_SERVER['REQUEST_METHOD']) {
        case 'GET':
            //Récupération de données
            switch (true) {
                case isset($params['id']):
                    //Une seule donnée
                    return $controller->first($params['table'], $params['id']);

                case isset($params['champs']):
                    //Une recherche de données
                    return $controller->find($params['table'], $params['champs']);

                default:
                    //Toutes les données
                    return $controller->getAll($params['table']);
            }

        case 'POST':
            //Création de données
            return $controller->create($params['table'], $params['champs']);

        case 'PUT':
            //Mise à jour de données
            return $controller->update($params['table'], $params['id'], $params['champs']);

        case 'DELETE':
            //Suppression de données
            return $controller->delete($params['table'], $params['id']);
    }
}

//Interprétation des données sur une requête de type DELETE
function parseDeleteParams($uri)
{
    $input = explode('/', $uri);
    return [
        'id' => sizeof($input) >= 3 ? $input[2] : null,
        'table' => strtolower($input[1])
    ];
}
//Interprétation des données sur une méthode de type POST
function parsePostParams()
{
    return parseInput($_POST);
}
//Interprétation des données sur une méthode de type GET
function parseGetParams($uri)
{
    $input = explode('/', $uri);
    $data = [
        'table' => $input[1]
    ];
    if (isset($_GET['champs'])) {
        $data['champs'] = json_decode($_GET['champs']);
    } else if (isset($_GET['id'])) {
        $data['id'] = $_GET['id'];
    }

    return $data;
}
//Interprétation des données sur une méthode de type PUT
function parsePutParams()
{
    //Traitement particulier ici car php ne gère pas nativement les requêtes PUT.
    //Une requête PUT se traite comme une requête POST, il est donc nécessaire de récupérer le body de la requête et l'interpréter soi-même
    return parseInput(parse_raw_http_request(file_get_contents('php://input')));
}
//Interprétation des paramètres de requête
function parseInput($input)
{
    if (isset($input['champs'])) {
        //Les champs doivent être fournis en JSON
        $input['champs'] = json_decode($input['champs']);

        //Si ce n'est pas le cas, erreur 400
        if (json_last_error() != null) {
            http_response_code(400);
            exit;
        }
    }

    if (isset($input['table'])) {
        //On s'assure que la table est fournie en minuscule
        $input['table'] = strtolower($input['table']);
    }
    return $input;
}

//Interprétation du body de la requête
function parse_raw_http_request($data)
{
    // Définir les variables pour stocker les données analysées
    $parsedData = [];
    $boundary = null;
    $data .= PHP_EOL;

    preg_match('/multipart\/form-data;\sboundary=\"([\d\w\-]+)\"/', $_SERVER['CONTENT_TYPE'], $boundary);
    $boundary = trim($boundary[1]);

    // Séparer les parties de la requête en fonction du délimiteur
    $parts = explode("--$boundary", $data);
    // Parcourir chaque partie de la requête
    foreach ($parts as $part) {
        // Ignorer les parties vides et le délimiteur final
        if (trim($part) === '' || strpos($part, 'Content-Disposition') === false) {
            continue;
        }

        // Extraire le nom du champ et la valeur des données
        preg_match('/Content-Disposition:\sform-data;\sname=\"?(\w+)\"?[\r\n]+(.+?)[\r\n]+/', $part, $matches);
        $name = trim($matches[1]);
        $value = $matches[2];
        // Ajouter la paire clé-valeur au tableau analysé
        $parsedData[$name] = $value;
    }

    return $parsedData;
}
