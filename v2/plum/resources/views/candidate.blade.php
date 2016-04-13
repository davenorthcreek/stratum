@extends('admin_template')

@section('content')
    <div class='row'>
        <div class='col-md-6'>
            <!-- Box -->
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">Candidate Info</h3>
                    <div class="box-tools pull-right">
                        <button class="btn btn-box-tool" data-widget="collapse" data-toggle="tooltip" title="Collapse"><i class="fa fa-minus"></i></button>
                        <button class="btn btn-box-tool" data-widget="remove" data-toggle="tooltip" title="Remove"><i class="fa fa-times"></i></button>
                    </div>
                </div>
                <div class="box-body">

                    <?php $status = $thecandidate->get("preferredContact");
                          $thejson = $thecandidate->marshalToJSON();
                          $decoded = json_decode($thejson, true); ?>
                    @if($status=="No")
                        <form action='{{route("candidateFormTemplate", ["id" => $thecandidate->get("id")])}}' method="GET">
                          <button id="launchButton" value="Launch">Edit Template and Launch Form</button>
                        </form>
                    @endif
                    <?php $thecandidate->exportToHTML($form) ?>
                </div><!-- /.box-body -->
                <div class="box-footer">

                </div><!-- /.box-footer-->
            </div><!-- /.box -->
        </div><!-- /.col -->

    </div><!-- /.row -->
@endsection
