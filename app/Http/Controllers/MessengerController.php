<?php

namespace App\Http\Controllers;

use App\Events\ChannelAdd;
use App\Events\MessageSent;
use Illuminate\Http\Request;
use App\Repositories\MessengerRepository;
use Mockery\Exception;

class MessengerController extends Controller
{
    protected $repository;

    public function __construct(MessengerRepository $repository) {
        $this->repository = $repository;
    }

    public function getChats() {
        $user = auth()->user();
        $this->repository->init($user);

        $chats = $this->repository->getChats();

        return response()->json([
            "chat_list" => $chats,
        ]);
    }

    public function getChatHistory(Request $request) {
        $user = auth()->user();
        $this->repository->init($user);
        $page = $request->page ?: 0;

        $data = $this->repository->getChatHistory($request->channel_id, $page);

        return response()->json($data);
    }

    public function sendMessage(Request $request) {
        $user = auth()->user();
        $this->repository->init($user);

        if (trim($request->text) == '') {
            return response()->json([
                "message" => __("Сообщение не может быть пустым"),
            ], 400);
        }

        $message = $this->repository->sendMessage($request->channel_id, $request->text);

        if ($message) {
            $channelIdArray = explode("_", $request->channel_id);
            $channelId = $channelIdArray[0] == 'class' ? $request->channel_id : $channelIdArray[1].'_'.$channelIdArray[0];

            if($channelIdArray[0] != 'class') {
                $checkChatHistory = $this->repository->checkChatHistory($channelIdArray[1], $message->id);

                if( !$checkChatHistory ) {
                    broadcast(new ChannelAdd($user, $message, $channelIdArray[1]))->toOthers();
                }
            }

            broadcast(new MessageSent($user, $message, $channelId))->toOthers();
        }

        return response()->json([
            "message" => $message
        ]);
    }

    public function getUsers() {
        $user = auth()->user();
        $this->repository->init($user);

        $users = $this->repository->getUsers();

        return response()->json([
            "user_list" => $users
        ]);
    }
}
