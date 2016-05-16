<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use App\Http\Controllers\CandidateController as CanCon;
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
        $cc = new CanCon();
        $c3 = $cc->load($id); //Bullhorn Candidate record, from cache if available
        Log::debug("Bullhorn Category:");
        Log::debug($c3->get("category"));
        Log::debug($c3->get("categoryID"));
        $candidate->set("categoryID", $c3->get("categoryID"));
        $candidate->set("customText4", $c3->get("customText4"));
        $form = $formResult->get("form");
        $questions = $formResult->get('questions');
        $qbyq = [];
        foreach ($questions as $q1) {
            $qbyq[$q1->get("humanQuestionId")][] = $q1;
            $qbyq[$q1->get('humanQAId')][] = $q1;
        }

        $wac = new \Stratum\Controller\WorldappController();
        $theForm = $wac->find_active_form();
        $theQuestions = $wac->get_questions($theForm->id);
        //Log::debug($theQuestions);
        $qid = 0;
        foreach($theQuestions as $theQ) {
            $text = $theQ->text;
            if (strpos($text, "If you have not already sent us a copy of your CV")) {
                //this is the question we care about
                $qid = $theQ->questionId;
            }
        }
        if ($qid) {
            $respondentId = $formResult->get("respondentId");
            Log::debug("Getting response for $qid and $respondentId");
            //$response = $wac->get_response($qid, $respondentId);
            //Log::debug($response);
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
      $now = new \DateTime();
      $stamp = $now->format("U") * 1000;
      $candidate->set("customDate1", $stamp);
      $candidate->set("customDate2", $stamp);
      
      $bc = new \Stratum\Controller\BullhornController();
      //$bc->submit($candidate);
      //$bc->updateCandidateStatus($candidate, "Interview Done");
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
