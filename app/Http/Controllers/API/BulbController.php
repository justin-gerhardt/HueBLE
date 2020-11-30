<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use Illuminate\Validation\Rule;
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
        # This really feels like it should be a model property but that doesn't seem to be the laravel way
        $this->validate($request, [
            'mac' => ['required', 'regex:/^([a-z0-9]{2}:){5}[a-z0-9]{2}$/i', "unique:App\\Models\\HueBulb,mac"],
            'groups' => ['array'],
            'groups.*' => ['exists:App\\Models\\LightGroup,id']
        ]);
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
        $this->validate($request, [
            'mac' => ['regex:/^([a-z0-9]{2}:){5}[a-z0-9]{2}$/i', Rule::unique(HueBulb::class,'mac')->ignore($bulb->id)],
            'groups' => ['array'],
            'groups.*' => ['exists:App\\Models\\LightGroup,id']
        ]);
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
        $this->validate($request, [
            'lit' => ["required", "boolean"]
        ]);
        $bulb->SetState($request->boolean('lit'));
    }
}
