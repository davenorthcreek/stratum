<!DOCTYPE html>
<html>
  <head>
    <meta content="text/html" http-equiv="content-type">
  </head>
  <body>
    <p>Candidate Form Uploaded:</p>
    <p>{{$candidateName}}</p>
    <p>{{$candidateID}}</p>
    <p>Time of Form Upload: {{$date}}</p>
    @if($availability)
        <p>{{$availability}}</p>
    @endif
  </body>
</html>
