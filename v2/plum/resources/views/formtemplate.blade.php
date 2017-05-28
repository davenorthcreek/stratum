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
                <form method="post" id="contentUpdate" enctype="multipart/form-data" action='{{route("candidateUpdateTemplate", ["id" => $id])}}' >
                    {{csrf_field()}}
                    <input type='hidden' name='id' value="{{$id}}">
                    <div class="box-body">
                        <div class="box-header form-inline">
                            <div class="form-group">
                                <label>Candidate Name:{{' '.$candidate->first_name}}{{' '.$candidate->last_name}}</label>
                            </div>
                        </div>
                        <div class="box-body">
                            @if($launch || $success)
                                <div class="form-group">
                                    <label>Candidate Email Address: {{$candidate->email}}</label>
                                </div>
                                @if($launch)
                                    <button type="button" id="confirmLaunch_top" class="btn btn-success">Launch the Form</button>
                                @endif
                                <h3 class="box-title">Email Template</h3>
                                <hr>
                                {!! $formTemplate->get('content') !!}
                                <hr>
                                @foreach($formTemplate->get('attachments') as $att)
                                    <div>
                                        File Attached: {{ $att['filename'] }}
                                    </div>
                                <hr>
                                @endforeach
                            @else
                                <div class="form-group">
                                    <label>Candidate Email Address: {{$candidate->email}}</label>
                                    <h3 class="box-title">Email Template</h3>
                                    <textarea id="contentEditor" name="contentEditor" rows="10" cols="80">
                                        {{$formTemplate->get('content')}}
                                    </textarea>
                                </div>
                                <div class="form-group">
                                  <label for="attachmentFile">Upload Any Attachment Here</label>
                                  <input type="file" id="attachmentFile" name='attachmentFile'>

                                  <p class="help-block">File will be added to the email sent to the Candidate (one attachment only).</p>
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
                    <button type="button" id="confirmLaunch" class="btn btn-success">Launch the Form</button>
                    <!--button type="button" class="btn btn-danger" data-toggle="modal" data-target="#dialog-confirm" id="launchForm">Launch Form</button -->
                </form>
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

<!-- CK Editor -->
<script src="https://cdn.ckeditor.com/4.5.10/standard/ckeditor.js"></script>

<script>
@if($launch || $success)
    $('#confirmLaunch').click(function () {
        $('#launchButtonForm').submit();
    });
    $('#confirmLaunch_top').click(function () {
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
