<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use \Stratum\Controller\BullhornController;
use \Stratum\Controller\FormController;
use \Stratum\Model\Candidate;
use Log;
use Cache;

class CandidateController extends Controller
{
  public function index() {
      $id = 10809;
      return $this->show(10809);
  }

  function load($id) {
      $candidate = null;
      if (Cache::has($id)) {
          $candidate = Cache::get($id);
      } else {
          $candidate = \App\Prospect::where("reference_number", $id)->first();
          Cache::add($id, $candidate, 60);
      }
      return $candidate;
  }

  public function show($id) {
      $message = "Candidate Information";
      $candidate = $this->load($id); //reference number
      $data['thecandidate'] = $candidate;
      $fc = new \Stratum\Controller\FormController();
      $data['form'] = $fc->setupForm();
      $cuc = new CorporateUserController();
      $data['candidates'] = $cuc->load_candidates();
      $data['message'] = $message;
      return view('candidate')->with($data);
  }


  public function search(Request $request) {
      $id = $request->input("q");
      $candidate = $this->load($id);
      if ($candidate->getName()) {
          return $this->show($id);
      } else {
        $message = "There is no candidate with ID <strong>$id</strong>.";
        $cuc = new CorporateUserController();
        $data['candidates'] = $cuc->load_candidates();
        $data['message'] = $message;
        return view('error')->with($data);
      }
  }

}
