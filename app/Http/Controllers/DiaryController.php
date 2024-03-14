<?php

namespace App\Http\Controllers;

use App\Repositories\DiaryRepository;
use Carbon\Carbon;
use Illuminate\Http\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Storage;
use Spatie\ImageOptimizer\Image;
use Spatie\ImageOptimizer\OptimizerChainFactory;
use Spatie\LaravelImageOptimizer\Facades\ImageOptimizer;

class DiaryController extends Controller
{
    protected $repository;

    public function __construct(DiaryRepository $repository)
    {
        $this->repository = $repository;
    }

    public function schedule($locale) {
        $user = auth()->user();
        $this->repository->init($user);

        $week = -1;
        $week = -40;


        $monday = date("Y-m-d", strtotime('monday '.$week.' week'));
        $saturday = date("Y-m-d", strtotime("+6 days", strtotime($monday)));

        $schedule = $this->repository->schedule($monday, $saturday);

        $monday = date("d.m", strtotime($monday));
        $saturday = date("d.m", strtotime($saturday));

        return response()->json([
            "week" => $week,
            "week_date" => $monday.' - '.$saturday,
            "schedule" => $schedule
        ], 200);
    }

    public function jurnalToday(Request $request) {
        $user = auth()->user();
        $this->repository->init($user);

        $date = date('Y-m-d');
        $date = '2023-01-19';


        $diary = $this->repository->diary($date);
        $day = date('w', strtotime($date));
        $day_name = __('d_'.$day);

        return response()->json([
            "date" => $date,
            "day" => $day,
            "day_name" => $day_name,
            "diary" => $diary
        ], 200);
    }

    public function distanceSchedule(Request $request) {
        $user = auth()->user();
        $this->repository->init($user);

        $page = $request->page ?: 1;
        $pageLimit = 10;

        $data = $this->repository->distanceSchedule($page, $pageLimit);

        return response()->json($data);
    }

    public function distanceMaterialByDiaryId($locale, $id) {
        $user = auth()->user();
        $this->repository->init($user);

        $material = $this->repository->distanceMaterialByDiaryId($id);

        return response()->json($material);
    }

    public function distanceSendAnswer(Request $request) {
        $user = auth()->user();
        $this->repository->init($user);

        $diary = $this->repository->getDiary($request->diary_id);
        if($diary->distance == 0 || $diary->distance_status == 0) {
            return response()->json([
                'message' => __('Учитель запретил доступ к материалам этого предмета')
            ], 400);
        }

        $feedback = $this->repository->getFeedback($request->diary_id);
        if($feedback->student_comment != '') {
            return response()->json([
                'message' => __('Вы уже ответили')
            ], 400);
        }

        if (!$request->answer || trim($request->answer) == '') {
            return response()->json([
                'message' => __('Ответ не может быт пустым')
            ], 400);
        }

        $images_path = [];
        for($i=1; $i <= 5; $i++) {
            if($request->hasFile('image_'.$i) && $request->file('image_'.$i)->isValid()) {
                if($request->file('image_'.$i)->extension() != 'jpg') {
                    return response()->json([
                        'message' => __('Файл должен быт в формате JPG')
                    ], 400);
                }
                if($request->file('image_'.$i)->getSize() > 9999999) {
                    return response()->json([
                        'message' => __('Размер файла не должен превышать 10 мегабайта')
                    ], 400);
                }

                $img = imageCreateFromJpeg($request->file('image_'.$i)->path());
                $temp_name = time().'_'.uniqid();
                $temp_path = storage_path("temp_optimized_images/".$temp_name.".jpeg");
                imageJpeg($img, $temp_path, 75);

                $schoolnum = sprintf("%05d", $user->id_mektep);
                $path = Storage::disk('ftp')->putFile('files/students/'.$schoolnum.'/'.$user->class->class.'/'.$user->id_class, new File($temp_path), 'public');

                unlink($temp_path);
                if(!$path) {
                    return response()->json([
                        'message' => __('Не удалось загрузить файл')
                    ], 400);
                }

                $images_path[] = "https://cloud4.mektep.edu.kz/".$path;
            }
        }

        $feedback->student_comment = $request->answer;
        $feedback->filename = implode(" ", $images_path);
        $feedback->answered_at = date("Y-m-d H:i:s");
        $feedback->save();

        return response()->json([
            'message' => __('success')
        ], 200);
    }

    public function predmetPlan($locale, $predmetId) {
        $user = auth()->user();
        $this->repository->init($user);

        $plan = $this->repository->getPlansByPredmet($predmetId);

        return response()->json([
            'list' => $plan
        ], 200);
    }
}
