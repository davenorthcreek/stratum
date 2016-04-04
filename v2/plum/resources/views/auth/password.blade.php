@extends('auth.auth')

@section('content')
    <div class="login-box">
        <div class="login-logo">
            <a href="/"><img src="{{asset('/images/logo.png')}}"></a>
        </div>
        <!-- /.login-logo -->
        <div class="login-box-body">
            <p class="login-box-msg">Reset your password</p>
            @if(count($errors))
                <div class="alert alert-danger" role="alert">
                    @foreach($errors->all() as $error)
                        <p>{{$error}}</p>
                    @endforeach
                </div>
            @endif
            @if(session('status'))
                <div class="alert alert-success" role="alert">
                    <p>{{session('status')}}</p>
                </div>
            @endif
            <form action="{{url('/password/email')}}" method="post">
                {!! csrf_field() !!}
                <div class="form-group has-feedback">
                    <input type="email" class="form-control" placeholder="Email" name="email" value="{{ old('email') }}">
                    <span class="glyphicon glyphicon-envelope form-control-feedback"></span>
                </div>
                <div class="row">
                    <div class="col-xs-12">
                        <button type="submit" class="btn btn-primary btn-block btn-flat">Send Password Reset Link</button>
                    </div><!-- /.col -->
                </div>
            </form>

        </div><!-- /.login-box-body -->
        </div><!-- /.login-box -->
@endsection