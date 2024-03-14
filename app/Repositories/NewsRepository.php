<?php

namespace App\Repositories;


use App\Models\News;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;

class NewsRepository
{
    use Notifiable;

    protected $user;
    protected $lang;
    protected $model;

    public function __construct(News $model)
    {
        $this->model = $model;
    }

    public function init($user)
    {
        $this->user = $user;

        if (app()->getLocale() == 'ru') $this->lang = 'rus';
        else if (app()->getLocale() == 'kk') $this->lang = 'kaz';
    }

    public function newsList() {
        $news = $this->model
            ->select('id', 'title', 'datetime', 'filename as image_url')
            ->where('lang', '=', $this->lang)
            ->orderBy('datetime','desc')
            ->get()->take(10);

        $newsList = [];
        foreach ($news as $item) {
            if ($item['image_url'] == '') {
                $item['image_url'] = 'https://mobile.mektep.edu.kz/uploads/images/default_background.jpg';
            }

            $strTime = strtotime($item['datetime']);
            $dayOfMonth = date('d', $strTime);
            $month = date('m', $strTime);
            $year = date('Y', $strTime);
            $time = date('H:i', $strTime);

            $item['date'] = ltrim($dayOfMonth, 0).' '.__('m_'.$month).' '.$year.' '.$time;
            unset($item['datetime']);

            $newsList[] = $item;
        }

        return $newsList;
    }

    public function getOneById($id) {
        $new = $this->model
            ->select('date', 'title', 'text', 'datetime', 'filename as image_url')
            ->where('id', '=', $id)
            ->first();

        if ($new) {
            $this->model->where('id', '=', $id)->increment('views');
        }

        if ($new['image_url'] == '') {
            $new['image_url'] = 'https://mobile.mektep.edu.kz/uploads/images/default_background.jpg';
        }

        $strTime = strtotime($new['datetime']);
        $dayOfMonth = date('d', $strTime);
        $month = date('m', $strTime);
        $year = date('Y', $strTime);
        $time = date('H:i', $strTime);

        $new['date'] = ltrim($dayOfMonth, 0).' '.__('m_'.$month).' '.$year.' '.$time;
        unset($new['datetime']);

        return $new;
    }
}
