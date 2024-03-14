<?php

namespace App\Repositories;

use App\Models\Diary as Model;
use App\Models\DistanceFeedback;
use App\Models\DistanceMaterial;
use App\Models\Journal;
use App\Models\Message;
use App\Models\Plan;
use Carbon\Carbon;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class DiaryRepository
{
    use Notifiable;

    protected $user;
    protected $lang;
    protected $model;
    protected $journalModel;
    protected $distanceFeedbackModel;
    protected $distanceMaterialModel;
    protected $planModel;

    public function __construct(Model $model,
                                Journal $journalModel,
                                DistanceFeedback $distanceFeedbackModel,
                                DistanceMaterial $distanceMaterialModel,
                                Plan $planModel)
    {
        $this->model = $model;
        $this->journalModel = $journalModel;
        $this->distanceFeedbackModel = $distanceFeedbackModel;
        $this->distanceMaterialModel = $distanceMaterialModel;
        $this->planModel = $planModel;
    }

    public function init($user)
    {
        $this->user = $user;
        $this->model->init($user->id_mektep);
        $this->journalModel->init($user->id_mektep);
        $this->distanceFeedbackModel->init($user->id_mektep);
        $this->distanceMaterialModel->init($user->id_mektep);
        $this->planModel->init($user->id_mektep);

        if (app()->getLocale() == 'ru') $this->lang = 'rus';
        else if (app()->getLocale() == 'kk') $this->lang = 'kaz';
    }

    public function todayDiary() {
        $date = date('Y-m-d');

        $diary = $this->model
            ->select(
                $this->model->getTable().'.date as date',
                $this->model->getTable().'.number as lesson_num',
                $this->model->getTable().'.id_predmet as id_predmet',
                $this->model->getTable().'.tema as tema',
                $this->model->getTable().'.submitted as submitted',
                'mektep_teacher.surname as teacher_surname',
                'mektep_teacher.name as teacher_name',
                'mektep_class.class as class',
                'mektep_class.group as group',
                'mektep_class.smena as smena',
                'mektep_predmet.subgroup as subgroup',
                'mektep_predmet.id_subgroup as id_subgroup',
                'edu_predmet_name.predmet_'.$this->lang.' as predmet_name')
            ->leftJoin('mektep_class', $this->model->getTable().'.id_class', '=', 'mektep_class.id')
            ->leftJoin('mektep_predmet', $this->model->getTable().'.id_predmet', '=', 'mektep_predmet.id')
            ->leftJoin('edu_predmet_name', 'mektep_predmet.predmet', '=', 'edu_predmet_name.id')
            ->leftJoin('mektep_teacher', $this->model->getTable().'.id_teacher', '=', 'mektep_teacher.id')
            ->where($this->model->getTable().'.id_class', $this->user->id_class)
            ->where($this->model->getTable().'.date', $date)
            ->orderBy('mektep_class.smena', 'asc')
            ->orderBy($this->model->getTable().'.number', 'asc')
            ->get()->all();

        $subgroups = DB::table('mektep_class_subgroups')
            ->where('id_class', '=', $this->user->id_class)
            ->get()
            ->keyBy('id')
            ->toArray();

        $smenaQuery = DB::table('mektep_smena')
            ->where('id_mektep', '=', $this->user->id_mektep)
            ->get()->all();
        $smenaQuery = json_decode(json_encode($smenaQuery), true);

        $smenaTime = [];
        foreach ($smenaQuery as $item) {
            for ($i = 1; $i <= 10; $i++) {
                $smenaTime[$item['smena']][$i]['start_time'] = $item['z'.$i.'_start'];
                $smenaTime[$item['smena']][$i]['end_time'] = $item['z'.$i.'_end'];
            }
        }

        $diaryArr = [];
        foreach ($diary as $key => $item) {
//            $prev_tema = $this->model
//                ->select('tema', 'submitted as prev_submitted')
//                ->where('id_teacher', '=', auth()->user()->id)
//                ->where('id_predmet', '=', $item['id_predmet'])
//                ->where('date', '<', $item['date'])
//                ->where('submitted', '=', 1)
//                ->orderBy('date', 'desc')
//                ->first();
//
//            if ($prev_tema) {
//                $diary[$key]['prev_submitted'] = $prev_tema['prev_submitted'] != null ? $prev_tema['prev_submitted'] : 0;
//                $diary[$key]['prev_tema'] = $prev_tema['tema'] != null ? str_replace("\r\n",'', $prev_tema['tema']) : __("Не задано");
//            }
//            else {
//                $diary[$key]['prev_submitted'] = 0;
//                $diary[$key]['prev_tema'] = __("Не задано");
//            }


            if($item->id_subgroup > 0 && $subgroups[$item->id_subgroup]) {
                $subgroup_stud_ids = json_decode($subgroups[$item->id_subgroup]->{'group_students_'.$item->subgroup});

                if(!in_array($this->user->id, $subgroup_stud_ids)) {
                    continue;
                }
            }

            $item['tema'] = $item['tema'] != null ? $item['tema'] : __("Не задано");

            $item['class'] = $item['class'].'«'.$item['group'].'»';
            unset($item['group']);

            $item['start_time'] = $item['date'].' '.$smenaTime[$item['smena']][$item['lesson_num']]['start_time'].':00';
            $item['end_time'] = $item['date'].' '.$smenaTime[$item['smena']][$item['lesson_num']]['end_time'].':00';
            unset($item['date']);

            $diaryArr[] = $item;
        }


        return  $diaryArr;
    }

    public function schedule($monday, $saturday) {
        $weekDiary = $this->model
            ->select('date',
                'number as lesson_num',
                'edu_predmet_name.predmet_'.$this->lang.' as predmet_name',
                'mektep_class.smena as smena',
                'mektep_predmet.id as predmet_id',
                'mektep_predmet.id_subgroup as id_subgroup',
                'mektep_predmet.subgroup as subgroup',
                'mektep_teacher.surname as teacher_surname',
                'mektep_teacher.name as teacher_name',
            )
            ->leftJoin('mektep_predmet', $this->model->getTable().'.id_predmet', '=', 'mektep_predmet.id')
            ->leftJoin('edu_predmet_name', 'mektep_predmet.predmet', '=', 'edu_predmet_name.id')
            ->leftJoin('mektep_class', $this->model->getTable().'.id_class', '=', 'mektep_class.id')
            ->leftJoin('mektep_teacher', $this->model->getTable().'.id_teacher', '=', 'mektep_teacher.id')
            ->where($this->model->getTable().'.id_class', '=', $this->user->id_class)
            ->where($this->model->getTable().'.date', '>=', $monday)
            ->where($this->model->getTable().'.date', '<=', $saturday)
            ->orderBy($this->model->getTable().'.date')
            ->orderBy('mektep_class.smena')
            ->orderBy($this->model->getTable().'.number')
            ->orderBy($this->model->getTable().'.id')
            ->get()->all();

        $subgroups = DB::table('mektep_class_subgroups')
            ->where('id_class', '=', $this->user->id_class)
            ->get()
            ->keyBy('id')
            ->toArray();

        $smenaQuery = DB::table('mektep_smena')
            ->where('id_mektep', '=', $this->user->id_mektep)
            ->get()->all();
        $smenaQuery = json_decode(json_encode($smenaQuery), true);

        $smenaTime = [];
        foreach ($smenaQuery as $item) {
            for ($i = 1; $i <= 10; $i++) {
                $smenaTime[$item['smena']][$i]['start_time'] = $item['z'.$i.'_start'];
                $smenaTime[$item['smena']][$i]['end_time'] = $item['z'.$i.'_end'];
            }
        }

        foreach ($weekDiary as $key => $item) {
            $weekDiary[$key]['start_time'] = $smenaTime[$item['smena']][$item['lesson_num']]['start_time'];
            $weekDiary[$key]['end_time'] = $smenaTime[$item['smena']][$item['lesson_num']]['end_time'];

            $day = date('w', strtotime($item['date']));
            $weekDiary[$key]['day_number'] = $day;
            $weekDiary[$key]['day_name'] = __('d_'.$day);
        }


        $weekDiaryFilteredByDay = [];
        foreach ($weekDiary as $key => $item) {
            if($item['id_subgroup'] > 0 && $subgroups[$item['id_subgroup']]) {
                $subgroup_stud_ids = json_decode($subgroups[$item['id_subgroup']]->{'group_students_'.$item['subgroup']});

                if(!in_array($this->user->id, $subgroup_stud_ids)) {
                    continue;
                }
            }

            if ($item['date'] == date("Y-m-d")) {
                $weekDiaryFilteredByDay[$item['day_number']]['current_day'] = true;
            }
            else {
                $weekDiaryFilteredByDay[$item['day_number']]['current_day'] = false;
            }

            $weekDiaryFilteredByDay[$item['day_number']]['date'] = date("d.m", strtotime($item['date']));
            $weekDiaryFilteredByDay[$item['day_number']]['day'] = $item['day_name'];
            $weekDiaryFilteredByDay[$item['day_number']]['day_number'] = (int)$item['day_number'];
            $weekDiaryFilteredByDay[$item['day_number']]['lessons'][] = $item;

            unset($item['day_number']);
            unset($item['day']);
            unset($item['date']);
        }
        $weekDiaryFilteredByDay2 = [];
        foreach ($weekDiaryFilteredByDay as $item) {
            $weekDiaryFilteredByDay2[] = $item;
        }

        return $weekDiaryFilteredByDay2;
    }

    public function diary($date) {
        $diary = $this->model
            ->select(
                'tema',
                'number as lesson_num',
                'edu_predmet_name.predmet_'.$this->lang.' as predmet_name',
                'mektep_class.smena as smena',
                'mektep_predmet.id as predmet_id',
                'mektep_predmet.subgroup as subgroup',
                'mektep_predmet.id_subgroup as id_subgroup',
                'mektep_teacher.surname as teacher_surname',
                'mektep_teacher.name as teacher_name',
            )
            ->leftJoin('mektep_predmet', $this->model->getTable().'.id_predmet', '=', 'mektep_predmet.id')
            ->leftJoin('edu_predmet_name', 'mektep_predmet.predmet', '=', 'edu_predmet_name.id')
            ->leftJoin('mektep_class', $this->model->getTable().'.id_class', '=', 'mektep_class.id')
            ->leftJoin('mektep_teacher', $this->model->getTable().'.id_teacher', '=', 'mektep_teacher.id')
            ->where($this->model->getTable().'.id_class', '=', $this->user->id_class)
            ->where($this->model->getTable().'.date', '=', $date)
            ->orderBy('mektep_class.smena')
            ->orderBy($this->model->getTable().'.number')
            ->orderBy($this->model->getTable().'.id')
            ->get()->all();

        $subgroups = DB::table('mektep_class_subgroups')
            ->where('id_class', '=', $this->user->id_class)
            ->get()
            ->keyBy('id')
            ->toArray();

        $marks_query = $this->journalModel
            ->where('jurnal_date', '=', $date)
            ->where('jurnal_student_id', '=', $this->user->id)
            ->get()
            ->all();

        $marks = [];
        foreach ($marks_query as $item) {
            $marks[$item['jurnal_predmet']][$item['jurnal_lesson']] = $item['jurnal_mark'];
        }

        $smenaQuery = DB::table('mektep_smena')
            ->where('id_mektep', '=', $this->user->id_mektep)
            ->get()->all();
        $smenaQuery = json_decode(json_encode($smenaQuery), true);

        $smenaTime = [];
        foreach ($smenaQuery as $item) {
            for ($i = 1; $i <= 10; $i++) {
                $smenaTime[$item['smena']][$i]['start_time'] = $item['z'.$i.'_start'];
                $smenaTime[$item['smena']][$i]['end_time'] = $item['z'.$i.'_end'];
            }
        }

        $diary_array = [];
        foreach ($diary as $key => $item) {
            if($item->id_subgroup > 0 && $subgroups[$item->id_subgroup]) {
                $subgroup_stud_ids = json_decode($subgroups[$item->id_subgroup]->{'group_students_'.$item->subgroup});

                if(!in_array($this->user->id, $subgroup_stud_ids)) {
                    continue;
                }
            }

            $item['start_time'] = $smenaTime[$item['smena']][$item['lesson_num']]['start_time'];
            $item['end_time'] = $smenaTime[$item['smena']][$item['lesson_num']]['end_time'];
            $item['mark'] = array_key_exists($item['predmet_id'], $marks) && array_key_exists($item['lesson_num'], $marks[$item['predmet_id']]) ? $marks[$item['predmet_id']][$item['lesson_num']] : null;

            $diary_array[] = $item;
        }



        return $diary_array;
    }

    public function distanceSchedule($page, $pageLimit) {
        $allCount = $this->model->where($this->model->getTable().'.id_class', '=', $this->user->id_class)
            ->where($this->model->getTable().'.date', '<=', date("Y-m-d"))
            ->where($this->model->getTable().'.distance', '=', 1)
            ->count();
        $totalPages = ceil($allCount / $pageLimit);
        $offset = ($page-1) * $pageLimit;


        $schedule = $this->model->select(
            $this->model->getTable().'.id',
            $this->model->getTable().'.date',
            'mektep_predmet.id as predmet_id',
            'mektep_predmet.distance as distance_access',
            'edu_predmet_name.predmet_'.$this->lang.' as predmet_name',
            'mektep_teacher.surname',
            'mektep_teacher.name',
            $this->model->getTable().'.tema',
//            $this->model->getTable().'.distance_status',
            $this->model->getTable().'.created_at',
            $this->distanceFeedbackModel->getTable().'.student_comment',
            $this->distanceFeedbackModel->getTable().'.filename'
        )
            ->leftJoin('mektep_predmet', 'mektep_predmet.id', '=', $this->model->getTable().'.id_predmet')
            ->leftJoin('edu_predmet_name', 'edu_predmet_name.id', '=', 'mektep_predmet.predmet')
            ->leftJoin('mektep_teacher', 'mektep_teacher.id', '=', $this->model->getTable().'.id_teacher')
            ->leftJoin($this->distanceFeedbackModel->getTable(), function($join)
            {
                $join->on($this->distanceFeedbackModel->getTable().'.diary_id', '=', $this->model->getTable().'.id');
                $join->on($this->distanceFeedbackModel->getTable().'.predmet_id','>=', $this->model->getTable().'.id_predmet');
                $join->where($this->distanceFeedbackModel->getTable().'.student_id', '=', $this->user->id);
            })
            ->where($this->model->getTable().'.id_class', '=', $this->user->id_class)
            ->where($this->model->getTable().'.date', '<=', date("Y-m-d"))
            ->where($this->model->getTable().'.distance', '=', 1)
            ->orderBy($this->model->getTable().'.date', 'DESC')
            ->skip($offset)
            ->take($pageLimit)
            ->get()
            ->toArray();

        $scheduleArr = [];
        foreach ($schedule as $item) {
            $item['is_answered'] = $item['student_comment'] != '' || $item['filename'] != '' ? true : false;
            $item['teacher_fio'] = $item['surname'].' '.$item['name'];


            unset($item['student_comment'], $item['filename'], $item['surname'], $item['name']);

            $scheduleArr[] = $item;
        }

        return [
            "count" => $allCount,
            "total_pages" => $totalPages,
            "current_page" => (int)$page,
            "list" => $scheduleArr,
        ];
    }

    public function distanceMaterialByDiaryId($id)
    {
        $diary = $this->model->findOrFail($id);

        if ($diary->distance_status == 0) {
            return response()->json([
                'message' => __('Учитель запретил доступ к материалам этого предмета')
            ], 400);
        }

        $material = $this->distanceMaterialModel->findOrFail($diary->distance_material_id);
        $feedBack = $this->distanceFeedbackModel->where('diary_id', '=', $id)->where('student_id', '=', $this->user->id)->first();
        $predmet = DB::table('mektep_predmet')
            ->select(
                'mektep_predmet.id as id', 'sagat',
                'edu_predmet_name.predmet_' . $this->lang . ' as predmet_name',
                'mektep_teacher.surname as teacher_surname',
                'mektep_teacher.name as teacher_name',
                'mektep_teacher.lastname as teacher_lastname',
            )
            ->leftJoin('edu_predmet_name', 'mektep_predmet.predmet', '=', 'edu_predmet_name.id')
            ->leftJoin('mektep_teacher', 'mektep_predmet.id_teacher', '=', 'mektep_teacher.id')
            ->where('mektep_predmet.id', '=', $diary->id_predmet)
            ->first();


        if ($feedBack) {
            $feedBack->views++;
            $feedBack->save();
        } else {
            $feedBack = new DistanceFeedback();
            $feedBack->init($this->user->id_mektep);
            $feedBack->diary_id = $id;
            $feedBack->student_id = $this->user->id;
            $feedBack->class_id = $this->user->id_class;
            $feedBack->material_id = $material->id;
            $feedBack->predmet_id = $diary->id_predmet;
            $feedBack->teacher_id = $diary->id_teacher;
            $feedBack->mark = '';
            $feedBack->student_comment = '';
            $feedBack->teacher_comment = '';
            $feedBack->filename = '';
            $feedBack->created_at = date('Y-m-d H:i:s');
            $feedBack->answered_at = '0000-01-01 00:00:00';
            $feedBack->views = 1;
            $feedBack->save();
        }


        return [
            'is_answered' => $feedBack->student_comment == '' ? false : true,
//            'resend_status' => $feedBack->teacher_comment == '' ? true : false,
            'diary_id' => $id,
            'predmet_name' => $predmet->predmet_name,
            'sagat' => $predmet->sagat,
            'teacher_fio' => $predmet->teacher_surname . ' ' . $predmet->teacher_name . ' ' . $predmet->teacher_lastname,
            'title' => $material->title,
            'homework' => $material->homework,
            'literatura' => $material->literatura,
            'file_url' => $material->filename,
            'media' => $material->media,
            'link' => $material->link,
            'teacher_comment' => $feedBack->teacher_comment,
            'mark' => $feedBack->mark,
            'student_answer' => $feedBack->student_comment,
            'student_images' => explode(" ", $feedBack->filename),
        ];
    }


    public function getDiary($id)
    {
        return $this->model->findOrFail($id);
    }

    public function getFeedback($diaryId)
    {
        return $this->distanceFeedbackModel
            ->where("diary_id", '=', $diaryId)
            ->where("student_id", '=', $this->user->id)
            ->firstOrFail();
    }

    public function getPlansByPredmet($predmetId) {
        $plan = $this->planModel
            ->select('id','title', 'sagat')
            ->where('mektep_predmet_id', '=', $predmetId)
            ->orderBy('id', 'asc')
            ->get()->all();

        foreach ($plan as $key => $item) {
            $plan[$key]['sagat'] = $item['sagat'].' '.__('ч.');
            $plan[$key]['title'] = str_replace("\r\n",'', $item['title']);
        }

        return $plan;
    }
}
