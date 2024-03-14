<?php

namespace App\Http\Controllers;

use App\Repositories\DiaryRepository;
use App\Repositories\MessengerRepository;
use App\Repositories\NewsRepository;
use Illuminate\Http\Request;

class MainPageController extends Controller
{
    protected $newsRepository;
    protected $messangerRepository;
    protected $diaryRepository;

    public function __construct(NewsRepository $newsRepository, MessengerRepository $messangerRepository, DiaryRepository $diaryRepository)
    {
        $this->newsRepository = $newsRepository;
        $this->messangerRepository = $messangerRepository;
        $this->diaryRepository = $diaryRepository;
    }

    public function mainPage() {
        $user = auth()->user();
        $this->newsRepository->init($user);
        $this->messangerRepository->init($user);
        $this->diaryRepository->init($user);

        $newMessagesCount = $this->messangerRepository->newMessagesCount();
        $newsList = $this->newsRepository->newsList();
        $diary = $this->diaryRepository->todayDiary();

        $date = date("Y-m-d"); //заменить на текущую дату date("Y-m-d")
        $dayOfWeek = date('w', strtotime($date));

        $todayInfo = [
            'current_time' => date('Y-m-d H:i:s'), //заменить на текущую дату date('Y-m-d H:i:s')
            'day_number' => (int)$dayOfWeek,
            'day' => __('d_'.$dayOfWeek),
        ];
        $todayInfo['message_count'] = $newMessagesCount;

        return response()->json([
            "info" => $todayInfo,
            "diary" => $diary,
            "news_list" => $newsList
        ]);
    }

    public function getNewById($locale, $id) {
        $user = auth()->user();
        $this->newsRepository->init($user);
        $this->messangerRepository->init($user);

        $new = $this->newsRepository->getOneById($id);

        return response()->json($new);
    }
}
