<?php

namespace App\Http\Controllers;

use App\Models\Document;
use Illuminate\Http\Request;

class DocumentController extends Controller
{
    public function getAll()
    {
        $array = ['error' => ''];
        $docs = Document::all();
        foreach ($docs as $key => $value){
            $docs[$key]['file'] = asset('storage/'.$value['file_url']);
        }

        $array['list'] = $docs;

        return $array;
    }
}
