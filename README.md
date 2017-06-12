# Laravel 5.3 Project using Socket.io for sending real time messages

<h1>Steps</h1>

<ul>
  <li><a href="#step0">Step 0: Install Node and Redis </a></li>
  <li><a href="#step1">Step 1: Setup and Dependecies </a></li>
  <li><a href="#step2">Step 2: Prepare the Database</a></li>
  <li><a href="#step3">Step 3: Create Model</a></li>
  <li><a href="#step4">Step 4: Create Controller</a></li>
  <li><a href="#step5">Step 5: Set up route</a></li>
  <li><a href="#step6">Step 6: Create View</a></li>
  <li><a href="#step7">Step 7: Create Server File</a></li>
  <li><a href="#step8">Step 8: Test and Run</a></li>
</ul>


<a name="step0"></a>
<h1>Step 0: Install Node and Redis</h1>

To make possible the communication from the two different backend servers, Laravel 5 and NodeJS we will use Redis.
Redis is a key value storage with a publish/subscriber feature.
Basically every message published on a specific queue will be intercepted from every subscriber, in this case the subscriber will be the NodeJS server.

<b>Make sure you have node installed</b>: <a href="https://nodejs.org/en/">Node</a>

<b>Mac</b>
If you have brew you can install redis easily by running this command
Run
```bash
brew install redis
```

Then, to start the server
Run
```bash
redis-server
```

<a name="step1"></a>
<h1>Step 1: Setup and Dependencies</h1>

Install Socket.io, Express, redis

Run
```bash
npm install socket.io express redis --save
```

Open composer.json and update the require object to include entrust:
```php
"require": {
    "php": ">=5.6.4",
    "laravel/framework": "5.3.*",
    "predis/predis": "^1.1"
},
```

Then, run
```bash
composer update
```

To avoid conflict with Redis in PHP environment we will modify also the alias to the Redis module of Laravel. In the config <b>config/app.php</b> file change

```php
'Redis'    => 'Illuminate\Support\Facades\Redis',
```

to

```php
'LRedis'    => 'Illuminate\Support\Facades\Redis',
```

<a name="step2"></a>
<h1>Step 2: Prepare the Database</h1>

For Authentication, run
```bash
php artisan make:auth
```

Update .env file with your database settings(db_database, db_username, db_password)

```php
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=[db_name]
DB_USERNAME=[db_user]
DB_PASSWORD=[db_pwd]
```


If you install a fresh laravel project then you have already users table migration.

You need to create the messages table.
```bash
php artisan make:migration create_messages_table

```

<strong>messages</strong>
```php
<?php

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class CreateMessagesTable extends Migration
{
     /**
      * Run the migrations.
      *
      * @return void
      */
     public function up()
     {
         Schema::create('messages', function (Blueprint $table) {
             $table->increments('id');
             $table->integer('user_id')->unsigned();
             $table->text('message');
             $table->timestamps();

            $table->foreign('user_id')->references('id')->on('users');
         });
     }

    /**
      * Reverse the migrations.
      *
      * @return void
      */
     public function down()
     {
         Schema::drop('messages');
     }
}
```

Run
```bash
php artisan migrate
```

<a name="step3"></a>
<h1>Step 3: Create Model for Message</h1>

Run
```bash
php artisan make:model Message
```

```php
<?php

namespace App;

use Illuminate\Database\Eloquent\Model;

class Message extends Model
{
    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'user_id', 'message'
    ];

}
```


<a name="step4"></a>
<h1>Step 4: Create Controller</h1>

Run
```bash
php artisan make:controller SocketController
```

```php
<?php

	namespace App\Http\Controllers;

	use Illuminate\Http\Request;

	use App\Http\Requests;
	use App\Http\Controllers\Controller;
	use LRedis, Auth;

	use App\Message;

	class SocketController extends Controller
	{
	    //Write Message
	    public function writemessage()
	    {
	        $messages = Message::leftJoin('users', function($join) {
	            $join->on('messages.user_id', '=', 'users.id');
	        })->select(
	            'users.name', 'messages.message')->orderBy('messages.created_at', 'asc')
	            ->get();

	        return view('writemessage', compact('messages'));
	    }

	    //Send Message
	    public function sendMessage(Request $request)
	    {
	        $user = Auth::user();

	        $input = $request->all();
	        $redis = LRedis::connection();

	        if(!isset($input['message']) || trim($input['message']) === ''){
	        }else{
	            Message::create([
	                'user_id' => $user->id,
	                'message' => $input['message']
	            ]);

	            $data = ['message' => $input['message'], 'user' => $user->name];
	            $redis->publish('message', json_encode($data));
	        }
	    }
	}
```

<a name="step5"></a>
<h1>Step 5: Set up route</h1>

```php
<?php

Route::group(['middleware' => ['auth']], function() {
    Route::get('writemessage', ['as'=>'writemessage','uses'=>'SocketController@writemessage']);
    Route::post('sendmessage', 'SocketController@sendMessage');
});
```

<a name="step6"></a>
<h1>Step 6: Create View</h1>


<strong>resources/views/writemessage.blade.php</strong>

```php
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


```

<a name="step7"></a>
<h1>Step 7: Create Server File</h1>

In the project root folder create a new file <b>server.js</b> file

```javascript
var app = require('express')();
var server = require('http').Server(app);
var io = require('socket.io')(server);
var redis = require('redis');
var port_number = 8890;

server.listen(port_number, function(){
    console.log("Listening on "+port_number)
});
io.on('connection', function (socket) {

    console.log("new client connected");
    var redisClient = redis.createClient();
    redisClient.subscribe('message');

    redisClient.on("message", function(channel, message) {
        console.log("new message in queue "+ message + "channel");
        socket.emit(channel, message);
    });

    socket.on('disconnect', function() {
        redisClient.quit();
    });
});

```

<a name="step8"></a>
<h1>Step 8: Test and Run</h1>

In the project root folder create a new file <b>server.js</b> file

To test the application you can start the <b>Redis</b> and <b>NodeJS</b> server.

In terminal run:
```bash
redis-server
```

In your project root folder run:
```bash
node server.js
```
In your project root folder run: node server.js

Login into you app and test by sending a message