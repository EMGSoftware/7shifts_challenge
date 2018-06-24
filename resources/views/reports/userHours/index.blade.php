<!doctype html>
<html lang="en">
  <head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">

    <link rel="stylesheet" href="https://stackpath.bootstrapcdn.com/bootstrap/4.1.1/css/bootstrap.min.css" integrity="sha384-WskhaSGFgHYWDcbwN70/dfYBj47jz9qbsMId/iRN3ewGhXQFZCSftd1LZCfmhktB" crossorigin="anonymous">
    <link href="{{ asset ('css/bootstrap-sortable.css') }}" rel="stylesheet" type="text/css">
    
    <title>7Shifts</title>
  </head>
  <body>
    <table class="table table-striped sortable">
        <thead>
            <tr>
                <th data-defaultsign="AZ">User</th>
                <th>Regular Hours</th>
                <th>Overtime Hours</th>
                <th>Total Hours</th>
                <th>Location</th>
            </tr>
        </thead>
        <tbody>
            @forelse ($report as $item)
            <tr>
                <td>
                    <img src="{{  $item->picture }}"/>
                    <span>
                        {{ $item->user }}
                    </span>
                </td>
                <td>{{ $item->regularHours }}</td>
                <td>{{ $item->overtimeHours }}</td>
                <td>{{ ($item->regularHours + $item->overtimeHours) }}</td>
                <td>{{ $item->location }}</td>
            </tr>
            @empty
            <tr>
                <th colspan="5">No data available</th>
            </tr>
            @endforelse
        </tbody>
    </table>

    <script src="https://code.jquery.com/jquery-3.3.1.slim.min.js" integrity="sha384-q8i/X+965DzO0rT7abK41JStQIAqVgRVzpbzo5smXKp4YfRvH+8abtTE1Pi6jizo" crossorigin="anonymous"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/popper.js/1.14.3/umd/popper.min.js" integrity="sha384-ZMP7rVo3mIykV+2+9J3UJ46jBk0WLaUAdn689aCwoqbBJiSnjAK/l8WvCWPIPm49" crossorigin="anonymous"></script>
    <script src="https://stackpath.bootstrapcdn.com/bootstrap/4.1.1/js/bootstrap.min.js" integrity="sha384-smHYKdLADwkXOn1EmN1qk/HfnUcbVRZyYmZ4qpPea6sjB/pTJ0euyQp0Mk8ck+5T" crossorigin="anonymous"></script>
    <script src="{{ asset ('js/bootstrap-sortable.js') }}"></script>

  </body>
</html>