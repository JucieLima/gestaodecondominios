<?php

namespace App\Http\Controllers\Api;

use App\Models\Document;

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
