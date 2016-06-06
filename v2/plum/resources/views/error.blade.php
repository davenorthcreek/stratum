@extends('admin_template')

@section('content')
    <div class='row'>
        <div class='col-md-9'>
            <div class="alert alert-danger">
                {!! $message !!}
            </div>
        </div><!-- /.col -->

    </div><!-- /.row -->
@endsection
