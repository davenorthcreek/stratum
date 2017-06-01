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
@page {
        size: auto;
		/* ensure you append the header/footer name with 'html_' */
		header: html_MyCustomHeader; /* sets <htmlpageheader name="MyCustomHeader"> as the header */
		footer: html_MyCustomFooter; /* sets <htmlpagefooter name="MyCustomFooter"> as the footer */
        margin-header: 18mm;
        margin-top: 40mm;
        margin-bottom: 25mm;
	}
</style>
</head>
<body>
    <htmlpageheader name="MyCustomHeader">
        <div>
	        <img height="18mm" style="float:right" src="{{asset("/images/header_image.jpg")}}">
        </div>
    </htmlpageheader>
    <htmlpagefooter name="MyCustomFooter">
        <img src="{{asset("/images/footer.png")}}">
    </htmlpagefooter>
    <div>
      <section>
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
                    <div style="display: inline-block; text-align: right">PDF Generated {{$date}}</div>
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
                                    <th>Candidate Info</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($section as $qhead=>$question)
                                <tr>
                                    <td>
                                        @foreach ($question['Question'] as $wa)
                                        {{$wa}}<br>
                                        @endforeach
                                    </td>
                                    <td>
                                        @if(in_array($qhead, ['File Uploads:', 'List of Skills', 'Conversion Interview']))
                                            <?php $candidate->log_this($qhead);
                                                  $candidate->log_this($question);
                                            ?>
                                            {!! $question['WorldApp'] !!}
                                        @else
                                            {{$question['WorldApp']}}
                                        @endif
                                    </td>

                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                        </div> <!--table-responsive -->
                    </div> <!--box-body -->
                </div><!-- /.box -->
                @endforeach
                <table>
                    <tbody>
                        <tr>
                            <td>Email Sent to Candidate</td>
                            <td>{!! $candidate->email_sent !!}</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div><!-- /.col -->

    </div><!-- /.row -->

</section>
</div>
</body>
</html>
