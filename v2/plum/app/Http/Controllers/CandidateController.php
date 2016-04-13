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

  public function show($id) {
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
      $data['thecandidate'] = $candidate;
      $fc = new \Stratum\Controller\FormController();
      $data['form'] = $fc->setupForm();
      $cuc = new CorporateUserController();
      $data['candidates'] = $cuc->load_candidates();
      return view('candidate')->with($data);
  }

}
