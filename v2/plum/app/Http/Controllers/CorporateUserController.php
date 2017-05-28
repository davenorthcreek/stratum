<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use \Stratum\Controller\BullhornController;
use \Stratum\Model\Candidate;
use \Stratum\Model\CorporateUser;
use Log;
use Cache;
use Auth;

class CorporateUserController extends Controller
{
  public function index() {
      $candidates = $this->load_candidates();
      //$candidates = $cuser->getAssocCandidates();
      $data['candidates'] = $candidates;
      return view('admin_template')->with($data);
  }

  public function load_candidates() {
      $candidates = [];
      $cuser = $this->loadCorporateUser(); //Auth::user()
      $prospects = $cuser->prospects()->get();  //returns a collection of Eloquent objects

      foreach ($prospects as $prospect) {
          $status = $prospect->getStatus();
          if ($status == "Interview Done") {
              $candidates['IC'][] = $prospect; //Interview Completed
          } else if ($status == "Form Completed") {
              $candidates['FC'][] = $prospect; //Form Completed
          } else if ($status == "Form Sent") {
              $candidates['RFS'][] = $prospect; //Reg Form Sent
          } else {
              $candidates['No'][] = $prospect; //Nothing done; new prospect
          }
      }
      if ($cuser->is_admin) {
          $prospects = \App\Prospect::all();
          foreach ($prospects as $prospect) {
              $status = $prospect->getStatus();
              if ($status == "Interview Done") {
                  $candidates['All']['IC'][] = $prospect; //Interview Completed
              } else if ($status == "Form Completed") {
                  $candidates['All']['FC'][] = $prospect; //Form Completed
              } else if ($status == "Form Sent") {
                  $candidates['All']['RFS'][] = $prospect; //Reg Form Sent
              } else {
                  $candidates['All']['No'][] = $prospect; //Nothing done; new prospect
              }
          }
      }
      return $candidates;
  }

  public function flushCandidatesFromCache() {
      Cache::flush();
  }

  public function flushCandidateStatusFromCache() {
      $cuser = $this->loadCorporateUser();
      $id = $cuser->get("id");
      Log::debug("Removing corporate user ".$id." from cache");
      Cache::forget("user".$id);

  }

  public function refresh() {
      $this->flushCandidatesFromCache();
      return $this->index();
  }

  private function loadCorporateUser() {
      $user = Auth::user();
      return $user;
  }

  private function replace_key_function($array, $key1, $key2) {
      if ($array) {
          $keys = array_keys($array);
          $index = array_search($key1, $keys);

          if ($index !== false) {
              $keys[$index] = $key2;
              $array = array_combine($keys, $array);
          }
      }
      return $array;
  }

}
