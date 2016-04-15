@extends('admin_template')

@section('content')

    <div class='row'>
        <div class='col-md-6'>
            <div class="box box-primary"><div class="box-header with-border">
                <button class="btn btn-mini btn-primary pull-right btn-click-action"
                    data-widget="collapseAll" data-toggle="tooltip" title="Collapse/Expand All">
                    <i class='fa fa-plus'></i>
                </button>
            </div>
        </div>
            <!-- Box -->

                    {{$formResult->exportToHTML($form) }}

        </div><!-- /.col -->

    </div><!-- /.row -->
@endsection

@section('local_scripts')
<!-- Select2 -->
<script src={{ asset("/bower_components/admin-lte/plugins/select2/select2.full.min.js") }}></script>
<script type="text/javascript">
    $(function () {
      // Replace the <textarea id="editor1"> with a CKEditor
      // instance, using default configuration.
      $(".select2").select2({theme: "bootstrap"});
    });

    var btnClassClick = function(e){
        $("i",this).toggleClass("fa fa-plus fa fa-minus");
        
        $("[data-widget='collapse']").click();
    }

    $('.btn-click-action').on('click', btnClassClick);

</script>
@endsection
