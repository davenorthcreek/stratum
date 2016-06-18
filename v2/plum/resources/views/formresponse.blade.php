@extends('admin_template')

@section('content')

    <div class='row'>
        <div class='col-md-9'>
            <?PHP $id = $candidate->get('id'); ?>
            @if (count($errors) > 0)
                <div class="alert alert-danger">
                    <ul>
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif
            @if(isset($errormessage))
                <div class="panel panel-danger">
                    <div class="panel-heading">{{ $errormessage['message'] }}</div>
                    <div class="panel-body">
                        @foreach ($errormessage['errors'] as $error)
                            Property:&nbsp;<strong>{{$error['propertyName'] }}</strong><br>
                            Value:&nbsp;&nbsp;&nbsp;<strong>{{$candidate->get_a_string($candidate->get($error['propertyName'])) }}</strong><br>
                            Severity:&nbsp;<strong>{{$error['severity'] }}</strong><br>
                            Issue:&nbsp;&nbsp;&nbsp;<strong>{{$error['type'] }}</strong><br><hr>
                        @endforeach
                    </div>
                </div>
            @endif
            <form method="post" id="confirmValues" action='{{route("confirmValues", ["id" => $id])}}' >
                {{csrf_field()}}
                <input type='hidden' name='id' value="{{$id}}">
            <div class="box box-primary">
                <div class="box-header with-border">
                    <button type="submit" class="btn btn-danger" id="confirmV">Submit Values to Bullhorn</button>
                    <button class="btn btn-mini btn-primary pull-right btn-click-action"
                        data-widget="collapseAll" data-toggle="tooltip" title="Collapse/Expand All">
                        <i class='fa fa-plus'></i>
                    </button>
                </div>
            </div>
            <?php
                $sections = $form->get("sections");
                $headers = $form->get("sectionHeaders");
            ?>
            @for ($i = 0; $i < count($sections); $i++)
                <?php
                $section = $sections[$i];
                $label = $headers[$i];
                ?>

                <div class="box box-primary collapsed-box">
                    <div class="box-header with-border">
                        <h3 class='box-title'>{{ $label }}</h3>
                        <div class="box-tools pull-right">
                            <button class="btn btn-box-tool" data-widget="collapse" data-toggle="tooltip" title="Collapse/Expand"><i class="fa fa-plus"></i></button>
                        </div>
                    </div>
                    <div class='box-body' style='display: none;'>
                        {{ $formResult->exportSectionToHTML($form, $section, $qbyq, $candidate) }}
                    </div>
                    <div class="box-footer"></div><!-- /.box-footer-->
                </div><!-- /.box -->
            @endfor
            <div class="box box-primary">
                <div class="box-header with-border">
                    <button type="submit" class="btn btn-danger" id="confirmV">Submit Values to Bullhorn</button>
                    <!--button type="submit" class="btn btn-info"
                        formaction='{{route("exportPDF", ["id" => $id])}}' id="toPDF">Export PDF only</button -->
                    <button role="button" class="btn btn-mini btn-primary pull-right btn-click-action"
                        data-widget="collapseAll" data-toggle="tooltip" title="Collapse/Expand All">
                        <i class='fa fa-plus'></i>
                    </button>
                </div>
            </div>
        </form>
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
      $(".select2").select2({
          theme: "bootstrap"
      });
    });

    var btnClassClick = function(e){
        $("i",this).toggleClass("fa fa-plus fa fa-minus");

        $("[data-widget='collapse']").click();
        return false;
    }

    $('.btn-click-action').on('click', btnClassClick);

    var unselect57 = function(e){
        $('#Q57_checkbox').prop('checked', false);
    }

    $("#Q57_checkbox").click(function(){
        if($("#Q57_checkbox").is(':checked') ){
            $(".Q57 > option").prop("selected","selected");// Select All Options
            $(".Q57").trigger("change");// Trigger change to select 2
        }
    });

    $('.Q57').on('select2:unselect', unselect57);

    $("#Q62_checkbox").click(function(){
        if($("#Q62_checkbox").is(':checked') ){
            $(".Q62 > option").prop("selected","selected");// Select All Options
            $(".Q62").trigger("change");// Trigger change to select 2
        }
    });

    var unselect62 = function(e){
        $('#Q62_checkbox').prop('checked', false);
    }

    $('.Q62').on('select2:unselect', unselect62);

</script>
@endsection
