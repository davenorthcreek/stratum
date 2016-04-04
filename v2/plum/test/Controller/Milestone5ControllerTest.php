<?php

namespace Stratum\Test\Controller;

use Stratum\Controller\WorldappController;
use \Monolog\Logger;
use \Monolog\Handler\StreamHandler;


class Milestone5ControllerTest extends \PHPUnit_Framework_TestCase {
	
	protected static $wcontroller;
    protected static $bcontroller;
	
	protected $candidate;
    protected $form;
	protected $log;
	
	private function getWorldappController() {	
		if (!self::$wcontroller) {
			$this->log->debug("Creating new WorldappController in getController");
			self::$wcontroller = new \Stratum\Controller\WorldappController();
		}
		return self::$wcontroller;
	}
    
    private function getBullhornController() {	
		if (!self::$bcontroller) {
			$this->log->debug("Creating new BullhornController in getController");
			self::$bcontroller = new \Stratum\Controller\BullhornController();
		}
		return self::$bcontroller;
	}
	
	public static function setUpBeforeClass() {
		self::$wcontroller = new \Stratum\Controller\WorldappController();
        self::$bcontroller = new \Stratum\Controller\BullhornController();
	}
	
	protected function setUp() {
		$this->log = new Logger('Stratum');
		$this->log->pushHandler(new StreamHandler('src/log/'.date('Y-m-d').'.log', Logger::DEBUG));
		$this->candidate = new \Stratum\Model\Candidate();
		$this->candidate->setLogger($this->log);
		self::$bcontroller->setLogger($this->log);
        self::$wcontroller->setLogger($this->log);
    
    	$this->form = self::$wcontroller->find_form_by_name('Registration Form - Stratum International');
		echo "Active form:\nName: ".$this->form->name."\nID: ".$this->form->id."\n";
		
        
	}

/**  
    public function testGetEmailTemplate() {
        
        $emailTemplate = self::$wcontroller->getEmailTemplate($this->form->id);
        var_dump($emailTemplate);
        
    }
**/
        
    public function testSetEmailTemplate() {

        //userid=0&entitytype=candidate&entityid=99&privatelabelid=105&height=yy&width=xx
        $upload = "http://northcreek.ca/bhwidget/?userid=0&entitytype=candidate&entityid=10809&privatelabelid=105&height=yy&width=xx";
        $url_components = preg_split("/[?&]/", $upload);
        $id = 0;
        foreach ($url_components as $piece) {
            $length = strlen('entityid');
            if (substr($piece, 0, $length) === 'entityid') {
                $eid = preg_split("/=/", $piece);
                $id = $eid[1];
                echo "Found ID ".$id."\n";
            }
        }
        
        $this->candidate->set("id", $id);
        echo "Loading ".$id."\n";
		self::$bcontroller->load($this->candidate);
        
        $this->assertNotNull($this->candidate);
        
        $ownerId = $this->candidate->get("owner")["id"];
        $owner = new \Stratum\Model\CorporateUser();
        $owner->set("id", $ownerId);
        self::$bcontroller->loadCorporateUser($owner);

        $this->assertNotNull($owner);
        
        $emailTemplate = self::$wcontroller->getEmailTemplate($this->form->id);
        
        
        echo "Here is the original template:\n";
        var_dump($emailTemplate);
        
        $newTemplate = [];
        $newTemplate['formId'] =    $this->form->id;
        $newTemplate['from'] =      $owner->get("email");
        $newTemplate['replyTo'] =   $owner->get("email");
        $newTemplate['subject'] =   $emailTemplate->subject;
        
        $types = $this->candidate->get("customTextBlock2");
        $obj = $this->candidate->loadCustomObject(3);
        if (!$obj) {
            //$types is an array, must translate to String
            $type = '';
            if (is_array($types)) {
                foreach ($types as $t) {
                    $type .= $t.";";
                }
            }
            $type = substr($type, 0, strlen($type)-1); //remove last semi-colon
            
            $newTemplate['content'] = $type;
        } else {
            $newTemplate['content'] = $obj->get("customTextBlock1");
        }
        echo "Here is what we are sending to them:\n";
        var_dump($newTemplate);
        
        self::$wcontroller->setEmailTemplate($newTemplate);
        
        $foundTemplate = self::$wcontroller->getEmailTemplate($this->form->id);
        
        echo "This is what we get from the server after the change:\n";
        var_dump($foundTemplate);
        $this->assertEquals($newTemplate['replyTo'], $foundTemplate->replyTo, "Setting Email Template");
        $this->assertEquals($newTemplate['content'], $foundTemplate->content, "Setting Email Template");
        
        //go back to sanity
        
        $returnTemplate = [];
        $returnTemplate['formId'] = $this->form->id;
        $returnTemplate['from'] = $emailTemplate->from;
        $returnTemplate['replyTo'] = $emailTemplate->replyTo;
        $returnTemplate['subject'] = $emailTemplate->subject;
        $returnTemplate['content'] = $emailTemplate->content;
        
        self::$wcontroller->setEmailTemplate($returnTemplate);
        $foundTemplate2 = self::$wcontroller->getEmailTemplate($this->form->id);
        
        $this->assertEquals($emailTemplate->content, $foundTemplate2->content, "Setting email template back the way we found it");
        
        echo "And this is what is on the server now that all is back as it should be:\n";
        var_dump($foundTemplate2);
    }
	
	public function testGetActiveForm() {
    
        //userid=0&entitytype=candidate&entityid=99&privatelabelid=105&height=yy&width=xx
        $upload = "http://northcreek.ca/launch/?userid=0&entitytype=candidate&entityid=10809&privatelabelid=105&height=yy&width=xx";
        $url_components = preg_split("/[?&]/", $upload);
        $id = 0;
        foreach ($url_components as $piece) {
            $length = strlen('entityid');
            if (substr($piece, 0, $length) === 'entityid') {
                $eid = preg_split("/=/", $piece);
                $id = $eid[1];
                echo "Found ID ".$id."\n";
            }
        }
        $this->candidate->set("id", $id);
        echo "Loading ".$id."\n";
		self::$bcontroller->load($this->candidate);
    	$this->assertNotNull($this->candidate);
        
        //autofilled fields to be extracted from candidate
        //id,firstName,lastName,dateOfBirth,nickName,email,email2,mobile,phone,workPhone,fax3,pager,customTextBlock2
        $id =               $this->candidate->get("id");
        $firstName =        $this->candidate->get("firstName");
        $lastName =         $this->candidate->get("lastName");
        $dateOfBirth =      $this->candidate->getDateOfBirthWithFormat("d/m/Y");
        $maritalStatus =    $this->candidate->get("nickName");
        $email =            $this->candidate->get("email");
        $workEmail =        $this->candidate->get("email2");
        $mobile =           $this->candidate->get("mobile");
        $homePhone =        $this->candidate->get("phone");
        $workPhone =        $this->candidate->get("workPhone");
        $fax =              $this->candidate->get("fax3");
        $skype =            $this->candidate->get("pager");
        $types =            $this->candidate->get("customTextBlock2");
        $ownerId =          $this->candidate->get("owner")["id"];
        $owner = new \Stratum\Model\CorporateUser();
        $owner->set("id", $ownerId);
        self::$bcontroller->loadCorporateUser($owner);
        $obj =              $this->candidate->loadCustomObject(3);
        if ($obj) {
            $radio =        $obj->get("text1");
            $content =      $obj->get("textBlock1");
        
            if ($radio == "No – Generic Email") {
                echo "will send generic email\n";
            } else if ($radio == "Yes - Free Text") {
                if ($content == "") {
                    echo "will not send anything, no-op\n";
                } else {
                    echo "will send with content:\n";
                    echo $content."\n";
                }
            } else if ($radio == "Yes - Template") {
                if ($content == "") {
                    echo "must load template data into customObj3->textBlock1\n";
                } else {
                    echo "will send with content:\n";
                    echo $content."\n";
                }
            } else {
                echo "Found this value for Radio: $radio\n";
                echo "Found this value for content:\n$content\n";
            }
        }
        
        //$types is an array, must translate to String
        $type = '';
        if (is_array($types)) {
            foreach ($types as $t) {
                $type .= $t.";";
            }
        }
        $type = substr($type, 0, strlen($type)-1); //remove last semi-colon
        
		$autofill = ['21741440'=>[$id,$firstName, $lastName, $dateOfBirth, $maritalStatus],
					 '21741491'=>[$type],
					 '21741451'=>[$email,
								  $workEmail,
								  $mobile,
								  $homePhone,
								  $workPhone,
                                  $fax,
								  $skype]];
        var_dump($autofill);
		//$send = self::$wcontroller->sendUrlWithAutofill($form->id, $email, $autofill);
		//var_dump($send);
	}
	
}
