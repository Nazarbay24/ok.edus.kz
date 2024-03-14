<?php

namespace App\Repositories;


use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\DB;

class ProfileRepository
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

    public function profile() {
        $classAndMektep = DB::table('mektep_class')
            ->select('mektep_class.class',
                'mektep_class.group',
                'mektepter.name_'.$this->lang.' as mektep_name',
                'mektep_teacher.name as name',
                'mektep_teacher.surname as surname',
                'mektep_teacher.lastname as lastname',
            )
            ->leftJoin('mektepter', 'mektep_class.id_mektep', '=', 'mektepter.id')
            ->leftJoin('mektep_teacher', 'mektep_class.kurator', '=', 'mektep_teacher.id')
            ->where('mektep_class.id', '=', $this->user->id_class)
            ->first();

        $parents = DB::table('mektep_parents')
            ->select('name', 'surname', 'lastname')
            ->whereIn('id', [$this->user->parent_ata_id, $this->user->parent_ana_id])
            ->get()
            ->toArray();

        $card = DB::connection('cards')
            ->table('cards_ready')
            ->select('card_number')
            ->where('status', '=', 'student')
            ->where('user_id', $this->user->id)
            ->orderBy('is_active', 'desc')
            ->orderBy('updated_at')
            ->first();

        $card_number = $card ? (string)$card->card_number : '0000000';

//        $notifications = DB::table('mobile_students_notification')
//            ->select('mobile_students_notification.id as id',
//                'mobile_students_notification.slug_'.app()->getLocale().' as name',
//                'mobile_students_notification_permission.notification_id as status'
//            )
//            ->leftJoin('mobile_students_notification_permission', function ($join) {
//                $join->on('mobile_students_notification_permission.notification_id', '=', 'mobile_students_notification.id')
//                    ->where('mobile_students_notification_permission.student_id', '=', $this->user->id);
//            })
//            ->get()
//            ->toArray();

//        $notificationsArr = [];
//        foreach ($notifications as $item) {
//            $item->status = $item->status == null ? true : false;
//            $notificationsArr[] = $item;
//        }

        return [
            "qr_code" => md5($this->user->iin),
            "card_number" => (string)$card_number,
            "balance" => "0.00",
            "name" => $this->user->name,
            "surname" => $this->user->surname,
            "lastname" => $this->user->lastname,
            "pol" => $this->user->pol,
            "teacher_fio" => $classAndMektep->surname.' '.$classAndMektep->name.' '.$classAndMektep->lastname,
//            "birthday" => date('d.m.Y', strtotime($this->user->birthday)),
            "mektep" => $classAndMektep->mektep_name,
            "class" => $classAndMektep->class.' «'.$classAndMektep->group.'»',
            "language" => $this->user->mobile_lang != null ? $this->user->mobile_lang : 'kk',
            "parent_1" => key_exists(0, $parents) ? $parents[0] : null,
            "parent_2" => key_exists(1, $parents) ? $parents[1] : null,
//            "notifications" => $notificationsArr
        ];
    }

    public function classInfo() {
        $classAndMektepAndTeacher = DB::table('mektep_class')
            ->select('mektep_class.class',
                'mektep_class.group',
                'mektep_class.id as class_id',
                'edu_language.lang_'.$this->lang.' as class_lang',
                'edu_class_type.lang_'.$this->lang.' as class_type',
                'mektepter.name_'.$this->lang.' as mektep_name',
                'mektep_teacher.name as teacher_name',
                'mektep_teacher.surname as teacher_surname',
                'mektep_teacher.lastname as teacher_lastname',
            )
            ->leftJoin('edu_class_type', 'mektep_class.class_type_id', '=', 'edu_class_type.id')
            ->leftJoin('mektepter', 'mektep_class.id_mektep', '=', 'mektepter.id')
            ->leftJoin('mektep_teacher', 'mektep_class.kurator', '=', 'mektep_teacher.id')
            ->leftJoin('edu_language', 'mektep_class.edu_language', '=', 'edu_language.id')
            ->where('mektep_class.id', '=', $this->user->id_class)
            ->first();

        $classmates = DB::table('mektep_students')
            ->select('id', 'name', 'surname', 'lastname', 'birthday', 'email')
            ->where('id_class', '=', $this->user->id_class)
            ->where('id', '!=', $this->user->id)
            ->orderBy('surname')
            ->orderBy('name')
            ->get()
            ->toArray();

        $classmatesArr = [];
        foreach ($classmates as $item) {
            $item->birthday = date('d.m.Y', strtotime($item->birthday));
            $classmatesArr[] = $item;
        }

        return [
            "mektep" => $classAndMektepAndTeacher->mektep_name,
            "class" => $classAndMektepAndTeacher->class.' «'.$classAndMektepAndTeacher->group.'»',
            "kurator_fio" => $classAndMektepAndTeacher->teacher_surname.' '.$classAndMektepAndTeacher->teacher_name.' '.$classAndMektepAndTeacher->teacher_lastname,
            "class_id" => $classAndMektepAndTeacher->class_id,
            "class_type" => $classAndMektepAndTeacher->class_type,
            "class_lang" => $classAndMektepAndTeacher->class_lang,
            "classmates" => $classmatesArr
        ];
    }

    public function teachersList() {
        $teachers = DB::table('mektep_predmet')
            ->select('mektep_teacher.id as id',
                'mektep_teacher.surname as surname',
                'mektep_teacher.name as name',
                'mektep_teacher.birthday as birthday',
                'mektep_teacher.last_visit as last_visit',
                'edu_predmet_name.predmet_'.$this->lang.' as predmet_name',
            )
            ->leftJoin('mektep_teacher', 'mektep_teacher.id', '=', 'mektep_predmet.id_teacher')
            ->leftJoin('edu_predmet_name', 'edu_predmet_name.id', '=', 'mektep_predmet.predmet')
            ->where('mektep_predmet.id_class', '=', $this->user->id_class)
            ->orderBy('mektep_teacher.surname')
            ->orderBy('mektep_teacher.name')
            ->get()
            ->toArray();

        $teachersArr = [];
        foreach ($teachers as $item) {
            $item->birthday = date('d.m.Y', strtotime($item->birthday));
            $item->last_visit = mb_substr($item->last_visit, 0, 16);

            $teachersArr[] = $item;
        }

        return $teachersArr;
    }

    public function classmatesList() {
        $classmates = DB::table('mektep_students')
            ->select('id', 'name', 'surname', 'lastname', 'birthday', 'email')
            ->where('id_class', '=', $this->user->id_class)
            ->where('id', '!=', $this->user->id)
            ->orderBy('surname')
            ->orderBy('name')
            ->get()
            ->toArray();

        $classmatesArr = [];
        foreach ($classmates as $item) {
            $item->birthday = date('d.m.Y', strtotime($item->birthday));
            $classmatesArr[] = $item;
        }

        return $classmatesArr;
    }

    public function schoolInfo() {
        $mektep = DB::table('mektepter', 'm')
            ->select(
                'm.full_name_'.$this->lang.' as mektep_name',
                'm.address_'.$this->lang.' as mektep_address',
                'm.phone',
                'm.email',
                'm.web',
                't.surname',
                't.name',
                't.lastname'
            )
            ->leftJoin('mektep_teacher as t', 't.id', '=', 'm.director')
            ->where('m.id', $this->user->id_mektep)
            ->first();

        $mektep->director_fio = $mektep->surname.' '.$mektep->name.' '.$mektep->lastname;
        unset($mektep->surname, $mektep->name, $mektep->lastname);

        return $mektep;
    }


}
