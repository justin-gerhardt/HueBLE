<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\LightGroup;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class LightGroupController extends Controller
{
    public function index()
    {
        return LightGroup::all()->load("bulbs");
    }


    public function store(Request $request)
    {
        $this->validate($request, [
            'name' => ['required', "unique:App\\Models\\LightGroup,name"],
            'bulbs' => ['array'],
            'bulbs.*' => ['exists:App\\Models\\HueBulb,id']
        ]);
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
        $this->validate($request, [
            'name' => [ Rule::unique(LightGroup::class,'name')->ignore($group->id)],
            'bulbs' => ['array'],
            'bulbs.*' => ['exists:App\\Models\\HueBulb,id']
        ]);
        $group->update($request->all());
        if ($request->has("bulbs")) {
            $group->bulbs()->sync($request->input("bulbs"));
        }
        return $group->load("bulbs");
    }

    public function destroy(LightGroup $group)
    {
        $group->delete();
        return response()->noContent();
    }


    public function isLit(LightGroup $group){
        return response()->json([
            'lit' =>  $group->LitStatus()
        ]);
    }

    public function setState(Request $request, LightGroup $group){
        $this->validate($request, [
            'lit' => ["required", "boolean"]
        ]);
        $group->SetState($request->boolean('lit'));
        return response()->noContent();
    }

}
