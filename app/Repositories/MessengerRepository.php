<?php

namespace App\Repositories;


use App\Events\MessageSent;
use App\Models\Message;
use App\Models\MessageGroupRead;
use App\Models\Student;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;

class MessengerRepository
{
    use Notifiable;

    protected $user;
    protected $lang;

    public function init($user)
    {
        $this->user = $user;

        if (app()->getLocale() == 'ru') $this->lang = 'rus';
        else if (app()->getLocale() == 'kk') $this->lang = 'kaz';
    }

    public function newMessagesCount() {
        $messages = Message::
            where('to_id', '=', $this->user->id)
            ->where('read_status', '=', 0)
            ->count();

        return $messages;
    }

    public function getChats() {
        $sended = Message::select('ms.*')
            ->from('ok_edus_message as ms')
            ->where('from_id', '=', $this->user->id)
            ->whereNull('to_class_id')
            ->whereRaw('created_at = (SELECT MAX(created_at) FROM ok_edus_message as ms2 WHERE ms.to_id = ms2.to_id AND ms.from_id = ms2.from_id )')
            ->orderBy('created_at', 'DESC')
            ->get()
            ->keyBy('to_id')
            ->toArray();

        $received = Message::select('ms.*')
            ->from('ok_edus_message as ms')
            ->where('to_id', '=', $this->user->id)
            ->whereNull('to_class_id')
            ->whereRaw('created_at = (SELECT MAX(created_at) FROM ok_edus_message as ms2 WHERE ms.to_id = ms2.to_id AND ms.from_id = ms2.from_id )')
            ->orderBy('created_at', 'DESC')
            ->get()
            ->keyBy('to_id')
            ->toArray();

        $unreadCount = Message::
            select('from_id', DB::raw('count(*) as unread_count'))
            ->where('to_id', '=', $this->user->id)
            ->whereNull('to_class_id')
            ->where('read_status', '=', 0)
            ->groupBy('from_id')
            ->get()
            ->keyBy('from_id')
            ->toArray();

        $groupChat = Message::
            where('to_class_id', '=', $this->user->id_class)
            ->orderBy('created_at', 'desc')
            ->first();

        $groupUnreadCount = MessageGroupRead::
            where('to_class_id', '=', $this->user->id_class)
            ->where('to_id', '=', $this->user->id)
            ->where('read_status', '=', 0)
            ->count();


        $studIds = array_merge(array_column($sended, 'to_id'), array_column($received, 'from_id'));
        $studIds[] = $groupChat['from_id'];

        $students = Student::select('id', 'surname', 'name', 'pol')->whereIn('id', $studIds)->get()->keyBy('id')->toArray();

        $chats = [];
        foreach ($sended as $key => $item) {
            if(array_key_exists($key, $received)) {
                if ($item['created_at'] < $received[$key]['created_at']) {
                    $item = $received[$key];
                }
                unset($received[$key]);
            }

            $username = array_key_exists($key, $students) ? $students[$key]['surname'].' '.$students[$key]['name'] : 'undefined';

            $chats[] = [
                'channel_id' => $this->user->id.'_'.$key,
//                'user_id' => $key,
                'username' => $username,
                'sender' => __('Вы'),
                'message' => $item['content'],
                'date' => $item['created_at'],
                'unread_count' => array_key_exists($key, $unreadCount) ? $unreadCount[$key]['unread_count'] : 0,
            ];
        }

        foreach ($received as $key => $item) {
            $chats[] = [
                'channel_id' => $this->user->id.'_'.$key,
//                'user_id' => $key,
                'username' => $students[$key]['surname'].' '.$students[$key]['name'],
                'sender' => $students[$key]['name'],
                'message' => $item['content'],
                'date' => $item['created_at'],
                'unread_count' => array_key_exists($key, $unreadCount) ? $unreadCount[$key]['unread_count'] : 0,
            ];
        }

        if($groupChat) {
            $chats[] = [
                'channel_id' => 'class_'.$groupChat->to_class_id,
//                'user_id' => $groupChat->to_class_id,
                'username' => $this->user->class->class.'«'.$this->user->class->group.'» '.__('Класс'),
                'sender' => $students[$groupChat->from_id]['name'],
                'message' => $groupChat->content,
                'date' => $groupChat->created_at,
                'unread_count' => $groupUnreadCount,
            ];
        }



        usort($chats, function ($a_new, $b_new) {
            $a_new = strtotime($a_new["date"]);
            $b_new = strtotime($b_new["date"]);

            return $b_new - $a_new;
        });

        return $chats;
    }

    public function getChatHistory($channelId, $page) {
        $channelIdArray = explode("_", $channelId);
        $isClassChat = $channelIdArray[0] == 'class' ? true : false;
        $id = $channelIdArray[1];

        $limit = 50;
        $offset = ($page-1) * $limit;
        $allCount = 0;

        $username = null;
        $lastVisit = null;

        if($isClassChat) {
            $chatHistory = Message::
                select('ok_edus_message.*', 'mektep_students.surname', 'mektep_students.name')
                ->leftJoin('mektep_students', 'mektep_students.id', '=', 'ok_edus_message.from_id')
                ->where('to_class_id', '=', $id)
                ->orderBy('created_at', 'desc')
                ->skip($offset)
                ->take($limit)
                ->get()
                ->toArray();

            $allCount = Message::
                    where('to_class_id', '=', $id)
                    ->count();

            MessageGroupRead::where('to_class_id', '=', $id)
                ->where('to_id', '=', $this->user->id)
                ->where('read_status', '=', 0)
                ->update([
                    'read_status' => 1,
                    'read_at' => date("Y-m-d H:i:s")
                ]);

            $username = $this->user->class->class.'«'.$this->user->class->group.'» '.__('Класс');
        }
        else {
            $chatHistory = Message::
                select('ok_edus_message.*', 'mektep_students.surname', 'mektep_students.name')
                ->leftJoin('mektep_students', 'mektep_students.id', '=', 'ok_edus_message.from_id')
                ->where(function (Builder $query) use ($id) {
                    $query->where('from_id', $this->user->id)
                        ->where('to_id', $id);
                })
                ->orWhere(function (Builder $query) use ($id) {
                    $query->where('from_id', $id)
                        ->where('to_id', $this->user->id);
                })
                ->orderBy('created_at', 'desc')
                ->skip($offset)
                ->take($limit)
                ->get()
                ->toArray();

            $allCount = Message::
                where(function (Builder $query) use ($id) {
                    $query->where('from_id', $this->user->id)
                        ->where('to_id', $id);
                })
                ->orWhere(function (Builder $query) use ($id) {
                    $query->where('from_id', $id)
                        ->where('to_id', $this->user->id);
                })
                ->count();

            Message::where('from_id', $id)
                ->where('to_id', $this->user->id)
                ->where('read_status', 0)
                ->update([
                    'read_status' => 1,
                    'read_at' => date("Y-m-d H:i:s")
                ]);

            $stud = DB::table('mektep_students')->select('surname', 'name', 'last_visit')->where('id', $id)->first();
            $username = $stud->surname.' '.$stud->name;
            $lastVisit = $stud->last_visit == '0000-00-00 00:00:00' ? null : $stud->last_visit;
        }

        $chatHistory = array_reverse($chatHistory);

        $totalPages = ceil($allCount / $limit);

        $chatHistoryArr = [];
        foreach ($chatHistory as $item) {
            $chatHistoryArr[] = [
                "message_id" => $item['id'],
                "is_my" => $item['from_id'] == $this->user->id ? true : false,
                "text" => $item['content'],
                "username" => $item['surname'].' '.$item['name'],
                "is_read" => $item['read_status'] == 1 ? true : false,
                "date" => $item['created_at']
            ];
        }


        return [
            "channelId" => $channelId,
            "username" => $username,
            "last_visit" => $lastVisit,
            "current_page" => (int)$page,
            "total_pages" => $totalPages,
            "messages_list" => $chatHistoryArr,
        ];
    }

    public function sendMessage($channelId, $text) {
        $channelIdArray = explode("_", $channelId);
        $isClassChat = $channelIdArray[0] == 'class' ? true : false;
        $id = $channelIdArray[1];

        if($isClassChat) {
            $message = Message::create([
                "from_id" => $this->user->id,
                "to_class_id" => $this->user->id_class,
                "content" => $text
            ]);

            $studIds = DB::table("mektep_students")
                ->select("id")
                ->where("id_class", $this->user->id_class)
                ->get()
                ->toArray();

            $insertArray = [];
            foreach ($studIds as $stud) {
                $insertArray[] = [
                    "to_id" => $stud->id,
                    "to_class_id" => $this->user->id_class,
                    "message_id" => $message->id
                ];
            }

            MessageGroupRead::insert($insertArray);
        }
        else {
            $message = Message::create([
                "from_id" => $this->user->id,
                "to_id" => $id,
                "content" => $text
            ]);


        }

        return $message;
    }

    public function getUsers() {
        $students = DB::table("mektep_students")
            ->select('id', 'surname', 'name', 'last_visit')
            ->where('id_class', $this->user->id_class)
            ->orderBy('surname')
            ->orderBy('name')
            ->get()
            ->toArray();

        $users = [];
        foreach ($students as $student) {
            $users[] = [
                "channel_id" => $this->user->id.'_'.$student->id,
                "username" => $student->surname.' '.$student->name,
                "last_visit" => $student->last_visit == '0000-00-00 00:00:00' ? null : $student->last_visit
            ];
        }

        return $users;
    }

    public function checkChatHistory($id, $messageId) {
        $chatHistory = Message::
            where('id', '!=', $messageId)
            ->where(function (Builder $query) use ($id) {
                $query->where('from_id', $this->user->id)
                    ->where('to_id', $id);
            })
            ->orWhere(function (Builder $query) use ($id) {
                $query->where('from_id', $id)
                    ->where('to_id', $this->user->id);
            })
            ->first();

        return $chatHistory ? true : false;
    }
}
