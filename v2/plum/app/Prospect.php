<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Log;

class Prospect extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'first_name', 'last_name', 'email', 'discipline', 'reference_number', 'owner_id'
    ];

    /**
     * The attributes excluded from the model's JSON form.
     *
     * @var array
     */
    protected $hidden = [

    ];


	/**
     * Get the Owner (User) record associated with the Prospect.
     */
    public function owner()
    {
        return $this->belongsTo('App\User', 'owner_id');
    }

    public function getName() {
        return $this->first_name." ".$this->last_name;
    }


    /**
     *  Get the Status of the Prospect based on timestamps in the Prospect record
     */
    public function getStatus() {
        $status = "No";
        if ($this->form_sent != 0) {
            Log::debug($this->form_sent);
            $status = "Form Sent";
        }
        if ($this->form_returned != 0) {
            Log::debug($this->form_returned);
            $status = "Form Completed";
        }
        if ($this->form_approved != 0) {
            Log::debug($this->form_approved);
            $status = "Interview Done";
        }
        Log::debug("Status is $status");
        return $status;
    }


    public function marshalToJSON() {
        return $this->toJson();
    }

    public function get($key) {
        return $this->__get($key);
    }

    public function set($key) {
        return $this->__set($key);
    }

    private function get_suitable_label($item) {
        $array =
        [   'email' => "Email",
            'first_name' => "First Name",
            'last_name'  => "Last Name",
            'discipline' => "Discipline",
            'reference_number' => 'Reference Number'
        ];
        if (array_key_exists($item, $array)) {
            return $array[$item];
        } else {
            Log::debug("Can't find label for $item");
            return "Label";
        }
    }

    public function get_a_string($thing) {
        $new_string = $thing; //not a reference
        if (is_bool($new_string)) {
            $this->log_debug("Boolean get_a_string");
            $this->var_debug($new_string);
            if ($new_string) {
                return "true";
            } else {
                return "false";
            }
        } else if (is_array($thing)) {
            $new_array = [];
            foreach ($thing as $subthing) {
                $new_array[] = $this->get_a_string($subthing);
            }
            $new_string = implode(', ', $new_array);
        }
        if (is_a($thing, "\Stratum\Model\ModelObject")) {
            $new_string = get_class($thing);
            $this->log_debug("Found an object $new_string");
        }
        $new_string = trim($new_string);
        return $new_string;
    }

    public function log_this($object) {
        Log::debug($object);
    }

    public function exportSummaryToHTML(\Stratum\Model\Form $form) {
        echo '<div class="box box-primary">';
        echo '<div class="box-header with-border">';
        echo "\n\t<h3 class='box-title'>Candidate Data</h3>";
        echo "\n</div>";
        echo "\n<div class='box-body'>\n";
        echo "\n<div class='table-responsive'>";
        echo "\n<table class='table'>\n";
        echo "\n<thead>\n<tr>";
        echo "\n<th><button class='btn btn-secondary btn-sm'>Field Name</button></th>";
        echo "\n<th><label>Value</label></th>\n";
        echo "\n</tr></thead>";
        echo "\n<tbody>";
        $summary = ["email", "first_name", "last_name", "discipline", "reference_number"];
        foreach ($summary as $item) {
            $value = $this->get($item);
            //we have value
            $wa = $form->getWorldAppLabel($item);
            if (!$wa) {
                $wa = $this->get_suitable_label($item);
            }
            //we have human-readable label
            //let's display this!
            echo "\n<tr>";
            //echo "\n<div class='form-group'>";
            echo "\n<td>";
            echo "\n<button class='btn btn-secondary btn-sm'>".$wa."</button>";
            echo "\n</td><td>";
            echo "\n<label>$value</label>\n";
            echo "\n</td></tr>";
            //echo "</div>\n";
        }
        echo "\n</tbody>";
        echo "\n</table>";
        echo "</div>\n"; //table-responsive
        echo "</div>\n"; //box-body
        echo "</div>\n"; //box

    }
}
