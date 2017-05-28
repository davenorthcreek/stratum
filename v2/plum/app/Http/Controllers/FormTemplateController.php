<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;
use \Stratum\Controller\BullhornController;
use \Stratum\Controller\WorldappController;
use \Stratum\Model\FormTemplate;
use \Stratum\Model\Candidate;

use Log;
use Cache;
use Auth;
use Mail;
use Carbon\Carbon;

class FormTemplateController extends Controller
{

    private $wcontroller;
    private $bcontroller;

    public function initiate() {
        $data['owner'] = Auth::user();
        $cuc = new CorporateUserController();
        $data['candidates'] = $cuc->load_candidates();
        $data['message'] = 'Initiate Candidate Workflow';
        return view('initiate')->with($data);
    }

    public function initiateWorkflow(Request $request) {
        $prospect = new \App\Prospect();
        $prospect->email = $request->input('email');
        $prospect2 = \App\Prospect::where("email", $request->input("email"))->first();
        if ($prospect2) {
            Log::debug("updating rather than creating");
            $prospect = $prospect2;
        }
        $prospect->discipline = implode(';', $request->input('discipline'));
        $prospect->first_name = $request->input('firstName');
        $prospect->last_name = $request->input('lastName');
        $prospect->reference_number = $request->input('referenceNumber');
        $prospect->owner_id = Auth::user()->id;
        $prospect->save();

        return $this->getIndexWithId($prospect->reference_number, $prospect);
    }

    public function getIndexWithId($id, $candidate = null) {
        if (!$candidate) {
            $candidate = \App\Prospect::where("reference_number", $id)->first();
        }
        $template = $this->setup_template($candidate->email, $candidate);
        //load the custom Object that contains the template information
        //$obj = $candidate->loadCustomObject(3);
        //$content =   $obj->get("customTextBlock1");
        $firstname = $candidate->first_name;
        $owner = $template->get('owner');  //Auth::user()
        $owner_email = $owner->email;
        if (!$owner_email) {
            Log::debug("No email in owner record");
            $owner_email = "admin@stratum-int.com";
        }
        $owner_phone = $owner->phone;
        if (!$owner_phone) {
            Log::debug("No phone in owner record");
            $owner_phone = "+44 (0) 203 627 3271";
        }
        $owner_name  = $owner->name;
        $owner_sig   = $owner->email_signature;

        //default template is from WorldApp
        $content = <<<EOC
<span style="font-size: 10pt; font-family: arial, helvetica, sans-serif;"><p>Hi $firstname,</p>

<p>Thank you for registering with Stratum International.</p>

<p>Before we arrange an interview we&rsquo;d like a bit more information about you.</p>

<p>Our online Registration Form will take ten to fifteen minutes to complete and will form the basis of your record with us, so it&rsquo;s important your answers are comprehensive and accurate.</p>

<p>We&rsquo;ll ask about the skills areas in which you have expertise and the areas of mining in which you have the most experience. If you feel the form misses any vital information you can bring it up in your interview.</p>

<p>Click on the link below to start completing the form. You can save your progress at any stage and return to it later but please aim to complete the form in the next ten days.</p>

<p><strong>[S]</strong></p>

<p>Note: this link is unique to you. Please do not forward it or share it.</p>

<p>Once you have completed the form we&rsquo;ll get in touch to arrange your interview.</p>

<p>If you have any difficulty accessing or using the form you can contact me on <a href="tel:$owner_phone">$owner_phone</a>.  Alternatively contact our admin team at <a href="mailto:admin@stratum-int.com">admin@stratum-int.com</a> and they&rsquo;ll be very happy to help.</p>

<p>&nbsp;</p>

$owner_sig
EOC;

    $old_email_sig = <<<EOS
<p>Kind Regards</p>

<p>The Stratum Team<br />
<strong>Stratum l </strong>Talent <strong>l </strong>Management</p>

<p>t: <a href="tel:%2B44%20%280%29%20203%20627%203271">+44 (0) 203 627 3271</a><br />
a: 24 Greville Street, London, UK, EC1N 8SS<br />
e: <a href="mailto:admin@stratum-int.com">admin@stratum-int.com</a><br />
w: <a href="http://www.stratum-international.com/">www.stratum-international.com</a></p>
EOS;

        $template->set('content', $content);

        $data['formTemplate'] = $template;
        $data['page_title'] = "Form Template";
        $data['success'] = false;
        $data['launch'] = false;
        $data['candidate'] = $candidate;
        $data['email'] = $candidate->email;
        $data['id'] = $candidate->reference_number;
        $cuc = new CorporateUserController();
        $data['candidates'] = $cuc->load_candidates();
        return view('formtemplate')->with($data);
    }

  public function postUpdateContent(Request $request) {
      $id = $request->input("id"); //now the reference_number of the Prospect
      $content = $request->input('contentEditor');
      Log::debug('Updated Content: (skipping, too long)');
      //Log::debug($content);
      if (!$candidate) {
          $candidate = \App\Prospect::where("reference_number", $id)->first();
      }
      $template = $this->setup_template($candidate->email, $candidate);
      $template->set('content', $content);
      //Log::debug($request);
      if ($request->hasFile("attachmentFile")) {
          Log::debug("Request has file");
          if ($request->file("attachmentFile")->isValid()) {
              Log::debug("File uploaded!");
              $attachment = $request->file("attachmentFile");
              $attachmentName = $attachment->getClientOriginalName();
              Log::debug("Name: ".$attachmentName);
              $path = $attachment->getRealPath();
              $att_data = file_get_contents($path);
              $request->session()->push('attachments',[$attachmentName=>$att_data]);
              $atts = $template->get("attachments");
              $atts[] = ['filename'=>$attachmentName];
              $template->set('attachments', $atts);
          }
      }
      $data['formTemplate'] = $template;
      $data['launch'] = true;
      $data['success'] = false;
      $data['page_title'] = "Form Template";
      $data['candidate'] = $candidate;
      $data['id'] = $id;
      $cuc = new CorporateUserController();
      $data['candidates'] = $cuc->load_candidates();
      return view('formtemplate')->with($data);

  }

  public function postLaunchForm(Request $request) {
      $id = $request->input("id"); //again, this is the reference_number of the Prospect
      if (!$candidate) {
          $candidate = \App\Prospect::where("reference_number", $id)->first();
      }
      Log::debug("Launching form for user ".$id);
      Log::debug($request);
      $content = $request->input('content');
      Log::debug("Content:");
      Log::debug($content);
      $template = $this->setup_template($candidate->email, $candidate);
      $template->set('content', $content);

      $form = $template->get('form');
      $candidate = $template->get('candidate');
      $this->setNewEmailTemplate($template, $content, $request->session());

      $autofill = $this->prepareAutofill($candidate);
      Log::debug("Candidate name:  ".$candidate->getName());
      Log::debug("Candidate email: ".$candidate->email);

      $send = $this->wcontroller->sendUrlWithAutofill($form->id, $candidate->email, $autofill);


      $this->returnEmailTemplate($form, $template->get('emailTemplate'));

      $candidate->form_sent = Carbon::now();
      $candidate->save();

      $this->send_email_to_owner($content, $candidate);
      $this->send_email_to_admin($candidate);

      $cuc = new CorporateUserController();
      $data['candidates'] = $cuc->load_candidates();
      $data['id'] = $id;
      $data['page_title'] = "Form Template";
      $data['success'] = true;
      $data['launch'] = false;
      $data['formTemplate'] = $template;
      $data['candidate'] = $candidate;

      return view('formtemplate')->with($data);

  }


  function send_email_to_owner($message, $candidate) {
    //Now send email copy to owner
    $owner = Auth::user();
    $owner_email = $owner->email;
    $ref = $candidate->reference_number;
    Log::debug("sending email to $owner_email about Interview being sent");
    $maildata['candidateName'] = $candidate->getName();
    $maildata['candidateID'] = $ref;
    $maildata['consultantName'] = $owner->name;
    $maildata['date'] = date(DATE_RFC2822);
    $maildata['content'] = $message;
    Mail::send('email.form_confirmation', $maildata, function ($m) use ($candidate, $ref, $owner_email) {
        $m->from('admin@stratum-int.com', 'Stratum Integration Service');
        $m->to($owner_email)->subject('Confirmation of Form Sent to '.$candidate->getName().' '.$ref);
    });

  }

  function send_email_to_admin($candidate) {

      $ref = $candidate->reference_number;
      Log::debug("sending email to admin@stratum-int.com about Interview being sent");
      $user = Auth::user();
      $maildata['candidateName'] = $candidate->getName();
      $maildata['candidateID'] = $candidate->reference_number;
      $maildata['consultantName'] = $user->name;
      $maildata['date'] = date(DATE_RFC2822);
      Mail::send('email.form_sent', $maildata, function ($m) use ($candidate, $ref) {
          $m->from('admin@stratum-int.com', 'Stratum Integration Service');
          $m->to('admin@stratum-int.com')->subject('Form Sent to '.$candidate->getName().' '.$ref);
      });

  }

  private function setup_template($email, $candidate) {
      $template = new FormTemplate();
      //load the id from the request
      //$id = $request->getAttribute('entityid');


      $template->set('email', $email);


      //set up the controllers and their loggers
      $this->wcontroller = new \Stratum\Controller\WorldappController();

      $formName = 'Registration Form - Stratum International';
      if (Cache::has($formName)) {
          Log::debug("Loading form from cache");
          $form = Cache::get($formName);
      } else {
          //find the correct form
          $form = $this->wcontroller->find_form_by_name($formName);
          Cache::add($formName, $form, 60);
      }
      $template->set('form', $form);

      /***
      $candidate = null;
      if (Cache::has($email)) {
          $candidate = Cache::get($email);
          Log::debug("Loading candidate from cache with email ".$email);
      } else {
          //load the candidate data from local sources?
          $candidate = new \Stratum\Model\Candidate();
          $candidate->set("email", $email);
          Cache::add($email, $candidate, 60);
      }
      ***/
      $template->set('candidate', $candidate); //is now an App\Prospect

      $owner = Auth::user();

      $template->set('owner', $owner);

      //download and store the original template (so we can convert back)
      $emailTemplate = null;
      if (Cache::has('original')) {
          $emailTemplate = Cache::get('original');
      } else {
          $emailTemplate = $this->wcontroller->getEmailTemplate($form->id);
          Cache::add('original', $emailTemplate, 60);
      }
      $template->set('emailTemplate', $emailTemplate);

      return $template;
  }

  private function getAttachments($session) {
      $attachments = null;
      if ($session->has('attachments')) {
          Log::debug("add Attachments");

          foreach($session->get('attachments') as $attach) {
              foreach ($attach as $name => $data) {
                  Log::debug($name);
                  $attachments['name'] = $name;
                  $attachments['attachment'] = $data;
              }
          }
      }
      return $attachments;
  }

  private function setNewEmailTemplate($template, $content, $session) {
      //set up the correct template
      $form = $template->get('form');
      $owner = $template->get('owner');
      $emailTemplate = $template->get('emailTemplate');
      $newTemplate = [];
      $newTemplate['formId'] =    $form->id;
      $newTemplate['from'] =      $owner->email;
      $newTemplate['replyTo'] =   $owner->email;
      $newTemplate['subject'] =   $emailTemplate->subject;
      $newTemplate['content'] =   $content;

      $attachments = $this->getAttachments($session);

      //set the correct template on the WorldApp server
      $response = $this->wcontroller->setEmailTemplate($newTemplate, $attachments);

  }

  private function prepareAutofill($candidate) {
        //autofilled fields to be extracted from candidate
        //id,firstName,lastName,dateOfBirth,nickName,email,email2,mobile,phone,workPhone,fax3,pager,customTextBlock2
        $id =               $candidate->reference_number;
        $firstName =        $candidate->first_name;
        $lastName =         $candidate->last_name;
        $email =            $candidate->email;
        $workEmail =        '';
        $mobile =           '';
        $homePhone =        '';
        $workPhone =        '';
        $fax =              '';
        $skype =            '';
        $type =             $candidate->discipline;
        $autofill = ['21741440'=>[$id, $firstName, $lastName],
                     '21741491'=>[$type], // should be 21741516
                     '21741451'=>[$email,
                                  $workEmail,
                                  $mobile,
                                  $homePhone,
                                  $workPhone,
                                  $fax,
                                  $skype]];
        return $autofill;
    }

  private function returnEmailTemplate($form, $emailTemplate) {
      //return the email template to the original
      $returnTemplate = [];
      $returnTemplate['formId'] =  $form->id;
      $returnTemplate['from'] =    $emailTemplate->from;
      $returnTemplate['replyTo'] = $emailTemplate->replyTo;
      $returnTemplate['subject'] = $emailTemplate->subject;
      $returnTemplate['content'] = $emailTemplate->content;

      $this->wcontroller->setEmailTemplate($returnTemplate);

  }
}
