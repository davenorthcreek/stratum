<!DOCTYPE html>
<html>
<head>
</head>
<body>
    <div>
      <section >
        <h1>
           {{ $page_title or "" }}
          <small>{{ $page_description or null }}</small>
        </h1>
      </section>
      <section>
    <div>
        <div>
            <div>
                <div>
                    <h3>{{ $message }}</h3>
                </div>
                @foreach ($sections as $sec_head=>$section)
                <div>
                    <div>
                        <h3>{{$sec_head}}</h3>
                    </div>
                    <div>
                        <div>
                        <table>
                            <thead>
                                <tr>
                                    <th>Field Name</th>
                                    <th>WorldApp Fields</th>
                                    <th>Existing Value</th>
                                    <th>WorldApp Form Value</th>
                                    <th>Final Value</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($section as $qhead=>$question)
                                <tr>
                                    <td>
                                        {{$qhead}}
                                    </td>
                                    <td>
                                        @foreach ($question['Question'] as $wa)
                                        {{$wa}}<br>
                                        @endforeach
                                    </td>
                                    <td>
                                        @if($qhead=='Reg Form Sent')
                                            {!! $question['Bullhorn'] !!}
                                        @else
                                            {{$question['Bullhorn']}}
                                        @endif
                                    </td>
                                    <td>
                                        {{$question['WorldApp']}}
                                    </td>
                                    <td>

                                        {{$question['Plum']}}
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                        </div> <!--table-responsive -->
                    </div> <!--box-body -->
                </div><!-- /.box -->
                @endforeach
            </div>
        </div><!-- /.col -->

    </div><!-- /.row -->
</section>
</div>
</body>
</html>
