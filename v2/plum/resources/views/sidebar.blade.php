<!-- Left side column. contains the logo and sidebar -->
<aside class="main-sidebar">

  <!-- sidebar: style can be found in sidebar.less -->
  <section class="sidebar">

    <!-- Sidebar user panel (optional) -->
    <div class="user-panel">
      <div class="pull-left image">
        <img src="{{ asset("/bower_components/admin-lte/dist/img/LisaGregory.jpg") }}" class="img-circle" alt="User Image">
      </div>
      <div class="pull-left info">
        <p>Lisa Gregory</p>
        <!-- Status -->
        <a href="#"><i class="fa fa-circle text-success"></i> Online</a>
      </div>
    </div>

    <!-- search form (Optional) -->
    <form action="#" method="get" class="sidebar-form">
      <div class="input-group">
        <input type="text" name="q" class="form-control" placeholder="Search...">
            <span class="input-group-btn">
              <button type="submit" name="search" id="search-btn" class="btn btn-flat"><i class="fa fa-search"></i>
              </button>
            </span>
      </div>
    </form>
    <!-- /.search form -->

    <!-- Sidebar Menu -->
    <ul class="sidebar-menu">
      <li class="header">MENU</li>
      <!-- Optionally, you can add icons to the links -->
      <li class="active"><a href="{{url("/")}}"><i class="fa fa-link"></i> <span>Home</span></a></li>
      @if(array_key_exists('No', $candidates))
          <li class="treeview">
            <a href="#"><i class="fa fa-link"></i> <span>New Candidates</span> <i class="fa fa-angle-left pull-right"></i></a>
            <ul class="treeview-menu">
              @foreach($candidates['No'] as $candidate)
                  <?PHP $id = $candidate->get("id"); ?>
                  <li><a href="{{url("/candidate/$id")}}">{{$candidate->getName()}}</a></li>
              @endforeach
            </ul>
          </li>
      @endif
      @if(array_key_exists('RFS', $candidates))
          <li class="treeview">
            <a href="#"><i class="fa fa-link"></i> <span>Reg Form Sent</span> <i class="fa fa-angle-left pull-right"></i></a>
            <ul class="treeview-menu">
              @foreach($candidates['RFS'] as $candidate)
                  <?PHP $id = $candidate->get("id"); ?>
                  <li><a href="{{url("/candidate/$id")}}">{{$candidate->getName()}}</a></li>
              @endforeach
            </ul>
          </li>
      @endif
      @if(array_key_exists('FC', $candidates))
          <li class="treeview">
            <a href="#"><i class="fa fa-link"></i> <span>Form Completed</span> <i class="fa fa-angle-left pull-right"></i></a>
            <ul class="treeview-menu">
              @foreach($candidates['FC'] as $candidate)
                  <?PHP $id = $candidate->get("id"); ?>
                  <li><a href="{{url("/formresponse/$id")}}">{{$candidate->getName()}}</a></li>
              @endforeach
            </ul>
          </li>
      @endif
      @if(array_key_exists('IC', $candidates))
          <li class="treeview">
            <a href="#"><i class="fa fa-link"></i> <span>Interview Completed</span> <i class="fa fa-angle-left pull-right"></i></a>
            <ul class="treeview-menu">
              @foreach($candidates['IC'] as $candidate)
                  <?PHP $id = $candidate->get("id"); ?>
                  <li><a href="{{url("/candidate/$id")}}">{{$candidate->getName()}}</a></li>
              @endforeach
            </ul>
          </li>
      @endif
    </ul>
    <!-- /.sidebar-menu -->
  </section>
  <!-- /.sidebar -->
</aside>
