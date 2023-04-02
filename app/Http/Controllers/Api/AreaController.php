<?php

namespace App\Http\Controllers\Api;

use App\Models\Area;
use App\Models\AreaDisabledDay;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;

class AreaController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return JsonResponse
     */
    public function index()
    {
        $array = [];

        $areas = Area::where('allowed', 1)->get();

        $week = ['segunda', 'terça', 'quarta', 'quinta', 'sexta', 'sábado','domingo'];

        foreach ($areas as $area){
            $days = explode(',', $area['days']);
            $workingDays = $days;

            $daysGroup = [];

            //Adicionando primeiro dia
            $lastDay = intVal(current($days));
            $daysGroup[] = $week[$lastDay];
            array_shift($days);

            //Adicionando dias relevantes
            foreach ($days as $day){
                if(intVal($day) != $lastDay + 1){
                    $daysGroup[] = $week[$lastDay];
                    $daysGroup[] = $week[$day];
                }
                $lastDay = intVal($day);
            }

            //Adicionando último dia
            $daysGroup[] = $week[end($days)];

            //Montando array de dias
            $close = false;
            $dates = '';
            foreach ($daysGroup as $group){
                if($close){
                    $dates .= ' - ' . $group . ',';
                }else{
                    $dates .= $group;
                }
                $close = !$close;
            }
            $dates = explode(',', $dates);
            array_pop($dates);

            //adicionando o período disponível
            $start = date('H:i', strtotime($area['starts_at']));
            $end = date('H:i', strtotime($area['ends_at']));

            foreach ($dates as $key => $value){
                $dates[$key] .= ', de '. $start . ' às ' . $end;
            }

            $disabledDays = [];
            $indexDays = 0;
            for ($i = 0; $i < 7; $i++){
                if(!in_array($i, $workingDays)){
                    $disabledDays[] = $i;
                }else{
                    $workingDays[$indexDays] = intVal($workingDays[$indexDays]);
                    $indexDays++;
                }
            }

            $array['response'][] = [
                'id' => $area['id'],
                'title' => $area['title'],
                'allowed' => $area['allowed'],
                'cover' => 'storage/'.$area['cover'],
                'dates' => $dates,
                'starts_at' => $area['starts_at'],
                'ends_at' => $area['ends_at'],
                'working_days' => $workingDays,
                'disabled_days' => $disabledDays,
                'mode' => $area['mode'],
            ];
        }

        return response()->json($array);
    }

    /**
     * @param int $id
     * @return JsonResponse
     */
    public function getDisabledDates(int $id): JsonResponse
    {
        $array = [];

        $area = Area::find($id);
        if(!$area){
            $array['error'] = "Área inexistente.";
            return response()->json($array, 404);
        }

        $disabledDays = AreaDisabledDay::where("area_id", $id)->get();
        foreach ($disabledDays as $day){
            $array['response'][] = $day;
        }

        return response()->json($array);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param Request $request
     * @return JsonResponse
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->all();

        $validator = Validator::make($request->all(),[
            'allowed' => 'required|numeric',
            'title' => 'required',
            'days' => 'required',
            'starts_at' => 'required|date_format:H:i:s',
            'ends_at' => 'required|date_format:H:i:s',
            'mode' => 'required',
            'cover' => 'mimes:jpeg,jpg,png|required'
        ]);

        if($validator->fails()){
            $array['error'] = $validator->errors()->all();
            return response()->json($array, 400);
        }

        $image = $request->file('cover')->store('images', 'public');

        $area = new Area();
        $area->allowed = $data['allowed'];
        $area->title = $data['title'];
        $area->days = $data['days'];
        $area->starts_at = $data['starts_at'];
        $area->ends_at = $data['ends_at'];
        $area->mode = $data['mode'];
        $area->cover = $image;

        $area->save();

        $array['response'] = $area;


        return response()->json($array);
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return JsonResponse
     */
    public function show($id)
    {
        $response = [];
        $find = Area::find($id);
        if(!$find){
            $response['error'] = "A área informada não existe no sistema!";
            return  response()->json($response, 404);
        }

        $results = Area::find($id)->reservations()
            ->where("date", ">=", date('Y-m-d'))
            ->where("date", "<=", date('Y-m-d', strtotime("+3 months")))
            ->get();

        foreach ($results  as $result){
            $response['data'][] = [
                'reservation' => $result,
                'unit' => $result->unit,
            ];
        }

        return  response()->json($response);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param Request $request
     * @param  int  $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        $array = [];
        $data = $request->all();

        $area = Area::find($id);
        if(!$area){
            $array['error'] = "A área com o id {$id} não foi localizada no sistema";
            return response()->json($array, 404);
        }

        $validator = Validator::make($request->all(),[
            'allowed' => 'required|numeric',
            'title' => 'required',
            'days' => 'required',
            'starts_at' => 'required|date_format:H:i:s',
            'ends_at' => 'required|date_format:H:i:s',
            'mode' => 'required',
            'cover' => 'mimes:jpeg,jpg,png'
        ]);

        if($validator->fails()){
            $array['error'] = $validator->errors()->all();
            return response()->json($array, 400);
        }

        if($request->file('cover')){
            Storage::disk('public')->delete($area->cover);
            $image = $request->file('cover')->store('images', 'public');
            $area->cover = $image;
        }

        $area->allowed = $data['allowed'];
        $area->title = $data['title'];
        $area->days = $data['days'];
        $area->starts_at = $data['starts_at'];
        $area->ends_at = $data['ends_at'];
        $area->mode = $data['mode'];
        $area->save();

        $array['response'] = $area;

        return response()->json($array);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return JsonResponse
     */
    public function destroy(int $id): JsonResponse
    {
        $response = [];
        $user = auth()->user();
        if($user['role'] != 'admin'){
            $response['error'] = "Desculpe! Você não tem permissão para executar esta ação.";
            return response()->json($response, 403);
        }

        $area = Area::find($id);
        if(!$area){
            $response['error'] = "Não foi possível localizar a área informada no banco de dados.";
            return response()->json($response, 404);
        }

        Storage::disk('public')->delete($area->cover);
        $area->delete();

        $response['result'] = "Área excluída com sucesso.";
        return response()->json($response);
    }
}
