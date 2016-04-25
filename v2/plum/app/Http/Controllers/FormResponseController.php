<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use \Stratum\Controller\FormController;
use \Stratum\Controller\CandidateController;
use \Stratum\Model\FormResult;
use \Stratum\Model\Candidate;
use Storage;
use Log;

class FormResponseController extends Controller
{
    public function index($id) {
        $cuc = new CorporateUserController();
        $controller = new \Stratum\Controller\FormController();
        $ccontroller = new \Stratum\Controller\CandidateController();
        $entityBody = Storage::disk('local')->get($id.'.txt');
        $formResult = $controller->parse($entityBody);
        $candidate = new \Stratum\Model\Candidate();
        $candidate = $ccontroller->populate($candidate, $formResult);
        $form = $formResult->get("form");
        $questions = $formResult->get('questions');
        $qbyq = [];
        foreach ($questions as $q1) {
            $qbyq[$q1->get("humanQuestionId")][] = $q1;
            $qbyq[$q1->get('humanQAId')][] = $q1;
        }
        //expand/collapse all button
        $data['form'] = $form;
        $data['qbyq'] = $qbyq;
        $data['candidate'] = $candidate;
        $data['formResult'] = $formResult;
        $data['candidates'] = $cuc->load_candidates();
        $data['page_title'] = "Form Response";
        return view('formresponse')->with($data);
    }

  public function confirmValues(Request $request) {
      $id = $request->input("id");
      $fc = new \Stratum\Controller\FormController();
      $cc = new \Stratum\Controller\CandidateController();
      $entityBody = Storage::disk('local')->get($id.'.txt');
      $formResult = $fc->parse($entityBody);
      $c2 = new \Stratum\Model\Candidate();
      $c2 = $cc->populate($c2, $formResult);
      $form = $formResult->get("form");
      $candidate = new \Stratum\Model\Candidate();
      $candidate = $cc->populateFromRequest($candidate, $request->all(), $c2, $formResult);
      $bc = new \Stratum\Controller\BullhornController();
      $bc->submit($candidate);
      $bc->updateCandidateStatus($candidate, "IC");
      $data['thecandidate'] = $candidate;
      $fc = new \Stratum\Controller\FormController();
      $data['form'] = $fc->setupForm();
      $cuc = new CorporateUserController();
      $cuc->flushCandidatesFromCache();
      $data['candidates'] = $cuc->load_candidates();
      $data['message'] = "Data Uploaded";
      return view('candidate')->with($data);
  }
}
