<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class LessonsController extends Controller
{
    public function videoLessonsList() {
        $user = auth()->user();

        $lang = $user->class->edu_language == 1 ? 'kaz' : 'rus';

        $lessons_json = json_decode(file_get_contents(storage_path() . "/video_lessons_youtube/youtube_list_".$user->class->class."_".$lang.".json"), true);

        $lessons = [];
        foreach ($lessons_json['items'] as $item) {
            $lessons[] = [
                'url' => 'https://www.youtube.com/watch?v=' . $item['snippet']['resourceId']['videoId'],
                'title' => $item['snippet']['title'],
                'thumbnails' => $item['snippet']['thumbnails'],
            ];
        }

        return response()->json([
            "lessons_list" => $lessons,
        ]);
    }

    public function educationRecources() {
        $user = auth()->user();

        $recources = json_decode(file_get_contents(storage_path() . "/education_recources.json"), true);

        return response()->json([
            "items" => $recources['items'],
        ]);
    }
}
