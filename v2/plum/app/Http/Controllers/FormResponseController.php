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
use Carbon\Carbon;
use \Mpdf\Mpdf;

class FormResponseController extends Controller
{
    public function index($id) {
        //here id is the Prospect reference number
        $cuc = new CorporateUserController();
        $form_controller = new \Stratum\Controller\FormController();
        $ccontroller = new \Stratum\Controller\CandidateController();
        $entityBody = Storage::disk('local')->get($id.'.txt');
        $formResult = $form_controller->parse($entityBody);
        $candidate = new \Stratum\Model\Candidate();
        $candidate = $ccontroller->populate($candidate, $formResult);
        $cc = new CanCon();
        $c3 = $cc->load($id); //Prospect record from local database

        $form = $formResult->get("form");
        $questions = $formResult->get('questions');
        $qbyq = [];
        foreach ($questions as $q1) {
            $qbyq[$q1->get("humanQuestionId")][] = $q1;
            $qbyq[$q1->get('humanQAId')][] = $q1;
        }
        //expand/collapse all button depends on presence of values in the section
        $valuesPresent = [];
        //$form->output_sections();
        $sections = $form->get("sections");
        $headers  = $form->get("sectionHeaders");
        $index = 0;
        foreach ($sections as $sec) {
            $header = $headers[$index];
            $index++;
            $valuePresent = false;
            Log::debug("Got to section $index with label $header, checking for values present");
            foreach ($sec as $qmap) {
                $answers = [];
                $qid = $qmap->getBestId();
                if (!array_key_exists($qid, $qbyq)) {
                    //Log::debug("No $qid in QbyQ");
                } else {
                    $qs = $qbyq[$qid];
                    if (is_array($qs)) {
                        foreach($qs as $q) {
                            $answers = $formResult->getValue($qid, $q, $qmap, $answers);
                            if ($answers['valueFound']) {
                                $valuePresent = true; //never set back to false
                            }
                        }
                    } else if ($qs) {
                        $answers = $formResult->getValue($qid, $qs, $qmap, $answers);
                        if ($answers['valueFound']) {
                            $valuePresent = true; //never set back to false
                        }
                    }
                }
                if (!$valuePresent) {
                    $valuePresent = $qmap->checkforBullhornValue($candidate);
                }
            }
            $valuesPresent[$header] = $valuePresent;
        }
        $data['valuesPresent'] = $valuesPresent;
        $data['form'] = $form;
        $data['qbyq'] = $qbyq;
        $data['candidate'] = $candidate;
        $data['formResult'] = $formResult;
        $data['candidates'] = $cuc->load_candidates();
        $data['page_title'] = "Form Response";
        return view('formresponse')->with($data);
    }

    public function downloadPDF($id) {
        $path = Storage::disk('local')->getDriver()->getAdapter()->applyPathPrefix($id.".pdf");
        Log::debug("Returning path $path");
        return response()->download($path);
    }

    public function exportPDF($request) {
        //prep
        $id = $request->input("id");
        $fc = new \Stratum\Controller\FormController();
        $entityBody = Storage::disk('local')->get($id.'.txt');
        $formResult = $fc->parse($entityBody);
        $cuc = new CorporateUserController();
        $cc = new \Stratum\Controller\CandidateController();
        $candidate = \App\Prospect::where("reference_number", $id)->first();
        $c2 = new \Stratum\Model\Candidate();
        $c2 = $cc->populate($c2, $formResult); //raw WorldApp results
        $c3 = $cc->populateFromRequest(new \Stratum\Model\Candidate(), $request->all(), $c2, $formResult);
        $pdf_data = $this->generatePDF($id, $candidate, $c2, $c3);
        return view('export_the_pdf')->with($pdf_data);
    }

    private function generatePDF($id, $candidate, $c2, $c3) {
        //now generate the data
        $pdf_data['sections'] = $this->submitPDF($candidate, $c2, $c3);
        Log::debug("Back from submitPDF");
        $pdf_data['id'] = $id;
        $pdf_data['candidate'] = $candidate;
        $pdf_data['date'] = Carbon::now();
        $name = $candidate->getName();
        $pdf_data['message'] = "Online Registration Form for ".$name;
        //now create the pdf

        $mypdf = new \Mpdf\Mpdf();

        $html = \View::make('export_the_pdf', $pdf_data)->render();
        $mypdf->WriteHTML($html);
        $pdf_data['string'] = $mypdf->Output('', 'S');
        $responses = Storage::disk('local')->put("$id.pdf", $pdf_data['string']);
        return $pdf_data;
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
        $prospect = \App\Prospect::where("reference_number", $id)->first();
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
            $c2->set("formResult", $formResult); //trojan horse to get formResult to pdf
            $pdf_data = $this->generatePDF($id, $prospect, $c2, $candidate);
            Log::debug("Uploaded PDF record from form");
            // to shortcut to html view
            //return view('export_the_pdf')->with($pdf_data);

            $data['message'] = "Data Uploaded";
            $prospect->form_approved = Carbon::now();
            $prospect->save();
            $cuc->flushCandidatesFromCache();
            Log::debug("sending email to admin@stratum-int.com about Interview completion");
            $user = Auth::user();
            $maildata['candidateName'] = $candidate->getName();
            $maildata['candidateID'] = $id;
            $maildata['consultantName'] = $user->name;
            $maildata['date'] = date(DATE_RFC2822);
            Mail::send('email.interview_complete', $maildata, function ($m) use ($candidate, $id) {
                $m->from('admin@stratum-int.com', 'Stratum Integration Service');
                $m->to('admin@stratum-int.com')->subject('Interview Complete '.$candidate->getName().' '.$id);
            });
        }
        $data['candidates'] = $cuc->load_candidates();
        $data['thecandidate'] = $prospect;
        $fc = new \Stratum\Controller\FormController();
        $data['form'] = $fc->setupForm();


        return view('candidate')->with($data);
    }

    public function submitPDF(\App\Prospect $bhcandidate,
                              \Stratum\Model\Candidate $wacandidate,
                              \Stratum\Model\Candidate $rqcandidate
                             ) {
        Log::debug("At submitPDF");

        $candidates['bh'] = $bhcandidate;
        $candidates['wa'] = $wacandidate;
        $candidates['rq'] = $rqcandidate;

        //going to use FormResponse (web form) as a template for the pdf.
        //so I will get the section headers - but only display section if
        //there is content.

        $formResult = $wacandidate->get("formResult");
        $form = $formResult->get("form");	      //parsed QandA.txt to get questions in order
        $sections = $form->get("sections");       //and sections
        $headers = $form->get("sectionHeaders");  //with appropriate labels
        $sectionData = [];
        for ($i = 0; $i < count($sections); $i++) {
            $section = $sections[$i];
            $label = $headers[$i];
            $retval = $this->exportSectionToPDF($form, $section, $label, $candidates);
            if ($retval) {
                $sectionData[$label] = $retval;
            }
        }
        $sectionData['Skills'] = $this->convertSkillsForPDF($candidates);
        $sectionData['Recommenders'] = $this->convertReferencesForPDF($candidates);
        //$sectionData['Additional Tab'] = $this->convertCustomObjForPDF($candidates, $form);
        $sectionData['Notes'] = $this->convertNotesForPDF($candidates);
        Log::debug("Returning section data from submitPDF");
        return $sectionData;
    }

    private function exportSectionToPDF($form, $section, $label, $candidates) {
        $retVal = [];
        Log::debug("Entering exportSectionToPDF for $label");
        $questionMaps = $form->get('questionMappings');
        foreach ($section as $qmap) {  //qmaps for each question in a section
            $theId = $qmap->getBestId();
                /******************************
                 first pass, find subquestions
                /**************************** */
            $mult = $qmap->get("multipleAnswers"); //boolean
            $type = $qmap->get("type");
            if ($type == "boolean") {
                if (array_key_exists($theId, $questionMaps)) {
                    $sectionQs[$theId] = $qmap;
                }
            } else if ($mult && ($type!='choice') && ($type != 'numeric') && ($type != "list") && ($type != "multichoice")) {
                foreach ($qmap->get("answerMappings") as $q2) {
                    $theId = $q2->getBestId();
                    $sectionQs[$theId] = $q2;
                }
            } else {
                $theId = $qmap->getBestId();
                $sectionQs[$theId] = $qmap;
            }
        }
        if (array_key_exists("Q3", $sectionQs)) {
            //Q3/5/7 were merged into one Nationality widget
            //just display Nationality once
            unset($sectionQs["Q5"]);
            unset($sectionQs["Q7"]);
        }

        //make a cache of longest value to put at end - usually the same value repeated
        $too_long = 70;  //less than 70 is no bother
        $combine_anyway = array("Net Salary", "Gross Salary",
                                "Education Completed: Degree", "Education Completed: Diploma");
        $put_at_end = [];

        Log::debug("Exporting $label to PDF with answers");
        foreach ($sectionQs as $human=>$qmap) {
            if ($qmap->get("type")=="SubsectionEnd") {
                continue;
            }

                /****************************************
                second pass, export to PDF with answers
                ************************************** */
            $value = $this->exportQMToPDF($qmap, $human, $form, $candidates);
            Log::debug("$value found for $human");
            if ($value && ($value['bhvalue'] || $value['wavalue'] || $value['rqvalue'])) {
                $wa = $value['wa'];
                $plum_value = $value['rqvalue'];
                if ($plum_value &&
                   (strlen($plum_value) >= $too_long || in_array($wa, $combine_anyway))
                    ) {
                    $put_at_end[$plum_value][] = $value;
                } else {
                    $retVal[$wa]['Question'][] = $value['wa'];
                    $retVal[$wa]['Bullhorn'] = $value['bhvalue'];
                    $retVal[$wa]['WorldApp'] = $value['wavalue'];
                    $retVal[$wa]['Plum'] = $value['rqvalue'];
                    $retVal[$wa]['repeat'] = 1;
                }
            }
        }
        Log::debug("Now at the put_at_end part of the section $label");
        foreach ($put_at_end as $plum=>$values) {
            $count = count($values);
            $first = true;
            foreach ($values as $value) {
                $wa = $value['wa'];
                $retVal[$wa]['Question'][] = $value['wa'];
                $retVal[$wa]['Bullhorn'] = $value['bhvalue'];
                $retVal[$wa]['WorldApp'] = $value['wavalue'];
                if ($first) {
                    $first = false;
                    $retVal[$wa]['Plum'] = $value['rqvalue'];
                    $retVal[$wa]['repeat'] = $count;
                }
            }
        }
        Log::debug("Now returning");
        return $retVal;
    }

    private function exportQMToPDF($qmap, $human, $form, $candidates) {
        Log::debug("exportQMToPDF for $human");
        $bh = $qmap->get("BullhornField");
        $wa = $qmap->get("WorldAppAnswerName");
        $yn = '';
        if (!$bh) {
            foreach ($qmap->get("answerMappings") as $q2) {
                $bh = $q2->get("BullhornField");
                if ($bh) {
                    if (!$wa) {
                        $wa = $q2->get("WorldAppAnswerName");
                    }
                    break;
                }
            }
        }
        if ($bh == 'Note') {
            return null;
        }
        if (strpos($bh, 'customObject')===0) {
            Log::debug("at a customObject Field: $bh; $wa");
        }
        if ($bh == "customTextBlock2" && $qmap->get("type") == "Text") {
            return null; //only need one 'Discipline' field
        }
        foreach ($candidates as $src=>$candidate) {
            if ($src == 'wa') {
                $fr = $candidate->get("formResult");
                $value = $fr->findByWorldApp($wa);
                Log::debug("looking up $wa for PDF");
                Log::debug($value);
            } else {
                $value = $candidate->get($bh);
            }
            $result_split = [];
            $val_condensed = $candidate->get_a_string($value);
            $value_split = preg_split("/[,;]\s/", $val_condensed);
            foreach ($value_split as $val) {
                if (!in_array($val, $result_split)) {
                    $result_split[] = $val;
                }
            }
            $value = implode(', ', $result_split);
            $ret[$src.'value'] = $value;
        }
        //special fields (for formatting)
        if ($bh == 'skillID' && $ret['rqvalue']) {
            //overwrite if there is something there
            $ret['rqvalue'] = 'See Skills Section below';
        }
        if ($bh == 'specialtyCategoryID') {
            $ret['rqvalue'] = $ret['wavalue'];
        }
        if ($bh == 'files') {
            $url = $ret['wavalue'];
            $url = preg_replace("|file|", "file<br>", $url);
            $ret['wavalue'] = $url;
        }
        if ($qmap->get("type") == 'boolean') {
            $shorter = substr($wa, 0, strrpos($wa, ' '));
            $wa = $shorter;
        }
        $ret['bh'] = $bh;
        $ret['wa'] = $wa;

        if (substr($ret['wavalue'],0,5) == 'true,') {
            $ret['wavalue'] = substr($ret['wavalue'], 5); //remove starting true, from answer
        }

        //now that all the complex work is done, going to collate wa and rq
        //so that we can remove all but one column
        $combine_anyway = array("Net Salary", "Gross Salary",
                                "Equivalent Net Salary",
                                "Guaranteed Cash Allowances",
                                "CTC Package",
                                "Salary, Benefit and Bonus Notes",
                                "Education Completed: Degree",
                                "Education Completed: Diploma",
                                "Professional Memberships and Affiliations",
                                "Professional and Industry Qualifications",
                                "Industry Qualifications and Memberships (Free Text)"
                            );
        if (in_array($wa, $combine_anyway)) {
            //don't mess with this stuff
        } else {
            if ($ret['rqvalue'] && !$ret['wavalue']) {
                $ret['wavalue'] = $ret['rqvalue'];
            }

        }

        Log::debug("Returning from exportQMToPDF");
        return $ret;
    }

    private function convertSkillsForPDF($candidates) {
        //going to discard all but rq candidate (Plum)
        $candidate = $candidates['rq'];
        $skills = $candidate->get("skillID");
        $skill_output = "";
        if ($skills) {
            foreach (preg_split("/\n/", $skills) as $skill) {
                $skill_output .= $skill."<br>\n";
            }
        }
        $ret['List of Skills']['Question'][] = "List of Skills";
        $ret['List of Skills']['Bullhorn'] = '';
        $ret['List of Skills']['WorldApp'] = $skill_output;
        $ret['List of Skills']['Plum'] = $skill_ouput;
        $ret['List of Skills']['repeat'] = 1;
        return $ret;
    }

    private function convertReferencesForPDF($candidates) {
        $bh_refs = $candidates['bh']->get("references");
        $wa_refs = $candidates['wa']->get("references"); //keyed by 'recommenderX'
        $plum_refs = $candidates['rq']->get("references");
        //Log::debug($bh_refs);
        //Log::debug($wa_refs);
        //Log::debug($plum_refs);
        //sort by email (probably not repeated)
        $by_email = [];
        for ($i = 0; $i<count($bh_refs); $i++) {
            $bh_r = $bh_refs[$i];
            $bh_email = $bh_r->get("referenceEmail");
            $by_email[$bh_email]['bh'] = $bh_r;
        }
        for ($i = 0; $i<count($plum_refs); $i++) {
            $plum = $plum_refs[$i];
            $pl_email = $plum->get("referenceEmail");
            $by_email[$pl_email]['rq'] = $plum;
        }
        $index = 0;
        for ($i = 0; $i<count($wa_refs); $i++) {
            $index++;
            $wa_r = $wa_refs['recommender'.$index];
            $wa_email = $wa_r->get("referenceEmail");
            $by_email[$wa_email]['wa'] = $wa_r;
        }
        $refData = [];
        $index = 0;
        foreach($by_email as $email=>$refs) {
            $index++;
            $refData["firstName$index"]['Question'][] = "Recommender $index First Name";
            $refData["lastName$index"]['Question'][] = "Recommender $index Last Name";
            $refData["employer$index"]['Question'][] = "Recommender $index Company / Employer";
            $refData["title$index"]['Question'][] = "Recommender $index Job Title";
            $refData["phone$index"]['Question'][] = "Recommender $index Phone Number";
            $refData["email$index"]['Question'][] = "Recommender $index Email";
            $refData["relationship$index"]['Question'][] = "Recommender $index Your Relationship with the Recommender";
            if (array_key_exists("bh", $refs)) {
                $ref = $refs['bh'];
                $refData["firstName$index"]['Bullhorn'] = $ref->get("referenceFirstName");
                $refData["lastName$index"]['Bullhorn'] = $ref->get("referenceLastName");
                $refData["employer$index"]['Bullhorn'] = $ref->get("companyName");
                $refData["title$index"]['Bullhorn'] = $ref->get("referenceTitle");
                $refData["phone$index"]['Bullhorn'] = $ref->get("referencePhone");
                $refData["email$index"]['Bullhorn'] = $ref->get("referenceEmail");
                $refData["relationship$index"]['Bullhorn'] = $ref->get("customTextBlock1");
            } else {
                $refData["firstName$index"]['Bullhorn'] = '';
                $refData["lastName$index"]['Bullhorn'] = '';
                $refData["employer$index"]['Bullhorn'] = '';
                $refData["title$index"]['Bullhorn'] = '';
                $refData["phone$index"]['Bullhorn'] = '';
                $refData["email$index"]['Bullhorn'] = '';
                $refData["relationship$index"]['Bullhorn'] = '';
            }

            if (array_key_exists("rq", $refs)) {
                $plum = $refs['rq'];
                $refData["firstName$index"]['Plum'] = $plum->get("referenceFirstName");
                $refData["lastName$index"]['Plum'] = $plum->get("referenceLastName");
                $refData["employer$index"]['Plum'] = $plum->get("companyName");
                $refData["title$index"]['Plum'] = $plum->get("referenceTitle");
                $refData["phone$index"]['Plum'] = $plum->get("referencePhone");
                $refData["email$index"]['Plum'] = $plum->get("referenceEmail");
                $refData["relationship$index"]['Plum'] = $plum->get("customTextBlock1");
            } else {
                $refData["firstName$index"]['Plum'] = '';
                $refData["lastName$index"]['Plum'] = '';
                $refData["employer$index"]['Plum'] = '';
                $refData["title$index"]['Plum'] = '';
                $refData["phone$index"]['Plum'] = '';
                $refData["email$index"]['Plum'] = '';
                $refData["relationship$index"]['Plum'] = '';
            }
            if (array_key_exists("wa", $refs)) {
                $wa_r = $refs["wa"];
                $refData["firstName$index"]['WorldApp'] = $wa_r->get("referenceFirstName");
                $refData["lastName$index"]['WorldApp'] = $wa_r->get("referenceLastName");
                $refData["employer$index"]['WorldApp'] = $wa_r->get("companyName");
                $refData["title$index"]['WorldApp'] = $wa_r->get("referenceTitle");
                $refData["phone$index"]['WorldApp'] = $wa_r->get("referencePhone");
                $refData["email$index"]['WorldApp'] = $wa_r->get("referenceEmail");
                $refData["relationship$index"]['WorldApp'] = $wa_r->get("customTextBlock1");
            } else {
                $refData["firstName$index"]['WorldApp'] = '';
                $refData["lastName$index"]['WorldApp'] = '';
                $refData["employer$index"]['WorldApp'] = '';
                $refData["title$index"]['WorldApp'] = '';
                $refData["phone$index"]['WorldApp'] = '';
                $refData["email$index"]['WorldApp'] = '';
                $refData["relationship$index"]['WorldApp'] = '';
            }
            $refData["firstName$index"]['repeat'] = 1;
            $refData["lastName$index"]['repeat'] = 1;
            $refData["employer$index"]['repeat'] = 1;
            $refData["title$index"]['repeat'] = 1;
            $refData["phone$index"]['repeat'] = 1;
            $refData["email$index"]['repeat'] = 1;
            $refData["relationship$index"]['repeat'] = 1;
        }
        return $refData;
    }

    private function convertCustomObjForPDF($candidates, $form) {
        $customObj['bh'] = $candidates['bh']->get("customObject1s");
        $customObj['wa'] = $candidates['wa']->loadCustomObject(1);
        $customObj['rq'] = $candidates['rq']->get("customObject1s");
        foreach($customObj as $src=>$obj) {
            if ($obj) {
                $json[$src] = $obj->marshalToArray();
            }
        }
        $objData = [];
        foreach ($json['wa'][0] as $key=>$attr) {
            if (in_array($key, ["dateAdded", "dateLastModified"])) {
                continue;
            }
            $fieldName = "customObject1.".$key;
            $qmappings = $form->get("BHMappings");
            if (array_key_exists($fieldName, $qmappings)) {
                $qmap = $qmappings[$fieldName][0];
                $wa = $qmap->get("WorldAppAnswerName");
                $objData[$key]['Question'][] = $wa;
            } else {
                continue; //skip ID and person
            }
            $objData[$key]['Bullhorn'] = $customObj['wa']->get_a_string($attr);
            //worldapp
            $wa_obj = $json['wa'][0];
            $wa_attr = '';
            if (array_key_exists($key, $wa_obj)) {
                $wa_attr = $wa_obj[$key];
            }
            $objData[$key]['WorldApp'] = $customObj['wa']->get_a_string($wa_attr);
            //request
            $rq_obj = $json['rq'][0];
            $rq_attr = '';
            if (array_key_exists($key, $rq_obj)) {
                $rq_attr = $rq_obj[$key];
            }
            $objData[$key]['Plum'] = $customObj['wa']->get_a_string($rq_attr);
            $objData[$key]['repeat'] = 1;
        }
        return $objData;
    }

    private function convertNotesForPDF($candidates) {
        $notes['bh'] = $candidates['bh']->get("notes");
        $notes['rq'] = $candidates['rq']->get("Note"); //conversion interview
        $wavalue = $candidates['wa']->get("Note"); //just Availability value here
        $noteData = [];
        //Conversion Interview
        $rqvalue = $notes['rq']['comments'];
        $bhvalue = '';
        if ($notes['bh']) {
            foreach ($notes['bh'] as $note) {
                if ($note['action'] == "Conversion Interview") {
                    $bhvalue = $note['comments'];
                }
            }
        }
        // no WorldApp value for this field
        $noteData['Conversion Interview']['Question'][] = "From Consultant's Confirmation Page";
        $noteData['Conversion Interview']['Bullhorn'] = $bhvalue;
        $noteData['Conversion Interview']['WorldApp'] = $rqvalue;
        $noteData['Conversion Interview']['Plum'] = $rqvalue;
        $noteData['Conversion Interview']['repeat'] = 1;

        //Availability
        $bhvalue2 = '';
        if ($notes['bh']) {
            foreach ($notes['bh'] as $note) {
                if ($note['action'] == "Availability") {
                    $bhvalue2 = $note['comments'];
                }
            }
        }
        $noteData['Availability']['Question'][] = 'Call Availability';
        $noteData['Availability']['Bullhorn'] = $bhvalue2;
        $noteData['Availability']['WorldApp'] = $wavalue;
        $noteData['Availability']['Plum'] = ''; //WorldApp value not edited
        $noteData['Availability']['repeat'] = 1;

        //Reg Form sent
        //only in Bullhorn, kicks off the whole Plum process
        //$bhvalue3 = '';
        //if ($notes['bh']) {
        //    foreach ($notes['bh'] as $note) {
        //        if ($note['action'] == "Reg Form Sent") {
        //            $bhvalue3 = $note['comments'];
        //        }
        //    }
        //}
        //$noteData['Reg Form Sent']['Question'][] = 'Email Template on Website';
        //$noteData['Reg Form Sent']['Bullhorn'] = $bhvalue3;
        //$noteData['Reg Form Sent']['WorldApp'] = '';
        //$noteData['Reg Form Sent']['Plum'] = '';
        //$noteData['Reg Form Sent']['repeat'] = 1;
        return $noteData;
    }
}
