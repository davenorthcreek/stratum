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
use Carbon\Carbon;

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

    	$candidate = new \Stratum\Model\Candidate(); //not Prospect now
    	$candidateController = new \Stratum\Controller\CandidateController();
    	$candidate = $candidateController->populate($candidate, $formResult);
    	Log::debug("Form Completed for ".$candidate->getName());

        //update availability Note in Bullhorn
        $availability = $formResult->findByWorldApp("Call Availability");
        Log::debug($availability);
        $maildata = [];
        if ($availability) {
            //send availability info in email to Prospect owner
            $maildata['availability'] = "Call Availability: ".$availability['Call Availability']['value'];
        }
        Log::debug("looking up prospect with reference number ".$candidate->get("id"));
        $prospect = \App\Prospect::where("reference_number", $candidate->get("id"))->first();
        Log::debug($prospect);
        $owner = $prospect->owner()->first();
        Log::debug($owner);
        $to_email = $owner->email;
        Log::debug("Owner email is ".$to_email);
        Log::debug("Owner name is ".$owner->name);
        if (!$to_email) {
            $to_email = "dev@northcreek.ca";
        }
        Log::debug("sending email to ".$owner->name." at ".$to_email." about Form Submission");
        $maildata['candidateName'] = $candidate->getName();
        $maildata['candidateID'] = $candidate->get("id");
        $maildata['date'] = date(DATE_RFC2822);
        $paths = $this->download_files($candidate);
        Mail::send('email.form_uploaded', $maildata, function ($m) use ($to_email, $candidate, $paths) {
            $m->from('admin@stratum-int.com', 'Stratum Integration Service');
            $m->to($to_email)->subject('Form Submission from '.$candidate->getName().' '.$candidate->get("id"));
            foreach ($paths as $filename => $body) {
                $m->attachData($path, $filename);
            }
        });

        Mail::send('email.form_uploaded', $maildata, function($m) use ($candidate, $paths) {
            $m->from('admin@stratum-int.com', 'Stratum Integration Service');
            $m->to('admin@stratum-int.com')->subject('Form Submission from '.$candidate->getName().' '.$candidate->get("id"));
            foreach ($paths as $filename => $body) {
                $m->attachData($path, $filename);
            }
        });

        $prospect->form_returned = Carbon::now();
        $prospect->save();

        //Now to store form results in local storage
        $entityBody = Storage::disk('local')->put($candidate->get("id").".txt", $entityBody);
    }

    /**
    * @return $paths a list of files to attacht to the email
    */

    private function download_files($candidate) {
        $paths = [];
        $filelist = $candidate->get("files");
        $this->log_debug("Going to try to upload files $filelist");

        $files = explode(", ", $filelist);
        $filename = '';
        foreach ($files as $url) {
            $this->log_debug("Going to try to upload file $url");
            $ch = curl_init($url);

            curl_setopt($ch, CURLOPT_HEADER, true);
            curl_setopt($ch, CURLOPT_HEADERFUNCTION, array($this,'readHeader'));
            curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
            curl_setopt($ch, CURLOPT_BINARYTRANSFER, 1);
            $response = curl_exec($ch);
            $header_size = curl_getinfo($ch, CURLINFO_HEADER_SIZE);
            $header = substr($response, 0, $header_size);
            $body = substr($response, $header_size);
            curl_close($ch);
            $this->var_debug($this->responseHeaders[$url]);
            if ($this->responseHeaders[$url]) { //may be null if link expired
                $filename = '';
                foreach ($this->responseHeaders[$url] as $header_item) {
                    if (preg_match('/filename="(.*?)";/', $header_item, $matches)) {
                        $filename = $matches[1];
                    }
                }
                $paths[$filename] = $body;
            }
        }
        return $paths;
    }
}
