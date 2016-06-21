<!DOCTYPE html>
<html>
<head>
<style>
table {
    border-collapse: collapse;
}

table, th, td {
    border: 1px solid black;
}
table, td {
    word-wrap:break-word;
}
</style>
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
                    <?php $candidate->log_this($section); ?>
                    <div>
                        <div>
                        <table>
                            <thead>
                                <tr>
                                    <!--th>Field Name</th -->
                                    <th>WorldApp Fields</th>
                                    <th>Existing Value</th>
                                    <th>WorldApp Form Value</th>
                                    <th>Pushed to Bullhorn</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($section as $qhead=>$question)
                                <tr>
                                    <!--td>
                                        {{$qhead}}
                                    </td -->
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
                                        @if($qhead=='File Uploads:')
                                            {!! $question['WorldApp'] !!}
                                        @else
                                            {{$question['WorldApp']}}
                                        @endif
                                    </td>
                                    @if(isset($question['Plum']))
                                        <td rowspan="{{$question['repeat']}}">
                                            @if($qhead=='List of Skills')
                                                {!! $question['Plum'] !!}
                                            @else
                                                {{$question['Plum']}}
                                            @endif
                                        </td>
                                    @endif
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
