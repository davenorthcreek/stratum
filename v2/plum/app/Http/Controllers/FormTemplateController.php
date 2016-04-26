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

class FormTemplateController extends Controller
{

    private $wcontroller;
    private $bcontroller;

    private function load_candidates() {
        $cuc = new CorporateUserController();
        return $cuc->load_candidates();
    }

    private function flushCandidatesFromCache() {
        $cuc = new CorporateUserController();
        return $cuc->flushCandidatesFromCache();
    }

    public function getIndexWithId($id) {
        $template = $this->setup_template($id);
        //load the custom Object that contains the template information
        //$obj = $candidate->loadCustomObject(3);
        //$content =   $obj->get("customTextBlock1");


        //default template is from WorldApp
        $content = '<span style="font-size: 10pt; font-family: arial, helvetica, sans-serif;">Hello,</span><br /><br /><span style="font-size: 10pt; font-family: arial, helvetica, sans-serif;">Please find the link below to the Stratum Online Registration Form. </span><br /><br /><span style="font-size: 10pt; font-family: arial, helvetica, sans-serif;">In order to ensure accuracy during the candidate interview you must make sure you complete the form as fully as possible. On the form we are only looking for skills which you would considered an expert. Similarly we only want to know about areas of your work where you have had extensive experience. If there is anything else you would like to tell us about please do so during your registration interview. You may also clarify any points at this time as well.</span><br /><br /><span style="font-size: 10pt; font-family: arial, helvetica, sans-serif;">Should you have any difficulty accessing or using the form please contact the Stratum Administration team who will be happy to assist you. Contact details are in the signature below. </span><br /><br /><span style="font-size: 10pt; font-family: arial, helvetica, sans-serif;">Please click on the following link to access the form. This link is unique to you. Please do not forward it.</span><p><br /><span style="font-size: 10pt; font-family: arial, helvetica, sans-serif;"><strong>[S]</strong></span><br /><br /><br /><span style="font-size: 10pt; font-family: arial, helvetica, sans-serif;">Once you have completed and submitted the form to us, we will be in contact to arrange an interview.</span><br /><br /><span style="font-size: 10pt; font-family: arial, helvetica, sans-serif;">If you have any questions in the meantime please feel free to get in touch.</span><br /><br /><span style="font-size: 10pt; font-family: arial, helvetica, sans-serif;"><br />Kind Regards<br /></span><br /><br /><span style="font-family: arial, helvetica, sans-serif; font-size: 10pt;">The Stratum Team</span></p><p><span style="font-size: 10pt; font-family: arial, helvetica, sans-serif;"><strong>Stratum </strong><span style="color: #808000;"><strong>l</strong><strong> </strong></span>Talent <span style="color: #808000;"><strong>l </strong></span>Management<br /> <strong><br /> </strong>t: +44 (0) 203 627 3271</span></p><p><span style="font-size: 10pt; font-family: arial, helvetica, sans-serif;">a: 24 Greville Street, London, UK, EC1N 8SS</span><br /><span style="font-size: 10pt; font-family: arial, helvetica, sans-serif;">e: admin@stratum-int.com </span><br /><span style="font-size: 10pt; font-family: arial, helvetica, sans-serif;">w: <a href="http://www.stratum-international.com/">www.stratum-international.com</a></span></p><p><br /><br />';

        $template->set('content', $content);

        $data['formTemplate'] = $template;
        $data['page_title'] = "Form Template";
        $data['success'] = false;
        $data['launch'] = false;
        $data['candidates'] = $this->load_candidates();
        return view('formtemplate')->with($data);
    }

    public function getIndex() {
        return getIndexWithId(10809);

  }

  public function postUpdateContent(Request $request) {
      $id = $request->input("id");
      $content = $request->input('contentEditor');
      Log::debug('Updated Content: ');
      Log::debug($content);
      $template = $this->setup_template($id);
      $template->set('content', $content);
      $data['formTemplate'] = $template;
      $data['launch'] = true;
      $data['success'] = false;
      $data['page_title'] = "Form Template";
      $data['candidates'] = $this->load_candidates();
      return view('formtemplate')->with($data);

  }

  public function postLaunchForm(Request $request) {
      $id = $request->input("id");
      Log::debug("Launching form for user ".$id);
      Log::debug($request);
      $content = $request->input('content');
      Log::debug("Content:");
      Log::debug($content);
      $template = $this->setup_template($id);
      $template->set('content', $content);

      $form = $template->get('form');
      $candidate = $template->get('candidate');
      $this->setNewEmailTemplate($template, $content);

      $autofill = $this->prepareAutofill($candidate);
      Log::debug("Candidate name:  ".$candidate->get("name"));
      Log::debug("Candidate email: ".$candidate->get("email"));

      $send = $this->wcontroller->sendUrlWithAutofill($form->id, $candidate->get('email'), $autofill);

      $this->returnEmailTemplate($form, $template->get('emailTemplate'));

      $data['page_title'] = "Form Template";
      $data['success'] = true;
      $data['launch'] = false;
      $data['formTemplate'] = $template;

      //update candidate status
      $this->bcontroller->updateCandidateStatus($candidate, 'Reg Form Sent');
      $this->flushCandidatesFromCache();
      Cache::forget($candidate->get("id"));
      $data['candidates'] = $this->load_candidates();
      return view('formtemplate')->with($data);

  }

  private function setup_template($id) {
      $template = new FormTemplate();
      //load the id from the request
      //$id = $request->getAttribute('entityid');

      $template->set('id', $id);


      //set up the controllers and their loggers
      $this->wcontroller = new \Stratum\Controller\WorldappController();
      $this->bcontroller = new \Stratum\Controller\BullhornController();

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

      $candidate = null;
      if (Cache::has($id)) {
          $candidate = Cache::get($id);
          Log::debug("Loading candidate from cache with id ".$id);
      } else {
          //load the candidate data from Bullhorn
          $candidate = new \Stratum\Model\Candidate();
          $candidate->set("id", $id);
          $this->bcontroller->load($candidate);
          Cache::add($id, $candidate, 60);
      }
      $template->set('candidate', $candidate);

      $owner = $this->findCorporateUser($candidate);

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

  private function findCorporateUser($candidate) {
      //find the corporateUser Owner of this candidate (for From and ReplyTo email address)
      //$owner1 = json_decode($candidate->get("owner"), true); //a json array structure
      $owner1 = $candidate->get("owner");
      Log::debug($owner1);
      $owner = null;
      if (isset($owner1['id'])) {
          $ownerId = $owner1['id'];

          if (Cache::has("user".$ownerId)) {
              $owner = Cache::get("user".$ownerId);
          } else {
              $owner = new \Stratum\Model\CorporateUser();
              $owner->set("id", $ownerId);
              $this->bcontroller->loadCorporateUser($owner);
              Cache::add('user'.$ownerId, $owner, 60);
          }
      } else {
          Log::debug("No ID in owner?");
          Log::debug($owner1['id']);
      }
      return $owner;
  }

  private function setNewEmailTemplate($template, $content) {
      //set up the correct template
      $form = $template->get('form');
      $owner = $template->get('owner');
      $emailTemplate = $template->get('emailTemplate');
      $newTemplate = [];
      $newTemplate['formId'] =    $form->id;
      $newTemplate['from'] =      $owner->get("email");
      $newTemplate['replyTo'] =   $owner->get("email");
      $newTemplate['subject'] =   $emailTemplate->subject;
      $newTemplate['content'] =   $content;

      //set the correct template on the WorldApp server
      $this->wcontroller->setEmailTemplate($newTemplate);

  }

  private function prepareAutofill($candidate) {
      //autofilled fields to be extracted from candidate
      //id,firstName,lastName,dateOfBirth,nickName,email,email2,mobile,phone,workPhone,fax3,pager,customTextBlock2
      $id =               $candidate->get('id');
      $firstName =        $candidate->get("firstName");
      $lastName =         $candidate->get("lastName");
      $dateOfBirth =      $candidate->getDateOfBirthWithFormat("d/m/Y");
      $maritalStatus =    $candidate->get("nickName");
      $email =            $candidate->get("email");
      $workEmail =        $candidate->get("email2");
      $mobile =           $candidate->get("mobile");
      $homePhone =        $candidate->get("phone");
      $workPhone =        $candidate->get("workPhone");
      $fax =              $candidate->get("fax3");
      $skype =            $candidate->get("pager");
      $types =            $candidate->get("customTextBlock2");
      //$types is an array, must translate to String
      $type = '';
      if (is_array($types)) {
          foreach ($types as $t) {
              $type .= $t.";";
          }
      }
      $type = substr($type, 0, strlen($type)-1); //remove last semi-colon

      $autofill = ['21741440'=>[$id, $firstName, $lastName, $dateOfBirth, $maritalStatus],
                   '21741491'=>[$type],
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
