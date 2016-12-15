@extends('layouts.app')

@section('content')
    <script src="//code.jquery.com/jquery-1.11.2.min.js"></script>
    <script src="//code.jquery.com/jquery-migrate-1.2.1.min.js"></script>
    <script src="https://cdn.socket.io/socket.io-1.3.4.js"></script>

    <div class="container">
        <div class="row">
            <div class="col-lg-8 col-lg-offset-2" >
                <div class="panel panel-default">
                    <div class="panel-heading">Messages Received</div>
                    <div id="messages" style="height: 250px;     padding: 15px;">

                        @foreach ($messages as $key => $message)
                            <p>{{$message->first_name}} : {{$message->message}}</p>
                        @endforeach

                    </div>
                </div>
            </div>
        </div>
        <div class="row">
            <div class="col-md-10 col-md-offset-1">
                <div class="panel panel-default">
                    <div class="panel-heading">Send message</div>
                    <form action="sendmessage" method="POST">
                        {{ csrf_field() }}
                        <input type="text" name="message" class="message" >
                        <input type="submit" value="send" class="send">
                    </form>
                </div>
            </div>
        </div>
    </div>

    <script>
        var socket = io.connect('http://localhost:8890');
        socket.on('message', function (data) {
            data = JSON.parse(data);
            $( "#messages" ).append( "<p>"+data.user+" : "+data.message+"</p>" );
        });

        $('input.send').click(function(e){
            e.preventDefault();
            search();
        });

        function search() {
            var message = $('input.message').val();
            $.ajax({
                type: "POST",
                url: "sendmessage",
                data: { "_token": $('meta[name="csrf-token"]').attr('content'), "message": message},
                cache: false,
                success: function(results){
                }
            });
        }

    </script>

@endsection
