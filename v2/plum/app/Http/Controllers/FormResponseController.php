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
      $data['form'] = $formResult->get("form");
      $data['candidate'] = $candidate;
      $data['formResult'] = $formResult;
      $data['candidates'] = $cuc->load_candidates();
      $data['page_title'] = "Form Response";
      return view('formresponse')->with($data);
  }
}
