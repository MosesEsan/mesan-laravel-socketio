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
            'users.name','messages.message')->orderBy('messages.created_at', 'asc')
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