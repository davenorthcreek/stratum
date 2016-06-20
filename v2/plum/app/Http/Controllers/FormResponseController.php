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
use Auth;
use Mail;
use PDF;
use \Kendu\Mpdf\PdfWrapper;
use \mpdf;

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
        $candidate->set("category", $c3->get("category"));
        $candidate->set("customText4", $c3->get("customText4"));
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

    public function exportPDF($request) {
        //prep
        $id = $request->input("id");
        $fc = new \Stratum\Controller\FormController();
        $entityBody = Storage::disk('local')->get($id.'.txt');
        $formResult = $fc->parse($entityBody);
        $cuc = new CorporateUserController();
        $cc = new \Stratum\Controller\CandidateController();
        $bc = new \Stratum\Controller\BullhornController();
        $candidate = new \Stratum\Model\Candidate();
        $candidate->set("id", $id);
        $candidate = $bc->loadFully($candidate);
        $c2 = new \Stratum\Model\Candidate();
        $c2 = $cc->populate($c2, $formResult); //raw WorldApp results
        $c3 = $cc->populateFromRequest(new \Stratum\Model\Candidate(), $request->all(), $c2, $formResult);
        $pdf_data = $this->generatePDF($id, $candidate, $c2, $c3, $bc);
        return view('export_the_pdf')->with($pdf_data);
    }

    private function generatePDF($id, $candidate, $c2, $c3, $bc) {
        //now generate the data
        $pdf_data['sections'] = $bc->submitPDF($candidate, $c2, $c3);
        $pdf_data['id'] = $id;
        $pdf_data['candidate'] = $candidate;
        $name = $candidate->getName();
        $pdf_data['message'] = "Data History for ".$name;
        //now create the pdf
        $mypdf = new mPDF();
        $html = \View::make('export_the_pdf', $pdf_data)->render();
        $mypdf->WriteHTML($html);
        $pdf_data['string'] = $mypdf->Output('', 'S');
        $responses = Storage::disk('local')->put("Test.pdf", $pdf_data['string']);
        Log::debug($responses);
        return $pdf_data;
    }

    private function uploadPDF($candidate, $pdf_data, $bc) {
        $filename = $candidate->get("firstName")."_".$candidate->get("lastName").".pdf";
        $bc->submit_file_as_string($candidate, $filename, $pdf_data['string'], "application/pdf");

    }

    public function confirmValues(Request $request) {
        $id = $request->input("id");
        $fc = new \Stratum\Controller\FormController();
        $cc = new \Stratum\Controller\CandidateController();
        $cuc = new CorporateUserController();
        $entityBody = Storage::disk('local')->get($id.'.txt');
        $formResult = $fc->parse($entityBody);
        $c2 = new \Stratum\Model\Candidate();
        $c2 = $cc->populate($c2, $formResult);
        $form = $formResult->get("form");
        $candidate = new \Stratum\Model\Candidate();
        $candidate = $cc->populateFromRequest($candidate, $request->all(), $c2, $formResult);
        if ($candidate->get("validated") != 'true') {
            $data['errormessage']['message'] = "You must confirm that this form is correct and accurate.  Use the Browser Back button to avoid losing your edits.";
            $error['propertyName'] = "validated";
            $error['severity'] = 'Validation Failure';
            $error['type'] = 'Must Accept Form';
            $data['errormessage']['errors'][] = $error;
            $data['message'] = "Validation Failure: Data Not Uploaded";
        } else {

            $now = new \DateTime();
            $stamp = $now->format("U") * 1000;
            $candidate->set("customDate1", $stamp);
            $candidate->set("customDate2", $stamp);

            //$data['message'] = 'Debugging only, nothing uploaded to Bullhorn';

            $bc = new \Stratum\Controller\BullhornController();
            $c1 = new \Stratum\Model\Candidate();
            $c1->set("id", $id);
            $c1 = $bc->loadFully($c1); //$c1 is the bullhorn existing candidate
            $c2->set("formResult", $formResult); //trojan horse to get formResult to pdf
            $pdf_data = $this->generatePDF($id, $c1, $c2, $candidate, $bc);
            $this->uploadPDF($candidate, $pdf_data, $bc);
            Log::debug("Uploaded PDF record from form");
            // to shortcut to html view
            return view('export_the_pdf')->with($pdf_data);
            $retval = $bc->submit($candidate);
            if (array_key_exists("errorMessage", $retval)) {
                $data['errormessage']['message'] = $retval['errorMessage'];
                $data['errormessage']['errors'] = $retval['errors'];
                $data['message'] = "Problem uploading data";
            } else {
                $data['message'] = "Data Uploaded";
                $bc->updateCandidateStatus($candidate, "Interview Done");
                //$bc->submitPDF($candidate);
                $cuc->flushCandidatesFromCache();
                Log::debug("sending email to admin@stratum-int.com about Interview completion");
                $user = Auth::user();
                $maildata['candidateName'] = $candidate->getName();
                $maildata['candidateID'] = $id;
                $maildata['consultantName'] = $user->name;
                $maildata['date'] = date(DATE_RFC2822);
                Mail::send('email.interview_complete', $maildata, function ($m) use ($candidate, $id) {
                    $m->from('dev@northcreek.ca', 'Plum Data Integration Service');
                    $m->to('admin@stratum-int.com')->subject('Interview Complete '.$candidate->getName().' '.$id);
                });
            }
        }
        $data['candidates'] = $cuc->load_candidates();
        $data['thecandidate'] = $candidate;
        $fc = new \Stratum\Controller\FormController();
        $data['form'] = $fc->setupForm();


        return view('candidate')->with($data);
    }
}
