@extends('admin_template')

@section('content')
    <div class='row'>
        <div class='col-md-9'>
            @if(isset($errormessage))
                <div class="panel panel-danger">
                    <div class="panel-heading">{{ $errormessage['message'] }}</div>
                    <div class="panel-body">
                        @foreach ($errormessage['errors'] as $error)
                            Property:&nbsp;<strong>{{$error['propertyName'] }}</strong><br>
                            Value:&nbsp;&nbsp;&nbsp;<strong>{{$thecandidate->get_a_string($thecandidate->get($error['propertyName'])) }}</strong><br>
                            Severity:&nbsp;<strong>{{$error['severity'] }}</strong><br>
                            Issue:&nbsp;&nbsp;&nbsp;<strong>{{$error['type'] }}</strong><br><hr>
                        @endforeach
                    </div>
                </div>
            @endif
            <!-- Box -->
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">{{ $message }}</h3>
                </div>
                <div class="box-body">

                    <form method="post" id="confirmValues" action='{{route("initiateForm")}}' >
                        {{csrf_field()}}
                        <div class='form-group'>
                            <label for='email'>Candidate First Name</label>
                            <input class='form-control' name='firstName' type='text' value='' required="true">
                        </div>
                        <div class='form-group'>
                            <label for='email'>Candidate Last Name</label>
                            <input class='form-control' name='lastName' type='text' value='' required="true">
                        </div>
                        <div class='form-group'>
                            <label for='email'>Email</label>
                            <input class='form-control' name='email' type='email' value='' required="true">
                        </div>
                        <div class='form-group'>
                            <label for='email'>Candidate Reference Number</label>
                            <input class='form-control' name='referenceNumber' type='text' value='' required="true">
                        </div>

                        <div class='form-group'>
                            <label for='discipline[]'>Discipline</label>
                            <select class='form-control select2' multiple='multiple' id='discipline[]' data-placeholder='Discipline' name='discipline[]' required="true" style='width: 100%;'>
                                <option></option>
                                <option VALUE="EO:Executive (Board)">EO:Executive (Board)</option>
                                <option VALUE="EX:Geology">EX:Geology</option>
                                <option VALUE="IN:Port">IN:Port</option>
                                <option VALUE="IN:Rail">IN:Rail</option>
                                <option VALUE="MO:General &amp; Mine Management">MO:General &amp; Mine Management</option>
                                <option VALUE="MO:Maintenance">MO:Maintenance</option>
                                <option VALUE="MO:Metallurgy &amp; Processing">MO:Metallurgy &amp; Processing</option>
                                <option VALUE="MO:Mine Engineering &amp; Mine Geology">MO:Mine Engineering &amp; Mine Geology</option>
                                <option VALUE="MO:Production">MO:Production</option>
                                <option VALUE="PR:Construction">PR:Construction</option>
                                <option VALUE="PR:Engineering">PR:Engineering</option>
                                <option VALUE="PRC:Project Controls">PRC:Project Controls</option>
                                <option VALUE="PR:Project Management">PR:Project Management</option>
                                <option VALUE="SE:Commercial">SE:Commercial</option>
                                <option VALUE="SE:Finance">SE:Finance</option>
                                <option VALUE="SE:HSE">SE:HSE</option>
                                <option VALUE="SE:Human Resources &amp; Training">SE:Human Resources &amp; Training</option>
                                <option VALUE="SE:Procurement">SE:Procurement</option>
                                <option VALUE="SE:Supply &amp; Logistics">SE:Supply &amp; Logistics</option>
                                <option VALUE="SE:Sustainability (incl. CSR &amp; Environment)">SE:Sustainability (incl. CSR &amp; Environment)</option>
                                <option VALUE="SE:Other">SE:Other</option>
                                <option VALUE="SE:IT">SE:IT</option>
                                <option VALUE="SE:Legal">SE:Legal</option>
                            </select>
                        </div>
                        <div class="box box-primary">
                            <div class="box-header with-border">
                                <button type="submit" class="btn btn-danger" id="confirmV">Start Stratum Workflow for this Candidate</button>
                            </div>
                        </div>
                    </form>
                </div><!-- /.box-body -->
                <div class="box-footer">

                </div><!-- /.box-footer-->
            </div><!-- /.box -->
        </div><!-- /.col -->

    </div><!-- /.row -->
@endsection

@section('local_scripts')
<!-- Select2 -->
<script src="{{ asset("/bower_components/admin-lte/plugins/select2/select2.full.min.js") }}"></script>
<script type="text/javascript">
    $(function () {
      $(".select2").select2({
          theme: "bootstrap",
          maximumSelectionLength: 2
      });
    });

    var btnClassClick = function(e) {
        if ($("i", this).hasClass("fa-plus")) {
            $state = "plus";
        } else {
            $state = "minus";
        }
        $("i",this).toggleClass("fa fa-plus fa fa-minus");
        //find all the divs that have data in them, ignore the empty ones
        $(".present").each(function(e) {
            if ($state=="plus") {
                //expand
                if ($("i",this).hasClass("fa-plus")) {
                    this.click();
                }
            } else {
                if ($("i",this).hasClass("fa-minus")) {
                    this.click();
                }
            }
        });
        return false;
    }

</script>
@endsection
