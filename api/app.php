<?php
use \Psr\Http\Message\ServerRequestInterface as Request;
use \Psr\Http\Message\ResponseInterface as Response;

require '/../vendor/autoload.php';

$config['determineRouteBeforeAppMiddleware'] = true;
$config['displayErrorDetails'] = true;
$config['addContentLengthHeader'] = false;

$config['db']['host']   = 'localhost';
$config['db']['user']   = 'root';
$config['db']['pass']   = '';
$config['db']['dbname'] = 'joborderdb';

$app = new \Slim\App(['settings' => $config]);

$container = $app->getContainer();
 
# PDO database library
$container['db'] = function ($c) {
    $settings = $c->get('settings')['db'];
    $pdo = new PDO("mysql:host=" . $settings['host'] . ";dbname=" . $settings['dbname'], $settings['user'], $settings['pass']);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
    return $pdo;
};

# Check if user is logged in
$middlewareLoggedIn = function ($request, $response, $next) {
    $route = $request->getAttribute('route');
    $route_name = $route->getName();

    $unauthorize_routes = array('home');

    if (!isset($_SESSION['user']) && in_array($route_name, $unauthorize_routes)){
        # redirect user to Login Page
        $response = $response->withRedirect('login');
    } else {
        $response = $next($request, $response);
    }

    return $response;
};

// $app->add($middlewareLoggedIn);

# Login Authentication
$app->post('/login/authenticate', function (Request $request, Response $response) {
    $data = $request->getParsedBody();
    $username = filter_var($data['username'], FILTER_SANITIZE_STRING);
    $password = filter_var($data['password'], FILTER_SANITIZE_STRING);
    
    $sth = $this->db->prepare("SELECT * FROM users WHERE `username`=:username AND `password`=:password");
    $sth->bindParam("username", $username);
    $sth->bindParam("password", $password);
    $sth->execute();
    $user = $sth->fetchObject();
    $data = array("user"=>$user, "accounts"=>$user);
    $data = array("data"=>$data);
    return $this->response->withJson($data);
})->setName('LoginAuthenticate');

# Retrieve All Job Orders
$app->get('/list', function (Request $request, Response $response, $args) {
    $sth = $this->db->prepare("SELECT * FROM job_orders");
    $sth->execute();
    $job_orders = $sth->fetchAll();

    return $this->response->withJson($job_orders);
})->setName('job_order_list');

# Retrieve specific JO
$app->get('/jo/{job_order_id}', function (Request $request, Response $response, $args) {
    $sth = $this->db->prepare("SELECT * FROM job_orders WHERE job_order_id=:job_order_id");
    $sth->bindParam("job_order_id", $args['job_order_id']);
    $sth->execute();
    $job_order = $sth->fetchObject();

    return $this->response->withJson($job_order);
});

# Add new JO
$app->post('/add', function ($request, $response) {
    $data = $request->getParsedBody();
    $sql = "INSERT INTO job_orders (`job_order_title`,`job_order_desc`) VALUES (:job_order_title,:job_order_desc)";
    $sth = $this->db->prepare($sql);
    $sth->bindParam("job_order_title", $data['job_order_title']);
    $sth->bindParam("job_order_desc", $data['job_order_desc']);
    $sth->execute();
    $data['id'] = $this->db->lastInsertId();

    return $this->response->withJson($data);
});

# Update specific JO
$app->put('/edit/{job_order_id}', function (Request $request, Response $response, $args) {
    $put_data = $request->getParsedBody();
    $sql = "UPDATE job_orders SET job_order_title=:job_order_title, job_order_desc=:job_order_desc WHERE job_order_id=:job_order_id";
    $sth = $this->db->prepare($sql);
    $sth->bindParam("job_order_id", $args['job_order_id']);
    $sth->bindParam("job_order_title", $put_data['job_order_title']);
    $sth->bindParam("job_order_desc", $put_data['job_order_desc']);
    $sth->execute();
    
    return $response->withStatus(200)->withJson($sth->rowCount() . ' JO(s) Updated!!!!');
});

# DELETE specific JO
$app->delete('/jo/delete/{job_order_id}', function ($request, $response, $args) {
    $sth = $this->db->prepare("DELETE FROM job_orders WHERE job_order_id=:job_order_id");
    $sth->bindParam("job_order_id", $args['job_order_id']);
    $sth->execute();

    return $response->withStatus(200)->write($sth->rowCount() . ' JO(s) Deleted!!!!');
});

#----------------------------------------------------------------------------------------

$app->post('/login/sample', function (Request $request, Response $response) {
	$data = $request->getParsedBody();
    $username = filter_var($data['username'], FILTER_SANITIZE_STRING);
    $password = filter_var($data['password'], FILTER_SANITIZE_STRING);
    
    $sth = $this->db->prepare("SELECT * FROM users WHERE `username`=:username AND `password`=:password");
    $sth->bindParam("username", $username);
    $sth->bindParam("password", $password);
    $sth->execute();
    $user = $sth->fetchObject();
    
    return $this->response->withJson($user);
})->add(function ($request, $response, $next) {
    $data = $request->getParsedBody();

    if(empty($data['username']) || empty($data['password'])){
        $response->getBody()->write('Please Provide Username and Password!');
        return $response;
    }

	$response = $next($request, $response);
	// $response->getBody()->write('<br />Login Successful!');

	return $response;
});