<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Http\Controllers\CandidateController as CanCon;
use \Stratum\Controller\FormController;
use \Stratum\Controller\CandidateController;
use \Stratum\Model\Candidate;
use \Stratum\Model\CorporateUser;
use Log;
use Cache;
use Storage;
use Mail;

class UploadController extends Controller
{
    public function upload(Request $request) {
        $entityBody = file_get_contents('php://input');
    	Log::debug($entityBody);
    	$formController = new \Stratum\Controller\FormController();
    	Log::debug("parsing input data");
    	$formResult = $formController->parse($entityBody);
        Log::Debug("parsed input data");
    	//form has updated mappings for each question

    	$candidate = new \Stratum\Model\Candidate();
    	$candidateController = new \Stratum\Controller\CandidateController();
    	$candidate = $candidateController->populate($candidate, $formResult);
    	Log::debug("Form Completed for ".$candidate->getName());

        $controller = new \Stratum\Controller\BullhornController();

        //upload files from WorldApp to Bullhorn
        $controller->submit_files($candidate);

        //update availability Note in Bullhorn
        $availability = $formResult->findByWorldApp("Call Availability");
        Log::debug($availability);
        if ($availability) {
            $note['comments'] = "Call Availability: ".$availability['Call Availability']['value'];
            $note['action'] = 'Availability';
            Log::debug($note);
            $candidate->set("Note", $note);
            $controller->submit_note($candidate);
        }
        $cc = new CanCon();
        $c3 = $cc->load($candidate->get("id")); //Bullhorn Candidate record, from cache if available
        $owner = $c3->get("owner");
        /*
        array (
          'id' => 10237,
          'firstName' => 'Stratum',
          'lastName' => 'API',
        )
        */
        $cuser = new \Stratum\Model\CorporateUser();
        $cuser->set("id", $owner['id']);
        $cuser = $controller->loadCorporateUser($cuser);
        $to_email = $cuser->get("email");
        if (!$to_email) {
            $to_email = "dev@northcreek.ca";
        }
        Log::debug("sending email to ".$cuser->getName()." at ".$to_email." about Form Submission");
        $maildata['candidateName'] = $candidate->getName();
        $maildata['candidateID'] = $candidate->get("id");
        $maildata['date'] = date(DATE_RFC2822);
        Mail::send('email.form_uploaded', $maildata, function ($m) use ($to_email, $candidate) {
            $m->from('dev@northcreek.ca', 'Plum Data Integration Service');
            $m->to($to_email)->subject('Form Submission from '.$candidate->getName().' '.$candidate->get("id"));
        });

        $controller->updateCandidateStatus($candidate, "Form Completed");

        //Now to store form results in local storage
        $entityBody = Storage::disk('local')->put($candidate->get("id").".txt", $entityBody);
    }
}
