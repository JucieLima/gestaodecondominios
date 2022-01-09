<?php

namespace App\Http\Controllers;

use App\Models\Wall;
use App\Models\WallLike;
use Illuminate\Http\Request;

class WallController extends Controller
{
    public function getAll()
    {
        $array = [
            "error" => '',
            'list' => [],
        ];

        $array['list'] = Wall::all();
        foreach ($array['list'] as $key => $value){
            $array['list'][$key]['liked'] = false;

            $array['list'][$key]['likes'] = WallLike::where("wall", $value['id'])->count();
            $myLike = WallLike::where("wall", $value['id'])->where("user", auth()->user()['id'])->count();
            if($myLike){
                $array['list'][$key]['liked'] = true;
            }

        }

        return $array;
    }

    public function like(int $id)
    {
        $array = ['error' => ''];
        $user = auth()->user();
        $myLike = WallLike::where('wall', $id)->where('user', $user['id'])->count();

        if($myLike){
            WallLike::where('wall', $id)->where('user', $user['id'])->delete();
            $array['liked'] = false;
        }else{
            $like = new WallLike();
            $like->wall = $id;
            $like->user = $user['id'];
            $like->save();
            $array['liked'] = true;
        }

        $array['likes'] = WallLike::where('wall', $id)->count();

        return $array;
    }
}
