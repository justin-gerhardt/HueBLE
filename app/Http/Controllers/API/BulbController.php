<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\HueBulb;

class BulbController extends Controller
{

    public function index()
    {
        return HueBulb::all()->load("groups");
    }


    public function store(Request $request)
    {
        $bulb = HueBulb::create($request->all());
        if ($request->has("groups")) {
            $bulb->groups()->sync($request->input("groups"));
        }
        return $bulb->load("groups");
    }


    public function show(HueBulb $bulb)
    {
        return $bulb->load("groups");
    }


    public function update(Request $request, HueBulb $bulb)
    {
        $bulb->update($request->all());
        if ($request->has("groups")) {
            $bulb->groups()->sync($request->input("groups"));
        }
        return $bulb->load("groups");
    }

    public function destroy(HueBulb $bulb)
    {
        $bulb->delete();
        return 204;
    }

    public function isLit(HueBulb $bulb)
    {
        return '{"lit": ' . ($bulb->isLit() ? "true" : "false") . '}';
    }

    public function setState(Request $request, HueBulb $bulb)
    {
        $state = $request->boolean('lit');
        # TODO: distinguish false and not set
        $bulb->SetState($state);
    }
}
