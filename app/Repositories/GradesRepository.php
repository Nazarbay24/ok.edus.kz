<?php

namespace App\Repositories;

use App\Models\Chetvert;
use App\Models\CriterialMark;
use App\Models\Diary;
use App\Models\Journal;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;


class GradesRepository
{
    use Notifiable;

    protected $user;
    protected $lang;
    protected $jurnalModel;
    protected $criterialModel;
    protected $chetvertModel;
    protected $diaryModel;

    public function __construct(Journal $jurnalModel, CriterialMark $criterialModel, Chetvert $chetvertModel, Diary $diaryModel) {
        $this->jurnalModel = $jurnalModel;
        $this->criterialModel = $criterialModel;
        $this->chetvertModel = $chetvertModel;
        $this->diaryModel = $diaryModel;
    }

    public function init($user)
    {
        $this->user = $user;
        $this->jurnalModel->init($user->id_mektep);
        $this->criterialModel->init($user->id_mektep);
        $this->chetvertModel->init($user->id_mektep);
        $this->diaryModel->init($user->id_mektep);

        if (app()->getLocale() == 'ru') $this->lang = 'rus';
        else if (app()->getLocale() == 'kk') $this->lang = 'kaz';
    }

    public function predmetList() {
        $predmets = DB::table('mektep_predmet')
            ->select(
                'mektep_predmet.id as id', 'sagat', 'subgroup', 'id_subgroup', 'max_ch_1', 'max_ch_2', 'max_ch_3', 'max_ch_4',
                'edu_predmet_name.predmet_'.$this->lang.' as predmet_name',
                'mektep_teacher.surname as teacher_surname',
                'mektep_teacher.name as teacher_name',
            )
            ->leftJoin('edu_predmet_name', 'mektep_predmet.predmet', '=', 'edu_predmet_name.id')
            ->leftJoin('mektep_teacher', 'mektep_predmet.id_teacher', '=', 'mektep_teacher.id')
            ->where('mektep_predmet.id_class', '=', $this->user->id_class)
            ->get()
            ->toArray();

        $predmets_arr = [];
        foreach ($predmets as $item) {
            $item->is_criterial = false;

            for($i = 1; $i <= 4; $i++) {
                if($item->{'max_ch_'.$i} != null) {
                    $item->is_criterial = true;
                }
                unset($item->{'max_ch_'.$i});
            }

            $predmets_arr[] = $item;
        }

        return $predmets_arr;
    }


    public function jurnal($id_predmet, $chetvert, $month = null) {
        $predmet = $this->getPredmet($id_predmet);

        unset($predmet->max_ch_1, $predmet->max_ch_2 ,$predmet->max_ch_3, $predmet->max_ch_4);

        if($month) {
            $monthNext = $month == 12 ? '01' : sprintf("%02d", $month+1);
            $year = $month >= 9 ? config('mektep_config.year') : config('mektep_config.year') + 1;
            $yearNext = $monthNext >= 9 ? config('mektep_config.year') : config('mektep_config.year') + 1;

            $dateStart = $year.'-'.$month.'-01';
            $dateEnd = $yearNext.'-'.$monthNext.'-01';
        }
        else {
            $chetvertDates = config('mektep_config.chetvert');

            $dateStart = $chetvertDates[$chetvert]['start'];
            $dateEnd = $chetvertDates[$chetvert]['end'];
        }

        $jurnal = $this->diaryModel
            ->select(
                $this->diaryModel->getTable().'.date',
                $this->jurnalModel->getTable().'.jurnal_mark'
            )
            ->leftJoin($this->jurnalModel->getTable(), function ($join) {
                $join->on($this->jurnalModel->getTable().'.jurnal_date', '=', $this->diaryModel->getTable().'.date');
                $join->on($this->jurnalModel->getTable().'.jurnal_predmet','=', $this->diaryModel->getTable().'.id_predmet');
                $join->where($this->jurnalModel->getTable().'.jurnal_student_id', '=', $this->user->id);
            })
            ->where($this->diaryModel->getTable().'.id_predmet', '=', $id_predmet)
            ->where($this->diaryModel->getTable().'.date', '>=', $dateStart)
            ->where($this->diaryModel->getTable().'.date', $month ? '<' : '<=', $dateEnd)
            ->orderBy($this->diaryModel->getTable().'.date')
            ->get()->toArray();

        $jurnal_arr = [];
        foreach ($jurnal as $item) {
            $new_item = [
                'date' => date('d', strtotime($item['date'])),
                'grade' => $item['jurnal_mark'],
            ];

            $month = date('m', strtotime($item['date']));

            $jurnal_arr[$month]['name'] = __('m_'.$month);
            $jurnal_arr[$month]['dates'][] = $new_item;
        }

        $jurnal_arr2 = [];
        foreach ($jurnal_arr as $item) {
            $jurnal_arr2[] = $item;
        }

        return [
            "predmet" => $predmet,
            "list" => $jurnal_arr2
        ];
    }

    public function chetvert() {
        $predmetList = $this->predmetList();

        $chetvertMarks = $this->chetvertModel
            ->where('id_student', '=', $this->user->id)
            ->get()
            ->toArray();

        $chetvertMarksByPredmetId = [];
        foreach ($chetvertMarks as $item) {
            $chetvertMarksByPredmetId[$item['id_predmet']][$item['chetvert_nomer']] = $item['mark'];
        }

        $predmets = [];
        foreach ($predmetList as $item) {
            $grades = [];
            for($i = 1; $i <= 7; $i++) {
                $grades[] = [
                    'chetvert' => $i,
                    'grade' => key_exists($item->id, $chetvertMarksByPredmetId) && key_exists($i, $chetvertMarksByPredmetId[$item->id]) ? $chetvertMarksByPredmetId[$item->id][$i] : null
                ];
            }

            $predmets[] = [
                'predmet_name' => $item->predmet_name,
                'grades' => $grades
            ];
        }

        return [
            "predmet_list" => $predmets,
        ];
    }

    public function criterial($id_predmet, $chetvert) {
        $predmet = $this->getPredmet($id_predmet);
        $predmet = (array) $predmet;
        $criterialMarks = $this->getCriterialMarks($id_predmet);
        $studentsList = [
            0 => [
                'id' => $this->user->id,
                'fio' => $this->user->surname.' '.$this->user->name
            ]
        ];

        if ($predmet['max_ch_1'] == null && $predmet['max_ch_2'] == null && $predmet['max_ch_3'] == null && $predmet['max_ch_4'] == null) {
            throw new \Exception('Not found',404);
        }

        if($predmet['class_num'] == 1){
            $mark2 = range(0,20);
            $mark3 = range(21,50);
            $mark4 = range(51,80);
            $mark5 = range(81,100);
        }else{
            $mark2 = range(0,39);
            $mark3 = range(40,64);
            $mark4 = range(65,84);
            $mark5 = range(85,100);
        }

        // максимальные баллы за четверть
        $criterialMax[1] = json_decode($predmet['max_ch_1']);
        $criterialMax[2] = json_decode($predmet['max_ch_2']);
        $criterialMax[3] = json_decode($predmet['max_ch_3']);
        $criterialMax[4] = json_decode($predmet['max_ch_4']);

        if( $chetvert < 5 && !is_array($criterialMax[$chetvert]) ) {
//            throw new \Exception(__('СОр, СОч за этот четверть не настроено'),404);

            return [
                'predmet_name' => $predmet['predmet_name'],
                'class' => $predmet['class'],
                'sagat' => $predmet['sagat'],
                'chetvert' => $chetvert,
                'is_half_year' => false,
                'sor' => [
                    [
                        'sor_num' => 1,
                        'sor_max' => 1,
                    ]
                ],
                'soch' => false,
                'soch_max' => 0,
                'students_list' => $studentsList,
            ];
        }

        // количество СОР за четверть
        $sorCount[1] = is_array($criterialMax[1]) ? count($criterialMax[1])-1 : 0;
        $sorCount[2] = is_array($criterialMax[2]) ? count($criterialMax[2])-1 : 0;
        $sorCount[3] = is_array($criterialMax[3]) ? count($criterialMax[3])-1 : 0;
        $sorCount[4] = is_array($criterialMax[4]) ? count($criterialMax[4])-1 : 0;

//        $sorCount[1] = $predmetCriterial['num_sor_1'];
//        $sorCount[2] = $predmetCriterial['num_sor_2'];
//        $sorCount[3] = $predmetCriterial['num_sor_3'];
//        $sorCount[4] = $predmetCriterial['num_sor_4'];

        // количество СОЧ за четверть
        $sochCount[1] = is_array($criterialMax[1]) && $criterialMax[1][0] != null ? 1 : 0;
        $sochCount[2] = is_array($criterialMax[2]) && $criterialMax[2][0] != null ? 1 : 0;
        $sochCount[3] = is_array($criterialMax[3]) && $criterialMax[3][0] != null ? 1 : 0;
        $sochCount[4] = is_array($criterialMax[4]) && $criterialMax[4][0] != null ? 1 : 0;

        $sochCountForGodovoy = false;
        foreach ($sochCount as $item) {
            if($item == 1) $sochCountForGodovoy = true;
        }

//        $sochCount[1] = $predmetCriterial['num_soch_1'];
//        $sochCount[2] = $predmetCriterial['num_soch_2'];
//        $sochCount[3] = $predmetCriterial['num_soch_3'];
//        $sochCount[4] = $predmetCriterial['num_soch_4'];


// ЧЕТВЕРТНОЙ начало *******************
        if ($chetvert < 5)
        {
            $isHalfYear = ($chetvert == 2 || $chetvert == 4) && $sochCount[$chetvert-1] == 0 ? true : false; // полугодие ли этот четверть
            $formativeMarks = $this->getFormativeMarks($id_predmet, $chetvert, $isHalfYear);

            $sochMax = $sochCount[$chetvert] > 0 ? $criterialMax[$chetvert][0] : 0;

            if ($isHalfYear) {  // максимально возможный балл всех СОР за четверть, если это полугодие то за 2 четверти
                if ($sochCount[$chetvert] > 0) {
                    $sorMaxAll = abs((array_sum($criterialMax[$chetvert]) + array_sum($criterialMax[$chetvert-1])) - $sochMax);
                }
                else {
                    $sorMaxAll = abs((array_sum($criterialMax[$chetvert]) + array_sum($criterialMax[$chetvert-1])));
                }
            }
            else {
                if ($sochCount[$chetvert] > 0) {
                    $sorMaxAll = abs(array_sum($criterialMax[$chetvert]) - $sochMax);
                }
                else {
                    if(is_array($criterialMax[$chetvert])) {
                        $sorMaxAll = abs(array_sum($criterialMax[$chetvert]));
                    }
                    else {
                        $sorMaxAll = 0;
                    }
                }
            }


            foreach ($studentsList as $student_key => $student) { // тут начинается основная логика вычисления процентов четверти
                $mark = 0;
                $sorTotalGrade = 0;
                $sochGrade = null;
                $totalProc = null;

                for ($i = 0; $i < $sorCount[$chetvert]; $i++) { // суммируем все баллы СОР за четверть
                    if (isset($criterialMarks[$student['id']][$chetvert][$i+1])) {
                        $sorTotalGrade = $sorTotalGrade + $criterialMarks[$student['id']][$chetvert][$i+1];
                    }
                }
                if ($isHalfYear) { // если это полугодие прибавляем к СОР баллы предедущего четверти
                    for ($i = 0; $i < $sorCount[$chetvert-1]; $i++) {
                        if (isset($criterialMarks[$student['id']][$chetvert-1][$i+1])) {
                            $sorTotalGrade = $sorTotalGrade + $criterialMarks[$student['id']][$chetvert-1][$i+1];
                        }
                    }
                }

                if (isset($criterialMarks[$student['id']][$chetvert][0])) { // если есть получаем бал СОЧ
                    $sochGrade = $criterialMarks[$student['id']][$chetvert][0];
                }


                if ($isHalfYear || $sochCount[$chetvert] > 0) // Вычисляем проценты если это полугодие или есть СОЧ, иначе просто показываем оценки СОР
                {
                    $formativeProc = null;
                    if (array_key_exists($student['id'],$formativeMarks )) {
                        $formativeProc = round(number_format((($formativeMarks[$student['id']]/10) * 100 * ($sochCount[$chetvert] > 0 ? 0.25 : 0.5)), 1, '.', ''));
                    }
                    $sorProc = round(number_format((($sorTotalGrade/$sorMaxAll)*100 * ($sochCount[$chetvert] > 0 ? 0.25 : 0.5)), 1, '.', ''));

                    if ($sochCount[$chetvert] > 0) { // если есть СОЧ за четверть вычисляем суммарный проц с вместе с СОЧ, иначе без СОЧ
                        if ($sochGrade) { // если есть оценка СОЧ у студента
                            $sochProc = round(number_format((($sochGrade/$sochMax)*100 * 0.5), 1, '.', ''));
                            $totalProc = round(number_format(($formativeProc + $sorProc + $sochProc), 1, '.', '')); // суммарный проц с СОЧ

                            $studentsList[$student_key]['soch_grade'] = (string)$sochGrade;
                            $studentsList[$student_key]['soch_proc'] = $sochProc.' %';
                        }
                        else {
                            $studentsList[$student_key]['soch_grade'] = '0';
                            $studentsList[$student_key]['soch_proc'] = '0 %';
                        }
                    }
                    else { // суммарный проц без СОЧ
                        $totalProc = round(number_format(($formativeProc + $sorProc), 1, '.', ''));
                    }


                    // оцениваем если студенту выставлена оценка СОЧ или за полугодие СОЧ не оценивается
                    if ($totalProc && (($sochCount[$chetvert] > 0 && is_numeric($sochGrade)) || $sochCount[$chetvert] == 0))
                    {
                        if     (in_array($totalProc, $mark2)) $mark = 2;
                        elseif (in_array($totalProc, $mark3)) $mark = 3;
                        elseif (in_array($totalProc, $mark4)) $mark = 4;
                        elseif (in_array($totalProc, $mark5)) $mark = 5;
                    }

//                    if (array_key_exists($student['id'],$formativeMarks )) {
//                        $studentsList[$student_key]['formative_grade'] = $formativeMarks[$student['id']];
//                    }
                    $studentsList[$student_key]['formative_grade'] = array_key_exists($student['id'],$formativeMarks) ? $formativeMarks[$student['id']] : '0';
                    $studentsList[$student_key]['formative_proc'] = $formativeProc ? $formativeProc.' %' : '0 %';
                    $studentsList[$student_key]['sor_proc'] = $sorProc ? $sorProc.' %' : '0 %';
                    $studentsList[$student_key]['total_proc'] = $totalProc ? $totalProc.' %' : '0 %';
                    $studentsList[$student_key]['grade'] = strval($mark);
                } // конец вычисления

                // оценки СОР
                $studentsList[$student_key]['sor'] = [];
                for ($i = 0; $i < $sorCount[$chetvert]; $i++) {
                    if (isset($criterialMarks[$student['id']][$chetvert][$i+1])) {
                        $studentsList[$student_key]['sor'][] = strval($criterialMarks[$student['id']][$chetvert][$i+1]);
                    }
                    else {
                        $studentsList[$student_key]['sor'][] = '';
                    }
                }
            }

            $sor = []; // максимально возможные баллы за каждый СОР
            for ($i = 0; $i < $sorCount[$chetvert]; $i++) {
                $sor[] = [
                    'sor_num' => $i+1,
                    'sor_max' => $criterialMax[$chetvert][$i+1],
                ];
            }

            //$halfYearSystem = $sochCount[1] > 0 ? false : true;

            return [
                'predmet_name' => $predmet['predmet_name'],
                'sagat' => $predmet['sagat'],
                'chetvert' => $chetvert,
                'is_half_year' => $isHalfYear,
                'sor' => $sor,
                'soch' => (bool)$sochCount[$chetvert],
                'soch_max' => $sochMax,
                'students_list' => $studentsList,
            ];
        }
// ЧЕТВЕРТНОЙ конец *****************


// ГОДОВОЙ начало ******************
        elseif ($chetvert == 5)
        {
            $formativeMarks = $this->getFormativeMarks($id_predmet, $chetvert, false, true);

            $sochMax = 0;
            $sorMaxAll = 0;
            for ($chet = 1; $chet < 5; $chet++)
            {
                if ($sochCount[$chet] > 0) {
                    $sochMax = $sochMax + $criterialMax[$chet][0];
                }
                for ($sor = 0; $sor < $sorCount[$chet]; $sor++) {
                    $sorMaxAll = $sorMaxAll + $criterialMax[$chet][$sor+1];
                }
            }


            foreach ($studentsList as $student_key => $student) {
                $mark = 0;
                $sorTotal = 0;
                $sochTotal = null;


                for ($chet = 1; $chet < 5; $chet++) {
                    for ($sor = 0; $sor < $sorCount[$chet]; $sor++) {
                        if (isset($criterialMarks[$student['id']][$chet][$sor+1])) {
                            $sorTotal = $sorTotal + $criterialMarks[$student['id']][$chet][$sor+1];
                        }
                    }
                }

                if ($sochCountForGodovoy) {
                    for ($chet = 1; $chet < 5; $chet++) {
                        if (isset($criterialMarks[$student['id']][$chet][0])) {
                            $sochTotal = $sochTotal + $criterialMarks[$student['id']][$chet][0];
                        }
                    }
                }

                if (array_key_exists($student['id'],$formativeMarks )) {
                    $formativeProc = round(number_format((($formativeMarks[$student['id']] / 10) * 100 * ($sochCountForGodovoy ? 0.25 : 0.5)), 1, '.', ''), 1);
                }
                $sorProc = round(number_format((($sorTotal / $sorMaxAll) * 100 * ($sochCountForGodovoy ? 0.25 : 0.5)), 1, '.', ''), 1);

                if ($sochTotal) {
                    $sochProc = round(number_format((($sochTotal / $sochMax) * 100 * 0.5), 1, '.', ''), 1);
                    $totalProc = round(number_format(($formativeProc + $sorProc + $sochProc), 1, '.', ''));

                    $studentsList[$student_key]['soch_grade'] = intval($sochTotal);
                    $studentsList[$student_key]['soch_proc'] = $sochProc.' %';
                } else {
                    $totalProc = round(number_format(($formativeProc + $sorProc), 1, '.', ''));
                }


                if (in_array($totalProc, $mark2)) $mark = 2;
                elseif (in_array($totalProc, $mark3)) $mark = 3;
                elseif (in_array($totalProc, $mark4)) $mark = 4;
                elseif (in_array($totalProc, $mark5)) $mark = 5;

                if (array_key_exists($student['id'],$formativeMarks )) {
                    $studentsList[$student_key]['formative_grade'] = $formativeMarks[$student['id']];
                }
                else {
                    $studentsList[$student_key]['formative_grade'] = '0';
                }
                $studentsList[$student_key]['sor_grade'] = strval($sorTotal);
                $studentsList[$student_key]['formative_proc'] = $formativeProc ? $formativeProc . ' %' : '0 %';
                $studentsList[$student_key]['sor_proc'] = $sorProc ? $sorProc . ' %' : '0 %';
                $studentsList[$student_key]['total_proc'] = $totalProc ? $totalProc . ' %' : '0 %';
                $studentsList[$student_key]['grade'] = strval($mark);
            }

            $halfYearSystem = $sochCount[1] > 0 ? false : true;
            return [
                'predmet_name' => $predmet['predmet_name'],
                'sagat' => $predmet['sagat'],
                'chetvert' => $chetvert,
                'is_half_year' => $halfYearSystem,
                'soch' => (bool)$sochCount[2],
                'soch_max' => strval($sochMax),
                'sor_max' => strval($sorMaxAll),
                'students_list' => $studentsList,
            ];
        }
    }

    protected function getPredmet($id_predmet) {
        return DB::table('mektep_predmet')
            ->select(
                'sagat', 'subgroup', 'id_subgroup',
                'edu_predmet_name.predmet_'.$this->lang.' as predmet_name',
                'mektep_teacher.surname as teacher_surname',
                'mektep_teacher.name as teacher_name',
                'mektep_class.class as class_num',
                'mektep_predmet.max_ch_1 as max_ch_1',
                'mektep_predmet.max_ch_2 as max_ch_2',
                'mektep_predmet.max_ch_3 as max_ch_3',
                'mektep_predmet.max_ch_4 as max_ch_4',
            )
            ->leftJoin('edu_predmet_name', 'mektep_predmet.predmet', '=', 'edu_predmet_name.id')
            ->leftJoin('mektep_teacher', 'mektep_predmet.id_teacher', '=', 'mektep_teacher.id')
            ->leftJoin('mektep_class', 'mektep_predmet.id_class', '=', 'mektep_class.id')
            ->where('mektep_predmet.id', '=', $id_predmet)
            ->first();
    }


    public function getCriterialMarks($id_predmet)
    {
        $criterialMarksQuery = $this->criterialModel
            ->where('id_student', '=', $this->user->id)
            ->where('id_predmet', '=', $id_predmet)
            ->get()->all();

        $criterialMarks = [];
        foreach ($criterialMarksQuery as $item) {
            $criterialMarks[$item['id_student']][$item['chetvert']][$item['razdel']] = $item['student_score'];
        }

        return $criterialMarks;
    }

    public function getStudentsList($id_class, $subgroup, $id_subgroup)
    {
        $studentsList = Student::
        select('id',
            'name',
            'surname',
            'lastname')
            ->where('id_class', '=', $id_class)
            ->orderBy('surname_latin')
            ->get()->all();
        if (!$studentsList) throw new \Exception('Not found',404);

        if ($id_subgroup > 0) {
            $subgroup = ClassSubgroup::select('group_students_'.$subgroup.' as ids')->where('id', '=', $id_subgroup)->first();
            $subgroup_students = json_decode($subgroup['ids']);
        }

        $studentsListWithFIO = [];
        foreach ($studentsList as $key => $item) {
            if ($id_subgroup > 0) {
                if (in_array($item['id'], $subgroup_students)) {
                    $studentsListWithFIO[] = [
                        "id" => (int)$item['id'],
                        "fio" => $item['surname'].' '.$item['name'],
                    ];
                }
            }
            else {
                $studentsListWithFIO[] = [
                    "id" => (int)$item['id'],
                    "fio" => $item['surname'].' '.$item['name'],
                ];
            }
        }

        return $studentsListWithFIO;
    }

    public function getFormativeMarks($id_predmet, $chetvert, $isHalfYear = false, $isYear = false)
    {
        if ($isYear) {
            $allMarksQuery = $this->jurnalModel
                ->where('jurnal_student_id', '=', $this->user->id)
                ->where('jurnal_predmet', '=', $id_predmet)
                ->get()->all();
        }
        else {
            $chetvertDates = config('mektep_config.chetvert');
            $chetvertStart = $isHalfYear ? $chetvertDates[$chetvert-1]['start'] : $chetvertDates[$chetvert]['start'];
            $chetvertEnd = $chetvertDates[$chetvert]['end'];

            $allMarksQuery = $this->jurnalModel
                ->where('jurnal_student_id', '=', $this->user->id)
                ->where('jurnal_predmet', '=', $id_predmet)
                ->where('jurnal_date', '>=', $chetvertStart)
                ->where('jurnal_date', '<=', $chetvertEnd)
                ->get()->all();
        }


        $allMarks = [];
        foreach ($allMarksQuery as $item) {
            if ($item['jurnal_mark'] >= 1 && $item['jurnal_mark'] <= 10) {
                $allMarks[$item['jurnal_student_id']]['marks'][] = $item['jurnal_mark'];
            }
        }
        foreach ($allMarks as $id => $marks) {
            $allMarks[$id] = strval(round(array_sum($marks['marks']) / count($marks['marks']), 1));
        }

        return $allMarks;
    }
}
