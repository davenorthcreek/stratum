<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use \Stratum\Controller\FormController;
use \Stratum\Controller\CandidateController;
use \Stratum\Model\Candidate;
use \Stratum\Model\CorporateUser;
use Log;
use Cache;
use Storage;

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

        //update availability Note in Bullhorn
        $availability = $formResult->findByWorldApp("Call Availability");
        Log::debug($availability);
        if ($availability) {
            $to_transfer = new \Stratum\Model\Candidate();
            $to_transfer->set("id", $candidate->get("id"));
            $note['comments'] = "Call Availability: ".$availability['Call Availability']['value'];
            Log::debug($note);
            $to_transfer->set("Note", $note);
            $controller->submit_note($to_transfer);
        }

        $controller->updateCandidateStatus($candidate, "Form Completed");

        //Now to store form results in local storage
        $entityBody = Storage::disk('local')->put($candidate->get("id").".txt", $entityBody);
    }
}
