@extends('admin_template')

@section('content')


        <!-- Content Header (Page header) -->
<section class="content-header">
    <h1>
        Profile settings
    </h1>
</section>

<!-- Main content -->
<section class="content profile">
    <div class="row">
        <div class="col-md-4"><!-- change personal info -->
            <div class="box box-primary">
                <div class="box-header with-border">
                    <h3 class="box-title">Change your personal info</h3>
                </div>
                <!-- /.box-header -->
                <!-- form start -->
                <form role="form">
                    <div class="alert alert-danger hide" role="alert"></div>
                    <div class="alert alert-success hide" role="alert"></div>
                    {{csrf_field()}}
                    <div class="box-body">
                        <div class="form-group">
                            <label for="email">Email address</label>
                            <input name="email" type="email" class="form-control" id="email" placeholder="Enter your email" value="{{Auth::user()->email}}" required>
                        </div>
                        <div class="form-group">
                            <label for="name">Name</label>
                            <input name="name" type="text" class="form-control" id="name" placeholder="Enter your name" value="{{Auth::user()->name}}" required>
                        </div>
                    </div>
                    <!-- /.box-body -->

                    <div class="box-footer">
                        <button type="submit" class="btn btn-primary submit-btn" data-url="{{url('/profile/user')}}">Save</button>
                    </div>
                </form>
            </div>
        </div><!-- end change personal info -->
        <div class="col-md-4"><!-- change password -->
            <div class="box box-danger">
                <div class="box-header with-border">
                    <h3 class="box-title">Change your password</h3>
                </div>
                <!-- /.box-header -->
                <!-- form start -->
                <form role="form">
                    <div class="alert alert-danger hide" role="alert"></div>
                    <div class="alert alert-success hide" role="alert"></div>
                    {{csrf_field()}}
                    <div class="box-body">
                        <div class="form-group">
                            <label for="current_password">Current password</label>
                            <input name="current_password" type="password" class="form-control" id="current_password" placeholder="Enter your current password" required>
                        </div>
                        <div class="form-group">
                            <label for="exampleInputPassword1">New password</label>
                            <input name="password" type="password" class="form-control" id="password" placeholder="Enter your new password" required>
                        </div>
                        <div class="form-group">
                            <label for="exampleInputPassword1">Repeat new password</label>
                            <input name="password_confirmation" type="password" class="form-control" id="password_confirmation" placeholder="Repeat your new password" required>
                        </div>
                    </div>
                    <!-- /.box-body -->

                    <div class="box-footer">
                        <button type="submit" class="btn btn-primary submit-btn" data-url="{{url('/profile/password')}}">Save</button>
                    </div>
                </form>
            </div>
        </div><!-- end change password -->
    </div>
</section><!-- /.content -->


@endsection

@section('title')
Profile
@endsection

@section('local_scripts')

    <script>
        $('form').on('submit', function (e) {
            e.preventDefault();
            var form = $(this);
            var submitBtn = form.find('.submit-btn');
            form.find('.alert').addClass('hide').html('');
            submitBtn.addClass('disabled');
            $.ajax({
                url: submitBtn.attr('data-url'),
                type: "POST",
                data: form.serialize(),
                success: function (resp) {
                    if(resp.status) {
                        form.find('.alert-success').removeClass('hide').html('<p>Success</p>');
                        submitBtn.removeClass('disabled');
                        location.reload();
                    }
                },
                error: function (resp) {
                    var errors = '';
                    for(var error in resp.responseJSON) {
                        errors += '<p>' + resp.responseJSON[error] + '</p>';
                    }
                    form.find('.alert-danger').removeClass('hide').html(errors);
                    submitBtn.removeClass('disabled');
                }
            });
        });
    </script>

@endsection
