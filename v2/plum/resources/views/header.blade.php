<!-- Main Header -->
<header class="main-header">

  <!-- Logo -->
  <a href="{{url("/home")}}" class="logo">
    <!-- mini logo for sidebar mini 50x50 pixels -->
    <span class="logo-mini"><b>S</b>tratum</span>
    <!-- logo for regular state and mobile devices -->
    <span class="logo-lg"><b>Stratum</b>Integration</span>
  </a>

  <!-- Header Navbar -->
  <nav class="navbar navbar-static-top" role="navigation">
    <!-- Sidebar toggle button-->
    <a href="#" class="sidebar-toggle" data-toggle="offcanvas" role="button">
      <span class="sr-only">Toggle navigation</span>
    </a>
    <!-- Navbar Right Menu -->
    <div class="navbar-custom-menu">
      <ul class="nav navbar-nav">


        <!-- User Account Menu -->
        <li class="dropdown user user-menu">
          <!-- Menu Toggle Button -->
          <a href="#" class="dropdown-toggle" data-toggle="dropdown">
            <!-- The user image in the navbar-->
            <img src="{{ asset("/images/logo.png") }}" class="user-image" alt="Stratum Logo">
            <!--img src="{{ asset("/images/LisaGregory.jpg") }}" class="user-image" alt="User Image" -->
            <!-- hidden-xs hides the username on small devices so only the image appears. -->
            <span class="hidden-xs">{{{ isset(Auth::user()->name) ? Auth::user()->name : Auth::user()->email }}}</span>
          </a>
          <ul class="dropdown-menu">
            <!-- The user image in the menu -->
            <li class="user-header">
              <img src="{{ asset("/images/logo.png") }}" class="img-responsive" alt="Stratum Logo">
              <!--img src="{{ asset("/images/LisaGregory.jpg") }}" class="img-circle" alt="User Image" -->

              <p>
                {{{ isset(Auth::user()->name) ? Auth::user()->name : Auth::user()->email }}}
                <small>User</small>
              </p>
            </li>
            <!-- Menu Body -->
            <li class="user-body">

              <!-- /.row -->
            </li>
            <!-- Menu Footer-->
            <li class="user-footer">
              <div class="pull-left">
                <a href="{{url('/profile')}}" class="btn btn-default btn-flat">Profile</a>
              </div>
              <div class="pull-right">
                <a href="{{url('/auth/logout')}}" class="btn btn-default btn-flat">Sign out</a>
              </div>
            </li>
          </ul>
        </li>
        <!-- Control Sidebar Toggle Button -->
        <li>
          <a href="#" data-toggle="control-sidebar"><i class="fa fa-gears"></i></a>
        </li>
      </ul>
    </div>
  </nav>
</header>
