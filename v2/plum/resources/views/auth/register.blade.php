@extends('auth.auth')

@section('content')
    <div class="login-box">
        <div class="login-logo">
            <!--a href="/"><img src="{{asset('/images/logo.png')}}"></a -->
            <a href="/"><img src="{{asset('/images/k8408177.jpg')}}"><br>
            <h2 style="color:purple"><b>Plum</b>Integration</h2></a>
        </div>
        <!-- /.login-logo -->
        <div class="login-box-body">
          <p class="login-box-msg">Register as a new user</p>
          @if(count($errors))
          <div class="alert alert-danger" role="alert">
              @foreach($errors->all() as $error)
                  <p>{{$error}}</p>
              @endforeach
          </div>
          @endif
          <form method="POST" action="{{url('/auth/register')}}">
            {!! csrf_field() !!}
            <div class="form-group has-feedback">
                <input type="text" class="form-control" placeholder="Name" name="name">
                <span class="glyphicon glyphicon-heart form-control-feedback"></span>
            </div>
            <div class="form-group has-feedback">
                <input type="email" class="form-control" placeholder="Email" name="email">
                <span class="glyphicon glyphicon-envelope form-control-feedback"></span>
            </div>
            <div class="form-group has-feedback">
                <input type="password" class="form-control" placeholder="Password" name="password">
                <span class="glyphicon glyphicon-lock form-control-feedback"></span>
            </div>
            <div class="form-group has-feedback">
                <input type="password" class="form-control" placeholder="Confirm Password" name="password_confirmation">
                <span class="glyphicon glyphicon-lock form-control-feedback"></span>
            </div>
            <div class="row">
                <div class="col-xs-8">
                </div><!-- /.col -->
                <div class="col-xs-4">
                    <button type="submit" class="btn btn-primary btn-block btn-flat">Register</button>
                </div><!-- /.col -->
            </div>
        </form>
        <div class="row">
          <div class="col-xs-8">
            <a href="{{url('/password/email')}}">I forgot my password</a>
          </div>
          <div class="col-xs-4">
            <form method="get" action="{{url('/auth/login')}}">
              <button type="submit" class="btn btn-primary btn-block btn-flat">Login</button>
            </form>
          </div>
        </div>
      </div><!-- /.login-box-body -->
    </div><!-- /.login-box -->
@endsection
