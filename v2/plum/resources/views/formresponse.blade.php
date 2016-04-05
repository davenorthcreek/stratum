@extends('admin_template')

@section('content')
    <div class='row'>
        <div class='col-md-6'>
            <!-- Box -->

                    {{$formResult->exportToHTML($candidate) }}
                
        </div><!-- /.col -->

    </div><!-- /.row -->
@endsection
