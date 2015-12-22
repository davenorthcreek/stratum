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

$app->get('launch', function (Request $request, Response $response) use ($log) {
    //load the id from the request
    $id = $request->getAttribute('entityid');
    
    //set up the controllers and their loggers
    $wcontroller = new \Stratum\Controller\WorldappController();
    $bcontroller = new \Stratum\Controller\BullhornController();
    $bcontroller->setLogger($log);
    $wcontroller->setLogger($log);
    
    //find the correct form
    $form = $wcontroller->find_form_by_name('Registration Form - Stratum International');
    
    //load the candidate data from Bullhorn
    $candidate = new Stratum\Model\Candidate();
    $candidate->set("id", $id);
    $bcontroller->load($candidate);
    
    //find the edited email template
    $template = $candidate->get('customTextBlock5');
    
    //autofilled fields to be extracted from candidate
    //id,firstName,lastName,dateOfBirth,nickName,email,email2,mobile,phone,workPhone,fax3,pager,customTextBlock2
    $firstName =        $candidate->get("firstName");
    $lastName =         $candidate->get("lastName");
    $dateOfBirth =      $candidate->getDateOfBirthWithFormat("d/m/Y");
    $maritalStatus =    $candidate->get("nickName");
    $email =            $candidate->get("email");
    $workEmail =        $candidate->get("email2");
    $mobile =           $candidate->get("mobile");
    $homePhone =        $candidate->get("phone");
    $workPhone =        $candidate->get("workPhone");
    $fax =              $candidate->get("fax3");
    $skype =            $candidate->get("pager");
    $types =            $candidate->get("customTextBlock2");
    //$types is an array, must translate to String
    $type = '';
    if (is_array($types)) {
        foreach ($types as $t) {
            $type .= $t.";";
        }
    }
    $type = substr($type, 0, strlen($type)-1); //remove last semi-colon
    
    $autofill = ['21741440'=>[$id,$firstName, $lastName, $dateOfBirth, $maritalStatus],
					 '21741491'=>[$type],
					 '21741451'=>[$email,
								  $workEmail,
								  $mobile,
								  $homePhone,
								  $workPhone,
                                  $fax,
								  $skype]];
    $send = self::$wcontroller->sendUrlWithAutofill($form->id, $email, $autofill);
});

$app->run();


?>
