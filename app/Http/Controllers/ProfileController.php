<?php

namespace App\Http\Controllers;

use App\Repositories\ProfileRepository;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ProfileController extends Controller
{
    protected $repository;

    public function __construct(ProfileRepository $repository)
    {
        $this->repository = $repository;
    }

    public function profile() {
        $user = auth()->user();
        $this->repository->init($user);

        $profile = $this->repository->profile();

        return response()->json($profile);
    }

    public function classInfo() {
        $user = auth()->user();
        $this->repository->init($user);

        $classInfo = $this->repository->classInfo();

        return response()->json($classInfo);
    }

    public function teachersList() {
        $user = auth()->user();
        $this->repository->init($user);

        $teachersList = $this->repository->teachersList();

        return response()->json([
            "teachers_list" => $teachersList
        ]);
    }

    public function classmatesList() {
        $user = auth()->user();
        $this->repository->init($user);

        $classmatesList = $this->repository->classmatesList();

        return response()->json([
            "classmates_list" => $classmatesList
        ]);
    }

    public function schoolInfo() {
        $user = auth()->user();
        $this->repository->init($user);

        $data = $this->repository->schoolInfo();

        return response()->json($data);
    }

    public function changeLang(Request $request) {
        $request->validate([
            "lang" => "required|in:ru,kk,en",
        ]);

        $user = auth()->user();
        $user->mobile_lang = $request->lang;

        if($user->save()) {
            return response()->json([
                "message" => 'success'
            ]);
        }
        else {
            return response()->json([
                "message" => 'error'
            ],400);
        }
    }

    public function changePassword(Request $request) {
        $request->validate([
            "cur_password" => "required",
            "new_password" => "required|min:6",
        ]);

        $user = auth()->user();

        if($request->cur_password == $user->pass) {
            $user->pass = $request->new_password;
            if($user->save()) {
                return response()->json([
                    "message" => __("Пароль успешно изменен")
                ]);
            }
        }
        else {
            return response()->json([
                "message" => __("Неверный пароль")
            ],400);
        }
    }
}
