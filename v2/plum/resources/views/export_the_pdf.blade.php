@extends('admin_template')

@section('content')

    <div class='row'>
        <div class='col-md-9'>
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
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">{{ $message }}</h3>
                </div>

                <div class="box box-primary">
                    <div class="box-header with-border">
                        <h3 class='box-title'>Section Label goes here</h3>
                    </div>
                    <div class='box-body'>
                        <div class='table-responsive'>
                        <table class='table'>
                            <thead>
                                <tr>
                                    <th><button class='btn btn-secondary btn-sm'>Field Name</button></th>
                                    <th><label>Value</label></th>
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>
                                        <button class='btn btn-secondary btn-sm'>Question Label</button>
                                    </td>
                                    <td>
                                        <label>value</label>
                                    </td>
                                </tr>
                            </tbody>
                        </table>
                        </div> <!--table-responsive -->
                    </div> <!--box-body -->

                    <div class="box-footer"></div><!-- /.box-footer-->
                </div><!-- /.box -->
            </div>
        <form method="post" id="exportPDF_Form" action='{{route("exportPDF", ["id" => $id])}}' >
            {{csrf_field()}}
            <input type='hidden' name='id' value="{{$id}}">
            <button type="submit" class="btn btn-danger" id="toPDF">Export PDF only</button>
        </form>
        </div><!-- /.col -->

    </div><!-- /.row -->
@endsection

@section('local_scripts')

<script type="text/javascript">

</script>
@endsection
