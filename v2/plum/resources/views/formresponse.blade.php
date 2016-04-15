@extends('admin_template')

@section('content')
    <div class='row'>
        <div class='col-md-6'>
            <!-- Box -->

                    {{$formResult->exportToHTML($form) }}

        </div><!-- /.col -->

    </div><!-- /.row -->
@endsection

@section('local_scripts')
<!-- Select2 -->
<script src={{ asset("/bower_components/admin-lte/plugins/select2/select2.full.min.js") }}></script>
<script>
    $(function () {
      // Replace the <textarea id="editor1"> with a CKEditor
      // instance, using default configuration.
      $(".select2").select2({theme: "bootstrap"});
    });

    $('#collapseAll').click(function () {
        $("[data-widget='collapse']").click();
    });
</script>
@endsection
