<?php
require 'vendor/autoload.php';
use \Slim\Slim as Slim;

/**
 * Setup the timezone
 */
ini_set('date.timezone', 'America/Edmonton');


$logWriter = new \Flynsarmy\SlimMonolog\Log\MonologWriter(array(
    'handlers' => array(
        new \Monolog\Handler\StreamHandler('src/log/'.date('Y-m-d').'.log'),
    ),
));
//$logWriter = new \Slim\LogWriter(fopen('src/log/errors_slim.log', 'a'));
//$logWriter = new \Slim\LogWriter(fopen('src/log/bullhorn.log', 'a'));

$app=new \Slim\Slim(array(
	'debug'=>true,
	'log.enabled' =>    true,
	'log.level' =>      \Slim\Log::DEBUG,
    'mode' =>           'development',
    'log.writer' => 	$logWriter,
    'templates.path' => 'Stratum/templates'
));

$app->setName('stratum');
$log = $app->getLog();

$app->post('/endpoint/:id', function ($endpoint) use ($log) {
	
	$entityBody = file_get_contents('php://input');
	$log->debug($entityBody);
	$formController = new Stratum\Controller\FormController();
	$log->debug("parsing input data");
	$formResult = $formController->parse($entityBody);
	$formResult->setLogger($log);
	//form has updated mappings for each question
	$candidate = new Stratum\Model\Candidate();
    $candidate->setLogger($log);
    $log->debug("parsed input data");
	$candidateController = new Stratum\Controller\CandidateController();
	$candidateController->setLogger($log);
	$candidate = $candidateController->populate($candidate, $formResult);
	$log->debug("Candidate submitted with name ".$candidate->getName());
    $controller = new Stratum\Controller\BullhornController();
    $controller->setLogger($log);
	$controller->submit($candidate);

	
	
});

$app->run();


?>
