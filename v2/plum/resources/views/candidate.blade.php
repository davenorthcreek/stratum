@extends('admin_template')

@section('content')
    <div class='row'>
        <div class='col-md-9'>
            <!-- Box -->
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">{{ $message }}</h3>
                    <div class="box-tools pull-right">
                        <button class="btn btn-box-tool" data-widget="collapse" data-toggle="tooltip" title="Collapse"><i class="fa fa-minus"></i></button>
                        <button class="btn btn-box-tool" data-widget="remove" data-toggle="tooltip" title="Remove"><i class="fa fa-times"></i></button>
                    </div>
                </div>
                <div class="box-body">

                    <?php $status = $thecandidate->get("preferredContact");
                          $thejson = $thecandidate->marshalToJSON();
                          $refs = $thecandidate->marshalReferences();
                          $decoded = json_decode($thejson, true); ?>
                    @if($status=="No")
                        <form action='{{route("candidateFormTemplate", ["id" => $thecandidate->get("id")])}}' method="GET">
                          <button id="launchButton" value="Launch">Edit Template and Launch Form</button>
                        </form>
                    @endif
                    @if($status=="Interview Done")
                        <form action='{{route("formResponseDisplay", ["id" => $thecandidate->get("id")])}}' method="GET">
                          <button id="launchButton" value="Launch">Reload WorldApp Form Data</button>
                        </form>
                    @endif
                    <?php $thecandidate->exportSummaryToHTML($form) ?>
                </div><!-- /.box-body -->
                <div class="box-footer">

                </div><!-- /.box-footer-->
            </div><!-- /.box -->
        </div><!-- /.col -->

    </div><!-- /.row -->
@endsection
