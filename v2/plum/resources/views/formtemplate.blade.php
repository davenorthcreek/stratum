@extends('admin_template')

@section('content')
    <div class='row'>
        <div class='col-md-9'>
            <!-- Box -->
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">Email Template and Form Launch</h3>
                    <div class="box-tools pull-right">
                        <button class="btn btn-box-tool" data-widget="collapse" data-toggle="tooltip" title="Collapse"><i class="fa fa-minus"></i></button>
                        <button class="btn btn-box-tool" data-widget="remove" data-toggle="tooltip" title="Remove"><i class="fa fa-times"></i></button>
                    </div>
                </div>
                <?PHP $id = $formTemplate->get('id'); ?>
                <form method="post" id="contentUpdate" action='{{route("candidateUpdateTemplate", ["id" => $id])}}' >
                    {{csrf_field()}}
                    <input type='hidden' name='id' value="{{$id}}">
                    <div class="box-body">
                        <div class="box-header form-inline">
                            <div class="form-group">
                                <label>Candidate Name: {{$formTemplate->get('candidate')->get('name')}}</label>
                            </div>
                        </div>
                        <div class="box-body">
                            @if($launch || $success)
                                <div class="form-group">
                                    <label>Candidate Bullhorn ID: {{$id}}</label>
                                </div>
                                <h3 class="box-title">Email Template</h3>
                                <hr>
                                {!! $formTemplate->get('content') !!}
                                <hr>
                            @else
                                <div class="form-group">
                                    <label>Candidate Bullhorn ID: {{$id}}</label>
                                    <h3 class="box-title">Email Template</h3>
                                    <textarea id="contentEditor" name="contentEditor" rows="10" cols="80">
                                        {{$formTemplate->get('content')}}
                                    </textarea>
                                </div>
                                <div class="form-group">
                                    <button type="submit" class="btn btn-primary" id="update">Update (or just approve) this Template</button>
                                </div>
                            @endif
                        </div>
                    </div><!-- /.box-body -->
                </form>
                @if($launch)
                <form method="post" id="launchButtonForm" action='{{route("candidateLaunchForm", ["id" => $id])}}' >
                    {{csrf_field()}}
                    <input type="hidden" name="content" id="content" value="{{$formTemplate->get('content')}}">
                    <input type='hidden' name='id' value="{{$id}}">
                    <button type="button" class="btn btn-danger" data-toggle="modal" data-target="#dialog-confirm" id="launchForm">Launch Form</button>
                @endif
                @if($success)
                    <label>Form has been launched!</label>
                @endif
                <div class="box-footer">

                </div><!-- /.box-footer-->
            </div><!-- /.box -->
        </div><!-- /.col -->

    </div><!-- /.row -->

@endsection

@section('local_scripts')
<div class="modal modal-danger" id="dialog-confirm">
  <div class="modal-dialog">
    <div class="modal-content">
      <div class="modal-header">
        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
          <span aria-hidden="true">&times;</span></button>
        <h4 class="modal-title">Launch the Form?</h4>
      </div>
      <div class="modal-body">
          <p>Warning!</p>
          <p>This will send an email from WorldApp to {{$formTemplate->get('candidate')->get('name')}}
          at email address {{$formTemplate->get('candidate')->get('email')}}.</p>
          <p>The content of the message will be:</p><hr>
          {!! $formTemplate->get('content') !!}
          <hr>
          <p>Are you sure you want to send this?</p>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline pull-left" data-dismiss="modal">Close</button>
        <button type="button" id="confirmLaunch" class="btn btn-outline">Launch the Form</button>
      </div>
    </div>
    <!-- /.modal-content -->
  </div>
  <!-- /.modal-dialog -->
</div>
<!-- /.modal -->

<!-- CK Editor -->
<script src="https://cdn.ckeditor.com/4.4.3/standard/ckeditor.js"></script>

<script>
@if($launch || $success)
    $('#confirmLaunch').click(function () {
        $('#launchButtonForm').submit();
    });
@else
    $(function () {
      // Replace the <textarea id="editor1"> with a CKEditor
      // instance, using default configuration.
      CKEDITOR.replace('contentEditor');
    });
@endif

</script>
@endsection
