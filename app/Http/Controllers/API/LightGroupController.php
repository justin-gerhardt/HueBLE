<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\LightGroup;
use Illuminate\Http\Request;

class LightGroupController extends Controller
{
    public function index()
    {
        return LightGroup::all()->load("bulbs");
    }


    public function store(Request $request)
    {
        $group = LightGroup::create($request->all());
        if ($request->has("bulbs")) {
            $group->bulbs()->sync($request->input("bulbs"));
        }
        return $group->load("bulbs");
    }


    public function show(LightGroup $group)
    {
        return $group->load("bulbs");
    }


    public function update(Request $request, LightGroup $group)
    {
        $group->update($request->all());
        if ($request->has("bulbs")) {
            $group->bulbs()->sync($request->input("bulbs"));
        }
        return $group->load("bulbs");
    }

    public function destroy(LightGroup $group)
    {
        $group->delete();
        return 204;
    }


    public function isLit(LightGroup $group){
        return '{"lit": "' . ($group->LitStatus() ?? "null") . '"}';
    }

    public function setState(Request $request, LightGroup $group){
        $state =  $request->boolean('lit');
        # TODO: distinguish false and not set
        $group->SetState($state);
    }

}
