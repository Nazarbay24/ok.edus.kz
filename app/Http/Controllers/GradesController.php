<?php

namespace App\Http\Controllers;

use App\Repositories\GradesRepository;
use Illuminate\Http\Request;

class GradesController extends Controller
{
    protected $repository;

    public function __construct(GradesRepository $repository)
    {
        $this->repository = $repository;
    }

    public function predmetsList() {
        $user = auth()->user();
        $this->repository->init($user);

        $predmetList = $this->repository->predmetList($user);

        return response()->json([
            "predmet_list" => $predmetList
        ]);
    }


    public function jurnalGrades(Request $request) {
        $id_predmet = $request->input('id_predmet');
        $chetvert = $request->input('chetvert');
        $month = sprintf("%02d", $request->input('month'));

        $user = auth()->user();
        $this->repository->init($user);

        $jurnal = $this->repository->jurnal($id_predmet, $chetvert, $month);

        return response()->json($jurnal);
    }

    public function chetvertGrades(Request $request) {
        $user = auth()->user();
        $this->repository->init($user);

        $chetvert = $this->repository->chetvert();

        return response()->json($chetvert);
    }

    public function criterialGrades(Request $request) {
        $id_predmet = $request->input('id_predmet');
        $chetvert = $request->input('chetvert');

        $user = auth()->user();
        $this->repository->init($user);

        $criterial = $this->repository->criterial($id_predmet, $chetvert);

        return response()->json($criterial);
    }
}
