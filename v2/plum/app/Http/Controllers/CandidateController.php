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
          //load the candidate data from Bullhorn
          $candidate = new \Stratum\Model\Candidate();
          $candidate->set("id", $id);
          $bc = new BullhornController();
          $bc->load($candidate);
          Cache::add($id, $candidate, 60);
      }
      return $candidate;
  }

  public function show($id) {
      $message = "Candidate Information";
      $candidate = $this->load($id);
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
      if ($candidate) {
          return $this->show($id);
      } else {
        $message = "There is no candidate with ID $id";
        $cuc = new CorporateUserController();
        $data['candidates'] = $cuc->load_candidates();
        $data['message'] = $message;
        return view('admin_template')->with($data);
      }
  }

}
